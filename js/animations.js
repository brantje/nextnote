$(document).ready(function () {
    var isSingleViewMode = (app_config.user.view_mode === 'single');
    var $body = $('body');
    if(isSingleViewMode){
    	$('#ownnote').find('.view-container').width('200%');
	}
	$body.on('click', '.file.pointer, #new', function (e) {
		if ($(window).width() <= 994 || isSingleViewMode) {
			setTimeout(function () {
				$('#ownnote').animate({scrollLeft: $(window).width()}, 750);
			}, 50);
		}
	});
	$body.on('click', '#canceledit, #grouplist .group', function (e) {
		if ($(window).width() <= 994 || isSingleViewMode) {
			$('#ownnote').animate({scrollLeft: 0}, 750);
		}
	});

	$body.on('click', '.toggle-view-mode', function () {
		if(app_config.user.view_mode === 'single') {
			$('#ownnote').find('.view-container').animate({width: '100%'}, 750);
			$(this).find('i').removeClass('fa-arrows-h').addClass('fa-columns');
			isSingleViewMode = false;
			app_config.user.view_mode = 'col';
		} else if(app_config.user.view_mode === 'col'){
			$('#ownnote').find('.view-container').animate({width: '200%'}, 750);
			$(this).find('i').removeClass('fa-columns').addClass('fa-arrows-h');
			isSingleViewMode = true;
			app_config.user.view_mode = 'single';
		}
	});
});
