<?php

include_once('../../../../wp-config.php');

global $wpdb, $current_user;
	

// Bulk: mark all as read
if ($_POST['action'] == 'bulk_readall') {

	if (is_user_logged_in()) {
	
		$uid = $_POST['uid'];
		
		$sql = "UPDATE ".$wpdb->base_prefix."symposium_mail SET mail_read = 'on' WHERE mail_to = %d";
		$wpdb->query($wpdb->prepare($sql, $current_user->ID));
		
		echo 'OK';

	} else {
		
		echo "NOT LOGGED IN";
	
	}
		
	exit;
}

// Bulk: recover all
if ($_POST['action'] == 'bulk_recover') {

	if (is_user_logged_in()) {
	
		$tray = $_POST['tray'];
		$uid = $_POST['uid'];
		
		if ($tray == 'in') {
			$sql = "UPDATE ".$wpdb->base_prefix."symposium_mail SET mail_in_deleted = '' WHERE mail_to = %d";
		} else {
			$sql = "UPDATE ".$wpdb->base_prefix."symposium_mail SET mail_sent_deleted = '' WHERE mail_from = %d";
		}
		$wpdb->query($wpdb->prepare($sql, $current_user->ID));
		
		echo 'OK';

	} else {
		
		echo "NOT LOGGED IN";
	
	}
		
	exit;
}

// Bulk: delete/delete all
if ($_POST['action'] == 'bulk_delete') {

	if (is_user_logged_in()) {
	
		$list = isset($_POST['data']) ? $_POST['data'] : false;
		$tray = $_POST['tray'];
		$uid = $_POST['uid'];
		$scope = $_POST['scope'];
		
		$html = '';
		
		if ($scope == 'marked' && $list) {
		
			foreach($list as $item) {
				if ($tray == 'in') {
					$sql = "UPDATE ".$wpdb->base_prefix."symposium_mail SET mail_in_deleted = 'on' WHERE mail_mid = %d";
				} else {
					$sql = "UPDATE ".$wpdb->base_prefix."symposium_mail SET mail_sent_deleted = 'on' WHERE mail_mid = %d";
				}
				if (!$wpdb->query($wpdb->prepare($sql, $item)) )
					$html .= $item.' not deleted';
			}
			
		} 
		
		if ($scope == 'all') {

				if ($tray == 'in') {
					$sql = "UPDATE ".$wpdb->base_prefix."symposium_mail SET mail_in_deleted = 'on' WHERE mail_to = %d";
				} else {
					$sql = "UPDATE ".$wpdb->base_prefix."symposium_mail SET mail_sent_deleted = 'on' WHERE mail_from = %d";
				}
				if (!$wpdb->query($wpdb->prepare($sql, $current_user->ID)) )
					$html .= 'all mail not deleted';
			
		}
		
		$html = ($html == '') ? 'OK' : $html;
		echo $html;

	} else {
		
		echo "NOT LOGGED IN";
	
	}
		
	exit;
}

// Get id of a display name
if ($_POST['action'] == 'getRecipientId') {
	
	if (is_user_logged_in()) {
	
		$name = $_POST['name'];

		$sql = "SELECT u.ID FROM ".$wpdb->base_prefix."users u 
			INNER JOIN ".$wpdb->base_prefix."symposium_friends f ON u.ID = f.friend_to 
			WHERE (lower(u.display_name) = %s AND f.friend_from = %d)";

		$id = $wpdb->get_results( $wpdb->prepare($sql, strtolower($name), $current_user->ID ) );
		
		if ($id) {
			if ( count($id) > 1 ) {
				echo sprintf(__('Please select a name from the drop down list to make sure you send to the right person (%s).', WPS_TEXT_DOMAIN), $name );
			}
		} else {
			echo $id[0];
		}
	
	}	
	exit;
}

// Load Compose Forum
if ($_POST['action'] == 'loadComposeForm') {

	if (is_user_logged_in()) {

		$mail_to = $_POST["mail_to"];
		$recipient = $wpdb->get_var($wpdb->prepare("SELECT display_name FROM ".$wpdb->base_prefix."users WHERE ID = %d", $mail_to));

		echo $recipient;
	}
	exit;
}


// Get reply info
if ($_POST['action'] == 'getReply') {

	if (is_user_logged_in()) {

		$mid = $_POST["mail_id"];
		$recipient_id = $_POST["recipient_id"];

		$recipient = $wpdb->get_var($wpdb->prepare("SELECT display_name FROM ".$wpdb->base_prefix."users WHERE ID = %d", $recipient_id));
		$sql = "SELECT m.*, u.display_name FROM ".$wpdb->base_prefix."symposium_mail m LEFT JOIN ".$wpdb->base_prefix."users u ON m.mail_from = u.ID WHERE mail_mid = %d";
		$mail_message = $wpdb->get_row($wpdb->prepare($sql, $mid));
	
		$subject = strip_tags(stripslashes($mail_message->mail_subject));
		if (substr($subject, 0, 4) != "Re: ") {
			$subject = "Re: ".$subject;
		}
		$message = stripslashes($mail_message->mail_message);
	
		$header = chr(13)."--------------------------".chr(13);
		$header .= "From: ".stripslashes($mail_message->display_name).chr(13);
		$header .= "Sent: ".$mail_message->mail_sent.chr(13);
		$header .= "Subject: ".stripslashes($mail_message->mail_subject).chr(13).chr(13);
	
		$message = $header.$message;
	
		// return recipent (name), subject and message as JSON
		$return_arr = array();
		$row_array['recipient_id'] = $recipient_id;
		$row_array['recipient'] = $recipient;
		$row_array['subject'] = $subject;
		$row_array['message'] = $message;
	    array_push($return_arr,$row_array);
	
		echo json_encode($return_arr);
		
	}
	exit;
			
}

// Delete mail
if ($_POST['action'] == 'deleteMail') {

	if (is_user_logged_in()) {

		$mid = $_POST["mid"];
		$tray = $_POST["tray"];
		
		if ($tray == "in") {
			if ($wpdb->query( $wpdb->prepare("UPDATE ".$wpdb->base_prefix."symposium_mail SET mail_in_deleted = 'on' WHERE mail_mid = %d AND mail_to = %d", $mid, $current_user->ID) )) {
				echo __("Message deleted.", WPS_TEXT_DOMAIN);
			} else {
				echo __("Failed to delete message: ".$wpdb->last_query, "wp-symposium");				
			}
		}
		if ($tray == "sent") {
			if ($wpdb->query( $wpdb->prepare("UPDATE ".$wpdb->base_prefix."symposium_mail SET mail_sent_deleted = 'on' WHERE mail_mid = %d AND mail_from = %d", $mid, $current_user->ID) )) {
				echo __("Message deleted.", WPS_TEXT_DOMAIN);
			} else {
				echo __("Failed to delete message: ".$wpdb->last_query, "wp-symposium");				
			}
		}
		

	}
	
}


// Send mail
if ($_POST['action'] == 'sendMail') {

	if (is_user_logged_in()) {

		$compose_recipient_id = $_POST["compose_recipient_id"];
		
		$return = "Problem, not sure what, sorry.";
	
		$recipient = $wpdb->get_row("SELECT * FROM ".$wpdb->base_prefix."users WHERE ID = '".$compose_recipient_id."'");
		if (!$recipient) {
			$return = 'Recipient could not be found, sorry.';
		} else {
			$subject = strip_tags($_POST['compose_subject']);
			$message = sanitize_text_field($_POST['compose_text']);
			$previous = $_POST['compose_previous'];
		
			$message = $message.$previous;
		
			// Send mail
			if ( $rows_affected = $wpdb->insert( $wpdb->base_prefix . "symposium_mail", array( 
			'mail_from' => $current_user->ID, 
			'mail_to' => $recipient->ID, 
			'mail_sent' => date("Y-m-d H:i:s"), 
			'mail_subject' => $subject,
			'mail_message' => $message
			 ) ) ) {
				$return = __('Message sent to', WPS_TEXT_DOMAIN).' '.$recipient->display_name;
			 } else {
				$return = '<p><strong>'.__('There was a problem sending your mail to', WPS_TEXT_DOMAIN).' '.$recipient->display_name.'.</strong></p>';
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
					// email sent ok.
				} else {
					$return .= '<p><strong>'.__('There was a problem sending an email notification to', WPS_TEXT_DOMAIN).' '.$recipient->user_email.'.</strong></p>';
				}
			}

		}
		
		echo $return;
	}
}

// Get mail messages
if ($_POST['action'] == 'getBox') {

	if (is_user_logged_in()) {
	
		$tray = $_POST["tray"];
		$term = $_POST["term"];
		$unread = $_POST["unread"];
		$uid = $_POST["uid"];

		$start = 0;
		if (isset($_POST['start'])) { $start = $_POST["start"]; }
		$length = 5;
		if (isset($_POST['length'])) { $length = $_POST["length"]; }

		if ($unread == "true") {
			$unread = "AND m.mail_read != 'on' ";
		} else {
			$unread = "";
		}
		
		if ($tray == "in") {
			$sql = "SELECT m.*, u.display_name FROM ".$wpdb->base_prefix."symposium_mail m LEFT JOIN ".$wpdb->base_prefix."users u ON m.mail_from = u.ID WHERE mail_in_deleted != 'on' ".$unread." AND mail_to = %d AND (u.display_name LIKE '%%%s%%' OR mail_subject LIKE '%%%s%%' OR mail_message LIKE '%%%s%%') ORDER BY mail_mid DESC LIMIT %d, %d";
		} else {
			$sql = "SELECT m.*, u.display_name FROM ".$wpdb->base_prefix."symposium_mail m LEFT JOIN ".$wpdb->base_prefix."users u ON m.mail_to = u.ID WHERE mail_sent_deleted != 'on' ".$unread." AND mail_from = %d AND (u.display_name LIKE '%%%s%%' OR mail_subject LIKE '%%%s%%' OR mail_message LIKE '%%%s%%') ORDER BY mail_mid DESC LIMIT %d, %d";
		}

		$mail = $wpdb->get_results($wpdb->prepare($sql, $uid, $term, $term, $term, $start, $length));
		$sql = $wpdb->last_query;

		$return_arr = array();	

		$row_array['next_message_id'] = $start+$length;
        array_push($return_arr,$row_array);
        
        if (WPS_DEBUG) {
	        $row_array['mail_read'] = "mail_item_unread";
	        $row_array['mail_mid'] = 0;
	        $row_array['mail_sent'] = '';
	        $row_array['mail_from'] = 'debug';
	        $row_array['mail_subject'] = 'debug info';
	        $row_array['message'] = $sql;
	        array_push($return_arr,$row_array);
        }

        $mail_cnt = 0;

		if ($mail) {
			foreach ($mail as $item)
			{
				$mail_cnt++;

				if ($item->mail_read != "on") {
					$row_array['mail_read'] = "mail_item_unread";
				} else {
					$row_array['mail_read'] = "mail_item_read";
				}
				$row_array['mail_mid'] = $item->mail_mid;
				$row_array['mail_sent'] = __wps__time_ago($item->mail_sent);
				if ($tray == "in") {
					$row_array['mail_from'] = stripslashes(__wps__profile_link($item->mail_from));
				} else {
					$row_array['mail_from'] = stripslashes(__wps__profile_link($item->mail_to));
				}
				if ($item->mail_read != 'on') {
					$row_array['mail_from'] = '<img src="'.get_option(WPS_OPTIONS_PREFIX.'_images').'/new.gif" /> '.$row_array['mail_from'];
				}
				$row_array['mail_subject'] = __wps__bbcode_remove(stripslashes($item->mail_subject));
				@$row_array['mail_subject'] = preg_replace(
				  "/(>|^)([^<]+)(?=<|$)/iesx",
				  "'\\1' . str_replace('" . $term . "', '<span class=\"__wps__search_highlight\">" . $term . "</span>', '\\2')",
				  $row_array['mail_subject']
				);
                
				$row_array['mail_subject'] = stripslashes($row_array['mail_subject']);
				$message = strip_tags(stripslashes($item->mail_message));
				if ( strlen($message) > 75 ) { $message = substr($message, 0, 75)."..."; }
				@$message = preg_replace(
				  "/(>|^)([^<]+)(?=<|$)/iesx",
				  "'\\1' . str_replace('" . $term . "', '<span class=\"__wps__search_highlight\">" . $term . "</span>', '\\2')",
				  $message
				);
				$row_array['message'] = stripslashes(__wps__bbcode_remove($message));

		        array_push($return_arr,$row_array);

			}
		} else {
	        array_push($return_arr,$row_array);			
		}

		echo json_encode($return_arr);
		
	}

}
				
// Get single mail message
if ($_POST['action'] == 'getMailMessage') {

	if (is_user_logged_in()) {
	
		$mail_mid = $_POST['mid'];	
		$tray = sanitize_text_field($_POST['tray']);	

		if ($tray == "in") {
			$mail = $wpdb->get_row($wpdb->prepare("SELECT m.*, u.display_name FROM ".$wpdb->base_prefix."symposium_mail m LEFT JOIN ".$wpdb->base_prefix."users u ON m.mail_from = u.ID WHERE mail_mid = %d", $mail_mid));
		} else {
			$mail = $wpdb->get_row($wpdb->prepare("SELECT m.*, u.display_name FROM ".$wpdb->base_prefix."symposium_mail m LEFT JOIN ".$wpdb->base_prefix."users u ON m.mail_to = u.ID WHERE mail_mid = %d", $mail_mid));
		}
		
		// check that permission is okay
		if ( ($tray == "in" && $mail->mail_to == $current_user->ID) || ($tray != "in" && $mail->mail_from == $current_user->ID) || (__wps__get_current_userlevel() == 5) ) {
						
			// Swap codes from template
			$msg = stripslashes(str_replace('[]', '', get_option(WPS_OPTIONS_PREFIX.'_template_mail_message')));
	
			// First the avatar
			if (strpos($msg, '[avatar') !== FALSE) {
	
				if ($tray == "in") {
					$uid = $mail->mail_from;
				} else {
					$uid = $mail->mail_to;
				}
	
				if (strpos($msg, '[avatar]')) {
					$msg = str_replace("[avatar]", get_avatar($uid, 44), $msg);						
				} else {
					$x = strpos($msg, '[avatar');
					$avatar = substr($msg, 0, $x);
					$avatar2 = substr($msg, $x+8, 2);
					$avatar3 = substr($msg, $x+11, strlen($msg)-$x-11);
									
					$msg = $avatar . get_avatar($uid, $avatar2) . $avatar3;				
				}
			}
	
			// Now the subject and sender
			$msg = str_replace("[mail_subject]", "<span style='font-weight:bold'>".stripslashes(__wps__bbcode_replace($mail->mail_subject))."</span>", $msg);						
			
			// Sender/recipient
			if ($tray == "in") {
				$msg = str_replace("[mail_recipient]", __('From', WPS_TEXT_DOMAIN)." ".stripslashes($mail->display_name), $msg);
			} else {
				$msg = str_replace("[mail_recipient]", __('To', WPS_TEXT_DOMAIN)." ".stripslashes($mail->display_name), $msg);
			}
	
			// Sent
			$msg = str_replace("[mail_sent]", __wps__time_ago($mail->mail_sent), $msg);
	
			// Delete button
			$msg = str_replace("[delete_button]", '<img src="'.get_option(WPS_OPTIONS_PREFIX.'_images').'/delete2.png" id='.$mail_mid.' class="message_delete" />', $msg);
	
			// Reply button
			if ($tray == 'in') {
				if ($mail->mail_to == $current_user->ID) {		
					$msg = str_replace("[reply_button]", '<input type="submit" id='.$mail->mail_from.' rel="'.stripslashes($mail->display_name).'" title='.$mail_mid.' class="message_reply __wps__button" value="'.__('Reply', WPS_TEXT_DOMAIN).'" />', $msg);
				} else {
					$msg = str_replace("[reply_button]", '', $msg);
				}
			} else {
				if ($mail->mail_from == $current_user->ID) {		
					$msg = str_replace("[reply_button]", '<input type="submit" id='.$mail->mail_to.' title='.$mail_mid.' rel="'.stripslashes($mail->display_name).'" class="message_reply __wps__button" value="'.__('Reply', WPS_TEXT_DOMAIN).'" />', $msg);
				} else {
					$msg = str_replace("[reply_button]", '', $msg);
				}
			}
	
			// Message
			$msg = str_replace("[message]", stripslashes(__wps__bbcode_replace($mail->mail_message)), $msg);
			
			// Emoticons
			$msg = __wps__buffer($msg);
			
			// Layout for HTML
			$msg = str_replace(chr(10), "<br />", $msg);
				
			// Mark as read
			if ($tray == "in") {
				$wpdb->query( $wpdb->prepare("UPDATE ".$wpdb->base_prefix."symposium_mail SET mail_read = 'on' WHERE mail_mid = %d AND mail_to = %d", $mail_mid, $current_user->ID) );
			}
	
			// Fetch new unread count
			$unread = "?!";
			if ($tray == "in") {
				$unread = $wpdb->get_var("SELECT COUNT(*) FROM ".$wpdb->base_prefix.'symposium_mail'." WHERE mail_to = ".$mail->mail_to." AND mail_".$tray."_deleted != 'on' AND mail_read != 'on'");
			} else {
				$unread = $wpdb->get_var("SELECT COUNT(*) FROM ".$wpdb->base_prefix.'symposium_mail'." WHERE mail_from = ".$mail->mail_from." AND mail_".$tray."_deleted != 'on' AND mail_read != 'on'");
			}
	
			echo $mail_mid."[split]".$unread."[split]".$tray."[split]".$msg;
			
		}
		exit;

	}
}


?>

	
