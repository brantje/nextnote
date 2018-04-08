function ocNextnoteUrl(url) {
	return OC.linkTo("nextnote",url).replace("apps/nextnote","index.php/apps/nextnote");
}

$(document).ready(function() {
	$('#nextnote-view_mode').change(function() {
		var val = $(this).val();
		$.post(ocNextnoteUrl("api/v2.0/settings-user"), { field: 'view_mode', value: val }, function (data) {
		});
	});
});

