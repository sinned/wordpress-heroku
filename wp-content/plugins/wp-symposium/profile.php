<?php
/*
WP Symposium Profile
Description: Member Profile component for the Symposium suite of plug-ins. Also enables Friends. Put [symposium-profile], [symposium-settings], [symposium-personal], [symposium-friends] or [symposium-extended] on any WordPress page to display relevant content. If Gallery in use, can also use [symposium-galleries].
*/


// Get constants
require_once(dirname(__FILE__).'/default-constants.php');


// [symposium-member-header] (just header)
function __wps__profile_member_header()  
{  
	return __wps__show_profile("header");
	exit;		
}

// [symposium-profile-menu] 
function __wps__profile_member_menu()  
{  
	global $wpdb, $current_user;

	$html = "";
	
	if (is_user_logged_in()) {
		
		if (isset($_GET['uid'])) {
			$uid = $_GET['uid'];
		} else {
			$uid = $current_user->ID;
		}	        			

		$html .= "<div class='__wps__wrapper'>";
		$html .= "<div id='profile_menu' style='margin-left: 0px;'>";
		$html .= __wps__show_profile_menu($uid, $current_user->ID);
		$html .= "</div>";
		$html .= "</div>";
		
	} else {
	
		$html = "&nbsp;";
		
	}
		
	return $html;
	exit;
		
}

// [symposium-stream] (aggregated wall)
function __wps__stream($view)  
{  
	global $current_user;
	
	$view = ($view == '') ? $view = 'activity' : $view = $view['view'];
	
	$html = "<div class='__wps__wrapper'>";

	// Facebook Connect
	if (function_exists('__wps__facebook'))						
		$html .= __wps__get_facebook();	
		
	$html .= __wps__buffer(__wps__profile_body(0, $current_user->ID, 0, "stream_".$view, 0, false));
	$html .= "</div>";
	        			
	return $html;
	exit;
		
}

// [symposium-profile] (wall)
function __wps__profile()  
{  
	        			
	return __wps__show_profile(get_option(WPS_OPTIONS_PREFIX.'_wps_profile_default'));
	exit;
		
}

// [symposium-activity] (friends activity)
function __wps__profile_activity()  
{  
										
	return __wps__show_profile("activity");
	exit;
		
}

// [symposium-all] (all activity)
function __wps__profile_all()  
{  
										
	return __wps__show_profile("all");
	exit;
		
}

// [symposium-friends]
function __wps__profile_friend()  
{  

	return __wps__show_profile("friends");
	exit;
		
}

// [symposium-personal]
function __wps__profile_personal()  
{  
										
	return __wps__show_profile("personal");
	exit;
		
}

// [symposium-settings]
function __wps__profile_settings()  
{  
										
	return __wps__show_profile("settings");
	exit;
		
}

// [symposium-extended]
function __wps__profile_extended()  
{  
										
	return __wps__show_profile("extended");
	exit;
		
}

// [symposium-avatar]
function __wps__profile_avatar()  
{  
										
	return __wps__show_profile("avatar");
	exit;
		
}


// [symposium-gallery]
function __wps__menu_gallery()  
{  
										
	return __wps__show_profile("gallery");
	exit;
		
}

// Adds profile page
function __wps__show_profile($page)  
{  

	global $wpdb, $current_user;

	$uid = '';
	
	if (isset($_POST['from']) && $_POST['from'] == 'small_search') {
		if ($_POST['uid'] == '') {
			$search = $_POST['member_small'];
			$uid = $wpdb->get_var($wpdb->prepare("SELECT u.ID FROM ".$wpdb->base_prefix."users u WHERE u.display_name LIKE '%s%%'", $search));
		}
	} 
	if ($uid == '') {

		if (isset($_GET['uid'])) {
			$uid = $_GET['uid'];
		} else {
			if (isset($_POST['uid'])) {
				$uid = $_POST['uid'];
			} else {
				$uid = $current_user->ID;
			}
		}

	}
	$uid2 = $current_user->ID;

	// resolve stubs if using permalinks
	if ( get_option(WPS_OPTIONS_PREFIX.'_permalink_structure') && get_query_var('stub')) {
		$stubs = explode('/', get_query_var('stub'));
		$stub0 = $stubs[0];
		if (WPS_DEBUG) echo $stub0.'<br />';
		
		if ($stub0) {
			$sql = "SELECT ID FROM ".$wpdb->base_prefix."users WHERE replace(display_name, ' ', '') = %s";
			$id = $wpdb->get_var($wpdb->prepare($sql, $stub0));
			if (WPS_DEBUG) echo $wpdb->last_query.'<br />';
			if ($id) {
				$uid = $id;
			}
		}
	}
		
	// Use default layout, or templates?
	if (get_option(WPS_OPTIONS_PREFIX.'_use_templates') != "on") {
		
		$html = "<div class='__wps__wrapper'>";
			
			$html .= "<div id='profile_header_div'>";
			$html .= "<div id='profile_label'>[profile_label]</div>";
			$html .= "<div id='profile_header_panel'>";
			$html .= "<div id='profile_photo' class='corners'>[avatar,200]</div>";
			$html .= "<div id='profile_details'>";
			$html .= "<div id='profile_name'>[display_name]</div>";
			$html .= "<p>[location]<br />[born]</p>";

			// Include any extended fields
			$sql = "SELECT * FROM ".$wpdb->base_prefix."symposium_extended";
			$extensions = $wpdb->get_results($sql);

			$ext_rows = array();		
			if ($extensions) {		
				foreach ($extensions as $extension) {
					array_push ($ext_rows, array (	'eid'=>$extension->eid,
													'slug'=>$extension->extended_slug,
													'order'=>$extension->extended_order ) );
				}
			}						
			if ($ext_rows) {
				$include = get_option(WPS_OPTIONS_PREFIX.'_profile_extended_fields');
				$ext_rows = __wps__sub_val_sort($ext_rows,'order');
				foreach ($ext_rows as $row) {
					if (strpos($include, $row['eid'].',') !== FALSE)
						$html .= '[ext_'.$row['slug'].']';
				}
			}
			
			$html .= "</div>";
			$html .= "</div>";
			$html .= "</div>";
			$html .= "<div id='profile_actions_div'>[actions][poke][follow]</div>";
	
			$html .= "<div id='force_profile_page' style='display:none'>".$page."</div>";
			$html .= "<div id='profile_body_tabs_wrapper'>";
			$html .= "[menu_tabs]";
			$html .= "<div id='profile_body' class='profile_body_no_menu'>[page]</div>";
			$html .= "</div>";

		$html .= '</div>';

		$privacy = __wps__get_meta($uid, 'share');
		$html .= '<div id="__wps__current_user_page" style="display:none">'.$uid.'</div>';

		if (is_user_logged_in() || $privacy == 'public') {		

			$display_name = $wpdb->get_var($wpdb->prepare("SELECT display_name FROM ".$wpdb->base_prefix."users WHERE ID = %d", $uid));
		
			$html = str_replace("[display_name]", $display_name, $html);		

			// Profile label
			if ($label = __wps__get_meta($uid, 'profile_label')) {
				$html = str_replace("[profile_label]", $label, $html);
			} else {
				$html = str_replace("<div id='profile_label'>[profile_label]</div>", '', $html);
			}
			
			// Follow/Unfollow
			if (function_exists('__wps__profile_plus') && is_user_logged_in() && $uid != $uid2) {
				if (__wps__is_following($uid2, $uid)) {
					$html = str_replace("[follow]", '<input type="submit" ref="unfollow" value="'.__('Unfollow', WPS_TEXT_DOMAIN).'" class="__wps__button follow-button">', $html);
				} else {
					$html = str_replace("[follow]", '<input type="submit" ref="follow" value="'.__('Follow', WPS_TEXT_DOMAIN).'" class="__wps__button follow-button">', $html);
				}
			} else {
				$html = str_replace("[follow]", '', $html);
			}
		
			// Poke
			if (get_option(WPS_OPTIONS_PREFIX.'_use_poke') == 'on' && is_user_logged_in() && $uid != $uid2) {
				$html = str_replace("[poke]", '<input type="submit" value="'.get_option(WPS_OPTIONS_PREFIX.'_poke_label').'" class="__wps__button poke-button">', $html);
			} else {
				$html = str_replace("[poke]", '', $html);
			}
		
			// Extended fields
			if (strpos($html, '[ext_') !== FALSE) {
				// Prepare array for use
				$sql = "SELECT * FROM ".$wpdb->base_prefix."symposium_extended";
				$extensions = $wpdb->get_results($sql);
		
				$ext_rows = array();		
				if ($extensions) {		
					foreach ($extensions as $extension) {
						$value = __wps__get_meta($uid, 'extended_'.$extension->extended_slug);

						// New way
						$value = stripslashes($extension->extended_default);

						if ($extension->extended_type == "List") {
							$sql = "SELECT meta_value FROM ".$wpdb->base_prefix."usermeta WHERE user_id = %d and meta_key = 'symposium_extended_".$extension->extended_slug."'";
							if ($listitem = $wpdb->get_row($wpdb->prepare($sql, $uid))) {
								$value = stripslashes($listitem->meta_value);
							}						
						}

						if ($extension->extended_type == "Checkbox") {
							$sql = "SELECT meta_value FROM ".$wpdb->base_prefix."usermeta WHERE user_id = %d and meta_key = 'symposium_extended_".$extension->extended_slug."'";
							if ($checkbox = $wpdb->get_row($wpdb->prepare($sql, $uid))) {
								$value = stripslashes($checkbox->meta_value);
							}
						}

						if ($extension->extended_type == "Text" || $extension->extended_type == "Textarea") {
							$sql = "SELECT meta_value FROM ".$wpdb->base_prefix."usermeta WHERE user_id = %d and meta_key = 'symposium_extended_".$extension->extended_slug."'";
							if ($text = $wpdb->get_row($wpdb->prepare($sql, $uid))) {
								$value = stripslashes($text->meta_value);
							}
						}

						if ($extension->extended_type == 'Checkbox' || $value) {
							array_push ($ext_rows, array (	'slug'=>$extension->extended_slug,
															'name'=>$extension->extended_name,
															'value'=>$value,
															'type'=>$extension->extended_type,
															'order'=>$extension->extended_order ) );
						}
					}
				}
						
				$c = 0;
				while ($c < 100 && strpos($html, '[ext_') !== FALSE) {
					$ext = '';
					$c++;
					$s1 = strpos($html, '[ext_');
					$s2 = strpos($html, ']', $s1+1);
					$start = substr($html, 0, $s1);
					$code = substr($html, $s1+5, $s2-$s1-5);		

					$end = substr($html, $s2+1, strlen($html)-$s1);
					
					if ( ($uid == $uid2) || (is_user_logged_in() && strtolower($privacy) == 'everyone') || (strtolower($privacy) == 'public') || (strtolower($privacy) == 'friends only' && __wps__friend_of($uid, $current_user->ID)) ) {

						if ($ext_rows) {
							
							$ext_rows = __wps__sub_val_sort($ext_rows,'order');
							foreach ($ext_rows as $row) {
								
								if (strtolower($row['slug']) == strtolower($code)) {
									if ($row['type'] == 'Checkbox' && !$row['value'] && get_option(WPS_OPTIONS_PREFIX.'_profile_show_unchecked') != 'on') { 
										// Don't show if unchecked and chosen not to show (in Profile config)
									} else {


										if ($row['type'] == 'Text' && $row['value']) {
											$ext .= '<div class="__wps__profile_page_header_ext_label">'.$row['name'].'</div>';
											$ext .= '<div class="__wps__profile_page_header_ext_value">'.stripslashes(stripslashes($row['value'])).'</div>';
										}
										
										if ($row['type'] == 'Textarea' && $row['value']) {
											$ext .= '<div class="__wps__profile_page_header_ext_label">'.stripslashes($row['name']).'</div>';
											$ext .= '<div class="__wps__profile_page_header_ext_value">'.stripslashes(str_replace(chr(10),'<br />',__wps__make_url(stripslashes($row['value'])))).'</div>';
										}
										
										if ($row['type'] == 'List' && $row['value']) {
											$ext .= '<div class="__wps__profile_page_header_ext_label">'.stripslashes($row['name']).'</div>';
											$ext .= '<div class="__wps__profile_page_header_ext_value">'.str_replace(chr(10),'<br />',stripslashes(__wps__make_url($row['value']))).'</div>';
										}										
										
										if ($row['type'] == 'Checkbox') {
											if (get_option(WPS_OPTIONS_PREFIX.'_profile_show_unchecked') == 'on' || $row['value']) {
												$ext .= '<div class="__wps__profile_page_header_ext_label">';
												$ext .= stripslashes($row['name'])."&nbsp;";
												if ($row['value']) { 
													$ext .= "<img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/tick.png' />"; 
												} else {
													$ext .= "<img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/cross.png' />"; 
												}
												$ext .= '</div>';
											}
										}
												
									}
								}
							}
							
						} 
						if ($c == 1) {
							$html = $start.'<div id="__wps__profile_page_header_ext_fields">'.$ext;
						} else {
							$html = $start.$ext;
						}
						if (strpos($end, '[ext_') === FALSE)
							$html .= '</div>';
							
						$html .= $end;

					} else {
						$html = $start.$end;
					}
										
				}	
			}
					
			$location = "";
			$born = "";
			
			if ( ($uid == $uid2) || (is_user_logged_in() && strtolower($privacy) == 'everyone') || (strtolower($privacy) == 'public') || (strtolower($privacy) == 'friends only' && __wps__friend_of($uid, $uid2)) ) {
					
				$city = __wps__get_meta($uid, 'extended_city');
				$country = __wps__get_meta($uid, 'extended_country');
				
				if ($city != '') { $location .= $city; }
				if ($city != '' && $country != '') { $location .= ", "; }
				if ($country != '') { $location .= $country; }
		
				$day = (int)__wps__get_meta($uid, 'dob_day');
				$month = __wps__get_meta($uid, 'dob_month');
				$year = (int)__wps__get_meta($uid, 'dob_year');
		
				if ($year > 0 || $month > 0 || $day > 0) {
					$monthname = __wps__get_monthname($month);
					if ($day == 0) $day = '';
					if ($year == 0) $year = '';
					$born = get_option(WPS_OPTIONS_PREFIX.'_show_dob_format');
					$born = ( $born != '') ? $born : __('Born', WPS_TEXT_DOMAIN).' %monthname %day%th, %year';
					$day0 = str_pad($day, 2, '0', STR_PAD_LEFT);
					$month = ($month > 0) ? str_pad($month, 2, '0', STR_PAD_LEFT) : '';
					$month0 = ($month > 0) ? str_pad($month, 2, '0', STR_PAD_LEFT) : '';
					$year = ($year > 0) ? $year : '';
					$born = str_replace('%0day', $day0, $born);
					$born = str_replace('%day', $day, $born);
					$born = str_replace('%monthname', $monthname, $born);
					$born = str_replace('%0month', $month0, $born);
					$born = str_replace('%month', $month, $born);
					$born = str_replace('%year', $year, $born);
					$th = 'th';
					if ($day == 1 || $day == 21 || $day == 31) $th = 'st';
					if ($day == 2 || $day == 22) $th = 'nd';
					if ($day == 3 || $day == 23) $th = 'rd';
					if (strpos($born, '%th')) {
						if ($day) {
							$born = str_replace('%th', $th, $born);
						} else {
							$born = str_replace('%th', '', $born);
						}
					}
					$born = str_replace(' ,', ',', $born);
					if ($year == '') $born = str_replace(', ', '', $born);
					$born = apply_filters ( '__wps__profile_born', $born, $day, $month, $year );
				
				}
				
			} else {
			
				if (strtolower($privacy) == 'friends only') {
					$html = str_replace("[born]", sprintf(__("Personal information only for %s.", WPS_TEXT_DOMAIN), get_option(WPS_OPTIONS_PREFIX.'_alt_friends')), $html);						
				}
		
				if (strtolower($privacy) == 'nobody') {
					$html = str_replace("[born]", __("Personal information is private.", WPS_TEXT_DOMAIN), $html);						
				}
				
			}
		
			$html = str_replace("[location]", $location, $html);
			if (get_option(WPS_OPTIONS_PREFIX.'_show_dob') == 'on') {
				$html = str_replace("[born]", $born, $html);
			} else {
				$html = str_replace("[born]", "", $html);
			}
			
			if ( is_user_logged_in() ) {
				
				$actions = '';
				
				if ($uid == $uid2) {

					// Facebook Connect
					if (function_exists('__wps__facebook'))						
						$actions .= __wps__get_facebook();	
														
				} else {
		
					// Buttons									
					if (__wps__friend_of($uid, $uid2)) {
			
						// A friend
						// Send mail
						if (function_exists('__wps__mail'))
							$actions .= '<input type="submit" class="__wps__button" id="profile_send_mail_button" value="'.__('Send a Mail...', WPS_TEXT_DOMAIN).'" />';
						
					} 
					if (!__wps__friend_of($uid, $uid2)) {
						
						if (__wps__pending_friendship($uid)) {
							// Pending
							$actions .= '<input type="submit" title="'.$uid.'" id="cancelfriendrequest" class="__wps__button" value="'.sprintf(__('Cancel %s Request', WPS_TEXT_DOMAIN), get_option(WPS_OPTIONS_PREFIX.'_alt_friend')).'" /> ';
							$actions .= '<div id="cancelfriendrequest_done" class="hidden addasfriend_input">'.sprintf(__('%s Request Cancelled', WPS_TEXT_DOMAIN), get_option(WPS_OPTIONS_PREFIX.'_alt_friend')).'</div>';
						} else {							
							// Not a friend
							$actions .= '<div id="addasfriend_done1_'.$uid.'" class="addasfriend_input">';
							$actions .= '<div id="add_as_friend_message">';
							$actions .= '<input type="text" title="'.$uid.'" id="addfriend" class="input-field" onclick="this.value=\'\'" value="'.sprintf(__('Add as a %s', WPS_TEXT_DOMAIN), get_option(WPS_OPTIONS_PREFIX.'_alt_friend')).'...."';
							if (!get_option(WPS_OPTIONS_PREFIX.'_show_buttons')) {
								$actions .= ' style="width:210px"';
							}
							$actions .= '>';
							if (get_option(WPS_OPTIONS_PREFIX.'_show_buttons')) {
								$actions .= '<input type="submit" title="'.$uid.'" id="addasfriend" class="__wps__button" value="'.__('Add', WPS_TEXT_DOMAIN).'" /> ';
							}
			
							$actions .= '</div></div>';
							$actions .= '<div id="addasfriend_done2_'.$uid.'" class="hidden addasfriend_input">'.sprintf(__('%s Request Sent', WPS_TEXT_DOMAIN), get_option(WPS_OPTIONS_PREFIX.'_alt_friend')).'</div>';
							
						}

						if (__wps__get_current_userlevel() == 5) {
							// Send mail if WPS admin
							if (function_exists('__wps__mail'))
								$actions .= '<input type="submit" class="__wps__button" style="float:left" id="profile_send_mail_button" value="'.__('Send a Mail...', WPS_TEXT_DOMAIN).'" />';
						}
						
					}				
					
				}
						
				$html = str_replace("[actions]", $actions, $html);						
			} else {
				$html = str_replace("[actions]", "", $html);												
			}
			
			// Photo
			if (strpos($html, '[avatar') !== FALSE) {
				if (strpos($html, '[avatar]')) {
					$html = str_replace("[avatar]", get_avatar($uid, 200), $html);						
				} else {
					$x = strpos($html, '[avatar');
					$y = strpos($html, ']', $x);
					$diff = $y-$x-8;
					$avatar = substr($html, 0, $x);
					$avatar2 = substr($html, $x+8, $diff);
					$avatar3 = substr($html, $x+$diff+9, strlen($html)-$x-($diff+9));
		
					$html = $avatar . get_avatar($uid, $avatar2) . $avatar3;
					
				}
			}	

			// Put in menu
			$html = str_replace("[menu_tabs]", __wps__show_profile_menu_tabs($uid, $uid2), $html);

			// add activity stream
			if ($page == 'activity' || $page == 'all') {
				$view = get_option(WPS_OPTIONS_PREFIX.'_wps_profile_default');
				switch($view) {									
					case 'extended':$view = ''; break;
					case 'all':$view = 'all_activity'; break;
					case 'activity':$view = 'friends_activity'; break;
					case 'extended':$view = ''; break;
					default: break;
				}
				$body = __wps__buffer(__wps__profile_body($uid, $uid2, 0, $view, 0, false));
			} else {
				$body = '';
			}
			$html = str_replace("[page]", $body, $html);												

			// Filter for profile header
			$html = apply_filters ( '__wps__profile_header_filter', $html, $uid );			
			
		} else {
			
			$html = __wps__show_login_link(__("Please <a href='%s'>login</a> to view this member's profile.", WPS_TEXT_DOMAIN));
			
		}

		
	} else {
		
		$share = __wps__get_meta($uid, 'share');
		if (WPS_DEBUG) echo 'UID:'.$uid.'<br />';
		$html = '<div id="__wps__current_user_page" style="display:none">'.$uid.'</div>';
		
		if (is_user_logged_in() || $share == 'public') {		
			
			$user = $wpdb->get_row($wpdb->prepare("SELECT display_name FROM ".$wpdb->base_prefix."users WHERE ID = %d", $uid));
			
			if ($user) {
				
				// Wrapper
				$html .= "<div class='__wps__wrapper'>";
	
					$html .= __wps__profile_header($uid, $current_user->ID, __wps__get_url('mail'), $user->display_name);
	
					if ($page != 'header') {
						
						if (isset($_GET['view']) && $_GET['view'] != '') {
							$page = $_GET['view'];
						}
						if (isset($_POST['view']) && $_POST['view'] != '') {
							$page = $_POST['view'];
						}
						if ($page == '') { $page = get_option(WPS_OPTIONS_PREFIX.'_wps_profile_default'); }
						
						$template = get_option(WPS_OPTIONS_PREFIX.'_template_profile_body');
						$template = str_replace("[]", "", stripslashes($template));
						
						// Put in forced profile page
						$template = str_replace("[default]", $page, stripslashes($template));
	
						// Put in busy image
						$template = str_replace("[page]", "<img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/busy.gif' />", stripslashes($template));
	
						// Put in menu
						$template = str_replace("[menu]", __wps__show_profile_menu($uid, $current_user->ID), stripslashes($template));
						$template = str_replace("[menu_tabs]", __wps__show_profile_menu_tabs($uid, $current_user->ID), stripslashes($template));
	
						$html .= $template;
	  				
						$html .= "<br class='clear' />";
						
					}
						
				
				$html .= "</div>";
				$html .= "<div style='clear: both'></div>";
				
			} else {
				
				$html = __("Member not found, sorry", WPS_TEXT_DOMAIN);
			}
		
		} else {
			
			$html = __wps__show_login_link(__("Please <a href='%s'>login</a> to view this member's profile.", WPS_TEXT_DOMAIN));
			
		}	
	
		// Finally, substitute other codes
		$html = str_replace("[menu_tabs]", __wps__show_profile_menu_tabs($uid, $current_user->ID), stripslashes($html));

		// Facebook Connect
	}

				
	return $html;
	exit;

}  

function __wps__profile_header($uid1, $uid2, $url, $display_name) {
	
	global $wpdb, $current_user;
	$plugin = WPS_PLUGIN_URL;

	$template = get_option(WPS_OPTIONS_PREFIX.'_template_profile_header');
	$html = str_replace("[]", "", stripslashes($template));

	$privacy = __wps__get_meta($uid1, 'share');
	
	$html = str_replace("[display_name]", $display_name, $html);
	
	// Extended fields
	if (strpos($html, '[ext_') !== FALSE) {
		
		// Prepare array for use
		$sql = "SELECT * FROM ".$wpdb->base_prefix."symposium_extended";
		$extensions = $wpdb->get_results($sql);

		$ext_rows = array();		
		if ($extensions) {		
			foreach ($extensions as $extension) {
				$value = __wps__get_meta($uid1, 'extended_'.$extension->extended_slug);
				if ($extension->extended_type == 'Checkbox' || $value) {
					array_push ($ext_rows, array (	'slug'=>$extension->extended_slug,
													'name'=>$extension->extended_name,
													'value'=>$value,
													'type'=>$extension->extended_type,
													'order'=>$extension->extended_order ) );
				}
			}
		}
				
		$c = 0;
		while ($c < 100 && strpos($html, '[ext_') !== FALSE) {
			$ext = '';
			$c++;
			$s1 = strpos($html, '[ext_');
			$s2 = strpos($html, ']', $s1+1);
			$start = substr($html, 0, $s1);
			$code = substr($html, $s1+5, $s2-$s1-5);

			$end = substr($html, $s2+1, strlen($html)-$s1);
			if ( ($uid1 == $uid2) || (is_user_logged_in() && strtolower($privacy) == 'everyone') || (strtolower($privacy) == 'public') || (strtolower($privacy) == 'friends only' && __wps__friend_of($uid1, $current_user->ID)) ) {

				if ($ext_rows) {
					
					$ext_rows = __wps__sub_val_sort($ext_rows,'order');
					foreach ($ext_rows as $row) {
						if (strtolower($row['slug']) == strtolower($code)) {
							if ($row['type'] == 'Checkbox' && !$row['value'] && get_option(WPS_OPTIONS_PREFIX.'_profile_show_unchecked') != 'on') { 
								// Don't show if unchecked and chosen not to show (in Profile config)
							} else {

								if ($row['type'] == 'Text' && $row['value']) {
									$ext .= '<div class="__wps__profile_page_header_ext_label">'.stripslashes($row['name']).'</div>';
									$ext .= '<div class="__wps__profile_page_header_ext_value">'.stripslashes(__wps__make_url($row['value'])).'</div>';
								}
								if ($row['type'] == 'Textarea' && $row['value']) {
									$ext .= '<div class="__wps__profile_page_header_ext_label">'.stripslashes($row['name']).'</div>';
									$ext .= '<div class="__wps__profile_page_header_ext_value">'.str_replace(chr(10),'<br />',stripslashes(__wps__make_url($row['value']))).'</div>';
								}
								if ($row['type'] == 'List' && $row['value']) {
									$ext .= '<div class="__wps__profile_page_header_ext_label">'.stripslashes($row['name']).'</div>';
									$ext .= '<div class="__wps__profile_page_header_ext_value">'.str_replace(chr(10),'<br />',stripslashes(__wps__make_url($row['value']))).'</div>';
								}
								if ($row['type'] == 'Checkbox') {
									if (get_option(WPS_OPTIONS_PREFIX.'_profile_show_unchecked') == 'on' || $row['value']) {
										$ext .= '<div class="__wps__profile_page_header_ext_label">'.stripslashes($row['name'])."&nbsp;";
										if ($row['value']) { 
											$ext .= "<img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/tick.png' />"; 
										} else {
											$ext .= "<img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/cross.png' />"; 
										}
										$ext .= '</div>';
									}
								}
							}
						}
					}
				} 
				
				if ($c == 1) {
					$html = $start.'<div id="__wps__profile_page_header_ext_fields">'.$ext;
				} else {
					$html = $start.$ext;
				}
				if (strpos($end, '[ext_') === FALSE)
					$html .= '</div>';
					
				$html .= $end;
			} else {
				$html = $start.$end;
			}
		}	
	}
		
	// Profile label
	if ($label = __wps__get_meta($uid1, 'profile_label')) {
		$html = str_replace("[profile_label]", $label, $html);
	} else {
		$html = str_replace("<div id='profile_label'>[profile_label]</div>", '', $html);
	}
	
	// Follow/Unfollow
	if (function_exists('__wps__profile_plus') && is_user_logged_in() && $uid1 != $uid2) {
		if (__wps__is_following($uid2, $uid1)) {
			$html = str_replace("[follow]", '<input type="submit" ref="unfollow" value="'.__('Unfollow', WPS_TEXT_DOMAIN).'" class="__wps__button follow-button">', $html);
		} else {
			$html = str_replace("[follow]", '<input type="submit" ref="follow" value="'.__('Follow', WPS_TEXT_DOMAIN).'" class="__wps__button follow-button">', $html);
		}
	} else {
		$html = str_replace("[follow]", '', $html);
	}

	// Poke
	if (get_option(WPS_OPTIONS_PREFIX.'_use_poke') == 'on' && is_user_logged_in() && $uid1 != $uid2) {
		$html = str_replace("[poke]", '<input type="submit" value="'.get_option(WPS_OPTIONS_PREFIX.'_poke_label').'" class="__wps__button poke-button">', $html);
	} else {
		$html = str_replace("[poke]", '', $html);
	}

	

	$location = "";
	$born = "";
	
	if ( ($uid1 == $uid2) || (is_user_logged_in() && strtolower($privacy) == 'everyone') || (strtolower($privacy) == 'public') || (strtolower($privacy) == 'friends only' && __wps__friend_of($uid1, $current_user->ID)) ) {
			
		$city = __wps__get_meta($uid1, 'extended_city');
		$country = __wps__get_meta($uid1, 'extended_country');
		
		if ($city != '') { $location .= $city; }
		if ($city != '' && $country != '') { $location .= ", "; }
		if ($country != '') { $location .= $country; }

		$day = (int)__wps__get_meta($uid1, 'dob_day');
		$month = __wps__get_meta($uid1, 'dob_month');
		$year = (int)__wps__get_meta($uid1, 'dob_year');

		if ($year > 0 || $month > 0 || $day > 0) {
			$monthname = __wps__get_monthname($month);
			if ($day == 0) $day = '';
			if ($year == 0) $year = '';
			$born = get_option(WPS_OPTIONS_PREFIX.'_show_dob_format');
			$born = ( $born != '') ? $born : __('Born', WPS_TEXT_DOMAIN).' %monthname %day%th, %year';
			$day0 = str_pad($day, 2, '0', STR_PAD_LEFT);
			$month = ($month > 0) ? str_pad($month, 2, '0', STR_PAD_LEFT) : '';
			$month0 = ($month > 0) ? str_pad($month, 2, '0', STR_PAD_LEFT) : '';
			$year = ($year > 0) ? $year : '';
			$born = str_replace('%0day', $day0, $born);
			$born = str_replace('%day', $day, $born);
			$born = str_replace('%monthname', $monthname, $born);
			$born = str_replace('%0month', $month0, $born);
			$born = str_replace('%month', $month, $born);
			$born = str_replace('%year', $year, $born);
			$th = 'th';
			if ($day == 1 || $day == 21 || $day == 31) $th = 'st';
			if ($day == 2 || $day == 22) $th = 'nd';
			if ($day == 3 || $day == 23) $th = 'rd';
			if (strpos($born, '%th')) {
				if ($day) {
					$born = str_replace('%th', $th, $born);
				} else {
					$born = str_replace('%th', '', $born);
				}
			}
			$born = str_replace(' ,', ',', $born);
			if ($year == '') $born = str_replace(', ', '', $born);
			$born = apply_filters ( '__wps__profile_born', $born, $day, $month, $year );
		
		}
		
	} else {
	
		if (strtolower($privacy) == 'friends only') {
			$html = str_replace("[born]", sprintf(__("Personal information only for %s.", WPS_TEXT_DOMAIN), get_option(WPS_OPTIONS_PREFIX.'_alt_friends')), $html);						
		}

		if (strtolower($privacy) == 'nobody') {
			$html = str_replace("[born]", __("Personal information is private.", WPS_TEXT_DOMAIN), $html);						
		}
		
	}

	$html = str_replace("[location]", $location, $html);
	if (get_option(WPS_OPTIONS_PREFIX.'_show_dob') == 'on') {
		$html = str_replace("[born]", $born, $html);
	} else {
		$html = str_replace("[born]", "", $html);
	}
	
	if ( is_user_logged_in() ) {
		
		$actions = '';
		
		if ($uid1 == $uid2) {

			// Facebook Connect
			if (function_exists('__wps__facebook'))						
				$actions .= __wps__get_facebook();	
												
		} else {

			// Buttons									
			if (__wps__friend_of($uid1, $current_user->ID)) {
	
				// A friend
				// Send mail
				if (function_exists('__wps__mail'))
					$actions .= '<input type="submit" class="__wps__button" id="profile_send_mail_button" value="'.__('Send a Mail...', WPS_TEXT_DOMAIN).'" />';
				
			} else {
				
				if (__wps__pending_friendship($uid1)) {
					// Pending
					$actions .= '<input type="submit" title="'.$uid1.'" id="cancelfriendrequest" class="__wps__button" value="'.sprintf(__('Cancel %s Request', WPS_TEXT_DOMAIN), get_option(WPS_OPTIONS_PREFIX.'_alt_friend')).'" /> ';
					$actions .= '<div id="cancelfriendrequest_done" class="hidden addasfriend_input">'.sprintf(__('%s Request Cancelled', WPS_TEXT_DOMAIN), get_option(WPS_OPTIONS_PREFIX.'_alt_friend')).'</div>';
				} else {							
					// Not a friend
					$actions .= '<div id="addasfriend_done1_'.$uid1.'" class="addasfriend_input" >';
					$actions .= '<div id="add_as_friend_message">';
					$actions .= '<input type="text" title="'.$uid1.'"id="addfriend" class="input-field" onclick="this.value=\'\'" value="'.sprintf(__('Add as a %s', WPS_TEXT_DOMAIN), get_option(WPS_OPTIONS_PREFIX.'_alt_friend')).'...."';
					if (!get_option(WPS_OPTIONS_PREFIX.'_show_buttons')) {
						$actions .= ' style="width:210px"';
					}
					$actions .= '>';
					if (get_option(WPS_OPTIONS_PREFIX.'_show_buttons')) {
						$actions .= '<input type="submit" title="'.$uid1.'" id="addasfriend" class="__wps__button" value="'.__('Add', WPS_TEXT_DOMAIN).'" /> ';
					}
	
					$actions .= '</div></div>';
					$actions .= '<div id="addasfriend_done2_'.$uid1.'" class="hidden addasfriend_input">'.sprintf(__('%s Request Sent', WPS_TEXT_DOMAIN), get_option(WPS_OPTIONS_PREFIX.'_alt_friend')).'</div>';
					
				}
			}				
			
		}

				
		$html = str_replace("[actions]", $actions, $html);						
	} else {
		$html = str_replace("[actions]", "", $html);												
	}
	
	// Photo
	if (strpos($html, '[avatar') !== FALSE) {
		if (strpos($html, '[avatar]')) {
			$html = str_replace("[avatar]", get_avatar($uid1, 200), $html);						
		} else {
			$x = strpos($html, '[avatar');
			$y = strpos($html, ']', $x);
			$diff = $y-$x-8;
			$avatar = substr($html, 0, $x);
			$avatar2 = substr($html, $x+8, $diff);
			$avatar3 = substr($html, $x+$diff+9, strlen($html)-$x-($diff+9));

			$html = $avatar . get_avatar($uid1, $avatar2) . $avatar3;
			
		}
	}	

	
	// Filter for profile header
	$html = apply_filters ( '__wps__profile_header_filter', $html, $uid1 );

	
	return $html;


}

function __wps__show_profile_menu_tabs($uid1, $uid2) {
        	
	global $wpdb, $current_user;

		$menu = '';
		$share = __wps__get_meta($uid1, 'share');		
		$privacy = __wps__get_meta($uid1, 'wall_share');		
		$is_friend = __wps__friend_of($uid1, $current_user->ID);
		if ( $wpdb->get_results( $wpdb->prepare("SELECT meta_key FROM ".$wpdb->base_prefix."usermeta WHERE user_ID = %d AND meta_key LIKE '%symposium_extended_%' AND meta_value != ''", $uid1) ) > 0 ) { $extended = "on"; } else { $extended = ""; }
		
		if ($uid1 == $uid2) {
			$structure = get_option(WPS_OPTIONS_PREFIX."_profile_menu_structure");
		} else {
			$structure = get_option(WPS_OPTIONS_PREFIX."_profile_menu_structure_other");
		}
		$str_arr = explode(chr(10), $structure);
		
		if ( ($uid1 == $uid2) || (is_user_logged_in() && strtolower($privacy) == 'everyone') || (strtolower($privacy) == 'public') || (strtolower($privacy) == 'friends only' && $is_friend) ) {
			
			// Filter for additional menu items 
			$menu .= '<div style="float:right;text-align:right;">'.apply_filters ( '__wps__profile_menu_filter_tabs', $menu, $uid1, $uid2, $privacy, $is_friend, $extended, $share, '' ).'</div>';

			$menu .= '<ul class="__wps__dropdown">';

			// Note pending friends
			$pending_friends = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM ".$wpdb->base_prefix."symposium_friends f WHERE f.friend_to = %d AND f.friend_accepted != 'on'", $uid1));		
			if ( ($pending_friends > 0) && ($uid1 == $uid2) ) {
				$pending_friends = " (".$pending_friends.")";
			} else {
				$pending_friends = "";
			}	
			
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
					$item = str_replace('%f', $pending_friends, $item);
					$menu .= '<li class="__wps__top_menu">'.$item;
					$menu .= '<ul class="__wps__sub_menu">';
				}
				
				// Child item
				if (strpos($item, '=') !== false) {
					list($title,$value) = explode('=', $item);
					$value = str_replace(chr(13), '', $value);
					$i = '';

					$menu = apply_filters ( '__wps__profile_menu_tabs_filter', $menu, $title, $value, $uid1, $uid2, $privacy, $is_friend, $extended, $share );
	
					switch ($value) {
					case 'viewprofile' :
						if ( (is_user_logged_in() && strtolower($share) == 'everyone') || (strtolower($share) == 'public') || (strtolower($share) == 'friends only' && $is_friend) || ($uid1 == $uid2)) {
							$i = '<li id="menu_extended" class="__wps__profile_menu">'.$title.'</li>';
						}
						break;
					case 'details' :
						if ($uid1 == $uid2)
							$i = '<li id="menu_settings" class="__wps__profile_menu" href="javascript:void(0)">'.$title.'</li>';
						break;
					case 'settings':
						if ($uid1 == $uid2)
							$i = '<li id="menu_personal" class="__wps__profile_menu" href="javascript:void(0)">'.$title.'</li>';
						break;
					case 'avatar' :
						if ( ($uid1 == $uid2) && (get_option(WPS_OPTIONS_PREFIX.'_profile_avatars') == "on") )
							$i = '<li id="menu_avatar" class="__wps__profile_menu" href="javascript:void(0)">'.$title.'</li>';
						break;				
					case 'activitymy' :
						$i = '<li id="menu_wall" class="__wps__profile_menu" href="javascript:void(0)">'.$title.'</li>';
						break;
					case 'activityfriends' :
						if (strtolower($share) == 'public' && !(is_user_logged_in())) {
							// don't show friends activity to public
						} else {
							$i = '<li id="menu_activity" class="__wps__profile_menu" href="javascript:void(0)">'.$title.'</li>';
						}
						break;
					case 'activityall' :
						if (strtolower($share) == 'public' && !(is_user_logged_in())) {
							// don't show all activity to public
						} else {
							$i = '<li id="menu_all" class="__wps__profile_menu" href="javascript:void(0)">'.$title.'</li>';
						}
						break;
					case 'myfriends' :
						if ( ($uid1 == $uid2) || (is_user_logged_in() && strtolower($share) == 'everyone') || (strtolower($share) == 'friends only' && $is_friend) || __wps__get_current_userlevel() == 5) {
							if ($uid1 == $uid2) {
								$i = '<li id="menu_friends" class="__wps__profile_menu" href="javascript:void(0)">'.$title.' '.$pending_friends.'</li>';
							} else {
								$i = '<li id="menu_friends" class="__wps__profile_menu" href="javascript:void(0)">'.$title.'</li>';
							}
						}
						break;
					case 'mygroups' :
						if (function_exists('__wps__group'))
							$i = '<li id="menu_groups" class="__wps__profile_menu" href="javascript:void(0)">'.$title.'</li>';
						break;
					default :
						$i = apply_filters ( '__wps__profile_menu_tabs', '', $title, $value, $uid1, $uid2, $privacy, $is_friend, $extended, $share );
						break;
					}
					if ($i) $menu .= $i;
				}
				
			}
			if ($started_top_level) {
				$menu .= '</ul></li>';
			}

			$menu .= '</ul>';
				
			$menu .= '<div id="__wps__menu_tabs_wrapper"></div>';
			
			$menu .= '<div style="clear:both;padding-bottom:20px;"></div>';
		
		} else {
			
			$menu = '';
			
		}
		
	return $menu;

}


function __wps__get_facebook() {

	$profile_url = __wps__get_url('profile');
	$q = __wps__string_query($profile_url);
			
	$fhtml = "<div id='facebook_div'>";
	
		if (!class_exists('__wps__FacebookApiException'))
			include_once("library/src/facebook.php");
		
		$__wps__facebook = new __wps__Facebook(array(
		'appId'=>get_option(WPS_OPTIONS_PREFIX.'_facebook_api'),
		'secret'=>get_option(WPS_OPTIONS_PREFIX.'_facebook_secret'),
		'cookie'=>true
		));
		
		if (isset($_GET['fb']) && $_GET['fb'] == 'lo') {
			setcookie('fbs_'.$__wps__facebook->getAppId(), '', time()-100, '/', 'domain.com');
			session_destroy();
			header('Location: '.$profile_url);
		} 
		
		// Get User ID
		$user = $__wps__facebook->getUser();
		
		if ($user) {
			
		  try {
	
		    // Proceed knowing you have a logged in user who's authenticated.
		    $user_profile = $__wps__facebook->api('/me');
		    
			$fhtml .= "<input type='checkbox' CHECKED id='post_to_facebook' /> ";
			$fhtml .= sprintf(__("Share post on Facebook as <a target='_blank' href='%s'>%s</a>", WPS_TEXT_DOMAIN), $user_profile['link'], $user_profile['name']);
	
		    $fhtml .= ' (<a href="'.$profile_url.$q.'fb=lo">'.__('Disconnect', WPS_TEXT_DOMAIN).'</a>)';
			
		  } catch (__wps__FacebookApiException $e) {
		      
			$result = $e->getResult();
	        echo "<pre>User authenticated";
	        print_r($result);
	        echo "</pre>";
		    $user = null;
		    
		  }
		} else {					
	
		  $fhtml .= "<img src='".WPS_PLUGIN_URL."/images/logo_facebook.png' style='float:left; margin-right: 5px;' />";						
			$params = array(
			    'canvas' => 1,
			    'scope'  => 'publish_actions,user_about_me',
			    'fbconnect' => 1
			);
		  $fhtml .= '<a href="'.$__wps__facebook->getLoginUrl($params).'">'.__('Connect to Facebook', WPS_TEXT_DOMAIN).'</a>';
	
		}
				
	$fhtml .= "</div>";
	
	return $fhtml;
	
}

/* ====================================================== SET SHORTCODE ====================================================== */

if (!is_admin()) {

	// Check for extensions
	if (!defined('WPS_EXT_PROFILE_NAME')) {
		add_shortcode(WPS_SHORTCODE_PREFIX.'-profile', '__wps__profile');  
		add_shortcode(WPS_SHORTCODE_PREFIX.'-friends', '__wps__profile_friend');  
		add_shortcode(WPS_SHORTCODE_PREFIX.'-all', '__wps__profile_all');  
		add_shortcode(WPS_SHORTCODE_PREFIX.'-personal', '__wps__profile_personal');  
		add_shortcode(WPS_SHORTCODE_PREFIX.'-settings', '__wps__profile_settings');  
		add_shortcode(WPS_SHORTCODE_PREFIX.'-avatar', '__wps__profile_avatar');  
		add_shortcode(WPS_SHORTCODE_PREFIX.'-gallery', '__wps__menu_gallery');  
	}

	add_shortcode(WPS_SHORTCODE_PREFIX.'-stream', '__wps__stream');  
	add_shortcode(WPS_SHORTCODE_PREFIX.'-activity', '__wps__profile_activity');  
	add_shortcode(WPS_SHORTCODE_PREFIX.'-extended', '__wps__profile_extended');  
	add_shortcode(WPS_SHORTCODE_PREFIX.'-menu', '__wps__profile_member_menu');  
	add_shortcode(WPS_SHORTCODE_PREFIX.'-member-header', '__wps__profile_member_header');  

}
?>
