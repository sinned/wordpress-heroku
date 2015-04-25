<?php
error_reporting(0);

//MySQL DB settings
include_once('../../../../../wp-config.php');
global $wpdb,$current_user;


if (is_user_logged_in()) {

	//insert message into chat table
	$msg = str_replace('>', '&gt;', str_replace('<', '&lt;', $_POST['message']));
	if($wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."symposium_chat2 (from_id,to_id,message,sent) VALUES(%s,%s,'%s',%s)",$current_user->ID, $_POST['to_id'], mysql_real_escape_string(strip_tags($msg)), time()))){
		$sql = "DELETE FROM ".$wpdb->base_prefix."symposium_chat2_typing WHERE typing_from = %d";
		$wpdb->query($wpdb->prepare($sql, $current_user->ID));
		$sql = "UPDATE ".$wpdb->base_prefix."usermeta SET meta_value = %s WHERE user_id = %d AND meta_key = %s";
		if ($wpdb->query($wpdb->prepare($sql, date("Y-m-d H:i:s"), $current_user->ID, 'symposium_last_activity'))) {
			print '1';
		} else {
			print $wpdb->last_query;
		}
	}else{
		print $wpdb->last_query;
	}

}

?>