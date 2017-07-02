<?php

\OCP\Util::addScript('ownnote', 'admin');

$folder = $_['folder'];
$sharemode = $_['sharemode'];
$l = OCP\Util::getL10N('ownnote');

?>

<div class="section">
    <h2>ownNote</h2>
    
	<label for="ownnote-type"><?php p($l->t("How would you like to store your notes?")); ?></label><br>
	<select id="ownnote-type">
		<option <?php if ($folder == "") echo "selected"; ?> value=""><?php p($l->t("Database only")); ?></option>
		<option <?php if ($folder != "") echo "selected"; ?> value="folder"><?php p($l->t("Database and folder")); ?></option>
	</select><br/>
	<br/>
	<div id="ownnote-folder-settings" style="display: <?php if ($folder != "") echo "block"; else echo "none"; ?>">
		<label for="ownnote-folder"><?php p($l->t("Please enter the folder name you would like to use to store notes, with no slashes.")); ?></label><br>
		<input type="text" style="width: 250pt" name="ownnote-folder" id="ownnote-folder" value="<?php p($folder) ?>" /><br>
		<br/>
	</div>
	
	<label for="ownnote-sharemode"><?php p($l->t("How would you like to handle groups for shared notes?")); ?></label><br>
	<em><?php p($l->t("Synchronized groups merge all shared notes, that have the same groupnames in the same group. Standalone groups get separate groups with their owner in the name. If 'UserA' shares a note in 'GroupX' with 'UserB', that also has a group called 'GroupX', 'Synchronized' will show them in the group 'GroupX' and 'Standalone' will show two groups 'GroupX' and 'GroupX (UserA)' for 'UserB'.")); ?></em><br>
	<select id="ownnote-sharemode">
		<option <?php if ($sharemode == "merge") echo "selected"; ?> value="merge"><?php p($l->t("Synchronized")); ?></option>
		<option <?php if ($sharemode == "standalone") echo "selected"; ?> value="standalone"><?php p($l->t("Standalone")); ?></option>
	</select><br/>
	<br/>

</div>
