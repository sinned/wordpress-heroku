<?php
	include_once('../../../wp-config.php');
	global $wpdb;
	$iid = $_REQUEST['iid'];
	$size = $_REQUEST['size'];
	$sql = "SELECT ".$size." FROM ".$wpdb->base_prefix."symposium_gallery_items WHERE iid = %d";
	$image = $wpdb->get_var($wpdb->prepare($sql, $iid));	
	header("Content-type: image/jpeg");
	echo stripslashes($image);
?>