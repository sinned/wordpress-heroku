<?php
include_once('../wp-config.php');
include_once(dirname(__FILE__).'/mobile_check.php');
	
global $wpdb, $current_user;

if (isset($_GET['topic_id'])) {
	$topic_id = $_GET['topic_id'];
} else {
	$topic_id = 0;
}

if (isset($_GET['cat_id'])) {
	$cat_id = $_GET['cat_id'];
} else {
	$cat_id = 0;
}

// Redirect if not on a mobile
if (!$mobile) {

	$forum_url = __wps__get_url('forum');
	if (strpos($forum_url, '?') !== FALSE) {
		$q = "&";
	} else {
		$q = "?";
	}

	header('Location: '.$forum_url.$q.'cid='.$cat_id.'&show='.$topic_id);
}

$maxlen = 35; // Max length of topic/category text

// Page Title
$page_title = get_bloginfo('name');

?>
<!DOCTYPE html>
<html>
<head>
<title><?php echo $page_title; ?></title>
<meta charset="UTF-8" />
<link rel="stylesheet" type="text/css" href="style.css" />
<?php if ($big_display) { ?>
	<link rel="stylesheet" type="text/css" href="bigdisplay.css" />
<?php } ?>
</head>
<body>

<?php
if ( !is_user_logged_in() ) {

	include_once('./header_loggedout.php');

} else {

	include_once('./header_loggedin.php');
	show_header('[home,forum,replies,new,friends]');

	require_once('mobile-default-constants.php');
	require_once(WPS_PLUGIN_DIR.'/class.wps.php');
	require_once(WPS_PLUGIN_DIR.'/class.wps_user.php');
	require_once(WPS_PLUGIN_DIR.'/class.wps_ui.php');
	require_once(WPS_PLUGIN_DIR.'/class.wps_forum.php');

	$wps = new wps(); 
	$wps_ui = new wps_ui(); 
	$wps_forum = new wps_forum(); // Defaults to top level, can pass a category ID to set root level
	
	global $wpdb;
	$html = "";
	
	// Tries to retrieve last 7 days unless postcount causes it to be less
	$include = strtotime("now") - (86400 * 7); // 1 week
	$include = date("Y-m-d H:i:s", $include);
	$postcount = 100;
	$gid = 0;
	$parent = 0;
	$desc = 'DESC';
	
	// All topics started
	$posts = $wpdb->get_results("
		SELECT tid, topic_subject, topic_owner, topic_post, topic_category, topic_date, display_name, topic_parent 
		FROM ".$wpdb->prefix.'symposium_topics'." t INNER JOIN ".$wpdb->base_prefix.'users'." u ON t.topic_owner = u.ID 
		WHERE topic_parent = ".$parent." AND topic_group = ".$gid." AND topic_date > '".$include."' ORDER BY tid ".$desc." LIMIT 0,".$postcount); 


	if ($posts) {

		foreach ($posts as $post)
		{
			$text = __wps__make_url(stripslashes($post->topic_post));
			$text = __wps__bbcode_replace($text);
			$text = str_replace('<p>', "<br />", $text);	
			$text = str_replace('</p>', "", $text);	
			$text = str_replace('<br /><br />', "<br />", $text);	
			$text = str_replace(chr(13), "<br />", $text);	
					
			$html .= '<div class="__wps__threads_div">';
			
				$html .= '<div><a  class="__wps__threads_title" href="forum.php?tid='.$post->tid.'&'.$a.'">'.stripslashes($post->topic_subject).'</a></div>';
	
				$html .= '<div>';
				$html .= __('Started by', WPS_TEXT_DOMAIN).' '.$post->display_name.', '.__wps__time_ago($post->topic_date).' '.__('in', WPS_TEXT_DOMAIN).' ';
				$html .= $wps_forum->get_category($post->topic_category).'</a>';
	
				$replies = $wps_forum->get_replies_count($post->tid);
				if ($replies)
					$html .= " (".sprintf(_n('%d reply', '%d replies', $replies), $replies).")";
				$html .= "</div><br />";
				if (substr($text, 0, 6) == '<br />') $text = substr($text, 6);
				$html .= $text;

				if ($replies > 0) {
					$last_reply = $wps_forum->get_replies($post->tid, $start=0, $limit=1, 'DESC');
					$text = __wps__make_url(stripslashes($last_reply->topic_post));
					$text = __wps__bbcode_replace($text);
					$text = str_replace('<p>', "<br />", $text);	
					$text = str_replace('</p>', "", $text);	
					$text = str_replace('<br /><br />', "<br />", $text);	
					$text = str_replace(chr(13), "<br />", $text);	
					$html .= '<hr /><strong>'.__('Last reply by', WPS_TEXT_DOMAIN).' '.$last_reply->display_name.' '.__wps__time_ago($last_reply->topic_date).':</strong><br />';
					$html .= $text;
				} else {
					$html .= '<hr />'.__('No replies', WPS_TEXT_DOMAIN);
				}
			
			$html .= '</div>';
			
		}
	}	
			
	echo $html;		
}


include_once(dirname(__FILE__).'/footer.php');	



?>
</body>
</html>
