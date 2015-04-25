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



// Re-act to POSTed information *******************************************************************

if ($_POST['new_reply_post'] != '') {

	$tid = $_POST['new_reply_topic'];
	$cat_id = $_POST['new_reply_category'];
	$reply_text = $_POST['new_reply_post'];
	$group_id = 0;
	
	$wpdb->show_errors();

	// Get list of roles for this user (for use below)
    $user_roles = $current_user->roles;
    $user_role = strtolower(array_shift($user_roles));
    if ($user_role == '') $user_role = 'NONE';
    
	// Check that permitted to category
	$levels = $wpdb->get_var($wpdb->prepare("SELECT level FROM ".$wpdb->prefix."symposium_cats WHERE cid = ".$cat_id));
	$cat_roles = unserialize($levels);
	
	if (strpos(strtolower($cat_roles), 'everyone,') !== FALSE || strpos(strtolower($cat_roles), $user_role.',') !== FALSE) {					

		// Check for moderation
		if (get_option(WPS_OPTIONS_PREFIX.'_moderation') == "on") {
			$topic_approved = "";
		} else {
			$topic_approved = "on";
		}
	
		if ($reply_text != '') { 
			
			// Invalidate HTML
			$reply_text = str_replace("<", "&lt;", $reply_text);
			$reply_text = str_replace(">", "&gt;", $reply_text);
			
			// Store new topic in post					
			$wpdb->query( $wpdb->prepare( "
			INSERT INTO ".$wpdb->prefix."symposium_topics
			( 	topic_subject, 
				topic_category,
				topic_post, 
				topic_date, 
				topic_started, 
				topic_owner, 
				topic_parent, 
				topic_views,
				topic_approved,
				topic_group
			)
			VALUES ( %s, %d, %s, %s, %s, %d, %d, %d, %s, %d )", 
			array(
				'', 
				$cat_id,
				$reply_text, 
				date("Y-m-d H:i:s"), 
				date("Y-m-d H:i:s"), 
				$current_user->ID, 
				$tid,
				0,
				$topic_approved,
				$group_id
				) 
			) );
			
			// Email people who want to know and prepare body
			$owner_name = $wpdb->get_var($wpdb->prepare("SELECT display_name FROM ".$wpdb->base_prefix."users WHERE ID = ".$current_user->ID));
			$parent = $wpdb->get_var($wpdb->prepare("SELECT topic_subject FROM ".$wpdb->prefix."symposium_topics WHERE tid = ".$tid));
			
			$body = "<span style='font-size:24px'>".$parent."</span><br /><br />";
			$body .= "<p>".$owner_name." ".__('replied', WPS_TEXT_DOMAIN)."...</p>";
			$body .= "<p>".$reply_text."</p>";
			$body .= "<p>".$forum_url.$q."cid=".$cat_id."&show=".$tid."</p>";
			$body = str_replace(chr(13), "<br />", $body);
			$body = str_replace("\\r\\n", "<br />", $body);
			$body = str_replace("\\", "", $body);
			
			if ($topic_approved == "on") {
				$query = $wpdb->get_results("
					SELECT user_email
					FROM ".$wpdb->base_prefix."users u RIGHT JOIN ".$wpdb->prefix."symposium_subs ON ".$wpdb->prefix."symposium_subs.uid = u.ID 
					WHERE u.ID != ".$current_user->ID." AND tid = ".$tid);
					
				if ($query) {						
					foreach ($query as $user) {		
						__wps__sendmail($user->user_email, __('New Forum Reply', WPS_TEXT_DOMAIN), $body);							
					}
				}						
			} else {
				// Email admin if post needs approval
				$body = "<span style='font-size:24px; font-style:italic;'>".__("Moderation required for a reply", WPS_TEXT_DOMAIN)."</span><br /><br />".$body;
				__wps__sendmail(get_bloginfo('admin_email'), __('Moderation required for a reply', WPS_TEXT_DOMAIN), $body);
			}					
	
		}
	
		header('Location: forum.php?cat_id='.$cat_id.'&topic_id='.$tid.'&a='.$_POST['a']);
		
	}
			
}

// End of POSTed information **********************************************************************	

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
echo '<div id="header">'.get_bloginfo('name').'<div class="home_link"><a href="index.php?'.$a.'">'.__('Home', WPS_TEXT_DOMAIN).'</a></div></div>';
echo '<div class="subheading">'.__('Post a reply', WPS_TEXT_DOMAIN).'</div>';
	
$cat_name = $wpdb->get_var($wpdb->prepare("SELECT title FROM ".$wpdb->prefix."symposium_cats WHERE cid = ".$cat_id));
if ($cat_name == '') { $cat_name = 'Top level'; }
echo "<ul><li><a href='forum.php?cat_id=".$cat_id."&".$a."'>".__('Back to', WPS_TEXT_DOMAIN)." ".$cat_name."...</a></li></ul>";

// Show login link?
if ( is_user_logged_in() ) {
		
	// Add reply form
	echo '<div class="form">';
		echo '<form action="" method="POST">';
		echo '<input type="hidden" name="a" value="'.$_GET['a'].'" />';
		echo '<input type="hidden" name="new_reply_category" value="'.$cat_id.'" />';
		echo '<input type="hidden" name="new_reply_topic" value="'.$topic_id.'" />';
		echo __('Reply text', WPS_TEXT_DOMAIN).'<br />';
		echo '<textarea name="new_reply_post"></textarea><br />';
		echo '<input type="submit" class="submit" value="'.__('Post reply', WPS_TEXT_DOMAIN).'" />';
		echo '</form>';
	echo '</div>';

}

include_once(dirname(__FILE__).'/footer.php');	
?>
</body>
</html>
