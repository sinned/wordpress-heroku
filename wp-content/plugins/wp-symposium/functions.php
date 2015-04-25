<?php

function __wps__getTopic($topic_id, $group_id=0, $pagination=9999, $page=1) {

	global $wpdb, $current_user, $blog_id;	

	$html = '';
	
	$plugin = WP_CONTENT_URL.'/plugins/wp-symposium/';
	
	$previous_login = __wps__get_meta($current_user->ID, 'previous_login');

	$level = __wps__get_current_userlevel();

	// Check permissions
	$user = get_userdata( $current_user->ID );
	$can_view = false;
	$viewer = str_replace('_', '', str_replace(' ', '', strtolower(get_option(WPS_OPTIONS_PREFIX.'_viewer'))));
	if ($user) {
		$capabilities = $user->{$wpdb->prefix.'capabilities'};
	
		// Can view topic?
		if ($capabilities) {
			foreach ( $capabilities as $role => $name ) {
				if ($role) {
					$role = strtolower($role);
					$role = str_replace(' ', '', $role);
					$role = str_replace('_', '', $role);
					if (WPS_DEBUG) $html .= 'Checking view role '.$role.' against '.$viewer.'<br />';
					if (strpos($viewer, $role) !== FALSE) $can_view = true;
					if (WPS_DEBUG && $can_view) $html .= "CAN VIEW<br />";
				}
			}		 														
		}
	}
	$everyone = str_replace(' ', '', __('everyone', WPS_TEXT_DOMAIN)); // Handle some non-English translations of 'everyone'
	if (strpos($viewer, $everyone) !== FALSE) $can_view = true;
	
	// Can create topic?
	$can_edit = false;
	$viewer = str_replace('_', '', str_replace(' ', '', strtolower(get_option(WPS_OPTIONS_PREFIX.'_forum_editor'))));
	if ($user && $capabilities) {
		foreach ( $capabilities as $role => $name ) {
			if ($role) {
				$role = strtolower($role);
				$role = str_replace(' ', '', $role);
				$role = str_replace('_', '', $role);
				if (WPS_DEBUG) $html .= 'Checking create role '.$role.' against '.$viewer.'<br />';
				if (strpos($viewer, $role) !== FALSE) $can_edit = true;
				if (WPS_DEBUG && $can_edit) $html .= "CAN EDIT<br />";
			}
		}		 														
	}
	if (strpos($viewer, __('everyone', WPS_TEXT_DOMAIN)) !== FALSE) $can_edit = true;
	if ($group_id > 0) {
		$sql = "SELECT COUNT(*) FROM ".$wpdb->prefix."symposium_group_members WHERE group_id=%d AND valid='on' AND member_id=%d";
		$member_count = $wpdb->get_var($wpdb->prepare($sql, $group_id, $current_user->ID));
		if ($member_count == 0) { $can_edit = false; } else { $can_edit = true; }
	}

	// Can reply to a topic?
	$can_reply = false;
	$viewer = str_replace('_', '', str_replace(' ', '', strtolower(get_option(WPS_OPTIONS_PREFIX.'_forum_reply'))));
	if ($user && $capabilities) {
		foreach ( $capabilities as $role => $name ) {
			if ($role) {
				$role = strtolower($role);
				$role = str_replace(' ', '', $role);
				$role = str_replace('_', '', $role);
				if (WPS_DEBUG) $html .= 'Checking reply role '.$role.' against '.$viewer.'<br />';
				if (strpos($viewer, $role) !== FALSE) $can_reply = true;
				if (WPS_DEBUG && $can_reply) $html .= "CAN REPLY<br />";
			}
		}		 														
	}
	if (strpos($viewer, __('everyone', WPS_TEXT_DOMAIN)) !== FALSE) $can_reply = true;
	if ($group_id > 0) {
		$sql = "SELECT COUNT(*) FROM ".$wpdb->prefix."symposium_group_members WHERE group_id=%d AND valid='on' AND member_id=%d";
		$member_count = $wpdb->get_var($wpdb->prepare($sql, $group_id, $current_user->ID));
		if ($member_count == 0) { $can_reply = false; } else { $can_reply = true; }
	}
	// check for allow replies setting on forum
	$can_reply_switch = $wpdb->get_var($wpdb->prepare("SELECT allow_replies FROM ".$wpdb->prefix."symposium_topics WHERE tid = %d", $topic_id));

	// Can add a comment to a reply?
	$can_comment = false;
	$viewer = str_replace('_', '', str_replace(' ', '', strtolower(get_option(WPS_OPTIONS_PREFIX.'_forum_reply_comment'))));
	if ($user && $capabilities) {
		foreach ( $capabilities as $role => $name ) {
			if ($role) {
				$role = strtolower($role);
				$role = str_replace(' ', '', $role);
				$role = str_replace('_', '', $role);
				if (WPS_DEBUG) $html .= 'Checking reply comment role '.$role.' against '.$viewer.'<br />';
				if (strpos($viewer, $role) !== FALSE) $can_comment = true;
				if (WPS_DEBUG && $can_comment) $html .= "CAN COMMENT<br />";
			}
		}		 														
	}
	if (strpos($viewer, __('everyone', WPS_TEXT_DOMAIN)) !== FALSE) $can_comment = true;
	if ($group_id > 0) {
		$sql = "SELECT COUNT(*) FROM ".$wpdb->prefix."symposium_group_members WHERE group_id=%d AND valid='on' AND member_id=%d";
		$member_count = $wpdb->get_var($wpdb->prepare($sql, $group_id, $current_user->ID));
		if ($member_count == 0) { $can_comment = false; } else { $can_comment = true; }
	}


	// Get list of roles for this user
	global $current_user;
    $user_roles = $current_user->roles;
    $user_role = strtolower(array_shift($user_roles));
    $user_role = str_replace('_', '', str_replace(' ', '', $user_role));
    if ($user_role == '') $user_role = 'NONE';

	// Get list of permitted roles from forum_cat and check allowed for this topic's category
	$sql = "SELECT topic_category FROM ".$wpdb->prefix."symposium_topics WHERE tid = %d";
	$category = $wpdb->get_var($wpdb->prepare($sql, $topic_id));
	$sql = "SELECT level FROM ".$wpdb->prefix."symposium_cats WHERE cid = %d";
	$level = $wpdb->get_var($wpdb->prepare($sql, $category));
	$cat_roles = unserialize($level);
    $cat_roles = str_replace('_', '', str_replace(' ', '', $cat_roles));

	if ($group_id > 0) {
		if (__wps__member_of($group_id) != "yes") $can_view = false;
	} else {
		if (strpos(strtolower($cat_roles), 'everyone,') !== FALSE || strpos(strtolower($cat_roles), $user_role.',') !== FALSE) {
			// can view
		} else {
			$can_view = false;
		}
	}

	if ( $can_view ) {

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
		
		$post = $wpdb->get_row("
			SELECT tid, topic_subject, topic_approved, topic_category, topic_post, topic_started, display_name, topic_sticky, topic_owner, for_info 
			FROM ".$wpdb->prefix."symposium_topics t INNER JOIN ".$wpdb->base_prefix."users u ON t.topic_owner = u.ID 
			WHERE (t.topic_approved = 'on' OR t.topic_owner = ".$current_user->ID.") AND tid = ".$topic_id);
		
		if ($post) {

			// Store removal limit for votes
			$html .= '<div id="symposium_forum_vote_remove" style="display:none">'.get_option(WPS_OPTIONS_PREFIX.'_use_votes_remove').'</div>';
			$html .= '<div id="symposium_forum_vote_remove_msg" style="display:none">'.__('This post has been voted off the forum', WPS_TEXT_DOMAIN).'</div>';
		

			$html .= '<div id="__wps__forum_topic_header">';

				// Breadcrumbs
				$cat_id = $post->topic_category;

				$breadcrumbs = $wpdb->get_row($wpdb->prepare("SELECT title, hide_breadcrumbs FROM ".$wpdb->prefix."symposium_cats WHERE cid = %d", $cat_id));
				if ($breadcrumbs->hide_breadcrumbs == 'on') {

					$html .= '<div id="topic_breadcrumbs" class="breadcrumbs label">';

						$this_level = $wpdb->get_row($wpdb->prepare("SELECT cid, title, cat_parent, stub FROM ".$wpdb->prefix."symposium_cats WHERE cid = %d", $cat_id));

						if (get_option(WPS_OPTIONS_PREFIX.'_forum_ajax') == 'on') { 
							$html .= '<a href="#cid='.$this_level->cid.'" class="category_title" title="'.$this_level->cid.'">'.__('Back to', WPS_TEXT_DOMAIN).' '.trim(stripslashes($this_level->title)).'</a>'; 
						} else { 
							if (get_option(WPS_OPTIONS_PREFIX.'_permalink_structure') && $group_id == 0) {
								$html .= '<a href="'.$forum_url.'/'.$this_level->stub.'" title="'.$this_level->cid.'">'.__('Back to', WPS_TEXT_DOMAIN).' '.trim(stripslashes($this_level->title)).'</a>'; 
							} else {
								$html .= '<a href="'.$forum_url.$q."cid=".$this_level->cid.'" title="'.$this_level->cid.'">'.__('Back to', WPS_TEXT_DOMAIN).' '.trim(stripslashes($this_level->title)).'</a>'; 
							}
						} 

					$html .= '</div>';

				} else {
						
					$html .= '<div id="topic_breadcrumbs" class="breadcrumbs label">';

						if (!get_option(WPS_OPTIONS_PREFIX.'_wps_lite')) {
							$this_level = $wpdb->get_row($wpdb->prepare("SELECT cid, title, cat_parent, stub FROM ".$wpdb->prefix."symposium_cats WHERE cid = %d", $cat_id));
							if ($this_level) { 

								if ($this_level->cat_parent == 0) { 
									if (get_option(WPS_OPTIONS_PREFIX.'_forum_ajax') == 'on') { 
										$html .= '<a href="#cid=0" class="category_title" title="0">'.__('Forum Home', WPS_TEXT_DOMAIN)."</a> &rarr; "; 
										$html .= '<a href="#cid='.$this_level->cid.'" class="category_title" title="'.$this_level->cid.'">'.trim(stripslashes($this_level->title)).'</a>'; 
									} else { 
										if (get_option(WPS_OPTIONS_PREFIX.'_permalink_structure') && $group_id == 0) {
											$html .= '<a href="'.$forum_url.'">'.__('Forum Home', WPS_TEXT_DOMAIN)."</a> &rarr; "; 
											$html .= '<a href="'.$forum_url.'/'.$this_level->stub.'" title="'.$this_level->cid.'">'.trim(stripslashes($this_level->title)).'</a>'; 
										} else {
											$html .= '<a href="'.$forum_url.$q.'cid=0" title="0">'.__('Forum Home', WPS_TEXT_DOMAIN)."</a> &rarr; "; 
											$html .= '<a href="'.$forum_url.$q."cid=".$this_level->cid.'" title="'.$this_level->cid.'">'.trim(stripslashes($this_level->title)).'</a>'; 
										}
									} 
								} else { 

									$parent_level = $wpdb->get_row($wpdb->prepare("SELECT cid, title, cat_parent, stub FROM ".$wpdb->prefix."symposium_cats WHERE cid = %d", $this_level->cat_parent)); 

									if ($parent_level->cat_parent == 0) { 
										if (get_option(WPS_OPTIONS_PREFIX.'_forum_ajax') == 'on') { 
											$html .= '<a href="#cid=0" class="category_title" title="0">'.__('Forum Home', WPS_TEXT_DOMAIN)."</a> &rarr; "; 
										} else { 
											if (get_option(WPS_OPTIONS_PREFIX.'_permalink_structure') && $group_id == 0) {
												$html .= '<a href="'.$forum_url.'" title="0">'.__('Forum Home', WPS_TEXT_DOMAIN)."</a> &rarr; "; 
											} else {
												$html .= '<a href="'.$forum_url.$q.'cid=0" title="0">'.__('Forum Home', WPS_TEXT_DOMAIN)."</a> &rarr; "; 
											}
										} 
									} else { 
										$parent_level_2 = $wpdb->get_row($wpdb->prepare("SELECT cid, title, cat_parent, stub FROM ".$wpdb->prefix."symposium_cats WHERE cid = %d", $parent_level->cat_parent)); 
										if (get_option(WPS_OPTIONS_PREFIX.'_forum_ajax') == 'on') { 
											$html .= '<a href="#cid=0" class="category_title" title="0">'.__('Forum Home', WPS_TEXT_DOMAIN)."</a> &rarr; " ; 
											$html .= '<a href="#cid='.$parent_level_2->cid.'" class="category_title" title="'.$parent_level_2->cid.'">'.trim(stripslashes($parent_level_2->title))."</a> &rarr; "; 
										} else { 
											if (get_option(WPS_OPTIONS_PREFIX.'_permalink_structure') && $group_id == 0) {
												$html .= '<a href="'.$forum_url.'" title="0">'.__('Forum Home', WPS_TEXT_DOMAIN)."</a> &rarr; " ; 
												$html .= '<a href="'.$forum_url.'/'.$parent_level_2->stub.'" title="'.$parent_level_2->cid.'">'.trim(stripslashes($parent_level_2->title))."</a> &rarr; "; 
											} else {
												$html .= '<a href="'.$forum_url.$q.'cid=0" title="0">'.__('Forum Home', WPS_TEXT_DOMAIN)."</a> &rarr; " ; 
												$html .= '<a href="'.$forum_url.$q."cid=".$parent_level_2->cid.'" title="'.$parent_level_2->cid.'">'.trim(stripslashes($parent_level_2->title))."</a> &rarr; "; 
											}
										} 
									} 
									if (get_option(WPS_OPTIONS_PREFIX.'_forum_ajax') == 'on') { 
										$html .= '<a href="#cid='.$parent_level->cid.'" class="category_title" title="'.$parent_level->cid.'">'.trim(stripslashes($parent_level->title))."</a> &rarr; " ; 
										$html .= '<a href="#cid='.$this_level->cid.'" class="category_title" title="'.$this_level->cid.'">'.trim(stripslashes($this_level->title))."</a>" ; 
									} else { 
										if (get_option(WPS_OPTIONS_PREFIX.'_permalink_structure') && $group_id == 0) {
											$html .= '<a href="'.$forum_url.'/'.$parent_level->stub.'" title="'.$parent_level->cid.'">'.trim(stripslashes($parent_level->title))."</a> &rarr; " ; 
											$html .= '<a href="'.$forum_url.'/'.$this_level->stub.'" title="'.$this_level->cid.'">'.trim(stripslashes($this_level->title))."</a>" ; 
										} else {
											$html .= '<a href="'.$forum_url.$q."cid=".$parent_level->cid.'" title="'.$parent_level->cid.'">'.trim(stripslashes($parent_level->title))."</a> &rarr; " ; 
											$html .= '<a href="'.$forum_url.$q."cid=".$this_level->cid.'" title="'.$this_level->cid.'">'.trim(stripslashes($this_level->title))."</a>" ; 
										}
									} 
								} 
							} else {
								if (get_option(WPS_OPTIONS_PREFIX.'_forum_ajax') == 'on') {
									$html .= '&larr; <a href="#cid=0" class="category_title" title="0">'.__('Forum Home', WPS_TEXT_DOMAIN)."</a>";
								} else {
									if (get_option(WPS_OPTIONS_PREFIX.'_permalink_structure') && $group_id == 0) {
										$html .= '&larr; <a href="'.$forum_url.'" title="0">'.__('Forum Home', WPS_TEXT_DOMAIN)."</a>";
									} else {
										$html .= '&larr; <a href="'.$forum_url.$q.'cid=0" title="0">'.__('Forum Home', WPS_TEXT_DOMAIN)."</a>";
									}
								}
							}

						} else {
							// Lite mode
							$html .= '<a href="#cid=0" class="category_title" title="0">'.__('Forum Home', WPS_TEXT_DOMAIN)."</a> &rarr; ";
							$html .= '<a href="#cid='.$post->topic_category.'" class="category_title" title="'.$post->topic_category.'">'.__('Topic list', WPS_TEXT_DOMAIN).'</a>';
						}
										
					$html .= '</div>';

				}
						
				// Quick jump, subscribe, Sticky and Allow Replies
				if (is_user_logged_in()) {
					$html .= "<div id='__wps__topic_options' class='label' style='width:95%;'>";

						$html .= '<div style="float:left; text-align:right;margin-bottom:6px;">';
							if (get_option(WPS_OPTIONS_PREFIX.'_suppress_forum_notify') != "on") {
								$forum_all = __wps__get_meta($current_user->ID, 'forum_all');
								$html .= "<input type='checkbox' title='".$post->tid."' id='subscribe' name='subscribe'";
								if ($forum_all == 'on') {
									$html .= " style='display:none;'";
								}
								$subscribed_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM ".$wpdb->prefix."symposium_subs WHERE tid = %d and uid = %d", $post->tid, $current_user->ID));
								if ($subscribed_count > 0) { $html .= ' checked'; } 
								$html .= "> ";
								if ($forum_all != 'on') {
									$html .= __("Tell me about replies", WPS_TEXT_DOMAIN)."&nbsp;&nbsp;&nbsp;";
								}
							}
							if (current_user_can('level_10')) {
								$html .= "<input type='checkbox' title='".$post->tid."' id='sticky' name='sticky'";
								if ($post->topic_sticky > 0) { $html .= ' checked'; }
								$html .= "> ".__("Sticky", WPS_TEXT_DOMAIN);
								$html .= "&nbsp;&nbsp;&nbsp;<input type='checkbox' title='".$post->tid."' id='replies' name='replies'";
								$allow_replies = $wpdb->get_var($wpdb->prepare("SELECT allow_replies FROM ".$wpdb->prefix."symposium_topics WHERE tid = %d", $post->tid));
								if ($allow_replies == "on") { $html .= ' checked'; }
								$html .= "> ".__("Replies allowed", WPS_TEXT_DOMAIN);
							}
						$html .= '</div>';

						// Add Quickjump drop-down list
						if (get_option(WPS_OPTIONS_PREFIX.'_show_dropdown') && !$group_id)
							$html .= '<div style="float:right;">'.__wps__forum_dropdown($cat_id, $topic_id, $group_id).'</div>';				


					$html .= "</div>";
				}

				// Forum options
				$html .= "<div id='forum_options'>";

					$html .= "<a id='show_search' href='javascript:void(0)'>".__("Search", WPS_TEXT_DOMAIN)."</a>";
					$html .= "&nbsp;&nbsp;&nbsp;&nbsp;<a id='show_all_activity' href='javascript:void(0)'>".__("Activity", WPS_TEXT_DOMAIN)."</a>";
					$html .= "&nbsp;&nbsp;&nbsp;&nbsp;<a id='show_threads_activity' href='javascript:void(0)'>".__("Latest Topics", WPS_TEXT_DOMAIN)."</a>";

					if (is_user_logged_in()) {
						$html .= "&nbsp;&nbsp;&nbsp;&nbsp;<a id='show_activity' href='javascript:void(0)'>".__("My Activity", WPS_TEXT_DOMAIN)."</a>";
						$html .= "&nbsp;&nbsp;&nbsp;&nbsp;<a id='show_favs' href='javascript:void(0)'>".__("Favorites", WPS_TEXT_DOMAIN)."</a>";
					}

				$html .= "</div>";
				
				// Sharing icons
				if (get_option(WPS_OPTIONS_PREFIX.'_sharing') != '') {
					$html .= __wps__show_sharing_icons($cat_id, $post->tid, get_option(WPS_OPTIONS_PREFIX.'_sharing'), $group_id);
				}
			
				// Edit Form
				$html .= '<div id="edit-topic-div">';

					$html .= '<div class="new-topic-subject label">'.__("Topic Subject", WPS_TEXT_DOMAIN).'</div>';
					$html .= '<div id="'.$post->tid.'" class="edit-topic-tid"></div>';
					$html .= '<div id="" class="edit-topic-parent"></div>';
					$html .= '<input class="new-topic-subject-input" type="text" name="edit_topic_subject">';
					$html .= '<div class="new-topic-subject label">'.__("Topic Text", WPS_TEXT_DOMAIN).'</div>';
					$html .= __wps__bbcode_toolbar('edit_topic_text');
					$html .= '<textarea class="new-topic-subject-text" id="edit_topic_text" name="edit_topic_text"></textarea>';
					if ($group_id == 0) {
						$html .= '<div class="new-category-div" style="float:left;">'.__("Move Category", WPS_TEXT_DOMAIN).': <select name="new-category" class="new-category" style="width: 200px">';
						$html .= '<option value="">'.__("Select", WPS_TEXT_DOMAIN).'...</option>';
						$categories = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix.'symposium_cats ORDER BY listorder');			
						if ($categories) {
							foreach ($categories as $category) {
								if ($category->allow_new == "on" || current_user_can('level_10')) {
									$html .= '<option value='.$category->cid.'>'.stripslashes($category->title).'</option>';
								}
							}
						}
						$html .= '</select></div>';
					} else {
						// No categories for groups
						$html .= '<input name="new-category" type="hidden" value="0">';
					}

				$html .= '</div>';

			$html .= "</div>"; // __wps__forum_topic_header				

			// Topic starting post
			$html .= "<div id='starting-post'>";
		
				// Show topic header
				$html .= "<div id='top_of_first_post'>";
			
					$html .= "<div class='avatar' style='margin-bottom:0px; margin-top:6px;'>";
						$html .= get_avatar($post->topic_owner, 64);

						if (get_option(WPS_OPTIONS_PREFIX.'_forum_info')) {
						
							$html .= "<div class='forum_info'>";
							
								$sql = "SELECT count(*) FROM ".$wpdb->prefix."symposium_topics WHERE topic_owner = %d";
								$count = $wpdb->get_var($wpdb->prepare($sql, $post->topic_owner));
								$html .= __('Posts:', WPS_TEXT_DOMAIN).' ';
								$html .= '<span class="forum_info_numbers">'.$count.'</span>';

							$html .= "</div>";	
						
							if (get_option(WPS_OPTIONS_PREFIX.'_use_answers') == 'on') {
								$html .= "<div class='forum_info'>";
									// Get widget settings (also used under Replies)
									$settings = get_option("widget_forumexperts-widget");
									if (isset($settings[2]['timescale'])) {
										if (WPS_DEBUG) $html .= 'Getting Widget settings<br />';
										$timescale = $settings[2]['timescale'];
										$w_cat_id = $settings[2]['cat_id'];
										$cat_id_exclude = $settings[2]['cat_id_exclude'];
										$groups = $settings[2]['groups'];
									} else {
										if (WPS_DEBUG) $html .= 'Using default settings<br />';
										$timescale = 7;
										$w_cat_id = '';
										$cat_id_exclude = '';
										$groups = '';
									}
									// Now get value of rating (how many answers during timescale)
									$sql = "SELECT COUNT(*) FROM ".$wpdb->prefix."symposium_topics WHERE topic_owner = %d AND topic_answer = 'on' ";
									$sql .= "AND topic_date >= ( CURDATE() - INTERVAL ".$timescale." DAY )";
									if ($w_cat_id != '' && $w_cat_id > 0) {
										$sql .= "AND topic_category IN (".$w_cat_id.") ";
									}
									if ($cat_id_exclude != '' && $cat_id_exclude > 0) {
										$sql .= "AND topic_category NOT IN (".$cat_id_exclude.") ";
									}
									if ($groups != 'on') {
										$sql .= "AND topic_group = 0 ";
									}								
									$count = $wpdb->get_var($wpdb->prepare($sql, $post->topic_owner));
									if ($count > 0) {
										$html .= __('Rating:', WPS_TEXT_DOMAIN).' ';
										$html .= '<span class="forum_info_numbers">'.$count.'</span>';
									}
									if (WPS_DEBUG) $html .= $wpdb->last_query;
								$html .= "</div>";
							}

							if ($post->topic_started > $previous_login && $post->topic_owner != $current_user->ID && is_user_logged_in() && get_option(WPS_OPTIONS_PREFIX.'_forum_stars')) {
								$html .= "<img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/new.gif' alt='New!' /> ";
							}
						}
						
					$html .= "</div>";
			
					$html .= "<div class='topic-post-header-with-fav'>";
			
						$html .= "<div class='topic-post-header'>";

							if (get_option(WPS_OPTIONS_PREFIX.'_allow_reports') == 'on') {
								$html .= "<a href='javascript:void(0)' title='forum_".$post->tid."' class='report label symposium_report' style='display:none; cursor:pointer'><div class='topic-edit-icon' style='margin-top:-5px;' ><img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/warning.png' /></div></a>";
							}
							$reply_posted_time = strtotime($post->topic_started);
							$reply_posted_expire = $reply_posted_time + (get_option(WPS_OPTIONS_PREFIX.'_forum_lock') * 60);
							$now = time();
							$seconds_left = $reply_posted_expire - $now;
							if ($seconds_left > 0) {
								$title = __('Lock in', WPS_TEXT_DOMAIN).' '.gmdate("H:i:s", $seconds_left);
							} else {
								$title = __('Admin only', WPS_TEXT_DOMAIN);
							}
							if (get_option(WPS_OPTIONS_PREFIX.'_forum_lock') == 0) {
								$title = __('No lock time', WPS_TEXT_DOMAIN);
								$seconds_left = 1;
							}
							if ( ($post->topic_owner == $current_user->ID && $seconds_left > 0) || (can_manage_forum()) ) {
								$now = date('Y-m-d H:i:s', time() - get_option(WPS_OPTIONS_PREFIX.'_forum_lock') * 60);
								if ( (get_option(WPS_OPTIONS_PREFIX.'_forum_lock')==0) || ($post->topic_started > $now) || (can_manage_forum()) ) {
									$html .= "<a href='javascript:void(0)' title='".$post->tid."' id='edit-this-topic' class='edit_topic edit label' style='cursor:pointer'><div class='topic-edit-icon'><img title='".$title."' src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/edit.png' /></div></a>";
								}
							}

							$post_text = stripslashes(__wps__bbcode_replace(stripslashes($post->topic_subject)));
							// Make any shortcodes safe
							$post_text = str_replace("[", "&#91;", $post_text);
							$post_text = str_replace("]", "&#93;", $post_text);
							$html .= $post_text;
							$topic_subject = $post_text;
			
							if ($post->topic_approved != 'on') { $html .= " <em>[".__("pending approval", WPS_TEXT_DOMAIN)."]</em>"; }

							// Favourites
							if (is_user_logged_in()) {
								if (strpos(__wps__get_meta($current_user->ID, 'forum_favs'), "[".$post->tid."]") === FALSE) { 
									$html .= "<img title='".$post->tid."' id='fav_link' src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/fav-off.png' style='height:22px; width:22px; cursor:pointer;' alt='".__("Click to add to favorites", WPS_TEXT_DOMAIN)."' />";						
								} else {
									$html .= "<img title='".$post->tid."' id='fav_link' src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/fav-on.png' style='height:22px; width:22px; cursor:pointer;' alt='".__("Click to remove to favorites", WPS_TEXT_DOMAIN)."' />";						
								}
							}

						$html .= "</div><div style='clear:both'></div>";
										
						$html .= "<div class='started-by' style='margin-top:10px'>";
						$html .= __("Started by", WPS_TEXT_DOMAIN);
						if ( substr(get_option(WPS_OPTIONS_PREFIX.'_forum_ranks'), 0, 2) == 'on' ) {
							$html .= " <span class='forum_rank'>".__wps__forum_rank($post->topic_owner)."</span>";
						}
						$html .= " ".__wps__profile_link($post->topic_owner);
						$html .= " ".__wps__time_ago($post->topic_started);
						$html .= "</div>";

						$post_text = stripslashes($post->topic_post);

						$has_code = (strpos($post_text, '&#91;code&#93;') !== FALSE || strpos($post_text, '[code]') !== FALSE) ? true : false;
						$has_code_end = (strpos($post_text, '&#91;/code&#93;') !== FALSE || strpos($post_text, '[/code]') !== FALSE) ? true : false;
						$post_text = __wps__make_url(stripslashes($post_text));
						$post_text = __wps__bbcode_replace($post_text);
						if (!$has_code) {
							$post_text = __wps__buffer($post_text);
						} else {
							$post_text = str_replace('&#91;code&#93;<br />', '&#91;code&#93;', $post_text);
							if ($has_code_end) {
								$post_text = str_replace('&#91;code&#93;', '<pre>', $post_text);
								$post_text = str_replace('&#91;/code&#93;', '</pre>', $post_text);
								$post_text = str_replace('[code]', '<pre>', $post_text);
								$post_text = str_replace('[/code]', '</pre>', $post_text);
							} else {
								$post_text .= '</pre>';
							}
						}
						if (get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg') == '' || !get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg') || get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg') == 'bold,italic,|,fontselect,fontsizeselect,forecolor,backcolor,|,bullist,numlist,|,link,unlink,|,image,media,|,emotions') {
							$post_text = str_replace(chr(10), "<br />", $post_text);
							$post_text = str_replace(chr(13), "<br />", $post_text);
						}

						// Make any shortcodes safe
						$post_text = str_replace("[", "&#91;", $post_text);
						$post_text = str_replace("]", "&#93;", $post_text);

						// Tidy up <pre> gap
						$post_text = str_replace('<pre><br />', '<pre>', $post_text);
						$post_text = str_replace('</pre><br />', '</pre>', $post_text);

						$html .= "<div class='topic-post-post'>".$post_text."</div><br />";
						
						// Allow owner or site admin to mark topic for information only
						if (get_option(WPS_OPTIONS_PREFIX.'_use_answers') == 'on') {
							if ($post->topic_owner == $current_user->ID || __wps__get_current_userlevel($current_user->ID) == 5) {
								$html .= '<input type="checkbox" id="symposium_for_info" title="'.$post->tid.'"';
								if ($post->for_info == 'on') { $html .= " CHECKED"; }
								$html .= ' /> ';
								$html .= '<em>'.__('This topic is for information only, no answer will be selected.', WPS_TEXT_DOMAIN).'</em>';
							} else {
								if ($post->for_info == 'on') { 
									$html .= '<em>'.__('This topic is for information only, no answer will be selected.', WPS_TEXT_DOMAIN).'</em>';
								}
							}
						}

						// show any uploaded files
						if (get_option(WPS_OPTIONS_PREFIX.'_img_db') == 'on') {

							$cnt_cont = 1;

							// get list of uploaded files from database
							$sql = "SELECT tmpid, filename FROM ".$wpdb->prefix."symposium_topics_images WHERE tid = ".$post->tid." ORDER BY tmpid";
							$images = $wpdb->get_results($sql);
							foreach ($images as $file) {
								$html .= '<div>';
								$ext = explode('.', $file->filename);
								if ($ext[sizeof($ext)-1]=='gif' || $ext[sizeof($ext)-1]=='jpg' || $ext[sizeof($ext)-1]=='png' || $ext[sizeof($ext)-1]=='jpeg') {
									// Image
									$url = WP_CONTENT_URL."/plugins/wp-symposium/get_attachment.php?tid=".$post->tid."&filename=".$file->filename;
									$html .= "<a target='_blank' href='".$url."' rev='".$cnt_cont."' data-owner='".$post->topic_owner."' data-name='".stripslashes($post->topic_subject)."' data-iid='".$cnt_cont."' rel='symposium_gallery_photos_".$post->tid."' class='wps_gallery_album'>";
									$cnt_cont++;
									if (get_option(WPS_OPTIONS_PREFIX.'_forum_thumbs') == 'on') {
										list($width, $height, $type, $attr) = getimagesize($url);
										//list($width, $height, $type, $attr) = getimagesize(parse_url(get_bloginfo('url'),PHP_URL_SCHEME)."://".parse_url(get_bloginfo('url'),PHP_URL_HOST).$url);
										$max_width = get_option(WPS_OPTIONS_PREFIX.'_forum_thumbs_size');
										if ($width > $max_width) {
											$height = $height / ($width / $max_width);
											$width = $max_width;
										}
										$html .= '<img src="'.$url.'" rel="symposium_gallery_photos_'.$post->tid.'" style="width:'.$width.'px; height:'.$height.'px" />';
									} else {
										$html .= $file->filename;
									}
									$html .= "</a> ";
								} else {
									// Video
									if ($ext[sizeof($ext)-1]=='mp4' && get_option(WPS_OPTIONS_PREFIX.'_jwplayer') == "on") {
										$html .= "<a href='#' rel='jwplayer'>".$file->filename."</a>";
									} else {
										// Document
										global $blog_id;
										if ($blog_id > 1) {
											$url = get_option(WPS_OPTIONS_PREFIX.'_img_url').'/'.$blog_id.'/forum/'.$post->tid.'/'.$file->filename;
										} else {
											$url = get_option(WPS_OPTIONS_PREFIX.'_img_url').'/forum/'.$post->tid.'/'.$file->filename;
										}
										$url = WP_CONTENT_URL."/plugins/wp-symposium/get_attachment.php?tid=".$post->tid."&filename=".$file->filename;
										
										$html .= "<a href='".$url."' title='".$file->filename."'>".$url."</a><br>";	
										$html .= "<a href='".$url."' title='".$file->filename."' rel='mediaplayer'>".$file->filename."</a>";															
									}
								}
								if ($post->topic_owner == $current_user->ID || __wps__get_current_userlevel($current_user->ID) == 5) {
									$html .= '<img id="'.$post->tid.'" title="'.$file->filename.'" class="remove_forum_post link_cursor" src="'.get_option(WPS_OPTIONS_PREFIX.'_images').'/delete.png" /> ';
								}
								$html .= '</div>';	
							}


						} else {

							// Filesystem
							if (isset($blog_id) && $blog_id > 1) {
								$targetPath = get_option(WPS_OPTIONS_PREFIX.'_img_path')."/".$blog_id."/forum/".$post->tid;
							} else {
								$targetPath = get_option(WPS_OPTIONS_PREFIX.'_img_path')."/forum/".$post->tid;
							}

							if (file_exists($targetPath)) {
								$handler = opendir($targetPath);
								$file_list = array();
								while ($file = readdir($handler)) {
									if ( ($file != "." && $file != ".." && $file != ".DS_Store") && (!is_dir($targetPath.'/'.$file)) ) {
										$file_list[] = array('name' => $file, 'size' => filesize($targetPath.'/'.$file), 'mtime' => filemtime($targetPath.'/'.$file));
									}
								}
								// sort by datetime file stamp
								usort($file_list, "__wps__cmp");
								$cnt = 0;
								$cnt_cont = 1;
								global $blog_id;
								foreach($file_list as $file) {
									$cnt++;

									$html .= '<div>';
									if ($blog_id > 1) {
										$url = get_option(WPS_OPTIONS_PREFIX.'_img_url').'/'.$blog_id.'/forum/'.$post->tid.'/'.$file['name'];
									} else {
										$url = get_option(WPS_OPTIONS_PREFIX.'_img_url').'/forum/'.$post->tid.'/'.$file['name'];
									}
									$ext = explode('.', $file['name']);
									if (strpos(get_option(WPS_OPTIONS_PREFIX.'_image_ext'), $ext[sizeof($ext)-1]) > 0) {
										// Image
										$html .= "<a target='_blank' href='".$url."' data-name='".stripslashes($post->topic_subject)."' data-iid='".$cnt_cont."' rel='symposium_gallery_photos_".$post->tid."'>";
										
										if (get_option(WPS_OPTIONS_PREFIX.'_forum_thumbs') == 'on') {
											//list($width, $height, $type, $attr) = getimagesize($url);
											list($width, $height, $type, $attr) = @getimagesize(parse_url(get_bloginfo('url'),PHP_URL_SCHEME)."://".parse_url(get_bloginfo('url'),PHP_URL_HOST).$url);
											$max_width = get_option(WPS_OPTIONS_PREFIX.'_forum_thumbs_size');
											if ($width > $max_width) {
												$height = $height / ($width / $max_width);
												$width = $max_width;
											}
											$html .= '<img src="'.$url.'" rev="'.$cnt_cont.'" rel="symposium_gallery_photos_'.$post->tid.'" class="wps_gallery_album" style="width:'.$width.'px; height:'.$height.'px" />';
											$cnt_cont++;

										} else {
											$html .= $file;
										}
										$html .= '</a> ';
									} else {
										// Video
										if (get_option(WPS_OPTIONS_PREFIX.'_jwplayer') == "on" && strpos(get_option(WPS_OPTIONS_PREFIX.'_video_ext'), $ext[sizeof($ext)-1]) > 0) {

											if (get_option(WPS_OPTIONS_PREFIX.'_forum_thumbs') == 'on') {
												$html .= '<div id="mediaplayer_'.$cnt.'">JW Player goes here</div> ';
											} else {
												$html .= '<div style="display:none">';
												$html .= '<div id="mediaplayer_'.$cnt.'">JW Player goes here</div> ';
												$html .= '</div>';
												$html .= "<a href='".$url."' class='jwplayer' title='".$file['name']."' rel='mediaplayer'>".$file['name']."</a> ";															
											}

											$html .= '<script type="text/javascript"> ';
											$html .= '	jwplayer("mediaplayer_'.$cnt.'").setup({';
											$html .= '		flashplayer: "'.WPS_PLUGIN_URL.'/jwplayer/player.swf",';
											$html .= '		image: "'.WPS_PLUGIN_URL.'/jwplayer/preview.gif",';
											$html .= '		file: "'.$url.'",';
											$html .= '		width: "'.get_option(WPS_OPTIONS_PREFIX.'_forum_thumbs_size').'px",';
											$html .= '		height: "250px"';
											$html .= '	});';
											$html .= '</script>';	

										} else {
											// Document
											$html .= "<a href='".$url."' title='".$file['name']."' rel='mediaplayer'>".$file['name']."</a>";															
										}
									}
									if ($post->topic_owner == $current_user->ID || __wps__get_current_userlevel($current_user->ID) == 5) {
										$html .= '<img id="'.$post->tid.'" title="'.$file['name'].'" class="remove_forum_post link_cursor" src="'.get_option(WPS_OPTIONS_PREFIX.'_images').'/delete.png" /> ';
									}
									$html .= '</div>';
									
								}			
								closedir($handler);
							}
						}
						
						// Add Signature
						$signature = str_replace("\\", "", __wps__get_meta($post->topic_owner, 'signature'));
						if ($signature != '') {
							$html .= '<div class="sep_top"><em>'.__wps__make_url($signature).'</em></div>';
						}

										
					$html .= "</div><div style='clear:both'></div>";				
												
				$html .= "</div>";

				// Update views
				if (__wps__get_current_userlevel() == 5) {
					if (get_option(WPS_OPTIONS_PREFIX.'_include_admin') == "on") { 
						$wpdb->query( $wpdb->prepare("UPDATE ".$wpdb->prefix."symposium_topics SET topic_views = topic_views + 1 WHERE tid = %d", $post->tid) );
					}
				} else {
					$wpdb->query( $wpdb->prepare("UPDATE ".$wpdb->prefix."symposium_topics SET topic_views = topic_views + 1 WHERE tid = %d", $post->tid) );
				}
					
			$html .= "</div>";		

			// Advertising code +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

			if (get_option(WPS_OPTIONS_PREFIX.'_ad_forum_topic_start')) {
				$html .= "<div id='ad_forum_topic_start'>";
					$html .= stripslashes(get_option(WPS_OPTIONS_PREFIX.'_ad_forum_topic_start'));	
				$html .= "</div>";
			}

			// Replies ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
			
			$sql = "SELECT t.tid, (SELECT SUM(score) FROM ".$wpdb->prefix."symposium_topics_scores s WHERE s.tid = t.tid) AS score, topic_subject, topic_approved, topic_post, t.topic_started, t.topic_date, topic_owner, display_name, topic_answer, ID
				FROM ".$wpdb->prefix."symposium_topics t INNER JOIN ".$wpdb->base_prefix."users u ON t.topic_owner = u.ID 
				WHERE (t.topic_approved = 'on' OR t.topic_owner = %d) AND t.topic_parent = %d ORDER BY t.tid";
				
		
			if (get_option(WPS_OPTIONS_PREFIX.'_oldest_first') != "on") { $sql .= " DESC"; }
	
			$child_query = $wpdb->get_results($wpdb->prepare($sql, $current_user->ID, $post->tid));

			$sql = "SELECT COUNT(*) FROM ".$wpdb->prefix."symposium_topics t INNER JOIN ".$wpdb->base_prefix."users u ON t.topic_owner = u.ID WHERE (t.topic_approved = 'on' OR t.topic_owner = %d) AND t.topic_parent = %d ORDER BY t.tid";
			$child_count = $wpdb->get_var($wpdb->prepare($sql, $current_user->ID, $post->tid));

			$html .= "<div id='child-posts'>";

				if ($child_query) {

					// Pagination		
					if (get_option(WPS_OPTIONS_PREFIX.'_pagination') == "on" && get_option(WPS_OPTIONS_PREFIX.'_forum_ajax') != 'on') {
						$pagination = get_option(WPS_OPTIONS_PREFIX.'_pagination_size') ? get_option(WPS_OPTIONS_PREFIX.'_pagination_size') : 10;
						$page_count = is_int($child_count/$pagination) ? floor($child_count/$pagination) : floor($child_count/$pagination)+1;
						if ($page_count > 1) {
							if ( get_option(WPS_OPTIONS_PREFIX.'_permalink_structure') ) {
								$uri_parts = explode('?', $_SERVER['REQUEST_URI'], 2);
								$pagination_url = $uri_parts[0];
								$url_parts = explode('/', $pagination_url);
								$part = get_option(WPS_OPTIONS_PREFIX.'_permalinks_cats') ? 4 : 3;
								$goto_page = $url_parts[$part];
								$page = is_numeric($goto_page) ? $goto_page : 1;
								if (get_option(WPS_OPTIONS_PREFIX.'_permalinks_cats')) {
									$pagination_url = '/'.$url_parts[1].'/'.$url_parts[2].'/'.$url_parts[3];
								} else {
									$pagination_url = '/'.$url_parts[1].'/'.$url_parts[2];
								}
							} else {
								$pagination_url = $forum_url.$q.'cid='.$post->topic_category.'&show='.$post->tid;
								$page = isset($_GET['view']) ? $_GET['view'] : 1;
							}
							if (get_option(WPS_OPTIONS_PREFIX.'_pagination_location') == 'both' || get_option(WPS_OPTIONS_PREFIX.'_pagination_location') == 'top' || !get_option(WPS_OPTIONS_PREFIX.'_pagination_location'))
								$html .= __wps__insert_pagination($page, $page_count, $group_id, $pagination_url);
	  					}
	  				}

					// Get current number of votes by this member to see if can vote
					$sql = "SELECT count(*) FROM ".$wpdb->prefix."symposium_topics WHERE topic_owner = %d";
					$post_count = $wpdb->get_var($wpdb->prepare($sql, $current_user->ID));

					// Div to show if can't vote yet
					$html .= '<div id="symposium_novote_dialog" style="display:none">';
					$html .= sprintf(__("Spam Protection", WPS_TEXT_DOMAIN), get_option(WPS_OPTIONS_PREFIX.'_use_votes_min'));
					$html .= '</div>';
					$html .= '<div id="symposium_novote" style="display:none">';
					$html .= sprintf(__("Sorry, you can't vote until you have made %d posts.", WPS_TEXT_DOMAIN), get_option(WPS_OPTIONS_PREFIX.'_use_votes_min'));
					$html .= '</div>';

					// For pagination
					$reply_count = 0;
					$start_reply = ($page-1)*$pagination+1;
					$end_reply = (($page-1)*$pagination)+$pagination;
					
					foreach ($child_query as $child) {
						
						$reply_count++;
						if ($reply_count >= $start_reply && $reply_count <= $end_reply) {
							$score = $child->score;
							if ($score == NULL) { $score = 0; }
							
							$reply_html = '';

							$reply_html .= "<div id='reply".$child->tid."' class='child-reply";
								$trusted = __wps__get_meta($child->topic_owner, 'trusted');
								if ($trusted == 'on') { $reply_html .= " trusted"; }
								$reply_html .= "'>";

								$reply_html .= "<div class='avatar'>";
									$reply_html .= get_avatar($child->ID, 64);
									
									if (get_option(WPS_OPTIONS_PREFIX.'_forum_info')) {
									
										$reply_html .= "<div class='forum_info'>";
										
											$sql = "SELECT count(*) FROM ".$wpdb->prefix."symposium_topics WHERE topic_owner = %d";
											$count = $wpdb->get_var($wpdb->prepare($sql, $child->topic_owner));
											$reply_html .= __('Posts:', WPS_TEXT_DOMAIN).' ';								
											$reply_html .= '<span class="forum_info_numbers">'.$count.'</span>';
											
										$reply_html .= "</div>";	

										if (get_option(WPS_OPTIONS_PREFIX.'_use_answers') == 'on') {
											$reply_html .= "<div class='forum_info'>";
												$sql = "SELECT COUNT(*) FROM ".$wpdb->prefix."symposium_topics WHERE topic_owner = %d AND topic_answer = 'on' ";
												$sql .= "AND topic_date >= ( CURDATE() - INTERVAL ".$timescale." DAY )";
												if ($w_cat_id != '' && $w_cat_id > 0) {
													$sql .= "AND topic_category IN (".$w_cat_id.") ";
												}
												if ($cat_id_exclude != '' && $cat_id_exclude > 0) {
													$sql .= "AND topic_category NOT IN (".$cat_id_exclude.") ";
												}
												if ($groups != 'on') {
													$sql .= "AND topic_group = 0 ";
												}								
												$count = $wpdb->get_var($wpdb->prepare($sql, $child->topic_owner));
												if ($count > 0) {
													$reply_html .= __('Rating:', WPS_TEXT_DOMAIN).' ';
													$reply_html .= '<span class="forum_info_numbers">'.$count.'</span>';
												}
												if (WPS_DEBUG) $reply_html .= $wpdb->last_query;
											$reply_html .= "</div>";	
										}	
										
									}

									if ($child->topic_date > $previous_login && $child->topic_owner != $current_user->ID && is_user_logged_in() && get_option(WPS_OPTIONS_PREFIX.'_forum_stars')) {
										$reply_html .= "<img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/new.gif' alt='New!' /> ";
									}

									
								$reply_html .= "</div>";	
														
								// Reply box
								$reply_html .= "<div class='__wps__reply_box'>";
								
									if ( (get_option(WPS_OPTIONS_PREFIX.'_use_votes_remove') == $score) && (__wps__get_current_userlevel() < 5) && ($score != 0) ) {
										$reply_html .= '<p>'.__('This post has been voted off the forum', WPS_TEXT_DOMAIN).'</p>';
									} else {
									
										if ( (get_option(WPS_OPTIONS_PREFIX.'_use_votes_remove') == $score) && ($score != 0) ) {
											$reply_html .= '<p>'.__('This post has been voted off the forum (only visible to site admins) with a score of', WPS_TEXT_DOMAIN).' '.$score.'.</p>';
										}
										// Votes (if being used)
										if (get_option(WPS_OPTIONS_PREFIX.'_use_votes') == 'on' && ($child->topic_owner != $current_user->ID || __wps__get_current_userlevel() == 5)) {
											$reply_html .= "<div class='floatright forum_post_score' style='width: 24px; text-align:center;'>";
												$reply_html .= "<div style='line-height:16px;'>";
													if ($post_count >= get_option(WPS_OPTIONS_PREFIX.'_use_votes_min')) {
														$reply_html .= "<img id='".$child->tid."' class='forum_post_score_change' title='plus' src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/smilies/good.png' style='cursor:pointer;width:24px; height:24px;' />";
													} else {
														$reply_html .= "<img id='".$child->tid."' class='forum_post_score_change' title='novote' src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/smilies/good.png' style='cursor:pointer;width:24px; height:24px;' />";
													}
												$reply_html .= "</div>";
												$reply_html .= "<div id='forum_score_".$child->tid."' style='margin-bottom:3px'>";
													if ($child->score > 0) { $reply_html .= '+'; }
													$reply_html .= $score;
												$reply_html .= "</div>";
												$reply_html .= "<div>";
													if ($post_count >= get_option(WPS_OPTIONS_PREFIX.'_use_votes_min')) {
														$reply_html .= "<img id='".$child->tid."' class='forum_post_score_change' title='minus' src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/smilies/bad.png' style='cursor:pointer;width:24px; height:24px;' />";
													} else {
														$reply_html .= "<img id='".$child->tid."' class='forum_post_score_change' title='novote' src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/smilies/bad.png' style='cursor:pointer;width:24px; height:24px;' />";
													}
												$reply_html .= "</div>";
											$reply_html .= "</div>";
										}
										// Answer feature (if being used)
										if (get_option(WPS_OPTIONS_PREFIX.'_use_answers') == 'on') {
											$reply_html .= "<div class='floatright'>";
												if ($child->topic_answer == 'on') {
													$reply_html .= "<img id='symposium_accepted_answer' src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/tick.png' style='cursor:pointer;margin-top:3px;width:20px; height:20px;' />";
												} else {
													if ($post->topic_owner == $current_user->ID || __wps__get_current_userlevel() == 5) {
														$reply_html .= "<a id=".$child->tid." class='forum_post_answer' href='javascript:void(0);' style='margin-right:10px;";
														if ($post->for_info == 'on') {
															$reply_html .= "display:none;";
														}
														$reply_html .= "'>".__('Accept answer', WPS_TEXT_DOMAIN)."</a>";
													}
												}
											$reply_html .= "</div>";
										}
										
										$reply_html .= "<div class='topic-edit-delete-icon'>";
											// Report warning (if being used)
											if (get_option(WPS_OPTIONS_PREFIX.'_allow_reports') == 'on') {
												$reply_html .= "<a href='javascript:void(0)' class='floatright link_cursor symposium_report' style='display:none' title='reply_".$child->tid."'><img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/warning.png' /></a>";
											}
											if ( ($child->topic_owner == $current_user->ID) || (can_manage_forum()) ) {
												$reply_posted_time = strtotime($child->topic_started);
												$reply_posted_expire = $reply_posted_time + (get_option(WPS_OPTIONS_PREFIX.'_forum_lock') * 60);
												$now = time();
												$seconds_left = $reply_posted_expire - $now;
												if ($seconds_left > 0) {
													$title = __('Locking reply in', WPS_TEXT_DOMAIN).' '.gmdate("H:i:s", $seconds_left);
													$ttitle = '<br /><em>'.$title.'</em>';
												} else {
													$title = __('Admin only', WPS_TEXT_DOMAIN);
													$ttitle = '';
												}
												if (get_option(WPS_OPTIONS_PREFIX.'_forum_lock') == 0) {
													$title = __('No lock time', WPS_TEXT_DOMAIN);
													$seconds_left = 1;
												}
												if ( ($child->topic_owner == $current_user->ID && $seconds_left > 0) || (can_manage_forum()) ) {
													$reply_html .= "<a href='javascript:void(0)' class='floatright link_cursor delete_forum_reply' style='display:none' id='".$child->tid."'><img title='".$title."' src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/delete.png' /></a>";
													$reply_html .= "<a href='javascript:void(0)' class='floatright link_cursor edit_forum_reply' style='display:none; margin-right: 5px' id='".$child->tid."'><img title='".$title."' src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/edit.png' /></a>";
												} else {
													// $reply_html .= 'locked';
												}
											}
										$reply_html .= "</div>";
										$reply_html .= "<div class='started-by'>";
										if ( substr(get_option(WPS_OPTIONS_PREFIX.'_forum_ranks'), 0, 2) == 'on' ) {
											$reply_html .= "<span class='forum_rank'>".__wps__forum_rank($child->topic_owner)."</span> ";
										}
										$reply_html .= __wps__profile_link($child->topic_owner);
										$reply_html .= " ".__("replied", WPS_TEXT_DOMAIN)." ".__wps__time_ago($child->topic_date)."...";
										if (isset($ttitle)) $reply_html .= $ttitle;
										$reply_html .= "</div>";
										$reply_html .= "<div id='child_".$child->tid."' class='child-reply-post'>";

											$reply_text = __wps__make_url(stripslashes($child->topic_post));

											$has_code = (strpos($reply_text, '&#91;code&#93;') !== FALSE || strpos($reply_text, '[code]') !== FALSE) ? true : false;
											$has_code_end = (strpos($reply_text, '&#91;/code&#93;') !== FALSE || strpos($reply_text, '[/code]') !== FALSE) ? true : false;
											$reply_text = __wps__make_url(stripslashes($reply_text));
											$reply_text = __wps__bbcode_replace($reply_text);
											if (!$has_code) {
												$reply_text = __wps__buffer($reply_text);
											} else {
												$reply_text = str_replace('&#91;code&#93;<br />', '&#91;code&#93;', $reply_text);
												if ($has_code_end) {
													$reply_text = str_replace('&#91;code&#93;', '<pre>', $reply_text);
													$reply_text = str_replace('&#91;/code&#93;', '</pre>', $reply_text);
													$reply_text = str_replace('[code]', '<pre>', $reply_text);
													$reply_text = str_replace('[/code]', '</pre>', $reply_text);
												} else {
													$reply_text .= '</pre>';
												}
											}
											if (get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg') == '' || !get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg') || get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg') == 'bold,italic,|,fontselect,fontsizeselect,forecolor,backcolor,|,bullist,numlist,|,link,unlink,|,image,media,|,emotions') {
												$reply_text = str_replace(chr(10), "<br />", $reply_text);
												$reply_text = str_replace(chr(13), "<br />", $reply_text);
											}

											// Make any shortcodes safe
											$reply_text = str_replace("[", "&#91;", $reply_text);
											$reply_text = str_replace("]", "&#93;", $reply_text);

											// Tidy up <pre> gap
											$reply_text = str_replace('<pre><br />', '<pre>', $reply_text);
											$reply_text = str_replace('</pre><br />', '</pre>', $reply_text);

											$reply_html .= "<p>".$reply_text;
											if ($child->topic_approved != 'on') { $reply_html .= " <em>[".__("pending approval", WPS_TEXT_DOMAIN)."]</em>"; }
											$reply_html .= "</p>";
		
										$reply_html .= "</div>";
																		
									}
		
									// show any uploaded files
									if (get_option(WPS_OPTIONS_PREFIX.'_img_db') == 'on') {
		
										// get list of uploaded files from database
										$sql = "SELECT tmpid, filename FROM ".$wpdb->prefix."symposium_topics_images WHERE tid = ".$child->tid." ORDER BY tmpid";
										$images = $wpdb->get_results($sql);
										foreach ($images as $file) {

											$reply_html .= '<div>';
											$url = WP_CONTENT_URL."/plugins/wp-symposium/get_attachment.php?tid=".$child->tid."&filename=".$file->filename;

											$reply_html .= "<a target='_blank' href='".$url."' rev='".$cnt_cont."' data-owner='".$post->topic_owner."' data-name='".stripslashes($post->topic_subject)."' data-iid='".$cnt_cont."' class='wps_gallery_album' ";

											$ext = explode('.', $file->filename);
											if ($ext[sizeof($ext)-1]=='gif' || $ext[sizeof($ext)-1]=='jpg' || $ext[sizeof($ext)-1]=='png' || $ext[sizeof($ext)-1]=='jpeg') {
												$reply_html .= " rel='symposium_gallery_photos_".$post->tid."'";
											}
											$reply_html .= '>';

											if (get_option(WPS_OPTIONS_PREFIX.'_forum_thumbs') == 'on') {
												list($width, $height, $type, $attr) = getimagesize($url);
												//list($width, $height, $type, $attr) = getimagesize(parse_url(get_bloginfo('url'),PHP_URL_SCHEME)."://".parse_url(get_bloginfo('url'),PHP_URL_HOST).$url);
												$max_width = get_option(WPS_OPTIONS_PREFIX.'_forum_thumbs_size');
												if ($width > $max_width) {
													$height = $height / ($width / $max_width);
													$width = $max_width;
												}
												$reply_html .= '<img src="'.$url.'" rev="'.$cnt_cont.'" rel="symposium_gallery_photos_'.$post->tid.'" style="width:'.$width.'px; height:'.$height.'px" />';
											} else {
												$reply_html .= $file->filename;
											}
											$reply_html .= '</a> ';
											$reply_html .= '<img id="'.$child->tid.'" title="'.$file->filename.'" class="remove_forum_post link_cursor" src="'.get_option(WPS_OPTIONS_PREFIX.'_images').'/delete.png" /> ';
											$reply_html .= '</div>';	
											$cnt_cont++;
										}
		
									} else {
										
										if (isset($blog_id) && $blog_id > 1) {
											$targetPath = get_option(WPS_OPTIONS_PREFIX.'_img_path')."/".$blog_id."/forum/".$post->tid.'/'.$child->tid;
										} else {
											$targetPath = get_option(WPS_OPTIONS_PREFIX.'_img_path')."/forum/".$post->tid.'/'.$child->tid;
										}

										if (file_exists($targetPath)) {

											$handler = opendir($targetPath);
											$file_list = array();
											while ($file = readdir($handler)) {
												if ( ($file != "." && $file != ".." && $file != ".DS_Store") && (!is_dir($targetPath.'/'.$file)) ) {
													$file_list[] = array('name' => $file, 'size' => filesize($targetPath.'/'.$file), 'mtime' => filemtime($targetPath.'/'.$file));
												}
											}
											// sort by datetime file stamp
											usort($file_list, "__wps__cmp");

											$cnt = 0;
											$handler = opendir($targetPath);
											foreach($file_list as $file) {
													
												$cnt++;

												$reply_html .= '<div style="overflow:auto;">';
												global $blog_id;
												if ($blog_id > 1) {
													$url = get_option(WPS_OPTIONS_PREFIX.'_img_url').'/'.$blog_id.'/forum/'.$post->tid.'/'.$child->tid.'/'.$file['name'];
												} else {
													$url = get_option(WPS_OPTIONS_PREFIX.'_img_url').'/forum/'.$post->tid.'/'.$child->tid.'/'.$file['name'];
												}
												$ext = explode('.', $file['name']);
												if (strpos(get_option(WPS_OPTIONS_PREFIX.'_image_ext'), $ext[sizeof($ext)-1]) > 0) {
													// Image
													$reply_html .= "<a target='_blank' href='".$url."' data-name='".stripslashes($post->topic_subject)."' data-iid='".$cnt_cont."' rel='symposium_gallery_photos_".$post->tid."'";
													$reply_html .= ' class="wps_gallery_album" title="'.$file['name'].'">';
													if (get_option(WPS_OPTIONS_PREFIX.'_forum_thumbs') == 'on') {
														//list($width, $height, $type, $attr) = getimagesize(get_bloginfo('url').$url);
														list($width, $height, $type, $attr) = @getimagesize(parse_url(get_bloginfo('url'),PHP_URL_SCHEME)."://".parse_url(get_bloginfo('url'),PHP_URL_HOST).$url);
														$max_width = get_option(WPS_OPTIONS_PREFIX.'_forum_thumbs_size');
														if ($width > $max_width) {
															$height = $height / ($width / $max_width);
															$width = $max_width;
														}
														$reply_html .= '<img src="'.$url.'" rev="'.$cnt_cont.'" rel="symposium_gallery_photos_'.$post->tid.'" class="wps_gallery_album" style="width:'.$width.'px; height:'.$height.'px" />';
														$cnt_cont++;
													} else {
														$reply_html .= $file['name'];
													}
													$reply_html .= '</a> ';
												} else {
													// Video
													if (get_option(WPS_OPTIONS_PREFIX.'_jwplayer') == "on" && strpos(get_option(WPS_OPTIONS_PREFIX.'_video_ext'), $ext[sizeof($ext)-1]) > 0) {
														
														$video_id = $child->tid.'_'.$cnt;
														
														if (get_option(WPS_OPTIONS_PREFIX.'_forum_thumbs') == 'on') {
															$reply_html .= '<div id="mediaplayer'.$video_id.'">JW Player goes here</div> ';
														} else {
															$reply_html .= '<div style="display:none">';
															$reply_html .= '<div id="mediaplayer'.$video_id.'">JW Player goes here</div> ';
															$reply_html .= '</div>';
															$reply_html .= "<a href='#' class='jwplayer' title='".$file['name']."' rel='mediaplayer".$video_id."'>".$file['name']."</a> ";															
														}
														$reply_html .= '<script type="text/javascript"> ';
														$reply_html .= '	jwplayer("mediaplayer'.$video_id.'").setup({';
														$reply_html .= '		flashplayer: "'.WPS_PLUGIN_URL.'/jwplayer/player.swf",';
														$reply_html .= '		image: "'.WPS_PLUGIN_URL.'/jwplayer/preview.gif",';
														$reply_html .= '		file: "'.$url.'",';
														$reply_html .= '		width: "'.get_option(WPS_OPTIONS_PREFIX.'_forum_thumbs_size').'px",';
														$reply_html .= '		height: "250px"';
														$reply_html .= '	});';
														$reply_html .= '</script>';																																												
													} else {
														// Document
														$reply_html .= "<a target='_blank' href='".$url."'>".$file['name']."</a> ";		
													}
												}
												if ($child->topic_owner == $current_user->ID || __wps__get_current_userlevel($current_user->ID) == 5) {
													$reply_html .= '<img id="'.$post->tid.'/'.$child->tid.'" title="'.$file['name'].'" class="remove_forum_post link_cursor" src="'.get_option(WPS_OPTIONS_PREFIX.'_images').'/delete.png" /> ';
												}
												$reply_html .= '</div>';
													
											}			
											closedir($handler);
										}
									}

								// Add Signature
								$signature = str_replace("\\", "", __wps__get_meta($child->topic_owner, 'signature'));
								if ($signature != '') {
									$reply_html .= '<div class="sep_top"><em>'.__wps__make_url($signature).'</em></div>';
								}
								
								// Check for any comments now for side image (then used below)
								$sql = "SELECT * FROM ".$wpdb->prefix."symposium_topics WHERE
										topic_parent = %d ORDER BY tid";
								$comments = $wpdb->get_results($wpdb->prepare($sql, $child->tid));
								if (WPS_DEBUG) $reply_html .= $wpdb->last_query;
								if ($comments)
									$reply_html .= "<div class='__wps__forum_comment_bubble'><img style='border:0;box-shadow:none;width:32px;height:32px;' src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/comment.png' /></div>";

								$reply_html .= "</div>"; // End of reply box
								
								// Comments on the reply
								$reply_html .= "<div class='reply-comments'>";

									$reply_html .= "<div class='reply-comments-box'>";
									if ($comments) {
										foreach ($comments AS $comment) {
											$reply_html .= "<div id='comment".$comment->tid."' class='reply-comments-reply'>";
												$reply_html .= get_avatar($comment->topic_owner, 32);
												$reply_html .= "<div class='reply-comments-box-text'>";

													$reply_html .= "<div class='topic-edit-delete-icon'>";
														// Report warning (if being used) 
														if (get_option(WPS_OPTIONS_PREFIX.'_allow_reports') == 'on') {
															$reply_html .= "<a title='comment_".$comment->tid."' href='javascript:void(0);' style='padding:0px' class='report_post symposium_report reply_warning'><img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/warning.png' style='width:14px;height:14px' /></a>";
														}
														// Delete comment
														if ( ($comment->topic_owner == $current_user->ID && $seconds_left > 0) || (current_user_can('level_10')) ) {
															$reply_html .= "<a href='javascript:void(0)' class='floatright link_cursor delete_forum_reply' style='display:none' id='".$comment->tid."'><img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/delete.png' /></a>";
														}
													$reply_html .= "</div>";


													$reply_html .= "<div class='started-by'>".__wps__profile_link($comment->topic_owner)." ".__('commented', WPS_TEXT_DOMAIN)." ".__wps__time_ago($comment->topic_date)."</div>";
													$reply_html .= __wps__buffer(stripslashes($comment->topic_post));
												$reply_html .= "</div>";
											$reply_html .= "</div>";
										}
									}
									$reply_html .= "</div>";
		
									$reply_html .= "<div class='quick-comment-box-sep'></div>";
								

								// Quick comment box (show link)
								if ($can_comment && $can_reply_switch) {
									$reply_html .= "<div class='quick-comment-box-show'>";
										$reply_html .= '<a class="quick-comment-box-show-link" href="javascript:void(0);">'.__('Add a quick comment...', WPS_TEXT_DOMAIN).'</a>';
									$reply_html .= "</div>";
								}
								
								// Quick comment box
								if (get_option(WPS_OPTIONS_PREFIX.'_elastic') == 'on') { $elastic = ' elastic'; } else { $elastic = ''; }					
								$reply_html .= "<div class='quick-comment-box'>";
									$reply_html .= "<textarea class='quick-comment-box-comment ".$elastic."'>";
									$reply_html .= "</textarea><br />";
									$reply_html .= '<input type="submit" rel="'.$child->tid.'" class="quick-comment-box-add __wps__button" value="'.__("Add Comment", WPS_TEXT_DOMAIN).'" />';
								$reply_html .= "</div>";
								
								$reply_html .= "</div>"; // End comments on the reply
								
							$reply_html .= "</div>";
							
							$reply_html = apply_filters( '__wps__forum_replies_filter', $reply_html );
							
							$html .= $reply_html;

	 						$html .= '<div class="sep __wps__child_reply_sep"></div>';


	 					} // End pagination check
 
					}

					if (!isset($page_count)) $page_count = 1;
					if ( ($page_count > 1) && (get_option(WPS_OPTIONS_PREFIX.'_pagination') == "on" && get_option(WPS_OPTIONS_PREFIX.'_forum_ajax') != 'on') && (get_option(WPS_OPTIONS_PREFIX.'_pagination_location') == 'both' || get_option(WPS_OPTIONS_PREFIX.'_pagination_location') == 'bottom' || !get_option(WPS_OPTIONS_PREFIX.'_pagination_location')) )
						$html .= __wps__insert_pagination($page, $page_count, $group_id, $pagination_url);

			
			} else {
		
				$html .= "<div class='child-reply'>";
				$html .= __("No replies posted yet.", WPS_TEXT_DOMAIN);
				$html .= "</div>";
				$html .= "<div class='sep'></div>";						
		
			}			

			$html .= "</div>";

			// Quick Reply
			if ($can_reply) {
				
				if ($can_reply_switch) {
				
					$html .= '<div id="reply-topic-bottom" name="reply-topic-bottom" style="padding:0; width:100%;">';
	
						$html .= '<input type="hidden" id="__wps__reply_tid" value="'.$post->tid.'">';
						$html .= '<input type="hidden" id="__wps__reply_cid" value="'.$cat_id.'">';
											
						$html .= '<div class="reply-topic-subject label">'.__("Reply to this Topic", WPS_TEXT_DOMAIN).'</div>';
	
						if (get_option(WPS_OPTIONS_PREFIX.'_elastic') == 'on') { $elastic = ' elastic'; } else { $elastic = ''; }
	
						if (get_option(WPS_OPTIONS_PREFIX.'_use_wp_editor')) {
							// WordPress TinyMCE
							$settings = array(
							    'wpautop' => true,
							    'media_buttons' => false,
							    'tinymce' => array(
							        'theme_advanced_buttons1' => 'bold,example,italic,|,bullist,numlist,blockquote,|,justifyleft,justifycenter,justifyright,|,spellchecker,fullscreen,wp_adv',
							        'theme_advanced_buttons2' => 'fontselect,forecolor,backcolor,fontsizeselect,underline,|,charmap,|,outdent,indent',
							        'theme_advanced_buttons3' => '',
							        'theme_advanced_buttons4' => '',
							        'width' => '100%'
							    	),
							    'quicktags' => false,
							    'textarea_rows' => 10
							);					
							ob_start();
							wp_editor( '', 'wpstinymcereply', $settings );						
							$editor = ob_get_contents();
							ob_end_clean();
							$html .= $editor.'<br />';
						} else {
							$html .= '<div id="__wps__reply_text_parent" style="width:'.get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_width').';">';
							$html .= __wps__bbcode_toolbar('__wps__reply_text');
							$html .= '<textarea class="textarea_Editor reply-topic-text" id="__wps__reply_text" style="width:100%; height:'.get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_height').';" ></textarea></div>';
							$html .= '<div class="sep"></div>';
						}

						// For admin's only set this as the answer
						if (get_option(WPS_OPTIONS_PREFIX.'_use_answers') == 'on' && __wps__get_current_userlevel() == 5) {
							$html .= '<br /><input type="checkbox" id="quick-reply-answer" /> '.__('Set this as the answer', WPS_TEXT_DOMAIN).'<br />';
						}
	
						if ( get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg') ) $html .= '<br />';
						$html .= '<input type="submit" id="quick-reply-warning" class="__wps__button" style="float: left" value="'.__("Reply", WPS_TEXT_DOMAIN).'" />';
	
						// Upload
						if (get_option(WPS_OPTIONS_PREFIX.'_forum_uploads')) {
							
							// Attach an image...
							if (get_option(WPS_OPTIONS_PREFIX.'_forum_uploads')) {
								include_once('server/file_upload_include.php');
								$html .= show_upload_form(
									WP_CONTENT_DIR.'/wps-content/members/'.$current_user->ID.'/forum_upload/', 
									WP_CONTENT_URL.'/wps-content/members/'.$current_user->ID.'/forum_upload/',
									'forum',
									__('Attach file', WPS_TEXT_DOMAIN),
									$post->tid
								);							
							}
							$html .= '<div id="forum_file_list" style="clear:both;">';

								if (get_option(WPS_OPTIONS_PREFIX.'_img_db') == 'on') {
									
									// get list of uploaded files from database
									$sql = "SELECT tmpid, filename FROM ".$wpdb->prefix."symposium_topics_images WHERE tid = 0 AND uid = ".$current_user->ID." ORDER BY tmpid";
									$images = $wpdb->get_results($sql);
									foreach ($images as $file) {
										$html .= '<div>';
										$html .= '<a href=""';
										$ext = explode('.', $file->filename);
										if ($ext[sizeof($ext)-1]=='gif' || $ext[sizeof($ext)-1]=='jpg' || $ext[sizeof($ext)-1]=='png' || $ext[sizeof($ext)-1]=='jpeg') {
											$html .= ' target="_blank" rel="symposium_forum_images-'.$post->tid.'"';
										} else {
											$html .= ' target="_blank"';
										}
										$html .= ' title="'.$file->filename.'">'.$file->filename.'</a> ';
										$html .= '<img id="0" title="'.$file->filename.'" class="remove_forum_post link_cursor" src="'.get_option(WPS_OPTIONS_PREFIX.'_images').'/delete.png" /> ';
										$html .= '</div>';	
									}
									
								} else {
									
									// get list of uploaded files from file system
									$targetPath = get_option(WPS_OPTIONS_PREFIX.'_img_path')."/forum/".$post->tid.'_'.$current_user->ID.'_tmp/';
									if (file_exists($targetPath)) {
										$handler = opendir($targetPath);
										while ($file = readdir($handler)) {
											if ($file != "." && $file != ".." && $file != ".DS_Store") {
												$html .= '<div>';
												$html .= '<a href="'.get_option(WPS_OPTIONS_PREFIX.'_img_url').'/forum/'.$post->tid.'_'.$current_user->ID.'_tmp/'.$file.'"';
												$ext = explode('.', $file);
												if ($ext[sizeof($ext)-1]=='gif' || $ext[sizeof($ext)-1]=='jpg' || $ext[sizeof($ext)-1]=='png' || $ext[sizeof($ext)-1]=='jpeg') {
													$html .= ' target="_blank" rel="symposium_forum_images-'.$post->tid.'"';
												} else {
													$html .= ' target="_blank"';
												}
												$html .= ' title="'.$file.'">'.$file.'</a> ';
												$html .= '<img id="'.$post->tid.'_'.$current_user->ID.'_tmp" title="'.$file.'" class="remove_forum_post link_cursor" src="'.get_option(WPS_OPTIONS_PREFIX.'_images').'/delete.png" /> ';
												$html .= '</div>';
											}
										}			
										closedir($handler);
									}	
								}
								
							$html .= '</div>';	
							
						}

					$html .= '</div>';
				
				} else {
					$html .= "<p style='margin-top:10px'>".__("This topic is closed, no replies are allowed.", WPS_TEXT_DOMAIN);
				}				

				// Add page title at the start
				if ( get_option(WPS_OPTIONS_PREFIX.'_forum_ajax') ) {
					$html = $topic_subject.' | '.html_entity_decode(get_bloginfo('name'), ENT_QUOTES).'[|]'.$html;
				}

			} else {
				if ($group_id == 0) {
					$html .= "<p>".__("You are not permitted to reply on this forum.", WPS_TEXT_DOMAIN);
					if (__wps__get_current_userlevel() == 5) $html .= '<br />'.sprintf(__('Permissions are set via the WordPress admin dashboard->%s->Options->Forum.', WPS_TEXT_DOMAIN), WPS_WL_SHORT).'<br />';	
					
					// Show login form, and redirect back here
					if (get_option(WPS_OPTIONS_PREFIX.'_forum_login') && !is_user_logged_in()) {

						$html .= ' '.__wps__show_login_link(__("<a href='%s'>Login...</a>", WPS_TEXT_DOMAIN));
					}

					$html .= "</p>";

				}
			}
			
		
		} else {
			$html = __('Sorry, this topic is no longer available.', WPS_TEXT_DOMAIN);
		}
		
		
	} else {
		// Final check if it's just not there
		$sql = "SELECT tid FROM ".$wpdb->prefix."symposium_topics WHERE tid = %d";
		if ($wpdb->get_var($wpdb->prepare($sql, $topic_id))) {
			if (is_user_logged_in()) {
				$html .= __("You do not have permission to view this topic, sorry.", WPS_TEXT_DOMAIN);
			} else {
				$html .= __wps__show_login_link(__("You do not have permission to view this topic, sorry. <a href='%s'>Log in...</a>", WPS_TEXT_DOMAIN), false);
			}
			if (__wps__get_current_userlevel() == 5) $html .= '<br /><br />'.sprintf(__('Permissions are set via the WordPress admin dashboard->%s->Options->Forum.', WPS_TEXT_DOMAIN), WPS_WL_SHORT);
		} else {
			$html = __('Sorry, this topic does not exist.', WPS_TEXT_DOMAIN);
		}
	}

	
	// Filter for profile header
	$html = apply_filters ( 'symposium_forum_topic_header_filter', $html, $topic_id );

	return $html;	
}

function __wps__insert_pagination($page, $page_count, $group_id, $pagination_url) {
	$h = '';
	$h .= '<div class="pagination_box">';
	for ($x=1; $x<=$page_count; $x++) {
		$h .= '<div class="pagination_number';
		if ($x == $page) $h .= ' pagination_number_current';
		$h .= '">';
		if (get_option(WPS_OPTIONS_PREFIX.'_permalink_structure') && $group_id == 0) {
			$page_url = $pagination_url.'/'.$x;
		} else {
			$page_url = $pagination_url.'&view='.$x;
		}
		$h .= '<a style="text-decoration: none" href="'.$page_url.'">'.$x.'</a>';
		$h .= '</div>';
	} 
	$h .= '</div>';
	$h = apply_filters ( '__wps__forum_pagination', $h );

	return $h;
}

function __wps__forum_dropdown($cat_id, $topic_id, $group_id) {
	
	$html = '';
		
	// Get list of roles for this user
	global $current_user;
	$user_roles = $current_user->roles;
	$user_role = strtolower(array_shift($user_roles));
	$user_role = str_replace('_', '', str_replace(' ', '', $user_role));
	if ($user_role == '') $user_role = 'NONE';

	$html .= '<div id="__wps__forum_dropdown" style="float:left">';
	$html .= __('Go to:', WPS_TEXT_DOMAIN).' ';
	$html .= '<select id="__wps__change_forum_category">';
	$html .= '<option value=-1>'.__('Select a category...', WPS_TEXT_DOMAIN).'</option>';
	$html .= '<option value=0>'.__('Top level', WPS_TEXT_DOMAIN).'</option>';
	$html .= __wps__forum_dropdown_get_categories(0, 0, $cat_id, $topic_id, $user_role);
	$html .= '</select></div>';
	
	return $html;
}

function __wps__forum_dropdown_get_categories($parent_id, $indent, $cat_id, $topic_id, $user_role) {

	global $wpdb;

	$html = '';
	
	$sql = "SELECT * FROM ".$wpdb->prefix."symposium_cats WHERE cat_parent = %d AND hide_main != 'on' ORDER BY listorder";
	$cats = $wpdb->get_results($wpdb->prepare($sql, $parent_id));

	foreach ($cats AS $cat) {

		// Check that permitted to category
		$cat_roles = unserialize($cat->level);
		$cat_roles = str_replace('_', '', str_replace(' ', '', $cat_roles));
	
		if (strpos(strtolower($cat_roles), 'everyone,') !== FALSE || strpos(strtolower($cat_roles), $user_role.',') !== FALSE) {			
			// if so, then add category and sub-categories
			$title = substr('----------', 0, $indent).' '.$cat->title;
			$selected = ($cat->cid == $cat_id && !$topic_id) ? "SELECTED" : "";
			$html .= sprintf('<option value=%d %s>%s</option>', $cat->cid, $selected, $title);
			$html .= __wps__forum_dropdown_get_categories($cat->cid, ($indent+1), $cat_id, $topic_id, $user_role);
		}
	
	}
	
	return stripslashes($html);
	
}

function __wps__getForum($cat_id, $limit_from=0, $group_id=0) {

	global $wpdb, $current_user;
		
	$limit_count = get_option(WPS_OPTIONS_PREFIX.'_topic_count') ? get_option(WPS_OPTIONS_PREFIX.'_topic_count') : 10; // Use even number to ensure row backgrounds continue to alternate

	$previous_login = __wps__get_meta($current_user->ID, 'previous_login');
	$forum_all = __wps__get_meta($current_user->ID, 'forum_all');

	$plugin = WP_CONTENT_URL.'/plugins/wp-symposium/';
	
	// Get forum URL worked out
	$forum_url = __wps__get_url('forum');
	if (strpos($forum_url, '?') !== FALSE) {
		$q = "&";
	} else {
		$q = "?";
	}

	$html = '';	
	$text_color = '';

	// Get group URL worked out
	$continue = true;
	if ($group_id > 0) {
		$forum_url = __wps__get_url('group');
		if (strpos($forum_url, '?') !== FALSE) {
			$q = "&gid=".$group_id."&";
		} else {
			$q = "?gid=".$group_id."&";
		}
		$group_info = $wpdb->get_row($wpdb->prepare("SELECT content_private, allow_new_topics FROM ".$wpdb->prefix . 'symposium_groups WHERE gid=%d', $group_id));
		$content_private = $group_info->content_private;
		$allow_new_topics = $group_info->allow_new_topics;
		$continue = false;
		if (__wps__member_of($group_id) == 'yes') {
			$continue = true;
		} else {
			if ($content_private != 'on') {
				$continue = true;
			}			
		}
	}

	// Get list of roles for this user
    $user_roles = $current_user->roles;
    $user_role = strtolower(array_shift($user_roles));
    $user_role = str_replace('_', '', str_replace(' ', '', $user_role));
	if ($user_role == '') $user_role = 'NONE';

	// If in a group forum check that they are a member!
	if ( $continue ) {
		
		// Post preview
		$snippet_length = get_option(WPS_OPTIONS_PREFIX.'_preview1');
		if ($snippet_length == '') { $snippet_length = '0'; }
		$snippet_length_long = get_option(WPS_OPTIONS_PREFIX.'_preview2');
		if ($snippet_length_long == '') { $snippet_length_long = '100'; }
		
		if ($limit_from == 0) {
		
			$template = get_option(WPS_OPTIONS_PREFIX.'_template_forum_header');
			$template = str_replace("[]", "", stripslashes($template));
	
			// Breadcrumbs	
			$allow_new = 'on';
			if ($wpdb->get_var($wpdb->prepare("SELECT hide_breadcrumbs FROM ".$wpdb->prefix."symposium_cats WHERE cid = %d", $cat_id)) == 'on') {
				$allow_new = $wpdb->get_var($wpdb->prepare("SELECT allow_new FROM ".$wpdb->prefix."symposium_cats WHERE cid = %d", $cat_id));
			} else {
				$breadcrumbs = '<div id="forum_breadcrumbs" class="breadcrumbs label">';

				if ($cat_id > 0) {
			
					if (!get_option(WPS_OPTIONS_PREFIX.'_wps_lite')) {
			
						$this_level = $wpdb->get_row($wpdb->prepare("SELECT cid, title, allow_new, cat_parent, stub FROM ".$wpdb->prefix."symposium_cats WHERE cid = %d", $cat_id));
						$allow_new = $this_level->allow_new;
						if ($this_level->cat_parent == 0) {
							if (get_option(WPS_OPTIONS_PREFIX.'_forum_ajax') == 'on') {
								$breadcrumbs .= '<a href="#cid=0" class="category_title" title="0">'.__('Forum Home', WPS_TEXT_DOMAIN)."</a> &rarr; ";
								$breadcrumbs .= '<a href="#cid='.$this_level->cid.'" class="category_title" title="'.$this_level->cid.'">'.stripslashes(trim($this_level->title)).'</a>';
							} else {
								if (get_option(WPS_OPTIONS_PREFIX.'_permalink_structure') && $group_id == 0) {
									$breadcrumbs .= '<a href="'.$forum_url.'" title="0">'.__('Forum Home', WPS_TEXT_DOMAIN)."</a> &rarr; ";
									$breadcrumbs .= '<a href="'.$forum_url.'/'.$this_level->stub.'" title="'.$this_level->cid.'">'.stripslashes(trim($this_level->title)).'</a>';
								} else {
									$breadcrumbs .= '<a href="'.$forum_url.$q.'cid=0" title="0">'.__('Forum Home', WPS_TEXT_DOMAIN)."</a> &rarr; ";
									$breadcrumbs .= '<a href="'.$forum_url.$q."cid=".$this_level->cid.'" title="'.$this_level->cid.'">'.stripslashes(trim($this_level->title)).'</a>';
								}
							}
						} else {
			
							$parent_level = $wpdb->get_row($wpdb->prepare("SELECT cid, title, cat_parent, stub FROM ".$wpdb->prefix."symposium_cats WHERE cid = %d", $this_level->cat_parent));
			
							if ($parent_level->cat_parent == 0) {
								if (get_option(WPS_OPTIONS_PREFIX.'_forum_ajax') == 'on') {
									$breadcrumbs .= '<a href="#cid=0" class="category_title" title="0">'.__('Forum Home', WPS_TEXT_DOMAIN)."</a> &rarr; ";
								} else {
									if (get_option(WPS_OPTIONS_PREFIX.'_permalink_structure') && $group_id == 0) {
										$breadcrumbs .= '<a href="'.$forum_url.'" title="0">'.__('Forum Home', WPS_TEXT_DOMAIN)."</a> &rarr; ";
									} else {
										$breadcrumbs .= '<a href="'.$forum_url.$q.'cid=0" title="0">'.__('Forum Home', WPS_TEXT_DOMAIN)."</a> &rarr; ";
									}
								}
							} else {
								$parent_level_2 = $wpdb->get_row($wpdb->prepare("SELECT cid, title, cat_parent, stub FROM ".$wpdb->prefix."symposium_cats WHERE cid = %d", $parent_level->cat_parent));
								if (get_option(WPS_OPTIONS_PREFIX.'_forum_ajax') == 'on') {
									$breadcrumbs .= '<a href="#cid=0" class="category_title" title="0">'.__('Forum Home', WPS_TEXT_DOMAIN)."</a> &rarr; " ;
									$breadcrumbs .= '<a href="#cid='.$parent_level_2->cid.'" class="category_title" title="'.$parent_level_2->cid.'">'.stripslashes($parent_level_2->title)."</a> &rarr; ";
								} else {
									if (get_option(WPS_OPTIONS_PREFIX.'_permalink_structure') && $group_id == 0) {
										$breadcrumbs .= '<a href="'.$forum_url.'" title="0">'.__('Forum Home', WPS_TEXT_DOMAIN)."</span></a> &rarr; " ;
										$breadcrumbs .= '<a href="'.$forum_url.'/'.$parent_level_2->stub.'"  title="'.$parent_level_2->cid.'">'.stripslashes($parent_level_2->title)."</a> &rarr; ";
									} else {
										$breadcrumbs .= '<a href="'.$forum_url.$q.'cid=0" title="0">'.__('Forum Home', WPS_TEXT_DOMAIN)."</span></a> &rarr; " ;
										$breadcrumbs .= '<a href="'.$forum_url.$q."cid=".$parent_level_2->cid.'"  title="'.$parent_level_2->cid.'">'.stripslashes($parent_level_2->title)."</a> &rarr; ";
									}
								}
							}
							if (get_option(WPS_OPTIONS_PREFIX.'_forum_ajax') == 'on') {
								$breadcrumbs .= '<a href="#cid='.$parent_level->cid.'" class="category_title" title="'.$parent_level->cid.'">'.$parent_level->title."</a> &rarr; " ;
								$breadcrumbs .= '<a href="#cid='.$this_level->cid.'" class="category_title" title="'.$this_level->cid.'">'.stripslashes($this_level->title)."</a>" ;
							} else {
								if (get_option(WPS_OPTIONS_PREFIX.'_permalink_structure') && $group_id == 0) {
									$breadcrumbs .= '<a href="'.$forum_url.'/'.$parent_level->stub.'" title="'.$parent_level->cid.'">'.$parent_level->title."</a> &rarr; " ;
									$breadcrumbs .= '<a href="'.$forum_url.'/'.$this_level->stub.'" title="'.$this_level->cid.'">'.stripslashes($this_level->title)."</a>" ;
								} else {
									$breadcrumbs .= '<a href="'.$forum_url.$q."cid=".$parent_level->cid.'" title="'.$parent_level->cid.'">'.$parent_level->title."</a> &rarr; " ;
									$breadcrumbs .= '<a href="'.$forum_url.$q."cid=".$this_level->cid.'" title="'.$this_level->cid.'">'.stripslashes($this_level->title)."</a>" ;
								}
							}
						}

					} else {
						// Lite mode
						$this_level = $wpdb->get_row($wpdb->prepare("SELECT allow_new, cat_parent FROM ".$wpdb->prefix."symposium_cats WHERE cid = %d", $cat_id));
						$allow_new = $this_level->allow_new;
						$breadcrumbs .= '<a href="#cid=0" class="category_title" title="0">'.__('Forum Home', WPS_TEXT_DOMAIN)."</a>";
						if ($this_level->cat_parent > 0) {
							$breadcrumbs .= ' &rarr; <a href="'.$forum_url.$q."cid=".$this_level->cat_parent.'" title="'.$this_level->cat_parent.'">'.__('Up a level', WPS_TEXT_DOMAIN)."</a>" ;
						}
					}
				
				}
					
				$breadcrumbs .= '</div>';
			}
						
			// If a group forum, check that is a member - and that new topics can be created
			if ($group_id > 0) {
				// Is user a member of the group?
				$sql = "SELECT COUNT(*) FROM ".$wpdb->prefix."symposium_group_members WHERE group_id=%d AND valid='on' AND member_id=%d";
				$member_count = $wpdb->get_var($wpdb->prepare($sql, $group_id, $current_user->ID));
				if ($member_count == 0) { 
					// Non members can never create topics
					$allow_new = ''; 
				} else {
					$sql = "SELECT member_id FROM ".$wpdb->prefix."symposium_group_members WHERE group_id=%d AND member_id=%d and admin='on'";
					$admin_check = $wpdb->get_var($wpdb->prepare($sql, $group_id, $current_user->ID));
					if ($admin_check || __wps__get_current_userlevel() == 5) {
						// Group and site admin can always create new topics
						$allow_new = 'on';
					} else {
						// Get setting from Group settings
						$allow_new = $allow_new_topics;
					}
				}	
			}
	
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
			if ($group_id > 0) {
				$sql = "SELECT COUNT(*) FROM ".$wpdb->prefix."symposium_group_members WHERE group_id=%d AND valid='on' AND member_id=%d";
				$member_count = $wpdb->get_var($wpdb->prepare($sql, $group_id, $current_user->ID));
				if ($member_count == 0) { $can_edit = false; } else { $can_edit = true; }
			}	
			
			// Advertising code
			if (get_option(WPS_OPTIONS_PREFIX.'_ad_forum_categories')) {
				$top_advert = '<div id="ad_forum_categories">'.stripslashes(get_option(WPS_OPTIONS_PREFIX.'_ad_forum_categories')).'</div>';
			}

			// Dropdown list for quick link			
			if (get_option(WPS_OPTIONS_PREFIX.'_show_dropdown') && !$group_id)
				$breadcrumbs .= __wps__forum_dropdown($cat_id, 0, $group_id);

			// New Topic Button & Form	
			$new_topic_form = "";
			if (is_user_logged_in()) {

				if ( ($can_edit) && (can_manage_forum() || $allow_new == 'on') ) {
	
					$new_topic_button = '<input type="submit" class="__wps__button floatright" id="new-topic-button" value="'.__("New Topic", WPS_TEXT_DOMAIN).'" />';
	
					$new_topic_form .= '<div name="new-topic" id="new-topic" style="display:none;">';
						$new_topic_form .= '<input type="hidden" id="cid" value="'.$cat_id.'">';

						$defaultcat = $wpdb->get_var($wpdb->prepare("SELECT cid FROM ".$wpdb->prefix."symposium_cats WHERE defaultcat = %s", 'on'));

						if ($group_id == 0) {
	
							$new_topic_form .= '<div class="new-topic-category label">'.__("Select a Category", WPS_TEXT_DOMAIN).'<br />';
							if (can_manage_forum()) {
								$categories = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix.'symposium_cats ORDER BY title');			
							} else {
								$categories = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix.'symposium_cats WHERE allow_new = "on" ORDER BY title');			
							}
							if ($categories) {
								$new_topic_form .= '<select name="new_topic_category" id="new_topic_category">';
					
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
							

						} else {
							// No categories for groups
							$new_topic_form .= '<input name="new_topic_category" type="hidden" value="0">';
						}
						
						$new_topic_form .= '<div style="clear:both"></div>';
						$new_topic_form .= '<div id="new-topic-subject-label" class="new-topic-subject label">'.__("Topic Subject", WPS_TEXT_DOMAIN).'</div>';
						$new_topic_form .= '<div style="clear:both"></div>';
						$new_topic_form .= '<input class="new-topic-subject-input" type="text" id="new_topic_subject" value="">';
						$new_topic_form .= '<div style="clear:both"></div>';
						$new_topic_form .= '<div class="new-topic-subject label">'.__("First Post in Topic", WPS_TEXT_DOMAIN).'</div>';
						if (get_option(WPS_OPTIONS_PREFIX.'_elastic') == 'on' && get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg') != 'on') { $elastic = ' elastic'; } else { $elastic = ''; }

						$new_topic_form .= '<div id="__wps__new-topic-subject-text_parent" style="width:'.get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_width').'; height:'.get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_height').';">';
						$new_topic_form .= __wps__bbcode_toolbar('new_topic_text');
						$new_topic_form .= '<textarea class="new-topic-subject-text'.$elastic.'" id="new_topic_text" style="width:100%;"></textarea>';
						$new_topic_form .= '</div>';
						if (get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg') == 'on') { $new_topic_form .= '<br />'; }
							
						// Upload
						if (get_option(WPS_OPTIONS_PREFIX.'_forum_uploads')) {
							include_once('server/file_upload_include.php');
							$new_topic_form .= show_upload_form(
								WP_CONTENT_DIR.'/wps-content/members/'.$current_user->ID.'/forum_upload/', 
								WP_CONTENT_URL.'/wps-content/members/'.$current_user->ID.'/forum_upload/',
								'forum',
								__('Attach file', WPS_TEXT_DOMAIN)
							);							
							$new_topic_form .= '<div id="forum_file_list" style="clear:both;"></div>';
						}
						
						$new_topic_form .= '<div>';
						if (get_option(WPS_OPTIONS_PREFIX.'_suppress_forum_notify') != "on") {
							if ($forum_all != 'on') {
								$new_topic_form .= '<input style="margin: 0;" type="checkbox" id="new_topic_subscribe"> '.__("Tell me when I get any replies", WPS_TEXT_DOMAIN).'<br />';
							}
						}
						if (get_option(WPS_OPTIONS_PREFIX.'_use_answers') == 'on') {
							$new_topic_form .= '<input style="margin: 0 0 10px 0;" type="checkbox" id="info_only"> '.__('This topic is for information only, no answer will be selected.', WPS_TEXT_DOMAIN);
						}
						$new_topic_form .= '</div>';
	
						$new_topic_form .= '<input id="symposium_new_post" type="submit" class="__wps__button" style="float: left" value="'.__("Post", WPS_TEXT_DOMAIN).'" />';
						$new_topic_form .= '<input id="cancel_post" type="submit" class="__wps__button clear" onClick="javascript:void(0)" value="'.__("Cancel", WPS_TEXT_DOMAIN).'" />';
	
	
						$new_topic_form .= '</div>';
	
					$new_topic_form .= '</div>';
	
				} else {
	
					if ($group_id == 0 && $allow_new == 'on') {
						$new_topic_form = "<p>".__("You are not permitted to start a new topic.", WPS_TEXT_DOMAIN);	
						if (__wps__get_current_userlevel() == 5) $new_topic_form .= sprintf(__('<br />Permissions are set via the WordPress admin dashboard->%s->Options->Forum.', WPS_TEXT_DOMAIN), WPS_WL);	
						$new_topic_forum .= "</p>";
					}
					if ($group_id > 0 && $allow_new != 'on' && $member_count > 0) {
						$new_topic_form = "<p>".__("New topics are disabled on this forum.", WPS_TEXT_DOMAIN)."</p>";							
					}
					$new_topic_button = '';
					
				}
				
	
			} else {
	
				$new_topic_button = '';
	
				if (get_option(WPS_OPTIONS_PREFIX.'_forum_login') == "on") {
					$new_topic_form .= "<p style='text-align: right;'>".__("Until you login, you can only view the forum.", WPS_TEXT_DOMAIN);
					$new_topic_form .= " <a href=".wp_login_url( get_permalink() )." class='simplemodal-login' title='".__("Login", WPS_TEXT_DOMAIN)."'>".__("Login", WPS_TEXT_DOMAIN).".</a></p>";
				}
	
			}
	
			// Options
			$digest = "";
			$subscribe = "";

			if (is_user_logged_in()) {
		
				$send_summary = get_option(WPS_OPTIONS_PREFIX.'_send_summary');
				if ($send_summary == "on" && $cat_id == 0) {
					$forum_digest = __wps__get_meta($current_user->ID, 'forum_digest');
					$digest = "<div class='__wps__subscribe_option label'>";
					$digest .= "<input type='checkbox' id='symposium_digest' name='symposium_digest'";
					if ($forum_digest == 'on') { $digest .= ' checked'; } 
					$digest .= "> ".__("Receive digests via email", WPS_TEXT_DOMAIN);
					$digest .= "</div>";
				}
				if (get_option(WPS_OPTIONS_PREFIX.'_suppress_forum_notify') != "on") {
					if ($forum_all != 'on') {
						if ($group_id == 0) {
							if ($cat_id > 0) {
								$subscribed_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM ".$wpdb->prefix."symposium_subs WHERE tid = 0 AND cid = %d AND uid = %d", $cat_id, $current_user->ID));
								$subscribe = "<div class='__wps__subscribe_option label'>";
								$subscribe .= "<input type='checkbox' title='".$cat_id."' id='symposium_subscribe' name='symposium_subscribe'";
								if ($subscribed_count > 0) { $subscribe .= ' checked'; } 
								$subscribe .= "> ".__("Tell me when there are new topics posted", WPS_TEXT_DOMAIN);
								$subscribe .= "</div>";
							}
						} else {
							$subscribed_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM ".$wpdb->prefix."symposium_subs WHERE tid = 0 AND cid = %d AND uid = %d", (10000+$group_id), $current_user->ID));
							$subscribe = "<div class='__wps__subscribe_option label'>";
							$subscribe .= "<input type='checkbox' title='".(10000+$group_id)."' id='symposium_subscribe' name='symposium_subscribe'";
							if ($subscribed_count > 0) { $subscribe .= ' checked'; } 
							$subscribe .= "> ".__("Tell me when there are new topics posted", WPS_TEXT_DOMAIN);
							$subscribe .= "</div>";
						}
					}
				}
	
			}	
	
			// Options above forum table
			$forum_options = "<div id='forum_options'>";
	
				$forum_options .= "<a id='show_search' class='label' href='javascript:void(0)'>".__("Search", WPS_TEXT_DOMAIN)."</a>";
				$forum_options .= "&nbsp;&nbsp;&nbsp;&nbsp;<a id='show_all_activity' href='javascript:void(0)'>".__("Activity", WPS_TEXT_DOMAIN)."</a>";
				$forum_options .= "&nbsp;&nbsp;&nbsp;&nbsp;<a id='show_threads_activity' class='label' href='javascript:void(0)'>".__("Latest Topics", WPS_TEXT_DOMAIN)."</a>";
	
				if (is_user_logged_in()) {
					$forum_options .= "&nbsp;&nbsp;&nbsp;&nbsp;<a id='show_activity' class='label' href='javascript:void(0)'>".__("My Activity", WPS_TEXT_DOMAIN)."</a>";
					$forum_options .= "&nbsp;&nbsp;&nbsp;&nbsp;<a id='show_favs' class='label' href='javascript:void(0)'>".__("Favorites", WPS_TEXT_DOMAIN)."</a>";
				}
	
			$forum_options .= "</div>";

			// Sharing icons
			if (get_option(WPS_OPTIONS_PREFIX.'_sharing') != '' && $cat_id > 0) {
				$sharing = __wps__show_sharing_icons($cat_id, 0, get_option(WPS_OPTIONS_PREFIX.'_sharing'), $group_id);
			} else {
				$sharing = "";
			}
	
			// Replace template tokens and add to output
			$template = str_replace('[new_topic_form]', $new_topic_form, $template);
			if (!isset($top_advert)) $top_advert = '';
			$template = str_replace('[top_advert]', $top_advert, $template);
			$template = str_replace('[new_topic_button]', $new_topic_button, $template);
			$template = str_replace('[breadcrumbs]', $breadcrumbs, $template);
			$template = str_replace('[digest]', $digest, $template);
			$template = str_replace('[subscribe]', $subscribe, $template);
			$template = str_replace('[forum_options]', $forum_options, $template);
			$template = str_replace('[sharing]', $sharing, $template);
	
			$html .= '<div id="__wps__forum_cagtegory_header">'.$template.'</div>';
			
			if ($group_id == 0) {
	
				// Show child categories in this category (and not in a group) ++++++++++++++++++++++++++++++++++++++++++++++++++
				$sql = $wpdb->prepare("SELECT * FROM ".$wpdb->prefix."symposium_cats WHERE cat_parent = %d AND hide_main != 'on' ORDER BY listorder", $cat_id);
				$categories = $wpdb->get_results($sql);

				// Row template		
				if ( $group_id > 0 ) {
					$template = get_option(WPS_OPTIONS_PREFIX.'_template_group_forum_category');
				} else {
					$template = get_option(WPS_OPTIONS_PREFIX.'_template_forum_category');
				}
				$template = str_replace("[]", "", stripslashes($template));

				if ($categories) {
					
					// Start of table
					$html .= '<div id="__wps__table" class="__wps__forum_table">';
	
						$num_cats = $wpdb->num_rows;
						$cnt = 0;
						foreach($categories as $category) {

							$forum_row = '';

							if ($cnt == 3 && get_option(WPS_OPTIONS_PREFIX.'_ad_forum_in_categories')) {
								// Advertising code +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
								$cnt++;
								if ($cnt&1) {
									$forum_row .= '<div class="row ';
									if ($cnt == $num_cats) { $forum_row .= ' round_bottom_left round_bottom_right'; }
									$forum_row .= '">';
								} else {
									$forum_row .= '<div class="row_odd ';
									if ($cnt == $num_cats) { $forum_row .= ' round_bottom_left round_bottom_right'; }
									$forum_row .= '">';
								}
								$forum_row .= "<div id='ad_forum_in_categories'>";
									$forum_row .= stripslashes(get_option(WPS_OPTIONS_PREFIX.'_ad_forum_in_categories'));	
								$forum_row .= "</div>";
								$forum_row .= '</div>';
							}

 							// Get list of permitted roles from forum_cat and check allowed
							$cat_roles = unserialize($category->level);
							$cat_roles = strtolower(str_replace('_', '', str_replace(' ', '', $cat_roles)));
							if (WPS_DEBUG) $forum_row .= 'Checking forum category role '.$user_role.' against '.$cat_roles.'<br />'.strpos($cat_roles, $user_role.',').'<br />';

							// Check if high enough rank (and applicable)
							if ( substr(get_option(WPS_OPTIONS_PREFIX.'_forum_ranks'), 0, 2) == 'on' ) {
								$min_rank = $category->min_rank;
								$my_count = __wps__forum_rank_points($current_user->ID);
								if (WPS_DEBUG) $forum_row .= "Minimum rank = ".$min_rank." (my score = ".$my_count.")";
								if ($my_count >= $min_rank) {
									$rank_ok = true;
								} else {
									$rank_ok = false;
								}
							} else {
								$rank_ok = true;
							}

							if ( ($rank_ok) && (strpos(strtolower($cat_roles), 'everyone,') !== FALSE || strpos($cat_roles, $user_role.',') !== FALSE) ) {

								$cnt++;
								if ($cnt&1) {
									$forum_row .= '<div class="row ';
									if ($cnt == $num_cats) { $forum_row .= ' round_bottom_left round_bottom_right'; }
									$forum_row .= '">';
								} else {
									$forum_row .= '<div class="row_odd ';
									if ($cnt == $num_cats) { $forum_row .= ' round_bottom_left round_bottom_right'; }
									$forum_row .= '">';
								}
							
									// Start row template
									$row_template = $template;
						
									// Last Topic/Reply
									$last_topic = $wpdb->get_row($wpdb->prepare("
										SELECT tid, stub, topic_subject, topic_approved, topic_post, topic_date, topic_owner, topic_sticky, topic_parent, display_name, topic_category 
										FROM ".$wpdb->prefix."symposium_topics t 
										INNER JOIN ".$wpdb->base_prefix."users u ON u.ID = t.topic_owner
										WHERE (topic_approved = 'on' OR topic_owner = %d) AND topic_parent = 0 AND topic_category = %d ORDER BY topic_date DESC LIMIT 0,1", $current_user->ID, $category->cid)); 
		
									if ($last_topic) {
										
											if (!get_option(WPS_OPTIONS_PREFIX.'_wps_lite')) {
												$reply = $wpdb->get_row($wpdb->prepare("
													SELECT t.*, u.display_name
													FROM ".$wpdb->prefix."symposium_topics t 
													LEFT JOIN ".$wpdb->base_prefix."users u ON t.topic_owner = u.ID
													WHERE (topic_approved = 'on' OR topic_owner = %d) 
													  AND topic_parent = %d 
													ORDER BY topic_date DESC LIMIT 0,1", $current_user->ID, $last_topic->tid)); 
											} else {
												$reply = false;
											}
		
											// Avatar
											if ($reply) {
												$topic_owner = $reply->topic_owner;
											} else {
												$topic_owner = $last_topic->topic_owner;									
											}
											if (strpos($row_template, '[avatar') !== FALSE) {
	
												if (strpos($row_template, '[avatar]')) {
													$row_template = str_replace("[avatar]", get_avatar($topic_owner, 32), $row_template);
												} else {
													$x = strpos($row_template, '[avatar');
													$avatar = substr($row_template, 0, $x);
													$avatar2 = substr($row_template, $x+8, 2);
													$avatar3 = substr($row_template, $x+11, strlen($row_template)-$x-11);
													
													$row_template = $avatar . get_avatar($topic_owner, $avatar2) . $avatar3;
												}
											}
											
											if ($reply) {
												$row_template = str_replace("[replied]", __wps__profile_link($reply->topic_owner)." ".__("replied to", WPS_TEXT_DOMAIN)." ", $row_template);	
												$subject = __wps__bbcode_remove($last_topic->topic_subject);
												if (get_option(WPS_OPTIONS_PREFIX.'_forum_ajax') == 'on') {
													$subject = '<a title="'.$last_topic->tid.'" class="topic_subject backto row_link_topic" href="#cid='.$category->cid.',tid='.$last_topic->tid.'">'.stripslashes($subject).'</a> ';
												} else {
													if (get_option(WPS_OPTIONS_PREFIX.'_permalink_structure') && $group_id == 0) {
														$perma_cat = __wps__get_forum_category_part_url($reply->topic_category);
														$subject = '<a class="backto row_link_topic" href="'.$forum_url.'/'.$perma_cat.$last_topic->stub.'">'.stripslashes($subject).'</a> ';
													} else {
														$subject = '<a class="backto row_link_topic" href="'.$forum_url.$q."cid=".$last_topic->topic_category."&show=".$last_topic->tid.'">'.stripslashes($subject).'</a> ';
													}
												}
												if ($reply->topic_approved != 'on') { $subject .= "<em>[".__("pending approval", WPS_TEXT_DOMAIN)."]</em> "; }
												$subject_text = strip_tags(stripslashes($reply->topic_post));
												$subject_text = __wps__bbcode_remove($subject_text);
												if ( strlen($subject_text) > $snippet_length_long ) { $subject_text = substr($subject_text, 0, $snippet_length_long)."..."; }
												$row_template = str_replace("[subject_text]", $subject_text, $row_template);	
												$row_template = str_replace("[subject]", $subject, $row_template);	
												$row_template = str_replace("[ago]", __wps__time_ago($reply->topic_date), $row_template);	
											} else {
												$row_template = str_replace("[replied]", __wps__profile_link($last_topic->topic_owner)." ".__("started", WPS_TEXT_DOMAIN)." ", $row_template);	
												$subject = __wps__bbcode_remove($last_topic->topic_subject);
												if (get_option(WPS_OPTIONS_PREFIX.'_forum_ajax') == 'on') {
													$subject = '<a title="'.$last_topic->tid.'" class="topic_subject backto row_link_topic" href="#cid='.$category->cid.',tid='.$last_topic->tid.'">'.stripslashes($subject).'</a> ';
												} else {
													if (get_option(WPS_OPTIONS_PREFIX.'_permalink_structure') && $group_id == 0) {
														$perma_cat = __wps__get_forum_category_part_url($last_topic->topic_category);
														$subject = '<a class="backto row_link_topic" href="'.$forum_url.'/'.$perma_cat.$last_topic->stub.'">'.stripslashes($subject).'</a> ';
													} else {
														$subject = '<a class="backto row_link_topic" href="'.$forum_url.$q."cid=".$last_topic->topic_category."&show=".$last_topic->tid.'">'.stripslashes($subject).'</a> ';
													}
												}
												$subject_text = strip_tags(stripslashes($last_topic->topic_post));
												$subject_text = __wps__bbcode_remove($subject_text);												
												if ( strlen($subject_text) > $snippet_length_long ) { $subject_text = substr($subject_text, 0, $snippet_length_long)."..."; }
												$row_template = str_replace("[subject_text]", $subject_text, $row_template);	
												$row_template = str_replace("[subject]", $subject, $row_template);	
												$row_template = str_replace("[ago]", __wps__time_ago($last_topic->topic_date), $row_template);	
											}
		
									} else {
		
										if (strpos($row_template, '[avatar') !== FALSE) {
											if (strpos($row_template, '[avatar]')) {
												$row_template = str_replace("[avatar]", get_avatar($reply->topic_owner, 32), $row_template);						
											} else {
												$x = strpos($row_template, '[avatar');
												$avatar = substr($row_template, 0, $x);
												$avatar2 = substr($row_template, $x+8, 2);
												$avatar3 = substr($row_template, $x+11, strlen($row_template)-$x-11);
												$row_template = $avatar . $avatar3;									
											}
										}
										$row_template = str_replace("[subject_text]", "", $row_template);	
										$row_template = str_replace("[replied]", "", $row_template);	
										$row_template = str_replace("[subject]", "", $row_template);	
										$row_template = str_replace("[ago]", "", $row_template);
									}

									// Topic Count
									if (!get_option(WPS_OPTIONS_PREFIX.'_wps_lite')) {
										$topic_count = __wps__get_topic_count($category->cid);
			
										if ($topic_count > 0) {
											$topic_count_html = "<div class='post_count' style='color:".$text_color.";'>".$topic_count."</div>";
											$topic_count_html .= "<div style='color:".$text_color.";' class='post_count_label'>";
											if ($topic_count != 1) {
												$topic_count_html .= __("TOPICS", WPS_TEXT_DOMAIN);
											} else {
												$topic_count_html .= __("TOPIC", WPS_TEXT_DOMAIN);
											}
											$topic_count_html .= "</div>";
											$row_template = str_replace("[topic_count]", $topic_count_html, $row_template);	
										} else {
											$topic_count_html = "<div class='post_count' style='color:".$text_color.";'>0</div>";
											$topic_count_html .= "<div style='color:".$text_color.";' class='post_count_label'>";
											$topic_count_html .= __("TOPICS", WPS_TEXT_DOMAIN);
											$topic_count_html .= "</div>";	
										}
										$row_template = str_replace("[topic_count]", $topic_count_html, $row_template);	
									} else {
										$row_template = str_replace("[topic_count]", "", $row_template);	
									}

						
									// Replies
									if (get_option(WPS_OPTIONS_PREFIX.'_use_styles')) {
										$text_color = get_option(WPS_OPTIONS_PREFIX.'_text_color');
									} else {
										$text_color = '';
									}
									if (!get_option(WPS_OPTIONS_PREFIX.'_wps_lite')) {
										$post_count = 0;
		
										$sql = "SELECT COUNT(t.tid)
												FROM ".$wpdb->prefix."symposium_topics t 
												WHERE (t.topic_approved = 'on' OR t.topic_owner = %d) 
												  AND t.topic_category = %d
												  AND t.topic_parent > 0";
		
										$post_count = $wpdb->get_var($wpdb->prepare($sql, $current_user->ID, $category->cid));
										
										if ($post_count > 0) { 
											$post_count_html = "<div class='post_count' style='color:".$text_color.";'>".$post_count."</div>";
												$post_count_html .= "<div style='color:".$text_color.";' class='post_count_label'>";
												if ($post_count > 1) {
													$post_count_html .= __("REPLIES", WPS_TEXT_DOMAIN);
												} else {
													$post_count_html .= __("REPLY", WPS_TEXT_DOMAIN);
												}
												$post_count_html .= "</div>";
												$row_template = str_replace("[post_count]", $post_count_html, $row_template);	
										} else {
											$post_count_html = "<div class='post_count' style='color:".$text_color.";'>0</div>";
											$post_count_html .= "<div style='color:".$text_color.";' class='post_count_label'>";
											$post_count_html .= __("REPLIES", WPS_TEXT_DOMAIN);
											$post_count_html .= "</div>";
											$row_template = str_replace("[post_count]", $post_count_html, $row_template);	
										}
									} else {
										$row_template = str_replace("[post_count]", "", $row_template);	
									}

									// Check for new topics or replies in this category
									$category_title_html = "";
									$recursive_new = false;

									if (is_user_logged_in()) {
										$sql = "SELECT COUNT(*) FROM ".$wpdb->prefix."symposium_topics WHERE topic_category = %d AND topic_started >= %s AND topic_owner != %d";
										$new_topics = $wpdb->get_var($wpdb->prepare($sql, $category->cid, $previous_login, $current_user->ID));
									
										if ($new_topics && $new_topics > 0) {
											$recursive_new = true;
										}
									
										$sql = "SELECT COUNT(*) FROM ".$wpdb->prefix."symposium_topics t 
											LEFT JOIN ".$wpdb->prefix."symposium_topics p ON t.topic_parent = p.tid 
											WHERE t.topic_started >= %s
											  AND t.topic_owner != %d 
											  AND p.topic_category = %d";
										$new_replies = $wpdb->get_var($wpdb->prepare($sql, $previous_login, $current_user->ID, $category->cid));

										if ($new_replies && $new_replies > 0) {
											$recursive_new = true;
										} 
		
									}
									
									// Category title
									if (get_option(WPS_OPTIONS_PREFIX.'_forum_ajax') == 'on') {
										$category_title_html .= '<a class="category_title backto row_link" href="#cid='.$category->cid.'" title='.$category->cid.'>'.stripslashes($category->title).'</a>';
									} else {
										if (get_option(WPS_OPTIONS_PREFIX.'_permalink_structure') && $group_id == 0) {
											$category_title_html .= '<a class="backto row_link" href="'.$forum_url.'/'.$category->stub.'">'.stripslashes($category->title).'</a> ';
										} else {
											$category_title_html .= '<a class="backto row_link" href="'.$forum_url.$q."cid=".$category->cid.'">'.stripslashes($category->title).'</a> ';
										}
									}
									if ($recursive_new && get_option(WPS_OPTIONS_PREFIX.'_forum_stars'))
											$category_title_html .= "<img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/new.gif' alt='New!' /> ";

									$subscribed = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM ".$wpdb->prefix."symposium_subs WHERE cid = %d AND uid = %d", $category->cid, $current_user->ID));
									if ($subscribed > 0 && $forum_all != 'on') { $category_title_html .= ' <img src="'.get_option(WPS_OPTIONS_PREFIX.'_images').'/orange-tick.gif" alt="'.__('Subscribed', WPS_TEXT_DOMAIN).'" />'; } 
									$row_template = str_replace("[category_title]", $category_title_html, $row_template);	
									
									// Category description
									$category_desc_html = stripslashes($category->cat_desc);
									$row_template = str_replace("[category_desc]", $category_desc_html, $row_template);	
		
									// Add row template to HTML
									$forum_row .= $row_template;

									// Show child categories according to forum settings
									if (get_option(WPS_OPTIONS_PREFIX.'_alt_subs') == 'on') {
										$forum_row .= "<div>";
											// Get child categories
											$sql = $wpdb->prepare("SELECT * FROM ".$wpdb->prefix."symposium_cats WHERE cat_parent = %d AND hide_main != 'on' ORDER BY listorder", $category->cid);
											$categories = $wpdb->get_results($sql);
											if ($categories):
												$forum_row .= '<ul class="subcat_forums" style="clear: both">';
												foreach ($categories as $subcat):
													$forum_row .= '<li>';
													if (get_option(WPS_OPTIONS_PREFIX.'_forum_ajax') == 'on') {
														$forum_row .= '<a class="" href="#cid='.$subcat->cid.'" title='.$subcat->cid.'>'.stripslashes($subcat->title).'</a>';
													} else {
														if (get_option(WPS_OPTIONS_PREFIX.'_permalink_structure') && $group_id == 0) {
															$forum_row .= '<a class="" href="'.$forum_url.'/'.$subcat->stub.'">'.stripslashes($subcat->title).'</a> ';
														} else {
															$forum_row .= '<a class="" href="'.$forum_url.$q."cid=".$subcat->cid.'">'.stripslashes($subcat->title).'</a> ';
														}
													}
													$forum_row .= '</li>';
												endforeach;
												$forum_row .= '</ul>';
											endif;
										$forum_row .= "</div>";
									}
								
									// Separator
									$forum_row .= "<div class='sep'></div>";											
		
		
								$forum_row .= "</div>"; // Row in the table

								$forum_row .= '<div class="__wps__table_row_sep"></div>';
								
							}

							$forum_row = apply_filters ( '__wps__forum_category_filter', $forum_row, $category->cid );

							$html .= $forum_row;

						}
	
					$html .= '</div>';
					
					$html .= '<div id="__wps__table_sep"></div>';
			
				}
			}
		}
	
		// Show topics in this category ++++++++++++++++++++++++++++++++++++++++++++++++++
		if (!get_option(WPS_OPTIONS_PREFIX.'_wps_lite')) {
			$sql = "SELECT tid, stub, topic_subject, topic_approved, topic_post, topic_owner, topic_category, topic_date, display_name, topic_sticky, allow_replies, topic_started, 
				(SELECT COUNT(tid) FROM ".$wpdb->prefix."symposium_topics s WHERE s.topic_parent = t.tid AND s.topic_answer = 'on') AS answers, for_info
				FROM ".$wpdb->prefix."symposium_topics t INNER JOIN ".$wpdb->base_prefix."users u ON t.topic_owner = u.ID 
				WHERE (topic_approved = 'on' OR topic_owner = %d) AND topic_category = %d AND topic_parent = 0 AND topic_group = %d ORDER BY topic_sticky DESC, topic_date DESC 
				LIMIT %d, %d";
			$query = $wpdb->get_results($wpdb->prepare($sql, $current_user->ID, $cat_id, $group_id, $limit_from, $limit_count)); 
		} else {
			$sql = "SELECT tid, stub, topic_subject, topic_approved, topic_post, topic_owner, topic_category, topic_date, display_name, topic_sticky, allow_replies, topic_started,
				0 AS answers, for_info
				FROM ".$wpdb->prefix."symposium_topics t INNER JOIN ".$wpdb->base_prefix."users u ON t.topic_owner = u.ID 
				WHERE (topic_approved = 'on' OR topic_owner = %d) AND topic_category = %d AND topic_parent = 0 AND topic_group = %d ORDER BY topic_sticky DESC, topic_date DESC 
				LIMIT %d, %d";
			$query = $wpdb->get_results($wpdb->prepare($sql, $current_user->ID, $cat_id, $group_id, $limit_from, $limit_count)); 
		}
	
		$num_topics = $wpdb->num_rows;
	
		// Row template		
		if ( $group_id > 0 ) {
			$template = get_option(WPS_OPTIONS_PREFIX.'_template_group_forum_topic');
		} else {
			$template = get_option(WPS_OPTIONS_PREFIX.'_template_forum_topic');
		}
		$template = str_replace("[]", "", stripslashes($template));
			
		// Favourites
		$favs = __wps__get_meta($current_user->ID, 'forum_favs');
	
		$cnt = 0;									
					
		if ($query) {

			// Shouldn't get here, but this is a double check in case of deep links/hackers
			// Get list of permitted roles from forum_cat and check allowed
			$sql = "SELECT level FROM ".$wpdb->prefix."symposium_cats WHERE cid = %d";
			$levels = $wpdb->get_var($wpdb->prepare($sql, $query[0]->topic_category));
			$cat_roles = unserialize($levels);
			$cat_roles = str_replace('_', '', str_replace(' ', '', $cat_roles));
			if ($group_id > 0 || strpos(strtolower($cat_roles), 'everyone,') !== FALSE || strpos(strtolower($cat_roles), $user_role.',') !== FALSE) {		

				if ($limit_from == 0) {
					$html .= '<div id="__wps__table" class="__wps__forum_table">';		
				}
		
					// For every topic in this category 
					foreach ($query as $topic) {
	
						if ($cnt == 3 && get_option(WPS_OPTIONS_PREFIX.'_ad_forum_in_categories')) {
							// Advertising code +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
							$cnt++;
							if ($cnt&1) {
								$html .= '<div style="border-radius:0px;-moz-border-radius:0px" class="row ';
								if ($cnt == $num_topics) { $html .= ' round_bottom_left round_bottom_right'; }
								$html .= '">';
							} else {
								$html .= '<div style="border-radius:0px;-moz-border-radius:0px" class="row_odd ';
								if ($cnt == $num_topics) { $html .= ' round_bottom_left round_bottom_right'; }
								$html .= '">';
							}
							$html .= "<div id='ad_forum_in_categories'>";
								$html .= stripslashes(get_option(WPS_OPTIONS_PREFIX.'_ad_forum_in_categories'));	
							$html .= "</div>";
							$html .= '</div>';
						}

						$cnt++;
	
						if (!get_option(WPS_OPTIONS_PREFIX.'_wps_lite')) {
							$replies = $wpdb->get_var($wpdb->prepare("SELECT COUNT(tid) FROM ".$wpdb->prefix."symposium_topics WHERE (topic_approved = 'on' OR topic_owner = %d) AND topic_parent = %d", $current_user->ID, $topic->tid));
							$reply_views = $wpdb->get_var($wpdb->prepare("SELECT sum(topic_views) FROM ".$wpdb->prefix."symposium_topics WHERE (topic_approved = 'on' OR topic_owner = %d) AND tid = %d", $current_user->ID, $topic->tid));
						} else {
							$replies = false;
						}

						if ($cnt&1) {
							$html .= '<div id="row'.$topic->tid.'" style="border-radius:0px;-moz-border-radius:0px" class="row ';
							if ($cnt == $num_topics) { $html .= ' round_bottom_left round_bottom_right'; }
						} else {
							$html .= '<div id="row'.$topic->tid.'" style="border-radius:0px;-moz-border-radius:0px" class="row_odd ';
							if ($cnt == $num_topics) { $html .= ' round_bottom_left round_bottom_right'; }
						}
						$closed_word = strtolower(get_option(WPS_OPTIONS_PREFIX.'_closed_word'));
						if ( strpos(strtolower($topic->topic_subject), "{".$closed_word."}") > 0) {
							$color_check = ' transparent';
						} else {
							$color_check = '';
						}
						$html .= $color_check.'">';
	
							// Reset template
							$topic_template = $template;
						
							// Started by/Last Reply
							if (!get_option(WPS_OPTIONS_PREFIX.'_wps_lite')) {
								$sql = "SELECT tid, topic_subject, topic_approved, topic_post, topic_owner, topic_date, display_name, topic_sticky, topic_parent 
									FROM ".$wpdb->prefix."symposium_topics t INNER JOIN ".$wpdb->base_prefix."users u ON t.topic_owner = u.ID 
									WHERE (topic_approved = 'on' OR topic_owner = %d) AND topic_parent = %d ORDER BY tid DESC LIMIT 0,1";
								$last_post = $wpdb->get_results($wpdb->prepare($sql, $current_user->ID, $topic->tid)); 
							} else {
								$last_post = false;
							}
								
							$child_is_new = false;
							if ( $last_post ) {
	
								$done_last_reply = false;
								foreach ($last_post as $each_last_post) {
									
									if ($each_last_post->topic_date > $previous_login && $each_last_post->topic_owner != $current_user->ID && is_user_logged_in()) {
										$child_is_new = true;
									}
								
									if (!$done_last_reply) {
									
										$done_last_reply = true;
										
										// Who started it?
										if (strpos($topic_template, '[avatarfirst') !== FALSE) {
											if (strpos($topic_template, '[avatarfirst]')) {
												$topic_template = str_replace("[avatarfirst]", get_avatar($topic->topic_owner, 32), $topic_template);						
											} else {
												$x = strpos($topic_template, '[avatarfirst');
												$avatar = substr($topic_template, 0, $x);
												$avatar2 = substr($topic_template, $x+13, 2);
												$avatar3 = substr($topic_template, $x+16, strlen($topic_template)-$x-11);
															
												$topic_template = $avatar . get_avatar($topic->topic_owner, $avatar2) . $avatar3;
											
											}
										}
										$topic_template = str_replace("[startedby]", __("Started by", WPS_TEXT_DOMAIN)." ".__wps__profile_link($topic->topic_owner), $topic_template);	
										$topic_template = str_replace("[started]", " ".__wps__time_ago($topic->topic_started).".", $topic_template);	
										
										// Last reply
										if (strpos($topic_template, '[avatar') !== FALSE) {
											if (strpos($topic_template, '[avatar]')) {
												$topic_template = str_replace("[avatar]", get_avatar($each_last_post->topic_owner, 32), $topic_template);						
											} else {
												$x = strpos($topic_template, '[avatar');
												$avatar = substr($topic_template, 0, $x);
												$avatar2 = substr($topic_template, $x+8, 2);
												$avatar3 = substr($topic_template, $x+11, strlen($topic_template)-$x-11);
															
												$topic_template = $avatar . get_avatar($each_last_post->topic_owner, $avatar2) . $avatar3;
											
											}
										}
										$topic_template = str_replace("[replied]", __("Last reply by", WPS_TEXT_DOMAIN)." ".__wps__profile_link($each_last_post->topic_owner), $topic_template);	
										$topic_template = str_replace("[ago]", " ".__wps__time_ago($each_last_post->topic_date), $topic_template);	
										$post = stripslashes($each_last_post->topic_post);
										$post = strip_tags($post);
										$post = __wps__bbcode_remove($post);
										if ( strlen($post) > $snippet_length_long ) { $post = substr($post, 0, $snippet_length_long)."..."; }
										if ($each_last_post->topic_approved != 'on') { $post .= " <em>[".__("pending approval", WPS_TEXT_DOMAIN)."]</em>"; }
										$topic_template = str_replace("[topic]", " <span class='row_topic_text'>".trim($post)."</span>", $topic_template);										

									}
									
								}
									
							} else {
								
								// Strip out reply (not applicable)
								$topic_template = str_replace("avatar_last_topic'>", "avatar_last_topic' style='display:none'>", $topic_template);
								$topic_template = str_replace("last_topic_text'>", "last_topic_text' style='display:none'>", $topic_template);

								$topic_template = str_replace("[replied]", __("Last reply by", WPS_TEXT_DOMAIN)." ".__wps__profile_link($topic->topic_owner), $topic_template);	
								$topic_template = str_replace("[ago]", " ".__wps__time_ago($topic->topic_date), $topic_template);	
								$post = stripslashes($topic->topic_post);
								if ( strlen($post) > $snippet_length_long ) { $post = substr($post, 0, $snippet_length_long)."..."; }
								$post = __wps__bbcode_remove($post);
								$post = strip_tags($post);
								if ($topic->topic_approved != 'on') { $post .= " <em>[".__("pending approval", WPS_TEXT_DOMAIN)."]</em>"; }
								$topic_template = str_replace("[topic]", " <span class='row_topic_text'>".$post."</span>", $topic_template);										
								
								// First post
								if (strpos($topic_template, '[avatarfirst') !== FALSE) {
									if (strpos($topic_template, '[avatarfirst]')) {
										$topic_template = str_replace("[avatarfirst]", get_avatar($topic->topic_owner, 32), $topic_template);						
									} else {
										$x = strpos($topic_template, '[avatarfirst');
										$avatar = substr($topic_template, 0, $x);
										$avatar2 = substr($topic_template, $x+13, 2);
										$avatar3 = substr($topic_template, $x+16, strlen($topic_template)-$x-11);
													
										$topic_template = $avatar . get_avatar($topic->topic_owner, $avatar2) . $avatar3;
									
									}
								}
								$topic_template = str_replace("[startedby]", __("Started by", WPS_TEXT_DOMAIN)." ".__wps__profile_link($topic->topic_owner), $topic_template);	
								$topic_template = str_replace("[started]", " ".__wps__time_ago($topic->topic_started).".", $topic_template);	
													
							}

							// Move views and replies up if now replies (ie. no text)
							$adjustment = (false && !$replies) ? 'margin-top: -80px; ' : '';

							// Views
							if (!get_option(WPS_OPTIONS_PREFIX.'_wps_lite')) {
								$views_html = "<div class='post_count' style='".$adjustment."color:".get_option(WPS_OPTIONS_PREFIX.'_text_color').";'>".$reply_views."</div>";
								if ($reply_views != 1) { 
									$views_html .= "<div style='color:".get_option(WPS_OPTIONS_PREFIX.'_text_color').";' class='post_count_label'>".__("VIEWS", WPS_TEXT_DOMAIN)."</div>";
								} else {
									$views_html .= "<div style='color:".get_option(WPS_OPTIONS_PREFIX.'_text_color').";' class='post_count_label'>".__("VIEW", WPS_TEXT_DOMAIN)."</div>";						
								}
								$topic_template = str_replace("[views]", $views_html, $topic_template);	
							} else {
								$topic_template = str_replace("[views]", "", $topic_template);	
							}

							// Replies
							if (!get_option(WPS_OPTIONS_PREFIX.'_wps_lite')) {
								$replies_html = "<div class='post_count' style='".$adjustment."color:".get_option(WPS_OPTIONS_PREFIX.'_text_color').";'>".$replies."</div>";
								$replies_html .= "<div style='color:".get_option(WPS_OPTIONS_PREFIX.'_text_color').";' class='post_count_label'>";
								if ($replies != 1) {
									$replies_html .= __("REPLIES", WPS_TEXT_DOMAIN);
								} else {
									$replies_html .= __("REPLY", WPS_TEXT_DOMAIN);
								}
								$replies_html .= "</div>";
								$topic_template = str_replace("[replies]", $replies_html, $topic_template);	
							} else {
								$topic_template = str_replace("[replies]", "", $topic_template);	
							}
		
							// Topic Title		
							$topic_title_html = "";
							// Delete link if applicable
							$reply_posted_time = strtotime($topic->topic_started);
							$reply_posted_expire = $reply_posted_time + (get_option(WPS_OPTIONS_PREFIX.'_forum_lock') * 60);
							$now = time();
							$seconds_left = $reply_posted_expire - $now;
							if ($seconds_left > 0) {
								$title = __('Lock in', WPS_TEXT_DOMAIN).' '.gmdate("H:i:s", $seconds_left);
								$ttitle = '<br /><em>'.$title.'</em>';
							} else {
								$title = __('Admin only', WPS_TEXT_DOMAIN);
								$ttitle = '';
							}
							if (get_option(WPS_OPTIONS_PREFIX.'_forum_lock') == 0) {
								$title = __('No lock time', WPS_TEXT_DOMAIN);
								$ttitle = '';
								$seconds_left = 1;
							}
							if (can_manage_forum() || ($current_user->ID == $topic->topic_owner && $seconds_left > 0)) {
								$topic_title_html .= "<div class='topic-delete-icon'>";
								$topic_title_html .= "<a class='floatright delete_topic link_cursor' id='".$topic->tid."'style='width:16px;'><img title='".$title."' src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/delete.png' style='width:16px; height:16px;' /></a>";
								$topic_title_html .= "</div>";
							}
				
							if (strpos($favs, "[".$topic->tid."]") === FALSE ) { } else {
								$topic_title_html .= "<img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/fav-on.png' class='floatleft' style='height:18px; width:18px; margin-right:4px; margin-top:4px' />";						
							}								

							$subject = stripslashes(__wps__bbcode_remove($topic->topic_subject));
							$topic_title_html .= '<div class="row_link_div">';
		
								if ($topic->for_info == "on") { $topic_title_html .= '<img src="'.get_option(WPS_OPTIONS_PREFIX.'_images').'/info.png" alt="'.__('Information only', WPS_TEXT_DOMAIN).'" /> '; }
								if ($topic->answers > 0) { $topic_title_html .= '<img src="'.get_option(WPS_OPTIONS_PREFIX.'_images').'/tick.png" alt="'.__('Answer accepted', WPS_TEXT_DOMAIN).'" /> '; }
								if (get_option(WPS_OPTIONS_PREFIX.'_forum_ajax') == 'on') {
									$topic_title_html .= '<a title="'.$topic->tid.'" href="#cid='.$topic->topic_category.',tid='.$topic->tid.'" class="topic_subject backto row_link">'.stripslashes($subject).'</a>';
								} else {
									if (get_option(WPS_OPTIONS_PREFIX.'_permalink_structure') && $group_id == 0) {
										$perma_cat = __wps__get_forum_category_part_url($topic->topic_category);
										$topic_title_html .= '<a class="backto row_link" href="'.$forum_url.'/'.$perma_cat.$topic->stub.'">'.stripslashes($subject).'</a> ';							
									} else {
										$topic_title_html .= '<a class="backto row_link" href="'.$forum_url.$q."cid=".$topic->topic_category."&show=".$topic->tid.'">'.stripslashes($subject).'</a> ';							
									}
								}
								if (is_user_logged_in() && get_option(WPS_OPTIONS_PREFIX.'_forum_stars')) {		
									if ( ($topic->topic_started > $previous_login && $topic->topic_owner != $current_user->ID) || ($child_is_new) ) {
										$topic_title_html .= "<img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/new.gif' alt='New!' /> ";
									}	
								}
											
								if ($topic->topic_approved != 'on') { $topic_title_html .= " <em>[".__("pending approval", WPS_TEXT_DOMAIN)."]</em>"; }
								if (is_user_logged_in()) {
									$is_subscribed = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM ".$wpdb->prefix."symposium_subs WHERE cid = 0 AND tid = %d AND uid = %d", $topic->tid, $current_user->ID));
									if ($is_subscribed > 0 && $forum_all != 'on') { $topic_title_html .= ' <img src="'.get_option(WPS_OPTIONS_PREFIX.'_images').'/orange-tick.gif" alt="Subscribed" />'; } 
								}
								if ($topic->allow_replies != 'on') { $topic_title_html .= ' <img src="'.get_option(WPS_OPTIONS_PREFIX.'_images').'/padlock.gif" alt="Replies locked" />'; } 
								if ($topic->topic_sticky) { $topic_title_html .= ' <img src="'.get_option(WPS_OPTIONS_PREFIX.'_images').'/pin.gif" alt="Sticky Topic" />'; } 
				
							$topic_title_html .= "</div>";

							if ( $snippet_length > 0) {
								$post = stripslashes($topic->topic_post);
								$post = str_replace("<br />", " ", $post);
								$post = strip_tags($post);
								$post = __wps__bbcode_remove($post);
								if ( strlen($post) > $snippet_length ) { $post = substr($post, 0, $snippet_length)."..."; }
								$topic_title_html .= "<span class='row_topic_text'>".$post."</span>";
								$topic_title_html .= $ttitle;
							}
		
							$topic_template = str_replace("[topic_title]", $topic_title_html, $topic_template);	
					
						// Add template to HTML				
						$html .= $topic_template;								
		
						$html .= "</div>";
								
						// Separator
						$html .= "<div class='sep __wps__table_row_sep'></div>";		
				
					}
		
					if ($num_topics >= $limit_count) {
						$html .= "<a href='javascript:void(0)' id='showmore_forum' title='".($limit_from+$limit_count).",".$cat_id."'>".__("more...", WPS_TEXT_DOMAIN)."</a>";
					}
		
				if ($limit_from == 0) {
					$html .= "</div>"; // End of table
				}
				
			}
		}

		if ( get_option(WPS_OPTIONS_PREFIX.'_forum_ajax') ) {
			$cat_title = $wpdb->get_var($wpdb->prepare("SELECT title FROM ".$wpdb->prefix."symposium_cats WHERE cid = %d", $cat_id));
			if ($cat_title) {
				$html = $cat_title.' | '.html_entity_decode(get_bloginfo('name'), ENT_QUOTES).'[|]'.$html;
			} else {
				$html = __('Forum', WPS_TEXT_DOMAIN).' | '.html_entity_decode(get_bloginfo('name'), ENT_QUOTES).'[|]'.$html;
			}
		}
		
	} else {
		
		$html = "DONTSHOW";
		
	}

	// Filter for header
	$html = apply_filters ( '__wps__forum_categories_header_filter', $html, $cat_id );

	return $html;

}

function __wps__cmp($a, $b) {
	if ($a['name'] == $b['name']) {
		return 0;
	}
	return ($a['name'] < $b['name']) ? -1 : 1;
}

function __wps__get_topic_count($cat) {
	
	global $wpdb, $current_user;

	$topic_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM ".$wpdb->prefix."symposium_topics WHERE (topic_approved = 'on' OR topic_owner = %d) AND topic_parent = 0 AND topic_category = %d", $current_user->ID, $cat));

	$category_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM ".$wpdb->prefix."symposium_cats WHERE cat_parent = %d", $cat));

	return $topic_count+$category_count;	

}

function __wps__show_sharing_icons($cat_id, $topic_id, $sharing, $group_id) {
	
	global $wpdb;
	
	$html = "<div id='share_link' style='text-align:right;width:180px;'>";

		// Sharing icons
		// Work out link to this page, dealing with permalinks or not
		// Get forum URL worked out
		$forum_url = __wps__get_url('forum');
		if (strpos($forum_url, '?') !== FALSE) {
			$q = "&";
		} else {
			$q = "?";
		}
		$pageURL = $forum_url.$q."cid=".$cat_id;
		if ($topic_id > 0) {
			$pageURL .= "%26show=".$topic_id;
			$info = $wpdb->get_row($wpdb->prepare("SELECT topic_subject, stub FROM ".$wpdb->prefix."symposium_topics WHERE tid = %d", $topic_id));
			$title = __wps__strip_smilies($info->topic_subject);
			$stub = $info->stub;
		} else {
			$title = '';
			$stub = '';
		}
		
		// Get group URL worked out
		if ($group_id > 0) {
			$forum_url = __wps__get_url('group');
			if (strpos($forum_url, '?') !== FALSE) {
				$q = "%26gid=".$group_id."%26";
			} else {
				$q = "?gid=".$group_id."%26";
			}
			$pageURL = $forum_url.$q;
			if ($topic_id > 0) {
				$pageURL .= "cid=0%26show=".$topic_id;
			}
		}
		
		$plugin = WP_CONTENT_URL.'/plugins/wp-symposium/';

		// Permalink
		if (!(strpos($sharing, "pl") === FALSE)) {
			if (get_option(WPS_OPTIONS_PREFIX.'_permalink_structure') && $group_id == 0) {
				$perma_cat = __wps__get_forum_category_part_url($cat_id);
				$pageURL = __wps__get_url('forum').'/'.$perma_cat.$stub;
			} else {
				$pageURL = str_replace("%26", "&", $pageURL);	
			}
			$html .= "<img class='symposium_social_share' id='share_permalink' title='".$pageURL."' src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/link-icon.gif' style='cursor:pointer; margin-left:3px; height:22px; width:22px' alt='Permalink icon' />";
		}
		// Email
		if (!(strpos($sharing, "em") === FALSE)) {
			$html .= "<a class='symposium_social_share' id='share_email' title='".__('Share via email', WPS_TEXT_DOMAIN)."' href='mailto:%20?subject=".str_replace(" ", "%20", $title)."&body=".$pageURL."'>";
			$html .= "<img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/email-icon.gif' style='margin-left:3px; height:22px; width:22px;' alt='Email icon' /></a>";
		}
		// Facebook
		if (!(strpos($sharing, "fb") === FALSE)) {
			$pageURL = urlencode($pageURL);
			$html .= "<a class='symposium_social_share' id='share_facebook' target='_blank' title='".__('Share on Facebook', WPS_TEXT_DOMAIN)."' href='http://www.facebook.com/share.php?u=".$pageURL."&t=".$title."'>";
			$html .= "<img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/facebook-icon.gif' style='margin-left:3px; height:22px; width:22px' alt='Facebook icon' /></a>";
		}
		// Twitter
		if (!(strpos($sharing, "tw") === FALSE)) {
			$html .= "<a class='symposium_social_share' id='share_twitter' target='_blank' title='".__('Share on Twitter', WPS_TEXT_DOMAIN)."' href='http://twitter.com/home?status=".$pageURL."'>";
			$html .= "<img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/twitter-icon.gif' style='margin-left:3px; height:22px; width:22px' alt='Twitter icon' /></a>";
		}
		// Bebo
		if (!(strpos($sharing, "be") === FALSE)) {
			$html .= "<a class='symposium_social_share' id='share_bebo' target='_blank' title='".__('Share on Bebo', WPS_TEXT_DOMAIN)."' href='http://www.bebo.com/c/share?Url=".$pageURL."&Title=".$title."'>";
			$html .= "<img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/bebo-icon.gif' style='margin-left:3px; height:22px; width:22px' alt='Bebo icon' /></a>";
		}
		// LinkedIn
		if (!(strpos($sharing, "li") === FALSE)) {
			$html .= "<a class='symposium_social_share' id='share_linkedin' target='_blank' title='".__('Share on LinkedIn', WPS_TEXT_DOMAIN)."' href='http://www.linkedin.com/shareArticle?mini=true&url=".$pageURL."&title=".$title."'>";
			$html .= "<img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/linkedin-icon.gif' style='margin-left:3px; height:22px; width:22px' alt='LinkedIn icon' /></a>";
		}
		// MySpace
		if (!(strpos($sharing, "ms") === FALSE)) {
			$html .= "<a class='symposium_social_share' id='share_myspace' target='_blank' title='".__('Share on MySpace', WPS_TEXT_DOMAIN)."' href='http://www.myspace.com/Modules/PostTo/Pages/?u=".$pageURL."&t=".$title."'>";
			$html .= "<img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/myspace-icon.gif' style='margin-left:3px; height:22px; width:22px' alt='MySpace icon' /></a>";
		}

	$html .= "</div>";	
	
	return $html;
}

function __wps__forum_rank($uid) {
	
	global $wpdb;	
	
	$max_sql = "SELECT topic_owner, COUNT(*) AS cnt FROM ".$wpdb->prefix."symposium_topics GROUP BY topic_owner ORDER BY cnt DESC LIMIT 0,1";
	$max = $wpdb->get_row($max_sql);

	$my_sql = "SELECT COUNT(*) AS cnt FROM ".$wpdb->prefix."symposium_topics WHERE topic_owner = %d";
	$my_count = $wpdb->get_var($wpdb->prepare($my_sql, $uid));
	
	$forum_ranks = get_option(WPS_OPTIONS_PREFIX.'_forum_ranks');

	$ranks = explode(';', $forum_ranks);
	$my_rank = '';
	
	if ($my_count == $max->cnt) { 
		$my_rank = $ranks[1];
	} else {
		for ( $l = 10; $l >= 1; $l=$l-1) {
			if ($my_count >= $ranks[($l*2)+2]) {
				$my_rank = $ranks[($l*2)+1];
			}
		}
	}
	
	return $my_rank;

}

function __wps__forum_rank_points($uid) {

	global $wpdb;	
	
	$my_sql = "SELECT COUNT(*) AS cnt FROM ".$wpdb->prefix."symposium_topics WHERE topic_owner = %d";
	$my_count = $wpdb->get_var($wpdb->prepare($my_sql, $uid));

	return $my_count;

}

function __wps__clean_html($dirty) {
	$remove_php_regex = '/(<\?{1}[pP\s]{1}.+\?>)/';
 	$remove_replacement = '';  
 
 	// Get rid of PHP tags
   	$dirty = preg_replace($remove_php_regex, $remove_replacement, $dirty);	

	// No filter for allows HTML tags
	$allowedtags = array(
		'a' => array('href' => array(), 'title' => array(), 'target' => array()),
		'abbr' => array('title' => array()), 'acronym' => array('title' => array()),
		'blockquote' => array(), 
		'br' => array(), 
		'caption' => array(), 
		'code' => array(), 
		'pre' => array(), 
		'em' => array(), 
		'strong' => array(),
		'div' => array(), 
		'p' => array('style' => array()), 
		'ul' => array(), 
		'ol' => array(), 
		'li' => array(),
		'h1' => array(), 'h2' => array(), 'h3' => array(), 'h4' => array(), 'h5' => array(), 'h6' => array(),
		'img' => array('style' => array(), 'src' => array(), 'class' => array(), 'alt' => array(),'height' => array(),'width' => array()),
		'sup' => array(),
		'span' => array('style' => array()), 
		's' => array(), 
		'strike' => array(),
		'table' => array('style' => array(),'border' => array(),'cellspacing' => array(),'cellpadding' => array()), 
		'tbody' => array(),
		'tr' => array(),
		'td' => array('style' => array(),'valign' => array(),'align' => array(),'rowspan' => array(),'colspan' => array()), 
		'sup' => array(),
		'end' => array()
	);
   	
	return wp_kses($dirty, $allowedtags );
}

function __wps__bbcode_remove($text_to_search) {
	$text_to_search = str_replace('&#91;', '[', $text_to_search);
	$text_to_search = str_replace('&#93;', ']', $text_to_search);
 	$pattern = '|[[\/\!]*?[^\[\]]*?]|si';
 	$replace = '';
 	return preg_replace($pattern, $replace, $text_to_search);
}

function __wps__bbcode_replace($text_to_search) {

	$text_to_search = str_replace('http://youtu.be/', 'http://www.youtube.com/watch?v=', $text_to_search);
	$text_to_search = str_replace('&#91;', '[', $text_to_search);
	$text_to_search = str_replace('&#93;', ']', $text_to_search);

	$search = array(
	        '@\[(?i)quote\](.*?)\[/(?i)quote\]@si',
	        '@\[(?i)b\](.*?)\[/(?i)b\]@si',
	        '@\[(?i)i\](.*?)\[/(?i)i\]@si',
	        '@\[(?i)s\](.*?)\[/(?i)s\]@si',
	        '@\[(?i)u\](.*?)\[/(?i)u\]@si',
	        '@\[(?i)img\](.*?)\[/(?i)img\]@si',
	        '@\[(?i)url\](.*?)\[/(?i)url\]@si',
	        '@\[(?i)url=(.*?)\](.*?)\[/(?i)url\]@si',
	        '@\[(?i)code\](.*?)\[/(?i)code\]@si',
			'@\[youtube\].*?(?:v=)?([^?&[]+)(&[^[]*)?\[/youtube\]@is'
	);
	$replace = array(
	        '<div class="__wps__quote">\\1</div>',
	        '<strong>\\1</strong>',
	        '<i>\\1</i>',
	        '<s>\\1</s>',
	        '<u>\\1</u>',
	        '<img src="\\1">',
	        '<a href="\\1">\\1</a>',
	        '<a href="\\1">\\2</a>',
	        '<div class="__wps__code">\\1</div>',
	        '<iframe title="YouTube video player" width="475" height="290" src="http://www.youtube.com/embed/\\1" frameborder="0" allowfullscreen></iframe>'
	);

	$r = preg_replace($search, $replace, $text_to_search);

	$r = str_replace('[', '&#91;', $r);
	$r = str_replace(']', '&#93;', $r);
   
   	return $r;

}

function __wps__show_profile_menu($uid1, $uid2) {
	
	global $wpdb, $current_user;

		$share = __wps__get_meta($uid1, 'share');		
		$privacy = __wps__get_meta($uid1, 'wall_share');		
		$is_friend = __wps__friend_of($uid1, $current_user->ID);
		$sql = "SELECT meta_key FROM ".$wpdb->base_prefix."usermeta WHERE user_ID = %d AND meta_key LIKE '%symposium_extended_%' AND meta_value != ''";
		if ( $wpdb->get_results( $wpdb->prepare($sql, $uid1) ) > 0 ) { $extended = "on"; } else { $extended = ""; }
		
		$html = '';

		if ($uid1 > 0) {

			// Filter for additional menu items 
			$html .= apply_filters ( '__wps__profile_menu_filter', $html, $uid1, $uid2, $privacy, $is_friend, $extended, $share, '' );
			
			if ($uid1 == $uid2 || __wps__get_current_userlevel() == 5) {
				if (get_option(WPS_OPTIONS_PREFIX.'_profile_avatars') == 'on' && get_option(WPS_OPTIONS_PREFIX.'_menu_avatar')) {
					$html .= '<div id="menu_avatar" class="__wps__profile_menu">'.(($t = get_option(WPS_OPTIONS_PREFIX.'_menu_avatar_text')) != '' ? $t :  __('Profile Photo', WPS_TEXT_DOMAIN)).'</div>';
				}
				if (get_option(WPS_OPTIONS_PREFIX.'_menu_details'))
					$html .= '<div id="menu_personal" class="__wps__profile_menu">'.(($t = get_option(WPS_OPTIONS_PREFIX.'_menu_details_text')) != '' ? $t :  __('Profile Details', WPS_TEXT_DOMAIN)).'</div>';
				if (get_option(WPS_OPTIONS_PREFIX.'_menu_settings'))
					$html .= '<div id="menu_settings" class="__wps__profile_menu">'.(($t = get_option(WPS_OPTIONS_PREFIX.'_menu_settings_text')) != '' ? $t :  __('Community Settings', WPS_TEXT_DOMAIN)).'</div>';

			}
			
			// Add mail for admin's so they can read members's mail
			if (__wps__get_current_userlevel() == 5 && $uid1 != $current_user->ID && function_exists('__wps__mail')) {
				$mailpage = __wps__get_url('mail');
				$q = __wps__string_query($mailpage);
				$html .= '<a href="'.$mailpage.$q.'uid='.$uid1.'" class="__wps__profile_menu">'.__('Mail Admin', WPS_TEXT_DOMAIN).'</a>';
			}
			
		}
		
		// Filter for additional text/HTML after menu items
		$html .= apply_filters ( '__wps__profile_menu_end_filter', $html, $uid1, $uid2, $privacy, $is_friend, $extended, $share );

		// Filter for entire menu
		$html = apply_filters ( 'symposium_profile_entire_menu_filter', $html, $uid1, $uid2, $privacy, $is_friend, $extended, $share );
	
	return $html;

}


function __wps__make_url($text) {

    return make_clickable($text);

}


function __wps__safe_param($param) {
	$return = true;
	
	if (is_numeric($param) == FALSE) { $return = false; }
	if (strpos($param, ' ') != FALSE) { $return = false; }
	if (strpos($param, '%20') != FALSE) { $return = false; }
	if (strpos($param, ';') != FALSE) { $return = false; }
	if (strpos($param, '<script>') != FALSE) { $return = false; }
	
	return $return;
}

function __wps__pagination($total, $current, $url) {
	
	$r = '';

	$r .= '<div class="tablenav"><div class="tablenav-pages">';
	for ($i = 0; $i < $total; $i++) {
		if ($i == $current) {
            $r .= "<b>".($i+1)."</b> ";
        } else {
        	if ( ($i == 0) || ($i == $total-1) || ($i+1 == $current) || ($i+1 == $current+2) ) {
	            $r .= " <a href='".$url.($i+1)."'>".($i+1)."</a> ";
        	} else {
        		$r .= "...";
        	}
        }
	}
	$r .= '</div></div>';
	
	while ( strpos($r, "....") > 0) {
		$r = str_replace("....", "...", $r);
	}
	
	if ($i == 1) {
		return '';
	} else {
		return $r;
	}
}

function __wps__pending_friendship($uid) {
   	global $wpdb, $current_user;
	wp_get_current_user();
	
	$sql = "SELECT * FROM ".$wpdb->base_prefix."symposium_friends WHERE (friend_accepted != 'on') AND (friend_from = %d AND friend_to = %d OR friend_to = %d AND friend_from = %d)";
	
	if ( $wpdb->get_var($wpdb->prepare($sql, $uid, $current_user->ID, $uid, $current_user->ID)) ) {
		return true;
	} else {
		return false;
	}

}

function __wps__friend_of($from, $to) {
   	global $wpdb, $current_user;
	wp_get_current_user();

	$sql = $wpdb->prepare("SELECT * FROM ".$wpdb->base_prefix."symposium_friends WHERE (friend_accepted = 'on') AND (friend_from = %d AND friend_to = %d)", $from, $to);	
	if ( $wpdb->get_var( $sql )) {
		return true;
	} else {
		return false;
	}

}

function __wps__is_following($uid, $following) {
   	global $wpdb;
	
	if ( $wpdb->get_var($wpdb->prepare("SELECT * FROM ".$wpdb->base_prefix."symposium_following WHERE uid = %d AND following = %d", $uid, $following)) ) {
		return true;
	} else {
		return false;
	}

}
	

function __wps__get_current_userlevel() {

   	global $wpdb, $current_user;
	wp_get_current_user();

	// Work out user level
	$user_level = 0; // Guest
	if (is_user_logged_in()) { $user_level = 1; } // Subscriber
	if (current_user_can('edit_posts')) { $user_level = 2; } // Contributor
	if (current_user_can('edit_published_posts')) { $user_level = 3; } // Author
	if (current_user_can('moderate_comments')) { $user_level = 4; } // Editor
	if (current_user_can('activate_plugins')) { $user_level = 5; } // Administrator
	
	return $user_level;

}

function __wps__get_url($plugin) {
	
	global $wpdb;
	$return = false;
	if ($plugin == 'mail' && function_exists('__wps__mail')) {
		$return = get_option(WPS_OPTIONS_PREFIX.'_mail_url');
	}
	if ($plugin == 'forum' && function_exists('__wps__forum')) {
		$return = get_option(WPS_OPTIONS_PREFIX.'_forum_url');
	}
	if ($plugin == 'profile') {
		$return = get_option(WPS_OPTIONS_PREFIX.'_profile_url');
	}
	if ($plugin == 'avatar') {
		$return = WPS_AVATAR_URL;
	}
	if ($plugin == 'members' && function_exists('__wps__members')) {
		$return = get_option(WPS_OPTIONS_PREFIX.'_members_url');
	}
	if ($plugin == 'groups' && function_exists('__wps__group')) {
		$return = get_option(WPS_OPTIONS_PREFIX.'_groups_url');
	}
	if ($plugin == 'group' && function_exists('__wps__group')) {
		$return = get_option(WPS_OPTIONS_PREFIX.'_group_url');
	}
	if ($plugin == 'gallery' && function_exists('__wps__gallery')) {
		$return = get_option(WPS_OPTIONS_PREFIX.'_gallery_url');
	}
	if ($return == false) {
		$return = "INVALID PLUGIN URL REQUESTED (".$plugin.")";
	}
	if ($return[strlen($return)-1] == '/') { $return = substr($return,0,-1); }

	return get_bloginfo('url').$return;

}


function __wps__alter_table($table, $action, $field, $format, $null, $default) {
	
	if ($action == "MODIFY") { $action = "MODIFY COLUMN"; }
	if ($default != "") { $default = "DEFAULT ".$default; }

	global $wpdb;	
	
	$success = false;

	$check = '';
	$sql = "SHOW COLUMNS FROM ".$wpdb->prefix."symposium_".$table;
	$res = $wpdb->get_results($sql);
	if ($res) {
		foreach($res as $row) {
			if ($row->Field == $field) { 
				$check = 'exists';
			}
		}
	}
		
	if ($action == "ADD" && $check == '') {
		if ($format != 'text') {
		  	$wpdb->query("ALTER TABLE ".$wpdb->prefix."symposium_".$table." ".$action." ".$field." ".$format." ".$null." ".$default);
		} else {
		  	$wpdb->query("ALTER TABLE ".$wpdb->prefix."symposium_".$table." ".$action." ".$field." ".$format);
		}
	}

	if ($action == "MODIFY COLUMN") {
		if ($format != 'text') {
			$sql = "ALTER TABLE ".$wpdb->prefix."symposium_".$table." ".$action." ".$field." ".$format." ".$null." ".$default;
		} else {
			$sql = "ALTER TABLE ".$wpdb->prefix."symposium_".$table." ".$action." ".$field." ".$format;
		}
	  	$wpdb->query($sql);
	}
	
	if ($action == "DROP") {
		$sql = "ALTER TABLE ".$wpdb->prefix."symposium_".$table." DROP ".$field;
	  	$wpdb->query($sql);
	}
	
	return $success;

}

// Used to audit changes made
function __wps__audit($type, $uid, $meta, $value, $action) {

	if (get_option(WPS_OPTIONS_PREFIX.'_audit') == "on") {
		
		global $wpdb, $current_user, $blog_id;
		
		$wpdb->query( $wpdb->prepare( "
			INSERT INTO ".$wpdb->prefix."symposium_audit
			( 	type, 
				blog_id,
				user_id,
				current_user_id,
				meta,
				value,
				action,
				timestamp
			)
			VALUES ( %s, %d, %d, %d, %s, %s, %s, %s )", 
		    array(
		    	$type,
		    	$blog_id,
		    	$uid,
		    	$current_user->ID,
		    	$meta,
		    	$value,
		    	$action, 
		    	date("Y-m-d H:i:s")
		    	) 
		    ) );
		    
	}
}


// Updates user meta, and if not yet created, will create it
function __wps__update_meta($uid, $meta, $value) {

	global $wpdb, $current_user;

	// Can only update if logged in
	if (is_user_logged_in()) {	

		if ($meta != 'profile_avatar') {	
			
			// strip quotes from older version of WPS
			if (is_string($value) && substr($value, 0, 1) == "'") {
				$value = substr($value, 1, strlen($value)-2);
			}
	
			// create if not yet there
			if ($wpdb->get_var($wpdb->prepare("SELECT meta_key FROM ".$wpdb->base_prefix."usermeta WHERE meta_key = 'symposium_extended_city' AND user_id = %d", $uid)) != 'symposium_extended_city') __wps__create_usermeta($uid);
			
			// check if linked
			$slug = str_replace('extended_', '', $meta);
			$link = $wpdb->get_row($wpdb->prepare("SELECT extended_type, wp_usermeta FROM ".$wpdb->base_prefix."symposium_extended WHERE extended_slug = %s", $slug));
			if ($link && $link->wp_usermeta) {
				if ($link->extended_type == 'Checkbox') {
					$value = ($value == '' || $value == 'false') ? 'false' : 'true';
					$wps_value = ($value == 'true') ? 'on' : '';
				} else {
					$wps_value = $value;
				}
				if ($link->wp_usermeta) {
					$sql = "SELECT * FROM ".$wpdb->base_prefix."usermeta WHERE user_id = %d AND meta_key = %s";
					if ($wpdb->get_results($wpdb->prepare($sql, $uid, $link->wp_usermeta))) {
						$sql = "UPDATE ".$wpdb->base_prefix."usermeta SET meta_value = %s WHERE user_id = %d AND meta_key = %s";
						$action = 'update';
					} else {
						$sql = "INSERT INTO ".$wpdb->base_prefix."usermeta (meta_value, user_id, meta_key) VALUES (%s, %d, %s)";
						$action = 'insert';
					}
					$wpdb->query($wpdb->prepare($sql, $value, $uid, $link->wp_usermeta));
					$sql = "SELECT * FROM ".$wpdb->base_prefix."usermeta WHERE user_id = %d AND meta_key = %s";
					if ($wpdb->get_results($wpdb->prepare($sql, $uid, 'symposium_'.$meta))) {
						$sql = "UPDATE ".$wpdb->base_prefix."usermeta SET meta_value = %s WHERE user_id = %d AND meta_key = %s";
						$action = 'update';
					} else {
						$sql = "INSERT INTO ".$wpdb->base_prefix."usermeta (meta_value, user_id, meta_key) VALUES (%s, %d, %s)";
						$action = 'insert';
					}
					$wpdb->query($wpdb->prepare($sql, $wps_value, $uid, 'symposium_'.$meta));
					__wps__audit('usermeta', $uid, 'symposium_'.$meta, $wps_value, $action);
					
				} else {
					$sql = "SELECT * FROM ".$wpdb->base_prefix."usermeta WHERE user_id = %d AND meta_key = %s";
					if ($wpdb->get_results($wpdb->prepare($sql, $uid, 'symposium_'.$meta))) {
						$sql = "UPDATE ".$wpdb->base_prefix."usermeta SET meta_value = %s WHERE user_id = %d AND meta_key = %s";
						$action = 'update';
					} else {
						$sql = "INSERT INTO ".$wpdb->base_prefix."usermeta (meta_value, user_id, meta_key) VALUES (%s, %d, %s)";
						$action = 'insert';
					}
					$wpdb->query($wpdb->prepare($sql, $value, $uid, 'symposium_'.$meta));
					__wps__audit('usermeta', $uid, 'symposium_'.$meta, $value, $action);
				}
			} else {			
				$sql = "SELECT * FROM ".$wpdb->base_prefix."usermeta WHERE user_id = %d AND meta_key = %s";
				if ($wpdb->get_results($wpdb->prepare($sql, $uid, 'symposium_'.$meta))) {
					$sql = "UPDATE ".$wpdb->base_prefix."usermeta SET meta_value = %s WHERE user_id = %d AND meta_key = %s";
					$action = 'update';
				} else {
					$sql = "INSERT INTO ".$wpdb->base_prefix."usermeta (meta_value, user_id, meta_key) VALUES (%s, %d, %s)";
					$action = 'insert';
				}
				$wpdb->query($wpdb->prepare($sql, $value, $uid, 'symposium_'.$meta));
				__wps__audit('usermeta', $uid, 'symposium_'.$meta, $value, $action);
			}		
			return true;
			
		} else {
				
			if ($value == '') { $value = "''"; }
			
			// check if exists, and create record if not
			// only update is to profile_avatar so can check this directly
			if (!$wpdb->get_var($wpdb->prepare("SELECT profile_avatar FROM ".$wpdb->base_prefix."symposium_usermeta WHERE uid = %d", $uid))) {
				$wpdb->insert($wpdb->base_prefix.'symposium_usermeta', array( 'uid' => $uid ) );
			}
					
			// now update value
			if (is_string($value)) {
				$type = '%s';
				if (substr($value, 0, 1) == "'") {
					$value = substr($value, 1, strlen($value)-2);
				}
			} else {
				$type = '%d';
			}
			
			$r = ($wpdb->update( $wpdb->base_prefix.'symposium_usermeta', 
				array( $meta => $value ), 
				array( 'uid' => $uid ), 
				array( $type ), 
				array( '%d' )
				));
				
		  	return $r;
		  	
		}
		
	} else {
		
		return false;
		
	}
}

// Get user meta data, and create if not yet available
function __wps__get_meta($uid, $meta, $legacy=false) {

	global $wpdb;

	if (!$legacy && $meta != 'profile_avatar') {

		// create if not yet there
		if ($wpdb->get_var($wpdb->prepare("SELECT meta_key FROM ".$wpdb->base_prefix."usermeta WHERE meta_key = 'symposium_extended_city' AND user_id = %d", $uid)) != 'symposium_extended_city') __wps__create_usermeta($uid);
		if (strpos($meta, 'extended') !== FALSE) {
			$db = $wpdb->prefix;
		} else {
			$db = $wpdb->base_prefix;
		}
		if ($meta == 'city') $meta = 'extended_city';
		if ($meta == 'country') $meta = 'extended_country';

		// check if linked
		$slug = str_replace('extended_', '', $meta);
		$link = $wpdb->get_row($wpdb->prepare("SELECT extended_type, wp_usermeta FROM ".$wpdb->base_prefix."symposium_extended WHERE extended_slug = %s", $slug));
		if ($link) {
			if ($link->wp_usermeta) {
				$value = get_user_meta($uid, $link->wp_usermeta, true);
				if ($link->extended_type == 'Checkbox') $value = ($value=='true') ? 'on' : '';
			} else {
				$value = get_user_meta($uid, 'symposium_'.$meta, true);
			}
		} else {			
			$value = get_user_meta($uid, 'symposium_'.$meta, true);
		}
		if ($meta == 'extended')		
			echo $value;		
		return $value;

	} else {
		// create if not yet there
		if ($wpdb->get_var($wpdb->prepare("SELECT meta_key FROM ".$wpdb->base_prefix."usermeta WHERE meta_key = 'symposium_extended_city' AND user_id = %d", $uid)) != 'symposium_extended_city') __wps__create_usermeta($uid);

		if ($meta == 'extended_city') $meta = 'extended_city';
		if ($meta == 'extended_country') $meta = 'country';
		if ($value = $wpdb->get_var($wpdb->prepare("SELECT ".$meta." FROM ".$wpdb->base_prefix.'symposium_usermeta'." WHERE uid = %d", $uid)) ) {
			return $value;
		} else {
			return false; 	
		}
		
	}
}


function __wps__create_usermeta($uid) {

	if ($uid > 0) {
		
		global $wpdb;
		
		// do a final check that usermeta does not exist
		if ($wpdb->get_var($wpdb->prepare("SELECT meta_key FROM ".$wpdb->base_prefix."usermeta WHERE meta_key = 'symposium_extended_city' AND user_id = %d", $uid)) != 'symposium_extended_city') {
			
			// insert initial friend(s) if set
			if (get_option(WPS_OPTIONS_PREFIX.'_all_friends')) {
				// Loop through all users, adding them as friends to each other
				$sql = "SELECT ID FROM ".$wpdb->base_prefix."users WHERE ID != %d";
				$users = $wpdb->get_results($wpdb->prepare($sql, $uid));			
				foreach ($users as $user) {
					$wpdb->query( $wpdb->prepare( "
						INSERT INTO ".$wpdb->prefix."symposium_friends
						( 	friend_from, 
							friend_to,
							friend_accepted,
							friend_message,
							friend_timestamp
						)
						VALUES ( %d, %d, %s, %s, %s )", 
					    array(
					    	$uid,
					    	$user->ID,
					    	'on', 
					    	'',
					    	date("Y-m-d H:i:s")
					    	) 
					    ) );
					$wpdb->query( $wpdb->prepare( "
						INSERT INTO ".$wpdb->prefix."symposium_friends
						( 	friend_to, 
							friend_from,
							friend_accepted,
							friend_message,
							friend_timestamp
						)
						VALUES ( %d, %d, %s, %s, %s )", 
					    array(
					    	$uid,
					    	$user->ID,
					    	'on', 
					    	'',
					    	date("Y-m-d H:i:s")
					    	) 
					    ) );
				}			
			} else {
				$initial_friend = get_option(WPS_OPTIONS_PREFIX.'_initial_friend');
				if ( ($initial_friend != '') && ($initial_friend != '0') ) {
		
					$list = explode(',', $initial_friend);
		
					foreach ($list as $new_friend) {
		
					   if ($new_friend != $uid) {
						$wpdb->query( $wpdb->prepare( "
						INSERT INTO ".$wpdb->base_prefix."symposium_friends
							( 	friend_from, 
								friend_to,
								friend_accepted,
								friend_timestamp,
								friend_message
							)
						VALUES ( %d, %d, %s, %s, %s )", 
						        array(
					        	$new_friend, 
					        	$uid,
					        	'on',
				        		date("Y-m-d H:i:s"),
					        	''
				        		) 
					        ) );
						$wpdb->query( $wpdb->prepare( "
						INSERT INTO ".$wpdb->base_prefix."symposium_friends
							( 	friend_to, 
								friend_from,
								friend_accepted,
								friend_timestamp,
								friend_message
							)
						VALUES ( %d, %d, %s, %s, %s )", 
						        array(
					        	$new_friend, 
					        	$uid,
					        	'on',
				        		date("Y-m-d H:i:s"),
					        	''
				        		) 
					        ) );					        
					   }
					}
				}
			}
			
			// add to initial groups if set
			$initial_groups = get_option(WPS_OPTIONS_PREFIX.'_initial_groups');
			if ( ($initial_groups != '') && ($initial_groups != '0') ) {
	
				$list = explode(',', $initial_groups);
	
				foreach ($list as $new_group) {
	
					// Add membership
					$wpdb->query( $wpdb->prepare( "
						INSERT INTO ".$wpdb->prefix."symposium_group_members
						( 	group_id, 
							member_id,
							admin,
							valid,
							joined
						)
						VALUES ( %d, %d, %s, %s, %s )", 
				        array(
				        	$new_group, 
				        	$uid, 
				        	'',
				        	'on',
				        	date("Y-m-d H:i:s")
				        	) 
				        ) );
				        
				}
			}
	
			// add default forum categories subscriptions
			$initial_forums = get_option(WPS_OPTIONS_PREFIX.'_wps_default_forum');
			if ( ($initial_forums != '') && ($initial_forums != '0') ) {
	
				$list = explode(',', $initial_forums);
	
				foreach ($list as $new_sub) {
	
					// Add subscription
					$wpdb->query( $wpdb->prepare( "
						INSERT INTO ".$wpdb->prefix."symposium_subs
						( 	uid, 
							tid,
							cid
						)
						VALUES ( %d, %d, %d )", 
				        array(
				        	$uid, 
				        	0, 
				        	$new_sub
				        	) 
				        ) );
				        
				}
			}
			
			// insert user meta
			update_user_meta($uid, 'symposium_forum_digest', 'on');
			__wps__audit('usermeta', $uid, 'symposium_forum_digest', 'on', 'create');
			update_user_meta($uid, 'symposium_notify_new_messages', 'on');
			__wps__audit('usermeta', $uid, 'symposium_notify_new_messages', 'on', 'create');
			update_user_meta($uid, 'symposium_notify_new_wall', 'on');
			__wps__audit('usermeta', $uid, 'symposium_notify_new_wall', 'on', 'create');
			update_user_meta($uid, 'symposium_extended_city', null);
			__wps__audit('usermeta', $uid, 'symposium_extended_city', '[null]', 'create');
			update_user_meta($uid, 'symposium_extended_country', null);
			__wps__audit('usermeta', $uid, 'symposium_extended_country', '[null]', 'create');
			update_user_meta($uid, 'symposium_dob_day', null);
			__wps__audit('usermeta', $uid, 'symposium_dob_day', '[null]', 'create');
			update_user_meta($uid, 'symposium_dob_month', null);
			__wps__audit('usermeta', $uid, 'symposium_dob_month', '[null]', 'create');
			update_user_meta($uid, 'symposium_dob_year', null);
			__wps__audit('usermeta', $uid, 'symposium_dob_year', '[null]', 'create');

			update_user_meta($uid, 'symposium_share', get_option(WPS_OPTIONS_PREFIX.'_wps_default_privacy'));
			__wps__audit('usermeta', $uid, 'symposium_share', get_option(WPS_OPTIONS_PREFIX.'_wps_default_privacy'), 'create');
			update_user_meta($uid, 'symposium_wall_share', get_option(WPS_OPTIONS_PREFIX.'_wps_default_privacy'));
			__wps__audit('usermeta', $uid, 'symposium_wall_share', get_option(WPS_OPTIONS_PREFIX.'_wps_default_privacy'), 'create');

			update_user_meta($uid, 'symposium_last_activity', null);
			__wps__audit('usermeta', $uid, 'symposium_last_activity', '[null]', 'create');
			update_user_meta($uid, 'symposium_status', '');
			__wps__audit('usermeta', $uid, 'symposium_status', '[empty string]', 'create');
			update_user_meta($uid, 'symposium_visible', 'on');
			__wps__audit('usermeta', $uid, 'symposium_visible', 'on', 'create');
			update_user_meta($uid, 'symposium_widget_voted', '');
			__wps__audit('usermeta', $uid, 'symposium_widget_voted', '[empty string]', 'create');
			update_user_meta($uid, 'symposium_profile_photo', '');
			__wps__audit('usermeta', $uid, 'symposium_profile_photo', '[empty string]', 'create');
			update_user_meta($uid, 'symposium_forum_favs', null);
			__wps__audit('usermeta', $uid, 'symposium_forum_favs', '[null]', 'create');
			update_user_meta($uid, 'symposium_trusted', '');
			__wps__audit('usermeta', $uid, 'symposium_trusted', '[empty string]', 'create');
			update_user_meta($uid, 'previous_login', null);
			__wps__audit('usermeta', $uid, 'previous_login', '[null]', 'create');
			update_user_meta($uid, 'symposium_previous_login', null);
			__wps__audit('usermeta', $uid, 'symposium_previous_login', '[null]', 'create');
			update_user_meta($uid, 'symposium_forum_all', '');
			__wps__audit('usermeta', $uid, 'symposium_forum_all', '[empty string]', 'create');
			update_user_meta($uid, 'symposium_signature', '');
			__wps__audit('usermeta', $uid, 'symposium_signature', '[empty string]', 'create');
			update_user_meta($uid, 'symposium_rss_share', '');
			__wps__audit('usermeta', $uid, 'symposium_rss_share', '[empty string]', 'create');
			update_user_meta($uid, 'symposium_plus_lat', 0);
			__wps__audit('usermeta', $uid, 'symposium_plus_lat', '0', 'create');
			update_user_meta($uid, 'symposium_plus_long', 0);
			__wps__audit('usermeta', $uid, 'symposium_plus_long', '0', 'create');
			
			// Hook for further action to take place after the creation of a user, such as update metadata with different values...
			do_action('__wps__create_user_hook', $uid);
		}
	}
}


// Display array contents (for debugging only)
function __wps__displayArray($arrayname,$tab="&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp",$indent=0) {
 $curtab ="";
 $returnvalues = "";
 while(list($key, $value) = each($arrayname)) {
  for($i=0; $i<$indent; $i++) {
   $curtab .= $tab;
   }
  if (is_array($value)) {
   $returnvalues .= "$curtab$key : Array: <br />$curtab{<br />\n";
   $returnvalues .= __wps__displayArray($value,$tab,$indent+1)."$curtab}<br />\n";
   }
  else $returnvalues .= "$curtab$key => $value<br />\n";
  $curtab = NULL;
  }
 return $returnvalues;
}

// Link to profile if plugin activated
function __wps__profile_link($uid, $just_link=false) {

	global $wpdb;
	$user_info = get_userdata($uid);

	$display_name = $wpdb->get_var($wpdb->prepare("SELECT display_name FROM ".$wpdb->base_prefix."users WHERE ID = %d", $uid));
	if (function_exists('__wps__profile') && ($user_info) && ($user_info->user_login != 'nobody') ) {

		$profile_url = __wps__get_url('profile');
		$q = __wps__string_query($profile_url);

		if ($just_link) {
			$html = $profile_url.$q.'uid='.$uid;
		} else {

			$html = '<a href=\''.$profile_url.$q.'uid='.$uid.'\'>'.$display_name.'</a>';
			
			// Using permalinks?
			if (get_option(WPS_OPTIONS_PREFIX.'_permalink_structure')) {			
				$tag = strtolower(str_replace(' ', '', $display_name));
				$p = get_option(WPS_OPTIONS_PREFIX.'_rewrite_members');
				$p = substr($p, 0, strpos($p, '/'));
				$url = get_bloginfo('url')."/".$p."/".$tag;
				$html = "<a href='".$url."'>".$display_name.'</a>';
			}
		}

		
	} else {
		if ($just_link) {
			$html = false;
		} else {
			$html = $display_name;
		}
	}

	return $html;
}

// Work out query extension
function __wps__string_query($p) {
	if (strpos($p, '?') !== FALSE) { 
		$q = "&"; // No Permalink
	} else {
		$q = "?"; // Permalink
	}
	return $q;
}


// How long ago as text
function __wps__time_ago($date,$granularity=1) {
	
	$retval = '';
    $date = strtotime($date);
    $difference = (time() - $date) + 1;
    $periods = array(__('decade', WPS_TEXT_DOMAIN) => 315360000,
        'year' => 31536000,
        'month' => 2628000,
        'week' => 604800, 
        'day' => 86400,
        'hour' => 3600,
        'minute' => 60,
        'second' => 1);

	if ($difference > 315360000) {

	    $return = sprintf (__('a while ago', WPS_TEXT_DOMAIN), $retval);
		
	} else {
		
		if ($difference < 1) {
			
		    $return = sprintf (__('just now', WPS_TEXT_DOMAIN), $retval);
		    
		} else {
                                 
		    foreach ($periods as $key => $value) {
		        if ($difference >= $value) {
		            $time = floor($difference/$value);
		            $difference %= $value;
		            $retval .= ($retval ? ' ' : '').$time.' ';
		            $key = (($time > 1) ? $key.'s' : $key);
		            if ($key == 'year') { $key = __('year', WPS_TEXT_DOMAIN); }
		            if ($key == 'years') { $key = __('years', WPS_TEXT_DOMAIN); }
		            if ($key == 'month') { $key = __('month', WPS_TEXT_DOMAIN); }
		            if ($key == 'months') { $key = __('months', WPS_TEXT_DOMAIN); }
		            if ($key == 'week') { $key = __('week', WPS_TEXT_DOMAIN); }
		            if ($key == 'weeks') { $key = __('weeks', WPS_TEXT_DOMAIN); }
		            if ($key == 'day') { $key = __('day', WPS_TEXT_DOMAIN); }
		            if ($key == 'days') { $key = __('days', WPS_TEXT_DOMAIN); }
		            if ($key == 'hour') { $key = __('hour', WPS_TEXT_DOMAIN); }
		            if ($key == 'hours') { $key = __('hours', WPS_TEXT_DOMAIN); }
		            if ($key == 'minute') { $key = __('minute', WPS_TEXT_DOMAIN); }
		            if ($key == 'minutes') { $key = __('minutes', WPS_TEXT_DOMAIN); }
		            if ($key == 'second') { $key = __('second', WPS_TEXT_DOMAIN); }
		            if ($key == 'seconds') { $key = __('seconds', WPS_TEXT_DOMAIN); }
		            $retval .= $key;
		            $granularity--;
		        }
		        if ($granularity == '0') { break; }
		    }

		    $return = sprintf (__('%s ago', WPS_TEXT_DOMAIN), $retval);
		    
		}
    

	}
    return $return;


}


// Send email
function __wps__sendmail($email, $subject, $msg)
{
	global $wpdb;

	$crlf = PHP_EOL;
	
	// get footer
	$footer = stripslashes(get_option(WPS_OPTIONS_PREFIX.'_footer'));

	// get template
	$template = get_option(WPS_OPTIONS_PREFIX.'_template_email');
	$template = str_replace("[]", "", stripslashes($template));

	// Body Filter
	$msg = apply_filters ( '__wps__email_body_filter', $msg );

	$template =  str_replace('[message]', $msg, $template);
	$template =  str_replace('[footer]', $footer, $template);
	$template =  str_replace('[powered_by_message]', sprintf(__('Powered by %s - Social Networking for WordPress', WPS_TEXT_DOMAIN), WPS_WL), $template);
	$template =  str_replace('[version]', WPS_VER, $template);

	$template = str_replace(chr(10), "<br />", $template);
	
	if ( strpos($subject, '#TID') ){
		$from_email = trim(get_option(WPS_OPTIONS_PREFIX.'_mailinglist_from'));
		$from_name = html_entity_decode(trim(stripslashes(get_bloginfo('name'))), ENT_QUOTES, 'UTF-8').' '.__('Forum', WPS_TEXT_DOMAIN);
	} else {
		$from_email = trim(get_option(WPS_OPTIONS_PREFIX.'_from_email'));
		$from_name = html_entity_decode(trim(stripslashes(get_bloginfo('name'))), ENT_QUOTES, 'UTF-8');
	}
	
	if ($from_email == '') { 
		// $from_email = "noreply@".get_bloginfo('url'); // old version
		preg_match('@^(?:http://)?([^/]+)@i', get_bloginfo('url'), $matches); 
		preg_match('/[^.]+\.[^.]+$/', $matches[1], $matches);
		$from_email = "noreply@" . $matches[0];
	}	
		
	// To send HTML mail, the Content-type header must be set
	$headers = "MIME-Version: 1.0" . $crlf;
	$headers .= "Content-type:text/html;charset=utf-8" . $crlf;
	$headers .= "From: " . $from_name . " <" . $from_email . ">" . $crlf;

	// Header Filter
	$headers = apply_filters ( '__wps__email_header_filter', $headers );

	if (WPS_DEBUG) echo 'To: '.$email.'<br />From: '.str_replace($crlf, '<br />', $headers).' '.$from_email.'<br />'.stripslashes($subject).'<br />'.$template;

	// finally send mail
	if (wp_mail($email, stripslashes($subject), $template, $headers))
	{
		if (WPS_DEBUG) echo 'SENDING SUCCEEDED<br />';
		return true;
	} else {
		if (WPS_DEBUG) echo '<strong>SENDING FAILED</strong><br />';
		return false;
	}

}

// Show login link if needed
function __wps__show_login_link($str, $echo=true) {
	$login_url = (stripslashes(get_option(WPS_OPTIONS_PREFIX.'_wps_login_url')) != '') ? stripslashes(get_option(WPS_OPTIONS_PREFIX.'_wps_login_url')) : site_url().'/wp-login.php?redirect_to='.urlencode(__wps__pageURL());
	$login_url = str_replace('[url]', urlencode(__wps__pageURL()), $login_url);
	if ($echo) {
		echo sprintf($str, $login_url);
	} else {
		return sprintf($str, $login_url);
	}
}

// Function to turn a mysql datetime (YYYY-MM-DD HH:MM:SS) into a unix timestamp 
function __wps__convert_datetime($str) { 

	if ($str != '' && $str != NULL) {
		list($date, $time) = explode(' ', $str); 
		list($year, $month, $day) = explode('-', $date); 
		list($hour, $minute, $second) = explode(':', $time); 		
		$timestamp = mktime($hour, $minute, $second, $month, $day, $year); 
     } else {
		$timestamp = 999999999;
	 }
    return $timestamp; 
} 

function __wps__powered_by() {

	global $wpdb;

	if (WPS_HIDE_FOOTER)
		return $template;
		
	$template = get_option(WPS_OPTIONS_PREFIX.'_template_page_footer');
	$template = str_replace("[]", "", stripslashes($template));
	
	$template =  str_replace('[powered_by_message]', sprintf(__('Powered by %s - Social Networking for WordPress', WPS_TEXT_DOMAIN), WPS_WL), $template);
	$template =  str_replace('[version]', WPS_VER, $template);		
	
	return $template;
	
}

// Groups

function __wps__get_group_avatar($gid, $size) {


	global $wpdb, $blog_id;

	if (get_option(WPS_OPTIONS_PREFIX.'_img_db') == "on") {
	
		$sql = "SELECT group_avatar FROM ".$wpdb->prefix."symposium_groups WHERE gid = %d";
		$group_photo = $wpdb->get_var($wpdb->prepare($sql, $gid));

		if ($group_photo == '' || $group_photo == 'upload_failed') {
			return "<img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/unknown.jpg' style='height:".$size."px; width:".$size."px;' />";
		} else {
			return "<img src='".WP_CONTENT_URL."/plugins/".WPS_DIR."/server/get_group_avatar.php?gid=".$gid."' style='width:".$size."px; height:".$size."px' />";
		}
		
		return $html;
		
	} else {

		$sql = "SELECT profile_photo FROM ".$wpdb->prefix."symposium_groups WHERE gid = %d";
		$profile_photo = $wpdb->get_var($wpdb->prepare($sql, $gid));

		if ($profile_photo == '' || $profile_photo == 'upload_failed') {
			return "<img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/unknown.jpg' style='height:".$size."px; width:".$size."px;' />";
		} else {
			if ($blog_id > 1) {
				$img_url = get_option(WPS_OPTIONS_PREFIX.'_img_url')."/".$blog_id."/groups/".$gid."/profile/";	
			} else {
				$img_url = get_option(WPS_OPTIONS_PREFIX.'_img_url')."/groups/".$gid."/profile/";	
			}
			$img_src =  str_replace('//','/',$img_url) . $profile_photo;
			return "<img src='".$img_src."' style='width:".$size."px; height:".$size."px' />";
		}
		
	}
	
	exit;
	
}

function __wps__member_of($gid) {
	
	global $wpdb, $current_user;

	$sql = "SELECT valid FROM ".$wpdb->prefix."symposium_group_members   
	WHERE group_id = %d AND member_id = %d";
	$members = $wpdb->get_results($wpdb->prepare($sql, $gid, $current_user->ID));
	
	if (!$members) {
		return "no";
	} else {
		$member = $members[0];
		if ($member->valid == "on") {
			return "yes";
		} else {
			return "pending";
		}
	}

}

function __wps__group_admin($gid) {
	
	global $wpdb, $current_user;

	$sql = "SELECT admin FROM ".$wpdb->prefix."symposium_group_members   
	WHERE group_id = %d AND member_id = %d";
	$admin = $wpdb->get_var($wpdb->prepare($sql, $gid, $current_user->ID));
	
	if ($admin) {
		if ($admin == "on") {
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
	
}


/* Get site URL with protocol */
function __wps__siteURL()
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $domainName = $_SERVER['HTTP_HOST'];
    return $protocol.$domainName;
}
/* Get page URL with parameters */
function __wps__pageURL()
{
	$pageURL = (@$_SERVER["HTTPS"] == "on") ? "https://" : "http://";
	if ($_SERVER["SERVER_PORT"] != "80")
	{
	    $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
	} 
	else 
	{
	    $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
	}
	return $pageURL;
}



/* Function to sort multi-dimensional arrays */
/* For sort use asort or arsort              */
function __wps__sub_val_sort($a,$subkey,$asc=true) {
	if (count($a)) {
		foreach($a as $k=>$v) {
			$b[$k] = strtolower($v[$subkey]);
		}
		if ($asc) {
			asort($b);
		} else {
			arsort($b);
		}
		foreach($b as $key=>$val) {
			$c[] = $a[$key];
		}
		return $c;
	} else {
		return $a;
	}
}

// Add index to table
function __wps__add_index($table_name, $key_name, $parameter = "") {
	global $wpdb;
	if(!$wpdb->get_results("SHOW INDEX FROM ".$table_name." WHERE Key_name = '".$key_name."_index'")) {
		$wpdb->query("CREATE ".$parameter." INDEX ".$key_name."_index ON ".$table_name."(".$key_name.")");
	}
}

function __wps__strpos($haystack, $needles=array(), $offset=0) {

	$chr = array();
	foreach($needles as $needle) {
		$res = strpos($haystack, $needle, $offset);
		if ($res !== false) $chr[$needle] = $res;
	}
	if (empty($chr)) return false;
	return min($chr);
}

function __wps__profile_body($uid1, $uid2, $post, $version, $limit_from, $exclude_info_box=true, $rel=false) {

	global $wpdb, $current_user;

	// How many new items should be shown (before and after clicking more...)
	// Note that this is more of a scale, than a precise value (although it's close to the same)
	// For example, doubling to 60 would, roughly, show about 60 posts (depending on privacy)
	$limit_count = ($rel) ? $rel : 30; 

	$plugin = WPS_PLUGIN_URL;
	
//	if ($uid1 > 0) {
		
		if (get_option(WPS_OPTIONS_PREFIX.'_use_styles') == "on") {
			$bg_color_2 = 'background-color: '.get_option(WPS_OPTIONS_PREFIX.'_bg_color_2');
		} else {
			$bg_color_2 = '';
		}
		$privacy = ($uid1 > 0) ? __wps__get_meta($uid1, 'wall_share') : 'public';	
		
		$html = "";
			
		if (is_user_logged_in() || $privacy == 'public') {	
		
			$is_friend = ($uid1 > 0) ? __wps__friend_of($uid1, $current_user->ID) : false;
	
			if ( ($uid1 == $uid2) || (is_user_logged_in() && strtolower($privacy) == 'everyone') || (strtolower($privacy) == 'public') || (strtolower($privacy) == 'friends only' && $is_friend) || __wps__get_current_userlevel() == 5) {
			
				// Optional panel
				if ($exclude_info_box && get_option(WPS_OPTIONS_PREFIX.'_show_wall_extras') == "on" && $limit_from == 0 && version != 'stream_activity') {
						
						$html .= "<div id='__wps__profile_right_column'>";
	
						// Extended	
						$extended = __wps__get_meta($uid1, 'extended');
						$fields = explode('[|]', $extended);
						$has_extended_fields = false;
						if ($fields) {
							$ext_rows = array();
							foreach ($fields as $field) {
								$split = explode('[]', $field);
								if ( ($split[0] != '') && ($split[1] != '') ) {
								
									$extension = $wpdb->get_row($wpdb->prepare("SELECT extended_name,extended_order FROM ".$wpdb->base_prefix."symposium_extended WHERE eid = %d", $split[0]));
									
									$ext = array (	'name'=>$extension->extended_name,
													'value'=>wpautop(__wps__make_url($split[1])),
													'order'=>$extension->extended_order );
									array_push($ext_rows, $ext);
									
									$has_info = true;
									$has_extended_fields = true;
								}
							}
							$ext_rows = __wps__sub_val_sort($ext_rows,'order');
							foreach ($ext_rows as $row) {
								$html .= "<div style='margin-bottom:0px;overflow: auto;'>";
								$html .= "<div style='font-weight:bold;'>".stripslashes($row['name'])."</div>";
								$html .= "<div>".wpautop(__wps__make_url(stripslashes($row['value'])))."</div>";
								$html .= "</div>";
							}
						}
						
															
						// Friends
						$has_friends = false;
						$html .= "<div class='profile_panel_friends_div'>";
				
							$sql = "SELECT f.*, cast(m.meta_value as datetime) as last_activity FROM ".$wpdb->base_prefix."symposium_friends f LEFT JOIN ".$wpdb->base_prefix."usermeta m ON m.user_id = f.friend_to WHERE f.friend_from = %d AND f.friend_accepted = 'on' AND m.meta_key = 'symposium_last_activity'ORDER BY cast(m.meta_value as datetime) DESC LIMIT 0,6";
							$friends = $wpdb->get_results($wpdb->prepare($sql, $uid1));
							
							if ($friends) {
								
								$inactive = get_option(WPS_OPTIONS_PREFIX.'_online');
								$offline = get_option(WPS_OPTIONS_PREFIX.'_offline');
								
								$html .= '<div class="profile_panel_friends_div_title">'.sprintf(__('Recently Active %s', WPS_TEXT_DOMAIN), get_option(WPS_OPTIONS_PREFIX.'_alt_friends')).'</div>';
								foreach ($friends as $friend) {
									
									$time_now = time();
									$last_active_minutes = strtotime($friend->last_activity);
									$last_active_minutes = floor(($time_now-$last_active_minutes)/60);
																	
									$html .= "<div class='profile_panel_friends_div_row'>";		
										$html .= "<div class='profile_panel_friends_div_avatar'>";
											$html .= get_avatar($friend->friend_to, 42);
										$html .= "</div>";
										$html .= "<div>";
											$html .= __wps__profile_link($friend->friend_to)."<br />";
											$html .= __('Last active', WPS_TEXT_DOMAIN).' '.__wps__time_ago($friend->last_activity).".";
										$html .= "</div>";
				
									$html .= "</div>";
								}
								
								$has_friends = true;
							}
													
						$html .= "</div>";
						
						if (!$has_extended_fields && !$has_friends) {
							$html .= __('Make friends and they will be listed here...', WPS_TEXT_DOMAIN);
						}
	
					$html .= "</div>";
				
				}				
					
				// Wall
				
				// Filter for additional buttons
				if ($version == "wall") {
					$html = apply_filters ( '__wps__profile_wall_header_filter', $html, $uid1, $uid2, $privacy, $is_friend, __wps__get_meta($uid1, 'extended') );
				}
				
				
				/* add activity stream */	
				$html .= __wps__activity_stream($uid1, $version, $limit_from, $limit_count, $post);
		
			} else {
	
				if ($version == "friends_activity") {
					$html .= '<p>'.__("Sorry, this member has chosen not to share their activity.", WPS_TEXT_DOMAIN);
				}
	
				if ($version == "wall") {
					$html .= '<p>'.__("Sorry, this member has chosen not to share their activity.", WPS_TEXT_DOMAIN);
				}
				
			}		
			return __wps__buffer($html);
			
		} else {

			return __wps__show_login_link(__("Please <a href='%s'>login</a> to view this member's profile.", WPS_TEXT_DOMAIN), false);
			
		}
		
//	} else {
//		return '';		
//	}

}

function __wps__activity_stream($uid1='', $version='wall', $limit_from=0, $limit_count=10, $post='', $show_add_comment=true) {

	// Get button style from extension if available
	$button_style = __wps__get_extension_button_style();

	// version = stream_activity, friends_activity, all_activity
	// uid1 = the user's page (which we are looking at)
	// uid2 = the current user
	// $limit_from (starting post)
	// $limit_count (how many to show)
	// $post (individual activity post ID if applicable)
	
	global $wpdb,$current_user;
	if ($uid1 == '') $uid1 = $current_user->ID;
	$uid2 = $current_user->ID;
	
	// Get privacy level for this member's activity

	$privacy = $uid1 > 0 ? __wps__get_meta($uid1, 'wall_share') : 'public';

	$html = "";

	$html = apply_filters( '__wps__activity_top', $html, $uid1, $uid2, $version );										
	
	if (is_user_logged_in() || $privacy == 'public') {	
	
		$is_friend = ($uid1 > 0) ? __wps__friend_of($uid1, $current_user->ID) : false;	
		
		if ( ($uid1 == $uid2) || (is_user_logged_in() && strtolower($privacy) == 'everyone') || (strtolower($privacy) == 'public') || (strtolower($privacy) == 'friends only' && $is_friend) || __wps__get_current_userlevel() == 5) {

			$profile_page = __wps__get_url('profile');
			if ($profile_page[strlen($profile_page)-1] != '/') { $profile_page .= '/'; }
			$q = __wps__string_query($profile_page);	
			
			$html .= "<div id='__wps__wall'>";
		
				if ( 
					( 
					  ( ($version == 'stream_activity') && ($uid2 > 0) ) || 
					  ( 
					    ($limit_from == 0) && 
					    ($post == '') && 
					    ($uid1 != '') && 
					    ( ($uid1 == $uid2) || ($is_friend))
					   ) && (is_user_logged_in())
				     ) 
				   ) {
				       
					// Post Comment Input
					if ($show_add_comment) {

						if ($uid1 == $uid2) {							
							$whatsup = stripslashes(get_option(WPS_OPTIONS_PREFIX.'_status_label'));
							$whatsup = str_replace("'", "`", $whatsup);
						} else {
							$whatsup = __('Write a comment...', WPS_TEXT_DOMAIN);
						}

						$html .= "<div id='symposium_user_id' style='display:none'>".strtolower($current_user->ID)."</div>";
						$html .= "<div id='symposium_user_login' style='display:none'>".strtolower($current_user->user_login)."</div>";
						$html .= "<div id='symposium_user_email' style='display:none'>".strtolower($current_user->user_email)."</div>";		

						// Add status surrounding div
						$html .= '<div id="symposium_add_status">';
						
							// The textarea			
							$html .= '<textarea ';
							if (get_option(WPS_OPTIONS_PREFIX.'_elastic')) $html .= 'class="elastic" ';
							$html .= 'id="__wps__comment"  onblur="this.value=(this.value==\'\') ? \''.$whatsup.'\' : this.value;" onfocus="this.value=(this.value==\''.$whatsup.'\') ? \'\' : this.value;">';
							$html .= $whatsup;
							$html .= '</textarea>';

							if (get_option(WPS_OPTIONS_PREFIX.'_show_buttons')) {
								$html .= '<input id="__wps__add_comment" type="submit" class="__wps__button" style="'.$button_style.'" value="'.__('Post', WPS_TEXT_DOMAIN).'" /><br />';
							} else {
								$html .= '<br />';
							}

							// Embed YouTube...
							if (get_option(WPS_OPTIONS_PREFIX."_activity_youtube")) {
								$html .= '<input type="submit" id="activity_youtube_embed_button" onclick="return false;" class="__wps__button" style="'.$button_style.'" value="'.__('YouTube', WPS_TEXT_DOMAIN).'">';
								$html .= '<div id="activity_youtube_embed_id"></div>';
							}
							
							// Attach an image...
							if (get_option(WPS_OPTIONS_PREFIX."_activity_images")) {
								include_once('server/file_upload_include.php');
								$html .= show_upload_form(
									WP_CONTENT_DIR.'/wps-content/members/'.$current_user->ID.'/activity_upload/', 
									WP_CONTENT_URL.'/wps-content/members/'.$current_user->ID.'/activity_upload/',
									'activity',
									__('Add image', WPS_TEXT_DOMAIN),
									0,
									0,
									0,
									$uid1,
									$button_style
								);							
							}

						$html .= '</div>'; // End surrounding div

					}
				}

				$html = apply_filters( '__wps__activity_below_whatsup', $html, $uid1, $uid2, $version );										

			
				if ($post != '') {
					$post_cid = 'c.cid = '.$post.' AND ';
				} else {
					$post_cid = '';
				}

				// Add groups join if in use
				if (function_exists('__wps__groups')) {
					$groups = "LEFT JOIN ".$wpdb->prefix."symposium_groups g ON c.subject_uid = g.gid";
					$group_field = ", g.content_private";
				} else {
					$groups = "";
					$group_field = ", 'on' as content_private";
				}

				if (WPS_DEBUG) $html .= '$version='.$version.'<br />';
				
				if ($version == "all_activity" || $version == "stream_activity") {
					$sql = "SELECT c.*, u.display_name, u2.display_name AS subject_name" . $group_field . "   
					FROM ".$wpdb->base_prefix."symposium_comments c 
					LEFT JOIN ".$wpdb->base_prefix."users u ON c.author_uid = u.ID 
					LEFT JOIN ".$wpdb->base_prefix."users u2 ON c.subject_uid = u2.ID 
					" . $groups . "
					WHERE ( ".$post_cid." c.comment_parent = 0 
					  ) AND c.type != 'photo' 
					ORDER BY c.comment_timestamp DESC LIMIT %d,%d";					
					$comments = $wpdb->get_results($wpdb->prepare($sql, $limit_from, $limit_count));	
				}
			
				if ($version == "friends_activity") {
					$sql = "SELECT c.*, u.display_name, u2.display_name AS subject_name" . $group_field . " 
					FROM ".$wpdb->base_prefix."symposium_comments c 
					LEFT JOIN ".$wpdb->base_prefix."users u ON c.author_uid = u.ID 
					LEFT JOIN ".$wpdb->base_prefix."users u2 ON c.subject_uid = u2.ID 
					" . $groups . "
					WHERE ( ".$post_cid." (
					      ( (c.subject_uid = %d) OR (c.author_uid = %d) OR (c.subject_uid = %d) OR (c.author_uid = %d)  
					   OR ( c.author_uid IN (SELECT friend_to FROM ".$wpdb->base_prefix."symposium_friends WHERE friend_from = %d)) ) AND c.comment_parent = 0 
				   	   OR ( 
				   	   		%d IN (SELECT author_uid FROM ".$wpdb->base_prefix."symposium_comments WHERE comment_parent = c.cid ) 
							AND ( c.author_uid IN (SELECT friend_to FROM ".$wpdb->base_prefix."symposium_friends WHERE friend_from = %d)) 
				   	   	  ) )
					  ) AND c.type != 'photo' 
					ORDER BY c.comment_timestamp DESC LIMIT %d,%d";	
					$comments = $wpdb->get_results($wpdb->prepare($sql, $uid1, $uid1, $uid2, $uid2, $uid1, $uid1, $uid1, $limit_from, $limit_count));	
				}
			
				if ($version == "wall") {
					$sql = "SELECT c.*, u.display_name, u2.display_name AS subject_name" . $group_field . " 
							FROM ".$wpdb->base_prefix."symposium_comments c 
							LEFT JOIN ".$wpdb->base_prefix."users u ON c.author_uid = u.ID 
							LEFT JOIN ".$wpdb->base_prefix."users u2 ON c.subject_uid = u2.ID 
							" . $groups . "
							WHERE (".$post_cid." (
							      ( (c.subject_uid = %d OR c.author_uid = %d) AND c.comment_parent = 0 )
						   	   OR ( %d IN (SELECT author_uid FROM ".$wpdb->base_prefix."symposium_comments WHERE comment_parent = c.cid  ) )
							  ) ) AND c.type != 'photo' 
							ORDER BY c.comment_timestamp DESC LIMIT %d,%d";
					$comments = $wpdb->get_results($wpdb->prepare($sql, $uid1, $uid1, $uid1, $limit_from, $limit_count));	
					
				}

				if (WPS_DEBUG) $html .= $wpdb->last_query.'<br />';

				// Build wall
				if ($comments) {
										
					$cnt = 0;
					foreach ($comments as $comment) {
			
						$continue = true;
						if (is_user_logged_in() && $version == "friends_activity" && $uid1 != $uid2 && $comment->author_uid == $uid1 && $comment->subject_uid == $uid1) {
							$sql = "SELECT COUNT(*) FROM ".$wpdb->base_prefix."symposium_comments c 
									WHERE c.comment_parent = %d AND c.is_group != 'on'
									  AND c.author_uid != %d";
							if ($wpdb->get_var($wpdb->prepare($sql, $comment->cid, $uid1)) == 0) $continue = false;
							if (WPS_DEBUG) $html .= $wpdb->last_query.'<br />';
						}

						if ($continue) {

							if (WPS_DEBUG) $html .= '<br>continue<br>';
							$cnt++;
						
							$privacy = __wps__get_meta($comment->author_uid, 'wall_share');
							
							if ( ($comment->subject_uid == $uid1) 
								|| ($comment->author_uid == $uid1) 
								|| (strtolower($privacy) == 'everyone' && $uid2 > 0) 
								|| (strtolower($privacy) == 'public') 
								|| (strtolower($privacy) == 'friends only' && (__wps__friend_of($comment->author_uid, $uid1) || (__wps__friend_of($comment->author_uid, $uid2) && $version == "stream_activity") ) ) 
								) {
									
								// If a group post and user is not the author we need to check privacy of group settings
								if ($comment->is_group == 'on' && $comment->author_uid != $uid2) {
									// If not private group, or a member, then display
									if ($comment->content_private != 'on' || __wps__member_of($comment->subject_uid) == 'yes') {
										$private_group = '';
									} else {
										// Otherwise hide
										$private_group = 'on';
									}
								} else {
									// Not a group post so not applicable
									$private_group = '';
								}
								
								if ($private_group != 'on') {
									
									// Check to avoid poke's (as private)								
									if  ( ($comment->type != 'poke') || ($comment->type == 'poke' && ($comment->author_uid == $uid2 || $comment->subject_uid == $uid2 )) ) {	
															
										$comment_div = "<div class='wall_post_div' id='post_".$comment->cid."'>";
										
											// Avatar
											$comment_inner_div = "<div class='wall_post_avatar'>";
												$comment_inner_div .= get_avatar($comment->author_uid, 64);
											$comment_inner_div .= "</div>";
							
											$user_info = get_user_by('id', $comment->author_uid);
											if ($user_info && $user_info->user_login != 'nobody') {
												$comment_inner_div .= '<a href="'.$profile_page.$q.'uid='.$comment->author_uid.'">'.stripslashes($comment->display_name).'</a> ';
												if ($comment->author_uid != $comment->subject_uid && !$comment->is_group) {
													$comment_inner_div .= ' &rarr; ';
													$user_info = get_userdata($comment->subject_uid);
													if ($user_info->user_login != 'nobody') {
														$comment_inner_div .= '<a href="'.$profile_page.$q.'uid='.$comment->subject_uid.'">'.stripslashes($comment->subject_name).'</a> ';
													} else {
														$comment_inner_div .= stripslashes($comment->subject_name).' ';
													}
												}
											} else {
												$comment_inner_div .= stripslashes($comment->display_name).' ';
												if ($comment->author_uid != $comment->subject_uid && !$comment->is_group) {
													$comment_inner_div .= ' &rarr; ';
													$user_info = get_userdata($comment->subject_uid);
													if ($user_info->user_login != 'nobody') {
														$comment_inner_div .= '<a href="'.$profile_page.$q.'uid='.$comment->subject_uid.'">'.stripslashes($comment->subject_name).'</a> ';
													} else {
														$comment_inner_div .= stripslashes($comment->subject_name).' ';
													}
												}
											}
											$comment_inner_div .= __wps__time_ago($comment->comment_timestamp).".";

											$comment_inner_div .= "<div class='__wps__activity_icons'>";
												// Like/dislike icons
												if (get_option(WPS_OPTIONS_PREFIX.'_activity_likes') && is_user_logged_in() && $comment->author_uid != $uid2 ) {
													$sql = "SELECT COUNT(*) FROM ".$wpdb->base_prefix."symposium_likes WHERE cid = %d AND uid = %d";
													$already_liked = $wpdb->get_var($wpdb->prepare($sql, $comment->cid, $current_user->ID));
													if (!$already_liked) {
														$comment_inner_div .= "<div class='wall_post_like delete_post_top'>";
															$comment_inner_div .= "<img class='wall_add_like' title='".__('You like this.', WPS_TEXT_DOMAIN)."' data-action='like' rel='".$comment->cid."' style='width:20px;height:20px;' src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/smilies/good.png' />";
															$comment_inner_div .= "<img class='wall_add_like' title='".__('You do not like this.', WPS_TEXT_DOMAIN)."' data-action='dislike' rel='".$comment->cid."' style='width:20px;height:20px' src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/smilies/bad.png' />";
														$comment_inner_div .= "</div>";
													}
												}	
																						
												// Delete and report
												$comment_inner_div .= "<div style='width:60px; float:right;height:16px;'>";
												if (get_option(WPS_OPTIONS_PREFIX.'_allow_reports') == 'on') {
													$comment_inner_div .= " <a title='post_".$comment->cid."' href='javascript:void(0);' class='report_post report_post_top symposium_report'><img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/warning.png' style='width:16px;height:16px' /></a>";
												}
												if (__wps__get_current_userlevel() == 5 || $comment->subject_uid == $uid2 || $comment->author_uid == $uid2) {
													$comment_inner_div .= " <a title='".$comment->cid."' rel='post' href='javascript:void(0);' class='delete_post delete_post_top'><img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/delete.png' style='width:16px;height:16px' /></a>";
												}
												$comment_inner_div .= '</div>';

												// Likes/Dislikes
												if (get_option(WPS_OPTIONS_PREFIX.'_activity_likes')) {
													$sql = "SELECT COUNT(*) FROM ".$wpdb->base_prefix."symposium_likes WHERE cid = %d AND type = 'like'";
													$likes = $wpdb->get_var($wpdb->prepare($sql, $comment->cid));
													$start_likes = $likes;
													$sql = "SELECT vid FROM ".$wpdb->base_prefix."symposium_likes WHERE cid = %d AND type = 'like' AND uid=%d";
													$youlike = $wpdb->get_var($wpdb->prepare($sql, $comment->cid, $uid2));
													$sql = "SELECT COUNT(*) FROM ".$wpdb->base_prefix."symposium_likes WHERE cid = %d AND type = 'dislike'";
													$dislikes = $wpdb->get_var($wpdb->prepare($sql, $comment->cid));
													$sql = "SELECT vid FROM ".$wpdb->base_prefix."symposium_likes WHERE cid = %d AND type = 'dislike' AND uid=%d";
													$youdislike = $wpdb->get_var($wpdb->prepare($sql, $comment->cid, $uid2));
													$comment_inner_div .= "<div id='__wps__likes_".$comment->cid."'>";
														if ($likes) {
															$link = '<a id="symposium_show_likes" href="javascript:void(0)" rel="'.$comment->cid.'">';
															$comment_inner_div .= "<img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/smilies/good.png' style='width:16px;height:16px' /> ";
															if ($youlike) {
																$comment_inner_div .= __('You', WPS_TEXT_DOMAIN);
																$likes--;
																if ($likes > 1) {
																	$comment_inner_div .= ' '.sprintf(__('and %s%d others</a> like this.', WPS_TEXT_DOMAIN), $link, $likes);
																}
																if ($likes == 1) {
																	$comment_inner_div .= ' '.sprintf(__('and %s1 other</a> person likes this.', WPS_TEXT_DOMAIN), $link);
																}
																if ($likes == 0) {
																	$comment_inner_div .= ' '.__('like this.', WPS_TEXT_DOMAIN);
																}
															} else {
																if ($likes > 1) {
																	$comment_inner_div .= sprintf(__('%s%d people</a> like this.', WPS_TEXT_DOMAIN), $link, $likes);
																}
																if ($likes == 1) {
																	$sql = "SELECT uid FROM ".$wpdb->base_prefix."symposium_likes WHERE cid = %d AND type = 'like'";
																	$uid = $wpdb->get_var($wpdb->prepare($sql, $comment->cid));
																	$comment_inner_div .= __wps__profile_link($uid).' '.__('likes this.', WPS_TEXT_DOMAIN);
																}															
															}
														}
														if ($dislikes) {
															if ($start_likes) $comment_inner_div .= '<br />';
															$link = '<a id="symposium_show_likes" href="javascript:void(0)" rel="'.$comment->cid.'">';
															$comment_inner_div .= "<img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/smilies/bad.png' style='width:16px;height:16px' /> ";
															if ($youdislike) {
																$comment_inner_div .= __('You', WPS_TEXT_DOMAIN);
																$dislikes--;
																if ($dislikes > 1) {
																	$comment_inner_div .= ' '.sprintf(__('and %s%d others</a> don\'t like this.', WPS_TEXT_DOMAIN), $link, $dislikes);
																}
																if ($dislikes == 1) {
																	$comment_inner_div .= ' '.sprintf(__('and %s1 other</a> person don\'t like this.', WPS_TEXT_DOMAIN), $link);
																}
																if ($dislikes == 0) {
																	$comment_inner_div .= ' '.__('don\'t like this.', WPS_TEXT_DOMAIN);
																}
															} else {
																if ($dislikes > 1) {
																	$comment_inner_div .= sprintf(__('%s%d people</a> don\'t like this.', WPS_TEXT_DOMAIN), $link, $dislikes);
																}
																if ($dislikes == 1) {
																	$sql = "SELECT uid FROM ".$wpdb->base_prefix."symposium_likes WHERE cid = %d AND type = 'dislike'";
																	$uid = $wpdb->get_var($wpdb->prepare($sql, $comment->cid));
																	$comment_inner_div .= __wps__profile_link($uid).' '.__('doesn\'t like this.', WPS_TEXT_DOMAIN);
																}															
															}
														}
													$comment_inner_div .= "</div>";
												}
												$comment_inner_div .= "</div>";
											
											// Always show reply fields or not?
											$show_class = (get_option(WPS_OPTIONS_PREFIX.'_profile_comments')) ? '' : 'symposium_wall_replies';
											$show_field = (get_option(WPS_OPTIONS_PREFIX.'_profile_comments')) ? '' : 'display:none;';
											
											// $text = the comment
											$text = $comment->comment;
                                            
											// Added to or comment on a gallery
											if ($comment->type == 'gallery' && strpos($text, '[]')) {

												$lib = explode('[]', $text);
												$text = '<div style="width:100%">';
												// Add message
												$text .= $lib[0].'<br />';
												$action = $lib[2];
												$aid = $lib[1];
												if ($action == 'comment') {
													$single_iid = $lib[3];
													$comment_text = $lib[4];
												}


												// Get album title
												$sql = "SELECT name FROM ".$wpdb->base_prefix."symposium_gallery WHERE gid = %d";
												$album_title = $wpdb->get_var($wpdb->prepare($sql, $aid));
												$text .= '<div id="wps_gallery_album_name_'.$aid.'" style="display:none">'.stripslashes($album_title).'</div>';
																								
												// Get images
												$sql = "SELECT * FROM ".$wpdb->base_prefix."symposium_gallery_items WHERE gid = %d ORDER BY photo_order";
												$photos = $wpdb->get_results($wpdb->prepare($sql, $aid));		

												$cnt = 0;
												if ($photos) {
	   												foreach ($photos as $photo) {	
											
														$cnt++;    
																						
														// DB or Filesystem?
														if (get_option(WPS_OPTIONS_PREFIX.'_img_db') == "on") {
															$thumb_src = WP_CONTENT_URL."/plugins/wp-symposium/get_album_item.php?iid=".$photo->iid."&size=photo";
														} else {
											    	        $thumb_src = get_option(WPS_OPTIONS_PREFIX.'_img_url').'/members/'.$photo->owner.'/media/'.$aid.'/thumb_'.$photo->name;
														}
														
														$image = $thumb_src;
														$iid = $photo->iid;
														$name = $photo->title;

														if (($action == 'added' && $cnt == 1) || ($action == 'comment' && $iid == $single_iid)) {
															$image = preg_replace('/thumb_/', 'show_', $image, 1);												
															$title = '';		
										  					$text .= '<a class="__wps__photo_cover_action wps_gallery_album" data-name="'.stripslashes($title).'" data-iid="'.$iid.'" href="'.$image.'" rev="'.$cnt.'" rel="symposium_gallery_photos_'.$aid.'" title="'.$name.'">';
															$text .= '<img class="profile_activity_gallery_first_image" src="'.$image.'" /><br />';
															$text .= '</a>';
														}
														if ($action == 'added') {
															if (sizeof($photos) > 2) {
																if ($cnt == 2) {
																	$text .= '<div id="wps_comment_plus" style="height:55px;overflow:hidden;width:100%">';
																}
																if ($cnt > 1 && $cnt <= sizeof($photos)) {
												  					$text .= '<a class="__wps__photo_cover_action wps_gallery_album" data-name="'.stripslashes($title).'" data-owner="'.$photo->owner.'" data-iid="'.$iid.'" href="'.$image.'" rev="'.$cnt.'" rel="symposium_gallery_photos_'.$aid.'" title="'.$name.'">';
																	$text .= '<img style="width:50px;height:50px;margin-right:5px;margin-bottom:5px;float:left;" src="'.$image.'" />';
																	$text .= '</a>';
																}
																if ($cnt == sizeof($photos)) {
																	$text .= '</div>';
																}													
															}																	    
														} else {
															if ($iid != $single_iid) {
																if (!isset($title)) $title = '';
											  					$text .= '<a class="__wps__photo_cover_action wps_gallery_album" data-name="'.stripslashes($title).'" data-iid="'.$iid.'" href="'.$image.'" rev="'.$cnt.'" rel="symposium_gallery_photos_'.$aid.'" title="'.$name.'">';
																$text .= '<img style="display:none;" src="'.$image.'" />';
																$text .= '</a>';
															}
														}
											   		}
													if ($cnt > 7 && $action == 'added') {
														$text .= '<div id="wps_gallery_comment_more" style="clear:both;cursor:pointer">';
														$text .= __('more...', WPS_TEXT_DOMAIN).'</div>';
													}
												}
												if ($action == 'comment') {
													$text .= $comment_text;
												}
												
												$text .= '</div>';

											}
											
											// Check for any associated uploaded images for activity
											$directory = WP_CONTENT_DIR."/wps-content/members/".$comment->subject_uid.'/activity/';
											if (file_exists($directory)) {
												$handler = opendir($directory);
												while ($image = readdir($handler)) {
													$path_parts = pathinfo($image);
													if ($path_parts['filename'] == $comment->cid) {
														$directoryURL = WP_CONTENT_URL."/wps-content/members/".$comment->subject_uid.'/activity/'.$image;
														$text .= '<div style="margin-bottom:5px"></div>';
														// sort out text for title bar of dialog box
														if (strlen($comment->comment) < 75) {
															$title_bar = $comment->comment;
														} else {
															$title_bar = substr($comment->comment, 0, 75).'...';
														}
														// remove emoticons to avoid breaking image link to popup
														$remove = array("{{", "}}", ":)",";)",":-)",":(",":'(",":x",":X",":D",":|",":?",":z",":P");
														foreach ($remove as $key => $value){
														   $title_bar  = str_replace($value, "", $title_bar);
														}
			
														// rev = this image to default on (would be a count of all images included)
														// rel = the 'group' of images to be included
														$text .= "<a target='_blank' href='".$directoryURL."' rev='1' rel='symposium_activity_images_".$comment->cid."' data-owner='".$comment->subject_uid."' data-name='".$title_bar."' data-iid='".$comment->cid."' class='wps_gallery_album'>";
														$text .= '<img class="profile_activity_image" src="'.$directoryURL.'" />';
														$text .= '</a>';
													}
												}
											}											
											
											// Finally show comment...!
											$text = stripslashes($text);
											$comment_inner_div .= '<div class="next_comment '.$show_class.'" id="'.$comment->cid.'">';
											if ($comment->is_group) {
												$url = __wps__get_url('group');
												$q = __wps__string_query($url);
												$url .= $q.'gid='.$comment->subject_uid.'&post='.$comment->cid;
												$group_name = $wpdb->get_var($wpdb->prepare("SELECT name FROM ".$wpdb->base_prefix."symposium_groups WHERE gid = %d", $comment->subject_uid));
												$comment_inner_div .= __("Group post in", WPS_TEXT_DOMAIN)." <a href='".$url."'>".stripslashes($group_name)."</a>: ".__wps__make_url($text);
											} else {
												$comment_inner_div .= __wps__make_url($text);
											}
											
											$comment_inner_div = apply_filters( '__wps__activity_row_item_filter', $comment_inner_div, $comment );									
                                        
											// Replies +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
											
											$sql = "SELECT c.*, u.display_name FROM ".$wpdb->base_prefix."symposium_comments c 
												LEFT JOIN ".$wpdb->base_prefix."users u ON c.author_uid = u.ID 
												LEFT JOIN ".$wpdb->base_prefix."symposium_comments p ON c.comment_parent = p.cid 
												WHERE c.comment_parent = %d AND c.is_group != 'on' ORDER BY c.cid";
							
											$replies = $wpdb->get_results($wpdb->prepare($sql, $comment->cid));	
							
											$count = 0;
											if ($replies) {
												if (count($replies) > 4) {
													$comment_inner_div .= "<div id='view_all_comments_div'>";
													$comment_inner_div .= "<a title='".$comment->cid."' class='view_all_comments' href='javascript:void(0);'>".__(sprintf("View all %d comments", count($replies)), WPS_TEXT_DOMAIN)."</a>";
													$comment_inner_div .= "</div>";
												}
												foreach ($replies as $reply) {
													$count++;
													if ($count > count($replies)-4) {
														$reply_style = "";
													} else {
														$reply_style = "display:none; ";
													}
													$comment_inner_div .= "<div id='".$reply->cid."' class='reply_div' style='".$reply_style."'>";
														$comment_inner_div .= "<div class='__wps__wall_reply_div'>";
															$comment_inner_div .= "<div class='wall_reply'>";
																$comment_inner_div .= '<a href="'.$profile_page.$q.'uid='.$reply->author_uid.'">'.stripslashes($reply->display_name).'</a> ';
																$comment_inner_div .= __wps__time_ago($reply->comment_timestamp).".";
																$comment_inner_div .= '<div style="width:50px; float:right;">';
																if (get_option(WPS_OPTIONS_PREFIX.'_allow_reports') == 'on') {
																	$comment_inner_div .= " <a title='post_".$reply->cid."' href='javascript:void(0);' style='padding:0px' class='report_post symposium_report reply_warning'><img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/warning.png' style='width:14px;height:14px' /></a>";
																}

																// Like/dislike icons for reply
																if (get_option(WPS_OPTIONS_PREFIX.'_activity_likes') && is_user_logged_in() && $reply->author_uid != $uid2 ) {
																	$sql = "SELECT COUNT(*) FROM ".$wpdb->base_prefix."symposium_likes WHERE cid = %d AND uid = %d";
																	$already_liked = $wpdb->get_var($wpdb->prepare($sql, $reply->cid, $current_user->ID));
																	if (!$already_liked) {
																		$comment_inner_div .= "<div class='wall_post_like delete_reply' style='margin:0;padding:0;'>";
																			$comment_inner_div .= "<img class='wall_add_like' title='".__('You like this.', WPS_TEXT_DOMAIN)."' data-action='like' rel='".$reply->cid."' style='padding:0;width:20px;height:20px;' src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/smilies/good.png' />";
																			$comment_inner_div .= "<img class='wall_add_like' title='".__('You do not like this.', WPS_TEXT_DOMAIN)."' data-action='dislike' rel='".$reply->cid."' style='padding:0;width:20px;height:20px' src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/smilies/bad.png' />";
																		$comment_inner_div .= "</div>";
																	}
																}	

																if (__wps__get_current_userlevel($uid2) == 5 || $reply->subject_uid == $uid2 || $reply->author_uid == $uid2) {
																	$comment_inner_div .= " <a title='".$reply->cid."' rel='reply' href='javascript:void(0);' style='padding:0px' class='delete_post delete_reply'><img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/delete.png' style='width:14px;height:14px' /></a>";
																}
																$comment_inner_div .= '</div>';
																$comment_inner_div .= "<br />";
																
																// Likes/Dislikes for replies
																if (get_option(WPS_OPTIONS_PREFIX.'_activity_likes')) {
																	$sql = "SELECT COUNT(*) FROM ".$wpdb->base_prefix."symposium_likes WHERE cid = %d AND type = 'like'";
																	$likes = $wpdb->get_var($wpdb->prepare($sql, $reply->cid));
																	$start_likes = $likes;
																	$sql = "SELECT vid FROM ".$wpdb->base_prefix."symposium_likes WHERE cid = %d AND type = 'like' AND uid=%d";
																	$youlike = $wpdb->get_var($wpdb->prepare($sql, $reply->cid, $uid2));
																	$sql = "SELECT COUNT(*) FROM ".$wpdb->base_prefix."symposium_likes WHERE cid = %d AND type = 'dislike'";
																	$dislikes = $wpdb->get_var($wpdb->prepare($sql, $reply->cid));
																	$sql = "SELECT vid FROM ".$wpdb->base_prefix."symposium_likes WHERE cid = %d AND type = 'dislike' AND uid=%d";
																	$youdislike = $wpdb->get_var($wpdb->prepare($sql, $reply->cid, $uid2));
																	$comment_inner_div .= "<div id='__wps__likes_".$reply->cid."'>";
																		if ($likes) {
																			$link = '<a id="symposium_show_likes" href="javascript:void(0)" rel="'.$reply->cid.'">';
																			$comment_inner_div .= "<img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/smilies/good.png' style='width:16px;height:16px' /> ";
																			if ($youlike) {
																				$comment_inner_div .= __('You', WPS_TEXT_DOMAIN);
																				$likes--;
																				if ($likes > 1) {
																					$comment_inner_div .= ' '.sprintf(__('and %s%d others</a> like this.', WPS_TEXT_DOMAIN), $link, $likes);
																				}
																				if ($likes == 1) {
																					$comment_inner_div .= ' '.sprintf(__('and %s1 other person</a> likes this.', WPS_TEXT_DOMAIN), $link);
																				}
																				if ($likes == 0) {
																					$comment_inner_div .= ' '.__('like this.', WPS_TEXT_DOMAIN);
																				}
																			} else {
																				if ($likes > 1) {
																					$comment_inner_div .= sprintf(__('%s%d people</a> like this.', WPS_TEXT_DOMAIN), $link, $likes);
																				}
																				if ($likes == 1) {
																					$sql = "SELECT uid FROM ".$wpdb->base_prefix."symposium_likes WHERE cid = %d AND type = 'like'";
																					$uid = $wpdb->get_var($wpdb->prepare($sql, $reply->cid));
																					$comment_inner_div .= __wps__profile_link($uid).' '.__('likes this.', WPS_TEXT_DOMAIN);
																				}															
																			}
																		}
																		if ($dislikes) {
																			if ($start_likes) $comment_inner_div .= '<br />';
																			$link = '<a id="symposium_show_likes" href="javascript:void(0)" rel="'.$reply->cid.'">';
																			$comment_inner_div .= "<img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/smilies/bad.png' style='width:16px;height:16px' /> ";
																			if ($youdislike) {
																				$comment_inner_div .= __('You', WPS_TEXT_DOMAIN);
																				$dislikes--;
																				if ($dislikes > 1) {
																					$comment_inner_div .= ' '.sprintf(__('and %s%d others</a> don\'t like this.', WPS_TEXT_DOMAIN), $link, $dislikes);
																				}
																				if ($dislikes == 1) {
																					$comment_inner_div .= ' '.sprintf(__('and %s1 other</a> person don\'t like this.', WPS_TEXT_DOMAIN), $link);
																				}
																				if ($dislikes == 0) {
																					$comment_inner_div .= ' '.__('don\'t like this.', WPS_TEXT_DOMAIN);
																				}
																			} else {
																				if ($dislikes > 1) {
																					$comment_inner_div .= sprintf(__('%s%d people</a> don\'t like this.', WPS_TEXT_DOMAIN), $link, $dislikes);
																				}
																				if ($dislikes == 1) {
																					$sql = "SELECT uid FROM ".$wpdb->base_prefix."symposium_likes WHERE cid = %d AND type = 'dislike'";
																					$uid = $wpdb->get_var($wpdb->prepare($sql, $reply->cid));
																					$comment_inner_div .= __wps__profile_link($uid).' '.__('doesn\'t like this.', WPS_TEXT_DOMAIN);
																				}															
																			}
																		}
																	$comment_inner_div .= "</div>";
																}
																$comment_inner_div .= __wps__make_url(stripslashes($reply->comment));
															$comment_inner_div .= "</div>";
														$comment_inner_div .= "</div>";
														
														$comment_inner_div .= "<div class='wall_reply_avatar'>";
															$comment_inner_div .= get_avatar($reply->author_uid, 40);
														$comment_inner_div .= "</div>";		
													$comment_inner_div .= "</div>";
												}
											} else {
												$comment_inner_div .= "<div class='no_wall_replies'></div>";
											}												
											$comment_inner_div .= "<div style='clear:both;' id='__wps__comment_".$comment->cid."'></div>";
							
											// Reply (comment) field
											if ( 
													(is_user_logged_in()) && 
													(
														($uid1 == $uid2) || 
														(
															strtolower($privacy) == 'everyone' || 
															strtolower($privacy) == 'public' || 
															(strtolower($privacy) == 'friends only' && $is_friend) || 
															($version = "stream_activity" && strtolower($privacy) == 'friends only' && __wps__friend_of($comment->author_uid, $current_user->ID))
														)
													)
												) 
											{
												if ($comment->type != 'gallery' && $comment->type != 'friend') {
													$comment_inner_div .= '<div style="margin-top:5px;'.$show_field.'" id="__wps__reply_div_'.$comment->cid.'" >';
	
													$comment_inner_div .= '<textarea title="'.$comment->cid.'" class="__wps__reply';
													if (get_option(WPS_OPTIONS_PREFIX.'_elastic')) $comment_inner_div .= ' elastic';
													$comment_inner_div .= '" id="__wps__reply_'.$comment->cid.'" onblur="this.value=(this.value==\'\') ? \''.__('Write a comment...', WPS_TEXT_DOMAIN).'\' : this.value;" onfocus="this.value=(this.value==\''.__('Write a comment...', WPS_TEXT_DOMAIN).'\') ? \'\' : this.value;">'.__('Write a comment...', WPS_TEXT_DOMAIN).'</textarea>';
													
													if (get_option(WPS_OPTIONS_PREFIX.'_show_buttons')) {
														$comment_inner_div .= '<br /><input title="'.$comment->cid.'" type="submit" style="width:75px;'.$button_style.'" class="__wps__button symposium_add_reply" value="'.__('Add', WPS_TEXT_DOMAIN).'" />';
													}
													$comment_inner_div .= '<input id="symposium_author_'.$comment->cid.'" type="hidden" value="'.$comment->subject_uid.'" />';
													$comment_inner_div .= '</div>';
												}
											}

											$comment_inner_div .= "</div>";
											
											$comment_inner_div = apply_filters( '__wps__activity_item_inner_filter', $comment_inner_div );										
				
										$comment_div .= $comment_inner_div."</div>";
								
										$comment_div = apply_filters( '__wps__activity_item_filter', $comment_div );
	
										// Check if forcing UTF8 (to handle umlets, etc)
										if (get_option(WPS_OPTIONS_PREFIX.'_force_utf8') == 'on') 
											$comment_div = utf8_decode($comment_div);
											
										$html .= $comment_div;
									}
									
								}
								
							} else {
								// Protected by privacy settings
							}	
						} // Comment by member with no replies and looking at friends activity
					}
					
					$id = 'wall';
					if ($version == "all_activity" || $version == "stream_activity") { $id='all'; }
					if ($version == "friends_activity") { $id='activity'; }
			
					if ($post == '' && $cnt > 0) {
						// Set next comment to show
						// old version was $next (regression testing) = $limit_from+$cnt+1;
						$next = $limit_from+$limit_count;
						if (is_user_logged_in()) $html .= "<a href='javascript:void(0)' id='".$id."' class='showmore_wall' title='".($next)."'>".__("more...", WPS_TEXT_DOMAIN)."</a>";
					} else {
						if ($post == '') {
							$html .= "<br />".__("Nothing to show, sorry.", WPS_TEXT_DOMAIN);
						}
					}
						
				} else {
					$html .= "<br />".__("Nothing to show, sorry.", WPS_TEXT_DOMAIN);
				}
			
			$html .= "</div>";

			} else {

			if ($version == "friends_activity") {
				$html .= '<p>'.__("Sorry, this member has chosen not to share their activity.", WPS_TEXT_DOMAIN);
			}

			if ($version == "wall") {
				$html .= '<p>'.__("Sorry, this member has chosen not to share their activity.", WPS_TEXT_DOMAIN);
			}
			
		}		
		return $html;
//		return __wps__buffer($html);
		
	} else {

		return __wps__show_login_link(__("Please <a href='%s'>login</a> to view this member's profile.", WPS_TEXT_DOMAIN), false);
		
	}
		
	return $html;
}



// **********************************************************************************
// FUNCTIONS SHARED BETWEEN AJAX AND NON-AJAX VERSIONS OF WIDGETS
// **********************************************************************************

// New activity/status posts
function __wps__do_friends_status_Widget($postcount,$preview,$forum) {
	
	global $wpdb, $current_user;
	
	$shown_uid = "";
	$shown_count = 0;	
	$html = '';

	// Work out link to profile page, dealing with permalinks or not
	$profile_url = __wps__get_url('profile');
	$q = __wps__string_query($profile_url);

	$user_info = get_user_by('login', 'nobody');
	$nobody_id = $user_info->ID;
	if (!$nobody_id) $nobody_id = 0;

	// Content of widget
	$sql = "SELECT cid, author_uid, comment, comment_timestamp, display_name, type 
	FROM ".$wpdb->base_prefix."symposium_comments c 
	LEFT JOIN ".$wpdb->base_prefix."symposium_friends f ON c.author_uid = f.friend_to
	INNER JOIN ".$wpdb->base_prefix."users u ON c.author_uid = u.ID 
	WHERE f.friend_from = %d
	  AND is_group != 'on' 
	  AND comment_parent = 0 
	  AND author_uid != %d AND subject_uid != %d 
	  AND author_uid = subject_uid ";
	if ($forum != 'on') { $sql .= "AND type != 'forum' "; }		  
	$sql .= "ORDER BY cid DESC LIMIT 0,250";
	
	
	$posts = $wpdb->get_results($wpdb->prepare($sql, $current_user->ID, $nobody_id, $nobody_id));
	if (WPS_DEBUG) echo $wpdb->last_query;
			
	if ($posts) {

		$html .= "<div id='__wps__recent_activity'>";
			
			foreach ($posts as $post)
			{
				if ($shown_count < $postcount) {

					if (strpos($shown_uid, $post->author_uid.",") === FALSE) { 
						
						$share = __wps__get_meta($post->author_uid, 'wall_share');
						$is_friend = __wps__friend_of($post->author_uid, $current_user->ID);

						if ( (is_user_logged_in() && strtolower($share) == 'everyone') || (strtolower($share) == 'public') || (strtolower($share) == 'friends only' && $is_friend) ) {

							$html .= "<div class='__wps__recent_activity_row'>";		
								$html .= "<div class='__wps__recent_activity_row_avatar'>";
									$html .= "<a href='".$profile_url.$q."uid=".$post->author_uid."'>";
										$html .= get_avatar($post->author_uid, 32);
									$html .= "</a>";
								$html .= "</div>";
								$html .= "<div class='__wps__recent_activity_row_post'>";
									$text = stripslashes($post->comment);
									if ($post->type == 'post') {
										$text = stripslashes($post->comment);
										$text = strip_tags($text);
										if ( strlen($text) > $preview ) { $text = substr($text, 0, $preview)."..."; }
									}
									if ($post->type == 'gallery') {												
										if (strpos($text, '[]')) {
											$lib = explode('[]', $text);
											$text = $lib[0];
										} else {
											if (($x = strpos($text, 'wps_comment_plus')) !== FALSE) {
												$text = substr($text, 0, $x-9);
											}
										}
									}
									if (strpos($text, '__wps__photo_image')) {
											$text = strip_tags($text,'<img><a></a>');
											$text = str_replace("img", "img style='width:32px;height:32px;'", $text);
											$html .= __wps__time_ago($post->comment_timestamp)." ".__wps__profile_link($post->author_uid)." ".$text."<br>";
									} else {
										$html .= __wps__profile_link($post->author_uid)." ".$text." ".__wps__time_ago($post->comment_timestamp).".<br>";
									}
								$html .= "</div>";
							$html .= "</div>";
						
							$shown_count++;
							$shown_uid .= $post->author_uid.",";							
						}
					}
				} else {
					break;
				}
			}

		$html .= "</div>";

	}
		
	echo $html;
}

// Recently active members
function do_recent_Widget($__wps__recent_count,$__wps__recent_desc,$__wps__recent_show_light,$__wps__recent_show_mail) {
		
	global $wpdb, $current_user;
	
	$html = '';

	$user_info = get_user_by('login', 'nobody');
	$nobody_id = $user_info->ID;
	if (!$nobody_id) $nobody_id = 0;

	// Content of widget
	$sql = "SELECT u.ID, u.display_name, cast(m.meta_value as datetime) as last_activity 
		FROM ".$wpdb->base_prefix."users u 
		LEFT JOIN ".$wpdb->base_prefix."usermeta m ON u.ID = m.user_id
		WHERE u.ID != %d AND m.meta_key = 'symposium_last_activity'
		ORDER BY cast(m.meta_value as datetime) DESC LIMIT 0,".$__wps__recent_count;

	$members = $wpdb->get_results($wpdb->prepare($sql, $nobody_id));
		
	if ($members) {

		$mail_url = __wps__get_url('mail');
		$profile_url = __wps__get_url('profile');
		$q = __wps__string_query($mail_url);
		$time_now = time();

		$html .= "<div id='__wps__new_members'>";
		
			$cnt = 0;
			foreach ($members as $member)
			{
				if (__wps__get_meta($member->ID, 'status') != 'offline') {
					$last_active_minutes = strtotime($member->last_activity);
					$last_active_minutes = floor(($time_now-$last_active_minutes)/60);
				} else {
					$last_active_minutes = 999999999;
				}
				
				if ($__wps__recent_desc == 'on') {
					$html .= "<div class='__wps__new_members_row'>";		
						$html .= "<div class='__wps__new_members_row_avatar'>";
							$html .= "<a href='".$profile_url.$q."uid=".$member->ID."'>";
								$html .= get_avatar($member->ID, 32);
							$html .= "</a>";
						$html .= "</div>";
						$html .= "<div class='__wps__new_members_row_member'>";
							$html .= __wps__profile_link($member->ID)." ";
							if ($__wps__recent_show_light == 'on') {
								if ($last_active_minutes >= get_option(WPS_OPTIONS_PREFIX.'_offline')) {
									$html .= '<img src="'.get_option(WPS_OPTIONS_PREFIX.'_images').'/loggedout.gif">';
								} else {
									if ($last_active_minutes >= get_option(WPS_OPTIONS_PREFIX.'_online')) {
										$html .= '<img src="'.get_option(WPS_OPTIONS_PREFIX.'_images').'/inactive.gif">';
									} else {
										$html .= '<img src="'.get_option(WPS_OPTIONS_PREFIX.'_images').'/online.gif">';
									}
								}
							}
							$html .= '<br />';
							$html .= __('Last active', WPS_TEXT_DOMAIN)." ";
							$html .= __wps__time_ago($member->last_activity).".";
							if (function_exists('__wps__mail') && $__wps__recent_show_mail == 'on' && __wps__friend_of($member->ID, $current_user->ID) ) {
								$html .= " <a title='".$member->display_name."' href='".$mail_url.$q."view=compose&to=".$member->ID."'>".__('Send Mail', WPS_TEXT_DOMAIN)."</a>";
							}
						$html .= "</div>";
					$html .= "</div>";
				} else {
					$html .= "<a title='".$member->display_name."' style='padding-right:3px;padding-bottom:3px;float:left;cursor:pointer;' href='".$profile_url.$q."uid=".$member->ID."'>";
						$html .= get_avatar($member->ID, 32);
					$html .= "</a>";
				}
			}
			$html .= "</div>";				
	} else {
		$html .= "<div id='__wps__new_members'>";
		$html .= __("Nobody recently online.", WPS_TEXT_DOMAIN);
		$html .= "</div>";							
	}
		
	echo $html;
	
}

// New activity/status posts
function __wps__do_Recentactivity_Widget($postcount,$preview,$forum) {
	
	global $wpdb, $current_user;
	
	$shown_uid = "";
	$shown_count = 0;	
	$html = '';
	// Work out link to profile page, dealing with permalinks or not
	$profile_url = __wps__get_url('profile');
	$q = __wps__string_query($profile_url);

	$user_info = get_user_by('login', 'nobody');
	$nobody_id = $user_info->ID;
	if (!$nobody_id) $nobody_id = 0;

	// Content of widget
	$sql = "SELECT cid, author_uid, comment, comment_timestamp, display_name, type 
	FROM ".$wpdb->base_prefix."symposium_comments c 
	INNER JOIN ".$wpdb->base_prefix."users u ON c.author_uid = u.ID 
	WHERE is_group != 'on' 
	  AND comment_parent = 0 
	  AND author_uid != %d AND subject_uid != %d 
	  AND author_uid = subject_uid ";
	if ($forum != 'on') { $sql .= "AND type != 'forum' "; }		  
	$sql .= "ORDER BY cid DESC LIMIT 0,250";
	
	$posts = $wpdb->get_results($wpdb->prepare($sql, $nobody_id, $nobody_id));
			
	if ($posts) {

		$html .= "<div id='__wps__recent_activity'>";
			
			foreach ($posts as $post)
			{
				if ($shown_count < $postcount) {

					if (strpos($shown_uid, $post->author_uid.",") === FALSE) { 

						$share = __wps__get_meta($post->author_uid, 'wall_share');
						$is_friend = __wps__friend_of($post->author_uid, $current_user->ID);

						if ( (is_user_logged_in() && strtolower($share) == 'everyone') || (strtolower($share) == 'public') || (strtolower($share) == 'friends only' && $is_friend) ) {

							$html .= "<div class='__wps__recent_activity_row'>";		
								$html .= "<div class='__wps__recent_activity_row_avatar'>";
									$html .= "<a href='".$profile_url.$q."uid=".$post->author_id."'>";
										$html .= get_avatar($post->author_uid, 32);
									$html .= "</a>";
								$html .= "</div>";
								$html .= "<div class='__wps__recent_activity_row_post'>";
									$text = stripslashes($post->comment);
									if ($post->type == 'post') {
										$text = stripslashes($post->comment);
										$text = strip_tags($text);
										if ( strlen($text) > $preview ) { $text = substr($text, 0, $preview)."..."; }
									}
									if (($x = strpos($text, 'wps_comment_plus')) !== FALSE) {
										$text = substr($text, 0, $x-9);
									}
									if ($post->type == 'gallery') {												
										if (strpos($text, '[]')) {
											$lib = explode('[]', $text);
											$text = $lib[0];
										} else {
											if (($x = strpos($text, 'wps_comment_plus')) !== FALSE) {
												$text = substr($text, 0, $x-9);
											}
										}
									}
									if (strpos($text, '__wps__photo_image')) {
										$text = strip_tags($text,'<img><a></a>');
										$text = str_replace("img", "img style='width:32px;height:32px;'", $text);
										$html .= __wps__time_ago($post->comment_timestamp)." <a href='".$profile_url.$q."uid=".$post->author_uid."&post=".$post->cid."'>".$post->display_name."</a> ".$text."<br>";
									} else {
										$html .= "<a href='".$profile_url.$q."uid=".$post->author_uid."&post=".$post->cid."'>".$post->display_name."</a> ".$text." ".__wps__time_ago($post->comment_timestamp).".<br>";
									}
								$html .= "</div>";
							$html .= "</div>";
						
							$shown_count++;
							$shown_uid .= $post->author_uid.",";							
						}
					}
				} else {
					break;
				}
			}

		$html .= "</div>";

	}
		
	echo $html;
}


// Newly joined members
function __wps__do_members_Widget($__wps__members_count) {
	
	global $wpdb, $current_user;
	
	$html = '';
	
	$user_info = get_user_by('login', 'nobody');
	$nobody_id = $user_info->ID;
	if (!$nobody_id) $nobody_id = 0;	

	// Content of widget
	$members = $wpdb->get_results($wpdb->prepare("
		SELECT * FROM ".$wpdb->base_prefix."users
		WHERE ID != %d 
		ORDER BY user_registered DESC LIMIT 0,".$__wps__members_count, $nobody_id)); 

	if ($members) {
		
		$profile_url = __wps__get_url('profile');
		$q = __wps__string_query($profile_url);

		$html .= "<div id='__wps__new_members'>";

			foreach ($members as $member)
			{
				$html .= "<div class='__wps__new_members_row'>";		
					$html .= "<div class='__wps__new_members_row_avatar'>";
						$html .= "<a href='".$profile_url.$q."uid=".$member->ID."'>";
							$html .= get_avatar($member->ID, 32);
						$html .= "</a>";
					$html .= "</div>";
					$html .= "<div class='__wps__new_members_row_member'>";
						$html .= __wps__profile_link($member->ID)."<br />".__('Joined', WPS_TEXT_DOMAIN)." ";
						$html .= __wps__time_ago($member->user_registered).".";
					$html .= "</div>";
				$html .= "</div>";
			}
			
			$html .= "</div>";				
	}
		
	echo $html;
}

// Show friends
function __wps__do_friends_Widget($__wps__friends_count,$__wps__friends_desc,$__wps__friends_mode,$__wps__friends_show_light,$__wps__friends_show_mail) {
	
	global $wpdb, $current_user;
	$html = '';

	$user_info = get_user_by('login', 'nobody');
	$nobody_id = $user_info->ID;
	if (!$nobody_id) $nobody_id = 0;

	// Content of widget
	$sql = "SELECT u.ID, u.display_name, cast(m.meta_value as datetime) as last_activity 
		FROM ".$wpdb->base_prefix."symposium_friends f
		LEFT JOIN ".$wpdb->base_prefix."users u ON f.friend_to = u.ID
		LEFT JOIN ".$wpdb->base_prefix."usermeta m ON f.friend_to = m.user_id
		WHERE f.friend_from = %d AND f.friend_accepted = 'on' 
		AND f.friend_to != %d 
		AND m.meta_key = 'symposium_last_activity'
		ORDER BY cast(m.meta_value as datetime) DESC LIMIT 0,".$__wps__friends_count;

	$members = $wpdb->get_results($wpdb->prepare($sql, $current_user->ID, $nobody_id));
		
	if ($members) {

		$mail_url = __wps__get_url('mail');
		$profile_url = __wps__get_url('profile');
		$q = __wps__string_query($mail_url);
		$time_now = time();

		$html .= "<div id='__wps__new_members'>";
		
			if ($__wps__friends_mode == 'all' || $__wps__friends_mode == 'online') {
				$loop=1;
			} else {
				$loop=2;
			}
			for ($l=1; $l<=$loop; $l++) {
				
				if ($__wps__friends_mode == 'split') {
					if ($l==1) {
						$html .= '<div style="font-weight:bold">'.__('Online', WPS_TEXT_DOMAIN).'</div>';
					} else {
						$html .= '<div style="clear:both;margin-top:6px;font-weight:bold">'.__('Offline', WPS_TEXT_DOMAIN).'</div>';
					}
					
				}

				$cnt = 0;
				foreach ($members as $member)
				{
					$last_active_minutes = strtotime($member->last_activity);
					$last_active_minutes = floor(($time_now-$last_active_minutes)/60);
					
					$show = false;
					if ($__wps__friends_mode == 'online' && $last_active_minutes < get_option(WPS_OPTIONS_PREFIX.'_offline')) { $show = true; }
					if ( ($__wps__friends_mode == 'split') && ( ($last_active_minutes < get_option(WPS_OPTIONS_PREFIX.'_offline') && $l == 1) || ($last_active_minutes >= get_option(WPS_OPTIONS_PREFIX.'_offline') && $l == 2) ) ) { $show = true; }
					if ($__wps__friends_mode == 'all') { $show = true; }
					
					if ($show) {
						$cnt++;								
						if ($__wps__friends_desc == 'on') {
							$html .= "<div class='__wps__new_members_row'>";		
								$html .= "<div class='__wps__new_members_row_avatar'>";
									$html .= "<a href='".$profile_url.$q."uid=".$member->ID."'>";
										$html .= get_avatar($member->ID, 32);
									$html .= "</a>";
								$html .= "</div>";
								$html .= "<div class='__wps__new_members_row_member'>";
									$html .= __wps__profile_link($member->ID)."<br />";
									if ($__wps__friends_show_light == 'on') {
										if ($last_active_minutes >= get_option(WPS_OPTIONS_PREFIX.'_offline')) {
											$html .= '<img src="'.get_option(WPS_OPTIONS_PREFIX.'_images').'/loggedout.gif"> ';
										} else {
											if ($last_active_minutes >= get_option(WPS_OPTIONS_PREFIX.'_online')) {
												$html .= '<img src="'.get_option(WPS_OPTIONS_PREFIX.'_images').'/inactive.gif"> ';
											} else {
												$html .= '<img src="'.get_option(WPS_OPTIONS_PREFIX.'_images').'/online.gif"> ';
											}
										}
									}
									$html .= __('last active', WPS_TEXT_DOMAIN)." ";
									$html .= __wps__time_ago($member->last_activity).".";
									if (function_exists('__wps__mail') && $__wps__friends_show_mail == 'on') {
										$html .= " <a title='".$member->display_name."' href='".$mail_url.$q."view=compose&to=".$member->ID."'>".__('Send Mail', WPS_TEXT_DOMAIN)."</a>";
									}
								$html .= "</div>";
							$html .= "</div>";
						} else {
							$html .= "<a title='".$member->display_name."' style='padding-right:3px;padding-bottom:3px;float:left;cursor:pointer;' href='".$profile_url.$q."uid=".$member->ID."'>";
								$html .= get_avatar($member->ID, 32);
							$html .= "</a>";
						}
					}
				}
				if ($cnt == 0) {
					$html .= __('Nobody', WPS_TEXT_DOMAIN);
				}
			}
			
			$html .= "</div>";				
	} else {
		$html .= "<div id='__wps__new_members'>";
		$html .= sprintf(__("No %s yet, add %s via their profile page.", WPS_TEXT_DOMAIN), get_option(WPS_OPTIONS_PREFIX.'_alt_friends'), get_option(WPS_OPTIONS_PREFIX.'_alt_friends'));
		$html .= "</div>";							
	}
	
	echo $html;
}

// Recent forum posts
function __wps__do_Forumrecentposts_Widget($postcount,$preview,$cat_id,$show_replies,$incl_cat,$incl_parent,$just_own) {
	
	global $wpdb, $current_user;

	$user_info = get_user_by('login', 'nobody');
	$nobody_id = $user_info ? $user_info->ID : 0;
	if (!$nobody_id) $nobody_id = 0;
	
	// Content of widget
	$sql = "SELECT t.tid, t.stub, p.stub as parent_stub, p.topic_parent as parent_parent, p.topic_subject as parent_subject, p.topic_category as parent_category, t.topic_subject, t.topic_owner, t.topic_post, t.topic_started, t.topic_category, t.topic_date, u.display_name, t.topic_parent, t.topic_group 
	FROM ".$wpdb->prefix.'symposium_topics'." t 
	INNER JOIN ".$wpdb->base_prefix.'users'." u ON t.topic_owner = u.ID 
	LEFT JOIN ".$wpdb->prefix."symposium_topics p ON t.topic_parent = p.tid 
	WHERE t.topic_approved = 'on' AND (t.topic_parent = 0 || p.topic_parent = 0) ";
	if ($cat_id != '' && $cat_id > 0) {
		$sql .= "AND t.topic_category = ".$cat_id." ";
	}
	if ($show_replies != 'on') {
		$sql .= "AND t.topic_parent = 0 ";
	}
	if ($just_own == 'on') {
		$sql .= "AND t.topic_owner = ".$current_user->ID." ";
	}
	$sql .= "AND t.topic_owner != %d ";
	$sql .= "ORDER BY t.tid DESC LIMIT %d,%d";
	$posts = $wpdb->get_results($wpdb->prepare($sql, $nobody_id, 0, 100)); 
	$count = 0;
	$html = '';
	
	if (WPS_DEBUG) $html .= $wpdb->last_query.'<br />';
	
	// Previous login
	if (is_user_logged_in()) {
		$previous_login = __wps__get_meta($current_user->ID, 'previous_login');
	}

	// Get URLs worked out
	$profile_url = __wps__get_url('profile');
	$forum_url = __wps__get_url('forum');
	$forum_q = __wps__string_query($forum_url);

	// Get list of roles for this user
    $user_roles = $current_user->roles;
    $user_role = strtolower(array_shift($user_roles));
    if ($user_role == '') $user_role = 'NONE';
    						
	if ($posts) {

		$html .= "<div id='__wps__latest_forum'>";
			
			foreach ($posts as $post)
			{
					if ($post->topic_group == 0 || (__wps__member_of($post->topic_group) == "yes") || ($wpdb->get_var($wpdb->prepare("SELECT content_private FROM ".$wpdb->prefix."symposium_groups WHERE gid = %d", $post->topic_group)) != "on") ) {

						// Check permitted to see forum category
						$sql = "SELECT level FROM ".$wpdb->prefix."symposium_cats WHERE cid = %d";
						$levels = $wpdb->get_var($wpdb->prepare($sql, $post->topic_category));
						$cat_roles = unserialize($levels);
						if (strpos(strtolower($cat_roles), 'everyone,') !== FALSE || strpos(strtolower($cat_roles), $user_role.',') !== FALSE) {
							
							$html .= "<div class='__wps__latest_forum_row'>";		
								$html .= "<div class='__wps__latest_forum_row_avatar'>";
									$html .= "<a href='".$profile_url.$forum_q."uid=".$post->topic_owner."'>";
										$html .= get_avatar($post->topic_owner, 32);
									$html .= "</a>";
								$html .= "</div>";
								$html .= "<div class='__wps__latest_forum_row_post'>";
									if ($post->topic_parent > 0) {
										$html .= __wps__profile_link($post->topic_owner);
										if ($preview > 0) {
											$text = strip_tags(stripslashes($post->topic_post));
											$text = __wps__bbcode_remove($text);
											if ( strlen($text) > $preview ) { $text = substr($text, 0, $preview)."..."; }
											if ($post->parent_parent == 0) {
												$html .= " ".__('replied', WPS_TEXT_DOMAIN);
											} else {
												$html .= " ".__('commented', WPS_TEXT_DOMAIN);
											}
											if ($text == '')
												$text = __('(No text)', WPS_TEXT_DOMAIN);
											if (get_option(WPS_OPTIONS_PREFIX.'_permalink_structure')) {
												$perma_cat = __wps__get_forum_category_part_url($post->topic_category);
												$html .= " <a href='".$forum_url.'/'.$perma_cat.$post->parent_stub."'>".$text."</a>";
											} else {
												$html .= " <a href='".$forum_url.$forum_q."cid=".$post->topic_category."&show=".$post->topic_parent."'>".$text."</a>";
											}
											if ($incl_parent) {
												if (get_option(WPS_OPTIONS_PREFIX.'_permalink_structure')) {
													$perma_cat = __wps__get_forum_category_part_url($post->parent_category);
													$html .= ' '.__('to', WPS_TEXT_DOMAIN)." <a href='".$forum_url.'/'.$perma_cat.$post->parent_stub."'>".strip_tags(stripslashes($post->parent_subject))."</a> ";
												} else {
													if ($post->parent_parent == 0) {
														$html .= ' '.__('to', WPS_TEXT_DOMAIN)." <a href='".$forum_url.$forum_q."cid=".$post->parent_category."&show=".$post->topic_parent."'>".strip_tags(stripslashes($post->parent_subject))."</a> ";
													}
												}
											}
											if ($incl_cat) {
												if (get_option(WPS_OPTIONS_PREFIX.'_permalink_structure')) {
													$cat = stripslashes($wpdb->get_var($wpdb->prepare('SELECT title FROM '.$wpdb->prefix.'symposium_cats WHERE cid = %d', $post->parent_category)));
													$perma_cat = __wps__get_forum_category_part_url($post->parent_category);
													$html .= ' '.__('in', WPS_TEXT_DOMAIN)." <a href='".$forum_url.'/'.$perma_cat."'>".$cat."</a> ";
												} else {
													$html .= ' '.__('in', WPS_TEXT_DOMAIN)." <a href='".$forum_url.$forum_q."cid=".$post->parent_category."'>".$cat."</a> ";
												}
											}
										} else {
											$html .= "<br />";
										}
										$html .= " ".__wps__time_ago($post->topic_date).".";
									} else {
										$html .= __wps__profile_link($post->topic_owner);
										if ($preview > 0) {
											$text = stripslashes($post->topic_subject);
											$text = __wps__bbcode_remove($text);
											if ( strlen($text) > $preview ) { $text = substr($text, 0, $preview)."..."; }
											if ($post->topic_group == 0) {
												$url = $forum_url;
												$q = $forum_q;
											} else {
												// Get group URL worked out
												$url = __wps__get_url('group');
												if (strpos($url, '?') !== FALSE) {
													$q = "&gid=".$post->topic_group."&";
												} else {
													$q = "?gid=".$post->topic_group."&";
												}
											}
											$html .= " ".__('started', WPS_TEXT_DOMAIN);
											if (get_option(WPS_OPTIONS_PREFIX.'_permalink_structure')) {
												$perma_cat = __wps__get_forum_category_part_url($post->topic_category);
												$html .= " <a href='".$url.'/'.$perma_cat.$post->stub."'>".$text."</a>";
											} else {
												$html .= " <a href='".$url.$q."cid=".$post->topic_category."&show=".$post->tid."'>".$text."</a>";
											}
											if ($incl_cat) {
												if (get_option(WPS_OPTIONS_PREFIX.'_permalink_structure') && $group_id == 0) {
													$cat = stripslashes($wpdb->get_var($wpdb->prepare('SELECT title FROM '.$wpdb->prefix.'symposium_cats WHERE cid = %d', $post->topic_category)));
													$perma_cat = __wps__get_forum_category_part_url($post->topic_category);
													$html .= ' '.__('in', WPS_TEXT_DOMAIN)." <a href='".$forum_url.'/'.$perma_cat."'>".$cat."</a> ";
												} else {
													$html .= ' '.__('in', WPS_TEXT_DOMAIN)." <a href='".$forum_url.$forum_q."cid=".$post->topic_category."'>".$cat."</a> ";
												}
											}
										}
										$html .= " ".__wps__time_ago($post->topic_started).".";
									}
										if (is_user_logged_in() && get_option(WPS_OPTIONS_PREFIX.'_forum_stars')) {
											if ($post->topic_date > $previous_login && $post->topic_owner != $current_user->ID) {
												$html .= " <img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/new.gif' alt='New!' />";
											}
										}
								$html .= "</div>";
							$html .= "</div>";
							
							$count++;
							if ($count >= $postcount) {
								break;
							}
						}
						
					}
			}

		$html .= "</div>";

	} else {
		$html .= __('None', WPS_TEXT_DOMAIN);
	}
		
	echo $html;
}

// Summary/login widget
function __wps__do_summary_Widget($show_loggedout,$show_form,$login_url,$show_avatar,$login_username,$login_password,$login_remember_me,$login_button,$login_forgot,$login_register,$show_avatar_size) {
	
	global $wpdb,$current_user;
	
	// Content of widget
	echo "<div id='__wps__summary_widget'>";

	if (is_user_logged_in()) {

		// LOGGED IN

		if ($show_avatar) {
			$show_avatar_size = $show_avatar_size ? $show_avatar_size : 100;
			echo "<div id='__wps__summary_widget_avatar'>";
			echo get_avatar($current_user->ID, $show_avatar_size);
			echo "</div>";
		}
	
		if ($show_avatar) {
			echo "<div id='__wps__summary_widget_list' style='margin-left: ".($show_avatar_size+15)."px;'>";
		} else {
			echo "<div id='__wps__summary_widget_list_noavatar'>";
		}

			echo "<ul style='list-style:none'>";

				// Link to profile page
				if (function_exists('__wps__profile')) {

					// Get mail URL worked out
					$profile_url = __wps__get_url('profile');

					echo "<li>";
						echo "<a href='".$profile_url."'>".$current_user->display_name."</a> ";
					echo "</li>";
				}

				// Mail
				if (function_exists('__wps__mail')) {

					// Get mail URL worked out
					$mail_url = __wps__get_url('mail');

					echo "<li id='symposium_summary_mail'>";
						$total_mail = $wpdb->get_var($wpdb->prepare("SELECT count(*) FROM ".$wpdb->base_prefix."symposium_mail WHERE mail_to = %d AND mail_in_deleted != 'on'", $current_user->ID));
						echo "<a href='".$mail_url."'>".__("Messages:", WPS_TEXT_DOMAIN)."</a> ".$total_mail;
						$unread_mail = $wpdb->get_var($wpdb->prepare("SELECT count(*) FROM ".$wpdb->base_prefix."symposium_mail WHERE mail_to = %d AND mail_in_deleted != 'on' AND mail_read != 'on'", $current_user->ID));
						if ($unread_mail > 0) {
							echo " (".$unread_mail." ".__("unread",WPS_TEXT_DOMAIN).")";
						}
					echo "</li>";
				}

				// Friends
				if (function_exists('__wps__profile')) {

					// Get mail URL worked out
					$friends_url = __wps__get_url('profile');
					if (strpos($friends_url, '?') !== FALSE) {
						$q = "&view=friends";
					} else {
						$q = "?view=friends";
					}

					echo "<li id='symposium_summary_profile'>";
						$sql = "SELECT count(*) FROM ".$wpdb->base_prefix."symposium_friends WHERE friend_to = %d AND friend_accepted = 'on'";
						$current_friends = $wpdb->get_var($wpdb->prepare($sql, $current_user->ID));
						echo  "<a href='".$friends_url.$q."'>".get_option(WPS_OPTIONS_PREFIX.'_alt_friends').":</a> ".$current_friends;
						$sql = "SELECT count(*) FROM ".$wpdb->base_prefix."symposium_friends WHERE friend_to = %d AND friend_accepted != 'on'";
						$friend_requests = $wpdb->get_var($wpdb->prepare($sql, $current_user->ID));

						if ($friend_requests == 1) {	
							echo " (".$friend_requests." ".__("request",WPS_TEXT_DOMAIN).")";
						}
						if ($friend_requests > 1) {	
							echo " (".$friend_requests." ".__("requests",WPS_TEXT_DOMAIN).")";
						}
					echo "</li>";

					// Previous login(s)
					/*
					echo 'Last login<br>';
					echo __wps__get_meta($current_user->ID, 'last_login').'<br />';
					echo 'Previous login<br>';
					echo __wps__get_meta($current_user->ID, 'previous_login').'<br />';
					*/


					// Hook for more list items
					do_action('symposium_widget_summary_hook_loggedin');

					if ( current_user_can('manage_options') ) {
						echo wp_register( "<li id='symposium_summary_dashboard'>", "</li>", true);
					}
					if ($show_loggedout == 'on') {
						echo "<li id='symposium_summary_logout'>";
						echo wp_loginout( get_bloginfo('url'), true );
						echo "</li>";
					}

				}

			echo "</ul>";

		echo "</div>";


				
	} else {

		// LOGGED OUT

		// Hook for more list items
		do_action('symposium_widget_summary_hook_loggedout');

		echo '<div id="__wps__summary_widget_logged_out_form">';

			if ($show_loggedout == 'on' && $show_form == '') {
				echo wp_loginout( get_permalink(), true);
				echo ' (<a href="'.wp_lostpassword_url( get_bloginfo('url') ).'" title="'.__('Forgot Password?', WPS_TEXT_DOMAIN).'">'.__('Forgot Password?', WPS_TEXT_DOMAIN).'</a>)<br />';
				echo wp_register( "", "", true);
			}

			if ($show_loggedout == 'on' && $show_form == 'on') {
			   if ($login_url != '') {
			      wp_login_form(array(
			         'redirect' => $login_url, 
			         'label_username' => stripslashes($login_username), 
			         'label_password' => stripslashes($login_password),
			         'label_remember' => stripslashes($login_remember_me),
			         'label_log_in' => stripslashes($login_button)
			      ) )  ;
			   } else {
			      wp_login_form(array(
			         'redirect' => get_permalink(), 
			         'label_username' => stripslashes($login_username), 
			         'label_password' => stripslashes($login_password),
			         'label_remember' => stripslashes($login_remember_me),
			         'label_log_in' => stripslashes($login_button)
			      ) )  ;
			   }
			   echo '<a href="'.wp_lostpassword_url( get_bloginfo('url') ).'" title="'.stripslashes($login_forgot).'">'.stripslashes($login_forgot).'</a><br />';
			   echo wp_register("<!--", "--><a href='".get_bloginfo('url')."/wp-login.php?action=register'>".stripslashes($login_register)."</a>", true) ;
			}	

		echo '</div>';


	}
		
	echo "</div>";	
	
}

// Forum posts with no answer
function __wps__do_Forumnoanswer_Widget($preview,$cat_id,$cat_id_exclude,$timescale,$postcount,$groups) {
	
	global $wpdb, $current_user;
	
	$html = '';

	// Previous login
	if (is_user_logged_in()) {
		$previous_login = __wps__get_meta($current_user->ID, 'previous_login');
	}
	
	// Content of widget
	
	$sql = "SELECT t.tid, t.topic_subject, t.topic_owner, t.topic_post, t.topic_category, t.topic_date, u.display_name, t.topic_parent, t.topic_group, t.topic_started, 
		(SELECT COUNT(*) FROM ".$wpdb->prefix."symposium_topics v WHERE v.topic_parent = t.tid) AS replies 
		FROM ".$wpdb->prefix."symposium_topics t 
		INNER JOIN ".$wpdb->base_prefix.'users'." u ON t.topic_owner = u.ID
		WHERE t.topic_parent = 0 
		  AND t.for_info != 'on' 
		  AND t.topic_approved = 'on' 
		  AND t.topic_started >= ( CURDATE() - INTERVAL ".$timescale." DAY ) 
		AND NOT EXISTS 
		  (SELECT tid from ".$wpdb->prefix."symposium_topics s 
		    WHERE s.topic_parent = t.tid AND s.topic_answer = 'on') ";
	if ($cat_id != '' && $cat_id > 0) {
		$sql .= "AND topic_category IN (".$cat_id.") ";
	}
	if ($cat_id_exclude != '' && $cat_id_exclude > 0) {
		$sql .= "AND topic_category NOT IN (".$cat_id_exclude.") ";
	}
	if ($groups != 'on') {
		$sql .= "AND topic_group = 0 ";
	}
	$sql .= "ORDER BY t.topic_started DESC LIMIT 0,".$postcount;
	$posts = $wpdb->get_results($sql); 
			
	// Get forum URL worked out
	$forum_url = __wps__get_url('forum');
	$forum_q = __wps__string_query($forum_url);

	// Get list of roles for this user
    $user_roles = $current_user->roles;
    $user_role = strtolower(array_shift($user_roles));
    if ($user_role == '') $user_role = 'NONE';
    							
	if ($posts) {

		$html .= "<div id='__wps__latest_forum'>";
			
			foreach ($posts as $post)
			{
					if ($post->topic_group == 0 || (__wps__member_of($post->topic_group) == "yes") || ($wpdb->get_var($wpdb->prepare("SELECT content_private FROM ".$wpdb->prefix."symposium_groups WHERE gid = %d", $post->topic_group)) != "on") ) {

						// Check permitted to see forum category
						$sql = "SELECT level FROM ".$wpdb->prefix."symposium_cats WHERE cid = %d";
						$levels = $wpdb->get_var($wpdb->prepare($sql, $post->topic_category));
						$cat_roles = unserialize($levels);
						if (strpos(strtolower($cat_roles), 'everyone,') !== FALSE || strpos(strtolower($cat_roles), $user_role.',') !== FALSE) {

							$html .= "<div class='__wps__latest_forum_row'>";		
								$html .= "<div class='__wps__latest_forum_row_avatar'>";
									$html .= get_avatar($post->topic_owner, 32);
								$html .= "</div>";
								$html .= "<div class='__wps__latest_forum_row_post'>";
									$html .= __wps__profile_link($post->topic_owner);
									if ($preview > 0) {
										$text = stripslashes($post->topic_subject);
										if ( strlen($text) > $preview ) { $text = substr($text, 0, $preview)."..."; } 
										if ($post->topic_group == 0) {
											$url = $forum_url;
											$q = $forum_q;
										} else {
											// Get group URL worked out
											$url = __wps__get_url('group');
											if (strpos($url, '?') !== FALSE) {
												$q = "&gid=".$post->topic_group."&";
											} else {
												$q = "?gid=".$post->topic_group."&";
											}
										}
										$html .= " ".__('started', WPS_TEXT_DOMAIN)." <a href='".$url.$q."cid=".$post->topic_category."&show=".$post->tid."'>".$text."</a>";
									} else {
										$html .= "<br />";
									}
									$html .= " ".__wps__time_ago($post->topic_started).". ";
									if ($post->replies > 0) {
										$html .= $post->replies.' ';
										if ($post->replies != 1) {
											$html .= __('replies', WPS_TEXT_DOMAIN);
										} else {
											$html .= __('reply', WPS_TEXT_DOMAIN);
										}
										$html .= ".";
									}
									if (is_user_logged_in() && get_option(WPS_OPTIONS_PREFIX.'_forum_stars')) {
										if ($post->topic_started > $previous_login && $post->topic_owner != $current_user->ID) {
											$html .= " <img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/new.gif' alt='New!' />";
										}
									}
									$html .= "<br />";
								$html .= "</div>";
							$html .= "</div>";
						}								
					}
			}

		$html .= "</div>";

	}
	
	echo $html;
}

function __wps__do_Forumexperts_Widget($cat_id,$cat_id_exclude,$timescale,$postcount,$groups) {
	
	global $wpdb,$current_user;
	
	$html = '';

	$user_info = get_user_by('login', 'nobody');
	$nobody_id = $user_info->ID;
	if (!$nobody_id) $nobody_id = 0;

	// Content of widget
	$sql = "SELECT topic_owner, display_name, count(*) AS cnt FROM 
	 		(SELECT topic_owner FROM ".$wpdb->prefix."symposium_topics t 
			 WHERE t.topic_owner != ".$nobody_id." AND t.topic_answer = 'on' AND t.topic_date >= ( CURDATE() - INTERVAL ".$timescale." DAY ) "; 
	if ($cat_id != '' && $cat_id > 0) {
		$sql .= "AND topic_category IN (".$cat_id.") ";
	}
	if ($cat_id_exclude != '' && $cat_id_exclude > 0) {
		$sql .= "AND topic_category NOT IN (".$cat_id_exclude.") ";
	}
	if ($groups != 'on') {
		$sql .= "AND topic_group = 0 ";
	}
	$sql .= "ORDER BY topic_owner) AS tmp ";
	$sql .= "LEFT JOIN ".$wpdb->base_prefix."users u ON topic_owner = u.ID ";
	$sql .= "GROUP BY topic_owner, display_name ";
	$sql .= "ORDER BY cnt DESC";
	$posts = $wpdb->get_results($sql);
	
	$count = 1;
	
	if ($posts) {

		$html .= "<div id='__wps__latest_forum'>";
			
			foreach ($posts as $post)
			{
				$html .= '<div style="clear:both;">';
					$html .= '<div style="float:left;">';
						$html .= __wps__profile_link($post->topic_owner);
					$html .= '</div>';
					$html .= '<div style="float:right;">';
						$html .= $post->cnt.'<br />';
					$html .= '</div>';
				$html .= '</div>';
				
				if ($count++ == $postcount) {
					break;
				}
			}

		$html .= "</div>";

	}
	
	echo $html;	
}

function __wps__do_Alerts_Widget($postcount) {
	
	global $wpdb,$current_user;

	if ( get_option(WPS_OPTIONS_PREFIX.'__wps__news_main_activated') || get_option(WPS_OPTIONS_PREFIX.'__wps__news_main_network_activated') ) {

		if (is_user_logged_in()) {

			$max_items = $postcount < 50 ? $postcount : 50;

			// Get new news items
			$sql = "SELECT nid, news, added, new_item
				FROM ".$wpdb->base_prefix."symposium_news 
				WHERE subject = %d 
				ORDER BY new_item desc, nid DESC LIMIT 0,%d";

			$items = $wpdb->get_results($wpdb->prepare($sql, $current_user->ID, $max_items));
		
			// Loop through comments, adding to array if any exist
			if ($items) {
				echo '<div class="__wps__alerts_widget_list">';
					foreach ($items as $item) {

						echo '<div class="__wps__alerts_widget_row">';
						echo stripslashes($item->news).' ';
						echo __wps__time_ago($item->added);
						echo '</div>';
						
					}	
					$sql = "SELECT ID FROM ".$wpdb->prefix."posts WHERE lower(post_content) LIKE '%[symposium-alerts]%' AND post_type = 'page' AND post_status = 'publish';";
					$pages = $wpdb->get_results($sql);	
					if ($pages) {
						$url = get_permalink($pages[0]->ID);
					}
					echo '<a id="__wps__alerts_widget_more" style="float:right" href="'.$url.'">'.__('more...', WPS_TEXT_DOMAIN).'</a>';
					
				echo '</div>';

			} 


		}
		
	} else {
		echo __('Alerts module not activated. You can activate it on the WP Symposium installation page.', WPS_TEXT_DOMAIN);
	}
	
}

// **********************************************************************************


function __wps__is_plus() {

	$saved_code = get_option(WPS_OPTIONS_PREFIX.'_activation_code');
	$code = preg_replace('#[^0-9]#','',$saved_code);
	if (($saved_code) && ($code > time() || $saved_code == 'wps' || substr($saved_code,0,3) == 'vip')) {
		return true;
	} else {
		return false;
	}
	
}

function has_bronze_plug_actived() {
	// Do check for Bronze plugins and activation code
	$has_bronze_plug_actived = false;

	if (get_option(WPS_OPTIONS_PREFIX.'__wps__mobile_activated') 				|| get_option(WPS_OPTIONS_PREFIX.'__wps__mobile_network_activated'))				$has_bronze_plug_actived = true;
	if (get_option(WPS_OPTIONS_PREFIX.'__wps__add_notification_bar_activated') 	|| get_option(WPS_OPTIONS_PREFIX.'__wps__add_notification_bar_network_activated'))	$has_bronze_plug_actived = true;
	if (get_option(WPS_OPTIONS_PREFIX.'__wps__events_main_activated') 			|| get_option(WPS_OPTIONS_PREFIX.'__wps__events_main_network_activated'))			$has_bronze_plug_actived = true;
	if (get_option(WPS_OPTIONS_PREFIX.'__wps__facebook_activated')				|| get_option(WPS_OPTIONS_PREFIX.'__wps__facebook_network_activated'))				$has_bronze_plug_actived = true;
	if (get_option(WPS_OPTIONS_PREFIX.'__wps__gallery_activated') 				|| get_option(WPS_OPTIONS_PREFIX.'__wps__gallery_network_activated'))				$has_bronze_plug_actived = true;
	if (get_option(WPS_OPTIONS_PREFIX.'__wps__groups_activated') 				|| get_option(WPS_OPTIONS_PREFIX.'__wps__groups_network_activated'))				$has_bronze_plug_actived = true;
	if (get_option(WPS_OPTIONS_PREFIX.'__wps__lounge_main_activated') 			|| get_option(WPS_OPTIONS_PREFIX.'__wps__lounge_main_network_activated')) 			$has_bronze_plug_actived = true;
	if (get_option(WPS_OPTIONS_PREFIX.'__wps__news_main_activated') 			|| get_option(WPS_OPTIONS_PREFIX.'__wps__news_main_network_activated'))				$has_bronze_plug_actived = true;
	if (get_option(WPS_OPTIONS_PREFIX.'__wps__profile_plus_activated') 			|| get_option(WPS_OPTIONS_PREFIX.'__wps__profile_plus_network_activated'))			$has_bronze_plug_actived = true;
	if (get_option(WPS_OPTIONS_PREFIX.'__wps__rss_main_activated') 				|| get_option(WPS_OPTIONS_PREFIX.'__wps__rss_main_network_activated'))				$has_bronze_plug_actived = true;
	if (get_option(WPS_OPTIONS_PREFIX.'__wps__mailinglist_activated') 			|| get_option(WPS_OPTIONS_PREFIX.'__wps__mailinglist_network_activated'))			$has_bronze_plug_actived = true;
	if (get_option(WPS_OPTIONS_PREFIX.'__wps__wysiwyg_activated') 				|| get_option(WPS_OPTIONS_PREFIX.'__wps__wysiwyg_network_activated'))				$has_bronze_plug_actived = true;

    return $has_bronze_plug_actived;
}

function __wps__bronze_countdown() {
	
	$saved_code = get_option(WPS_OPTIONS_PREFIX.'_activation_code');
	
	if (substr($saved_code,0,3) == 'vip' || substr($saved_code,0,3) == 'wps') {
		
		if (substr($saved_code,0,3) == 'vip') {
			return array(365, '<p>'.__('This is a lifetime activation code.', WPS_TEXT_DOMAIN).'</p>');
		} else {
			return array(365, '<p>'.__('This is a temporary activation code, it should not be used permenantly.', WPS_TEXT_DOMAIN).'</p>');
		}
		
	} else {

        $code = preg_replace('#[^0-9]#','',$saved_code);
        $diff = $code - time();
	
		$days = floor($diff / (60*60*24));
		$diff = $diff - ($days*60*60*24);
		
		$hours = floor($diff / (60*60));
		$diff = $diff - ($hours*60*60);
	
		$minutes = floor($diff / (60));
		$seconds = $diff - ($minutes*60);
		
		$minutes = $minutes > 9 ? $minutes : '0'.(string)$minutes;
		$seconds = $seconds > 9 ? $seconds : '0'.(string)$seconds;

		if ($days < 366) {
			return array($days, '<p>'.__('Your current "Bronze+" activation code expires on', WPS_TEXT_DOMAIN).' '.@date('l d F Y', $code).' ('.sprintf('%d days, %d:%s:%s', $days, $hours, $minutes, $seconds).')'.', '.__('<a href="http://www.wpsymposium.com/membership" target="_new">get a new activation code</a> before it runs out to reset for another 365 days.', WPS_TEXT_DOMAIN).'<br />'.sprintf(__('Note that this may not tie in with your %s expiry payment date.', WPS_TEXT_DOMAIN), WPS_WL).'</p>');
		} else {
			return array($days, '<p style="color:red">'.__('<strong>Invalid activation code</strong> - <a href="http://www.wpsymposium.com/membership" target="_new">please get a valid activation code</a>', WPS_TEXT_DOMAIN).'.</p>');
		}
	
		
	}

}
  
function __wps__get_monthname($month) {
	
	$monthname = '';
	switch($month) {									
		case 0:$monthname = "";break;
		case 1:$monthname = __("January", WPS_TEXT_DOMAIN);break;
		case 2:$monthname = __("February", WPS_TEXT_DOMAIN);break;
		case 3:$monthname = __("March", WPS_TEXT_DOMAIN);break;
		case 4:$monthname = __("April", WPS_TEXT_DOMAIN);break;
		case 5:$monthname = __("May", WPS_TEXT_DOMAIN);break;
		case 6:$monthname = __("June", WPS_TEXT_DOMAIN);break;
		case 7:$monthname = __("July", WPS_TEXT_DOMAIN);break;
		case 8:$monthname = __("August", WPS_TEXT_DOMAIN);break;
		case 9:$monthname = __("September", WPS_TEXT_DOMAIN);break;
		case 10:$monthname = __("October", WPS_TEXT_DOMAIN);break;
		case 11:$monthname = __("November", WPS_TEXT_DOMAIN);break;
		case 12:$monthname = __("December", WPS_TEXT_DOMAIN);break;
	}
	return $monthname;
}

function __wps__is_wpmu() {
    global $wpmu_version;
    if (function_exists('is_multisite'))
        if (is_multisite()) return true;
    if (!empty($wpmu_version)) return true;
    return false;
}

function __wps__get_forum_category_part_url($cat_id) {
	if (get_option(WPS_OPTIONS_PREFIX.'_permalinks_cats') && $cat_id) {
		global $wpdb;
		$sql = "select title from ".$wpdb->prefix."symposium_cats WHERE cid = %d";
		return __wps__create_stub($wpdb->get_var($wpdb->prepare($sql, $cat_id))).'/';
	} else {
		return '';
	}
}

function __wps__create_stub($text) {
	global $wpdb;
	$stub = preg_replace("/[^A-Za-z0-9 ]/",'',$text);
	$stub = strtolower(str_replace(' ', '-', $stub));
	$sql = "SELECT COUNT(*) FROM ".$wpdb->prefix."symposium_topics WHERE stub = '".$stub."'";
	$cnt = $wpdb->get_var($sql);
	if ($cnt > 0) $stub .= "-".$cnt;
	$stub = str_replace('--', '-', $stub);
	return $stub;
}

function __wps__get_stub_id($stub, $type) {
	global $wpdb;
	$id = false;

	switch($type) {	
	case 'forum-cat':
		$sql = "SELECT cid FROM ".$wpdb->prefix."symposium_cats WHERE stub = %s";
		$id = $wpdb->get_var($wpdb->prepare($sql, $stub));
		break;								
	case 'forum-topic':
		$sql = "SELECT tid FROM ".$wpdb->prefix."symposium_topics WHERE topic_parent = 0 AND stub = %s";
		$id = $wpdb->get_var($wpdb->prepare($sql, $stub));
		break;
	}	
	return $id;
}

function __wps__get_extension_button_style($add='') {
	// Check for profile extension
	$button_style = '';
	if (defined('WPS_EXT_PROFILE_NAME')) {
		if ($button_name_font = get_option('__wps__profile_'.WPS_EXT_PROFILE_NAME.'_button_name_font')) 
			$button_style .= "font-family: ".get_option('__wps__profile_'.WPS_EXT_PROFILE_NAME.'_button_name_font')." !important;";
		$button_style .= "font-size: ".get_option('__wps__profile_'.WPS_EXT_PROFILE_NAME.'_button_name_font_size')."px;";
		$button_style .= "background-color: ".get_option('__wps__profile_'.WPS_EXT_PROFILE_NAME.'_button_background_color_1').";";
		$button_style .= "color: ".get_option('__wps__profile_'.WPS_EXT_PROFILE_NAME.'_button_text_color').";";
		$button_style .= "border: 1px solid ".get_option('__wps__profile_'.WPS_EXT_PROFILE_NAME.'_button_background_color_b').";";
		$button_style .= "background-image: -moz-linear-gradient(center top, ".get_option('__wps__profile_'.WPS_EXT_PROFILE_NAME.'_button_background_color_1').", ".get_option('__wps__profile_'.WPS_EXT_PROFILE_NAME.'_button_background_color_2').") !important;";
		$button_style .= "background-image:  -webkit-linear-gradient(top, ".get_option('__wps__profile_'.WPS_EXT_PROFILE_NAME.'_button_background_color_1').", ".get_option('__wps__profile_'.WPS_EXT_PROFILE_NAME.'_button_background_color_2').") !important;";
		$button_style .= "background-image:  -moz-linear-gradient(top, ".get_option('__wps__profile_'.WPS_EXT_PROFILE_NAME.'_button_background_color_1').", ".get_option('__wps__profile_'.WPS_EXT_PROFILE_NAME.'_button_background_color_2').") !important;";
		$button_style .= "background-image:  linear-gradient(to bottom, ".get_option('__wps__profile_'.WPS_EXT_PROFILE_NAME.'_button_background_color_1').", ".get_option('__wps__profile_'.WPS_EXT_PROFILE_NAME.'_button_background_color_2').") !important;";
		$button_style .= $add;
	}
	return $button_style;	
}

function can_manage_forum() {
	global $wpdb,$current_user;
	$can_moderate = current_user_can('manage_options') ? true : false;
	if (!$can_moderate && is_user_logged_in()) {
		$user = get_userdata( $current_user->ID );
		$moderators = str_replace('_', '', str_replace(' ', '', strtolower(get_option(WPS_OPTIONS_PREFIX.'_moderators'))));
		$capabilities = $user->{$wpdb->base_prefix.'capabilities'};
		if ($capabilities) {
			foreach ( $capabilities as $role => $name ) {
				if ($role) {
					$role = strtolower($role);
					$role = str_replace(' ', '', $role);
					$role = str_replace('_', '', $role);
					if (WPS_DEBUG) $html .= 'Checking user role '.$role.' against '.$moderators.'<br />';
					if (strpos($moderators, $role) !== FALSE) $can_moderate = true;
				}
			}		 														
		} else {
			// No WordPress role stored
		}
	}	
	return $can_moderate;
}

function __wps__bbcode_toolbar($rel) {
	$new_topic_form = '';
	$icons = get_option(WPS_OPTIONS_PREFIX.'_use_bbcode_icons') ? strtolower(get_option(WPS_OPTIONS_PREFIX.'_use_bbcode_icons')) : '';
	if (get_option(WPS_OPTIONS_PREFIX.'_use_bbcode')) {
		$new_topic_form .= '<div class="__wps__toolbar">';
		if (strpos($icons, 'bold') !== FALSE) $new_topic_form .= '<div class="__wps__toolbar_bold" rel="'.$rel.'"></div>';
		if (strpos($icons, 'italic') !== FALSE) $new_topic_form .= '<div class="__wps__toolbar_italic" rel="'.$rel.'"></div>';
		if (strpos($icons, 'underline') !== FALSE) $new_topic_form .= '<div class="__wps__toolbar_underline" rel="'.$rel.'"></div>';
		if (strpos($icons, 'link') !== FALSE) $new_topic_form .= '<div class="__wps__toolbar_url" rel="'.$rel.'"></div>';
		if (strpos($icons, 'quote') !== FALSE) $new_topic_form .= '<div class="__wps__toolbar_quote" rel="'.$rel.'"></div>';
		if (strpos($icons, 'code') !== FALSE) $new_topic_form .= '<div class="__wps__toolbar_code" rel="'.$rel.'"></div>';
		$new_topic_form .= '</div>';	
	}
	return $new_topic_form;
}

?>
