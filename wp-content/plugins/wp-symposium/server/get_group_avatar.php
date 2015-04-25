<?php

	/* Get Group Avatar */

	include_once('../../../../wp-config.php');
	
	global $wpdb;
	
	$sql = "SELECT group_avatar FROM ".$wpdb->prefix."symposium_groups WHERE gid = %d";
	$avatar = $wpdb->get_var($wpdb->prepare($sql, $_REQUEST['gid']));	
	
	header("Content-type: image/jpeg");
	
	echo stripslashes($avatar);

?>