<?php

include_once('../../../../wp-config.php');
	
$action = "";
if (isset($_POST['action'])) { $action .= $_POST['action']; }
if (isset($_GET['action'])) { $action .= $_GET['action']; }


// Get mail message
if ($action == "deactivate_debug") {
	global $wpdb;
	if (is_user_logged_in() && __wps__get_current_userlevel()==5) {
		update_option(WPS_OPTIONS_PREFIX.'_debug_mode', '');
	} else {
		echo __('Only site administrators can de-activate debug mode.', WPS_TEXT_DOMAIN);
	}
	exit;
}


// Get mail message
if ($action == "get_mail_message") {
	global $wpdb;
	if (is_user_logged_in() && __wps__get_current_userlevel()) {
		$sql = "SELECT m.*, u1.display_name as u1_display_name, u2.display_name as u2_display_name FROM ".$wpdb->base_prefix."symposium_mail m 
		LEFT JOIN ".$wpdb->base_prefix."users u1 on m.mail_from = u1.ID
		LEFT JOIN ".$wpdb->base_prefix."users u2 on m.mail_to = u2.ID
		WHERE mail_mid = %d LIMIT 0,1";
		$message = $wpdb->get_row($wpdb->prepare($sql, $_POST['mail_mid']));
		$r = __('From', WPS_TEXT_DOMAIN).': '.$message->u1_display_name.'<br />';
		$r .= __('To', WPS_TEXT_DOMAIN).': '.$message->u2_display_name;
		$r .= '<p style="font-style:italic">'.__('Sent', WPS_TEXT_DOMAIN).': '.$message->mail_sent.'</p>';
		$r .= '<p style="font-weight:bold">'.__('Subject', WPS_TEXT_DOMAIN).': '.stripslashes($message->mail_subject).'</p>';
		$r .= '<p>'.stripslashes($message->mail_message).'</p>';
		echo $r;
	} else {
		echo 'ACCESS DENIED';
	}
	exit;
}

// Warning report
if ($action == "sendReport") {
	
	global $wpdb, $current_user;

	$r = 'OK';

	$code = $_POST['code'];
	$report_text = $_POST['report_text'];
	$url = $_POST['url'];

	__wps__sendmail(get_bloginfo('admin_email'), __('Warning Report', WPS_TEXT_DOMAIN), __('From', WPS_TEXT_DOMAIN).': '.$current_user->display_name.'<br /><br />'.$report_text.'<br /><br />URL: '.$url.'<br /><br />Ref: '.$code);							

	exit;	
}

// Add new page
if ($action == "add_new_page") {
	
	global $wpdb, $current_user;

	if (current_user_can('edit_pages')) {

		$r = 'OK';
	
		$shortcode = strip_tags($_POST['shortcode']);
		$name = $_POST['name'];
		$post_name = str_replace(' ', '', strtolower($name));
		
		$wpdb->query( $wpdb->prepare( "
			INSERT INTO ".$wpdb->prefix."posts
			( 	post_author, 
				post_date,
				post_date_gmt,
				post_content,
				post_title,
				post_status,
				comment_status,
				ping_status,
				post_name,
				post_modified,
				post_modified_gmt,
				post_parent,
				menu_order,
				post_type,
				comment_count
			)
			VALUES ( %d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %d, %d, %s, %d )", 
		    array(
		    	$current_user->ID, 
		    	date("Y-m-d H:i:s"), 
		    	gmdate("Y-m-d H:i:s"),
		    	'['.$shortcode.']',
		    	$name,
		    	'publish',
		    	'closed',
		    	'open',
		    	$post_name,
		    	date("Y-m-d H:i:s"), 
		    	gmdate("Y-m-d H:i:s"),
		    	0,
		    	0,
		    	'page',
		    	0
		    	) 
		    ) );
		    
		    // get new ID
			$new_id = $wpdb->insert_id;
			
			// page meta data
			$wpdb->query( $wpdb->prepare( "
				INSERT INTO ".$wpdb->base_prefix."postmeta
				( 	post_id, 
					meta_key,
					meta_value
				)
				VALUES ( %d, %s, %s )", 
			    array(
			    	$new_id, 
			    	'_wp_page_template',
			    	'sidebar-page.php'
			    	) 
			    ) );
		    		
		    // update guid
		    $url = get_bloginfo('url');
	    	if ($url[strlen($url)-1] == '/') { $url = substr($url,0,-1); }
	    	$url .= '/?p='.$new_id;
			$sql = "UPDATE ".$wpdb->prefix."posts SET guid = '%s' WHERE ID = %d";
			$wpdb->query($wpdb->prepare($sql, $url, $new_id)); 

	} else {
		$r = 'Insufficient rights to edit pages';	
		
	}
	
		    	    
	echo $r;
	exit;	
}

// Add to existing page
if ($action == "add_to_page") {
	
	global $wpdb, $current_user;

	if (current_user_can('edit_pages')) {

		$r = 'OK';
	
		$shortcode = strip_tags($_POST['shortcode']);
		$id = $_POST['id'];
	
		// get existing value
		$sql = "SELECT post_content FROM ".$wpdb->prefix."posts WHERE ID = %d";
		$tmp = $wpdb->get_var($wpdb->prepare($sql, $id));	
		$tmp .= '['.$shortcode.']';
	
		// update
		$sql = "UPDATE ".$wpdb->prefix."posts SET post_content = %s WHERE ID = %d";
		$wpdb->query($wpdb->prepare($sql, $tmp, $id)); 	    	    
		
	} else {
	
		$r = 'Insufficient rights to edit pages';	
	}
	
	echo $r;
	exit;	
}

// Import templates file
if ($action == "import_template_file") {

	$import_file = $_POST['import_file'];
		
	if (current_user_can('edit_pages')) {
	
		__wps__update_import_snippet("template_profile_header", $import_file);
		__wps__update_import_snippet("template_profile_body", $import_file);
		__wps__update_import_snippet("template_page_footer", $import_file);
		__wps__update_import_snippet("template_email", $import_file);
		__wps__update_import_snippet("template_mail_tray", $import_file);
		__wps__update_import_snippet("template_mail_message", $import_file);
		__wps__update_import_snippet("template_forum_header", $import_file);
		__wps__update_import_snippet("template_group", $import_file);
		__wps__update_import_snippet("template_forum_category", $import_file);
		__wps__update_import_snippet("template_forum_topic", $import_file);
		__wps__update_import_snippet("template_group_forum_category", $import_file);
		__wps__update_import_snippet("template_group_forum_topic", $import_file);
		
	}

	echo 'OK';
	exit;
}
function __wps__update_import_snippet($tag, $import_file) {
	global $wpdb;
	$start = strpos($import_file, "<!-- ".$tag." -->") + strlen("<!-- ".$tag." -->")+1;
	$end = strpos($import_file, "<!-- end_".$tag." -->");
	$snippet = substr($import_file, $start, $end-$start-1);
	
	$snippet = strip_tags($snippet, '<div><p><a><b><strong><em><i><u><br><style><hr>');

	update_option(WPS_OPTIONS_PREFIX.'_'.$tag, $snippet);
}

if ($action == "symposium_logout") {

  	wp_logout();
	exit;
}

if ($action == "symposium_test_ajax") {

	$value = $_POST['postID'];	
	echo $value*100;
	exit;
}

?>
