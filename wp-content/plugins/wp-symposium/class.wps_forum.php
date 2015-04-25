<?php

// ******************************** MAIL CLASS ********************************

class wps_forum {

	public function __construct($top_level='') {
		$root = $top_level != '' ? $top_level : 0;
		$this->root = $root;	// Set the top level of the forum
	}

	/* Following methods provide get/set functionality ______________________________________ */
	
	function get_categories($top_level='') {
		
		$root = $top_level != '' ? $root = $top_level : $this->root;
		
		global $wpdb;
		$sql = "SELECT *
			FROM ".$wpdb->prefix."symposium_cats
			WHERE cat_parent = %d
			ORDER BY listorder";
		$cats = $wpdb->get_results($wpdb->prepare($sql, $top_level));
		
		return $cats;
		
	}

	function get_topics($cid='', $start=0, $limit=9999, $order='DESC', $gid=0) {

		if ($cid !== '') {
			
			global $wpdb;
			$sql = "SELECT t.*, u.display_name,
			    (SELECT count(*) FROM ".$wpdb->prefix."symposium_topics st WHERE topic_parent = t.tid) AS topic_replies
				FROM ".$wpdb->prefix."symposium_topics t
				LEFT JOIN ".$wpdb->base_prefix."users u ON t.topic_owner = u.ID
				WHERE topic_category = %d
				AND topic_parent = 0
				AND topic_group = %d
				ORDER BY tid ".$order."
				LIMIT %d, %d";
				
			if ($limit-$start != 1) {
				$topics = $wpdb->get_results($wpdb->prepare($sql, $cid, $gid, $start, $limit));
			} else {
				$topics = $wpdb->get_row($wpdb->prepare($sql, $cid, $gid, $start, $limit));
			}

			return $topics;

		} else {
			echo 'No category ID passed';
			return false;
		}
		
	}

	function get_topics_count($cid='') {

		global $wpdb;

		if ($cid !== '') {
			
			$sql = "SELECT count(*) FROM ".$wpdb->prefix."symposium_topics WHERE topic_category = %d AND topic_parent = 0";
			return $wpdb->get_var($wpdb->prepare($sql, $cid));

		} else {

			$sql = "SELECT count(*) FROM ".$wpdb->prefix."symposium_topics";
			return $wpdb->get_var($wpdb->prepare($sql, $cid));
		}
		
	}

	function get_category($cid='') {

		if ($cid !== '') {
			
			global $wpdb;

			$sql = "SELECT title FROM ".$wpdb->prefix."symposium_cats WHERE cid = %d";
			return $wpdb->get_var($wpdb->prepare($sql, $cid));

		} else {

			return false;
		}
		
	}	
	
	function get_replies($tid='', $start=0, $limit=9999, $order='DESC') {
		
		if ($tid != '') {

			global $wpdb;
			$sql = "SELECT t.*, u.display_name
				FROM ".$wpdb->prefix."symposium_topics t
				LEFT JOIN ".$wpdb->base_prefix."users u ON t.topic_owner = u.ID
				WHERE topic_parent = %d
				ORDER BY tid ".$order."
				LIMIT %d, %d";
				
			if ($limit-$start != 1) {
				$replies = $wpdb->get_results($wpdb->prepare($sql, $tid, $start, $limit));
			} else {
				$replies = $wpdb->get_row($wpdb->prepare($sql, $tid, $start, $limit));
			}
			
			if ($replies) {
				return $replies;
			} else {
				return false;
			}

		} else {
			echo 'No topic ID passed';
			return false;
		}
		
	}			

	function get_replies_count($tid='') {
		
		if ($tid != '') {

			global $wpdb;
			$sql = "SELECT count(*)
				FROM ".$wpdb->prefix."symposium_topics t
				WHERE topic_parent = %d";
				
			return $wpdb->get_var($wpdb->prepare($sql, $tid));

		} else {
			return false;
		}
		
	}			
	
	function get_topic($tid='') {
		
		if ($tid != '') {
			
			global $wpdb;
			$sql = "SELECT t.*, u.display_name
				FROM ".$wpdb->prefix."symposium_topics t
				LEFT JOIN ".$wpdb->base_prefix."users u ON t.topic_owner = u.ID
				WHERE tid = %d";
				
			return $wpdb->get_row($wpdb->prepare($sql, $tid));

		} else {
			echo 'No topic ID passed';
			return false;
		}
		
	}	

	/* Following methods provide functionality _____________________________________________________________________ */
	
	function delete_post($reply_id) {
		global $wpdb,$current_user;
		
		// first get details of reply to delete to check for permission
		$reply = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."symposium_topics WHERE tid = %d", $reply_id));
		if ($reply) {
			if ($reply->topic_owner == $current_user->ID || current_user_can('manage_options')) {
				// check if locked
				$now = date('Y-m-d H:i:s', time() - get_option(WPS_OPTIONS_PREFIX.'_forum_lock') * 60);
				if ( (get_option(WPS_OPTIONS_PREFIX.'_forum_lock')==0) || ($reply->topic_started > $now) || (current_user_can('level_10')) ) {
					// First delete any replies
					$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."symposium_topics WHERE topic_parent = %d", $reply_id));
					$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."symposium_topics WHERE tid = %d", $reply_id));
					return $reply_id;					
				} else {
					return false;
				}
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	
	function is_topic_locked($tid) {
		global $wpdb;
		$now = date('Y-m-d H:i:s', time() - get_option(WPS_OPTIONS_PREFIX.'_forum_lock') * 60);
		$topic_started = $wpdb->get_var($wpdb->prepare("SELECT topic_started FROM ".$wpdb->prefix."symposium_topics WHERE tid = %d", $tid));
		if ( (get_option(WPS_OPTIONS_PREFIX.'_forum_lock')==0) || ($topic_started > $now) || (current_user_can('level_10')) ) {
			return false;
		} else {
			return true;
		}
		return false;
	}

	function add_reply($tid, $reply_text, $uid=0, $replybyemail=false) {
		
		if ($tid != '') {

			global $wpdb, $current_user;

			// Defaults for current state of class
			$topic_approved = 'on';
			$group_id = 0;
			$answered = '';
			
			// User ID?
			if ($uid == 0) { $uid = $current_user->ID; }

			// Get category for this topic ID
			$cat_id = $wpdb->get_var($wpdb->prepare("SELECT topic_category from ".$wpdb->prefix."symposium_topics where tid = %d", $tid));
			
			// Don't allow HTML in subject if not using WYSIWYG editor
			if (get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg') != 'on') {
				$reply_text = str_replace("<", "&lt;", $reply_text);
				$reply_text = str_replace(">", "&gt;", $reply_text);
			}

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
			
			// First check for potential duplicate
			$sql = "SELECT tid FROM ".$wpdb->prefix."symposium_topics WHERE topic_parent = %d AND topic_post = %s";
			$duplicate = $wpdb->get_var($wpdb->prepare($sql, $tid, $reply_text));
						
			if (!$duplicate) {

				if (	
					
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
							topic_answer
						)
						VALUES ( %s, %d, %s, %s, %s, %d, %d, %d, %s, %d, %s )", 
				        array(
				        	'', 
				        	$cat_id,
				        	$reply_text, 
				        	date("Y-m-d H:i:s"), 
							date("Y-m-d H:i:s"), 
							$uid, 
							$tid,
							0,
							$topic_approved,
							$group_id,
							$answered
				        	) 
				        ) )

				) {

					// get new topic id (or response) for return
					$new_id = $wpdb->insert_id;
					
					// Now send out emails as appropriate				
	
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
	
					// Email people who want to know and prepare body
					$owner_name = $wpdb->get_var($wpdb->prepare("SELECT display_name FROM ".$wpdb->base_prefix."users WHERE ID = %d", $uid));
					$parent = $wpdb->get_var($wpdb->prepare("SELECT topic_subject FROM ".$wpdb->prefix."symposium_topics WHERE tid = %d", $tid));
					
					$body = "<span style='font-size:24px'>".$parent."</span><br /><br />";
					$body .= "<p>".$owner_name." ".__('replied', WPS_TEXT_DOMAIN)."...</p>";
					$body .= "<p>".$reply_text."</p>";
					$url = $forum_url.$q."cid=".$cat_id."&show=".$tid;
					$body .= "<p><a href='".$url."'>".$url."</a></p>";
					$body = str_replace(chr(13), "<br />", $body);
					$body = str_replace("\\r\\n", "<br />", $body);
					$body = str_replace("\\", "", $body);
				
					$email_list = '0,';
					if ($topic_approved == "on") {
				
				
						$query = $wpdb->get_results($wpdb->prepare("
							SELECT user_email, ID
							FROM ".$wpdb->base_prefix."users u 
							RIGHT JOIN ".$wpdb->prefix."symposium_subs ON ".$wpdb->prefix."symposium_subs.uid = u.ID 
							WHERE u.ID != %d AND tid = %d", $uid, $tid));
							
						if ($query) {						
							foreach ($query as $user) {	
				
								// Filter to allow further actions to take place
								apply_filters ('__wps__forum_newreply_filter', $user->ID, $uid, $owner_name, $url);
						
								// Keep track of who sent to so far
								$email_list .= $user->ID.',';

								// Check for Reply-By-Email						
								if ($replybyemail || function_exists('__wps__mailinglist')) { 
									$subject_add = ' #TID='.$tid.' ['.__('do not edit', WPS_TEXT_DOMAIN).']'; 
									$body = get_option(WPS_OPTIONS_PREFIX.'_mailinglist_prompt').'<br />'.get_option(WPS_OPTIONS_PREFIX.'_mailinglist_divider').'<br /><br />'.get_option(WPS_OPTIONS_PREFIX.'_mailinglist_divider_bottom').'<br /><br />'.'<br /><br />'.$body;
								} else {
									$subject_add = '';
								}

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
							WHERE ID != %d AND 
							ID NOT IN (".$email_list.")";
						$list = $wpdb->get_results($wpdb->prepare($sql, $current_user->ID));

						if ($list) {
							
							$list_array = array();
							foreach ($list as $item) {
				
								if (__wps__get_meta($item->ID, 'forum_all') == 'on') {
									$add = array (	
										'ID' => $item->ID,
										'user_email' => $item->user_email
									);						
									array_push($list_array, $add);
								}
								
							}
							$query = __wps__sub_val_sort($list_array, 'last_activity');	
							
						} else {
						
							$query = false;
							
						}	
										
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
									$capabilities = $the_user->{$wpdb->prefix . 'capabilities'};
				
									if ( !isset( $wp_roles ) )
										$wp_roles = new WP_Roles();
										
									$user_role = 'NONE';
									if ($capabilities) {
										foreach ( $wp_roles->role_names as $role => $name ) {
										
											if ( array_key_exists( $role, $capabilities ) )
												$user_role = $role;
										}				
									}
									
									// Check in this topics category level
									if (strpos(strtolower($cat_roles), 'everyone,') !== FALSE || strpos(strtolower($cat_roles), $user_role.',') !== FALSE) {	 
				
										// Filter to allow further actions to take place
										apply_filters ('__wps__forum_newreply_filter', $user->ID, $uid, $owner_name, $url);
				
										// Send mail
										if (strpos(get_option(WPS_OPTIONS_PREFIX.'_subject_forum_reply'), '[topic]') !== FALSE) {
											$subject = str_replace("[topic]", $parent, get_option(WPS_OPTIONS_PREFIX.'_subject_forum_reply'));
										} else {
											$subject = get_option(WPS_OPTIONS_PREFIX.'_subject_forum_reply');
										}
										__wps__sendmail($user->user_email, $subject, $body);							
										
									}
									
								}
							}
						}	
						
					} else {
						// Email admin if post needs approval
						$body = "<span style='font-size:24px; font-style:italic;'>".__("Moderation required for a reply", WPS_TEXT_DOMAIN)."</span><br /><br />".$body;
						__wps__sendmail(get_bloginfo('admin_email'), __('Moderation required for a reply', WPS_TEXT_DOMAIN), $body);
					}	
										
					return $new_id;
					
				} else {
					
					//__wps__sendmail(get_bloginfo('admin_email'), __('POP3 insert failed', WPS_TEXT_DOMAIN), 'Query:'.$wpdb->last_query);
					return false;
					
				}
				
			} else {
				
				//__wps__sendmail(get_bloginfo('admin_email'), __('POP3 insert failed', WPS_TEXT_DOMAIN), 'Duplicate skipped: '.$wpdb->last_query);
				return false;
				
			} // End duplicate check
			
			
		} else {
			
			//__wps__sendmail(get_bloginfo('admin_email'), __('POP3 insert failed', WPS_TEXT_DOMAIN), 'No tid passed');
			return false;
			
		}
		
	}
		
	/* Following methods check for various conditions and return boolean value ______________________________________ */
	
}



?>
