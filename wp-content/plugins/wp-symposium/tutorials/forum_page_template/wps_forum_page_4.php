<?php
/**
 * Template Name: Demo forum page 4
 * Description: A Forum Page Template to demonstrate using classes
 *
 * @package WordPress
 * @subpackage Twenty_Eleven
 * @since Twenty Eleven 1.0
 */

get_header(); 

/* Javascript - this should be in a separate JS file, but included to support the tutorial */
?>

<script type="text/javascript">

	jQuery(document).ready(function() { 	
		
		// Add Elastic text support
		if (jQuery(".elastic").length) { jQuery('.elastic').elastic(); }
		
		// Add WYSIWYG editor support (only works if enabled in Forum settings)
		tiny_mce_init('__wps__reply_text');
					
	});

</script>

<?php


// include the PHP class files, the path should match your server, the following will probably do!
require_once(WPS_PLUGIN_DIR.'/class.wps.php');
require_once(WPS_PLUGIN_DIR.'/class.wps_user.php');
require_once(WPS_PLUGIN_DIR.'/class.wps_ui.php');
require_once(WPS_PLUGIN_DIR.'/class.wps_forum.php');

$wps = new wps(); 
$wps_ui = new wps_ui(); 
$wps_forum = new wps_forum(); // Defaults to top level, can pass a category ID to set root level

/*
First we over-ride settings for forum page to ensure links to this page across go to
the correct page. Note that you will need to visit/reload this page
the first time the script is run, as various constants are set prior to this page template
loading. If you visit Admin->Installation the default values will be reset, 
which includes after upgrading WPS, so re-visit this page at least once after visiting 
the Installation page, to put things back to the new page. Alternatively, create a 
page that updates this (and maybe other) URLs that you can visit as admin once after upgrading WPS.

This is hardcoded to a particular page for now. If distributing to other user's this will
need to be dynamically set! Change it to make the URL of your new forum page, mine is as
per the tutorial (ie. a page called "AA Forum").
*/
$wps->set_forum_url('/aa-forum');
?>

<!--
Links to styles used in this page template - shouldn't be included in the page template really,
but is included here to keep things simple for the tutorial at www.wpsymposium.com/blog.
Should be included in the theme header.php in the <HEAD> ... </HEAD> tags.
This also assumes the .css file is also in the current theme folder along with this page file. 
-->
<link rel="stylesheet" type="text/css" href="<?php bloginfo('template_url'); ?>/wps_forum_page.css" />

<div id="primary">
	<div id="content" role="main">
	
	<!-- WordPress page content components -->
	<?php the_post(); ?>
	<?php get_template_part( 'content', 'page' ); ?>
	<!-- End WordPress page content components -->

	<?php	
	// Get passed parameters
	$catid = isset($_GET['catid']) ? $_GET['catid'] : 0;
	$tid = isset($_GET['tid']) ? $_GET['tid'] : 0;
	
	// If reply POSTed then act
	if (isset($_POST['__wps__reply_text']) && $_POST['__wps__reply_text']) {
		$wps_forum->add_reply($tid, $_POST['__wps__reply_text']);
	}
	
	// If del_id in URL then delete that URL
	if (isset($_GET['del_id']) && is_numeric($_GET['del_id'])) {
		if ($id = $wps_forum->delete_post($_GET['del_id'])) echo '<div class="my-notice">Post deleted</div>';
	}

	// If tid passed, show the topic
	if ($tid) {

		$topic = $wps_forum->get_topic($tid);
		
		if ($topic) {

			// Add breadcrumbs (including last level as a link)
			echo '<div id="my-topic-breadcrumbs">';
				echo $wps_ui->get_breadcrumbs($catid, 'catid', true, '&rarr;', true);
			echo '</div>';
			
			// Show avatar
			echo '<div id="my-topic-avatar">';
				$wps_user = new wps_user($topic->topic_owner);
				echo $wps_user->get_avatar(100);
			echo '</div>';
			
			// Show initial topic post
			echo '<div id="my-forum-topic">';
				if ( ($topic->topic_owner == $current_user->ID || current_user_can('manage_options')) && (!$wps_forum->is_topic_locked($topic->tid)) ) {
					echo '<a href="'.$wps->get_forum_url().$wps->get_url_q($wps->get_forum_url()).'catid='.$catid.'&tid='.$tid.'&del_id='.$topic->tid.'" class="delete">Delete topic and replies</a>';
				}
				echo '<div id="my-topic-subject">';
				echo stripslashes($topic->topic_subject);
				echo '</div>';
				echo '<div id="my-topic-author">';
				echo 'Started by '.$topic->display_name.' '.__wps__time_ago($topic->topic_started);
				echo '</div>';
				echo '<div id="my-topic-first-post">';
					$post_text = __wps__make_url(stripslashes($topic->topic_post));
					$post_text = __wps__bbcode_replace($post_text);
					if (!WPS_WYSIWYG) $post_text = str_replace(chr(13), "<br />", $post_text);
					echo $post_text;
				echo '</div>';
			echo '</div>';
			
			// Show replies
			$replies = $wps_forum->get_replies($tid, 0, 9999, 'ASC');
			if ($replies) {				
				foreach ($replies as $reply) {
				// Show avatar
					echo '<div class="my-forum-reply">';
						if ( ($reply->topic_owner == $current_user->ID || current_user_can('manage_options')) && (!$wps_forum->is_topic_locked($reply->tid)) ) {
							echo '<a href="'.$wps->get_forum_url().$wps->get_url_q($wps->get_forum_url()).'catid='.$catid.'&tid='.$tid.'&del_id='.$reply->tid.'" class="delete">Delete reply</a>';
						}
						echo '<div class="my-reply-avatar">';
							$wps_user = new wps_user($reply->topic_owner);
							echo $wps_user->get_avatar(60);
						echo '</div>';
						echo '<div class="my-reply-author">';
							echo 'Reply by '.$reply->display_name.' '.__wps__time_ago($reply->topic_started);
						echo '</div>';
						$post_text = __wps__make_url(stripslashes($reply->topic_post));
						$post_text = __wps__bbcode_replace($post_text);
						$post_text = str_replace(chr(13), "<br />", $post_text);
						echo '<div class="my-reply-text">';
							echo $post_text;
						echo '</div>';
					echo '</div>';
				}
			}
			
			// Reply field
			echo '<div style="margin-left:118px; margin-bottom:15px;">';
				echo '<strong>Add a reply:</strong><br />';
				echo $wps_ui->forum_reply('Reply', 'my-submit-button', 'my-reply-textarea', true);
			echo '</div>';
			
		} else {
			
			echo '<strong>Topic not available, sorry</strong><br />';
			echo '<a href="'.$wps->get_forum_url().$wps->get_url_q($wps->get_forum_url()).'catid='.$catid.'">Go to forum category</a>';			
			
		}
		
	} else {

		// Add breadcrumbs
		echo $wps_ui->get_breadcrumbs($catid, 'catid');
		
		// Start forum table
		echo '<div id="my-forum-table">';
				
			// Shows categories from the current level
			$categories = $wps_forum->get_categories($catid);
			if ($categories) {
				echo '<div class="my-forum-row my-forum-row my-forum-row-header">';
					echo '<div class="my-forum-title">';
						echo '<strong>CATEGORY</strong>';
					echo '</div>';
					echo '<div class="my-forum-title-topic">';
						echo '<strong>LAST TOPIC</strong>';
					echo '</div>';
				echo '</div>';
				foreach ($categories as $category) {
					echo '<div class="my-forum-row">';
						echo '<div class="my-forum-row-title">';
							$title = stripslashes($category->title);	
							echo '<a href="'.$wps->get_forum_url().$wps->get_url_q($wps->get_forum_url()).'catid='.$category->cid.'">'.$title.'</a>';
						echo '</div>';
						echo '<div class="my-forum-row-last-topic">';
							$last_topic = $wps_forum->get_topics($category->cid, 0, 1);
							if ($last_topic) {
								echo '<div class="my-forum-row-last-topic-avatar">';
									$wps_user = new wps_user($last_topic->topic_owner);
									echo '<a href="'.$wps->get_profile_url().$wps->get_url_q($wps->get_profile_url()).'uid='.$last_topic->topic_owner.'">';
									echo $wps_user->get_avatar(48);
									echo '</a>';
								echo '</div>';
								echo "<a href='".$wps->get_forum_url().$wps->get_url_q($wps->get_forum_url())."catid=".$category->cid."&tid=".$last_topic->tid."'>".stripslashes($last_topic->topic_subject)."</a> ";
								echo '<span class="my-forum-row-last-topic-owner">'.$last_topic->display_name.',</span> ';
								echo '<span class="my-forum-row-last-topic-started">'.__wps__time_ago($last_topic->topic_started).'</span>';
							}
						echo '</div>';
					echo '</div>';
				}
			}
			
			
		
			// Show topics in this category
			$topics = $wps_forum->get_topics($catid);
			if ($topics) {
				// Header
				echo '<div class="my-topic-row-header">';
					echo '<div class="my-forum-title">';
						echo '<strong>TOPIC</strong>';
					echo '</div>';
					echo '<div class="my-topics-title-topic">';
						echo '<strong>LAST REPLY</strong>';
					echo '</div>';
					echo '<div class="my-topics-title-replies">';
						echo '<strong>REPLIES</strong>';
					echo '</div>';
				echo '</div>';
				foreach ($topics as $topic) {
					echo '<div class="my-topic-row">';
						// Topic subject
						echo '<div class="my-topic-row-title">';
							$topic_subject = stripslashes($topic->topic_subject);
							if (strlen($topic_subject) > 60) $topic_subject = substr($topic_subject, 0, 60).'...';
							echo "<a href='".$wps->get_forum_url().__wps__string_query($wps->get_forum_url())."catid=".$catid."&tid=".$topic->tid."'>".$topic_subject."</a>";
						echo '</div>';
						// Last reply
						$last_reply = $wps_forum->get_replies($topic->tid, 0, 1);
						echo '<div class="my-forum-row-last-topic">';
							if ($last_reply) {
								$reply = stripslashes($last_reply->topic_post);
								$reply = str_replace('<br />', ' ', $reply);
								$reply = str_replace('<p>', '', $reply);
								$reply = str_replace('</p>', ' ', $reply);
								if (strlen($reply) > 60) $reply = substr(strip_tags($reply), 0, 60).'...';
								echo '<div class="my-topic-row-last-topic-avatar">';
									$wps_user = new wps_user($last_reply->topic_owner);
									echo '<a href="'.$wps->get_profile_url().'?uid='.$last_reply->topic_owner.'">';
									echo $wps_user->get_avatar(48);
									echo '</a>';
								echo '</div>';
								echo '<span class="my-forum-row-last-topic-owner">'.$last_reply->display_name.',</span> ';
								echo '<span class="my-forum-row-last-topic-started">'.__wps__time_ago($last_reply->topic_started).'</span>';
								if ($topic->topic_views > 0) {
									echo '<span class="my-forum-row-last-num-views">, '.$topic->topic_views.' views</span> ';
								}
								echo '<br />'.$reply;
							}
						echo '</div>';
						// Number of replies
						echo '<div class="my-forum-row-num-replies">';
							echo $topic->topic_replies;
						echo '</div>';					
					echo '</div>';
				}
			}	
	
		echo '</div>';

	}
	?>
				
	</div><!-- #content -->
</div><!-- #primary -->

<?php get_footer(); ?>
