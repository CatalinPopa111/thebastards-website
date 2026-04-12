<div style="float: left;cursor: pointer;width: 49%;text-align: right;margin: 1em 0;">
	<input type="hidden" id="wpfc-auto-cleanup-nonce" value="<?php echo wp_create_nonce("auto-cleanaup"); ?>" />
	<select id="wpfc-auto-cleanup-option">
		<option <?php echo wp_get_schedule("wpfc_db_auto_cleanup") == "" ? "selected=''" : ""; ?> value="off">Auto Cleanup: OFF</option>
		<option <?php echo wp_get_schedule("wpfc_db_auto_cleanup") == "daily" ? "selected=''" : ""; ?> value="daily">Auto Cleanup: Once a Day</option>
		<option <?php echo wp_get_schedule("wpfc_db_auto_cleanup") == "weekly" ? "selected=''" : ""; ?> value="weekly">Auto Cleanup: Once a Week</option>
	</select>
</div>