<?php
/*
WP Symposium Lounge
Description: Shoutbox plugin compatible with WP Symposium. Put [symposium-lounge] on any WordPress page. Also acts as demonstration for WP Symposium development.
*/


/* ====================================================================== MAIN =========================================================================== */

// Get constants
require_once(dirname(__FILE__).'/default-constants.php');

function __wps__lounge_main() {
	// This function is also used to information Wordpress that it is activated.
	// Ties in with __wps__add_lounge_to_admin_menu() function below.

	// The following duplicates the AJAX code in lounge_functions.php (ref. // Start lounge content)
	$html = '<div class="__wps__wrapper">';

		// This filter allows others to add text (or whatever) above the output
		$html = apply_filters ( '__wps__lounge_filter_top', $html);

		if (is_user_logged_in()) {
	
			// Display the comment form
			$html .= '<div id="__wps__lounge_add_comment_div">';
			$html .= '<input type="text" class="input-field" id="__wps__lounge_add_comment" onblur="this.value=(this.value==\'\') ? \''.__("Add a comment..", WPS_TEXT_DOMAIN).'\' : this.value;" onfocus="this.value=(this.value==\''.__("Add a comment..", WPS_TEXT_DOMAIN).'\') ? \'\' : this.value;" value="'.__("Add a comment..", WPS_TEXT_DOMAIN).'">';
			$html .= '&nbsp;<input id="__wps__lounge_add_comment_button" type="submit" class="__wps__button" value="'.__('Add', WPS_TEXT_DOMAIN).'" /> ';
			$html .= '</div>';
		
		}

		// Prepare for the output (which is created via AJAX)
		$html .= '<div id="__wps__lounge_div">';
		$html .= "<img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/busy.gif' />";
		$html .= '</div>';
	
	$html .= '</div>';
	
	// Send HTML
	return $html;
	
}


/* ===================================================================== ADMIN =========================================================================== */


// ----------------------------------------------------------------------------------------------------------------------------------------------------------

function __wps__lounge_init()
{
}
add_action('init', '__wps__lounge_init');

// ----------------------------------------------------------------------------------------------------------------------------------------------------------



/* ================================================================== SET SHORTCODE ====================================================================== */

if (!is_admin()) {
	add_shortcode(WPS_SHORTCODE_PREFIX.'-lounge', '__wps__lounge_main');  
}

/* ====================================================== HOOKS/FILTERS INTO WORDPRESS/WP Symposium ====================================================== */

// Add Menu item to Profile Menu through filter provided
// The menu picks up the id of div with id of menu_ (eg: menu_lounge) and will then run
// 'path-to/wp-symposium/ajax/lounge_functions.php' when clicked.
// It will pass $_POST['action'] set to menu_lounge to that file to then be acted upon.

function __wps__add_lounge_menu($html,$uid1,$uid2,$privacy,$is_friend,$extended,$share,$extra_class)  
{  
	global $current_user;

	// Do a check that user is logged in, if so create the HTML to add to the menu
	if (is_user_logged_in()) {  

		if ( ($uid1 == $uid2) || (is_user_logged_in() && strtolower($privacy) == 'everyone') || (strtolower($privacy) == 'public') || (strtolower($privacy) == 'friends only' && $is_friend) || __wps__get_current_userlevel() == 5) {
	
			if ($uid1 == $uid2) {
				if (get_option(WPS_OPTIONS_PREFIX.'_menu_lounge'))
					$html .= '<div id="menu_lounge" class="__wps__profile_menu '.$extra_class.'">'.(($t = get_option(WPS_OPTIONS_PREFIX.'_menu_lounge_text')) != '' ? $t :  __('The Lounge', WPS_TEXT_DOMAIN)).'</div>';  
			} else {
				if (get_option(WPS_OPTIONS_PREFIX.'_menu_lounge_other'))
					$html .= '<div id="menu_lounge" class="__wps__profile_menu '.$extra_class.'">'.(($t = get_option(WPS_OPTIONS_PREFIX.'_menu_lounge_other_text')) != '' ? $t :  __('The Lounge', WPS_TEXT_DOMAIN)).'</div>';  
			}
		}
		
	}
	return $html;
}  
add_filter('__wps__profile_menu_filter', '__wps__add_lounge_menu', 10, 8);

function __wps__add_lounge_menu_tabs($html,$title,$value,$uid1,$uid2,$privacy,$is_friend,$extended,$share)  
{  
	
	if ($value == 'lounge') {
		

		global $current_user;
	
		// Do a check that user is logged in, if so create the HTML to add to the menu
		if (is_user_logged_in()) {  
	
			if ( ($uid1 == $uid2) || (is_user_logged_in() && strtolower($privacy) == 'everyone') || (strtolower($privacy) == 'public') || (strtolower($privacy) == 'friends only' && $is_friend) || __wps__get_current_userlevel() == 5) 
				$html .= '<li id="menu_lounge" class="__wps__profile_menu" href="javascript:void(0)">'.$title.'</a></li>';
			
		}
		
	}
	
	return $html;
}  
add_filter('__wps__profile_menu_tabs', '__wps__add_lounge_menu_tabs', 10, 9);

// ----------------------------------------------------------------------------------------------------------------------------------------------------------

// Add "The Lounge" to admin menu via hook
function __wps__add_lounge_to_admin_menu()
{
	$hidden = get_option(WPS_OPTIONS_PREFIX.'_long_menu') == "on" ? '_hidden': '';
	add_submenu_page('symposium_debug'.$hidden, __('The Lounge', WPS_TEXT_DOMAIN), __('The Lounge', WPS_TEXT_DOMAIN), 'edit_themes', WPS_DIR.'/lounge_admin.php');
}
add_action('__wps__admin_menu_hook', '__wps__add_lounge_to_admin_menu');


?>
