<?php
error_reporting(0);

//MySQL DB settings
include_once('../../../../../wp-config.php');
global $wpdb;

if (is_user_logged_in()) {
	
	$sent_time = time() - 7200; // Hide old messages


	//check if current user has unreceived messages which are older than limit, if yes, display it with date
	$check_unreceived = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->base_prefix."symposium_chat2 WHERE from_id=%s AND to_id=%s AND sent < %s AND recd='0' ORDER BY id", $_POST['partner_id'], $_POST['own_id'], $sent_time));
	if($check_unreceived){
		foreach ($check_unreceived as $check_ur_row) {
			//there is/are an unreceived message(s)
			//mark message(s) received and update their received times
			$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."symposium_chat2 SET recd='1',sent=%s WHERE id=%s AND recd='0'", time(), $check_ur_row->id));
		}
	
		//insert info message as system into current chat
		$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->base_prefix."symposium_chat2 (from_id,to_id,message,sent,system_message) VALUES(%s,%s,'".__('These are unreceived messages from the previous chat session!', WPS_TEXT_DOMAIN)."',%s,'yes')", $_POST['partner_id'], $_POST['own_id'], time()));
	}

	// set typing flag, or clear
	$is_typing = $_POST['typing'];
	if ($is_typing) {
		$sql = "INSERT INTO ".$wpdb->base_prefix."symposium_chat2_typing (typing_from,typing_to) VALUES (%d,%d)";
		$wpdb->query($wpdb->prepare($sql, $_POST['own_id'], $_POST['partner_id']));
	} else {
		$sql = "DELETE FROM ".$wpdb->base_prefix."symposium_chat2_typing WHERE typing_from = %d AND typing_to = %d";
		$wpdb->query($wpdb->prepare($sql, $_POST['own_id'], $_POST['partner_id']));
	}

	// get typing flag
	$sql = "SELECT * FROM ".$wpdb->base_prefix."symposium_chat2_typing WHERE typing_from = %d AND typing_to = %d";
	$check_typing = $wpdb->get_row($wpdb->prepare($sql, $_POST['partner_id'], $_POST['own_id']));
	if ($check_typing) {
		$is_typing = 1;
	} else {
		$is_typing = 0;
	}
	$is_typing = '#('.$is_typing.')#';
	
	//load messages
	$res = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->base_prefix."symposium_chat2 WHERE (from_id=%s AND to_id=%s AND sent > %s) OR (from_id=%s AND to_id=%s AND sent > %s) ORDER BY sent", $_POST['own_id'], $_POST['partner_id'], $sent_time, $_POST['partner_id'], $_POST['own_id'], $sent_time));
	if($res){
		$last_id = '';
		$from_id = '';
		foreach($res as $row) {
			
			$last_id = '#['.$row->id.']#';
			$from_id = '#{'.$row->from_id.'}#';
			
			$msg = stripslashes($row->message);

			//remove anything that may become a smiley
			$msg = str_replace('http://', '', $msg);
			$msg = str_replace('https://', '', $msg);
			$msg = str_replace('ftp://', '', $msg);

			// Smilies as classic text
			$smileys = WPS_PLUGIN_URL . '/images/smilies/';
			$smileys_dir = WPS_PLUGIN_DIR . '/images/smilies/';
			$buffer = $msg;
			$buffer = str_replace(":)", "<img src='".$smileys."smile.png' alt='emoticon'/>", $buffer);
			$buffer = str_replace(":-)", "<img src='".$smileys."smile.png' alt='emoticon'/>", $buffer);
			$buffer = str_replace(":(", "<img src='".$smileys."sad.png' alt='emoticon'/>", $buffer);
			$buffer = str_replace(":'(", "<img src='".$smileys."crying.png' alt='emoticon'/>", $buffer);
			$buffer = str_replace(":x", "<img src='".$smileys."kiss.png' alt='emoticon'/>", $buffer);
			$buffer = str_replace(":X", "<img src='".$smileys."shutup.png' alt='emoticon'/>", $buffer);
			$buffer = str_replace(":D", "<img src='".$smileys."laugh.png' alt='emoticon'/>", $buffer);
			$buffer = str_replace(":|", "<img src='".$smileys."neutral.png' alt='emoticon'/>", $buffer);
			$buffer = str_replace(":?", "<img src='".$smileys."question.png' alt='emoticon'/>", $buffer);
			$buffer = str_replace(":z", "<img src='".$smileys."sleepy.png' alt='emoticon'/>", $buffer);
			$buffer = str_replace(":P", "<img src='".$smileys."tongue.png' alt='emoticon'/>", $buffer);
			$buffer = str_replace(";)", "<img src='".$smileys."wink.png' alt='emoticon'/>", $buffer);
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
						$buffer = $first_bit."<img style='width:24px;height:24px' src='".$smileys.strip_tags($bit).".png' alt='emoticon'/>".$last_bit;
					}
				}
			} while ($i < 100 && strpos($buffer, "{{")>0);
			$msg = $buffer;
			
			//make hyperlinks
			$msg = links_add_target(make_clickable($msg));
			
			//print messages
			if($row->system_message == 'yes'){				
				//message from system				
				print '<p class="system">'.$msg.'</p>';									
			}elseif($row->from_id != $_POST['own_id']){
				print '<p class="notme">'.$msg.'</p>';
			}else{
				print '<p class="me">'.$msg.'</p>';
			}

			//if to_id = current user, mark message as received
			if($row->to_id == $_POST['own_id']){
				$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->base_prefix."symposium_chat2 SET recd='1' WHERE id=%s AND recd='0'", $row->id));
			}		
			
			$last_msg = $row->sent;
		}	
		
		print $is_typing;
		print $last_id;
		print $from_id;
		
		
	}else{

		print '';		
	}
	
} else {
	print '!';
}
?>