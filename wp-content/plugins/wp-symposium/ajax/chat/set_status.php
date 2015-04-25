<?php
error_reporting(0);
session_start();
//MySQL DB settings
include_once('../../../../../wp-config.php');
global $wpdb;


if (is_user_logged_in() && isset($_POST['status'])) {
	
	if($_POST['status'] == 'online'){
		$chat_status = 'online';
		$offlineshift = 0; 
	}else{
		$chat_status = 'offline';
		$offlineshift = time() + 10; // partners waiting 10 second to have offline message
	}								 // if user reload page , automatically go offline state
									 // after page reload go online again
	$result = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->base_prefix."symposium_chat2_users WHERE id = %d", $_POST['own_id']));
	if ($result) {
		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."symposium_chat2_users SET chat_status=%s, offlineshift=%d WHERE id=%d", $chat_status, $offlineshift, $_POST['own_id']));
	} else {						
		$wpdb->query( $wpdb->prepare( "
			INSERT INTO ".$wpdb->base_prefix."symposium_chat2_users 
			( 	id,
				chat_status, 
				offlineshift
			)
			VALUES ( %d, %s, %d )", 
	        array(
	        	$_POST['own_id'], 
	        	$chat_status,
	        	$offlineshift
	        	) 
	        ) );
	}
	print $wpdb->last_query;
	
} else {
	if (isset($_POST['chatbox_status'])) {
		$_SESSION['chatbox_status'] = $_POST['chatbox_status'];
	} else {
		unset($_SESSION['chatbox_status']);
	}
}
?>	