<?php

// ******************************** MAIL CLASS ********************************

class wps_mail {

	public function __construct($id='') {
		global $current_user;
		$id != '' ? $id = $id : $id = $current_user->ID;
		$this->id = $id;											// Set the ID of this member's mail
	}

	/* Following methods provide get/set functionality ______________________________________ */
		
	function get_inbox_count($unread_only=false, $sent_items=false, $term='') {
		global $wpdb;
		$unread = $unread_only ? " AND mail_read = ''" : "";
		$sql = "SELECT COUNT(mail_mid) AS cnt FROM ".$wpdb->base_prefix."symposium_mail m
    		INNER JOIN ".$wpdb->base_prefix."users u ON m.mail_from = u.ID
			WHERE m.mail_to = ".$this->id."
			AND m.mail_in_deleted != 'on' 
			".$unread."
			AND (m.mail_subject LIKE '%".$term."%'
			OR m.mail_message LIKE '%".$term."%'
			OR u.display_name LIKE '%".$term."%')";

		return $wpdb->get_var($sql);
	}
	
	function get_inbox($count=10, $start=0, $avatar_size=40, $term="", $order=true, $message_len=75, $unread_only=false, $sent_items=false) {
	    global $wpdb;
	    $mail_count = 1;
    	$return_arr = array();
    	$results_order = $order ? "DESC" : "";
    	$unread = $unread_only ? " AND m.mail_read = ''" : "";

		if (!$sent_items) {
		    $sql = "SELECT m.mail_mid, m.mail_from, m.mail_to, m.mail_read, m.mail_sent, m.mail_subject, m.mail_message, u.display_name
	    		FROM ".$wpdb->base_prefix."symposium_mail m
	    		INNER JOIN ".$wpdb->base_prefix."users u ON m.mail_from = u.ID
	    		WHERE m.mail_in_deleted != 'on'
	    		  AND m.mail_to = %d
	    		ORDER BY m.mail_mid ".$results_order."
	    		LIMIT ".$start.",1000"; // Maximum 1,000 to reduce load on database
		} else {
		    $sql = "SELECT m.mail_mid, m.mail_from, m.mail_to, m.mail_read, m.mail_sent, m.mail_subject, m.mail_message, u.display_name
	    		FROM ".$wpdb->base_prefix."symposium_mail m
	    		INNER JOIN ".$wpdb->base_prefix."users u ON m.mail_to = u.ID
	    		WHERE m.mail_sent_deleted != 'on'
	    		  AND m.mail_from = %d
	    		ORDER BY m.mail_mid ".$results_order."
	    		LIMIT ".$start.",1000"; // Maximum 1,000 to reduce load on database
		}
	 	$messages = $wpdb->get_results($wpdb->prepare($sql, $this->id));
	 	
	 	foreach ($messages AS $item) {
	 	    
	 	    $continue = false;
	 	    if ($term != '') {
	 	        if (strpos(strtolower($item->mail_subject), strtolower($term)) !== FALSE) $continue = true;
	 	        if (strpos(strtolower($item->mail_message), strtolower($term)) !== FALSE) $continue = true;
	 	        if (strpos(strtolower($item->display_name), strtolower($term)) !== FALSE) $continue = true;
	 	    } else {
	 	        $continue = true;
	 	    }

	 	    if ($continue) {
	 	    
				$row_array['mail_id'] = $item->mail_mid;
				$row_array['mail_from'] = $item->mail_from;
				$row_array['mail_to'] = $item->mail_to;
				$row_array['mail_read'] = $item->mail_read;
				$row_array['mail_sent'] = $item->mail_sent;
				$row_array['mail_subject'] = __wps__bbcode_remove(stripslashes($item->mail_subject));
				$row_array['mail_subject'] = preg_replace(
				  "/(>|^)([^<]+)(?=<|$)/iesx",
				  "'\\1' . str_replace('" . $term . "', '<span class=\"__wps__search_highlight\">" . $term . "</span>', '\\2')",
				  $row_array['mail_subject']
				);
				$row_array['mail_subject'] = stripslashes($row_array['mail_subject']);
				$message = strip_tags(stripslashes($item->mail_message));
				if ( strlen($message) > $message_len ) { $message = substr($message, 0, $message_len)."..."; }
				$message = preg_replace(
				  "/(>|^)([^<]+)(?=<|$)/iesx",
				  "'\\1' . str_replace('" . $term . "', '<span class=\"__wps__search_highlight\">" . $term . "</span>', '\\2')",
				  $message
				);
				$row_array['mail_message'] = $message;
				$row_array['display_name'] = $item->display_name;
				if (!$sent_items) {
					$row_array['display_name_link'] = stripslashes(__wps__profile_link($item->mail_from));
					$row_array['avatar'] = get_avatar($item->mail_from, $avatar_size);
				} else {
					$row_array['display_name_link'] = stripslashes(__wps__profile_link($item->mail_to));
					$row_array['avatar'] = get_avatar($item->mail_to, $avatar_size);
				}
				array_push($return_arr,$row_array);
				if ($mail_count++ == $count) break;
				
	 	    }
	 	}
	 	return $return_arr;
	}
	
	function get_message($mail_id) {
		if (is_numeric($mail_id)) {
			global $wpdb;
			$sql= "SELECT m.*, u.display_name FROM ".$wpdb->base_prefix."symposium_mail m
	    		INNER JOIN ".$wpdb->base_prefix."users u ON m.mail_from = u.ID
	    		WHERE m.mail_mid = %d";
		 	$message = $wpdb->get_row($wpdb->prepare($sql, $mail_id));		
		 	return $message;
		} else {
			return false;
		}
	}
	
	function set_as_read($mail_mid) {
		if (is_numeric($mail_mid)) {
			global $wpdb;
			$sql = "UPDATE ".$wpdb->base_prefix."symposium_mail SET mail_read = 'on' WHERE mail_mid = %d";
			$wpdb->query($wpdb->prepare($sql, $mail_mid));
			return true;
		} else {
			return false;
		}
	}
	
	function set_as_deleted($mail_mid) {
		if (is_numeric($mail_mid)) {
			global $wpdb, $current_user;
			if ( is_user_logged_in() ) {
				$sql = "SELECT mail_to, mail_from FROM ".$wpdb->base_prefix."symposium_mail WHERE mail_mid = %d";
				$tofrom = $wpdb->get_row($wpdb->prepare($sql, $mail_mid));
				if ($this->id == $tofrom->mail_to) {
					$sql = "UPDATE ".$wpdb->base_prefix."symposium_mail SET mail_in_deleted = 'on' WHERE mail_mid = %d";
					$wpdb->query($wpdb->prepare($sql, $mail_mid));
					return true;
				}
				if ($this->id == $tofrom->mail_from) {
					$sql = "UPDATE ".$wpdb->base_prefix."symposium_mail SET mail_sent_deleted = 'on' WHERE mail_mid = %d";
					$wpdb->query($wpdb->prepare($sql, $mail_mid));
				}
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	/* Following methods provide functionality _____________________________________________________________________ */
	
	function sendmail($compose_recipient_id) {

		global $wpdb, $current_user;
		
		if (is_user_logged_in()) {
	
			$recipient = $wpdb->get_row("SELECT * FROM ".$wpdb->base_prefix."users WHERE ID = '".$compose_recipient_id."'");
			if (!$recipient) {
				$return = false;
			} else {

				// subject and message from wps_ui elements
				$subject = $_POST['wps-mail-subject'];
				$message = $_POST['wps-mail-message'];
				
				// Do some magic to the message
				$message = str_replace(chr(13), "<br />", $message);

				// Send mail
				if ( $rows_affected = $wpdb->prepare( $wpdb->insert( $wpdb->base_prefix . "symposium_mail", array( 
				'mail_from' => $current_user->ID, 
				'mail_to' => $recipient->ID, 
				'mail_sent' => date("Y-m-d H:i:s"), 
				'mail_subject' => $subject,
				'mail_message' => $message
				 ) ) ) ) {
					$return = true;
				 } else {
					$return = false;
				 }
	
				$mail_id = $wpdb->insert_id;
				// Filter to allow further actions to take place
				apply_filters ('__wps__sendmessage_filter', $recipient->ID, $current_user->ID, $current_user->display_name, $mail_id);
			
				// Send real email if chosen
				if ( __wps__get_meta($recipient->ID, 'notify_new_messages') ) {
	
					$url = __wps__get_url('mail');
	
					$body = "<h1>".$subject."</h1>";
					$body .= "<p><a href='".$url.__wps__string_query($url)."mid=".$mail_id."'>".sprintf(__("Go to %s Mail", WPS_TEXT_DOMAIN), __wps__get_url('mail'))."...</a></p>";
					$body .= "<p>";
					$body .= $message;
					$body .= "</p>";
					$body .= "<p><em>";
					$body .= $current_user->display_name;
					$body .= "</em></p>";
					$body .= $previous;
				
					$body = str_replace(chr(13), "<br />", $body);
					$body = str_replace("\\r\\n", "<br />", $body);
					$body = str_replace("\\", "", $body);
	
					$mail_subject = get_option(WPS_OPTIONS_PREFIX.'_subject_mail_new');
					if (strpos($mail_subject, '[subject]') !== FALSE) {
						$mail_subject = str_replace("[subject]", $subject, $mail_subject);
					}
					if ( __wps__sendmail($recipient->user_email, $mail_subject, $body) ) {
						$return = true;
					} else {
						$return = false;
					}
				}
	
			}
			
		} else {
			$return = false; // not logged in
		}

		return $return;

	}
	
	/* Following methods check for various conditions and return boolean value ______________________________________ */
	
}



?>
