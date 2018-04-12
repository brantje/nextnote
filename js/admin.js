function ocNextnoteUrl(url) {
	var newurl = OC.linkTo("nextnote",url).replace("apps/nextnote","index.php/apps/nextnote");
	return newurl;
}

$(document).ready(function() {
	$('#nextnote-sharemode').change(function() {
		var val = $(this).val();
		$.post(ocNextnoteUrl("api/v2/settings"), { field: 'sharemode', value: val }, function (data) {
		});
	});
});

