function ocOwnnoteUrl (url) {
	var newurl = OC.linkTo("ownnote", url).replace("apps/ownnote", "index.php/apps/ownnote");
	return newurl;
}

$(document).ready(function () {
	$('#ownnote-folder').change(function () {
		var val = $(this).val();
		$.post(ocOwnnoteUrl("ajax/v0.2/ajaxsetval"), {
			field: 'folder',
			value: val
		}, function (data) {
			console.log('response', data);
		});
	});
	$('#ownnote-type').change(function () {
		var val = $(this).val();
		if (val == "") {
			$('#ownnote-folder').val('');
			$('#ownnote-folder-settings').css('display', 'none');
			$.post(ocOwnnoteUrl("ajax/v0.2/ajaxsetval"), {
				field: 'folder',
				value: ''
			}, function (data) {
				console.log('response', data);
			});
		} else
			$('#ownnote-folder-settings').css('display', 'block');
	});
	$('#ownnote-disableannouncement').change(function () {
		var da = "";
		var c = $(this).is(':checked');
		if (c)
			da = "checked";
		$.post(ocOwnnoteUrl("ajax/v0.2/ajaxsetval"), {
			field: 'disableAnnouncement',
			val: da
		}, function (data) {
			console.log('response', data);
		});
	});
	$('#ownnote-sharemode').change(function () {
		var val = $(this).val();
		$.post(ocOwnnoteUrl("ajax/v0.2/ajaxsetval"), {
			field: 'sharemode',
			value: val
		}, function (data) {
		});
	});
});

