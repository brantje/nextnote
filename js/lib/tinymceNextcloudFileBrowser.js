var NextCloudFileBrowserDialogue = function(field_name, url, type, win) {
	var fileListUrl = '../files/ajax/list.php?dir=';
	var currentPath;
	console.log(type)
	//type file|image
	OC.dialogs.filepicker('Pick a file', function (file) {
		var filePath = OC.linkToRemote('webdav') + file;

		if(type === 'image') {
			var allowedExtensions = ['png', 'jpg', 'jpeg'];
			var extension = file.split('.').pop();
			if (allowedExtensions.indexOf(extension) < 0) {
				$('#mceNextcloudFileBrowser').dialog('destroy');
				var allowedList = allowedExtensions.join("<li>");
				OCdialogs.message(
					'<div class="message">File extension is not allowed!<br />Allowed extensions:<br /><ul><li>' + allowedList + '</ul></div>',
					'Error',
					'alert',
					OCdialogs.OK_BUTTON,
					null,
					null,
					true
				);
				return;
			}

			var xhr = new XMLHttpRequest();
			xhr.onload = function () {
				var reader = new FileReader();
				reader.onloadend = function () {
					win.document.getElementById(field_name).value = reader.result;
					$('#mceNextcloudFileBrowser').dialog('destroy');

				};
				reader.readAsDataURL(xhr.response);
			};

			xhr.onerror = function () {
				OCdialogs.info('Error while downloading file', 'Oops...');
			};

			xhr.open('GET', filePath);
			xhr.responseType = 'blob';
			xhr.send();
		} else {
			win.document.getElementById(field_name).value = filePath;
			$('#mceNextcloudFileBrowser').dialog('destroy');
		}
	});
	//
};
