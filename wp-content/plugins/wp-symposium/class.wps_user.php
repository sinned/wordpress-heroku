<?php

// ******************************** CURRENT USER CLASS ********************************

class wps_user {

	public function __construct($id='') {
		global $current_user;
		$id != '' ? $id = $id : $id = $current_user->ID;
		$this->id = $id;													// Set the ID of this member
		$user_info = get_userdata($id);

		if ($user_info) {
			$this->display_name = $user_info->display_name;						// WordPress display name
			$this->first_name = $user_info->first_name;							// WordPress first name
			$this->last_name = $user_info->last_name;							// WordPress last name
			$this->user_login = $user_info->user_login;							// WordPress user login
			$this->user_email = $user_info->user_email;							// WordPress user email address
			$this->city = __wps__get_meta($id, 'extended_city');				// City
			$this->country = __wps__get_meta($id, 'extended_country');		// Country
			$this->avatar = '';													// Avatar (readonly)
			$this->latest_activity = '';										// Most recent activity post
			$this->activity_privacy = __wps__get_meta($id, 'wall_share');	// Privacy for sharing activity
			$this->dob_day = __wps__get_meta($id, 'dob_day');				// Date of Birth (day)
			$this->dob_month = __wps__get_meta($id, 'dob_month');			// Date of Birth (month)
			$this->dob_year = __wps__get_meta($id, 'dob_year');				// Date of Birth (year)		
			$this->last_activity = __wps__get_meta($id, 'last_activity');	// When last active		
		}
		
	}
	
	
	/* Following methods provide get/set functionality ______________________________________ */
	
	// Member ID
    function get_id() {
		return $this->id;
    }	
	
	// First name
	function set_first_name($value) {
    	$this->first_name = $value;
		wp_update_user( array ('ID' => $this->id, 'first_name' => $value) );
    }
    function get_first_name() {
		return $this->first_name;
    }	

	// Last name
	function set_last_name($value) {
    	$this->last_name = $value;
		wp_update_user( array ('ID' => $this->id, 'last_name' => $value) );
    }
    function get_last_name() {
		return $this->last_name;
    }	

	// Display name
	function set_display_name($value) {
    	$this->display_name = $value;
		wp_update_user( array ('ID' => $this->id, 'display_name' => $value) );
    }
    function get_display_name() {
		return isset($this->display_name) ? $this->display_name : '';
    }	

	// Member login
	function set_user_login($value) {
    	$this->user_login = $value;
		wp_update_user( array ('ID' => $this->id, 'user_login' => $value) );
    }
    function get_user_login() {
		return $this->user_login;
    }	

	// Member email address
	function set_user_email($value) {
    	$this->user_email = $value;
		wp_update_user( array ('ID' => $this->id, 'user_email' => $value) );
    }
    function get_user_email() {
		return $this->user_email;
    }	

	// Member city
	function set_city($value) {
    	$this->city = $value;
		__wps__update_meta($this->id, 'city', $value);
    }
    function get_city() {
		return $this->city;
    }

	// Member country
	function set_country($value) {
    	$this->country = $value;
		__wps__update_meta($this->id, 'country', $value);
    }
    function get_country() {
		return $this->country;
    }   
   
   	// Member avatar
    function get_avatar($size=64, $link = true) {
		return get_avatar($this->id, $size, '', false, $link);
    }        

	// Member latest activity
	function set_latest_activity($value) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "
		INSERT INTO ".$wpdb->base_prefix."symposium_comments
		( 	subject_uid, 
			author_uid,
			comment_parent,
			comment_timestamp,
			comment,
			is_group
		)
		VALUES ( %d, %d, %d, %s, %s, %s )", 
        array(
        	$this->id, 
        	$this->id, 
        	0,
        	date("Y-m-d H:i:s"),
        	$value,
        	''
        	) 
        ) );
    }
    function get_latest_activity($as_row=false) {
        global $wpdb;
        if (!$as_row) {
	        $comment = stripslashes($wpdb->get_var( $wpdb->prepare("SELECT comment FROM ".$wpdb->base_prefix."symposium_comments WHERE subject_uid = %d AND author_uid = %d AND comment_parent = 0 ORDER BY cid DESC LIMIT 0,1", $this->id, $this->id)));
        } else {
            $comment = $wpdb->get_row( $wpdb->prepare("SELECT comment FROM ".$wpdb->base_prefix."symposium_comments WHERE subject_uid = %d AND author_uid = %d AND comment_parent = 0 ORDER BY cid DESC LIMIT 0,1", $this->id, $this->id));
        }
		return $comment;
    }        
    function get_latest_activity_age() {
        global $wpdb;
		$dateof = $wpdb->get_var( $wpdb->prepare("SELECT comment_timestamp FROM ".$wpdb->base_prefix."symposium_comments WHERE subject_uid = %d AND author_uid = %d AND comment_parent = 0 ORDER BY cid DESC LIMIT 0,1", $this->id, $this->id));
		return $dateof;
    }        
	function set_activity_privacy($value) {
    	$this->activity_privacy = $value;
		__wps__update_meta($this->id, 'wall_share', $value);
    }
    function get_activity_privacy() {
		return $this->activity_privacy;
    }   

	function get_replies($post, $limit_from=0, $limit_count=100) {
		
		if ($post) {
			global $wpdb;
	
			$sql = "SELECT c.*, u.display_name, u2.display_name AS subject_name   
			FROM ".$wpdb->base_prefix."symposium_comments c 
			LEFT JOIN ".$wpdb->base_prefix."users u ON c.author_uid = u.ID 
			LEFT JOIN ".$wpdb->base_prefix."users u2 ON c.subject_uid = u2.ID 
			WHERE c.comment_parent = %d 
			ORDER BY c.comment_timestamp DESC LIMIT %d,%d";					
			$comments = $wpdb->get_results($wpdb->prepare($sql, $post, $limit_from, $limit_count));			
			
			return $comments;
			
		} else {
			return false;
		}
		
	}
	
	function get_comments($tid, $limit_from=0, $limit_count=100) {
		
		if ($tid) {
			global $wpdb;
	
			$sql = "SELECT c.*, u.display_name, u2.display_name AS subject_name   
			FROM ".$wpdb->base_prefix."symposium_comments c 
			LEFT JOIN ".$wpdb->base_prefix."users u ON c.author_uid = u.ID 
			LEFT JOIN ".$wpdb->base_prefix."users u2 ON c.subject_uid = u2.ID 
			WHERE c.comment_parent = %d 
			ORDER BY c.comment_timestamp LIMIT %d,%d";					
			$comments = $wpdb->get_results($wpdb->prepare($sql, $tid, $limit_from, $limit_count));			
			
			return $comments;			
		} else {
			return false;
		}
	}
	
	function get_activity_post($tid) {
		if ($tid) {
			global $wpdb;
	
			$sql = "SELECT c.*, u.display_name
			FROM ".$wpdb->base_prefix."symposium_comments c 
			LEFT JOIN ".$wpdb->base_prefix."users u ON c.author_uid = u.ID 
			WHERE c.cid = %d 
			ORDER BY c.comment_timestamp";					
			$comment = $wpdb->get_row($wpdb->prepare($sql, $tid));			
			
			return $comment;			
		} else {
			return false;
		}
	}
	
    function get_activity($uid1='', $version='wall', $limit_from, $limit_count) {
		
		// version = stream_activity, friends_activity, all_activity
		// uid1 = the user's page (which we are looking at)
		// uid2 = the current user
		// $limit_from (starting post)
		// $limit_count (how many to show)
		// $post (individual activity post ID if applicable)
		
		global $wpdb,$current_user;
		if ($uid1 == '') $uid1 = $current_user->ID;
		$uid2 = $current_user->ID;
		
		// Get privacy level for this member's activity
	
		$privacy = $uid1 > 0 ? __wps__get_meta($uid1, 'wall_share') : 'public';
	
		
		if (is_user_logged_in() || $privacy == 'public') {	
		
			$is_friend = ($uid1 > 0) ? __wps__friend_of($uid1, $current_user->ID) : false;	
			
			if ( ($uid1 == $uid2) || (is_user_logged_in() && strtolower($privacy) == 'everyone') || (strtolower($privacy) == 'public') || (strtolower($privacy) == 'friends only' && $is_friend) || __wps__get_current_userlevel() == 5) {
	
					$post_cid = '';
	
					// Add groups join if in use
					if (function_exists('__wps__groups')) {
						$groups = "LEFT JOIN ".$wpdb->prefix."symposium_groups g ON c.subject_uid = g.gid";
						$group_field = ", g.content_private";
					} else {
						$groups = "";
						$group_field = ", 'on' as content_private";
					}
	
					if ($version == "all_activity" || $version == "stream_activity") {
						$sql = "SELECT c.*, u.display_name, u2.display_name AS subject_name" . $group_field . "   
						FROM ".$wpdb->base_prefix."symposium_comments c 
						LEFT JOIN ".$wpdb->base_prefix."users u ON c.author_uid = u.ID 
						LEFT JOIN ".$wpdb->base_prefix."users u2 ON c.subject_uid = u2.ID 
						" . $groups . "
						WHERE ( ".$post_cid." c.comment_parent = 0 
						  ) AND c.type != 'photo' 
						ORDER BY c.comment_timestamp DESC LIMIT %d,%d";					
						$comments = $wpdb->get_results($wpdb->prepare($sql, $limit_from, $limit_count));	
					}
				
					if ($version == "friends_activity") {
						$sql = "SELECT c.*, u.display_name, u2.display_name AS subject_name" . $group_field . " 
						FROM ".$wpdb->base_prefix."symposium_comments c 
						LEFT JOIN ".$wpdb->base_prefix."users u ON c.author_uid = u.ID 
						LEFT JOIN ".$wpdb->base_prefix."users u2 ON c.subject_uid = u2.ID 
						" . $groups . "
						WHERE ( ".$post_cid." (
						      ( (c.subject_uid = %d) OR (c.author_uid = %d) OR (c.subject_uid = %d) OR (c.author_uid = %d)  
						   OR ( c.author_uid IN (SELECT friend_to FROM ".$wpdb->base_prefix."symposium_friends WHERE friend_from = %d)) ) AND c.comment_parent = 0 
					   	   OR ( 
					   	   		%d IN (SELECT author_uid FROM ".$wpdb->base_prefix."symposium_comments WHERE comment_parent = c.cid ) 
								AND ( c.author_uid IN (SELECT friend_to FROM ".$wpdb->base_prefix."symposium_friends WHERE friend_from = %d)) 
					   	   	  ) )
						  ) AND c.type != 'photo' 
						ORDER BY c.comment_timestamp DESC LIMIT %d,%d";	
						$comments = $wpdb->get_results($wpdb->prepare($sql, $uid1, $uid1, $uid2, $uid2, $uid1, $uid1, $uid1, $limit_from, $limit_count));	
					}
				
					if ($version == "wall") {
						$sql = "SELECT c.*, u.display_name, u2.display_name AS subject_name" . $group_field . " 
								FROM ".$wpdb->base_prefix."symposium_comments c 
								LEFT JOIN ".$wpdb->base_prefix."users u ON c.author_uid = u.ID 
								LEFT JOIN ".$wpdb->base_prefix."users u2 ON c.subject_uid = u2.ID 
								" . $groups . "
								WHERE (".$post_cid." (
								      ( (c.subject_uid = %d OR c.author_uid = %d) AND c.comment_parent = 0 )
							   	   OR ( %d IN (SELECT author_uid FROM ".$wpdb->base_prefix."symposium_comments WHERE comment_parent = c.cid  ) )
								  ) ) AND c.type != 'photo' 
								ORDER BY c.comment_timestamp DESC LIMIT %d,%d";
						$comments = $wpdb->get_results($wpdb->prepare($sql, $uid1, $uid1, $uid1, $limit_from, $limit_count));	
						
					}
	
					return $comments;
				
			} else {			
				return false;
			}
			
		} else {
	
			return false;		
			
		}

    }        
        
    // Last active
    function get_last_activity() {
		return $this->last_activity;
    }	    
    
    // Member Date of birth
	function set_dob_day($value) {
    	$this->dob_day = $value;
		__wps__update_meta($this->id, 'dob_day', $value);
    }
    function get_dob_day() {
		return $this->dob_day;
    }
	function set_dob_month($value) {
    	$this->dob_month = $value;
		__wps__update_meta($this->id, 'dob_month', $value);
    }
    function get_dob_month() {
		return $this->dob_month;
    }
	function set_dob_year($value) {
    	$this->dob_year = $value;
		__wps__update_meta($this->id, 'dob_year', $value);
    }
    function get_dob_year() {
		return $this->dob_year;
    }
    
    // Get single extended field
    function get_user_meta($uid, $slug) {
		return __wps__get_meta($uid, $slug);
    }
    
    // Member Extended Profile information
    function get_extended() {
        global $wpdb;
        
        $sql = "SELECT * FROM ".$wpdb->base_prefix."usermeta where user_id = %d and meta_key like 'symposium_extended%%'";
        $values = $wpdb->get_results($wpdb->prepare($sql, $this->id));
		if ($values) {
			$ext_rows = array();
			foreach ($values as $value) {
				$slug = str_replace('symposium_extended_', '', $value->meta_key);
				$sql = "SELECT * FROM ".$wpdb->base_prefix."symposium_extended WHERE extended_slug = %s";
				$extension = $wpdb->get_row($wpdb->prepare($sql, $slug));
				if ($extension) {
					$ext = array (	'name'=>$extension->extended_name,
									'value'=>$value->meta_value,
									'type'=>$extension->extended_type,
									'order'=>$extension->extended_order );
					array_push($ext_rows, $ext);
				}
			}
			$ext_rows = __wps__sub_val_sort($ext_rows,'order');
			return $ext_rows;
			
		} else {
			return false;
		}

    }
    
    // Who following
    // Returns array of member IDs, ordered by last activity
    function get_following($max=10) {
	    global $wpdb;

	    $id = $this->id;

	    $sql = "SELECT f.following AS id FROM ".$wpdb->base_prefix."symposium_following f
	    		WHERE uid = %d LIMIT 0,%d";
	 	$members_list = $wpdb->get_results($wpdb->prepare($sql, $id, $max));

	 	if ($members_list) {
			$members_array = array();
			foreach ($members_list as $member) {
	
				$add = array (	
					'id' => $member->id,
					'last_activity' => __wps__get_meta($member->id, 'last_activity')
				);
				array_push($members_array, $add);
			}
			$members = __wps__sub_val_sort($members_array, 'last_activity', false);
		} else {
			$members = false;
		}

	 	return $members;
    }

    // Groups
    // Returns array of group IDs, ordered by last activity
    function get_groups($max=10) {
	    global $wpdb;

	    $id = $this->id;

	    $sql = "SELECT f.group_id AS id, g.last_activity FROM ".$wpdb->base_prefix."symposium_group_members f
	    		LEFT JOIN ".$wpdb->base_prefix."symposium_groups g ON f.group_id = g.gid
	    		WHERE f.member_id = %d LIMIT 0,%d";
	 	$results = $wpdb->get_results($wpdb->prepare($sql, $id, $max));

	 	if ($results) {
			$list = array();
			foreach ($results as $item) {
	
				$add = array (	
					'id' => $item->id,
					'last_activity' => $item->last_activity
				);
				array_push($list, $add);
			}
			$list = __wps__sub_val_sort($list, 'last_activity', false);
		} else {
			$list = false;
		}
	 	return $list;
    }

    // Member friends
    // Returns array of friend IDs, ordered by last activity
    function get_friends($max=10) {
	    global $wpdb;

	    $id = $this->id;

	    $sql = "SELECT f.friend_to AS id, u.meta_value as last_activity FROM ".$wpdb->base_prefix."symposium_friends f
	    		LEFT JOIN ".$wpdb->base_prefix."usermeta u ON f.friend_to = u.user_id
	    		WHERE friend_from = %d AND friend_accepted = 'on' AND u.meta_key = 'symposium_last_activity'
	    		ORDER BY u.meta_value DESC LIMIT 0,%d";
	 	$friends_list = $wpdb->get_results($wpdb->prepare($sql, $id, $max));

	 	if ($friends_list) {
			$friends_array = array();
			foreach ($friends_list as $friend) {
	
				$add = array (	
					'id' => $friend->id,
					'last_activity' => __wps__get_meta($friend->id, 'last_activity')
				);
				array_push($friends_array, $add);
			}
			$friends = $friends_array;
		} else {
			$friends = false;
		}

	 	return $friends;
    }

    // Member friend requests
    // Returns array of member IDs requesting friendship, ordered by last activity
    function get_friend_requests($max=10) {
	    global $wpdb;

	    $id = $this->id;

	    $sql = "SELECT f.friend_from AS id, friend_timestamp FROM ".$wpdb->base_prefix."symposium_friends f
	    		WHERE friend_to = %d AND friend_accepted != 'on' LIMIT 0,%d";
	 	$friends_list = $wpdb->get_results($wpdb->prepare($sql, $id, $max));

	 	if ($friends_list) {
			$friends_array = array();
			foreach ($friends_list as $friend) {
	
				$add = array (	
					'id' => $friend->id,
					'last_activity' => __wps__get_meta($friend->id, 'last_activity'),
					'requested' => $friend->friend_timestamp
				);
				array_push($friends_array, $add);
			}
			$friends = __wps__sub_val_sort($friends_array, 'last_activity', false);
		} else {
			$friends = false;
		}

	 	return $friends;
    }

    // Profile page URL
	function get_profile_url($just_url=false) {
		return __wps__profile_link($this->id, $just_url);
	}
      
	/* Following methods check for various conditions and return boolean value ______________________________________ */
	
    function is_permitted($type='activity') {
		return user_has_permission($this->id, $type);    
    }

	function is_friend() {
	   	global $wpdb,$current_user;
		if ( $wpdb->get_var($wpdb->prepare("SELECT * FROM ".$wpdb->base_prefix."symposium_friends WHERE (friend_accepted = 'on') AND (friend_from = %d AND friend_to = %d)", $this->id, $current_user->ID)) ) {
			return true;
		} else {
			return false;
		}
	}

	function is_pending_friend() {
	   	global $wpdb,$current_user;		
		$sql = "SELECT * FROM ".$wpdb->base_prefix."symposium_friends WHERE (friend_accepted != 'on') AND ((friend_from = %d AND friend_to = %d) OR (friend_to = %d AND friend_from = %d))";
		if ( $wpdb->get_var($wpdb->prepare($sql, $this->id, $current_user->ID, $this->id, $current_user->ID)) ) {
			return true;
		} else {
			return false;
		}
	} 
	
	function get_alerts() {

		global $wpdb, $current_user;
		
		// Get link to profile page
		$profile_url = __wps__get_url('profile');
		if (strpos($profile_url, '?') !== FALSE) {
			$q = "&";
		} else {
			$q = "?";
		}
		
		// Start array
		$news = array();
		
		$limit = 50;
			
		// Wrapper
		$sql = "SELECT n.*, u.display_name FROM ".$wpdb->base_prefix."symposium_news n 
			LEFT JOIN ".$wpdb->base_prefix."users u ON n.author = u.ID 
			WHERE subject = %d 
			ORDER BY added DESC LIMIT 0,%d";
		$news_rows = $wpdb->get_results($wpdb->prepare($sql, $this->id, $limit));
		if ($news_rows) {
			foreach ($news_rows as $item) {
	
				$news = array (	'nid'=>$item->nid,
								'author'=>$item->author,
								'item'=>$item->news,
								'added'=>$item->added,
								'new_item'=>$item->new_item );
				array_push($news_rows, $news);
					
			}
		}
	
		return $news;
		
	}

}

/* Single functions to reduce duplication above ____________________________________________________________________________ */

function user_has_permission($id, $type) {
	global $wpdb,$current_user;
	if ($type == 'activity') $type = 'wall_share';
	if ($type == 'personal') $type = 'share';
	$privacy = __wps__get_meta($id, $type);
	if (is_user_logged_in() || $privacy == 'public') {	
		$is_friend = __wps__friend_of($id, $current_user->ID);
		if ((WPS_CURRENT_USER_PAGE == $current_user->ID) || (is_user_logged_in() && strtolower($privacy) == 'everyone') || (strtolower($privacy) == 'public') || (strtolower($privacy) == 'friends only' && $is_friend) || __wps__get_current_userlevel() == 5) {
			return true;
		}
	}
	return false;
}

?>
