<?php
/*
WP Symposium Notification Alerts
Description: <strong>BRONZE PLUGIN</strong>. Updates a menu item (or DIV) with alerts/notifications for the logged in member.
*/


/* ====================================================================== MAIN =========================================================================== */

// Get constants
require_once(dirname(__FILE__).'/default-constants.php');

function __wps__news_main() {
	// This function is used to information Wordpress that it is activated.
	// Ties in with __wps__add_news_to_admin_menu() function below.		
}

// ----------------------------------------------------------------------------------------------------------------------------------------------------------

function __wps__news_add($author, $subject, $news) {

	global $wpdb,$current_user;

	if (	$wpdb->query( $wpdb->prepare( "
			INSERT INTO ".$wpdb->base_prefix."symposium_news
			( 	author,
				subject, 
				added,
				news
			)
			VALUES ( %d, %d, %s, %s )", 
	        array(
	        	$author,
				$subject, 
	        	date("Y-m-d H:i:s"),
	        	$news
	        	) 
        	) ) 
	) {
		return "OK";
	} else { 
		return $wpdb->last_query;
	}

}


/* ===================================================================== ADMIN =========================================================================== */


// ----------------------------------------------------------------------------------------------------------------------------------------------------------

function __wps__news_init()
{
	if (!is_admin()) {
	}
}
function __wps__add_news_footer() {
	echo '<div id="__wps__news_polling" style="display:none">'.get_option(WPS_OPTIONS_PREFIX."_news_polling").'</div>';
}
add_action('init', '__wps__news_init');
add_action('wp_footer', '__wps__add_news_footer');

// ----------------------------------------------------------------------------------------------------------------------------------------------------------

/* ====================================================== HOOKS/FILTERS INTO WORDPRESS/WP Symposium ====================================================== */

// Add "Alerts" to admin menu via hook
function __wps__add_news_to_admin_menu()
{
	$hidden = get_option(WPS_OPTIONS_PREFIX.'_long_menu') == "on" ? '_hidden': '';
	add_submenu_page('symposium_debug'.$hidden, __('Alerts', WPS_TEXT_DOMAIN), __('Alerts', WPS_TEXT_DOMAIN), 'manage_options', WPS_DIR.'/news_admin.php');
}
add_action('__wps__admin_menu_hook', '__wps__add_news_to_admin_menu');

// ----------------------------------------------------------------------------------------------------------------------------------------------------------

function __wps__news_offsets() {
	// Place Alerts offset settings in DOM so accessible via Javascript
 	echo "<div id='__wps__news_x_offset' style='display:none'>".get_option(WPS_OPTIONS_PREFIX."_news_x_offset")."</div>";
	echo "<div id='__wps__news_y_offset' style='display:none'>".get_option(WPS_OPTIONS_PREFIX."_news_y_offset")."</div>";
}
add_action('wp_footer', '__wps__news_offsets');

// ----------------------------------------------------------------------------------------------------------------------------------------------------------

// Add [symposium-alerts] shortcode for history of news items
function __wps__alerts_history($attr) {	

	global $wpdb, $current_user;
	$html = "";
	
	if (is_user_logged_in()) {

		// Get link to profile page
		$profile_url = __wps__get_url('profile');
		if (strpos($profile_url, '?') !== FALSE) {
			$q = "&";
		} else {
			$q = "?";
		}
		
		$limit = isset($attr['count']) ? $attr['count'] : 50;

	
		// Wrapper
		$html .= "<div class='__wps__wrapper'>";

		$sql = "SELECT n.*, u.display_name FROM ".$wpdb->base_prefix."symposium_news n 
			LEFT JOIN ".$wpdb->base_prefix."users u ON n.author = u.ID 
			WHERE subject = %d 
			ORDER BY added DESC LIMIT 0,%d";
		$news = $wpdb->get_results($wpdb->prepare($sql, $current_user->ID, $limit));

		$shown_heading_today = $shown_heading_yesterday = $shown_heading_recent = $shown_heading_lastweek = $shown_heading_thismonth = $shown_heading_lastmonth = $shown_heading_old = false;
		
		if ($news) {
			foreach ($news as $item) {

				$date = strtotime($item->added);
				$difference = (time() - $date) + 1;
				$days = floor($difference/86400);
				$months = floor($difference/2628000);
	
				$heading = '';
				if (!$shown_heading_today && $days == 0) {
					$heading = __('Today', WPS_TEXT_DOMAIN);
					$shown_heading_today = true;
				}
				if (!$shown_heading_yesterday && $days == 1) {
					$heading = __('Yesterday', WPS_TEXT_DOMAIN);
					$shown_heading_yesterday = true;
				}
				if (!$shown_heading_recent && $days >= 2 && $days <= 6) {
					$heading = __('Recently', WPS_TEXT_DOMAIN);
					$shown_heading_recent = true;
				}
				if (!$shown_heading_lastweek && $days >= 7 && $days <= 13) {
					$heading = __('Last week', WPS_TEXT_DOMAIN);
					$shown_heading_lastweek = true;
				}
				if (!$shown_heading_thismonth && $days >= 14 && $months == 0) {
					$heading = __('This month', WPS_TEXT_DOMAIN);
					$shown_heading_thismonth = true;
				}
				if (!$shown_heading_lastmonth && $months == 1) {
					$heading = __('Last month', WPS_TEXT_DOMAIN);
					$shown_heading_lastmonth = true;
				}
				if (!$shown_heading_old && $months > 1) {
					$heading = __('Old', WPS_TEXT_DOMAIN);
					$shown_heading_old = true;
				}
					
				if ($heading) {
					$html .= "<div class='topic-post-header' style='margin-bottom:10px'>";
						$html .= $heading;
					$html .= "</div>";
				}
				
				$html .= "<div class='__wps__news_history_row'>";
					$html .= "<div class='__wps__news_history_avatar'>";
					$html .= '<a href="'.$profile_url.$q.'uid='.$item->author.'">'.get_avatar($item->author, 40).'</a>';
					$html .= '</div>';
					$html .= "<div class='__wps__news_history_avatar'>";
					$html .= $item->news;
					$html .= "<br /><span class='__wps__news_history_ago'>".__wps__time_ago($item->added)."</span>";
					$html .= ' '.__('by', WPS_TEXT_DOMAIN).' <a href="'.$profile_url.$q.'uid='.$item->author.'">'.stripslashes($item->display_name).'</a>';
					$html .= "</div>";
				$html .= "</div>";
			}
		} else {

			$html .= __("Nothing to show yet.", WPS_TEXT_DOMAIN);

		}
		$html .= "</div>";
		// End Wrapper
	
		$html .= "<div style='clear: both'></div>";
	
		// Clear read news items
		$wpdb->query("UPDATE ".$wpdb->base_prefix."symposium_news SET new_item = '' WHERE subject = ".$current_user->ID);

	} else {
		
		$html .= __("Please login, thank you.", WPS_TEXT_DOMAIN);
		
	}

	// Send HTML
	return $html;

}
if (!is_admin()) {
	add_shortcode(WPS_SHORTCODE_PREFIX.'-alerts', '__wps__alerts_history');  
}

/* ====================================================== ALERTS (if available) ====================================================== */


// Add news item that a poke was sent
function __wps__send_poke($message_to, $message_from, $from_name, $poke, $cid) {
	$url = __wps__get_url('profile');
	$message = $from_name.__(' has sent you a ', WPS_TEXT_DOMAIN).$poke;
	__wps__news_add($message_from, $message_to, "<a href='".$url.__wps__string_query($url)."uid=".$message_from."&post=".$cid."'>".$message."</a>");
}
add_filter('__wps__send_poke_filter', '__wps__send_poke', 10, 5);

// ----------------------------------------------------------------------------------------------------------------------------------------------------------

// Add news item that mail was sent
function __wps__news_add_message($message_to, $message_from, $from_name, $mail_id) {
	$url = __wps__get_url('mail');
	__wps__news_add($message_from, $message_to, "<a href='".$url.__wps__string_query($url)."mid=".$mail_id."'>".__("You have a new message from", WPS_TEXT_DOMAIN)." ".$from_name."</a>");
}
add_filter('__wps__sendmessage_filter', '__wps__news_add_message', 10, 4);

// ----------------------------------------------------------------------------------------------------------------------------------------------------------

// Add news item that friend request was made
function __wps__news_add_friendrequest($message_to, $message_from, $from_name) {
	$url = __wps__get_url('profile');
	__wps__news_add($message_from, $message_to, "<a href='".$url.__wps__string_query($url)."view=friends'>".sprintf(__("New %s request from", WPS_TEXT_DOMAIN), get_option(WPS_OPTIONS_PREFIX.'_alt_friend'))." ".$from_name."</a>");
}
add_filter('__wps__friendrequest_filter', '__wps__news_add_friendrequest', 10, 3);

// ----------------------------------------------------------------------------------------------------------------------------------------------------------

// Add news item that friend request was accepted
function __wps__news_add_friendaccepted($message_to, $message_from, $from_name) {
	$url = __wps__get_url('profile');
	__wps__news_add($message_from, $message_to, "<a href='".$url.__wps__string_query($url)."view=friends'>".sprintf(__("%s request accepted by", WPS_TEXT_DOMAIN), get_option(WPS_OPTIONS_PREFIX.'_alt_friend'))." ".$from_name."</a>");
}
add_filter('__wps__friendaccepted_filter', '__wps__news_add_friendaccepted', 10, 3);

// ----------------------------------------------------------------------------------------------------------------------------------------------------------

// Add news item that new forum topic posted (when subscribed)
function __wps__news_add_newtopic($message_to, $from_id, $from_name, $url) {
	__wps__news_add($from_id, $message_to, "<a href='".$url."'>".__("Subscribed forum topic by", WPS_TEXT_DOMAIN)." ".$from_name."</a>");
}
add_filter('__wps__forum_newtopic_filter', '__wps__news_add_newtopic', 10, 4);

// ----------------------------------------------------------------------------------------------------------------------------------------------------------

// Add news item that new forum reply posted (when subscribed)
function __wps__news_add_newreply($message_to, $message_from, $from_name, $url) {
	if ($message_to != $message_from) {
		__wps__news_add($message_from, $message_to, "<a href='".$url."'>".__("Subscribed forum reply by", WPS_TEXT_DOMAIN)." ".$from_name."</a>");
	}
}
add_filter('__wps__forum_newreply_filter', '__wps__news_add_newreply', 10, 4);

// ----------------------------------------------------------------------------------------------------------------------------------------------------------

// Add news item that new forum reply comment added (when subscribed)
function __wps__news_add_newreplycomment($message_to, $message_from, $from_name, $url) {
	if ($message_to != $message_from) {
		__wps__news_add($message_from, $message_to, "<a href='".$url."'>".__("Subscribed forum comment by", WPS_TEXT_DOMAIN)." ".$from_name."</a>");
	}
}
add_filter('__wps__forum_newreplycomment_filter', '__wps__news_add_newreplycomment', 10, 4);

// ----------------------------------------------------------------------------------------------------------------------------------------------------------

// Add news item that new post has been posted on member's profile
function __wps__news_add_wall_newpost($post_to, $post_from, $from_name) {
	if ($post_to != $post_from) {
		__wps__news_add($post_from, $post_to, "<a href='".__wps__get_url('profile')."'>".$from_name." ".__("has posted on your profile.", WPS_TEXT_DOMAIN)."</a>");
	}
}
add_filter('__wps__wall_newpost_filter', '__wps__news_add_wall_newpost', 10, 3);

// ----------------------------------------------------------------------------------------------------------------------------------------------------------

// Add news item that new comment has been added as a reply to a post on member's profile
function __wps__news_add_wall_reply($first_post_subject, $first_post_author, $from_id, $from_name, $url) {
	global $current_user;

	if ($first_post_subject != $current_user->ID) {
		__wps__news_add($from_id, $first_post_subject, "<a href='".$url."'>".$from_name." ".__("has replied to a post on your profile", WPS_TEXT_DOMAIN)."</a>");
	} else {
		if ($first_post_author != $current_user->ID) {
			__wps__news_add($from_id, $first_post_author, "<a href='".$url."'>".$from_name." ".__("has replied to a post you started", WPS_TEXT_DOMAIN)."</a>");
		}
	}

}
add_filter('__wps__wall_postreply_filter', '__wps__news_add_wall_reply', 10, 5);

// ----------------------------------------------------------------------------------------------------------------------------------------------------------

// Add news item that new comment has been added as a reply to a post this member is involved in
function __wps__news_add_wall_reply_involved_in($post_to, $post_from, $from_name, $url) {
	if ($post_to != $post_from) {
		__wps__news_add($post_from, $post_to, "<a href='".$url."'>".$from_name." ".__("has replied to a post you are involved in", WPS_TEXT_DOMAIN)."</a>");
	}
}
add_filter('__wps__wall_postreply_involved_filter', '__wps__news_add_wall_reply_involved_in', 10, 4);



?>
