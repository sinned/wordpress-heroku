<?php
	include_once('../../../../wp-config.php');
	global $wpdb;
	$uid = $_REQUEST['uid'];
	if (is_numeric($uid)) {
		$sql = "SELECT profile_avatar FROM ".$wpdb->base_prefix."symposium_usermeta WHERE uid = %d";
		$avatar = $wpdb->get_var($wpdb->prepare($sql, $uid));	
		header("Content-type: image/jpeg");
		echo stripslashes($avatar);
	} else {
		echo "Incorrect parameter";
	}
?>