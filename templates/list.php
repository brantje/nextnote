<?php
\OCP\Util::addScript('ownnote', 'script');
\OCP\Util::addScript('ownnote', 'ownnote');
\OCP\Util::addScript('ownnote', 'share');
\OCP\Util::addScript('ownnote', 'tinymce/tinymce.min');
\OCP\Util::addStyle('core', 'icons');
\OCP\Util::addStyle('gallery', 'share');
\OCP\Util::addStyle('ownnote', 'style');

$sharemode = \OCP\Config::getAppValue('ownnote', 'sharemode', 'merge');
$disableAnnouncement = \OCP\Config::getAppValue ( 'ownnote', 'disableAnnouncement', '' );
$l = OCP\Util::getL10N ('ownnote');

$ocVersionArray = OCP\Util::getVersion();
$ocVersion = "";
$oci = 0;
$ocl = sizeof ($ocVersionArray);
foreach ( $ocVersionArray as $v ) {
	$ocVersion .= $v;
	$oci ++;
	if ($oci < $ocl)
		$ocVersion .= ".";
}

?>
<div id="app">
	<div id="app-navigation">
		<ul id="grouplist">
		</ul>
	</div>
	<div id="app-content">
		<div id="ownnote"></div>
	</div>
	
	<input type="hidden" name="sharemode" id="sharemode" value="<?php echo $sharemode; ?>"/>
	<input type="hidden" name="disableAnnouncement" id="disableAnnouncement" value="<?php echo $disableAnnouncement; ?>"/>
	<div id="ownnote-l10n">
		l10n["# day ago"] = "<?php p($l->t("# day ago")); ?>";
		l10n["# days ago"] = "<?php p($l->t("# days ago")); ?>";
		l10n["# hour ago"] = "<?php p($l->t("# hour ago")); ?>";
		l10n["# hours ago"] = "<?php p($l->t("# hours ago")); ?>";
		l10n["# minute ago"] = "<?php p($l->t("# minute ago")); ?>";
		l10n["# minutes ago"] = "<?php p($l->t("# minutes ago")); ?>";
		l10n["# month ago"] = "<?php p($l->t("# month ago")); ?>";
		l10n["# months ago"] = "<?php p($l->t("# months ago")); ?>";
		l10n["# second ago"] = "<?php p($l->t("# second ago")); ?>";
		l10n["# seconds ago"] = "<?php p($l->t("# seconds ago")); ?>";
		l10n["# week ago"] = "<?php p($l->t("# week ago")); ?>";
		l10n["# weeks ago"] = "<?php p($l->t("# weeks ago")); ?>";
		l10n["# year ago"] = "<?php p($l->t("# year ago")); ?>";
		l10n["# years ago"] = "<?php p($l->t("# years ago")); ?>";
		l10n["All"] = "<?php p($l->t("All")); ?>";
		l10n["You have no notes to display"] = "<?php p($l->t("You have no notes to display")); ?>";
		l10n["Create new notes or let others share their notes with you"] = "<?php p($l->t("Create new notes or let others share their notes with you")); ?>";
		l10n["An ungrouped file has the same name as a file in this group."] = "<?php p($l->t("An ungrouped file has the same name as a file in this group.")); ?>";
		l10n["Cancel"] = "<?php p($l->t("Cancel")); ?>";
		l10n["Create"] = "<?php p($l->t("Create")); ?>";
		l10n["Dismiss"] = "<?php p($l->t("Dismiss")); ?>";
		l10n["Filename/group already exists."] = "<?php p($l->t("Filename/group already exists.")); ?>";
		l10n["Group"] = "<?php p($l->t("Group")); ?>";
		l10n["Group already exists."] = "<?php p($l->t("Group already exists.")); ?>";
		l10n["Just now"] = "<?php p($l->t("Just now")); ?>";
		l10n["Modified"] = "<?php p($l->t("Modified")); ?>";
		l10n["Name"] = "<?php p($l->t("Name")); ?>";
		l10n["New"] = "<?php p($l->t("New")); ?>";
		l10n["Not grouped"] = "<?php p($l->t("Not grouped")); ?>";
		l10n["Shared with you"] = "<?php p($l->t("Shared with you")); ?>";
		l10n["Shared with others"] = "<?php p($l->t("Shared with others")); ?>";
		l10n["Note"] = "<?php p($l->t("Note")); ?>";
		l10n["Notes"] = "<?php p($l->t("Notes")); ?>";
		l10n["Quick Save"] = "<?php p($l->t("Quick Save")); ?>";
		l10n["Save"] = "<?php p($l->t("Save")); ?>";
	</div>
</div>