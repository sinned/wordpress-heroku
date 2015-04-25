<?php
	include_once('../../../wp-config.php');
	global $wpdb;
	$tid = $_REQUEST['tid'];
	$filename = $_REQUEST['filename'];
	$sql = "SELECT upload FROM ".$wpdb->base_prefix."symposium_topics_images WHERE tid = %d AND filename = %s";
	$image = $wpdb->get_var($wpdb->prepare($sql, $tid, $filename));	
	header("Content-type: image/jpeg");
	echo stripslashes($image);
//	header("Content-type: application/pdf");
//	echo $image;
?>