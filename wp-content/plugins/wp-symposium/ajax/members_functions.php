<?php

include_once('../../../../wp-config.php');

global $wpdb, $current_user;
wp_get_current_user();

// Member search (autocomplete)
if (isset($_GET['term'])) {
		
	global $wpdb;	
	$return_arr = array();
	$term = $_GET['term'];
	$roles = $_GET['roles'];

	$sql = "SELECT * FROM ".$wpdb->base_prefix."users WHERE  
	( display_name LIKE '%".$term."%')
	ORDER BY display_name LIMIT 0,25";
	
	$list = $wpdb->get_results($sql);
	
	if ($list) {

		if ( !isset( $wp_roles ) ) $wp_roles = new WP_Roles();									

		$user_info = get_user_by('login', 'nobody');
		$nobody_id = ($user_info) ? $user_info->ID : 0;

		foreach ($list as $item) {
			
			$include = false;
			// Check to see if this member is in the included list of roles
			if ($roles) {
				$user = get_userdata( $item->ID );
				$capabilities = $user->{$wpdb->base_prefix.'capabilities'};
				foreach ( $capabilities as $role => $name ) {
					if ($role) {
						$role = strtolower($role);
						$role = str_replace(' ', '', $role);
						$role = str_replace('_', '', $role);
						if (strpos($roles, $role) !== FALSE) $include = true;
					}
				}
			} else {
				$include = true;
			}


			if ($include && ($item->ID != $nobody_id)) {
				$row_array['id'] = $item->ID;
				$row_array['value'] = $item->ID;
				$row_array['name'] = $item->display_name;
				$row_array['avatar'] = get_avatar($item->ID, 40);
	
				$share = __wps__get_meta($item->ID, 'share');							
				$is_friend = __wps__friend_of($item->ID, $current_user->ID);
	
				if ( ($item->ID == $current_user->ID) || (is_user_logged_in() && strtolower($share) == 'everyone') || (strtolower($share) == 'public') || (strtolower($share) == 'friends only' && $is_friend) ) {
					$row_array['city'] = __wps__get_meta($item->ID, 'extended_city');
					$row_array['country'] = __wps__get_meta($item->ID, 'extended_country');
				} else {
					$row_array['city'] = '';
					$row_array['country'] = '';
				}
				
		        array_push($return_arr,$row_array);
			}
		}
	}

	echo json_encode($return_arr);
	exit;

}


// Members list search
if ($_POST['action'] == 'getMembers') {

	$me = $current_user->ID;
	$page = 1;
	$html = '';
	$search_limit = 1000;
	

	$dir_levels = ($_POST['roles'] != '') ? $_POST['roles'] : '';
	$page_length = ($_POST['page_length'] != '') ? $_POST['page_length'] : 25;
	$extended = isset($_POST['extended']) ? $_POST['extended'] : '';
	$start = ($_POST['start'] != '') ? $_POST['start'] : 0;
	$term = ($_POST['action'] != '') ? strtolower($_POST['term']) : '';
	$orderby = ($_POST['orderby'] != '') ? strtolower($_POST['orderby']) : 'display_name';
	if ($orderby == 'display_name') { $orderby = 'u.display_name'; }
	if ($orderby == 'distance') { $orderby = 'distance, u.display_name'; }
	if ($orderby == 'last_activity') { $orderby = 'cast(m4.meta_value as datetime) DESC'; }

	$friends = ($_POST['friends'] != '') ? $_POST['friends'] : '';
	$sql_ext = strlen($term) != 1 ? "OR (lower(u.display_name) LIKE '% %".$term."%')" : "";

	$quick_ver = get_option(WPS_OPTIONS_PREFIX.'_dir_full_ver');

	$user_info = get_user_by('login', 'nobody');
	$nobody_id = ($user_info) ? $user_info->ID : 0;
	
	if (!$quick_ver && get_option(WPS_OPTIONS_PREFIX.'_use_distance') && function_exists('__wps__profile_plus') && is_user_logged_in() && ($lat = __wps__get_meta($current_user->ID, 'plus_lat')) != '') {

		$long = __wps__get_meta($current_user->ID, 'plus_long');
		$measure = ($value = get_option(WPS_OPTIONS_PREFIX."_plus_lat_long")) ? $value : '';	

		$members = $wpdb->get_results("
				SELECT u.ID as uid, u.display_name, cast(m4.meta_value as datetime) as last_activity,
				CASE 
				  WHEN u.display_name LIKE '% %' THEN right(u.display_name, length(u.display_name)-locate(' ', u.display_name))
				  ELSE u.display_name
				END AS surname,
				CASE m7.meta_value
				  WHEN '0' THEN 99999
				  ELSE FLOOR(((ACOS(SIN(".$lat." * PI() / 180) * SIN(m7.meta_value * PI() / 180) + COS(".$lat." * PI() / 180) * COS(m7.meta_value * PI() / 180) * COS((".$long." - m8.meta_value) * PI() / 180)) * 180 / PI()) * 60 * 1.1515))
				END AS distance,
				m7.meta_value AS u_lat,
				m8.meta_value AS u_long
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
				LIMIT 0,".$search_limit);
					
	} else {

		if ($quick_ver) {

			$members = $wpdb->get_results("
			SELECT u.ID as uid, u.display_name, '' as last_activity, 99999 AS distance 
			FROM ".$wpdb->base_prefix."users u 
 			WHERE lower(u.display_name) LIKE '%".$term."%'
			ORDER BY u.display_name 
			LIMIT 0,".$search_limit);
						
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
		
	}
	
	if (WPS_DEBUG) $html .= $wpdb->last_query;
	
	
	if ($members) {
		
		if (WPS_DEBUG) $html .= 'Members found ';
		
		$inactive = get_option(WPS_OPTIONS_PREFIX.'_online');
		$offline = get_option(WPS_OPTIONS_PREFIX.'_offline');
		$profile = __wps__get_url('profile');
		$count = 0;
		$skip = 0;
				
		$mailpage = __wps__get_url('mail');
		if ($mailpage[strlen($mailpage)-1] != '/') { $mailpage .= '/'; }
		$q = __wps__string_query($mailpage);			

		if ( !isset( $wp_roles ) ) $wp_roles = new WP_Roles();									
		
		// Get Extended Field info for advanced search
		if (!get_option(WPS_OPTIONS_PREFIX.'_wps_lite')) {
			$sql = "SELECT * FROM ".$wpdb->base_prefix."symposium_extended ORDER BY eid";
			$extensions = $wpdb->get_results($sql);
		}

					
		foreach ($members as $member) {

			if (WPS_DEBUG) $html .= $member->display_name.' ';
			
			// Check to see if this member is in the included list of roles
			$user = get_userdata( $member->uid );
			$capabilities = $user->{$wpdb->base_prefix.'capabilities'};

			$include = false;
			if (WPS_DEBUG) $html .= '$dir_levels='.$dir_levels.' ';
			
			if ($capabilities) {
				foreach ( $capabilities as $role => $name ) {
					if (WPS_DEBUG) $html .= '$role='.$role.' ';
					if ($role) {
						$role = strtolower($role);
						$role = str_replace(' ', '', $role);
						$role = str_replace('_', '', $role);
						if (strpos($dir_levels, $role) !== FALSE) $include = true;
					}
				}
				
			} else {
				if (WPS_DEBUG) $html .= 'no capabilities.<br />';
				// No capabilities, so let's assume they should be included
				$include = true;
			}
			
			if ($include && ($member->uid != $nobody_id)) {	
				
				if (WPS_DEBUG) $html .= 'include ';
				
				$skip++;
				if ($skip < $start) {
					// skip through those already shown
				} else {							
				
					$time_now = time();
					$last_active_minutes = strtotime($member->last_activity);
					$last_active_minutes = floor(($time_now-$last_active_minutes)/60);

					$continue = true;

					// Check if a friend (if option is checked)
					if (!$friends || $friends && __wps__friend_of($member->uid, $current_user->ID)) {

						// Check against extended fields
						if ($extended && !get_option(WPS_OPTIONS_PREFIX.'_wps_lite')) {
							foreach($extended as $extended_field) {
								$extend_field_parts = explode('|', $extended_field);
								$type = $extend_field_parts[0];
								$eid = $extend_field_parts[1];
								$value = trim($extend_field_parts[2]);
								
								foreach ($extensions as $extension) {
									
									if ($extension->eid == $eid) {
	
										// Get stub
										$stub = 'extended_'.$extension->extended_slug;
	
										// List
										if ($type == 'list') {
											if ($value != __('Any', WPS_TEXT_DOMAIN)) {
												if (__wps__get_meta($member->uid, $stub) != $value) {
													$continue = false;
												}
											}
										}
										// Checkbox
										if ($type == 'checkbox') {
											if ($value == 'on') {
												if (!__wps__get_meta($member->uid, $stub)) {
													$continue = false;
												}
											} else {
												if (__wps__get_meta($member->uid, $stub)) {
													$continue = false;
												}
											}
										}
									}
								}							
							}
						}
								
								
						// Now carry on if okay to do so	
						if ($continue) {
	
							$count++;
							if ($count > get_option(WPS_OPTIONS_PREFIX.'_dir_page_length')) break;
							
							$city = __wps__get_meta($member->uid, 'extended_city');
							$country = __wps__get_meta($member->uid, 'extended_country');
							$share = __wps__get_meta($member->uid, 'share');
							$wall_share = __wps__get_meta($member->uid, 'wall_share');
	
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
				
										if (!get_option(WPS_OPTIONS_PREFIX.'_wps_lite')) {
											// Show Send Mail button
											if (function_exists('__wps__mail') && get_option(WPS_OPTIONS_PREFIX.'_show_dir_buttons') && $member->uid != $current_user->ID) {
												if ($is_friend) {
													// A friend
													$html .= "<div class='mail_icon' style='display:none;float:right; margin-right:5px;'>";
													$html .= '<img style="cursor:pointer" src="'.get_option(WPS_OPTIONS_PREFIX.'_images').'/orange-tick.gif" onclick="document.location = \''.$mailpage.$q.'view=compose&to='.$member->uid.'\';">';
													$html .= "</div>";
												}
											}
										}
			
										$html .= __wps__profile_link($member->uid);
			
										if ($member->last_activity != '') {
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
												if (WPS_DEBUG) {
													$html .= ' '.$member->distance;
													$html .= ' ['.$member->u_lat.'/'.$member->u_long.']';
													$html .= ' ('.__wps__get_meta($member->uid, 'plus_lat').'/'.__wps__get_meta($member->uid, 'plus_lng').')<br />';
												}
											} else {
												$html .= '<br />'.__('Location is set to private', WPS_TEXT_DOMAIN);
											}
										} else {
											// No distance recorded for member
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
													$html .= '<div>'.__wps__buffer(__wps__make_url(stripslashes($comment->comment))).'</div>';
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
								$html .= "</div>";
							$html .= "</div>";	
						}
					}	
				}
			} else {
				if (WPS_DEBUG) $html .= 'exclude ';
			}
		}
		
		if ($count > 0) {
			if ($count > $page_length) {
				$html .= "<div id='showmore_directory_div' style='text-align:center; width:100%'><a href='javascript:void(0)' id='showmore_directory'>".__("more...", WPS_TEXT_DOMAIN)."</a></div>";
			}				
		} else {
			$html .= "<div style='text-align:center; width:100%'>".__("No members found", WPS_TEXT_DOMAIN)."</div>";
		}

	} else {
		$html .= "<div style='text-align:center; width:100%'>".__("No members found", WPS_TEXT_DOMAIN)."</div>";
	}

	echo $html;


}

?>

	
