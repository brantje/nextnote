/* global Ownnote, escapeHTML */
window.Ownnote = {};
window.Ownnote.Share = {};

(function ($, Ownnote) {
  "use strict";

  /**
   * @typedef {Object} Ownnote.Share.Types.ShareInfo
   * @property {Number} share_type
   * @property {Number} permissions
   * @property {Number} file_source optional
   * @property {Number} item_source
   * @property {String} token
   * @property {String} share_with
   * @property {String} share_with_displayname
   * @property {String} mail_send
   * @property {String} displayname_file_owner
   * @property {String} displayname_owner
   * @property {String} uid_owner
   * @property {String} uid_file_owner
   * @property {String} expiration optional
   * @property {Number} stime
   */

      // copied and stripped out from the old core
  var Share = {
        SHARE_TYPE_USER: 0,
        SHARE_TYPE_GROUP: 1,
        SHARE_TYPE_LINK: 3,
        SHARE_TYPE_EMAIL: 4,
        SHARE_TYPE_REMOTE: 6,

        itemShares: [],

        /**
         * Shares for the currently selected file.
         * (for which the dropdown is open)
         *
         * Key is item type and value is an array or
         * shares of the given item type.
         */
        currentShares: {},

        /**
         * Whether the share dropdown is opened.
         */
        droppedDown: false,

        /**
         *
         * @param path {String} path to the file/folder which should be shared
         * @param shareType {Number} 0 = user; 1 = group; 3 = public link; 6 = federated cloud
         *     share
         * @param shareWith {String} user / group id with which the file should be shared
         * @param publicUpload {Boolean} allow public upload to a public shared folder
         * @param password {String} password to protect public link Share with
         * @param permissions {Number} 1 = read; 2 = update; 4 = create; 8 = delete; 16 = share; 31
         *     = all (default: 31, for public shares: 1)
         * @param callback {Function} method to call back after a successful share creation
         * @param errorCallback {Function} method to call back after a failed share creation
         *
         * @returns {*}
         */
        share: function (noteid, shareType, shareWith, publicUpload, password, permissions, callback, errorCallback) {

          return $.ajax({
            url: OC.generateUrl('/apps/nextnote/') + 'api/v2.0/sharing/shares',
            type: 'POST',
            data: {
              noteid: noteid,
              shareType: shareType,
              shareWith: shareWith,
              publicUpload: publicUpload,
              password: password,
              permissions: permissions
            },
            dataType: 'json'
          }).done(function (result) {
            if (result) {
              var data = {
                id: noteid
              };
              if (callback) {
                callback(data);
              }
            }
          }).fail(function (xhr) {
            var result = xhr.responseJSON;
            if (_.isFunction(errorCallback)) {
              errorCallback(result);
            } else {
              var msg = t('core', 'Error');
              if (result.ocs && result.ocs.meta.message) {
                msg = result.ocs.meta.message;
              }
              OC.dialogs.alert(msg, t('core', 'Error while sharing'));
            }
          });
        },
        /**
         *
         * @param {Number} shareId
         * @param {Function} callback
         */
        unshare: function (shareId, shareType, shareWith, callback) {
          var url = OC.generateUrl('/apps/nextnote/') + 'api/v2.0/sharing/shares/' + shareId;
          url += '?' + $.param({
                'shareType': shareType,
                'shareWith': shareWith
              });
          $.ajax({
            url: url,
            type: 'DELETE'
          }).done(function () {
            if (callback) {
              callback();
            }
          }).fail(function () {
            OC.dialogs.alert(t('core', 'Error while unsharing'), t('core', 'Error'));

          });
        },
        /**
         *
         * @param {Number} shareId
         * @param {Number} permissions
         */
        setPermissions: function (shareId, shareType, shareWith, permissions) {
          var url = OC.generateUrl('/apps/nextnote/') + 'api/v2.0/sharing/shares/' + shareId + '/permissions';
          $.ajax({
            url: url,
            type: 'PUT',
            data: {
              shareType: shareType,
              shareWith: shareWith,
              permissions: permissions
            }
          }).fail(function () {
            OC.dialogs.alert(t('core', 'Error while changing permissions'),
                t('core', 'Error'));
          });
        },
        /**
         *
         * @param {String} itemType
         * @param {String} path
         * @param {String} appendTo
         * @param {String} link
         * @param {Number} possiblePermissions
         * @param {String} filename
         */
        showDropDown: function (itemType, path, appendTo, link, possiblePermissions, filename) {
          // This is a sync AJAX request on the main thread...
          var data = this._loadShares(path);
          var dropDownEl;
          var self = this;
          var html = '<div id="dropdown" class="drop shareDropDown" data-item-type="' + itemType +
              '" data-item-source="' + path + '">';
          if (data !== false && data[0] && !_.isUndefined(data[0].uid_owner) &&
              data[0].uid_owner !== OC.currentUser
          ) {
            html += '<span class="reshare">';
            if (oc_config.enable_avatars === true) {
              html += '<div class="avatar"></div>';
            }

            if (data[0].share_type == this.SHARE_TYPE_GROUP) {
              html += t('core', 'Shared with you and the group {group} by {owner}', {
                group: data[0].share_with,
                owner: data[0].displayname_owner
              });
            } else {
              html += t('core', 'Shared with you by {owner}',
                  {owner: data[0].displayname_owner});
            }
            html += '</span><br />';
            // reduce possible permissions to what the original share allowed
            possiblePermissions = possiblePermissions & data[0].permissions;
          }

          if (possiblePermissions & OC.PERMISSION_SHARE) {
            // Determine the Allow Public Upload status.
            // Used later on to determine if the
            // respective checkbox should be checked or
            // not.
            var publicUploadEnabled = $('#Ownnote').data('allow-public-upload');
            if (typeof publicUploadEnabled == 'undefined') {
              publicUploadEnabled = 'no';
            }
            var allowPublicUploadStatus = false;

            $.each(data, function (key, value) {
              if (value.share_type === self.SHARE_TYPE_LINK) {
                allowPublicUploadStatus =
                    (value.permissions & OC.PERMISSION_CREATE) ? true : false;
                return true;
              }
            });

            var sharePlaceholder = t('core', 'Share with users or groups …');
            if (oc_appconfig.core.remoteShareAllowed) {
              sharePlaceholder = t('core', 'Share with users, groups or remote users …');
            }

            html += '<label for="shareWith" class="hidden-visually">' + t('core', 'Share') +
                '</label>';
            html +=
                '<input id="shareWith" type="text" placeholder="' + sharePlaceholder + '" />';
            if (oc_appconfig.core.remoteShareAllowed) {
              var federatedCloudSharingDoc =
                  '<a target="_blank" class="icon-info svg shareWithRemoteInfo" ' +
                  'href="{docLink}" title="' + t('core',
                      'Share with users, groups or remote users …\'') +
                  '"></a>';
              html += federatedCloudSharingDoc.replace('{docLink}',
                  oc_appconfig.core.federatedCloudShareDoc);
            }
            html += '<span class="shareWithLoading icon-loading-small hidden"></span>';
            html += '<ul id="shareWithList">';
            html += '</ul>';
            var linksAllowed = $('#allowShareWithLink').val() === 'yes';
            var defaultExpireMessage = '';
            if (link && linksAllowed) {
              html += '<div id="link" class="linkShare">';
              html += '<span class="icon-loading-small hidden"></span>';
              html +=
                  '<input type="checkbox" class="checkbox checkbox--right" ' +
                  'name="linkCheckbox" id="linkCheckbox" value="1" />' +
                  '<label for="linkCheckbox">' + t('core', 'Share link') + '</label>';
              html += '<br />';


              if ((itemType === 'folder' || itemType === 'file') &&
                  oc_appconfig.core.defaultExpireDateEnforced) {
                defaultExpireMessage =
                    t('core',
                        'The public link will expire no later than {days} days after it is created',
                        {'days': oc_appconfig.core.defaultExpireDate}) + '<br/>';
              }

              html += '<label for="linkText" class="hidden-visually">' + t('core', 'Link') +
                  '</label>';
              html += '<input id="linkText" type="text" readonly="readonly" />';
              html +=
                  '<input type="checkbox" class="checkbox checkbox--right" ' +
                  'name="showPassword" id="showPassword" value="1" />' +
                  '<label for="showPassword" style="display:none;">' +
                  t('core', 'Password protect') + '</label>';
              html += '<div id="linkPass">';
              html += '<label for="linkPassText" class="hidden-visually">' +
                  t('core', 'Password') + '</label>';
              html += '<input id="linkPassText" type="password" placeholder="' +
                  t('core', 'Choose a password for the public link') + '" />';
              html += '<span class="icon-loading-small hidden"></span>';
              html += '</div>';

              if (itemType === 'folder' && (possiblePermissions & OC.PERMISSION_CREATE) &&
                  publicUploadEnabled === 'yes') {
                html += '<div id="allowPublicUploadWrapper" style="display:none;">';
                html += '<span class="icon-loading-small hidden"></span>';
                html +=
                    '<input type="checkbox" class="checkbox checkbox--right" value="1" name="allowPublicUpload" id="sharingDialogAllowPublicUpload"' +
                    ((allowPublicUploadStatus) ? 'checked="checked"' : '') + ' />';
                html += '<label for="sharingDialogAllowPublicUpload">' +
                    t('core', 'Allow editing') + '</label>';
                html += '</div>';
              }

              var mailPublicNotificationEnabled = $('input:hidden[name=mailPublicNotificationEnabled]').val();
              if (mailPublicNotificationEnabled === 'yes') {
                html += '<form id="emailPrivateLink">';
                html +=
                    '<input id="email" style="display:none; width:62%;" value="" placeholder="' +
                    t('core', 'Email link to person') + '" type="text" />';
                html +=
                    '<input id="emailButton" style="display:none;" type="submit" value="' +
                    t('core', 'Send') + '" />';
                html += '</form>';
              }
            }

            html += '<div id="expiration" >';
            html +=
                '<input type="checkbox" class="checkbox checkbox--right" ' +
                'name="expirationCheckbox" id="expirationCheckbox" value="1" />' +
                '<label for="expirationCheckbox">' +
                t('core', 'Set expiration date') + '</label>';
            html += '<label for="expirationDate" class="hidden-visually">' +
                t('core', 'Expiration') + '</label>';
            html += '<input id="expirationDate" type="text" placeholder="' +
                t('core', 'Expiration date') + '" style="display:none; width:90%;" />';
            if(defaultExpireMessage) {
              html += '<em id="defaultExpireMessage">' + defaultExpireMessage + '</em>';
            }

            html += '</div>';
            dropDownEl = $(html);
            dropDownEl = dropDownEl.appendTo(appendTo);


            // trigger remote share info tooltip
            if (oc_appconfig.core.remoteShareAllowed) {
              $('.shareWithRemoteInfo').tooltip({placement: 'top'});
            }

            //Get owner avatars
            if (oc_config.enable_avatars === true && data !== false && data[0] !== false &&
                !_.isUndefined(data[0]) && !_.isUndefined(data[0].uid_owner)) {
              dropDownEl.find(".avatar").avatar(data[0].uid, 32);
            }

            // Reset item shares
            this.itemShares = [];
            this.currentShares = {};
            if (data) {
              $.each(data, function (index, share) {
                if (share.share_type === self.SHARE_TYPE_LINK) {
                  self.showLink(share.id, share.token, share.share_with);
                } else {
                  if (share.share_with !== OC.currentUser || share.share_type !== self.SHARE_TYPE_USER) {
                    if (share.share_type === self.SHARE_TYPE_REMOTE) {
                      self._addShareWith(share.id,
                          share.share_type,
                          share.share_with,
                          share.share_with_displayname,
                          share.permissions,
                          OC.PERMISSION_READ | OC.PERMISSION_UPDATE |
                          OC.PERMISSION_CREATE,
                          share.mail_send,
                          share.item_source,
                          false);
                    } else {
                      self._addShareWith(share.id,
                          share.share_type,
                          share.share_with,
                          share.share_with_displayname,
                          share.permissions,
                          possiblePermissions,
                          share.mail_send,
                          share.item_source,
                          false);
                    }
                  }
                }
                if (share.expiration != null) {
                  var expireDate = moment(share.expiration, 'YYYY-MM-DD').format(
                      'DD-MM-YYYY');
                  self.showExpirationDate(expireDate, share.stime);
                }
              });
            }
            $('#shareWith').autocomplete({
              minLength: 1,
              delay: 750,
              source: function (search, response) {
                var $loading = $('#dropdown .shareWithLoading');
                $loading.removeClass('hidden');
                // Can be replaced with Sharee API
                // https://github.com/owncloud/core/pull/18234
                $.get('/ocs/v1.php/apps/files_sharing/api/v1/sharees?format=json&search=' + search.term.trim() + '&perPage=200&itemType=' + itemType, {
                  fetch: 'getShareWith',
                  search: search.term.trim(),
                  perPage: 200,
                  itemShares: this.itemShares,
                  itemType: itemType
                }, function (result) {
                  var sharees = result.ocs.data;

                  var results = [];

                  for (var key in sharees) {
                    if (sharees.hasOwnProperty(key)) {
                      if (sharees[key]) {
                        if (!sharees[key].hasOwnProperty('circles')) {
                          results = results.concat(sharees[key])
                        }
                      }
                    }
                  }

                  $loading.addClass('hidden');
                  if (result.ocs.meta.status === 'ok') {
                    $("#shareWith").autocomplete("option", "autoFocus", true);
                    response(results);
                  } else {
                    response();
                  }
                }).fail(function () {
                  $('#dropdown').find('.shareWithLoading').addClass('hidden');
                  OC.Notification.show(t('core', 'An error occured. Please try again'));
                  window.setTimeout(OC.Notification.hide, 5000);
                });
              },
              focus: function (event) {
                event.preventDefault();
              },
              select: function (event, selected) {
                event.stopPropagation();
                var $dropDown = $('#dropdown');
                var itemSource = $dropDown.data('item-source');
                var expirationDate = '';
                if ($('#expirationCheckbox').is(':checked') === true) {
                  expirationDate = $("#expirationDate").val();
                }
                var shareType = selected.item.value.shareType;
                var shareWith = selected.item.value.shareWith;
                $(this).val(shareWith);
                // Default permissions are Edit (CRUD) and Share
                // Check if these permissions are possible
                var permissions = OC.PERMISSION_READ;
                if (shareType === Ownnote.Share.SHARE_TYPE_REMOTE) {
                  permissions =
                      OC.PERMISSION_CREATE | OC.PERMISSION_UPDATE | OC.PERMISSION_READ;
                } else {
                  if (possiblePermissions & OC.PERMISSION_UPDATE) {
                    permissions = permissions | OC.PERMISSION_UPDATE;
                  }
                  if (possiblePermissions & OC.PERMISSION_CREATE) {
                    permissions = permissions | OC.PERMISSION_CREATE;
                  }
                  if (possiblePermissions & OC.PERMISSION_DELETE) {
                    permissions = permissions | OC.PERMISSION_DELETE;
                  }
                  if (oc_appconfig.core.resharingAllowed &&
                      (possiblePermissions & OC.PERMISSION_SHARE)) {
                    permissions = permissions | OC.PERMISSION_SHARE;
                  }
                }

                var $input = $(this);
                var $loading = $dropDown.find('.shareWithLoading');
                $loading.removeClass('hidden');
                $input.val(t('core', 'Adding user...'));
                $input.prop('disabled', true);
                Ownnote.Share.share(
                    itemSource,
                    shareType,
                    shareWith,
                    0,
                    null,
                    permissions,
                    function (data) {
                      var posPermissions = possiblePermissions;
                      if (shareType === Ownnote.Share.SHARE_TYPE_REMOTE) {
                        posPermissions = permissions;
                      }
                      Ownnote.Share._addShareWith(data.id, shareType, shareWith,
                          selected.item.label,
                          permissions, posPermissions, false, itemSource, false);
                    });
                $input.prop('disabled', false);
                $loading.addClass('hidden');
                $('#shareWith').val('');
                return false;
              }
            }).data("ui-autocomplete")._renderItem = function (ul, item) {
              // customize internal _renderItem function to display groups and users
              // differently
              var insert = $("<a>");
              var text = item.label;
              if (item.value.shareType === Ownnote.Share.SHARE_TYPE_GROUP) {
                text = text + ' (' + t('core', 'group') + ')';
              } else if (item.value.shareType === Ownnote.Share.SHARE_TYPE_REMOTE) {
                text = text + ' (' + t('core', 'remote') + ')';
              }
              insert.text(text);
              if (item.value.shareType === Ownnote.Share.SHARE_TYPE_GROUP) {
                insert = insert.wrapInner('<strong></strong>');
              }
              return $("<li>")
              .addClass(
                  (item.value.shareType ===
                  Ownnote.Share.SHARE_TYPE_GROUP) ? 'group' : 'user')
              .append(insert)
              .appendTo(ul);
            };

            if (link && linksAllowed && $('#email').length != 0) {
              $('#email').autocomplete({
                minLength: 1,
                source: function (search, response) {
                  $.get(OC.filePath('core', 'ajax', 'share.php'), {
                    fetch: 'getShareWithEmail',
                    search: search.term
                  }, function (result) {
                    if (result.status == 'success' && result.data.length > 0) {
                      response(result.data);
                    }
                  });
                },
                select: function (event, item) {
                  $('#email').val(item.item.email);
                  return false;
                }
              })
              .data("ui-autocomplete")._renderItem = function (ul, item) {
                return $('<li>')
                .append('<a>' + escapeHTML(item.displayname) + "<br>" +
                    escapeHTML(item.email) + '</a>')
                .appendTo(ul);
              };
            }

          } else {
            html += '<input id="shareWith" type="text" placeholder="' +
                t('core', 'Resharing is not allowed') +
                '" style="width:90%;" disabled="disabled"/>';
            html += '</div>';
            dropDownEl = $(html);
            dropDownEl.appendTo(appendTo);
          }
          dropDownEl.attr('data-item-source-name', filename);
          $('#dropdown').slideDown(OC.menuSpeed, function () {
            Ownnote.Share.droppedDown = true;
          });
          if ($('html').hasClass('lte9')) {
            $('#dropdown input[placeholder]').placeholder();
          }
          $('#shareWith').focus();
          if(!link){
            $('#expiration').hide();
          }
        },
        /**
         *
         * @param callback
         */
        hideDropDown: function (callback) {
          this.currentShares = null;
          $('#dropdown').slideUp(OC.menuSpeed, function () {
            Ownnote.Share.droppedDown = false;
            $('#dropdown').remove();
            if (typeof FileActions !== 'undefined') {
              $('tr').removeClass('mouseOver');
            }
            if (callback) {
              callback.call();
            }
          });
        },
        /**
         *
         * @param id
         * @param token
         * @param password
         */
        showLink: function (id, token, password) {
          var $linkCheckbox = $('#linkCheckbox');
          this.itemShares[this.SHARE_TYPE_LINK] = true;
          $linkCheckbox.attr('checked', true);
          $linkCheckbox.attr('data-id', id);
          var $linkText = $('#linkText');

          var link = parent.location.protocol + '//' + location.host +
              OC.generateUrl('/apps/' + Ownnote.appName + '/s/') + token;

          $linkText.val(link);
          $linkText.slideDown(OC.menuSpeed);
          $linkText.css('display', 'block');
          if (oc_appconfig.core.enforcePasswordForPublicLink === false || password === null) {
            $('#showPassword+label').show();
          }
          if (password != null) {
            $('#linkPass').slideDown(OC.menuSpeed);
            $('#showPassword').attr('checked', true);
            $('#linkPassText').attr('placeholder', '**********');
          }
          $('#expiration').show();
          $('#emailPrivateLink #email').show();
          $('#emailPrivateLink #emailButton').show();
          $('#allowPublicUploadWrapper').show();
        },
        /**
         *
         */
        hideLink: function () {
          $('#linkText').slideUp(OC.menuSpeed);
          $('#defaultExpireMessage').hide();
          $('#showPassword+label').hide();
          $('#linkPass').slideUp(OC.menuSpeed);
          $('#emailPrivateLink #email').hide();
          $('#emailPrivateLink #emailButton').hide();
          $('#allowPublicUploadWrapper').hide();
        },
        /**
         * Displays the expiration date field
         *
         * @param {String} date current expiration date
         * @param {Date|Number|String} [shareTime] share timestamp in seconds, defaults to now
         */
        showExpirationDate: function (date, shareTime) {
          var $expirationDate = $('#expirationDate');
          var $expirationCheckbox = $('#expirationCheckbox');
          var now = new Date();
          // min date should always be the next day
          var minDate = new Date();
          minDate.setDate(minDate.getDate() + 1);
          var datePickerOptions = {
            minDate: minDate,
            maxDate: null
          };
          // TODO: hack: backend returns string instead of integer
          shareTime = this._parseTime(shareTime);
          if (_.isNumber(shareTime)) {
            shareTime = new Date(shareTime * 1000);
          }
          if (!shareTime) {
            shareTime = now;
          }
          $expirationCheckbox.attr('checked', true);
          $expirationDate.val(date);
          $expirationDate.slideDown(OC.menuSpeed);
          $expirationDate.css('display', 'block');
          $expirationDate.datepicker({
            dateFormat: 'dd-mm-yy'
          });
          if (oc_appconfig.core.defaultExpireDateEnforced) {
            $expirationCheckbox.attr('disabled', true);
            shareTime = OC.Util.stripTime(shareTime).getTime();
            // max date is share date + X days
            datePickerOptions.maxDate =
                new Date(shareTime + oc_appconfig.core.defaultExpireDate * 24 * 3600 * 1000);
          }
          if (oc_appconfig.core.defaultExpireDateEnabled) {
            $('#defaultExpireMessage').slideDown(OC.menuSpeed);
          }
          $.datepicker.setDefaults(datePickerOptions);
        },
        /**
         * Get the default Expire date
         *
         * @return {String} The expire date
         */
        getDefaultExpirationDate: function () {
          var expireDateString = '';
          if (oc_appconfig.core.defaultExpireDateEnabled) {
            var date = new Date().getTime();
            var expireAfterMs = oc_appconfig.core.defaultExpireDate * 24 * 60 * 60 * 1000;
            var expireDate = new Date(date + expireAfterMs);
            var month = expireDate.getMonth() + 1;
            var year = expireDate.getFullYear();
            var day = expireDate.getDate();
            expireDateString = year + "-" + month + '-' + day + ' 00:00:00';
          }
          return expireDateString;
        },
        /**
         * Loads all shares associated with a path
         *
         * @param path
         *
         * @returns {Ownnote.Share.Types.ShareInfo|Boolean}
         * @private
         */
        _loadShares: function (noteid) {
          var data = false;
          var url = OC.generateUrl('/apps/nextnote/') + 'api/v2.0/sharing/shares';
          $.ajax({
            url: url,
            type: 'GET',
            data: {
              noteid: noteid,
              shared_with_me: true
            },
            async: false
          }).done(function (result) {
            data = result;
            $.ajax({
              url: url,
              type: 'GET',
              data: {
                noteid: noteid,
                reshares: true
              },
              async: false
            }).done(function (result) {
              data = _.union(data, result);
            })

          });

          if (data === false) {
            OC.dialogs.alert(t('Ownnote', 'Error while retrieving shares'),
                t('core', 'Error'));
          }

          return data;
        },
        /**
         *
         * @param shareId
         * @param shareType
         * @param shareWith
         * @param shareWithDisplayName
         * @param permissions
         * @param possiblePermissions
         * @param mailSend
         *
         * @private
         */
        _addShareWith: function (shareId, shareType, shareWith, shareWithDisplayName, permissions, possiblePermissions, mailSend, itemSource) {
          var shareItem = {
            share_id: shareId,
            share_type: shareType,
            share_with: shareWith,
            share_with_displayname: shareWithDisplayName,
            permissions: permissions,
            itemSource: itemSource,
          };
          if (shareType === this.SHARE_TYPE_GROUP) {
            shareWithDisplayName = shareWithDisplayName + " (" + t('core', 'group') + ')';
          }
          if (shareType === this.SHARE_TYPE_REMOTE) {
            shareWithDisplayName = shareWithDisplayName + " (" + t('core', 'remote') + ')';
          }
          if (!this.itemShares[shareType]) {
            this.itemShares[shareType] = [];
          }
          this.itemShares[shareType].push(shareWith);

          var editChecked = '',
              createChecked = '',
              updateChecked = '',
              deleteChecked = '',
              shareChecked = '';
          if (permissions & OC.PERMISSION_CREATE) {
            createChecked = 'checked="checked"';
            editChecked = 'checked="checked"';
          }
          if (permissions & OC.PERMISSION_UPDATE) {
            updateChecked = 'checked="checked"';
            editChecked = 'checked="checked"';
          }
          if (permissions & OC.PERMISSION_DELETE) {
            deleteChecked = 'checked="checked"';
            editChecked = 'checked="checked"';
          }
          if (permissions & OC.PERMISSION_SHARE) {
            shareChecked = 'checked="checked"';
          }
          var html = '<li style="clear: both;" ' +
              'data-id="' + escapeHTML(shareId) + '"' +
              'data-share-type="' + escapeHTML(shareType) + '"' +
              'data-share-with="' + escapeHTML(shareWith) + '"' +
              'data-item-source="' + escapeHTML(itemSource) + '"' +
              'title="' + escapeHTML(shareWith) + '">';
          var showCrudsButton;
          html +=
              '<a href="#" class="unshare"><img class="svg" alt="' + t('core', 'Unshare') +
              '" title="' + t('core', 'Unshare') + '" src="' +
              OC.imagePath('core', 'actions/delete') + '"/></a>';
          if (oc_config.enable_avatars === true) {
            html += '<div class="avatar"></div>';
          }
          html += '<span class="username">' + escapeHTML(shareWithDisplayName) + '</span>';
          var mailNotificationEnabled = $('input:hidden[name=mailNotificationEnabled]').val();
          if (mailNotificationEnabled === 'yes' &&
              shareType !== this.SHARE_TYPE_REMOTE) {
            var checked = '';
            if (mailSend === 1) {
              checked = 'checked';
            }
            html +=
                '<input id="mail-' + escapeHTML(shareWith) + '" type="checkbox" class="mailNotification checkbox checkbox--right" ' +
                'name="mailNotification" ' +
                checked + ' />';
            html +=
                '<label for="mail-' + escapeHTML(shareWith) + '">' + t('core', 'notify by email') + '</label>';
          }
          html += '<span class="sharingOptionsGroup">';
          if (oc_appconfig.core.resharingAllowed &&
              (possiblePermissions & OC.PERMISSION_SHARE)) {
            html += '<input id="canShare-' + escapeHTML(shareWith) +
                '" type="checkbox" class="permissions checkbox checkbox--right" name="share" ' +
                shareChecked + ' data-permissions="' + OC.PERMISSION_SHARE + '" />';
            html += '<label for="canShare-' + escapeHTML(shareWith) + '">' +
                t('core', 'can share') + '</label>';
          }
          /*if (possiblePermissions & OC.PERMISSION_CREATE ||
              possiblePermissions & OC.PERMISSION_UPDATE ||
              possiblePermissions & OC.PERMISSION_DELETE) {
            html += '<input id="canEdit-' + escapeHTML(shareWith) +
                '" type="checkbox" class="permissions checkbox checkbox--right" name="edit" ' +
                editChecked + ' />';
            html += '<label for="canEdit-' + escapeHTML(shareWith) + '">' +
                t('core', 'can edit') + '</label>';
          }*/
          if (shareType !== this.SHARE_TYPE_REMOTE) {
            showCrudsButton = '<a class="showCruds"><img class="svg" alt="' +
                t('core', 'access control') + '" src="' +
                OC.imagePath('core', 'actions/triangle-s') + '"/></a>';
          }
          //html += '<div class="cruds" style="display:none;">';
          if (possiblePermissions & OC.PERMISSION_UPDATE) {
            html += '<input id="canUpdate-' + escapeHTML(shareWith) +
                '" type="checkbox" class="permissions checkbox checkbox--right" name="update" ' +
                updateChecked + ' data-permissions="' + OC.PERMISSION_UPDATE + '"/>';
            html += '<label for="canUpdate-' + escapeHTML(shareWith) + '">' +
                t('core', 'can edit') + '</label>';
          }
          if (possiblePermissions & OC.PERMISSION_DELETE) {
            html += '<input id="canDelete-' + escapeHTML(shareWith) +
                '" type="checkbox" class="permissions checkbox checkbox--right" name="delete" ' +
                deleteChecked + ' data-permissions="' + OC.PERMISSION_DELETE + '"/>';
            html += '<label for="canDelete-' + escapeHTML(shareWith) + '">' +
                t('core', 'delete') + '</label>';
          }
          html += '</span>';
          //html += '</div>';
          html += '</li>';
          html = $(html).appendTo('#shareWithList');
          if (oc_config.enable_avatars === true) {
            if (shareType === this.SHARE_TYPE_USER) {
              html.find('.avatar').avatar(escapeHTML(shareWith), 32);
            } else {
              //Add sharetype to generate different seed if there is a group and use with
              // the same name
              html.find('.avatar').imageplaceholder(
                  escapeHTML(shareWith) + ' ' + shareType);
            }
          }
          // insert cruds button into last label element
          var lastLabel = html.find('>label:last');
          if (lastLabel.exists()) {
            lastLabel.append(showCrudsButton);
          }
          else {
            html.find('.cruds').before(showCrudsButton);
          }
          if (!this.currentShares[shareType]) {
            this.currentShares[shareType] = [];
          }
          this.currentShares[shareType].push(shareItem);
        },
        /**
         * Parses a string to an valid integer (unix timestamp)
         * @param time
         * @returns {*}
         * @internal Only used to work around a bug in the backend
         * @private
         */
        _parseTime: function (time) {
          if (_.isString(time)) {
            // skip empty strings and hex values
            if (time === '' || (time.length > 1 && time[0] === '0' && time[1] === 'x')) {
              return null;
            }
            time = parseInt(time, 10);
            if (isNaN(time)) {
              time = null;
            }
          }
          return time;
        }
      };

  Ownnote.Share = Share;
})(jQuery, Ownnote);

$(document).ready(function () {

  if (typeof monthNames != 'undefined') {
    // min date should always be the next day
    var minDate = new Date();
    minDate.setDate(minDate.getDate() + 1);
    $.datepicker.setDefaults({
      monthNames: monthNames,
      monthNamesShort: $.map(monthNames, function (v) {
        return v.slice(0, 3) + '.';
      }),
      dayNames: dayNames,
      dayNamesMin: $.map(dayNames, function (v) {
        return v.slice(0, 2);
      }),
      dayNamesShort: $.map(dayNames, function (v) {
        return v.slice(0, 3) + '.';
      }),
      firstDay: firstDay,
      minDate: minDate
    });
  }
  $(document).on('click', 'a.share', function (event) {
    event.stopPropagation();
    if ($(this).data('item-type') !== undefined && $(this).data('path') !== undefined) {
      var itemType = $(this).data('item-type');
      var path = $(this).data('path');
      var appendTo = $(this).parent().parent();
      var link = false;
      var possiblePermissions = $(this).data('possible-permissions');
      if ($(this).data('link') !== undefined && $(this).data('link') == true) {
        link = true;
      }
     // Ownnote.Share.showDropDown(itemType, path, appendTo, link, possiblePermissions);

      if (Ownnote.Share.droppedDown) {
        if (path != $('#dropdown').data('path')) {
          Ownnote.Share.hideDropDown(function() {
            Ownnote.Share.showDropDown(itemType, path, appendTo, link,
                possiblePermissions);
          });
        } else {
          Ownnote.Share.hideDropDown();
        }
      } else {
        Ownnote.Share.showDropDown(itemType, path, appendTo, link, possiblePermissions);
      }
    }
  });

  $(this).click(function (event) {
    var target = $(event.target);
    var isMatched = !target.is('.drop, .ui-datepicker-next, .ui-datepicker-prev, .ui-icon')
        && !target.closest('#ui-datepicker-div').length &&
        !target.closest('.ui-autocomplete').length;
    if (Ownnote.Share.droppedDown && isMatched &&
        $('#dropdown').has(event.target).length === 0) {
        Ownnote.Share.hideDropDown();
    }
  });

  $(document).on('click', '#dropdown .showCruds', function (e) {

    $(this).parent().find('.cruds').toggle();
    return false;
  });

  $(document).on('click', '#dropdown .unshare', function () {
    var $li = $(this).closest('li');
    var shareType = $li.data('share-type');
    var shareWith = $li.attr('data-share-with');
    var shareId = $li.attr('data-id');
    var itemSource = $li.data('item-source');
    var $button = $(this);

    if (!$button.is('a')) {
      $button = $button.closest('a');
    }

    if ($button.hasClass('icon-loading-small')) {
      // deletion in progress
      return false;
    }
    $button.empty().addClass('icon-loading-small');
    Ownnote.Share.unshare(itemSource, shareType, shareWith, function () {
      $li.remove();
      var index = Ownnote.Share.itemShares[shareType].indexOf(shareWith);
      Ownnote.Share.itemShares[shareType].splice(index, 1);
      // updated list of shares
      Ownnote.Share.currentShares[shareType].splice(index, 1);
      // todo: update listing
    });

    return false;
  });

  $(document).on('change', '#dropdown .permissions', function () {
    var $li = $(this).closest('li');
    var checkboxes = $('.permissions', $li);
    if ($(this).attr('name') == 'edit') {
      var checked = $(this).is(':checked');
      // Check/uncheck Create, Update, and Delete checkboxes if Edit is checked/unck
      $(checkboxes).filter('input[name="create"]').attr('checked', checked);
      $(checkboxes).filter('input[name="update"]').attr('checked', checked);
      $(checkboxes).filter('input[name="delete"]').attr('checked', checked);
    } else {
      // Uncheck Edit if Create, Update, and Delete are not checked
      if (!$(this).is(':checked')
          && !$(checkboxes).filter('input[name="create"]').is(':checked')
          && !$(checkboxes).filter('input[name="update"]').is(':checked')
          && !$(checkboxes).filter('input[name="delete"]').is(':checked')) {
        $(checkboxes).filter('input[name="edit"]').attr('checked', false);
        // Check Edit if Create, Update, or Delete is checked
      } else if (($(this).attr('name') == 'create'
          || $(this).attr('name') == 'update'
          || $(this).attr('name') == 'delete')) {
        $(checkboxes).filter('input[name="edit"]').attr('checked', true);
      }
    }
    var permissions = OC.PERMISSION_READ;
    $(checkboxes).filter(':not(input[name="edit"])').filter(':checked').each(
        function (index, checkbox) {
          permissions += $(checkbox).data('permissions');
        });
    Ownnote.Share.setPermissions(
        $li.attr('data-item-source'),
        $li.attr('data-share-type'),
        $li.attr('data-share-with'),
        permissions
    );
  });

  $(document).on('change', '#dropdown #linkCheckbox', function () {
    var $dropDown = $('#dropdown');
    var path = $dropDown.data('item-source');
    var shareId = $('#linkCheckbox').data('id');
    var shareWith = '';
    var publicUpload = 0;
    var $loading = $dropDown.find('#link .icon-loading-small');
    var $button = $(this);

    if (!$loading.hasClass('hidden')) {
      // already in progress
      return false;
    }

    if (this.checked) {
      // Reset password placeholder
      $('#linkPassText').attr('placeholder',
          t('core', 'Choose a password for the public link'));
      // Reset link
      $('#linkText').val('');
      $('#showPassword').prop('checked', false);
      $('#linkPass').hide();
      $('#sharingDialogAllowPublicUpload').prop('checked', false);
      $('#expirationCheckbox').prop('checked', false);
      $('#expirationDate').hide();
      var expireDateString = '';
      // Create a link
      if (oc_appconfig.core.enforcePasswordForPublicLink === false) {
        expireDateString = Ownnote.Share.getDefaultExpirationDate();
        $loading.removeClass('hidden');
        $button.addClass('hidden');
        $button.prop('disabled', true);
        Ownnote.Share.share(
            path,
            Ownnote.Share.SHARE_TYPE_LINK,
            shareWith,
            publicUpload,
            null,
            OC.PERMISSION_READ,
            function (data) {
              $loading.addClass('hidden');
              $button.removeClass('hidden');
              $button.prop('disabled', false);
              Ownnote.Share.showLink(data.id, data.token, null);
            });
      } else {
        $('#linkPass').slideToggle(OC.menuSpeed);
        $('#linkPassText').focus();
      }
      if (expireDateString !== '') {
        Ownnote.Share.showExpirationDate(expireDateString);
      }
    } else {
      // Delete private link
      Ownnote.Share.hideLink();
      $('#expiration').slideUp(OC.menuSpeed);
      if ($('#linkText').val() !== '') {
        $loading.removeClass('hidden');
        $button.addClass('hidden');
        $button.prop('disabled', true);
        Ownnote.Share.unshare(shareId, function () {
          $loading.addClass('hidden');
          $button.removeClass('hidden');
          $button.prop('disabled', false);
          Ownnote.Share.itemShares[Ownnote.Share.SHARE_TYPE_LINK] = false;
        });
      }
    }
  });

  $(document).on('click', '#dropdown #linkText', function () {
    $(this).focus();
    $(this).select();
  });

  // Handle the Allow Public Upload Checkbox
  $(document).on('click', '#sharingDialogAllowPublicUpload', function () {

    // Gather data
    var $dropDown = $('#dropdown');
    var shareId = $('#linkCheckbox').data('id');
    var allowPublicUpload = $(this).is(':checked');
    var $button = $(this);
    var $loading = $dropDown.find('#allowPublicUploadWrapper .icon-loading-small');

    if (!$loading.hasClass('hidden')) {
      // already in progress
      return false;
    }

    // Update the share information
    $button.addClass('hidden');
    $button.prop('disabled', true);
    $loading.removeClass('hidden');
    //(path, shareType, shareWith, publicUpload, password, permissions)
    $.ajax({
      url: OC.linkToOCS('apps/files_sharing/api/v1', 2) + 'shares/' + shareId +
      '?format=json',
      type: 'PUT',
      data: {
        publicUpload: allowPublicUpload
      }
    }).done(function () {
      $loading.addClass('hidden');
      $button.removeClass('hidden');
      $button.prop('disabled', false);
    });
  });

  $(document).on('click', '#dropdown #showPassword', function () {
    $('#linkPass').slideToggle(OC.menuSpeed);
    if (!$('#showPassword').is(':checked')) {
      var shareId = $('#linkCheckbox').data('id');
      var $loading = $('#showPassword .icon-loading-small');

      $loading.removeClass('hidden');
      $.ajax({
        url: OC.linkToOCS('apps/files_sharing/api/v1', 2) + 'shares/' + shareId +
        '?format=json',
        type: 'PUT',
        data: {
          password: null
        }
      }).done(function () {
        $loading.addClass('hidden');
        $('#linkPassText').attr('placeholder',
            t('core', 'Choose a password for the public link'));
      });
    } else {
      $('#linkPassText').focus();
    }
  });

  $(document).on('focusout keyup', '#dropdown #linkPassText', function (event) {
    var linkPassText = $('#linkPassText');
    if (linkPassText.val() != '' && (event.type == 'focusout' || event.keyCode == 13)) {
      var dropDown = $('#dropdown');
      var $loading = dropDown.find('#linkPass .icon-loading-small');
      var shareId = $('#linkCheckbox').data('id');

      $loading.removeClass('hidden');
      $.ajax({
        url: OC.linkToOCS('apps/files_sharing/api/v1', 2) + 'shares/' + shareId +
        '?format=json',
        type: 'PUT',
        data: {
          password: $('#linkPassText').val()
        }
      }).done(function (data) {
        $loading.addClass('hidden');
        linkPassText.val('');
        linkPassText.attr('placeholder', t('core', 'Password protected'));

        if (oc_appconfig.core.enforcePasswordForPublicLink) {
          Ownnote.Share.showLink(data.id, data.token, "password set");
        }
      }).fail(function (xhr) {
        var result = xhr.responseJSON;
        $loading.addClass('hidden');
        linkPassText.val('');
        linkPassText.attr('placeholder', result.data.message);
      });
    }
  });

  $(document).on('click', '#dropdown #expirationCheckbox', function () {
    if (this.checked) {
      Ownnote.Share.showExpirationDate('');
    } else {
      var shareId = $('#linkCheckbox').data('id');
      $.ajax({
        url: OC.linkToOCS('apps/files_sharing/api/v1', 2) + 'shares/' + shareId +
        '?format=json',
        type: 'PUT',
        data: {
          expireDate: ''
        }
      }).done(function () {
        $('#expirationDate').slideUp(OC.menuSpeed);
        if (oc_appconfig.core.defaultExpireDateEnforced === false) {
          $('#defaultExpireMessage').slideDown(OC.menuSpeed);
        }
      }).fail(function () {
        OC.dialogs.alert(t('core', 'Error unsetting expiration date'),
            t('core', 'Error'));
      });
    }
  });

  $(document).on('change', '#dropdown #expirationDate', function () {
    var shareId = $('#linkCheckbox').data('id');
    if(!shareId){
      return;
    }
    $(this).tooltip('hide');
    $(this).removeClass('error');

    $.ajax({
      url: OC.linkToOCS('apps/files_sharing/api/v1', 2) + 'shares/' + shareId +
      '?format=json',
      type: 'PUT',
      data: {
        expireDate: $(this).val()
      }
    }).done(function () {
      if (oc_appconfig.core.defaultExpireDateEnforced === 'no') {
        $('#defaultExpireMessage').slideUp(OC.menuSpeed);
      }
    }).fail(function (xhr) {
      var result = xhr.responseJSON;
      var expirationDateField = $('#dropdown #expirationDate');
      if (result && !result.ocs.meta.message) {
        expirationDateField.attr('original-title',
            t('core', 'Error setting expiration date'));
      } else {
        expirationDateField.attr('original-title', result.ocs.meta.message);
      }
      expirationDateField.tooltip({placement: 'top'});
      expirationDateField.tooltip('show');
      expirationDateField.addClass('error');
    });
  });


  $(document).on('submit', '#dropdown #emailPrivateLink', function (event) {
    event.preventDefault();
    var link = $('#linkText').val();
    var itemType = $('#dropdown').data('item-type');
    var itemSource = $('#dropdown').data('item-source');
    var fileName = $('.last').children()[0].innerText;
    var email = $('#email').val();
    var expirationDate = '';
    if ($('#expirationCheckbox').is(':checked') === true) {
      expirationDate = $("#expirationDate").val();
    }
    if (email != '') {
      $('#email').prop('disabled', true);
      $('#email').val(t('core', 'Sending ...'));
      $('#emailButton').prop('disabled', true);

      $.post(OC.filePath('core', 'ajax', 'share.php'), {
            action: 'email',
            toaddress: email,
            link: link,
            file: fileName,
            itemType: itemType,
            itemSource: itemSource,
            expiration: expirationDate
          },
          function (result) {
            $('#email').prop('disabled', false);
            $('#emailButton').prop('disabled', false);
            if (result && result.status == 'success') {
              $('#email').css('font-weight', 'bold').val(t('core', 'Email sent'));
              setTimeout(function () {
                $('#email').css('font-weight', 'normal').val('');
              }, 2000);
            } else {
              OC.dialogs.alert(result.data.message, t('core', 'Error while sharing'));
            }
          });
    }
  });

  $(document).on('click', '#dropdown input[name=mailNotification]', function () {
    var $li = $(this).closest('li');
    var itemType = $('#dropdown').data('item-type');
    var itemSource = $('a.share').data('item-source');
    var action = '';
    if (this.checked) {
      action = 'informRecipients';
    } else {
      action = 'informRecipientsDisabled';
    }
    var shareType = $li.data('share-type');
    var shareWith = $li.attr('data-share-with');
    $.post(OC.filePath('core', 'ajax', 'share.php'), {
      action: action,
      recipient: shareWith,
      shareType: shareType,
      itemSource: itemSource,
      itemType: itemType
    }, function (result) {
      if (result.status !== 'success') {
        OC.dialogs.alert(t('core', result.data.message), t('core', 'Warning'));
      }
    });

  });
});

$(document).ready(function () {

    $('body').on('click', '.file.pointer', function (e) {
		if($(window).width() <= 994) {
			setTimeout(function () {
				$('#ownnote').animate({scrollLeft: $(window).width()}, 750);
			}, 50);
		}
	});
    $('body').on('click', '#canceledit, #grouplist .group', function (e) {
        if($(window).width() <= 994) {
			$('#ownnote').animate({scrollLeft: 0}, 750);
		}
	});
});
