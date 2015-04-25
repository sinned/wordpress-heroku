<?php
include_once('../wp-config.php');
include_once(dirname(__FILE__).'/mobile_check.php');

global $wpdb, $current_user;

// Redirect if not on a mobile
if (!$mobile) {
	header('Location: ./..');
}

require_once(ABSPATH.'wp-content/plugins/wp-symposium/class.wps.php');
require_once(ABSPATH.'wp-content/plugins/wp-symposium/class.wps_ui.php');
require_once(ABSPATH.'wp-content/plugins/wp-symposium/class.wps_user.php');

$wps = new wps();
$wps_ui = new wps_ui();
$wps_user = new wps_user($wps->get_current_user_page()); // default to current user, or pass a user ID


// Re-act to POSTed information *******************************************************************
if (isset($_POST['post_comment']) && $_POST['post_comment'] != '' && $_POST['post_comment'] != __('Write a comment...', WPS_TEXT_DOMAIN) && $current_user->ID > 0) {
	$new_status = $_POST['post_comment'];

	// Don't allow HTML
	$new_status = str_replace("<", "&lt;", $new_status);
	$new_status = str_replace(">", "&gt;", $new_status);

	$wpdb->query( $wpdb->prepare( "
		INSERT INTO ".$wpdb->base_prefix."symposium_comments
		( 	subject_uid, 
			author_uid,
			comment_parent,
			comment_timestamp,
			comment,
			is_group
		)
		VALUES ( %d, %d, %d, %s, %s, %s )", 
			array(
				$current_user->ID, 
		       	$current_user->ID, 
		       	$_POST['tid'],
		       	date("Y-m-d H:i:s"),
		       	$new_status,
		       	''
		       	) 
		 ) );

		// New Post ID
		$new_id = $wpdb->insert_id;

		// Parent ID
		$parent = (isset($_GET['tid'])) ? $_GET['tid'] : 0;
        		        
	    // Subject's name for use below
		$subject_name = $wpdb->get_var($wpdb->prepare("SELECT display_name FROM ".$wpdb->base_prefix."users WHERE ID = %d", $wps_user->id));

		// Get parent post (the first post)
		$sql = "SELECT * FROM ".$wpdb->base_prefix."symposium_comments WHERE cid = %d";
		$parent_post = $wpdb->get_row($wpdb->prepare($sql, $parent));
	
		// Email the author of the parent (ie. first post) if wants to be notified					
		$sql = "SELECT ID, user_email FROM ".$wpdb->base_prefix."users WHERE ID = %d AND ID != %d";
		$parent_post_recipient = $wpdb->get_row($wpdb->prepare($sql, $parent_post->author_uid, $current_user->ID));

		if ($parent_post_recipient) {
			if (__wps__get_meta($parent_post_recipient->ID, 'notify_new_wall') == 'on') {

				$profile_url = __wps__get_url('profile');
				$profile_url .= __wps__string_query($profile_url);
				$url = $profile_url."uid=".$wps_user->id."&post=".$parent_post->cid;

				$body = "<p>".$current_user->display_name." ".__('has replied to a post you started', WPS_TEXT_DOMAIN).":</p>";
				$body .= "<p>".stripslashes($new_status)."</p>";
				$body .= "<p><a href='".$url."'>".__('Go to the post', WPS_TEXT_DOMAIN)."...</a></p>";
				__wps__sendmail($parent_post_recipient->user_email, __('Profile Reply', WPS_TEXT_DOMAIN), $body);				
			}
		}
		
		// Get URL for later use in several places
		$profile_url = __wps__get_url('profile');
		$profile_url .= __wps__string_query($profile_url);
		$url = $profile_url."uid=".$wps_user->id."&post=".$parent_post->cid;
		
		// Email the subject of the parent (ie. first post) and want to be notified
		if ($parent_post->subject_uid != $parent_post->author_uid) {
			$sql = "SELECT ID, user_email FROM ".$wpdb->base_prefix."users WHERE ID = %d AND ID != %d";			
			$parent_post_recipient = $wpdb->get_row($wpdb->prepare($sql, $parent_post->subject_uid, $current_user->ID));
			
			if ($parent_post_recipient) {
				if (__wps__get_meta($parent_post_recipient->ID, 'notify_new_wall') == 'on') {

					if ($parent_post_recipient->notify_new_wall == 'on') {
						$body = "<p>".$current_user->display_name." ".__('has replied to a post started on your profile', WPS_TEXT_DOMAIN).":</p>";
						$body .= "<p>".stripslashes($new_status)."</p>";
						$body .= "<p><a href='".$url."'>".__('Go to the post', WPS_TEXT_DOMAIN)."...</a></p>";
						__wps__sendmail($parent_post_recipient->user_email, __('Profile Reply', WPS_TEXT_DOMAIN), $body);				
					}	
				}
			}
		}

		// Filter to allow further actions to take place
		apply_filters ('__wps__wall_postreply_filter', $parent_post->subject_uid, $parent_post->author_uid, $current_user->ID, $current_user->display_name, $url);
				
		// Email all the people who have replied to this post and want to be notified
		$sql = "SELECT DISTINCT u.user_email, u.ID
			FROM ".$wpdb->base_prefix."symposium_comments c 
			LEFT JOIN ".$wpdb->base_prefix."users u ON c.author_uid = u.ID 
			WHERE c.comment_parent = %d AND u.ID != %d";
	
		$reply_recipients = $wpdb->get_results($wpdb->prepare($sql, $parent, $current_user->ID));

		if ($reply_recipients) {
			foreach ($reply_recipients as $reply_recipient) {
				
				if (__wps__get_meta($reply_recipient->ID, 'notify_new_wall') == 'on') {
			
					if ($reply_recipient->ID != $parent_post->subject_uid && $reply_recipient->ID != $parent_post->author_uid) {

						if ($reply_recipient->notify_new_wall == 'on') {
							$body = "<p>".$current_user->display_name." ".__('has replied to a post you are involved in', WPS_TEXT_DOMAIN).":</p>";
							$body .= "<p>".stripslashes($new_status)."</p>";
							$body .= "<p><a href='".$url."'>".__('Go to the post', WPS_TEXT_DOMAIN)."...</a></p>";
							__wps__sendmail($reply_recipient->user_email, __('New Post Reply', WPS_TEXT_DOMAIN), $body);				
						}

						// Filter to allow further actions to take place
						apply_filters ('__wps__wall_postreply_involved_filter', $reply_recipient->ID, $current_user->ID, $current_user->display_name, $url);		

					}
				}
			
			}
		}
		
	// redirect to avoid multiple form posts
	?>
	<script>
	window.location.href = "<?php echo __wps__pageURL(); ?>";
	</script>
	<?php
	exit;
}

?>
<!DOCTYPE html>
<html>
<head>
<title><?php echo get_bloginfo('name');?></title>
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

	echo '<br /><br />';
	echo '<input type="submit" onclick="location.href=\'login.php?'.$a.'\'" class="submit small blue fullwidth" value="'.__('Login', WPS_TEXT_DOMAIN).'" />';
	echo '<br /><br />';
	
} else {

	include_once('./header_loggedin.php');
	show_header('[back]');

	$tid = (isset($_GET['tid'])) ? $_GET['tid'] : 0;
	
	if ($tid) {
		
		// Show first post		
		$post = $wps_user->get_activity_post($tid);
		echo "<div class='__wps__profile_activity_div'>";
		echo $post->display_name.' '.__('posted', WPS_TEXT_DOMAIN).' ';
		echo __wps__time_ago($post->comment_timestamp).".<br />";
		echo '<br />'.$post->comment;
		
		// Check for any associated uploaded images for activity
		$directory = WP_CONTENT_DIR."/wps-content/members/".$post->author_uid.'/activity/';
		if (file_exists($directory)) {
			$handler = opendir($directory);
			while ($image = readdir($handler)) {
				$path_parts = pathinfo($image);
				if ($path_parts['filename'] == $post->cid) {
					$directoryURL = WP_CONTENT_URL."/wps-content/members/".$post->author_uid.'/activity/'.$image;
					echo '<br /><img style="max-width:100%" src="'.$directoryURL.'" /><br />';
				}
			}
		}		
		
		echo "</div>";

		// Get comments		
		$comments = $wps_user->get_comments($tid);
		
		// Build wall
		if ($comments) {	
										
			$cnt = 0;
			foreach ($comments as $comment) {
			
				$cnt++;
												
				echo "<div class='__wps__profile_activity_div'>";
				
					echo '<a href="index.php?'.$a.'&uid='.$comment->author_uid.'">'.stripslashes($comment->display_name).'</a> ';
					if ($comment->author_uid != $comment->subject_uid && !$comment->is_group) {
						echo ' &rarr; <a href="index.php?'.$a.'&uid='.$comment->subject_uid.'">'.stripslashes($comment->subject_name).'</a> ';
					}
					echo __wps__time_ago($comment->comment_timestamp).".<br />";

					echo '<br />'.stripslashes($comment->comment);

				echo "</div>";
						
			}
		}

		// Show reply form
		echo '<form action="" id="reply_form" method="POST" onSubmit="document.getElementById(\'reply_form\').style.display = \'none\'">';
		echo '<input type="hidden" name="tid" value="'.$tid.'" />';
		echo $wps_ui->comment_post(__('Write a comment...', WPS_TEXT_DOMAIN), 'input_text');
		echo $wps_ui->comment_post_button(__('Post', WPS_TEXT_DOMAIN), 'submit small red wide');
		echo '</form>';
		

	} else {
		
		echo "No TID";
		
	}
}

include_once(dirname(__FILE__).'/footer.php');	

?>
</body>
</html>
