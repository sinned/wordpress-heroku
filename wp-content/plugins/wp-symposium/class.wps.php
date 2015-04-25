<?php

// *************************************** CLASS **************************************

class wps {

	public function __construct() {
		$this->poke_label = get_option(WPS_OPTIONS_PREFIX.'_poke_label');			// appears on "Poke" (tm) button
		$this->mail_url = get_option(WPS_OPTIONS_PREFIX.'_mail_url');				// URL of mail page, ending with a slash
		$this->profile_url = get_option(WPS_OPTIONS_PREFIX.'_profile_url');		// URL of profile page, ending with a slash
		$this->members_url = get_option(WPS_OPTIONS_PREFIX.'_members_url');		// URL of members page, ending with a slash
		$this->groups_url = get_option(WPS_OPTIONS_PREFIX.'_groups_url');			// URL of groups page, ending with a slash
		$this->group_url = get_option(WPS_OPTIONS_PREFIX.'_group_url');			// URL of group page, ending with a slash
		$this->gallery_url = get_option(WPS_OPTIONS_PREFIX.'_gallery_url');		// URL of gallery page, ending with a slash
		$this->forum_url = get_option(WPS_OPTIONS_PREFIX.'_forum_url');			// URL of forum page, ending with a slash
		$this->img_url = get_option(WPS_OPTIONS_PREFIX.'_img_url');				// URL of images stored on filesystem, e.g. /wp-content/wps-content
	}
	

	// get/set functions ______________________________________________________________________________
	
	function get_images_url() {
		return get_option(WPS_OPTIONS_PREFIX.'_images');
	}
	function set_poke_label($value) {
		update_option(WPS_OPTIONS_PREFIX.'_poke_label', $value);
    	$this->poke_label = $value;
    }
    function get_poke_label() {
		return $this->poke_label;
    }	

	function set_mail_url($value) {
		update_option(WPS_OPTIONS_PREFIX.'_mail_url', $value);
    	$this->mail_url = $value;
    }
    function get_mail_url() {
		return get_bloginfo('url').$this->mail_url;
    }	
        
	function set_profile_url($value) {
		update_option(WPS_OPTIONS_PREFIX.'_profile_url', $value);
    	$this->profile_url = $value;
    }
    function get_profile_url() {
		return get_bloginfo('url').$this->profile_url;
    }	
	
	function set_members_url($value) {
		update_option(WPS_OPTIONS_PREFIX.'_members_url', $value);
    	$this->members_url = $value;
    }
    function get_members_url() {
		return get_bloginfo('url').$this->members_url;
    }	
      
	function set_groups_url($value) {
		update_option(WPS_OPTIONS_PREFIX.'_groups_url', $value);
    	$this->groups_url = $value;
    }
    function get_groups_url() {
		return get_bloginfo('url').$this->groups_url;
    }	
      
	function set_group_url($value) {
		update_option(WPS_OPTIONS_PREFIX.'_group_url', $value);
    	$this->group_url = $value;
    }
    function get_group_url() {
		return get_bloginfo('url').$this->group_url;
    }	
      
	function set_gallery_url($value) {
		update_option(WPS_OPTIONS_PREFIX.'_gallery_url', $value);
    	$this->gallery_url = $value;
    }
    function get_gallery_url() {
		return get_bloginfo('url').$this->gallery_url;
    }	
      
	function set_forum_url($value) {
		update_option(WPS_OPTIONS_PREFIX.'_forum_url', $value);
    	$this->forum_url = $value;
    }
    function get_forum_url() {
		return get_bloginfo('url').$this->forum_url;
    }	
      
	function set_img_url($value) {
		update_option(WPS_OPTIONS_PREFIX.'_img_url', $value);
    	$this->img_url = $value;
    }
    function get_img_url() {
		return $this->img_url;
    }	
    
	function get_url_q($url) {
		return __wps__string_query($url);
	}

    function get_current_user_page() {
        return WPS_CURRENT_USER_PAGE;
    }

	/* Methods to provide functionality */
	function is_activated($feature='') {
		if ($feature == 'events') $feature = 'events_main';
		if ($feature == 'news') $feature = 'news_main';
		if (get_option(WPS_OPTIONS_PREFIX.'__wps__'.$feature.'_activated') || get_option(WPS_OPTIONS_PREFIX.'__wps__'.$feature.'_network_activated')) {
			return true;
		} else {
			return false;
		}

	}
	
	function add_activity_post($from_id=0, $to_id=0, $url='', $type='') {
		
		global $wpdb, $current_user;
		
		$from_id = $from_id==0 ? $current_user->ID : $from_id;
		$to_id = $to_id==0 ? $current_user->ID : $to_id;

		$success = ($wpdb->query( $wpdb->prepare( "
			INSERT INTO ".$wpdb->base_prefix."symposium_comments
			( 	subject_uid, 
				author_uid,
				comment_parent,
				comment_timestamp,
				comment,
				is_group,
				type
			)
			VALUES ( %d, %d, %d, %s, %s, %s, %s )", 
	        array(
	        	$to_id, 
	        	$from_id, 
	        	0,
	        	date("Y-m-d H:i:s"),
	        	$url,
	        	'',
	        	$type
	        	) 
	        ) ) );	
        		
        return $success;
	}
	         
}

// Single function to reduce duplication
function wps_update_table($table, $field, $value, $format) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare("UPDATE ".$wpdb->base_prefix.$table." SET ".$field." = ".$format, $value));
}


?>
