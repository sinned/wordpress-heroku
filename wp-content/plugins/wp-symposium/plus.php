<?php
/*
WP Symposium Profile Plus
Description: Adds additional Profile features to WP Symposium
*/

// Get constants
require_once(dirname(__FILE__).'/default-constants.php');


function __wps__profile_plus(){}

/* ====================================================== ADMIN ====================================================== */

require_once(WPS_PLUGIN_DIR . '/functions.php');



/* ====================================================== HOOKS/FILTERS INTO WORDPRESS/WP Symposium ====================================================== */

// Add plugin to admin menu via hook
function symposium_add_profile_plus_to_admin_menu()
{
	$hidden = get_option(WPS_OPTIONS_PREFIX.'_long_menu') == "on" ? '_hidden': '';
	add_submenu_page('symposium_debug'.$hidden, __('Profile Plus', WPS_TEXT_DOMAIN), __('Profile Plus', WPS_TEXT_DOMAIN), 'manage_options', WPS_DIR.'/plus_admin.php');
}
add_action('__wps__admin_menu_hook', 'symposium_add_profile_plus_to_admin_menu');

// ----------------------------------------------------------------------------------------------------------------------------------------------------------

// Add Menu item to @mentions

function __wps__add_mentions_menu($html,$uid1,$uid2,$privacy,$is_friend,$extended,$share,$extra_class)  
{  
	global $current_user;

	if ( (is_user_logged_in() && strtolower($share) == 'everyone') || (strtolower($share) == 'public') || (strtolower($share) == 'friends only' && $is_friend) || __wps__get_current_userlevel() == 5) {

		if ($uid1 == $uid2) {
			if (get_option(WPS_OPTIONS_PREFIX.'_menu_mentions'))
				$html .= '<div id="menu_mentions" class="__wps__profile_menu '.$extra_class.'">'.(($t = get_option(WPS_OPTIONS_PREFIX.'_menu_mentions_text')) != '' ? $t :  __('Forum @mentions', WPS_TEXT_DOMAIN)).'</div>';  
		} else {
			if (get_option(WPS_OPTIONS_PREFIX.'_menu_mentions_other'))
				$html .= '<div id="menu_mentions" class="__wps__profile_menu '.$extra_class.'">'.(($t = get_option(WPS_OPTIONS_PREFIX.'_menu_mentions_other_text')) != '' ? $t :  __('Forum @mentions', WPS_TEXT_DOMAIN)).'</div>';  
		}
		
	}
	
	return $html;
	
}  
add_filter('__wps__profile_menu_filter', '__wps__add_mentions_menu', 10, 8);

function __wps__add_mentions_menu_tabs($html,$title,$value,$uid1,$uid2,$privacy,$is_friend,$extended,$share)  
{  
	if ($value == 'mentions') {
		
		global $current_user;
	
		if ( (($uid1 == $uid2) || is_user_logged_in() && strtolower($share) == 'everyone') || (strtolower($share) == 'public') || (strtolower($share) == 'friends only' && $is_friend) || __wps__get_current_userlevel() == 5)
			$html .= '<li id="menu_mentions" class="__wps__profile_menu" href="javascript:void(0)">'.$title.'</li>';
		
	}
	
	return $html;
	
}  
add_filter('__wps__profile_menu_tabs', '__wps__add_mentions_menu_tabs', 10, 9);

// Add Menu item to Profile Menu through filter provided

function __wps__add_following_menu($html,$uid1,$uid2,$privacy,$is_friend,$extended,$share,$extra_class)  
{  
	global $current_user;

	if ( ((is_user_logged_in() && strtolower($share) == 'everyone') || (strtolower($share) == 'public') || (strtolower($share) == 'friends only' && $is_friend) || __wps__get_current_userlevel() == 5) ) {

		if ($uid1 == $uid2) {
			if (get_option(WPS_OPTIONS_PREFIX.'_menu_following'))
				$html .= '<div id="menu_plus" class="__wps__profile_menu '.$extra_class.'">'.(($t = get_option(WPS_OPTIONS_PREFIX.'_menu_following_text')) != '' ? $t :  __('I am Following', WPS_TEXT_DOMAIN)).'</div>';  
			if (get_option(WPS_OPTIONS_PREFIX.'_menu_followers'))
				$html .= '<div id="menu_plus_me" class="__wps__profile_menu '.$extra_class.'">'.(($t = get_option(WPS_OPTIONS_PREFIX.'_menu_followers_text')) != '' ? $t :  __('My Followers', WPS_TEXT_DOMAIN)).'</div>';  
		} else {
			if (get_option(WPS_OPTIONS_PREFIX.'_menu_following_other'))
				$html .= '<div id="menu_plus" class="__wps__profile_menu '.$extra_class.'">'.(($t = get_option(WPS_OPTIONS_PREFIX.'_menu_following_other_text')) != '' ? $t :  __('Following', WPS_TEXT_DOMAIN)).'</div>';  
			if (get_option(WPS_OPTIONS_PREFIX.'_menu_followers_other'))
				$html .= '<div id="menu_plus_me" class="__wps__profile_menu '.$extra_class.'">'.(($t = get_option(WPS_OPTIONS_PREFIX.'_menu_followers_other_text')) != '' ? $t :  __('Followers', WPS_TEXT_DOMAIN)).'</div>';  
		}
		
	}
	
	return $html;
	
}  
add_filter('__wps__profile_menu_filter', '__wps__add_following_menu', 10, 8);

function __wps__add_following_menu_tabs($html,$title,$value,$uid1,$uid2,$privacy,$is_friend,$extended,$share)  
{  
	if ($value == 'following') {
		
		global $current_user;
	
		if ( (($uid1 == $uid2) || (is_user_logged_in() && strtolower($share) == 'everyone') || (strtolower($share) == 'public') || (strtolower($share) == 'friends only' && $is_friend) || __wps__get_current_userlevel() == 5) )
			$html .= '<li id="menu_plus" class="__wps__profile_menu" href="javascript:void(0)">'.$title.'</li>';
			
	}
	
	return $html;
	
}  
add_filter('__wps__profile_menu_tabs', '__wps__add_following_menu_tabs', 10, 9);

function __wps__add_followers_menu_tabs($html,$title,$value,$uid1,$uid2,$privacy,$is_friend,$extended,$share)  
{  
	if ($value == 'followers') {
		
		global $current_user;
	
		if ( (($uid1 == $uid2) || (is_user_logged_in() && strtolower($share) == 'everyone') || (strtolower($share) == 'public') || (strtolower($share) == 'friends only' && $is_friend) || __wps__get_current_userlevel() == 5) )	
			$html .= '<li id="menu_plus_me" class="__wps__profile_menu" href="javascript:void(0)">'.$title.'</li>';
		
	}
	
	return $html;
	
}  
add_filter('__wps__profile_menu_tabs', '__wps__add_followers_menu_tabs', 10, 9);

function __wps__search($width='200')  
{  
	$width = 'style="width:'.$width.'px"';
   	$prompt = ($prompt = get_option(WPS_OPTIONS_PREFIX.'_site_search_prompt')) ? $prompt : __('Search...', WPS_TEXT_DOMAIN);
	
	$html = '<input type="text" id="__wps__member_small" '.$width.' 
				onblur="this.value=(this.value==\'\') ? \''.$prompt.'\' : this.value;" 
				onfocus="this.value=(this.value==\''.$prompt.'\') ? \'\' : this.value;" 
				value="'.$prompt.'" />';				
	
	return $html;
}

/* ====================================================== SET SHORTCODE ====================================================== */

// [symposium-following] (for profile page)
function __wps__profile_following()  
{  
	return __wps__show_profile("plus");
	exit;	
}
add_shortcode(WPS_SHORTCODE_PREFIX.'-following', '__wps__profile_following');  


if (!is_admin()) {
	add_shortcode(WPS_SHORTCODE_PREFIX.'-search', '__wps__search');  
}


?>
