<?php
// If uninstall not called from WordPress exit
if( !defined( 'WP_UNINSTALL_PLUGIN' ) )
exit ();

// Get constants
require_once(dirname(__FILE__).'/default-constants.php');

global $wpdb, $wp_rewrite;
if (is_multisite()) {
    $blogs = $wpdb->get_results("SELECT blog_id FROM ".$wpdb->base_prefix."blogs");
    if ($blogs) {
        foreach($blogs as $blog) {
            switch_to_blog($blog->blog_id);
			__wps__uninstall_delete();
			__wps__uninstall_rrmdir(WP_CONTENT_DIR.'/wps-content');
			$wp_rewrite->flush_rules();

        }
        restore_current_blog();
    }   
} else {
	__wps__uninstall_delete();
	__wps__uninstall_rrmdir(WP_CONTENT_DIR.'/wps-content');			
	$wp_rewrite->flush_rules();
}


function __wps__uninstall_rrmdir($dir) {
   if (is_dir($dir)) {
	 $objects = scandir($dir);
	 foreach ($objects as $object) {
	   if ($object != "." && $object != "..") {
		 if (filetype($dir."/".$object) == "dir") __wps__uninstall_rrmdir($dir."/".$object); else unlink($dir."/".$object);
	   }
	 }
	 reset($objects);
	 rmdir($dir);
   }
} 

function __wps__uninstall_delete() {
	
	global $wpdb;
	
	// Clear up Reply by Email
	$_SESSION['__wps__mailinglist_lock'] = 'locked';
	wp_clear_scheduled_hook('__wps__mailinglist_hook');
	$_SESSION['__wps__mailinglist_lock'] = '';
	
	// delete options		
   	$wpdb->query("DELETE FROM ".$wpdb->prefix."options WHERE option_name LIKE '".WPS_OPTIONS_PREFIX."%'");
   	
   	// delete user meta data
	$wpdb->query("DELETE FROM ".$wpdb->prefix."usermeta WHERE meta_key like '".WPS_OPTIONS_PREFIX."%'");
	
	// delete tables
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."symposium_cats");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."symposium_chat");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."symposium_chat2");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."symposium_chat2_users");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."symposium_comments");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."symposium_events");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."symposium_events_bookings");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."symposium_extended");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."symposium_following");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."symposium_friends");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."symposium_gallery");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."symposium_gallery_comments");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."symposium_gallery_items");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."symposium_groups");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."symposium_group_members");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."symposium_likes");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."symposium_lounge");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."symposium_mail");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."symposium_news");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."symposium_styles");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."symposium_subs");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."symposium_topics");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."symposium_topics_images");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."symposium_topics_scores");
   	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."symposium_usermeta");
   	
	// clear schedules
	wp_clear_scheduled_hook('symposium_notification_hook');	
	
}	

?>
