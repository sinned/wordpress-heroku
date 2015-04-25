<?php

/**
 * Constants used for management purposes.
 * Do not edit unless you have been given instructions! If this file is changed, support will unfortunately not be provided.
 * Note: It is not permitted to re-sell this set of plugins as a commercial product without prior permission.
 * Important: Backup your site and database before making any changes.... just in case! You've been warned!
 * When upgrading, do so manually, then replace this file with your own version BEFORE accessing the website or admin area.
 */

/**
 * Can be changed post installation  ================================================================================================
 */
 
if ( !defined('WPS_WL') ) 						define('WPS_WL', 'WP Symposium'); 										// Long name
if ( !defined('WPS_WL_SHORT') ) 				define('WPS_WL_SHORT', 'WP Symposium');									// Alternative short name
if ( !defined('WPS_DIR') ) 						define('WPS_DIR', 'wp-symposium'); 										// Installed plugin folder (within WordPress plugins)
if ( !defined('WPS_WELCOME_MESSAGE') ) 			define('WPS_WELCOME_MESSAGE', '../wps-welcome.html'); 					// Alternative file location of welcome message
if ( !defined('WPS_TEXT_DOMAIN') ) 				define('WPS_TEXT_DOMAIN', 'wp-symposium'); 								// Text domain for translations
if ( !defined('WPS_SHORTCODE_PREFIX') )			define('WPS_SHORTCODE_PREFIX', 'symposium');							// Prefix for shortcodes 
if ( !defined('WPS_HIDE_ACTIVATION') )			define('WPS_HIDE_ACTIVATION', false);									// Whether to hide activation code on Installation page
if ( !defined('WPS_HIDE_FOOTER') )				define('WPS_HIDE_FOOTER', false);										// Convenient way to permenantly hide the page footer
if ( !defined('WPS_HIDE_INSTALL_INFO') )		define('WPS_HIDE_INSTALL_INFO', false);									// Whether to hide additional info on Installation page
if ( !defined('WPS_HIDE_DASHBOARAD_W') )		define('WPS_HIDE_DASHBOARAD_W', false);									// Whether to hide WPS WordPress admin dashboard widget
if ( !defined('WPS_HIDE_PLUGINS') )				define('WPS_HIDE_PLUGINS', false);										// Whether to hide all plugins and menu (keep one non-WPS plugin active to avoid PHP warnings)
if ( !defined('WPS_CHANGE_PLUGINS') )			define('WPS_CHANGE_PLUGINS', false);									// Whether to re-brand plugins
/* Following are only used if WPS_CHANGE_PLUGINS is set to true and WPS_HIDE_PLUGINS set to false */
if ( !defined('WPS_CHANGE_DESC') )				define('WPS_CHANGE_DESC', 'WP Symposium plugin. All rights reserved.');	// Global WPS description, or false to skip
if ( !defined('WPS_CHANGE_VER') )				define('WPS_CHANGE_VER', '1');											// Version, or false to skip
if ( !defined('WPS_CHANGE_AUTHOR') )			define('WPS_CHANGE_AUTHOR', 'Simon Goodchild');							// Author, or false to skip
if ( !defined('WPS_CHANGE_AUTHORURI') )			define('WPS_CHANGE_AUTHORURI', 'http://www.wpsymposium.com');			// Author web address, or false to skip
if ( !defined('WPS_CHANGE_PLUGINURI') )			define('WPS_CHANGE_PLUGINURI', 'http://www.wpsymposium.com');			// Plugin web address, or false to skip

/* Allows WordPress plugin URL to be over-ridden */
if ( !defined('WPS_PLUGIN_DIR') ) 			define('WPS_PLUGIN_DIR', WP_PLUGIN_DIR.'/'.WPS_DIR);

/**
 * References within code
 * You can also globally replace __wps__ with __xxx__ in all files to rebrand internal code functions and CSS (make it unique!) 
 */
 
 
/**
 * Must not be changed post installation ============================================================================================
 */
 
if ( !defined('WPS_OPTIONS_PREFIX') )		define('WPS_OPTIONS_PREFIX', 'symposium'); 								// Prefix for WordPress options table (make this unique!!)



/**
 * Directory and URL of WordPress plugins ===========================================================================================
 */

if ( !defined('WPS_PLUGIN_DIR') ) 			define('WPS_PLUGIN_DIR', WP_PLUGIN_DIR.'/'.WPS_DIR);
if ( !defined('WPS_PLUGIN_URL') ) 			define('WPS_PLUGIN_URL', plugins_url('', __FILE__));


?>
