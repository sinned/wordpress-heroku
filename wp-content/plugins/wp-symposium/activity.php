<?php
include_once('../../../wp-config.php');

global $wpdb;

// RSS Feed of a member's activity *****************************************************************

$uid = $_GET['uid'];

$sql = "SELECT display_name FROM ".$wpdb->base_prefix."users WHERE ID = %d";
$display_name = $wpdb->get_var($wpdb->prepare($sql, $uid));

header("Content-type: application/rss+xml");

echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<rss version="2.00">';
echo '<channel>';
echo '<title>'.get_bloginfo('name').': '.$display_name.'</title>';
echo '<link>'.get_bloginfo('url').'</link>';
echo '<description>'.get_bloginfo('description').'</description>';
$now = date("D, d M Y H:i:s T");
echo '<lastBuildDate>'.$now.'</lastBuildDate>';

if ($uid > 0) {

	$rss_share = __wps__get_meta($uid, 'rss_share');
	
	if ($rss_share == 'on') {

		$sql = "SELECT cid, comment_timestamp, comment FROM ".$wpdb->base_prefix."symposium_comments WHERE is_group != 'on' AND comment_parent = 0 AND author_uid = %d AND subject_uid = %d ORDER BY cid DESC LIMIT 0,25";
		$activities = $wpdb->get_results($wpdb->prepare($sql, $uid, $uid));

		$profile_url = __wps__get_url('profile');
				
		foreach ($activities as $activity) {

			echo '<item>';
				echo '<title>'.stripslashes($activity->comment).'</title>';
				echo '<link>'.$profile_url.__wps__string_query($profile_url).'uid='.$uid.'&amp;post='.$activity->cid.'</link>';
				echo '<guid>'.$profile_url.__wps__string_query($profile_url).'uid='.$uid.'&amp;post='.$activity->cid.'</guid>';
				echo '<pubDate>'.date(DATE_RSS, strtotime($activity->comment_timestamp)).'</pubDate>';
			echo '</item>';


		}

		echo '</channel>';
		echo '</rss>';
		
	} else {

		echo '<item>';
			echo '<title>'.__('This activity is now not available publicly.', WPS_TEXT_DOMAIN).'</title>';
		echo '</item>';
		
	}
	
	
}

?>
