<?php

include_once('../../../../wp-config.php');

global $wpdb, $current_user, $blog_id;
wp_get_current_user();

// Accept Answer *************************************************************
if ($_POST['action'] == 'acceptAnswer') {
	
	$tid = $_POST['tid'];

	if (is_user_logged_in()) {

		if (__wps__safe_param($tid)) {
			
			$r = 'OK';

			// Get parent tid first
			$sql = "SELECT topic_parent, topic_owner FROM ".$wpdb->prefix."symposium_topics WHERE tid = %d";
			$topic_info = $wpdb->get_row($wpdb->prepare($sql, $tid));
			$topic_parent = $topic_info->topic_parent;
			$topic_owner = $topic_info->topic_owner;

			// Get owner of original topic post
			$sql = "SELECT topic_owner FROM ".$wpdb->prefix."symposium_topics WHERE tid = %d";
			$original_post_owner = $wpdb->get_var($wpdb->prepare($sql, $topic_parent));
			
			// Check to see if any answers already exits
			$sql = "SELECT COUNT(topic_answer) FROM ".$wpdb->prefix."symposium_topics WHERE topic_parent = %d AND topic_answer = 'on'";
			$answers = $wpdb->get_var($wpdb->prepare($sql, $topic_parent));			
			
			// Now clear all accepted answers for this topic (in case selected a different answer from previously chosen answer)			
			$sql = "UPDATE ".$wpdb->prefix."symposium_topics SET topic_answer = '' WHERE topic_parent = %d";
			$wpdb->get_var($wpdb->prepare($sql, $topic_parent));
			
			// Finally update new accepted answer
			$sql = "UPDATE ".$wpdb->prefix."symposium_topics SET topic_answer = 'on' WHERE tid = %d";
			$wpdb->get_var($wpdb->prepare($sql, $tid));
	
			// Prepare to return comments in JSON format
			$return_arr = array();		        
			
			// Hook for answer removed (previously there was an topic reply with topic_answer set to 'on'
			if ($answers) {
				do_action('symposium_forum_answer_removed_hook', $tid, $topic_owner, $topic_parent, $original_post_owner, $current_user->ID);
				$row_array['message'] = __('You have changed the answer. If a better answer is posted, you can change your selection again.', WPS_TEXT_DOMAIN);
				$row_array['title'] = __('Answer accepted', WPS_TEXT_DOMAIN);
			} else {
				$row_array['message'] = __('You have accepted an answer. If a better answer is posted, you can change your selection.', WPS_TEXT_DOMAIN);
				$row_array['title'] = __('Answer accepted', WPS_TEXT_DOMAIN);
			}
			array_push($return_arr, $row_array);

			// Hook for answer accepted
			do_action('symposium_forum_answer_accepted_hook', $tid, $topic_owner, $topic_parent, $original_post_owner, $current_user->ID);

			echo json_encode($return_arr);
			exit;		        
		}

	}
	
}

// Remove Uploaded Image ****************************************************************
if ($_POST['action'] == 'removeUploadedImage') {

	$folder = $_POST['folder'];
	$file = $_POST['file'];

	$html = '';
	
	if (get_option(WPS_OPTIONS_PREFIX.'_img_db') == "on") {

		if ($wpdb->query( $wpdb->prepare( "DELETE FROM ".$wpdb->prefix."symposium_topics_images WHERE filename = %s AND tid = %d AND uid = %d", $file, $folder, $current_user->ID  ) ) ) {
			$html .= 'OK';
		} else {
			$html .= __('Failed to remove image from database', WPS_TEXT_DOMAIN);
		}

	} else {
		
		if ($blog_id > 1) {			
			$src = get_option(WPS_OPTIONS_PREFIX.'_img_path').'/'.$blog_id.'/forum/'.$folder.'/'.$file;
		} else {
			$src = get_option(WPS_OPTIONS_PREFIX.'_img_path').'/forum/'.$folder.'/'.$file;
		}
		
		if (file_exists($src)) {
			if (unlink($src)) {
				$html .= 'OK';
			} else {
				$html .= __('Failed to remove image', WPS_TEXT_DOMAIN);
			}
		} else {
			$html .= __('Image to remove is not there...', WPS_TEXT_DOMAIN).' '.$src;
		}
	}
	
	echo $html;
	exit;
	
}

// Update Score *************************************************************
if ($_POST['action'] == 'updateTopicScore') {
	
	$tid = $_POST['tid'];
	$change = $_POST['change'];

	if (is_user_logged_in()) {

		if (__wps__safe_param($tid)) {
			
			$r = 'OK';
			
			// Check if already voted on this post and remove if so
			$sql = "SELECT COUNT(*) FROM ".$wpdb->prefix."symposium_topics_scores WHERE tid = %d and uid = %d";
			$already_voted = $wpdb->get_var($wpdb->prepare($sql, $tid, $current_user->ID));

			if ($already_voted > 0) {
				$sql = "DELETE FROM ".$wpdb->prefix."symposium_topics_scores WHERE tid = %d and uid = %d";
				$wpdb->query($wpdb->prepare($sql, $tid, $current_user->ID));
				
				$r = __('Thank you for voting. You can only have one vote per post, so your previous vote has been replaced.', WPS_TEXT_DOMAIN);
			}			

			// Insert new vote
			$wpdb->query( $wpdb->prepare( "
				INSERT INTO ".$wpdb->prefix."symposium_topics_scores 
				( 	tid,
					uid, 
					score, 
					topic_date
				)
				VALUES ( %d, %d, %s, %s )", 
		        array(
		        	$tid, 
		        	$current_user->ID,
		        	$change, 
		        	date("Y-m-d H:i:s")
		        	) 
		        ) );

			// Get latest vote total
			$sql = "SELECT SUM(score) FROM ".$wpdb->prefix."symposium_topics_scores WHERE tid = %d";
			$voted = $wpdb->get_var($wpdb->prepare($sql, $tid));

			// Prepare to return comments in JSON format
			$return_arr = array();
			$row_array['str'] = stripslashes($r);
			$row_array['score'] = $voted;
		        
			array_push($return_arr, $row_array);
			
			echo json_encode($return_arr);
			exit;		        
		}

	}
	
}


// Delete Reply *************************************************************
if ($_POST['action'] == 'deleteReply') {

	if (is_user_logged_in()) {

		$tid = $_POST['topic_id'];

		// Get owner of this reply
		$sql = "SELECT topic_owner FROM ".$wpdb->prefix."symposium_topics WHERE tid = %d";
		$owner = $wpdb->get_var($wpdb->prepare($sql, $tid));
		
		if (can_manage_forum() || $owner == $current_user->ID) {
			if (__wps__safe_param($tid)) {
				$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."symposium_topics WHERE tid = %d", $tid));
			}
		
			echo $tid;
			
			// Hook for more actions
			do_action('symposium_forum_delete_reply_hook', $owner, $tid);			
		
		} else {
			echo "NOT ADMIN OR OWNER";
		}
	
	}
}

// Delete Topic and Replies *************************************************
if ($_POST['action'] == 'deleteTopic') {

	if (is_user_logged_in()) {

		$tid = $_POST['topic_id'];
		$topic_owner = $wpdb->get_var($wpdb->prepare("SELECT topic_owner FROM ".$wpdb->prefix."symposium_topics WHERE tid = %d", $tid));

		if (current_user_can('level_10') || $current_user->ID == $topic_owner) {
			if (__wps__safe_param($tid)) {
				$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."symposium_topics WHERE topic_parent = %d", $tid));
				$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."symposium_topics_scores WHERE tid = %d", $tid));
				$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."symposium_topics WHERE tid = %d", $tid));
				$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."symposium_subs WHERE tid = %d", $tid));
				
				// Delete comments
				$sql = "DELETE FROM ".$wpdb->base_prefix."symposium_comments WHERE subject_uid = %d 
					AND author_uid = %d 
					AND comment LIKE '%".__('Started a new forum topic:', WPS_TEXT_DOMAIN)."%' 
					AND comment LIKE '%show=%d%%' 
					AND type = 'forum'";					
				$wpdb->query($wpdb->prepare($sql, $current_user->ID, $current_user->ID, $tid));	
							
			}

			// Hook for more actions
			do_action('symposium_forum_delete_topic_hook', $topic_owner, $tid);			
		
			echo 'OK';
		
		} else {
			echo "NOT ADMIN OR OWNER";
		}
	}
}

// New Topic ****************************************************************
if ($_POST['action'] == 'forumNewPost') {

	if (is_user_logged_in()) {

		$new_topic_subject = $_POST['subject'];
		$new_topic_text = $_POST['text'];
		
		if (isset($_POST['category'])) { $new_topic_category = $_POST['category']; } else { $new_topic_category = 0; }
		$new_topic_subscribe = $_POST['subscribed'];
		$info_only = $_POST['info_only'];
		$group_id = $_POST['group_id'];
		if ($group_id > 0) { $new_topic_category = 0; }

		if (get_option(WPS_OPTIONS_PREFIX.'_striptags') == 'on') {
			$new_topic_subject = strip_tags($new_topic_subject);
			$new_topic_text = strip_tags($new_topic_text);
		}
		
		// Check for moderation
		if (get_option(WPS_OPTIONS_PREFIX.'_moderation') == "on" && __wps__get_current_userlevel() < 5) {
			$topic_approved = "";
		} else {
			$topic_approved = "on";
		}

		if ($new_topic_subject == '') { $new_topic_subject = __('No subject', WPS_TEXT_DOMAIN); }
		if ($new_topic_text == '') { $new_topic_text = __('No message', WPS_TEXT_DOMAIN);  }
	
		// Get forum URL worked out
		$forum_url = __wps__get_url('forum');
		if (strpos($forum_url, '?') !== FALSE) {
			$q = "&";
		} else {
			$q = "?";
		}
	
		// Get group URL worked out
		if ($group_id > 0) {
			$forum_url = __wps__get_url('group');
			if (strpos($forum_url, '?') !== FALSE) {
				$q = "&gid=".$group_id."&";
			} else {
				$q = "?gid=".$group_id."&";
			}
		}
			
		// Store new topic in post
	
		// Replace carriage returns
		$new_topic_text = str_replace("\n", chr(13), $new_topic_text);	
	
		// Don't allow HTML in subject
		$new_topic_subject = str_replace("<", "&lt;", $new_topic_subject);
		$new_topic_subject = str_replace(">", "&gt;", $new_topic_subject);
		// Don't allow HTML in body if not using WYSIWYG editor
		if (get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg') != 'on') {
			$new_topic_text = str_replace("<", "&lt;", $new_topic_text);
			$new_topic_text = str_replace(">", "&gt;", $new_topic_text);
		}

		// Avoid shortcodes
		$new_topic_subject = str_replace("[", "&#91;", $new_topic_subject);
		$new_topic_subject = str_replace("]", "&#93;", $new_topic_subject);
		$new_topic_text = str_replace("[", "&#91;", $new_topic_text);
		$new_topic_text = str_replace("]", "&#93;", $new_topic_text);

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
				$info_only,
				$group_id,
				$_SERVER['REMOTE_ADDR'],
				isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : ''
	        	) 
	        ) );
        
		// Store subscription if wanted
		$new_tid = $wpdb->insert_id;
		if ($new_topic_subscribe == 'on') {
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

		// Check for any tmp uploaded files for this post and transfer to permenant storage
		if (get_option(WPS_OPTIONS_PREFIX.'_img_db') == "on") {
			
			$wpdb->query( $wpdb->prepare( "UPDATE ".$wpdb->base_prefix."symposium_topics_images SET tid = %d WHERE tid = 0 AND uid = %d", $new_tid, $current_user->ID ));
			
		} else {

			if ($blog_id > 1) {
				$tmp_path = get_option(WPS_OPTIONS_PREFIX.'_img_path').'/'.$blog_id.'/forum/0_'.$current_user->ID.'_tmp';
				$to_path = get_option(WPS_OPTIONS_PREFIX.'_img_path').'/'.$blog_id.'/forum/'.$new_tid;
			} else {
				$tmp_path = get_option(WPS_OPTIONS_PREFIX.'_img_path').'/forum/0_'.$current_user->ID.'_tmp';
				$to_path = get_option(WPS_OPTIONS_PREFIX.'_img_path').'/forum/'.$new_tid;
			}
			if (file_exists($tmp_path)) {
				mkdir($to_path, 0777, true);
				// copy tmp files to new location
				$handler = opendir($tmp_path);
				while ($file = readdir($handler)) {
					if ($file != "." && $file != ".." && $file != ".DS_Store") {
						copy($tmp_path.'/'.$file, $to_path.'/'.$file);
						unlink($tmp_path.'/'.$file);
					}
				}
				__wps__rrmdir_tmp($tmp_path);
				closedir($handler);
			}
			
		}
				
		// Set category to the category posted into
		$cat_id = $new_topic_category;
	
		// Update last activity (if posting to a group)
		if ($group_id > 0) {
			$wpdb->query( $wpdb->prepare( "UPDATE ".$wpdb->base_prefix."symposium_groups SET last_activity = %s WHERE gid = %d", array( date("Y-m-d H:i:s"), $group_id ) ));
		}
		
		// Email admin if post needs approval
		if (get_option(WPS_OPTIONS_PREFIX.'_permalink_structure') && $group_id == 0) {
			$perma_cat = __wps__get_forum_category_part_url($cat_id);
			$url = __wps__get_url('forum').'/'.$perma_cat.$stub;
			$cat_url = __wps__get_url('forum').'/'.$perma_cat;
		} else {
			$url = $forum_url.$q."cid=".$cat_id."&show=".$new_tid;
			$cat_url = $forum_url.$q."cid=".$cat_id;
		}
		if ($topic_approved != 'on') {
			$owner_name = $wpdb->get_var($wpdb->prepare("SELECT display_name FROM ".$wpdb->base_prefix."users WHERE ID = %d", $current_user->ID));
			$body = "<p>".$owner_name." ".__('has started a new topic', WPS_TEXT_DOMAIN);
			$category = $wpdb->get_var($wpdb->prepare("SELECT title FROM ".$wpdb->prefix."symposium_cats WHERE cid = %d", $cat_id));
			$body .= " ".__('in', WPS_TEXT_DOMAIN)." ".$category;
			$body .= "...</p>";
							
			$body .= "<span style='font-size:24px'>".$new_topic_subject."</span><br /><br />";
			$body .= "<p>".$new_topic_text."</p>";
			$body .= "<p><a href='".$url."'>".$url."</a></p>";
			$body = str_replace(chr(13), "<br />", $body);
			$body = str_replace("\\r\\n", "<br />", $body);
			$body = str_replace("\\", "", $body);
			$body = "<span style='font-size:24px font-style:italic;'>".__('Moderation Required', WPS_TEXT_DOMAIN)."</span><br /><br />".$body;
			__wps__sendmail(get_bloginfo('admin_email'), __('Moderation Required', WPS_TEXT_DOMAIN), $body);
		}

		// Hook to add new forum topic to activity
		if (!get_option(WPS_OPTIONS_PREFIX.'_wps_lite')) {
			$category = '<a href="'.$cat_url.'">'.$wpdb->get_var($wpdb->prepare("SELECT title FROM ".$wpdb->prefix."symposium_cats WHERE cid = %d", $cat_id)).'</a>';
			if ($group_id == 0) {
				$prompt = sprintf(__('Started a new forum topic in %s:', WPS_TEXT_DOMAIN), $category);
			} else {
				$prompt = sprintf(__('Started a new group forum topic:', WPS_TEXT_DOMAIN), $category);
			}
			$post = $prompt.' <a href="'.$url.'">'.$new_topic_subject.'</a>';
			do_action('__wps__forum_newtopic_hook', $current_user->ID, $current_user->display_name, $current_user->ID, $post, 'forum', $new_tid);			
		}

		// Return new Topic ID
		echo $new_tid.'[|]'.$url;
		exit;	
				
	} else {
	
		echo 'NOT LOGGED IN';
		exit;
		
	}

}

// New Topic (send notification emails) ****************************************************************
if ($_POST['action'] == 'forumNewPostEmails') {

	if (is_user_logged_in()) {

		$new_tid = $_POST['new_tid'];
		$cat_id = $_POST['cat_id'];
		if ($cat_id == '') $cat_id = 0;
		$group_id = $_POST['group_id'];
				
		// Get topic information
		$sql = "SELECT * FROM ".$wpdb->prefix."symposium_topics WHERE tid = %d";
		$topic = $wpdb->get_row($wpdb->prepare($sql, $new_tid));
		$new_topic_subject = stripslashes($topic->topic_subject);
		$new_topic_text = stripslashes($topic->topic_post);
		$stub = stripslashes($topic->stub);
		
		if ($topic->topic_approved == 'on') {
		
			// Get forum URL worked out
			$forum_url = __wps__get_url('forum');
			$q = __wps__string_query($forum_url);
	
			// Get group URL worked out
			if ($group_id > 0) {
				$forum_url = __wps__get_url('group');
				$q = __wps__string_query($forum_url);
			}
	
			// Get post owner name and prepare email body
			$owner_name = $wpdb->get_var($wpdb->prepare("SELECT display_name FROM ".$wpdb->base_prefix."users WHERE ID = %d", $current_user->ID));
			$body = "<p>".$owner_name." ".__('has started a new topic', WPS_TEXT_DOMAIN);
			$category = $wpdb->get_var($wpdb->prepare("SELECT title FROM ".$wpdb->prefix."symposium_cats WHERE cid = %d", $cat_id));
							
			$body .= " ".__('in', WPS_TEXT_DOMAIN)." ".$category;
			$body .= "...</p>";

			$body .= "<span style='font-size:24px'>".$new_topic_subject."</span><br /><br />";
			$body .= "<p>".$new_topic_text."</p>";
			if (get_option(WPS_OPTIONS_PREFIX.'_permalink_structure') && $group_id == 0) {
				$perma_cat = __wps__get_forum_category_part_url($cat_id);
				$url = $forum_url.'/'.$perma_cat.$stub;
			} else {
				if ($group_id == 0) {
					$url = $forum_url.$q."cid=".$cat_id."&show=".$new_tid;
				} else {
					$url = $forum_url.$q."gid=".$group_id."&cid=".$cat_id."&show=".$new_tid;
				}
			}
			$body .= "<p><a href='".$url."'>".$url."</a></p>";
			$body = str_replace(chr(13), "<br />", $body);
			$body = str_replace("\\r\\n", "<br />", $body);
			$body = str_replace("\\", "", $body);

			if (function_exists('__wps__mailinglist')) { 
				$subject_add = ' #TID='.$new_tid.' ['.__('do not edit', WPS_TEXT_DOMAIN).']'; 
				$body_prefix = get_option(WPS_OPTIONS_PREFIX.'_mailinglist_prompt').'<br />'.get_option(WPS_OPTIONS_PREFIX.'_mailinglist_divider').'<br /><br />'.get_option(WPS_OPTIONS_PREFIX.'_mailinglist_divider_bottom').'<br /><br />';
			} else {
				$subject_add = '';
				$body_prefix = '';
			}
														
		
			// Email people who want to know	
			
			$email_list = '0,';
			
			// Get list of everyone who wants an email about a new topic in this category/group
			if ($group_id == 0) {
				// not a group
				$sql = "SELECT user_email, ID
						FROM ".$wpdb->base_prefix."users u RIGHT JOIN ".$wpdb->prefix."symposium_subs s ON s.uid = u.ID 
						WHERE s.tid = 0 AND u.ID != %d AND s.cid = %d";
				$cid = $cat_id;
				$query = $wpdb->get_results($wpdb->prepare($sql, $current_user->ID, $cid));
			} else {
				// a group, so only select group members
				$sql = "SELECT user_email, ID
						FROM ".$wpdb->base_prefix."users u RIGHT JOIN ".$wpdb->prefix."symposium_subs s ON s.uid = u.ID 
						INNER JOIN ".$wpdb->prefix."symposium_group_members m ON s.uid = m.member_id AND s.cid = m.group_id
						WHERE s.tid = 0 AND u.ID != %d AND s.cid = %d";

				$sql = "SELECT user_email, ID 
						FROM ".$wpdb->base_prefix."users u 
						INNER JOIN ".$wpdb->prefix."symposium_subs s ON (s.uid = u.ID AND s.cid = %d)
						INNER JOIN ".$wpdb->prefix."symposium_group_members m ON (s.uid = m.member_id AND m.group_id = %d)
						WHERE s.tid = 0 AND u.ID != %d";

				$cid = $group_id + 10000;
				$query = $wpdb->get_results($wpdb->prepare($sql, $cid, $group_id, $current_user->ID));
			}
			if (WPS_DEBUG) echo $wpdb->last_query.'<br><br>';
			
			// Work out mail subject
			if (strpos(get_option(WPS_OPTIONS_PREFIX.'_subject_forum_new'), '[topic]') !== FALSE) {
				$topic = $wpdb->get_var($wpdb->prepare("SELECT topic_subject FROM ".$wpdb->prefix."symposium_topics WHERE tid = %d", $new_tid));
				$subject = str_replace("[topic]", $topic, get_option(WPS_OPTIONS_PREFIX.'_subject_forum_new'));
			} else {
				$subject = get_option(WPS_OPTIONS_PREFIX.'_subject_forum_new');
			}	
																	
			if ($query) {					

				global $current_user;

				foreach ($query as $user) {
					
					// Hook and Filter to allow further actions to take place
					apply_filters ('__wps__forum_newtopic_filter', $user->ID, $current_user->ID, $current_user->display_name, $url);

					// Add to list of those sent to
					$email_list .= $user->ID.',';
					
					// Send mail
					__wps__sendmail($user->user_email, $subject.$subject_add, $body_prefix.$body);						
				}						
			}

			// Now send to everyone who wants to know about all new topics and replies (if allowed)
			
			if (get_option(WPS_OPTIONS_PREFIX.'_allow_subscribe_all') == "on") {
				
				$email_list .= '0';
				if ($group_id == 0) {
					$sql = "SELECT ID,user_email FROM ".$wpdb->base_prefix."users u 
					    LEFT JOIN ".$wpdb->base_prefix."usermeta m ON u.ID = m.user_id
						WHERE u.ID != %d AND
						m.meta_key = 'symposium_forum_all' AND
						m.meta_value = 'on' AND
						u.ID NOT IN (".$email_list.")";
						$query = $wpdb->get_results($wpdb->prepare($sql, $current_user->ID));	
				} else {
					// just group members
					$sql = "SELECT ID,user_email FROM ".$wpdb->base_prefix."users u 
					    LEFT JOIN ".$wpdb->base_prefix."usermeta m ON u.ID = m.user_id
					    INNER JOIN ".$wpdb->prefix."symposium_group_members gm ON (u.ID = gm.member_id AND gm.group_id = %d)
						WHERE u.ID != %d AND
						m.meta_key = 'symposium_forum_all' AND
						m.meta_value = 'on' AND
						u.ID NOT IN (".$email_list.")";
						$query = $wpdb->get_results($wpdb->prepare($sql, $group_id, $current_user->ID));	
				}
				
				if (WPS_DEBUG) echo $wpdb->last_query.'<br><br>';
				
				if ($group_id == 0) {
					// Get list of permitted roles for this topic category
					$sql = "SELECT level FROM ".$wpdb->prefix."symposium_cats WHERE cid = %d";
					$level = $wpdb->get_var($wpdb->prepare($sql, $cat_id));
					$cat_roles = unserialize($level);					
				}
							
				if ($query) {						
					foreach ($query as $user) {	

						if (WPS_DEBUG) echo "Checking ".$user->user_email."<br>";
						$continue = false;
						if ($group_id == 0) {
							
							if (WPS_DEBUG) echo "Not a group<br>";

							// Get role of recipient user
							$the_user = get_userdata( $user->ID );
							$user_email = $the_user->user_email;
							$capabilities = $the_user->{$wpdb->prefix . 'capabilities'};
	
							if ( !isset( $wp_roles ) )
								$wp_roles = new WP_Roles();
								
							$user_role = 'NONE';
							if ($capabilities != null) {
								foreach ( $wp_roles->role_names as $role => $name ) {
								
									if ( array_key_exists( $role, $capabilities ) )
										$user_role = $role;
								}
							}
	
							// Check in this topics category level
							if (strpos(strtolower($cat_roles), 'everyone,') !== FALSE || strpos(strtolower($cat_roles), $user_role.',') !== FALSE) {	
								$continue = true;
								if (WPS_DEBUG) "Valid permission<br>";								
							} else {
								if (WPS_DEBUG) "In valid permission<br>";								
							}
							
						} else {						
							$user_email = $user->user_email;	
							if (WPS_DEBUG) echo "A group<br>";
							$continue = true;
						}
						
						if ($continue) {
							
							if (WPS_DEBUG) echo "Continue.<br>";
							
							// Filter to allow further actions to take place
							apply_filters ('__wps__forum_newtopic_filter', $user->ID, $current_user->ID, $current_user->display_name, $url);
				
							// Send mail
							__wps__sendmail($user_email, $subject.$subject_add, $body_prefix.$body);							
							
						} else {
							if (WPS_DEBUG) echo "Don't continue.<br>";
						}
						
					}
				}
			}	
						
			echo '';
			exit;
	
		} // endif topic_approved == 'on'	
				
	} else {
	
		echo 'NOT LOGGED IN';
		exit;
		
	}

}

// Get Topic ****************************************************************
if ($_POST['action'] == 'getTopic') {
		
	$topic_id = $_POST['topic_id'];
	$group_id = $_POST['group_id'];

	echo __wps__getTopic($topic_id, $group_id);
	exit;
}

// Get Forum ****************************************************************
if ($_POST['action'] == 'getForum') {

	$cat_id = $_POST['cat_id'];
	
	if (isset($_POST['limit_from'])) { $limit_from = $_POST['limit_from']; } else { $limit_from = 0; }
	$group_id = $_POST['group_id'];
	
	echo __wps__getForum($cat_id, $limit_from, $group_id);

}

function get_topic_count($cat) {
	
	global $wpdb, $current_user;

	$topic_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM ".$wpdb->prefix."symposium_topics WHERE (topic_approved = 'on' OR topic_owner = %d) AND topic_parent = 0 AND topic_category = %d", $current_user->ID, $cat));

	$category_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM ".$wpdb->prefix."symposium_cats WHERE cat_parent = %d", $cat));

	return $topic_count+$category_count;	
	exit;
}

// Comment on Reply ****************************************************************
if ($_POST['action'] == 'replycomment') {
	
	if (is_user_logged_in()) {

		$tid = $_POST['tid'];
		$cat_id = $_POST['cid'];
		$rid = $_POST['rid'];
		$comment_text = $_POST['comment_text'];
		
		$striptags = get_option(WPS_OPTIONS_PREFIX.'_striptags');
		if ($striptags == 'on') {
			$comment_text = strip_tags($comment_text);
		}
		
		$group_id = $_POST['group_id'];
	
		$wpdb->show_errors;
	
		if ($comment_text != '') {
	
			// Get forum URL worked out
			$forum_url = __wps__get_url('forum');
			if (strpos($forum_url, '?') !== FALSE) {
				$q = "&";
			} else {
				$q = "?";
			}
		
			// Get group URL worked out
			if ($group_id > 0) {
				$forum_url = __wps__get_url('group');
				if (strpos($forum_url, '?') !== FALSE) {
					$q = "&gid=".$group_id."&";
				} else {
					$q = "?gid=".$group_id."&";
				}
			}
		
			// Don't allow HTML in subject if not using WYSIWYG editor
			if (get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg') != 'on') {
				$comment_text = str_replace("<", "&lt;", $comment_text);
				$comment_text = str_replace(">", "&gt;", $comment_text);
			}

			// Avoid shortcodes
			$comment_text = str_replace("[", "&#91;", $comment_text);
			$comment_text = str_replace("]", "&#93;", $comment_text);

			// Check for banned words
			$chatroom_banned = get_option(WPS_OPTIONS_PREFIX.'_chatroom_banned');
			if ($chatroom_banned != '') {
				$badwords = $pieces = explode(",", $chatroom_banned);

				 for($i=0;$i < sizeof($badwords);$i++){
				 	if (strpos(' '.$reply_text.' ', $badwords[$i])) {
					 	$comment_text=eregi_replace($badwords[$i], "***", $comment_text);
				 	}
				 }
			}

			// Store new comment
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
				topic_group,
				topic_answer,
				remote_addr,
				http_x_forwarded_for
			)
			VALUES ( %s, %d, %s, %s, %s, %d, %d, %d, %s, %d, %s, %s, %s )", 
	        array(
	        	'', 
	        	$cat_id,
	        	$comment_text, 
	        	date("Y-m-d H:i:s"), 
				date("Y-m-d H:i:s"), 
				$current_user->ID, 
				$rid,
				0,
				'on',
				$group_id,
				'',
				$_SERVER['REMOTE_ADDR'],
				$_SERVER['HTTP_X_FORWARDED_FOR']
				
	        	) 
	        ) );
	
			//if (WPS_DEBUG) echo $wpdb->last_query.'<br />';

			// get new topic (comment) id
			$new_id = $wpdb->insert_id;

			// Update last activity (if posting to a group)
			if ($group_id > 0) {
				$wpdb->query( $wpdb->prepare( "UPDATE ".$wpdb->base_prefix."symposium_groups SET last_activity = %s WHERE gid = %d", array( date("Y-m-d H:i:s"), $group_id ) ));
			}
        
			// Hook for more actions
			do_action('symposium_forum_replycomment_hook', $current_user->ID, $current_user->display_name, $new_id);			
		
			// Send back id of comment so email notifications can be sent out
			echo $new_id;
				
		}	
		
	}
	exit;
}

// New Reply (send notification emails) ****************************************************************
if ($_POST['action'] == 'replycommentemails') {

	if (is_user_logged_in()) {
		
		global $wpdb,$current_user;

		// Get URL info for forum
		$forum_url = __wps__get_url('forum');
		$q = __wps__string_query($forum_url);

		// rid is ID of the comment, so get info to be used
		$rid = $_POST['rid'];
		$sql = "SELECT topic_parent, topic_post FROM ".$wpdb->prefix."symposium_topics WHERE tid = %d";
		$comment = $wpdb->get_row($wpdb->prepare($sql, $rid));
		if (WPS_DEBUG) echo 'RID='.$rid.'<br />';
		
		// Now get info on the reply (on which the comment is being made)
		$sql = "SELECT * FROM ".$wpdb->prefix."symposium_topics WHERE tid = %d";
		$reply = $wpdb->get_row($wpdb->prepare($sql, $comment->topic_parent));
		
		if (WPS_DEBUG) {
			echo 'Sending out replycommentemails<br />';
			echo $wpdb->last_query.'<br />';
		}
				
		// Email people who want to know and prepare body
		$owner_name = $current_user->display_name;
		$parent = $wpdb->get_var($wpdb->prepare("SELECT topic_subject FROM ".$wpdb->prefix."symposium_topics WHERE tid = %d", $reply->topic_parent));
		$parent_tid = $wpdb->get_var($wpdb->prepare("SELECT tid FROM ".$wpdb->prefix."symposium_topics WHERE tid = %d", $reply->topic_parent));
		$stub = $wpdb->get_var($wpdb->prepare("SELECT stub FROM ".$wpdb->prefix."symposium_topics WHERE tid = %d", $reply->topic_parent));
		$group_id = $reply->topic_group;
		$cat_id = $reply->topic_category;
		$reply_text = stripslashes($reply->topic_post);
		$comment_text = stripslashes($comment->topic_post);
		if (WPS_DEBUG) echo $owner_name.','.$parent.','.$stub.','.$group_id.'.<br />';
		
		$body = "<span style='font-size:24px'>".$parent."</span><br /><br />";
		$body .= "<p>".$owner_name." ".__('commented', WPS_TEXT_DOMAIN)."...</p>";
		$body .= "<p>".$comment_text."</p>";
		$body .= "<p>".__('The reply commented on is', WPS_TEXT_DOMAIN)."...</p>";
		$body .= "<p>".$reply_text."</p>";
		if (get_option(WPS_OPTIONS_PREFIX.'_permalink_structure') && $group_id == 0) {
			$perma_cat = __wps__get_forum_category_part_url($cat_id);
			$url = $forum_url.'/'.$perma_cat.$stub;
		} else {
			if ($group_id == 0) {
				$forum_url = __wps__get_url('forum');
				$url = $forum_url.$q."cid=".$cat_id."&show=".$parent_tid;
			} else {
				$group_url = __wps__get_url('group');
				$url = $group_url.$q."gid=".$group_id."&cid=".$cat_id."&show=".$parent_tid;
			}
		}
		$body .= "<p><a href='".$url."'>".$url."</a></p>";
		$body = str_replace(chr(13), "<br />", $body);
		$body = str_replace("\\r\\n", "<br />", $body);
		$body = str_replace("\\", "", $body);
	
		// add section for reply-by-email
		if (function_exists('__wps__mailinglist')) { 
			$subject_add = ' #TID='.$reply->topic_parent.' ['.__('do not edit', WPS_TEXT_DOMAIN).']'; 
			$body = get_option(WPS_OPTIONS_PREFIX.'_mailinglist_prompt').'<br />'.get_option(WPS_OPTIONS_PREFIX.'_mailinglist_divider').'<br /><br />'.get_option(WPS_OPTIONS_PREFIX.'_mailinglist_divider_bottom').'<br /><br />'.'<br /><br />'.$body;
		} else {
			$subject_add = '';
		}
	
	
		// Do the sending...
		$email_list = '0,';
		$tid = $reply->topic_parent;
		$sql = "SELECT user_email, ID
				FROM ".$wpdb->base_prefix."users u 
				RIGHT JOIN ".$wpdb->prefix."symposium_subs ON ".$wpdb->prefix."symposium_subs.uid = u.ID 
				WHERE u.ID != %d AND tid = %d";
		$query = $wpdb->get_results($wpdb->prepare($sql, $current_user->ID, $parent_tid));

		if (WPS_DEBUG) echo 'Checking subscription: '.$wpdb->last_query.'<br />';
			
		if ($query) {						
			foreach ($query as $user) {	

				// Filter to allow further actions to take place
				if (WPS_DEBUG) echo 'Applying __wps__forum_newreplycomment_filter: '.$user->ID.','.$current_user->ID.','.$current_user->display_name.','.$url.'<br />';
				apply_filters ('__wps__forum_newreplycomment_filter', $user->ID, $current_user->ID, $current_user->display_name, $url);
		
				// Keep track of who sent to so far
				$email_list .= $user->ID.',';
		
				// Send mail
				if (strpos(get_option(WPS_OPTIONS_PREFIX.'_subject_forum_reply'), '[topic]') !== FALSE) {
					$subject = str_replace("[topic]", $parent, get_option(WPS_OPTIONS_PREFIX.'_subject_forum_reply'));
				} else {
					$subject = get_option(WPS_OPTIONS_PREFIX.'_subject_forum_reply');
				}
				__wps__sendmail($user->user_email, $subject.$subject_add, $body);							
			}
		}						

		// Now send to everyone who wants to know about all new topics and replies
		$email_list .= '0';
		$sql = "SELECT ID,user_email FROM ".$wpdb->base_prefix."users u 
			LEFT JOIN ".$wpdb->base_prefix."usermeta m ON u.ID = m.user_id
			WHERE u.ID != %d AND
			m.meta_key = 'symposium_forum_all' AND
			m.meta_value = 'on' AND
			u.ID NOT IN (".$email_list.")";
		$query = $wpdb->get_results($wpdb->prepare($sql, $current_user->ID));
		
		if (WPS_DEBUG) echo 'Checking subscribe-to-all: '.$wpdb->last_query.'<br />';
		
		// Get list of permitted roles for this topic category
		$sql = "SELECT level FROM ".$wpdb->prefix."symposium_cats WHERE cid = %d";
		$level = $wpdb->get_var($wpdb->prepare($sql, $cat_id));
		$cat_roles = unserialize($level);					

		if ($query) {						
			foreach ($query as $user) {	
				
				// If a group and a member of the group, or not a group forum...
				if ($group_id == 0 || __wps__member_of($group_id) == "yes") {
					
					// Get role of recipient user
					$the_user = get_userdata( $user->ID );
					$user_email = $the_user->user_email;
					$capabilities = $the_user->{$wpdb->prefix . 'capabilities'};

					if ( !isset( $wp_roles ) )
						$wp_roles = new WP_Roles();
						
					$user_role = 'NONE';
					foreach ( $wp_roles->role_names as $role => $name ) {
					
						if ( array_key_exists( $role, $capabilities ) )
							$user_role = $role;
					}
					// Check in this topics category level
					if (WPS_DEBUG) echo 'Role check: '.$group_id.','.__wps__member_of($group_id).','.strtolower($cat_roles).','.$user_role.'<br />';
					if ((__wps__member_of($group_id) == "yes") || strpos(strtolower($cat_roles), 'everyone,') !== FALSE || strpos(strtolower($cat_roles), $user_role.',') !== FALSE) {	 

						// Filter to allow further actions to take place
						if (WPS_DEBUG) echo 'Applying __wps__forum_newreplycomment_filter: '.$user->ID.','.$current_user->ID.','.$current_user->display_name.','.$url.'<br />';
						apply_filters ('__wps__forum_newreplycomment_filter', $user->ID, $current_user->ID, $current_user->display_name, $url);

						// Send mail
						if (strpos(get_option(WPS_OPTIONS_PREFIX.'_subject_forum_reply'), '[topic]') !== FALSE) {
							$subject = str_replace("[topic]", $parent, get_option(WPS_OPTIONS_PREFIX.'_subject_forum_reply'));
						} else {
							$subject = get_option(WPS_OPTIONS_PREFIX.'_subject_forum_reply');
						}
						__wps__sendmail($user_email, $subject.$subject_add, $body);							
						
					}
					
				}
			}
		}	
		
	} else {
		
		echo 'NOT LOGGED IN';
		exit;
		
	}
}

// Reply to Topic ****************************************************************
if ($_POST['action'] == 'reply') {
	
	if (is_user_logged_in()) {
		
		$show_debug = false;

		$tid = $_POST['tid'];
		$cat_id = $_POST['cid'];
		$answered = $_POST['answered'];

		$reply_text = $_POST['reply_text'];
		
		$striptags = get_option(WPS_OPTIONS_PREFIX.'_striptags');
		if ($striptags == 'on' && get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg') != 'on')
			$reply_text = strip_tags($reply_text);
		
		$group_id = $_POST['group_id'];
	
		$wpdb->show_errors;
	
		if ($reply_text != '') {
	
			// Get forum URL worked out
			$forum_url = __wps__get_url('forum');
			if (strpos($forum_url, '?') !== FALSE) {
				$q = "&";
			} else {
				$q = "?";
			}
		
			// Get group URL worked out
			if ($group_id > 0) {
				$forum_url = __wps__get_url('group');
				if (strpos($forum_url, '?') !== FALSE) {
					$q = "&gid=".$group_id."&";
				} else {
					$q = "?gid=".$group_id."&";
				}
			}
		
			// Check for moderation
			$moderation = get_option(WPS_OPTIONS_PREFIX.'_moderation');
			if ($moderation == "on") {
				$topic_approved = "";
			} else {
				$topic_approved = "on";
			}
		
			// Don't allow HTML in subject if not using WYSIWYG editor
			if (get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg') != 'on') {
				$reply_text = str_replace("<", "&lt;", $reply_text);
				$reply_text = str_replace(">", "&gt;", $reply_text);
			}

			// Avoid shortcodes
			$reply_text = str_replace("[", "&#91;", $reply_text);
			$reply_text = str_replace("]", "&#93;", $reply_text);

			// Check for banned words
			$chatroom_banned = get_option(WPS_OPTIONS_PREFIX.'_chatroom_banned');
			if ($chatroom_banned != '') {
				$badwords = $pieces = explode(",", $chatroom_banned);

				 for($i=0;$i < sizeof($badwords);$i++){
				 	if (strpos(' '.$reply_text.' ', $badwords[$i])) {
					 	$reply_text=eregi_replace($badwords[$i], "***", $reply_text);
				 	}
				 }
			}
			
			// Forwarded for?
			$for = (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '';
			// Store new reply in post					
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
				topic_group,
				topic_answer,
				remote_addr,
				http_x_forwarded_for
			)
			VALUES ( %s, %d, %s, %s, %s, %d, %d, %d, %s, %d, %s, %s, %s )", 
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
				$group_id,
				$answered,
				$_SERVER['REMOTE_ADDR'],
				$for				
	        	) 
	        ) );
	
			if (WPS_DEBUG && $show_debug) echo $wpdb->last_query.'<br />';

			// get new topic id (or response)
			$new_id = $wpdb->insert_id;

			// check for any attachments
			if (get_option(WPS_OPTIONS_PREFIX.'_img_db') == "on") {
				
				$wpdb->query( $wpdb->prepare( "UPDATE ".$wpdb->base_prefix."symposium_topics_images SET tid = %d WHERE uid = %d AND tid = 0", $new_id, $current_user->ID ));
				
			} else {
				
				// File system
				if ($blog_id > 1) {
					$to_path = get_option(WPS_OPTIONS_PREFIX.'_img_path').'/'.$blog_id.'/forum/'.$tid.'/'.$new_id;
					$tmp_path = get_option(WPS_OPTIONS_PREFIX.'_img_path').'/'.$blog_id.'/forum/'.$tid.'_'.$current_user->ID.'_tmp';
				} else {
					$to_path = get_option(WPS_OPTIONS_PREFIX.'_img_path').'/forum/'.$tid.'/'.$new_id;
					$tmp_path = get_option(WPS_OPTIONS_PREFIX.'_img_path').'/forum/'.$tid.'_'.$current_user->ID.'_tmp';
				}
				if (WPS_DEBUG && $show_debug) echo 'Looking for images in '.$tmp_path.' ';
				if (file_exists($tmp_path)) {
					if (WPS_DEBUG && $show_debug) echo 'FILE EXISTS: '.$tmp_path.' ';
					mkdir($to_path, 0777, true);
					// copy tmp files to new location
					$handler = opendir($tmp_path);
					while ($file = readdir($handler)) {
						if ($file != "." && $file != ".." && $file != ".DS_Store") {
							if (WPS_DEBUG && $show_debug) echo 'Copy '.$tmp_path.'/'.$file.' to '.$to_path.'/'.$file.'<br />';
							copy($tmp_path.'/'.$file, $to_path.'/'.$file);
							unlink($tmp_path.'/'.$file);
						}
					}
					__wps__rrmdir_tmp($tmp_path);
					closedir($handler);
				} else {
					if (WPS_DEBUG && $show_debug) echo 'FILE DOES NOT EXIST: '.$tmp_path.'<br />';
				}
				
			}

			// Update last activity (if posting to a group)
			if ($group_id > 0) {
				$wpdb->query( $wpdb->prepare( "UPDATE ".$wpdb->base_prefix."symposium_groups SET last_activity = %s WHERE gid = %d", array( date("Y-m-d H:i:s"), $group_id ) ));
			}
        
			// Update main topic date for freshness
			$bump_topics = get_option(WPS_OPTIONS_PREFIX.'_bump_topics');
			if ($bump_topics == 'on') {
				$wpdb->query( $wpdb->prepare("UPDATE ".$wpdb->prefix."symposium_topics SET topic_date = '".date("Y-m-d H:i:s")."' WHERE tid = %d", $tid) );					
			}

			// Add new forum reply to activity
			$has_plus_activated = false;
			if (get_option(WPS_OPTIONS_PREFIX.'__wps__profile_plus_activated') || get_option(WPS_OPTIONS_PREFIX.'__wps__profile_plus_network_activated')) $has_plus_activated = true;

			$option_to_check = ($group_id == 0) ? WPS_OPTIONS_PREFIX.'_show_forum_replies_on_activity' : WPS_OPTIONS_PREFIX.'_show_group_replies_on_activity';
			if ($has_plus_activated && get_option($option_to_check) && !get_option(WPS_OPTIONS_PREFIX.'_wps_lite')) {
				if (get_option(WPS_OPTIONS_PREFIX.'_permalink_structure') && $group_id == 0) {
					$stub = $wpdb->get_var($wpdb->prepare("SELECT stub FROM ".$wpdb->prefix."symposium_topics WHERE tid = %d", $tid));
					$perma_cat = __wps__get_forum_category_part_url($cat_id);
					$url = $forum_url.'/'.$perma_cat.$stub;							
					$cat_url = $forum_url.'/'.$perma_cat;
				} else {
					$url = $forum_url.$q."cid=".$cat_id."&show=".$tid;
					$cat_url = $forum_url.$q."cid=".$cat_id;
				}
				$subject = $wpdb->get_var($wpdb->prepare("SELECT topic_subject FROM ".$wpdb->prefix."symposium_topics WHERE tid = %d", $tid));
				$category = '<a href="'.$cat_url.'">'.$wpdb->get_var($wpdb->prepare("SELECT title FROM ".$wpdb->prefix."symposium_cats WHERE cid = %d", $cat_id)).'</a>';
				if ($group_id == 0) {
					$prompt = sprintf(__('Replied to a forum topic in %s:', WPS_TEXT_DOMAIN), $category);
				} else {
					$prompt = sprintf(__('Replied to a group forum topic in %s:', WPS_TEXT_DOMAIN), $category);
				}
				$post = __($prompt, WPS_TEXT_DOMAIN).' <a href="'.$url.'">'.$subject.'</a>';
				do_action('__wps__forum_newtopic_hook', $current_user->ID, $current_user->display_name, $current_user->ID, $post, 'forum', $tid);	
			}

			// Send back new tid so email notifications can be sent out
			echo $new_id;
				
		}	
		
	}
	exit;
}

// New Reply (send notification emails) ****************************************************************
if ($_POST['action'] == 'forumReplyEmails') {

	if (is_user_logged_in()) {
		
		global $wpdb,$current_user;

		// Get URL info for forum
		$forum_url = __wps__get_url('forum');
		$q = __wps__string_query($forum_url);

		// tid of reply sent, so then get info on reply
		$tid = $_POST['tid'];
		if (WPS_DEBUG) echo 'TID='.$tid.'<br />';
		$sql = "SELECT * FROM ".$wpdb->prefix."symposium_topics WHERE tid = %d";
		$reply = $wpdb->get_row($wpdb->prepare($sql, $tid));
		
		if (WPS_DEBUG) {
			echo 'Sending out forumReplyEmails<br />';
			echo $wpdb->last_query.'<br />';
		}
		
		// Email people who want to know and prepare body
		$owner_name = $current_user->display_name;
		$parent = $wpdb->get_var($wpdb->prepare("SELECT topic_subject FROM ".$wpdb->prefix."symposium_topics WHERE tid = %d", $reply->topic_parent));
		$parent_tid = $wpdb->get_var($wpdb->prepare("SELECT tid FROM ".$wpdb->prefix."symposium_topics WHERE tid = %d", $reply->topic_parent));
		$stub = $wpdb->get_var($wpdb->prepare("SELECT stub FROM ".$wpdb->prefix."symposium_topics WHERE tid = %d", $reply->topic_parent));
		$group_id = $reply->topic_group;
		$cat_id = $reply->topic_category;
		$reply_text = stripslashes($reply->topic_post);
		$topic_approved = $reply->topic_approved;
		if (WPS_DEBUG) echo $owner_name.','.$parent.','.$stub.','.$group_id.','.$topic_approved.'<br />';
		
		$body = "<span style='font-size:24px'>".$parent."</span><br /><br />";
		$body .= "<p>".$owner_name." ".__('replied', WPS_TEXT_DOMAIN)."...</p>";
		$body .= "<p>".$reply_text."</p>";
		if (get_option(WPS_OPTIONS_PREFIX.'_permalink_structure') && $group_id == 0) {
			$perma_cat = __wps__get_forum_category_part_url($cat_id);
			$url = $forum_url.'/'.$perma_cat.$stub;
		} else {
			if ($group_id == 0) {
				$forum_url = __wps__get_url('forum');
				$url = $forum_url.$q."cid=".$cat_id."&show=".$parent_tid;
			} else {
				$group_url = __wps__get_url('group');
				$url = $group_url.$q."gid=".$group_id."&cid=".$cat_id."&show=".$parent_tid;
			}
		}
		$body .= "<p><a href='".$url."'>".$url."</a></p>";
		$body = str_replace(chr(13), "<br />", $body);
		$body = str_replace("\\r\\n", "<br />", $body);
		$body = str_replace("\\", "", $body);
	
		// add section for reply-by-email
		if (function_exists('__wps__mailinglist')) { 
			$subject_add = ' #TID='.$reply->topic_parent.' ['.__('do not edit', WPS_TEXT_DOMAIN).']'; 
			$body = get_option(WPS_OPTIONS_PREFIX.'_mailinglist_prompt').'<br />'.get_option(WPS_OPTIONS_PREFIX.'_mailinglist_divider').'<br /><br />'.get_option(WPS_OPTIONS_PREFIX.'_mailinglist_divider_bottom').'<br /><br />'.'<br /><br />'.$body;
		} else {
			$subject_add = '';
		}
	
		$email_list = '0,';
		if ($topic_approved == "on") {
			
			$sql = "SELECT user_email, ID
					FROM ".$wpdb->base_prefix."users u 
					RIGHT JOIN ".$wpdb->prefix."symposium_subs ON ".$wpdb->prefix."symposium_subs.uid = u.ID 
					WHERE u.ID != %d AND tid = %d";
				
			$query = $wpdb->get_results($wpdb->prepare($sql, $current_user->ID, $parent_tid));
	
			if (WPS_DEBUG) echo 'Checking subscription: '.$wpdb->last_query.'<br />';
				
			if ($query) {						
				foreach ($query as $user) {	
	
					// Filter to allow further actions to take place
					if (WPS_DEBUG) echo 'Applying __wps__forum_newreply_filter: '.$user->ID.','.$current_user->ID.','.$current_user->display_name.','.$url.'<br />';
					apply_filters ('__wps__forum_newreply_filter', $user->ID, $current_user->ID, $current_user->display_name, $url);
			
					// Keep track of who sent to so far
					$email_list .= $user->ID.',';
			
					// Send mail
					if (strpos(get_option(WPS_OPTIONS_PREFIX.'_subject_forum_reply'), '[topic]') !== FALSE) {
						$subject = str_replace("[topic]", $parent, get_option(WPS_OPTIONS_PREFIX.'_subject_forum_reply'));
					} else {
						$subject = get_option(WPS_OPTIONS_PREFIX.'_subject_forum_reply');
					}
					__wps__sendmail($user->user_email, $subject.$subject_add, $body);							
				}
			}						
	
			// Now send to everyone who wants to know about all new topics and replies (if allowed)
			if (get_option(WPS_OPTIONS_PREFIX.'_allow_subscribe_all') == "on") {
				$email_list .= '0';
				$sql = "SELECT ID,user_email FROM ".$wpdb->base_prefix."users u 
				    LEFT JOIN ".$wpdb->base_prefix."usermeta m ON u.ID = m.user_id
					WHERE u.ID != %d AND
					m.meta_key = 'symposium_forum_all' AND
					m.meta_value = 'on' AND
					u.ID NOT IN (%s)";
				$query = $wpdb->get_results($wpdb->prepare($sql, $current_user->ID, $email_list));
		
				if (WPS_DEBUG) echo 'Checking subscribe-to-all: '.$wpdb->last_query.'<br />';
				
				// Get list of permitted roles for this topic category
				$sql = "SELECT level FROM ".$wpdb->prefix."symposium_cats WHERE cid = %d";
				$level = $wpdb->get_var($wpdb->prepare($sql, $cat_id));
				$cat_roles = unserialize($level);					
		
				if ($query) {						
					foreach ($query as $user) {	
						
						// If a group and a member of the group, or not a group forum...
						if ($group_id == 0 || __wps__member_of($group_id) == "yes") {
							
							// Get role of recipient user
							$the_user = get_userdata( $user->ID );
							$user_email = $the_user->user_email;
							$capabilities = $the_user->{$wpdb->prefix . 'capabilities'};
		
							if ( !isset( $wp_roles ) )
								$wp_roles = new WP_Roles();
								
							$user_role = 'NONE';
							if ($capabilities != null) {
								foreach ( $wp_roles->role_names as $role => $name ) {
								
									if ( array_key_exists( $role, $capabilities ) )
										$user_role = $role;
								}
							}
							// Check in this topics category level
							if (WPS_DEBUG) echo 'Role check: '.$group_id.','.__wps__member_of($group_id).','.strtolower($cat_roles).','.$user_role.'<br />';
							if ((__wps__member_of($group_id) == "yes") || strpos(strtolower($cat_roles), 'everyone,') !== FALSE || strpos(strtolower($cat_roles), $user_role.',') !== FALSE) {	 
		
								// Filter to allow further actions to take place
								if (WPS_DEBUG) echo 'Applying __wps__forum_newreply_filter: '.$user->ID.','.$current_user->ID.','.$current_user->display_name.','.$url.'<br />';
								apply_filters ('__wps__forum_newreply_filter', $user->ID, $current_user->ID, $current_user->display_name, $url);
		
								// Send mail
								if (strpos(get_option(WPS_OPTIONS_PREFIX.'_subject_forum_reply'), '[topic]') !== FALSE) {
									$subject = str_replace("[topic]", $parent, get_option(WPS_OPTIONS_PREFIX.'_subject_forum_reply'));
								} else {
									$subject = get_option(WPS_OPTIONS_PREFIX.'_subject_forum_reply');
								}
								__wps__sendmail($user_email, $subject.$subject_add, $body);							
								
							}
							
						}
					}
				}	
			}
		} else {
			// Email admin if post needs approval
			$body = "<span style='font-size:24px; font-style:italic;'>".__("Moderation required for a reply", WPS_TEXT_DOMAIN)."</span><br /><br />".$body;
			__wps__sendmail(get_bloginfo('admin_email'), __('Moderation required for a reply', WPS_TEXT_DOMAIN), $body);
		}		
		
	} else {
		
		echo 'NOT LOGGED IN';
		exit;
		
	}
}
	
// AJAX to fetch forum activity
if ($_POST['action'] == 'getActivity') {

	// Work out link to this page, dealing with permalinks or not
	$thispage = __wps__get_url('forum');
	$q = __wps__string_query($thispage);
	$grouppage = __wps__get_url('group');
	
	$snippet_length = get_option(WPS_OPTIONS_PREFIX.'_preview1');
	if ($snippet_length == '') { $snippet_length = '0'; }
	
	$html = '<div id="forum_activity_div">';
	
		$html .= '<div id="forum_activity_all_new_topics">';
		
			$html .= '<div id="forum_activity_title">'.__('Recent Topics', WPS_TEXT_DOMAIN).'</div>';
		
			// All topics started
			$sql = "SELECT t.*, u.display_name FROM ".$wpdb->prefix."symposium_topics t LEFT JOIN ".$wpdb->base_prefix."users u ON t.topic_owner = u.ID WHERE t.topic_approved = 'on' AND topic_parent = 0 ORDER BY topic_started DESC LIMIT 0,40";
	
			$topics = $wpdb->get_results($sql);
			if ($topics) {
				foreach ($topics as $topic) {
					
					if ($topic->topic_group == 0 || __wps__member_of($topic->topic_group) == 'yes') {
						
						$html .= "<div class='forum_activity_new_topic_subject'>";
						if ($topic->topic_group == 0) {

							if (get_option(WPS_OPTIONS_PREFIX.'_permalink_structure') && $group_id == 0) {
								$stub = $wpdb->get_var($wpdb->prepare("SELECT stub FROM ".$wpdb->prefix."symposium_topics WHERE tid = %d", $topic->tid));
								$perma_cat = __wps__get_forum_category_part_url($topic->topic_category);
								$url = $thispage.'/'.$perma_cat.$stub;							
								$html .= "<a href='".$url."'>".__wps__bbcode_remove(stripslashes($topic->topic_subject))."</a> ";
							} else {
								$html .= "<a href='".$thispage.$q.'cid='.$topic->topic_category.'&show='.$topic->tid."'>".__wps__bbcode_remove(stripslashes($topic->topic_subject))."</a>";
							}
							
						} else {
							$html .= "<a href='".$grouppage.$q.'gid='.$topic->topic_group.'&cid='.$topic->topic_category.'&show='.$topic->tid."'>".__wps__bbcode_remove(stripslashes($topic->topic_subject))."</a>";
						}
						$html .= "</div>";
						
						$text = __wps__bbcode_remove(strip_tags(stripslashes($topic->topic_post)));
						if ( strlen($text) > $snippet_length ) { $text = substr($text, 0, $snippet_length)."..."; }
						$html .= $text."<br />";
	
						$html .= "<em>".__("Started by", WPS_TEXT_DOMAIN)." ".$topic->display_name.", ".__wps__time_ago($topic->topic_started);
						
						// Replies
						$replies = $wpdb->get_results($wpdb->prepare("SELECT t.*, u.display_name FROM ".$wpdb->prefix."symposium_topics t LEFT JOIN ".$wpdb->base_prefix."users u ON t.topic_owner = u.ID WHERE topic_parent = %d ORDER BY topic_date DESC", $topic->tid));
						if ($replies) {
							$cnt = 0;
							$dt = '';
							foreach ($replies as $reply) {
								$cnt++;
								if ($dt == '') { $dt = $reply->topic_date; }
							}
							
							if ($cnt > 0) {
								$html .= ". ".$cnt." ";
								if ($cnt == 1) 
								{ 
									$html .= __("reply", WPS_TEXT_DOMAIN);
									$html .= ", ".__wps__time_ago($dt)." by ".$reply->display_name;
								} else {
									$html .= __("replies", WPS_TEXT_DOMAIN);
									$html .= ", ".__("last one", WPS_TEXT_DOMAIN)." ".__wps__time_ago($dt)." by ".$reply->display_name;
								}
								
							}
						}	
						
						$html .= ".</em>";		
						
					}	
					
				}
			} else {
				$html .= "<p>".__("No topics started yet", WPS_TEXT_DOMAIN).".</p>";
			}
		
		$html .= '</div>';

		$html .= '<div id="forum_activity_new_topics">';
		
			$html .= '<div id="forum_activity_title">'.__('You recently started', WPS_TEXT_DOMAIN).'</div>';
		
			// Topics Started
			$sql = "SELECT * FROM ".$wpdb->prefix."symposium_topics WHERE topic_approved = 'on' AND topic_owner = %d AND topic_parent = 0 ORDER BY topic_started DESC LIMIT 0,100";
	
			$topics = $wpdb->get_results($wpdb->prepare($sql, $current_user->ID));
			if ($topics) {
				foreach ($topics as $topic) {		

					if ($topic->topic_group == 0 || __wps__member_of($topic->topic_group) == 'yes') {
						
						$html .= "<div class='forum_activity_new_topic_subject'>";
						if ($topic->topic_group == 0) {

							if (get_option(WPS_OPTIONS_PREFIX.'_permalink_structure') && $group_id == 0) {
								$stub = $wpdb->get_var($wpdb->prepare("SELECT stub FROM ".$wpdb->prefix."symposium_topics WHERE tid = %d", $topic->tid));
								$perma_cat = __wps__get_forum_category_part_url($topic->topic_category);
								$url = $thispage.'/'.$perma_cat.$stub;							
								$html .= "<a href='".$url."'>".__wps__bbcode_remove(stripslashes($topic->topic_subject))."</a> ";
							} else {
								$html .= "<a href='".$thispage.$q.'cid='.$topic->topic_category.'&show='.$topic->tid."'>".__wps__bbcode_remove(stripslashes($topic->topic_subject))."</a>, ";
							}
							
							$html .= __wps__time_ago($topic->topic_date);
						} else {
							$html .= "<a href='".$grouppage.$q.'gid='.$topic->topic_group.'&cid='.$topic->topic_category.'&show='.$topic->tid."'>".__wps__bbcode_remove(stripslashes($topic->topic_subject))."</a>, ".__wps__time_ago($topic->topic_date);
						}
						$html .= "</div>";
					
						$text = __wps__bbcode_remove(strip_tags(stripslashes($topic->topic_post)));
						if ( strlen($text) > $snippet_length ) { $text = substr($text, 0, $snippet_length)."..."; }
						$html .= $text."<br />";
	
						// Replies
						$replies = $wpdb->get_results($wpdb->prepare("SELECT t.*, u.display_name FROM ".$wpdb->prefix."symposium_topics t LEFT JOIN ".$wpdb->base_prefix."users u ON t.topic_owner = u.ID WHERE topic_parent = %d ORDER BY tid DESC", $topic->tid));
						if ($replies) {
							$cnt = 0;
							$dt = '';
							$display_name = '';
							foreach ($replies as $reply) {
								$cnt++;
								if ($dt == '') { $dt = $reply->topic_date; $display_name = $reply->display_name; }
							}
							
							if ($cnt > 0) {
								$html .= "<em>".$cnt." ";
								if ($cnt == 1) 
								{ 
									$html .= __("reply", WPS_TEXT_DOMAIN);
									$html .= ", ".__wps__time_ago($dt)." by ".$display_name.".</em>";
								} else {
									$html .= __("replies", WPS_TEXT_DOMAIN);
									$html .= ", ".__("last one", WPS_TEXT_DOMAIN)." ".__wps__time_ago($dt)." by ".$display_name.".</em>";
								}
								
							}
						} else {
							$html .= "<em>".__("No replies", WPS_TEXT_DOMAIN)."</em>";
						}				
					}
				}
			} else {
				$html .= __("<p>You have not started any forum topics.</p>", WPS_TEXT_DOMAIN);
			}
		
		$html .= '</div>';
		
		$html .= '<div id="forum_activity_replies">';
		
			$html .= '<div id="forum_activity_title">'.__('Your recent replies', WPS_TEXT_DOMAIN).'</div>';
		
			// Topics Replied to
			
			$shown = '';
			$sql = "SELECT t.*, t2.topic_subject, p.tid as parent_tid, p.topic_owner as parent_owner, p.topic_date as parent_date 
			FROM ".$wpdb->prefix."symposium_topics t 
			LEFT JOIN ".$wpdb->prefix."symposium_topics t2 ON t.topic_parent = t2.tid 
			LEFT JOIN ".$wpdb->prefix."symposium_topics p ON t.topic_parent = p.tid 
			WHERE t.topic_approved = 'on' AND t.topic_owner = %d AND t.topic_parent > 0 AND t2.topic_subject != ''
			ORDER BY t.topic_date DESC LIMIT 0,75";
			
			$topics = $wpdb->get_results($wpdb->prepare($sql, $current_user->ID));
			if ($topics) {
				foreach ($topics as $topic) {	

					if ($topic->topic_group == 0 || __wps__member_of($topic->topic_group) == 'yes') {
											
						if (strpos($shown, $topic->topic_parent.",") === FALSE) { 

							$html .= "<div class='forum_activity_new_topic_subject'>";
							if ($topic->topic_group == 0) {

								if (get_option(WPS_OPTIONS_PREFIX.'_permalink_structure') && $group_id == 0) {
									$stub = $wpdb->get_var($wpdb->prepare("SELECT stub FROM ".$wpdb->prefix."symposium_topics WHERE tid = %d", $topic->topic_parent));
									$perma_cat = __wps__get_forum_category_part_url($topic->topic_category);
									$url = $thispage.'/'.$perma_cat.$stub;							
									$html .= "<a href='".$url."'>".__wps__bbcode_remove(stripslashes($topic->topic_subject))."</a> ";
								} else {
									$html .= "<a href='".$thispage.$q.'cid='.$topic->topic_category.'&show='.$topic->topic_parent."'>".__wps__bbcode_remove(stripslashes($topic->topic_subject))."</a>";
								}	
															
							} else {
								$html .= "<a href='".$grouppage.$q.'gid='.$topic->topic_group.'&cid='.$topic->topic_category.'&show='.$topic->topic_parent."'>".__wps__bbcode_remove(stripslashes($topic->topic_subject))."</a>";
							}
							$html .= "</div>";	
																				
							$text = __wps__bbcode_remove(strip_tags(stripslashes($topic->topic_post)));
							if ( strlen($text) > $snippet_length ) { $text = substr($text, 0, $snippet_length)."..."; }
							$html .= $text;
							if (get_option(WPS_OPTIONS_PREFIX.'_use_answers') == 'on' && $topic->topic_answer == 'on') {
								$html .= ' <img style="width:12px; height:12px" src="'.get_option(WPS_OPTIONS_PREFIX.'_images').'/tick.png" alt="'.__('Answer Accepted', WPS_TEXT_DOMAIN).'" />';
							}
							$html .= "<br />";
							$html .= "<em>";
							$html .= __("You replied", WPS_TEXT_DOMAIN)." ".__wps__time_ago($topic->topic_date);
							$last_reply = $wpdb->get_row($wpdb->prepare("SELECT t.*, u.display_name FROM ".$wpdb->prefix."symposium_topics t LEFT JOIN ".$wpdb->base_prefix."users u ON t.topic_owner = u.ID WHERE topic_parent = %d ORDER BY tid DESC LIMIT 0,1", $topic->topic_parent));
							if ($last_reply->topic_owner != $topic->topic_owner) {
								$html .= ", ".__("last reply by", WPS_TEXT_DOMAIN)." ".$last_reply->display_name." ".__wps__time_ago($last_reply->topic_date).".";
							} else {
								$html .= ".";
							}
							$html .= "</em>";
							
							$shown .= $topic->topic_parent.",";
						}
					}
				}
			} else {
				$html .= __("<p>You have not replied to any forum topics.</p>", WPS_TEXT_DOMAIN);
			}
		
		$html .= '</div>';		

	$html .= '</div>';
	
	echo $html;
	exit;
}

// AJAX to fetch group forum activity
if ($_POST['action'] == 'getAllActivity') {

	$symposium_last_login = __wps__get_meta($current_user->ID, 'symposium_last_login');

	$gid = $_POST['gid'];	

	// Work out link to this page, dealing with permalinks or not
	if ($gid == 0) {
		$thispage = __wps__get_url('forum');
		if ($thispage[strlen($thispage)-1] != '/') { $thispage .= '/'; }
		if (strpos($thispage, "?") === FALSE) { 
			$q = "?";
		} else {
			// No Permalink
			$q = "&";
		}
	} else {
		$thispage = __wps__get_url('group');
		if ($thispage[strlen($thispage)-1] != '/') { $thispage .= '/'; }
		if (strpos($thispage, "?") === FALSE) { 
			$q = "?";
		} else {
			// No Permalink
			$q = "&";
		}
		$q .= "gid=".$gid."&";
	}
	
	$preview = 50;	
	$postcount = 100; // Tries to retrieve last 7 days, but this will be a maximum
	
	$include = strtotime("now") - (86400 * 7); // 1 week
	$include = date("Y-m-d H:i:s", $include);
	
	// Get list of roles for this user
    $user_roles = $current_user->roles;
    $user_role = strtolower(array_shift($user_roles));	
    if ($user_role == '') $user_role = 'NONE';
	
	$html = '<div id="forum_activity_div">';
	
		// All topics started
		$sql = "
			SELECT t.tid, t.topic_subject, t.topic_owner, t.topic_post, t.topic_category, t.topic_date, u.display_name, t.topic_parent, t.topic_answer, p.topic_category as parent_category 
			FROM ".$wpdb->prefix."symposium_topics t INNER JOIN ".$wpdb->base_prefix."users u ON t.topic_owner = u.ID 
			LEFT JOIN ".$wpdb->prefix."symposium_topics p ON t.topic_parent = p.tid 
			WHERE t.topic_approved = 'on' AND t.topic_date > %s AND t.topic_group = %d 
			AND (p.topic_parent = 0 || p.topic_subject != '')
			ORDER BY t.tid DESC LIMIT 0,%d";
			
		$posts = $wpdb->get_results($wpdb->prepare($sql, $include, $gid, $postcount)); 
		if (WPS_DEBUG) $html .= $wpdb->last_query.'<br />';

		if ($posts) {

			foreach ($posts as $post)
			{
				
				// Check permitted to see forum category
				$sql = "SELECT level FROM ".$wpdb->prefix."symposium_cats WHERE cid = %d";
				$cat_levels = $wpdb->get_var($wpdb->prepare($sql, $post->topic_category));
				$cat_roles = unserialize($cat_levels);
				if ($gid > 0 || strpos(strtolower($cat_roles), 'everyone,') !== FALSE || strpos(strtolower($cat_roles), $user_role.',') !== FALSE) {				
		
					$html .= "<div class='__wps__forum_activity_row'>";
						$html .= "<div class='__wps__forum_activity_row_avatar'>";
							$html .= get_avatar($post->topic_owner, 20);
						$html .= "</div>";
						$html .= "<div class='__wps__forum_activity_row_text'>";
							if ($post->topic_parent > 0) {
								$text = strip_tags(stripslashes($post->topic_post));
								if ( strlen($text) > $preview ) { $text = substr($text, 0, $preview)."..."; }
								$html .= __wps__profile_link($post->topic_owner)." ".__('replied', WPS_TEXT_DOMAIN)." ";

								if (get_option(WPS_OPTIONS_PREFIX.'_permalink_structure') && $group_id == 0) {
									$stub = $wpdb->get_var($wpdb->prepare("SELECT stub FROM ".$wpdb->prefix."symposium_topics WHERE tid = %d", $post->topic_parent));
									$perma_cat = __wps__get_forum_category_part_url($post->parent_category);
									$url = $thispage.$perma_cat.$stub;							
									$html .= "<a href='".$url."'>".$text."</a> ";
								} else {
									$html .= "<a href='".$thispage.$q."cid=".$post->topic_category."&show=".$post->topic_parent."'>".$text."</a> ";
								}

								$html .= __wps__time_ago($post->topic_date).".";

								if (get_option(WPS_OPTIONS_PREFIX.'_use_answers') == 'on' && $post->topic_answer == 'on') {
									$html .= ' <img style="width:12px; height:12px" src="'.get_option(WPS_OPTIONS_PREFIX.'_images').'/tick.png" alt="'.__('Answer Accepted', WPS_TEXT_DOMAIN).'" />';
								}
								$html .= "<br>";
							} else {
								$text = strip_tags(stripslashes($post->topic_subject));
								if ( strlen($text) > $preview ) { $text = substr($text, 0, $preview)."..."; }
								$html .= __wps__profile_link($post->topic_owner)." ".__('started', WPS_TEXT_DOMAIN)." ";
								
								if (get_option(WPS_OPTIONS_PREFIX.'_permalink_structure') && $group_id == 0) {
									$stub = $wpdb->get_var($wpdb->prepare("SELECT stub FROM ".$wpdb->prefix."symposium_topics WHERE tid = %d", $post->tid));
									$perma_cat = __wps__get_forum_category_part_url($post->topic_category);
									$url = $thispage.$perma_cat.$stub;							
									$html .= "<a href='".$url."'>".$text."</a> ";
								} else {
									$html .= "<a href='".$thispage.$q."cid=".$post->topic_category."&show=".$post->tid."'>".$text."</a> ";
								}
								
								$html .= __wps__time_ago($post->topic_date).".<br>";

							}
						$html .= "</div>";
						if ($post->topic_date > $symposium_last_login && $post->topic_owner != $current_user->ID && is_user_logged_in() && get_option(WPS_OPTIONS_PREFIX.'_forum_stars')) {
							$html .= "<div style='float:left;'>";
								$html .= "<img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/new.gif' alt='New!' /> ";
							$html .= "</div>";
						}		
	
					$html .= "</div>";
					
				}
			}

		} else {
			$html .= "<p>".__("No topics started yet", WPS_TEXT_DOMAIN).".</p>";
		}
	
	$html .= '</div>';
	
	echo $html;
	exit;
}

// AJAX to fetch forum activity as threads
if ($_POST['action'] == 'getThreadsActivity') {

	$symposium_last_login = __wps__get_meta($current_user->ID, 'symposium_last_login');

	$gid = $_POST['gid'];
	
	$html = '<div id="forum_activity_div">';
	$html .= showThreadChildren(0, 0, $gid, $symposium_last_login);	
	$html .= '</div>';
	
	echo $html;
	exit;
}

function showThreadChildren($parent, $level, $gid, $symposium_last_login) {
	
	global $wpdb, $current_user;

	// Work out link to this page, dealing with permalinks or not
	if ($gid == 0) {
		$thispage = __wps__get_url('forum');
		if ($thispage[strlen($thispage)-1] != '/') { $thispage .= '/'; }
		if (strpos($thispage, "?") === FALSE) { 
			$q = "?";
		} else {
			// No Permalink
			$q = "&";
		}
	} else {
		$thispage = __wps__get_url('group');
		if ($thispage[strlen($thispage)-1] != '/') { $thispage .= '/'; }
		if (strpos($thispage, "?") === FALSE) { 
			$q = "?";
		} else {
			// No Permalink
			$q = "&";
		}
		$q .= "gid=".$gid."&";
	}
	
	$html = "";
	
	$preview = 50 - (10*$level);	
	if ($preview < 10) { $preview = 10; }
	$postcount = 20; // Tries to retrieve last 7 days, but this will be a maximum number of posts or replies
	
	if ($level == 0) {
		$avatar_size = 30;
		$margin_top = 10;
		$desc = "DESC";
	} else {
		$avatar_size = 20;
		$margin_top = 3;
		$desc = "DESC";
	}

	$include = strtotime("now") - (86400 * 280); // 4 weeks
	$include = date("Y-m-d H:i:s", $include);

	// All topics started
	$sql = "
		SELECT t.tid, t.topic_subject, t.topic_owner, t.topic_post, t.topic_category, t.topic_date, u.display_name, t.topic_parent, t.topic_answer, t.topic_started, p.topic_category as parent_category 
		FROM ".$wpdb->prefix.'symposium_topics'." t INNER JOIN ".$wpdb->base_prefix.'users'." u ON t.topic_owner = u.ID 
		LEFT JOIN ".$wpdb->prefix."symposium_topics p ON t.topic_parent = p.tid
		WHERE t.topic_approved = 'on' AND t.topic_parent = %d AND t.topic_group = %d AND t.topic_date > %s 
		AND (t.topic_parent = 0 || p.topic_parent = 0) 
		ORDER BY t.tid ".$desc." LIMIT 0,%d";
	$posts = $wpdb->get_results($wpdb->prepare($sql, $parent, $gid, $include, $postcount)); 

	// Get list of roles for this user
	global $current_user;
    $user_roles = $current_user->roles;
    $user_role = strtolower(array_shift($user_roles));
    if ($user_role == '') $user_role = 'NONE';

	if ($posts) {

		foreach ($posts as $post)
		{

			$sql = "SELECT level FROM ".$wpdb->prefix."symposium_cats WHERE cid = %d";
			$cat_level = $wpdb->get_var($wpdb->prepare($sql, $post->topic_category));
			$cat_roles = unserialize($cat_level);
			if ($gid > 0 || strpos(strtolower($cat_roles), 'everyone,') !== FALSE || strpos(strtolower($cat_roles), $user_role.',') !== FALSE) {		

				$html .= "<div class='__wps__forum_activity_row' style='padding-left: ".($level*40)."px; margin-top:".$margin_top."px;min-height:".$avatar_size."px;'>";		
					$html .= "<div class='__wps__forum_activity_row_avatar' style='padding-left: ".($level*40)."px;'>";
						$html .= get_avatar($post->topic_owner, $avatar_size);
					$html .= "</div>";
					$move_over = ($level == 0) ? 40 : 30;
					$html .= "<div class='__wps__forum_activity_row_text' style='margin-left: ".$move_over."px;'>";
						if ($post->topic_parent > 0) {
							$text = strip_tags(stripslashes($post->topic_post));
							if ( strlen($text) > $preview ) { $text = substr($text, 0, $preview)."..."; }
							$html .= __wps__profile_link($post->topic_owner)." ".__('replied', WPS_TEXT_DOMAIN)." ";
						
							if (get_option(WPS_OPTIONS_PREFIX.'_permalink_structure') && $group_id == 0) {
								$stub = $wpdb->get_var($wpdb->prepare("SELECT stub FROM ".$wpdb->prefix."symposium_topics WHERE tid = %d", $post->topic_parent));
								$perma_cat = __wps__get_forum_category_part_url($post->parent_category);
								$url = $thispage.$perma_cat.$stub;							
								$html .= "<a href='".$url."'>".$text."</a> ";
							} else {
								$html .= "<a href='".$thispage.$q."cid=".$post->topic_category."&show=".$post->topic_parent."'>".$text."</a> ";
							}
					
							$html .= __wps__time_ago($post->topic_date);
							if (get_option(WPS_OPTIONS_PREFIX.'_use_answers') == 'on' && $post->topic_answer == 'on') {
								$html .= ' <img style="width:12px; height:12px" src="'.get_option(WPS_OPTIONS_PREFIX.'_images').'/tick.png" alt="'.__('Answer Accepted', WPS_TEXT_DOMAIN).'" />';
							}
							$html .= "<br>";
						} else {
							$text = stripslashes($post->topic_subject);
							if ( strlen($text) > $preview ) { $text = substr($text, 0, $preview)."..."; }
							$html .= __wps__profile_link($post->topic_owner)." ".__('started', WPS_TEXT_DOMAIN)." ";

							if (get_option(WPS_OPTIONS_PREFIX.'_permalink_structure') && $group_id == 0) {
								$stub = $wpdb->get_var($wpdb->prepare("SELECT stub FROM ".$wpdb->prefix."symposium_topics WHERE tid = %d", $post->tid));
								$perma_cat = __wps__get_forum_category_part_url($post->topic_category);
								$url = $thispage.$perma_cat.$stub;							
								$html .= "<a href='".$url."'>".$text."</a> ";
							} else {
								$html .= "<a href='".$thispage.$q."cid=".$post->topic_category."&show=".$post->tid."'>".$text."</a> ";
							}
							
							$html .= __wps__time_ago($post->topic_started).".<br>";
						}
					$html .= "</div>";
					if ($post->topic_date > $symposium_last_login && $post->topic_owner != $current_user->ID && is_user_logged_in() && get_option(WPS_OPTIONS_PREFIX.'_forum_stars')) {
						$html .= "<img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/new.gif' alt='New!' /> ";
					}
				$html .= "</div>";
				
				$html .= showThreadChildren($post->tid, $level+1, $gid, $symposium_last_login);
			}			
							
		}
	}	
	
	return $html;
}

// AJAX to fetch favourites
if ($_POST['action'] == 'getFavs') {
	
	if (is_user_logged_in()) {

		$snippet_length_long = get_option(WPS_OPTIONS_PREFIX.'_preview2');
		if ($snippet_length_long == '') { $snippet_length_long = '100'; }
	
		$html = '';
	
		$favs = __wps__get_meta($current_user->ID, 'forum_favs');
		$favs = explode('[', $favs);
		if ($favs) {
			foreach ($favs as $fav) {
				$fav = str_replace("]", "", $fav);
				if ($fav != '') {
				
					$post = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."symposium_topics WHERE tid = %d", $fav));

					if ($post) {
						
						// Work out link to this page, dealing with permalinks or not
						$gid = $post->topic_group;
						if ($gid == 0) {
							$thispage = __wps__get_url('forum');
							if ($thispage[strlen($thispage)-1] != '/') { $thispage .= '/'; }
							if (strpos($thispage, "?") === FALSE) { 
								$q = "?";
							} else {
								// No Permalink
								$q = "&";
							}
						} else {
							$thispage = __wps__get_url('group');
							if ($thispage[strlen($thispage)-1] != '/') { $thispage .= '/'; }
							if (strpos($thispage, "?") === FALSE) { 
								$q = "?";
							} else {
								// No Permalink
								$q = "&";
							}
							$q .= "gid=".$gid."&";
						}
	
						$html .= '<div id="fav_'.$fav.'" class="fav_row" style="padding:6px; margin-bottom:10px;">';
	
							$html .= " <a title='".$fav."' class='__wps__delete_fav' style='cursor:pointer'><img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/delete.png' style='width:16px;height:16px' /></a>";
					
							$html .= '<div class="forum_activity_new_topic_subject">';

							if (get_option(WPS_OPTIONS_PREFIX.'_permalink_structure') && $group_id == 0) {
								$stub = $wpdb->get_var($wpdb->prepare("SELECT stub FROM ".$wpdb->prefix."symposium_topics WHERE tid = %d", $post->tid));
								$perma_cat = __wps__get_forum_category_part_url($post->topic_category);
								$url = $thispage.$perma_cat.$stub;							
								$html .= "<a href='".$url."'>".stripslashes($post->topic_subject)."</a> ";
							} else {
								$html .= '<a href="'.$thispage.$q.'cid='.$post->topic_category.'&show='.$post->tid.'">'.stripslashes($post->topic_subject).'</a>';
							}
							
							$html .= '</div>';
	
							$replies = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix.'symposium_topics'." WHERE topic_parent = %d ORDER BY topic_date DESC", $post->tid));
							if ($replies) {
								$cnt = 0;
								$dt = '';
								foreach ($replies as $reply) {
									$cnt++;
									if ($dt == '') { $dt = $reply->topic_date; }
								}
							
								if ($cnt > 0) {
									$html .= "<em>".$cnt." ";
									if ($cnt == 1) 
									{ 
										$html .= __("reply", WPS_TEXT_DOMAIN);
										$html .= ", ".__wps__time_ago($dt).".</em>";
									} else {
										$html .= __("replies", WPS_TEXT_DOMAIN);
										$html .= ", ".__("last one", WPS_TEXT_DOMAIN)." ".__wps__time_ago($dt).".</em>";
									}
								
								}
							}
	
							$text = strip_tags(stripslashes($post->topic_post));
							if ( strlen($text) > $snippet_length_long ) { $text = substr($text, 0, $snippet_length_long)."..."; }
						
							$html .= "<br />".$text;
						
						$html .= '</div>';
					}
				}
			}
		}
	
		if ($html == '') {
		
			$html .= __("You can add your favourite forum topics by clicking on the heart beside any forum topic title.", WPS_TEXT_DOMAIN);
		}
	
		echo $html;
		
	}
	exit;
}

// AJAX function to toggle post as a favourite
if ($_POST['action'] == 'toggleFav') {

	if (is_user_logged_in()) {

		$tid = $_POST['tid'];	

		// Update meta record exists for user
		$favs = __wps__get_meta($current_user->ID, "forum_favs");
		if (strpos($favs, "[".$tid."]") === FALSE) { 
			$favs .= "[".$tid."]";
			$r = "added";
		} else {
			$favs = str_replace("[".$tid."]", "", $favs);
			$r = $tid;
		}
		__wps__update_meta($current_user->ID, "forum_favs", $favs);

		echo $r;
		
	}
	exit;

}
	
// AJAX function to get topic details for editing
if ($_POST['action'] == 'getEditDetails') {

	if (is_user_logged_in()) {

		$tid = $_POST['tid'];	
	
		$details = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$wpdb->prefix.'symposium_topics'." WHERE tid = %d", $tid)); 
		if ($details->topic_subject == '') {
			$parent = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$wpdb->prefix.'symposium_topics'." WHERE tid = %d", $details->topic_parent)); 
			$subject = $parent->topic_subject;
		} else {
			$subject = $details->topic_subject;
		}
		$subject = str_replace("&lt;", "<", $subject);	
		$subject = str_replace("&gt;", ">", $subject);	
		$subject = str_replace("&#91;", "[", $subject);	
		$subject = str_replace("&#93;", "]", $subject);	
		$topic_post = $details->topic_post;
		if (get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg') == 'on') {
			$topic_post = htmlspecialchars($topic_post);
			$topic_post = str_replace(chr(10), "<br />", $topic_post);
			$topic_post = str_replace(chr(13), "<br />", $topic_post);
		} else {
			$topic_post = str_replace("<br />", chr(10), $topic_post);
			$topic_post = str_replace("<p>", "", $topic_post);
			$topic_post = str_replace("</p>", "", $topic_post);
		}
		
		$post_text = stripslashes($topic_post);

		$post_text = __wps__make_url(stripslashes($post_text));
		$has_code = strpos($post_text, '&#91;code&#93;') ? true : false;
		if (!get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg')) {
			$post_text = str_replace(chr(10), "<br />", $post_text);
			$post_text = str_replace(chr(13), "<br />", $post_text);
		} else {
			$post_text = __wps__bbcode_replace($post_text); 
		}

		if ($details) {
			$p = stripslashes($post_text);
			if (!get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg')) {
				// just in case previous version was saved with WYSIWYG editor and now switched off
				$p = str_replace('<br />', chr(10), $p);
				$p = strip_tags($p);
			}
			echo stripslashes($subject)."[split]".$p."[split]".$details->topic_parent."[split]".$details->tid."[split]".$details->topic_category;
		} else {
			echo "Problem retrieving topic information[split]Passed Topic ID = ".$tid;
		}
		
	}
	exit;
}

// AJAX function to update Digest subscription
if ($_POST['action'] == 'updateDigest') {

	$value = $_POST['value'];	

	// Update meta record exists for user
	__wps__update_meta($current_user->ID, "forum_digest", "'".$value."'");
	echo $value;
	exit;

}

// AJAX function to update topic details after editing
if ($_POST['action'] == 'updateEditDetails') {

	if (is_user_logged_in()) {

		$tid = $_POST['tid'];	

		$topic_subject = $_POST['topic_subject'];	
		$topic_post = $_POST['topic_post'];	
		
		if (get_option(WPS_OPTIONS_PREFIX.'_striptags') == 'on') {
			$topic_subject = strip_tags($topic_subject);	
			if (get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg') != 'on')
				$topic_post = strip_tags($topic_post);	
		}
		
		$topic_post = str_replace("\n", chr(13), $topic_post);	
		$topic_category = $_POST['topic_category'];
		
		// Ensure safe HTML
		$topic_subject = str_replace("<", "&lt;", $topic_subject);	
		$topic_subject = str_replace(">", "&gt;", $topic_subject);	
		$topic_subject = str_replace("[", "&#91;", $topic_subject);
		$topic_subject = str_replace("]", "&#93;", $topic_subject);
		if (get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg') != 'on') {
			$topic_post = str_replace("<", "&lt;", $topic_post);	
			$topic_post = str_replace(">", "&gt;", $topic_post);
			$topic_post = $topic_post;
		} else {
			$topic_post = $topic_post;
		}

		$topic_post = str_replace("[", "&#91;", $topic_post);
		$topic_post = str_replace("]", "&#93;", $topic_post);
		
		if ($topic_category == "") {
			$topic_category = $wpdb->get_var($wpdb->prepare("SELECT topic_category FROM ".$wpdb->prefix.'symposium_topics'." WHERE tid = %d", $tid));
		}

		$wpdb->query( $wpdb->prepare("UPDATE ".$wpdb->prefix."symposium_topics SET topic_category = %d WHERE topic_parent = %d", $topic_category, $tid) );

		$sql = "UPDATE ".$wpdb->prefix."symposium_topics SET topic_subject = %s, topic_post = %s, topic_category = %d WHERE tid = %d";
		$wpdb->query( $wpdb->prepare($sql, $topic_subject, $topic_post, $topic_category, $tid) );
			
		$parent = $wpdb->get_var($wpdb->prepare("SELECT topic_parent FROM ".$wpdb->prefix.'symposium_topics'." WHERE tid = %d", $tid));
		
	}
	
	exit;
}

// AJAX function to subscribe/unsubscribe to symposium topic
if ($_POST['action'] == 'updateForum') {
	
	if (is_user_logged_in()) {

		$tid = $_POST['tid'];
		$action = $_POST['value'];

		// Store subscription if wanted
		if (__wps__safe_param($tid)) {
			$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."symposium_subs WHERE uid = %d AND tid = %d", $current_user->ID, $tid));
		}
	
		if ($action == 1)
		{		
			// Store subscription if wanted
			$wpdb->query( $wpdb->prepare( "
				INSERT INTO ".$wpdb->prefix."symposium_subs
				( 	uid, 
					tid
				)
				VALUES ( %d, %d )", 
		        array(
		        	$current_user->ID, 
		        	$tid
		        	) 
		        ) );
			exit;
		
		} else {
			
			exit;
			// Removed, and not re-added
		}
	
		echo "Sorry - subscription failed";
		
	}
	exit;
}

// AJAX function to change sticky status
if ($_POST['action'] == 'updateForumSticky') {

	if (is_user_logged_in()) {

		$tid = $_POST['tid'];
		$value = $_POST['value'];

		// Store subscription if wanted
		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."symposium_topics SET topic_sticky = %d WHERE tid = %d", $value, $tid));
	
		if ($value==1) {
			echo "Topic is sticky";
		} else {
			echo "Topic is NOT sticky";
		}
	}
	exit;
}

// AJAX function to change allow replies status
if ($_POST['action'] == 'updateTopicReplies') {

	if (is_user_logged_in()) {

		$tid = $_POST['tid'];
		$value = $_POST['value'];

		// Store subscription if wanted
		$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."symposium_topics SET allow_replies = %s WHERE tid = %d", $value, $tid));
	
		if ($value=='on') {
			echo "Replies are allowed";
		} else {
			echo "Replies are NOT allowed";
		}

	}
	exit;
}

// AJAX function to change allow replies status
if ($_POST['action'] == 'toggleForInfo') {

	if (is_user_logged_in()) {

		$topics = $wpdb->prefix . 'symposium_topics';

		$tid = $_POST['tid'];
		$value = $_POST['value'];

		// Store subscription if wanted
		$wpdb->query($wpdb->prepare("UPDATE %d SET for_info = %s WHERE tid = %d", $topics, $value, $tid));
	
		if ($value=='on') {
			echo "Topic expected an answer";
		} else {
			echo "Topic is for info only";
		}
		
	}
	exit;
}

// AJAX function to subscribe/unsubscribe to new symposium topics
if ($_POST['action'] == 'updateForumSubscribe') {

	if (is_user_logged_in()) {

		$subs = $wpdb->prefix . 'symposium_subs';

		$action = $_POST['value'];
		$cid = $_POST['cid'];

		// Store subscription if wanted
		if (__wps__safe_param($cid))
			$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."symposium_subs WHERE uid = %d AND tid = 0 AND (cid = %d OR cid = 0)", $current_user->ID, $cid));
	
		if ($action == 1)
		{		
			// Store subscription if wanted
			$wpdb->query( $wpdb->prepare( "
				INSERT INTO ".$subs."
				( 	uid, 
					tid,
					cid
				)
				VALUES ( %d, %d, %d )", 
		        array(
		        	$current_user->ID, 
		        	0,
		        	$cid
		        	) 
		        ) );
				echo 'Subscription added.';			
			exit;
		
		} else {

			echo 'Subscription removed.';			
			exit;
		}
	
		echo "Sorry - subscription failed";
	}
	exit;

}

// Social media icon share
if ($_POST['action'] == 'socialShare') {
	if (is_user_logged_in()) {
		do_action('symposium_forum_socialmedia_hook', $current_user->ID, isset($_POST['destination']) ? $_POST['destination'] : 'error');			
	}
	exit;
}

// Do search
if ($_POST['action'] == 'getSearch') {

	$term = $_POST['term'];
	$found_count=0;
	$max_return=20; // Helps with avoiding return huge amounts of HTML (and unresponsive page)

	// Get list of roles for this user
    $user_roles = $current_user->roles;
    $user_role = strtolower(array_shift($user_roles));
    if ($user_role == '') $user_role = 'NONE';
    							
	$html = '<div id="forum_activity_div">';
 
	if (trim($term) != '') {
						
		$sql = "SELECT t.*, p.tid AS parent_tid, u2.display_name as parent_display_name, p.topic_subject AS parent_topic_subject, p.topic_started AS parent_topic_started, u.display_name 
			FROM ".$wpdb->prefix."symposium_topics t 
			LEFT JOIN ".$wpdb->base_prefix."users u ON t.topic_owner = u.ID 
			LEFT JOIN ".$wpdb->prefix."symposium_topics p ON t.topic_parent = p.tid 
			LEFT JOIN ".$wpdb->base_prefix."users u2 ON p.topic_owner = u2.ID 
			WHERE t.topic_approved = 'on' && (t.topic_subject LIKE '%%%s%%' OR t.topic_post LIKE '%%%s%%' OR u.display_name LIKE '%%%s%%') 
			AND (p.topic_parent = 0 || p.topic_parent is null || p.topic_subject != '')
			ORDER BY t.tid DESC LIMIT 0,40";
		$topics = $wpdb->get_results($wpdb->prepare($sql, $term, $term, $term));

		if ($topics) {

			foreach ($topics as $topic) {	

				// Check permitted to see forum category
				$sql = "SELECT level FROM ".$wpdb->prefix."symposium_cats WHERE cid = %d";
				$levels = $wpdb->get_var($wpdb->prepare($sql, $topic->topic_category));
				$cat_roles = unserialize($levels);
				if ($topic->topic_group > 0 || strpos(strtolower($cat_roles), 'everyone,') !== FALSE || strpos(strtolower($cat_roles), $user_role.',') !== FALSE) {					

					$gid = $topic->topic_group;
					
					if ($gid == 0 || __wps__member_of($gid) == "yes") {
	
						if ($found_count > $max_return) { 
							$html .= '<p>'.sprintf(__('A maxium of %d search results will be displayed, please narrow your search.', WPS_TEXT_DOMAIN), $max_return).'</p>';
							break; 
						}
	
						$found_count++;
	
						// Work out link to this page, dealing with permalinks or not
						if ($gid == 0) {
							$thispage = __wps__get_url('forum');
							if ($thispage[strlen($thispage)-1] != '/') { $thispage .= '/'; }
							if (strpos($thispage, "?") === FALSE) { 
								$q = "?";
							} else {
								// No Permalink
								$q = "&";
							}
						} else {
							$thispage = __wps__get_url('group');
							if ($thispage[strlen($thispage)-1] != '/') { $thispage .= '/'; }
							if (strpos($thispage, "?") === FALSE) { 
								$q = "?";
							} else {
								// No Permalink
								$q = "&";
							}
							$q .= "gid=".$gid."&";
						}
	
						$html .= "<div class='__wps__search_subject_row_div'>";
						
							$html .= "<div class='__wps__search_subject_div'>";
	
							if ($topic->topic_parent != 0) {
								$html .= $topic->display_name.' ';
								$html .= __("in reply to", WPS_TEXT_DOMAIN)." ";
								$topic_subject = __wps__bbcode_remove(stripslashes($topic->parent_topic_subject));
								$topic_subject = preg_replace(
								  "/(>|^)([^<]+)(?=<|$)/esx",
								  "'\\1' . str_replace('" . $term . "', '<span class=\"__wps__search_highlight\">" . $term . "</span>', '\\2')",
								  $topic_subject
								);

								if (get_option(WPS_OPTIONS_PREFIX.'_permalink_structure') && $group_id == 0) {
									$stub = $wpdb->get_var($wpdb->prepare("SELECT stub FROM ".$wpdb->prefix."symposium_topics WHERE tid = %d", $topic->parent_tid));
									$perma_cat = __wps__get_forum_category_part_url($topic->topic_category);
									$url = $thispage.$perma_cat.$stub;							
									$html .= "<a class='__wps__search_subject' href='".$url."'>".stripslashes($topic_subject)."</a> ";
								} else {
									$html .= "<a class='__wps__search_subject' href='".$thispage.$q.'cid='.$topic->topic_category.'&show='.$topic->parent_tid."'>".stripslashes($topic_subject)."</a> ";
								}
								$html .= __("by", WPS_TEXT_DOMAIN)." ".$topic->parent_display_name.", ".__wps__time_ago($topic->parent_topic_started).".";
							} else {
								$topic_subject = __wps__bbcode_remove(stripslashes($topic->topic_subject));
								$topic_subject = preg_replace(
								  "/(>|^)([^<]+)(?=<|$)/iesx",
								  "'\\1' . str_replace('" . $term . "', '<span class=\"__wps__search_highlight\">" . $term . "</span>', '\\2')",
								  $topic_subject
								);
								if (get_option(WPS_OPTIONS_PREFIX.'_permalink_structure') && $group_id == 0) {
									$stub = $wpdb->get_var($wpdb->prepare("SELECT stub FROM ".$wpdb->prefix."symposium_topics WHERE tid = %d", $topic->tid));
									$perma_cat = __wps__get_forum_category_part_url($topic->topic_category);
									$url = $thispage.$perma_cat.$stub;							
									$html .= "<a class='__wps__search_subject' href='".$url."'>".stripslashes($topic_subject)."</a> ";
								} else {
									$html .= "<a class='__wps__search_subject' href='".$thispage.$q.'cid='.$topic->topic_category.'&show='.$topic->tid."'>".stripslashes($topic_subject)."</a> ";
								}
								$html .= __("by", WPS_TEXT_DOMAIN)." ".$topic->display_name.", ".__wps__time_ago($topic->topic_started).".";
							}
	
							$html .= "</div>";
	
							$text = __wps__bbcode_remove(strip_tags(stripslashes($topic->topic_post)));
							
							$result = "";
							$buffer = 20;
							
							for ($i = 0; $i <= strlen($text)-strlen($term); $i++) {
								if ( substr(strtolower($text), $i, strlen($term)) == strtolower($term) ) {
									$start = ($i - $buffer >= 0) ? $i - $buffer : 0;
									$end = strlen($term) + ($buffer * 2);
									$end = ($end >= strlen($text)) ? strlen($text) : $end;
									$snippet = substr($text, $start, $end);
									if ($start > 0) { $snippet = "...".$snippet; }
									if ($end < strlen($text)) { $snippet .= "...&nbsp;&nbsp;"; }
									$snippet = preg_replace('/('.$term.')/i', "<span class=\"__wps__search_highlight\">$1</span>", $snippet); 
									$result .= $snippet;
								}
							}
	
							if ($result != '') {
								
								$html .= stripslashes($result)."<br />";	
								if ($topic->topic_parent != 0) {
									$html .= "<em>".__("Posted by", WPS_TEXT_DOMAIN)." ".$topic->display_name.", ".__wps__time_ago($topic->topic_started).".</em>";
								}
							}
			
						$html .= '</div>';
	
					}
				}
			}
		
		}

		$html .= "<p><br />".__("Results found:", WPS_TEXT_DOMAIN)." ".$found_count."</p>";				

	}
	
	$html .= '</div>';
	
	//$html .= " ".$wpdb->last_query;
	echo $html;
	exit;
}


function __wps__rrmdir_tmp($dir) {
   if (is_dir($dir)) {
	 $objects = scandir($dir);
	 foreach ($objects as $object) {
	   if ($object != "." && $object != "..") {
		 if (filetype($dir."/".$object) == "dir") __wps__rrmdir_tmp($dir."/".$object); else unlink($dir."/".$object);
	   }
	 }
	 reset($objects);
	 rmdir($dir);
   }
}  



?>
