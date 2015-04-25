<?php

include_once('../../../../wp-config.php');

global $wpdb, $current_user;
wp_get_current_user();

// Change online status
if ($_POST['action'] == 'symposium_status') {

	global $wpdb, $current_user;
   	$status = $_POST['status'];
   	
   	if ($status == 'true') {

		__wps__update_meta($current_user->ID, 'status', 'offline');

   	} else {

		__wps__update_meta($current_user->ID, 'status', '');

   	}
   	
   	echo "OK";
   	exit;
	
}


// Get friends online
if ($_POST['action'] == 'symposium_getfriendsonline') {
	
	global $wpdb, $current_user;

	if (is_user_logged_in()) {

	   	$inactive = $_POST['inactive'];
	   	$offline = $_POST['offline'];
	   	$me = $current_user->ID;
		$time_now = time();
		$use_chat = $_POST['use_chat'];
		$friends_online = 0;
		$plugin = WPS_PLUGIN_URL;
   	
	   	$return = '';
		
		$get_all = !get_option(WPS_OPTIONS_PREFIX.'_wps_panel_all');

		if ($get_all) {
			$sql = "SELECT u.ID, u.display_name
				FROM ".$wpdb->base_prefix."users u
				LEFT JOIN ".$wpdb->base_prefix."symposium_friends f ON u.ID = f.friend_to WHERE
				   f.friend_accepted = 'on' AND f.friend_from = ".$me;
		} else {
			$sql = "SELECT u.ID, u.display_name
				FROM ".$wpdb->base_prefix."users u
				WHERE u.ID != ".$me;
		}	

		$friends_list = $wpdb->get_results($sql);

		if ($friends_list) {
			$friends_array = array();
			foreach ($friends_list as $friend) {

				$add = array (	
					'ID' => $friend->ID,
					'display_name' => $friend->display_name,
					'last_activity' => __wps__get_meta($friend->ID, 'last_activity'),
					'status' => __wps__get_meta($friend->ID, 'status')
				);
				
				array_push($friends_array, $add);
			}
			$friends = __wps__sub_val_sort($friends_array, 'last_activity', false);
			
		} else {
			
			$friends = false;
		}

		if ($friends) {			
			foreach ($friends as $friend) {
			
				$time_now = time();
				if ($friend['last_activity'] && $friend['status'] != 'offline') {
					$last_active_minutes = __wps__convert_datetime($friend['last_activity']);
					$last_active_minutes = floor(($time_now-$last_active_minutes)/60);
				} else {
					$last_active_minutes = 999999999;
				}
	
				if (!get_option(WPS_OPTIONS_PREFIX.'wps_panel_offline') && ($last_active_minutes >= $offline)) {
					// Don't include offline members, and this member is offline
				} else {

					$return .= "<div style='clear:both; margin-top:4px; overflow: auto;overflow-y:hidden;'>";		
						$return .= "<div style='float: left; width:15px; padding-left:4px;'>";
							if ($last_active_minutes >= $offline) {
								$return .= "<img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/loggedout.gif' alt='Logged Out'>";
							} else {
								$friends_online++;
								if ($last_active_minutes >= $inactive) {
									$return .= "<img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/inactive.gif' alt='Inactive'>";
								} else {
									$return .= "<img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/online.gif' alt='Online'>";
								}
							}
						$return .= "</div>";
						$return .= "<div>";
							if ( $use_chat != 'on' || get_option(WPS_OPTIONS_PREFIX.'_wps_lite') ) {
								if (function_exists('__wps__profile')) {	
									$return .= "<a class='__wps__offline_name' href='".__wps__get_url('profile')."?uid=".$friend['ID']."'>";
									$return .= "<span title='".$friend['ID']."'>".$friend['display_name']."</span>";
									$return .= "</a>";
								}
							} else {
								$return .= "<a href='javascript:void(0);' alt='".$friend['ID']."|".$friend['display_name']."' class='__wps__online_name __wps__chat_user' title='".$friend['ID']."'>".$friend['display_name']."</a>";
							}
						$return .= "</div>";
					$return .= "</div>";
				}
			}
		}

		echo $friends_online."[split]".$return;
		
	}
	
	exit;
	
}

// Get friend requests
if ($_POST['action'] == 'symposium_friendrequests') {

   	global $wpdb, $current_user;	
   	$me = $current_user->ID;

	if (is_user_logged_in()) {

		$sql = "SELECT COUNT(*) FROM ".$wpdb->base_prefix."symposium_friends f WHERE f.friend_to = %d AND f.friend_accepted != 'on'";
		$pending = $wpdb->get_var($wpdb->prepare($sql, $me));
	
		echo $pending;
		
	}
	
	exit;

}

// Get count of unread mail
if ($_POST['action'] == 'symposium_getunreadmail') {

   	global $wpdb, $current_user;	

	if (is_user_logged_in()) {

	   	$me = $current_user->ID;
	   	$sql = "SELECT COUNT(*) FROM ".$wpdb->base_prefix.'symposium_mail'." WHERE mail_to = %d AND mail_in_deleted != 'on' AND mail_read != 'on'";
		$unread_in = $wpdb->get_var($wpdb->prepare($sql, $me));
	
		echo $unread_in;
		
	}
	
	exit;
}



?>
