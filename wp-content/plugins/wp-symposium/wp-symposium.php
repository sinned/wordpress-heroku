<?php
/*
Plugin Name: WP Symposium
Plugin URI: http://www.wpsymposium.com
Description: Turn your WordPress site into a social network. Activate features on the Installation page.
Version: 15.1
Author: Simon Goodchild
Author URI: http://www.wpsymposium.com
License: GPL3
*/

/* Please see licence.txt for End User Licence Agreement */
 
/* ====================================================== SETUP ====================================================== */


// Get constants
require_once(dirname(__FILE__).'/default-constants.php');
include_once(dirname(__FILE__).'/functions.php');
include_once(dirname(__FILE__).'/hooks_filters.php');


global $wpdb, $current_user;

// Set version
define('WPS_VER', '15.1');

// Load activated sub-plugins
require_once(dirname(__FILE__).'/widgets.php');
require_once(dirname(__FILE__).'/yesno.php');
	
// Load optionally activated sub-plugins (via Installation page)	
if ((get_option(WPS_OPTIONS_PREFIX.'__wps__mobile_activated') 				|| get_option(WPS_OPTIONS_PREFIX.'__wps__mobile_network_activated'))				&& file_exists(dirname(__FILE__).'/mobile.php')) 		require_once(dirname(__FILE__).'/mobile.php');
if ((get_option(WPS_OPTIONS_PREFIX.'__wps__forum_activated') 				|| get_option(WPS_OPTIONS_PREFIX.'__wps__forum_network_activated')) 				&& file_exists(dirname(__FILE__).'/forum.php')) 		require_once(dirname(__FILE__).'/forum.php');
if ((get_option(WPS_OPTIONS_PREFIX.'__wps__profile_activated') 				|| get_option(WPS_OPTIONS_PREFIX.'__wps__profile_network_activated'))				&& file_exists(dirname(__FILE__).'/profile.php')) 		require_once(dirname(__FILE__).'/profile.php');
if ((get_option(WPS_OPTIONS_PREFIX.'__wps__mail_activated')					|| get_option(WPS_OPTIONS_PREFIX.'__wps__mail_network_activated'))					&& file_exists(dirname(__FILE__).'/mail.php')) 			require_once(dirname(__FILE__).'/mail.php');
if ((get_option(WPS_OPTIONS_PREFIX.'__wps__members_activated') 				|| get_option(WPS_OPTIONS_PREFIX.'__wps__members_network_activated'))				&& file_exists(dirname(__FILE__).'/members.php')) 		require_once(dirname(__FILE__).'/members.php');
if ((get_option(WPS_OPTIONS_PREFIX.'__wps__add_notification_bar_activated') || get_option(WPS_OPTIONS_PREFIX.'__wps__add_notification_bar_network_activated'))	&& file_exists(dirname(__FILE__).'/panel.php')) 		require_once(dirname(__FILE__).'/panel.php');
if ((get_option(WPS_OPTIONS_PREFIX.'__wps__events_main_activated') 			|| get_option(WPS_OPTIONS_PREFIX.'__wps__events_main_network_activated'))			&& file_exists(dirname(__FILE__).'/events.php')) 		require_once(dirname(__FILE__).'/events.php');
if ((get_option(WPS_OPTIONS_PREFIX.'__wps__facebook_activated')				|| get_option(WPS_OPTIONS_PREFIX.'__wps__facebook_network_activated'))				&& file_exists(dirname(__FILE__).'/facebook.php')) 		require_once(dirname(__FILE__).'/facebook.php');
if ((get_option(WPS_OPTIONS_PREFIX.'__wps__gallery_activated') 				|| get_option(WPS_OPTIONS_PREFIX.'__wps__gallery_network_activated'))				&& file_exists(dirname(__FILE__).'/gallery.php')) 		require_once(dirname(__FILE__).'/gallery.php');
if ((get_option(WPS_OPTIONS_PREFIX.'__wps__groups_activated') 				|| get_option(WPS_OPTIONS_PREFIX.'__wps__groups_network_activated'))				&& file_exists(dirname(__FILE__).'/groups.php')) 		require_once(dirname(__FILE__).'/groups.php');
if ((get_option(WPS_OPTIONS_PREFIX.'__wps__lounge_main_activated') 			|| get_option(WPS_OPTIONS_PREFIX.'__wps__lounge_main_network_activated')) 			&& file_exists(dirname(__FILE__).'/lounge.php')) 		require_once(dirname(__FILE__).'/lounge.php');
if ((get_option(WPS_OPTIONS_PREFIX.'__wps__news_main_activated') 			|| get_option(WPS_OPTIONS_PREFIX.'__wps__news_main_network_activated'))				&& file_exists(dirname(__FILE__).'/news.php')) 			require_once(dirname(__FILE__).'/news.php');
if ((get_option(WPS_OPTIONS_PREFIX.'__wps__profile_plus_activated') 		|| get_option(WPS_OPTIONS_PREFIX.'__wps__profile_plus_network_activated'))			&& file_exists(dirname(__FILE__).'/plus.php')) 			require_once(dirname(__FILE__).'/plus.php');
if ((get_option(WPS_OPTIONS_PREFIX.'__wps__rss_main_activated') 			|| get_option(WPS_OPTIONS_PREFIX.'__wps__rss_main_network_activated'))				&& file_exists(dirname(__FILE__).'/rss.php')) 			require_once(dirname(__FILE__).'/rss.php');
if ((get_option(WPS_OPTIONS_PREFIX.'__wps__mailinglist_activated') 			|| get_option(WPS_OPTIONS_PREFIX.'__wps__mailinglist_network_activated'))			&& file_exists(dirname(__FILE__).'/mailinglist.php'))	require_once(dirname(__FILE__).'/mailinglist.php');

// Actions that are loaded before WordPress can check on page content
add_action('init', '__wps__scriptsAction');
add_action('init', '__wps__languages');
add_action('init', '__wps__js_init');

// Front end actions (includes check if required)
add_action('wp_head', '__wps__header', 10);
add_action('wp_footer', '__wps__concealed_avatar', 10);
add_action('template_redirect', '__wps__replace');
add_action('wp_head', '__wps__add_stylesheet');

// Following required whether features on the page or not
add_action('wp_login', '__wps__login');
add_action('init', '__wps__notification_setoptions');
add_action('wp_footer', '__wps__lastactivity', 10);
add_action('wp_footer', '__wps__dialogs', 10);

function __wps__dialogs() {

	// Dialog
	echo "<div id='dialog' style='display:none'></div>";	
	echo "<div class='__wps__notice' style='display:none; z-index:999999;'><img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/busy.gif' /> ".__('Saving...', WPS_TEXT_DOMAIN)."</div>";
	echo "<div class='__wps__pleasewait' style='display:none; z-index:999999;'><img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/busy.gif' /> ".__('Please Wait...', WPS_TEXT_DOMAIN)."</div>";	
	echo "<div class='__wps__sending' style='display:none; z-index:999999;'><img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/busy.gif' /> ".__('Sending...', WPS_TEXT_DOMAIN)."</div>";	

	// Make a note of the "nobody" user (for access from Javascript)
	$user_info = get_user_by('login', 'nobody');
	$nobody_id = $user_info ? $user_info->ID : 0;
	echo "<div id='nobody_user' style='display:none'>".$nobody_id."</div>";
	
}
// ----------------------------------------------------------------------------------------------------------------------------------------------------------

// Used in WordPress admin
if (is_admin()) {
	include(dirname(__FILE__).'/menu.php');
	add_filter('admin_footer_text', '__wps__footer_admin');
	add_action('admin_notices', '__wps__admin_warnings');
	if (!WPS_HIDE_DASHBOARAD_W) add_action('wp_dashboard_setup', '__wps__dashboard_widget');	
	add_action('init', '__wps__admin_init');
	// deactivation
	register_deactivation_hook(__FILE__, '__wps__deactivate');

}

/* ===================================================== ADMIN ====================================================== */	

// Check for updates
if ( ( get_option(WPS_OPTIONS_PREFIX."_version") != WPS_VER && is_admin()) || (isset($_GET['force_create_wps']) && $_GET['force_create_wps'] == 'yes' && is_admin())) {

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

	// Create initial versions of tables *************************************************************************************

	$wpdb->show_errors();
	
	include('create_tables.php');
	include('create_options.php');


	
	// Update motd flags
	update_option(WPS_OPTIONS_PREFIX.'_motd', '');
	update_option(WPS_OPTIONS_PREFIX.'_reminder', '');
	update_option(WPS_OPTIONS_PREFIX."_install_assist", false);

	// Setup Notifications
	__wps__notification_setoptions();
	
	// ***********************************************************************************************
 	// Update Versions *******************************************************************************
	update_option(WPS_OPTIONS_PREFIX."_version", WPS_VER);

		
}

// Does the current page feature WPS?
function __wps__required() {
	
	// Using panel?
	if (function_exists('__wps__add_notification_bar'))
		return true;

	// Page/post contains shortcode?
	global $post;
	if ($post) {
		$content = $post->post_content;	
		if (strpos($content, '[symposium-') !== FALSE)
			return true;
		
		if (get_option(WPS_OPTIONS_PREFIX.'_always_load')) {
			return true;
		} else {
			return false;
		}
	} else {
		return false;
	}
}

// Any admin warnings
function __wps__admin_warnings() {

   	global $wpdb; 	

	// CSS check
    $myStyleFile = WPS_PLUGIN_DIR . '/css/'.get_option(WPS_OPTIONS_PREFIX.'_wps_css_file');
    if ( !file_exists($myStyleFile) ) {
		echo "<div class='error'><p>".WPS_WL.": ";
		_e( sprintf('Stylesheet (%s) not found.', $myStyleFile), WPS_TEXT_DOMAIN);
		echo "</p></div>";
    }

	// JS check
    $myJSfile = WPS_PLUGIN_DIR . '/js/'.get_option(WPS_OPTIONS_PREFIX.'_wps_js_file');
    if ( !file_exists($myJSfile) ) {
		echo "<div class='error'><p>".WPS_WL.": ";
		_e( sprintf('Javascript file (%s) not found, please check <a href="admin.php?page=symposium_debug"></a>the installation page</a>.', $myJSfile), WPS_TEXT_DOMAIN);
		echo "</p></div>";
    }

    // MOTDs
    if (get_option(WPS_OPTIONS_PREFIX.'_motd') != 'on' && (!(isset($_GET['page']) && $_GET['page'] == 'symposium_welcome'))) {

		if ( current_user_can( 'edit_theme_options' ) ) {   
			if (isset($_POST['symposium_hide_motd']) && $_POST['symposium_hide_motd'] == 'Y') {
				if (!isset($_POST['symposium_hide_motd_nonce']) || wp_verify_nonce($_POST['symposium_hide_motd_nonce'],'symposium_hide_motd_nonce'))
					update_option(WPS_OPTIONS_PREFIX.'_motd', 'on');
			} else {
				__wps__plugin_welcome();
			}
		}
    }

    if (get_option(WPS_OPTIONS_PREFIX.'_reminder') != 'on' && (!(isset($_GET['page']) && $_GET['page'] == 'symposium_welcome'))) {

		if ( current_user_can( 'edit_theme_options' ) ) {   
			if (isset($_POST['symposium_hide_reminder']) && $_POST['symposium_hide_reminder'] == 'Y') {
				if (wp_verify_nonce($_POST['symposium_hide_reminder_nonce'],'symposium_hide_reminder_nonce'))
					update_option(WPS_OPTIONS_PREFIX.'_reminder', 'on');
			} else {
				__wps__plugin_reminder();
			}
		}
    }
    		
	// Check for legacy plugin folders	    
	$list = '';
	if (file_exists(WP_PLUGIN_DIR.'/wp-symposium-alerts')) { $list .= WP_PLUGIN_DIR.'/wp-symposium-alerts<br />'; }
	if (file_exists(WP_PLUGIN_DIR.'/wp-symposium-events')) { $list .= WP_PLUGIN_DIR.'/wp-symposium-events<br />'; }
	if (file_exists(WP_PLUGIN_DIR.'/wp-symposium-facebook')) { $list .= WP_PLUGIN_DIR.'/wp-symposium-facebook<br />'; }
	if (file_exists(WP_PLUGIN_DIR.'/wp-symposium-gallery')) { $list .= WP_PLUGIN_DIR.'/wp-symposium-gallery<br />'; }
	if (file_exists(WP_PLUGIN_DIR.'/wp-symposium-groups')) { $list .= WP_PLUGIN_DIR.'/wp-symposium-groups<br />'; }
	if (file_exists(WP_PLUGIN_DIR.'/wp-symposium-lounge')) { $list .= WP_PLUGIN_DIR.'/wp-symposium-lounge<br />'; }
	if (file_exists(WP_PLUGIN_DIR.'/wp-symposium-mobile')) { $list .= WP_PLUGIN_DIR.'/wp-symposium-mobile<br />'; }
	if (file_exists(WP_PLUGIN_DIR.'/wp-symposium-plus')) { $list .= WP_PLUGIN_DIR.'/wp-symposium-plus<br />'; }
	if (file_exists(WP_PLUGIN_DIR.'/wp-symposium-mailinglist')) { $list .= WP_PLUGIN_DIR.'/wp-symposium-mailinglist<br />'; }
	if (file_exists(WP_PLUGIN_DIR.'/wp-symposium-rss')) { $list .= WP_PLUGIN_DIR.'/wp-symposium-rss<br />'; }
	if (file_exists(WP_PLUGIN_DIR.'/wp-symposium-yesno')) { $list .= WP_PLUGIN_DIR.'/wp-symposium-yesno<br />'; }
	if ($list != '') {
		echo '<div class="updated" style="margin-top:15px">';
		echo "<strong>".WPS_WL."</strong><br /><div style='padding:4px;'>";
		echo __('Please remove the following folders via FTP.<br />Do <strong>NOT</strong> remove them via the plugins admin page as this could delete data from your database:', WPS_TEXT_DOMAIN).'<br /><br />';
		echo $list;
		echo '</div></div>';
	}
    
}

// Dashboard Widget
function __wps__dashboard_widget(){
	wp_add_dashboard_widget('symposium_id', WPS_WL, '__wps__widget');
}
function __wps__widget() {
	
	global $wpdb, $current_user;
	
	echo '<img src="'.get_option(WPS_OPTIONS_PREFIX.'_images').'/logo_small.png" alt="Logo" style="float:right; width:120px;height:120px;" />';

	echo '<table><tr><td valign="top">';
	
		echo '<table>';
		echo '<tr><td colspan="2" style="padding:4px"><strong>'.__('Forum', WPS_TEXT_DOMAIN).'</strong></td></tr>';
		echo '<tr><td style="padding:4px"><a href="admin.php?page=symposium_categories">'.__('Categories', WPS_TEXT_DOMAIN).'</a></td>';
		echo '<td style="padding:4px">'.$wpdb->get_var("SELECT COUNT(*) FROM ".$wpdb->prefix.'symposium_cats').'</td></tr>';
		echo '<tr><td style="padding:4px">'.__('Topics', WPS_TEXT_DOMAIN).'</td>';
		echo '<td style="padding:4px">'.$wpdb->get_var("SELECT COUNT(*) FROM ".$wpdb->prefix.'symposium_topics'." WHERE topic_parent = 0").'</td></tr>';
		echo '<tr><td style="padding:4px">'.__('Replies', WPS_TEXT_DOMAIN).'</td>';
		echo '<td style="padding:4px">'.$wpdb->get_var("SELECT COUNT(*) FROM ".$wpdb->prefix.'symposium_topics'." WHERE topic_parent > 0").'</td></tr>';
		echo '<tr><td style="padding:4px">'.__('Views', WPS_TEXT_DOMAIN).'</td>';
		echo '<td style="padding:4px">'.$wpdb->get_var("SELECT SUM(topic_views) FROM ".$wpdb->prefix.'symposium_topics'." WHERE topic_parent = 0").'</td></tr>';
		echo '<tr><td style="padding:4px">'.__('Mail', WPS_TEXT_DOMAIN).'</td>';
		$mailcount = $wpdb->get_var("SELECT COUNT(*) FROM ".$wpdb->base_prefix.'symposium_mail');
		$unread = $wpdb->get_var("SELECT COUNT(*) FROM ".$wpdb->base_prefix.'symposium_mail'." WHERE mail_read != 'on'");
		echo '<td style="padding:4px">'.$mailcount.' ';
		printf (__('(%s unread)', WPS_TEXT_DOMAIN), $unread);
		echo '</td></tr>';
		echo '</table>';
		
	echo "</td><td valign='top'>";

		echo '<table>';
			echo '<tr><td colspan="2" style="padding:4px"><strong>'.__('Plugins', WPS_TEXT_DOMAIN).'</strong></td></tr>';
			echo '<tr><td colspan="2" style="padding:4px">';
			if (function_exists('__wps__forum')) {
				echo '<a href="'.__wps__get_url('forum').'">'.__('Go to Forum', WPS_TEXT_DOMAIN).'</a>';
			} else {
				echo __('Forum not activated', WPS_TEXT_DOMAIN);
			}
			echo "</td></tr>";
			
			echo '<tr><td colspan="2" style="padding:4px">';
			if (function_exists('__wps__profile')) {
				$url = __wps__get_url('profile');
				echo '<a href="'.$url.__wps__string_query($url).'uid='.$current_user->ID.'">'.__('Go to Profile', WPS_TEXT_DOMAIN).'</a>';
			} else {
				echo __('Profile not activated', WPS_TEXT_DOMAIN);
			}
			echo "</td></tr>";
	
			echo '<tr><td colspan="2" style="padding:4px">';
			if (function_exists('__wps__mail')) {
				echo '<a href="'.__wps__get_url('mail').'">'.__('Go to Mail', WPS_TEXT_DOMAIN).'</a>';
			} else {
				echo __('Mail not activated', WPS_TEXT_DOMAIN);
			}
			echo "</td></tr>";
			
			echo '<tr><td colspan="2" style="padding:4px">';
			if (function_exists('__wps__members')) {
				echo '<a href="'.__wps__get_url('members').'">'.__('Go to Member Directory', WPS_TEXT_DOMAIN).'</a>';
			} else {
				echo __('Member Directory not activated', WPS_TEXT_DOMAIN);
			}
			echo "</td></tr>";
			
			echo '<tr><td colspan="2" style="padding:4px">';
			if (function_exists('__wps__group')) {
				echo '<a href="'.__wps__get_url('groups').'">'.__('Go to Group Directory', WPS_TEXT_DOMAIN).'</a><br />';
			} else {
				echo __('Groups not activated', WPS_TEXT_DOMAIN);
			}
			echo "</td></tr>";
			
		echo "</table>";

	echo "</td></tr></table>";

}

function __wps__deactivate() {

	wp_clear_scheduled_hook('symposium_notification_hook');
	delete_option('symposium_debug_mode');
	delete_option(WPS_OPTIONS_PREFIX."_version");

}

/* ====================================================== NOTIFICATIONS ====================================================== */

function __wps__notification_setoptions() {
	update_option(WPS_OPTIONS_PREFIX."_notification_inseconds",86400);
	// 60 = 1 minute, 3600 = 1 hour, 10800 = 3 hours, 21600 = 6 hours, 43200 = 12 hours, 86400 = Daily, 604800 = Weekly
	/* This is where the actual recurring event is scheduled */
	if (!wp_next_scheduled('symposium_notification_hook')) {
		$dt=explode(':',date('d:m:Y',time()));
		$schedule=mktime(0,1,0,$dt[1],$dt[0],$dt[2])+86400;
		// set for 00:01 from tomorrow
		wp_schedule_event($schedule, "symposium_notification_recc", "symposium_notification_hook");
	}
}

/* a reccurence has to be added to the cron_schedules array */
add_filter('cron_schedules', '__wps__notification_more_reccurences');
function __wps__notification_more_reccurences($recc) {
	$recc['symposium_notification_recc'] = array('interval' => get_option(WPS_OPTIONS_PREFIX."_notification_inseconds"), 'display' => WPS_WL_SHORT.' Notification Schedule');
	return $recc;
}
	
/* This is the scheduling hook for our plugin that is triggered by cron */
function __wps__notification_trigger_schedule() {
	__wps__notification_do_jobs('cron');
}

/* This is called by the scheduled cron job, and by Health Check Daily Digest check */
function __wps__notification_do_jobs($mode) {
	
	global $wpdb;
	$summary_email = __("Website Title", WPS_TEXT_DOMAIN).": ".get_bloginfo('name')."<br />";
	$summary_email .= __("Website URL", WPS_TEXT_DOMAIN).": ".get_bloginfo('wpurl')."<br />";
	$summary_email .= __("Admin Email", WPS_TEXT_DOMAIN).": ".get_bloginfo('admin_email')."<br />";
	$summary_email .= __("WordPress version", WPS_TEXT_DOMAIN).": ".get_bloginfo('version')."<br />";
	$summary_email .= sprintf(__("%s version", WPS_TEXT_DOMAIN), WPS_WL).": ".WPS_VER."<br />";
	$summary_email .= __("Daily Digest mode", WPS_TEXT_DOMAIN).": ".$mode."<br /><br />";
	$topics_count = 0;
	$user_count = 0;
	$success = "INCOMPLETE. ";
		

	$users_sent_to_success = '';
	$users_sent_to_failed = '';
				
	// ******************************************* Daily Digest ******************************************
	$send_summary = get_option(WPS_OPTIONS_PREFIX.'_send_summary');
	if ($send_summary == "on" || $mode == 'cron' || $mode == 'symposium_dailydigest_admin' || $mode == 'send_admin_summary_and_to_users') {
		
		// Calculate yesterday			
		$startTime = mktime(0, 0, 0, date('m'), date('d')-1, date('Y'));
		$endTime = mktime(23, 59, 59, date('m'), date('d')-1, date('Y'));
		
		// Get all new topics from previous period
		$topics_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM ".$wpdb->prefix.'symposium_topics'." WHERE topic_parent = %d AND UNIX_TIMESTAMP(topic_date) >= ".$startTime." AND UNIX_TIMESTAMP(topic_date) <= ".$endTime, 0));

		if ($topics_count > 0 || $mode == 'symposium_dailydigest_admin') {

			// Get Forum URL 
			$forum_url = __wps__get_url('forum');
			// Decide on query suffix on whether a permalink or not
			if (strpos($forum_url, '?') !== FALSE) {
				$q = "&";
			} else {
				$q = "?";
			}

			$body = "";
			
			$categories = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix.'symposium_cats'." ORDER BY listorder"); 
			if ($categories) {
				foreach ($categories as $category) {
					
					$shown_category = false;
					$topics = $wpdb->get_results("
						SELECT tid, topic_subject, topic_parent, topic_post, topic_date, display_name, topic_category 
						FROM ".$wpdb->prefix.'symposium_topics'." INNER JOIN ".$wpdb->base_prefix.'users'." ON ".$wpdb->prefix.'symposium_topics'.".topic_owner = ".$wpdb->base_prefix.'users'.".ID 
						WHERE topic_parent = 0 AND topic_category = ".$category->cid." AND UNIX_TIMESTAMP(topic_date) >= ".$startTime." AND UNIX_TIMESTAMP(topic_date) <= ".$endTime." 
						ORDER BY tid"); 
					if ($topics) {
						if (!$shown_category) {
							$shown_category = true;
							$body .= "<h1>".stripslashes($category->title)."</h1>";
						}
						$body .= "<h2>".__('New Topics', WPS_TEXT_DOMAIN)."</h2>";
						$body .= "<ol>";
						foreach ($topics as $topic) {
							$body .= "<li><strong><a href='".$forum_url.$q."cid=".$category->cid."&show=".$topic->tid."'>".stripslashes($topic->topic_subject)."</a></strong>";
							$body .= " started by ".$topic->display_name.":<br />";																
							$body .= stripslashes($topic->topic_post);
							$body .= "</li>";
						}
						$body .= "</ol>";
					}

					$replies = $wpdb->get_results("
						SELECT tid, topic_subject, topic_parent, topic_post, topic_date, display_name, topic_category 
						FROM ".$wpdb->prefix.'symposium_topics'." INNER JOIN ".$wpdb->base_prefix.'users'." ON ".$wpdb->prefix.'symposium_topics'.".topic_owner = ".$wpdb->base_prefix.'users'.".ID 
						WHERE topic_parent > 0 AND topic_category = ".$category->cid." AND UNIX_TIMESTAMP(topic_date) >= ".$startTime." AND UNIX_TIMESTAMP(topic_date) <= ".$endTime."
						ORDER BY topic_parent, tid"); 
					if ($replies) {
						if (!$shown_category) {
							$shown_category = true;
							$body .= "<h1>".$category->title."</h1>";
						}
						$body .= "<h2>".__('Replies in', WPS_TEXT_DOMAIN)." ".$category->title."</h2>";
						$current_parent = '';
						foreach ($replies as $reply) {
							$parent = $wpdb->get_var($wpdb->prepare("SELECT topic_subject FROM ".$wpdb->prefix.'symposium_topics'." WHERE tid = %d", $reply->topic_parent));
							if ($parent != $current_parent) {
								$body .= "<h3>".$parent."</h3>";
								$current_parent = $parent;
							}
							$body .= "<em>".$reply->display_name." wrote:</em> ";
							$post = __wps__clean_html(stripslashes($reply->topic_post));							
							if (strlen($post) > 100) { $post = substr($post, 0, 100)."..."; }
							if (strpos($reply->topic_post, '<iframe src=\"http://www.youtube.com') !== FALSE)
								$post .= " (".__('video', WPS_TEXT_DOMAIN).")";
							$body .= $post;
							$body .= " <a href='".$forum_url.$q."cid=".$category->cid."&show=".$topic->tid."'>".__('View topic', WPS_TEXT_DOMAIN)."...</a>";
							$body .= "<br />";
							$body .= "<br />";
						}						
					}	
				}
			}
			
			$body .= "<p>".__("You can stop receiving these emails at", WPS_TEXT_DOMAIN)." <a href='".$forum_url."'>".$forum_url."</a>.</p>";
			
			// Send the mail
			if (($mode == 'cron' && get_option(WPS_OPTIONS_PREFIX.'_send_summary') == "on") || $mode == 'send_admin_summary_and_to_users') {
				// send to all users
				$users = $wpdb->get_results("SELECT DISTINCT user_email 
				FROM ".$wpdb->base_prefix.'users'." u 
				INNER JOIN ".$wpdb->base_prefix."usermeta m ON u.ID = m.user_id 
				WHERE meta_key = 'symposium_forum_digest' and m.meta_value = 'on'"); 
				
				if ($users) {
					foreach ($users as $user) {
						$user_count++;
						$email = $user->user_email;
						if(__wps__sendmail($email, __('Daily Forum Digest', WPS_TEXT_DOMAIN), $body)) {
							$users_sent_to_success .= $user->user_email.'<br />';
							update_option(WPS_OPTIONS_PREFIX."_notification_triggercount",get_option(WPS_OPTIONS_PREFIX."_notification_triggercount")+1);
						} else {
							$users_sent_to_failed .= $user->user_email.'<br />';
						}						
					}
				} else {
					$users_sent_to_success = __('No users have selected to receive the digest.', WPS_TEXT_DOMAIN).'<br />';
				}
			}
			if ($mode == 'symposium_dailydigest_admin') {
				// send to admin only
				if(__wps__sendmail(get_bloginfo('admin_email'), __('Daily Forum Digest (admin only)', WPS_TEXT_DOMAIN), $body)) {
					$users_sent_to_success .= get_bloginfo('admin_email').'<br />';
				} else {
					$users_sent_to_failed .= get_bloginfo('admin_email').'<br />';
				}										
			}

		}
	}
	
	// Send admin summary
	$summary_email .= __("Forum topic count for previous day (midnight to midnight)", WPS_TEXT_DOMAIN).": ".$topics_count."<br />";
	$summary_email .= __("Daily Digest sent count", WPS_TEXT_DOMAIN).": ".$user_count."<br /><br />";
	$summary_email .= "<b>".__("List of recipients sent to:", WPS_TEXT_DOMAIN)."</b><br />";
	if ($users_sent_to_success != '') {
	$summary_email .= $users_sent_to_success;
	} else {
		$summary_email .= 'None.';
	}
	$summary_email .= "<br /><br /><b>List of sent failures:</b><br />";
	if ($users_sent_to_failed != '') {
		$summary_email .= $users_sent_to_failed;
	} else {
		$summary_email .= 'None.';
	}
	$email = get_bloginfo('admin_email');
	if (__wps__sendmail($email, __('Daily Digest Summary Report', WPS_TEXT_DOMAIN), $summary_email)) {
		$success = "OK<br />(summary sent to ".get_bloginfo('admin_email').")<br />";
	} else {
		$success = "FAILED sending to ".get_bloginfo('admin_email').". ";
	}
	
	return $success;
	
}

// Record last logged in and previously logged in 
function __wps__login($user_login) {

	global $wpdb, $current_user;

	// Get ID for this user
	$sql = "SELECT ID from ".$wpdb->base_prefix."users WHERE user_login = %s";
	$id = $wpdb->get_var($wpdb->prepare($sql, $user_login));

	if (__wps__get_meta($id, 'status') != 'offline') {
		// Get last time logged in
		$last_login = __wps__get_meta($id, 'last_login');
		// And previous login
		$previous_login = __wps__get_meta($id, 'previous_login');

		// Store as previous time last logged in
		if ($previous_login == NULL) {
			__wps__update_meta($id, 'previous_login', "'".date("Y-m-d H:i:s")."'");
		} else {
			__wps__update_meta($id, 'previous_login', "'".$last_login."'");
		}
		// Store this log in as the last time logged in
		__wps__update_meta($id, 'last_login', "'".date("Y-m-d H:i:s")."'");

	}	
}

// Replace get_avatar 
if ( (get_option(WPS_OPTIONS_PREFIX.'_profile_avatars') == "on") && ( !function_exists('get_avatar') ) ) {

	function get_avatar( $id_or_email, $size = '96', $default = '', $alt = false, $link = true ) {

		global $wpdb, $current_user;
							
		if ( false === $alt)
			$safe_alt = '';
		else
			$safe_alt = esc_attr( $alt );
	
		if ( !is_numeric($size) )
			$size = '96';
	
		$email = '';
		$display_name = '';
		if ( is_numeric($id_or_email) ) {
			$id = (int) $id_or_email;
			$user = get_userdata($id);
			if ( $user )
				$email = $user->user_email;
		} elseif ( is_object($id_or_email) ) {
			// No avatar for pingbacks or trackbacks
			$allowed_comment_types = apply_filters( 'get_avatar_comment_types', array( 'comment' ) );
			if ( ! empty( $id_or_email->comment_type ) && ! in_array( $id_or_email->comment_type, (array) $allowed_comment_types ) )
				return false;
	
			if ( !empty($id_or_email->user_id) ) {
				$id = (int) $id_or_email->user_id;
				$user = get_userdata($id);
				if ( $user)
					$email = $user->user_email;
			} elseif ( !empty($id_or_email->comment_author_email) ) {
				$email = $id_or_email->comment_author_email;
			}
		} else {
			$id = $wpdb->get_var("select ID from ".$wpdb->base_prefix."users where user_email = '".$id_or_email."'");
		}
	
		if ( empty($default) ) {
			$avatar_default = get_option('avatar_default');
			if ( empty($avatar_default) )
				$default = 'mystery';
			else
				$default = $avatar_default;
		}
	
		if ( !empty($email) )
			$email_hash = md5( strtolower( $email ) );
	
		if ( is_ssl() ) {
			$host = 'https://secure.gravatar.com';
		} else {
			if ( !empty($email) )
				$host = sprintf( "http://%d.gravatar.com", ( hexdec( $email_hash[0] ) % 2 ) );
			else
				$host = 'http://0.gravatar.com';
		}
	
		if ( 'mystery' == $default )
			$default = "$host/avatar/ad516503a11cd5ca435acc9bb6523536?s={$size}"; // ad516503a11cd5ca435acc9bb6523536 == md5('unknown@gravatar.com')
		elseif ( 'blank' == $default )
			$default = includes_url('images/blank.gif');
		elseif ( !empty($email) && 'gravatar_default' == $default )
			$default = '';
		elseif ( 'gravatar_default' == $default )
			$default = "$host/avatar/s={$size}";
		elseif ( empty($email) )
			$default = "$host/avatar/?d=$default&amp;s={$size}";
		elseif ( strpos($default, 'http://') === 0 )
			$default = add_query_arg( 's', $size, $default );
			
		if ( !empty($email) ) {
			$out = "$host/avatar/";
			$out .= $email_hash;
			$out .= '?s='.$size;
			$out .= '&amp;d=' . urlencode( $default );
	
			$rating = get_option('avatar_rating');
			if ( !empty( $rating ) )
				$out .= "&amp;r={$rating}";
	
			$avatar = "<img alt='{$safe_alt}' src='{$out}' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";
		} else {
			$avatar = "<img alt='{$safe_alt}' src='{$default}' class='avatar avatar-{$size} photo avatar-default' height='{$size}' width='{$size}' />";
		}
		
		$return = '';
		
		if (!isset($id)) { $id = 0; }
		if (get_option(WPS_OPTIONS_PREFIX.'_img_db') == "on") {
		
			$profile_photo = __wps__get_meta($id, 'profile_avatar');
			$profile_avatars = get_option(WPS_OPTIONS_PREFIX.'_profile_avatars');
		
			if ($profile_photo == '' || $profile_photo == 'upload_failed' || $profile_avatars != 'on') {
				$return .= apply_filters('get_avatar', $avatar, $id_or_email, $size, $default, $alt);
			} else {
				$return .= "<img src='".WP_CONTENT_URL."/plugins/".WPS_DIR."/server/get_profile_avatar.php?uid=".$id."' style='width:".$size."px; height:".$size."px' class='avatar avatar-".$size." photo' />";
			}
			
		} else {

			$profile_photo = __wps__get_meta($id, 'profile_photo');
			$profile_avatars = get_option(WPS_OPTIONS_PREFIX.'_profile_avatars');

			if ($profile_photo == '' || $profile_photo == 'upload_failed' || $profile_avatars != 'on') {
				$return .= apply_filters('get_avatar', $avatar, $id_or_email, $size, $default, $alt);
			} else {
				$img_url = get_option(WPS_OPTIONS_PREFIX.'_img_url')."/members/".$id."/profile/";	
				$img_src = str_replace('//','/',$img_url) . $profile_photo;
				$return .= "<img src='".$img_src."' style='width:".$size."px; height:".$size."px' class='avatar avatar-".$size." photo' />";
			}
			
		}
		
		if (!get_option(WPS_OPTIONS_PREFIX.'_wps_use_gravatar') && strpos($return, 'gravatar')) {
			$return = "<img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/unknown.jpg' style='width:".$size."px; height:".$size."px' class='avatar avatar-".$size." photo' />";
		}

		// Get URL to profile
		if (function_exists('__wps__profile') && $id != '' ) {
			$profile_url = __wps__get_url('profile');
			$profile_url = $profile_url.__wps__string_query($profile_url).'uid='.$id;
			if ($link) {
				$p = " style='cursor:pointer' onclick='javascript:document.location=\"".$profile_url."\";' />";
			} else {
				$p = " style='cursor:pointer' />";
			}
	       	$return = str_replace("/>", $p, $return);                          
		}

		// Filter to allow changes
		$return = apply_filters('__wps__get_avatar_filter', $return, $id);

		// Add Profile Plus (hover box) if installed
		if (function_exists('__wps__profile_plus')) {
			if (get_option(WPS_OPTIONS_PREFIX.'_wps_show_hoverbox') == 'on') {
				if ($id != '') {
					$display_name = str_replace("'", "&apos;", $wpdb->get_var("select display_name from ".$wpdb->base_prefix."users where ID = '".$id."'"));
				} else {
					$display_name = '';
				}
				if (__wps__friend_of($id, $current_user->ID)) {
			       	$return = str_replace("class='", "rel='friend' title = '".$display_name."' id='".$id."' class='__wps__follow ", $return);
				} else {
					if (__wps__pending_friendship($id)) {
				       	$return = str_replace("class='", "rel='pending' title = '".$display_name."' id='".$id."' class='__wps__follow ", $return);
					} else {
				       	$return = str_replace("class='", "rel='' title = '".$display_name."' id='".$id."' class='__wps__follow ", $return);
					}
				}
				if (__wps__is_following($current_user->ID, $id)) {
					$return = str_replace("class='", "rev='following' class='", $return);
				} else {
					$return = str_replace("class='", "rev='' class='", $return);
				}
			}
		}

		return $return;

	}
	
}

// Update user activity on page load
function __wps__lastactivity() {
   	global $wpdb, $current_user;
	wp_get_current_user();
	
	// Update last logged in
	if (is_user_logged_in() && __wps__get_meta($current_user->ID, 'status') != 'offline') {
		__wps__update_meta($current_user->ID, 'last_activity', "'".date("Y-m-d H:i:s")."'");
	}

}

function __wps__concealed_avatar() {
	if (__wps__required()) {
		global $current_user;
		// Place hidden div of current user to use when adding to screen
		echo "<div id='__wps__current_user_avatar' style='display:none;'>";
		echo get_avatar($current_user->ID, 200);
		echo "</div>";
		// Hover box
		echo "<div id='__wps__follow_box' class='widget-area corners' style='display:none'>Hi</div>";
	}
}

function __wps__footer_admin () {
	// Hidden DIV for admin dialog boxes
	echo '<span id="footer-thankyou">' . __( 'Thank you for creating with <a href="http://wordpress.org/">WordPress</a>.' ) . '</span>';
	echo "<div id='symposium_dialog' class='wp-dialog' style='padding:10px;display:none'></div>";				
}

// Hook to replace Smilies
function __wps__buffer($buffer){ // $buffer contains entire page

	if (!get_option(WPS_OPTIONS_PREFIX.'_wps_lite') && !strpos($buffer, "<rss") ) {

		global $wpdb;
		
		if (get_option(WPS_OPTIONS_PREFIX.'_emoticons') == "on") {
			
			$smileys = WPS_PLUGIN_URL . '/images/smilies/';
			$smileys_dir = WPS_PLUGIN_DIR . '/images/smilies/';
			// Smilies as classic text
			$buffer = str_replace(":)", "<img src='".$smileys."smile.png' />", $buffer);
			$buffer = str_replace(":-)", "<img src='".$smileys."smile.png' />", $buffer);
			$buffer = str_replace(":(", "<img src='".$smileys."sad.png' />", $buffer);
			$buffer = str_replace(":'(", "<img src='".$smileys."crying.png' />", $buffer);
			$buffer = str_replace(":x", "<img src='".$smileys."kiss.png' />", $buffer);
			$buffer = str_replace(":X", "<img src='".$smileys."shutup.png' />", $buffer);
			$buffer = str_replace(":D", "<img src='".$smileys."laugh.png' />", $buffer);
			$buffer = str_replace(":|", "<img src='".$smileys."neutral.png' />", $buffer);
			$buffer = str_replace(":?", "<img src='".$smileys."question.png' />", $buffer);
			$buffer = str_replace(":z", "<img src='".$smileys."sleepy.png' />", $buffer);
			$buffer = str_replace(":P", "<img src='".$smileys."tongue.png' />", $buffer);
			$buffer = str_replace(";)", "<img src='".$smileys."wink.png' />", $buffer);
			// Other images
			
			$i = 0;
			do {
				$i++;
				$start = strpos($buffer, "{{");
				if ($start === false) {
				} else {
					$end = strpos($buffer, "}}");
					if ($end === false) {
					} else {
						$first_bit = substr($buffer, 0, $start);
						$last_bit = substr($buffer, $end+2, strlen($buffer)-$end-2);
						$bit = substr($buffer, $start+2, $end-$start-2);
						$buffer = $first_bit."<img style='width:24px;height:24px' src='".$smileys.strip_tags($bit).".png' />".$last_bit;
					}
				}
			} while ($i < 100 && strpos($buffer, "{{")>0);
			
		}
			
		if (get_option(WPS_OPTIONS_PREFIX.'_tags') == "on") {

			// User tagging		
			
			$profile_url = __wps__get_url('profile');
			$profile = $profile_url.__wps__string_query($profile_url).'uid=';
			$needles = array();
			for($i=0;$i<=47;$i++){ array_push($needles, chr($i)); }
			for($i=58;$i<=63;$i++){ array_push($needles, chr($i)); }
			for($i=91;$i<=96;$i++){ array_push($needles, chr($i)); }
			
			$i = 0;
			do {
				$i++;
				$start = strpos($buffer, "@");
				if ($start === false) {
				} else {
					$end = __wps__strpos($buffer, $needles, $start);
					if ($end === false) $end = strlen($buffer);
					$first_bit = substr($buffer, 0, $start);
					$last_bit = substr($buffer, $end, strlen($buffer)-$end+2);
					$bit = substr($buffer, $start+1, $end-$start-1);
					$sql = 'SELECT ID FROM '.$wpdb->base_prefix.'users WHERE replace(display_name, " ", "") = %s LIMIT 0,1';
					$id = $wpdb->get_var($wpdb->prepare($sql, $bit));
					if ($id) {
						$buffer = $first_bit.'<a href="'.$profile.$id.'" class="__wps__usertag">&#64;'.$bit.'</a>'.$last_bit;
					} else {
						$sql = 'SELECT ID FROM '.$wpdb->base_prefix.'users WHERE user_login = %s LIMIT 0,1';
						$id = $wpdb->get_var($wpdb->prepare($sql, $bit));
						if ($id) {
							$buffer = $first_bit.'<a href="'.$profile.$id.'" class="__wps__usertag">&#64;'.$bit.'</a>'.$last_bit;
						} else {
							$buffer = $first_bit.'&#64;'.$bit.$last_bit;
						}
					}
				}
			} while ($i < 100 && strpos($buffer, "@"));		
		}
		
	}

	return $buffer;
	
}

function __wps__strip_smilies($buffer){ 
	$buffer = str_replace(":)", "", $buffer);
	$buffer = str_replace(":-)", "", $buffer);
	$buffer = str_replace(":(", "", $buffer);
	$buffer = str_replace(":'(", "", $buffer);
	$buffer = str_replace(":x", "", $buffer);
	$buffer = str_replace(":X", "", $buffer);
	$buffer = str_replace(":D", "", $buffer);
	$buffer = str_replace(":|", "", $buffer);
	$buffer = str_replace(":?", "", $buffer);
	$buffer = str_replace(":z", "", $buffer);
	$buffer = str_replace(":P", "", $buffer);
	$buffer = str_replace(";)", "", $buffer);
	
	return $buffer;
}

// Hook for adding unread mail, etc
function __wps__unread($buffer){ 
	
   	global $wpdb, $current_user;
	wp_get_current_user();

	// Unread mail
	$unread_in = $wpdb->get_var("SELECT COUNT(*) FROM ".$wpdb->base_prefix.'symposium_mail'." WHERE mail_to = ".$current_user->ID." AND mail_in_deleted != 'on' AND mail_read != 'on'");
	if ($unread_in > 0) {
		$buffer = str_replace("%m", "(".$unread_in.")", $buffer);
	} else {
		$buffer = str_replace("%m", "", $buffer);
	}
	
    // Pending friends
	$pending_friends = $wpdb->get_var("SELECT COUNT(*) FROM ".$wpdb->base_prefix."symposium_friends f WHERE f.friend_to = ".$current_user->ID." AND f.friend_accepted != 'on'");

	if ($pending_friends > 0) {
		$buffer = str_replace("%f", "(".$pending_friends.")", $buffer);
	} else {
		$buffer = str_replace("%f", "", $buffer);
	}

    return $buffer;
    
}

// Add jQuery and jQuery scripts
function __wps__js_init() {

	global $wpdb;
		
	$plugin = WPS_PLUGIN_URL;

	// Only load if not admin (and chosen in Settings)
	if (!is_admin()) {

		if (get_option(WPS_OPTIONS_PREFIX.'_jquery') == "on") {
			wp_enqueue_script('jquery');	 		
		}

		if (get_option(WPS_OPTIONS_PREFIX.'_jqueryui') == "on") {
			wp_enqueue_script('jquery-ui-custom', $plugin.'/js/jquery-ui-1.10.3.custom.min.js', array('jquery'));	
		    wp_register_style('__wps__jquery-ui-css', WPS_PLUGIN_URL.'/css/jquery-ui-1.10.3.custom.css');
			wp_enqueue_style('__wps__jquery-ui-css');
		}	

	 	if (get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg') == "on" || function_exists('__wps__events_main') || function_exists('__wps__group')) {
	 		if (!get_option(WPS_OPTIONS_PREFIX.'_tinymce') == "on") {
		 		wp_enqueue_script('wps-tinymce', $plugin.'/tiny_mce/tiny_mce_src.js', array('jquery'));	
		 	}
	 	}

	 	if (get_option(WPS_OPTIONS_PREFIX.'_jwplayer') == "on") {
	 		wp_enqueue_script('wps-jwplayer', $plugin.'/js/jwplayer.js', array('jquery'));	
	 	}

		// Upload CSS
	    wp_register_style('__wps__upload_ui_css', WPS_PLUGIN_URL.'/css/jquery.fileupload-ui.css');
		wp_enqueue_style('__wps__upload_ui_css');
	    // Upload JS
		wp_enqueue_script('__wps__tmpl', WPS_PLUGIN_URL.'/js/tmpl.min.js', array('jquery'));	
		wp_enqueue_script('__wps__load_image', WPS_PLUGIN_URL.'/js/load-image.min.js', array('jquery'));	
		wp_enqueue_script('__wps__canvas_to_blob', WPS_PLUGIN_URL.'/js/canvas-to-blob.min.js', array('jquery'));	
		wp_enqueue_script('__wps__iframe_transport', WPS_PLUGIN_URL.'/js/jquery.iframe-transport.js', array('jquery'));	
		wp_enqueue_script('__wps__fileupload', WPS_PLUGIN_URL.'/js/jquery.fileupload.js', array('jquery'));	
		wp_enqueue_script('__wps__fileupload_fp', WPS_PLUGIN_URL.'/js/jquery.fileupload-fp.js', array('jquery'));	
		wp_enqueue_script('__wps__fileupload_ui', WPS_PLUGIN_URL.'/js/jquery.fileupload-ui.js', array('jquery'));	

	}
	
}

// Perform admin duties, such as add jQuery and jQuery scripts and other admin jobs
function __wps__admin_init() {
	if (is_admin()) {

		// jQuery dialog box for use in admin
		wp_enqueue_script( 'jquery-ui-dialog' );
		wp_enqueue_style( 'wp-jquery-ui-dialog' );
		
		// WordPress color picker
		wp_enqueue_style( 'farbtastic' );
	    wp_enqueue_script( 'farbtastic' );

	  	// Load admin CSS
	  	$myStyleUrl = WPS_PLUGIN_URL . '/css/wps-admin.css';
	  	$myStyleFile = WPS_PLUGIN_DIR . '/css/wps-admin.css';
	  	if ( file_exists($myStyleFile) ) {
	    	wp_register_style('__wps__Admin_StyleSheet', $myStyleUrl);
	    	wp_enqueue_style('__wps__Admin_StyleSheet');
	  	}

	}
}

// Add JS scripts to WordPress for use and other preparatory stuff
function __wps__scriptsAction() {

	$__wps__plugin_url = WPS_PLUGIN_URL;
	$__wps__plugin_path = str_replace("http://".$_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"], "", $__wps__plugin_url);
 
	global $wpdb, $current_user;
	wp_get_current_user();

	// Set script timeout
	if (get_option(WPS_OPTIONS_PREFIX.'_wps_time_out') > 0) {
		set_time_limit(get_option(WPS_OPTIONS_PREFIX.'_wps_time_out'));
	}

	// Debug mode?
	define('WPS_DEBUG', get_option(WPS_OPTIONS_PREFIX.'_debug_mode'));

	// Using Panel?
	$use_panel = false;
	if ((get_option(WPS_OPTIONS_PREFIX.'__wps__add_notification_bar_activated') || get_option(WPS_OPTIONS_PREFIX.'__wps__add_notification_bar_network_activated'))	&& file_exists(dirname(__FILE__).'/panel.php'))
		$use_panel = true;
		
	// Set up variables for use throughout
	if (!is_admin()) {

		// Mail
		if ( !isset($_GET['view']) ) { 
			$view = "in"; 
		} else {
			$view = $_GET['view'];
		} 
	
		// Current User Page (eg. a profile page)
		if (isset($_GET['uid'])) {
			$page_uid = $_GET['uid']*1;
		} else {
			$page_uid = 0;
			if (isset($_POST['uid'])) { 
				$page_uid = $_POST['uid']*1; 
			} else {
				// Try the permalink?
				if (get_option(WPS_OPTIONS_PREFIX.'_permalink_structure')) {
					// get URL
					$url = $_SERVER["REQUEST_URI"];
					
					// if trailing slash, remove if
					if ( $url[strlen($url)-1] == '/' )
						$url = substr($url, 0, strlen($url)-1);
					$last_slash = strrpos($url, '/');
					
					if ($last_slash === FALSE) {
						$page_uid = $current_user->ID;
					} else {
						$u = substr($url, $last_slash+1, strlen($url)-$last_slash);
						$sql = "SELECT ID FROM ".$wpdb->base_prefix."users WHERE replace(display_name, ' ', '') = %s";
						$id = $wpdb->get_row($wpdb->prepare($sql, str_replace(' ', '', $u)));
						if ($id) {
							$page_uid = $id->ID;
						} else {
							$page_uid = $current_user->ID;
						}
					}
				} else {
					// default then to current user
					$page_uid = $current_user->ID;
				}
			}
		}
		if ($page_uid == 0) {
			if (isset($_POST['from']) && $_POST['from'] == 'small_search') {
				$search = $_POST['member_small'];
				$get_uid = $wpdb->get_var("SELECT u.ID FROM ".$wpdb->base_prefix."users u WHERE (u.display_name LIKE '".$search."%') OR (u.display_name LIKE '% %".$search."%') ORDER BY u.display_name LIMIT 0,1");
				if ($get_uid) { $page_uid = $get_uid; }
			} 
		}		
		define('WPS_CURRENT_USER_PAGE', $page_uid);

		// Forum
		if (isset($_GET['show'])) {
			$show_tid = $_GET['show']*1;
		} else {
			$show_tid = 0;
			if (isset($_POST['tid'])) { $show_tid = $_POST['tid']*1; }
		}
		$cat_id = '';
		if (isset($_GET['cid'])) { $cat_id = $_GET['cid']; }
		if (isset($_POST['cid'])) { $cat_id = $_POST['cid']; }

		// Group page
		if (isset($_GET['gid'])) {
			$page_gid = $_GET['gid']*1;
		} else {
			$page_gid = 0;
			if (isset($_POST['gid'])) { 
				$page_gid = $_POST['gid']*1; 
			}
		}
		// If visiting a group page, check to see if forum is default view
		if (is_user_logged_in() && $page_gid > 0) {
			$forum = $wpdb->get_row($wpdb->prepare("SELECT group_forum, default_page FROM ".$wpdb->prefix."symposium_groups WHERE gid = %d", $page_gid));
			if ($forum->default_page == 'forum' && $forum->group_forum == 'on') {
				$cat_id = 0;
			}
		}
								
		// Gallery
		$album_id = 0;
		if (isset($_GET['album_id'])) { $album_id = $_GET['album_id']; }
		if (isset($_POST['album_id'])) { $album_id = $_POST['album_id']; }
		
		// Get styles for JS
		if (get_option(WPS_OPTIONS_PREFIX.'_use_styles') == "on") {
			$bg_color_2 = get_option(WPS_OPTIONS_PREFIX.'_bg_color_2');
			$row_border_size = get_option(WPS_OPTIONS_PREFIX.'_row_border_size');
			$row_border_style = get_option(WPS_OPTIONS_PREFIX.'_row_border_style');
			$text_color_2 = get_option(WPS_OPTIONS_PREFIX.'_text_color_2');
		} else {
			$bg_color_2 = '';
			$row_border_size = '';
			$row_border_style = '';
			$text_color_2 = '';
		}
	
		// GET post?
		if (isset($_GET['post'])) {
			$GETpost = $_GET['post'];
		} else {
			$GETpost = '';
		}
	
		// Display Name
		if (isset($current_user->display_name)) {
			$display_name = stripslashes($current_user->display_name);
		} else {
			$display_name = '';
		}

		// Embedded content from external plugin?
		if (isset($_GET['embed'])) {
			$embed = 'on';
		} else {
			$embed = '';
		}
	
		// to parameter
		if (isset($_GET['to'])) {
			$to = $_GET['to'];
		} else {
			$to = '';
		}
		
		// mail ID
		if (isset($_GET['mid'])) {
			$mid = $_GET['mid'];
		} else {
			$mid = '';
		}
		
		// chat sound
		$chat_sound = __wps__get_meta($current_user->ID, 'chat_sound');
		if (!$chat_sound) $chat_sound = 'Pop.mp3';
		
		// Get forum upload valid extensions
		$permitted_ext = get_option(WPS_OPTIONS_PREFIX.'_image_ext').','.get_option(WPS_OPTIONS_PREFIX.'_video_ext').','.get_option(WPS_OPTIONS_PREFIX.'_doc_ext');

		global $blog_id;
		if ($blog_id > 1) {
			$wps_content = get_option(WPS_OPTIONS_PREFIX.'_img_url')."/".$blog_id;
		} else {
			$wps_content = get_option(WPS_OPTIONS_PREFIX.'_img_url');
		}
				
		// Load JS
	 	wp_enqueue_script('__wps__', $__wps__plugin_url.'/js/'.get_option(WPS_OPTIONS_PREFIX.'_wps_js_file'), array('jquery'));
	
	 	// Load JScharts?
	 	if (get_option(WPS_OPTIONS_PREFIX.'_jscharts')) {
	 	    if (get_option(WPS_OPTIONS_PREFIX.'_wps_js_file') == 'wps.js') {
			 	wp_enqueue_script('wps_jscharts', $__wps__plugin_url.'/js/jscharts.js', array('jquery'));
	 	    } else {
			 	wp_enqueue_script('wps_jscharts', $__wps__plugin_url.'/js/jscharts.min.js', array('jquery'));
	 	    }
	 	}
	 	
	 	// Use WP editor? (not for use yet!!!!)
	 	update_option(WPS_OPTIONS_PREFIX.'_use_wp_editor', false);
	 	
		// Set JS variables
		wp_localize_script( '__wps__', '__wps__', array(
			// variables
			'permalink' => get_permalink(),
			'plugins' => WP_PLUGIN_URL, 
			'plugin_url' => WPS_PLUGIN_URL.'/', 
			'wps_content_dir' => WP_CONTENT_DIR.'/wps-content',
			'plugin_path' => $__wps__plugin_path,
			'images_url' => get_option(WPS_OPTIONS_PREFIX.'_images'),
			'inactive' => get_option(WPS_OPTIONS_PREFIX.'_online'),
			'forum_url' => __wps__get_url('forum'),
			'mail_url' => __wps__get_url('mail'),
			'profile_url' => __wps__get_url('profile'),
			'groups_url' => __wps__get_url('groups'),
			'group_url' => __wps__get_url('group'),
			'gallery_url' => __wps__get_url('gallery'),
			'page_gid' => $page_gid,
			'offline' => get_option(WPS_OPTIONS_PREFIX.'_offline'),
			'use_chat' => get_option(WPS_OPTIONS_PREFIX.'_use_chat'),
			'chat_polling' => get_option(WPS_OPTIONS_PREFIX.'_chat_polling'),
			'bar_polling' => get_option(WPS_OPTIONS_PREFIX.'_bar_polling'),
			'view' => $view,
			'profile_default' => get_option(WPS_OPTIONS_PREFIX.'_wps_profile_default'),
			'show_tid' => $show_tid,
			'cat_id' => $cat_id,
			'album_id' => $album_id,
			'current_user_id' => $current_user->ID,
			'current_user_display_name' => $display_name,
			'current_user_level' => __wps__get_current_userlevel($current_user->ID),
			'current_user_page' => $page_uid,
			'current_group' => $page_gid,
			'post' => $GETpost,
			'please_wait' => __('Please Wait...', WPS_TEXT_DOMAIN),
			'saving' => __('Saving...', WPS_TEXT_DOMAIN),
			'site_title' => get_bloginfo('name'),
			'site_url' => get_bloginfo('url'),
			'bg_color_2' => $bg_color_2,
			'row_border_size' => $row_border_size,
			'row_border_style' => $row_border_style,
			'text_color_2' => $text_color_2,
			'template_mail_tray' => get_option(WPS_OPTIONS_PREFIX.'_template_mail_tray'),
			'embed' => $embed,
			'to' => $to,
			'is_admin' => 0,
			'mail_id' => $mid,
			'permitted_ext' => $permitted_ext,
			'forum_ajax' => get_option(WPS_OPTIONS_PREFIX.'_forum_ajax'),
			'wps_lite' => get_option(WPS_OPTIONS_PREFIX.'_wps_lite'),
			'wps_use_poke' => get_option(WPS_OPTIONS_PREFIX.'_use_poke'),
			'wps_forum_stars' => get_option(WPS_OPTIONS_PREFIX.'_forum_stars'),
			'wps_forum_refresh' => get_option(WPS_OPTIONS_PREFIX.'_forum_refresh'),
			'wps_wysiwyg' => get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg'),
			'wps_wysiwyg_1' => get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_1'),
			'wps_wysiwyg_2' => get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_2'),
			'wps_wysiwyg_3' => get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_3'),
			'wps_wysiwyg_4' => get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_4'),
			'wps_wysiwyg_css' => get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_css'),
			'wps_wysiwyg_skin' => get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_skin'),
			'wps_wysiwyg_width' => get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_width'),
			'wps_wysiwyg_height' => get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_height'),
			'wps_plus' => (defined('WPS_PLUS')) ? WPS_PLUS : '',
			'wps_alerts_activated' => (get_option(WPS_OPTIONS_PREFIX.'__wps__news_main_activated') || get_option(WPS_OPTIONS_PREFIX.'__wps__news_main_network_activated')),
			'wps_admin_page' => 'na',
			'dir_page_length' => get_option(WPS_OPTIONS_PREFIX.'_dir_page_length'),
			'dir_full_ver' => get_option(WPS_OPTIONS_PREFIX.'_dir_full_ver') ? true : false,
			'use_elastic' => get_option(WPS_OPTIONS_PREFIX.'_elastic'),
			'events_user_places' => get_option(WPS_OPTIONS_PREFIX.'_events_user_places'),
			'events_use_wysiwyg' => get_option(WPS_OPTIONS_PREFIX.'_events_use_wysiwyg'),
			'debug' => WPS_DEBUG,
			'include_context' => get_option(WPS_OPTIONS_PREFIX.'_include_context'),
			'use_wp_editor' => get_option(WPS_OPTIONS_PREFIX.'_use_wp_editor'),
			'profile_menu_scrolls' => get_option(WPS_OPTIONS_PREFIX.'_profile_menu_scrolls'),
			'profile_menu_delta' => get_option(WPS_OPTIONS_PREFIX.'_profile_menu_delta'),
			'profile_menu_adjust' => get_option(WPS_OPTIONS_PREFIX.'_profile_menu_adjust'),
			'panel_enabled' => $use_panel,
			'chat_sound' => $chat_sound,
			'wps_content' => $wps_content,
			// translations
			'clear' 			=> __( 'Clear', WPS_TEXT_DOMAIN ),
			'update' 			=> __( 'Update', WPS_TEXT_DOMAIN ),
			'cancel' 			=> __( 'Cancel', WPS_TEXT_DOMAIN ),
			'pleasewait' 		=> __( 'Please wait', WPS_TEXT_DOMAIN ),
			'saving' 			=> __( 'Saving', WPS_TEXT_DOMAIN ),
			'more' 				=> __( 'more...', WPS_TEXT_DOMAIN ),
			'next' 				=> __( 'Next', WPS_TEXT_DOMAIN ),
			'areyousure' 		=> __( 'Are you sure?', WPS_TEXT_DOMAIN ),
			'browseforfile' 	=> __( 'Browse for file', WPS_TEXT_DOMAIN ),
			'attachimage' 		=> __( 'Attach an image', WPS_TEXT_DOMAIN ),
			'attachfile' 		=> __( 'Attach file', WPS_TEXT_DOMAIN ),
			'whatsup' 			=> stripslashes(get_option(WPS_OPTIONS_PREFIX.'_status_label')),
			'whatsup_done' 		=> __( 'Post added to your activity.', WPS_TEXT_DOMAIN ),
			'sendmail' 			=> __( 'Send a private mail...', WPS_TEXT_DOMAIN ),
			'privatemail' 		=> __( 'Private Mail', WPS_TEXT_DOMAIN ),
			'privatemailsent' 	=> __( 'Private mail sent!', WPS_TEXT_DOMAIN ),
			'addasafriend' 		=> sprintf(__("Add as a %s...", WPS_TEXT_DOMAIN), get_option(WPS_OPTIONS_PREFIX.'_alt_friend')),
			'friendpending' 	=> sprintf(__("%s request sent", WPS_TEXT_DOMAIN), get_option(WPS_OPTIONS_PREFIX.'_alt_friend')),
			'attention' 		=> get_option(WPS_OPTIONS_PREFIX.'_poke_label'),
			'follow' 			=> __( 'Follow', WPS_TEXT_DOMAIN ),
			'unfollow' 			=> __( 'Unfollow', WPS_TEXT_DOMAIN ),
			'sent' 				=> __( 'Message sent!', WPS_TEXT_DOMAIN ),
			'likes' 			=> __( 'Likes', WPS_TEXT_DOMAIN ),
			'dislikes'		 	=> __( 'Dislikes', WPS_TEXT_DOMAIN ),
			'forumsearch' 		=> __( 'Search on forum', WPS_TEXT_DOMAIN ),
			'gallerysearch' 	=> __( 'Search Gallery', WPS_TEXT_DOMAIN ),
			'profile_info' 		=> __( 'Member Profile', WPS_TEXT_DOMAIN ),
			'plus_mail' 		=> __( 'Mailbox', WPS_TEXT_DOMAIN ),
			'plus_follow_who' 	=> __( 'Who am I following?', WPS_TEXT_DOMAIN ),
			'plus_friends' 		=> get_option(WPS_OPTIONS_PREFIX.'_alt_friends'),
			'request_sent' 		=> sprintf(__("Your %s request has been sent.", WPS_TEXT_DOMAIN), get_option(WPS_OPTIONS_PREFIX.'_alt_friend')),
			'add_a_comment' 	=> __( 'Add a comment:', WPS_TEXT_DOMAIN ),
			'add' 				=> __( 'Add', WPS_TEXT_DOMAIN ),
			'show_original' 	=> __( 'Show original', WPS_TEXT_DOMAIN ),
			'write_a_comment' 	=> __( 'Write a comment...', WPS_TEXT_DOMAIN ),
			'follow_box' 		=> __( 'Hi', WPS_TEXT_DOMAIN ),
			'events_enable_places' => __( 'Enable booking places:', WPS_TEXT_DOMAIN ),
			'events_max_places' => __( 'Maximum places:', WPS_TEXT_DOMAIN ),
			'events_show_max'	 => __( 'Maximum places:', WPS_TEXT_DOMAIN ),
			'events_confirmation' => __( 'Bookings require confirmation:', WPS_TEXT_DOMAIN ),
			'events_tickets_per_booking' => __( 'Max tickets per booking:', WPS_TEXT_DOMAIN ),
			'events_tab_1' 		=> __( 'Summary', WPS_TEXT_DOMAIN ),
			'events_tab_2' 		=> __( 'More Information', WPS_TEXT_DOMAIN ),
			'events_tab_3' 		=> __( 'Confirmation Email', WPS_TEXT_DOMAIN ),
			'events_tab_4' 		=> __( 'Attendees', WPS_TEXT_DOMAIN ),
			'events_send_email' => __( 'Send confirmation email:', WPS_TEXT_DOMAIN ),
			'events_replacements' => __( 'You can use the following:', WPS_TEXT_DOMAIN ),
			'events_pay_link' 	=> __( 'HTML for payment:', WPS_TEXT_DOMAIN ),
			'events_cost' 		=> __( 'Price per booking:', WPS_TEXT_DOMAIN ),
			'events_howmany' 	=> __( 'How many tickets do you want?', WPS_TEXT_DOMAIN ),
			'events_labels' 	=> __( 'Ref|User|Booked|Confirmation email sent|# Tickets|Payment Confirmed|Actions|Confirm attendee|Send Mail|Re-send confirmation email|Remove attendee|Confirm payment', WPS_TEXT_DOMAIN ),
			'gallery_labels' 	=> __( 'Rename|Photo renamed.|Drag thumbnails to re-order, and then|save|Delete this photo|Set as album cover', WPS_TEXT_DOMAIN ),
			'sending' 			=> __( 'Sending', WPS_TEXT_DOMAIN ),
			'go' 				=> __( 'Go', WPS_TEXT_DOMAIN ),
			'bbcode_url'	 	=> __( 'Enter a website URL...', WPS_TEXT_DOMAIN ),
			'bbcode_problem' 	=> __( 'Please make sure all BB Codes have open and close tags!', WPS_TEXT_DOMAIN ),
			'bbcode_label' 		=> __( 'Enter text to show...', WPS_TEXT_DOMAIN )			
		));

	}
	
	if (is_admin()) {
		
		// Load admin JS
	 	wp_enqueue_script('__wps__', $__wps__plugin_url.'/js/wps-admin.js', array('jquery'));
	 	
		// Set JS variables
		wp_localize_script( '__wps__', '__wps__', array(
			'plugins' => WP_PLUGIN_URL, 
			'plugin_url' => WPS_PLUGIN_URL.'/', 
			'plugin_path' => $__wps__plugin_path,
			'images_url' => get_option(WPS_OPTIONS_PREFIX.'_images'),
			'inactive' => get_option(WPS_OPTIONS_PREFIX.'_online'),
			'forum_url' => get_option(WPS_OPTIONS_PREFIX.'_forum_url'),
			'mail_url' => get_option(WPS_OPTIONS_PREFIX.'_mail_url'),
			'profile_url' => get_option(WPS_OPTIONS_PREFIX.'_profile_url'),
			'groups_url' => get_option(WPS_OPTIONS_PREFIX.'_groups_url'),
			'group_url' => get_option(WPS_OPTIONS_PREFIX.'_group_url'),
			'gallery_url' => get_option(WPS_OPTIONS_PREFIX.'_gallery_url'),
			'offline' => get_option(WPS_OPTIONS_PREFIX.'_offline'),
			'use_chat' => get_option(WPS_OPTIONS_PREFIX.'_use_chat'),
			'chat_polling' => get_option(WPS_OPTIONS_PREFIX.'_chat_polling'),
			'bar_polling' => get_option(WPS_OPTIONS_PREFIX.'_bar_polling'),
			'current_user_id' => $current_user->ID,
			'is_admin' => 1,
			'wps_admin_page' => 'symposium_debug'
			
		));
	}
	
}

/* ====================================================== PAGE LOADED FUNCTIONS ====================================================== */

function __wps__replace() {
	if (__wps__required()) {	
		ob_start();
		ob_start('__wps__unread');
	}
}

/* ====================================================== ADMIN FUNCTIONS ====================================================== */

// Add Stylesheet
function __wps__add_stylesheet() {

	global $wpdb;

	if (!is_admin()) {

	    // Load CSS
	    $myStyleUrl = WPS_PLUGIN_URL . '/css/'.get_option(WPS_OPTIONS_PREFIX.'_wps_css_file');
	    $myStyleFile = WPS_PLUGIN_DIR . '/css/'.get_option(WPS_OPTIONS_PREFIX.'_wps_css_file');
	    if ( file_exists($myStyleFile) ) {
	        wp_register_style('__wps__StyleSheet', $myStyleUrl);
	        wp_enqueue_style('__wps__StyleSheet');
	    }

			
	}

}

// Language files
function __wps__languages() {
	
	if ( file_exists(dirname(__FILE__).'/languages/') ) {
        load_plugin_textdomain(WPS_TEXT_DOMAIN, false, dirname(plugin_basename(__FILE__)).'/languages/');
    } else {
        if ( file_exists(dirname(__FILE__).'/lang/') ) {
            load_plugin_textdomain(WPS_TEXT_DOMAIN, false, dirname(plugin_basename(__FILE__)).'/lang/');
        } else {
			load_plugin_textdomain(WPS_TEXT_DOMAIN);
        }
    }

}


?>
