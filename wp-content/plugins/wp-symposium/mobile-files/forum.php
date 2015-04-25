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

if ($cat_id > 0) {
	$sql = "SELECT title FROM ".$wpdb->prefix."symposium_cats WHERE cid = %d";
	$page_title = stripslashes($wpdb->get_var($wpdb->prepare($sql, $cat_id)));
}

if ($topic_id > 0) {
	$sql = "SELECT topic_subject FROM ".$wpdb->prefix."symposium_topics WHERE tid=%d";
	$page_title = stripslashes($wpdb->get_var($wpdb->prepare($sql, $topic_id)));
}
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

	// Get passed parameters
	$catid = isset($_GET['catid']) ? $_GET['catid'] : 0;
	$tid = isset($_GET['tid']) ? $_GET['tid'] : 0;

	if ($catid > 0) {	
		show_header('[home,topics,replies,gotop,new,friends]');
	} else {
		show_header('[home,topics,replies,new,friends]');
	}

	require_once('mobile-default-constants.php');
	require_once(WPS_PLUGIN_DIR.'/class.wps.php');
	require_once(WPS_PLUGIN_DIR.'/class.wps_user.php');
	require_once(WPS_PLUGIN_DIR.'/class.wps_ui.php');
	require_once(WPS_PLUGIN_DIR.'/class.wps_forum.php');
	

	$wps = new wps(); 
	$wps_ui = new wps_ui(); 
	$wps_forum = new wps_forum(); // Defaults to top level, can pass a category ID to set root level
	
	echo '<div style="clear:both"></div>';

	// If reply POSTed then act
	if (isset($_POST['__wps__reply_text']) && $_POST['__wps__reply_text']) {
		$wps_forum->add_reply($tid, '<p>'.$_POST['__wps__reply_text'].'</p>');
	}
	
	// If del_id in URL then delete that URL
	if (isset($_GET['del_id']) && is_numeric($_GET['del_id'])) {
		if ($id = $wps_forum->delete_post($_GET['del_id'])) echo '<div class="my-notice">Post deleted</div>';
	}

	// If tid passed, show the topic
	if ($tid) {

		$topic = $wps_forum->get_topic($tid);
		
		if ($topic) {

			// Show initial topic post
			echo '<div id="my-forum-topic">';
				echo '<div class="__wps__threads_title">'.stripslashes($topic->topic_subject).'</div>';
				echo 'Started by '.$topic->display_name.' '.__wps__time_ago($topic->topic_started).'<br /><br />';
				echo '<div class="__wps__threads_div">';
					$post_text = __wps__make_url(stripslashes($topic->topic_post));
					$post_text = __wps__bbcode_replace($post_text);
					$post_text = str_replace(chr(13), "<br />", $post_text);
					if (substr($post_text, 0, 6) == '<br />') $post_text = substr($post_text, 6);
					echo $post_text;
				echo '</div>';
			echo '</div>';
			
			$forum_url = __wps__get_url('forum');
			$forum_q = __wps__string_query($forum_url);
			if (get_option(WPS_OPTIONS_PREFIX.'_permalink_structure')) {
				$perma_cat = __wps__get_forum_category_part_url($topic->topic_category);
				echo "<br />(<a href='".$forum_url.'/'.$perma_cat.$topic->stub."'>".__('view on full site', WPS_TEXT_DOMAIN)."</a>)<br /><br />";
			} else {
				echo "<br />(<a href='".$forum_url.$forum_q."cid=".$topic->topic_category."&show=".$topic->tid."'>".__('view on full site', WPS_TEXT_DOMAIN)."</a>)<br /><br />";
			}			
				
			// Show replies
			$replies = $wps_forum->get_replies($tid, 0, 9999, 'ASC');
			if ($replies) {				
				foreach ($replies as $reply) {
				// Show avatar
					echo '<div class="__wps__threads_div">';
						echo '<strong>';
							echo 'Reply by '.$reply->display_name.' '.__wps__time_ago($reply->topic_started);
						echo '</strong>';
						$post_text = __wps__make_url(stripslashes($reply->topic_post));
						$post_text = __wps__bbcode_replace($post_text);
						$post_text = str_replace(chr(13), "<br />", $post_text);
						echo '<div class="my-reply-text">';
							echo $post_text;
						echo '</div>';

						// Show comments
						$comments = $wps_forum->get_replies($reply->tid, 0, 9999, 'ASC');
						if ($comments) {				
							foreach ($comments as $comment) {
							// Show avatar
								echo '<div class="__wps__comments_div">';
									echo '<strong>';
										echo 'Comment by '.$comment->display_name.' '.__wps__time_ago($comment->topic_started);
									echo '</strong>';
									$comment_text = __wps__make_url(stripslashes($comment->topic_post));
									$comment_text = __wps__bbcode_replace($comment_text);
									$comment_text = str_replace(chr(13), "<br />", $comment_text);
									echo '<div class="my-comment-text">';
										echo $comment_text;
									echo '</div>';
								echo '</div>';
							}
						}
						

					echo '</div>';
				}
			}
			
			// Reply field
			echo '<br /><strong>Add a reply:</strong><br /><br />';
			echo $wps_ui->forum_reply('Reply', 'submit small blue', 'textarea', true);
			
		} else {
			
			echo '<em>Topic not available, sorry</em>';
			
		}
		
	} else {

		echo '<div id="my-forum-table">';
				
			// Shows categories from the current level
			$categories = $wps_forum->get_categories($catid);
			if ($categories) {
				foreach ($categories as $category) {
					echo '<div class="my-forum-row">';
						echo '<div class="my-forum-row-title">';
							$title = stripslashes($category->title);	
							$title .= ' ('.$wps_forum->get_topics_count($category->cid).')';
							echo '<input type="submit" onclick="location.href=\'forum.php?'.$a.'&catid='.$category->cid.'\'" class="submit small fullwidth rosy" value="'.$title.'" />';
						echo '</div>';
						echo '<div style="margin-bottom:5px">';
							$last_topic = $wps_forum->get_topics($category->cid, 0, 10);
							if ($last_topic) {
								foreach ($last_topic as $topic) {
									$wps_user = new wps_user($topic->topic_owner);
									echo "<a href='forum.php?".$a."&catid=".$category->cid."&tid=".$topic->tid."'>".stripslashes($topic->topic_subject)."</a> ";
									echo '<br />'.$topic->display_name.', ';
									echo __wps__time_ago($topic->topic_started);
									$replies = $wps_forum->get_replies_count($topic->tid);
									echo " (".sprintf(_n('%d reply', '%d replies', $replies), $replies).")<br />";
								}
							}
						echo '</div>';
					echo '</div>';
				}
			}
			
			
		
			// Show topics in this category
			$topics = $wps_forum->get_topics($catid);
			if ($topics) {

				foreach ($topics as $topic) {
					echo '<div class="__wps__threads_div">';
						// Topic subject
						$topic_subject = stripslashes($topic->topic_subject);
						if (strlen($topic_subject) > 60) $topic_subject = substr($topic_subject, 0, 60).'...';
						echo "<a href='forum.php?".$a."&catid=".$catid."&tid=".$topic->tid."'>".$topic_subject."</a>";
						echo "<br />".__("Started by", WPS_TEXT_DOMAIN).' '.$topic->display_name;
						echo ", ".__wps__time_ago($topic->topic_date);
						$text = __wps__make_url(stripslashes($topic->topic_post));
						$text = __wps__bbcode_replace($text);
						$text = str_replace('<p>', "<br />", $text);	
						$text = str_replace('</p>', "", $text);	
						$text = str_replace('<br /><br />', "<br />", $text);	
						$text = str_replace(chr(13), "<br />", $text);	
						if (substr($text, 0, 6) == '<br />') $text = substr($text, 6);
						
						// Last reply
						$last_reply = $wps_forum->get_replies($topic->tid, 0, 1);
						if ($last_reply) {
							echo " (".sprintf(_n('%d reply', '%d replies', $topic->topic_replies), $topic->topic_replies).")<br />";
							echo '<br /><br />'.$text;

							$text = __wps__make_url(stripslashes($last_reply->topic_post));
							$text = __wps__bbcode_replace($text);
							$text = str_replace('<p>', "<br />", $text);	
							$text = str_replace('</p>', "", $text);	
							$text = str_replace('<br /><br />', "<br />", $text);	
							$text = str_replace(chr(13), "<br />", $text);	
							echo '<hr /><strong>'.__('Last reply by', WPS_TEXT_DOMAIN).' '.$last_reply->display_name.' '.__wps__time_ago($last_reply->topic_date).':</strong><br />';
							echo $text;
						} else {
							echo '<br /><br />'.$text;
							echo '<hr />'.__('No replies', WPS_TEXT_DOMAIN);
						}
					echo '</div>';
				}
			} else {
				if ($catid)
					echo __('<br />No topics here.', WPS_TEXT_DOMAIN);
			}
	
		echo '</div>';

	}
	
	
}

include_once(dirname(__FILE__).'/footer.php');	
?>
</body>
</html>
