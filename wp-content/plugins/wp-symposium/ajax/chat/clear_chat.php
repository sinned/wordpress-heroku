<?php
error_reporting(0);

//MySQL DB settings
include_once('../../../../../wp-config.php');
global $wpdb;

if (is_user_logged_in()) {

	//clear all messages
	
	$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."symposium_chat2 WHERE to_id=%s AND from_id=%s", $_POST['to_id'], $_POST['from_id']));
	$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->base_prefix."symposium_chat2 WHERE to_id=%s AND from_id=%s", $_POST['from_id'], $_POST['to_id']));
	
	$cleared_by = $wpdb->get_var($wpdb->prepare("SELECT display_name FROM ".$wpdb->base_prefix."users WHERE ID = %d", $_POST['from_id']));
	$msg = mysql_real_escape_string(strip_tags(sprintf(__('Chat cleared by %s at %s', WPS_TEXT_DOMAIN), $cleared_by, date('H:i'))));
	
	$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."symposium_chat2 (from_id,to_id,message,sent,system_message) VALUES (%s, %s, %s, %s, %s)", $_POST['from_id'], $_POST['to_id'], $msg, time(), 'yes'));
	
	print '1';
	
}
?>