<?php

/* ====================================================== ADMIN MENU ====================================================== */


function __wps__plugin_menu() {
	
	global $wpdb, $current_user;
		
	// Act on any parameters, so menu counts are correct
	if (isset($_GET['action'])) {
		
		switch($_GET['action']) {
			
			case "post_del":
				if (isset($_GET['tid'])) {

					if (__wps__safe_param($_GET['tid'])) {

						// Get details
						$post = $wpdb->get_row( $wpdb->prepare("SELECT t.*, u.user_email FROM ".$wpdb->prefix."symposium_topics t LEFT JOIN ".$wpdb->base_prefix."users u ON t.topic_owner = u.ID WHERE tid = %d", $_GET['tid']) );
	
						$body = "<span style='font-size:24px'>".__('Your forum post has been rejected by the moderator', WPS_TEXT_DOMAIN).".</span>";
						if ($post->topic_parent == 0) { $body .= "<p><strong>".stripslashes($post->topic_subject)."</strong></p>"; }
						$body .= "<p>".stripslashes($post->topic_post)."</p>";
						$body = str_replace(chr(13), "<br />", $body);
						$body = str_replace("\\r\\n", "<br />", $body);
						$body = str_replace("\\", "", $body);
							
						// Email author to let them know it was deleted
						if (get_option(WPS_OPTIONS_PREFIX.'_moderation_email_rejected') == "on")						
							__wps__sendmail($post->user_email, __('Forum Post Rejected', WPS_TEXT_DOMAIN), $body);

						// Update
						$wpdb->query( $wpdb->prepare( "DELETE FROM ".$wpdb->prefix."symposium_topics WHERE tid = %d", $_GET['tid'] ) );

					} else {
						echo "BAD PARAMETER PASSED: ".$_GET['tid'];
					}
					
				}
				break;

			case "post_approve":
				if (isset($_GET['tid'])) {

					$forum_url = __wps__get_url('forum');
					$group_url = __wps__get_url('group');
					$q = __wps__string_query($forum_url);		
					
					if (__wps__safe_param($_GET['tid'])) {

						// Update
						$wpdb->query( $wpdb->prepare( "UPDATE ".$wpdb->prefix."symposium_topics SET topic_approved = 'on' WHERE tid = %d", $_GET['tid'] ) );
						
						// Get details
						$post = $wpdb->get_row( $wpdb->prepare("SELECT t.*, u.user_email, u.display_name FROM ".$wpdb->prefix."symposium_topics t LEFT JOIN ".$wpdb->base_prefix."users u ON t.topic_owner = u.ID WHERE tid = %d", $_GET['tid']) );
	
						$body = "<span style='font-size:24px'>".__('Your forum post has been approved by the moderator', WPS_TEXT_DOMAIN).".</span>";
						if ($post->topic_parent == 0) { $body .= "<p><strong>".stripslashes($post->topic_subject)."</strong></p>"; }
						$body .= "<p>".stripslashes($post->topic_post)."</p>";
						$url = $forum_url.$q."cid=".$post->topic_category."&show=".$_GET['tid'];
						$body .= "<p><a href='".$url."'>".$url."</a></p>";
						$body = str_replace(chr(13), "<br />", $body);
						$body = str_replace("\\r\\n", "<br />", $body);
						$body = str_replace("\\", "", $body);
						
						// Work out URL
						$parent = $wpdb->get_row("SELECT tid, topic_subject FROM ".$wpdb->prefix."symposium_topics WHERE tid = ".$post->topic_parent);
						if ($post->topic_group == 0) {	

							if ($post->topic_parent == 0) {					
								$url = $forum_url.$q."cid=".$post->topic_category."&show=".$_GET['tid'];
							} else {
								$url = $forum_url.$q."cid=".$post->topic_category."&show=".$parent->tid;
							}	
						
						} else {
							
							if ($post->topic_parent == 0) {					
								$url = $group_url.$q."gid=".$post->topic_group."&cid=".$post->topic_category."&show=".$_GET['tid'];
							} else {
								$url = $group_url.$q."gid=".$post->topic_group."&cid=".$post->topic_category."&show=".$parent->tid;
							}							
						
						}						

						// Email author to let them know
						if (get_option(WPS_OPTIONS_PREFIX.'_moderation_email_accepted') == "on")						
							__wps__sendmail($post->user_email, __('Forum Post Approved', WPS_TEXT_DOMAIN), $body);
	
						// Email people who want to know and prepare body (and post activity comment)
						if ($post->topic_parent > 0) {						
							$body = "<span style='font-size:24px'>".$parent->topic_subject."</span><br /><br />";
							$body .= "<p>".$post->display_name." ".__('replied', WPS_TEXT_DOMAIN)."...</p>";
						} else {
							$body = "<span style='font-size:24px'>".$post->topic_subject."</span><br /><br />";
							$body .= "<p>".$post->display_name." ".__('started', WPS_TEXT_DOMAIN)."...</p>";
							$post_url = __('Started a new forum topic:', WPS_TEXT_DOMAIN).' <a href="'.$url.'">'.$post->topic_subject.'</a>';
							do_action('__wps__forum_newtopic_hook', $post->topic_owner, $post->display_name, $post->topic_owner, $post_url, 'forum', $_GET['tid']);	
						}
						
						$body .= "<p>".$post->topic_post."</p>";
						$body .= "<p>".$url."</p>";
						$body = str_replace(chr(13), "<br />", $body);
						$body = str_replace("\\r\\n", "<br />", $body);
						$body = str_replace("\\", "", $body);

						$email_list = '0,';				
						if ($post->topic_group == 0) {	
							
							// Main Forum			
												
							if ($post->topic_parent > 0) {
								$query = $wpdb->get_results("
									SELECT u.ID, u.user_email
									FROM ".$wpdb->base_prefix."users u RIGHT JOIN ".$wpdb->prefix."symposium_subs s ON s.uid = u.ID 
									WHERE tid = ".$parent->tid);
							} else {
								$query = $wpdb->get_results("
									SELECT u.ID, u.user_email
									FROM ".$wpdb->base_prefix."users u RIGHT JOIN ".$wpdb->prefix."symposium_subs s ON s.uid = u.ID 
									WHERE cid = ".$post->topic_category);
							}
														
							if ($query) {						
								foreach ($query as $user) {		
									// Filter to allow further actions to take place
									if ($post->topic_parent > 0) {
										apply_filters ('__wps__forum_newreply_filter', $user->ID, $post->topic_owner, $post->display_name, $url);								
									} else {
										apply_filters ('__wps__forum_newtopic_filter', $user->ID, $post->topic_owner, $post->display_name, $url);
									}										

									// Keep track of who sent to so far
									$email_list .= $user->ID.',';

									__wps__sendmail($user->user_email, __('New Forum Post', WPS_TEXT_DOMAIN), $body);							
								}
							}
							
						} else {
							
							// Group Forum
							$group_name = $wpdb->get_var($wpdb->prepare("SELECT name FROM ".$wpdb->base_prefix."symposium_groups WHERE gid = %d", $post->topic_group));
			
							$sql = "SELECT ID, user_email FROM ".$wpdb->base_prefix."users u 
							LEFT JOIN ".$wpdb->prefix."symposium_group_members g ON u.ID = g.member_id 
							WHERE u.ID > 0 AND g.group_id = %d AND u.ID != %d";
			
							$members = $wpdb->get_results($wpdb->prepare($sql, $post->topic_group, $current_user->ID));
			
							if ($members) {
								foreach ($members as $member) {
									if ($post->topic_parent > 0) {
										apply_filters ('__wps__forum_newreply_filter', $member->ID, $post->topic_owner, $post->display_name, $url);								
									} else {
										apply_filters ('__wps__forum_newtopic_filter', $member->ID, $post->topic_owner, $post->display_name, $url);
									}										

									// Keep track of who sent to so far
									$email_list .= $member->ID.',';

									__wps__sendmail($member->user_email, __('New Group Forum Post', WPS_TEXT_DOMAIN), $body);							
								}
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
						$level = $wpdb->get_var($wpdb->prepare($sql, $post->topic_category));
						$cat_roles = unserialize($level);
							
						if ($query) {						
							foreach ($query as $user) {	

								// Get role of recipient user
								$the_user = get_userdata( $user->ID );
								$capabilities = $the_user->{$wpdb->prefix . 'capabilities'};
		
								if ( !isset( $wp_roles ) )
									$wp_roles = new WP_Roles();
									
								$user_role = 'NONE';
								foreach ( $wp_roles->role_names as $role => $name ) {
									
									if ( array_key_exists( $role, $capabilities ) ) {
										$user_role = $role;
									}
								}
								
								// Check in this topics category level
								if (strpos(strtolower($cat_roles), 'everyone,') !== FALSE || strpos(strtolower($cat_roles), $user_role.',') !== FALSE) {	 
		
									// Filter to allow further actions to take place
									apply_filters ('__wps__forum_newreply_filter', $user->ID, $current_user->ID, $current_user->display_name, $url);

									// Send mail
									__wps__sendmail($user->user_email, __('New Forum Post', WPS_TEXT_DOMAIN), $body);							
									
								}
							}
						}
											
					} else {
						echo "BAD PARAMETER PASSED: ".$_GET['tid'];
					}

				}
				break;

		}
	}

	if (!WPS_HIDE_PLUGINS) {

		// Build menu
		$count = $wpdb->get_var("SELECT COUNT(*) FROM ".$wpdb->prefix.'symposium_topics'." WHERE topic_approved != 'on'"); 
		if ($count > 0) {
			$count1 = "<span class='update-plugins' title='".$count." comments to moderate'><span class='update-count'>".$count."</span></span>";
			$count2 = " (".$count.")";
		} else {
			$count1 = "";
			$count2 = "";
		}
	
		// Aggregate menu items?
		$hidden = get_option(WPS_OPTIONS_PREFIX.'_long_menu') == "on" ? '_hidden': '';
	
		// Build menus
		if (__wps__is_wpmu()) {
			// WPMS
			add_menu_page(WPS_WL_SHORT,WPS_WL_SHORT.$count1, 'manage_options', 'symposium_debug', '__wps__plugin_debug', 'none'); 
			add_submenu_page('symposium_debug', __('Installation', WPS_TEXT_DOMAIN), __('Installation', WPS_TEXT_DOMAIN), 'manage_options', 'symposium_debug', '__wps__plugin_debug');
			add_submenu_page('symposium_debug', __('Welcome', WPS_TEXT_DOMAIN), __('Welcome', WPS_TEXT_DOMAIN), 'manage_options', 'symposium_welcome', '__wps__plugin_welcome');
			add_submenu_page('symposium_debug'.$hidden, __('Settings', WPS_TEXT_DOMAIN), __('Settings', WPS_TEXT_DOMAIN), 'manage_options', 'symposium_settings', '__wps__plugin_settings');
			add_submenu_page('symposium_debug'.$hidden, __('Advertising', WPS_TEXT_DOMAIN), __('Advertising', WPS_TEXT_DOMAIN), 'manage_options', 'symposium_advertising', '__wps__plugin_advertising');
			add_submenu_page('symposium_debug'.$hidden, __('Thesaurus', WPS_TEXT_DOMAIN), __('Thesaurus', WPS_TEXT_DOMAIN), 'manage_options', 'symposium_thesaurus', '__wps__plugin_thesaurus');
			add_submenu_page('symposium_debug'.$hidden, __('Templates', WPS_TEXT_DOMAIN), __('Templates', WPS_TEXT_DOMAIN), 'manage_options', 'symposium_templates', '__wps__plugin_templates');
			if (get_option(WPS_OPTIONS_PREFIX.'_audit') == "on") 
				add_submenu_page('symposium_debug'.$hidden, __('Audit', WPS_TEXT_DOMAIN), __('Audit', WPS_TEXT_DOMAIN), 'manage_options', 'symposium_audit', '__wps__plugin_audit');
	
			// Aggregate menu items
			if (get_option(WPS_OPTIONS_PREFIX.'_long_menu') == "on") {
				add_submenu_page('symposium_debug', __('Options', WPS_TEXT_DOMAIN), __('Options', WPS_TEXT_DOMAIN), 'manage_options', 'symposium_options', '__wps__menu_options');
				add_submenu_page('symposium_debug', __('Manage', WPS_TEXT_DOMAIN), __('Manage', WPS_TEXT_DOMAIN), 'manage_options', 'symposium_manage', '__wps__menu_manage');
				if ($count2) add_submenu_page('symposium_debug', __('Moderate', WPS_TEXT_DOMAIN), sprintf(__('Moderate%s', WPS_TEXT_DOMAIN), $count1), 'manage_options', 'symposium_moderation', '__wps__plugin_moderation');
			}
			add_submenu_page('symposium_debug', __('Styles', WPS_TEXT_DOMAIN), __('Styles', WPS_TEXT_DOMAIN), 'manage_options', 'symposium_styles', '__wps__plugin_styles');
			
			if (function_exists('__wps__profile')) {
				add_submenu_page('symposium_debug'.$hidden, __('Profile', WPS_TEXT_DOMAIN), __('Profile', WPS_TEXT_DOMAIN), 'manage_options', 'symposium_profile', '__wps__plugin_profile');
			}
			if (function_exists('__wps__forum')) {
				if (!current_user_can('manage_options')) {
					// Not an administrator, so check if Forum Moderation menu item should be shown
					$can_moderate = false;
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
					if ($can_moderate)
						add_menu_page('Moderation','Moderation'.$count1, 'read', 'symposium_moderation', '__wps__plugin_moderation', plugin_dir_url( __FILE__ ).'/images/logo_admin_icon.png', 8); 
				}
				add_submenu_page('symposium_debug'.$hidden, __('Forum', WPS_TEXT_DOMAIN), __('Forum', WPS_TEXT_DOMAIN), 'manage_options', 'symposium_forum', '__wps__plugin_forum');
				add_submenu_page('symposium_debug'.$hidden, __('Forum Categories', WPS_TEXT_DOMAIN), __('Forum Categories', WPS_TEXT_DOMAIN), 'manage_options', 'symposium_categories', '__wps__plugin_categories');
				add_submenu_page('symposium_debug'.$hidden, __('Forum Posts', WPS_TEXT_DOMAIN), sprintf(__('Forum Posts %s', WPS_TEXT_DOMAIN), $count2), 'manage_options', 'symposium_moderation', '__wps__plugin_moderation');
			}
			if (function_exists('__wps__add_notification_bar')) {
				add_submenu_page('symposium_debug'.$hidden, __('Panel', WPS_TEXT_DOMAIN), __('Panel', WPS_TEXT_DOMAIN), 'manage_options', 'symposium_bar', '__wps__plugin_bar');
			}
			if (function_exists('__wps__members')) {
				add_submenu_page('symposium_debug'.$hidden, __('Member Directory', WPS_TEXT_DOMAIN), __('Member Directory', WPS_TEXT_DOMAIN), 'manage_options', '__wps__members_menu', '__wps__members_menu');
			}
			if (function_exists('__wps__mail')) {
				add_submenu_page('symposium_debug'.$hidden, __('Mail', WPS_TEXT_DOMAIN), __('Mail', WPS_TEXT_DOMAIN), 'update_core', '__wps__mail_menu', '__wps__mail_menu');
				add_submenu_page('symposium_debug'.$hidden, __('Mail Messages', WPS_TEXT_DOMAIN), __('Mail Messages', WPS_TEXT_DOMAIN), 'update_core', '__wps__mail_messages_menu', '__wps__mail_messages_menu');
			}
		} else {
			// Single intallation
			add_menu_page(WPS_WL_SHORT,WPS_WL_SHORT.$count1, 'manage_options', 'symposium_debug', '__wps__plugin_debug', 'none'); 
			add_submenu_page('symposium_debug', __('Installation', WPS_TEXT_DOMAIN), __('Installation', WPS_TEXT_DOMAIN), 'manage_options', 'symposium_debug', '__wps__plugin_debug');
			add_submenu_page('symposium_debug', __('Welcome', WPS_TEXT_DOMAIN), __('Welcome', WPS_TEXT_DOMAIN), 'manage_options', 'symposium_welcome', '__wps__plugin_welcome');
			add_submenu_page('symposium_debug'.$hidden, __('Settings', WPS_TEXT_DOMAIN), __('Settings', WPS_TEXT_DOMAIN), 'manage_options', 'symposium_settings', '__wps__plugin_settings');
			add_submenu_page('symposium_debug'.$hidden, __('Advertising', WPS_TEXT_DOMAIN), __('Advertising', WPS_TEXT_DOMAIN), 'manage_options', 'symposium_advertising', '__wps__plugin_advertising');
			add_submenu_page('symposium_debug'.$hidden, __('Thesaurus', WPS_TEXT_DOMAIN), __('Thesaurus', WPS_TEXT_DOMAIN), 'manage_options', 'symposium_thesaurus', '__wps__plugin_thesaurus');
			add_submenu_page('symposium_debug'.$hidden, __('Templates', WPS_TEXT_DOMAIN), __('Templates', WPS_TEXT_DOMAIN), 'manage_options', 'symposium_templates', '__wps__plugin_templates');
			if (get_option(WPS_OPTIONS_PREFIX.'_audit') == "on") 
				add_submenu_page('symposium_debug'.$hidden, __('Audit', WPS_TEXT_DOMAIN), __('Audit', WPS_TEXT_DOMAIN), 'manage_options', 'symposium_audit', '__wps__plugin_audit');
			
			// Aggregate menu items
			if (get_option(WPS_OPTIONS_PREFIX.'_long_menu') == "on") {
				add_submenu_page('symposium_debug', __('Options', WPS_TEXT_DOMAIN), __('Options', WPS_TEXT_DOMAIN), 'manage_options', 'symposium_options', '__wps__menu_options');
				add_submenu_page('symposium_debug', __('Manage', WPS_TEXT_DOMAIN), __('Manage', WPS_TEXT_DOMAIN), 'manage_options', 'symposium_manage', '__wps__menu_manage');
				if ($count2) add_submenu_page('symposium_debug', __('Moderate', WPS_TEXT_DOMAIN), sprintf(__('Moderate%s', WPS_TEXT_DOMAIN), $count1), 'manage_options', 'symposium_moderation', '__wps__plugin_moderation');
			}
	
			add_submenu_page('symposium_debug', __('Styles', WPS_TEXT_DOMAIN), __('Styles', WPS_TEXT_DOMAIN), 'manage_options', 'symposium_styles', '__wps__plugin_styles');
			
			if (function_exists('__wps__profile')) {
				add_submenu_page('symposium_debug'.$hidden, __('Profile', WPS_TEXT_DOMAIN), __('Profile', WPS_TEXT_DOMAIN), 'manage_options', 'symposium_profile', '__wps__plugin_profile');
			}
			if (function_exists('__wps__forum')) {
				if (!current_user_can('manage_options')) {
					// Not an administrator, so check if Forum Moderation menu item should be shown
					$can_moderate = false;
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
					if ($can_moderate)
						add_menu_page('Moderation','Moderation'.$count1, 'read', 'symposium_moderation', '__wps__plugin_moderation', plugin_dir_url( __FILE__ ).'/images/logo_admin_icon.png', 8); 
				}
				add_submenu_page('symposium_debug'.$hidden, __('Forum', WPS_TEXT_DOMAIN), __('Forum', WPS_TEXT_DOMAIN), 'manage_options', 'symposium_forum', '__wps__plugin_forum');
				add_submenu_page('symposium_debug'.$hidden, __('Forum Categories', WPS_TEXT_DOMAIN), __('Forum Categories', WPS_TEXT_DOMAIN), 'manage_options', 'symposium_categories', '__wps__plugin_categories');
				add_submenu_page('symposium_debug'.$hidden, __('Forum Posts', WPS_TEXT_DOMAIN), sprintf(__('Forum Posts %s', WPS_TEXT_DOMAIN), $count2), 'manage_options', 'symposium_moderation', '__wps__plugin_moderation');
			}
			if (function_exists('__wps__add_notification_bar')) {
				add_submenu_page('symposium_debug'.$hidden, __('Panel', WPS_TEXT_DOMAIN), __('Panel', WPS_TEXT_DOMAIN), 'manage_options', 'symposium_bar', '__wps__plugin_bar');
			}
			if (function_exists('__wps__members')) {
				add_submenu_page('symposium_debug'.$hidden, __('Member Directory', WPS_TEXT_DOMAIN), __('Member Directory', WPS_TEXT_DOMAIN), 'manage_options', '__wps__members_menu', '__wps__members_menu');
			}
			if (function_exists('__wps__mail')) {
				add_submenu_page('symposium_debug'.$hidden, __('Mail', WPS_TEXT_DOMAIN), __('Mail', WPS_TEXT_DOMAIN), 'manage_options', '__wps__mail_menu', '__wps__mail_menu');
				add_submenu_page('symposium_debug'.$hidden, __('Mail Messages', WPS_TEXT_DOMAIN), __('Mail Messages', WPS_TEXT_DOMAIN), 'manage_options', '__wps__mail_messages_menu', '__wps__mail_messages_menu');
			}
		}
		do_action('__wps__admin_menu_hook');
	}
}

function __wps__menu_options() {
  	echo '<div class="wrap">';
  	echo '<div id="icon-themes" class="icon32"><br /></div>';
  	echo '<h2>'.sprintf(__('%s Options', WPS_TEXT_DOMAIN), WPS_WL).'</h2><br />';

	__wps__show_tabs_header('options');
	
	echo '<table class="form-table __wps__admin_table"><tr><td>';
	
	$show = '';
	if (function_exists('__wps__profile')) $show .= '<li><a href="admin.php?page=symposium_profile">'.__('Member Profile', WPS_TEXT_DOMAIN).'</a></li>';
	if (function_exists('__wps__forum')) $show .= '<li><a href="admin.php?page=symposium_forum">'.__('Forum', WPS_TEXT_DOMAIN).'</a></li>';
	if (function_exists('__wps__members')) $show .= '<li><a href="admin.php?page=__wps__members_menu">'.__('Member Directory', WPS_TEXT_DOMAIN).'</a></li>';
	if (function_exists('__wps__mail')) $show .= '<li><a href="admin.php?page=__wps__mail_menu">'.sprintf(__('Private %s mail', WPS_TEXT_DOMAIN), WPS_WL).'</a></li>';
	
	if ($show) {
		echo '<h2>'.__('Options Tabs', WPS_TEXT_DOMAIN).'</h2>';
		echo '<p><em>'.__('The relevant feature has to be <a href="admin.php?page=symposium_debug">activated</a>, for its tab above to appear.', WPS_TEXT_DOMAIN).'</em></p>';	
		echo '<ul style="list-style-type: square; margin: 10px 0 10px 30px;">';
		echo $show;
		if (function_exists('__wps__forum')) echo '<li><a href="admin.php?page=symposium_categories">'.sprintf(__('Manage Forum Categories', WPS_TEXT_DOMAIN), WPS_WL).'</a></li>';
		if (function_exists('__wps__forum')) echo '<li><a href="admin.php?page=symposium_moderation">'.sprintf(__('Manage Forum Posts', WPS_TEXT_DOMAIN), WPS_WL).'</a></li>';
		echo '</ul>';
	}

	$show2 = '';
	if (function_exists('__wps__add_notification_bar')) $show2 .= '<li><a href="admin.php?page=symposium_bar">'.__('Panel (notification bar/chat)', WPS_TEXT_DOMAIN).'</a></li>';
	if (function_exists('__wps__profile_plus')) $show2 .= '<li><a href="admin.php?page='.WPS_DIR.'/plus_admin.php">'.__('Additional profile related options', WPS_TEXT_DOMAIN).'</a></li>';
	if (function_exists('__wps__group')) $show2 .= '<li><a href="admin.php?page='.WPS_DIR.'/gallery_admin.php">'.__('Gallery', WPS_TEXT_DOMAIN).'</a></li>';
	if (function_exists('__wps__gallery')) $show2 .= '<li><a href="admin.php?page='.WPS_DIR.'/groups_admin.php">'.sprintf(__('%s Groups', WPS_TEXT_DOMAIN), WPS_WL).'</a></li>';
	if (function_exists('__wps__news_main')) $show2 .= '<li><a href="admin.php?page='.WPS_DIR.'/news_admin.php">'.__('Alerts', WPS_TEXT_DOMAIN).'</a></li>';
	if (function_exists('__wps__events_main')) $show2 .= '<li><a href="admin.php?page='.WPS_DIR.'/events_admin.php">'.sprintf(__('%s Events', WPS_TEXT_DOMAIN), WPS_WL).'</a></li>';
	if (function_exists('__wps__facebook')) $show2 .= '<li><a href="admin.php?page='.WPS_DIR.'/facebook_admin.php">'.__('Post profile messages to Facebook', WPS_TEXT_DOMAIN).'</a></li>';
	if (function_exists('__wps__mailinglist')) $show2 .= '<li><a href="admin.php?page='.WPS_DIR.'/mailinglist_admin.php">'.__('Reply to forum topics and replies by email', WPS_TEXT_DOMAIN).'</a></li>';
	if (function_exists('__wps__lounge_main')) $show2 .= '<li><a href="admin.php?page='.WPS_DIR.'/lounge_admin.php">'.__('The Lounge options (demonstrator)', WPS_TEXT_DOMAIN).'</a></li>';
	if (function_exists('__wps__mobile')) $show2 .= '<li><a href="admin.php?page=__wps__mobile_menu">'.__('Access for mobile devices', WPS_TEXT_DOMAIN).'</a></li>';

	if ($show2) {
		echo '<h2>'.__('Bronze Member Feature Tabs', WPS_TEXT_DOMAIN).'</h2>';
		echo '<ul style="list-style-type: square; margin: 10px 0 10px 30px;">';
		echo $show2;
		echo '</ul>';
	}
	
	if (!$show && !$show2) {
		echo '<h2>'.sprintf(__('Activate some %s plugins', WPS_TEXT_DOMAIN), WPS_WL).'</h2>';
		echo '<p>'.sprintf(__('For the relevant option tabs to appear above, <a href="admin.php?page=symposium_debug">activate</a> some %s features.', WPS_TEXT_DOMAIN), WPS_WL).'</p>';
	}
	
	echo '</td></tr></table>';	
	
	__wps__show_tabs_header_end();
}

function __wps__menu_manage() {
  	echo '<div class="wrap">';
  	echo '<div id="icon-themes" class="icon32"><br /></div>';
  	echo '<h2>'.sprintf(__('%s Management', WPS_TEXT_DOMAIN), WPS_WL).'</h2><br />';

	__wps__show_manage_tabs_header('manage');
	
	echo '<table class="form-table __wps__admin_table"><tr><td>';
	
	echo '<h2>'.__('Management Tabs', WPS_TEXT_DOMAIN).'</h2>';
	echo '<ul style="list-style-type: square; margin: 10px 0 10px 30px;">';
	echo '<li><a href="admin.php?page=symposium_settings">'.sprintf(__('Overall settings for %s', WPS_TEXT_DOMAIN), WPS_WL).'</a></li>';
	echo '<li><a href="admin.php?page=symposium_advertising">'.sprintf(__('Optional advertising blocks', WPS_TEXT_DOMAIN), WPS_WL).'</a></li>';
	echo '<li><a href="admin.php?page=symposium_thesaurus">'.sprintf(__('Wording alternatives %s', WPS_TEXT_DOMAIN), WPS_WL).'</a></li>';
	if (function_exists('__wps__forum')) echo '<li><a href="admin.php?page=symposium_categories">'.sprintf(__('%s forum categories and permissions', WPS_TEXT_DOMAIN), WPS_WL).'</a></li>';
	if (function_exists('__wps__forum')) echo '<li><a href="admin.php?page=symposium_moderation">'.sprintf(__('View and moderation %s forum topics and replies', WPS_TEXT_DOMAIN), WPS_WL).'</a></li>';
	if (function_exists('__wps__mail')) echo '<li><a href="admin.php?page=__wps__mail_messages_menu">'.sprintf(__('View and delete %s mail messages', WPS_TEXT_DOMAIN), WPS_WL).'</a></li>';
	echo '<li><a href="admin.php?page=symposium_templates">'.sprintf(__('%s layout templates', WPS_TEXT_DOMAIN), WPS_WL).'</a></li>';
	if (get_option(WPS_OPTIONS_PREFIX.'_audit') == "on") echo '<li><a href="admin.php?page=symposium_audit">'.__('Analyse the audit table', WPS_TEXT_DOMAIN).'</a></li>';
	echo '</ul>';

	echo '<h2>'.__('User Management', WPS_TEXT_DOMAIN).'</h2>';
	echo '<strong>'.__('Delete user', WPS_TEXT_DOMAIN).'</strong><br />';
	
	// Delete user, step 1
	if ( (isset($_POST['delete_wps_user'])) && ($_POST['delete_wps_user'] != '') ) {
        $u = sanitize_text_field($_POST['delete_wps_user']);
        $u =  preg_replace('#[^0-9]#','',$u);

		if (is_numeric($u)) {
			$id = $u;
			$user_info = get_userdata($id);
			$user_login = $user_info->user_login;
		} else {
			$user_info = get_user_by('login', 'nobody');
			$id = $user_info->ID;
			$user_login = $u;
		}
		if ($id && $user_login) {
			$user_info = get_user_by('login', 'nobody');
			if ($user_info) {

				echo sprintf(__('Mail, events, galleries and chat will be deleted. Forum posts and activity will be re-assigned to "nobody".', WPS_TEXT_DOMAIN), $user_login, $id).'<br />';

				echo '<form action="" method="POST">';	
					echo '<input name="delete_wps_user_id" type="hidden" value="'.$id.'" />';
					echo '<input name="delete_wps_user_transfer" type="hidden" value="'.$user_info->ID.'" />';
					echo '<input type="submit" class="button-primary" value="'.sprintf(__('Re-assign/delete content and remove %s', WPS_TEXT_DOMAIN), $user_login).'" />';
				echo '</form>';
			} else {
				echo '<em>'.__('First <a href="user-new.php">create a user</a> with the username "nobody", email "nobody@example.com" and set the firstname field to something like "Member no longer exists".', WPS_TEXT_DOMAIN).'</em><br />';
			}

			echo '<br /><br />';
		} else {
			echo '<span style="color:red; font-weight:bold">'.sprintf(__('User %s (ID %d) could not be found.', WPS_TEXT_DOMAIN), $user_login, $id).'</span><br /><br />';
		}
	} 

	// Delete user, step 2
	if ( (isset($_POST['delete_wps_user_id'])) && ($_POST['delete_wps_user_id'] != '') ) {
		$id = $_POST['delete_wps_user_id'];
		$to = $_POST['delete_wps_user_transfer'];
		$user_info = get_userdata($id);
		$deleting = $user_info->user_login;
		$user_info = get_userdata($to);
		$transfer = $user_info->user_login;
		echo sprintf(__('Deleting %s and re-assigning to %s...', WPS_TEXT_DOMAIN), $deleting, $transfer).' ';

		global $wpdb;

		// Delete mail
		$sql = "DELETE FROM ".$wpdb->prefix."symposium_mail WHERE mail_from=%d OR mail_to=%d";
		$wpdb->query($wpdb->prepare($sql, $id, $id));

		// Delete events
		$sql = "SELECT * FROM ".$wpdb->prefix."symposium_events WHERE event_owner=%d";
		$events = $wpdb->get_results($wpdb->prepare($sql, $id));
		foreach ($events as $event) {
			$sql = "DELETE FROM ".$wpdb->prefix."symposium_events_bookings WHERE event_id=%d";
			$wpdb->query($wpdb->prepare($sql, $event->eid));		
		}
		$sql = "DELETE FROM ".$wpdb->prefix."symposium_events WHERE event_owner=%d";
		$wpdb->query($wpdb->prepare($sql, $id));
		
		// Delete galleries
		$sql = "DELETE FROM ".$wpdb->prefix."symposium_gallery WHERE owner=%d";
		$wpdb->query($wpdb->prepare($sql, $id));
		$sql = "DELETE FROM ".$wpdb->prefix."symposium_gallery_items WHERE owner=%d";
		$wpdb->query($wpdb->prepare($sql, $id));

		// Delete friendships
		$sql = "DELETE FROM ".$wpdb->prefix."symposium_friends WHERE friend_from=%d OR friend_to=%d";
		$wpdb->query($wpdb->prepare($sql, $id, $id));

		// Delete followers
		$sql = "DELETE FROM ".$wpdb->prefix."symposium_following WHERE uid=%d OR following=%d";
		$wpdb->query($wpdb->prepare($sql, $id, $id));

		// Delete likes
		$sql = "DELETE FROM ".$wpdb->prefix."symposium_likes WHERE uid=%d";
		$wpdb->query($wpdb->prepare($sql, $id));

		// Delete chat
		$sql = "DELETE FROM ".$wpdb->prefix."symposium_chat_2 WHERE from_id=%d OR to_id=%d";
		$wpdb->query($wpdb->prepare($sql, $id, $id));
		$sql = "DELETE FROM ".$wpdb->prefix."symposium_chat_2_users WHERE id=%d";
		$wpdb->query($wpdb->prepare($sql, $id));
		$sql = "DELETE FROM ".$wpdb->prefix."symposium_chat_2_typing WHERE typing_from=%d OR typing_to=%d";
		$wpdb->query($wpdb->prepare($sql, $id, $id));

		// Delete from lounge plugin
		$sql = "DELETE FROM ".$wpdb->prefix."symposium_lounge WHERE author=%d";
		$wpdb->query($wpdb->prepare($sql, $id));

		// Delete from news
		$sql = "DELETE FROM ".$wpdb->prefix."symposium_news WHERE author=%d OR subject=%d";
		$wpdb->query($wpdb->prepare($sql, $id, $id));

		// Delete from subscriptions
		$sql = "DELETE FROM ".$wpdb->prefix."symposium_subs WHERE uid=%d";
		$wpdb->query($wpdb->prepare($sql, $id));

		// Delete from scores in forum, before re-assigning
		$sql = "DELETE FROM ".$wpdb->prefix."symposium_topics_scores WHERE uid=%d";
		$wpdb->query($wpdb->prepare($sql, $id));

		// Re-assign comments
		$sql = "UPDATE ".$wpdb->prefix."symposium_comments SET subject_uid=%d WHERE subject_uid=%d";
		$wpdb->query($wpdb->prepare($sql, $to, $id));
		$sql = "UPDATE ".$wpdb->prefix."symposium_comments SET author_uid=%d WHERE author_uid=%d";
		$wpdb->query($wpdb->prepare($sql, $to, $id));
		
		// Re-assign forum posts and images
		$sql = "UPDATE ".$wpdb->prefix."symposium_topics SET topic_owner=%d WHERE topic_owner=%d";
		$wpdb->query($wpdb->prepare($sql, $to, $id));
		$sql = "UPDATE ".$wpdb->prefix."symposium_topics SET uid=%d WHERE uid=%d";
		$wpdb->query($wpdb->prepare($sql, $to, $id));
		
		// Delete WPS user
		$sql = "DELETE FROM ".$wpdb->prefix."symposium_usermeta WHERE uid=%d";
		$wpdb->query($wpdb->prepare($sql, $id));

		// Delete WordPress user
		wp_delete_user( $id );

		echo __('done.', WPS_TEXT_DOMAIN);
		echo '<br /><br />';
	}

	echo __('Enter a username, or user ID:', WPS_TEXT_DOMAIN).'<br />';
	echo '<form action="" method="POST">';
	echo '<input type="text" name="delete_wps_user" />&nbsp;';
	echo '<input type="submit" class="button-primary" value="'.__('Find user', WPS_TEXT_DOMAIN).'" /><br />';
	echo '</form>';

	echo '</td></tr></table>';	
	
	__wps__show_manage_tabs_header_end();
}

function __wps__plugin_welcome() {

	update_option(WPS_OPTIONS_PREFIX.'_motd', '');

	if ($file = @file_get_contents(WPS_WELCOME_MESSAGE)) {
		// WPS_WELCOME_MESSAGE should defined in default-constants.php and is an absolute local path and filename
		echo $file;

	} else {
	
		?>
		<div id="wps-welcome-panel" class="welcome-panel" style="background-image: none; background-color: #dfd; margin: 30px 20px 0 0">
			<div id="motd" class="welcome-panel-content">

			    <h3><?php echo WPS_WL; ?></h3>		    
			    
				<p class="about-description">
				<?php echo sprintf(__( 'Thank you for installing %s v%s, welcome aboard! Go ahead and visit the <a href="%s">Installation page</a> to complete your installation/upgrade.', WPS_TEXT_DOMAIN ), WPS_WL, WPS_VER, "admin.php?page=symposium_debug"); ?>
			    <?php
			    $ver = str_replace('.', '-', WPS_VER);
			    if (strpos($ver, ' ') !== false) $ver = substr($ver, 0, strpos($ver, ' ')); 
			    echo '<br />'.sprintf(__('Please always read the <a href="%s" target="_blank">release notes</a> before upgrading %s.', WPS_TEXT_DOMAIN), 'http://www.wpsymposium.com/category/releases/', WPS_WL);
				echo ' '.__( 'And remember, drink tea, tea is good.' );
				echo ' <img style="width:20px;height:20px" src="'.get_option(WPS_OPTIONS_PREFIX.'_images').'/smilies/coffee.png" alt="Tea is good" />';
				echo '<br /><br />'.sprintf(__('If this is your first time with %s, the TwentyTen theme is probably best for now, <a href="%s">change it here</a>.', WPS_TEXT_DOMAIN), WPS_WL, 'themes.php');
				?>
			    </p>
	
				<div class="welcome-panel-column-container">
					<div class="welcome-panel-column" style="margin-left:5px;margin-right:-5px;width:33%;">
						<h4><?php _e( 'Getting Started' ); ?></h4>
						<ul>
							<li><?php echo sprintf( __( '<a class="welcome-icon welcome-view-site" target="_blank" href="%s">%s</a>' ), "http://www.wpsymposium.com/get-started", __('Getting started', WPS_TEXT_DOMAIN) ); ?></li>
							<li><?php echo sprintf( __( '<a class="welcome-icon welcome-add-page" href="%s">%s</a>' ), esc_url( admin_url('admin.php?page=symposium_debug') ), __('Activate some features', WPS_TEXT_DOMAIN) ); ?></li>
							<li><?php echo sprintf( __( '<a class="welcome-icon welcome-add-page" href="%s">%s</a>' ), esc_url( admin_url('admin.php?page=symposium_debug') ), __('Add to your site pages', WPS_TEXT_DOMAIN) ); ?></li>
							<li><?php echo sprintf( __( '<a class="welcome-icon welcome-widgets-menus" href="%s">%s</a>' ), esc_url( admin_url('admin.php?page=symposium_settings') ), __('Check your settings', WPS_TEXT_DOMAIN) ); ?></li>
							<li><?php echo sprintf( __( '<a class="welcome-icon welcome-write-blog" href="%s">%s</a>' ), esc_url( admin_url('admin.php?page=symposium_styles') ), __('Pick a color scheme', WPS_TEXT_DOMAIN) ); ?></li>
							<?php if (!__wps__is_plus()) echo '<li>'.sprintf( __( '<a class="welcome-icon welcome-learn-more" href="%s" target="_blank">%s</a>' ), esc_url( 'http://www.wpsymposium.com/membership'), __('Upgrade to Bronze membership', WPS_TEXT_DOMAIN)).'</li>'; ?>
						</ul>						
					</div>
					<div class="welcome-panel-column">
						<h4><?php _e('Upgrading from previous version?', WPS_TEXT_DOMAIN) ?></h4>
						<?php echo __('You will need to:', WPS_TEXT_DOMAIN); ?>
						<ul>
							<li><?php echo sprintf( __( '<a class="welcome-icon welcome-widgets-menus" href="%s">%s</a>' ), esc_url( admin_url('plugins.php') ), __('Ensure the plugin is activated', WPS_TEXT_DOMAIN) ); ?></li>
							<li><?php echo sprintf( __( '<a class="welcome-icon welcome-add-page" href="%s">%s</a>' ), esc_url( admin_url('admin.php?page=symposium_debug') ), __('Activate features on the Installation page', WPS_TEXT_DOMAIN) ); ?></li>
							<li><?php echo sprintf( __( '<a class="welcome-icon welcome-widgets-menus" href="%s">%s</a>' ), esc_url( admin_url('admin.php?page=symposium_templates') ), __('Reset your Templates', WPS_TEXT_DOMAIN) ); ?></li>
						</ul>
						<?php echo sprintf(__( 'It\'s <em>very important</em> that you read the <a href="%s" target="_blank">release notes</a>.', WPS_TEXT_DOMAIN ), "http://www.wpsymposium.com/category/releases/"); ?><br />
                    </div>
					<div class="welcome-panel-column welcome-panel-last">
						<h4><?php _e( 'Need a little extra help?', WPS_TEXT_DOMAIN ); ?></h4>
						<ul>
							<li><?php echo sprintf( __( '<a class="welcome-icon welcome-view-site" href="%s" target="_blank">%s</a>' ), esc_url( 'http://www.wpsymposium.com/faqs' ), __('Frequently Asked Questions', WPS_TEXT_DOMAIN) ); ?></li>
							<li><?php echo sprintf( __( '<a class="welcome-icon welcome-learn-more" href="%s" target="_blank">%s</a>' ), esc_url('http://www.wpsymposium.com/trythisfirst'), __('Try this first!', WPS_TEXT_DOMAIN) ); ?></li>
                        	<li><?php echo sprintf( __( '<a class="welcome-icon welcome-learn-more" href="%s" target="_blank">%s</a>' ), esc_url( 'http://www.wpsymposium.com/admin-guide/' ), __('Read the admin guide', WPS_TEXT_DOMAIN) ); ?></li>
							<li><?php echo sprintf( __( '<a class="welcome-icon welcome-learn-more" href="%s" target="_blank">%s</a>' ), esc_url('http://www.wpsymposium.com/discuss'), __('Visit the Forum', WPS_TEXT_DOMAIN) ); ?></li>
							<li><?php echo sprintf( __( '<a class="welcome-icon welcome-learn-more" href="%s" target="_blank">%s</a>' ), esc_url('http://www.wpsymposium.com/tutorials'), __('Check out the tutorials', WPS_TEXT_DOMAIN) ); ?></li>
						</ul>
                	</div>
				</div>
	
			</div>
			
				<form action="index.php" method="post">
				<div style="float:right;margin-bottom:10px">
					<input type="submit" class="button-primary" value="<?php _e("Hide this now (it's available via the menu)", WPS_TEXT_DOMAIN); ?>" />
					<input type="hidden" name="symposium_hide_motd" value="Y" />
					<?php wp_nonce_field('symposium_hide_motd_nonce','symposium_hide_motd_nonce'); ?>
				</div>
				</form>
			
		</div>
	<?php
	}

}

function __wps__plugin_reminder() {
	
	if ($file = @file_get_contents(WPS_WELCOME_MESSAGE)) {
		// WPS_WELCOME_MESSAGE should defined in default-constants.php and is an absolute local path and filename
		// As already displayed, do nothing here
	} else {
	
		if (!get_option(WPS_OPTIONS_PREFIX.'_motd')) {
			$top_margin = '5';
		} else {
			$top_margin = '30';
		}
		
		?>
		
		<div id="wps-welcome-panel" class="welcome-panel" style="background-image: none; background-color: #ddf; margin: <?php echo $top_margin; ?>px 20px 0 0;">
			<div id="motd" class="welcome-panel-content" >

			    <h3><?php echo WPS_WL.__(' - complete your installation', WPS_TEXT_DOMAIN); ?></h3>		    
			    
				<p class="about-description">
				<?php echo sprintf(__( 'Please ensure your installation/upgrade has completed successfully by visiting the <a href="%s">Installation page</a>.', WPS_TEXT_DOMAIN ), "admin.php?page=symposium_debug"); ?> 
				<?php echo sprintf(__( 'You should reset your <a href="%s">templates</a>.', WPS_TEXT_DOMAIN ), "admin.php?page=symposium_templates"); ?>
			    </p>
	
			</div>
			
				<form action="index.php" method="post">
				<div style="float:right;margin-bottom:10px; margin-top:20px;">
					<input type="submit" class="button-primary" value="<?php _e("Thanks, done it...", WPS_TEXT_DOMAIN); ?>" />
					<input type="hidden" name="symposium_hide_reminder" value="Y" />
					<?php wp_nonce_field('symposium_hide_reminder_nonce','symposium_hide_reminder_nonce'); ?>
				</div>
				</form>

			
			
		</div>
	<?php
	}

}

function __wps__update_templates() {

	if (isset($_POST['profile_header_textarea']))
		update_option(WPS_OPTIONS_PREFIX.'_template_profile_header', str_replace(chr(13), "[]", $_POST['profile_header_textarea']));
	if (isset($_POST['profile_body_textarea']))
		update_option(WPS_OPTIONS_PREFIX.'_template_profile_body', str_replace(chr(13), "[]", $_POST['profile_body_textarea']));
	if (isset($_POST['page_footer_textarea'])) {
		if ($_POST['page_footer_textarea'] == "") {
			update_option(WPS_OPTIONS_PREFIX.'_template_page_footer', str_replace(chr(13), "[]", sprintf("<!-- Powered by %s v%s -->", WPS_WL, get_option(WPS_OPTIONS_PREFIX."_version"))));
		} else {
			update_option(WPS_OPTIONS_PREFIX.'_template_page_footer', str_replace(chr(13), "[]", $_POST['page_footer_textarea']));
		}
	}
	if (isset($_POST['email_textarea']))
		update_option(WPS_OPTIONS_PREFIX.'_template_email', str_replace(chr(13), "[]", $_POST['email_textarea']));
	if (isset($_POST['template_mail_tray_textarea']))
		update_option(WPS_OPTIONS_PREFIX.'_template_mail_tray', str_replace(chr(13), "[]", $_POST['template_mail_tray_textarea']));
	if (isset($_POST['template_mail_message_textarea']))
		update_option(WPS_OPTIONS_PREFIX.'_template_mail_message', str_replace(chr(13), "[]", $_POST['template_mail_message_textarea']));
	if (isset($_POST['template_forum_header_textarea']))
		update_option(WPS_OPTIONS_PREFIX.'_template_forum_header', str_replace(chr(13), "[]", $_POST['template_forum_header_textarea']));
	if (isset($_POST['template_group_textarea']))
		update_option(WPS_OPTIONS_PREFIX.'_template_group', str_replace(chr(13), "[]", $_POST['template_group_textarea']));
	if (isset($_POST['template_forum_category_textarea']))
		update_option(WPS_OPTIONS_PREFIX.'_template_forum_category', str_replace(chr(13), "[]", $_POST['template_forum_category_textarea']));
	if (isset($_POST['template_forum_topic_textarea']))
		update_option(WPS_OPTIONS_PREFIX.'_template_forum_topic', str_replace(chr(13), "[]", $_POST['template_forum_topic_textarea']));
	if (isset($_POST['template_group_forum_category_textarea'])) {
		// Not currently supported
	}
	if (isset($_POST['template_group_forum_topic_textarea']))
		update_option(WPS_OPTIONS_PREFIX.'_template_group_forum_topic', str_replace(chr(13), "[]", $_POST['template_group_forum_topic_textarea']));			
}

function __wps__plugin_templates() {

	global $wpdb;
	
	if (isset($_POST['symposium_template_update']) && $_POST['symposium_template_update'] == 'on') {
		
		if (is_multisite() && isset($_POST['symposium_templates_network_update']) && $_POST['symposium_templates_network_update']) {
		    $blogs = $wpdb->get_results("SELECT blog_id FROM ".$wpdb->base_prefix."blogs");
		    $list = '';
		    if ($blogs) {
		        foreach($blogs as $blog) {
		            switch_to_blog($blog->blog_id);
					__wps__update_templates();
					$list .= '<br />&nbsp;&middot;&nbsp;'.get_bloginfo('name');
		        }
		        restore_current_blog();
		    }   
	
			// Put an settings updated message on the screen
			echo "<div class='updated slideaway'><p>".__('Network templates updated:'.$list, WPS_TEXT_DOMAIN)."</p></div>";
		    
		} else {
		
			__wps__update_templates();
	
			// Put an settings updated message on the screen
			echo "<div class='updated slideaway'><p>".__('Site templates updated', WPS_TEXT_DOMAIN).".</p></div>";
		}
		
	}

	$template_profile_header = str_replace("[]", chr(13), stripslashes(get_option(WPS_OPTIONS_PREFIX.'_template_profile_header')));
	$template_profile_body = str_replace("[]", chr(13), stripslashes(get_option(WPS_OPTIONS_PREFIX.'_template_profile_body')));
	$template_page_footer = str_replace("[]", chr(13), stripslashes(get_option(WPS_OPTIONS_PREFIX.'_template_page_footer')));
	$template_email = str_replace("[]", chr(13), stripslashes(get_option(WPS_OPTIONS_PREFIX.'_template_email')));
	$template_mail_tray = str_replace("[]", chr(13), stripslashes(get_option(WPS_OPTIONS_PREFIX.'_template_mail_tray')));
	$template_mail_message = str_replace("[]", chr(13), stripslashes(get_option(WPS_OPTIONS_PREFIX.'_template_mail_message')));
	$template_forum_header = str_replace("[]", chr(13), stripslashes(get_option(WPS_OPTIONS_PREFIX.'_template_forum_header')));
	$template_group = str_replace("[]", chr(13), stripslashes(get_option(WPS_OPTIONS_PREFIX.'_template_group')));
	$template_forum_category = str_replace("[]", chr(13), stripslashes(get_option(WPS_OPTIONS_PREFIX.'_template_forum_category')));
	$template_forum_topic = str_replace("[]", chr(13), stripslashes(get_option(WPS_OPTIONS_PREFIX.'_template_forum_topic')));
	$template_group_forum_category = str_replace("[]", chr(13), stripslashes(get_option(WPS_OPTIONS_PREFIX.'_template_group_forum_category')));
	$template_group_forum_topic = str_replace("[]", chr(13), stripslashes(get_option(WPS_OPTIONS_PREFIX.'_template_group_forum_topic')));

  	echo '<div class="wrap">';

	  	echo '<div id="icon-themes" class="icon32"><br /></div>';
	  	echo '<h2>'.sprintf(__('%s Management', WPS_TEXT_DOMAIN), WPS_WL).'</h2><br />';
		__wps__show_manage_tabs_header('templates');

		// Import
		echo '<div id="symposium_import_templates_form" style="display:none">';
		echo '<input type="submit" class="symposium_templates_cancel button" style="margin-left: 6px;" value="Cancel">';
		echo '<input id="symposium_import_file_button" type="submit" class="button-primary" style="float:left" value="Import"><div id="symposium_import_file_pleasewait" style="display:none;float:left;margin-left:10px;margin-right:5px;margin-top:5px;width:15px;"></div>';
		echo '<p>'.__('Paste previous exported templates into the text area below - please ensure that you are not including any suspicious code.', WPS_TEXT_DOMAIN).'</h3>';
		echo '<br /><table class="widefat">';
		echo '<thead>';
		echo '<tr>';
		echo '<th style="font-size:1.2em">'.__('Import Template', WPS_TEXT_DOMAIN).'</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';
		echo '<tr>';
		echo '<td>';

			echo '<textarea id="symposium_import_file" style="width:100%; height:600px;font-family:courier;font-size:11px;background-color:#fff;"></textarea>';

		echo '</td>';
		echo '</tr>';
		echo '</tbody>';
		echo '</table>';
		echo '</div>';
			
		// Export
		echo '<div id="symposium_export_templates_form" style="display:none">';
		echo '<input type="submit" class="symposium_templates_cancel button" value="Cancel">';
		echo '<p>'.__('Copy and paste the following into a text editor to backup or share with others. Do not change the comments!', WPS_TEXT_DOMAIN).'</h3>';
		echo '<br /><table class="widefat">';
		echo '<thead>';
		echo '<tr>';
		echo '<th style="font-size:1.2em">'.__('Export Template', WPS_TEXT_DOMAIN).'</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';
		echo '<tr>';
		echo '<td>';

			echo '<textarea style="width:100%; height:600px;font-family:courier;font-size:11px;background-color:transparent;border:0px;">';
		
				echo '<!-- template_profile_header -->'.chr(13).chr(10);
				echo $template_profile_header.chr(13).chr(10);
				echo '<!-- end_template_profile_header -->'.chr(13).chr(10).chr(13).chr(10);

				echo '<!-- template_profile_body -->'.chr(13).chr(10);
				echo $template_profile_body.chr(13).chr(10);
				echo '<!-- end_template_profile_body -->'.chr(13).chr(10).chr(13).chr(10);

				echo '<!-- template_page_footer -->'.chr(13).chr(10);
				echo $template_page_footer.chr(13).chr(10);
				echo '<!-- end_template_page_footer -->'.chr(13).chr(10).chr(13).chr(10);

				echo '<!-- template_email -->'.chr(13).chr(10);
				echo $template_email.chr(13).chr(10);
				echo '<!-- end_template_email -->'.chr(13).chr(10).chr(13).chr(10);

				echo '<!-- template_mail_tray -->'.chr(13).chr(10);
				echo $template_mail_tray.chr(13).chr(10);
				echo '<!-- end_template_mail_tray -->'.chr(13).chr(10).chr(13).chr(10);
		
				echo '<!-- template_mail_message -->'.chr(13).chr(10);
				echo $template_mail_message.chr(13).chr(10);
				echo '<!-- end_template_mail_message -->'.chr(13).chr(10).chr(13).chr(10);

				echo '<!-- template_forum_header -->'.chr(13).chr(10);
				echo $template_forum_header.chr(13).chr(10);
				echo '<!-- end_template_forum_header -->'.chr(13).chr(10).chr(13).chr(10);

				echo '<!-- template_group -->'.chr(13).chr(10);
				echo $template_group.chr(13).chr(10);
				echo '<!-- end_template_group -->'.chr(13).chr(10).chr(13).chr(10);
		
				echo '<!-- template_forum_category -->'.chr(13).chr(10);
				echo $template_forum_category.chr(13).chr(10);
				echo '<!-- end_template_forum_category -->'.chr(13).chr(10).chr(13).chr(10);
		
				echo '<!-- template_forum_topic -->'.chr(13).chr(10);
				echo $template_forum_topic.chr(13).chr(10);
				echo '<!-- end_template_forum_topic -->'.chr(13).chr(10).chr(13).chr(10);
		
				echo '<!-- template_group_forum_category -->'.chr(13).chr(10);
				echo $template_group_forum_category.chr(13).chr(10);
				echo '<!-- end_template_group_forum_category -->'.chr(13).chr(10).chr(13).chr(10);
		
				echo '<!-- template_group_forum_topic -->'.chr(13).chr(10);
				echo $template_group_forum_topic.chr(13).chr(10);
				echo '<!-- end_template_group_forum_topic -->'.chr(13).chr(10).chr(13).chr(10);
		
			echo '</textarea>';

		echo '</td>';
		echo '</tr>';
		echo '</tbody>';
		echo '</table>';
		echo '</div>';

		echo '<div id="symposium_templates_values">';

			echo '<input id="symposium_import_templates" type="submit" class="button" style="float:left;margin-right:6px;" value="'.__('Import', WPS_TEXT_DOMAIN).'">';
			echo '<input id="symposium_export_templates" type="submit" class="button" style="float:left;" value="'.__('Export', WPS_TEXT_DOMAIN).'">';

			echo '<form action="" method="post">';
			echo '<input type="hidden" name="symposium_template_update" value="on" />';
		
			echo '<input type="submit" class="button-primary" style="float:right;" value="'.__('Save', WPS_TEXT_DOMAIN).'">';
						
			$show_super_admin = (is_super_admin() && __wps__is_wpmu());
			if ( $show_super_admin )
				echo '<div style="float:right;margin:5px 10px 0 0;"><input type="checkbox" name="symposium_templates_network_update" /> '.__('When saving, update entire network', WPS_TEXT_DOMAIN).'</div>';

			// Profile Page Header
			echo '<br />';
			if (get_option(WPS_OPTIONS_PREFIX.'_use_templates') == 'on') {
				echo '<br /><table class="widefat">';
				echo '<thead>';
				echo '<tr>';
				echo '<th style="font-size:1.2em">'.__('Profile Page Header', WPS_TEXT_DOMAIN);
				echo ' (<a href="admin.php?page=symposium_profile">'.__('options', WPS_TEXT_DOMAIN).'</a>)';
				echo '</th>';
				echo '</tr>';
				echo '</thead>';
				echo '<tbody>';
				echo '<tr>';
				echo '<td>';
					echo '<table style="float:right;width:39%">';
					echo '<tr>';
					echo '<td width="33%">'.__('Codes available', WPS_TEXT_DOMAIN).'</td>';
					echo '<td>'.__('Output', WPS_TEXT_DOMAIN).'</td>';
					echo '</tr>';
					echo '<tbody>';
					echo '<tr>';
					echo '<td>[follow]</td>';
					echo '<td>'.__('\'Follow\' and \'Unfollow\' buttons (requires Profile Plus)').'</td>';
					echo '</tr>';
					echo '<tr>';
					echo '<td>[poke]</td>';
					echo '<td>'.__('Show \'poke\' button as defined in <a href=\'admin.php?page=symposium_profile\'>Profile settings</a>', WPS_TEXT_DOMAIN).'</td>';
					echo '</tr>';
					echo '<tr>';
					echo '<td>[display_name]</td>';
					echo '<td>'.__('Display Name', WPS_TEXT_DOMAIN).'</td>';
					echo '</tr>';
					echo '<tr>';
					echo '<td>[profile_label]</td>';
					echo '<td>'.__('Profile label set by admin', WPS_TEXT_DOMAIN).'</td>';
					echo '</tr>';
					echo '<tr>';
					echo '<td>[location]</td>';
					echo '<td>'.__('City and/or Country', WPS_TEXT_DOMAIN).'</td>';
					echo '</tr>';
					echo '<tr>';
					echo '<td>[born]</td>';
					echo '<td>'.__('Birthday', WPS_TEXT_DOMAIN).'</td>';
					echo '</tr>';
					echo '<tr>';
					echo '<td>[actions]</td>';
					echo '<td>'.sprintf(__('%s Request/Send Mail/etc buttons', WPS_TEXT_DOMAIN), get_option(WPS_OPTIONS_PREFIX.'_alt_friend')).'</td>';
					echo '</tr>';
					echo '<tr>';
					echo '<td>[avatar,x]</td>';
					echo '<td>'.__('Show avatar, size x in pixels (no spaces)', WPS_TEXT_DOMAIN).'</td>';
					echo '</tr>';
					if (function_exists('__wps__profile_plus')) {				
						echo '<tr>';
						echo '<td>[ext_slug]</td>';
						echo '<td>'.__('Extended field (replace slug)', WPS_TEXT_DOMAIN).'</td>';
						echo '</tr>';
					}
					echo '</tbody>';
					echo '</table>';
					echo '<textarea id="profile_header_textarea" name="profile_header_textarea" style="width:60%;height: 260px;">';
					echo $template_profile_header;
					echo '</textarea>';
					echo '<br /><a id="reset_profile_header" href="javascript:void(0)">'.__('Reset to default', WPS_TEXT_DOMAIN).'</a>';
				echo '</td>';
				echo '</tr>';
				echo '</tbody>';
				echo '</table>';
			} else {
				echo '<textarea id="profile_header_textarea" name="profile_header_textarea" style="display:none">';
				echo $template_profile_header;
				echo '</textarea>';
			}

			// Profile Page Body
			if (get_option(WPS_OPTIONS_PREFIX.'_use_templates') == 'on') {
				echo '<br /><table class="widefat">';
				echo '<thead>';
				echo '<tr>';
				echo '<th style="font-size:1.2em">'.__('Profile Page Body', WPS_TEXT_DOMAIN).'</th>';
				echo '</tr>';
				echo '</thead>';
				echo '<tbody>';
				echo '<tr>';
				echo '<td>';
					echo '<table style="float:right;width:39%">';
					echo '<tr>';
					echo '<td width="33%">'.__('Codes available', WPS_TEXT_DOMAIN).'</td>';
					echo '<td>'.__('Output', WPS_TEXT_DOMAIN).'</td>';
					echo '</tr>';
					echo '<tbody>';
					echo '<tr>';
					echo '<td>[default]</td>';
					echo '<td>'.__('Used to force page parameter (important)', WPS_TEXT_DOMAIN).'</td>';
					echo '</tr>';
					echo '<tr>';
					echo '<td>[page]</td>';
					echo '<td>'.__('Where page content will be placed', WPS_TEXT_DOMAIN).'</td>';
					echo '</tr>';
					echo '<tr>';
					echo '<td>[menu]</td>';
					echo '<td>'.__('Profile menu', WPS_TEXT_DOMAIN).'</td>';
					echo '</tr>';
					if (function_exists('__wps__profile_plus')) {
						echo '<tr>';
						echo '<td>[menu_tabs]</td>';
						echo '<td>'.__('Horizontal menu', WPS_TEXT_DOMAIN).'</td>';
						echo '</tr>';
					}
					echo '</tbody>';
					echo '</table>';
					echo '<textarea id="profile_body_textarea" name="profile_body_textarea" style="width:60%;height: 200px;">';
					echo $template_profile_body;
					echo '</textarea>';
					echo '<br /><a id="reset_profile_body" href="javascript:void(0)">'.__('Reset to default (vertical menu)', WPS_TEXT_DOMAIN).'</a>';
					echo ' | <a id="reset_profile_body_tabs" href="javascript:void(0)">'.__('Reset to default (horizontal menu)', WPS_TEXT_DOMAIN).'</a>';
				echo '</td>';
				echo '</tr>';
				echo '</tbody>';
				echo '</table>';
			} else {
				echo '<textarea id="profile_body_textarea" name="profile_body_textarea" style="display:none">';
				echo $template_profile_body;
				echo '</textarea>';
			}

			// Page Footer
			echo '<br /><table class="widefat">';
			echo '<thead>';
			echo '<tr>';
			echo '<th style="font-size:1.2em">'.__('Page Footer', WPS_TEXT_DOMAIN).'</th>';
			echo '</tr>';
			echo '</thead>';
			echo '<tbody>';
			echo '<tr>';
			echo '<td>';
				echo '<table style="float:right;width:39%">';
				echo '<tr>';
				echo '<td width="33%">'.__('Codes available', WPS_TEXT_DOMAIN).'</td>';
				echo '<td>'.__('Output', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tbody>';
				echo '<tr>';
				echo '<td>[powered_by_message]</td>';
				echo '<td>'.sprintf(__('Default Powered By %s message', WPS_TEXT_DOMAIN), WPS_WL).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[version]</td>';
				echo '<td>'.sprintf(__('Version of %s', WPS_TEXT_DOMAIN), WPS_WL).'</td>';
				echo '</tr>';
				echo '</tbody>';
				echo '</table>';
				echo '<textarea id="page_footer_textarea" name="page_footer_textarea" style="width:60%;height: 200px;">';
				echo $template_page_footer;
				echo '</textarea>';
				echo '<br /><a id="reset_page_footer" href="javascript:void(0)">'.__('Reset to default', WPS_TEXT_DOMAIN).'</a>';
			echo '</td>';
			echo '</tr>';
			echo '</tbody>';
			echo '</table>';

			// Mail Tray Item
			echo '<br /><table class="widefat">';
			echo '<thead>';
			echo '<tr>';
			echo '<th style="font-size:1.2em">'.__('Mail Page: Tray Item', WPS_TEXT_DOMAIN).'</th>';
			echo '</tr>';
			echo '</thead>';
			echo '<tbody>';
			echo '<tr>';
			echo '<td>';
				echo '<table style="float:right;width:39%">';
				echo '<tr>';
				echo '<td width="33%">'.__('Codes available', WPS_TEXT_DOMAIN).'</td>';
				echo '<td>'.__('Output', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tbody>';
				echo '<tr>';
				echo '<td>[mail_sent]</td>';
				echo '<td>'.__('When the message was sent', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[mail_from]</td>';
				echo '<td>'.__('Sender/recipient of the message', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[mail_subject]</td>';
				echo '<td>'.__('Subject of the message', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[mail_message]</td>';
				echo '<td>'.__('A snippet of the mail message', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '</tbody>';
				echo '</table>';
				echo '<textarea id="template_mail_tray_textarea" name="template_mail_tray_textarea" style="width:60%;height: 200px;">';
				echo $template_mail_tray;
				echo '</textarea>';
				echo '<br /><a id="reset_mail_tray" href="javascript:void(0)">'.__('Reset to default', WPS_TEXT_DOMAIN).'</a>';
			echo '</td>';
			echo '</tr>';
			echo '</tbody>';
			echo '</table>';
		
			// Mail Message
			echo '<br /><table class="widefat">';
			echo '<thead>';
			echo '<tr>';
			echo '<th style="font-size:1.2em">'.__('Mail Page: Message', WPS_TEXT_DOMAIN).'</th>';
			echo '</tr>';
			echo '</thead>';
			echo '<tbody>';
			echo '<tr>';
			echo '<td>';
				echo '<table style="float:right;width:39%">';
				echo '<tr>';
				echo '<td width="33%">'.__('Codes available', WPS_TEXT_DOMAIN).'</td>';
				echo '<td>'.__('Output', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tbody>';
				echo '<tr>';
				echo '<td>[avatar,x]</td>';
				echo '<td>'.__('Show avatar, size x in pixels (no spaces)', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[mail_subject]</td>';
				echo '<td>'.__('Subject of the message', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[mail_recipient]</td>';
				echo '<td>'.__('Sender/recipient of the message', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[mail_sent]</td>';
				echo '<td>'.__('When the message was sent', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[delete_button]</td>';
				echo '<td>'.__('Delete mail button', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[reply_button]</td>';
				echo '<td>'.__('Reply to mail button', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[message]</td>';
				echo '<td>'.__('The mail message', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '</tbody>';
				echo '</table>';
				echo '<textarea id="template_mail_message_textarea" name="template_mail_message_textarea" style="width:60%;height: 200px;">';
				echo $template_mail_message;
				echo '</textarea>';
				echo '<br /><a id="reset_mail_message" href="javascript:void(0)">'.__('Reset to default', WPS_TEXT_DOMAIN).'</a>';
			echo '</td>';
			echo '</tr>';
			echo '</tbody>';
			echo '</table>';
			
			// Forum Header
			echo '<br /><table class="widefat">';
			echo '<thead>';
			echo '<tr>';
			echo '<th style="font-size:1.2em">'.__('Forum Header', WPS_TEXT_DOMAIN).'</th>';
			echo '</tr>';
			echo '</thead>';
			echo '<tbody>';
			echo '<tr>';
			echo '<td>';
				echo '<table style="float:right;width:39%">';
				echo '<tr>';
				echo '<td width="33%">'.__('Codes available', WPS_TEXT_DOMAIN).'</td>';
				echo '<td>'.__('Output', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tbody>';
				echo '<tr>';
				echo '<td>[breadcrumbs]</td>';
				echo '<td>'.__('Breadcrumb trail', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[new_topic_button]</td>';
				echo '<td>'.__('New Topic button', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[new_topic_form]</td>';
				echo '<td>'.__('Form for new topic', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[digest]</td>';
				echo '<td>'.__('Subscribe to daily digest', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[subscribe]</td>';
				echo '<td>'.__('Receive email for new topics', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[forum_options]</td>';
				echo '<td>'.__('Search, All Activity, etc', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[sharing]</td>';
				echo '<td>'.__('Sharing icons', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[top_advert]</td>';
				echo '<td>'.__('Advert space above forum', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '</tbody>';
				echo '</table>';
				echo '<textarea id="template_forum_header_textarea" name="template_forum_header_textarea" style="width:60%;height: 200px;">';
				echo $template_forum_header;
				echo '</textarea>';
				echo '<br /><a id="reset_forum_header" href="javascript:void(0)">'.__('Reset to default', WPS_TEXT_DOMAIN).'</a>';
			echo '</td>';
			echo '</tr>';
			echo '</tbody>';
			echo '</table>';

			// Forum Categories
			echo '<br /><table class="widefat">';
			echo '<thead>';
			echo '<tr>';
			echo '<th style="font-size:1.2em">'.__('Forum Categories (list)', WPS_TEXT_DOMAIN).'</th>';
			echo '</tr>';
			echo '</thead>';
			echo '<tbody>';
			echo '<tr>';
			echo '<td>';
				echo '<table style="float:right;width:39%">';
				echo '<tr>';
				echo '<td width="33%">'.__('Codes available', WPS_TEXT_DOMAIN).'</td>';
				echo '<td>'.__('Output', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tbody>';
				echo '<tr>';
				echo '<td>[avatar,x]</td>';
				echo '<td>'.__('Show avatar, size x in pixels (no spaces)', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[replied]</td>';
				echo '<td>'.__('replied or started text', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[subject]</td>';
				echo '<td>'.__('Subject of last post/reply', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[subject_text]</td>';
				echo '<td>'.__('Text from the post', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[ago]</td>';
				echo '<td>'.__('Age of last post/reply', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[post_count]</td>';
				echo '<td>'.__('How many posts in next level of this category', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[topic_count]</td>';
				echo '<td>'.__('How many topics in next level of this category', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[category_title]</td>';
				echo '<td>'.__('Title of the category', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[category_desc]</td>';
				echo '<td>'.__('Description of the category', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '</tbody>';
				echo '</table>';
				echo '<textarea id="template_forum_category_textarea" name="template_forum_category_textarea" style="width:60%;height: 200px;">';
				echo $template_forum_category;
				echo '</textarea>';
				echo '<br /><a id="reset_template_forum_category" href="javascript:void(0)">'.__('Reset to default', WPS_TEXT_DOMAIN).'</a>';
			echo '</td>';
			echo '</tr>';
			echo '</tbody>';
			echo '</table>';

			// Forum Topics
			echo '<br /><table class="widefat">';
			echo '<thead>';
			echo '<tr>';
			echo '<th style="font-size:1.2em">'.__('Forum Topics (list)', WPS_TEXT_DOMAIN).'</th>';
			echo '</tr>';
			echo '</thead>';
			echo '<tbody>';
			echo '<tr>';
			echo '<td>';
				echo '<table style="float:right;width:39%">';
				echo '<tr>';
				echo '<td width="33%">'.__('Codes available', WPS_TEXT_DOMAIN).'</td>';
				echo '<td>'.__('Output', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tbody>';
				echo '<tr>';
				echo '<td>[avatarfirst,x]</td>';
				echo '<td>'.__('Show avatar of initial post, size x in pixels', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[avatar,x]</td>';
				echo '<td>'.__('Show avatar of last reply, size x in pixels', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[startedby]</td>';
				echo '<td>'.__('Who posted the initial topic post', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[started]</td>';
				echo '<td>'.__('Age of initial topic post', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[replied]</td>';
				echo '<td>'.__('Who last replied', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[topic]</td>';
				echo '<td>'.__('Last reply text', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[ago]</td>';
				echo '<td>'.__('Age of last reply', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[views]</td>';
				echo '<td>'.__('View count for this topic', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[replies]</td>';
				echo '<td>'.__('Reply count for this topic', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[topic_title]</td>';
				echo '<td>'.__('Title of the topic', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '</tbody>';
				echo '</table>';
				echo '<textarea id="template_forum_topic_textarea" name="template_forum_topic_textarea" style="width:60%;height: 200px;">';
				echo $template_forum_topic;
				echo '</textarea>';
				echo '<br /><a id="reset_template_forum_topic" href="javascript:void(0)">'.__('Reset to default', WPS_TEXT_DOMAIN).'</a>';
			echo '</td>';
			echo '</tr>';
			echo '</tbody>';
			echo '</table>';

			// Group
			if (get_option(WPS_OPTIONS_PREFIX.'_use_group_templates') == 'on') {
				echo '<br /><a name="group_options"></a>';
				echo '<table class="widefat">';
				echo '<thead>';
				echo '<tr>';
				echo '<th style="font-size:1.2em">'.__('Group Page', WPS_TEXT_DOMAIN);
				echo ' (<a href="admin.php?page=wp-symposium/groups_admin.php">'.__('options', WPS_TEXT_DOMAIN).'</a>)';
				echo '</th>';
				echo '</tr>';
				echo '</thead>';
				echo '<tbody>';
				echo '<tr>';
				echo '<td>';
				if (function_exists('__wps__groups')) {
					echo '<table style="float:right;width:39%">';
					echo '<tr>';
					echo '<td width="33%">'.__('Codes available', WPS_TEXT_DOMAIN).'</td>';
					echo '<td>'.__('Output', WPS_TEXT_DOMAIN).'</td>';
					echo '</tr>';
					echo '<tbody>';
					echo '<tr>';
					echo '<td>[group_name]</td>';
					echo '<td>'.__('Group name', WPS_TEXT_DOMAIN).'</td>';
					echo '</tr>';
					echo '<tr>';
					echo '<td>[group_description]</td>';
					echo '<td>'.__('Group description', WPS_TEXT_DOMAIN).'</td>';
					echo '</tr>';
					echo '<tr>';
					echo '<td>[actions]</td>';
					echo '<td>'.__('Join/delete/etc buttons', WPS_TEXT_DOMAIN).'</td>';
					echo '</tr>';
					echo '<tr>';
					echo '<td>[avatar,x]</td>';
					echo '<td>'.__('Show avatar, size x in pixels (no spaces)', WPS_TEXT_DOMAIN).'</td>';
					echo '</tr>';
					echo '<tr>';
					echo '<td>[default]</td>';
					echo '<td>'.__('Used to force page parameter (important)', WPS_TEXT_DOMAIN).'</td>';
					echo '</tr>';
					echo '<tr>';
					echo '<td>[page]</td>';
					echo '<td>'.__('Where page content will be placed', WPS_TEXT_DOMAIN).'</td>';
					echo '</tr>';
					echo '<tr>';
					echo '<td>[menu]</td>';
					echo '<td>'.__('Group menu', WPS_TEXT_DOMAIN).'</td>';
					echo '</tr>';
					if (function_exists('__wps__profile_plus')) {
						echo '<tr>';
						echo '<td>[menu_tabs]</td>';
						echo '<td>'.__('Horizontal menu', WPS_TEXT_DOMAIN).'</td>';
						echo '</tr>';
					}
					echo '</tbody>';
					echo '</table>';
					echo '<textarea id="template_group_textarea" name="template_group_textarea" style="width:60%;height: 200px;">';
					echo $template_group;
					echo '</textarea>';
					echo '<br /><a id="reset_group" href="javascript:void(0)">'.__('Reset to default (vertical menu)', WPS_TEXT_DOMAIN).'</a>';
					echo ' | <a id="reset_group_tabs" href="javascript:void(0)">'.__('Reset to default (horizontal menu)', WPS_TEXT_DOMAIN).'</a>';
				} else {
					echo __('Only available to <a href="http://www.wpsymposium.com">Bronze or higher members</a>.', WPS_TEXT_DOMAIN);
				}
				echo '</td>';
				echo '</tr>';
				echo '</tbody>';
				echo '</table>';
			} else {
				echo '<textarea id="template_group_textarea" name="template_group_textarea" style="display:none">';
				echo $template_group;
				echo '</textarea>';
			}

			// Group Forum Topics
			echo '<br /><table class="widefat">';
			echo '<thead>';
			echo '<tr>';
			echo '<th style="font-size:1.2em">'.__('Group Forum Topics (list)', WPS_TEXT_DOMAIN).'</th>';
			echo '</tr>';
			echo '</thead>';
			echo '<tbody>';
			echo '<tr>';
			echo '<td>';
				echo '<table style="float:right;width:39%">';
				echo '<tr>';
				echo '<td width="33%">'.__('Codes available', WPS_TEXT_DOMAIN).'</td>';
				echo '<td>'.__('Output', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tbody>';
				echo '<tr>';
				echo '<td>[avatarfirst,x]</td>';
				echo '<td>'.__('Show avatar of initial post, size x in pixels', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[avatar,x]</td>';
				echo '<td>'.__('Show avatar of last reply, size x in pixels', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[startedby]</td>';
				echo '<td>'.__('Who posted the initial topic post', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[started]</td>';
				echo '<td>'.__('Age of initial topic post', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[replied]</td>';
				echo '<td>'.__('Who last replied', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[topic]</td>';
				echo '<td>'.__('Last reply text', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[ago]</td>';
				echo '<td>'.__('Age of last reply', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[views]</td>';
				echo '<td>'.__('View count for this topic', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[replies]</td>';
				echo '<td>'.__('Reply count for this topic', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[topic_title]</td>';
				echo '<td>'.__('Title of the topic', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '</tbody>';
				echo '</table>';
				echo '<textarea id="template_group_forum_topic_textarea" name="template_group_forum_topic_textarea" style="width:60%;height: 200px;">';
				echo $template_group_forum_topic;
				echo '</textarea>';
				echo '<br /><a id="reset_template_group_forum_topic" href="javascript:void(0)">'.__('Reset to default', WPS_TEXT_DOMAIN).'</a>';
			echo '</td>';
			echo '</tr>';
			echo '</tbody>';
			echo '</table>';

			// Email Notifications
			echo '<br /><table class="widefat">';
			echo '<thead>';
			echo '<tr>';
			echo '<th style="font-size:1.2em">'.sprintf(__('%s Emails', WPS_TEXT_DOMAIN), WPS_WL).'</th>';
			echo '</tr>';
			echo '</thead>';
			echo '<tbody>';
			echo '<tr>';
			echo '<td>';
				echo '<table style="float:right;width:39%">';
				echo '<tr>';
				echo '<td width="33%">'.__('Codes available', WPS_TEXT_DOMAIN).'</td>';
				echo '<td>'.__('Output', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tbody>';
				echo '<tr>';
				echo '<td>[message]</td>';
				echo '<td>'.__('The email message', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[footer]</td>';
				echo '<td>'.__('Footer Message', WPS_TEXT_DOMAIN).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[powered_by_message]</td>';
				echo '<td>'.sprintf(__('Default Powered By %s message', WPS_TEXT_DOMAIN), WPS_WL).'</td>';
				echo '</tr>';
				echo '<tr>';
				echo '<td>[version]</td>';
				echo '<td>'.sprintf(__('Version of %s', WPS_TEXT_DOMAIN), WPS_WL).'</td>';
				echo '</tr>';
				echo '</tbody>';
				echo '</table>';
				echo '<textarea id="email_textarea" name="email_textarea" style="width:60%;height: 200px;">';
				echo $template_email;
				echo '</textarea>';
				echo '<br /><a id="reset_email" href="javascript:void(0)">'.__('Reset to default', WPS_TEXT_DOMAIN).'</a>';
			echo '</td>';
			echo '</tr>';

			echo '</tbody>';
			echo '</table>';
		
			echo '</form>';
			
		echo '</div>';

		__wps__show_manage_tabs_header_end();		
		
	echo '</div>';
}

function __wps__plugin_moderation() {

	global $wpdb, $current_user;

	// First check if can moderate forum (Options->Forum->Permissions->Moderate)	
	// Administrators always can
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

	// Set orphaned topic?
	if (isset($_POST['symposium_cat_list_tid'])) {
		$tid = $_POST['symposium_cat_list_tid'];
		$cid = $_POST['symposium_cat_list'];
		// UPDATE topic
		$sql = "UPDATE ".$wpdb->prefix."symposium_topics SET topic_category=%d WHERE tid=%d";
		$wpdb->query($wpdb->prepare($sql, $cid, $tid));
		// UPDATE any replies to match
		$sql = "UPDATE ".$wpdb->prefix."symposium_topics SET topic_category=%d WHERE topic_parent=%d";
		$wpdb->query($wpdb->prepare($sql, $cid, $tid));
	}

  	echo '<div class="wrap">';
  	
	  	echo '<div id="icon-themes" class="icon32"><br /></div>';
	  	echo '<h2>'.sprintf(__('%s Management', WPS_TEXT_DOMAIN), WPS_WL).'</h2><br />';
		__wps__show_manage_tabs_header('posts');
		echo '<div style="float:right;">';
		echo '<a href="admin.php?page=symposium_forum">'.__('Go to Forum Options', WPS_TEXT_DOMAIN).'</a>';	 
		echo '</div>';

		if ($can_moderate) {
			  	
		  	$all = $wpdb->get_var("SELECT count(*) FROM ".$wpdb->prefix."symposium_topics"); 
		  	$approved = $wpdb->get_var("SELECT count(*) FROM ".$wpdb->prefix."symposium_topics WHERE topic_approved = 'on'"); 
		  	$unapproved = $all-$approved;
		  	$topics_count = $wpdb->get_var("SELECT count(*) FROM ".$wpdb->prefix."symposium_topics WHERE topic_parent = 0"); 
		  	$orphaned_count = $wpdb->get_var("SELECT count(*) FROM ".$wpdb->prefix."symposium_topics WHERE topic_parent = 0 AND topic_category = 0"); 
		  	
		  	$mod = 'all';
		  	if (isset($_GET['mod']) && $_GET['mod'] != '') { $mod = $_GET['mod']; }
		  	if (!isset($_GET['mod']) && isset($_POST['mod']) && $_POST['mod'] != '') { $mod = $_POST['mod']; }
		  	
		  	if ($mod == "all") { $all_class='current'; $approved_class=''; $unapproved_class=''; $topics_class=''; $orphaned_class=''; }
		  	if ($mod == "approved") { $all_class=''; $approved_class='current'; $unapproved_class=''; $topics_class=''; $orphaned_class=''; }
		  	if ($mod == "unapproved") { $all_class=''; $approved_class=''; $unapproved_class='current'; $topics_class=''; $orphaned_class=''; }
		  	if ($mod == "topics") { $all_class=''; $approved_class=''; $unapproved_class=''; $topics_class='current'; $orphaned_class=''; }
		  	if ($mod == "orphaned") { $all_class=''; $approved_class=''; $unapproved_class=''; $topics_class=''; $orphaned_class='current'; }
		  	
		  	echo '<ul class="subsubsub" style="margin-top:-3px;">';
			echo "<li><a href='admin.php?page=symposium_moderation' class='".$all_class."'>".__('All', WPS_TEXT_DOMAIN)." <span class='count'>(".$all.")</span></a> |</li>";
			echo "<li><a href='admin.php?page=symposium_moderation&mod=approved' class='".$approved_class."'>".__('Approved', WPS_TEXT_DOMAIN)." <span class='count'>(".$approved.")</span></a> |</li>"; 
			echo "<li><a href='admin.php?page=symposium_moderation&mod=unapproved' class='".$unapproved_class."'>".__('Unapproved', WPS_TEXT_DOMAIN)." <span class='count'>(".$unapproved.")</span></a></li>";
			echo "<li><a href='admin.php?page=symposium_moderation&mod=topics' class='".$topics_class."'>".__('Just Topics', WPS_TEXT_DOMAIN)." <span class='count'>(".$topics_count.")</span></a></li>";
			echo "<li><a href='admin.php?page=symposium_moderation&mod=orphaned' class='".$orphaned_class."'>".__('Topics with no category', WPS_TEXT_DOMAIN)." <span class='count'>(".$orphaned_count.")</span></a></li>";
			echo "</ul>";
			
			$__wps__search = (isset($_POST['__wps__search'])) ? $_POST['__wps__search'] : '';
			echo '<form action="#" method="POST">';
			echo '<input type="submit" class="button-primary" style="margin-right:15px;float:right;" value="'.__('Reset', WPS_TEXT_DOMAIN).'" />';
			echo '<input type="hidden" name="__wps__search" value="" />';
			echo '</form>';
			echo '<form action="#" method="POST">';
			echo '<input type="submit" class="button-primary" style="margin-right:5px;float:right;" value="'.__('Search', WPS_TEXT_DOMAIN).'" />';
			echo '<input type="text" name="__wps__search" style="margin-right:5px;margin-bottom:5px; float:right;" value="'.$__wps__search.'" />';
			echo '</form>';
			
			// Paging info
			$showpage = 0;
			$pagesize = 20;
			$numpages = floor($all / $pagesize);
			if ($all % $pagesize > 0) { $numpages++; }
		  	if (isset($_GET['showpage']) && $_GET['showpage']) { $showpage = $_GET['showpage']-1; } else { $showpage = 0; }
		  	if ($showpage >= $numpages) { $showpage = $numpages-1; }
			$start = ($showpage * $pagesize);		
			if ($start < 0) { $start = 0; }  
					
			// Query
			$sql = "SELECT t.*, u.display_name FROM ".$wpdb->prefix.'symposium_topics'." t LEFT JOIN ".$wpdb->base_prefix.'users'." u ON t.topic_owner = u.ID ";
			if ($mod == "approved") { $sql .= "WHERE t.topic_approved = 'on' "; }
			if ($mod == "unapproved") { $sql .= "WHERE t.topic_approved != 'on' "; }
			if ($mod == "topics") { $sql .= "WHERE t.topic_parent = 0 "; }
			if ($mod == "orphaned") { $sql .= "WHERE t.topic_parent = 0 AND t.topic_category = 0 "; }
			if (isset($_POST['__wps__search'])) {
				if (strpos($sql, 'WHERE') !== FALSE) {
					$sql .= "AND t.topic_post LIKE '%".$__wps__search."%' ";
				} else {
					$sql .= "WHERE t.topic_post LIKE '%".$__wps__search."%' ";
				}
			}
			$sql .= "ORDER BY tid DESC "; 
			$sql .= "LIMIT ".$start.", ".$pagesize;
			$posts = $wpdb->get_results($sql);
		
			// Pagination (top)
			echo __wps__pagination($numpages, $showpage, "admin.php?page=symposium_moderation&mod=".$mod."&showpage=");
			
			echo '<br /><table class="widefat">';
			echo '<thead>';
			echo '<tr>';
			echo '<th>ID</td>';
			echo '<th>'.__('Author', WPS_TEXT_DOMAIN).'</th>';
			echo '<th>'.__('Cat/ID', WPS_TEXT_DOMAIN).'</th>';
			echo '<th style="width: 30px; text-align:center;">'.__('Status', WPS_TEXT_DOMAIN).'</th>';
			echo '<th>'.__('Preview', WPS_TEXT_DOMAIN).'</th>';
			echo '<th>'.__('IP &amp; Proxy', WPS_TEXT_DOMAIN).'</th>';
			echo '<th>'.__('Time', WPS_TEXT_DOMAIN).'</th>';
			echo '<th>'.__('Action', WPS_TEXT_DOMAIN).'</th>';
			echo '</tr>';
			echo '</thead>';
			echo '<tfoot>';
			echo '<tr>';
			echo '<th>ID</th>';
			echo '<th>'.__('Author', WPS_TEXT_DOMAIN).'</th>';
			echo '<th>'.__('Cat/ID', WPS_TEXT_DOMAIN).'</th>';
			echo '<th style="width: 30px; text-align:center;">'.__('Status', WPS_TEXT_DOMAIN).'</th>';
			echo '<th>'.__('Preview', WPS_TEXT_DOMAIN).'</th>';
			echo '<th>'.__('IP &amp; Proxy', WPS_TEXT_DOMAIN).'</th>';
			echo '<th>'.__('Time', WPS_TEXT_DOMAIN).'</th>';
			echo '<th>'.__('Action', WPS_TEXT_DOMAIN).'</th>';
			echo '</tr>';
			echo '</tfoot>';
			echo '<tbody>';
			
			if ($posts) {

				$forum_url = __wps__get_url('forum');
				if (strpos($forum_url, '?') !== FALSE) {
					$q = "&";
				} else {
					$q = "?";
				}
							
				foreach ($posts as $post) {
		
					echo '<tr>';
					echo '<td valign="top" style="width: 30px">'.$post->tid.'</td>';
					echo '<td valign="top" style="width: 175px; max-width:175px;">'.$post->display_name.'</td>';
					echo '<td valign="top" style="width: 30px">'.$post->topic_category.'/'.$post->tid.'</td>';
					echo '<td valign="top" style="width: 30px; text-align:center;">';
					if ($post->topic_approved != "on") {
						echo '<img src="'.get_option(WPS_OPTIONS_PREFIX.'_images').'/forum_orange.png" alt="Unapproved" />';
					} else {
						echo '<img src="'.get_option(WPS_OPTIONS_PREFIX.'_images').'/forum_green.png" alt="Unapproved" />';
					}
					echo '</td>';
					echo '<td style="width:350px;max-width:350px;overflow:hidden;" valign="top">';
					if ($post->topic_parent == 0) {
						echo '<a href="'.$forum_url.$q.'cid='.$post->topic_category.'&show='.$post->tid.'">';
						echo '<strong>'.__('New Topic', WPS_TEXT_DOMAIN).'</strong>';
					} else {
						echo '<a href="'.$forum_url.$q.'cid='.$post->topic_category.'&show='.$post->topic_parent.'">';
						echo '<strong>'.__('New Reply', WPS_TEXT_DOMAIN).'</strong>';
					}
					echo '</a>';
					echo ' ('.__('Parent', WPS_TEXT_DOMAIN).'='.$post->topic_parent.')<br />';
					$preview = stripslashes($post->topic_post);
					if ( strlen($preview) > 150 ) { $preview = substr($preview, 0, 150)."..."; }
					echo '<div style="float: left;">'.$preview;
					if ( strlen($preview) > 150 ) { 
						echo '<span class="show_full_post" title="'.stripslashes(str_replace('"', '&quot;', $post->topic_post)).'" style="margin-left:6px; cursor:pointer; text-decoration:underline;">'.__('View', WPS_TEXT_DOMAIN).'</span>';
					}
					echo '</div>';
					echo '</td>';
					echo '<td valign="top" style="width: 150px">'.$post->remote_addr.'<br />'.$post->http_x_forwarded_for.'</td>';
					echo '<td valign="top" style="width: 150px">'.$post->topic_started.'</td>';
					echo '<td valign="top" style="width: 150px">';
					$showpage = (isset($_GET['showpage'])) ? $_GET['showpage'] : 0;
					if ($post->topic_approved != "on" ) {
						echo "<a href='admin.php?page=symposium_moderation&action=post_approve&showpage=".$showpage."&tid=".$post->tid."'>".__('Approve', WPS_TEXT_DOMAIN)."</a> | ";
					}
					echo "<span class='trash delete'><a href='admin.php?page=symposium_moderation&action=post_del&showpage=".$showpage."&tid=".$post->tid."'>".__('Trash', WPS_TEXT_DOMAIN)."</a></span>";
					// Change category
					echo '<form action="#" method="POST">';
					echo '<input type="hidden" name="symposium_cat_list_tid" value="'.$post->tid.'">';
					echo '<input type="hidden" name="mod" value="'.$mod.'">';
					if ($post->topic_parent == 0) {
						echo '<div style="width:325px">';
						$sql = "SELECT * from ".$wpdb->prefix."symposium_cats ORDER BY title";
						$c = $wpdb->get_results($sql);
						if ($c) {
							echo '<SELECT NAME="symposium_cat_list">';
							echo '<OPTION VALUE="0">'.__('Change category...', WPS_TEXT_DOMAIN).'</OPTION>';
							foreach ($c as $cat) {
								echo '<OPTION VALUE="'.$cat->cid.'"';
								if ($cat->cid == $post->topic_category) echo ' SELECTED';
								echo '>'.stripslashes($cat->title).'</OPTION>';
							}
							echo '</SELECT>';
							echo '<INPUT TYPE="SUBMIT" CLASS="button-primary" VALUE="'.__('Set', WPS_TEXT_DOMAIN).'" />';
						}
						echo "</div></form>";
					}
					echo '</td>';
					echo '</tr>';			
		
				}
			} else {
				echo '<tr><td colspan="6">&nbsp;</td></tr>';
			}
			echo '</tbody>';
			echo '</table>';
		
			// Pagination (bottom)
			echo __wps__pagination($numpages, $showpage, "admin.php?page=symposium_moderation&mod=".$mod."&showpage=");

		} else {

			echo __('Sorry, you cannot moderate the forum.', WPS_TEXT_DOMAIN);

		}
		__wps__show_manage_tabs_header_end();
		
	echo '</div>'; // End of wrap div

}

function __wps__plugin_debug() {

/* ============================================================================================================================ */

	global $wpdb, $current_user;
	wp_get_current_user();

 	$wpdb->show_errors();

  	$fail = "<span style='color:red; font-weight:bold;'>";
  	$fail2 = "</span><br /><br />";
 	
  	echo '<div class="wrap">';
        	
	  	echo '<div id="icon-themes" class="icon32"><br /></div>';
	  	echo '<h2>'.sprintf(__('%s Installation', "wp-symposium"), WPS_WL).'</h2>';
	  	
	  	// ********** Note about WP Symposium Pro
	  	$reminder = '';
		$reminder .= '<div style="margin-top:10px; margin-bottom:10px; background-color:#fcc;padding:10px;border-radius:5px;border:1px solid #9a9;">';
			$reminder .= sprintf(__('Please note, this is the WP Symposium plugin. <a href="%s">WP Symposium Pro</a> is a separate plugin - the next generation of the plugin - and <a href="%s">available here</a> on the WordPress repository.', WPS_TEXT_DOMAIN), 'http://wordpress.org/plugins/wp-symposium-pro', 'http://wordpress.org/plugins/wp-symposium-pro');
		$reminder .= '</div>';
		echo $reminder;

	  	// ********** Summary
		echo '<div style="margin-top:10px; margin-bottom:10px">';
			echo sprintf(__("Visit this page to complete installation; after you add a %s shortcode to a page; change pages with %s shortcodes; if you change WordPress Permalinks; or if you experience problems.", WPS_TEXT_DOMAIN), WPS_WL, WPS_WL);
		echo '</div>';

		// Check for activated/deactivated sub-plugins	 
		if (isset($_POST['__wps__installation_update']) && $_POST['__wps__installation_update'] == 'Y') {
			// Network activations
			__wps__update_option(WPS_OPTIONS_PREFIX.'__wps__events_main_network_activated', isset($_POST['__wps__events_main_network_activated']), true);
			__wps__update_option(WPS_OPTIONS_PREFIX.'__wps__forum_network_activated', isset($_POST['__wps__forum_network_activated']), true);
			__wps__update_option(WPS_OPTIONS_PREFIX.'__wps__profile_network_activated', isset($_POST['__wps__profile_network_activated']), true);
			__wps__update_option(WPS_OPTIONS_PREFIX.'__wps__mail_network_activated', isset($_POST['__wps__mail_network_activated']), true);
			__wps__update_option(WPS_OPTIONS_PREFIX.'__wps__members_network_activated', isset($_POST['__wps__members_network_activated']), true);
			__wps__update_option(WPS_OPTIONS_PREFIX.'__wps__add_notification_bar_network_activated', isset($_POST['__wps__add_notification_bar_network_activated']), true);
			__wps__update_option(WPS_OPTIONS_PREFIX.'__wps__facebook_network_activated', isset($_POST['__wps__facebook_network_activated']), true);
			__wps__update_option(WPS_OPTIONS_PREFIX.'__wps__gallery_network_activated', isset($_POST['__wps__gallery_network_activated']), true);
			__wps__update_option(WPS_OPTIONS_PREFIX.'__wps__groups_network_activated', isset($_POST['__wps__groups_network_activated']), true);
			__wps__update_option(WPS_OPTIONS_PREFIX.'__wps__lounge_main_network_activated', isset($_POST['__wps__lounge_main_network_activated']), true);
			__wps__update_option(WPS_OPTIONS_PREFIX.'__wps__mobile_network_activated', isset($_POST['__wps__mobile_network_activated']), true);
			__wps__update_option(WPS_OPTIONS_PREFIX.'__wps__news_main_network_activated', isset($_POST['__wps__news_main_network_activated']), true);
			__wps__update_option(WPS_OPTIONS_PREFIX.'__wps__profile_plus_network_activated', isset($_POST['__wps__profile_plus_network_activated']), true);
			__wps__update_option(WPS_OPTIONS_PREFIX.'__wps__rss_main_network_activated', isset($_POST['__wps__rss_main_network_activated']), true);
			__wps__update_option(WPS_OPTIONS_PREFIX.'__wps__mailinglist_network_activated', isset($_POST['__wps__mailinglist_network_activated']), true);
			__wps__update_option(WPS_OPTIONS_PREFIX.'__wps__wysiwyg_network_activated', isset($_POST['__wps__wysiwyg_network_activated']), true);
			// Site specific
			__wps__update_option(WPS_OPTIONS_PREFIX.'__wps__events_main_activated', isset($_POST['__wps__events_main_activated']), false);
			__wps__update_option(WPS_OPTIONS_PREFIX.'__wps__forum_activated', isset($_POST['__wps__forum_activated']), false);
			__wps__update_option(WPS_OPTIONS_PREFIX.'__wps__profile_activated', isset($_POST['__wps__profile_activated']), false);
			__wps__update_option(WPS_OPTIONS_PREFIX.'__wps__mail_activated', isset($_POST['__wps__mail_activated']), false);
			__wps__update_option(WPS_OPTIONS_PREFIX.'__wps__members_activated', isset($_POST['__wps__members_activated']), false);
			__wps__update_option(WPS_OPTIONS_PREFIX.'__wps__add_notification_bar_activated', isset($_POST['__wps__add_notification_bar_activated']), false);
			__wps__update_option(WPS_OPTIONS_PREFIX.'__wps__facebook_activated', isset($_POST['__wps__facebook_activated']), false);
			__wps__update_option(WPS_OPTIONS_PREFIX.'__wps__gallery_activated', isset($_POST['__wps__gallery_activated']), false);
			__wps__update_option(WPS_OPTIONS_PREFIX.'__wps__groups_activated', isset($_POST['__wps__groups_activated']), false);
			__wps__update_option(WPS_OPTIONS_PREFIX.'__wps__lounge_main_activated', isset($_POST['__wps__lounge_main_activated']), false);
			__wps__update_option(WPS_OPTIONS_PREFIX.'__wps__mobile_activated', isset($_POST['__wps__mobile_activated']), false);
			__wps__update_option(WPS_OPTIONS_PREFIX.'__wps__news_main_activated', isset($_POST['__wps__news_main_activated']), false);
			__wps__update_option(WPS_OPTIONS_PREFIX.'__wps__profile_plus_activated', isset($_POST['__wps__profile_plus_activated']), false);
			__wps__update_option(WPS_OPTIONS_PREFIX.'__wps__rss_main_activated', isset($_POST['__wps__rss_main_activated']), false);
			__wps__update_option(WPS_OPTIONS_PREFIX.'__wps__mailinglist_activated', isset($_POST['__wps__mailinglist_activated']), false);
			__wps__update_option(WPS_OPTIONS_PREFIX.'__wps__wysiwyg_activated', isset($_POST['__wps__wysiwyg_activated']), false);
		}

		if (isset($_POST['symposium_validation_code'])):
            $clean = preg_replace('/[^,;a-zA-Z0-9_-]/','',$_POST['symposium_validation_code']);
			__wps__update_option(WPS_OPTIONS_PREFIX.'_activation_code', $clean, true);
        endif;

		// Do check for Bronze plugins and activation code
		$has_bronze_plug_actived = has_bronze_plug_actived();

		$show_super_admin = (is_super_admin() && __wps__is_wpmu());
				
		echo "<div style='margin-top:15px; margin-bottom:15px; '>";

			$colspan = 5;
			if ( $show_super_admin ) $colspan = 6;

			echo '<form action="admin.php?page=symposium_debug" method="POST">';
			echo '<input type="hidden" name="__wps__installation_update" value="Y" />';
			echo '<table class="widefat">';
			echo '<thead>';
			echo '<tr>';
			if ( $show_super_admin )
				echo '<th width="10px">'.__('Network&nbsp;Activated', WPS_TEXT_DOMAIN).'</th>';
			echo '<th width="10px">'.__('Activated', WPS_TEXT_DOMAIN).'</th>';
			echo '<th width="150px">'.__('Feature', WPS_TEXT_DOMAIN).'</th>';
			echo '<th>'.__('WordPress page/URL Found', WPS_TEXT_DOMAIN).'</th>';
			echo '<th  style="text-align:center;width:90px;">'.__('Status', WPS_TEXT_DOMAIN);
			if (current_user_can('update_core'))
				echo ' [<a href="javascript:void(0);" id="symposium_url">?</a>]</tg>';
			if (current_user_can('update_core'))
				echo '<th class="symposium_url">'.sprintf(__('%s Settings', WPS_TEXT_DOMAIN), WPS_WL_SHORT).'</th>';
			echo '</tr>';
			echo '</thead>';
			echo '<tbody>';
			echo '<tr>';
				if ( $show_super_admin )
					echo '<td>&nbsp;</td>';
				echo '<td style="text-align:center"><img src="'.WPS_PLUGIN_URL.'/images/tick.png" /></td>';
				echo '<td>'.__('Core', WPS_TEXT_DOMAIN).'</td>';
				echo '<td>&nbsp;</td>';
				echo '<td style="text-align:center"><img src="'.get_option(WPS_OPTIONS_PREFIX.'_images').'/smilies/good.png" /></td>';
				if (current_user_can('update_core'))
					echo '<td class="symposium_url" style="background-color:#efefef">-</td>';
			echo '</tr>';

			// Get version numbers installed (if applicable)
			$mobile_ver = get_option(WPS_OPTIONS_PREFIX."_mobile_version");
			if ($mobile_ver != '') $mobile_ver = "v".$mobile_ver;

			__wps__install_row('profile', __('Profile', WPS_TEXT_DOMAIN), WPS_SHORTCODE_PREFIX.'-profile', '__wps__profile', get_option(WPS_OPTIONS_PREFIX.'_profile_url'), WPS_DIR.'/profile.php', 'admin.php?page=profile', '__wps__<a href="admin.php?page=symposium_profile">Options</a>');
			__wps__install_row('forum', __('Forum', WPS_TEXT_DOMAIN), WPS_SHORTCODE_PREFIX.'-forum', '__wps__forum', get_option(WPS_OPTIONS_PREFIX.'_forum_url'), WPS_DIR.'/forum.php', 'admin.php?page=forum', '__wps__<a href="admin.php?page=symposium_forum">Options</a>');
			__wps__install_row('members', __('Members', WPS_TEXT_DOMAIN), WPS_SHORTCODE_PREFIX.'-members', '__wps__members', get_option(WPS_OPTIONS_PREFIX.'_members_url'), WPS_DIR.'/members.php', 'admin.php?page=__wps__members_menu', '__wps__<a href="admin.php?page=__wps__members_menu">Options</a>');
			__wps__install_row('mail', __('Mail', WPS_TEXT_DOMAIN), WPS_SHORTCODE_PREFIX.'-mail', '__wps__mail', get_option(WPS_OPTIONS_PREFIX.'_mail_url'), WPS_DIR.'/mail.php', '', '__wps__<a href="admin.php?page=__wps__mail_menu">Options</a>');

			do_action('__wps__installation_hook');
			
			if ( is_super_admin() ) {

				$bronze = '';
				$bronze .= '<tr style="height:50px;">';
					$bronze .= '<td style="vertical-align:middle;padding-left:10px;" colspan='.$colspan.'>';
					$bronze .= '<input type="submit" class="button-primary" value="'.__('Update', WPS_TEXT_DOMAIN).'" />';
					$bronze .= '</td>';
				$bronze .= '</tr>';
							
				$bronze .= '<thead><tr>';
					$bronze .= '<th colspan='.$colspan.'>'.__('The following are Bronze+ member features', WPS_TEXT_DOMAIN).'</th>';
				$bronze .= '</tr></thead>';
			
				$bronze .= '<tr>';
				$bronze .= '<td colspan='.$colspan.'>';
	
					// Validation code		
					if (!WPS_HIDE_ACTIVATION) {
								
						if (!get_option(WPS_OPTIONS_PREFIX.'_activation_code')) {
							$bronze .= '<img style="float:right; margin:0 0 5px 10px;" src="'.WPS_PLUGIN_URL.'/images/bronze.png" />';
						} else {
							$bronze .= '<img style="width:70px; height:70px; float:right; margin:0 0 5px 10px;" src="'.WPS_PLUGIN_URL.'/images/bronze.png" />';
						}
						if (!get_option(WPS_OPTIONS_PREFIX.'_activation_code')) {
							
							$bronze .= '<p>';
								$bronze .= __('The following features are "Bronze+" member features. You can use them for free, but a polite notice will be shown at the top of your web pages.', WPS_TEXT_DOMAIN).' ';
								$bronze .= __('<strong>If you have purchased Bronze+ membership at www.wpsymposium.com</strong>, your activation code accessed via <a href="http://www.wpsymposium.com/membership" target="_new">http://www.wpsymposium.com/membership</a> (make sure you&apos;ve logged in!).', WPS_TEXT_DOMAIN);
							$bronze .= '</p>';					
						}
						
						$countdown = __wps__bronze_countdown();
						$days_left = $countdown[0];
						if ($has_bronze_plug_actived) {
							if (get_option(WPS_OPTIONS_PREFIX.'_activation_code'))
								$bronze .= $countdown[1];
						} else {
							$bronze .= __('You do not have any Bronze+ features activated on this site, and therefore the activation code is irrelevant.', WPS_TEXT_DOMAIN).'<br /><br />';
						}
		
						$bronze .= '<input type="text" name="symposium_validation_code" value="'.get_option(WPS_OPTIONS_PREFIX.'_activation_code').'" style="height:24px;margin-top:-2px;width:300px; background-color: #ff9; border:1px solid #333;">';
						$bronze .= ' <input type="submit" class="button-primary" value="'.__('Save', WPS_TEXT_DOMAIN).'" />';
						if ($code=get_option(WPS_OPTIONS_PREFIX.'_activation_code')) {
							if ($days_left < 366) {
								if (($code != 'wps') && (substr($code,0,3) != 'vip')) {
									$code =  preg_replace('#[^0-9]#','',$code);
									if ($code < time())
										$bronze .= '<br /><br /> <strong>'.__('This activation code has expired! <a href="http://www.wpsymposium.com/membership" target="_new">Get a new activation code</a> to extend the date.', WPS_TEXT_DOMAIN).'</strong>';
								}
							}
						} else {
							$bronze .= '<br />(no activation code entered)';
						}
						$bronze .= '</p>';
					}
						
				$bronze .= '</td>';
				$bronze .= '</tr>';
				$bronze = apply_filters( '__wps__code_filter', $bronze );
				echo $bronze;
				
			} else {
				echo '<input type="text" name="symposium_validation_code" value="'.get_option(WPS_OPTIONS_PREFIX.'_activation_code').'" style="display:none;">';
			}

			if ( $show_super_admin ) {
				echo '<thead>';
				echo '<tr>';
				echo '<th width="10px">'.__('Network&nbsp;Activated', WPS_TEXT_DOMAIN).'</th>';
				echo '<th>'.__('Activated', WPS_TEXT_DOMAIN).'</th>';
				echo '<th>'.__('Feature', WPS_TEXT_DOMAIN).'</th>';
				echo '<th>'.__('WordPress page/URL Found', WPS_TEXT_DOMAIN).'</th>';
				echo '<th style="text-align:center">'.__('Status', WPS_TEXT_DOMAIN);
				if (current_user_can('update_core'))
					echo '<th class="symposium_url">'.sprintf(__('%s Settings', WPS_TEXT_DOMAIN), WPS_WL_SHORT).'</th>';
				echo '</tr>';
				echo '</thead>';
			}
						
			__wps__install_row('panel', __('Panel/Chat', WPS_TEXT_DOMAIN), '', '__wps__add_notification_bar', '-', WPS_DIR.'/panel.php', 'admin.php?page=bar', '__wps__<a href="admin.php?page=symposium_bar">Options</a>');
			__wps__install_row('wysiwyg', __('Forum WYSIWYG editor', WPS_TEXT_DOMAIN), '', '__wps__wysiwyg', '-', '', WPS_DIR.'/forum.php', '__wps__bronze__<a href="admin.php?page=symposium_forum">Options</a>');
			__wps__install_row('profile_plus', __('Profile_Plus', WPS_TEXT_DOMAIN), '', '__wps__profile_plus', '-', 'wp-symposium/plus.php', 'admin.php?page='.WPS_DIR.'/plus_admin.php', '__wps__bronze__<a href="admin.php?page=wp-symposium/plus_admin.php">Options</a>');
			__wps__install_row('groups', __('Groups', WPS_TEXT_DOMAIN), WPS_SHORTCODE_PREFIX.'-groups', '__wps__groups', get_option(WPS_OPTIONS_PREFIX.'_groups_url'), WPS_DIR.'/groups.php', 'admin.php?page='.WPS_DIR.'/groups_admin.php', '__wps__bronze__<a href="admin.php?page=wp-symposium/groups_admin.php">Options</a>');
			__wps__install_row('group', __('Group', WPS_TEXT_DOMAIN), WPS_SHORTCODE_PREFIX.'-group', '__wps__group', get_option(WPS_OPTIONS_PREFIX.'_group_url'), WPS_DIR.'/groups.php', '', '__wps__bronze__');
			__wps__install_row('gallery', __('Gallery', WPS_TEXT_DOMAIN), WPS_SHORTCODE_PREFIX.'-galleries', '__wps__gallery', '/gallery/', WPS_DIR.'/gallery.php','admin.php?page='.WPS_DIR.'/gallery_admin.php', '__wps__bronze__<a href="admin.php?page=wp-symposium/gallery_admin.php">Options</a>');
			__wps__install_row('alerts', __('Alerts', WPS_TEXT_DOMAIN), WPS_SHORTCODE_PREFIX.'-alerts', '__wps__news_main', '-', WPS_DIR.'/news.php', 'admin.php?page='.WPS_DIR.'/news_admin.php', '__wps__bronze__<a href="admin.php?page=wp-symposium/news_admin.php">Options</a>');
			__wps__install_row('events', __('Events', WPS_TEXT_DOMAIN), WPS_SHORTCODE_PREFIX.'-events', '__wps__events_main', '-', WPS_DIR.'/events.php', 'admin.php?page='.WPS_DIR.'/events_admin.php', '__wps__bronze__<a href="admin.php?page=wp-symposium/events_admin.php">Options</a>');
			__wps__install_row('mobile', __('Mobile', WPS_TEXT_DOMAIN), '', '__wps__mobile', '-', WPS_DIR.'/mobile.php', 'admin.php?page=__wps__mobile_menu', '__wps__bronze__<a href="admin.php?page=__wps__mobile_menu">Options</a>');
			__wps__install_row('reply_by_email', 'Reply_by_Email', '', '__wps__mailinglist', '-', WPS_DIR.'/mailinglist.php', 'admin.php?page='.WPS_DIR.'/symposium_mailinglist_admin.php', '__wps__bronze__<a href="admin.php?page=wp-symposium/mailinglist_admin.php">Options</a>');
			__wps__install_row('the_lounge', __('The_Lounge', WPS_TEXT_DOMAIN), WPS_SHORTCODE_PREFIX.'-lounge', '__wps__lounge_main', '-', WPS_DIR.'/lounge.php', 'admin.php?page='.WPS_DIR.'/lounge_admin.php', '__wps__bronze__<a href="admin.php?page=wp-symposium/lounge_admin.php">Options</a>');
			__wps__install_row('rss_feed', __('RSS_Feed', WPS_TEXT_DOMAIN), '', '__wps__rss_main', '-', WPS_DIR.'/rss.php', '', '__wps__bronze__');
			__wps__install_row('facebook', __('Facebook', WPS_TEXT_DOMAIN), '', '__wps__facebook', '-', WPS_DIR.'/facebook.php', 'admin.php?page='.WPS_DIR.'/facebook_admin.php', '__wps__bronze__');
	

			echo '<tr style="height:50px">';
				echo '<td style="vertical-align:middle;padding-left:10px;" colspan='.$colspan.'>';
				echo '<input type="submit" class="button-primary" value="'.__('Update', WPS_TEXT_DOMAIN).'" />';
				echo '</td>';
			echo '</tr>';
			
			echo '</tbody>';
			echo '</table>';
			
			echo '</form>';
				
		echo "</div>";

		// Check for request in URL to go straight to site
		if (isset($_GET['gotosite']) && $_GET['gotosite'] == '1') {
			echo '<script>';
			echo 'window.location.replace("'.get_bloginfo('url').'?p='.$_GET['pid'].'");';
			echo '</script>';
			die();
		}
		
		// Only show following to admins and above
		if (current_user_can('update_core') && (!WPS_HIDE_INSTALL_INFO)) {
			
			if (isset($_POST['__wps__install_assist_action'])) {
				$action = $_POST['__wps__install_assist_action'];
				if ($action == 'hide') {
					update_option(WPS_OPTIONS_PREFIX."_install_assist", false);
				} else {
					update_option(WPS_OPTIONS_PREFIX."_install_assist", true);
				}
			}
			
			$show = get_option(WPS_OPTIONS_PREFIX."_install_assist");
			
			if (!$show) {

				echo '<form action="" method="POST">';
				echo '<input type="hidden" name="__wps__install_assist_action" value="show" />';
				echo "<input id='__wps__install_assist_button' type='submit' class='button-secondary' value='".__('Show installation help', WPS_TEXT_DOMAIN)."' />";
				echo '</form>';
				
			} else {
				
				echo '<form action="" method="POST">';
				echo '<input type="hidden" name="__wps__install_assist_action" value="hide" />';
				echo "<input id='__wps__install_assist_button' type='submit' class='button-secondary' value='".__('Hide installation help', WPS_TEXT_DOMAIN)."' />";
				echo '</form>';
				
				echo "<div id='__wps__install_assist' style='margin-top:15px'>";
				
					echo "<div style='width:49%; float:left;'>";
					
						echo '<table class="widefat"><tr><td style="padding:0 0 0 10px">';
							echo '<h2 style="margin-bottom:10px">'.__('Core Information', WPS_TEXT_DOMAIN).'</h2>';
				
							echo '<p>';
							echo __('Site domain name', WPS_TEXT_DOMAIN).': '.get_bloginfo('url').'<br />';
							echo '</p>';
				
							echo "<p>";
				
								global $blog_id;
								echo __("WordPress site ID:", WPS_TEXT_DOMAIN)." ".$blog_id.'<br />';
								echo __("WordPress site name:", WPS_TEXT_DOMAIN)." ".get_bloginfo('name').'<br />';
								echo '<br />';
								echo sprintf(__("%s internal code version:", WPS_TEXT_DOMAIN), WPS_WL)." ";
								$ver = get_option(WPS_OPTIONS_PREFIX."_version");
								if (!$ver) { 
									echo "<br /><span style='clear:both;color:red; font-weight:bold;'>Error!</span> ".__('No code version set. Try <a href="admin.php?page=symposium_debug&force_create_wps=yes">re-creating/modifying</a> the database tables.', WPS_TEXT_DOMAIN)."</span><br />"; 
								} else {
									echo $ver."<br />";
								}
						
							echo "</p>";
							
							// Curl / JSON
							$disabled_functions=explode(',', ini_get('disable_functions'));
							$ok=true;
							if (!is_callable('curl_init')) {
								echo $fail.__('CURL PHP extension is not installed, please contact your hosting company.', WPS_TEXT_DOMAIN).$fail2;
								$ok=false;
							} else {
								if (in_array('curl_init', $disabled_functions)) {
									echo $fail.__('CURL PHP extension is disabled in php.ini, please contact your hosting company.', WPS_TEXT_DOMAIN).$fail2;
									$ok=false;
								} else {
									echo '<p>'.__('CURL PHP extension is installed and enabled in php.ini.', WPS_TEXT_DOMAIN).'</p>';
								}
							}
							if (!is_callable('json_decode')) {
								echo $fail.__('JSON PHP extension is not installed, please contact your hosting company.', WPS_TEXT_DOMAIN).$fail2;
								$ok=false;
							} else {
								if (in_array('json_decode', $disabled_functions)) {
									echo $fail.__('JSON PHP extension is disabled in php.ini, please contact your hosting company.', WPS_TEXT_DOMAIN).$fail2;
									$ok=false;
								} else {
									echo "<p>".__('JSON PHP extension is installed and enabled in php.ini.', WPS_TEXT_DOMAIN)."</p>";
								}
							}
							if (!$ok)
								echo $fail.__('Please contact your hosting company to ask for the above to be installed/enabled.', WPS_TEXT_DOMAIN).$fail2;
							
							// Debug mode?
							if (WPS_DEBUG) {
								echo "<p style='font-weight:bold'>".__('Running in DEBUG mode.', WPS_TEXT_DOMAIN)."</p>";
							}
						echo '</td></tr></table>';
		
						// Integrity check
						echo '<table class="widefat" style="margin-top:10px"><tr><td style="padding:0 10px 0 10px">';
							echo '<a name="ric"></a>';
							echo '<h2 style="margin-bottom:10px">'.__('Integrity check', WPS_TEXT_DOMAIN).'</h2>';
							
							if (isset($_POST['symposium_ric'])) {
								$report = '';

								// Check that user meta matches user table and delete to synchronise
								if (isset($_POST['symposium_ric_syn'])) {
									
									if (!isset($_POST['symposium_ric_username'])) {
										$sql = "SELECT user_id
												FROM ".$wpdb->base_prefix."usermeta m 
												LEFT JOIN ".$wpdb->base_prefix."users u 
												ON m.user_id = u.ID 
												WHERE u.ID IS NULL;";
										$missing_users = $wpdb->get_results($sql); 
									} else {
										$sql = "SELECT user_id
												FROM ".$wpdb->base_prefix."usermeta m 
												LEFT JOIN ".$wpdb->base_prefix."users u 
												ON m.user_id = u.ID 
												WHERE u.ID IS NULL AND u.user_login = %s;";
										$missing_users = $wpdb->get_results($wpdb->prepare($sql, $_POST['symposium_ric_username'])); 
									}
											
									if ($missing_users) {
										foreach ($missing_users as $missing) {
											$sql = "DELETE FROM ".$wpdb->base_prefix."usermeta WHERE user_id = %d";
											$wpdb->query($wpdb->prepare($sql, $missing->uid)); 
											$sql = "DELETE FROM ".$wpdb->base_prefix."symposium_friends WHERE friend_from = %d or friend_to = %d";
											$wpdb->query($wpdb->prepare($sql, $missing->uid, $missing->uid)); 
											$sql = "DELETE FROM ".$wpdb->base_prefix."symposium_group_members WHERE member_id = %d";
											$wpdb->query($wpdb->prepare($sql, $missing->uid)); 			
										}
									}	
									$report .= __("User tables syncronized", WPS_TEXT_DOMAIN).".<br />";															
								}
								
								// Fix missing categories, where replies exist with a category
	  							$sql = "SELECT * from ".$wpdb->prefix."symposium_topics where topic_parent = 0 AND topic_category = 0 order by tid desc";
							  	$a = $wpdb->get_results($sql);
							  	$updated = 0;
							  	foreach ($a as $b) {
							  	    if ($b->topic_category == 0) {
										// Got no category, so check for a reply that has a category
							  	        $sql = "select * from ".$wpdb->prefix."symposium_topics where topic_category > 0 AND topic_parent = %d LIMIT 0,1";
							  	        $d = $wpdb->get_row($wpdb->prepare($sql, $b->tid));
							  	        if ($d) {
							  	            // Update the parent category from 0, to that of it's reply
											$sql = "UPDATE ".$wpdb->prefix."symposium_topics SET topic_category = %d WHERE tid = %d";
											$wpdb->query($wpdb->prepare($sql, $d->topic_category, $b->tid));
											$updated++;
							  	        }
							  	    }
							  	}
							  	if (count($a) > 0) 
									$report .= sprintf( __("%d topics had missing categories, %d were fixed by copying from one of its replies", WPS_TEXT_DOMAIN), count($a), $updated ).".<br />";
									if (count($a)-$updated > 0) 
										$report .= sprintf(__('Fix the remaining orphaned topics <a href="%s">here</a>.', WPS_TEXT_DOMAIN), 'admin.php?page=symposium_moderation&mod=orphaned').'<br />';
							  								    	
								// Update topic categories (if category missing and with a parent)
								$sql = "SELECT * FROM ".$wpdb->prefix."symposium_topics
										WHERE topic_category = 0 AND topic_parent > 0";
								$topics = $wpdb->get_results($sql);
								if ($topics) {
									foreach ($topics AS $topic) {
										// Get the category of the parent and update
										$sql = "SELECT topic_category FROM ".$wpdb->prefix."symposium_topics WHERE tid = %d";
										$parent_cat = $wpdb->get_var($wpdb->prepare($sql, $topic->topic_parent));
										// Update this topic's category to it
										$sql = "UPDATE ".$wpdb->prefix."symposium_topics SET topic_category = %d WHERE tid = %d";
										$wpdb->query($wpdb->prepare($sql, $parent_cat, $topic->tid));
									}
									$report .= sprintf( __("%d replies had missing categories so copied from its parent", WPS_TEXT_DOMAIN), count($topics) )."<br />";
								}
								
								// If a members folder exists in wps-content, but user doesn't exist, report that it exists (can remove?)
								$path = get_option(WPS_OPTIONS_PREFIX.'_img_path').'/members';
								if(file_exists($path) && is_dir($path)) { 
									if ($handler = opendir($path)) {
										while (($sub = readdir($handler)) !== FALSE) {
											if ($sub != "." && $sub != ".." && $sub != "Thumb.db" && $sub != "Thumbs.db" && is_numeric($sub)) {
												if (is_dir($path."/".$sub)) {
													$id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->base_prefix."users WHERE ID = %d", $sub));
													if (!$id) {
														$report .= 'User ID ['.$sub.'] not found but '.$path."/".$sub.' exists<br />';
														//__wps__rrmdir($path."/".$sub);
													}
												}
											}
										}
									}
								} else {
									// Folder doesn't exist so create it
									if (!mkdir($path, 0777, true)) {
										$report .= sprintf(__("The %s images/media path could not be created (%s), check rights and re-run the Integrity Check", WPS_TEXT_DOMAIN), WPS_WL, $path);
									} else {
										$report .= sprintf(__("The %s images/media path (%s) was created", WPS_TEXT_DOMAIN), WPS_WL, $path);
									}
								}
								
								// Remove any users with user_id = Null
								$sql = "DELETE FROM ".$wpdb->base_prefix."usermeta WHERE user_id IS Null";
								$wpdb->query($sql);
			
								// Get a list of users that have duplicate keys in wp_usermeta
									$sql = "SELECT DISTINCT user_id FROM (
										SELECT user_id, meta_key, COUNT( user_id ) AS cnt
										FROM ".$wpdb->base_prefix."usermeta
										GROUP BY user_id, meta_key
										HAVING meta_key LIKE  '%symposium%'
										AND cnt > 1
										) AS results";
								$users = $wpdb->get_results($sql); 
			
								// Loop through each user
								if ($users) {
									foreach ($users AS $user) {
			
										if ($user->user_id != null) {
			
											$report .= '<strong>'.sprintf(__("Found duplicate meta_keys for user %d", WPS_TEXT_DOMAIN), $user->user_id).'</strong><br />';
			
											// Get list of meta keys that have duplicates
											$sql = "SELECT DISTINCT meta_key 
													FROM ".$wpdb->base_prefix."usermeta
													WHERE user_id = ".$user->user_id."
													AND meta_key LIKE '%symposium%'";
			
											$meta_keys = $wpdb->get_results($sql);
			
											// For each meta_key get latest, delete them all and re-add just one
											if ($meta_keys) {
												foreach ($meta_keys AS $meta) {
			
													$sql = "SELECT umeta_id, meta_key, meta_value 
															FROM ".$wpdb->base_prefix."usermeta
															WHERE user_id = %d
															AND meta_key =  %s
															ORDER BY umeta_id DESC 
															LIMIT 0 , 1";
			
													$single = $wpdb->get_row($wpdb->prepare($sql, $user->user_id, $meta->meta_key));
			
													// Don't include following as standard as may produce large HTML output
													// $report .= sprintf(__("Setting user %d meta_key '%s' as %s", WPS_TEXT_DOMAIN), $user->user_id, $single->meta_key, $single->meta_value).'<br />';
			
													// Do the clean up
													$sql = "DELETE FROM ".$wpdb->base_prefix."usermeta WHERE user_id = %d AND meta_key = %s";
													$wpdb->query($wpdb->prepare($sql, $user->user_id, $single->meta_key));
													update_user_meta( $user->user_id, $single->meta_key, $single->meta_value );
			
												}
											}
																							
										}
									}
								}
								
								// Update lat/long for distance calculation for all those users with a city and country
								if (function_exists('__wps__profile_plus')) {
									if (isset($_POST['symposium_ric_username']) && $_POST['symposium_ric_username'] != '') {
										$sql = "SELECT * FROM ".$wpdb->base_prefix."users WHERE user_login = %s OR display_name = %s";
										$users = $wpdb->get_results($wpdb->prepare($sql, $_POST['symposium_ric_username'], $_POST['symposium_ric_username']));
										$report .= sprintf(__("Geocoding for %s", WPS_TEXT_DOMAIN), $_POST['symposium_ric_username']).'<br />';
									} else {
										$sql = "SELECT * FROM ".$wpdb->base_prefix."users";
										$users = $wpdb->get_results($sql);
									}
									$not_reported_limit = false;
									
									if ($users) {

										foreach ($users as $user) {
											if (isset($_POST['symposium_ric_username']) && $_POST['symposium_ric_username'] != '')
												$report .= sprintf(__("Found %s", WPS_TEXT_DOMAIN), $_POST['symposium_ric_username']).'<br />';
											
											$lat = get_user_meta($user->ID, 'symposium_plus_lat', true);
											$lng = get_user_meta($user->ID, 'symposium_plus_long', true);
											
											if ( (!$lat || !$lng) || (isset($_POST['symposium_ric_username']) && $_POST['symposium_ric_username'] != '') ) {
												$city = get_user_meta($user->ID, 'symposium_extended_city', true);
												$country = get_user_meta($user->ID, 'symposium_extended_country', true);
		
												if ($city != '' && $country != '') {
													$city = str_replace(' ','%20',$city);
													$country = str_replace(' ','%20',$country);
									
													$fgc = 'http://maps.googleapis.com/maps/api/geocode/json?address='.$city.'+'.$country.'&sensor=false';
											
													if ($json = @file_get_contents($fgc) ) {
														if (WPS_DEBUG || (isset($_POST['symposium_ric_username']) && $_POST['symposium_ric_username'] != '')) $report .= "Connect URL to Google API with: ".$fgc."<br />";
														$json_output = json_decode($json, true);
														$json_output_array = __wps__displayArray($json_output);
														if (strpos($json_output_array, "OVER_QUERY_LIMIT") !== false) {
															if (!$not_reported_limit) {
																$report .= "<span style='color:red; font-weight:bold;'>".__("Google API limit reached, please repeat for remaining users, or enter a user login.", WPS_TEXT_DOMAIN).'</span><br />';														
																$not_reported_limit = true;
															}
														} else {
															$lat_new = $json_output['results'][0]['geometry']['location']['lat'];
															$lng_new = $json_output['results'][0]['geometry']['location']['lng'];														
															if (WPS_DEBUG || (isset($_POST['symposium_ric_username']) && $_POST['symposium_ric_username'] != ''))
																$report .= " - Google results: ".$lat_new."/".$lng_new."<br />";
			
															update_user_meta($user->ID, 'symposium_plus_lat', $lat_new);
															update_user_meta($user->ID, 'symposium_plus_long', $lng_new);
															
															if (!$not_reported_limit)
																$report .= sprintf(__("Updated %s [%d] geocode information for %s,%s from %s,%s to %s,%s", WPS_TEXT_DOMAIN), $user->display_name, $user->ID, $city, $country, $lat, $lng, $lat_new, $lng_new).'<br />';
														}
													} else {
														$report .= "<span style='color:red; font-weight:bold;'>".sprintf(__("Failed to connect to Google API<br>%s", WPS_TEXT_DOMAIN), $json).'</span><br />';
													}
												}
											}
										}
									} else {
										$report .= __("No users found.").'<br />';
									}
									
								} else {
									$report .= __("Not checking geocoding as Profile Plus not activated.").'<br />';
								}								
								
								// Remove dead friendships
								$del_count = 0;
								$sql = "SELECT fid from ".$wpdb->base_prefix."symposium_friends f
										left JOIN ".$wpdb->base_prefix."users u ON u.ID = f.friend_from
										WHERE u.ID is null";
								$orphaned = $wpdb->get_results($sql);
								foreach ($orphaned as $orphan) {
									$sql = "DELETE FROM ".$wpdb->base_prefix."symposium_friends WHERE fid = %d";
									$wpdb->query($wpdb->prepare($sql, $orphan->fid));
									$del_count++;
								}
								$sql = "SELECT fid from ".$wpdb->base_prefix."symposium_friends f
										left JOIN ".$wpdb->base_prefix."users u ON u.ID = f.friend_to
										WHERE u.ID is null";
								$orphaned = $wpdb->get_results($sql);
								foreach ($orphaned as $orphan) {
									$sql = "DELETE FROM ".$wpdb->base_prefix."symposium_friends WHERE fid = %d";
									$wpdb->query($wpdb->prepare($sql, $orphan->fid));
									$del_count++;
								}
								if ($del_count) $report .= sprintf(__("%d orphaned friendships removed.", WPS_TEXT_DOMAIN), $del_count).'<br />';
			
								// Filter
								$report = apply_filters( '__wps__integrity_check_hook', $report );						
			
								// Done
								echo "<div style='margin-top:15px;margin-right:15px; border:1px solid #060;background-color: #9f9; border-radius:5px;padding-left:8px; margin-bottom:10px;'>";
								if ($report == '') { $report = __('No problems found.', WPS_TEXT_DOMAIN); }
								echo "<strong>".__("Integrity check completed.", WPS_TEXT_DOMAIN)."</strong><br />".$report;
								echo "</div>";
								
							}
				
										
							echo "<p>".sprintf(__('<strong>Only click the button below once!<br />This can take a long while to complete if you have a lot of users - please wait for it to finish.<br />If your browser "times out" you can repeat until it completes.</strong><br />You should run the integrity check regularly, preferably daily. Before reporting a support request, please run the %s integrity check. This will remove potential inaccuracies within the database.', WPS_TEXT_DOMAIN), WPS_WL_SHORT)."</p>";
				
							echo '<form method="post" action="#ric">';
							echo '<input type="hidden" name="symposium_ric" value="Y">';
							echo __('Enter a user login/display name to restrict to one user.', WPS_TEXT_DOMAIN).'<br />';
							echo '<input type="text" name="symposium_ric_username" value=""><br />';
							echo '<input type="checkbox" name="symposium_ric_syn"> '.__('Syncronize WordPress user tables with WP Symposium', WPS_TEXT_DOMAIN);
							echo '<p></p><input type="submit" name="Submit" class="button-primary" value="'.__('Run integrity check', WPS_TEXT_DOMAIN).'" /></p>';
							echo '</form>';
		
						echo '</td></tr></table>';
		
						// ********** Reset database version
						echo '<table class="widefat" style="margin-top:10px"><tr><td style="padding:0 0 0 10px">';
							echo '<h2 style="margin-bottom:10px">'.sprintf(__('Refresh %s', WPS_TEXT_DOMAIN), WPS_WL).'</h2>';
							echo "<p>".__('To re-run the database table creation/modifications, <a href="admin.php?page=symposium_debug&force_create_wps=yes">click here</a>.<br /><strong>This will not destroy any existing tables or data</strong>.', WPS_TEXT_DOMAIN)."</p>";
							echo "<p>".sprintf(__('This will also display the %s <a href="%s">welcome page</a>.', WPS_TEXT_DOMAIN), WPS_WL, "admin.php?page=symposium_welcome")."</p>";
						echo '</td></tr></table>';
		
						// Purge chat
						echo '<table class="widefat" style="margin-top:10px"><tr><td style="padding:0 0 0 10px">';
							echo "<a name='purge'></a>";
							echo '<h2 style="margin-bottom:10px">'.__('Purge forum/chat', WPS_TEXT_DOMAIN).'</h2>';
			
							if (isset($_POST['purge_chat']) && $_POST['purge_chat'] != '' && is_numeric($_POST['purge_chat']) ) {
								
								$sql = "SELECT COUNT(id) FROM ".$wpdb->prefix."symposium_chat2 WHERE sent <= ".(time() - $_POST['purge_chat'] * 24 * 60 * 60);	
								$cnt = $wpdb->get_var( $sql );
								$sql = "DELETE FROM ".$wpdb->prefix."symposium_chat2 WHERE sent <= ".(time() - $_POST['purge_chat'] * 24 * 60 * 60);	
								$wpdb->query( $sql );
								
								echo "<div style='margin-top:10px; border:1px solid #060;background-color: #9f9; border-radius:5px;padding-left:8px; margin-bottom:10px;'>";
								echo "Chat purged: ".$cnt;
								echo "</div>";
							}
							
							// Purge topics
							if (isset($_POST['purge_topics']) && $_POST['purge_topics'] != '' && is_numeric($_POST['purge_topics']) ) {
								
								$sql = "SELECT tid FROM ".$wpdb->prefix."symposium_topics WHERE topic_started <= '".date("Y-m-d H:i:s",strtotime('-'.$_POST['purge_topics'].' days'))."'";	
								$topics = $wpdb->get_results( $sql );
								
								$cnt = 0;
								if ($topics) {
									foreach ($topics as $topic) {
										$cnt++;
										$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."symposium_subs WHERE tid = %d", $topic->tid));
										$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."symposium_topics WHERE tid = %d", $topic->tid));
									}
								}
								
								echo "<div style='margin-top:10px; border:1px solid #060;background-color: #9f9; border-radius:5px;padding-left:8px; margin-bottom:10px;'>";
								echo "Topics purged: ".$cnt;
								echo "</div>";
							}
			
							echo '<p>'.__('Forum activity and chat purged are <strong>deleted</strong> - you cannot undo this. Take a backup first!', WPS_TEXT_DOMAIN).'</p>';
				
							echo '<form action="" method="post"><table style="margin-bottom:10px">';
							echo '<tr><td style="border:0">'.__('Chat older than', WPS_TEXT_DOMAIN);
								echo '</td><td style="border:0"><input type="text" size="3" name="purge_chat"> ';
								echo __('days', WPS_TEXT_DOMAIN)."</td></tr>";
							echo '<tr><td style="border:0">'.__('Forum topics older than', WPS_TEXT_DOMAIN);
								echo '</td><td style="border:0"><input type="text" size="3" name="purge_topics"> ';
								echo __('days', WPS_TEXT_DOMAIN)."</td></tr></table>";
							echo '<input type="submit" class="button-primary delete" value="'.__('Purge', WPS_TEXT_DOMAIN).'">';
							echo '</form><br />';
						echo '</td></tr></table>';
		
					echo "</div>";
					echo "<div style='width:50%; float:right; padding-bottom:15px;'>";
		
						// Permalinks
						echo '<table class="widefat" style="float:right;"><tr><td style="padding:0 0 0 10px">';
							echo '<a name="perma"></a>';
							echo '<h2 style="margin-bottom:10px">'.sprintf(__('%s Permalinks', WPS_TEXT_DOMAIN), WPS_WL_SHORT).'</h2>';
							echo '<p style="font-weight:bold">'.__('It is recommended that you test these before implementing.', WPS_TEXT_DOMAIN).'</p>';
							
							// Act on submit
							$just_switched_on = false;
							if (isset($_POST[ 'symposium_permalinks' ])) {
								if ( $_POST[ 'symposium_permalinks_enable' ] == 'on' ) {
									// If switching on, default categories to on
									if (!get_option(WPS_OPTIONS_PREFIX.'_permalink_structure')) {
										update_option(WPS_OPTIONS_PREFIX.'_permalinks_cats', 'on');	
										$just_switched_on = true;			   	    
									} else {
										// If already on, act on categories checkbox
										if (isset($_POST[ 'symposium_permalinks_cats' ])) {
											update_option(WPS_OPTIONS_PREFIX.'_permalinks_cats', 'on');
										} else {
											update_option(WPS_OPTIONS_PREFIX.'_permalinks_cats', '');
										}
									}
									update_option(WPS_OPTIONS_PREFIX.'_permalink_structure', 'on');				   	    
									
								} else {
			
									if (get_option(WPS_OPTIONS_PREFIX.'_permalink_structure')) {
										echo '<p>'.__('The first time you enable permalinks, please be patient while your database is updated.', WPS_TEXT_DOMAIN).'</p>'; 
									}
									delete_option('symposium_permalink_structure');
									delete_option('symposium_permalinks_cats');
								}
							}
			
							if ( get_option('permalink_structure') != '' ) {
			
								echo '<form method="post" action="#perma">';
			
									if ( get_option(WPS_OPTIONS_PREFIX.'_permalink_structure')  ) {
										
										// Can't work with Forum in AJAX mode
										if (get_option(WPS_OPTIONS_PREFIX.'_forum_ajax')) {
											update_option(WPS_OPTIONS_PREFIX.'_forum_ajax', '');
											echo '<p style="color:green; font-weight:bold;">'.__('Forum "AJAX mode" has been disabled, as this is not compatible with permalinks.', WPS_TEXT_DOMAIN).'</p>'; 
										}
				
										// Do a check to ensure all forum categories have a slug
										$sql = "SELECT * FROM ".$wpdb->prefix."symposium_cats WHERE stub = ''";
										$cats = $wpdb->get_results($sql);
										if ($cats) {
											foreach ($cats as $cat) {
												$stub = __wps__create_stub($cat->title);
												$sql = "UPDATE ".$wpdb->prefix."symposium_cats SET stub = '".$stub."' WHERE cid = %d";
												$wpdb->query($wpdb->prepare($sql, $cat->cid));
												if (WPS_DEBUG) echo $wpdb->last_query.'<br>';
											}
										}
										// Do a check to ensure all forum topics have a slug
										$sql = "SELECT * FROM ".$wpdb->prefix."symposium_topics WHERE topic_parent = 0 AND stub = '' ORDER BY tid DESC";
										$topics = $wpdb->get_results($sql);
										if ($topics) {
											foreach ($topics as $topic) {
												$stub = __wps__create_stub($topic->topic_subject);
												$sql = "UPDATE ".$wpdb->prefix."symposium_topics SET stub = '".$stub."' WHERE tid = %d";
												$wpdb->query($wpdb->prepare($sql, $topic->tid));
												if (WPS_DEBUG) echo $wpdb->last_query.'<br>';
											}
										} 
			
										// update any POSTed values or update default values if necessary
										$reset = isset($_POST['symposium_permalinks_reset']) ? true : false;
			
										if ( (!$just_switched_on) && (!$reset) && ( get_option(WPS_OPTIONS_PREFIX.'_rewrite_forum_single') || get_option(WPS_OPTIONS_PREFIX.'_rewrite_forum_double') || get_option(WPS_OPTIONS_PREFIX.'_rewrite_members') ) )  {
												
												if (isset($_POST['symposium_permalinks']) && $_POST['symposium_permalinks'] == 'Y') {
													update_option(WPS_OPTIONS_PREFIX.'_rewrite_forum_single', $_POST['symposium_rewrite_forum_single']);
													update_option(WPS_OPTIONS_PREFIX.'_rewrite_forum_single_target', $_POST['symposium_rewrite_forum_single_target']);
													update_option(WPS_OPTIONS_PREFIX.'_rewrite_forum_double', $_POST['symposium_rewrite_forum_double']);
													update_option(WPS_OPTIONS_PREFIX.'_rewrite_forum_double_target', $_POST['symposium_rewrite_forum_double_target']);
													update_option(WPS_OPTIONS_PREFIX.'_rewrite_members', $_POST['symposium_rewrite_members']);
													update_option(WPS_OPTIONS_PREFIX.'_rewrite_members_target', $_POST['symposium_rewrite_members_target']);
												}
												flush_rewrite_rules();
												
										} else {
											
											// check that options exist if not put in defaults
			//								if ( ($reset) || ( !get_option(WPS_OPTIONS_PREFIX.'_rewrite_forum_single') && !get_option(WPS_OPTIONS_PREFIX.'_rewrite_forum_double')  && !get_option(WPS_OPTIONS_PREFIX.'_rewrite_members') ) ) {
			
												// get forum path and pagename
												$sql = "SELECT ID, post_title FROM ".$wpdb->prefix."posts WHERE post_content LIKE  '%[symposium-forum]%' AND post_status =  'publish' AND post_type =  'page'";
												$page = $wpdb->get_row($sql);
												$permalink = __wps__get_url('forum');
												$p = strtolower(trim(str_replace(get_bloginfo('url'), '', $permalink), '/'));
												$post_title = rawurlencode($page->post_title);
			
												// get profile path and pagename
												$sql = "SELECT ID, post_title FROM ".$wpdb->prefix."posts WHERE post_content LIKE  '%[symposium-profile]%' AND post_status =  'publish' AND post_type =  'page'";
												$page = $wpdb->get_row($sql);
												$permalink = __wps__get_url('profile');
												$m = strtolower(trim(str_replace(get_bloginfo('url'), '', $permalink), '/'));
												$members_title = rawurlencode($page->post_title);
												
												update_option(WPS_OPTIONS_PREFIX.'_rewrite_forum_single', $p.'/([^/]+)/?');
												update_option(WPS_OPTIONS_PREFIX.'_rewrite_forum_single_target', 'index.php?pagename='.$post_title.'&stub=/$matches[1]');
												update_option(WPS_OPTIONS_PREFIX.'_rewrite_forum_double', $p.'/([^/]+)/([^/]+)/?');
												update_option(WPS_OPTIONS_PREFIX.'_rewrite_forum_double_target', 'index.php?pagename='.$post_title.'&stub=$matches[1]/$matches[2]');
												update_option(WPS_OPTIONS_PREFIX.'_rewrite_members', $m.'/([^/]+)/?');
												update_option(WPS_OPTIONS_PREFIX.'_rewrite_members_target', 'index.php?pagename='.$members_title.'&stub=$matches[1]');
			
												flush_rewrite_rules();
												echo '<p style="color:green; font-weight:bold;">'.__('Re-write rules saved as default suggested values.', WPS_TEXT_DOMAIN).'</p>'; 
												
												update_option(WPS_OPTIONS_PREFIX.'_permalinks_cats', 'on');
			
			//								}
										}
			
										// Flush WP permalinks to clean up
										global $wp_rewrite;				
										$wp_rewrite->flush_rules();
			
										// Display fields allowing them to be altered												
																
										echo '<strong>'.__('Forum', WPS_TEXT_DOMAIN).'</strong><br />';
										echo '<input type="text" name="symposium_rewrite_forum_single" style="width:150px" value="'.get_option(WPS_OPTIONS_PREFIX.'_rewrite_forum_single').'" /> => ';
										echo '<input type="text" name="symposium_rewrite_forum_single_target" style="width:400px" value="'.get_option(WPS_OPTIONS_PREFIX.'_rewrite_forum_single_target').'" /><br />';
										echo '<input type="text" name="symposium_rewrite_forum_double" style="width:150px" value="'.get_option(WPS_OPTIONS_PREFIX.'_rewrite_forum_double').'" /> => ';
										echo '<input type="text" name="symposium_rewrite_forum_double_target" style="width:400px" value="'.get_option(WPS_OPTIONS_PREFIX.'_rewrite_forum_double_target').'" /><br />';
										echo '<br /><strong>'.__('Member Profile', WPS_TEXT_DOMAIN).'</strong><br />';
										echo '<input type="text" name="symposium_rewrite_members" style="width:150px" value="'.get_option(WPS_OPTIONS_PREFIX.'_rewrite_members').'" /> => ';
										echo '<input type="text" name="symposium_rewrite_members_target" style="width:400px" value="'.get_option(WPS_OPTIONS_PREFIX.'_rewrite_members_target').'" /><br /><br />';
										
										echo '<input type="hidden" name="symposium_permalinks" value="Y">';
										echo '<input type="checkbox" name="symposium_permalinks_enable" CHECKED > '.sprintf(__('%s Permalinks enabled', WPS_TEXT_DOMAIN), WPS_WL_SHORT).'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
										echo '<input type="checkbox" name="symposium_permalinks_cats"';
											if (get_option(WPS_OPTIONS_PREFIX.'_permalinks_cats')) echo ' CHECKED';
											echo '> '.__('Include categories in forum hyperlinks', WPS_TEXT_DOMAIN).'<br /><br />';
										echo '<input type="checkbox" name="symposium_permalinks_reset" /> '.__('Reset to default suggested values (if you have altered page names for example)', WPS_TEXT_DOMAIN);
										echo '<p style="margin: 10px 0 10px 0"><input type="submit" class="button-primary" value="'.__('Update', WPS_TEXT_DOMAIN).'" />';
										
									} else {
										echo '<input type="hidden" name="symposium_permalinks" value="Y">';
										echo '<input type="checkbox" name="symposium_permalinks_enable"> '.sprintf(__('Check to enable %s Permalinks', WPS_TEXT_DOMAIN), WPS_WL_SHORT);
										echo '<p style="margin: 10px 0 10px 0"><input type="submit" class="button-primary" value="'.__('Update', WPS_TEXT_DOMAIN).'" />';
									}
			
			
								echo '</form>';
			
							} else {
								echo '<p>'.__('You cannot use Permalinks if your WordPress <a href="options-permalink.php">permalink setting</a> is default.', WPS_TEXT_DOMAIN).'</p>'; 
							}
							
						echo '</td></tr></table>';
		
						// ********** Test Email   	
						echo '<table class="widefat" style="margin-top:10px; float:right;"><tr><td style="padding:0 0 0 10px">';
						
							if( isset($_POST[ 'symposium_testemail' ]) && $_POST[ 'symposium_testemail' ] == 'Y' && $_POST['symposium_testemail_address'] != '' ) {
								$to = $_POST['symposium_testemail_address'];
								if (__wps__sendmail($to, sprintf("%s Test Email", WPS_WL), __("This is a test email sent from", WPS_TEXT_DOMAIN)." ".get_bloginfo('url'))) {
									echo "<div class='updated'><p>";
									$from = get_option(WPS_OPTIONS_PREFIX.'_from_email');
									echo sprintf(__('Email sent to %s from', WPS_TEXT_DOMAIN), $to);
									echo ' '.$from;
									echo "</p></div>";
								} else {
									echo "<div class='error'><p>".__("Email failed to send", WPS_TEXT_DOMAIN).".</p></div>";
								}
							}
							echo '<h2 style="margin-bottom:10px">'.__('Send a test email', WPS_TEXT_DOMAIN).'</h2>';
				
							echo '<p>'.__('Enter a valid email address to test sending an email from the server', WPS_TEXT_DOMAIN).'.</p>';
							echo '<form method="post" action="">';
							echo '<input type="hidden" name="symposium_testemail" value="Y">';
							echo '<p><input type="text" name="symposium_testemail_address" value="" style="margin-right:15px;height:24px;width:300px" class="regular-text">';
							echo '<input type="submit" name="Submit" class="button-primary" value="'.__('Send email', WPS_TEXT_DOMAIN).'" /></p';
							echo '</form>';
							
						echo '</td></tr></table>';
		
						// Image uploading
						echo '<table class="widefat" style="margin-top:10px; float:right;"><tr><td style="padding:0 0 0 10px">';
							echo '<a name="image"></a>';
							echo '<h2 style="margin-bottom:10px">'.__('Image Uploading', WPS_TEXT_DOMAIN).'</h2>';
						
							echo "<div>";
							echo "<div id='symposium_user_login' style='display:none'>".strtolower($current_user->user_login)."</div>";
							echo "<div id='symposium_user_email' style='display:none'>".strtolower($current_user->user_email)."</div>";
							if (get_option(WPS_OPTIONS_PREFIX.'_img_db') == "on") {
								echo __("<p>You are storing images in the database.</p>", WPS_TEXT_DOMAIN);
							} else {
								echo __("<p>You are storing images in the file system.</p>", WPS_TEXT_DOMAIN);			
					
								if (file_exists(get_option(WPS_OPTIONS_PREFIX.'_img_path'))) {
									echo "<p>".sprintf(__('The folder %s exists, where images uploaded will be placed.', WPS_TEXT_DOMAIN), get_option(WPS_OPTIONS_PREFIX.'_img_path'))."</p>";
								} else {
									echo "<p>".sprintf(__('The folder %s does not exist, where images uploaded will be placed, trying to create...', WPS_TEXT_DOMAIN), get_option(WPS_OPTIONS_PREFIX.'_img_path'))."</p>";
									if (!mkdir(get_option(WPS_OPTIONS_PREFIX.'_img_path'), 0755, true)) {
										echo '<p>Failed to create '.get_option(WPS_OPTIONS_PREFIX.'_img_path').'.</p>';
										$error = error_get_last();
									    echo '<p>'.$error['message'].'<br />';
									    echo sprintf(__('For info, this script is in %s.', WPS_TEXT_DOMAIN), __FILE__);
									} else {
										echo '<p>Created '.get_option(WPS_OPTIONS_PREFIX.'_img_path').'.</p>';
									}
								}
								
								if (get_option(WPS_OPTIONS_PREFIX.'_img_url') == '') {
									echo "<p>".$fail.__('You must update the URL for your images on the <a href="admin.php?page=symposium_settings">Settings</a>.', WPS_TEXT_DOMAIN).$fail2."</p>";
								} else {
									echo "<p>".__('The URL to your images folder is', WPS_TEXT_DOMAIN)." <a href='".get_option(WPS_OPTIONS_PREFIX.'_img_url')."'>".get_option(WPS_OPTIONS_PREFIX.'_img_url')."</a>.</p>";
								}
			
								$tmpDir = get_option(WPS_OPTIONS_PREFIX.'_img_path').'/tmp';
								$tmpFile = '.txt';
								$tmpFile = time().'.tmp';
								$targetTmpFile = $tmpDir.'/'.$tmpFile;
								
								// Does tmp folder exist?
								if (!file_exists($tmpDir)) {
									if (@mkdir($tmpDir)) {
										echo '<p>'.sprintf(__('The %s temporary image folder (%s) does not currently exist', WPS_TEXT_DOMAIN), WPS_WL_SHORT, $tmpDir);
										echo __(', and has been created.', WPS_TEXT_DOMAIN).'</p>';
									} else {
										echo '<p>'.$fail.sprintf(__('The %s temporary image folder (%s) does not currently exist', WPS_TEXT_DOMAIN), WPS_WL_SHORT, $tmpDir);
										echo __(', and could not be created - please check permissions of this path.', WPS_TEXT_DOMAIN).$fail2.'</p>';
									}
								} else {
									echo '<p>'.sprintf(__('The %s temporary image folder (%s) exists.', WPS_TEXT_DOMAIN), WPS_WL_SHORT, $tmpDir).'</p>';
									
									// Check creating a temporary file in tmp
									if (touch($targetTmpFile)) {
										@unlink($targetTmpFile);
										echo "<p>".sprintf(__('Temporary file (%s) created and removed successfully.', WPS_TEXT_DOMAIN), $tmpFile)."</p>";
									} else {
										echo '<p>'.$fail.sprintf(__('A temporary file (%s) could not be created (in %s), please check permissions.', WPS_TEXT_DOMAIN), $targetTmpFile, $tmpDir);
									}
								}
								
							}
							echo "</div>";
						echo '</td></tr></table>';

						// Link to licence
						echo '<table class="widefat" style="margin-top:10px; float:right;"><tr><td style="padding:0 0 0 10px">';
							echo '<a name="image"></a>';
							echo '<h2 style="margin-bottom:10px">'.__('End User Licence Agreement', WPS_TEXT_DOMAIN).'</h2>';
						
							echo "<p>".sprintf(__("If you do not accept the terms of the <a href='%s'>licence</a>, please remove this plugin", WPS_TEXT_DOMAIN), WPS_PLUGIN_URL."/licence.txt").".</p>";
							
						echo '</td></tr></table>';
								
						// ********** Daily Digest 
						echo '<table class="widefat" style="margin-top:10px; float:right;"><tr><td style="padding:0 0 0 10px">';
							echo '<h2 style="margin-bottom:10px">'.__('Daily Digest', WPS_TEXT_DOMAIN).'</h2>';
				
							if( isset($_POST[ 'symposium_dailydigest' ]) && $_POST[ 'symposium_dailydigest' ] == 'Y' ) {
								$to_users = isset($_POST['symposium_dailydigest_users']) ? $_POST['symposium_dailydigest_users'] : '';
								$to_admin = isset($_POST['symposium_dailydigest_admin']) ? $_POST['symposium_dailydigest_admin'] : '';
								if ($to_users == 'on' || $to_admin == 'on') {
									echo "<div style='border:1px solid #060;background-color: #9f9; border-radius:5px;padding-left:8px; margin-bottom:10px;'>Running...<br />";
									if ($to_users == "on") {
										echo "Sending summary report and to all users...<br />";
										$success = __wps__notification_do_jobs('send_admin_summary_and_to_users');
									}								
									if ($to_admin == "on") {
										echo "Sending summary report and daily digest to admin only... ";
										$success = __wps__notification_do_jobs('symposium_dailydigest_admin');
									}			
									echo $success;
									echo "Complete.<br />";
									if ($success == 'OK' && $to_admin == 'on') {
										echo "Summary report sent to ".get_bloginfo('admin_email').".<br />";
									}
									echo "</div>";
								}
							}
							echo '<p>'.__('The Daily Digest also performs some basic database cleanup operations, which can be run at any time', WPS_TEXT_DOMAIN).'.</p>';
							echo '<form method="post" action="">';
							echo '<input type="hidden" name="symposium_dailydigest" value="Y">';
							echo '<input type="checkbox" name="symposium_dailydigest_admin" > '.__('Send Daily Digest and summary to admin', WPS_TEXT_DOMAIN).' ('.get_bloginfo('admin_email').')<br />';
							echo '<input type="checkbox" name="symposium_dailydigest_users" > '.__('Send Daily Digest to users now (includes summary to admin)', WPS_TEXT_DOMAIN);
							echo '<p style="margin-top:10px"><input type="submit" name="Submit" class="button-primary" value="'.__('Send Daily Digest', WPS_TEXT_DOMAIN).'" /></p>';
							echo '</form>';
						echo '</td></tr></table>';
		
					echo "</div>";
					
					echo "<div style='clear:both;'></div>";
		
					// ********** Stylesheets	
					echo '<table class="widefat" style="margin-top:10px; float:right;"><tr><td style="padding:0 0 0 10px">';
					
						echo '<h2 style="margin-bottom:10px">'.__('Stylesheets', WPS_TEXT_DOMAIN).'</h2>';
				
						// CSS check
						$myStyleFile = WPS_PLUGIN_DIR . '/css/'.get_option(WPS_OPTIONS_PREFIX.'_wps_css_file');
						if ( !file_exists($myStyleFile) ) {
							echo $fail . sprintf(__('Stylesheet (%s) not found.', WPS_TEXT_DOMAIN), $myStyleFile) . $fail2;
						} else {
							echo "<p style='color:green; font-weight:bold;'>" . sprintf(__('Stylesheet (%s) found.', WPS_TEXT_DOMAIN), $myStyleFile) . "</p>";
						}
							
						// ********** Javascript			
						echo '<h2 style="margin-bottom:10px">'.__('Javascript', WPS_TEXT_DOMAIN).'</h2>';
				
						// JS check
						$myJSfile = WPS_PLUGIN_DIR . '/js/'.get_option(WPS_OPTIONS_PREFIX.'_wps_js_file');
						if ( !file_exists($myJSfile) ) {
							echo $fail . sprintf(__('Javascript file (%s) not found.', WPS_TEXT_DOMAIN), $myJSfile) . $fail2;
						} else {
							echo "<p style='color:green; font-weight:bold;'>" . sprintf(__("Javascript file (%s) found.", WPS_TEXT_DOMAIN), $myJSfile) . "</p>";
						}
						echo "<p>" . sprintf(__("If you find that certain %s things don't work, like buttons or uploading profile photos, it is probably because the %s Javascript file isn't loading and/or working. Usually, this is because of another WordPress plugin. Try deactivating all non-%s plugins and switching to the TwentyEleven theme. If %s then works, re-activate the plug-ins one at a time until the error re-occurs, this will help you locate the plugin that is clashing. Then switch your theme back. Also try using Firefox, with the Firebug add-in installed - this will show you where the Javascript error is occuring.", WPS_TEXT_DOMAIN), WPS_WL, WPS_WL, WPS_WL_SHORT, WPS_WL_SHORT)."</p>";
						echo "<p>".__("If you are experiencing problems, <a href='http://www.wpsymposium.com/trythisfirst' target='_blank'>try this first</a>.", WPS_TEXT_DOMAIN)."</p>";
								
						echo "<div id='jstest'>".$fail.sprintf(__( "You have problems with Javascript. This may be because a plugin is loading another version of jQuery or jQuery UI - try deactivating all plugins apart from %s plugins, and re-activate them one at a time until the error re-occurs, this will help you locate the plugin that is clashing. It might also be because there is an error in a JS file, either the symposium.js or another plugin script.", WPS_TEXT_DOMAIN), WPS_WL_SHORT).$fail2."</div>";
					echo '</td></tr></table>';
		
			
					// ********** bbPress migration
					echo '<table class="widefat" style="margin-top:10px; float:right;"><tr><td style="padding:0 0 0 10px">';
						echo '<a name="bbpress"></a>';
						echo '<h2 style="margin-bottom:10px">'.__('bbPress Migration', WPS_TEXT_DOMAIN).'</h2>';
				
						// migrate any chosen bbPress forums
						if( isset($_POST[ 'symposium_bbpress' ]) && $_POST[ 'symposium_bbpress' ] == 'Y' ) {
							$id = $_POST['bbPress_forum'];
							$cat_title = $_POST['bbPress_category'];
							
							$success = true;
							$success_message = "";
							
							if ($cat_title != '') {
								
								$sql = "SELECT * FROM ".$wpdb->prefix."posts WHERE post_type = 'forum' AND ID = %d";
								$forum = $wpdb->get_row($wpdb->prepare($sql, $id));
								$success_message .= "Creating &quot;".$cat_title."&quot; from &quot;".$forum->post_title."&quot;. ";

								$stub = trim(preg_replace("/[^A-Za-z0-9 ]/",'',$cat_title));
								$stub = strtolower(str_replace(' ', '-', $stub));
								$sql = "SELECT COUNT(*) FROM ".$wpdb->prefix."symposium_cats WHERE stub = '".$stub."'";
								$cnt = $wpdb->get_var($sql);
								if ($cnt > 0) $stub .= "-".$cnt;
								$stub = str_replace('--', '-', $stub);

								// Add new forum category
								if ( $wpdb->query( $wpdb->prepare( "
									INSERT INTO ".$wpdb->prefix.'symposium_cats'."
									( 	title, 
										cat_parent,
										listorder,
										cat_desc,
										allow_new,
										hide_breadcrumbs,
										hide_main,
										stub
									)
									VALUES ( %s, %d, %d, %s, %s, %s, %s, %s )", 
									array(
										$cat_title, 
										0,
										0,
										$forum->post_content,
										'on',
										'',
										'',
										$stub
										) 
									) )
								) {
									
									$success_message .= __("Forum created OK with stub ".$stub.".", WPS_TEXT_DOMAIN)."<br />";
									
									$new_forum_id = $wpdb->insert_id;
			
									$sql = "SELECT * FROM ".$wpdb->prefix."posts WHERE post_type = 'topic' AND post_parent = %d";
									$topics = $wpdb->get_results($wpdb->prepare($sql, $id));
									$success_message .= "Migrating topics to &quot;".$cat_title."&quot;.<br />";
									
									if ($topics) {
										
										$failed = 0;
										foreach ($topics AS $topic) {
											
											$stub = __wps__create_stub($topic->post_title);

											if ( $wpdb->query( $wpdb->prepare( "
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
													for_info,
													topic_group,
													stub
												)
												VALUES ( %s, %d, %s, %s, %s, %d, %d, %d, %s, %s, %d, %s )", 
												array(
													$topic->post_title, 
													$new_forum_id,
													$topic->post_content, 
													$topic->post_modified,
													$topic->post_date, 
													$topic->post_author, 
													0,
													0,
													'on',
													'',
													0, 
													$stub
													) 
												) ) ) {
			
													$success_message .= "Migrated &quot;".$topic->post_title."&quot; OK.<br />";	
													
													$new_topic_id = $wpdb->insert_id;
							
													$sql = "SELECT * FROM ".$wpdb->prefix."posts WHERE post_type = 'reply' AND post_parent = %d";
													$replies = $wpdb->get_results($wpdb->prepare($sql, $topic->ID));
													
													if ($replies) {
														$success_message .= "Migrating replies to &quot;".$topic->post_title."&quot; OK. ";	
													
														$failed_replies = 0;
														foreach ($replies AS $reply) {
			
															if ( $wpdb->query( $wpdb->prepare( "
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
																$new_forum_id,
																$reply->post_content, 
																$reply->post_modified,
																$reply->post_date, 
																$reply->post_author, 
																$new_topic_id,
																0,
																'on',
																0,
																''
																) 
															) ) ) {
															} else {
																$failed_replies++;
															}
															
														}
			
														if ($failed_replies == 0) {
								
															$success_message .= __("Replies migrated OK.", WPS_TEXT_DOMAIN)."<br />";
															
														} else {
															$success_message .= sprintf(__("Failed to migrate %d replies.", WPS_TEXT_DOMAIN), $failed_replies)."<br />";
															$success = false;
														}
			
													} else {
														$success_message .= __("No replies to migrate.", WPS_TEXT_DOMAIN)."<br />";
													}
											
											} else {
												$failed++;
											}
											   
										}
										
										if ($failed == 0) {
				
											$success_message .= __("Topics and replies migrated OK.", WPS_TEXT_DOMAIN)."<br />";
											
										} else {
											$success_message .= sprintf(__("Failed to migrate %d topics.", WPS_TEXT_DOMAIN), $failed)."<br />";
											$success = false;
										}
									} else {
											$success_message .= __("No topics to migrate.", WPS_TEXT_DOMAIN)."<br />";
									}
									
								} else {
									$success_message .= __("Forum failed to migrate", WPS_TEXT_DOMAIN)."<br />";
									$success_message .= $wpdb->last_query."<br />";
									$success = false;
								}
									
									
							} else {
								$success_message .= __('Please enter a new forum category title', WPS_TEXT_DOMAIN);
							}
							
							if ($success) {
								echo "<div style='margin-top:10px;border:1px solid #060;background-color: #9f9; border-radius:5px;padding-left:8px; margin-bottom:10px;'>";
								echo $success_message;
								echo "Complete.<br />";			
								echo "</div>";
							} else {
								echo "<div style='margin-top:10px;border:1px solid #600;background-color: #f99; border-radius:5px;padding-left:8px; margin-bottom:10px;'>";
								echo $success_message;
								echo "</div>";
							}
							
						}
				
						// check to see if any bbPress forums exist
						$sql = "SELECT * FROM ".$wpdb->prefix."posts WHERE post_type = 'forum'";
						$forums = $wpdb->get_results($sql);
						if ($forums) {
							echo '<p>'.sprintf(__('If you have bbPress v2 plugin forums, you can migrate them to your %s forum as a new category.', WPS_TEXT_DOMAIN), WPS_WL).'</p>';
							echo '<p>'.__('This migration works with the <a href="" target="_blank">WordPress bbPress plugin v2</a>. If you are running a previous or stand-alone version of bbPress, you should upgrade your installation first.', WPS_TEXT_DOMAIN).'</p>';
							echo '<p>'.__('You should take a backup of your database before migrating, just in case there is a problem.', WPS_TEXT_DOMAIN).'</p>';
							echo '<form method="post" action="#bbpress">';
							echo '<input type="hidden" name="symposium_bbpress" value="Y">';
							echo __('Select forum to migrate:', WPS_TEXT_DOMAIN).' ';
							echo '<select name="bbPress_forum">';
							foreach ($forums AS $forum) {
								echo '<option value="'.$forum->ID.'">'.$forum->post_title.'</option>';
							}
							echo '</select><br />';
							echo __('Enter new forum category title:', WPS_TEXT_DOMAIN).' ';
							echo '<input type="text" name="bbPress_category" />';
							echo '<p><em>' . __("Although your bbPress forum is not altered, and only new categories/topics/replies are added, it is recommended that you backup your database first.", WPS_TEXT_DOMAIN) . '</em></p>';
							echo '<p class="submit"><input type="submit" name="Submit" class="button-primary" value="'.__('Migrate bbPress', WPS_TEXT_DOMAIN).'" /></p>';
							echo '</form>';
						} else {
							echo '<p>'.__('No bbPress forums found', WPS_TEXT_DOMAIN).'.</p>';
						}
					echo '</td></tr></table>';
		
		
					// ********** Mingle migration
					echo '<table class="widefat" style="margin-top:10px; float:right;"><tr><td style="padding:0 0 0 10px">';			
						echo '<a name="mingle"></a>';
						echo '<h2 style="margin-bottom:10px">'.__('Mingle Migration', WPS_TEXT_DOMAIN).'</h2>';
			
						// migrate any chosen mingle forums
						if( isset($_POST[ 'symposium_mingle' ]) && $_POST[ 'symposium_mingle' ] == 'Y' ) {
							$id = $_POST['mingle_forum'];
							$cat_title = $_POST['mingle_category'];
							
							$success = true;
							$success_message = "";
							
							if ($cat_title != '') {
								
								$sql = "SELECT * FROM ".$wpdb->prefix."forum_forums WHERE id = %d";
								$forum = $wpdb->get_row($wpdb->prepare($sql, $id));
								$success_message .= "Creating &quot;".$cat_title."&quot; from &quot;".$forum->name."&quot;. ";
			
								// Add new forum category
								if ( $wpdb->query( $wpdb->prepare( "
									INSERT INTO ".$wpdb->prefix.'symposium_cats'."
									( 	title, 
										cat_parent,
										listorder,
										cat_desc,
										allow_new
									)
									VALUES ( %s, %d, %d, %s, %s )", 
									array(
										$cat_title, 
										0,
										0,
										$forum->description,
										'on'
										) 
									) )
								) {
									
									$success_message .= __("Forum created OK.", WPS_TEXT_DOMAIN)."<br />";
									
									$new_forum_id = $wpdb->insert_id;
									
									// Get Mingle threads	
									$sql = "SELECT * FROM ".$wpdb->prefix."forum_threads WHERE parent_id = %d";
									$topics = $wpdb->get_results($wpdb->prepare($sql, $id));
									$success_message .= "Migrating topics to &quot;".$cat_title."&quot;.<br />";
									
									if ($topics) {
										
										$failed = 0;								
										foreach ($topics AS $topic) {
											
											if ( $wpdb->query( $wpdb->prepare( "
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
													for_info,
													topic_group
												)
												VALUES ( %s, %d, %s, %s, %s, %d, %d, %d, %s, %s, %d )", 
												array(
													$topic->subject, 
													$new_forum_id,
													'nopost', 
													$topic->last_post,
													$topic->date, 
													$topic->starter, 
													0,
													0,
													'on',
													'',
													0
													) 
												) ) ) {
													
													// Set up topic, now add all the replies	
													$success_message .= "Migrated &quot;".$topic->subject."&quot; OK.<br />";	
													
													$new_topic_id = $wpdb->insert_id;
							
													$sql = "SELECT * FROM ".$wpdb->prefix."forum_posts WHERE parent_id = %d";
													$replies = $wpdb->get_results($wpdb->prepare($sql, $topic->id));
													
													if ($replies) {
														$success_message .= "Migrating replies to &quot;".$topic->subject."&quot;.<br />";	
													
														$failed_replies = 0;
														$done_first_reply = false;
														foreach ($replies AS $reply) {
															
															if ($done_first_reply) {
			
																if ( $wpdb->query( $wpdb->prepare( "
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
																	$new_forum_id,
																	$reply->text, 
																	$reply->date,
																	$reply->date, 
																	$reply->author_id, 
																	$new_topic_id,
																	0,
																	'on',
																	0,
																	''
																	) 
																) ) ) {
																} else {
																	$failed_replies++;
																}
																
															} else {
																$done_first_reply = true;
																if ( $wpdb->query( $wpdb->prepare("UPDATE ".$wpdb->prefix."symposium_topics SET topic_post = '".$reply->text."' WHERE tid = %d", $new_topic_id) ) ) {
																	$success_message .= "Updated topic with intial post OK.<br />";
																} else {
																	$failed_replies++;
																}	
																
															}
															
														}
			
														if ($failed_replies == 0) {
								
															$success_message .= __("Replies migrated OK.", WPS_TEXT_DOMAIN)."<br />";
															
														} else {
															$success_message .= sprintf(__("Failed to migrate %d replies.", WPS_TEXT_DOMAIN), $failed_replies)."<br />";
															$success = false;
														}
			
													} else {
														$success_message .= __("No replies to migrate.", WPS_TEXT_DOMAIN)."<br />";
													}
																					
											} else {
												$failed++;
											}
											   
										}
										
										if ($failed == 0) {
				
											$success_message .= __("Topics and replies migrated OK.", WPS_TEXT_DOMAIN)."<br />";
											
										} else {
											$success_message .= sprintf(__("Failed to migrate %d topics.", WPS_TEXT_DOMAIN), $failed)."<br />";
											$success = false;
										}
									} else {
											$success_message .= __("No topics to migrate.", WPS_TEXT_DOMAIN)."<br />";
									}
									
								} else {
									$success_message .= __("Forum failed to migrate", WPS_TEXT_DOMAIN)."<br />";
									$success_message .= $wpdb->last_query."<br />";
									$success = false;
								}
									
									
							} else {
								$success_message .= __('Please enter a new forum category title', WPS_TEXT_DOMAIN);
							}
							
							if ($success) {
								echo "<div style='margin-top:10px;border:1px solid #060;background-color: #9f9; border-radius:5px;padding-left:8px; margin-bottom:10px;'>";
									echo 'Please now check the forum for your new migrated category. If you need to, you can move the position of the category (or delete it) in <a href="admin.php?page=symposium_categories">forum categories</a>.<br />';
									echo 'Migration complete. ';
									echo '<a href="javascript:void(0)" class="symposium_expand">View report</a>';
									echo '<div class="expand_this" style="display:none">';
										echo $success_message;
									echo "</div>";
								echo "</div>";
							} else {
								echo "<div style='margin-top:10px;border:1px solid #600;background-color: #f99; border-radius:5px;padding-left:8px; margin-bottom:10px;'>";
								echo $success_message;
								echo "</div>";
							}
							
						}
				
						// check to see if any Mingle forums exist
						if($wpdb->get_var("show tables like '%".$wpdb->prefix."forum_forums%'") == $wpdb->prefix."forum_forums") {
							$sql = "SELECT * FROM ".$wpdb->prefix."forum_forums";
							$forums = $wpdb->get_results($sql);
							if ($forums) {
								echo '<p>'.sprintf(__('If you have the Mingle v1.0.33 (or higher) plugin, you can migrate the forums to your %s forum as a new category.', WPS_TEXT_DOMAIN), WPS_WL).'</p>';
								echo '<p>'.__('This migration works with the <a href="" target="_blank">WordPress Mingle plugin</a>. If you are running a previous version of Mingle, you should upgrade your installation first.', WPS_TEXT_DOMAIN).'</p>';
								echo '<p>'.__('You should take a backup of your database before migrating, just in case there is a problem.', WPS_TEXT_DOMAIN).'</p>';
								echo '<form method="post" action="#mingle">';
								echo '<input type="hidden" name="symposium_mingle" value="Y">';
								echo __('Select forum to migrate:', WPS_TEXT_DOMAIN).' ';
								echo '<select name="mingle_forum">';
								foreach ($forums AS $forum) {
									echo '<option value="'.$forum->id.'">'.$forum->name.' ('.$forum->description.')</option>';
								}
								echo '</select><br />';
								echo __('Enter new forum category title:', WPS_TEXT_DOMAIN).' ';
								echo '<input type="text" name="mingle_category" />';
								echo '<p><em>' . __("Although your Mingle forum is not altered, and only new categories/topics/replies are added, it is recommended that you backup your database first.", WPS_TEXT_DOMAIN) . '</em></p>';
								echo '<p class="submit"><input type="submit" name="Submit" class="button-primary" value="'.__('Migrate Mingle', WPS_TEXT_DOMAIN).'" /></p>';
								echo '</form>';
							} else {
								echo '<p>'.__('No Mingle forums found', WPS_TEXT_DOMAIN).'.</p>';
							}
						} else {
								echo '<p>'.__('Mingle forum not installed', WPS_TEXT_DOMAIN).'.</p>';
						}
					echo '</td></tr></table>';
				
				echo '</div>'; 	
			
			}

	  	echo '</div>'; 	

	} // end admin check	
		
}
	  
function __wps__rrmdir($dir) {
   if (is_dir($dir)) {
	 $objects = scandir($dir);
	 foreach ($objects as $object) {
	   if ($object != "." && $object != "..") {
		 if (filetype($dir."/".$object) == "dir") __wps__rrmdir($dir."/".$object); else unlink($dir."/".$object);
	   }
	 }
	 reset($objects);
	 rmdir($dir);
   }
}  

function __wps__install_row($handle, $name, $shortcode, $function, $config_url, $plugin_dir, $settings_url, $install_help) {

	if (substr($install_help, 0, 7) == '__wps__') {
		
		global $wpdb;
		$install_help = str_replace('\\', '/', $install_help);
		$name = str_replace('_', ' ', $name);
		
		$status = '';
		
		echo '<tr>';

				$style = (is_super_admin() && __wps__is_wpmu()) ? '' : 'display:none;';
				echo '<td style="'.$style.'text-align:center">';
				
					if ($function != '__wps__group' && $install_help != '__wps_activated') {
						$network_activated = get_option(WPS_OPTIONS_PREFIX.$function.'_network_activated') ? 'CHECKED' : '';
						echo '<input type="checkbox" name="'.$function.'_network_activated" '.$network_activated.' />';
					} else {
						echo __('n/a', WPS_TEXT_DOMAIN);
					}
				
				echo '</td>';
	
			echo '<td style="text-align:center">';
			
				if ($function != '__wps__group') {
					$activated = get_option(WPS_OPTIONS_PREFIX.$function.'_activated') ? 'CHECKED' : '';
					$style = !get_option(WPS_OPTIONS_PREFIX.$function.'_network_activated') ? $style = '' : $style = 'style="display:none"';
					if ($install_help != '__wps__activated') {
						echo '<input type="checkbox" '.$style.' name="'.$function.'_activated" '.$activated.' />';
						if ($network_activated) 
							echo '<img src="'.WPS_PLUGIN_URL.'/images/tick.png" />';
					} else {
						echo '<img src="'.WPS_PLUGIN_URL.'/images/tick.png" />';
					}
				} else {
					echo __('n/a', WPS_TEXT_DOMAIN);
				}
			
			echo '</td>';
						
			// Name of Plugin
			echo '<td style="height:30px">';
				echo $name;
				if (!file_exists(WP_PLUGIN_DIR.'/'.$plugin_dir))
					echo '<br><span style="color:red;font-weight:bold">'.WP_PLUGIN_DIR.'/'.$plugin_dir.' missing!</span>';
				if (isset($network_activated) && ($network_activated || $activated) ) {
					if (strpos($install_help, '__wps__') == 0) $install_help = substr($install_help, 7, strlen($install_help)-7);
					$install_help = str_replace("bronze__", "", $install_help);
					if ($install_help != '') $install_help = ' ['.$install_help.']';
				} else {
					$install_help = '';
				}
			echo '</td>';
					
			// Shortcode on a page?
			$sql = "SELECT ID FROM ".$wpdb->prefix."posts WHERE lower(post_content) LIKE '%[".$shortcode."]%' AND post_type = 'page' AND post_status = 'publish';";
			$pages = $wpdb->get_results($sql);	
			if ( ($pages) && ($shortcode != '') ) {
				$page = $pages[0];
				$url = str_replace(get_bloginfo('url'), '', get_permalink($page->ID));
				echo '<td>';
					echo '<a href="'.get_permalink($page->ID).'" target="_blank">'.$url.'</a> ';
					echo '[<a href="post.php?post='.$page->ID.'&action=edit">'.__('Edit', WPS_TEXT_DOMAIN).'</a>] ';
					if (isset($status) && $status == 'tick') {
						if ($settings_url != '') {
							echo '[<a href="'.$settings_url.'">'.__('Configure', WPS_TEXT_DOMAIN).'</a>]';
						}
					}
				if ( (isset($status)) && ($url != $config_url && $status != 'cross') ) $status = 'error';
				if ($config_url == '-') $status = 'tick';
				echo $install_help.'</td>';
			} else {
				$url = '';
				echo '<td>';
				if ( (isset($status)) && ($status != 'cross') && ($status != 'notinstalled') && ($shortcode != '') ) {
					$status = 'add';
					echo '<div style="padding-top:4px;float:left; width:175px">'.sprintf(__('Add [%s] to:', WPS_TEXT_DOMAIN), $shortcode).'</div>';
					echo '<input type="submit" class="button symposium_addnewpage" id="'.$name.'" title="'.$shortcode.'" value="'.__('New Page', WPS_TEXT_DOMAIN).'" />';
					$sql = "SELECT * FROM ".$wpdb->prefix."posts WHERE post_status = 'publish' AND post_type = 'page' ORDER BY post_title";
					$pages = $wpdb->get_results($sql);
					if ($pages) {
						echo ' '.__('or', WPS_TEXT_DOMAIN).' ';
						echo '<select id="symposium_pagechoice_'.$shortcode.'" style="width:120px">';
						foreach ($pages as $page) {
							echo '<option value="'.$page->ID.'">'.$page->post_title;
						}
						echo '</select> ';
						echo '<input type="submit" class="button symposium_addtopage" id="'.$name.'" title="'.$shortcode.'" value="'.__('Add', WPS_TEXT_DOMAIN).'" />';
					}
				} else {
					if (isset($status) && $status == 'tick') {
						if ($settings_url != '') {
							echo '[<a href="'.$settings_url.'">'.__('Configure', WPS_TEXT_DOMAIN).'</a>]';
						}
					}
					if ($function == '__wps__wysiwyg') {
						if (current_user_can('update_core'))
							echo __('Also activates optional forum BB Code Toolbar', WPS_TEXT_DOMAIN);
					}
					if ($function == '__wps__add_notification_bar') {
						if (current_user_can('update_core'))
							echo ' [<a href="http://www.wpsymposium.com/chat/" target="_blank">'.__('Read this!', WPS_TEXT_DOMAIN).'</a>]';
					}
					if (isset($status) && $status == '') $status = 'tick';
				}
				echo '</td>';
			}
			
		
			
			// Status
			echo '<td style="text-align:center">';
	
				// Fix URL
				$fixed_url = false;
				$current_value = get_option(WPS_OPTIONS_PREFIX.'_'.strtolower($handle).'_url');
					if ($current_value != $url) {
						update_option(WPS_OPTIONS_PREFIX.'_'.strtolower($handle).'_url', $url);
						$fixed_url = true;
						if ($url != '') {
							echo '[<a href="javascript:void(0)" class="symposium_help" title="'.sprintf(__("URL updated successfully. It is important to visit this page to complete installation; after you add a %s shortcode to a page; change pages with %s shortcodes; if you change WordPress Permalinks; or if you experience problems.", WPS_TEXT_DOMAIN), WPS_WL, WPS_WL).'">'.__('Updated ok!', WPS_TEXT_DOMAIN).'</a>]';
						} else {
							echo '[<a href="javascript:void(0)" class="symposium_help" title="'.sprintf(__("URL removed. It is important to visit this page to complete installation; after you add a %s shortcode to a page; change pages with %s shortcodes; if you change WordPress Permalinks; or if you experience problems.", WPS_TEXT_DOMAIN), WPS_WL, WPS_WL).'">'.__('URL removed', WPS_TEXT_DOMAIN).'</a>]';
						}
					} else {
						if ($current_value) {
							$status = 'tick';
						}
					}
				
				if (!$fixed_url) {
						
					if (isset($status) && $status == 'notinstalled') {
						if ($function != '__wps__gallery') {
							echo '[<a href="javascript:void(0)" class="symposium_help" title="'.$install_help.'">'.__('Install', WPS_TEXT_DOMAIN).'</a>]';
						} else {
							echo __('Coming soon', WPS_TEXT_DOMAIN);
						}
					}
					if (isset($status) && $status == 'tick') {
						echo '<img src="'.get_option(WPS_OPTIONS_PREFIX.'_images').'/smilies/good.png" />';
					}
					if (isset($status) && $status == 'upgrade') {
						echo '<img src="'.get_option(WPS_OPTIONS_PREFIX.'_images').'/warning.png" />';
					}
					if (isset($status) && $status == 'cross') {			
						echo '[<a href="plugins.php?plugin_status=inactive">'.__('Activate', WPS_TEXT_DOMAIN).'</a>]';
					}
		
					if (isset($status) && $status == 'add') {
						echo '<img src="'.get_option(WPS_OPTIONS_PREFIX.'_images').'/'.$status.'.png" />';
					}
					
				}
				
			echo '</td>';
	
			// Setting in database
			if (current_user_can('update_core')) {
				echo '<td class="symposium_url" style="background-color:#efefef">';
				
					$value = get_option(WPS_OPTIONS_PREFIX.'_'.strtolower($handle).'_url');
					if (!$value && $status != 'add') { 
						echo 'n/a';
					} else {
						if ($value != 'Important: Please Visit Installation Page!') {
							echo $value;
						}	
					}
				echo '</td>';
			}
			
		echo '</tr>';
		
	}

}

function __wps__field_exists($tablename, $fieldname) {
	global $wpdb;
	$fields = $wpdb->get_results("SHOW fields FROM ".$tablename." LIKE '".$fieldname."'");

	if ($fields) {
		return true;
	} else {
		echo __('Missing Field', WPS_TEXT_DOMAIN).": ".$fieldname."<br />";
		return false;
	}

	return true;
}

function __wps__plugin_bar() {

  	echo '<div class="wrap">';
  	echo '<div id="icon-themes" class="icon32"><br /></div>';
  	echo '<h2>'.sprintf(__('%s Options', WPS_TEXT_DOMAIN), WPS_WL).'</h2><br />';

	__wps__show_tabs_header('panel');

	global $wpdb;

		// See if the user has posted notification bar settings
		if( isset($_POST[ 'symposium_update' ]) && $_POST[ 'symposium_update' ] == '__wps__plugin_bar' ) {

			update_option(WPS_OPTIONS_PREFIX.'_use_chat', isset($_POST[ 'use_chat' ]) ? $_POST[ 'use_chat' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'_bar_polling', $_POST[ 'bar_polling' ]);
			update_option(WPS_OPTIONS_PREFIX.'_chat_polling', $_POST[ 'chat_polling' ]);
			update_option(WPS_OPTIONS_PREFIX.'_wps_panel_all', isset($_POST[ 'wps_panel_all' ]) ? $_POST[ 'wps_panel_all' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'wps_panel_offline', isset($_POST[ 'wps_panel_offline' ]) ? $_POST[ 'wps_panel_offline' ] : '');
			
			// Put an settings updated message on the screen
			echo "<div class='updated slideaway'><p>".__('Saved', WPS_TEXT_DOMAIN).".</p></div>";
			
		}


			if (!function_exists('__wps__profile')) { 		
				echo "<div class='error'><p>".__('The Profile plugin must be activated for chat windows to work. The chat room will work without the Profile plugin.', WPS_TEXT_DOMAIN)."</p></div>";
			} 
			?>
			
			<form method="post" action=""> 
			<input type="hidden" name="symposium_update" value="__wps__plugin_bar">
		
			<table class="form-table __wps__admin_table">

			<tr><td colspan="2"><h2><?php _e('Options', WPS_TEXT_DOMAIN) ?></h2></td></tr>
			
			<tr valign="top"> 
			<td scope="row"><label for="wps_panel_all"><?php echo __('Show all members', WPS_TEXT_DOMAIN); ?></label></td>
			<td>
			<input type="checkbox" name="wps_panel_all" id="wps_panel_all" 
				<?php 
				if (get_option(WPS_OPTIONS_PREFIX.'_wps_panel_all') == "on") { echo "CHECKED"; } 
				?> 
			/>
			<span class="description"><?php echo __('Enable to include all members, disable to only include friends', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 
		
			<tr valign="top"> 
			<td scope="row"><label for="wps_panel_offline"><?php echo __('Show offline members', WPS_TEXT_DOMAIN); ?></label></td>
			<td>
			<input type="checkbox" name="wps_panel_offline" id="wps_panel_offline" 
				<?php 
				if (get_option(WPS_OPTIONS_PREFIX.'wps_panel_offline') == "on") { echo "CHECKED"; } 
				?> 
			/>
			<span class="description"><?php echo __('Enable to show members who are offline', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 
		
			<tr valign="top"> 
			<td scope="row"><label for="use_chat"><?php echo __('Enable chat windows', WPS_TEXT_DOMAIN); ?></label></td>
			<td>
			<input type="checkbox" name="use_chat" id="use_chat" 
				<?php 
				if (!function_exists('__wps__profile')) { echo 'disabled="disabled" '; }
				if (get_option(WPS_OPTIONS_PREFIX.'_use_chat') == "on") { echo "CHECKED"; } 
				?>
			/>
			<span class="description"><?php echo __('Real-time chat windows', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 
										
			<tr valign="top"> 
			<td scope="row"><label for="bar_polling"><?php echo __('Polling Intervals', WPS_TEXT_DOMAIN); ?></label></td> 
			<td><input name="bar_polling" type="text" id="bar_polling"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_bar_polling'); ?>" /> 
			<span class="description"><?php echo __('Frequency of checks for new mail, friends online, etc, in seconds. Recommended 120.', WPS_TEXT_DOMAIN); ?></td> 
			</tr> 
						
			<tr valign="top"> 
			<td scope="row"><label for="chat_polling">&nbsp;</label></td> 
			<td><input name="chat_polling" type="text" id="chat_polling"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_chat_polling'); ?>" /> 
			<span class="description"><?php echo __('Frequency of chat window updates in seconds. Recommended 10.', WPS_TEXT_DOMAIN); ?></td> 
			</tr> 

			</table> 
			 
			
			<p class="submit" style="margin-left:6px"> 
			<input type="submit" name="Submit" class="button-primary" value="<?php echo __('Save Changes', WPS_TEXT_DOMAIN); ?>" /> 
			</p> 
			</form> 

			<p style="margin-left:6px">
			<strong><?php echo __('Notes:', WPS_TEXT_DOMAIN); ?></strong>
			<ol>
			<li><?php echo __('The polling intervals occur in addition to an initial check on each page load.', WPS_TEXT_DOMAIN); ?></li>
			<li><?php echo __('The more frequent the polling intervals, the greater the load on your server.', WPS_TEXT_DOMAIN); ?></li>
			<li><?php echo __('Disabling chat windows will reduce the load on the server.', WPS_TEXT_DOMAIN); ?></li>
			</ol>
			</p>
			
			<?php

		__wps__show_tabs_header_end();
				
	echo '</div>';
}

function __wps__plugin_profile() {

	echo '<div class="wrap">';

	echo '<div id="icon-themes" class="icon32"><br /></div>';
  	echo '<h2>'.sprintf(__('%s Options', WPS_TEXT_DOMAIN), WPS_WL).'</h2><br />';

	__wps__show_tabs_header('profile');

	global $wpdb;
	global $user_ID;
	get_currentuserinfo();
	
	include_once( ABSPATH . 'wp-includes/formatting.php' );
	
		// Delete an extended field?
   		if ( isset($_GET['del_eid']) && $_GET['del_eid'] != '') {

			// get slug
			$sql = "SELECT extended_slug from ".$wpdb->base_prefix."symposium_extended WHERE eid = %d";
			$slug = $wpdb->query($wpdb->prepare($sql, $_GET['del_eid']));

			// now delete extended field
			$wpdb->query( $wpdb->prepare( "DELETE FROM ".$wpdb->base_prefix.'symposium_extended'." WHERE eid = %d", $_GET['del_eid']  ) );
				
			// finally delete all of these extended fields
			$sql = "DELETE FROM ".$wpdb->base_prefix."usermeta WHERE meta_key = 'symposium_".$slug."'";
			$wpdb->query($sql);

		}	
		

		// See if the user has posted profile settings
		if( isset($_POST[ 'symposium_update' ]) && $_POST[ 'symposium_update' ] == '__wps__plugin_profile' ) {

			update_option(WPS_OPTIONS_PREFIX.'_online', $_POST['online'] != '' ? $_POST['online'] : 5);
			update_option(WPS_OPTIONS_PREFIX.'_offline', $_POST['offline'] != '' ? $_POST['offline'] : 15);
			update_option(WPS_OPTIONS_PREFIX.'_use_poke', isset($_POST['use_poke']) ? $_POST['use_poke'] : '');
			update_option(WPS_OPTIONS_PREFIX.'_poke_label', $_POST['poke_label'] != '' ? $_POST['poke_label'] : __('Hey!', "wp-symposium"));
			update_option(WPS_OPTIONS_PREFIX.'_status_label', $_POST['status_label'] != '' ? str_replace("'", "`", $_POST['status_label']) : __('What`s up?', "wp-symposium"));
			update_option(WPS_OPTIONS_PREFIX.'_enable_password', isset($_POST['enable_password']) ? $_POST['enable_password'] : '');
			update_option(WPS_OPTIONS_PREFIX.'_show_wall_extras', isset($_POST['show_wall_extras']) ? $_POST['show_wall_extras'] : '');
			update_option(WPS_OPTIONS_PREFIX.'_profile_google_map', $_POST['profile_google_map'] != '' ? $_POST['profile_google_map'] : 250);
			update_option(WPS_OPTIONS_PREFIX.'_profile_comments', isset($_POST['profile_comments']) ? $_POST['profile_comments'] : '');
			update_option(WPS_OPTIONS_PREFIX.'_show_dob', isset($_POST['show_dob']) ? $_POST['show_dob'] : '');
			update_option(WPS_OPTIONS_PREFIX.'_show_dob_format', ($_POST['show_dob_format'] != '') ? $_POST['show_dob_format'] : __('Born', WPS_TEXT_DOMAIN).' %monthname %day%th, %year');
			update_option(WPS_OPTIONS_PREFIX.'_profile_avatars', isset($_POST['profile_avatars']) ? $_POST['profile_avatars'] : '');
			update_option(WPS_OPTIONS_PREFIX.'_initial_friend', $_POST['initial_friend']);
			update_option(WPS_OPTIONS_PREFIX.'_redirect_wp_profile', isset($_POST['redirect_wp_profile']) ? $_POST['redirect_wp_profile'] : '');
			
			update_option(WPS_OPTIONS_PREFIX.'_profile_show_unchecked', isset($_POST['profile_show_unchecked']) ? $_POST['profile_show_unchecked'] : '');
			update_option(WPS_OPTIONS_PREFIX.'_wps_profile_default', isset($_POST['wps_profile_default']) ? $_POST['wps_profile_default'] : '');
			update_option(WPS_OPTIONS_PREFIX.'_wps_default_privacy', isset($_POST['wps_default_privacy']) ? $_POST['wps_default_privacy'] : '');
			update_option(WPS_OPTIONS_PREFIX.'_wps_use_gravatar', isset($_POST['wps_use_gravatar']) ? $_POST['wps_use_gravatar'] : '');
			update_option(WPS_OPTIONS_PREFIX.'_hide_location', isset($_POST['symposium_hide_location']) ? $_POST['symposium_hide_location'] : '');
			update_option(WPS_OPTIONS_PREFIX.'_profile_menu_type', isset($_POST[ 'wps_profile_menu_type' ]) ? $_POST[ 'wps_profile_menu_type' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'_use_templates', isset($_POST[ 'wps_use_templates' ]) ? $_POST[ 'wps_use_templates' ] : '');


			if (isset($_POST['__wps__profile_extended_fields'])) {
		   		$range = array_keys($_POST['__wps__profile_extended_fields']);
		   		$level = '';
	   			foreach ($range as $key) {
					$level .= $_POST['__wps__profile_extended_fields'][$key].',';
		   		}
			} else {
				$level = '';
			}
			update_option(WPS_OPTIONS_PREFIX.'_profile_extended_fields', $level);	
			// This is the hidden field, if not using default layout
			if (isset($_POST[ '__wps__profile_extended_fields_list' ])) {
				update_option(WPS_OPTIONS_PREFIX.'_profile_extended_fields', $_POST[ '__wps__profile_extended_fields_list' ] );
			}


			// Profile menu
			
			// Vertical menu
			if (!get_option(WPS_OPTIONS_PREFIX.'_profile_menu_type')) {
				
				update_option(WPS_OPTIONS_PREFIX.'_menu_texthtml', isset($_POST['menu_texthtml']) ? $_POST['menu_texthtml'] : '');

				update_option(WPS_OPTIONS_PREFIX.'_menu_my_activity', isset($_POST['menu_my_activity']) ? $_POST['menu_my_activity'] : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_friends_activity', isset($_POST['menu_friends_activity']) ? $_POST['menu_friends_activity'] : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_all_activity', isset($_POST['menu_all_activity']) ? $_POST['menu_all_activity'] : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_profile', isset($_POST['menu_profile']) ? $_POST['menu_profile'] : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_friends', isset($_POST['menu_friends']) ? $_POST['menu_friends'] : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_mentions', isset($_POST['menu_mentions']) ? $_POST['menu_mentions'] : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_groups', isset($_POST['menu_groups']) ? $_POST['menu_groups'] : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_events', isset($_POST['menu_events']) ? $_POST['menu_events'] : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_gallery', isset($_POST['menu_gallery']) ? $_POST['menu_gallery'] : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_following', isset($_POST['menu_following']) ? $_POST['menu_following'] : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_followers', isset($_POST['menu_followers']) ? $_POST['menu_followers'] : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_lounge', isset($_POST['menu_lounge']) ? $_POST['menu_lounge'] : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_avatar', isset($_POST['menu_avatar']) ? $_POST['menu_avatar'] : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_details', isset($_POST['menu_details']) ? $_POST['menu_details'] : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_settings', isset($_POST['menu_settings']) ? $_POST['menu_settings'] : '');
				
				update_option(WPS_OPTIONS_PREFIX.'_menu_my_activity_other', isset($_POST['menu_my_activity_other']) ? $_POST['menu_my_activity_other'] : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_friends_activity_other', isset($_POST['menu_friends_activity_other']) ? $_POST['menu_friends_activity_other'] : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_all_activity_other', isset($_POST['menu_all_activity_other']) ? $_POST['menu_all_activity_other'] : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_profile_other', isset($_POST['menu_profile_other']) ? $_POST['menu_profile_other'] : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_friends_other', isset($_POST['menu_friends_other']) ? $_POST['menu_friends_other'] : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_mentions_other', isset($_POST['menu_mentions_other']) ? $_POST['menu_mentions_other'] : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_groups_other', isset($_POST['menu_groups_other']) ? $_POST['menu_groups_other'] : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_events_other', isset($_POST['menu_events_other']) ? $_POST['menu_events_other'] : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_gallery_other', isset($_POST['menu_gallery_other']) ? $_POST['menu_gallery_other'] : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_following_other', isset($_POST['menu_following_other']) ? $_POST['menu_following_other'] : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_followers_other', isset($_POST['menu_followers_other']) ? $_POST['menu_followers_other'] : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_lounge_other', isset($_POST['menu_lounge_other']) ? $_POST['menu_lounge_other'] : '');
				
				update_option(WPS_OPTIONS_PREFIX.'_menu_profile_text', isset($_POST['menu_profile_text']) ? stripslashes($_POST['menu_profile_text']) : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_my_activity_text', isset($_POST['menu_my_activity_text']) ? stripslashes($_POST['menu_my_activity_text']) : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_friends_activity_text', isset($_POST['menu_friends_activity_text']) ? stripslashes($_POST['menu_friends_activity_text']) : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_all_activity_text', isset($_POST['menu_all_activity_text']) ? stripslashes($_POST['menu_all_activity_text']) : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_friends_text', isset($_POST['menu_friends_text']) ? stripslashes($_POST['menu_friends_text']) : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_mentions_text', isset($_POST['menu_mentions_text']) ? stripslashes($_POST['menu_mentions_text']) : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_groups_text', isset($_POST['menu_groups_text']) ? stripslashes($_POST['menu_groups_text']) : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_events_text', isset($_POST['menu_events_text']) ? stripslashes($_POST['menu_events_text']) : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_gallery_text', isset($_POST['menu_gallery_text']) ? stripslashes($_POST['menu_gallery_text']) : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_following_text', isset($_POST['menu_following_text']) ? stripslashes($_POST['menu_following_text']) : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_followers_text', isset($_POST['menu_followers_text']) ? stripslashes($_POST['menu_followers_text']) : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_lounge_text', isset($_POST['menu_lounge_text']) ? stripslashes($_POST['menu_lounge_text']) : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_avatar_text', isset($_POST['menu_avatar_text']) ? stripslashes($_POST['menu_avatar_text']) : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_details_text', isset($_POST['menu_details_text']) ? stripslashes($_POST['menu_details_text']) : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_settings_text', isset($_POST['menu_settings_text']) ? stripslashes($_POST['menu_settings_text']) : '');
	
				update_option(WPS_OPTIONS_PREFIX.'_menu_profile_other_text', isset($_POST['menu_profile_other_text']) ? stripslashes($_POST['menu_profile_other_text']) : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_my_activity_other_text', isset($_POST['menu_my_activity_other_text']) ? stripslashes($_POST['menu_my_activity_other_text']) : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_friends_activity_other_text', isset($_POST['menu_friends_activity_other_text']) ? stripslashes($_POST['menu_friends_activity_other_text']) : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_all_activity_other_text', isset($_POST['menu_all_activity_other_text']) ? stripslashes($_POST['menu_all_activity_other_text']) : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_friends_other_text', isset($_POST['menu_friends_other_text']) ? stripslashes($_POST['menu_friends_other_text']) : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_mentions_other_text', isset($_POST['menu_mentions_other_text']) ? stripslashes($_POST['menu_mentions_other_text']) : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_groups_other_text', isset($_POST['menu_groups_other_text']) ? stripslashes($_POST['menu_groups_other_text']) : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_events_other_text', isset($_POST['menu_events_other_text']) ? stripslashes($_POST['menu_events_other_text']) : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_gallery_other_text', isset($_POST['menu_gallery_other_text']) ? stripslashes($_POST['menu_gallery_other_text']) : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_following_other_text', isset($_POST['menu_following_other_text']) ? stripslashes($_POST['menu_following_other_text']) : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_followers_other_text', isset($_POST['menu_followers_other_text']) ? stripslashes($_POST['menu_followers_other_text']) : '');
				update_option(WPS_OPTIONS_PREFIX.'_menu_lounge_other_text', isset($_POST['menu_lounge_other_text']) ? stripslashes($_POST['menu_lounge_other_text']) : '');
				
				
				
			}

			// Horizontal menu
			if (get_option(WPS_OPTIONS_PREFIX.'_profile_menu_type') || get_option(WPS_OPTIONS_PREFIX.'_use_templates') != "on") {

				$default_menu_structure = '[Profile]
View Profile=viewprofile
Profile Details=details
Community Settings=settings
Upload Avatar=avatar
[Activity]
My Activity=activitymy
Friends Activity=activityfriends
All Activity=activityall
[Social%f]
My Friends=myfriends
My Groups=mygroups
The Lounge=lounge
My @mentions=mentions
Who I am Following=following
My Followers=followers
[More]
My Events=events
My Gallery=gallery';

				$default_menu_structure_other = '[Profile]
View Profile=viewprofile
Profile Details=details
Community Settings=settings
Upload Avatar=avatar
[Activity]
Activity=activitymy
Friends Activity=activityfriends
All Activity=activityall
[Social]
Friends=myfriends
Groups=mygroups
The Lounge=lounge
@mentions=mentions
Following=following
Followers=followers
[More]
Events=events
Gallery=gallery';

				update_option(WPS_OPTIONS_PREFIX.'_profile_menu_structure', (isset($_POST['profile_menu_structure']) && $_POST['profile_menu_structure']) ? $_POST['profile_menu_structure'] : $default_menu_structure);
				update_option(WPS_OPTIONS_PREFIX.'_profile_menu_structure_other', (isset($_POST['profile_menu_structure_other']) && $_POST['profile_menu_structure_other']) ? $_POST['profile_menu_structure_other'] : $default_menu_structure_other);
			
			}

			// Update extended fields
	   		if (isset($_POST['eid']) && $_POST['eid'] != '') {
		   		$range = array_keys($_POST['eid']);
				foreach ($range as $key) {
					$eid = $_POST['eid'][$key];
					$order = $_POST['order'][$key];
					$type = $_POST['type'][$key];
					$default = $_POST['default'][$key];
					$readonly = $_POST['readonly'][$key];
					$search = $_POST['search'][$key];
					$name = $_POST['name'][$key];
					$slug = strtolower(preg_replace("/[^A-Za-z0-9_]/", '',$_POST['slug'][$key]));
					if (in_array($slug, array( "city", "country" ))) $slug .= '_2';
					$wp_usermeta = $_POST['wp_usermeta'][$key];
					$old_wp_usermeta = $_POST['old_wp_usermeta'][$key];
					
					if ( $wp_usermeta != $old_wp_usermeta ) {
						// Hook for connecting/disconnecting EF to/from WP metadata, do something with user data based on admin's choice
						do_action('symposium_update_extended_metadata_hook', $slug, $wp_usermeta, $old_wp_usermeta);
					}
					
					$wpdb->query( $wpdb->prepare( "
						UPDATE ".$wpdb->base_prefix.'symposium_extended'."
						SET extended_name = %s, extended_order = %s, extended_slug = %s, extended_type = %s, readonly = %s, search = %s, extended_default = %s, wp_usermeta = %s
						WHERE eid = %d", 
						$name, $order, $slug, $type, $readonly, $search, $default, $wp_usermeta, $eid ) );
				}		
			}
			
			// Add new extended field if applicable
			if ($_POST['new_name'] != '' && $_POST['new_name'] != __('New label', WPS_TEXT_DOMAIN) ) {

				if ( ( $_POST['new_slug'] == '' ) || ( $_POST['new_slug'] == __('New slug', WPS_TEXT_DOMAIN) ) ) { $slug = $_POST['new_name']; } else { $slug = $_POST['new_slug']; }
				$slug = sanitize_title_with_dashes( $slug );
				$slug = substr( $slug, 0, 64 );
				
				if (in_array($slug, array( "city", "country" ))) $slug .= '_2';

				$wpdb->query( $wpdb->prepare( "
					INSERT INTO ".$wpdb->base_prefix.'symposium_extended'."
					( 	extended_name, 
						extended_order,
						extended_slug,
						readonly,
						search,
						extended_type,
						extended_default,
						wp_usermeta
					)
					VALUES ( %s, %d, %s, %s, %s, %s, %s, %s )", 
					array(
						$_POST['new_name'], 
						$_POST['new_order'],
						$slug,
						$_POST['new_readonly'],
						'',
						$_POST['new_type'],
						$_POST['new_default'],
						$_POST['new_wp_usermeta']
					) 
				) );

			}
			
			// Put an settings updated message on the screen
			echo "<div class='updated slideaway'><p>".__('Saved', WPS_TEXT_DOMAIN).".</p></div>";
			
		}
					?>
						
					<form method="post" action=""> 
					<input type="hidden" name="symposium_update" value="__wps__plugin_profile">
				
					<table class="form-table __wps__admin_table"> 

					<tr><td colspan="2"><h2><?php _e('Options', WPS_TEXT_DOMAIN) ?></h2></td></tr>

					<tr valign="top"> 
					<td scope="row"><label for="wps_use_templates"><?php echo __('Custom Profile Page templates', WPS_TEXT_DOMAIN); ?></label></td>
					<td>
					<input type="checkbox" name="wps_use_templates" id="wps_use_templates" <?php if (get_option(WPS_OPTIONS_PREFIX.'_use_templates') == "on") { echo "CHECKED"; } ?>/>
					<span class="description"><?php echo sprintf(__('Activate <a href="%s">templates</a> for the profile page (default layout used if not)', WPS_TEXT_DOMAIN), 'admin.php?page=symposium_templates'); ?></span></td> 
					</tr> 

					<?php if (get_option(WPS_OPTIONS_PREFIX.'_use_templates') == "on") { ?>
						<tr valign="top"> 
						<td scope="row"><label for="wps_profile_menu_type"><?php echo __('Horizontal menu style', WPS_TEXT_DOMAIN); ?></label></td>
						<td>
						<input type="checkbox" name="wps_profile_menu_type" id="wps_profile_menu_type" <?php if (get_option(WPS_OPTIONS_PREFIX.'_profile_menu_type') == "on") { echo "CHECKED"; } ?>/>
						<span class="description"><?php echo __('Check to select horizontal menu version with drop-down items, for profile and group pages.', WPS_TEXT_DOMAIN); ?></span><br /><br />
						<span class="description"><strong><?php echo __('Important! If activated, make sure that you also do the following:', WPS_TEXT_DOMAIN); ?></strong></span><br />
						<ol>
						<span class="description"><li><?php echo __('Reset the <a href="admin.php?page=symposium_templates">Profile Page Body</a> template.', WPS_TEXT_DOMAIN); ?></span><br />
						<span class="description"><li><?php echo __('Set up your menu (below).', WPS_TEXT_DOMAIN); ?></li></span>
						<?php if (function_exists('__wps__group')) { ?>
							<span class="description"><li><?php echo __('Reset the <a href="admin.php?page=symposium_templates">Group Page</a> template.', WPS_TEXT_DOMAIN); ?></span><br />
							<span class="description"><li><?php echo sprintf(__('Set up your <a href="%s">group menu</a>.', WPS_TEXT_DOMAIN), 'admin.php?page=wp-symposium/groups_admin.php'); ?></li></span>
						<?php } ?>
						</ol>
						</td> 
						</tr> 
						<?php
						?>
						
					<?php } else { ?>
						<input type="hidden" name="wps_profile_menu_type" id="wps_profile_menu_type" value="<?php if (get_option(WPS_OPTIONS_PREFIX.'_profile_menu_type') == 'on') { echo 'on'; } ?>" />
					<?php } ?>

					<tr valign="top"> 
					<td scope="row"><label for="redirect_wp_profile"><?php echo __('Redirect profile page', WPS_TEXT_DOMAIN); ?></label></td>
					<td>
					<input type="checkbox" name="redirect_wp_profile" id="redirect_wp_profile" <?php if (get_option(WPS_OPTIONS_PREFIX.'_redirect_wp_profile') == "on") { echo "CHECKED"; } ?>/>
					<span class="description"><?php echo sprintf(__('Redirect WordPress generated links for WordPress profile page to %s profile page', WPS_TEXT_DOMAIN), WPS_WL_SHORT); ?></span></td> 
					</tr> 
				
					<tr valign="top">
					<td scope="row"><label for="wps_default_profile"><?php echo __('Default view', WPS_TEXT_DOMAIN); ?></label></td> 
					<td>
					<select name="wps_profile_default">
						<option value='extended'<?php if (get_option(WPS_OPTIONS_PREFIX.'_wps_profile_default') == 'extended') { echo ' SELECTED'; } ?>><?php echo __('Profile', WPS_TEXT_DOMAIN); ?></option>
						<option value='wall'<?php if (get_option(WPS_OPTIONS_PREFIX.'_wps_profile_default') == 'wall') { echo ' SELECTED'; } ?>><?php echo __('My activity', WPS_TEXT_DOMAIN); ?></option>
						<option value='activity'<?php if (get_option(WPS_OPTIONS_PREFIX.'_wps_profile_default') == 'activity') { echo ' SELECTED'; } ?>><?php echo __('Friends activity (includes my activity)', WPS_TEXT_DOMAIN); ?></option>
						<option value='all'<?php if (get_option(WPS_OPTIONS_PREFIX.'_wps_profile_default') == 'all') { echo ' SELECTED'; } ?>><?php echo __('All activity', WPS_TEXT_DOMAIN); ?></option>
					</select> 
					<span class="description"><?php echo __("Default view for the member's own profile page", WPS_TEXT_DOMAIN); ?></span></td> 
					</tr> 		

					<tr valign="top">
					<td scope="row"><label for="wps_default_privacy"><?php echo __('Default privacy level', WPS_TEXT_DOMAIN); ?></label></td> 
					<td>
					<select name="wps_default_privacy">
						<option value='Nobody'<?php if (get_option(WPS_OPTIONS_PREFIX.'_wps_default_privacy') == 'Nobody') { echo ' SELECTED'; } ?>><?php echo __('Nobody', WPS_TEXT_DOMAIN); ?></option>
						<option value='Friends only'<?php if (get_option(WPS_OPTIONS_PREFIX.'_wps_default_privacy') == 'Friends only') { echo ' SELECTED'; } ?>><?php echo __('Friends only', WPS_TEXT_DOMAIN); ?></option>
						<option value='Everyone'<?php if (get_option(WPS_OPTIONS_PREFIX.'_wps_default_privacy') == 'Everyone') { echo ' SELECTED'; } ?>><?php echo stripslashes(get_option(WPS_OPTIONS_PREFIX.'_alt_everyone')); ?></option>
						<option value='public'<?php if (get_option(WPS_OPTIONS_PREFIX.'_wps_default_privacy') == 'public') { echo ' SELECTED'; } ?>><?php echo __('Public', WPS_TEXT_DOMAIN); ?></option>
					</select> 
					<span class="description"><?php echo __("Default privacy setting for new members", WPS_TEXT_DOMAIN); ?></span></td> 
					</tr> 		

					<tr valign="top"> 
					<td scope="row"><label for="initial_friend"><?php echo __('Default Friend', WPS_TEXT_DOMAIN); ?></label></td> 
					<td><input name="initial_friend" type="text" id="initial_friend"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_initial_friend'); ?>" /> 
					<span class="description"><?php echo __('Comma separated list of user ID\'s that automatically become friends of new users (leave blank for no-one)', WPS_TEXT_DOMAIN); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="profile_avatars"><?php echo __('Profile Photos', WPS_TEXT_DOMAIN); ?></label></td>
					<td>
					<input type="checkbox" name="profile_avatars" id="profile_avatars" <?php if (get_option(WPS_OPTIONS_PREFIX.'_profile_avatars') == "on") { echo "CHECKED"; } ?>/>
					<span class="description"><?php echo __('Allow members to upload their own profile photos, over-riding the internal WordPress avatars', WPS_TEXT_DOMAIN); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="wps_use_gravatar"><?php echo __('Use Gravatar', WPS_TEXT_DOMAIN); ?></label></td>
					<td>
					<input type="checkbox" name="wps_use_gravatar" id="wps_use_gravatar" <?php if (get_option(WPS_OPTIONS_PREFIX.'_wps_use_gravatar') == "on") { echo "CHECKED"; } ?>/>
					<span class="description"><?php echo __('If allowing member to upload profile photos, should <a href="http://www.gravatar.com" target="_blank">gravatar</a> be used if they have not yet done so?', WPS_TEXT_DOMAIN); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="use_poke"><?php echo __('Poke/Nudge/Wink/etc', WPS_TEXT_DOMAIN); ?></label></td>
					<td>
					<input type="checkbox" name="use_poke" id="use_poke" <?php if (get_option(WPS_OPTIONS_PREFIX.'_use_poke') == "on") { echo "CHECKED"; } ?>/>
					<span class="description"><?php echo __('Enable this feature', WPS_TEXT_DOMAIN); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="poke_label"><?php echo __('Poke label', WPS_TEXT_DOMAIN); ?></label></td> 
					<td><input name="poke_label" type="text" id="poke_label"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_poke_label'); ?>" /> 
					<span class="description"><?php echo __('The "poke" button label for your site, beware of trademarked words (includes Poke and Nudge for example)', WPS_TEXT_DOMAIN); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="status_label"><?php echo __('Status label', WPS_TEXT_DOMAIN); ?></label></td> 
					<td><input name="status_label" type="text" id="status_label"  value="<?php echo stripslashes(get_option(WPS_OPTIONS_PREFIX.'_status_label')); ?>" /> 
					<span class="description"><?php echo __('The default prompt for new activity posts on the profile page', WPS_TEXT_DOMAIN); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="show_dob"><?php echo __('Use Date of Birth', WPS_TEXT_DOMAIN); ?></label></td>
					<td>
					<input type="checkbox" name="show_dob" id="show_dob" <?php if (get_option(WPS_OPTIONS_PREFIX.'_show_dob') == "on") { echo "CHECKED"; } ?>/>
					<span class="description"><?php echo __('Use date of birth on profile', WPS_TEXT_DOMAIN); ?></span></td> 
					</tr> 
										
					<tr valign="top"> 
					<td scope="row"><label for="show_dob_format"><?php echo __('Date of birth format', WPS_TEXT_DOMAIN); ?></label></td>
					<td><input name="show_dob_format" type="text" id="show_dob_format" style="width:250px;" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_show_dob_format'); ?>" /> 
					<span class="description"><?php echo sprintf(__('Valid parameters: %%0day %%day %%th %%0month %%month %%monthname %%year (see <a href="%s">admin guide</a>)', WPS_TEXT_DOMAIN), 'https://dl.dropbox.com/u/49355018/wps.pdf'); ?></span></td> 
					</tr> 
										
					<tr valign="top"> 
					<td scope="row"><label for="show_wall_extras"><?php echo __('Recently Active Friends Box', WPS_TEXT_DOMAIN); ?></label></td>
					<td>
					<input type="checkbox" name="show_wall_extras" id="show_wall_extras" <?php if (get_option(WPS_OPTIONS_PREFIX.'_show_wall_extras') == "on") { echo "CHECKED"; } ?>/>
					<span class="description"><?php echo __('Show Recently Active Friends box on side of wall (may take up space, depending on page template)', WPS_TEXT_DOMAIN); ?></span></td> 
					</tr> 
										
					<tr valign="top"> 
					<td scope="row"><label for="profile_google_map"><?php echo __('Google Map', WPS_TEXT_DOMAIN); ?></label></td> 
					<td><input name="profile_google_map" type="text" id="profile_google_map" style="width:50px" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_profile_google_map'); ?>" /> 
					<span class="description"><?php echo __('Size of location map, in pixels. eg: 250. Set to 0 to hide.', WPS_TEXT_DOMAIN); ?></span></td> 
					</tr> 
										
					<tr valign="top"> 
					<td scope="row"><label for="profile_comments"><?php echo __('Show comment fields', WPS_TEXT_DOMAIN); ?></label></td>
					<td>
					<input type="checkbox" name="profile_comments" id="profile_comments" <?php if (get_option(WPS_OPTIONS_PREFIX.'_profile_comments') == "on") { echo "CHECKED"; } ?>/>
					<span class="description"><?php echo __('Always show post comment fields (or hover to show)', WPS_TEXT_DOMAIN); ?></span></td> 
					</tr> 
										
					<tr valign="top"> 
					<td scope="row"><label for="symposium_hide_location"><?php echo __('Remove location fields', WPS_TEXT_DOMAIN); ?></label></td>
					<td>
					<input type="checkbox" name="symposium_hide_location" id="symposium_hide_location" <?php if (get_option(WPS_OPTIONS_PREFIX.'_hide_location') == "on") { echo "CHECKED"; } ?>/>
					<span class="description"><?php echo __('Hide and disable location profile fields, and exclude distance from member directory', WPS_TEXT_DOMAIN); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="enable_password"><?php echo __('Enable Password Change', WPS_TEXT_DOMAIN); ?></label></td>
					<td>
					<input type="checkbox" name="enable_password" id="enable_password" <?php if (get_option(WPS_OPTIONS_PREFIX.'_enable_password') == "on") { echo "CHECKED"; } ?>/>
					<span class="description"><?php echo __('Allow members to change their password', WPS_TEXT_DOMAIN); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="online"><?php echo __('Inactivity period', WPS_TEXT_DOMAIN); ?></label></td> 
					<td><input name="online" type="text" id="online" style="width:50px"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_online'); ?>" /> 
					<span class="description"><?php echo __('How many minutes before a member is assumed off-line', WPS_TEXT_DOMAIN); ?></span></td> 
					</tr> 
										
					<tr valign="top"> 
					<td scope="row"><label for="offline">&nbsp;</label></td> 
					<td><input name="offline" type="text" id="offline" style="width:50px"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_offline'); ?>" /> 
					<span class="description"><?php echo __('How many minutes before a member is assumed logged out', WPS_TEXT_DOMAIN); ?></span></td> 
					</tr> 
					
					<tr><td colspan="2"><h2><?php _e('Profile Menu Items', WPS_TEXT_DOMAIN) ?></h2></td></tr>

					<?php if (get_option(WPS_OPTIONS_PREFIX.'_profile_menu_type') || get_option(WPS_OPTIONS_PREFIX.'_use_templates') != "on") { ?>

						<tr valign="top"> 
						<td scope="row"><label for="profile_menu_structure"><?php echo __('Your page', WPS_TEXT_DOMAIN); ?></label></td>
						<td>
						<textarea rows="12" cols="40" name="profile_menu_structure" id="profile_menu_structure"><?php echo get_option(WPS_OPTIONS_PREFIX.'_profile_menu_structure') ?></textarea><br />
						<span class="description">
							<?php echo sprintf(__('Only applicable to the horizontal version of the profile page menu', WPS_TEXT_DOMAIN), WPS_WL); ?><br />
							<?php echo sprintf(__('%%f is replaced by any pending friendship requests in top level items', WPS_TEXT_DOMAIN), WPS_WL); ?>
						</span><br />
						<a id="__wps__reset_profile_menu" href="javascript:void(0)"><?php echo __('Reset the above...', WPS_TEXT_DOMAIN); ?></a>
						</td> 
						</tr> 
					
						<tr valign="top"> 
						<td scope="row"><label for="profile_menu_structure_other"><?php echo __('Other members', WPS_TEXT_DOMAIN); ?></label></td>
						<td>
						<textarea rows="12" cols="40" name="profile_menu_structure_other" id="profile_menu_structure_other"><?php echo get_option(WPS_OPTIONS_PREFIX.'_profile_menu_structure_other') ?></textarea><br />
						<span class="description"><?php echo sprintf(__('Only applicable to the horizontal version of the profile page menu', WPS_TEXT_DOMAIN), WPS_WL); ?></span><br />
						<a id="__wps__reset_profile_menu_other" href="javascript:void(0)"><?php echo __('Reset the above...', WPS_TEXT_DOMAIN); ?></a>
						</td> 
						</tr> 
						
					<?php } else { ?>

						<input type="hidden" name="profile_menu_structure" id="profile_menu_structure" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_profile_menu_structure') ?>" />
						<input type="hidden" name="profile_menu_structure_other" id="profile_menu_structure_other" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_profile_menu_structure_other') ?>" />
	
					<?php } ?>

					<?php if (!get_option(WPS_OPTIONS_PREFIX.'_profile_menu_type') && get_option(WPS_OPTIONS_PREFIX.'_use_templates') == "on") { ?>
					
						<tr valign="top"> 
						<td colspan="2" style="padding:0">
							<table>
								<tr style='font-weight:bold'>
									<td style="width:125px"><?php _e('Menu Item', WPS_TEXT_DOMAIN); ?></td>
									<td><?php _e('Own Page', WPS_TEXT_DOMAIN); ?></td>
									<td><?php _e('Own Page Text', WPS_TEXT_DOMAIN); ?></td>
									<td><?php _e('Other Members', WPS_TEXT_DOMAIN); ?></td>
									<td><?php _e('Other Members Text', WPS_TEXT_DOMAIN); ?></td>
								</tr>
								<tr>
									<td><span class="description"><?php echo __('Profile', WPS_TEXT_DOMAIN); ?></span></td>
									<td align='center'><input type="checkbox" name="menu_profile" id="menu_profile" <?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_profile') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_profile_text" type="text" id="menu_profile_text"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_profile_text'); ?>" /></td>
									<td align='center'><input type="checkbox" name="menu_profile_other" id="menu_profile_other" <?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_profile_other') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_profile_other_text" type="text" id="menu_profile_other_text"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_profile_other_text'); ?>" /></td>
								</tr>
								<tr>
									<td><span class="description"><?php echo __('My Activity', WPS_TEXT_DOMAIN); ?></span></td>
									<td align='center'><input type="checkbox" name="menu_my_activity" id="menu_my_activity" <?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_my_activity') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_my_activity_text" type="text" id="menu_my_activity_text"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_my_activity_text'); ?>" /></td>
									<td align='center'><input type="checkbox" name="menu_my_activity_other" id="menu_my_activity_other" <?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_my_activity_other') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_my_activity_other_text" type="text" id="menu_my_activity_other_text"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_my_activity_other_text'); ?>" /></td>
								</tr>
								<tr>
									<td><span class="description"><?php echo __('Friends Activity', WPS_TEXT_DOMAIN); ?></span></span></td>
									<td align='center'><input type="checkbox" name="menu_friends_activity" id="menu_friends_activity" <?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_friends_activity') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_friends_activity_text" type="text" id="menu_friends_activity_text"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_friends_activity_text'); ?>" /></td>
									<td align='center'><input type="checkbox" name="menu_friends_activity_other" id="menu_friends_activity_other" <?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_friends_activity_other') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_friends_activity_other_text" type="text" id="menu_friends_activity_other_text"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_friends_activity_other_text'); ?>" /></td>
								</tr>
								<tr>
									<td><span class="description"><?php echo __('All Activity', WPS_TEXT_DOMAIN); ?></span></td>
									<td align='center'><input type="checkbox" name="menu_all_activity" id="menu_all_activity" <?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_all_activity') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_all_activity_text" type="text" id="menu_all_activity_text"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_all_activity_text'); ?>" /></td>
									<td align='center'><input type="checkbox" name="menu_all_activity_other" id="menu_all_activity_other" <?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_all_activity_other') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_all_activity_other_text" type="text" id="menu_all_activity_other_text"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_all_activity_other_text'); ?>" /></td>
								</tr>
								<tr>
									<td><span class="description"><?php echo __('Friends', WPS_TEXT_DOMAIN); ?></span></td>
									<td align='center'><input type="checkbox" name="menu_friends" id="menu_friends" <?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_friends') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_friends_text" type="text" id="menu_friends_text"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_friends_text'); ?>" /></td>
									<td align='center'><input type="checkbox" name="menu_friends_other" id="menu_friends_other" <?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_friends_other') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_friends_other_text" type="text" id="menu_friends_other_text"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_friends_other_text'); ?>" /></td>
								</tr>
								<?php if ( function_exists('__wps__profile_plus') ) { ?>
								<tr>
									<td><span class="description"><?php echo __('Forum @mentions', WPS_TEXT_DOMAIN); ?></span></td>
									<td align='center'><input type="checkbox" name="menu_mentions" id="menu_mentions" <?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_mentions') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_mentions_text" type="text" id="menu_mentions_text"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_mentions_text'); ?>" /></td>
									<td align='center'><input type="checkbox" name="menu_mentions_other" id="menu_mentions_other" <?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_mentions_other') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_mentions_other_text" type="text" id="menu_mentions_other_text"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_mentions_other_text'); ?>" /></td>
								</tr>
								<?php } ?>
								<?php if ( function_exists('__wps__group') ) { ?>
								<tr>
									<td><span class="description"><?php echo __('Groups', WPS_TEXT_DOMAIN); ?></span></td>
									<td align='center'><input type="checkbox" name="menu_groups" id="menu_groups" <?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_groups') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_groups_text" type="text" id="menu_groups_text"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_groups_text'); ?>" /></td>
									<td align='center'><input type="checkbox" name="menu_groups_other" id="menu_groups_other" <?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_groups_other') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_groups_other_text" type="text" id="menu_groups_other_text"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_groups_other_text'); ?>" /></td>
								</tr>
								<?php } ?>
								<?php if ( function_exists('__wps__events_main') ) { ?>
								<tr>
									<td><span class="description"><?php echo __('Events', WPS_TEXT_DOMAIN); ?></span></td>
									<td align='center'><input type="checkbox" name="menu_events" id="menu_events" <?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_events') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_events_text" type="text" id="menu_events_text"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_events_text'); ?>" /></td>
									<td align='center'><input type="checkbox" name="menu_events_other" id="menu_events_other" <?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_events_other') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_events_other_text" type="text" id="menu_events_other_text"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_events_other_text'); ?>" /></td>
								</tr>
								<?php } ?>
								<?php if ( function_exists('__wps__gallery') ) { ?>
								<tr>
									<td><span class="description"><?php echo __('Gallery', WPS_TEXT_DOMAIN); ?></span></td>
									<td align='center'><input type="checkbox" name="menu_gallery" id="menu_gallery" <?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_gallery') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_gallery_text" type="text" id="menu_gallery_text"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_gallery_text'); ?>" /></td>
									<td align='center'><input type="checkbox" name="menu_gallery_other" id="menu_gallery_other" <?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_gallery_other') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_gallery_other_text" type="text" id="menu_gallery_other_text"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_gallery_other_text'); ?>" /></td>
								</tr>
								<?php } ?>
								<?php if ( function_exists('__wps__profile_plus') ) { ?>
								<tr>
									<td><span class="description"><?php echo __('Following', WPS_TEXT_DOMAIN); ?></span></td>
									<td align='center'><input type="checkbox" name="menu_following" id="menu_following" <?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_following') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_following_text" type="text" id="menu_following_text"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_following_text'); ?>" /></td>
									<td align='center'><input type="checkbox" name="menu_following_other" id="menu_following_other" <?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_following_other') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_following_other_text" type="text" id="menu_following_other_text"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_following_other_text'); ?>" /></td>
								</tr>
								<tr>
									<td><span class="description"><?php echo __('Followers', WPS_TEXT_DOMAIN); ?></span></td>
									<td align='center'><input type="checkbox" name="menu_followers" id="menu_followers" <?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_followers') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_followers_text" type="text" id="menu_followers_text"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_followers_text'); ?>" /></td>
									<td align='center'><input type="checkbox" name="menu_followers_other" id="menu_followers_other" <?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_followers_other') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_followers_other_text" type="text" id="menu_followers_other_text"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_followers_other_text'); ?>" /></td>
								</tr>
								<?php } ?>
								<?php if ( function_exists('__wps__lounge_main') ) { ?>
								<tr>
									<td><span class="description"><?php echo __('The Lounge', WPS_TEXT_DOMAIN); ?></span></td>
									<td align='center'><input type="checkbox" name="menu_lounge" id="menu_lounge" <?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_lounge') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_lounge_text" type="text" id="menu_lounge_text"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_lounge_text'); ?>" /></td>
									<td align='center'><input type="checkbox" name="menu_lounge_other" id="menu_lounge_other" <?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_lounge_other') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_lounge_other_text" type="text" id="menu_lounge_other_text"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_lounge_other_text'); ?>" /></td>
								</tr>
								<?php } ?>
								<tr>
									<td><span class="description"><?php echo __('Profile Photo', WPS_TEXT_DOMAIN); ?></span></td>
									<td align='center'><input type="checkbox" name="menu_avatar" id="menu_avatar" <?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_avatar') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_avatar_text" type="text" id="menu_avatar_text"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_avatar_text'); ?>" /></td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
								</tr>
								<tr>
									<td><span class="description"><?php echo __('Profile Details', WPS_TEXT_DOMAIN); ?></span></td>
									<td align='center'><input type="checkbox" name="menu_details" id="menu_details" <?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_details') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_details_text" type="text" id="menu_details_text"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_details_text'); ?>" /></td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
								</tr>
								<tr>
									<td><span class="description"><?php echo __('Community Settings', WPS_TEXT_DOMAIN); ?></span></td>
									<td align='center'><input type="checkbox" name="menu_settings" id="menu_settings" <?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_settings') == "on") { echo "CHECKED"; } ?>/></td>
									<td><input name="menu_settings_text" type="text" id="menu_settings_text"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_settings_text'); ?>" /></td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
								</tr>
							</table>
						
						</td> 
						</tr> 
	
						<tr valign="top"> 
						<td scope="row"><label for="menu_texthtml"><?php echo __('Profile Menu Text/HTML', WPS_TEXT_DOMAIN); ?></label></td>
						<td>
						<textarea name="menu_texthtml" id="menu_texthtml" rows="4" cols="30"><?php echo stripslashes(get_option(WPS_OPTIONS_PREFIX.'_menu_texthtml')); ?></textarea><br />
						<span class="description"><?php echo __('Text/HTML that appears at the end of the profile menu', WPS_TEXT_DOMAIN); ?></span></td> 
						</tr> 

					<?php } else {?>

						<input type="hidden" name="menu_profile" id="menu_profile" value="<?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_profile') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_profile_text" id="menu_profile_text" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_profile_text'); ?>" />
						<input type="hidden" name="menu_profile_other" id="menu_profile_other" value="<?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_profile_other') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_profile_other_text" id="menu_profile_other_text" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_profile_other_text'); ?>" />
						
						<input type="hidden" name="menu_my_activity" id="menu_my_activity" value="<?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_my_activity') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_my_activity_text" id="menu_my_activity_text" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_my_activity_text'); ?>" />
						<input type="hidden" name="menu_my_activity_other" id="menu_my_activity_other" value="<?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_my_activity_other') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_my_activity_other_text" id="menu_my_activity_other_text" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_my_activity_other_text'); ?>" />
						
						<input type="hidden" name="menu_friends_activity" id="menu_friends_activity" value="<?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_friends_activity') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_friends_activity_text" id="menu_friends_activity_text" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_friends_activity_text'); ?>" />
						<input type="hidden" name="menu_friends_activity_other" id="menu_friends_activity_other" value="<?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_friends_activity_other') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_friends_activity_other_text" id="menu_friends_activity_other_text" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_friends_activity_other_text'); ?>" />
						
						<input type="hidden" name="menu_all_activity" id="menu_all_activity" value="<?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_all_activity') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_all_activity_text" id="menu_all_activity_text" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_all_activity_text'); ?>" />
						<input type="hidden" name="menu_all_activity_other" id="menu_all_activity_other" value="<?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_all_activity_other') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_all_activity_other_text" id="menu_all_activity_other_text" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_all_activity_other_text'); ?>" />
						
						<input type="hidden" name="menu_friends" id="menu_friends" value="<?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_friends') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_friends_text" id="menu_friends_text" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_friends_text'); ?>" />
						<input type="hidden" name="menu_friends_other" id="menu_friends_other" value="<?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_friends_other') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_friends_other_text" id="menu_friends_other_text" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_friends_other_text'); ?>" />
						
						<input type="hidden" name="menu_mentions" id="menu_mentions" value="<?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_mentions') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_mentions_text" id="menu_mentions_text" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_mentions_text'); ?>" />
						<input type="hidden" name="menu_mentions_other" id="menu_mentions_other" value="<?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_mentions_other') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_mentions_other_text" id="menu_mentions_other_text" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_mentions_other_text'); ?>" />
						
						<input type="hidden" name="menu_groups" id="menu_groups" value="<?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_groups') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_groups_text" id="menu_groups_text" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_groups_text'); ?>" />
						<input type="hidden" name="menu_groups_other" id="menu_groups_other" value="<?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_groups_other') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_groups_other_text" id="menu_groups_other_text" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_groups_other_text'); ?>" />
						
						<input type="hidden" name="menu_events" id="menu_events" value="<?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_events') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_events_text" id="menu_events_text" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_events_text'); ?>" />
						<input type="hidden" name="menu_events_other" id="menu_events_other" value="<?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_events_other') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_events_other_text" id="menu_events_other_text" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_events_other_text'); ?>" />
						
						<input type="hidden" name="menu_gallery" id="menu_gallery" value="<?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_gallery') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_gallery_text" id="menu_gallery_text" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_gallery_text'); ?>" />
						<input type="hidden" name="menu_gallery_other" id="menu_gallery_other" value="<?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_gallery_other') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_gallery_other_text" id="menu_gallery_other_text" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_gallery_other_text'); ?>" />
						
						<input type="hidden" name="menu_following" id="menu_following" value="<?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_following') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_following_text" id="menu_following_text" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_following_text'); ?>" />
						<input type="hidden" name="menu_following_other" id="menu_following_other" value="<?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_following_other') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_following_other_text" id="menu_following_other_text" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_following_other_text'); ?>" />
						
						<input type="hidden" name="menu_followers" id="menu_followers" value="<?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_followers') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_followers_text" id="menu_followers_text" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_followers_text'); ?>" />
						<input type="hidden" name="menu_followers_other" id="menu_followers_other" value="<?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_followers_other') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_followers_other_text" id="menu_followers_other_text" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_followers_other_text'); ?>" />
						
						<input type="hidden" name="menu_lounge" id="menu_lounge" value="<?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_lounge') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_lounge_text" id="menu_lounge_text" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_lounge_text'); ?>" />
						<input type="hidden" name="menu_lounge_other" id="menu_lounge_other" value="<?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_lounge_other') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_lounge_other_text" id="menu_lounge_other_text" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_lounge_other_text'); ?>" />
						
						<input type="hidden" name="menu_avatar" id="menu_avatar" value="<?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_avatar') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_avatar_text" id="menu_avatar_text" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_avatar_text'); ?>" />
						
						<input type="hidden" name="menu_details" id="menu_details" value="<?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_details') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_details_text" id="menu_details_text" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_details_text'); ?>" />
						
						<input type="hidden" name="menu_settings" id="menu_settings" value="<?php if (get_option(WPS_OPTIONS_PREFIX.'_menu_settings') == "on") { echo "on"; } ?>" />
						<input type="hidden" name="menu_settings_text" id="menu_settings_text" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_settings_text'); ?>" />
						
						<input type="hidden" name="menu_texthtml" id="menu_texthtml" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_menu_texthtml'); ?>" />
						
											
					<?php } ?>

					<?php
						// Hook to add items to the Profile settings page
						echo apply_filters ( '__wps__profile_settings_before_ef_hook', "" );
					?>						

					<tr><td colspan="2"><h2><?php _e('Extended Fields', WPS_TEXT_DOMAIN) ?></h2></td></tr>

					<?php if (get_option(WPS_OPTIONS_PREFIX.'_use_templates') != "on") { ?>

						<?php
						// Optionally include extended fields
						$sql = "SELECT * FROM ".$wpdb->base_prefix."symposium_extended";
						$extensions = $wpdb->get_results($sql);
		
						$ext_rows = array();		
						if ($extensions) {		
							foreach ($extensions as $extension) {
								array_push ($ext_rows, array (	'eid'=>$extension->eid,
																'name'=>$extension->extended_name,
																'type'=>$extension->extended_type,
																'order'=>$extension->extended_order ) );
							}
						}						
						if ($ext_rows) {
							?>
							<tr valign="top"> 
							<td scope="row"><label for="redirect_wp_profile"><?php echo __('Extended Fields to show<br />on profile page header', WPS_TEXT_DOMAIN); ?></label></td>
							<td>
							<?php
							$include = get_option(WPS_OPTIONS_PREFIX.'_profile_extended_fields');
							$ext_rows = __wps__sub_val_sort($ext_rows,'order');
							foreach ($ext_rows as $row) {
								echo '<input type="checkbox" ';
								if (strpos($include, $row['eid'].',') !== FALSE)
									echo 'CHECKED ';
								echo 'name="__wps__profile_extended_fields[]" value="'.$row['eid'].'" />';
								echo ' <span class="description">'.stripslashes($row['name']).'</span><br />';
							}
							echo '</td></tr>';

						}
						?>
						
					<?php } else { 
						echo '<input type="hidden" name="__wps__profile_extended_fields_list" value="'.get_option(WPS_OPTIONS_PREFIX.'_profile_extended_fields').'" />';
					} ?>
					
					<tr valign="top"> 
					<td scope="row"><?php echo __('Current extended fields', WPS_TEXT_DOMAIN); ?></td><td>
					
						<?php
						echo '<input type="checkbox" name="profile_show_unchecked" id="profile_show_unchecked"';
						if (get_option(WPS_OPTIONS_PREFIX.'_profile_show_unchecked') == "on") { echo "CHECKED"; }
						echo '/> <span class="description">'. __('Display checkboxes fields that are not selected (on member profile page)', WPS_TEXT_DOMAIN).'</span>';

						// Extended Fields table
						$extensions = $wpdb->get_results("SELECT * FROM ".$wpdb->base_prefix."symposium_extended ORDER BY extended_order, extended_name");
						$sql = " WHERE meta_key NOT LIKE 'symposium_%'";
						$sql .= " AND meta_key NOT LIKE '%wp_%'";
						$sql .= " AND meta_key NOT LIKE '%level%'";
						$sql .= " AND meta_key NOT LIKE '%role%'";
						$sql .= " AND meta_key NOT LIKE '%capabilit%'";
						$sql = apply_filters( 'symposium_query_wp_metadata_hook', $sql );						
						$rows = $wpdb->get_results("SELECT DISTINCT meta_key FROM ".$wpdb->base_prefix."usermeta".$sql);
						
						echo '<style>.widefat td { border:0 } </style>';
						echo '<table class="widefat">';
						echo '<thead>';
						echo '<tr>';
						echo '<th style="width:40px">'.__('Order', WPS_TEXT_DOMAIN).'</th>';
						echo '<th style="width:40px">'.__('Slug', WPS_TEXT_DOMAIN).'</th>';
						echo '<th>'.__('Label', WPS_TEXT_DOMAIN).'</th>';
						echo '<th>'.__('Default Value', WPS_TEXT_DOMAIN).'</th>';
						echo '<th>'.__('Read Only?', WPS_TEXT_DOMAIN).'</th>';
						echo '<th>'.__('Advanced Search?', WPS_TEXT_DOMAIN).'</th>';
						echo '<th style="width:80px">'.__('Type', WPS_TEXT_DOMAIN).'</th>';
						echo '<th style="width:30px">&nbsp;</th>';
						echo '</tr>';
						echo '</thead>';
						echo '<tbody>';
						$cnt = 0;
						if ($extensions) {
							foreach ($extensions as $extension) {

								$slug = (!$extension->extended_slug) ? 'slug_'.$extension->eid : $extension->extended_slug ;
								$cnt++;
								if ( $cnt % 2 != 0 ) {
									echo '<tr>';
								} else {
									echo '<tr style="background-color:#eee">';
								}
									echo '<td>';
									echo '<input type="hidden" name="eid[]" value="'.$extension->eid.'" />';
									echo '<input type="text" name="order[]" style="width:40px" value="'.$extension->extended_order.'" />';
									echo '</td>';
									echo '<td>';
									echo '<input type="hidden" name="slug[]" value="'.$slug.'" />'.$slug;
									echo '</td>';
									echo '<td>';
									echo '<input type="text" name="name[]" value="'.stripslashes($extension->extended_name).'" />';
									echo '</td>';
									echo '<td>';
									echo '<input type="text" name="default[]" value="'.stripslashes($extension->extended_default).'" />';
									echo '</td>';
									echo '<td>';
									echo '<select name="readonly[]">';
									echo '<option value=""';
										if ($extension->readonly != 'on') echo ' SELECTED';
										echo '>'.__('No', WPS_TEXT_DOMAIN).'</option>';
									echo '<option value="on"';
										if ($extension->readonly == 'on') echo ' SELECTED';
										echo '>'.__('Yes', WPS_TEXT_DOMAIN).'</option>';
									echo '</select>';
									echo '</td>';
									echo '<td>';
									if ($extension->extended_type == 'Checkbox' || $extension->extended_type == 'List') {
										echo '<select name="search[]">';
										echo '<option value=""';
											if ($extension->search != 'on') echo ' SELECTED';
											echo '>'.__('No', WPS_TEXT_DOMAIN).'</option>';
										echo '<option value="on"';
											if ($extension->search == 'on') echo ' SELECTED';
											echo '>'.__('Yes', WPS_TEXT_DOMAIN).'</option>';
										echo '</select>';
									} else {
										echo '<select name="search[]">';
										echo '<option value=""';
											if ($extension->search != 'on') echo ' SELECTED';
											echo '>'.__('No (wrong type)', WPS_TEXT_DOMAIN).'</option>';
										echo '</select>';
									}
									echo '</td>';
									echo '<td>';
									echo '<select name="type[]">';
									echo '<option value="Text"';
										if ($extension->extended_type == 'Text') { echo ' SELECTED'; }
										echo '>'.__('Text', WPS_TEXT_DOMAIN).'</option>';
									echo '<option value="Checkbox"';
										if ($extension->extended_type == 'Checkbox') { echo ' SELECTED'; }
										echo '>'.__('Checkbox', WPS_TEXT_DOMAIN).'</option>';
									echo '<option value="List"';
										if ($extension->extended_type == 'List') { echo ' SELECTED'; }
										echo '>'.__('List', WPS_TEXT_DOMAIN).'</option>';
									echo '<option value="Textarea"';
										if ($extension->extended_type == 'Textarea') { echo ' SELECTED'; }
										echo '>'.__('Textarea', WPS_TEXT_DOMAIN).'</option>';
									echo '</select>';
									echo '</td>';
									echo '<td>';
									echo "<a href='admin.php?page=symposium_profile&view=profile&del_eid=".$extension->eid."' class='delete'>".__('Delete', WPS_TEXT_DOMAIN)."</a>";
									echo '</td>';
								echo '</tr>';
								if ( $cnt % 2 != 0 ) {
									echo '<tr>';
								} else {
									echo '<tr style="background-color:#eee">';
								}
								echo '<td colspan="2"></td><td colspan="6">';
									echo __('Linked WP Metadata', WPS_TEXT_DOMAIN).':<br />';
                                    echo '<input type="hidden" name="old_wp_usermeta[]" value="'.$extension->wp_usermeta.'" />';
									echo '<select name="wp_usermeta[]"><option value="" SELECTED></option>';
									if ($rows) {
										foreach ($rows as $row) {
											echo '<option value="'.$row->meta_key .'"';
											if ( $row->meta_key == $extension->wp_usermeta ) { echo ' SELECTED'; }
											echo '>'.$row->meta_key.'</option>';
										}
									}
									echo '</select>';
								echo '</td>';
								echo '</tr>';
							}
						}
						echo '</table>';
						
						echo '<tr valign="top">';
						echo '<td scope="row">'.__('Add extended field', WPS_TEXT_DOMAIN).'</td><td>';

						echo '<table class="widefat">';
						echo '<thead><tr>';
						echo '<th style="width:40px">'.__('Order', WPS_TEXT_DOMAIN).'</th>';
						echo '<th style="width:40px">'.__('Slug', WPS_TEXT_DOMAIN).'</th>';
						echo '<th>'.__('Label', WPS_TEXT_DOMAIN).'</th>';
						echo '<th>'.__('Default Value', WPS_TEXT_DOMAIN).'</th>';
						echo '<th>&nbsp;</th>';
						echo '<th>&nbsp;</th>';
						echo '<th style="width:80px">'.__('Type', WPS_TEXT_DOMAIN).'</th>';
						echo '<th style="width:30px">&nbsp;</th>';
						echo '</tr></thead>';
						echo '<tr>';
							echo '<td>';
							echo '<input type="text" name="new_order" style="width:40px" onclick="javascript:this.value = \'\'" value="0" />';
							echo '</td>';
							echo '<td>';
							echo '<input type="text" name="new_slug" style="width:75px" onclick="javascript:this.value = \'\'" value="'.__('New slug', WPS_TEXT_DOMAIN).'" />';
							echo '</td>';
							echo '<td>';
							echo '<input type="text" name="new_name" onclick="javascript:this.value = \'\'" value="'.__('New label', WPS_TEXT_DOMAIN).'" />';
							echo '</td>';
							echo '<td>';
							echo '<input type="text" name="new_default" onclick="javascript:this.value = \'\'" value="" />';
							echo '</td>';
							echo '<td>';
							echo '<select name="new_readonly">';
							echo '<option value="" SELECTED>'.__('No', WPS_TEXT_DOMAIN).'</option>';
							echo '<option value="on">'.__('Yes', WPS_TEXT_DOMAIN).'</option>';
							echo '</select>';
							echo '</td>';
							echo '<td></td>';
							echo '<td>';
							echo '<select name="new_type">';
							echo '<option value="Text" SELECTED>'.__('Text', WPS_TEXT_DOMAIN).'</option>';
							echo '<option value="Checkbox">'.__('Checkbox', WPS_TEXT_DOMAIN).'</option>';
							echo '<option value="List">'.__('List', WPS_TEXT_DOMAIN).'</option>';
							echo '<option value="Textarea">'.__('Textarea', WPS_TEXT_DOMAIN).'</option>';
							echo '</select>';
							echo '</td>';
							echo '<td>&nbsp;</td>';
						echo '</tr>';
						echo '<tr>';
							echo '<td colspan="2"></td><td colspan="5">';
							echo __('Linked WP Metadata', WPS_TEXT_DOMAIN).':<br />';
							echo '<select name="new_wp_usermeta"><option value="" SELECTED></option>';
							if ($rows) {
								foreach ($rows as $row) {
									echo '<option value="'.$row->meta_key .'">'.$row->meta_key.'</option>';
								}
							}
							echo '</select>';
							echo '</td>';
						echo '</tr>';
						echo '<tr><td colspan="7"><span class="description">';
						echo __('For lists, enter all the values separated by commas - the first value is the default choice.', WPS_TEXT_DOMAIN);
						echo '<br />'.__('For checkboxes, enter a value of \'on\' to default to checked.', WPS_TEXT_DOMAIN);
						echo '<br />'.__('Slugs should be a single descriptive word.', WPS_TEXT_DOMAIN);
						echo '<br />'.__('Members extended field values are not shown when they are left empty, except checkboxes where you can choose what happens above.', WPS_TEXT_DOMAIN);

						echo '<br /><br /><strong>'.__('Extended Fields and WordPress Profile Metadata', WPS_TEXT_DOMAIN).'</strong>';
						echo '<br />'.__('Extended fields can be linked to WordPress profile metadata - make sure you choose the correct type to match the WordPress profile metadata.', WPS_TEXT_DOMAIN);
						echo '<br />'.__('Only link to WordPress profile metadata that you want your user\'s to access, and use the read-only setting to stop them making changes.', WPS_TEXT_DOMAIN);

						// Display user info as an example
						$rows = $wpdb->get_results("SELECT meta_key, meta_value FROM ".$wpdb->base_prefix."usermeta".$sql." AND user_id = '".$user_ID."'");
						echo '<br /><br />';
						echo '<input id="symposium_meta_show_button" style="margin-bottom:10px;" onclick="document.getElementById(\'symposium_meta_show\').style.display=\'block\';document.getElementById(\'symposium_meta_show_button\').style.display=\'none\';document.getElementById(\'symposium_meta_show_button_hide\').style.display=\'block\';" value="'.__('Show WP metadata for current user', WPS_TEXT_DOMAIN).'" type="button">';
						echo '<input id="symposium_meta_show_button_hide" style="margin-bottom:10px;display:none;" onclick="document.getElementById(\'symposium_meta_show\').style.display=\'none\';document.getElementById(\'symposium_meta_show_button\').style.display=\'block\';document.getElementById(\'symposium_meta_show_button_hide\').style.display=\'none\';" value="'.__('Hide WP metadata', WPS_TEXT_DOMAIN).'" type="button">';
						echo '<div id="symposium_meta_show" style="display:none;">';
						
						echo '<table class="widefat" style="width:400px"><thead><tr>';
						echo '<th>'.__('WP Metadata', WPS_TEXT_DOMAIN).'</th>';
						echo '<th>'.__('Value', WPS_TEXT_DOMAIN).'</th>';
						echo '</tr></thead><tbody>';
						foreach ($rows as $row) {
							echo '<tr><td>'.$row->meta_key.'</td><td>';
							$meta_value = maybe_unserialize($row->meta_value);
							if (is_array($meta_value)) {
								echo '<input class="regular-text all-options disabled" type="text" value="'.__('SERIALIZED DATA', WPS_TEXT_DOMAIN).'" disabled="disabled" />';
							} else {
								// let's cut very long strings in parts so that browsers display them correctly
								$v = str_replace(",", ", ", $row->meta_value);
								$v = str_replace(";", "; ", $v);
								echo $v;
							}
							echo '</td></tr>';
						}
						echo '</tbody></table>';
						echo '</div>';
						
						echo '</td></tr></tbody></table>'; // class="widefat"
						
						// Hook to add items to the Profile settings page
						echo apply_filters ( '__wps__profile_settings_hook', "" );

						?>
						<tr><td colspan="2"><h2>Shortcodes</h2></td></tr>
			
						<table style="margin-left:10px; margin-top:10px;">						
							<tr><td>[<?php echo WPS_SHORTCODE_PREFIX; ?>-stream]</td>
								<td><?php echo __('Show all activity, with activity box.', WPS_TEXT_DOMAIN); ?></td></tr>
							<tr><td width="165px">[<?php echo WPS_SHORTCODE_PREFIX; ?>-profile]</td>
								<td><?php echo __('Profile page, defaulting to activity.', WPS_TEXT_DOMAIN); ?></td></tr>
							<tr><td>[<?php echo WPS_SHORTCODE_PREFIX; ?>-extended]</td>
								<td><?php echo __('Profile page, defaulting to extended information.', WPS_TEXT_DOMAIN); ?></td></tr>
							<tr><td>[<?php echo WPS_SHORTCODE_PREFIX; ?>-activity]</td>
								<td><?php echo __('Profile page, defaulting to friends activity.', WPS_TEXT_DOMAIN); ?></td></tr>
							<tr><td>[<?php echo WPS_SHORTCODE_PREFIX; ?>-settings]</td>
								<td><?php echo __('Profile page, defaulting to settings.', WPS_TEXT_DOMAIN); ?></td></tr>
							<tr><td>[<?php echo WPS_SHORTCODE_PREFIX; ?>-gallery]</td>
								<td><?php echo __('Profile page, defaulting to gallery.', WPS_TEXT_DOMAIN); ?></td></tr>
							<tr><td>[<?php echo WPS_SHORTCODE_PREFIX; ?>-personal]</td>
								<td><?php echo __('Profile page, defaulting to personal information.', WPS_TEXT_DOMAIN); ?></td></tr>
							<tr><td>[<?php echo WPS_SHORTCODE_PREFIX; ?>-friends]</td>
								<td><?php echo __('Profile page, defaulting to friends.', WPS_TEXT_DOMAIN); ?></td></tr>
							<tr><td>[<?php echo WPS_SHORTCODE_PREFIX; ?>-avatar]</td>
								<td><?php echo __('Profile page, defaulting to avatar upload.', WPS_TEXT_DOMAIN); ?></td></tr>
							<tr><td>[<?php echo WPS_SHORTCODE_PREFIX; ?>-following]</td>
								<td><?php echo __('Profile page, defaulting to members following.', WPS_TEXT_DOMAIN); ?></td></tr>
							<tr><td>[<?php echo WPS_SHORTCODE_PREFIX; ?>-menu]</td>
								<td><?php echo __('Display the profile menu.', WPS_TEXT_DOMAIN); ?></td></tr>
							<tr><td>[<?php echo WPS_SHORTCODE_PREFIX; ?>-member-header]</td>
								<td><?php echo __('Display just the member profile page header.', WPS_TEXT_DOMAIN); ?></td></tr>
							<?php if (function_exists('__wps__profile_plus')) { ?>
							<tr><td width="165px">[<?php echo WPS_SHORTCODE_PREFIX; ?>-following]</td>
								<td><?php echo __('Display the profile page, defaulting to show who the member is following.', WPS_TEXT_DOMAIN); ?></td></tr>
							<?php } ?>
						</table>
						
						<?php
												
						echo '</table>'; // class="form-table"
					?>
					</td></tr>
					
					</table>
	
					<?php
					echo '<p class="submit" style="margin-left:6px">';
					echo '<input type="submit" name="Submit" class="button-primary" value="'.__('Save Changes', WPS_TEXT_DOMAIN).'" />';
					echo '</p>';
					echo '</form>';
					
					
		__wps__show_tabs_header_end();
	  	

	echo '</div>';									  

}

function __wps__plugin_audit() {

  	echo '<div class="wrap">';
  	echo '<div id="icon-themes" class="icon32"><br /></div>';
  	echo '<h2>'.sprintf(__('%s Audit', WPS_TEXT_DOMAIN), WPS_WL).'</h2><br />';
	__wps__show_manage_tabs_header('audit');

	global $wpdb, $blog_id;

	// Clear audit table
	if( isset($_POST[ 'symposium_update' ]) && $_POST[ 'symposium_update' ] == '__wps__plugin_audit_clear' ) {
		$sql = "DELETE FROM ".$wpdb->base_prefix."symposium_audit";
		$wpdb->query($sql);

		echo "<div class='updated slideaway'><p>".__('Log cleared', WPS_TEXT_DOMAIN).".</p></div>";

	}

	// See if the user has posted general settings
	if( isset($_POST[ 'symposium_update' ]) && $_POST[ 'symposium_update' ] == '__wps__plugin_audit' ) {

		$type = $_POST['type'];
		$blogID = $_POST['blog'];
		$action = $_POST['action'];
		$userID = $_POST['user_id'];
		$current_userID = $_POST['current_user_id'];
		$metafield = $_POST['meta'];
		
		$orderby = $_POST['orderby'];
		$asc = $_POST['asc'];
		$start_date = $_POST['start'];
		$end_date = $_POST['end'];
		$count = $_POST['count'];
					
	} else {
		
		$type = 'all';
		$blogID = $blog_id;
		$action = 'all';
		$userID = 'all';
		$current_userID = 'all';
		$metafield = 'all';
		
		$orderby = 'blog_id';
		$asc = '';
		$start = date("Y-m-d");
		$start_date = date('Y-m-d', strtotime($start . ' - 1 month'));
		$end = date("Y-m-d");
		$end_date = date('Y-m-d', strtotime($end . ' + 1 day'));
		$count = 50;
	}


?>

	<table> 	

		<tr style="font-weight:bold">
		<td><?php _e('Type', WPS_TEXT_DOMAIN); ?></td>
		<td><?php _e('Blog', WPS_TEXT_DOMAIN); ?></td>
		<td><?php _e('Action', WPS_TEXT_DOMAIN); ?></td>
		<td><?php _e('User', WPS_TEXT_DOMAIN); ?></td>
		<td><?php _e('Current User', WPS_TEXT_DOMAIN); ?></td>
		<td><?php _e('Meta', WPS_TEXT_DOMAIN); ?></td>
		</tr> 
		<tr>
		<form method="post" action=""> 
		<input type="hidden" name="symposium_update" value="__wps__plugin_audit">
		<td>
		<select name="type">
			<option value="all" <?php if ($type == 'all') echo ' SELECTED'; ?>><?php _e('All', WPS_TEXT_DOMAIN); ?></option>
			<option value="usermeta" <?php if ($type == 'usermeta') echo ' SELECTED'; ?>><?php _e('User meta', WPS_TEXT_DOMAIN); ?></option>
		</select>
		</td> 
		<td>
		<select name="blog">
			<option value="all"><?php _e('All', WPS_TEXT_DOMAIN); ?></option>
			<?php 
		    $blogs = $wpdb->get_results("SELECT * FROM ".$wpdb->base_prefix."blogs");
		    if ($blogs) {
		        foreach($blogs as $blog) {
		            echo '<option value='.$blog->blog_id;
		            if ($blog->blog_id == $blogID) echo ' SELECTED';
		            echo '>'.$blog->blog_id.': '.$blog->path.'</option>';
		        }
		    }   	
		    ?>		
		</select>
		</td> 
		<td>
		<select name="action">
			<option value="all"><?php _e('All', WPS_TEXT_DOMAIN); ?></option>
			<?php 
		    $actions = $wpdb->get_results("SELECT DISTINCT action FROM ".$wpdb->base_prefix."symposium_audit ORDER BY action");
		    if ($actions) {
		        foreach($actions as $a) {
		            echo '<option value='.$a->action;
		            if ($a->action == $action) echo ' SELECTED';
		            echo '>'.$a->action.'</option>';
		        }
		    }   	
		    ?>		
		</select>
		</td> 
		<td>
		<select name="user_id">
			<option value="all"><?php _e('All', WPS_TEXT_DOMAIN); ?></option>
			<?php 
		    $users = $wpdb->get_results("SELECT * FROM ".$wpdb->base_prefix."users ORDER BY display_name");
		    if ($users) {
		        foreach($users as $user) {
		            echo '<option value='.$user->ID;
		            if ($user->ID == $userID) echo ' SELECTED';
		            echo '>'.$user->display_name.' ('.$user->user_login.')</option>';
		        }
		    }   	
		    ?>		
		</select>
		</td> 
		<td>
		<select name="current_user_id">
			<option value="all"><?php _e('All', WPS_TEXT_DOMAIN); ?></option>
			<?php 
		    $users = $wpdb->get_results("SELECT * FROM ".$wpdb->base_prefix."users ORDER BY display_name");
		    if ($users) {
		        foreach($users as $user) {
		            echo '<option value='.$user->ID;
					if ($user->ID == $current_userID) echo ' SELECTED';
		            echo '>'.$user->display_name.' ('.$user->user_login.')</option>';
		        }
		    }   	
		    ?>		
		</select>
		</td>
		<td>
		<select name="meta">
			<option value="all"><?php _e('All', WPS_TEXT_DOMAIN); ?></option>
			<?php 
		    $meta = $wpdb->get_results("SELECT DISTINCT meta FROM ".$wpdb->base_prefix."symposium_audit ORDER BY meta");
		    if ($meta) {
		        foreach($meta as $m) {
		            echo '<option value='.$m->meta;
		            if ($m->meta == $metafield) echo ' SELECTED';
		            echo '>'.$m->meta.'</option>';
		        }
		    }   	
		    ?>		
		</select>
		</td> 
		</tr>
		<tr><td colspan="8"><hr /></td></tr>
		<tr style="font-weight:bold">
		<td><?php _e('Start', WPS_TEXT_DOMAIN); ?></td>
		<td><?php _e('End', WPS_TEXT_DOMAIN); ?></td>
		<td><?php _e('Order by', WPS_TEXT_DOMAIN); ?></td>
		<td></td>
		<td><?php _e('Count', WPS_TEXT_DOMAIN); ?></td>
		</tr>		
		<tr>
		<td>
			<input type="text" name="start" style="width:100px" value="<?php echo $start_date; ?>" />
		</td> 
		<td>
			<input type="text" name="end" style="width:100px" value="<?php echo $end_date; ?>" />
		</td> 
		<td>
		<select name="orderby">
			<option value="blog_id" <?php if ($orderby == 'blog') echo ' SELECTED'; ?>><?php _e('Blog', WPS_TEXT_DOMAIN); ?></option>
			<option value="action" <?php if ($orderby == 'action') echo ' SELECTED'; ?>><?php _e('Action', WPS_TEXT_DOMAIN); ?></option>
			<option value="user_id" <?php if ($orderby == 'user_id') echo ' SELECTED'; ?>><?php _e('User', WPS_TEXT_DOMAIN); ?></option>
			<option value="current_user_id" <?php if ($orderby == 'current_user_id') echo ' SELECTED'; ?>><?php _e('Current User', WPS_TEXT_DOMAIN); ?></option>
			<option value="meta" <?php if ($orderby == 'meta') echo ' SELECTED'; ?>><?php _e('Meta', WPS_TEXT_DOMAIN); ?></option>
			<option value="timestamp" <?php if ($orderby == 'timestamp') echo ' SELECTED'; ?>><?php _e('Date', WPS_TEXT_DOMAIN); ?></option>
		</select>
		</td>
		<td>
		<select name="asc">
			<option value=""><?php _e('Ascending', WPS_TEXT_DOMAIN); ?></option>
			<option value="DESC" <?php if ($asc == 'DESC') echo ' SELECTED'; ?>><?php _e('Descending', WPS_TEXT_DOMAIN); ?></option>
		</select>
		</td> 
		<td>
			<table><tr><td>
				<input type="text" name="count" style="float: left;width:35px;margin-right:5px;" value="<?php echo $count; ?>" />
				<input type="submit" style="float:left; margin-right:15px;" class="button-primary" value="<?php _e('Filter', WPS_TEXT_DOMAIN); ?>" />
				</form>
			</td><td>
				<form method="post" action="">
					<input type="hidden" name="symposium_update" value="__wps__plugin_audit_clear">
					<input type="submit" style="float:left" class="__wps__are_you_sure button-primary" value="<?php _e('Clear audit log', WPS_TEXT_DOMAIN); ?>" />
				</form>
			</td></tr></table>
		</td> 
		</tr> 
		
	</table>

	<table class="widefat" style="margin-top:30px">
		<thead>
		<tr>
		<th><?php _e('Type', WPS_TEXT_DOMAIN); ?></th>
		<th><?php _e('Blog', WPS_TEXT_DOMAIN); ?></th>
		<th><?php _e('Action', WPS_TEXT_DOMAIN); ?></th>
		<th><?php _e('User', WPS_TEXT_DOMAIN); ?></th>
		<th><?php _e('Current User', WPS_TEXT_DOMAIN); ?></th>
		<th><?php _e('Meta', WPS_TEXT_DOMAIN); ?></th>
		<th><?php _e('Value', WPS_TEXT_DOMAIN); ?></th>
		<th><?php _e('Timestamp', WPS_TEXT_DOMAIN); ?></th>
		</tr> 
		</thead>
				
		<?php
		$sql = "
		SELECT a.*, u1.display_name, u2.display_name as display_name2
		FROM ".$wpdb->base_prefix."symposium_audit a
		LEFT JOIN ".$wpdb->base_prefix."users u1 ON a.user_id = u1.ID
		LEFT JOIN ".$wpdb->base_prefix."users u2 ON a.current_user_id = u2.ID WHERE ";
		if ($type != 'all') 			$sql .= " a.type = '".$type."' AND";
		if ($blogID != 'all') 			$sql .= " a.blog_id = ".$blogID." AND";
		if ($action != 'all') 			$sql .= " a.action = '".$action."' AND";
		if ($userID != 'all') 			$sql .= " a.user_id = ".$userID." AND";
		if ($current_userID != 'all') 	$sql .= " a.current_user_id = ".$current_userID." AND";
		if ($metafield != 'all') 		$sql .= " a.meta = '".$metafield."' AND";
		$sql .= " (timestamp >= '".$start_date." 00:00:00' AND timestamp <= '".$end_date." 23:59:59')";
		$sql .= " ORDER BY ".$orderby;
		if ($asc) $sql .= " DESC";
		$sql .= " LIMIT 0,".$count;
				
		$results = $wpdb->get_results($sql);
		if ($results) {
			foreach ($results as $r) {
				echo '<tr>';
					echo '<td>'.$r->type.'</td>';
					echo '<td>'.$r->blog_id.'</td>';
					echo '<td>'.$r->action.'</td>';
					echo '<td>'.$r->display_name.'</td>';
					echo '<td>'.$r->display_name2.'</td>';
					echo '<td>'.$r->meta.'</td>';
					echo '<td>'.$r->value.'</td>';
					echo '<td>'.$r->timestamp.'</td>';
				echo '</tr>';
			}
		} else {
			echo '<tr><td colspan="8">'.__('No results', WPS_TEXT_DOMAIN).'</td></tr>';
		}
		
		?>
												
	</table>
	
	<?php
			
	__wps__show_manage_tabs_header_end();

	echo '</div>';					  
	
}

function __wps__plugin_thesaurus() {

  	echo '<div class="wrap">';
  	echo '<div id="icon-themes" class="icon32"><br /></div>';
  	echo '<h2>'.sprintf(__('%s Management', WPS_TEXT_DOMAIN), WPS_WL).'</h2><br />';
	__wps__show_manage_tabs_header('thesaurus');

	global $wpdb;

		// See if the user has posted general settings
	if( isset($_POST[ 'symposium_update' ]) && $_POST[ 'symposium_update' ] == '__wps__plugin_theasurus' ) {

		update_option(WPS_OPTIONS_PREFIX.'_alt_friend', stripslashes($_POST[ 'alt_friend' ]));
		update_option(WPS_OPTIONS_PREFIX.'_alt_friends', stripslashes($_POST[ 'alt_friends' ]));
		update_option(WPS_OPTIONS_PREFIX.'_alt_everyone', stripslashes($_POST[ 'alt_everyone' ]));
			
		echo "<div class='updated slideaway'>";
		
		// Put an settings updated message on the screen
		echo "<p>".__('Saved', WPS_TEXT_DOMAIN).".</p></div>";
		
	}

	echo '<p>'.sprintf(__('Enter alternatives for the following to match your site. For example, replace Friend/Friends with Colleague/Colleagues. You may also want to change the <a href="%s">Profile menu items</a>.', WPS_TEXT_DOMAIN), esc_url( admin_url('admin.php?page=symposium_profile') )).'</p>';

?>
	<form method="post" action=""> 
	<input type="hidden" name="symposium_update" value="__wps__plugin_theasurus">

	<table> 	

		<tr style="font-weight:bold"> 
		<td width="150"><?php _e('Label', WPS_TEXT_DOMAIN); ?></td> 
		<td width="200"><?php _e('Singular', WPS_TEXT_DOMAIN); ?></td>
		<td><?php _e('Plural', WPS_TEXT_DOMAIN); ?></td>
		</tr> 
												
		<tr> 
		<td><label for="alt_friend"><?php echo __('Friends', WPS_TEXT_DOMAIN); ?></label></td> 
		<td><input name="alt_friend" type="text" id="alt_friend" style="width:100px" value="<?php echo stripslashes(get_option(WPS_OPTIONS_PREFIX.'_alt_friend')); ?>" class="regular-text" /></td> 
		<td><input name="alt_friends" type="text" id="alt_friends" style="width:100px" value="<?php echo stripslashes(get_option(WPS_OPTIONS_PREFIX.'_alt_friends')); ?>" class="regular-text" /></td> 
		</tr> 
												
		<tr> 
		<td><label for="alt_everyone"><?php echo __('Everyone', WPS_TEXT_DOMAIN); ?></label></td> 
		<td><input name="alt_everyone" type="text" id="alt_everyone" style="width:100px" value="<?php echo stripslashes(get_option(WPS_OPTIONS_PREFIX.'_alt_everyone')); ?>" class="regular-text" /></td> 
		<td>&nbsp;</td> 
		</tr> 
												
	</table>
	 
	<p class="submit" style="margin-left:6px"> 
	<input type="submit" name="Submit" class="button-primary" value="<?php echo __('Save Changes', WPS_TEXT_DOMAIN); ?>" /> 
	</p> 
	
	<?php
	
	echo '</form>';
		
	__wps__show_manage_tabs_header_end();

	echo '</div>';					  
	
}

function __wps__plugin_advertising() {

  	echo '<div class="wrap">';
  	echo '<div id="icon-themes" class="icon32"><br /></div>';
  	echo '<h2>'.sprintf(__('%s Advertising', WPS_TEXT_DOMAIN), WPS_WL).'</h2><br />';
	__wps__show_manage_tabs_header('advertising');

	global $wpdb;


	// See if the user has posted advertising settings
	if( isset($_POST[ 'symposium_update' ]) && $_POST[ 'symposium_update' ] == '__wps__plugin_advertising' ) {

		update_option(WPS_OPTIONS_PREFIX.'_ad_forum_topic_start', stripslashes($_POST[ 'ad_forum_topic_start' ]));
		update_option(WPS_OPTIONS_PREFIX.'_ad_forum_categories', stripslashes($_POST[ 'ad_forum_categories' ]));
		update_option(WPS_OPTIONS_PREFIX.'_ad_forum_in_categories', stripslashes($_POST[ 'ad_forum_in_categories' ]));
			
		echo "<div class='updated slideaway'>";
		
		// Put an settings updated message on the screen
		echo "<p>".__('Saved', WPS_TEXT_DOMAIN).".</p></div>";
		
	}

	echo '<p>'.__('Post advertising code below, for example from Google Adsense. You can also include HTML (maybe for layout).', WPS_TEXT_DOMAIN).'</p>';
	echo '<p>'.__('Please keep in mind that Google set a maximum of three standard ad units per web page.', WPS_TEXT_DOMAIN).'</p>';

?>
	

	<form method="post" action=""> 
	<input type="hidden" name="symposium_update" value="__wps__plugin_advertising">

	<h2><?php _e('Forum', WPS_TEXT_DOMAIN); ?></h2>

	<?php echo __('Within topic, under the initial starting post, before the replies.', WPS_TEXT_DOMAIN); ?><br />
	<textarea name="ad_forum_topic_start" type="text" id="ad_forum_topic_start" style="width:600px; height:200px;"><?php echo stripslashes(get_option(WPS_OPTIONS_PREFIX.'_ad_forum_topic_start')); ?></textarea><br />

	<?php echo __('On the list of forum categories, or list of topics within a category.', WPS_TEXT_DOMAIN); ?><br />
	<?php echo sprintf(__('Add [top_advert] to the <a href="%s">Forum Header template</a>, probably after the bottom line that\'s there already.', WPS_TEXT_DOMAIN), esc_url( admin_url('admin.php?page=symposium_templates')) ); ?><br />
	<textarea name="ad_forum_categories" type="text" id="ad_forum_categories" style="width:600px; height:200px;"><?php echo stripslashes(get_option(WPS_OPTIONS_PREFIX.'_ad_forum_categories')); ?></textarea><br />
												
	<?php echo __('Within the list of forum categories, or list of topics within a category (after the third item).', WPS_TEXT_DOMAIN); ?><br />
	<textarea name="ad_forum_in_categories" type="text" id="ad_forum_in_categories" style="width:600px; height:200px;"><?php echo stripslashes(get_option(WPS_OPTIONS_PREFIX.'_ad_forum_in_categories')); ?></textarea><br />
												
	 
	<p class="submit" style="margin-left:6px"> 
	<input type="submit" name="Submit" class="button-primary" value="<?php echo __('Save Changes', WPS_TEXT_DOMAIN); ?>" /> 
	</p> 
	
	<?php
	
	echo '</form>';
		
	__wps__show_manage_tabs_header_end();

	echo '</div>';					  
	
}

function __wps__plugin_settings() {

  	echo '<div class="wrap">';
  	echo '<div id="icon-themes" class="icon32"><br /></div>';
  	echo '<h2>'.sprintf(__('%s Management', WPS_TEXT_DOMAIN), WPS_WL).'</h2><br />';
	__wps__show_manage_tabs_header('settings');

	global $wpdb;

		// See if the user has posted general settings
		if( isset($_POST[ 'symposium_update' ]) && $_POST[ 'symposium_update' ] == '__wps__plugin_settings' ) {

			update_option(WPS_OPTIONS_PREFIX.'_footer', $_POST[ 'email_footer' ]);
			update_option(WPS_OPTIONS_PREFIX.'_from_email', $_POST[ 'from_email' ]);
			update_option(WPS_OPTIONS_PREFIX.'_jquery', isset($_POST[ 'jquery' ]) ? $_POST[ 'jquery' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'_jqueryui', isset($_POST[ 'jqueryui' ]) ? $_POST[ 'jqueryui' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'_tinymce', isset($_POST[ 'tinymce' ]) ? $_POST[ 'tinymce' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'_jwplayer', isset($_POST[ 'jwplayer' ]) ? $_POST[ 'jwplayer' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'_emoticons', isset($_POST[ 'emoticons' ]) ? $_POST[ 'emoticons' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'_wp_width', str_replace('%', 'pc', ($_POST[ 'wp_width' ])));
			update_option(WPS_OPTIONS_PREFIX.'_wp_alignment', $_POST[ 'wp_alignment' ]);
			update_option(WPS_OPTIONS_PREFIX.'_img_db', isset($_POST[ 'img_db' ]) ? $_POST[ 'img_db' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'_img_path', str_replace("\\\\", "\\", $_POST[ 'img_path' ]));
			update_option(WPS_OPTIONS_PREFIX.'_img_url', $_POST[ 'img_url' ]);
			update_option(WPS_OPTIONS_PREFIX.'_img_crop', isset($_POST[ 'img_crop' ]) ? $_POST[ 'img_crop' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'_show_buttons', isset($_POST[ 'show_buttons' ]) ? $_POST[ 'show_buttons' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'_striptags', isset($_POST[ 'striptags' ]) ? $_POST[ 'striptags' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'_image_ext', strtolower($_POST[ 'image_ext' ]));
			update_option(WPS_OPTIONS_PREFIX.'_video_ext', strtolower($_POST[ 'video_ext' ]));
			update_option(WPS_OPTIONS_PREFIX.'_doc_ext', strtolower($_POST[ 'doc_ext' ]));
			update_option(WPS_OPTIONS_PREFIX.'_elastic', isset($_POST[ 'elastic' ]) ? $_POST[ 'elastic' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'_force_utf8', isset($_POST[ 'force_utf8' ]) ? $_POST[ 'force_utf8' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'_images', $_POST[ 'images' ]);
			update_option(WPS_OPTIONS_PREFIX.'_wps_lite', isset($_POST[ 'wps_lite' ]) ? $_POST[ 'wps_lite' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'_wps_time_out', $_POST[ 'wps_time_out' ] != '' ? $_POST[ 'wps_time_out' ] : 0);
			update_option(WPS_OPTIONS_PREFIX.'_wps_js_file', $_POST[ 'wps_js_file' ]);
			update_option(WPS_OPTIONS_PREFIX.'_wps_css_file', $_POST[ 'wps_css_file' ]);
			update_option(WPS_OPTIONS_PREFIX.'_allow_reports', isset($_POST[ 'allow_reports' ]) ? $_POST[ 'allow_reports' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'_ajax_widgets', isset($_POST[ 'wps_ajax_widgets' ]) ? $_POST[ 'wps_ajax_widgets' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'_jscharts', isset($_POST[ 'jscharts' ]) ? $_POST[ 'jscharts' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'_subject_mail_new', $_POST[ 'subject_mail_new' ]);
			update_option(WPS_OPTIONS_PREFIX.'_subject_forum_new', $_POST[ 'subject_forum_new' ]);
			update_option(WPS_OPTIONS_PREFIX.'_subject_forum_reply', $_POST[ 'subject_forum_reply' ]);
			update_option(WPS_OPTIONS_PREFIX.'_long_menu', isset($_POST[ 'long_menu' ]) ? $_POST[ 'long_menu' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'_debug_mode', isset($_POST[ 'debug_mode' ]) ? $_POST[ 'debug_mode' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'_always_load', isset($_POST[ 'always_load' ]) ? $_POST[ 'always_load' ] : '');			
			update_option(WPS_OPTIONS_PREFIX.'_audit', isset($_POST[ 'audit' ]) ? $_POST[ 'audit' ] : '');			
			update_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_media_manager', isset($_POST[ 'use_wysiwyg_media_manager' ]) ? $_POST[ 'use_wysiwyg_media_manager' ] : '');			
			update_option(WPS_OPTIONS_PREFIX.'_basic_upload', isset($_POST[ 'basic_upload' ]) ? $_POST[ 'basic_upload' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'_wps_login_url', isset($_POST[ 'wps_login_url' ]) ? $_POST[ 'wps_login_url' ] : '');

			echo "<div class='updated slideaway'>";
			
			// Making content path if it doesn't exist
			$img_db = isset($_POST[ 'img_db' ]) ? $_POST[ 'img_db' ] : '';
			if ($img_db != 'on') {
				
				if (!file_exists($_POST[ 'img_path' ])) {
					if (!mkdir($_POST[ 'img_path' ], 0777, true)) {
						echo '<p>Failed to create '.$_POST[ 'img_path' ].'...</p>';
					} else {
						echo '<p>Created '.$_POST[ 'img_path' ].'.</p>';
					}
				}
			
			}
			
			// Put an settings updated message on the screen
			echo "<p>".__('Saved', WPS_TEXT_DOMAIN).".</p></div>";
			
		}

		$readonly = (get_option(WPS_OPTIONS_PREFIX.'_activation_code') == 'vip') ? true : false;
		
		?>
									
		<form method="post" action=""> 
			<input type="hidden" name="symposium_update" value="__wps__plugin_settings">

			<table class="form-table __wps__admin_table"> 

			<?php if ($readonly) { ?>
				<!-- Values that can't be edited when running with readonly flag -->
				<input type="hidden" name="wps_login_url" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_wps_login_url'); ?>" />			
				<input type="hidden" name="wps_time_out" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_wps_time_out'); ?>" />			
				<input type="hidden" name="wps_js_file" value="wps.min.js" />
				<input type="hidden" name="wps_css_file" value="wps.min.css" />
				<input type="hidden" name="wps_ajax_widgets" value="" />
				<input type="hidden" name="wps_lite" value="" />
				<input type="hidden" name="img_db" value="" />
				<input name="img_path" type="hidden" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_img_path'); ?>" /> 
				<input name="img_url" type="hidden" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_img_url'); ?>" /> 
				<input name="images" type="hidden" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_images'); ?>" /> 
				<input name="img_crop" type="hidden" value="on" /> 
				<input name="image_ext" type="hidden" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_image_ext'); ?>" /> 
				<input name="video_ext" type="hidden" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_video_ext'); ?>" /> 
				<input name="doc_ext" type="hidden" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_doc_ext'); ?>" /> 
				<input name="always_load" type="hidden" value="" /> 
				<input name="long_menu" type="hidden" value="on" /> 				
				<input name="jquery" type="hidden" value="on" /> 				
				<input name="jqueryui" type="hidden" value="on" /> 				
				<input name="tinymce" type="hidden" value="on" /> 				
				<input name="jscharts" type="hidden" value="on" /> 				
				<input name="emoticons" type="hidden" value="on" /> 				
				<input name="elastic" type="hidden" value="on" /> 				
				<input name="force_utf8" type="hidden" value="" /> 				
				<input name="audit" type="hidden" value="" /> 				
				<input name="debug_mode" type="hidden" value="" /> 
				
			<?php } ?>
			

			<?php if (!$readonly) { ?>
				<tr valign="top"> 
				<td scope="row"><label for="wps_time_out"><?php echo __('Script time out', WPS_TEXT_DOMAIN); ?></label></td>
				<td><input name="wps_time_out" type="text" id="wps_time_out" style="width:50px" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_wps_time_out'); ?>"/> 
				<span class="description"><?php echo __('Maximum PHP script time out value, set to 0 to disable this setting.', WPS_TEXT_DOMAIN); ?></span></td> 
				</tr> 
			<?php } ?>

			<?php if (!$readonly) { ?>
				<tr valign="top">
				<td scope="row"><label for="wps_js_file"><?php echo sprintf(__('%s JS files', WPS_TEXT_DOMAIN), WPS_WL_SHORT); ?></label></td> 
				<td>
				<select name="wps_js_file">
					<option value='wps.min.js'<?php if (get_option(WPS_OPTIONS_PREFIX.'_wps_js_file') == 'wps.min.js') { echo ' SELECTED'; } ?>><?php echo __('Minimized', WPS_TEXT_DOMAIN); ?></option>
					<option value='wps.js'<?php if (get_option(WPS_OPTIONS_PREFIX.'_wps_js_file') == 'wps.js') { echo ' SELECTED'; } ?>><?php echo __('Normal', WPS_TEXT_DOMAIN); ?></option>
				</select> 
				<span class="description"><?php echo __('Minimized loads faster, normal is useful for debugging', WPS_TEXT_DOMAIN); ?></span></td> 
				</tr> 		
				
				<tr valign="top">
				<td scope="row"><label for="wps_css_file"><?php echo sprintf(__('%s CSS files', WPS_TEXT_DOMAIN), WPS_WL_SHORT); ?></label></td> 
				<td>
				<select name="wps_css_file">
					<option value='wps.min.css'<?php if (get_option(WPS_OPTIONS_PREFIX.'_wps_css_file') == 'wps.min.css') { echo ' SELECTED'; } ?>><?php echo __('Minimized', WPS_TEXT_DOMAIN); ?></option>
					<option value='wps.css'<?php if (get_option(WPS_OPTIONS_PREFIX.'_wps_css_file') == 'wps.css') { echo ' SELECTED'; } ?>><?php echo __('Normal', WPS_TEXT_DOMAIN); ?></option>
				</select> 
				<span class="description"><?php echo __('Minimized loads faster, normal is useful for debugging', WPS_TEXT_DOMAIN); ?></span></td> 
				</tr> 		
			<?php } ?>
				

			<?php if (!$readonly) { ?>
				<tr valign="top"> 
				<td scope="row" style="width:150px;"><label for="wps_ajax_widgets"><?php echo __('Widgets AJAX mode', WPS_TEXT_DOMAIN); ?></label></td>
				<td>
				<input type="checkbox" name="wps_ajax_widgets" id="wps_ajax_widgets" <?php if (get_option(WPS_OPTIONS_PREFIX.'_ajax_widgets') == "on") { echo "CHECKED"; } ?>/>
				<span class="description"><?php echo sprintf(__("Use AJAX to load %s widgets (or load with page).", WPS_TEXT_DOMAIN), WPS_WL_SHORT); ?></span></td> 
				</tr> 
	
				<tr valign="top"> 
				<td scope="row" style="width:150px;"><label for="wps_lite"><?php echo __('Enable LITE mode', WPS_TEXT_DOMAIN); ?></label></td>
				<td>
				<input type="checkbox" name="wps_lite" id="wps_lite" <?php if (get_option(WPS_OPTIONS_PREFIX.'_wps_lite') == "on") { echo "CHECKED"; } ?>/>
				<span class="description"><?php echo __("Recommended for shared hosting, or where server load is an issue.", WPS_TEXT_DOMAIN); ?></span></td> 
				</tr> 
	
				<?php if (get_option(WPS_OPTIONS_PREFIX.'_wps_lite') == "on") { ?>
					
					<tr valign="top"></tr> 
					<td></td><td style="border:1px dotted #999; background-color: #fff;">
						<strong><?php echo sprintf(__('%s LITE mode', WPS_TEXT_DOMAIN), WPS_WL); ?></strong>
						<p>
						<?php echo sprintf(__('You are running %s in LITE mode, which reduces server load, but disables/reduces certain features of the %s plugins, and will take priority over any other settings you have made.', WPS_TEXT_DOMAIN), WPS_WL, WPS_WL).' '; ?>
						<?php echo __('If you activate additional plugins, return to this page to see an updated list below.', WPS_TEXT_DOMAIN); ?>
						</p>
	
						<p><?php echo __('To improve performance further, it is recommended that you:', WPS_TEXT_DOMAIN); ?></p>
						<ul style="list-style-type: circle; margin: 10px 0 20px 30px;">
							<li><?php echo sprintf(__('minimize the total number of all WordPress plugins and widgets used (%s and others). <a href="plugins.php?plugin_status=active">De-activate as many as possible!</a>', WPS_TEXT_DOMAIN), WPS_WL); ?></li>
							<?php if (function_exists('__wps__add_notification_bar')) { ?>
								<li><?php echo __('<a href="plugins.php?plugin_status=active">De-activate Panel</a> or <a href="admin.php?page=symposium_bar">set the polling intervals</a> high, eg: at least 300 and 20 seconds.', WPS_TEXT_DOMAIN); ?></li>
							<?php } ?>
							<?php if (function_exists('__wps__news_main')) { ?>
								<li><?php echo __('<a href="plugins.php?plugin_status=active">De-activate Alerts</a> or <a href="admin.php?page='.WPS_DIR.'/news_admin.php">set the polling interval</a> high, eg: at least 120 seconds.', WPS_TEXT_DOMAIN); ?></li>
							<?php } ?>
						</ul>
						
						<?php if (function_exists('__wps__add_notification_bar')) { ?>
							<p><strong><?php echo __('Panel', WPS_TEXT_DOMAIN); ?></strong></p>
							<ul style="list-style-type: circle; margin: 10px 0 10px 30px;">
								<li><?php echo __('Chat windows and the chatroom are disabled', WPS_TEXT_DOMAIN); ?></li>
								<li><?php echo __('Notification of new mail (etc) requires a page reload', WPS_TEXT_DOMAIN); ?></li>
							</ul>
						<?php } ?>
						
						<?php if (function_exists('__wps__news_main')) { ?>
						<p><strong><?php echo __('Alerts', WPS_TEXT_DOMAIN); ?></strong></p>
						<ul style="list-style-type: circle; margin: 10px 0 10px 30px;">
							<li><?php echo __('Live notification of new messages disabled (page reload required)', WPS_TEXT_DOMAIN); ?></li>
						</ul>
						<?php } ?>
						
						<p><strong><?php echo __('Forum', WPS_TEXT_DOMAIN); ?></strong></p>
						<ul style="list-style-type: circle; margin: 10px 0 10px 30px;">
							<li><?php echo __('Topic, post and reply counts are not displayed', WPS_TEXT_DOMAIN); ?></li>
							<li><?php echo __('Only new topics are shown, not latest replies', WPS_TEXT_DOMAIN); ?></li>
							<li><?php echo __('Answered topics not shown in topics list', WPS_TEXT_DOMAIN); ?></li>
							<li><?php echo __('Simplified breadcrumbs (forum navigation links)', WPS_TEXT_DOMAIN); ?></li>
							<li><?php echo __('Smilies/emoticons not replaced with images', WPS_TEXT_DOMAIN); ?></li>
							<li><?php echo __('User @tagging will not work', WPS_TEXT_DOMAIN); ?></li>
						</ul>
						
						<p><strong><?php echo __('Member Directory', WPS_TEXT_DOMAIN); ?></strong></p>
						<ul style="list-style-type: circle; margin: 10px 0 10px 30px;">
							<li><?php echo __('Latest activity post not shown', WPS_TEXT_DOMAIN); ?></li>
							<li><?php echo __('Add as a friend/Send Mail buttons disabled', WPS_TEXT_DOMAIN); ?></li>
						</ul>
						
						<p><strong><?php echo __('Profile', WPS_TEXT_DOMAIN); ?></strong></p>
						<ul style="list-style-type: circle; margin: 10px 0 10px 30px;">
							<li><?php echo __('Friends: Latest activity post not shown', WPS_TEXT_DOMAIN); ?></li>
							<li><?php echo __('Friends: New friendships made are not shown', WPS_TEXT_DOMAIN); ?></li>
							<li><?php echo __('Forum: Posts/replies are not shown on', WPS_TEXT_DOMAIN); ?></li>
						</ul>
						
					</td>
					</tr> 	
				
				<?php } ?>
									
				<tr valign="top"> 
				<td scope="row"><label for="img_db"><?php echo __('Store uploads in database', WPS_TEXT_DOMAIN); ?></label></td>
				<td>
				<input type="checkbox" name="img_db" id="img_db" <?php if (get_option(WPS_OPTIONS_PREFIX.'_img_db') == "on") { echo "CHECKED"; } ?>/>
				<span class="description"><?php echo __('Off by default to save to the file system (recommended). Select to upload to database', WPS_TEXT_DOMAIN).' - '; ?><span style='font-weight:bold; text-decoration: underline'><?php echo __("if you change, images will have to be reloaded, they remain in their storage 'state'.", WPS_TEXT_DOMAIN); ?></span></span></td> 
				</tr> 
				
				<?php if (get_option(WPS_OPTIONS_PREFIX.'_img_db') != "on") { ?>
					
					<tr valign="top" style='border-top: 1px dashed #666; border-right: 1px dashed #666; border-left: 1px dashed #666; '> 
					<td class="highlighted_row" scope="row"><label for="img_path"><?php echo __('Images directory', WPS_TEXT_DOMAIN); ?></label></td> 
					<td class="highlighted_row"><input name="img_path" type="text" id="img_path"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_img_path'); ?>" class="regular-text" /> 
					<span class="description">
					<?php echo __('Image upload directory, eg:', WPS_TEXT_DOMAIN).' '.WP_CONTENT_DIR.'/wps-content'; ?>
					<input type="button" onclick="document.getElementById('img_path').value='<?php echo WP_CONTENT_DIR.'/wps-content'; ?>'" value="<?php _e('Suggest', WPS_TEXT_DOMAIN); ?>" class="button" /></td> 
					</tr> 					
					
					<tr valign="top" style='border-right: 1px dashed #666; border-left: 1px dashed #666; '> 
					<td class="highlighted_row" scope="row"><label for="img_url"><?php echo __('Images URL', WPS_TEXT_DOMAIN); ?></label></td> 
					<td class="highlighted_row"><input name="img_url" type="text" id="img_url"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_img_url'); ?>" class="regular-text" /> 
					<?php $url = WP_CONTENT_URL.'/wps-content'; $url = str_replace(__wps__siteURL(), '', $url); ?>
					<span class="description"><?php echo __('URL to the images directory, Do not include http:// or your domain name eg: ', WPS_TEXT_DOMAIN).' <a href="'.$url.'">'.$url.'</a>'; ?>
					<input type="button" onclick="document.getElementById('img_url').value='<?php echo $url; ?>'" value="<?php _e('Suggest', WPS_TEXT_DOMAIN); ?>" class="button" /></td> 
					</tr> 					
	
					<tr valign="top" style='border-right: 1px dashed #666; border-bottom: 1px dashed #666; border-left: 1px dashed #666; '> 
					<td class="highlighted_row" colspan=2>
						<?php $img_tmp = ini_get('upload_tmp_dir'); ?>
						<?php echo __('For information, from PHP.INI on your server, the PHP temporary upload folder is:', WPS_TEXT_DOMAIN).' '.$img_tmp; ?>
						<?php if ($img_tmp == '') { echo '<strong>'.__("You need to <a href='http://uk.php.net/manual/en/ini.core.php#ini.upload-tmp-dir'>set this in your php.ini</a> file", WPS_TEXT_DOMAIN).'</strong>'; } ?>
					</td>
					</tr> 	
	
				<?php } else { ?>
	
					<input name="img_path" type="hidden" id="img_path"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_img_path'); ?>" /> 
					<input name="img_url" type="hidden" id="img_url"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_img_url'); ?>" /> 
					
				<?php } ?>
	
				<tr valign="top"> 
				<td scope="row"><label for="images"><?php echo sprintf(__('%s images URL', WPS_TEXT_DOMAIN), WPS_WL_SHORT); ?></label></td> 
				<td><input name="images" type="text" id="images" class="regular-text" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_images'); ?>"/> 
				<span class="description"><?php echo __('Change if you want to create your own set of custom images.', WPS_TEXT_DOMAIN); ?></span>
				<input type="button" onclick="document.getElementById('images').value='<?php echo str_replace(__wps__siteURL(), '', WPS_PLUGIN_URL.'/images'); ?>'" value="<?php _e('Suggest', WPS_TEXT_DOMAIN); ?>" class="button" /></td> 
				</tr> 
					
				<tr valign="top"> 
				<td scope="row"><label for="img_crop"><?php echo __('Crop avatar images', WPS_TEXT_DOMAIN); ?></label></td>
				<td>
				<input type="checkbox" name="img_crop" id="img_crop" <?php if (get_option(WPS_OPTIONS_PREFIX.'_img_crop') == "on") { echo "CHECKED"; } ?>/>
				<span class="description"><?php echo __("Allow uploaded images to be cropped</span>", WPS_TEXT_DOMAIN); ?></span></td> 
				</tr> 
	
				<tr valign="top"> 
				<td scope="row"><label for="image_ext"><?php echo __('Image extensions', WPS_TEXT_DOMAIN); ?></label></td> 
				<td><input name="image_ext" type="text" id="image_ext" class="regular-text" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_image_ext'); ?>"/> 
				<span class="description"><?php echo __('A comma separated list of permitted file extensions, leave blank for none. *.jpg,*.jpeg,*.png and *.gif supported.', WPS_TEXT_DOMAIN); ?></span></td> 
				</tr> 
	
				<?php if (get_option(WPS_OPTIONS_PREFIX.'_img_db') != "on") { ?>
	
					<tr valign="top"> 
					<td scope="row"><label for="video_ext"><?php echo __('Video extensions', WPS_TEXT_DOMAIN); ?></label></td> 
					<td><input name="video_ext" type="text" id="video_ext" class="regular-text" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_video_ext'); ?>"/> 
					<span class="description"><?php echo sprintf(__('A comma separated list of permitted file extensions, leave blank for none. H.264 format supported, <a %s>see here</a>.', WPS_TEXT_DOMAIN), 'href="http://www.longtailvideo.com/support/jw-player/jw-player-for-flash-v5/12539/supported-video-and-audio-formats" target="_blank"'); ?></span></td> 
					</tr> 
	
					<tr valign="top"> 
					<td scope="row"><label for="doc_ext"><?php echo __('Document extensions', WPS_TEXT_DOMAIN); ?></label></td> 
					<td><input name="doc_ext" type="text" id="doc_ext" class="regular-text" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_doc_ext'); ?>"/> 
					<span class="description"><?php echo __('A comma separated list of permitted file extensions, leave blank for none. Viewed in separate window or downloaded.', WPS_TEXT_DOMAIN); ?></span></td> 
					</tr> 
					
				<?php } else { ?>
	
					<tr valign="top"> 
					<td scope="row"><label for="video_ext"><?php echo __('Video extensions', WPS_TEXT_DOMAIN); ?></label></td> 
					<td><input name="video_ext" type="hidden" id="video_ext" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_video_ext'); ?>"/> 
					<span class="description"><?php echo __('Sorry, videos can only be saved when storing to the filesystem.', WPS_TEXT_DOMAIN); ?></span></td> 
					</tr> 
	
					<tr valign="top"> 
					<td scope="row"><label for="doc_ext"><?php echo __('Document extensions', WPS_TEXT_DOMAIN); ?></label></td> 
					<td><input name="doc_ext" type="hidden" id="doc_ext" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_doc_ext'); ?>"/> 
					<span class="description"><?php echo __('Sorry, documents can only be saved when storing to the filesystem.', WPS_TEXT_DOMAIN); ?></span></td> 
					</tr> 
	
				<?php } ?>

			<?php } ?>
			
			<tr valign="top"> 
			<td scope="row"><label for="email_footer"><?php echo __('Email Notifications', WPS_TEXT_DOMAIN); ?></label></td> 
			<td><input name="email_footer" type="text" id="email_footer"  value="<?php echo stripslashes(get_option(WPS_OPTIONS_PREFIX.'_footer')); ?>" class="regular-text" /> 
			<span class="description"><?php echo __('Footer appended to notification emails', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 

			<tr valign="top"> 
			<td scope="row"><label for="from_email">&nbsp;</label></td> 
			<td><input name="from_email" type="text" id="from_email"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_from_email'); ?>" class="regular-text" /> 
			<span class="description"><?php echo __('Email address used for email notifications', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 
										
			<tr valign="top"> 
			<td scope="row"><label for="subject_mail_new"><?php echo __('Mail subject lines', WPS_TEXT_DOMAIN); ?></label></td> 
			<td><input name="subject_mail_new" type="text" id="subject_mail_new"  value="<?php echo stripslashes(get_option(WPS_OPTIONS_PREFIX.'_subject_mail_new')); ?>" class="regular-text" /> 
			<span class="description"><?php echo __('New Mail Message, [subject] will be replaced by the message subject', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 

			<tr valign="top"> 
			<td scope="row"><label for="subject_forum_new">&nbsp;</label></td> 
			<td><input name="subject_forum_new" type="text" id="subject_forum_new"  value="<?php echo stripslashes(get_option(WPS_OPTIONS_PREFIX.'_subject_forum_new')); ?>" class="regular-text" /> 
			<span class="description"><?php echo __('New Forum Topic, [topic] will be replaced by the topic subject', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 

			<tr valign="top"> 
			<td scope="row"><label for="subject_forum_reply">&nbsp;</label></td> 
			<td><input name="subject_forum_reply" type="text" id="subject_forum_reply"  value="<?php echo stripslashes(get_option(WPS_OPTIONS_PREFIX.'_subject_forum_reply')); ?>" class="regular-text" /> 
			<span class="description"><?php echo __('New Forum Reply, [topic] will be replaced by the topic subject', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 

			<tr valign="top"> 
			<td scope="row"><label for="wp_width"><?php echo __('Width', WPS_TEXT_DOMAIN); ?></label></td>
			<td><input name="wp_width" type="text" id="wp_width" style="width:50px" value="<?php echo str_replace('pc', '%', get_option(WPS_OPTIONS_PREFIX.'_wp_width')); ?>"/> 
			<span class="description"><?php echo sprintf(__('Width of all %s plugins, eg: 600px or 100%%', WPS_TEXT_DOMAIN), WPS_WL); ?></span></td> 
			</tr> 

			<tr valign="top">
			<td scope="row"><label for="wp_alignment"><?php echo __('Alignment', WPS_TEXT_DOMAIN); ?></label></td> 
			<td>
			<select name="wp_alignment">
				<option value='Left'<?php if (get_option(WPS_OPTIONS_PREFIX.'_wp_alignment') == 'Left') { echo ' SELECTED'; } ?>><?php echo __('Left', WPS_TEXT_DOMAIN); ?></option>
				<option value='Center'<?php if (get_option(WPS_OPTIONS_PREFIX.'_wp_alignment') == 'Center') { echo ' SELECTED'; } ?>><?php echo __('Center', WPS_TEXT_DOMAIN); ?></option>
				<option value='Right'<?php if (get_option(WPS_OPTIONS_PREFIX.'_wp_alignment') == 'Right') { echo ' SELECTED'; } ?>><?php echo __('Right', WPS_TEXT_DOMAIN); ?></option>
			</select> 
			<span class="description"><?php echo sprintf(__('Alignment of all %s plugins', WPS_TEXT_DOMAIN), WPS_WL); ?></span></td> 
			</tr> 		

			<tr valign="top"> 
			<td scope="row"><label for="show_buttons"><?php echo __('Buttons on Activity pages', WPS_TEXT_DOMAIN); ?></label></td>
			<td>
			<input type="checkbox" name="show_buttons" id="show_buttons" <?php if (get_option(WPS_OPTIONS_PREFIX.'_show_buttons') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __("Pressing return submits a post/comment, select this option to also show submit buttons.</span>", WPS_TEXT_DOMAIN); ?></span></td> 
			</tr>
			<?php 
			if (get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg') == 'on') {
				?>
				<tr valign="top"> 
				<td scope="row"><label for="use_wysiwyg_media_manager"><?php echo __('Use media manager', WPS_TEXT_DOMAIN); ?></label></td>
				<td>
				<input type="checkbox" name="use_wysiwyg_media_manager" id="use_wysiwyg_media_manager" <?php if (get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_media_manager') == "on") { echo "CHECKED"; } ?>/>
				<span class="description"><?php echo __("Will switch from entering an image URL (when inserting an image) to a list of images in the WordPress media manager.</span>", WPS_TEXT_DOMAIN); ?></span></td> 
				</tr>
			<?php } ?>

			<tr valign="top"> 
			<td scope="row"><label for="striptags"><?php echo __('Strip tags', WPS_TEXT_DOMAIN); ?></label></td>
			<td>
			<input type="checkbox" name="striptags" id="striptags" <?php if (get_option(WPS_OPTIONS_PREFIX.'_striptags') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php 
			echo __("Completely remove HTML/script tags. If unchecked &lt; and &gt; will be replaced with &amp;lt; and &amp;gt;.", WPS_TEXT_DOMAIN); 
			echo "<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".__("NB. The Bronze member WYSIWYG editor, if in use, will display tags, whatever you set here, but not interpret them.", WPS_TEXT_DOMAIN); 
			?></span></td> 
			</tr>
								
			<tr valign="top"> 
			<td scope="row"><label for="allow_reports"><?php echo __('Allow reports', WPS_TEXT_DOMAIN); ?></label></td>
			<td>
			<input type="checkbox" name="allow_reports" id="allow_reports" <?php if (get_option(WPS_OPTIONS_PREFIX.'_allow_reports') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __("Shows a warning symbol to report content to the site administrator.", WPS_TEXT_DOMAIN); ?></span></td> 
			</tr>

			<tr valign="top"> 
			<td scope="row"><label for="basic_upload"><?php echo __('Basic File Upload', WPS_TEXT_DOMAIN); ?></label></td>
			<td>
			<input type="checkbox" name="basic_upload" id="basic_upload" <?php if (get_option(WPS_OPTIONS_PREFIX.'_basic_upload') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __("Use basic HTML file upload to avoid clashes with themes and plugins.", WPS_TEXT_DOMAIN); ?><br /><?php echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . __("Cannot crop avatars, and some features of upload reduced.", WPS_TEXT_DOMAIN); ?><br /><?php echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . __("Can only be used when storing images on the filesystem.", WPS_TEXT_DOMAIN); ?></span></td> 
			</tr>
			
			<?php if (!$readonly) { ?>

				<tr valign="top"> 
				<td scope="row"><label for="wps_login_url"><?php echo __('Login Page URL', WPS_TEXT_DOMAIN); ?></label></td> 
				<td><input name="wps_login_url" type="text" id="wps_login_url"  value="<?php echo stripslashes(get_option(WPS_OPTIONS_PREFIX.'_wps_login_url')); ?>" class="regular-text" /> 
				<span class="description"><?php echo __('Override link to login page. [url] is substituted with current page URL.', WPS_TEXT_DOMAIN); ?></span></td> 
				</tr> 

				<tr valign="top"> 
				<td scope="row"><label for="always_load"><?php echo sprintf(__('Load %s on every page', WPS_TEXT_DOMAIN), WPS_WL_SHORT); ?></label></td>
				<td>
				<input type="checkbox" name="always_load" id="always_load" <?php if (get_option(WPS_OPTIONS_PREFIX.'_always_load') == "on") { echo "CHECKED"; } ?>/>
				<span class="description"><?php echo sprintf(__("Will always load %s components, or attempt a check if required.", WPS_TEXT_DOMAIN), WPS_WL); ?></span></td> 
				</tr>

				<tr valign="top"> 
				<td scope="row"><label for="long_menu"><?php echo __('Admin Tabs', WPS_TEXT_DOMAIN); ?></label></td>
				<td>
				<input type="checkbox" name="long_menu" id="symposium_long_menu" <?php if (get_option(WPS_OPTIONS_PREFIX.'_long_menu') == "on") { echo "CHECKED"; } ?>/>
				<span class="description"><?php echo sprintf(__("Reduce %s admin menu items and show as tabs.", WPS_TEXT_DOMAIN), WPS_WL); ?></span></td> 
				</tr>
			<?php } ?>				

			<?php				
			// Hook to add items to the plugin settings page, just above debug options
			do_action ( '__wps__plugin_settings_mail_title_hook' );
			?>					
			
			<?php				
			// Hook to add items to the plugin settings page
			echo apply_filters( '__wps__plugin_settings_hook', "" );

			if (!$readonly) { ?>
			
				<tr valign="top"> 
				<td colspan="2"><?php echo '<h2>'.__('Troubleshooting', WPS_TEXT_DOMAIN).'</h2>'; echo __('The following may solve clashes with other WordPress plugins, etc.', WPS_TEXT_DOMAIN); ?>:</td>
				</tr> 
	
				<tr valign="top"> 
				<td scope="row"><label for="jquery"><?php echo __('Load jQuery', WPS_TEXT_DOMAIN); ?></label></td>
				<td>
				<input type="checkbox" name="jquery" id="jquery" <?php if (get_option(WPS_OPTIONS_PREFIX.'_jquery') == "on") { echo "CHECKED"; } ?>/>
				<span class="description"><?php echo __('Load jQuery on non-admin pages, disable if causing problems', WPS_TEXT_DOMAIN); ?></span></td> 
				</tr> 
	
				<tr valign="top"> 
				<td scope="row"><label for="jqueryui"><?php echo __('Load jQuery UI', WPS_TEXT_DOMAIN); ?></label></td>
				<td>
				<input type="checkbox" name="jqueryui" id="jqueryui" <?php if (get_option(WPS_OPTIONS_PREFIX.'_jqueryui') == "on") { echo "CHECKED"; } ?>/>
				<span class="description"><?php echo __('Load jQuery UI on non-admin pages, disable if causing problems', WPS_TEXT_DOMAIN); ?></span></td> 
				</tr> 

				<tr valign="top"> 
				<td scope="row"><label for="tinymce"><?php echo __('Do NOT TinyMCE', WPS_TEXT_DOMAIN); ?></label></td>
				<td>
				<input type="checkbox" name="tinymce" id="tinymce" <?php if (get_option(WPS_OPTIONS_PREFIX.'_tinymce') == "on") { echo "CHECKED"; } ?>/>
				<span class="description"><?php echo __('Do NOT load TinyMCE on non-admin pages, check if causing problems', WPS_TEXT_DOMAIN); ?></span></td> 
				</tr> 

				<tr valign="top"> 
				<td scope="row"><label for="jscharts"><?php echo __('Load JScharts/Jcrop', WPS_TEXT_DOMAIN); ?></label></td>
				<td>
				<input type="checkbox" name="jscharts" id="jscharts" <?php if (get_option(WPS_OPTIONS_PREFIX.'_jscharts') == "on") { echo "CHECKED"; } ?>/>
				<span class="description"><?php echo __('Load JSCharts and Jcrop on non-admin pages, disable if causing problems', WPS_TEXT_DOMAIN); ?></span></td> 
				</tr>					
			
				<tr valign="top"> 
				<td scope="row"><label for="jwplayer"><?php echo __('Load JW Player', WPS_TEXT_DOMAIN); ?></label></td>
				<td>
				<input type="checkbox" name="jwplayer" id="jwplayer" <?php if (get_option(WPS_OPTIONS_PREFIX.'_jwplayer') == "on") { echo "CHECKED"; } ?>/>
				<span class="description"><?php echo __('Load JW Player for forum video uploads, disable if not required', WPS_TEXT_DOMAIN); ?></span></td> 
				</tr> 
			
				<tr valign="top"> 
				<td scope="row"><label for="emoticons"><?php echo __('Smilies/Emoticons', WPS_TEXT_DOMAIN); ?></label></td>
				<td>
				<input type="checkbox" name="emoticons" id="emoticons" <?php if (get_option(WPS_OPTIONS_PREFIX.'_emoticons') == "on") { echo "CHECKED"; } ?>/>
				<span class="description"><?php echo __('Automatically replace smilies/emoticons with graphical images', WPS_TEXT_DOMAIN); ?></span></td> 
				</tr> 		
														
				<tr valign="top"> 
				<td scope="row"><label for="elastic"><?php echo __('Elastic Textboxes', WPS_TEXT_DOMAIN); ?></label></td>
				<td>
				<input type="checkbox" name="elastic" id="elastic" <?php if (get_option(WPS_OPTIONS_PREFIX.'_elastic') == "on") { echo "CHECKED"; } ?>/>
				<span class="description"><?php echo __('Include jQuery elastic function (automatically expand textboxes)', WPS_TEXT_DOMAIN); ?></span></td> 
				</tr> 		
	
				<tr valign="top"> 
				<td scope="row"><label for="force_utf8"><?php echo __('Force UTF8 decoding', WPS_TEXT_DOMAIN); ?></label></td>
				<td>
				<input type="checkbox" name="force_utf8" id="force_utf8" <?php if (get_option(WPS_OPTIONS_PREFIX.'_force_utf8') == "on") { echo "CHECKED"; } ?>/>
				<span class="description"><?php echo __('May solve accented characters not displaying properly', WPS_TEXT_DOMAIN); ?></span></td> 
				</tr> 		
	
				<tr valign="top"> 
				<td colspan="2"><h2><?php echo __('Developers only', WPS_TEXT_DOMAIN); ?></h2></td>
				</tr> 
	
				<tr valign="top"> 
				<td scope="row"><label for="audit"><?php echo __('Audit', WPS_TEXT_DOMAIN); ?></label></td>
				<td>
				<input type="checkbox" name="audit" id="symposium_audit" <?php if (get_option(WPS_OPTIONS_PREFIX.'_audit') == "on") { echo "CHECKED"; } ?>/>
				<?php if (get_option(WPS_OPTIONS_PREFIX.'_audit') == "on") { ?>
					<span class="description"><?php echo sprintf(__("Switch on auditing of key events (<a href='%s'>analyse</a>).", WPS_TEXT_DOMAIN), esc_url( admin_url('admin.php?page=symposium_audit') )); ?></span></td> 
				<?php } else { ?>
					<span class="description"><?php echo sprintf(__("Switch on auditing of key events (results then available via %s->Manage->Audit).", WPS_TEXT_DOMAIN), WPS_WL); ?></span></td>
				<?php } ?>
				</tr>
	
				<tr valign="top"> 
				<td scope="row"><label for="debug_mode"><?php echo __('Debug mode', WPS_TEXT_DOMAIN); ?></label></td>
				<td>
				<input type="checkbox" name="debug_mode" id="debug_mode" <?php if (get_option(WPS_OPTIONS_PREFIX.'_debug_mode') == "on") { echo "CHECKED"; } ?>/>
				<span class="description"><?php echo sprintf(__("Display additional %s information on-screen and in dialog boxes.", WPS_TEXT_DOMAIN), WPS_WL); ?></span></td> 
				</tr>
				
			<?php } ?>			
						
			</table>
			 
			<p class="submit" style="margin-left:6px"> 
			<input type="submit" name="Submit" class="button-primary" value="<?php echo __('Save Changes', WPS_TEXT_DOMAIN); ?>" /> 
			</p> 
			
			<?php
		
		echo '</form>';
		
		__wps__show_manage_tabs_header_end();

	echo '</div>';					  
}

function __wps__plugin_forum() {

  	echo '<div class="wrap">';
  	echo '<div id="icon-themes" class="icon32"><br /></div>';
  	echo '<h2>'.sprintf(__('%s Options', WPS_TEXT_DOMAIN), WPS_WL).'</h2><br />';

	__wps__show_tabs_header('forum');

	global $wpdb;

		// See if the user has posted forum settings
		if( isset($_POST[ 'symposium_update' ]) && $_POST[ 'symposium_update' ] == '__wps__plugin_forum' ) {

			update_option(WPS_OPTIONS_PREFIX.'_send_summary', isset($_POST[ 'send_summary' ]) ? $_POST[ 'send_summary' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'_include_admin', isset($_POST[ 'include_admin' ]) ? $_POST[ 'include_admin' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'_oldest_first', isset($_POST[ 'oldest_first' ]) ? $_POST[ 'oldest_first' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'_use_votes', isset($_POST[ 'use_votes' ]) ? $_POST[ 'use_votes' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'_use_votes_remove', $_POST[ 'use_votes_remove' ] != '' ? $_POST[ 'use_votes_remove' ] : 0);
			update_option(WPS_OPTIONS_PREFIX.'_use_votes_min', $_POST[ 'use_votes_min' ] != '' ? $_POST[ 'use_votes_min' ] : 10);
			update_option(WPS_OPTIONS_PREFIX.'_preview1', $_POST[ 'preview1' ] != '' ? $_POST[ 'preview1' ] : 0);
			update_option(WPS_OPTIONS_PREFIX.'_preview2', $_POST[ 'preview2' ] != '' ? $_POST[ 'preview2' ] : 100);
			update_option(WPS_OPTIONS_PREFIX.'_chatroom_banned', $_POST[ 'chatroom_banned' ]);
			update_option(WPS_OPTIONS_PREFIX.'_closed_word', $_POST[ 'closed_word' ]);
			update_option(WPS_OPTIONS_PREFIX.'_bump_topics', isset($_POST[ 'bump_topics' ]) ? $_POST[ 'bump_topics' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'_forum_ajax', isset($_POST[ 'forum_ajax' ]) ? $_POST[ 'forum_ajax' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'_forum_login', isset($_POST[ 'forum_login' ]) ? $_POST[ 'forum_login' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'_moderation', isset($_POST[ 'moderation' ]) ? $_POST[ 'moderation' ] : '');
			$sharing_permalink = (isset($_POST[ 'sharing_permalink' ])) ? "pl;" : ""; 
			$sharing_facebook = (isset($_POST[ 'sharing_facebook' ])) ? "fb;" : ""; 
			$sharing_twitter = (isset($_POST[ 'sharing_twitter' ])) ? "tw;" : ""; 
			$sharing_myspace = (isset($_POST[ 'sharing_myspace' ])) ? "ms;" : ""; 
			$sharing_bebo = (isset($_POST[ 'sharing_bebo' ])) ? "be;" : ""; 
			$sharing_linkedin = (isset($_POST[ 'sharing_linkedin' ])) ? "li;" : ""; 
			$sharing_email = (isset($_POST[ 'sharing_email' ])) ? "em;" : ""; 
			$sharing = $sharing_permalink.$sharing_facebook.$sharing_twitter.$sharing_myspace.$sharing_bebo.$sharing_linkedin.$sharing_email;
			update_option(WPS_OPTIONS_PREFIX.'_sharing', $sharing);
			$forum_ranks = (isset($_POST[ 'forum_ranks' ])) ? $_POST[ 'forum_ranks' ].';' : '';
			for ( $rank = 1; $rank <= 11; $rank ++) {
				$forum_ranks .= $_POST['rank'.$rank].";";
				$forum_ranks .= $_POST['score'.$rank].";";
			}
			update_option(WPS_OPTIONS_PREFIX.'_forum_ranks', $forum_ranks);
			update_option(WPS_OPTIONS_PREFIX.'_symposium_forumlatestposts_count', $_POST[ 'symposium_forumlatestposts_count' ] != '' ? $_POST[ 'symposium_forumlatestposts_count' ] : 10);
			update_option(WPS_OPTIONS_PREFIX.'_forum_uploads', isset($_POST[ 'forum_uploads' ]) ? $_POST[ 'forum_uploads' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'_forum_thumbs', isset($_POST[ 'forum_thumbs' ]) ? $_POST[ 'forum_thumbs' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'_forum_thumbs_size', $_POST[ 'forum_thumbs_size' ]);
			update_option(WPS_OPTIONS_PREFIX.'_forum_login_form', isset($_POST[ 'forum_login_form' ]) ? $_POST[ 'forum_login_form' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'_forum_info', isset($_POST[ 'forum_info' ]) ? $_POST[ 'forum_info' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'_forum_stars', isset($_POST[ 'forum_stars' ]) ? $_POST[ 'forum_stars' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'_forum_refresh', isset($_POST[ 'forum_refresh' ]) ? $_POST[ 'forum_refresh' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'_use_answers', isset($_POST[ 'use_answers' ]) ? $_POST[ 'use_answers' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'_wps_default_forum', $_POST[ 'wps_default_forum' ]);
			update_option(WPS_OPTIONS_PREFIX.'_use_bbcode', isset($_POST[ 'use_bbcode' ]) ? $_POST[ 'use_bbcode' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'_use_bbcode_icons', isset($_POST[ 'use_bbcode_icons' ]) ? $_POST[ 'use_bbcode_icons' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg', isset($_POST[ 'use_wysiwyg' ]) && !isset($_POST[ 'use_bbcode' ]) ? $_POST[ 'use_wysiwyg' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_1', (isset($_POST[ 'use_wysiwyg_1' ]) && $_POST[ 'use_wysiwyg_1' ] != '') ? $_POST[ 'use_wysiwyg_1' ] : 'bold,italic,|,fontselect,fontsizeselect,forecolor,backcolor,|,bullist,numlist,|,link,unlink,|,image,media,|,emotions');
			update_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_2', (isset($_POST[ 'use_wysiwyg_2' ]) && $_POST[ 'use_wysiwyg_2' ] != '') ? $_POST[ 'use_wysiwyg_2' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_3', (isset($_POST[ 'use_wysiwyg_3' ]) && $_POST[ 'use_wysiwyg_3' ] != '') ? $_POST[ 'use_wysiwyg_3' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_4', (isset($_POST[ 'use_wysiwyg_4' ]) && $_POST[ 'use_wysiwyg_4' ] != '') ? $_POST[ 'use_wysiwyg_4' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_css', (isset($_POST[ 'use_wysiwyg_css' ])) ? $_POST[ 'use_wysiwyg_css' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_skin', (isset($_POST[ 'use_wysiwyg_skin' ])) ? $_POST[ 'use_wysiwyg_skin' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_width', $_POST[ 'use_wysiwyg_width' ]);
			update_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_height', $_POST[ 'use_wysiwyg_height' ]);
			update_option(WPS_OPTIONS_PREFIX.'_forum_lock', $_POST[ 'forum_lock' ] != '' ? $_POST[ 'forum_lock' ] : 0);
			update_option(WPS_OPTIONS_PREFIX.'_include_context', isset($_POST[ 'include_context' ]) ? $_POST[ 'include_context' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'_allow_subscribe_all', isset($_POST[ 'allow_subscribe_all' ]) ? $_POST[ 'allow_subscribe_all' ] : '');
			
			update_option(WPS_OPTIONS_PREFIX.'_alt_subs', isset($_POST[ '_alt_subs' ]) ? $_POST[ '_alt_subs' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'_pagination', isset($_POST[ 'pagination' ]) ? $_POST[ 'pagination' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'_pagination_size', isset($_POST[ 'pagination_size' ]) ? $_POST[ 'pagination_size' ] : '10');
			update_option(WPS_OPTIONS_PREFIX.'_pagination_location', $_POST[ 'pagination_location' ]);

			update_option(WPS_OPTIONS_PREFIX.'_show_dropdown', isset($_POST[ 'show_dropdown' ]) ? $_POST[ 'show_dropdown' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'_topic_count', isset($_POST[ 'topic_count' ]) ? $_POST[ 'topic_count' ] : '');
			
			update_option(WPS_OPTIONS_PREFIX.'_moderation_email_rejected', isset($_POST[ 'moderation_email_rejected' ]) ? $_POST[ 'moderation_email_rejected' ] : '');
			update_option(WPS_OPTIONS_PREFIX.'_moderation_email_accepted', isset($_POST[ 'moderation_email_accepted' ]) ? $_POST[ 'moderation_email_accepted' ] : '');

			update_option(WPS_OPTIONS_PREFIX.'_suppress_forum_notify', isset($_POST[ 'suppress_forum_notify' ]) ? $_POST[ 'suppress_forum_notify' ] : '');

			// Clear forum subscriptions
			if (isset($_POST['clear_forum_subs'])) {
				$wpdb->query("DELETE FROM ".$wpdb->prefix."symposium_subs");
				echo "<script>alert('Forum subscriptions cleared');</script>";
			}

			// Forum moderators
			if (isset($_POST['moderators'])) {
		   		$range = array_keys($_POST['moderators']);
		   		$level = '';
	   			foreach ($range as $key) {
					$level .= $_POST['moderators'][$key].',';
		   		}
			} else {
				$level = '';
			}
			update_option(WPS_OPTIONS_PREFIX.'_moderators', serialize($level));

			// Forum viewers
			if (isset($_POST['viewers'])) {
		   		$range = array_keys($_POST['viewers']);
		   		$level = '';
	   			foreach ($range as $key) {
					$level .= $_POST['viewers'][$key].',';
		   		}
			} else {
				$level = '';
			}
			update_option(WPS_OPTIONS_PREFIX.'_viewer', serialize($level));
			
			// Forum editors (new topic)
			if (isset($_POST['editors'])) {
		   		$range = array_keys($_POST['editors']);
		   		$level = '';
	   			foreach ($range as $key) {
					$level .= $_POST['editors'][$key].',';
		   		}
			} else {
				$level = '';
			}
			update_option(WPS_OPTIONS_PREFIX.'_forum_editor', serialize($level));

			// Forum replies
			if (isset($_POST['repliers'])) {
		   		$range = array_keys($_POST['repliers']);
		   		$level = '';
	   			foreach ($range as $key) {
					$level .= $_POST['repliers'][$key].',';
		   		}
			} else {
				$level = '';
			}
			update_option(WPS_OPTIONS_PREFIX.'_forum_reply', serialize($level));	

			// Forum replies
			if (isset($_POST['commenters'])) {
		   		$range = array_keys($_POST['commenters']);
		   		$level = '';
	   			foreach ($range as $key) {
					$level .= $_POST['commenters'][$key].',';
		   		}
			} else {
				$level = '';
			}
			update_option(WPS_OPTIONS_PREFIX.'_forum_reply_comment', serialize($level));	
			
			// Put an settings updated message on the screen
			echo "<div class='updated slideaway'><p>".__('Saved', WPS_TEXT_DOMAIN).".</p></div>";

		}
		
		?>

			<form method="post" action=""> 
			<input type="hidden" name="symposium_update" value="__wps__plugin_forum">
				
			<table class="form-table __wps__admin_table"> 
		
			<tr><td colspan="2">
			<div style="float: right; margin-top:-15px;">
			<?php echo '<a href="admin.php?page=symposium_categories">'.__('Go to Forum Management', WPS_TEXT_DOMAIN).'</a>'; ?>
			</div>
			<h2>Editor</h2></td></tr>

            <tr valign="top"> 
            <td scope="row"><label for="use_wysiwyg_width"><?php echo __('Width', WPS_TEXT_DOMAIN); ?></label></td>
            <td><span class="description"><?php echo __('Width of editor (eg: 300px or 100%)', WPS_TEXT_DOMAIN); ?></span><br />
            <input name="use_wysiwyg_width" type="text" id="use_wysiwyg_width"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_width'); ?>" />
            </td> 
            </tr> 
            <tr valign="top"> 
            <td scope="row"><label for="use_wysiwyg_height"><?php echo __('Height', WPS_TEXT_DOMAIN); ?></label></td>
            <td><span class="description"><?php echo __('Height of editor (eg: 250px)', WPS_TEXT_DOMAIN); ?></span><br />
            <input name="use_wysiwyg_height" type="text" id="use_wysiwyg_height"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_height'); ?>" />
            </td> 
            </tr> 

			<?php if (get_option(WPS_OPTIONS_PREFIX.'__wps__wysiwyg_activated') || get_option(WPS_OPTIONS_PREFIX.'__wps__wysiwyg_network_activated')) { ?>

	            <tr valign="top"> 
	            <td scope="row"><label for="use_bbcode"><?php echo __('BB Code toolbar', WPS_TEXT_DOMAIN); ?></label></td>
	            <td>
	            <input type="checkbox" name="use_bbcode" id="use_bbcode" <?php if (get_option(WPS_OPTIONS_PREFIX.'_use_bbcode') == "on") { echo "CHECKED"; } ?>/>
	            <span class="description">
	            <?php echo __('Use BB Code toolbar on the forums (cannot be used with WYSIWYG editor).', WPS_TEXT_DOMAIN); ?><br />
	            </span></td> 
	            </tr> 

				<?php if (get_option(WPS_OPTIONS_PREFIX.'_use_bbcode') == 'on') { ?>
                    <tr valign="top" style='border-bottom: 1px dashed #666;border-right: 1px dashed #666; border-left: 1px dashed #666; border-top: 1px dashed #666;'> 
                    <td class="highlighted_row" scope="row"><label for="use_bbcode_icons"><?php echo __('BB Code toolbar icons', WPS_TEXT_DOMAIN).'<br />bold|italic|underline|link|quote|code'; ?></label></td>
                    <td class="highlighted_row"><span class="description">
                    	<?php echo __('Icons to include in the BB Code toolbar.', WPS_TEXT_DOMAIN); ?></span><br />
                    <?php if (!get_option(WPS_OPTIONS_PREFIX.'_use_bbcode_icons')) update_option(WPS_OPTIONS_PREFIX.'_use_bbcode_icons', 'bold|italic|underline|link|quote|code'); ?>
                    <input name="use_bbcode_icons" style="width:350px" type="text" id="use_bbcode_icons"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_use_bbcode_icons'); ?>" />
                    </td> 
                    </tr>

                <?php } else {
                    echo '<input type="hidden" name="use_bbcode_icons" id="use_bbcode_icons" value="'.get_option(WPS_OPTIONS_PREFIX.'_use_bbcode_icons').'" />';
                }  
                ?>

                <tr valign="top"> 
                <td scope="row"><label for="use_wysiwyg"><?php echo __('WYSIWYG editor', WPS_TEXT_DOMAIN); ?></label></td>
                <td>
                <input type="checkbox" name="use_wysiwyg" id="use_wysiwyg" <?php if (get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg') == "on") { echo "CHECKED"; } ?>/>
                <span class="description">
                <?php echo __('Use the TinyMCE WYSIWYG editor/toolbar on the forums.', WPS_TEXT_DOMAIN); ?><br />
                <?php echo __('NB. Some themes cause layout problems with TinyMCE. Verified with TwentyEleven and tested with many others, but', WPS_TEXT_DOMAIN); ?><br />
                <?php echo __('if your editor toolbar layout is broken, check your theme stylesheets.', WPS_TEXT_DOMAIN); ?>
                </span></td> 
                </tr> 
            	                
				<?php if (get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg') == 'on') { ?>					
                    <tr valign="top" style='border-right: 1px dashed #666; border-left: 1px dashed #666; border-top: 1px dashed #666;'> 
                    <td scope="row" class="highlighted_row"><label for="include_context"><?php echo __('Context menu', WPS_TEXT_DOMAIN); ?></label></td>
                    <td class="highlighted_row">
                    <input type="checkbox" name="include_context" id="include_context" <?php if (get_option(WPS_OPTIONS_PREFIX.'_include_context') == "on") { echo "CHECKED"; } ?>/>
                    <span class="description"><?php echo __('Activate right-mouse click context menu.', WPS_TEXT_DOMAIN); ?></span></td> 
                    </tr> 
                
                    <tr valign="top" style='border-right: 1px dashed #666; border-left: 1px dashed #666;'> 
                    <td class="highlighted_row" scope="row"><label for="use_wysiwyg_1"><?php echo __('Editor Toolbars', WPS_TEXT_DOMAIN); ?><br />
                    <a href="http://www.tinymce.com/wiki.php/Buttons/controls" target="_blank"><?php echo __('See all buttons/controls', WPS_TEXT_DOMAIN) ?></a><br />
                    <a href="javascript:void(0);" id="use_wysiwyg_reset"><?php echo __('Reset (full)', WPS_TEXT_DOMAIN); ?></a><br />
                    <a href="javascript:void(0);" id="use_wysiwyg_reset_min"><?php echo __('Reset (minimal)', WPS_TEXT_DOMAIN); ?></a>
                    </label></td>
                    <td class="highlighted_row">
                        <span class="description"><?php echo __('Toolbar row 1', WPS_TEXT_DOMAIN); ?></span><br />
                        <textarea name="use_wysiwyg_1" style="width:350px; height:80px" id="use_wysiwyg_1"><?php echo get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_1'); ?></textarea><br />
                        <span class="description"><?php echo __('Toolbar row 2', WPS_TEXT_DOMAIN); ?></span><br />
                        <textarea name="use_wysiwyg_2" style="width:350px; height:80px" id="use_wysiwyg_2"><?php echo get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_2'); ?></textarea><br />
                        <span class="description"><?php echo __('Toolbar row 3', WPS_TEXT_DOMAIN); ?></span><br />
                        <textarea name="use_wysiwyg_3" style="width:350px; height:80px" id="use_wysiwyg_3"><?php echo get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_3'); ?></textarea><br />
                        <span class="description"><?php echo __('Toolbar row 4', WPS_TEXT_DOMAIN); ?></span><br />
                        <textarea name="use_wysiwyg_4" style="width:350px; height:80px" id="use_wysiwyg_4"><?php echo get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_4'); ?></textarea><br />
                    </td> 
                    </tr> 
                    <tr valign="top" style='border-right: 1px dashed #666; border-left: 1px dashed #666; '> 
                    <td class="highlighted_row" scope="row"><label for="use_wysiwyg_css"><?php echo __('Editor CSS', WPS_TEXT_DOMAIN); ?></label></td>
                    <td class="highlighted_row"><span class="description"><?php echo __('Path for CSS file, eg:', WPS_TEXT_DOMAIN).' '.str_replace(__wps__siteURL(), '', WPS_PLUGIN_URL."/tiny_mce/themes/advanced/skins/wps.css"); ?></span><br />
                    <span class="description"><?php echo __('You may need to clear your browsing cache if changing the content of the file.', WPS_TEXT_DOMAIN); ?></span><br />
                    <?php if (!get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_css')) update_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_css', str_replace(__wps__siteURL(), '', WPS_PLUGIN_URL."/tiny_mce/themes/advanced/skins/wps.css")); ?>
                    <input name="use_wysiwyg_css" style="width:350px" type="text" id="use_wysiwyg_css"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_css'); ?>" />
                    </td> 
                    </tr> 
                    <tr valign="top" style='border-bottom: 1px dashed #666; border-right: 1px dashed #666; border-left: 1px dashed #666; '> 
                    <td class="highlighted_row" scope="row"><label for="use_wysiwyg_skin"><?php echo __('Skin folder', WPS_TEXT_DOMAIN); ?></label></td>
                    <td class="highlighted_row"><span class="description"><?php echo sprintf(__('Folders are stored in %s/tiny_mce/themes/advanced/skins; eg: cirkuit', WPS_TEXT_DOMAIN), str_replace(get_bloginfo('url'), '', WPS_PLUGIN_URL)); ?></span><br />
                    <?php if (!get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_skin')) update_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_skin', 'cirkuit'); ?>
                    <input name="use_wysiwyg_skin" type="text" id="use_wysiwyg_skin"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_skin'); ?>" />
                    </td> 
                    </tr> 
                <?php } else {
                    echo '<input type="hidden" name="use_wysiwyg_1" id="use_wysiwyg_1" value="'.get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_1').'" />';
                    echo '<input type="hidden" name="use_wysiwyg_2" id="use_wysiwyg_2" value="'.get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_2').'" />';
                    echo '<input type="hidden" name="use_wysiwyg_3" id="use_wysiwyg_3" value="'.get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_3').'" />';
                    echo '<input type="hidden" name="use_wysiwyg_4" id="use_wysiwyg_4" value="'.get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_4').'" />';
                    echo '<input type="hidden" name="use_wysiwyg_css" id="use_wysiwyg_css" value="'.get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_css').'" />';
                    echo '<input type="hidden" name="use_wysiwyg_skin" id="use_wysiwyg_skin" value="'.get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_skin').'" />';
                }  
                					
			} else {

				echo '<tr valign="top"><td colspan="2">';
				echo __("To access WYSWIYG forum editor and BB Code Toolbar settings, <a href='admin.php?page=symposium_debug'>activate the Bronze member feature</a>.", WPS_TEXT_DOMAIN).'<br />';
				echo '<em>NB. Some themes cause layout problems with TinyMCE WYSIWYG editor. The editor is verified with TwentyTwelve and tested with many others, but if your editor toolbar layout is broken, check your theme stylesheets.</em>';
				echo '</td></tr>';
				
				echo '<input type="hidden" name="use_wysiwyg" id="use_wysiwyg" value="'.get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_1').'" />';
			} ?>

			<tr><td colspan="2"><h2>AJAX/Refresh</h2></td></tr>

			<tr valign="top"> 
			<td scope="row"><label for="forum_ajax"><?php echo __('Use AJAX', WPS_TEXT_DOMAIN); ?></label></td>
			<td>
			<input type="checkbox" name="forum_ajax" id="forum_ajax" <?php if (get_option(WPS_OPTIONS_PREFIX.'_forum_ajax') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Use AJAX, or hyperlinks and page re-loading?', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 

			<tr valign="top"> 
			<td scope="row"><label for="forum_refresh"><?php echo __('Refresh forum after reply', WPS_TEXT_DOMAIN); ?></label></td>
			<td>
			<input type="checkbox" name="forum_refresh" id="forum_refresh" <?php if (get_option(WPS_OPTIONS_PREFIX.'_forum_refresh') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Reload the page after posting a reply on the forum.', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 
	
			<tr><td colspan="2"><h2>Moderation</h2></td></tr>

			<tr valign="top"> 
			<td scope="row"><label for="moderation"><?php echo __('Moderation', WPS_TEXT_DOMAIN); ?></label></td>
			<td>
			<input type="checkbox" name="moderation" id="moderation" <?php if (get_option(WPS_OPTIONS_PREFIX.'_moderation') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('New topics and posts require admin approval', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 
	
			<tr valign="top"> 
			<td scope="row"><label for="moderation_email_rejected"><?php echo __('Rejection email', WPS_TEXT_DOMAIN); ?></label></td>
			<td>
			<input type="checkbox" name="moderation_email_rejected" id="moderation_email_rejected" <?php if (get_option(WPS_OPTIONS_PREFIX.'_moderation_email_rejected') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Send email to user when forum post rejected', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 
	
			<tr valign="top"> 
			<td scope="row"><label for="moderation_email_accepted"><?php echo __('Accepted email', WPS_TEXT_DOMAIN); ?></label></td>
			<td>
			<input type="checkbox" name="moderation_email_accepted" id="moderation_email_accepted" <?php if (get_option(WPS_OPTIONS_PREFIX.'_moderation_email_accepted') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Send email to user when forum post accepted', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 
	
			<tr><td colspan="2"><h2>Attachments</h2></td></tr>

			<tr valign="top"> 
			<td scope="row"><label for="forum_uploads"><?php echo __('Allow uploads', WPS_TEXT_DOMAIN); ?></label></td>
			<td>
			<input type="checkbox" name="forum_uploads" id="forum_uploads" <?php if (get_option(WPS_OPTIONS_PREFIX.'_forum_uploads') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Allow members to upload files with forum posts (requires Flash to be installed)', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 

			<tr valign="top"> 
			<td scope="row"><label for="forum_thumbs"><?php echo __('Inline attachments', WPS_TEXT_DOMAIN); ?></label></td>
			<td>
			<input type="checkbox" name="forum_thumbs" id="forum_thumbs" <?php if (get_option(WPS_OPTIONS_PREFIX.'_forum_thumbs') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Show uploaded forum attachments as images/videos (not links). Documents are always links.', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 
		
			<tr valign="top"> 
			<td scope="row"><label for="forum_thumbs_size"><?php echo __('Thumbnail size', WPS_TEXT_DOMAIN); ?></label></td>
			<td><input name="forum_thumbs_size" style="width:50px" type="text" id="forum_thumbs_size"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_forum_thumbs_size'); ?>" /> 
			<span class="description"><?php echo __('If using inline attachments, maximum width', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 
			
			<tr><td colspan="2"><h2>Voting</h2></td></tr>

			<tr valign="top"> 
			<td scope="row"><label for="use_votes"><?php echo __('Use Votes', WPS_TEXT_DOMAIN); ?></label></td>
			<td>
			<input type="checkbox" name="use_votes" id="use_votes" <?php if (get_option(WPS_OPTIONS_PREFIX.'_use_votes') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Allow members to vote (plus or minus) on forum posts', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 

			<tr valign="top"> 
			<td scope="row"><label for="use_votes_min"><?php echo __('Votes (minimum posts)', WPS_TEXT_DOMAIN); ?></label></td>
			<td><input name="use_votes_min" style="width:50px" type="text" id="use_votes_min"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_use_votes_min'); ?>" /> 
			<span class="description"><?php echo __('How many posts a member must have made in order to vote', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 
	
	
			<tr valign="top"> 
			<td scope="row"><label for="use_votes_remove"><?php echo __('Votes (removal point)', WPS_TEXT_DOMAIN); ?></label></td>
			<td><input name="use_votes_remove" style="width:50px" type="text" id="use_votes_remove"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_use_votes_remove'); ?>" /> 
			<span class="description"><?php echo __('When a forum post gets this many votes, it is removed. Can be + or -. Leave as 0 to ignore.', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 

			<tr valign="top"> 
			<td scope="row"><label for="use_answers"><?php echo __('Votes (answers)', WPS_TEXT_DOMAIN); ?></label></td>
			<td>
			<input type="checkbox" name="use_answers" id="use_answers" <?php if (get_option(WPS_OPTIONS_PREFIX.'_use_answers') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Allows topic owners and administrators to mark a reply as an answer (one per topic)', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 

			<tr><td colspan="2"><h2>Permissions</h2></td></tr>

			<tr valign="top"> 
			<td scope="row"><label for="moderation_roles"><?php echo __('Roles that can moderate the forum', WPS_TEXT_DOMAIN); ?></label></td> 
			<td>
			<?php		
				// Get list of roles
				global $wp_roles;
				$all_roles = $wp_roles->roles;
		
				$view_roles = get_option(WPS_OPTIONS_PREFIX.'_moderators');

				foreach ($all_roles as $role) {
					echo '<input type="checkbox" name="moderators[]" value="'.$role['name'].'"';
					if ($role['name'] == 'Administrator' || strpos(strtolower($view_roles), strtolower($role['name']).',') !== FALSE) {
						echo ' CHECKED';
					}
					echo '> '.$role['name'].'<br />';
				}			
			?>
			<span class="description">
					<?php echo sprintf(__('The WordPress roles that can <a href="%s">moderate forum posts</a>. Administrator will always be checked.', WPS_TEXT_DOMAIN), "admin.php?page=symposium_moderation"); ?><br />
					<?php echo __('Checked roles can also manage the forum via the front-end.', WPS_TEXT_DOMAIN); ?>
			</span></td> 
			</tr> 

			<tr valign="top"> 
			<td scope="row"><label for="viewer"><?php echo __('View forum roles', WPS_TEXT_DOMAIN); ?></label></td> 
			<td>
			<?php		
				// Get list of roles
				global $wp_roles;
				$all_roles = $wp_roles->roles;
		
				$view_roles = get_option(WPS_OPTIONS_PREFIX.'_viewer');

				echo '<input type="checkbox" name="viewers[]" value="'.__('everyone', WPS_TEXT_DOMAIN).'"';
				if (strpos(strtolower($view_roles), strtolower(__('everyone', WPS_TEXT_DOMAIN)).',') !== FALSE) {
					echo ' CHECKED';
				}
				echo '> '.__('Guests', WPS_TEXT_DOMAIN).' ... <span class="description">'.__('means everyone can view the forum if checked', WPS_TEXT_DOMAIN).'</span><br />';						
				foreach ($all_roles as $role) {
					echo '<input type="checkbox" name="viewers[]" value="'.$role['name'].'"';
					if (strpos(strtolower($view_roles), strtolower($role['name']).',') !== FALSE) {
						echo ' CHECKED';
					}
					echo '> '.$role['name'].'<br />';
				}			
			?>
			<span class="description"><?php echo __('The WordPress roles that can view the entire forum (fine tune with <a href="admin.php?page=symposium_categories">forum categories</a>)', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 
	
			<tr valign="top"> 
			<td scope="row"><label for="forum_editor"><?php echo __('Forum new topic roles', WPS_TEXT_DOMAIN); ?></label></td> 
			<td>
			<?php		
				// Get list of roles
				global $wp_roles;
				$all_roles = $wp_roles->roles;
		
				$view_roles = get_option(WPS_OPTIONS_PREFIX.'_forum_editor');

				echo '<input type="checkbox" name="editors[]" value="'.__('everyone', WPS_TEXT_DOMAIN).'"';
				if (strpos(strtolower($view_roles), strtolower(__('everyone', WPS_TEXT_DOMAIN)).',') !== FALSE) {
					echo ' CHECKED';
				}
				echo '> '.__('Everyone', WPS_TEXT_DOMAIN).' ... <span class="description">'.__('means all members can post new topics if checked', WPS_TEXT_DOMAIN).'</span><br />';						
				foreach ($all_roles as $role) {
					echo '<input type="checkbox" name="editors[]" value="'.$role['name'].'"';
					if (strpos(strtolower($view_roles), strtolower($role['name']).',') !== FALSE) {
						echo ' CHECKED';
					}
					echo '> '.$role['name'].'<br />';
				}			
			?>
			<span class="description"><?php echo __('The WordPress roles that can post a new topic on the forum', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 

			<tr valign="top"> 
			<td scope="row"><label for="forum_reply"><?php echo __('Forum reply roles', WPS_TEXT_DOMAIN); ?></label></td> 
			<td>
			<?php		
				// Get list of roles
				global $wp_roles;
				$all_roles = $wp_roles->roles;
		
				$reply_roles = get_option(WPS_OPTIONS_PREFIX.'_forum_reply');

				echo '<input type="checkbox" name="repliers[]" value="'.__('everyone', WPS_TEXT_DOMAIN).'"';
				if (strpos(strtolower($reply_roles), strtolower(__('everyone', WPS_TEXT_DOMAIN)).',') !== FALSE) {
					echo ' CHECKED';
				}
				echo '> '.__('Everyone', WPS_TEXT_DOMAIN).' ... <span class="description">'.__('means all members can reply to topics if checked', WPS_TEXT_DOMAIN).'</span><br />';						
				foreach ($all_roles as $role) {
					echo '<input type="checkbox" name="repliers[]" value="'.$role['name'].'"';
					if (strpos(strtolower($reply_roles), strtolower($role['name']).',') !== FALSE) {
						echo ' CHECKED';
					}
					echo '> '.$role['name'].'<br />';
				}			
			?>
			<span class="description"><?php echo __('The WordPress roles that can reply to a topic on the forum', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 
		
			<tr valign="top"> 
			<td scope="row"><label for="forum_comments"><?php echo __('Forum comment roles', WPS_TEXT_DOMAIN); ?></label></td> 
			<td>
			<?php		
				// Get list of roles
				global $wp_roles;
				$all_roles = $wp_roles->roles;
		
				$reply_roles = get_option(WPS_OPTIONS_PREFIX.'_forum_reply_comment');

				echo '<input type="checkbox" name="commenters[]" value="'.__('everyone', WPS_TEXT_DOMAIN).'"';
				if (strpos(strtolower($reply_roles), strtolower(__('everyone', WPS_TEXT_DOMAIN)).',') !== FALSE) {
					echo ' CHECKED';
				}
				echo '> '.__('Everyone', WPS_TEXT_DOMAIN).' ... <span class="description">'.__('means all members can comment on replies if checked', WPS_TEXT_DOMAIN).'</span><br />';						
				foreach ($all_roles as $role) {
					echo '<input type="checkbox" name="commenters[]" value="'.$role['name'].'"';
					if (strpos(strtolower($reply_roles), strtolower($role['name']).',') !== FALSE) {
						echo ' CHECKED';
					}
					echo '> '.$role['name'].'<br />';
				}			
			?>
			<span class="description"><?php echo __('The WordPress roles that can add comments to forum replies', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 

			<tr><td colspan="2"><a name="ranks"></a><h2>Ranks</h2></td></tr>

			<?php
			$ranks = explode(';', get_option(WPS_OPTIONS_PREFIX.'_forum_ranks'));
			?>
			<tr valign="top"> 
			<td scope="row"><label for="forum_ranks"><?php echo __('Forum ranks', WPS_TEXT_DOMAIN); ?></label></td>
			<td>
			<input type="checkbox" name="forum_ranks" id="forum_ranks" <?php if ($ranks[0] == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Use ranks on the forum?', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr>

			<?php
			for ( $rank = 1; $rank <= 11; $rank ++) {
				echo '<tr valign="top">';
					if ($rank == 1) { 

						echo '<td scope="row">';
							echo __('Title and Posts Required', WPS_TEXT_DOMAIN);
						echo '</td>';

					} else {

						echo '<td scope="row">';
						
							if ($rank == 11) {
								echo '<em>'.__('(blank ranks are not used)', WPS_TEXT_DOMAIN).'</em>';
							} else {
								echo "&nbsp;";
							}
						
						echo '</td>';

					}
					?>
					<td>
						<?php 
							$this_rank = $rank*2-1;
							$this_rank_label = $ranks[$this_rank];
							$this_rank_value = $ranks[$this_rank+1];
							
							if ($this_rank_label != '') {
								echo '<input name="rank'.$rank.'" type="text" id="rank'.$rank.'"  value="'.$this_rank_label.'" /> ';
								if ($rank > 1) {
									echo '<input name="score'.$rank.'" type="text" id="score'.$rank.'" style="width:50px" value="'.$this_rank_value.'" /> ';
								} else {
									echo '<input name="score'.$rank.'" type="text" id="score'.$rank.'" style="width:50px; display:none;"" /> ';
								} 
							} else {
								echo '<input name="rank'.$rank.'" type="text" id="rank'.$rank.'"  value="" /> ';
								if ($rank > 1) {
									echo '<input name="score'.$rank.'" type="text" id="score'.$rank.'" style="width:50px" value="" /> ';
								}
							}
						?>

						<span class="description">
						<?php 
						if ($rank == 1) {
							echo __('Most posts', WPS_TEXT_DOMAIN); 
						} else {
							echo __('Rank', WPS_TEXT_DOMAIN).' '.($rank-1); 							
						}
						?></span>
					</td> 
				</tr>
			<?php
			}
			do_action('__wps__menu_forum_hook');	
			?>
			
			<tr><td colspan="2"><a name="display"></a><h2>Display</h2></td></tr>

			<tr valign="top"> 
			<td scope="row"><label for="_alt_subs"><?php echo __('Sub Categories', WPS_TEXT_DOMAIN); ?></label></td>
			<td>
			<input type="checkbox" name="_alt_subs" id="_alt_subs" <?php if (get_option(WPS_OPTIONS_PREFIX.'_alt_subs') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Show child categories under parent categories', WPS_TEXT_DOMAIN); ?></span>
			</td> 
			</tr> 

			<tr valign="top"> 
			<td scope="row"><label for="pagination"><?php echo __('Pagination', WPS_TEXT_DOMAIN); ?></label></td>
			<td>
			<input type="checkbox" name="pagination" id="pagination" <?php if (get_option(WPS_OPTIONS_PREFIX.'_pagination') == "on" && get_option(WPS_OPTIONS_PREFIX.'_forum_ajax') != "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Breaks topic replies into pages', WPS_TEXT_DOMAIN); ?></span>
			<?php if (get_option(WPS_OPTIONS_PREFIX.'_forum_ajax') == "on")
				echo '<br /><span class="description" style="color:red;">'.__('Sorry, this is not compatible if using AJAX on the forum (above).', WPS_TEXT_DOMAIN).'</span>';
			?>
			</td> 
			</tr> 

			<tr valign="top"> 
			<td scope="row"><label for="pagination_location"><?php echo __('Pagination Placement', WPS_TEXT_DOMAIN); ?></label></td>
			<td>
			<select name="pagination_location">
				<option value="both"<?php if (get_option(WPS_OPTIONS_PREFIX.'_pagination_location') == 'both') echo ' SELECTED'; ?>><?php echo __('Above and below replies', WPS_TEXT_DOMAIN); ?></option>
				<option value="top"<?php if (get_option(WPS_OPTIONS_PREFIX.'_pagination_location') == 'top') echo ' SELECTED'; ?>><?php echo __('Above replies', WPS_TEXT_DOMAIN); ?></option>
				<option value="bottom"<?php if (get_option(WPS_OPTIONS_PREFIX.'_pagination_location') == 'bottom') echo ' SELECTED'; ?>><?php echo __('Below replies', WPS_TEXT_DOMAIN); ?></option>
			</select>
			<span class="description"><?php echo __('If pagination is used, where to show page navigation', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 

			<tr valign="top"> 
			<td scope="row"><label for="pagination_size"><?php echo __('Number of replies per page (pagination)', WPS_TEXT_DOMAIN); ?></label></td>
			<td><input name="pagination_size" style="width:50px" type="text" id="pagination_size"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_pagination_size') ? get_option(WPS_OPTIONS_PREFIX.'_pagination_size') : 10; ?>" /> 
			<span class="description"><?php echo __('If pagination is used, how many replies per page.', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 
	
			<tr valign="top"> 
			<td scope="row"><label for="topic_count"><?php echo __('Forum category post count', WPS_TEXT_DOMAIN); ?></label></td>
			<td><input name="topic_count" style="width:50px" type="text" id="topic_count"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_topic_count') ? get_option(WPS_OPTIONS_PREFIX.'_topic_count') : 10; ?>" /> 
			<span class="description"><?php echo __('How many topics are shown in a forum category (use an even number).', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 
	
			<tr valign="top"> 
			<td scope="row"><label for="show_dropdown"><?php echo __('Dropdown category list', WPS_TEXT_DOMAIN); ?></label></td>
			<td>
			<input type="checkbox" name="show_dropdown" id="show_dropdown" <?php if (get_option(WPS_OPTIONS_PREFIX.'_show_dropdown') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Show a dropdown list of categories for quick navigation', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 

			<tr valign="top"> 
			<td scope="row"><label for="forum_info"><?php echo __('Member Info', WPS_TEXT_DOMAIN); ?></label></td>
			<td>
			<input type="checkbox" name="forum_info" id="forum_info" <?php if (get_option(WPS_OPTIONS_PREFIX.'_forum_info') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Show member info underneath avatar on forum', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 

			<tr valign="top"> 
			<td scope="row"><label for="forum_stars"><?php echo __('New post stars', WPS_TEXT_DOMAIN); ?></label></td>
			<td>
			<input type="checkbox" name="forum_stars" id="forum_stars" <?php if (get_option(WPS_OPTIONS_PREFIX.'_forum_stars') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Show stars for posts added since last login.', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 
	
			<tr valign="top"> 
			<td scope="row"><label for="forum_login"><?php echo __('Login Link', WPS_TEXT_DOMAIN); ?></label></td>
			<td>
			<input type="checkbox" name="forum_login" id="forum_login" <?php if (get_option(WPS_OPTIONS_PREFIX.'_forum_login') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Show login link on forum when not logged in?', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 
                                                			
			<tr valign="top"> 
			<td scope="row"><label for="forum_login_form"><?php echo __('Show login link below topic', WPS_TEXT_DOMAIN); ?></label></td>
			<td>
			<input type="checkbox" name="forum_login_form" id="forum_login_form" <?php if (get_option(WPS_OPTIONS_PREFIX.'_forum_login_form') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('If a user has to log in, show the login link underneath the topic/replies.', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 

			<tr><td colspan="2"><a name="more"></a><h2>Subscriptions</h2></td></tr>
									
			<tr valign="top"> 
			<td scope="row"><label for="suppress_forum_notify"><?php echo __('Forum subscription', WPS_TEXT_DOMAIN); ?></label></td>
			<td>
			<input type="checkbox" name="suppress_forum_notify" id="suppress_forum_notify" <?php if (get_option(WPS_OPTIONS_PREFIX.'_suppress_forum_notify') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Hide all forum subscription options', WPS_TEXT_DOMAIN); ?></span><br />
			<input type="checkbox" name="clear_forum_subs" id="clear_forum_subs" />
			<span class="description"><?php echo __('If checked, all forum subscriptions will be cleared when you save (option not saved, applied just once)', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 
	
			<tr valign="top"> 
			<td scope="row"><label for="allow_subscribe_all"><?php echo __('Subscribe to all forum activity', WPS_TEXT_DOMAIN); ?></label></td>
			<td>
			<input type="checkbox" name="allow_subscribe_all" id="allow_subscribe_all" <?php if (get_option(WPS_OPTIONS_PREFIX.'_allow_subscribe_all') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Allow subscribe to all via Profile page, Profile Details. If you have a lot of users, consider switching this off to improve forum performance.', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 
	
			<tr><td colspan="2"><a name="more"></a><h2>More...</h2></td></tr>

			<tr valign="top"> 
			<td scope="row"><label for="send_summary"><?php echo __('Daily Digest', WPS_TEXT_DOMAIN); ?></label></td>
			<td>
			<input type="checkbox" name="send_summary" id="send_summary" <?php if (get_option(WPS_OPTIONS_PREFIX.'_send_summary') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Enable daily summaries of forum activity to all members via email', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 
	
			<tr valign="top"> 
			<td scope="row"><label for="include_admin"><?php echo __('Admin views', WPS_TEXT_DOMAIN); ?></label></td>
			<td>
			<input type="checkbox" name="include_admin" id="include_admin" <?php if (get_option(WPS_OPTIONS_PREFIX.'_include_admin') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Include administrator viewing a topic in the total view count', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 
	
			<tr valign="top"> 
			<td scope="row"><label for="bump_topics"><?php echo __('Bump topics', WPS_TEXT_DOMAIN); ?></label></td>
			<td>
			<input type="checkbox" name="bump_topics" id="bump_topics" <?php if (get_option(WPS_OPTIONS_PREFIX.'_bump_topics') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Bumps topics to top of forum when new replies are posted', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 
	
			<tr valign="top"> 
			<td scope="row"><label for="oldest_first"><?php echo __('Order of replies', WPS_TEXT_DOMAIN); ?></label></td>
			<td>
			<input type="checkbox" name="oldest_first" id="oldest_first" <?php if (get_option(WPS_OPTIONS_PREFIX.'_oldest_first') == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Show oldest replies first (uncheck to reverse order)', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 
	
			<tr valign="top"> 
			<td scope="row"><label for="forum_lock"><?php echo __('Post lock time', WPS_TEXT_DOMAIN); ?></label></td>
			<td><input name="forum_lock" style="width:50px" type="text" id="forum_lock"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_forum_lock'); ?>" /> 
			<span class="description"><?php echo __('How many minutes before a forum topic/reply can no longer be edited/deleted, 0 for no lock.', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 
	
			<tr valign="top"> 
			<td scope="row"><label for="preview1"><?php echo __('Preview length', WPS_TEXT_DOMAIN); ?></label></td>
			<td><input name="preview1" style="width:50px" type="text" id="preview1"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_preview1'); ?>" /> 
			<span class="description"><?php echo __('Maximum number of characters to show in topic preview', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 
	
			<tr valign="top"> 
			<td scope="row"><label for="preview2"></label></td>
			<td><input name="preview2" style="width:50px" type="text" id="preview2"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_preview2'); ?>" /> 
			<span class="description"><?php echo __('Maximum number of characters to show in reply preview', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 

			<tr valign="top"> 
			<td scope="row"><label for="wps_default_forum"><?php echo __('Default Categories', WPS_TEXT_DOMAIN); ?></label></td>
			<td><input name="wps_default_forum" type="text" id="wps_default_forum"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_wps_default_forum'); ?>" /> 
			<span class="description"><?php echo __('List of forum categories IDs, that new site members automatically subscribe to (comma separated)', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 
			
			<tr valign="top"> 
			<td scope="row"><label for="chatroom_banned"><?php echo __('Banned forum words', WPS_TEXT_DOMAIN); ?></label></td> 
			<td><input name="chatroom_banned" type="text" id="chatroom_banned"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_chatroom_banned'); ?>" /> 
			<span class="description"><?php echo __('Comma separated list of words not allowed in the forum', WPS_TEXT_DOMAIN); ?></td> 
			</tr> 

									
			<tr valign="top"> 
			<td scope="row"><label for="closed_word"><?php echo __('Closed word', WPS_TEXT_DOMAIN); ?></label></td>
			<td><input name="closed_word" type="text" id="closed_word"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_closed_word'); ?>" /> 
			<span class="description"><?php echo __('Word used to denote a topic that is closed (see also Styles)', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 

			<?php
			$sharing = get_option(WPS_OPTIONS_PREFIX.'_sharing');
			if ( strpos($sharing, "pl") === FALSE ) { $sharing_permalink = ''; } else { $sharing_permalink = 'on'; }
			if ( strpos($sharing, "fb") === FALSE ) { $sharing_facebook = ''; } else { $sharing_facebook = 'on'; }
			if ( strpos($sharing, "tw") === FALSE ) { $sharing_twitter = ''; } else { $sharing_twitter = 'on'; }
			if ( strpos($sharing, "ms") === FALSE ) { $sharing_myspace = ''; } else { $sharing_myspace = 'on'; }
			if ( strpos($sharing, "li") === FALSE ) { $sharing_linkedin = ''; } else { $sharing_linkedin = 'on'; }
			if ( strpos($sharing, "be") === FALSE ) { $sharing_bebo = ''; } else { $sharing_bebo = 'on'; }
			if ( strpos($sharing, "em") === FALSE ) { $sharing_email = ''; } else { $sharing_email = 'on'; }
			?>
			

			<tr valign="top"> 
			<td scope="row"><label for="sharing_permalink"><?php echo __('Sharing icons included', WPS_TEXT_DOMAIN); ?></label></td>
			<td>
			<input type="checkbox" name="sharing_permalink" id="sharing_permalink" <?php if ($sharing_permalink == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Permalink (to copy)', WPS_TEXT_DOMAIN); ?></span><br />
			<input type="checkbox" name="sharing_email" id="sharing_email" <?php if ($sharing_email == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Email', WPS_TEXT_DOMAIN); ?></span><br />
			<input type="checkbox" name="sharing_facebook" id="sharing_facebook" <?php if ($sharing_facebook == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Facebook', WPS_TEXT_DOMAIN); ?></span><br />
			<input type="checkbox" name="sharing_twitter" id="sharing_twitter" <?php if ($sharing_twitter == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Twitter', WPS_TEXT_DOMAIN); ?></span><br />
			<input type="checkbox" name="sharing_myspace" id="sharing_myspace" <?php if ($sharing_myspace == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('MySpace', WPS_TEXT_DOMAIN); ?></span><br /> 
			<input type="checkbox" name="sharing_bebo" id="sharing_bebo" <?php if ($sharing_bebo == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Bebo', WPS_TEXT_DOMAIN); ?></span><br />
			<input type="checkbox" name="sharing_linkedin" id="sharing_linkedin" <?php if ($sharing_linkedin == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('LinkedIn', WPS_TEXT_DOMAIN); ?></span>
			</td> 
			</tr> 

			<tr valign="top"> 
			<td colspan=2>
				<p>
				<span class="description">
				<strong><?php echo __('Notes', WPS_TEXT_DOMAIN); ?></strong>
				<ul style='margin-left:6px'>
				<li>&middot;&nbsp;<?php echo __('Daily summaries (if there is anything to send) are sent when the first visitor comes to the site after midnight, local time.', WPS_TEXT_DOMAIN); ?></li>
				<li>&middot;&nbsp;<?php echo __('Be aware of any limits set by your hosting provider for sending out bulk emails, they may suspend your website.', WPS_TEXT_DOMAIN); ?></li>
				</ul>
				</p>
			</td>
			</tr> 

			<tr><td colspan="2"><h2>Shortcodes</h2></td></tr>

			<tr><td><?php echo '['.WPS_SHORTCODE_PREFIX.'-forum]'; ?></td>
				<td><?php _e('Display the forum.', WPS_TEXT_DOMAIN); ?></td></tr>
			<tr><td><?php echo '['.WPS_SHORTCODE_PREFIX.'-forum cat="2"]'; ?></td>
				<td><?php _e('Display just forum category ID 2 on a single WordPress page.', WPS_TEXT_DOMAIN); ?></td></tr>
			<tr valign="top"> 
			<td scope="row"><label for="symposium_forumlatestposts_count"><?php echo '['.WPS_SHORTCODE_PREFIX.'-forumlatestposts]'; ?></label></td>
			<td><?php _e('Display most recent forum posts and replies.', WPS_TEXT_DOMAIN); ?><br /><input name="symposium_forumlatestposts_count" style="width:50px" type="text" id="symposium_forumlatestposts_count"  value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_symposium_forumlatestposts_count'); ?>" /> 
			<span class="description"><?php 
			echo sprintf(__('Default number of topics to show. Can be overridden, eg: [%s-forumlatestposts count=10]', WPS_TEXT_DOMAIN), WPS_SHORTCODE_PREFIX).'<br />'; 
			echo '<span style="margin-left:55px">'.sprintf(__('Forum category IDs can be specified in the shortcode, eg: [%s-forumlatestposts cat=1]', WPS_TEXT_DOMAIN), WPS_SHORTCODE_PREFIX).'</span>'; ?></span></td> 
			</tr> 
			

															
			</table> 	
		 
			<p class="submit" style='margin-left:6px;'> 
			<input type="submit" name="Submit" class="button-primary" value="<?php echo __('Save Changes', WPS_TEXT_DOMAIN); ?>" /> 
			</p> 
			</form> 
		
	<?php	__wps__show_tabs_header_end(); ?> 
	</div>
<?php
}



function __wps__plugin_categories() {

	global $wpdb;

  	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.', WPS_TEXT_DOMAIN) );
  	}
  	
  	if (isset($_GET['action'])) {
		$action = $_GET['action'];
	} else {
		$action = '';
	}

	// Update values
	if (isset($_POST['title'])) {
		
   		$range = array_keys($_POST['cid']);
		foreach ($range as $key) {
			$cid = $_POST['cid'][$key];
			$cat_parent = $_POST['cat_parent'][$key];
			$title = $_POST['title'][$key];

			if (isset($_POST['level_'.$cid])) {
		   		$range2 = array_keys($_POST['level_'.$cid]);
		   		$level = '';
	   			foreach ($range2 as $key2) {
					$level .= $_POST['level_'.$cid][$key2].',';
		   		}
			} else {
				$level = '';
			}
			
			$listorder = $_POST['listorder'][$key];
			$allow_new = $_POST['allow_new'][$key];
			$hide_breadcrumbs = $_POST['hide_breadcrumbs'][$key];
			$hide_main = $_POST['hide_main'][$key];
			$cat_desc = $_POST['cat_desc'][$key];
			$min_rank = $_POST['min_rank'][$key] ? $_POST['min_rank'][$key] : 0;
			
			if ($cid == $_POST['default_category']) {
				$defaultcat = "on";
			} else {
				$defaultcat = "";
			}
			
			$wpdb->query( $wpdb->prepare( "
				UPDATE ".$wpdb->prefix.'symposium_cats'."
				SET title = %s, cat_parent = %d, min_rank = %d, listorder = %s, allow_new = %s, hide_breadcrumbs = %s, hide_main = %s, cat_desc = %s, defaultcat = %s, level = %s 
				WHERE cid = %d", 
				$title, $cat_parent, $min_rank, $listorder, $allow_new, $hide_breadcrumbs, $hide_main, $cat_desc, $defaultcat, serialize($level), $cid  ) );
							
		}

	}
		
  	// Add new category?
  	if ( (isset($_POST['new_title']) && $_POST['new_title'] != '') && ($_POST['new_title'] != __('Add New Category', WPS_TEXT_DOMAIN).'...') ) {
  		
  		$new_cat_desc = $_POST['new_cat_desc'];
  		if ($new_cat_desc == __('Optional Description', WPS_TEXT_DOMAIN)."...") {
  			$new_cat_desc = '';  		
  		}
  	
		$stub = trim(preg_replace("/[^A-Za-z0-9 ]/",'',$_POST['new_title']));
		$stub = strtolower(str_replace(' ', '-', $stub));
		$sql = "SELECT COUNT(*) FROM ".$wpdb->prefix."symposium_cats WHERE stub = '".$stub."'";
		$cnt = $wpdb->get_var($sql);
		if ($cnt > 0) $stub .= "-".$cnt;
		$stub = str_replace('--', '-', $stub);
		  		
		$wpdb->query( $wpdb->prepare( "
			INSERT INTO ".$wpdb->prefix.'symposium_cats'."
			( 	title, 
				cat_parent,
				listorder,
				cat_desc,
				allow_new,
				stub
			)
			VALUES ( %s, %d, %d, %s, %s, %s )", 
			array(
				$_POST['new_title'], 
				$_POST['new_parent'],
				$_POST['new_listorder'],
				$new_cat_desc,
				$_POST['new_allow_new'],
				$stub
				) 
			) );
		  
	}

  	// Delete a category?
  	if ( ($action == 'delcid') && (current_user_can('level_10')) ) {
  		// Must leave at least one category, so check
		$cat_count = $wpdb->get_var("SELECT COUNT(*) FROM ".$wpdb->prefix.'symposium_cats');
		if ($cat_count > 1) {
			$wpdb->query( $wpdb->prepare("DELETE FROM ".$wpdb->prefix.'symposium_cats'." WHERE cid = %d", $_GET['cid']) );
			if ($_GET['all'] == 1) {
				$wpdb->query( $wpdb->prepare("DELETE FROM ".$wpdb->prefix.'symposium_topics'." WHERE topic_category = %d", $_GET['cid']) );
			} else {
				$wpdb->query( $wpdb->prepare("UPDATE ".$wpdb->prefix.'symposium_topics'." SET topic_category = 0 WHERE topic_category = %d", $_GET['cid']) );
			}
		} else {
			echo "<div class='error'><p>".__('You must have at least one category', WPS_TEXT_DOMAIN).".</p></div>";
		}
  	}
 
		// See if the user has posted updated category information
		if( isset($_POST[ 'categories_update' ]) && $_POST[ 'categories_update' ] == 'Y' ) {
			
	   		$range = array_keys($_POST['tid']);
			foreach ($range as $key) {
		
				$tid = $_POST['tid'][$key];
				$topic_category = $_POST['topic_category'][$key];
				$wpdb->query( $wpdb->prepare("UPDATE ".$wpdb->prefix.'symposium_topics'." SET topic_category = ".$topic_category." WHERE tid = %d", $tid) );					
			}
	
			// Put an settings updated message on the screen
			echo "<div class='updated slideaway'><p>".__('Categories saved', WPS_TEXT_DOMAIN)."</p></div>";
	
		}
 	

  	echo '<div class="wrap">';
  	echo '<div id="icon-themes" class="icon32"><br /></div>';
  	echo '<h2>'.sprintf(__('%s Management', WPS_TEXT_DOMAIN), WPS_WL).'</h2><br />';
	__wps__show_manage_tabs_header('categories');
	echo '<div style="float:right">';
	echo '<a href="admin.php?page=symposium_forum">'.__('Go to Forum Options', WPS_TEXT_DOMAIN).'</a><br /><br />';	 
	echo '</div>';
	?> 
	<form method="post" action=""> 

	<table class="widefat">
	<thead>
	<tr>
	<th style="width:40px">ID</th>
	<th style="width:60px"><?php echo __('Parent ID', WPS_TEXT_DOMAIN); ?></th>
	<th><?php echo __('Category Title and Description', WPS_TEXT_DOMAIN); ?></th>
	<th><?php echo __('Permitted Roles', WPS_TEXT_DOMAIN); ?></th>
	<th style="text-align:center"><?php echo __('Topics', WPS_TEXT_DOMAIN); ?></th>
	<th><?php echo __('Order', WPS_TEXT_DOMAIN); ?></th>
	<th><?php echo __('Options', WPS_TEXT_DOMAIN); ?></th>
	<th>&nbsp;</th>
	</tr> 
	</thead>
	<?php	
	$included = __wps__show_forum_children(0, 0, '');

	// Get list of roles
	global $wp_roles;
	$all_roles = $wp_roles->roles;
	
	// Check for categories with incorrect Parent IDs
	$categories = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."symposium_cats ORDER BY cid");
	$shown_header = false;
	if ($categories) {
		foreach ($categories as $category) {

			if (!__wps__inHaystack($included, $category->cid)) {
				
				if (!$shown_header) {
					$shown_header = true;
					?>
					<thead>
					<tr>
					<th style="width:20px"></th>
					<th style="width:60px">&nbsp;</th>
					<th><strong><?php echo __('The following will not be displayed due to Parent ID (update or delete)', WPS_TEXT_DOMAIN); ?>...</strong></th>
					<th>&nbsp;</th>
					<th>&nbsp;</th>
					<th>&nbsp;</th>
					<th>&nbsp;</th>
					<th>&nbsp;</th>
					</tr> 
					</thead>
					<?php
				}
				echo '<tr valign="top">';
				echo '<input name="cid[]" type="hidden" value="'.$category->cid.'" />';
				echo '<td>'.$category->cid.'</td>';
				echo '<td><input name="cat_parent[]" type="text" value="'.stripslashes($category->cat_parent).'" style="width:50px" /></td>';
				echo '<td>';
				echo '<input name="title[]" type="text" value="'.stripslashes($category->title).'" class="regular-text" style="width:150px" /><br />';
				echo '<input name="cat_desc[]" type="text" value="'.stripslashes($category->cat_desc).'" class="regular-text" style="width:150px" />';
				echo '</td>';
				echo '<td>';
				$cat_roles = unserialize($category->level);
				echo '<input type="checkbox" class="wps_forum_cat_'.$category->cid.'" name="level_'.$category->cid.'[]" value="everyone"';
				if (strpos(strtolower($cat_roles), 'everyone,') !== FALSE) {
					echo ' CHECKED';
				}
				echo '> Everyone<br />';
				foreach ($all_roles as $role) {
					echo '<input type="checkbox" class="wps_forum_cat_'.$category->cid.'" name="level_'.$category->cid.'[]" value="'.$role['name'].'"';
					if (strpos(strtolower($cat_roles), strtolower($role['name']).',') !== FALSE) {
						echo ' CHECKED';
					}
					echo '> '.$role['name'].'<br />';
				}				
				echo '<a href="javascript:void(0);" title="'.$category->cid.'" class="symposium_cats_check">'.__('Check/uncheck all', WPS_TEXT_DOMAIN).'</a><br />';
				
				echo '</td>';
				echo '<td style="text-align:center;">';
				echo $wpdb->get_var("SELECT count(*) FROM ".$wpdb->prefix."symposium_topics WHERE topic_category = ".$category->cid);
				echo '</td>';
				echo '<td><input name="listorder[]" type="text" value="'.$category->listorder.'" style="width:50px" /></td>';
				echo '<td>';
				echo '<select name="allow_new[]">';
				echo '<option value="on"';
					if ($category->allow_new == "on") { echo " SELECTED"; }
					echo '>'.__('Yes', WPS_TEXT_DOMAIN).'</option>';
				echo '<option value=""';
					if ($category->allow_new != "on") { echo " SELECTED"; }
					echo '>'.__('No', WPS_TEXT_DOMAIN).'</option>';
				echo '</select>';
				echo '</td>';
				echo '<td>';
				echo '<a class="delete" href="?page=symposium_categories&action=delcid&all=0&cid='.$category->cid.'">'.__('Delete category', WPS_TEXT_DOMAIN).'</a><br />';
				echo '<a class="delete" href="?page=symposium_categories&action=delcid&all=1&cid='.$category->cid.'">'.__('Delete category and posts', WPS_TEXT_DOMAIN).'</a>';
				echo '</td>';
				echo '</tr>';
				
			}
		}
	}
	echo '<tr><td colspan="8">';
	echo sprintf(__('Note: "View forum roles", "Forum new topic roles" and "Forum reply roles" on <a href="%s">forum settings</a> effect the overall forum, the above permitted roles are for view and edit per forum category.', WPS_TEXT_DOMAIN), "admin.php?page=symposium_forum");
	echo '</td></tr>';
	
	?>
	
	<thead>
	<tr>
	<th style="width:20px"></th>
	<th style="width:60px"><?php echo __('Parent ID', WPS_TEXT_DOMAIN); ?></th>
	<th><?php echo __('Add New Category', WPS_TEXT_DOMAIN); ?></th>
	<th>&nbsp;</th>
	<th>&nbsp;</th>
	<th><?php echo __('Order', WPS_TEXT_DOMAIN); ?></th>
	<th><?php echo __('Allow new topics', WPS_TEXT_DOMAIN); ?></th>
	<th>&nbsp;</th>
	</tr> 
	</thead>

	<tr valign="top">
	<td>&nbsp;</td>
	<td><input name="new_parent" type="text" value="0" style="width:50px" /></td>
	<td>
		<input name="new_title" type="text" onclick="javascript:this.value = ''" value="<?php echo __('Add New Category', WPS_TEXT_DOMAIN); ?>..." class="regular-text" style="width:150px" /><br />
		<input name="new_cat_desc" type="text" onclick="javascript:this.value = ''" value="<?php echo __('Optional Description', WPS_TEXT_DOMAIN); ?>..." class="regular-text" style="width:150px" />
	</td>
	<td>&nbsp;</td>
	<td>&nbsp;</td>
	<td>
		<input name="new_listorder" type="text" value="0" style="width:50px" />
	</td>
	<td>
	<input type="checkbox" name="new_allow_new" CHECKED />
	</td>
	<td colspan=2>&nbsp;</td>
	</tr>
	</table> 

	<br /><?php echo __('Default Category', WPS_TEXT_DOMAIN); ?>:
	<?php
	$categories = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix.'symposium_cats ORDER BY listorder');

	if ($categories) {
		echo "<select name='default_category'>";
		foreach ($categories as $category) {
			echo "<option value=".$category->cid;
			if ($category->defaultcat == "on") { echo " SELECTED"; }
			echo ">".$category->title."</option>";
		}
		echo "</select>";
	}	
	?>
	 
	<p class="submit"> 
	<input type="submit" name="Submit" class="button-primary" value="<?php echo __('Save Changes', WPS_TEXT_DOMAIN); ?>" /> 
	</p> 
	
	<p>
	<?php
	echo __('Note:', WPS_TEXT_DOMAIN);
	echo '<li>'.__('choose "Delete category and posts" to delete a category and all topics in that category.', WPS_TEXT_DOMAIN).'</li>';
	echo '<li>'.sprintf(__('if category descriptions are not showing, try resetting your <a href="%s">forum templates</a>.', WPS_TEXT_DOMAIN), "admin.php?page=symposium_templates");
	echo '<span class="__wps__tooltip" title="'.__('The Forum Categories (list) template should include the [category_desc] code<br />to display the description of the forum category.', WPS_TEXT_DOMAIN).'">?</span></li>';
	?>
	<p>
	</form> 
	
	<?php
	__wps__show_manage_tabs_header_end();
	
  	echo '</div>';

} 	
function __wps__inHaystack($haystack, $needle) {
	$haystack = explode(',', $haystack);
	return in_array($needle, $haystack);
}

function __wps__show_forum_children($id, $indent, $list) {
	
	global $wpdb;

	// Get list of roles
	global $wp_roles;
	$all_roles = $wp_roles->roles;
	
	$categories = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."symposium_cats WHERE cat_parent = ".$id." ORDER BY listorder");

	if ($categories) {
		foreach ($categories as $category) {
			
			$list = $list.$category->cid.",";
			
			switch($indent) {
			case 0:
				$style="background-color:#aaf";
				break;
			case 1:
				$style="background-color:#bfb";
				break;
			case 2:
				$style="background-color:#fcc";
				break;
			case 3:
				$style="background-color:#ddd";
				break;
			case 4:
				$style="background-color:#eee";
				break;
			case 5:
				$style="background-color:#fff";
				break;
			default:
				$style="background-color:#fff";
				break;
			}

			echo '<tr valign="top">';
			echo '<input name="cid[]" type="hidden" value="'.$category->cid.'" />';
			echo '<td style="'.$style.'">'.str_repeat("...", $indent).'&nbsp;'.$category->cid.'</td>';
			echo '<td><input name="cat_parent[]" type="text" value="'.stripslashes($category->cat_parent).'" style="width:30px" />';
			echo '<span class="__wps__tooltip" title="'.__('ID of the forum category within which this forum category should appear.<br />Allows you to make a hierarchy of forum categories.', WPS_TEXT_DOMAIN).'">?</span>';
			echo '</td>';
			echo '<td>';
			echo str_repeat("&nbsp;&nbsp;&nbsp;", $indent).'<input name="title[]" type="text" value="'.stripslashes($category->title).'" class="regular-text" style="width:150px" />';
			echo '<span class="__wps__tooltip" title="'.__('Title of the forum category', WPS_TEXT_DOMAIN).'">?</span>';
			echo '<br />';
			echo str_repeat("&nbsp;&nbsp;&nbsp;", $indent).'<input name="cat_desc[]" type="text" value="'.stripslashes($category->cat_desc).'" class="regular-text" style="width:150px" />';
			echo '<span class="__wps__tooltip" title="'.__('Optional description of the forum category', WPS_TEXT_DOMAIN).'">?</span>';

			// Add option for minimum forum rank if in use
			$ranks = explode(';', get_option(WPS_OPTIONS_PREFIX.'_forum_ranks'));
			$using_ranks = $ranks[0] == 'on' ? true : false;
			if ($using_ranks) {
				echo '<br /><br />';
				echo '<table>';
				echo '<tr><td style="border:0px;padding-top:8px">'.sprintf(__('Minimum <a href="%s">rank score</a>', WPS_TEXT_DOMAIN), 'admin.php?page=symposium_forum#ranks') . ':</td>';
				echo '<td style="border:0px"><input name="min_rank[]" type="text" value="'.$category->min_rank.'" style="width:50px;" /><span class="__wps__tooltip" title="'.__('Check Forum Rank scores via the link. A user must have at least<br />the score entered here to view the forum category.', WPS_TEXT_DOMAIN).'">?</span></td></tr>';
				echo '</table>';
			}
			echo '</td>';
			echo '<td>';
			$cat_roles = unserialize($category->level);
			echo '<input type="checkbox" class="wps_forum_cat_'.$category->cid.'" name="level_'.$category->cid.'[]" value="everyone"';
			if (strpos(strtolower($cat_roles), 'everyone,') !== FALSE) {
				echo ' CHECKED';
			}
			echo '> Everyone<br />';
			foreach ($all_roles as $role) {
				echo '<input type="checkbox" class="wps_forum_cat_'.$category->cid.'" name="level_'.$category->cid.'[]" value="'.$role['name'].'"';
				if (strpos(strtolower($cat_roles), strtolower($role['name']).',') !== FALSE) {
					echo ' CHECKED';
				}
				echo '> '.$role['name'].'<br />';
			}
			
			echo '<a href="javascript:void(0);" title="'.$category->cid.'" class="symposium_cats_check">'.__('Check/uncheck all', WPS_TEXT_DOMAIN).'</a><br />';
			echo '</td>';
			echo '<td style="text-align:center">';
			echo $wpdb->get_var("SELECT count(*) FROM ".$wpdb->prefix."symposium_topics WHERE topic_parent = 0 AND topic_category = ".$category->cid);
			echo '</td>';
			echo '<td><input name="listorder[]" type="text" value="'.$category->listorder.'" style="width:50px" />';
			echo '<span class="__wps__tooltip" title="'.__('Set the order of the categories - use numeric values.', WPS_TEXT_DOMAIN).'">?</span>';
			echo '</td>';
			echo '<td>';
			echo __('Allow new topics?', WPS_TEXT_DOMAIN).'<br />';
			echo '<select name="allow_new[]">';
			echo '<option value="on"';
				if ($category->allow_new == "on") { echo " SELECTED"; }
				echo '>'.__('Yes', WPS_TEXT_DOMAIN).'</option>';
			echo '<option value=""';
				if ($category->allow_new != "on") { echo " SELECTED"; }
				echo '>'.__('No', WPS_TEXT_DOMAIN).'</option>';
			echo '</select>';
			echo '<span class="__wps__tooltip" title="'.__('Should users be able to start<br />new topics in this category?<br />Administrators can always<br />create a new topic.', WPS_TEXT_DOMAIN).'">?</span>';
			echo '<br />'.__('Hide breadcrumbs?', WPS_TEXT_DOMAIN).'<br />';
			echo '<select name="hide_breadcrumbs[]">';
			echo '<option value="on"';
				if ($category->hide_breadcrumbs == "on") { echo " SELECTED"; }
				echo '>'.__('Yes', WPS_TEXT_DOMAIN).'</option>';
			echo '<option value=""';
				if ($category->hide_breadcrumbs != "on") { echo " SELECTED"; }
				echo '>'.__('No', WPS_TEXT_DOMAIN).'</option>';
			echo '</select>';
			echo '<span class="__wps__tooltip" title="'.__('Hide the forum breadcrumbs for this category?', WPS_TEXT_DOMAIN).'">?</span>';
			echo '<br />'.__('Exclude from forum?', WPS_TEXT_DOMAIN).'<br />';
			echo '<select name="hide_main[]">';
			echo '<option value="on"';
				if ($category->hide_main == "on") { echo " SELECTED"; }
				echo '>'.__('Yes', WPS_TEXT_DOMAIN).'</option>';
			echo '<option value=""';
				if ($category->hide_main != "on") { echo " SELECTED"; }
				echo '>'.__('No', WPS_TEXT_DOMAIN).'</option>';
			echo '</select>';
			echo '<span class="__wps__tooltip" title="'.__('Exclude from the main forum?', WPS_TEXT_DOMAIN).'">?</span>';
			echo '</td>';
			echo '</td>';
			echo '<td>';
			echo '<a class="delete" href="?page=symposium_categories&action=delcid&all=0&cid='.$category->cid.'">'.__('Delete category', WPS_TEXT_DOMAIN).'</a><br />';
			echo '<a class="delete" href="?page=symposium_categories&action=delcid&all=1&cid='.$category->cid.'">'.__('Delete category and posts', WPS_TEXT_DOMAIN).'</a>';
			echo '</td>';
			echo '</tr>';

			$list = __wps__show_forum_children($category->cid, $indent+1, $list);
	
		}
	}
	
	return $list;
}

function __wps__plugin_styles() {
	
	global $wpdb;

	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.', WPS_TEXT_DOMAIN) );
	}

  	echo '<div class="wrap">';
  		echo '<div id="icon-themes" class="icon32"><br /></div>';
	  	echo '<h2>'.__('Styles', WPS_TEXT_DOMAIN).'</h2>';

		// See if the user has saved CSS
		if( isset($_POST[ 'symposium_update' ]) && $_POST[ 'symposium_update' ] == 'CSS' ) {
			$css = str_replace(chr(13), "[]", $_POST['css']);
			update_option(WPS_OPTIONS_PREFIX.'_css', $css);
  		}

		// See if the user has saved responsive CSS
		if( isset($_POST[ 'symposium_update' ]) && $_POST[ 'symposium_update' ] == 'responsive' ) {
			$css = str_replace(chr(13), "[]", $_POST['css']);
			update_option(WPS_OPTIONS_PREFIX.'_responsive', $css);
  		}

		// See if the user is deleting a style
		if ( isset($_GET[ 'delstyle' ]) ) {
			$sql = "DELETE FROM ".$wpdb->prefix."symposium_styles WHERE sid = %d";
			if ( $wpdb->query( $wpdb->prepare( $sql, $_GET[ 'delstyle' ])) ) {
				echo "<div class='updated slideaway'><p>".__('Template Deleted', WPS_TEXT_DOMAIN)."</p></div>";
			}
		}	
		// See if the user has selected a template
		if( isset($_POST[ 'sid' ]) ) {
			$style = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix.'symposium_styles'." WHERE sid = ".$_POST['sid']);
			if ($style) {
				update_option(WPS_OPTIONS_PREFIX.'_use_styles', 'on');
				update_option(WPS_OPTIONS_PREFIX.'_categories_background', $style->__wps__categories_background);
				update_option(WPS_OPTIONS_PREFIX.'_categories_color', $style->categories_color);
				update_option(WPS_OPTIONS_PREFIX.'_border_radius', $style->border_radius);
				update_option(WPS_OPTIONS_PREFIX.'_main_background', $style->main_background);
				update_option(WPS_OPTIONS_PREFIX.'_bigbutton_background', $style->bigbutton_background);
				update_option(WPS_OPTIONS_PREFIX.'_bigbutton_background_hover', $style->bigbutton_background_hover);
				update_option(WPS_OPTIONS_PREFIX.'_bigbutton_color', $style->bigbutton_color);
				update_option(WPS_OPTIONS_PREFIX.'_bigbutton_color_hover', $style->bigbutton_color_hover);
				update_option(WPS_OPTIONS_PREFIX.'_bg_color_1', $style->bg_color_1);
				update_option(WPS_OPTIONS_PREFIX.'_bg_color_2', $style->bg_color_2);
				update_option(WPS_OPTIONS_PREFIX.'_bg_color_3', $style->bg_color_3);
				update_option(WPS_OPTIONS_PREFIX.'_row_border_style', $style->row_border_style);
				update_option(WPS_OPTIONS_PREFIX.'_row_border_size', $style->row_border_size);
				update_option(WPS_OPTIONS_PREFIX.'_replies_border_size', $style->replies_border_size);
				update_option(WPS_OPTIONS_PREFIX.'_table_rollover', $style->table_rollover);
				update_option(WPS_OPTIONS_PREFIX.'_table_border', $style->table_border);
				update_option(WPS_OPTIONS_PREFIX.'_text_color', $style->text_color);
				update_option(WPS_OPTIONS_PREFIX.'_text_color_2', $style->text_color_2);
				update_option(WPS_OPTIONS_PREFIX.'_link', $style->link);
				update_option(WPS_OPTIONS_PREFIX.'_underline', $style->underline);
				update_option(WPS_OPTIONS_PREFIX.'_link_hover', $style->link_hover);
				update_option(WPS_OPTIONS_PREFIX.'_label', $style->label);
				update_option(WPS_OPTIONS_PREFIX.'_fontfamily', $style->fontfamily);
				update_option(WPS_OPTIONS_PREFIX.'_fontsize', $style->fontsize);
				update_option(WPS_OPTIONS_PREFIX.'_headingsfamily', $style->headingsfamily);
				update_option(WPS_OPTIONS_PREFIX.'_headingssize', $style->headingssize);

				$style_save_as = $style->title;
				$style_id = $style->sid;

				// Put an settings updated message on the screen
				echo "<div class='updated slideaway'><p>".__('Template Applied', WPS_TEXT_DOMAIN)."</p></div>";
			} else {
				echo "<div class='error'><p>".__('Template Not Found', WPS_TEXT_DOMAIN)."</p></div>";
			}
		}

		// See if the user has posted us some information
		if( isset($_POST[ 'symposium_update' ]) && $_POST[ 'symposium_update' ] == 'Y' ) {

			update_option(WPS_OPTIONS_PREFIX.'_use_styles', isset($_POST['use_styles']) ? $_POST['use_styles'] : '');
			update_option(WPS_OPTIONS_PREFIX.'_categories_background', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['__wps__categories_background']));
			update_option(WPS_OPTIONS_PREFIX.'_categories_color', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['categories_color']));
			update_option(WPS_OPTIONS_PREFIX.'_border_radius', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['border_radius']));
			update_option(WPS_OPTIONS_PREFIX.'_bigbutton_background', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['bigbutton_background']));
			update_option(WPS_OPTIONS_PREFIX.'_bigbutton_background_hover', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['bigbutton_background_hover']));
			update_option(WPS_OPTIONS_PREFIX.'_bigbutton_color', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['bigbutton_color']));
			update_option(WPS_OPTIONS_PREFIX.'_bigbutton_color_hover', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['bigbutton_color_hover']));
			update_option(WPS_OPTIONS_PREFIX.'_bg_color_1', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['bg_color_1']));
			update_option(WPS_OPTIONS_PREFIX.'_bg_color_2', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['bg_color_2']));
			update_option(WPS_OPTIONS_PREFIX.'_bg_color_3', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['bg_color_3']));
			update_option(WPS_OPTIONS_PREFIX.'_row_border_style', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['row_border_style']));
			update_option(WPS_OPTIONS_PREFIX.'_row_border_size', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['row_border_size']));
			update_option(WPS_OPTIONS_PREFIX.'_table_rollover', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['table_rollover']));
			update_option(WPS_OPTIONS_PREFIX.'_table_border', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['table_border']));
			update_option(WPS_OPTIONS_PREFIX.'_replies_border_size', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['replies_border_size']));
			update_option(WPS_OPTIONS_PREFIX.'_text_color', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['text_color']));
			update_option(WPS_OPTIONS_PREFIX.'_text_color_2', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['text_color_2']));
			update_option(WPS_OPTIONS_PREFIX.'_link', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['link']));
			update_option(WPS_OPTIONS_PREFIX.'_underline', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['underline']));
			update_option(WPS_OPTIONS_PREFIX.'_link_hover', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['link_hover']));
			update_option(WPS_OPTIONS_PREFIX.'_label', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['label']));
			update_option(WPS_OPTIONS_PREFIX.'_closed_opacity', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['closed_opacity']));
			update_option(WPS_OPTIONS_PREFIX.'_fontfamily', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['fontfamily']));
			update_option(WPS_OPTIONS_PREFIX.'_fontsize', str_replace("px", "", strtolower(preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST[ 'fontsize' ]))));
			update_option(WPS_OPTIONS_PREFIX.'_headingsfamily', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['headingsfamily']));
			update_option(WPS_OPTIONS_PREFIX.'_headingssize', str_replace("px", "", strtolower(preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST[ 'headingssize' ]))));
			update_option(WPS_OPTIONS_PREFIX.'_main_background', preg_replace('/[^,;a-zA-Z0-9#_-]/','',$_POST['main_background']));
			
			if( $_POST[ 'style_save_as' ] != '' ) {

				// Delete previous version if it exists
				$wpdb->query( $wpdb->prepare( "DELETE FROM ".$wpdb->prefix."symposium_styles WHERE title = %s", $_POST['style_save_as'] ) );

				// Save new template
			   	$rows_affected = $wpdb->insert( $wpdb->prefix."symposium_styles", array( 
				'title' => $_POST['style_save_as'], 
				'border_radius' => $_POST['border_radius'],
				'bigbutton_background' => $_POST['bigbutton_background'], 
				'bigbutton_background_hover' => $_POST['bigbutton_background_hover'],
				'bigbutton_color' => $_POST['bigbutton_color'], 
				'bigbutton_color_hover' => $_POST['bigbutton_color_hover'], 
				'bg_color_1' => $_POST['bg_color_1'], 
				'bg_color_2' => $_POST['bg_color_2'],
				'bg_color_3' => $_POST['bg_color_3'], 
				'table_rollover' => $_POST['table_rollover'], 
				'table_border' => $_POST['table_border'], 
				'row_border_style' => $_POST['row_border_style'], 
				'row_border_size' => $_POST['row_border_size'], 
				'replies_border_size' => $_POST['replies_border_size'], 
				'__wps__categories_background' => $_POST['__wps__categories_background'], 
				'categories_color' => $_POST['categories_color'], 
				'text_color' => $_POST['text_color'], 
				'text_color_2' => $_POST['text_color_2'], 
				'link' => $_POST['link'], 
				'underline' => $_POST['underline'], 
				'link_hover' => $_POST['link_hover'], 
				'label' => $_POST['label'],
				'main_background' => $_POST['main_background'],
				'closed_opacity' => $_POST['closed_opacity'],
				'fontfamily' => stripslashes($_POST['fontfamily']),
				'fontsize' => str_replace("px", "", strtolower($_POST[ 'fontsize' ])),
				'headingsfamily' => stripslashes($_POST['headingsfamily']),
				'headingssize' => str_replace("px", "", strtolower($_POST[ 'headingssize' ]))
				) );	
						
				// Put an settings updated message on the screen
				echo "<div class='updated slideaway'><p>".__('Template Saved', WPS_TEXT_DOMAIN)."</p></div>";
				
				$style_save_as = $_POST[ 'style_save_as' ];	   
			} else {
				$style_save_as = '';	   
			}

		}

		
		// Start tabs
		include_once(dirname(__FILE__).'/show_tabs_style.php');

		// View
		$styles_active = 'active';
		$css_active = 'inactive';
		$responsive_active = 'inactive';
		$view = "styles";
		if (isset($_GET['view']) && $_GET['view'] == 'css') {
			$styles_active = 'inactive';
			$responsive_active = 'inactive';
			$css_active = 'active';
			$view = "css";
		}
		if (isset($_GET['view']) && $_GET['view'] == 'responsive') {
			$styles_active = 'inactive';
			$responsive_active = 'active';
			$css_active = 'inactive';
			$view = "responsive";
		}
	
		echo '<div class="__wps__wrapper" style="margin-top:15px">';
	
			echo '<div id="mail_tabs">';
			echo '<div class="mail_tab nav-tab-'.$styles_active.'"><a href="admin.php?page=symposium_styles&view=styles" class="nav-tab-'.$styles_active.'-link">'.__('Styles', WPS_TEXT_DOMAIN).'</a></div>';
			echo '<div class="mail_tab nav-tab-'.$css_active.'"><a href="admin.php?page=symposium_styles&view=css" class="nav-tab-'.$css_active.'-link">'.__('CSS', WPS_TEXT_DOMAIN).'</a></div>';
			echo '<div class="mail_tab nav-tab-'.$responsive_active.'" style="width:100px"><a href="admin.php?page=symposium_styles&view=responsive" class="nav-tab-'.$responsive_active.'-link">'.__('Responsive', WPS_TEXT_DOMAIN).'</a></div>';
			echo '</div>';
		
			echo '<div id="mail-main">';

				// Responsive
				if ($view == "responsive") {

					$css = get_option(WPS_OPTIONS_PREFIX.'_responsive');
					$css = str_replace("[]", chr(13), stripslashes($css));

					echo '<form method="post" action=""> ';
					echo '<input type="submit" class="button-primary" style="float:right;" value="'.__('Save', WPS_TEXT_DOMAIN).'">';

					echo __('These styles affect output when your site is viewed on tablets and phones.', WPS_TEXT_DOMAIN);

					echo '<input type="hidden" name="symposium_update" value="responsive">';

					echo '<table class="widefat" style="clear: both; margin-top:25px">';
					echo '<tbody>';
					echo '<tr>';
					echo '<td>';
					echo '<textarea id="css" name="css" style="width:100%;height: 600px;">';
					echo $css;
					echo '</textarea>';
					echo '</td>';
					echo '</tr>';
					echo '</tbody>';
					echo '</table>';
					
					echo '</form>';
					
				}

				// CSS
				if ($view == "css") {

					$css = get_option(WPS_OPTIONS_PREFIX.'_css');
					$css = str_replace("[]", chr(13), stripslashes($css));

					echo '<form method="post" action=""> ';
					echo __('Styles entered here will take priority over linked stylesheets but not the <a href="admin.php?page=symposium_styles&view=responsive">responsive styles</a>.', WPS_TEXT_DOMAIN);
					echo '<input type="submit" class="button-primary" style="float:right;" value="'.__('Save', WPS_TEXT_DOMAIN).'">';

					echo '<input type="hidden" name="symposium_update" value="CSS">';

					echo '<table class="widefat" style="clear: both; margin-top:25px">';
					echo '<tbody>';
					echo '<tr>';
					echo '<td style="width:60%">';
					echo '<textarea id="css" name="css" style="width:100%;height: 600px;">';
					echo $css;
					echo '</textarea>';
					echo '</td>';
					echo '<td>';
						echo '<table class="widefat">';
						echo '<tr>';
						echo '<td style="font-weight:bold">'.__('Notes', WPS_TEXT_DOMAIN).'</td>';
						echo '</tr>';
						echo '<tbody>';
						echo '<tr><td>';
						echo __('To speed things up, why not open a new window and refresh it each time you save a change here?', WPS_TEXT_DOMAIN);
						echo '</td></tr>';
						echo '<tr><td>';
						echo sprintf(__('CSS will over-ride the %s Styles (other tab), but your theme may take priority.', WPS_TEXT_DOMAIN), WPS_WL);
						echo '</td></tr>';
						echo '<tr><td>';
						echo __('If a style doesn\'t apply, try putting !important after it. eg: color:red !important;', WPS_TEXT_DOMAIN);
						echo '</td></tr>';
						echo '<tr><td>';
						echo __('Refer to www.wpsymposium.com/2013/01/styles for more help and examples.', WPS_TEXT_DOMAIN);
						echo '</td></tr>';
						echo '</tbody>';
						echo '</table>';
					echo '</td>';
					echo '</tr>';
					echo '</tbody>';
					echo '</table>';
					
					echo '</form>';
					
				}
			
				// STYLES
				if ($view == "styles") {
			
						?> 

					<form method="post" action=""> 
					<input type="hidden" name="symposium_update" value="Y">

					<table class="form-table __wps__admin_table"> 

					<tr valign="top"> 
					<td scope="row"><label for="use_styles"><?php echo __('Use Styles?', WPS_TEXT_DOMAIN); ?></label></td>
					<td>
					<input type="checkbox" name="use_styles" id="use_styles" <?php if (get_option(WPS_OPTIONS_PREFIX.'_use_styles') == "on") { echo "CHECKED"; } ?>/>
					<span class="description"><?php echo __('Enable to use styles on this page, disable to rely on stylesheet', WPS_TEXT_DOMAIN); ?></span></td> 
					</tr> 
	
					<tr valign="top"> 
					<td scope="row"><label for="fontfamily"><?php echo __('Body Text', WPS_TEXT_DOMAIN); ?></label></td> 
					<td><input name="fontfamily" type="text" id="fontfamily" value="<?php echo stripslashes(get_option(WPS_OPTIONS_PREFIX.'_fontfamily')); ?>"/> 
					<span class="description"><?php echo __('Font family for body text', WPS_TEXT_DOMAIN); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="fontsize"></label></td> 
					<td><input name="fontsize" type="text" id="fontsize" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_fontsize'); ?>"/> 
					<span class="description"><?php echo __('Font size in pixels for body text', WPS_TEXT_DOMAIN); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="headingsfamily"><?php echo __('Headings', WPS_TEXT_DOMAIN); ?></label></td> 
					<td><input name="headingsfamily" type="text" id="headingsfamily" value="<?php echo stripslashes(get_option(WPS_OPTIONS_PREFIX.'_headingsfamily')); ?>"/> 
					<span class="description"><?php echo __('Font family for headings and large text', WPS_TEXT_DOMAIN); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="headingssize"></label></td> 
					<td><input name="headingssize" type="text" id="headingssize" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_headingssize'); ?>"/> 
					<span class="description"><?php echo __('Font size in pixels for headings and large text', WPS_TEXT_DOMAIN); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="main_background"><?php echo __('Main background', WPS_TEXT_DOMAIN); ?></label></td> 
					<td><input name="main_background" type="text" id="main_background" class="wps_pickColor" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_main_background'); ?>"  /> 
					<div style="position: absolute; margin-left:130px; margin-top:-110px;" class="colorpicker"></div>

					<span class="description"><?php echo __('Main background colour (for example, new/edit forum topic/post)', WPS_TEXT_DOMAIN); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="label"><?php echo __('Labels', WPS_TEXT_DOMAIN); ?></label></td> 
					<td><input name="label" type="text" id="label" class="wps_pickColor" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_label'); ?>"  /> 
					<div style="position: absolute; margin-left:130px; margin-top:-110px;" class="colorpicker"></div>
					<span class="description"><?php echo __('Colour of text labels outside forum areas', WPS_TEXT_DOMAIN); ?></span></td> 
					</tr> 
	
					<tr valign="top"> 
					<td scope="row"><label for="text_color"><?php echo __('Text Colour', WPS_TEXT_DOMAIN); ?></label></td> 
					<td><input name="text_color" type="text" id="text_color" class="wps_pickColor" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_text_color'); ?>"  /> 
					<div style="position: absolute; margin-left:130px; margin-top:-110px;" class="colorpicker"></div>
					<span class="description"><?php echo __('Primary Text Colour', WPS_TEXT_DOMAIN); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="text_color_2"></label></td> 
					<td><input name="text_color_2" type="text" id="text_color_2" class="wps_pickColor" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_text_color_2'); ?>"  /> 
					<div style="position: absolute; margin-left:130px; margin-top:-110px;" class="colorpicker"></div>
					<span class="description"><?php echo __('Secondary Text Colour', WPS_TEXT_DOMAIN); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="link"><?php echo __('Links', WPS_TEXT_DOMAIN); ?></label></td> 
					<td><input name="link" type="text" id="link" class="wps_pickColor" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_link'); ?>"  /> 
					<div style="position: absolute; margin-left:130px; margin-top:-110px;" class="colorpicker"></div>
					<span class="description"><?php echo __('Link Colour', WPS_TEXT_DOMAIN); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="link_hover"</label></td> 
					<td><input name="link_hover" type="text" id="link_hover" class="wps_pickColor" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_link_hover'); ?>"  /> 
					<div style="position: absolute; margin-left:130px; margin-top:-110px;" class="colorpicker"></div>
					<span class="description"><?php echo __('Link Colour on mouse hover', WPS_TEXT_DOMAIN); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="underline"><?php echo __('Underlined?', WPS_TEXT_DOMAIN); ?></label></td> 
					<td>
					<select name="underline" id="underline"> 
						<option <?php if ( get_option(WPS_OPTIONS_PREFIX.'_underline')=='') { echo "selected='selected'"; } ?> value=''><?php echo __('No', WPS_TEXT_DOMAIN); ?></option> 
						<option <?php if ( get_option(WPS_OPTIONS_PREFIX.'_underline')=='on') { echo "selected='selected'"; } ?> value='on'><?php echo __('Yes', WPS_TEXT_DOMAIN); ?></option> 
					</select> 
					<span class="description"><?php echo __('Whether links are underlined or not', WPS_TEXT_DOMAIN); ?></span></td> 
					</tr> 
			
					<tr valign="top"> 
					<td scope="row"><label for="border_radius"><?php echo __('Corners', WPS_TEXT_DOMAIN); ?></label></td> 
					<td>
					<select name="border_radius" id="border_radius"> 
						<option <?php if ( get_option(WPS_OPTIONS_PREFIX.'_border_radius')=='0') { echo "selected='selected'"; } ?> value='0'>0 pixels</option> 
						<option <?php if ( get_option(WPS_OPTIONS_PREFIX.'_border_radius')=='1') { echo "selected='selected'"; } ?> value='1'>1 pixels</option> 
						<option <?php if ( get_option(WPS_OPTIONS_PREFIX.'_border_radius')=='2') { echo "selected='selected'"; } ?> value='2'>2 pixels</option> 
						<option <?php if ( get_option(WPS_OPTIONS_PREFIX.'_border_radius')=='3') { echo "selected='selected'"; } ?> value='3'>3 pixels</option> 
						<option <?php if ( get_option(WPS_OPTIONS_PREFIX.'_border_radius')=='4') { echo "selected='selected'"; } ?> value='4'>4 pixels</option> 
						<option <?php if ( get_option(WPS_OPTIONS_PREFIX.'_border_radius')=='5') { echo "selected='selected'"; } ?> value='5'>5 pixels</option> 
						<option <?php if ( get_option(WPS_OPTIONS_PREFIX.'_border_radius')=='6') { echo "selected='selected'"; } ?> value='6'>6 pixels</option> 
						<option <?php if ( get_option(WPS_OPTIONS_PREFIX.'_border_radius')=='7') { echo "selected='selected'"; } ?> value='7'>7 pixels</option> 
						<option <?php if ( get_option(WPS_OPTIONS_PREFIX.'_border_radius')=='8') { echo "selected='selected'"; } ?> value='8'>8 pixels</option> 
						<option <?php if ( get_option(WPS_OPTIONS_PREFIX.'_border_radius')=='9') { echo "selected='selected'"; } ?> value='9'>9 pixels</option> 
						<option <?php if ( get_option(WPS_OPTIONS_PREFIX.'_border_radius')=='10') { echo "selected='selected'"; } ?> value='10'>10 pixels</option> 
						<option <?php if ( get_option(WPS_OPTIONS_PREFIX.'_border_radius')=='11') { echo "selected='selected'"; } ?> value='11'>11 pixels</option> 
						<option <?php if ( get_option(WPS_OPTIONS_PREFIX.'_border_radius')=='12') { echo "selected='selected'"; } ?> value='12'>12 pixels</option> 
						<option <?php if ( get_option(WPS_OPTIONS_PREFIX.'_border_radius')=='13') { echo "selected='selected'"; } ?> value='13'>13 pixels</option> 
						<option <?php if ( get_option(WPS_OPTIONS_PREFIX.'_border_radius')=='14') { echo "selected='selected'"; } ?> value='14'>14 pixels</option> 
						<option <?php if ( get_option(WPS_OPTIONS_PREFIX.'_border_radius')=='15') { echo "selected='selected'"; } ?> value='15'>15 pixels</option> 
					</select> 
					<span class="description"><?php echo __('Rounded Corner radius (not supported in all browsers)', WPS_TEXT_DOMAIN); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="bigbutton_background"><?php echo __('Buttons', WPS_TEXT_DOMAIN); ?></label></td> 
					<td><input name="bigbutton_background" type="text" id="bigbutton_background" class="wps_pickColor" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_bigbutton_background'); ?>"  /> 
					<div style="position: absolute; margin-left:130px; margin-top:-110px;" class="colorpicker"></div>
					<span class="description"><?php echo __('Background Colour', WPS_TEXT_DOMAIN); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="bigbutton_background_hover"></label></td> 
					<td><input name="bigbutton_background_hover" type="text" id="bigbutton_background_hover" class="wps_pickColor" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_bigbutton_background_hover'); ?>"  /> 
					<div style="position: absolute; margin-left:130px; margin-top:-110px;" class="colorpicker"></div>
					<span class="description"><?php echo __('Background Colour on mouse hover', WPS_TEXT_DOMAIN); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="bigbutton_color"></label></td> 
					<td><input name="bigbutton_color" type="text" id="bigbutton_color" class="wps_pickColor" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_bigbutton_color'); ?>"  /> 
					<div style="position: absolute; margin-left:130px; margin-top:-110px;" class="colorpicker"></div>
					<span class="description"><?php echo __('Text Colour', WPS_TEXT_DOMAIN); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="bigbutton_color_hover"></label></td> 
					<td><input name="bigbutton_color_hover" type="text" id="bigbutton_color_hover" class="wps_pickColor" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_bigbutton_color_hover'); ?>"  /> 
					<div style="position: absolute; margin-left:130px; margin-top:-110px;" class="colorpicker"></div>
					<span class="description"><?php echo __('Text Colour on mouse hover', WPS_TEXT_DOMAIN); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="bg_color_1"><?php echo __('Tables', WPS_TEXT_DOMAIN); ?></label></td> 
					<td><input name="bg_color_1" type="text" id="bg_color_1" class="wps_pickColor" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_bg_color_1'); ?>"  /> 
					<div style="position: absolute; margin-left:130px; margin-top:-110px;" class="colorpicker"></div>
					<span class="description"><?php echo __('Primary Colour', WPS_TEXT_DOMAIN); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="bg_color_2"></label></td> 
					<td><input name="bg_color_2" type="text" id="bg_color_2" class="wps_pickColor" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_bg_color_2'); ?>"  /> 
					<div style="position: absolute; margin-left:130px; margin-top:-110px;" class="colorpicker"></div>
					<span class="description"><?php echo __('Row Colour', WPS_TEXT_DOMAIN); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="bg_color_3"></label></td> 
					<td><input name="bg_color_3" type="text" id="bg_color_3" class="wps_pickColor" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_bg_color_3'); ?>"  /> 
					<div style="position: absolute; margin-left:130px; margin-top:-110px;" class="colorpicker"></div>
					<span class="description"><?php echo __('Alternative Row Colour', WPS_TEXT_DOMAIN); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="table_rollover"></label></td> 
					<td><input name="table_rollover" type="text" id="table_rollover" class="wps_pickColor" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_table_rollover'); ?>"  /> 
					<div style="position: absolute; margin-left:130px; margin-top:-110px;" class="colorpicker"></div>
					<span class="description"><?php echo __('Row colour on mouse hover', WPS_TEXT_DOMAIN); ?></span></td> 
					</tr> 
		
					<tr valign="top"> 
					<td scope="row"><label for="table_border"></label></td> 
					<td>
					<select name="table_border" id="table_border"> 
						<option <?php if ( get_option(WPS_OPTIONS_PREFIX.'_table_border')=='0') { echo "selected='selected'"; } ?> value='0'>0 pixels</option> 
						<option <?php if ( get_option(WPS_OPTIONS_PREFIX.'_table_border')=='1') { echo "selected='selected'"; } ?> value='1'>1 pixels</option> 
						<option <?php if ( get_option(WPS_OPTIONS_PREFIX.'_table_border')=='2') { echo "selected='selected'"; } ?> value='2'>2 pixels</option> 
						<option <?php if ( get_option(WPS_OPTIONS_PREFIX.'_table_border')=='3') { echo "selected='selected'"; } ?> value='3'>3 pixels</option> 
					</select> 
					<span class="description"><?php echo __('Border Size', WPS_TEXT_DOMAIN); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="row_border_style"><?php echo __('Table/Rows', WPS_TEXT_DOMAIN); ?></label></td> 
					<td>
					<select name="row_border_style" id="row_border_styledefault_role"> 
						<option <?php if ( get_option(WPS_OPTIONS_PREFIX.'_row_border_style')=='dotted') { echo "selected='selected'"; } ?> value='dotted'><?php echo __('Dotted', WPS_TEXT_DOMAIN); ?></option> 
						<option <?php if ( get_option(WPS_OPTIONS_PREFIX.'_row_border_style')=='dashed') { echo "selected='selected'"; } ?> value='dashed'><?php echo __('Dashed', WPS_TEXT_DOMAIN); ?></option> 
						<option <?php if ( get_option(WPS_OPTIONS_PREFIX.'_row_border_style')=='solid') { echo "selected='selected'"; } ?> value='solid'><?php echo __('Solid', WPS_TEXT_DOMAIN); ?></option> 
					</select> 
					<span class="description"><?php echo __('Border style between rows', WPS_TEXT_DOMAIN); ?></span></td> 
					</tr> 
		
					<tr valign="top"> 
					<td scope="row"><label for="row_border_size"></label></td> 
					<td>
					<select name="row_border_size" id="row_border_size"> 
						<option <?php if ( get_option(WPS_OPTIONS_PREFIX.'_row_border_size')=='0') { echo "selected='selected'"; } ?> value='0'>0 pixels</option> 
						<option <?php if ( get_option(WPS_OPTIONS_PREFIX.'_row_border_size')=='1') { echo "selected='selected'"; } ?> value='1'>1 pixels</option> 
						<option <?php if ( get_option(WPS_OPTIONS_PREFIX.'_row_border_size')=='2') { echo "selected='selected'"; } ?> value='2'>2 pixels</option> 
						<option <?php if ( get_option(WPS_OPTIONS_PREFIX.'_row_border_size')=='3') { echo "selected='selected'"; } ?> value='3'>3 pixels</option> 
					</select> 
					<span class="description"><?php echo __('Border size between rows', WPS_TEXT_DOMAIN); ?></span></td> 
					</tr> 
		
					<tr valign="top"> 
					<td scope="row"><label for="replies_border_size"><?php echo __('Other borders', WPS_TEXT_DOMAIN); ?></label></td> 
					<td>
					<select name="replies_border_size" id="replies_border_size"> 
						<option <?php if ( get_option(WPS_OPTIONS_PREFIX.'_replies_border_size')=='0') { echo "selected='selected'"; } ?> value='0'>0 pixels</option> 
						<option <?php if ( get_option(WPS_OPTIONS_PREFIX.'_replies_border_size')=='1') { echo "selected='selected'"; } ?> value='1'>1 pixels</option> 
						<option <?php if ( get_option(WPS_OPTIONS_PREFIX.'_replies_border_size')=='2') { echo "selected='selected'"; } ?> value='2'>2 pixels</option> 
						<option <?php if ( get_option(WPS_OPTIONS_PREFIX.'_replies_border_size')=='3') { echo "selected='selected'"; } ?> value='3'>3 pixels</option> 
					</select> 
					<span class="description"><?php echo __('For new topics/replies and topic replies', WPS_TEXT_DOMAIN); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="__wps__categories_background"><?php echo __('Miscellaneous', WPS_TEXT_DOMAIN); ?></label></td> 
					<td><input name="__wps__categories_background" type="text" id="__wps__categories_background" class="wps_pickColor" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_categories_background'); ?>"  /> 
					<div style="position: absolute; margin-left:130px; margin-top:-110px;" class="colorpicker"></div>
					<span class="description"><?php echo __('Background colour of, for example, current category', WPS_TEXT_DOMAIN); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td scope="row"><label for="categories_color"></label></td> 
					<td><input name="categories_color" type="text" id="categories_color" class="wps_pickColor" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_categories_color'); ?>"  /> 
					<div style="position: absolute; margin-left:130px; margin-top:-110px;" class="colorpicker"></div>
					<span class="description"><?php echo __('Text Colour', WPS_TEXT_DOMAIN); ?></span></td> 
					</tr> 

					<tr valign="top"> 
					<td colspan="2"><h3><?php echo __('Forum Styles', WPS_TEXT_DOMAIN); ?></h3></td> 
					</tr> 
	
					<tr valign="top"> 
					<td scope="row"><label for="closed_opacity"><?php echo __('Closed topics', WPS_TEXT_DOMAIN); ?></label></td> 
					<td><input name="closed_opacity" type="text" id="closed_opacity" value="<?php echo get_option(WPS_OPTIONS_PREFIX.'_closed_opacity'); ?>"  /> 
					<?php
					$closed_word = get_option(WPS_OPTIONS_PREFIX.'_closed_word');
					?>
					<span class="description"><?php echo sprintf(__('Opacity of topics with {%s} in the subject (between 0.0 and 1.0)', WPS_TEXT_DOMAIN), $closed_word); ?></span></td> 
					</tr> 

					</table> 
					<br />
	 
					<h2><?php echo __('Style Templates', WPS_TEXT_DOMAIN); ?></h2>
						
					<p><?php echo __('To save as a new style template, enter a name below, otherwise leave blank.', WPS_TEXT_DOMAIN); ?></p>

					<p>
					<?php echo __('Save as:', WPS_TEXT_DOMAIN); ?>
					<input type='text' id='style_save_as' name='style_save_as' value='<?php if (isset($style_save_as)) { echo str_replace("'", "&apos;", stripslashes($style_save_as)); } ?>' />
					<input type="submit" name="Submit" class="button-primary" value="<?php echo __('Save', WPS_TEXT_DOMAIN) ?>" /> 
					</p>
					</form>
						
					<?php
					$styles_lib = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix.'symposium_styles ORDER BY title');
					if ($styles_lib) {
						
						echo '<table class="widefat" style="width:450px">';
						echo '<thead>';
						echo '<tr>';
						echo '<th style="font-size:1.2em">'.__('Load Style Template', WPS_TEXT_DOMAIN).'</th>';
						echo '<th style="font-size:1.2em"></th>';
						echo '</tr>';
						echo '</thead>';
						echo '<tbody>';
						foreach ($styles_lib as $style_lib)
						{
							echo '<form method="post" action="">';
							echo "<input type='hidden' name='sid' value='".$style_lib->sid."' />";
							echo '<tr valign="top"><td>';
								echo stripslashes($style_lib->title);
							echo "</td><td style='text-align:right'>";
								echo "<input type='submit' id='style_save_as_button' style='margin-right:10px;' class='button' value='".__('Load', WPS_TEXT_DOMAIN)."' />";
								echo "<a class='delete' href='admin.php?page=symposium_styles&delstyle=".$style_lib->sid."'>".__('Delete', WPS_TEXT_DOMAIN)."</a>";
							echo "</td>";
							
							echo "</tr>";
							echo "</form>";
						}
						echo "</tbody></table>";
					}
					?>
					<p style='clear:both;'><br />
					<?php echo __("NB. If changes don't follow the above, you may be overriding them with the theme stylesheet.", WPS_TEXT_DOMAIN) ?>
					</p>
	
					<?php	
				}

			echo '</div>';
	
	 	echo '</div>'; // End of Styles 

 	echo '</div>'; // End of wrap

} 	


function __wps__mail_messages_menu() {

	global $wpdb;

	if (isset($_GET['mail_mid_del'])) {

		if (__wps__safe_param($_GET['mail_mid_del'])) {
			// Update
			$wpdb->query( $wpdb->prepare( "DELETE FROM ".$wpdb->base_prefix."symposium_mail WHERE mail_mid = %d", $_GET['mail_mid_del'] ) );
		} else {
			echo "BAD PARAMETER PASSED: ".$_GET['mail_mid_del'];
		}
		
	}

  	echo '<div class="wrap">';
  	
	  	echo '<div id="icon-themes" class="icon32"><br /></div>';
	  	echo '<h2>'.sprintf(__('%s Management', WPS_TEXT_DOMAIN), WPS_WL).'</h2><br />';
		__wps__show_manage_tabs_header('messages');
	  			
	  	$all = $wpdb->get_var("SELECT count(*) FROM ".$wpdb->base_prefix."symposium_mail"); 
		// Paging info
		$showpage = 0;
		$pagesize = 20;
		$numpages = floor($all / $pagesize);
		if ($all % $pagesize > 0) { $numpages++; }
	  	if (isset($_GET['showpage']) && $_GET['showpage']) { $showpage = $_GET['showpage']-1; } else { $showpage = 0; }
	  	if ($showpage >= $numpages) { $showpage = $numpages-1; }
		$start = ($showpage * $pagesize);		
		if ($start < 0) { $start = 0; }  
				
		// Query
		$sql = "SELECT m.* FROM ".$wpdb->base_prefix."symposium_mail m ";
		$sql .= "ORDER BY m.mail_mid DESC ";
		$sql .= "LIMIT ".$start.", ".$pagesize;
		$messages = $wpdb->get_results($sql);
				
		// Pagination (top)
		echo __wps__pagination($numpages, $showpage, "admin.php?page=__wps__mail_messages_menu&showpage=");
		
		echo '<br /><table class="widefat">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>ID</td>';
		echo '<th>'.__('From', WPS_TEXT_DOMAIN).'</th>';
		echo '<th>'.__('To', WPS_TEXT_DOMAIN).'</th>';
		echo '<th>'.__('Subject', WPS_TEXT_DOMAIN).'</th>';
		echo '<th>'.__('Sent', WPS_TEXT_DOMAIN).'</th>';
		echo '<th>'.__('Action', WPS_TEXT_DOMAIN).'</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tfoot>';
		echo '<tr>';
		echo '<th>ID</th>';
		echo '<th>'.__('From', WPS_TEXT_DOMAIN).'</th>';
		echo '<th>'.__('To', WPS_TEXT_DOMAIN).'</th>';
		echo '<th>'.__('Subject', WPS_TEXT_DOMAIN).'</th>';
		echo '<th>'.__('Sent', WPS_TEXT_DOMAIN).'</th>';
		echo '<th>'.__('Action', WPS_TEXT_DOMAIN).'</th>';
		echo '</tr>';
		echo '</tfoot>';
		echo '<tbody>';
		
		echo '<style>.mail_rollover:hover { background-color: #ccc; } </style>';

		if ($messages) {
			
			foreach ($messages as $message) {
	
				echo '<tr class="mail_rollover">';
				echo '<td valign="top" style="width: 30px">'.$message->mail_mid.'</td>';
				echo '<td valign="top" style="width: 100px">'.__wps__profile_link($message->mail_from).'</td>';
				echo '<td valign="top" style="width: 100px">'.__wps__profile_link($message->mail_to).'</td>';
				echo '<td valign="top" style="width: 200px; text-align:center;">';
				$preview = stripslashes($message->mail_subject);
				$preview_length = 150;
				if ( strlen($preview) > $preview_length ) { $preview = substr($preview, 0, $preview_length)."..."; }
				echo '<div style="float: left;">';
				echo '<a class="show_full_message" id="'.$message->mail_mid.'" style="cursor:pointer;margin-left:6px;">';
				echo $preview;
				echo '</a></div>';
				echo '</td>';
				echo '<td valign="top" style="width: 150px">'.$message->mail_sent.'</td>';
				echo '<td valign="top" style="width: 50px">';
				$showpage = (isset($_GET['showpage'])) ? $_GET['showpage'] : 0;
				echo "<span class='trash delete'><a href='admin.php?page=__wps__mail_messages_menu&action=message_del&showpage=".$showpage."&mail_mid_del=".$message->mail_mid."'>".__('Trash', WPS_TEXT_DOMAIN)."</a></span>";
				echo '</td>';
				echo '</tr>';			
	
			}
		} else {
			echo '<tr><td colspan="6">&nbsp;</td></tr>';
		}

		echo '</tbody>';
		echo '</table>';
	
		// Pagination (bottom)
		echo __wps__pagination($numpages, $showpage, "admin.php?page=__wps__mail_messages_menu&showpage=");

		__wps__show_manage_tabs_header_end();		
		
	echo '</div>'; // End of wrap div

}

function __wps__mail_menu() {

	global $wpdb, $current_user;

	// See if the user has posted forum settings
	if( isset($_POST[ 'symposium_update' ]) && $_POST[ 'symposium_update' ] == '__wps__mail_menu' ) {
		$mail_all = (isset($_POST[ 'mail_all' ])) ? $_POST[ 'mail_all' ] : '';
		
		// Update database
		update_option(WPS_OPTIONS_PREFIX.'_mail_all', $mail_all);

	}
	
	if ( isset($_POST['bulk_message']) ) {

		$cnt = 0;

		$subject = $_POST['bulk_subject'];
		$message =$_POST['bulk_message'];
		
		if ($subject == '' || $message == '') {
			echo "<div class='error'><p>".__('Please fill in the subject and message fields.', WPS_TEXT_DOMAIN).".</p></div>";
		} else {

			if (isset($_POST['roles'])) {
		   		$range = array_keys($_POST['roles']);
		   		$include_roles = '';
	   			foreach ($range as $key) {
					  $include_roles .= $_POST['roles'][$key].',';
		   		}
					$include_roles = str_replace('', ' ', $include_roles);
			} else {
				$include_roles = '';
			}

			// Chosen at least one WordPress role?
			if ($include_roles != '') {

		  	$url = __wps__get_url('mail');	
	
				$sql = "SELECT * FROM ".$wpdb->base_prefix."users";
				$members = $wpdb->get_results($sql);
			
				foreach ($members as $member) {

					// Get this member's WP role and check in permitted list
					$the_user = get_userdata( $member->ID );
					$capabilities = $the_user->{$wpdb->prefix . 'capabilities'};
		
					$user_role = 'NONE';
					if ( !isset( $wp_roles ) )
						$wp_roles = new WP_Roles();

					if ($capabilities) {
						foreach ( $wp_roles->role_names as $role => $name ) {
							if ( array_key_exists( $role, $capabilities ) ) {
								$user_role = str_replace(' ', '', $role);
							}
						}
					}
								
					// Check in this topics category level
					if (strpos(strtolower($include_roles), 'everyone,') !== FALSE || strpos(strtolower($include_roles), $user_role.',') !== FALSE) {	
				
						// Send mail
						if ( $rows_affected = $wpdb->prepare( $wpdb->insert( $wpdb->base_prefix . "symposium_mail", array( 
						'mail_from' => $current_user->ID, 
						'mail_to' => $member->ID, 
						'mail_sent' => date("Y-m-d H:i:s"), 
						'mail_subject' => $subject,
						'mail_message' => $message
						 ) ), '' ) ) {
					 		$cnt++;
				 		}
		
						$mail_id = $wpdb->insert_id;
				
						// Filter to allow further actions to take place
						apply_filters ('__wps__sendmessage_filter', $member->ID, $current_user->ID, $current_user->display_name, $mail_id);
			
						// Send real email if chosen
						if ( __wps__get_meta($member->ID, 'notify_new_messages') ) {
		
							$body = "<h1>".$subject."</h1>";
							$body .= "<p><a href='".$url.__wps__string_query($url)."mid=".$mail_id."'>".__("Go to Mail", WPS_TEXT_DOMAIN)."...</a></p>";
							$body .= "<p>";
							$body .= $message;
							$body .= "</p>";
							$body .= "<p><em>";
							$body .= $current_user->display_name;
							$body .= "</em></p>";
				
							$body = str_replace(chr(13), "<br />", $body);
							$body = str_replace("\\r\\n", "<br />", $body);
							$body = str_replace("\\", "", $body);
		
							// Send real email
							if (isset($_POST['bulk_email'])) {
								__wps__sendmail($member->user_email, __('New Mail Message', WPS_TEXT_DOMAIN), $body);
							}
						}
					}		
				}
			
				echo "<div class='updated'><p>";
				if (isset($_POST['bulk_email'])) {
					echo sprintf(__('Bulk message sent to %d members, and to their email addresses.', WPS_TEXT_DOMAIN), $cnt);
				} else {
					echo sprintf(__('Bulk message sent to %d members (but not to their email addresses).', WPS_TEXT_DOMAIN), $cnt);
				}
				echo "</p></div>";	
				$subject = '';
				$message = '';			
			} else {

				echo "<div class='error'><p>".__('Please choose at least one WordPress role.', WPS_TEXT_DOMAIN).".</p></div>";

			}
		}
	} else {
		$subject = '';
		$message = '';
	}

	// Get config data to show
	$mail_all = get_option(WPS_OPTIONS_PREFIX.'_mail_all');
	
  	echo '<div class="wrap">';
  	
	  	echo '<div id="icon-themes" class="icon32"><br /></div>';
	  	echo '<h2>'.sprintf(__('%s Options', WPS_TEXT_DOMAIN), WPS_WL).'</h2><br />';
	
		__wps__show_tabs_header('mail');
		?>
			
			<form method="post" action=""> 
			<input type="hidden" name="symposium_update" value="__wps__mail_menu">
	
			<table class="form-table __wps__admin_table"> 
			
			<tr><td colspan="2"><h2><?php _e('Options', WPS_TEXT_DOMAIN) ?></h2></td></tr>

			<tr valign="top"> 
			<td scope="row"><label for="mail_all"><?php echo __('Mail to all', WPS_TEXT_DOMAIN); ?></label></td>
			<td>
			<input type="checkbox" name="mail_all" id="mail_all" <?php if ($mail_all == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Allow mail to all members, even if not a friend?', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 
															
			</table> 	
		 
			<p class="submit" style='margin-left:6px;'> 
			<input type="submit" name="Submit" class="button-primary" value="<?php echo __('Save Changes', WPS_TEXT_DOMAIN); ?>" /> 
			</p> 
			</form> 

		
		<?php
		echo '<div style="margin-left:10px">';
		echo '<h2>'.__('Send bulk mail', WPS_TEXT_DOMAIN).'</h2>';
		echo '<p>'.sprintf(__('Send a message from you (%s) to all members of this website - if running WordPress MultiSite, this means all members on your site network.', WPS_TEXT_DOMAIN), $current_user->display_name).'</p>';
		echo '<form method="post" action="">';
		echo '<strong>'.__('Subject', WPS_TEXT_DOMAIN).'</strong><br />';
		echo '<textarea name="bulk_subject" style="width:500px; height:23px; margin-bottom:15px; overflow:hidden;">'.$subject.'</textarea><br />';
		echo '<strong>'.__('Select WordPress roles to include', WPS_TEXT_DOMAIN).'</strong><br />';
	  echo '<div style="margin:10px">';
				// Get list of roles
				global $wp_roles;
				$all_roles = $wp_roles->roles;
				echo '<input type="checkbox" name="roles[]" value="everyone"> '.__('All users', WPS_TEXT_DOMAIN).'<br />';
				foreach ($all_roles as $role) {
					echo '<input type="checkbox" name="roles[]" value="'.$role['name'].'"';
					echo '> '.$role['name'].'<br />';
				}			
		echo '</div>';
		echo '<strong>'.__('Message', WPS_TEXT_DOMAIN).'</strong><br />';
		echo '<textarea name="bulk_message" style="width:500px; height:200px;">'.$message.'</textarea><br />';
		echo '<p><em>'.__('You can include HTML.', WPS_TEXT_DOMAIN).'</em></p>';
		echo '<input type="checkbox" name="bulk_email" CHECKED> '.__('Internal mail will be sent, but also send out email notifications?', WPS_TEXT_DOMAIN);
		echo '<br /><em>'.__('Be wary of limitations from your hosting provider. Members who do not want email notifications will not be sent one.', WPS_TEXT_DOMAIN).'</em><br /><br />';
		echo '<input type="submit" name="Submit" class="button-primary" value="'.__('Send', WPS_TEXT_DOMAIN).'" />';
		echo '</form></div>';

		?>
		<table style="margin-left:10px; margin-top:10px;">						
			<tr><td colspan="2"><h2>Shortcodes</h2></td></tr>
			<tr><td width="165px">[<?php echo WPS_SHORTCODE_PREFIX; ?>-mail]</td>
				<td><?php echo __('Display the mail page.', WPS_TEXT_DOMAIN); ?></td></tr>
		</table>
		
		<?php		
		
		__wps__show_tabs_header_end();

	echo '</div>';
	

}

function __wps__members_menu() {
	
	global $wpdb;

	// See if the user has posted notification bar settings
	if( isset($_POST[ 'symposium_update' ]) && $_POST[ 'symposium_update' ] == '__wps__members_menu' ) {

		$dir_atoz_order = (isset($_POST['dir_atoz_order'])) ? $_POST['dir_atoz_order'] : '';
		$show_dir_buttons = (isset($_POST['show_dir_buttons'])) ? $_POST['show_dir_buttons'] : '';
		$dir_page_length = (isset($_POST['dir_page_length']) && $_POST['dir_page_length'] != '') ? $_POST['dir_page_length'] : '25';
		$dir_full_ver = (isset($_POST['dir_full_ver']) && $_POST['dir_full_ver'] != '') ? $_POST['dir_full_ver'] : '';
		$dir_hide_public = (isset($_POST['dir_hide_public']) && $_POST['dir_hide_public'] != '') ? $_POST['dir_hide_public'] : '';
		
		
		update_option(WPS_OPTIONS_PREFIX.'_dir_atoz_order', $dir_atoz_order);
		update_option(WPS_OPTIONS_PREFIX.'_show_dir_buttons', $show_dir_buttons);
		update_option(WPS_OPTIONS_PREFIX.'_dir_page_length', $dir_page_length);
		update_option(WPS_OPTIONS_PREFIX.'_dir_full_ver', $dir_full_ver);
		update_option(WPS_OPTIONS_PREFIX.'dir_hide_public', $dir_hide_public);
		

		// Included roles
		if (isset($_POST['dir_level'])) {
	   		$range = array_keys($_POST['dir_level']);
	   		$level = '';
   			foreach ($range as $key) {
				$level .= $_POST['dir_level'][$key].',';
	   		}
		} else {
			$level = '';
		}

		update_option(WPS_OPTIONS_PREFIX.'_dir_level', serialize($level));
		
		// Put an settings updated message on the screen
		echo "<div class='updated slideaway'><p>".__('Saved', WPS_TEXT_DOMAIN).".</p></div>";
		
	}

	// Get values to show
	$show_dir_buttons = get_option(WPS_OPTIONS_PREFIX.'_show_dir_buttons');
	$dir_page_length = get_option(WPS_OPTIONS_PREFIX.'_dir_page_length');
	
  	echo '<div class="wrap">';
  	
	  	echo '<div id="icon-themes" class="icon32"><br /></div>';
	  	echo '<h2>'.sprintf(__('%s Options', WPS_TEXT_DOMAIN), WPS_WL).'</h2><br />';
	
		__wps__show_tabs_header('directory');
		?>

			<form method="post" action=""> 
			<input type="hidden" name="symposium_update" value="__wps__members_menu">

			<table class="form-table __wps__admin_table">

			<tr><td colspan="2"><h2><?php _e('Options', WPS_TEXT_DOMAIN) ?></h2></td></tr>
			
			<tr valign="top">
			<td scope="row"><label for="dir_atoz_order"><?php echo __('Default view', WPS_TEXT_DOMAIN); ?></label></td> 
			<td>
			<select name="dir_atoz_order">
				<option value='last_activity'<?php if (get_option(WPS_OPTIONS_PREFIX.'_dir_atoz_order') == 'last_activity') { echo ' SELECTED'; } ?>><?php echo __('Most recently active', WPS_TEXT_DOMAIN); ?></option>
				<option value='display_name'<?php if (get_option(WPS_OPTIONS_PREFIX.'_dir_atoz_order') == 'display_name') { echo ' SELECTED'; } ?>><?php echo __('Display name', WPS_TEXT_DOMAIN); ?></option>
				<option value='surname'<?php if (get_option(WPS_OPTIONS_PREFIX.'_dir_atoz_order') == 'surname') { echo ' SELECTED'; } ?>><?php echo __('Surname (if entered in display_name)', WPS_TEXT_DOMAIN); ?></option>
			</select> 
			<span class="description"><?php echo __("Initial view of the member directory", WPS_TEXT_DOMAIN); ?></span></td>
			</tr> 		

			<tr valign="top"> 
			<td scope="row"><label for="dir_hide_public"><?php echo __('Make private?', WPS_TEXT_DOMAIN) ?></label></td>
			<td>
			<input type="checkbox" name="dir_hide_public" id="dir_hide_public" <?php if (isset($dir_hide_public) && $dir_hide_public == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Hide from public view, requires login to see directory', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 
			
			<tr valign="top"> 
			<td scope="row"><label for="dir_full_ver"><?php echo __('Faster search?', WPS_TEXT_DOMAIN) ?></label></td>
			<td>
			<input type="checkbox" name="dir_full_ver" id="dir_full_ver" <?php if (isset($dir_full_ver) && $dir_full_ver == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Improves search time, but search results are limited and cannot re-order search results', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 
			
			<tr valign="top"> 
			<td scope="row"><label for="show_dir_buttons"><?php echo __('Include member actions?', WPS_TEXT_DOMAIN) ?></label></td>
			<td>
			<input type="checkbox" name="show_dir_buttons" id="show_dir_buttons" <?php if ($show_dir_buttons == "on") { echo "CHECKED"; } ?>/>
			<span class="description"><?php echo __('Should buttons to add as a friend, or send mail, be shown on the directory?', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 
			
			<tr valign="top"> 
			<td scope="row"><label for="dir_page_length"><?php echo __('Page Length', WPS_TEXT_DOMAIN) ?></label></td> 
			<td><input name="dir_page_length" type="text" id="dir_page_length" style="width:50px" value="<?php echo $dir_page_length; ?>"  /> 
			<span class="description"><?php echo __('Number of members shown at a time on the directory', WPS_TEXT_DOMAIN); ?></span></td> 
			</tr> 	

			<tr valign="top"> 
			<td scope="row"><label for="dir_level"><?php echo __('Roles to include in directory', WPS_TEXT_DOMAIN) ?></label></td> 
			<td>
			<?php

				// Get list of roles
				global $wp_roles;
				$all_roles = $wp_roles->roles;

				$dir_roles = get_option(WPS_OPTIONS_PREFIX.'_dir_level');

				foreach ($all_roles as $role) {
					echo '<input type="checkbox" name="dir_level[]" value="'.$role['name'].'"';
					if (strpos(strtolower($dir_roles), strtolower($role['name']).',') !== FALSE) {
						echo ' CHECKED';
					}
					echo '> '.$role['name'].'<br />';
				}	

			?>
			</td>
			
			</tr>

			<tr><td colspan="2"><h2>Shortcodes</h2></td></tr>
			
			<tr valign="top"> 
				<td scope="row">
					[<?php echo WPS_SHORTCODE_PREFIX; ?>-members]
				</td>
				<td>
				<?php echo __('Displays a list of members, based upon roles selected above.', WPS_TEXT_DOMAIN).'<br />'; ?>
				<?php echo '<strong>'.__('Parameters', WPS_TEXT_DOMAIN).'</strong><br />'; ?>
				<?php echo __('<div style="width:75px;float:left;">roles:</div>over-ride the roles above and limit to those included (comma separated)', WPS_TEXT_DOMAIN).'<br />'; ?>
				<?php echo '<strong>'.__('Example', WPS_TEXT_DOMAIN).'</strong><br />'; ?>
				<?php echo sprintf(__('[%s-members roles="administrator,subscriber"]', WPS_TEXT_DOMAIN), WPS_SHORTCODE_PREFIX).'<br />'; ?>
				<span class="description"><?php echo __('You can use this shortcode (with different parameters) on multiple pages.', WPS_TEXT_DOMAIN); ?></span>
				</td>
			</tr>
									
			</table>
		
			<p class="submit" style="margin-left:6px;"> 
			<input type="submit" name="Submit" class="button-primary" value="<?php echo __('Save Changes', WPS_TEXT_DOMAIN); ?>" /> 
			</p> 
			</form> 

	<?php
	__wps__show_tabs_header_end();
	echo '</div>';
		
}

function __wps__show_tabs_header($active_tab) {

	if (get_option(WPS_OPTIONS_PREFIX.'_long_menu') == "on") {

		include_once(dirname(__FILE__).'/show_tabs_style.php');
	
		$options_active = $active_tab == 'options' ? 'active' : 'inactive';
		$profile_active = $active_tab == 'profile' ? 'active' : 'inactive';
		$forum_active = $active_tab == 'forum' ? 'active' : 'inactive';
		$bar_active = $active_tab == 'panel' ? 'active' : 'inactive';
		$directory_active = $active_tab == 'directory' ? 'active' : 'inactive';
		$mail_active = $active_tab == 'mail' ? 'active' : 'inactive';
		$mobile_active = $active_tab == 'mobile' ? 'active' : 'inactive';
		$plus_active = $active_tab == 'plus' ? 'active' : 'inactive';
		$events_active = $active_tab == 'events' ? 'active' : 'inactive';
		$facebook_active = $active_tab == 'facebook' ? 'active' : 'inactive';
		$groups_active = $active_tab == 'groups' ? 'active' : 'inactive';
		$lounge_active = $active_tab == 'lounge' ? 'active' : 'inactive';
		$replybyemail_active = $active_tab == 'replybyemail' ? 'active' : 'inactive';
		$alerts_active = $active_tab == 'alerts' ? 'active' : 'inactive';
		$gallery_active = $active_tab == 'gallery' ? 'active' : 'inactive';
			
		echo '<div id="mail_tabs">';
		echo '<div class="mail_tab nav-tab-'.$options_active.'"><a href="admin.php?page=symposium_options" class="nav-tab-'.$options_active.'-link">'.__('Options', WPS_TEXT_DOMAIN).'</a></div>';
		if (function_exists('__wps__profile')) 		echo '<div class="mail_tab nav-tab-'.$profile_active.'"><a href="admin.php?page=symposium_profile" class="nav-tab-'.$profile_active.'-link">'.__('Profile', WPS_TEXT_DOMAIN).'</a></div>';
		if (function_exists('__wps__profile_plus')) 	echo '<div class="mail_tab nav-tab-'.$plus_active.' bronze"><a href="admin.php?page='.WPS_DIR.'/plus_admin.php" class="nav-tab-'.$plus_active.'-link">'.__('Plus', WPS_TEXT_DOMAIN).'</a></div>';
		if (function_exists('__wps__forum')) 		echo '<div class="mail_tab nav-tab-'.$forum_active.'"><a href="admin.php?page=symposium_forum" class="nav-tab-'.$forum_active.'-link">'.__('Forum', WPS_TEXT_DOMAIN).'</a></div>';
		if (function_exists('__wps__members')) 		echo '<div class="mail_tab nav-tab-'.$directory_active.'"><a href="admin.php?page=__wps__members_menu" class="nav-tab-'.$directory_active.'-link">'.__('Directory', WPS_TEXT_DOMAIN).'</a></div>';
		if (function_exists('__wps__mail')) 			echo '<div class="mail_tab nav-tab-'.$mail_active.'"><a href="admin.php?page=__wps__mail_menu" class="nav-tab-'.$mail_active.'-link">'.__('Mail', WPS_TEXT_DOMAIN).'</a></div>';
		if (function_exists('__wps__group')) 		echo '<div class="mail_tab nav-tab-'.$groups_active.' bronze"><a href="admin.php?page='.WPS_DIR.'/groups_admin.php" class="nav-tab-'.$groups_active.'-link">'.__('Groups', WPS_TEXT_DOMAIN).'</a></div>';
		if (function_exists('__wps__gallery')) 		echo '<div class="mail_tab nav-tab-'.$gallery_active.' bronze"><a href="admin.php?page='.WPS_DIR.'/gallery_admin.php" class="nav-tab-'.$gallery_active.'-link">'.__('Gallery', WPS_TEXT_DOMAIN).'</a></div>';
		if (function_exists('__wps__news_main')) 	echo '<div class="mail_tab nav-tab-'.$alerts_active.' bronze"><a href="admin.php?page='.WPS_DIR.'/news_admin.php" class="nav-tab-'.$alerts_active.'-link">'.__('Alerts', WPS_TEXT_DOMAIN).'</a></div>';
		if (function_exists('__wps__add_notification_bar')) 	echo '<div class="mail_tab nav-tab-'.$bar_active.'"><a href="admin.php?page=symposium_bar" class="nav-tab-'.$bar_active.'-link">'.__('Panel', WPS_TEXT_DOMAIN).'</a></div>';
		if (function_exists('__wps__events_main')) 	echo '<div class="mail_tab nav-tab-'.$events_active.' bronze"><a href="admin.php?page='.WPS_DIR.'/events_admin.php" class="nav-tab-'.$events_active.'-link">'.__('Events', WPS_TEXT_DOMAIN).'</a></div>';
		if (function_exists('__wps__facebook')) 		echo '<div class="mail_tab nav-tab-'.$facebook_active.' bronze"><a href="admin.php?page='.WPS_DIR.'/facebook_admin.php" class="nav-tab-'.$facebook_active.'-link">'.__('Facebook', WPS_TEXT_DOMAIN).'</a></div>';
		if (function_exists('__wps__mobile')) 		echo '<div class="mail_tab nav-tab-'.$mobile_active.'"><a href="admin.php?page=__wps__mobile_menu" class="nav-tab-'.$mobile_active.'-link">'.__('Mobile', WPS_TEXT_DOMAIN).'</a></div>';
		if (function_exists('__wps__mailinglist')) 	echo '<div class="mail_tab nav-tab-'.$replybyemail_active.' bronze"><a href="admin.php?page='.WPS_DIR.'/mailinglist_admin.php" class="nav-tab-'.$replybyemail_active.'-link">'.__('Reply', WPS_TEXT_DOMAIN).'</a></div>';
		if (function_exists('__wps__lounge_main')) 	echo '<div class="mail_tab nav-tab-'.$lounge_active.' bronze"><a href="admin.php?page='.WPS_DIR.'/lounge_admin.php" class="nav-tab-'.$lounge_active.'-link">'.__('Lounge', WPS_TEXT_DOMAIN).'</a></div>';
		echo '</div>';
	
		echo '<div id="mail-main">';
		
	}
}

function __wps__show_tabs_header_end() {

	if (get_option(WPS_OPTIONS_PREFIX.'_long_menu') == "on")
		echo '</div>';
	
}	

function __wps__show_manage_tabs_header($active_tab) {

	if (get_option(WPS_OPTIONS_PREFIX.'_long_menu') == "on") {

		include_once(dirname(__FILE__).'/show_tabs_style.php');
		?> <style> .wrap .mail_tab { width: 85px; } </style> <?php
	
		$manage_active = $active_tab == 'manage' ? 'active' : 'inactive';
		$categories_active = $active_tab == 'categories' ? 'active' : 'inactive';
		$posts_active = $active_tab == 'posts' ? 'active' : 'inactive';
		$messages_active = $active_tab == 'messages' ? 'active' : 'inactive';
		$templates_active = $active_tab == 'templates' ? 'active' : 'inactive';
		$settings_active = $active_tab == 'settings' ? 'active' : 'inactive';
		$advertising_active = $active_tab == 'advertising' ? 'active' : 'inactive';
		$thesaurus_active = $active_tab == 'thesaurus' ? 'active' : 'inactive';
		$audit_active = $active_tab == 'audit' ? 'active' : 'inactive';
		
		global $wpdb;
  		$count = $wpdb->get_var("SELECT COUNT(*) FROM ".$wpdb->prefix.'symposium_topics'." WHERE topic_approved != 'on'"); 
		if ($count > 0) {
			$count2 = " (".$count.")";
		} else {
			$count2 = "";
		}
					
		echo '<div id="mail_tabs">';
		if (current_user_can('manage_options')) echo '<div class="mail_tab nav-tab-'.$manage_active.'"><a href="admin.php?page=symposium_manage" class="nav-tab-'.$manage_active.'-link">'.__('Manage', WPS_TEXT_DOMAIN).'</a></div>';
		if (current_user_can('manage_options')) echo '<div class="mail_tab nav-tab-'.$settings_active.'"><a href="admin.php?page=symposium_settings" class="nav-tab-'.$settings_active.'-link">'.__('Settings', WPS_TEXT_DOMAIN).'</a></div>';
		if (current_user_can('manage_options')) echo '<div class="mail_tab nav-tab-'.$advertising_active.'"><a href="admin.php?page=symposium_advertising" class="nav-tab-'.$advertising_active.'-link">'.__('Advertising', WPS_TEXT_DOMAIN).'</a></div>';
		if (current_user_can('manage_options')) echo '<div class="mail_tab nav-tab-'.$thesaurus_active.'"><a href="admin.php?page=symposium_thesaurus" class="nav-tab-'.$thesaurus_active.'-link">'.__('Thesaurus', WPS_TEXT_DOMAIN).'</a></div>';
		if (function_exists('__wps__forum') && current_user_can('manage_options')) echo '<div class="mail_tab nav-tab-'.$categories_active.'"><a href="admin.php?page=symposium_categories" class="nav-tab-'.$categories_active.'-link">'.__('Categories', WPS_TEXT_DOMAIN).'</a></div>';
		if (function_exists('__wps__forum')) echo '<div class="mail_tab nav-tab-'.$posts_active.'"><a href="admin.php?page=symposium_moderation" class="nav-tab-'.$posts_active.'-link">'.sprintf(__('Forum %s', WPS_TEXT_DOMAIN), $count2).'</a></div>';
		if (function_exists('__wps__mail') && current_user_can('manage_options')) echo '<div class="mail_tab nav-tab-'.$messages_active.'"><a href="admin.php?page=__wps__mail_messages_menu" class="nav-tab-'.$messages_active.'-link">'.__('Mail Messages', WPS_TEXT_DOMAIN).'</a></div>';
		if (current_user_can('manage_options')) echo '<div class="mail_tab nav-tab-'.$templates_active.'"><a href="admin.php?page=symposium_templates" class="nav-tab-'.$templates_active.'-link">'.__('Templates', WPS_TEXT_DOMAIN).'</a></div>';
		if (get_option(WPS_OPTIONS_PREFIX.'_audit') == "on") echo '<div class="mail_tab nav-tab-'.$audit_active.'"><a href="admin.php?page=symposium_audit" class="nav-tab-'.$audit_active.'-link">'.__('Audit', WPS_TEXT_DOMAIN).'</a></div>';
		echo '</div>';
	
		echo '<div id="mail-main">';
		
	}
}

function __wps__show_manage_tabs_header_end() {

	if (get_option(WPS_OPTIONS_PREFIX.'_long_menu') == "on")
		echo '</div>';
	
}	

/* Update option on all blogs as applicable */

function __wps__update_option($option, $value, $update_network)
{

	if (is_multisite() && $update_network) {	
	
		global $wpdb;
		
		$blogs = $wpdb->get_results("
			SELECT blog_id
			FROM {$wpdb->blogs}
			WHERE site_id = '{$wpdb->siteid}'
			AND archived = '0'
			AND spam = '0'
			AND deleted = '0'
		");
		
		foreach ($blogs as $blog) {
			__wps__set_options($blog->blog_id, $option, $value);
		}
        
	} else {
	
		update_option($option, $value);	
	}
}

function __wps__set_options($blog_id = null, $option, $value)
{
    if ($blog_id) {
        switch_to_blog($blog_id);
    }

    update_option($option, $value);
    
    if ($blog_id) {
        restore_current_blog();
    }
}


/* =============== ADD TO ADMIN MENU =============== */

if (is_admin()) {
	add_action('admin_menu', '__wps__plugin_menu');
}

?>
