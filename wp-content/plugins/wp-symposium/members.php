<?php
/*
WP Symposium Members Directory
Description: Directory component for the WP Symposium suite of plug-ins. Put [symposium-members] on any WordPress page.
*/
	

// Get constants
require_once(dirname(__FILE__).'/default-constants.php');

function __wps__members($attr) {	

	global $wpdb, $current_user;
	wp_get_current_user();

	$plugin = WPS_PLUGIN_URL;
	$dbpage = $plugin.'/symposium_members_db.php';
	
	$roles = isset($attr['roles']) ? $attr['roles'] : '';
	if ($roles) {
		if (strpos($roles, ' ') !== FALSE) $roles = str_replace(' ', '', $roles);
		if (strpos($roles, '_') !== FALSE) $roles = str_replace('_', '', $roles);
	}
	
	$html = '<div class="__wps__wrapper">';

		if (!is_user_logged_in() && get_option(WPS_OPTIONS_PREFIX.'dir_hide_public') ) {

			echo __wps__show_login_link(__("You need to be <a href='%s'>logged in</a> to view the directory.", WPS_TEXT_DOMAIN));

		} else {

			// If 'term' is passed as a parameter, it will influence the results
			$me = $current_user->ID;
			$page = 1;

			// Now check against shortcode parameter (overrides global roles)
			if ( !isset( $wp_roles ) ) $wp_roles = new WP_Roles();													
			if ($roles) {
				$dir_levels = $roles;
			} else {
				// Get included global levels
				$dir_levels = strtolower(get_option(WPS_OPTIONS_PREFIX.'_dir_level'));
				if (strpos($dir_levels, ' ') !== FALSE) $dir_levels = str_replace(' ', '', $dir_levels);
				if (strpos($dir_levels, '_') !== FALSE) $dir_levels = str_replace('_', '', $dir_levels);
			}
			$html .= '<div id="__wps__directory_roles" style="display:none">'.$dir_levels.'</div>';
			
			// Stores start value for more
			$start = get_option(WPS_OPTIONS_PREFIX.'_dir_page_length')+1;
			$html .= '<div id="symposium_directory_start" style="display:none">'.$start.'</div>';
			$html .= '<div id="symposium_directory_page_length" style="display:none">'.get_option(WPS_OPTIONS_PREFIX.'_dir_page_length').'</div>';
			
			$term = "";
			if (isset($_POST['member'])) { $term .= strtolower($_POST['member']); }
			if (isset($_GET['term'])) { $term .= strtolower($_GET['term']); }
			
			$html .= "<div class='members_row' style='padding:0px'>";
				$html .= '<div style="float:right; padding:0px;padding-top:2px;">';
				$html .= '<input id="members_go_button" type="submit" class="__wps__button" value="'.__("Search", WPS_TEXT_DOMAIN).'" />';
				if (is_user_logged_in()) {
					$html .= '<div style="clear:both;"><input type="checkbox" id="symposium_member_friends" /> '.__('Only friends', WPS_TEXT_DOMAIN).'</div>';
				}
				$html .= '</div>';	
				$html .= '<input type="text" id="symposium_member" autocomplete="off" name="symposium_member" class="members_search_box" value="'.$term.'" />';
				if (!get_option(WPS_OPTIONS_PREFIX.'_wps_lite') && function_exists('__wps__profile_plus')) {
					$html .= '<div style="clear:both">';
					$html .= '<a href="javascript:void(0);" id="symposium_show_advanced" /> '.__('Advanced search', WPS_TEXT_DOMAIN).'</a>';
					$html .= '</div>';
				}
			$html .= "</div>";

			if (!get_option(WPS_OPTIONS_PREFIX.'_wps_lite') && function_exists('__wps__profile_plus')) {
				// Loop through extended fields and offer as a search options (if there are any)
				$extensions = $wpdb->get_results("SELECT * FROM ".$wpdb->base_prefix."symposium_extended WHERE search = 'on' ORDER BY extended_order, extended_name");

				if ($extensions) {

					$html .= "<div id='symposium_advanced_search' style='width:90%;padding:0px;display:none;'>";
					
					$html .= "<table style='border:0'>";	

					foreach ($extensions as $extension) {
										
						$html .= '<tr>';

							if ($extension->extended_type == 'Checkbox') {
								$html .= '<td id="__wps__ext_label_'.$extension->eid.'" style="border:0">';
								$html .= stripslashes($extension->extended_name);
								$html .= '</td><td id="__wps__ext_value_'.$extension->eid.'" style="border:0">';
								$html .= '<input rel="checkbox" id="'.$extension->eid.'" class="symposium_extended_search" type="checkbox" name="extended_value[]" />';
								$html .= '</td>';
							}
							if ($extension->extended_type == 'List') {
								$html .= '<td id="__wps__ext_label_'.$extension->eid.'" style="border:0">';
								$html .= stripslashes($extension->extended_name).':';
								$html .= '</td><td id="__wps__ext_value_'.$extension->eid.'" style="border:0">';
								$html .= '<select rel="list" id="'.$extension->eid.'" class="symposium_extended_search" name="extended_value[]">';
								$items = explode(',', $extension->extended_default);
								$html .= '<option value="'.__('Any', WPS_TEXT_DOMAIN).'">'.__('Any', WPS_TEXT_DOMAIN).'</option>';
								foreach ($items as $item) {
									$html .= '<option value="'.$item.'">'.$item.'</option>';
								}												
								$html .= '</select>';
								$html .= '</td>';
							}

						$html .= '</tr>';
					}
					
					$html .= "</table>";
					
					$html .= "</div>";					
				}
			}			
			
			// Sort by option
			$order = get_option(WPS_OPTIONS_PREFIX.'_dir_atoz_order');
			if ($order == 'surname') { $orderby = 'surname'; }
			if ($order == 'display_name') { $orderby = 'u.display_name'; }
			if ($order == 'distance') { $orderby = 'distance, u.display_name'; }
			if ($order == 'last_activity') { $orderby = 'cast(m4.meta_value as datetime) DESC'; }		

			$html .= '<br /><div id="symposium_members_orderby_div">';
				$html .= __('Sort by:', WPS_TEXT_DOMAIN).' ';
				$html .= '<select id="symposium_members_orderby">';
					$html .= '<option value="last_activity"';
						if ($order == 'last_activity') $html .= ' SELECTED';
						$html .= '>'.__('Last activity', WPS_TEXT_DOMAIN).'</option>';
					$html .= '<option value="display_name"';
						if ($order == 'display_name') $html .= ' SELECTED';
						$html .= '>'.__('Display name', WPS_TEXT_DOMAIN).'</option>';
					$html .= '<option value="surname"';
						if ($order == 'surname') $html .= ' SELECTED';
						$html .= '>'.__('Surname (if entered in display name)', WPS_TEXT_DOMAIN).'</option>';
					if (get_option(WPS_OPTIONS_PREFIX.'_use_distance') && function_exists('__wps__profile_plus') && !get_option(WPS_OPTIONS_PREFIX.'_hide_location')) {
						$html .= '<option value="distance"';
							if ($order == 'distance') $html .= ' SELECTED';
							$html .= '>'.__('Distance', WPS_TEXT_DOMAIN).'</option>';
					}
				$html .= '</select>';
			$html .= '</div>';
			
			// A to Z
			$html .= '<div id="symposium_members_atoz">';
				for ($i = 65; $i <= 90; $i++) { 
					if (chr($i) != strtoupper($term)) {
						// Get directory URL worked out
						$member_url = __wps__get_url('members');
						$q = __wps__string_query($member_url);
						$html .= '<a href="'.$member_url.$q.'term='.chr($i).'">'.chr($i).'</a>&nbsp;&nbsp;';
					} else {
						$html .= '<strong>'.chr($i).'</strong>&nbsp;&nbsp;';
					}
				}
			$html .= '</div>';

			$html .= '<div id="__wps__members">';

				$search_limit = 1000;
				$sql_ext = strlen($term) != 1 ? "OR (lower(u.display_name) LIKE '% %".$term."%')" : "";
				
				$lat = __wps__get_meta($current_user->ID, 'plus_lat');
				if (get_option(WPS_OPTIONS_PREFIX.'_use_distance') && $lat != 0 && is_user_logged_in() && function_exists('__wps__profile_plus')) {
					
					$long = __wps__get_meta($current_user->ID, 'plus_long');
					$measure = ($value = get_option(WPS_OPTIONS_PREFIX."_plus_lat_long")) ? $value : '';
					$show_alt = ($value = get_option(WPS_OPTIONS_PREFIX."_plus_show_alt")) ? $value : '';
					
					$sql = "SELECT u.ID as uid, u.display_name, cast(m4.meta_value as datetime) as last_activity, 
					CASE 
					  WHEN u.display_name LIKE '% %' THEN right(u.display_name, length(u.display_name)-locate(' ', u.display_name))
					  ELSE u.display_name
					END AS surname,
					CASE m7.meta_value
					  WHEN '0' THEN 99999
					  ELSE FLOOR(((ACOS(SIN(".$lat." * PI() / 180) * SIN(m7.meta_value * PI() / 180) + COS(".$lat." * PI() / 180) * COS(m7.meta_value * PI() / 180) * COS((".$long." - m8.meta_value) * PI() / 180)) * 180 / PI()) * 60 * 1.1515))
					END AS distance 
					FROM ".$wpdb->base_prefix."users u 
					LEFT JOIN ".$wpdb->base_prefix."usermeta m4 ON m4.user_id = u.ID
					LEFT JOIN ".$wpdb->base_prefix."usermeta m7 ON m7.user_id = u.ID
					LEFT JOIN ".$wpdb->base_prefix."usermeta m8 ON m8.user_id = u.ID
					WHERE 
					m4.meta_key = 'symposium_last_activity' AND 
					m7.meta_key = 'symposium_plus_lat' AND 
					m8.meta_key = 'symposium_plus_long' AND 
					(u.display_name IS NOT NULL) AND
					(
					       (lower(u.display_name) LIKE '".$term."%') 
					    ".$sql_ext." 
					)
					ORDER BY ".$orderby." 
					LIMIT 0,".$search_limit;

					$members = $wpdb->get_results($sql);							

				} else {

					$members = $wpdb->get_results("
					SELECT u.ID as uid, u.display_name, cast(m4.meta_value as datetime) as last_activity, 99999 as distance,
					CASE 
					  WHEN u.display_name LIKE '% %' THEN right(u.display_name, length(u.display_name)-locate(' ', u.display_name))
					  ELSE u.display_name
					END AS surname
					FROM ".$wpdb->base_prefix."users u 
					LEFT JOIN ".$wpdb->base_prefix."usermeta m4 ON u.ID = m4.user_id
					WHERE 
					m4.meta_key = 'symposium_last_activity' AND 
					(u.display_name IS NOT NULL) AND
					(
					       (lower(u.display_name) LIKE '".$term."%') 
					    ".$sql_ext." 
					)
					ORDER BY ".$orderby." 
					LIMIT 0,".$search_limit);	
					
				}		

				if (WPS_DEBUG) {
					$html .= $wpdb->last_query;
					$html .= '<p>Returned '.count($members).' records.</p>';
				} else {
					$html .= '<div style="display:none">'.$wpdb->last_query.'</div>';
				}

				if ($members) {

					if (WPS_DEBUG) $html .= '<p>Processing $members.</p>';
				
					$inactive = get_option(WPS_OPTIONS_PREFIX.'_online');
					$offline = get_option(WPS_OPTIONS_PREFIX.'_offline');
					$profile = __wps__get_url('profile');
					$mailpage = __wps__get_url('mail');
					$q = __wps__string_query($mailpage);
					$count = 0;

					$user_info = get_user_by('login', 'nobody');
					$nobody_id = $user_info ? $user_info->ID : 0;

					foreach ($members as $member) {
						
						if (WPS_DEBUG) $html .= 'Member: '.$member->display_name.'<br />';
						
						$user_info = get_userdata($member->uid);							

						// Check to see if this member is in the included list of roles
						if (WPS_DEBUG) $html .= 'Checking capabilities... ';
						$user = get_userdata( $member->uid );
						$capabilities = $user->{$wpdb->base_prefix.'capabilities'};
						
						$include = false;
						if ($capabilities) {
							
							foreach ( $capabilities as $role => $name ) {
								if ($role) {
									if (WPS_DEBUG) $html .= $role.'<br />';
									$role = strtolower($role);
									$role = str_replace(' ', '', $role);
									$role = str_replace('_', '', $role);
									if (WPS_DEBUG) $html .= 'Checking role '.$role.' against '.$dir_levels.'<br />';
									if (strpos($dir_levels, $role) !== FALSE) $include = true;
								} else {
									if (WPS_DEBUG) $html .= 'no role<br />';
								}
							}		 														
						
						} else {
							if (WPS_DEBUG) $html .= 'no capabilities.<br />';
							// No capabilities, so let's assume they should be included
							$include = true;
						}

						if ($include && ($member->uid != $nobody_id)) {

								if (WPS_DEBUG) $html .= 'Include!<br />';

								$city = __wps__get_meta($member->uid, 'extended_city');
								$country = __wps__get_meta($member->uid, 'extended_country');
								$share = __wps__get_meta($member->uid, 'share');
								$wall_share = __wps__get_meta($member->uid, 'wall_share');
		
								$count++;
								if ($count > get_option(WPS_OPTIONS_PREFIX.'_dir_page_length')) break;

								$time_now = time();
								$last_active_minutes = strtotime($member->last_activity);
								$last_active_minutes = floor(($time_now-$last_active_minutes)/60);
															
								$html .= "<div class='members_row";
									
									$is_friend = __wps__friend_of($member->uid, $current_user->ID);
									if ($is_friend || $member->uid == $me) {
										$html .= " row_odd corners";		
									} else {
										$html .= " row corners";		
									}
									$html .= "'>";

									$html .= "<div class='members_info'>";

										$html .= "<div class='members_avatar'>";
											$html .= get_avatar($member->uid, 64);
										$html .= "</div>";	

										$html .= "<div style='padding-left: 75px;'>";						

											if ( ($member->uid == $me) || (is_user_logged_in() && strtolower($share) == 'everyone') || (strtolower($share) == 'public') || (strtolower($share) == 'friends only' && $is_friend) ) {
												$html .= "<div class='members_location'>";
													if ($city != '') {
														$html .= $city;
													}
													if ($country != '') {
														if ($city != '') {
															$html .= ', '.$country;
														} else {
															$html .= $country;
														}
													}
												$html .= "</div>";
											}

											if (function_exists('__wps__mail') && !get_option(WPS_OPTIONS_PREFIX.'_wps_lite')) {
												// Show Send Mail button
												if (get_option(WPS_OPTIONS_PREFIX.'_show_dir_buttons') && $member->uid != $current_user->ID) {
													if ($is_friend) {
														// A friend
														$html .= "<div class='mail_icon' style='display:none;float:right; margin-right:5px;'>";
														$html .= '<img style="cursor:pointer" src="'.get_option(WPS_OPTIONS_PREFIX.'_images').'/orange-tick.gif" onclick="document.location = \''.$mailpage.$q.'view=compose&to='.$member->uid.'\';">';
														$html .= "</div>";
													}
												}
											}

											$html .= __wps__profile_link($member->uid);

											if (!get_option(WPS_OPTIONS_PREFIX.'_wps_lite')) {
												$html .= ', ';
											} else {
												$html .= '<br />';
											}
											$html .= __('last active', WPS_TEXT_DOMAIN).' '.__wps__time_ago($member->last_activity).". ";
											if ($last_active_minutes >= $offline) {
												//$html .= '<img src="'.get_option(WPS_OPTIONS_PREFIX.'_images').'/loggedout.gif">';
											} else {
												if ($last_active_minutes >= $inactive) {
													$html .= '<img src="'.get_option(WPS_OPTIONS_PREFIX.'_images').'/inactive.gif">';
												} else {
													$html .= '<img src="'.get_option(WPS_OPTIONS_PREFIX.'_images').'/online.gif">';
												}
											}

											// Distance
											if (function_exists('__wps__profile_plus') && is_user_logged_in() && $member->distance < 99999 && $member->uid != $current_user->ID) {
												// if privacy settings permit
												if ( (strtolower($share) == 'everyone') 
													|| (strtolower($share) == 'public') 
													|| (strtolower($share) == 'friends only' && __wps__friend_of($member->uid, $current_user->ID)) 
													) {		
													if ($measure != 'on') { 
														$distance = intval(($member->distance/5)*8);
														$miles = __('km', WPS_TEXT_DOMAIN);
													} else {
														$distance = $member->distance;
														$miles = __('miles', WPS_TEXT_DOMAIN);
													}	
													$html .= '<br />'.__('Distance', WPS_TEXT_DOMAIN).': '.$distance.' '.$miles;
													if ($show_alt == 'on') {
														if ($measure != 'on') { 
															$html .= ' ('.intval(($distance/8)*5).' '.__('miles', WPS_TEXT_DOMAIN).')';
														} else {
															$html .= ' ('.intval(($distance/5)*8).' '.__('km', WPS_TEXT_DOMAIN).')';
														}
													}
												}
											}
											
											if (!get_option(WPS_OPTIONS_PREFIX.'_wps_lite')) {

												// Show label if entered
												if ($label = __wps__get_meta($member->uid, 'profile_label'))
													$html .= '<div class="__wps__members_info_label">'.$label.'</div>';															

												// if privacy settings permit
												if ( (strtolower($wall_share) == 'everyone') 
													|| (strtolower($wall_share) == 'public') 
													|| (strtolower($wall_share) == 'friends only' && __wps__friend_of($member->uid, $current_user->ID)) 
													) {		
																								
													// Show comment
													$sql = "SELECT cid, comment, type FROM ".$wpdb->base_prefix."symposium_comments
															WHERE author_uid = %d AND comment_parent = 0 AND type = 'post'
															ORDER BY cid DESC 
															LIMIT 0,1";
													$comment = $wpdb->get_row($wpdb->prepare($sql, $member->uid));
													if ($comment) {
														$html .= '<div style="max-height:250px">'.__wps__buffer(__wps__make_url(stripslashes($comment->comment))).'</div>';
													}
													// Show latest non-status activity if applicable
													if (function_exists('__wps__forum')) {
														$sql = "SELECT cid, comment FROM ".$wpdb->base_prefix."symposium_comments
																WHERE author_uid = %d AND comment_parent = 0 AND type = 'forum' 
																ORDER BY cid DESC 
																LIMIT 0,1";
														$forum = $wpdb->get_row($wpdb->prepare($sql, $member->uid));
														if ($forum && (!$comment || $forum->cid != $comment->cid)) {
															$html .= '<div>'.__wps__buffer(__wps__make_url(stripslashes($forum->comment))).'</div>';
														}
													}
												}
											}
											
											// Show add as a friend
											if (is_user_logged_in() && get_option(WPS_OPTIONS_PREFIX.'_show_dir_buttons') && $member->uid != $current_user->ID) {
												if (__wps__pending_friendship($member->uid)) {
													// Pending
													$html .= sprintf(__('%s request sent.', WPS_TEXT_DOMAIN), get_option(WPS_OPTIONS_PREFIX.'_alt_friend'));
												} else {
													if (!$is_friend) {
														// Not a friend
														$html .= '<div id="addasfriend_done1_'.$member->uid.'">';
														$html .= '<input class="add_as_friend_message addfriend_text" title="'.$member->uid.'" id="addtext_'.$member->uid.'" type="text" onclick="this.value=\'\'" value="'.sprintf(__('Add as a %s...', WPS_TEXT_DOMAIN), get_option(WPS_OPTIONS_PREFIX.'_alt_friend')).'">';
														$html .= '<input type="submit" title="'.$member->uid.'" class="addasfriend __wps__button" value="'.__('Add', WPS_TEXT_DOMAIN).'" /> ';						
														$html .= '</div>';
														$html .= '<div id="addasfriend_done2_'.$member->uid.'" class="hidden">'.sprintf(__('%s Request Sent', WPS_TEXT_DOMAIN), get_option(WPS_OPTIONS_PREFIX.'_alt_friend')).'</div>';	
													}
												}
											}

											// Filter for individual member reults
											$html = apply_filters ( '__wps__directory_member_filter', $html, $member->uid);
																					
										$html .= "</div>";	
																				
									$html .= "</div>";	// members_info

								$html .= "</div>";	// members_row
																
														
						} // if ($include)
						
					} // foreach ($members as $member)

					$html .= "<div id='showmore_directory_div' style='text-align:center; width:100%'><a href='javascript:void(0)' id='showmore_directory'>".__("more...", WPS_TEXT_DOMAIN)."</a></div>";

				} else {
					$html .= '<br />'.__('No members found', WPS_TEXT_DOMAIN)."....";
				} // if ($members)

			}
			
		$html .= '</div>'; // __wps__members
		
	$html .= '</div>'; // __wps__wrapper

	// Filter for header
	$html = apply_filters ( 'symposium_member_header_filter', $html );

	// Send HTML
	return $html;

}

/* ====================================================== SET SHORTCODE ====================================================== */
if (!is_admin()) {
	add_shortcode(WPS_SHORTCODE_PREFIX.'-members', '__wps__members');  
}



?>
