<?php
\OCP\Util::addScript('nextnote', 'user');

$l = OCP\Util::getL10N('nextnote');
?>
<div id="nextNoteSettings" class="section">
	<h2 data-anchor-name="nextnote">NextNote Settings</h2>
	<label for="nextnote-view_mode"><?php p($l->t("View mode")); ?></label><br>
	<select id="nextnote-view_mode" name="view_mode">
		<option <?php if ($_['config']['user']['view_mode'] == "col") echo "selected"; ?>
				value="col"><?php p($l->t("Column view")); ?></option>
		<option <?php if ($_['config']['user']['view_mode'] == "single") echo "selected"; ?>
				value="single"><?php p($l->t("Single view")); ?></option>
	</select><br/>
	<br/>
	<?php if ($_['config']['user']['first_user'] === "0") { ?>
			<input type="button" id="resetNewUser" value="<?php p($l->t("Create example note")); ?>">
	<?php } ?>
</div>