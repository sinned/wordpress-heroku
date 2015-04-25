<?php
/*
WP Symposium Groups
Description: Groups Directory and Page plugin compatible with WP Symposium. Put [symposium-groups] and [symposium-group] on any WordPress page.
*/


/* ***************************************************** GROUP PAGE ***************************************************** */

// Get constants
require_once(dirname(__FILE__).'/default-constants.php');

global $wpdb;

// [symposium-group] (wall)
function __wps__group()  
{  
	        			
	global $wpdb;
	$gid = '';

	if (isset($_GET['gid'])) {
		$gid = $_GET['gid'];
	} else {
		if (isset($_POST['gid'])) {
			$gid = $_POST['gid'];
		}
	}

	if ($gid) {
		$default_page = $wpdb->get_var($wpdb->prepare("SELECT default_page FROM ".$wpdb->prefix . 'symposium_groups WHERE gid=%d', $gid));
		return __wps_show_group($default_page);
	} else {
		return 'No group ID sent....';
	}
	
	exit;
		
}

// [symposium-group-members]
function __wps__group_members()  
{  

	return __wps_show_group("members");
	exit;
		
}

// [symposium-group-settings]
function __wps__group_settings()  
{  
										
	return __wps_show_group("settings");
	exit;
		
}


// Adds group page
function __wps_show_group($page)  
{  

	global $wpdb, $current_user;

	$gid = '';

	if (isset($_GET['gid'])) {
		$gid = $_GET['gid'];
	} else {
		if (isset($_POST['gid'])) {
			$gid = $_POST['gid'];
		}
	}
	
	$group_url = __wps__get_url('group');
	if (strpos($group_url, '?') !== FALSE) {
		$q = "&";
	} else {
		$q = "?";
	}
	
	// Check if private or public
	$sql = "SELECT private FROM ".$wpdb->prefix."symposium_groups WHERE gid = %d";
	$private = $wpdb->get_var($wpdb->prepare($sql, $gid));

	if (is_user_logged_in()) {
		
		if ($gid != '') {
			
			// Wrapper
			$html = "<div class='__wps__wrapper'>";
					
				$plugin = WPS_PLUGIN_URL;

				// Group views, therefore considered active, so update last activity
				$wpdb->query( $wpdb->prepare( "UPDATE ".$wpdb->prefix."symposium_groups SET last_activity = %s WHERE gid = %d", date("Y-m-d H:i:s"), $gid ));

				$group = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$wpdb->prefix . 'symposium_groups WHERE gid=%d', $gid));

				// Use default layout, or templates?
				if (get_option(WPS_OPTIONS_PREFIX.'_use_group_templates') == "on") {
					$template = get_option(WPS_OPTIONS_PREFIX.'_template_group');
					$template = str_replace("[]", "", stripslashes($template));
				} else {				
					$template = "<div id='group_header_div'><div id='group_header_panel'>";
					$template .= "<div id='group_details'>";
					$template .= "<div id='group_name'>[group_name]</div>";
					$template .= "<div id='group_description'>[group_description]</div>";
					$template .= "<div style='padding-top: 15px;padding-bottom: 15px;'>[actions]</div>";
					$template .= "</div>";
					$template .= "</div>";
					$template .= "<div id='group_photo' class='corners'>[avatar,170]</div>";
					$template .= "</div>";
					$template .= "<div id='group_wrapper'>";
					$template .= "<div id='force_group_page' style='display:none'>[default]</div>";
					$template .= "<div id='group_body_wrapper'>";
					$template .= "[menu_tabs]";
					$template .= "<div id='group_body' class='group_body_full'>[page]</div>";
					$template .= "</div>";
					$template .= "</div>";
				}
						
				// Buttons									
				$buttons = "";
				$member_of = __wps__member_of($gid);
				
				if (is_user_logged_in()) {
				
					if ($member_of != "yes") {
						
						if ($member_of == "no") {

							// Not a member, or pending, so show join button
							$member_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(gmid) FROM ".$wpdb->prefix."symposium_group_members WHERE group_id = %d", $gid));
							if ($group->max_members == 0 || $member_count < $group->max_members) {
								if ($group->private != "on") {
									$buttons .='<input type="submit" value="'.__("Join Group", WPS_TEXT_DOMAIN).'" id="groups_join_button" class="__wps__button">';
									$buttons .='<p id="groups_join_button_done" style="padding:6px;display:none">'.__('You are now a member of this group.', WPS_TEXT_DOMAIN).'</p>';
								} else {
									$buttons .='<input type="submit" value="'.__("Request to Join", WPS_TEXT_DOMAIN).'" id="groups_join_button" class="__wps__button">';
									$buttons .='<p id="groups_join_button_done" style="padding:6px;display:none">'.__('Your membership is awaiting approval.', WPS_TEXT_DOMAIN).'</p>';
								}
							} else {
								$buttons .='<p>'.__('Group membership is full.', WPS_TEXT_DOMAIN).'</p>';
							}

						} else {
							
							// Asked to join, waiting for decision
							$buttons .= "<p>".__("Your request to join is awaiting approval.", WPS_TEXT_DOMAIN)."</p>";

						}
									
					} else {

						if (__wps__group_admin($gid) != "yes") {
							// Is a member, so show leave button (if not an admin)
							$buttons .='<input type="submit" value="'.__("Leave Group", WPS_TEXT_DOMAIN).'" id="groups_leave_button" class="__wps__button">';
							$buttons .='<p id="groups_leave_button_done" style="padding:6px;display:none">'.__('You are no longer a member of this group.', WPS_TEXT_DOMAIN).'</p>';
						}
						
					}

					if (__wps__group_admin($gid) == "yes" || __wps__get_current_userlevel() == 5) {
						// Admin, so can delete group
						if (__wps__get_current_userlevel() == 5) {
							$buttons .= '<input type="submit" value="'.__("Delete Group", WPS_TEXT_DOMAIN).'" id="groups_delete_button" class="__wps__button">';
							$buttons .='<p id="groups_delete_button_done" style="padding:6px;display:none">'.__('Group deleted.', WPS_TEXT_DOMAIN).'</p>';
						} else {
							$buttons .= '<input type="submit" title="'.$gid.'" value="'.__("Delete Group", WPS_TEXT_DOMAIN).'" id="groups_delete_button_request" class="__wps__button">';
						}
					} 
				
				} else {
				
					$buttons = "";
				
				}
				// Replace Header Codes
				$template = str_replace("[group_name]", stripslashes($group->name), $template);
				$template = str_replace("[group_description]", stripslashes($group->description), $template);
				$template = str_replace("[actions]", $buttons, $template);

				// Avatar
				if (strpos($template, '[avatar') !== FALSE) {
					if (strpos($template, '[avatar]')) {
						$template = str_replace("[avatar]", __wps__get_group_avatar($gid, 200), $template);						
					} else {
						$x = strpos($template, '[avatar');
						$avatar = substr($template, 0, $x);
						$avatar2 = substr($template, $x+8, 3);
						$avatar3 = substr($template, $x+12, strlen($template)-$x-12);

						$template = $avatar . __wps__get_group_avatar($gid, $avatar2) . $avatar3;
					}
				}
				
				// Menu
				if (strpos($template, '[menu]') !== false) {
					// vertical menu
					$menu = "";
					$menu .= '<div id="group_menu_all" class="__wps__group_menu">'.__('All Groups', WPS_TEXT_DOMAIN).'</div>';
					$menu .= '<div id="group_menu_about" class="__wps__group_menu">'.__('Welcome page', WPS_TEXT_DOMAIN).'</div>';
					if ($member_of == "yes" || $group->content_private != "on") {
						$menu .= '<div id="group_menu_wall" class="__wps__group_menu">'.__('Group Activity', WPS_TEXT_DOMAIN).'</div>';
						if ($group->group_forum == "on") {
							$menu .= '<div id="group_menu_forum" class="__wps__group_menu">'.__('Group Forum', WPS_TEXT_DOMAIN).'</div>';
						}
						$menu .= '<div id="group_menu_members" class="__wps__group_menu">'.__('Active Members', WPS_TEXT_DOMAIN).'</div>';
					}
					if (__wps__group_admin($gid) == "yes" || __wps__get_current_userlevel() == 5) {
						$menu .= '<div id="group_menu_settings" class="__wps__group_menu">'.__('Group Settings', WPS_TEXT_DOMAIN).'</div>';
						if (get_option(WPS_OPTIONS_PREFIX.'_group_invites') == 'on') {
							$menu .= '<div id="group_menu_invites" class="__wps__group_menu">'.__('Group Invites', WPS_TEXT_DOMAIN).'</div>';
						}
					}
					$template = str_replace("[menu]", $menu, $template);
				} else {
					// horizontal menu
					$template = str_replace("[menu_tabs]", __wps__show_group_menu_tabs($gid, $member_of, $group), $template);
				}

				// Body
				if ($member_of == "yes" || $group->content_private != "on") {
					$template = str_replace("[page]", "<img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/busy.gif' />", $template);
					$template = str_replace("[default]", $page, $template);
				} else {
					$private_link = '';
					if (!is_user_logged_in()) {
						$private_link .= " <a href=".wp_login_url( $group_url.$q.'gid='.$gid )." class='simplemodal-login' title='".__("Login", WPS_TEXT_DOMAIN)."'>".__("Login", WPS_TEXT_DOMAIN).".</a>";
					}
					$template = str_replace("[page]", $private_link, $template);
					$template = str_replace("[default]", "", $template);
				}
				$template .= "<br class='clear' />";
				
				$html .= $template;
					

			$html .= "</div>"; // End of Wrapper
			$html .= "<br class='clear' />";
						
		} else {
			
			$html = __("Group not found, sorry.", WPS_TEXT_DOMAIN);
		}
		
	} else {
		
		$html = __wps__show_login_link(__("You need to <a href='%s'>login</a> to access this group.", WPS_TEXT_DOMAIN));
		
	}
	
	// Filter for header
	$html = apply_filters ( '__wps__group_header_filter', $html, $gid );

	
	return $html;								
	exit;

}  

function __wps__show_group_menu_tabs($gid, $member_of, $group) {
        	
	global $wpdb, $current_user;

		$structure = get_option(WPS_OPTIONS_PREFIX."_group_menu_structure");

		$str_arr = explode(chr(10), $structure);
				
		$menu = '<ul class="__wps__dropdown">';

		// Build menu		
		$started_top_level = false;
		foreach($str_arr as $item) {
			
			// Top level menu items
			if (strpos($item, '[') !== false) {
				$item = str_replace('[', '', $item);
				$item = str_replace(']', '', $item);
				if ($started_top_level) {
					$menu .= '</ul></li>';
				}
				$started_top_level = true;

				if ($member_of == "yes" || $group->content_private != "on") {			
					$menu .= '<li class="__wps__top_menu">'.$item;
				} else {
					$menu .= '<li class="__wps__top_menu" style="display:none">'.$item;
				}
				$menu .= '<ul class="__wps__sub_menu">';
			}
			
			// Child item
			if (strpos($item, '=') !== false) {
				list($title,$value) = explode('=', $item);
				$value = str_replace(chr(13), '', $value);
				$i = '';

				switch ($value) {
				case 'welcome' :
					$i = '<li id="group_menu_about" class="__wps__group_menu">'.$title.'</li>';
					break;
				case 'settings':
					if (__wps__group_admin($gid) == "yes" || __wps__get_current_userlevel() == 5) {
						$i = '<li id="group_menu_settings" class="__wps__group_menu" href="javascript:void(0)">'.$title.'</li>';
					}
					break;
				case 'invites' :
					if (__wps__group_admin($gid) == "yes" || __wps__get_current_userlevel() == 5) {
						if (get_option(WPS_OPTIONS_PREFIX.'_group_invites') == 'on')
							$i = '<li id="group_menu_invites" class="__wps__group_menu" href="javascript:void(0)">'.$title.'</li>';
					}
					break;				
				case 'activity' :
					$i = '<li id="group_menu_wall" class="__wps__group_menu" href="javascript:void(0)">'.$title.'</li>';
					break;
				case 'forum' :
					if ($group->group_forum == "on")
						$i = '<li id="group_menu_forum" class="__wps__group_menu" href="javascript:void(0)">'.$title.'</li>';
					break;
				case 'members' :
					$i = '<li id="group_menu_members" class="__wps__group_menu" href="javascript:void(0)">'.$title.'</li>';
					break;
				default :
					$i = apply_filters ( '__wps__group_menu_tabs', '', $title, $value, $gid, $member_of, $group);
					break;
				}
				if ($i) $menu .= $i;
			}
			
		}
		if ($started_top_level) {
			$menu .= '</ul></li>';
		}

		$menu .= '<div id="__wps__menu_tabs_wrapper"></div>';
			
		$menu .= '</ul><div style="clear:both;padding-bottom:20px;"></div>';
				
	return $menu;

}

/* ***************************************************** GROUPS ***************************************************** */

function __wps__groups() {	
	
	
	global $wpdb, $current_user;
	
	// View (and set tabs)
	if (!isset($_GET['view']) || $_GET['term'] != '') {
		$browse_active = 'active';
		$create_active = 'inactive';
		$view = "browse";
	} 
	if ( isset($_GET['view']) && $_GET['view'] == "create") {
		$browse_active = 'inactive';
		$create_active = 'active';
		$view = "create";
	} 

	$thispage = get_permalink();
	if ($thispage[strlen($thispage)-1] != '/') { $thispage .= '/'; }

	$group_url = get_option(WPS_OPTIONS_PREFIX.'_group_url');
	$group_all_create = get_option(WPS_OPTIONS_PREFIX.'_group_all_create');

	if (isset($_GET['page_id']) && $_GET['page_id'] != '') {
		// No Permalink
		$thispage = $group_url;
		$q = "&";
	} else {
		$q = "?";
	}

	if (isset($_GET['term'])) {
		$term = $_GET['term'];
	} else {
		$term = '';
	}

	$html = '<div class="__wps__wrapper">';

		if ( (is_user_logged_in()) && ($group_all_create == "on" || __wps__get_current_userlevel() == 5) ) {

			$html .= "<input type='submit' id='show_create_group_button' class='__wps__button' value='".__("Create Group", WPS_TEXT_DOMAIN)."'>";

			$html .= "<div id='create_group_form' style='display:none'>";
				$html .= "<div>";
				$html .= "<strong>".__("Name of Group", WPS_TEXT_DOMAIN)."</strong><br />";
				$html .= "<input type='text' id='name_of_group' class='new-topic-subject-input' style='width: 98% !important;'>";
				$html .= "</div>";

				$html .= "<div>";
				$html .= "<strong>".__("Description", WPS_TEXT_DOMAIN)."</strong><br />";
				$html .= "<input type='text' id='description_of_group' style='width: 98% !important;'>";
				$html .= "</div>";

				$html .= "<div style='margin-top:10px'>";
				$html .= "<input type='submit' id='create_group_button' class='__wps__button' value='".__("Create", WPS_TEXT_DOMAIN)."'>";
				$html .= "<input type='submit' id='cancel_create_group_button' class='__wps__button' value='".__("Cancel", WPS_TEXT_DOMAIN)."'>";
				$html .= "</div>";
			$html .= "</div>";

		}
		
		$html .= "<div id='groups_results'>";
		
		if ( $term != '' ) {
	
			$me = $current_user->ID;
			$page = 1;
			$page_length = 25;
	
			$term = "";
			if (isset($_POST['group'])) { $term .= $_POST['group']; }
			if (isset($_GET['term'])) { $term .= $_GET['term']; }

			$html .= "<div style='padding:0px;'>";
			$html .= '<input type="text" id="group" name="group" autocomplete="off" class="groups_search_box" value="'.$term.'" style="margin-right:10px" />';
			$html .= '<input type="hidden" id="group_id" name="group_id" />';
			$html .= '<input id="groups_go_button" type="submit" class="__wps__button" value="'.__("Search", WPS_TEXT_DOMAIN).'" />';
			$html .= "</div>";	

	
			$sql = "SELECT g.*, (SELECT COUNT(*) FROM ".$wpdb->prefix."symposium_group_members WHERE group_id = g.gid) AS member_count
			FROM ".$wpdb->prefix."symposium_groups g WHERE  
			( g.name LIKE '%".$term."%') OR 
			( g.description LIKE '%".$term."%' )
			ORDER BY group_order, last_activity DESC LIMIT 0,25";
			
			$groups = $wpdb->get_results($sql);


			if ($groups) {
				
				foreach ($groups as $group) {

					if (__wps__member_of($group->gid) == 'yes') { 
						$html .= "<div class='groups_row row_odd corners'>";
					} else {
						$html .= "<div class='groups_row row corners'>";
					}					
					
						$html .= "<div class='groups_avatar'>";
							$html .= __wps__get_group_avatar($group->gid, 64);
						$html .= "</div>";

						$html .= "<div class='group_name'>";
						$name = stripslashes($group->name) != '' ? stripslashes($group->name) : __('[No name]', WPS_TEXT_DOMAIN);
						$html .= "<a class='row_link' href='".__wps__get_url('group')."?gid=".$group->gid."'>".$name."</a>";
						$html .= "</div>";
						
						$html .= "<div class='group_member_count'>";
						$html .= __("Member Count:", WPS_TEXT_DOMAIN)." ".$group->member_count;
						if ($group->last_activity) {
							$html .= '<br /><em>'.__('last active', WPS_TEXT_DOMAIN).' '.__wps__time_ago($group->last_activity)."</em>";
						}
						$html .= "</div>";
					
						$html .= "<div class='group_description'>";
						$html .= $group->description;
						$html .= "</div>";
						
					$html .= "</div>";
					
				}
	
			}
			
		} else {
	
	
			$html .= "<div style='padding:0px;'>";
			$html .= '<input type="text" id="__wps__group" name="group" autocomplete="off" class="groups_search_box" value="'.$term.'" style="margin-right:10px" />';
			$html .= '<input type="hidden" id="group_id" name="group_id" />';
			$html .= '<input id="groups_go_button" type="submit" class="__wps__button" value="'.__("Search", WPS_TEXT_DOMAIN).'" />';
			$html .= "</div>";	
	
			
			$html .= "<div id='__wps__groups'><img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/busy.gif' /></div>";
			
		}
		
		$html .= "</div>"; // End of Groups Results
		
		if (isset($groups) && !$groups) 
				$html .= "<div style='clear:both'>".__("No group found....", WPS_TEXT_DOMAIN)."</div>";
		
	$html .= '</div>'; // End of Wrapper
	
	// Send HTML
	return $html;

}

/* ====================================================== ADMIN ====================================================== */


// Add plugin to admin menu via hook
function __wps__add_groups_to_admin_menu()
{
	$hidden = get_option(WPS_OPTIONS_PREFIX.'_long_menu') == "on" ? '_hidden' : '';
	add_submenu_page('symposium_debug'.$hidden, __('Groups', WPS_TEXT_DOMAIN), __('Groups', WPS_TEXT_DOMAIN), 'manage_options', WPS_DIR.'/groups_admin.php');
}
add_action('__wps__admin_menu_hook', '__wps__add_groups_to_admin_menu');

// Add JS scripts to WordPress for use
function __wps__groups_init()
{
}

/* ====================================================== SET SHORTCODE ====================================================== */


add_action('init', '__wps__groups_init');
add_shortcode(WPS_SHORTCODE_PREFIX.'-groups', '__wps__groups');  
add_shortcode(WPS_SHORTCODE_PREFIX.'-group', '__wps__group');  
add_shortcode(WPS_SHORTCODE_PREFIX.'-group-members', '__wps__group_members');  
add_shortcode(WPS_SHORTCODE_PREFIX.'-group-settings', '__wps__group_settings');  


?>
