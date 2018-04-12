function ocNextnoteUrl(url) {
	return OC.linkTo("nextnote",url).replace("apps/nextnote","index.php/apps/nextnote");
}

$(document).ready(function() {
	$('body').on('change', '#nextnote-view_mode', function() {
		var val = $(this).val();
		$.post(ocNextnoteUrl("api/v2/settings-user"), { key: 'view_mode', value: val }, function (data) {
			OC.Notification.showTemporary('Settings saved!');
		});
	});
	$('body').on('click', '#resetNewUser', function() {
		var val = $(this).val();
		$.post(ocNextnoteUrl("api/v2/settings-user"), { key: 'first_user', value: 1 }, function (data) {
			OC.Notification.showTemporary('Example note created');
			$('#resetNewUser').attr("disabled", "disabled");
		});
	});
});

