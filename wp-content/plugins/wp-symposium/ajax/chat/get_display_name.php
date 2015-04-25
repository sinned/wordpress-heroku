<?php
error_reporting(0);
session_start();
//MySQL DB settings
include_once('../../../../../wp-config.php');
global $wpdb;


if (is_user_logged_in() && isset($_POST['partner_id'])) {
	
	print $wpdb->get_var($wpdb->prepare("SELECT display_name FROM ".$wpdb->base_prefix."users WHERE ID = %d", $_POST['partner_id']));
	
}
?>