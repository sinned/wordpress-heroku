<?php

include_once('../../../../wp-config.php');

// Delete all news alerts
if ($_POST['action'] == 'delete_all_news') {
	global $wpdb,$current_user;
	if (is_user_logged_in()) {
		$sql = "DELETE FROM ".$wpdb->base_prefix."symposium_news WHERE subject = %d";
		$wpdb->query($wpdb->prepare($sql, $current_user->ID));
	}
}


// Get news content
if ($_POST['action'] == 'get_news') {

	global $wpdb,$current_user;
	$html = '';

	$max_items = 50;

	if (is_user_logged_in()) {

		// Get new news items
		$sql = "SELECT nid, news, added, new_item
			FROM ".$wpdb->base_prefix."symposium_news 
			WHERE subject = %d 
			ORDER BY new_item desc, nid DESC LIMIT 0,%d";

		$items = $wpdb->get_results($wpdb->prepare($sql, $current_user->ID, $max_items));

		// Prepare to return comments in JSON format
		$return_arr = array();
	
		// Loop through comments, adding to array if any exist
		if ($items) {
			foreach ($items as $item) {

	
				$row_array['nid'] = $item->nid;
				$row_array['news'] = stripslashes($item->news);
				$row_array['added'] = __wps__time_ago($item->added);
				$row_array['new_item'] = $item->new_item;
				array_push($return_arr, $row_array);
			}	
			$row_array['nid'] = 0;
			$sql = "SELECT ID FROM ".$wpdb->prefix."posts WHERE lower(post_content) LIKE '%[symposium-alerts]%' AND post_type = 'page' AND post_status = 'publish';";
			$pages = $wpdb->get_results($sql);	
			if ($pages) {
				$url = get_permalink($pages[0]->ID);
				$row_array['news'] = $url;
			}
			array_push($return_arr, $row_array);
		} 

	
		echo json_encode($return_arr);
		exit;

	} else {

		echo '[]';
		exit;
	}
}

if ($_POST['action'] == 'clear_read_news') {

	global $wpdb,$current_user;

	if (is_user_logged_in()) {

		// Clear read news items
		$wpdb->query("UPDATE ".$wpdb->base_prefix."symposium_news SET new_item = '' WHERE subject = ".$current_user->ID);

	}

	exit;
}	

// Summary of recent news items
if ($_POST['action'] == 'menu_news') {	

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
	echo $html;

}

	
?>

	
