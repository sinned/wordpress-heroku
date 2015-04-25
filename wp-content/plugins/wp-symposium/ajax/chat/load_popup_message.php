<?php
error_reporting(0);

//MySQL DB settings
include_once('../../../../../wp-config.php');
global $wpdb;

if (is_user_logged_in()) {

	$sent_time = time() - 7200; // Hide old messages
	
	//check if there is an unreceived message for current user
	$row = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$wpdb->base_prefix."symposium_chat2 WHERE to_id=%s AND recd='0' GROUP BY from_id LIMIT 0,1", $_POST['own_id']));

	if($row){
			
		//return user id and username
		$row2 = $wpdb->get_var($wpdb->prepare("SELECT display_name FROM ".$wpdb->base_prefix."users WHERE id=%s", $row->from_id));
		
		print $row->from_id.';;;'.$row2;
	
	}else{
		print '0';
	}
	
}
?>