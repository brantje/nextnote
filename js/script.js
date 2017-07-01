function tinymceInit () {
	tinymce.init({
		selector: "div.editable",
		menubar: false,
		plugins: [
			"advlist autolink lists link charmap print preview anchor",
			"searchreplace visualblocks code fullscreen noneditable",
			"insertdatetime media table contextmenu bdesk_photo autoresize"
		],
		extended_valid_elements: "form[name|id|action|method|enctype|accept-charset|onsubmit|onreset|target],input[id|name|type|value|size|maxlength|checked|accept|src|width|height|disabled|readonly|tabindex|accesskey|onfocus|onblur|onchange|onselect|onclick|onkeyup|onkeydown|required|style],textarea[id|name|rows|cols|maxlength|disabled|readonly|tabindex|accesskey|onfocus|onblur|onchange|onselect|onclick|onkeyup|onkeydown|required|style],option[name|id|value|selected|style],select[id|name|type|value|size|maxlength|checked|width|height|disabled|readonly|tabindex|accesskey|onfocus|onblur|onchange|onselect|onclick|multiple|style]",
		toolbar: "insertfile undo redo | styleselect | bold italic strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link bdesk_photo",
		allow_html_data_urls: true,
		allow_script_urls: true,
		paste_data_images: true,
		width: '100%',
		height: h - 130,
		autoresize_min_height: h - 130,
		autoresize_max_height: h - 130,
		noneditable_editable_class: "editable",
		init_instance_callback: function (editor) {
			resizeFont("13");
			startTimer();
		}
	});
}

function ocUrl (url) {
	var newurl = OC.generateUrl("/apps/ownnote/") + url;
	return newurl;
}

function resizeFont (s) {
	$('#editable_ifr').contents().find("head").append($("<style type='text/css'>  body{font-size:" + s + "px;}  </style>"));
}

var l10n = new Array();
function translate () {
	var t = $('#ownnote-l10n').html();
	eval(t);
}

function trans (s) {
	if (l10n[s])
		return l10n[s];
	else
		return s;
}

function deleteNote (event) {
	var nid = $(this).attr('i');
	$.post(ocUrl("ajax/v0.2/ownnote/ajaxdel"), {nid: nid}, function (data) {
		loadListing();
	});
}

function showNote (id) {
	var nid = $(this).attr('i');
	var uid = $(this).attr('uid');
	var n = $(this).attr('n');
	var g = $(this).attr('g');
	var p = $(this).attr('p');
	$.post(ocUrl("ajax/v0.2/ownnote/ajaxedit"), {nid: nid}, function (data) {
		buildEdit(nid, uid, n, g, p, data, false);
	});
}

function editNote (id) {
	var nid = $(this).attr('i');
	var uid = $(this).attr('uid');
	var n = $(this).attr('n');
	var g = $(this).attr('g');
	var p = $(this).attr('p');
	$.post(ocUrl("ajax/v0.2/ownnote/ajaxedit"), {nid: nid}, function (data) {
		buildEdit(nid, uid, n, g, p, data, true);
	});
}

function addNote () {
	$('#newfile').css('display', 'inline-block');
	$('#new').css('display', 'none');
	$('#newfilename').focus();
}

function cancelNote () {
	$('#newfile').css('display', 'none');
	$('#new').css('display', 'inline-block');
	$('#newfilename').css('color', '#A0A0A0');
	$('#newfilename').val('note title');
}

var h = 200;
function resizeContainer () {
	var o = $('#ownnote').offset();
	h = $(window).height() - o.top;
}

function buildEdit (id, uid, n, g, p, data, editable) {
	resizeContainer();
	var name = htmlQuotes(n);
	var group = htmlQuotes(g);
	var isEditable = editable && (uid === OC.currentUser || (p & OC.PERMISSION_UPDATE));

	var html = "";
	html += "<div id='controls'>";
	html += "	<div id='newfile' class='indent'>";
	html += "		<form id='editform' class='note-title-form'>";
	if (uid === OC.currentUser) {
		html += "			" + trans("Name") + ": <input type='text' class='fileinput' id='editfilename' value='" + name + "'>";
		html += "			&nbsp;&nbsp;" + trans("Group") + ": <select id='groupname'></select>";
		html += "			<input type='text' class='newgroupinput' id='newgroupname' placeholder='group title'>";
	} else {
		html += "			" + trans("Name") + ": <span class='bold'>" + n + "</span>";
		if (g !== "") {
			html += "			&nbsp;&nbsp;" + trans("Group") + ": <span class='bold'>" + g + "</span>";
		}
		html += "			<input type='hidden' class='fileinput' id='editfilename' value='" + name + "'/>";
		html += "			<input type='hidden' class='newgroupinput' id='newgroupname'/>";
	}
	html += "			<input type='hidden' id='originalfilename' value='" + name + "'>";
	html += "			<input type='hidden' id='originalgroup' value='" + group + "'>";
	html += "			<input type='hidden' id='originalid' value='" + id + "'>";
	html += "			<input type='hidden' id='groupname' value='" + group + "'>";
	if (isEditable) {
		html += "			<div id='quicksave' class='button'>" + trans("Quick Save") + "</div>";
		html += "			<div id='save' class='button'>" + trans("Save") + "</div>";
	}
	html += "			<div id='canceledit' class='button'>" + trans("Cancel") + "</div>";
	html += "		</form>";
	html += "	</div>";
	html += "</div>";
	html += "<div class='listingBlank'><!-- --></div>";

	// the note is editable, if the current user is the owner or has edit permissions
	var editableClass = isEditable ? 'editable' : 'mceNonEditable'
	html += "<div id='editable' class='" + editableClass + "'>";

	html += data;
	html += "</div>";
	document.getElementById("ownnote").innerHTML = html;
	tinymceInit();
	buildGroupSelectOptions(g);
	bindEdit();
}

var idle = false;
var idleTime = 0;
var idleInterval;
var origNote;
var checkDuration = 20;
var saveTime = 60;
function startTimer () {
	origNote = tinymce.activeEditor.getContent();
	idleIterval = setInterval(timerIncrement, checkDuration * 1000);
	$(document).mousemove(function (e) {
		notIdle();
	});
	$(document).keypress(function (e) {
		notIdle();
	});
	$('#editable_ifr').contents().find("body").mousemove(function (e) {
		notIdle();
	});
	tinymce.activeEditor.on('keyup', function (e) {
		notIdle();
	});
}

function notIdle () {
	idle = false;
	idleTime = 0;
}

function timerIncrement () {
	idleTime = idleTime + checkDuration;
	if ($('#editable_ifr') && $('#editable_ifr').css('display') == 'block') {
		if (!idle && idleTime >= saveTime) {
			var content = tinymce.activeEditor.getContent();
			if (content != origNote) {
				origNote = content;
				saveNote(true);
			}
			idle = true;
		}
	} else {
		clearInterval(idleInterval);
	}
}

function bindEdit () {
	$("#editform").bind("submit", function () {
		saveNote(false);
	});
	$("#quicksave").bind("click", function () {
		saveNote(true);
	});
	$("#save").bind("click", function () {
		saveNote(false);
	});
	$("#canceledit").bind("click", buildListing);
	$("#groupname").bind("change", checkNewGroup);
	$("#editfilename").bind("change", disableQuickSave);
}

function disableQuickSave () {
	$('#quicksave').css('background-color', 'white');
	$('#quicksave').css('color', '#888888');
	$("#quicksave").off("click");
}

function saveNote (stayinnote) {
	if (stayinnote) {
		$('#quicksave').css('background-color', 'green');
		$('#quicksave').css('color', 'white');
	}
	$('#editfilename').val($('#editfilename').val().replace(/\\/g, '-').replace(/\//g, '-'));
	var editfilename = $('#editfilename').val();
	var editgroup = $('#groupname').val();
	var originalfilename = $('#originalfilename').val();
	var originalgroup = $('#originalgroup').val();
	var originalid = $('#originalid').val();
	var content = tinymce.activeEditor.getContent();
	if (editgroup.toLowerCase() == "all" || editgroup.toLowerCase() == "not grouped") {
		editgroup = "";
	} else if (editgroup == '_new') {
		$('#newgroupname').val($('#newgroupname').val().replace(/\\/g, '-').replace(/\//g, '-'));
		editgroup = $('#newgroupname').val();
	}

	// if rename
	if (editfilename != originalfilename || editgroup != originalgroup) {
		var c = listing.length;
		var exists = false;
		for (i = 0; i < c; i++) {
			if (listing[i].deleted == 0)
				if (listing[i].group == editgroup && listing[i].name == editfilename) {
					exists = true;
					break;
				}
		}
		if (exists) {
			alert(trans("Filename/group already exists."));
		} else
			$.post(ocUrl("ajax/v0.2/ownnote/ajaxren"), {
				id: originalid,
				name: originalfilename,
				group: originalgroup,
				newname: editfilename,
				newgroup: editgroup
			}, function (data) {
				if (data === true) {
					$.post(ocUrl("ajax/v0.2/ownnote/ajaxsave"), {
						id: originalid,
						content: content
					}, function (data) {
						if (!stayinnote)
							loadListing();
						else {
							$('#quicksave').css('background-color', 'rgba(240, 240, 240, 0.9)');
							$('#quicksave').css('color', '#555');
						}
					});
				}
			});
	} else {		// plain new content
		$.post(ocUrl("ajax/v0.2/ownnote/ajaxsave"), {
			id: originalid,
			content: content
		}, function (data) {
			if (!stayinnote)
				loadListing();
			else {
				$('#quicksave').css('background-color', 'rgba(240, 240, 240, 0.9)');
				$('#quicksave').css('color', '#555');
			}
		});
	}
	return false;
}

var listing;
var listingtype = "All";
var sortby = "name";
var sortorder = "ascending";

function htmlQuotes (value, reverse) {
	if (!reverse) {
		var r = value;
		r = r.replace(/\'/g, '&#39;');
		r = r.replace(/\"/g, '&quot;');
		return r;
	} else {
		var r = value;
		r = r.replace(/&#39;/g, "'");
		r = r.replace(/&quot;/g, '"');
		return r;
	}
}

function loadListing () {
	var url = ocUrl("ajax/v0.2/ownnote/ajaxindex");
	$.get(url, function (data) {
		listing = data;
		buildNav(listingtype);
		buildListing();
		if (switchgroup != "") {
			$("[id='link-" + switchgroup + "']").click();
			switchgroup = "";
		}
	});
}

var sort_by = function (field, reverse, primer) {
	var key = primer ?
		function (x) {
			return primer(x[field])
		} :
		function (x) {
			return x[field]
		};
	reverse = [-1, 1][+!!reverse];
	return function (a, b) {
		return a = key(a), b = key(b), reverse * ((a > b) - (b > a));
	}
}

function computeDisplayname (note) {
	if (sharemode == 'merge') {
		return note.group;
	}
	return (note.uid == OC.currentUser) ? note.group : note.group + ' (' + note.uid + ')';
}

function buildListing () {
	// filter the notes by group
	filteredNotes = listing.filter(function (note) {
		switch (listingtype) {
			case 'All':
				return true;
				break;
			case 'Not grouped':
				return note.group === '';
				break;
			case 'Shared with you':
				return note.uid !== OC.currentUser;
				break;
			case 'Shared with others':
				return note.uid == OC.currentUser && note.shared_with.length > 0;
				break;
			default:
				if (sharemode === 'merge') {
					return note.group === listingtype;
				} else {
					return listingtype === computeDisplayname(note);
				}
		}
	});

	var html = "";
	html += "<div id='controls'>";
	html += "	<div id='new' class='button indent'>" + trans("New") + "</div>";
	html += "	<div id='newfile' class='newfile indent'>";
	html += "		<form id='createform' class='note-title-form'>";
	html += "			<input type='text' class='newfileinput' id='newfilename' value='note title'>";
	html += "			<select id='groupname'></select>";
	html += "			<input type='text' class='newgroupinput' id='newgroupname' placeholder='group title'>";
	html += "			<button id='create' class='button'>" + trans("Create") + "</button>";
	html += "			<div id='cancel' class='button'>" + trans("Cancel") + "</div>";
	html += "		</form>";
	html += "	</div>";
	html += "</div>";

	html += "<div class='listingBlank'><!-- --></div>";

	var c = filteredNotes.length;
	if (c == 0) {
		html += "<div id='emptycontent'>";
		html += "	<div class='icon-filetype-text'></div>";
		html += "	<h2>" + trans("You have no notes to display") + "</h2>";
		html += "	<p class='uploadmessage'>" + trans("Create new notes or let others share their notes with you") + "</p>";
		html += "</div>";
	} else {
		html += "<table class='listingSort'>";
		html += "<thead>";
		html += "<tr>";
		if (sortby == "name" && sortorder == "ascending") {
			html += "	<th class='notename filesort notesort'>";
			html += "		<div class='pointer sorttitle' id='sortname'>" + trans("Name") + "</div>";
			html += "		<div class='sortarrow sortup'><!-- --></div>";
			html += "	</th>";
			html += "	<th class='actions'></th>";
			html += "	<th class='info modified notesort'>";
			html += "		<span class='pointer' id='sortmod'>" + trans("Modified") + "</span>";
			html += "	</th>";
			filteredNotes.sort(sort_by('name', true, function (a) {
				return a.toUpperCase()
			}));
		} else if (sortby == "name" && sortorder == "descending") {
			html += "	<th class='notename filesort notesort'>";
			html += "		<div class='pointer sorttitle' id='sortname'>" + trans("Name") + "</div>";
			html += "		<div class='sortarrow sortdown'><!-- --></div>";
			html += "	</th>";
			html += "	<th class='actions'></th>";
			html += "   <th class='info modified notesort'>";
			html += "       <span class='pointer' id='sortmod'>" + trans("Modified") + "</span>";
			html += "   </th>";
			filteredNotes.sort(sort_by('name', false, function (a) {
				return a.toUpperCase()
			}));
		} else if (sortby == "mod" && sortorder == "ascending") {
			html += "	<th class='notename filesort notesort'>";
			html += "		<div class='pointer sorttitle' id='sortname'>" + trans("Name") + "</div>";
			html += "	</th>";
			html += "	<th class='actions'></th>";
			html += "	<th class='info modified notesort'>";
			html += "		<div class='pointer sorttitle' id='sortmod'>" + trans("Modified") + "</div>";
			html += "		<div class='sortarrow sortup'><!-- --></div>";
			html += "	</th>";
			filteredNotes.sort(sort_by('mtime', false, parseInt));
		} else if (sortby == "mod" && sortorder == "descending") {
			html += "	<th class='notename filesort notesort'>";
			html += "		<div class='pointer sorttitle' id='sortname'>" + trans("Name") + "</div>";
			html += "	</th>";
			html += "	<th class='actions'></th>";
			html += "	<th class='info modified notesort'>";
			html += "		<div class='pointer sorttitle' id='sortmod'>" + trans("Modified") + "</div>";
			html += "		<div class='sortarrow sortdown'><!-- --></div>";
			html += "	</th>";
			filteredNotes.sort(sort_by('mtime', true, parseInt));
		}
		html += "</tr>";
		html += "</thead>";

		for (i = 0; i < c; i++) {
			if (filteredNotes[i].deleted == 0) {
				var fileclass = 'modified';
				var name = htmlQuotes(filteredNotes[i].name);
				var group = htmlQuotes(filteredNotes[i].group);
				var file = name;
				if (group != '')
					file = "[" + group + "] " + name;
				if (filteredNotes[i].timediff < 30)
					fileclass = 'modified latestfile';
				html += "<tr class='listing'>";
				html += "	<td id='" + file + "' i='" + filteredNotes[i].id + "' n='" + name + "' g='" + group + "' title='" + name + "' p='" + filteredNotes[i].permissions + "' uid='" + filteredNotes[i].uid + "' class='file pointer'>" + name + "</td>";

				html += "	<td class='actions'>";

				// delete
				if (filteredNotes[i].uid === OC.currentUser || (filteredNotes[i].permissions & OC.PERMISSION_DELETE)) {
					html += "		<div id='" + file + "-delete' i='" + filteredNotes[i].id + "' n='" + name + "' g='" + group + "' class='buttons delete delete-note pointer'></div>";
				}

				// edit
				if (filteredNotes[i].uid === OC.currentUser || (filteredNotes[i].permissions & OC.PERMISSION_UPDATE)) {
					html += "		<div id='" + file + "-edit' i='" + filteredNotes[i].id + "' n='" + name + "' g='" + group + "' p='" + filteredNotes[i].permissions + "' uid='" + filteredNotes[i].uid + "' class='buttons edit edit-note pointer'></div>";
				}

				// share
				html += "		<div id='" + file + "-share' i='" + filteredNotes[i].id + "' n='" + name + "' g='" + group + "' class='share-note share pointer'>";
				html += "		<a class='share' data-item-type='ownnote' data-item=" + filteredNotes[i].id + " data-possible-permissions='31' data-path='" + filteredNotes[i].id + "'>";
				if (filteredNotes[i].uid != OC.currentUser) {
					html += "	       <span class='share-owner pointer'>" + filteredNotes[i].uid + "</span>";
				} else if (filteredNotes[i].shared_with.length > 0) {
					html += "	       <span class='share-owner pointer'>" + trans("Shared") + "</span>";
				}
				html += "			<div id='" + file + "' i='" + filteredNotes[i].id + "' n='" + name + "' g='" + group + "' class='buttons share share-note pointer'></div>";
				html += "		</a>";
				html += "		</div>";

				html += "	</td>";

				html += "	<td class='info'>";

				if (filteredNotes[i].timestring != '')
					html += "		<div class='" + fileclass + "'>" + filteredNotes[i].timestring + "</div>";
				else
					html += "		<div class='" + fileclass + "'>" + trans("Just now") + "</div>";
				html += "	</td>";

				html += "</tr>";
			}
		}

		html += "</table>";

	}
	document.getElementById("ownnote").innerHTML = html;
	$('#newfilename').css('color', '#A0A0A0');
	buildGroupSelectOptions(listingtype);
	bindListing();
}

function buildGroupSelectOptions (current) {
	var $select = $('select#groupname');
	$select.append($('<option value="">Not grouped</option>'));
	$select.append($('<option>').attr('value', '_new').text('New group'));
	$.each(groups, function (groupname, group) {
		var option = $('<option>').attr('value', group.displayname).text(group.displayname);
		if (group.displayname == current) {
			option.attr('selected', 'selected');
		}
		$select.append(option);
	});
}

function bindListing () {
	$(".file").bind("click", showNote);
	$(".edit-note").bind("click", editNote);
	$(".delete-note").bind("click", deleteNote);
	$("#sortname").bind("click", sortName);
	$("#sortmod").bind("click", sortMod);
	$("#new").bind("click", addNote);
	$("#cancel").bind("click", cancelNote);
	$("#createform").bind("submit", createNote);
	$("#groupname").bind("change", checkNewGroup);
	$("#newfilename").bind("focus", newNote);
}

function checkNewGroup () {
	var selectVal = $('select#groupname').val();
	if (selectVal == '_new') {
		$('#newgroupname').css('display', 'inline-block');
		$('#newgroupname').focus();
	} else {
		$('#newgroupname').css('display', 'none');
	}
	disableQuickSave();
}

function newNote () {
	$('#newfilename').css('color', '#000');
	var v = $('#newfilename').val();
	if (v == 'note title')
		$('#newfilename').val('');
}

function createNote () {
	var name = $('#newfilename').val();
	var group = $('#groupname').val();
	if (group == '_new') {
		group = $('#newgroupname').val();
	}
	cancelNote();
	$.post(ocUrl("ajax/v0.2/ownnote/ajaxcreate"), {
		name: name,
		group: group
	}, function (data) {
		loadListing();
	});
	return false;
}

function sortName () {
	if (sortby == "name")
		if (sortorder == "ascending")
			sortorder = "descending";
		else
			sortorder = "ascending";
	else {
		sortby = "name";
		sortorder = "ascending";
	}
	buildListing();
}

function sortMod () {
	if (sortby == "mod")
		if (sortorder == "ascending")
			sortorder = "descending";
		else
			sortorder = "ascending";
	else {
		sortby = "mod";
		sortorder = "ascending";
	}
	buildListing();
}

function buildNavItem (name, count, active, editable) {
	editable = typeof editable !== 'undefined' ? editable : true;
	var html = '';
	var a = ''
	var n = htmlQuotes(name);
	if (active) a = " active";
	if (name == "All" || name == "Not grouped" || name == "Shared with you" || name == "Shared with others") {
		html += '<li class="group' + a + '" data-type="all">';
		html += '	<a class="name" id="link-' + n + '" role="button" title="' + n + '">' + htmlQuotes(trans(name)) + '</a>';
	} else {
		html += '<li id="group-' + n + '-edit" class="group editing">';
		html += '	<ul class="oc-addnew open" style="display: inline-block; width: auto; height: auto;" aria-disabled="false">';
		html += '		<li>';
		html += '			<input id="edit-' + n + '-text" class="oc-addnew-name" type="text" value="' + n + '" style="display: inline;">';
		html += '			<button id="edit-' + n + '" class="new-button primary icon-checkmark-white" style="display: block;"></button>';
		html += '		</li>';
		html += '	</ul>';
		html += '</li>';
		html += '<li id="group-' + n + '" class="group' + a + '" data-type="category">';
		html += '	<a class="name" id="link-' + n + '" role="button" title="' + n + '">' + n + '</a>';
	}
	html += '	<span class="utils">';
	if (editable) {
		html += '		<a class="icon-rename action edit tooltipped rightwards" group="' + n + '" original-title=""></a>';
		html += '		<a class="icon-delete action delete tooltipped rightwards" group="' + n + '" original-title=""></a>';
	}
	html += '		<span class="action numnotes">' + count + '</span>';
	html += '	</span>';
	html += '</li>';
	return html;
}

function sortNav () {
	var list = [];
	for (var j = 0; j < groups.length; j++)
		list.push({'group': groups[j], 'count': counts[j]});
	list.sort(function (a, b) {
		return ((a.group < b.group) ? -1 : ((a.group == b.group) ? 0 : 1));
	});
	for (var k = 0; k < list.length; k++) {
		groups[k] = list[k].group;
		counts[k] = list[k].count;
	}
}

var groups = {};
function buildNav (a) {
	groups = {};
	var html = '';
	var c = listing.length;
	var uncat = 0;

	var sharedin = new Array();
	var sharedout = new Array();

	for (i = 0; i < c; i++) {
		if (listing[i].group != '') {
			var groupname = computeDisplayname(listing[i]);

			if (!(groupname in groups)) {
				groups[groupname] = {
					name: listing[i].group,
					displayname: groupname,
					count: 1,
					owner: listing[i].uid
				};
			} else {
				groups[groupname].count += 1;
			}
		} else {
			uncat++;
		}
		// shared with you
		if (listing[i].uid !== OC.currentUser) {
			sharedin.push(listing[i].id);
		}
		// shared with others
		if (listing[i].uid === OC.currentUser && listing[i].shared_with.length > 0) {
			sharedout.push(listing[i].id);
		}
	}
	sortNav();

	// add Sharing groups
	html += buildNavItem("Shared with you", sharedin.length, a == "Shared with you", false);
	html += buildNavItem("Shared with others", sharedout.length, a == "Shared with others", false);

	var groupnames = Object.keys(groups);
	html += buildNavItem('All', c, a == "All");
	if (groupnames.length > 0) {
		html += buildNavItem('Not grouped', uncat, a == "Not grouped");
	}

	// build groups
	$.each(groupnames, function (index, groupname) {
		var group = groups[groupname];
		html += buildNavItem(group.displayname, group.count, a == group.displayname, group.owner == OC.currentUser);
	});

	html += "<div id='announcement-container'></div>";
	$('#grouplist').html(html);
	bindNav();
}

function setCookie (cname, cvalue, exdays) {
	var d = new Date();
	d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
	var expires = "expires=" + d.toUTCString();
	document.cookie = cname + "=" + cvalue + "; " + expires;
}

function getCookie (cname) {
	var name = cname + "=";
	var ca = document.cookie.split(';');
	for (var i = 0; i < ca.length; i++) {
		var c = ca[i];
		while (c.charAt(0) == ' ') c = c.substring(1);
		if (c.indexOf(name) == 0) return c.substring(name.length, c.length);
	}
	return "";
}

function dismissAnnouncement () {
	setCookie("dismissedAnnouncement", $('#announcement').html().replace(/\n/g, ''), 30);
	$('#announcement-container').html('');
}

function selectGroup () {
	buildNav(this.title);
	listingtype = this.title;
	buildListing();
	if ($("#app-navigation-toggle").css("display") == "block") {
		setTimeout(closeNav, 250);
	}
}

function closeNav () {
	if ($("#app-navigation-toggle").css("display") == "block") {
		$("#app-navigation-toggle").click();
	}
}

function bindNav () {
	$(".name").bind("click", selectGroup);
	$(".icon-delete").bind("click", deleteGroup);
	$(".icon-rename").bind("click", editGroup);
	$(".new-button").bind("click", saveGroup);
}

function deleteGroup () {
	var g = $(this).attr('group');
	var c = listing.length;
	var exists = false;
	for (i = 0; i < c; i++)
		if (listing[i].deleted == 0)
			if (listing[i].group.toLowerCase() == g.toLowerCase())
				for (j = 0; j < c; j++)
					if (listing[j].deleted == 0)
						if (listing[j].group == '' && listing[i].name.toLowerCase() == listing[j].name.toLowerCase()) {
							exists = true;
							break;
						}
	if (exists)
		alert(trans("An ungrouped file has the same name as a file in this group."));
	else
		$.post(ocUrl("ajax/v0.2/ownnote/ajaxdelgroup"), {group: g}, function (data) {
			switchgroup = "All";
			loadListing();
		});
}

var cg = "";
var switchgroup = "";

function editGroup () {
	var g = $(this).attr('group');
	cg = g;
	var i = 'group-' + g;
	$("[id='" + i + "']").css("display", "none");
	$("[id='" + i + "-edit']").css("display", "inline-block");
}

function saveGroup () {
	var v = $("[id='" + this.id + "-text']").val();
	var c = listing.length;
	if (v != cg && v.toLowerCase() != "all" && v.toLowerCase() != "not grouped") {
		var exists = false;
		for (i = 0; i < c; i++)
			if (listing[i].deleted == 0)
				if (listing[i].group.toLowerCase() == v.toLowerCase()) {
					exists = true;
					break;
				}
		if (exists) {
			alert(trans("Group already exists."));
		} else {
			$.post(ocUrl("ajax/v0.2/ownnote/ajaxrengroup"), {
				group: cg,
				newgroup: v
			}, function (data) {
				switchgroup = v;
				cg = "";
				loadListing();
			});
		}
	} else {
		switchgroup = v;
		cg = "";
		loadListing();
	}
}

var sharemode = "merge";
var disableAnnouncement = "";
function getSettings () {
	sharemode = $('#sharemode').val();
	disableAnnouncement = $('#disableAnnouncement').val();
}

$(document).ready(function () {
	$.ajaxSetup({cache: false});
	translate();
	getSettings();
	loadListing();
});
	

