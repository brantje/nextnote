var NextCloudFileBrowserDialogue = function(field_name, url, type, win) {
	var fileListUrl = '../files/ajax/list.php?dir=';
	var currentPath;

	function fileBrowserClickAction(file) {
		console.log(file);
		currentPath = (currentPath === '/' || currentPath === '') ? currentPath : currentPath + '/';
		if (file.type === 'dir' || file.type === 'back') {
			var newPath = (file.type === 'back') ? file.filename : currentPath + file.name;
			listDir(newPath);
			return;
		}

		if (file.type === 'file') {
			var filePath = currentPath + file.name;
			var remotePath = OC.linkToRemote('webdav') + filePath;
			win.document.getElementById(field_name).value = remotePath;
			$('#mceNextcloudFileBrowser').dialog('destroy');
		}
	}


	function listDir(dir) {
		$.get(fileListUrl + dir, function(response) {
			var $browser = $('#mceNextcloudFileBrowser');
			var $fileList = $browser.find('#fileList');
			$fileList.html('');
			var files = response.data.files;
			currentPath = response.data.directory;
			$browser.find('#currentDir').html('Dir index of ' + currentPath);

			if (currentPath !== '/') {
				var path = currentPath.split('/');
				delete path[path.length - 1];
				files.unshift({
					id: null,
					filename: path.join('/'),
					type: 'back',
					name: 'Go back'
				});
			}

			$.each(files, function(key, file) {
				var row = $('<li data-type="' + file.type + '" class="' + file.type + '">' + file.name + '</li>');
				row.click(function() {
					fileBrowserClickAction(file);
				});
				row.appendTo($fileList);
			});
		});
	}

	var browser = $('<div id="mceNextcloudFileBrowser"><div id="currentDir"></div><ul id="fileList"></ul></div>');
	browser.css('zIndex', 99999);
	browser.dialog({
		position: ['middle', 50],
		title: 'Select a file from your Nextcloud',
		create: function() {
			$(browser).css('maxHeight', 500);
		},
		close: function() {
			$('#mceNextcloudFileBrowser').dialog('destroy');
		}
	});
	listDir('/');
	//
};
