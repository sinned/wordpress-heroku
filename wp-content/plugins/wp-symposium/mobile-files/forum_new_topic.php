<?php
include_once('../wp-config.php');
include_once(dirname(__FILE__).'/mobile_check.php');
	
global $wpdb, $current_user;

if (isset($_GET['cat_id'])) {
	$cat_id = $_GET['cat_id'];
} else {
	$cat_id = 0;
}

if (isset($_GET['cat_id'])) {
	$cat_id = $_GET['cat_id'];
} else {
	$cat_id = 0;
}

// Re-act to POSTed information *******************************************************************

if (isset($_POST['new_topic_subject']) && $_POST['new_topic_subject'] != '' && $_POST['new_topic_post'] != '') {
	$new_topic_subject = $_POST['new_topic_subject'];
	$new_topic_text = $_POST['new_topic_post'];
	$new_topic_category = $_POST['new_topic_category'];
	$group_id = 0;

	// Get list of roles for this user (for use below)
    $user_roles = $current_user->roles;
    $user_role = strtolower(array_shift($user_roles));
    if ($user_role == '') $user_role = 'NONE';
    
	// Check that permitted to category
	$levels = $wpdb->get_var($wpdb->prepare("SELECT level FROM ".$wpdb->prefix."symposium_cats WHERE cid = %d", $new_topic_category));
	$cat_roles = unserialize($levels);
	
	if (strpos(strtolower($cat_roles), 'everyone,') !== FALSE || strpos(strtolower($cat_roles), $user_role.',') !== FALSE) {					

		// Calculate forum URL
		$forum_url = __wps__get_url('forum');
		$q = __wps__string_query($forum_url);		
		
		// Check for moderation
		if (get_option(WPS_OPTIONS_PREFIX.'_moderation') == "on") {
			$topic_approved = "";
		} else {
			$topic_approved = "on";
		}
	
		if ($new_topic_subject == '') { $new_topic_subject = __('No subject', WPS_TEXT_DOMAIN); }
		if ($new_topic_text == '') { $new_topic_text = __('No message', WPS_TEXT_DOMAIN);  }
			
		// Don't allow HTML in subject
		$new_topic_subject = str_replace("<", "&lt;", $new_topic_subject);
		$new_topic_subject = str_replace(">", "&gt;", $new_topic_subject);
		$new_topic_text = str_replace("<", "&lt;", $new_topic_text);
		$new_topic_text = str_replace(">", "&gt;", $new_topic_text);

		// Check for banned words
		$chatroom_banned = get_option(WPS_OPTIONS_PREFIX.'_chatroom_banned');
		if ($chatroom_banned != '') {
			$badwords = $pieces = explode(",", $chatroom_banned);
		
			 for($i=0;$i < sizeof($badwords);$i++){
			 	if (strpos(' '.$new_topic_subject.' ', $badwords[$i])) {
				 	$new_topic_subject=eregi_replace($badwords[$i], "***", $new_topic_subject);
			 	}
			 	if (strpos(' '.$new_topic_text.' ', $badwords[$i])) {
				 	$new_topic_text=eregi_replace($badwords[$i], "***", $new_topic_text);
			 	}
			 }
		}

		// Create stub		
		$stub = __wps__create_stub($new_topic_subject);

		// Insert post
		$wpdb->query( $wpdb->prepare( "
			INSERT INTO ".$wpdb->prefix."symposium_topics 
			( 	topic_subject,
				stub,
				topic_category, 
				topic_post, 
				topic_date, 
				topic_started, 
				topic_owner, 
				topic_parent, 
				topic_views,
				topic_approved,
				for_info,
				topic_group,
				remote_addr,
				http_x_forwarded_for
			)
			VALUES ( %s, %s, %d, %s, %s, %s, %d, %d, %d, %s, %s, %d, %s, %s )", 
	        array(
	        	$new_topic_subject,
	        	$stub,
	        	$new_topic_category,
	        	$new_topic_text, 
	        	date("Y-m-d H:i:s"), 
				date("Y-m-d H:i:s"), 
				$current_user->ID, 
				0,
				0,
				$topic_approved,
				'',
				0,
				$_SERVER['REMOTE_ADDR'],
				isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : ''
	        	) 
	        ) );
	
		// New Topic ID
		$new_tid = $wpdb->insert_id;
		// Set category to the category posted into
		$cat_id = $new_topic_category;

        
		// Store subscription if wanted
		if ($_POST['new_topic_subscribe'] == 'on') {
			$wpdb->query( $wpdb->prepare( "
				INSERT INTO ".$wpdb->prefix."symposium_subs 
				( 	uid, 
					tid
				)
				VALUES ( %d, %d )", 
		        array(
		        	$current_user->ID, 
		        	$new_tid
		        	) 
		        ) );
		}			
						
		// Get post owner name and prepare email body
		$owner_name = $wpdb->get_var($wpdb->prepare("SELECT display_name FROM ".$wpdb->base_prefix."users WHERE ID = %d", $current_user->ID));
		$body = "<p>".$owner_name." ".__('has started a new topic', WPS_TEXT_DOMAIN);
		$category = $wpdb->get_var($wpdb->prepare("SELECT title FROM ".$wpdb->prefix."symposium_cats WHERE cid = %d", $cat_id));
		$body .= " ".__('in', WPS_TEXT_DOMAIN)." ".$category;
		$body .= "...</p>";
							
		$body .= "<span style='font-size:24px'>".$new_topic_subject."</span><br /><br />";
		$body .= "<p>".$new_topic_text."</p>";
		$url = $forum_url.$q."cid=".$cat_id."&show=".$new_tid;
		$body .= "<p><a href='".$url."'>".$url."</a></p>";
		$body = str_replace(chr(13), "<br />", $body);
		$body = str_replace("\\r\\n", "<br />", $body);
		$body = str_replace("\\", "", $body);
		
		if ($topic_approved == "on") {
			// Email people who want to know	
			$query = $wpdb->get_results("
				SELECT user_email
				FROM ".$wpdb->base_prefix."users u RIGHT JOIN ".$wpdb->prefix."symposium_subs s ON s.uid = u.ID 
				WHERE s.tid = 0 AND u.ID != ".$current_user->ID." AND s.cid = ".$cat_id);
				
			if ($query) {					
				foreach ($query as $user) {
					__wps__sendmail($user->user_email, __('New Forum Topic', WPS_TEXT_DOMAIN), $body);						
				}						
			}
		} else {
			// Email admin if post needs approval
			$body = "<span style='font-size:24px font-style:italic;'>".__('Moderation Required', WPS_TEXT_DOMAIN)."</span><br /><br />".$body;
			__wps__sendmail(get_bloginfo('admin_email'), __('Moderation Required', WPS_TEXT_DOMAIN), $body);
		}	

		// Hook to allow other actions
		$post = __('Started a new forum topic:', WPS_TEXT_DOMAIN).' <a href="'.$url.'">'.$new_topic_subject.'</a>';
		do_action('__wps__forum_newtopic_hook', $current_user->ID, $current_user->display_name, $current_user->ID, $post, 'forum', $new_tid);

		// redirect to new post
		$a = isset($_GET['a']) ? 'a='.$_GET['a'] : '';
		?>
		<script>
		window.location.href = "<?php echo 'forum.php?cat_id='.$cat_id.'&tid='.$new_tid.'&'.$a; ?>";
		</script>
		<?php
		exit;	
		
		
	} else {
		echo $cat_roles.','.$user_role;
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
if ( !is_user_logged_in() ) {

	include_once('./header_loggedout.php');

} else {

	include_once('./header_loggedin.php');
	show_header('[home,forum,topics,replies,friends]');

	require_once('mobile-default-constants.php');
	require_once(WPS_PLUGIN_DIR.'/class.wps.php');
	require_once(WPS_PLUGIN_DIR.'/class.wps_user.php');
	require_once(WPS_PLUGIN_DIR.'/class.wps_ui.php');
	require_once(WPS_PLUGIN_DIR.'/class.wps_forum.php');
	
	$cat_name = $wpdb->get_var($wpdb->prepare("SELECT title FROM ".$wpdb->prefix."symposium_cats WHERE cid = %d", $cat_id));
	if ($cat_name == '') { $cat_name = 'Top level'; }

	// Check to see if this member is in the included list of roles
	$user = get_userdata( $current_user->ID );
	$can_edit = false;
	$viewer = str_replace('_', '', str_replace(' ', '', strtolower(get_option(WPS_OPTIONS_PREFIX.'_forum_editor'))));
	if ($user) {
		$capabilities = $user->{$wpdb->prefix.'capabilities'};

		if ($capabilities) {
			
			foreach ( $capabilities as $role => $name ) {
				if ($role) {
					$role = strtolower($role);
					$role = str_replace(' ', '', $role);
					$role = str_replace('_', '', $role);
					if (WPS_DEBUG) $html .= 'Checking global forum (symposium_functions) role '.$role.' against '.$viewer.'<br />';
					if (strpos($viewer, $role) !== FALSE) $can_edit = true;
				}
			}		 														
		
		}	
	}
	if (strpos($viewer, __('everyone', WPS_TEXT_DOMAIN)) !== FALSE) $can_edit = true;
	
	// New Topic Form	
	$new_topic_form = "";
	if ($can_edit) {

		$new_topic_form .= '<form action="#" method="POST">';
			
			$new_topic_form .= '<div class="new-topic-category label">'.__("Select a Category", WPS_TEXT_DOMAIN).'<br />';
			if (current_user_can('level_10')) {
				$categories = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix.'symposium_cats ORDER BY title');			
			} else {
				$categories = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix.'symposium_cats WHERE allow_new = "on" ORDER BY title');			
			}
			if ($categories) {
				$new_topic_form .= '<select name="new_topic_category" class="dropdown">';
	
				foreach ($categories as $category) {

					// Check if high enough rank (and applicable)
					if ( substr(get_option(WPS_OPTIONS_PREFIX.'_forum_ranks'), 0, 2) == 'on' ) {
						$min_rank = $category->min_rank;
						$my_count = __wps__forum_rank_points($current_user->ID);
						if (WPS_DEBUG) $html .= "Minimum rank = ".$min_rank." (my score = ".$my_count.")";
						if ($my_count >= $min_rank) {
							$rank_ok = true;
						} else {
							$rank_ok = false;
						}
					} else {
						$rank_ok = true;
					}
												
					// Check that permitted to category
					$cat_roles = unserialize($category->level);
					$cat_roles = str_replace('_', '', str_replace(' ', '', $cat_roles));

					if ($rank_ok && (strpos(strtolower($cat_roles), 'everyone,') !== FALSE || strpos(strtolower($cat_roles), $user_role.',') !== FALSE)) {		

						$new_topic_form .= '<option value='.$category->cid;
						if ($cat_id > 0) {
							if (isset($defaultcat) && $category->cid == $cat_id) { $new_topic_form .= " SELECTED"; }
						} else {
							if (isset($defaultcat) && $category->cid == $defaultcat) { $new_topic_form .= " SELECTED"; }
						}
						$title = stripslashes($category->title);
						if ($category->allow_new != 'on') $title .= ' '.__('(admin only)', WPS_TEXT_DOMAIN);
						if ($cat_id == $category->cid)
							$new_topic_form .= ' SELECTED';
						$new_topic_form .= '>'.$title.'</option>';
																
					}
				}
	
				$new_topic_form .= '</select>';
			}
		
			
			$new_topic_form .= '<div style="clear:both"></div><br />';
			$new_topic_form .= '<div>'.__("Topic Subject", WPS_TEXT_DOMAIN).'</div>';
			$new_topic_form .= '<input class="input_text" type="text" name="new_topic_subject" value="">';
			$new_topic_form .= '<div style="clear:both"></div><br />';
			$new_topic_form .= '<div>'.__("First Post in Topic", WPS_TEXT_DOMAIN).'</div>';
			$new_topic_form .= '<textarea class="textarea" name="new_topic_post"></textarea>';
			
			$defaultcat = $wpdb->get_var($wpdb->prepare("SELECT cid FROM ".$wpdb->prefix."symposium_cats WHERE defaultcat = %s", 'on'));
			
			$new_topic_form .= '<div>';
			$forum_all = __wps__get_meta($current_user->ID, 'forum_all');
			if ($forum_all != 'on') {
				$new_topic_form .= '<input type="checkbox" class="checkbox" name="new_topic_subscribe"> '.__("Tell me when I get any replies", WPS_TEXT_DOMAIN).'<br />';
			}
			$new_topic_form .= '</div>';

			$new_topic_form .= '<div style="clear:both"></div><br />';
			$new_topic_form .= '<input id="symposium_new_post" type="submit" class="submit small green" value="'.__("Post new topic", WPS_TEXT_DOMAIN).'" />';


			$new_topic_form .= '</div>';

		$new_topic_form .= '</form>';

		echo $new_topic_form;

	} else {

		if ($allow_new == 'on') {
			$new_topic_form = "<p>".__("You are not permitted to start a new topic.", WPS_TEXT_DOMAIN);	
			if (__wps__get_current_userlevel() == 5) $new_topic_form .= sprintf(__('<br />Permissions are set via the WordPress admin dashboard->%s->Options->Forum.', WPS_TEXT_DOMAIN), WPS_WL);	
			$new_topic_forum .= "</p>";
		}
		
	}

	
}

include_once(dirname(__FILE__).'/footer.php');	
?>
</body>
</html>
