<?php
/*
WP Symposium Facebook
Description: Facebook Status plugin compatible with WP Symposium. Activate to use.
*/

	
// Get constants
require_once(dirname(__FILE__).'/default-constants.php');


// Function to WordPress knows this plugin is activated
function __wps__facebook()  
{  
	        			
	return 'wp-symposium';
	exit;
		
}


// Add plugin to admin menu via hook
function symposium_add_facebook_to_admin_menu()
{
	$hidden = get_option(WPS_OPTIONS_PREFIX.'_long_menu') == "on" ? '_hidden': '';
	add_submenu_page('symposium_debug'.$hidden, __('Facebook', WPS_TEXT_DOMAIN), __('Facebook', WPS_TEXT_DOMAIN), 'manage_options', WPS_DIR.'/facebook_admin.php');
}
add_action('__wps__admin_menu_hook', 'symposium_add_facebook_to_admin_menu');


?>
