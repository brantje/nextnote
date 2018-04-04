function ocNextnoteUrl(url) {
	var newurl = OC.linkTo("nextnote",url).replace("apps/nextnote","index.php/apps/nextnote");
	return newurl;
}

$(document).ready(function() {

	$.getJSON(ocNextnoteUrl('api/v2.0/settings'), function (data) {
		console.log(data)
		$.each(data, function (key, value) {
			// $('[name="'+ key +'"]').val(value);
		})
	});

	$('#nextnote-sharemode').change(function() {
		var val = $(this).val();
		$.post(ocNextnoteUrl("api/v2.0/settings"), { field: 'sharemode', value: val }, function (data) {
		});
	});
});

