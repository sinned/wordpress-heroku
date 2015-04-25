<?php

include_once('../../../../wp-config.php');

// Check for return from Facebook application acceptance
if (isset($_GET['state'])) {
	header("Location:".__wps__get_url('profile'));
}

// Clear Subscriptions
if ($_POST['action'] == 'clearSubs') {
	
	global $wpdb, $current_user;

	if (is_user_logged_in()) {

		$sql = "DELETE FROM ".$wpdb->prefix."symposium_subs WHERE uid = %d";
		$wpdb->query( $wpdb->prepare($sql, $current_user->ID));
		
	}
	
	echo 'OK';
	exit;
}

// Remove all friends
if ($_POST['action'] == 'remove_all_friends') {
	
	global $wpdb;

	if (is_user_logged_in()) {

		$uid = $_POST['uid'];	
		$sql = "DELETE FROM ".$wpdb->base_prefix."symposium_friends WHERE friend_from = %d || friend_to = %d";
		$rows_affected = $wpdb->query( $wpdb->prepare($sql, $uid, $uid) );
	}
	
	echo 'OK';
	exit;
}

// Remove Avatar
if ($_POST['action'] == 'remove_avatar') {
	
	global $wpdb;

	if (is_user_logged_in()) {

		$uid = $_POST['uid'];	
		__wps__update_meta($uid, 'profile_avatar', '');
		__wps__update_meta($uid, 'profile_photo', '');
		
	}
	
	echo 'OK';
	exit;
}

// Poke
if ($_POST['action'] == 'send_poke') {
	
	global $wpdb, $current_user;
	wp_get_current_user();

	if (is_user_logged_in()) {

		$recipient = $_POST['recipient'];

		$subject = __('You have been sent a ', WPS_TEXT_DOMAIN).get_option(WPS_OPTIONS_PREFIX.'_poke_label');
		$url = __wps__get_url('profile');
		$message = "<a href='".$url.__wps__string_query($url)."uid=".$current_user->ID."'>".$current_user->display_name."</a>".__(' has sent you a ', WPS_TEXT_DOMAIN).get_option(WPS_OPTIONS_PREFIX.'_poke_label');
	
		// Add to activity
		__wps__add_activity_comment($current_user->ID, $current_user->display_name, $recipient, get_option(WPS_OPTIONS_PREFIX.'_poke_label'), 'poke');
		
		// Get comment ID
		$sql = "SELECT cid FROM ".$wpdb->base_prefix."symposium_comments WHERE author_uid = %d ORDER BY cid DESC LIMIT 0,1";
		$cid = $wpdb->get_var($wpdb->prepare($sql, $current_user->ID));

		// Filter to allow further actions to take place
		apply_filters ('__wps__send_poke_filter', $recipient, $current_user->ID, $current_user->display_name, get_option(WPS_OPTIONS_PREFIX.'_poke_label'), $cid );
			
		// Send mail
		if (function_exists('__wps__mail')) {
			if ( $rows_affected = $wpdb->prepare( $wpdb->insert( $wpdb->base_prefix . "symposium_mail", array( 
			'mail_from' => $current_user->ID, 
			'mail_to' => $recipient, 
			'mail_sent' => date("Y-m-d H:i:s"), 
			'mail_subject' => $subject,
			'mail_message' => $message
			 ) ) ) ) {
				echo 'OK';
			 } else {
				echo 'FAIL';
			 }
		} else {
			echo 'OK';
		}
	}
	exit;
}


// Update Profile Avatar
if ($_POST['action'] == 'saveProfileAvatar') {

	global $wpdb;

	if (is_user_logged_in()) {
	
		$uid = $_POST['uid'];
		$x = $_POST['x'];
		$y = $_POST['y'];
		$w = $_POST['w'];
		$h = $_POST['h'];
		
		$r = '';
		$err = 'err';
		
		if ($w == 0)
			$x = $y = $w = $h = 0;
			
		$err .= 'w>0';

		// set new size and quality
		$targ_w = $targ_h = 200;
		$jpeg_quality = 90;

		// database or filesystem
	
		if (get_option(WPS_OPTIONS_PREFIX.'_img_db') == 'on') {
			
			$err .= 'db';
			
			// Using database
			$avatar = stripslashes(__wps__get_meta($uid, 'profile_avatar'));
		
			// create master from database
			$img_r = imagecreatefromstring($avatar);

			if ($w > 0) {
				
				// set new size
				$targ_w = $targ_h = 200;
	
				// create temporary image
				$dst_r = ImageCreateTrueColor( $targ_w, $targ_h );		
				// copy to new image, with new dimensions
				imagecopyresampled($dst_r,$img_r,0,0,$x,$y,$targ_w,$targ_h,$w,$h);
				// copy to variable
				ob_start();
				imageJPEG($dst_r);
				$new_img = ob_get_contents();
				ob_end_clean();
				
			} else {
				
				$new_img = $avatar;
				
			}
			
			// update database with resized blob
			__wps__update_meta($uid, 'profile_avatar', addslashes($new_img));	
			$r = '';
				
		} else {
			
			$err .= 'filesystem';
			
			// Using filesystem
			$profile_photo = __wps__get_meta($uid, 'profile_photo');
		
			$src = get_option(WPS_OPTIONS_PREFIX.'_img_path')."/members/".$uid."/profile/".$profile_photo;

			list ($width, $height, $type) = getimagesize ($src);			

			if ($type == 1)
		    {
		        $img_r = imagecreatefromgif($src);
		    }
		    elseif ($type == 2)
		    {
		        $img_r = imagecreatefromjpeg($src);
		    }
		    elseif ($type == 3)
		    {
		        $img_r = imagecreatefrompng($src);
		    }
		    else
		    {
		        $img_r = imagecreatefromwbmp($src);
		    }
    
			if ($w == 0)
				list($w, $h, $type) = getimagesize($src);
			$dst_r = ImageCreateTrueColor($targ_w, $targ_h);
		
			if ( imagecopyresampled($dst_r,$img_r,0,0,$x,$y,$targ_w,$targ_h,$w,$h) ) {
				
				$err .= ' resampled-ok';
	
				$to_path = get_option(WPS_OPTIONS_PREFIX.'_img_path')."/members/".$uid."/profile/";
				$filename = time().'.jpg';
				$to_file = $to_path.$filename;
				if (file_exists($to_path)) {
				    // folder already there
					$err .= ' folder-exists';
				} else {
					mkdir(str_replace('//','/',$to_path), 0777, true);
					$err .= ' mkdir';
				}
				
				if ( imagejpeg($dst_r,$to_file,$jpeg_quality) ) {

					$err .= ' imagejpeg-ok';
				
					// update database
					__wps__update_meta($uid, 'profile_photo', "'".$filename."'");
					$r = '';
					
				} else {

					$err .= ' imagejpeg-failed';
				
					$r = 'conversion to jpeg failed';
					
				}
					
			} else {

				$err .= ' crop failed: '.$profile_photo.','.$src.','.$dst_r.','.$img_r.','.$wpdb->last_query;
				$r = 'Error: '.$err;

			}
		}
			
	} else {
		
		$r = "NOT LOGGED IN";
		
	}
	
	echo $r;	
	exit;
	
}

// AJAX function to add status
if ($_POST['action'] == 'addStatus') {

	global $wpdb, $current_user;
	wp_get_current_user();

	if (is_user_logged_in()) {

		if (isset($_POST['subject_uid'])) {
			$subject_uid = $_POST['subject_uid'];
		} else {
			$subject_uid = $current_user->ID;
		}
		$author_uid = $current_user->ID;
        $text = $_POST['text'];
		//$text = sanitize_text_field($_POST['text']); // cannot do this as removes, for example, YouTube iframe
		$facebook = $_POST['facebook'];

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
	        	$subject_uid, 
	        	$author_uid, 
	        	0,
	        	date("Y-m-d H:i:s"),
	        	$text,
	        	''
	        	) 
	        ) );

		// New Post ID
		$new_id = $wpdb->insert_id;
		
		// Check for any pending uploads and copy to this post
		$directory = get_option(WPS_OPTIONS_PREFIX.'_img_path')."/members/".$subject_uid."/activity/pending/";
		$attached_filename = '';
		
		if (file_exists($directory)) {
			$handler = opendir($directory);
			$done_one = false;
			while ($image = readdir($handler)) {
				$ext = substr(strrchr($image,'.'),1);
				if (!$done_one && $image != "." && $image != ".." && ($ext == 'jpg' || $ext == 'gif' || $ext == 'png')) {
					$targetDir = get_option(WPS_OPTIONS_PREFIX.'_img_path')."/members/".$current_user->ID;
					$targetActivityDir = get_option(WPS_OPTIONS_PREFIX.'_img_path')."/members/".$subject_uid."/activity";
					$filename = $new_id.'.'.$ext;
					$targetActivityFile = $targetActivityDir.'/'.$filename;
					if (!file_exists($targetDir))
						@mkdir($targetDir);
					if (!file_exists($targetActivityDir))
						@mkdir($targetActivityDir);
		
					@copy($directory.'/'.$image, $targetActivityFile);
					@unlink($directory.'/'.$image);
					$image_filename = $image;
					$done_one = true;
				}
			}
			if ($done_one) {
				$attached_filename .= WP_CONTENT_URL."/wps-content/members/".$subject_uid."/activity/".$filename;
			}
		}

		// Send to Facebook?
		if ($facebook == 1 && function_exists('__wps__facebook')) {

			if (!class_exists('__wps__FacebookApiException'))
				include_once("../library/src/facebook.php");

			$__wps__facebook = new __wps__Facebook(array(
			'appId'=>get_option(WPS_OPTIONS_PREFIX.'_facebook_api'),
			'secret'=>get_option(WPS_OPTIONS_PREFIX.'_facebook_secret'),
			'cookie'=>true
			));
			
			// Get User ID
			$user = $__wps__facebook->getUser();
			
			if ($user) {
			  try {

				$iframe = __wps__extract_unit($text, '<iframe width=\"100%\" height=\"250\" src=\"http://www.youtube.com/embed/', '?wmode=transparent\" frameborder=\"0\" allowfullscreen></iframe>');		
				if ($iframe) {
					$iframe = 'http://www.youtube.com/watch?v='.$iframe;
					$ftext = strip_tags($text);
				} else {
					$ftext = $text;
				}
				$ftext = stripslashes($ftext);
				
				if ($attached_filename) {
					$attachment = array(
						'message' => $ftext,
						'name' => $filename,
						'picture' => $attached_filename,
						'link' => $iframe
					);
			    } else {
			        if (isset($iframe) && $iframe != '') {
						$attachment = array(
							'message' => $ftext,
							'link' => $iframe
						);
			        } else {
						$attachment = array(
							'message' => $ftext
						);
			        }
			    }
				
				try {
					$result = $__wps__facebook->api('/me/feed/', 'post', $attachment);
				} catch  (__wps__FacebookApiException $e) {
					$result = $e->getResult();
			        echo "<pre>";
			        print_r($result);
			        echo "</pre>";														
				}
				      					
			  } catch (__wps__FacebookApiException $e) {
				$result = $e->getResult();
		        echo "<pre>";
		        print_r($result);
		        echo "</pre>";														
			    $user = null;
			  }
			  
			} else {					
			  echo __('Failed to connect to Facebook', WPS_TEXT_DOMAIN);
			}
							
		}
		
		// If attached an image, now send it (had to do Facebook first due to session headers)
		if ($attached_filename) {
			echo $attached_filename;
		} else {
			//echo 'No attachment, looked in '.$directory;
		}
				
	   	// Subject's name for use below
		$subject_name = $wpdb->get_var($wpdb->prepare("SELECT display_name FROM ".$wpdb->base_prefix."users WHERE ID = %d", $subject_uid));
	
		// Email the subject (if they want to know about it and not self-posting)		        
		if ($author_uid != $subject_uid) {

			if (__wps__get_meta($subject_uid, 'notify_new_wall') == 'on') {
				// Filter to allow further actions to take place
				apply_filters ('__wps__wall_newpost_filter', $subject_uid, $author_uid, $current_user->display_name );

				$sql = "SELECT u.user_email FROM ".$wpdb->base_prefix."users u WHERE u.ID = %d";
				$recipient = $wpdb->get_row($wpdb->prepare($sql, $subject_uid));	
		
				if ($recipient) {
					$body = "<p>".$current_user->display_name." ".__('has added a new post on your profile', WPS_TEXT_DOMAIN).":</p>";
					$body .= "<p>".stripslashes($text)."</p>";
					$body .= "<p><a href='".__wps__get_url('profile')."?uid=".$subject_uid."&post=".$new_id."'>".__('Go to the post', WPS_TEXT_DOMAIN)."...</a></p>";
					__wps__sendmail($recipient->user_email, __('New Profile Post', WPS_TEXT_DOMAIN), $body);
				}
			}
		}
	
		// Hook for other actions to take place
		do_action('symposium_profile_newpost_hook', $subject_uid, $author_uid, $new_id, $text);	
        
		exit;
			
	} else {
		
		echo "NOT LOGGED IN";
		
	}
		
}

// AJAX function to add comment
if ($_POST['action'] == 'addComment') {

	global $wpdb, $current_user;
	wp_get_current_user();

	if (is_user_logged_in()) {

		$uid = $_POST['uid'];
		$text = $_POST['text'];
		$parent = $_POST['parent'];

		if (is_user_logged_in()) {

			if ( ($text != __(addslashes("Write a comment..."), "wp-symposium")) && ($text != '') ) {
	
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
			        	$uid, 
			        	$current_user->ID, 
			        	$parent,
			        	date("Y-m-d H:i:s"),
			        	$text,
			        	''
			        	) 
			        ) );

				// New Post ID
				$new_id = $wpdb->insert_id;
		        		        
			    // Subject's name for use below
				$subject_name = $wpdb->get_var($wpdb->prepare("SELECT display_name FROM ".$wpdb->base_prefix."users WHERE ID = %d", $uid));

				// Get parent post (the first post)
				$sql = "SELECT * FROM ".$wpdb->base_prefix."symposium_comments WHERE cid = %d";
				$parent_post = $wpdb->get_row($wpdb->prepare($sql, $parent));
			
				// Email the author of the parent (ie. first post) if wants to be notified					
				$sql = "SELECT ID, user_email FROM ".$wpdb->base_prefix."users WHERE ID = %d AND ID != %d";
				$parent_post_recipient = $wpdb->get_row($wpdb->prepare($sql, $parent_post->author_uid, $current_user->ID));

				if ($parent_post_recipient) {
					if (__wps__get_meta($parent_post_recipient->ID, 'notify_new_wall') == 'on') {
	
						$profile_url = __wps__get_url('profile');
						$profile_url .= __wps__string_query($profile_url);
						$url = $profile_url."uid=".$uid."&post=".$parent_post->cid;
		
						$body = "<p>".$current_user->display_name." ".__('has replied to a post you started', WPS_TEXT_DOMAIN).":</p>";
						$body .= "<p>".stripslashes($text)."</p>";
						$body .= "<p><a href='".$url."'>".__('Go to the post', WPS_TEXT_DOMAIN)."...</a></p>";
						__wps__sendmail($parent_post_recipient->user_email, __('Profile Reply', WPS_TEXT_DOMAIN), $body);				
					}
				}

				// Get URL for later use in several places
				$profile_url = __wps__get_url('profile');
				$profile_url .= __wps__string_query($profile_url);
				$url = $profile_url."uid=".$uid."&post=".$parent_post->cid;
				
				// Email the subject of the parent (ie. first post) and want to be notified
				if ($parent_post->subject_uid != $parent_post->author_uid) {
					$sql = "SELECT ID, user_email FROM ".$wpdb->base_prefix."users WHERE ID = %d AND ID != %d";			
					$parent_post_recipient = $wpdb->get_row($wpdb->prepare($sql, $parent_post->subject_uid, $current_user->ID));
					
					if ($parent_post_recipient) {
						if (__wps__get_meta($parent_post_recipient->ID, 'notify_new_wall') == 'on') {
	
							if ($parent_post_recipient->notify_new_wall == 'on') {
								$body = "<p>".$current_user->display_name." ".__('has replied to a post started on your profile', WPS_TEXT_DOMAIN).":</p>";
								$body .= "<p>".stripslashes($text)."</p>";
								$body .= "<p><a href='".$url."'>".__('Go to the post', WPS_TEXT_DOMAIN)."...</a></p>";
								__wps__sendmail($parent_post_recipient->user_email, __('Profile Reply', WPS_TEXT_DOMAIN), $body);				
							}	
						}
					}
				}

				// Filter to allow further actions to take place
				apply_filters ('__wps__wall_postreply_filter', $parent_post->subject_uid, $parent_post->author_uid, $current_user->ID, $current_user->display_name, $url);
						
				// Email all the people who have replied to this post and want to be notified
				$sql = "SELECT DISTINCT u.user_email, u.ID
					FROM ".$wpdb->base_prefix."symposium_comments c 
					LEFT JOIN ".$wpdb->base_prefix."users u ON c.author_uid = u.ID 
					WHERE c.comment_parent = %d AND u.ID != %d";
			
				$reply_recipients = $wpdb->get_results($wpdb->prepare($sql, $parent, $current_user->ID));

				if ($reply_recipients) {
					foreach ($reply_recipients as $reply_recipient) {
						
						if (__wps__get_meta($reply_recipient->ID, 'notify_new_wall') == 'on') {
					
							if ($reply_recipient->ID != $parent_post->subject_uid && $reply_recipient->ID != $parent_post->author_uid) {
		
								if ($reply_recipient->notify_new_wall == 'on') {
									$body = "<p>".$current_user->display_name." ".__('has replied to a post you are involved in', WPS_TEXT_DOMAIN).":</p>";
									$body .= "<p>".stripslashes($text)."</p>";
									$body .= "<p><a href='".$url."'>".__('Go to the post', WPS_TEXT_DOMAIN)."...</a></p>";
									__wps__sendmail($reply_recipient->user_email, __('New Post Reply', WPS_TEXT_DOMAIN), $body);				
								}
		
								// Filter to allow further actions to take place
								apply_filters ('__wps__wall_postreply_involved_filter', $reply_recipient->ID, $current_user->ID, $current_user->display_name, $url);		
		
							}
						}
					
					}
				}
								
				exit;

			} else {

				exit;
			
			}
			
			
		} else {
		
			exit;
		
		}
		
	}
}

// Show Wall
if ($_POST['action'] == 'menu_wall') {

	global $current_user;
	
	$uid1 = $_POST['uid1'];
	$uid2 = $current_user->ID;
	$post = $_POST['post'];
	$limit_from = $_POST['limit_from'];

	$html = __wps__buffer(__wps__profile_body($uid1, $uid2, $post, "wall", $limit_from));

	echo $html;
	
	exit;
	
}

// Show Friends Activity
if ($_POST['action'] == 'menu_activity') {

	global $current_user;
	
	$uid1 = $_POST['uid1'];
	$uid2 = $current_user->ID;
	$post = $_POST['post'];
	$limit_from = $_POST['limit_from'];
	$rel = isset($_POST['rel']) ? $_POST['rel'] : '';

	$html = __wps__buffer(__wps__profile_body($uid1, $uid2, $post, "friends_activity", $limit_from, true, $rel));

	echo $html;
	exit;
	
}

// Show All
if ($_POST['action'] == 'menu_all') {

	global $current_user;

	$uid1 = $_POST['uid1'];
	$uid2 = $current_user->ID;
	$post = $_POST['post'];
	$limit_from = $_POST['limit_from'];

	$html = __wps__buffer(__wps__profile_body($uid1, $uid2, $post, "all_activity", $limit_from));

	echo $html;
	exit;
	
}

// Show Extended
if ($_POST['action'] == 'menu_extended') {

	global $wpdb, $current_user;
	wp_get_current_user();

	$uid1 = $_POST['uid1'];
	$uid2 = $current_user->ID;

	$display_name = $wpdb->get_var($wpdb->prepare("SELECT display_name FROM ".$wpdb->base_prefix."users WHERE ID = %d", $uid1));
	$share = __wps__get_meta($uid1, 'share');
	$city = __wps__get_meta($uid1, 'extended_city');
	$country = __wps__get_meta($uid1, 'extended_country');
	
	$html = "";
	
	if (is_user_logged_in() || $share == 'public') {

			if ( ($uid1 == $uid2) || (is_user_logged_in() && strtolower($share) == 'everyone') || (strtolower($share) == 'public') || (strtolower($share) == 'friends only' && __wps__friend_of($uid1, $current_user->ID)) ) {
	
				// Google map
				$city = $city;
				$city = str_replace(' ','%20',$city);
				$country = $country;
				$country = str_replace(' ','%20',$country);
				$has_map = false;
				
				if ( ($city != '' || $country != '') && (get_option(WPS_OPTIONS_PREFIX.'_profile_google_map') > 0) ){ 	
									
					$html .= "<div id='google_profile_map' style='width:".get_option(WPS_OPTIONS_PREFIX.'_profile_google_map')."px; height:".get_option(WPS_OPTIONS_PREFIX.'_profile_google_map')."px'>";
					$html .= '<a target="_blank" href="http://maps.google.co.uk/maps?f=q&amp;source=embed&amp;hl=en&amp;geocode=&amp;q='.$city.',+'.$country.'&amp;ie=UTF8&amp;hq=&amp;hnear='.$city.',+'.$country.'&amp;output=embed&amp;z=5" alt="Click on map to enlarge" title="Click on map to enlarge">';
					$html .= '<img src="http://maps.google.com/maps/api/staticmap?center='.$city.',.+'.$country.'&zoom=5&size='.get_option(WPS_OPTIONS_PREFIX.'_profile_google_map').'x'.get_option(WPS_OPTIONS_PREFIX.'_profile_google_map').'&maptype=roadmap&markers=color:blue|label:&nbsp;|'.$city.',+'.$country.'&sensor=false" />';
					$html .= "</a></div>";
				
					$has_map = true;
				
				}
				
				// Extended Information
				$sql = "SELECT * FROM ".$wpdb->base_prefix."symposium_extended";
				$extensions = $wpdb->get_results($sql);

				$ext_rows = array();		
				if ($extensions) {		
					foreach ($extensions as $extension) {
						$value = __wps__get_meta($uid1, 'extended_'.$extension->extended_slug);
						if ($extension->extended_type == 'Checkbox' || $value) {
							array_push ($ext_rows, array (	'name'=>$extension->extended_name,
															'value'=>$value,
															'type'=>$extension->extended_type,
															'order'=>$extension->extended_order ) );
						}
					}
				}
				
				// Hook to add Extended Fields within the array $ext_rows
				$ext_rows = apply_filters ( '__wps__add_extended_field_filter', $ext_rows );
				
				if ($ext_rows) {
					
					$ext_rows = __wps__sub_val_sort($ext_rows,'order');
					foreach ($ext_rows as $row) {
						if ($row['type'] == 'Checkbox' && !$row['value'] && get_option(WPS_OPTIONS_PREFIX.'_profile_show_unchecked') != 'on') { 
							// Don't show if unchecked and chosen not to show (in Profile config)
						} else {

							$html .= "<div style='margin-bottom:0px;overflow: auto;'>";
							if ($row['type'] != 'Checkbox') {
								$html .= "<div class='profilemenu-head' style='font-weight:bold;'>".stripslashes($row['name'])."</div>";
								$value = str_replace("\n", "<br />", stripslashes(stripslashes($row['value'])));
								$html .= "<div style='margin-bottom:10px'>".__wps__make_url($value)."</div>";
							} else {
								$html .= "<div class='profilemenu-content' style='margin-bottom:10px;font-weight:bold;'>";
								$html .= stripslashes($row['name'])."&nbsp;";
								if (get_option(WPS_OPTIONS_PREFIX.'_profile_show_unchecked') == 'on') {
									if ($row['value']) { 
										$html .= "<br /><img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/tick.png' />"; 
									} else {
										$html .= "<br /><img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/cross.png' />"; 
									}
								}
								$html .= "</div><br />";
							}
							$html .= "</div>";
						}
					}
				} 
				
				if ($city == '' && $country == '' && !$ext_rows) {
	
					$html .= '<p>'.__("Sorry, there is no personal information to show.", WPS_TEXT_DOMAIN).'</p>';
	
				}
						
			} else {
			
				$html .= '<p>'.__("Sorry, this member has chosen not to share their personal information.", WPS_TEXT_DOMAIN).'</p>';
			
			}

		// add filter so text can be inserted above
		$html = apply_filters ('__wps__profile_header', $html);

	}
	
	// add filter which also applies to Public
	$html = apply_filters ('__wps__profile_header_public', $html);

	echo $html;
	exit;
	
}

// @mentions
if ($_POST['action'] == 'menu_mentions') {

	global $wpdb;
	$sql = "SELECT user_login, display_name FROM ".$wpdb->base_prefix."users WHERE ID = %d";
	$r = $wpdb->get_row($wpdb->prepare($sql, $_POST['uid1']));
	$tag = strtolower(str_replace(' ', '', $r->display_name));
	$tag2 = strtolower(str_replace(' ', '', $r->user_login));
	
	$html = "<p class='__wps__profile_heading'>".__('@mentions', WPS_TEXT_DOMAIN)."</p>";

	$sql = "SELECT * FROM 
	(
	SELECT t.topic_category, t2.topic_category as parent_category, t2.tid AS parent_id, t.tid as id, t2.topic_subject AS parent_text, t2.topic_group as parent_topic_group, t.topic_group as topic_group, t2.stub as parent_stub, t.stub as stub, t.topic_subject as text, t.topic_post as moretext, t.topic_owner AS author, t.topic_date AS dated, 'forum' AS type FROM ".$wpdb->prefix."symposium_topics t LEFT JOIN ".$wpdb->prefix."symposium_topics t2 ON t2.tid = t.topic_parent WHERE t.topic_post REGEXP '@".$tag."[[:>:]]'
	UNION ALL
	SELECT 0 AS topic_category, t.subject_uid as parent_category, t.comment_parent AS parent_id, t.cid as id, t2.comment AS parent_text, 0 as parent_topic_group, t.is_group as topic_group, u.display_name as parent_stub, g.name as stub, '' as text, t.comment as moretext, t.author_uid AS author, t.comment_timestamp AS dated, 'activity' AS type FROM ".$wpdb->prefix."symposium_comments t LEFT JOIN ".$wpdb->prefix."symposium_comments t2 ON t2.cid = t.comment_parent LEFT JOIN ".$wpdb->base_prefix."users u ON u.ID = t.subject_uid LEFT JOIN ".$wpdb->prefix."symposium_groups g ON g.gid = t.subject_uid WHERE t.type = 'post' AND t.comment REGEXP '@".$tag."[[:>:]]'
	)
	AS results ORDER BY dated DESC LIMIT 0,50";

	$mentions = $wpdb->get_results($sql);
	
	if (WPS_DEBUG) $html .= $wpdb->last_query.'<br />';

	if ($mentions) {
	
		foreach ($mentions AS $mention) {
			
			switch ($mention->type) {
				case 'forum': 
					if ($mention->parent_id > 0) {
						if (get_option(WPS_OPTIONS_PREFIX.'_permalink_structure') && $mention->parent_topic_group == 0) {
							$perma_cat = __wps__get_forum_category_part_url($mention->parent_category);
							$url = __wps__get_url('forum').'/'.$perma_cat.$mention->parent_stub;
						} else {
							if ($mention->parent_topic_group == 0) {
								$url = __wps__get_url('forum');
								$url .= __wps__string_query($url);
								$url .= 'show='.$mention->parent_id;
							} else {
								$url = __wps__get_url('group');
								$url .= __wps__string_query($url);
								$url .= 'gid='.$mention->parent_topic_group.'&cid=0&show='.$mention->parent_id;
							}
						}	
						$pre_text = __('Replied to', WPS_TEXT_DOMAIN).' ';
						$text = $mention->parent_text;
					} else {
						if (get_option(WPS_OPTIONS_PREFIX.'_permalink_structure') && $mention->topic_group == 0) {
							$perma_cat = __wps__get_forum_category_part_url($mention->topic_category);
							$url = __wps__get_url('forum').'/'.$perma_cat.$mention->stub;
						} else {
							if ($mention->topic_group == 0) {
								$url = __wps__get_url('forum');
								$url .= __wps__string_query($url);
								$url .= 'show='.$mention->id;
							} else {
								$url = __wps__get_url('group');
								$url .= __wps__string_query($url);
								$url .= 'gid='.$mention->topic_group.'&cid=0&show='.$mention->id;
							}
						}
						$pre_text = __('Started', WPS_TEXT_DOMAIN).' ';
						$text = $mention->text;
					}
					break;
				case 'activity':
					if (!$mention->topic_group) {
						if ($mention->parent_id == 0) {
						  $url = __wps__get_url('profile');
						  $url .= __wps__string_query($url);
						  $url .= 'uid='.$mention->author.'&post='.$mention->id;
						  $pre_text = __('Posted on', WPS_TEXT_DOMAIN).' ';
						  $text = sprintf(__("%s's activity", WPS_TEXT_DOMAIN), $mention->parent_stub);
						} else {
						  $url = __wps__get_url('profile');
						  $url .= __wps__string_query($url);
						  $url .= 'uid='.$mention->author.'&post='.$mention->parent_id;
						  $pre_text = __('Replied to a post on', WPS_TEXT_DOMAIN).' ';
						  $text = sprintf(__("%s's activity", WPS_TEXT_DOMAIN), $mention->parent_stub);
						}
					} else {
						if ($mention->parent_id == 0) {
						  $url = __wps__get_url('group');
						  $url .= __wps__string_query($url);
						  $url .= 'gid='.$mention->parent_category.'&post='.$mention->id;					
						  $pre_text = __('Posted on', WPS_TEXT_DOMAIN).' ';
						  $text = sprintf(__("%s group activity", WPS_TEXT_DOMAIN), $mention->stub);
						} else {
						  $url = __wps__get_url('group');
						  $url .= __wps__string_query($url);
						  $url .= 'gid='.$mention->parent_category.'&post='.$mention->parent_id;					
						  $pre_text = __('Replied to a post on', WPS_TEXT_DOMAIN).' ';
						  $text = sprintf(__("%s group activity", WPS_TEXT_DOMAIN), $mention->stub);
						}
					}
					break;
			}
			$html .= '<div class="__wps__mentions row">';
				$html .= '<div style="padding-left:70px;">';
					$html .= '<div style="width:70px; float:left; margin-left:-70px;">';
						$html .= get_avatar($mention->author, 64);
					$html .= '</div>';
					$html .= __wps__profile_link($mention->author).' '.__wps__time_ago($mention->dated).".<br />";
					$html .= $pre_text . "<a href='".$url."'>".str_replace('<br />', ' ', $text).'</a><br />';
					$content = strip_tags($mention->moretext);
					if ($content) {
						$maxlen = 500;
						if (strlen($content) > $maxlen) $content = substr($content, 0, $maxlen).'...';
						$html .= __wps__buffer(stripslashes($content));
					}
				$html .= '</div>';
			$html .= '</div>';

		}
	} else {

		$html .= __("Nothing to show, sorry.", WPS_TEXT_DOMAIN);
		
	}
		
	
	echo $html;

	exit;				
}

// Profile Avatar
if ($_POST['action'] == 'menu_avatar') {

	if (is_user_logged_in()) {

		$html = "";
		$uid1 = $_POST['uid1'];
		
		$html .= '<p>'.__('Choose an image...', WPS_TEXT_DOMAIN).' (';
		$html .= '<a id="symposium_remove_avatar" href="javascript:void(0)">'.__('or click here to remove', WPS_TEXT_DOMAIN).'</a>)';
		$html .= '</p>';
		
		include_once('../server/file_upload_include.php');
		$html .= show_upload_form(
			WP_CONTENT_DIR.'/wps-content/members/'.$current_user->ID.'/avatar_upload/', 
			WP_CONTENT_URL.'/wps-content/members/'.$current_user->ID.'/avatar_upload/',
			'avatar',
			__('Upload photo', WPS_TEXT_DOMAIN),
			0,
			0,
			0,
			$uid1,
			__wps__get_extension_button_style()
		);
		
		echo $html;
	}
	exit;				
}
				
// Show Settings
if ($_POST['action'] == 'menu_settings') {

	global $wpdb, $current_user;
	wp_get_current_user();

	if (is_user_logged_in()) {

		$html = "";
	
		$uid = $_POST['uid1'];
		
		if ($uid == $current_user->ID || __wps__get_current_userlevel($current_user->ID) == 5) {
		
			// get values
			$trusted = __wps__get_meta($uid, 'trusted');
			$profile_label = __wps__get_meta($uid, 'profile_label');
			$notify_new_messages = __wps__get_meta($uid, 'notify_new_messages');
			$notify_new_wall = __wps__get_meta($uid, 'notify_new_wall');
			$notify_likes = __wps__get_meta($uid, 'notify_likes');
			$forum_all = __wps__get_meta($uid, 'forum_all');
			$signature = __wps__get_meta($uid, 'signature');
			
			$user_info = get_userdata($uid);
	
			$html .= '<div id="symposium_settings_table">';

				// Trusted member (for example, for support staff)
				if (__wps__get_current_userlevel() == 5) {
					$html .= '<div style="border:1px solid #aaa; padding:6px 0 0 10px;margin-bottom:15px;">';
						$html .= '<div class="__wps__settings_row">';
						$html .= '<em>'.__('These options are only visible to site administrator.', WPS_TEXT_DOMAIN).'</em><br />';
						$html .= '</div>';
						$html .= '<div class="__wps__settings_row">';
						$html .= '<input type="checkbox" name="trusted" id="trusted"';
							if ($trusted == "on") { $html .= "CHECKED"; }
							$html .= '/> ';
							$html .= __('Is this member trusted (highlighted on forum)?', WPS_TEXT_DOMAIN);
						$html .= '</div>';
						$html .= '<div class="__wps__settings_row">';
						$html .= '<strong>'.__('Profile page header label', WPS_TEXT_DOMAIN).'</strong><br />';
						$html .= '<input type="text" name="profile_label" id="__wps__profile_label" class="input-field" style="width:300px" value="'.$profile_label.'" /> ';
						$html .= '</div>';
					$html .= '</div>';
				} else {
					$html .= '<input type="hidden" name="trusted" id="trusted" value="'.$trusted.'" />';
					$html .= '<input type="hidden" name="profile_label" id="__wps__profile_label" value="'.$profile_label.'" />';
				}

				// First name
				$html .= '<div id="symposium_settings_firstname" class="__wps__settings_row">';
					$html .= '<strong>'.__('Your first name', WPS_TEXT_DOMAIN).'</strong>';
					$html .= '<div>';
						$html .= '<input type="text" class="input-field" id="user_firstname" name="user_firstname" value="'.$user_info->user_firstname.'">';
					$html .= '</div>';
				$html .= '</div>';
			
				// Last name
				$html .= '<div id="symposium_settings_lastname" class="__wps__settings_row">';
					$html .= '<strong>'.__('Your last name', WPS_TEXT_DOMAIN).'</strong>';
					$html .= '<div>';
						$html .= '<input type="text" class="input-field" id="user_lastname" name="user_lastname" value="'.$user_info->user_lastname.'">';
					$html .= '</div>';
				$html .= '</div>';
			
				// Display name
				$html .= '<div id="symposium_settings_displayname" class="__wps__settings_row">';
					$html .= '<strong>'.__('Your name as shown', WPS_TEXT_DOMAIN).'</strong>';
					$html .= '<div>';
						$html .= '<input type="text" class="input-field" id="display_name" name="display_name" value="'.$user_info->display_name.'">';
						
						if (get_option(WPS_OPTIONS_PREFIX.'_tags') == "on" && !get_option(WPS_OPTIONS_PREFIX.'_wps_lite')) {
							$html .= '<br /><br />'.__('Your user tag is ', WPS_TEXT_DOMAIN).'<span id="symposium_tag" class="__wps__usertag">@'.strtolower(str_replace(' ', '', $user_info->display_name)).'</span>';
							$html .= '<div id="symposium_tag_info" style="display:none;">';
							$html .= __('When your @tag is clicked, the browser is taken to your profile page.', WPS_TEXT_DOMAIN).' ';
							$html .= __('Refer to others with @tags, using their display name (without spaces).', WPS_TEXT_DOMAIN);
							$html .= '</div>';
						}
						
					$html .= '</div>';
				$html .= '</div>';
			
				// Email address
				$html .= '<div id="symposium_settings_email" class="__wps__settings_row">';
					$html .= '<strong>'.__('Your email address', WPS_TEXT_DOMAIN).'</strong>';
					$html .= '<div>';
						$html .= '<input type="text" class="input-field" id="user_email" name="user_email" style="width:300px" value="'.$user_info->user_email.'">';
					$html .= '</div>';
				$html .= '</div>';
			
				// Signature (for forum)
				$html .= '<div id="symposium_settings_signature" class="__wps__settings_row">';
					$html .= '<strong>'.__('Forum signature', WPS_TEXT_DOMAIN).'</strong>';
					$html .= '<div>';
						$html .= '<input type="text" class="input-field" id="signature" name="signature" style="width:300px" value="'.str_replace("\\", "", $signature).'">';
					$html .= '</div>';
				$html .= '</div>';

				// Email notifications
				$html .= '<div id="symposium_settings_notify_new_messages" class="__wps__settings_row">';
					$html .= '<input type="checkbox" name="notify_new_messages" id="notify_new_messages"';
						if ($notify_new_messages == "on") { $html .= "CHECKED"; }
						$html .= '/> ';
						$html .= __('Receive an email when you get new mail messages?', WPS_TEXT_DOMAIN);
				$html .= '</div>';

				// Email wall
				$html .= '<div id="symposium_settings_notify_new_wall" class="__wps__settings_row">';
					$html .= '<input type="checkbox" name="notify_new_wall" id="notify_new_wall"';
						if ($notify_new_wall == "on") { $html .= "CHECKED"; }
						$html .= '/> ';
						$html .= sprintf(__('Receive an email when a %s adds a post?', WPS_TEXT_DOMAIN), get_option(WPS_OPTIONS_PREFIX.'_alt_friend'));
				$html .= '</div>';

				// Email wall likes/dislikes
				if (get_option(WPS_OPTIONS_PREFIX.'_activity_likes')) {
					$html .= '<div id="symposium_settings_notify_likes" class="__wps__settings_row">';
						$html .= '<input type="checkbox" name="notify_likes" id="notify_likes"';
							if ($notify_likes == "on") { $html .= "CHECKED"; }
							$html .= '/> ';
							$html .= __('Receive an email when you receive likes/dislikes?', WPS_TEXT_DOMAIN);
					$html .= '</div>';
				}
														
				// Email all forum activity (if allowed)
				if (get_option(WPS_OPTIONS_PREFIX.'_allow_subscribe_all') == "on") {
					if (function_exists('__wps__forum')) {
						$html .= '<div id="symposium_settings_forum_all" class="__wps__settings_row">';
							$html .= '<input type="checkbox" name="forum_all" id="forum_all"';
								if ($forum_all == "on") { $html .= "CHECKED"; }
								$html .= '/> ';
								$html .= __('Receive an email for all new forum topics and replies?', WPS_TEXT_DOMAIN).'<br />';
								$html .= '<a id="symposium_clear_all_subs" href="javascript:void(0);">'.__('Clear all existing forum subscriptions', WPS_TEXT_DOMAIN).'</a>';
						$html .= '</div>';
					}
				} else {
					if (get_option(WPS_OPTIONS_PREFIX.'_suppress_forum_notify') != "on") {
						$html .= '<div id="symposium_settings_forum_all" class="__wps__settings_row">';
							$html .= '<input type="hidden" name="forum_all" value="" />';
							$html .= '<a id="symposium_clear_all_subs" href="javascript:void(0);">'.__('Clear all existing forum subscriptions', WPS_TEXT_DOMAIN).'</a>';
						$html .= '</div>';
					}
				}
														
				// Password
				if (get_option(WPS_OPTIONS_PREFIX.'_enable_password') == "on") {
					$html .= '<div id="symposium_settings_password" class="__wps__settings_row">';
						$html .= '<div class="sep"></div>';
						$html .= '<div style="margin-bottom:15px; padding-top:15px;">';
							$html .= '<strong>'.__('Change your password', WPS_TEXT_DOMAIN).'</strong>';
							$html .= '<div>';
								$html .= '<input class="input-field" type="text" id="xyz1" name="xyz1" value="">';
							$html .= '</div>';
						$html .= '</div>';
						$html .= '<div style="clear:both">';
							$html .= __('Re-enter to confirm', WPS_TEXT_DOMAIN);
							$html .= '<div>';
								$html .= '<input class="input-field" type="text" id="xyz2" name="xyz2" value="">';
							$html .= '</div>';
						$html .= '</div>';
					$html .= '</div>';
															
				}

				// Filter for additional content
				// $uid = User settings relate to (normally same as current user, but may be admin)
				$html = apply_filters ( '__wps__menu_settings_filter', $html, $uid, $current_user->ID );

				// Hook for additional settings
				// $uid = User settings relate to (normally same as current user, but may be admin)
				do_action ( '__wps__menu_settings_hook', $uid, $current_user->ID );
				 
				$html .= '<br /><div class="__wps__settings_row">';
				$html .= '<input type="submit" id="updateSettingsButton" name="Submit" class="__wps__button" style="'.__wps__get_extension_button_style().'" value="'.__('Save', WPS_TEXT_DOMAIN).'" /> ';
				$html .= '</div>';

			$html .= '</div>';

		}
	
		echo $html;
		
	}
	exit;
	
}

// Update Settings
if ($_POST['action'] == 'updateSettings') {

	global $wpdb, $current_user;

	if (is_user_logged_in()) {
	
		$uid = $_POST['uid'];
		$notify_new_messages = $_POST['notify_new_messages'];
		$notify_new_wall = $_POST['notify_new_wall'];
		$forum_all = $_POST['forum_all'];
		$signature = $_POST['signature'];
		$password1 = $_POST['xyz1'];
		$password2 = $_POST['xyz2'];
		$user_firstname = $_POST['user_firstname'];
		$user_lastname = $_POST['user_lastname'];
		$display_name = $_POST['display_name'];
		$user_email = $_POST['user_email'];
		$trusted = $_POST['trusted'];
		$profile_label = $_POST['profile_label'];
		if (get_option(WPS_OPTIONS_PREFIX.'_activity_likes')) $notify_likes = $_POST['notify_likes'];

		// check that email address is valid
		if (is_email($user_email)) {
			
			__wps__update_meta($uid, 'notify_new_messages', "'".$notify_new_messages."'");
			__wps__update_meta($uid, 'forum_all', "'".$forum_all."'");
			__wps__update_meta($uid, 'signature', "'".addslashes(strip_tags($signature))."'");
			__wps__update_meta($uid, 'notify_new_wall', "'".$notify_new_wall."'");
			__wps__update_meta($uid, 'trusted', "'".$trusted."'");
			__wps__update_meta($uid, 'profile_label', "'".$profile_label."'");
			
			if (get_option(WPS_OPTIONS_PREFIX.'_activity_likes')) __wps__update_meta($uid, 'notify_likes', "'".$notify_likes."'");

			// Update firstname and lastname
			wp_update_user( array ('ID' => $uid, 'first_name' => $user_firstname, 'last_name' => $user_lastname) );
		
			$pwmsg = 'OK';
			$display_name_exists = $wpdb->get_var($wpdb->prepare("SELECT display_name FROM ".$wpdb->base_prefix."users WHERE ID != %d AND (replace(lower(display_name), ' ', '') = %s OR lower(display_name) = %s)", $uid, str_replace(' ', '', strtolower($display_name)), strtolower($display_name)));
			if ( get_option(WPS_OPTIONS_PREFIX."_unique_display_name") && $display_name_exists ) {
				$pwmsg = __("Display name (".$display_name.") is not available, it must be unique, sorry.", WPS_TEXT_DOMAIN);
			} else {
				$rows_affected = $wpdb->update( $wpdb->base_prefix.'users', array( 'display_name' => stripslashes($display_name) ), array( 'ID' => $uid ), array( '%s' ), array( '%d' ) );			
			}
			
			$email_exists = $wpdb->get_row("SELECT ID, user_email FROM ".$wpdb->base_prefix."users WHERE lower(user_email) = '".strtolower($user_email)."'");
			if ($email_exists && $email_exists->user_email == $user_email && $email_exists->ID != $current_user->ID && __wps__get_current_userlevel($current_user->ID) < 5) {
		    	$pwmsg = __("Email already exists, sorry.".$email_exists->ID, "wp-symposium");				
			} else {
				$rows_affected = $wpdb->update( $wpdb->base_prefix.'users', array( 'user_email' => $user_email ), array( 'ID' => $uid ), array( '%s' ), array( '%d' ) );
			}
				
			if ($password1 != '') {
				if ($password1 == $password2) {
					$pwd = wp_hash_password($password1);
					$sql = "UPDATE ".$wpdb->base_prefix."users SET user_pass = '%s' WHERE ID = %d";
				    if ($wpdb->query( $wpdb->prepare($sql, $pwd, $uid) ) ) {
	
	
						$sql = "SELECT user_login FROM ".$wpdb->base_prefix."users WHERE ID = %d";
						$username = $wpdb->get_var($wpdb->prepare($sql, $uid));

						$id = $uid;
						$url = __wps__get_url('profile')."?view=settings&msg=".$pwmsg;
	
				    	wp_login($username, $pwd, true);
				        wp_setcookie($username, $pwd, true);
				        wp_set_current_user($id, $username);
			    	
						$pwmsg = "PASSWORD CHANGED";										
					
				    } else {
				    	$pwmsg = __("Failed to update password, sorry.", WPS_TEXT_DOMAIN);
				    }
				} else {
			    	$pwmsg = __("Passwords different, please try again.", WPS_TEXT_DOMAIN);
				}
			}
			
			echo $pwmsg;
			
		} else {
			
			echo __("Invalid email address, please re-enter", WPS_TEXT_DOMAIN);
			
		}
		
	} else {
		
		echo "NOT LOGGED IN";
		
	}
	
	exit;
	
}
	
// Show Personal
if ($_POST['action'] == 'menu_personal') {

	global $wpdb, $current_user;
	wp_get_current_user();

	if (is_user_logged_in()) {	

		$uid = $_POST['uid1'];

		$html = "";

		if ($uid == $current_user->ID || __wps__get_current_userlevel($current_user->ID) == 5) {
		
			// get values
			$dob_day = __wps__get_meta($uid, 'dob_day');
			$dob_month = __wps__get_meta($uid, 'dob_month');
			$dob_year = __wps__get_meta($uid, 'dob_year');
			$city = __wps__get_meta($uid, 'extended_city');
			$country = __wps__get_meta($uid, 'extended_country');
			$share = __wps__get_meta($uid, 'share');
			$wall_share = __wps__get_meta($uid, 'wall_share');
			if (function_exists('__wps__rss_main')) {
				$rss_share = __wps__get_meta($uid, 'rss_share');
			} else {
				$rss_share = '';
			}
			$chat_sound = __wps__get_meta($uid, 'chat_sound');
	
			$html .= '<input type="hidden" name="symposium_update" value="P">';
			$html .= '<input type="hidden" name="uid" value="'.$uid.'">';

			$html .= '<div id="symposium_settings_table">';
	
				// Sharing personal information
				$html .= '<div class="__wps__settings_row">';
					$html .= '<strong>'.__('Who do you want to share personal information with?', WPS_TEXT_DOMAIN).'</strong>';
					$html .= '<div>';
						$html .= '<select id="share" name="share">';
							$html .= "<option value='Nobody'";
								if ($share == 'Nobody') { $html .= ' SELECTED'; }
								$html .= '>'.__('Nobody', WPS_TEXT_DOMAIN).'</option>';
							$html .= "<option value='Friends only'";
								if ($share == 'Friends only') { $html .= ' SELECTED'; }
								$html .= '>'.sprintf(__('%s Only', WPS_TEXT_DOMAIN), get_option(WPS_OPTIONS_PREFIX.'_alt_friends')).'</option>';
							$html .= "<option value='Everyone'";
								if ($share == 'Everyone') { $html .= ' SELECTED'; }
								$html .= '>'.stripslashes(get_option(WPS_OPTIONS_PREFIX.'_alt_everyone')).'</option>';
							$html .= "<option value='public'";
								if ($share == 'public') { $html .= ' SELECTED'; }
								$html .= '>'.__('Public', WPS_TEXT_DOMAIN).'</option>';
						$html .= '</select>';
					$html .= '</div>';
				$html .= '</div>';
		
				// Sharing wall
				$html .= '<div class="__wps__settings_row">';
					$html .= '<strong>'.__('Who do you want to share your activity with?', WPS_TEXT_DOMAIN).'</strong>';
					$html .= '<div>';
						$html .= '<select id="wall_share" name="wall_share">';
							$html .= "<option value='Nobody'";
								if ($wall_share == 'Nobody') { $html .= ' SELECTED'; }
								$html .= '>'.__('Nobody', WPS_TEXT_DOMAIN).'</option>';
							$html .= "<option value='Friends only'";
								if ($wall_share == 'Friends only') { $html .= ' SELECTED'; }
								$html .= '>'.sprintf(__('%s Only', WPS_TEXT_DOMAIN), get_option(WPS_OPTIONS_PREFIX.'_alt_friends')).'</option>';
							$html .= "<option value='Everyone'";
								if ($wall_share == 'Everyone') { $html .= ' SELECTED'; }
								$html .= '>'.stripslashes(get_option(WPS_OPTIONS_PREFIX.'_alt_everyone')).'</option>';
							$html .= "<option value='public'";
								if ($wall_share == 'public') { $html .= ' SELECTED'; }
								$html .= '>'.__('Public', WPS_TEXT_DOMAIN).'</option>';
						$html .= '</select>';
					$html .= '</div>';
				$html .= '</div>';

				// Publish RSS feed?
				if (function_exists('__wps__rss_main')) {
					$html .= '<div class="__wps__settings_row">';
						$html .= '<strong>'.__('Publish your activity via RSS (only your initial posts)?', WPS_TEXT_DOMAIN).'</strong>';
						$html .= '<div>';
							$html .= '<select id="rss_share" name="rss_share">';
								$html .= "<option value=''";
									if ($rss_share == '') { $html .= ' SELECTED'; }
									$html .= '>'.__('No', WPS_TEXT_DOMAIN).'</option>';
								$html .= "<option value='on'";
									if ($rss_share == 'on') { $html .= ' SELECTED'; }
									$html .= '>'.__('Yes', WPS_TEXT_DOMAIN).'</option>';
							$html .= '</select>';
						$html .= '</div>';
					$html .= '</div>';
				} else {
					$html .= '<input type="hidden" id="rss_share" value="">';
				}
				
				// Panel/chat sound alert choice
				if (function_exists('__wps__add_notification_bar') && get_option(WPS_OPTIONS_PREFIX.'_use_chat') == "on") {
					$html .= '<div class="__wps__settings_row">';
						$html .= '<strong>'.__('Sound for new chat messages? (refresh browser after changing)', WPS_TEXT_DOMAIN).'</strong>';
						$html .= '<div>';
							$html .= '<select id="chat_sound" name="chat_sound">';
								$html .= "<option value='none'";
									if ($chat_sound == 'none') { $html .= ' SELECTED'; }
									$html .= '>'.__('No sound', WPS_TEXT_DOMAIN).'</option>';
								$html .= "<option value='Pop.mp3'";
									if (!$chat_sound || $chat_sound == 'Pop.mp3') { $html .= ' SELECTED'; }
									$html .= '>'.__('Pop', WPS_TEXT_DOMAIN).'</option>';

								foreach (glob(WPS_PLUGIN_DIR.'/ajax/chat/flash/*.mp3') as $filename) {
							      	if (str_replace(WPS_PLUGIN_DIR.'/ajax/chat/flash/', '', $filename) != "Pop.mp3") {
										$html .= "<option value='".str_replace(WPS_PLUGIN_DIR.'/ajax/chat/flash/', '', $filename)."'";
										if ($chat_sound == str_replace(WPS_PLUGIN_DIR.'/ajax/chat/flash/', '', $filename)) { $html .= ' SELECTED'; }
											$html .= '>'.str_replace('_', ' ', str_replace('.mp3', '', str_replace(WPS_PLUGIN_DIR.'/ajax/chat/flash/', '', $filename))).'</option>';
								    }
								}
	
							$html .= '</select>';
						$html .= '</div>';
					$html .= '</div>';
				}
						
				// Birthday
				if (get_option(WPS_OPTIONS_PREFIX.'_show_dob') == 'on') {
				
					$html .= '<div class="__wps__settings_row">';
						$html .= '<strong>'.__('Your date of birth', WPS_TEXT_DOMAIN).'</strong>';
						$html .= '<div>';
							$html .= "<select id='dob_day' name='dob_day'>";
								$html .= "<option value=0";
									if ($dob_day == 0) { $html .= ' SELECTED'; }
									$html .= '>---</option>';
								for ($i = 1; $i <= 31; $i++) {
									$html .= "<option value='".$i."'";
										if ($dob_day == $i) { $html .= ' SELECTED'; }
										$html .= '>'.$i.'</option>';
								}
							$html .= '</select> / ';									
							$html .= "<select id='dob_month' name='dob_month'>";
								$html .= "<option value=0";
									if ($dob_month == 0) { $html .= ' SELECTED'; }
									$html .= '>---</option>';
								for ($i = 1; $i <= 12; $i++) {
									switch($i) {									
										case 1:$monthname = __("January", WPS_TEXT_DOMAIN);break;
										case 2:$monthname = __("February", WPS_TEXT_DOMAIN);break;
										case 3:$monthname = __("March", WPS_TEXT_DOMAIN);break;
										case 4:$monthname = __("April", WPS_TEXT_DOMAIN);break;
										case 5:$monthname = __("May", WPS_TEXT_DOMAIN);break;
										case 6:$monthname = __("June", WPS_TEXT_DOMAIN);break;
										case 7:$monthname = __("July", WPS_TEXT_DOMAIN);break;
										case 8:$monthname = __("August", WPS_TEXT_DOMAIN);break;
										case 9:$monthname = __("September", WPS_TEXT_DOMAIN);break;
										case 10:$monthname = __("October", WPS_TEXT_DOMAIN);break;
										case 11:$monthname = __("November", WPS_TEXT_DOMAIN);break;
										case 12:$monthname = __("December", WPS_TEXT_DOMAIN);break;
									}
									$html .= "<option value='".$i."'";
										if ($dob_month == $i) { $html .= ' SELECTED'; }
										$html .= '>'.$monthname.'</option>';
								}
							$html .= '</select> / ';									
							$html .= "<select id='dob_year' name='dob_year'>";
								$html .= "<option value=0";
									if ($dob_year == 0) { $html .= ' SELECTED'; }
									$html .= '>---</option>';
								for ($i = date("Y"); $i >= 1900; $i--) {
									$html .= "<option value='".$i."'";
										if ($dob_year == $i) { $html .= ' SELECTED'; }
										$html .= '>'.$i.'</option>';
								}
							$html .= '</select>';									
						$html .= '</div>';
					$html .= '</div>';
			
				} else {
				
					$html .= '<input type="hidden" id="dob_day" value="'.$dob_day.'">';
					$html .= '<input type="hidden" id="dob_month" value="'.$dob_month.'">';
					$html .= '<input type="hidden" id="dob_year" value="'.$dob_year.'">';

				}
			
				// City
				if (!get_option(WPS_OPTIONS_PREFIX.'_hide_location')) {
					$html .= '<div class="__wps__settings_row">';
						$html .= '<strong>'.__('Which town/city are you in?', WPS_TEXT_DOMAIN).'</strong>';
						$html .= '<div>';
							$html .= '<input type="text" id="city" name="city" value="'.$city.'">';
						$html .= '</div>';
					$html .= '</div>';
			
					// Country
					$html .= '<div class="__wps__settings_row">';
						$html .= '<strong>'.__('Which country are you in?', WPS_TEXT_DOMAIN).'</strong>';
						$html .= '<div>';
							$html .= '<input type="text" id="country" name="country" value="'.$country.'">';
						$html .= '</div>';
					$html .= '</div>';
				} else {
					$html .= '<input type="hidden" id="city" name="city" value="">';
					$html .= '<input type="hidden" id="country" name="country" value="">';
					if (WPS_DEBUG) $html .= '<div class="__wps__settings_row">City/Country disabled</div>';
				}
				
				if (WPS_DEBUG) {
					$html .= '<div class="__wps__settings_row">';
						$html .= '<strong>'.__('Latitude/Longitude (from Google API)', WPS_TEXT_DOMAIN).'</strong>';
						$html .= '<div>';
							$html .= __wps__get_meta($uid, 'plus_lat').'/'.__wps__get_meta($uid, 'plus_long');
						$html .= '</div>';
					$html .= '</div>';					
				}
		
				// Extensions
				$extensions = $wpdb->get_results("SELECT * FROM ".$wpdb->base_prefix."symposium_extended ORDER BY extended_order, extended_name");

				if ($extensions) {
				
					foreach ($extensions as $extension) {
						
						// Complete default values if not yet set
						$value = stripslashes($extension->extended_default);
						if ($extension->extended_type == "List") {
							$sql = "SELECT meta_value FROM ".$wpdb->base_prefix."usermeta WHERE user_id = %d and meta_key = 'symposium_extended_".$extension->extended_slug."'";
							if ($listitem = $wpdb->get_row($wpdb->prepare($sql, $uid))) {
								$value = stripslashes($listitem->meta_value);
							} else {
								$tmp = explode(',', $extension->extended_default);
								$value = trim($tmp[0]);
								__wps__update_meta($uid, 'extended_'.$extension->extended_slug, stripslashes($value));
							}
						
						}
										
						if ($extension->extended_type == "Checkbox") {
							$sql = "SELECT meta_value FROM ".$wpdb->base_prefix."usermeta WHERE user_id = %d and meta_key = 'symposium_extended_".$extension->extended_slug."'";
							if ($checkbox = $wpdb->get_row($wpdb->prepare($sql, $uid))) {
								$value = stripslashes($checkbox->meta_value);
							} else {
								__wps__update_meta($uid, 'extended_'.$extension->extended_slug, stripslashes($value));
							}
						}

						if ($extension->extended_type == "Text" || $extension->extended_type == "Textarea") {
							$sql = "SELECT meta_value FROM ".$wpdb->base_prefix."usermeta WHERE user_id = %d and meta_key = 'symposium_extended_".$extension->extended_slug."'";
							if ($text = $wpdb->get_row($wpdb->prepare($sql, $uid))) {
								$value = stripslashes($text->meta_value);
							} else {
								__wps__update_meta($uid, 'extended_'.$extension->extended_slug, stripslashes($value));
							}
						}
						
						// Draw objects according to type
						$html .= '<div class="__wps__settings_row">';
						
						$html .= '<strong>'.stripslashes($extension->extended_name).'</strong>';
							$html .= '<input type="hidden" name="eid[]" value="'.$extension->eid.'">';
							$html .= '<input type="hidden" name="extended_name[]" value="'.$extension->extended_slug.'">';
							if ($extension->extended_type != 'Checkbox') { $html .= '<div>'; }
								if ($extension->extended_type == 'Textarea') {
								if ($extension->readonly) {
									$html .= stripslashes($value);
								} else {
									$html .= '<textarea title="'.$extension->eid.'" class="eid_value profile_textarea" name="extended_value[]">'.stripslashes($value).'</textarea>';
								}
							}
							if ($extension->extended_type == 'Text') {
								if ($extension->readonly) {
									$html .= stripslashes($value);
								} else {
									$html .= '<input title="'.$extension->eid.'" class="eid_value" type="text" name="extended_value[]" value="'.stripslashes($value).'">';
								}
							}
							if ($extension->extended_type == 'Checkbox') {
								
								if ($extension->readonly) {
									if ($value == 'on') { 
										$html .= "<br /><img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/tick.png' /> "; 
									} else {
										$html .= "<br /><img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/cross.png' /> "; 
									}
								} else {
									$html .= '<input title="'.$extension->eid.'" class="eid_value" type="checkbox" name="extended_value[]"';
									if ($value) $html .= ' CHECKED';
									$html .= ' />';
								}
							}
							if ($extension->extended_type == 'List') {
								if ($extension->readonly) {
									$html .= trim($value);
								} else {
									$html .= '<select title="'.$extension->eid.'" class="eid_value" name="extended_value[]">';
									$items = explode(',', $extension->extended_default);
									foreach ($items as $item) {
										$html .= '<option value="'.trim($item).'"';
											if ($value == trim($item)) { $html .= " SELECTED"; }
											$html .= '>'.stripslashes(trim($item)).'</option>';
									}												
									$html .= '</select>';
								}
							}
							$html .= '</div>';
						$html .= '</div>';
					}
				}
				
				// Hook to add anything at the bottom of the personal page
				$html = apply_filters ( '__wps__add_wps_personal_filter', $html, $uid, $current_user->ID );
			
			$html .= '</div> ';
	 
			$html .= '<br /><p class="submit"> ';
				$html .= '<input type="submit" id="updatePersonalButton" name="Submit" class="__wps__button" style="'.__wps__get_extension_button_style().'" value="'.__('Save', WPS_TEXT_DOMAIN).'" /> ';
			$html .= '</p>';
		
		}
	
		echo $html;
		
	}
	exit;
	
}


// personal updates
if ($_POST['action'] == 'updatePersonal') {

	global $wpdb, $current_user;
	
	if (is_user_logged_in()) {

		$uid = $_POST['uid'];
		$dob_day = $_POST['dob_day'];
		$dob_month = $_POST['dob_month'];
		$dob_year = $_POST['dob_year'];
		$city = $_POST['city'];
		$country = $_POST['country'];
		$share = $_POST['share'];
		$wall_share = $_POST['wall_share'];
		$rss_share = $_POST['rss_share'];
		$extended = addslashes($_POST['extended']);
		$chat_sound = isset($_POST['chat_sound']) ? $_POST['chat_sound'] : '';
		
		$lat = __wps__get_meta($uid, 'plus_lat');
		$lng = __wps__get_meta($uid, 'plus_lng');
		$current_city = __wps__get_meta($uid, 'extended_city');
		$current_country = __wps__get_meta($uid, 'extended_country');
		
		__wps__update_meta($uid, 'dob_day', $dob_day);
		__wps__update_meta($uid, 'dob_month', $dob_month);
		__wps__update_meta($uid, 'dob_year', $dob_year);
		__wps__update_meta($uid, 'extended_city', "'".$city."'");
		__wps__update_meta($uid, 'extended_country', "'".$country."'");
		__wps__update_meta($uid, 'share', "'".$share."'");
		__wps__update_meta($uid, 'wall_share', "'".$wall_share."'");
		__wps__update_meta($uid, 'rss_share', "'".$rss_share."'");
		__wps__update_meta($uid, 'chat_sound', "'".$chat_sound."'");

		// Handle city and country
		if ($current_city == '' && $current_country == '') {

			__wps__update_meta($uid, 'plus_lat', $lat);
			__wps__update_meta($uid, 'plus_long', $lng);	
					
		} else {
				
			if ($current_city != $city || $current_country != $country || $lat == 0) {
			// get lat and long from Google API (if Profile Plus installed and internet connection available)
				if (function_exists('__wps__profile_plus') && $city != '' && $country != '') {
					$city = str_replace(' ','%20',$city);
					$country = str_replace(' ','%20',$country);
	
					$fgc = 'http://maps.googleapis.com/maps/api/geocode/json?address='.$city.'+'.$country.'&sensor=false';
			
					if ($json = @file_get_contents($fgc) ) {
						if (WPS_DEBUG) echo "Connect URL to Google API with: ".$fgc.", ";
						$json_output = json_decode($json, true);
						$lat = $json_output['results'][0]['geometry']['location']['lat'];
						$lng = $json_output['results'][0]['geometry']['location']['lng'];
						if (WPS_DEBUG) echo "Google results: ".$lat."/".$lng.", ";
		
						__wps__update_meta($uid, 'plus_lat', $lat);
						__wps__update_meta($uid, 'plus_long', $lng);					
					}
				}
			}
		}
		
		// Update user meta
		$rows = explode('[|]', $extended);
		if ($rows) {
			foreach ($rows as $row) {
				
				if ($row != '') {
					
					$fields = explode('[]', $row);
					$eid = $fields[0];
					$value = $fields[1];
					$value = ($value === false || $value == 'false') ? '' : $value;
					$value = ($value === true || $value == 'true') ? 'on' : $value;

					$sql = "SELECT extended_slug, extended_type, wp_usermeta FROM ".$wpdb->base_prefix."symposium_extended WHERE eid = %d";
					$extension = $wpdb->get_row($wpdb->prepare($sql, $eid));
					__wps__update_meta($uid, 'extended_'.$extension->extended_slug, $value);
				}

			}
		}
		
		// Hook to save items from the personal page:
		do_action ( 'symposium_save_wps_personal_hook', $_POST );
		
		echo 'OK';
					
	} else {
		echo "NOT LOGGED IN";
	}
	
	exit;
}


// Show Groups
if ($_POST['action'] == 'menu_groups') {

	global $wpdb, $current_user;

	$uid = $_POST['uid1'];
	$share = __wps__get_meta($uid, 'wall_share');

	$html = "<p class='__wps__profile_heading'>".__('Groups', WPS_TEXT_DOMAIN)."</p>";

	if (is_user_logged_in() || $share == 'public') {


		$group_url = __wps__get_url('group');
		$q = __wps__string_query($group_url);
	
		$sql = "SELECT m.*, g.*, (SELECT COUNT(*) FROM ".$wpdb->prefix."symposium_group_members WHERE group_id = g.gid) AS member_count  
		FROM ".$wpdb->prefix."symposium_group_members m 
		LEFT JOIN ".$wpdb->prefix."symposium_groups g ON m.group_id = g.gid 
		WHERE m.member_id = %d";
		
		$groups = $wpdb->get_results($wpdb->prepare($sql, $uid));	
		
		if ($groups) {
			foreach ($groups as $group) {	

				$html .= "<div class='groups_row row corners' style='width:90%'>";
					
					$html .= "<div class='groups_info'>";
	
						$html .= "<div class='groups_avatar'>";
							$html .= __wps__get_group_avatar($group->gid, 64);
						$html .= "</div>";

						$html .= "<div class='group_name'>";
						$html .= "<a href='".$group_url.$q."gid=".$group->gid."'>".stripslashes($group->name)."</a>";
						$html .= "</div>";
						
						$html .= "<div class='group_member_count'>";
						$html .= __("Member Count:", WPS_TEXT_DOMAIN)." ".$group->member_count;
						if ($group->last_activity) {
							$html .= '<br /><em>'.__('last active', WPS_TEXT_DOMAIN).' '.__wps__time_ago($group->last_activity).".</em>";
						}
						$html .= "</div>";
						
					$html .= "</div>";
					
				$html .= "</div>";
				
			}
		} else {
			$html .= __("Not a member of any groups.", WPS_TEXT_DOMAIN);
		}

		echo $html;
		
	} else {

		echo __wps__show_login_link(__("You need to be <a href='%s'>logged in</a> to view this member's groups.", WPS_TEXT_DOMAIN));

	}
	exit;
	
}

// Show Friends
if ($_POST['action'] == 'menu_friends') {

	$uid1 = $_POST['uid1'];

	$share = __wps__get_meta($uid1, 'wall_share');

	if (is_user_logged_in() || $share == 'public') {

		$limit_from = $_POST['limit_from'];

		$html = "<p class='__wps__profile_heading'>".get_option(WPS_OPTIONS_PREFIX.'_alt_friends')."</p>";
		$html .= __wps__profile_friends($uid1, $limit_from);
	
		echo $html;
	}
	exit;
	
}
										
// AJAX function to delete a post
if ($_POST['action'] == 'deletePost') {

	global $wpdb, $current_user;
	wp_get_current_user();

	if (is_user_logged_in()) {

		$cid = $_POST['cid'];
		$uid = $current_user->ID;

		if ( __wps__safe_param($cid) && __wps__safe_param($uid) ) {
		
			if ( __wps__get_current_userlevel($uid) == 5 ) {
				$sql = "SELECT * FROM ".$wpdb->base_prefix."symposium_comments WHERE cid = %d";
				$post = $wpdb->get_row($wpdb->prepare($sql, $cid));
				$sql = "DELETE FROM ".$wpdb->base_prefix."symposium_comments WHERE cid = %d";
				$rows_affected = $wpdb->query( $wpdb->prepare($sql, $cid) );
			} else {
				$sql = "SELECT * FROM ".$wpdb->base_prefix."symposium_comments WHERE cid = %d AND (subject_uid = %d OR author_uid = %d)";
				$post = $wpdb->get_row($wpdb->prepare($sql, $cid, $uid, $uid));
				$sql = "DELETE FROM ".$wpdb->base_prefix."symposium_comments WHERE cid = %d AND (subject_uid = %d OR author_uid = %d)";
				$rows_affected = $wpdb->query( $wpdb->prepare($sql, $cid, $uid, $uid) );
			}
			
			if ($post) {
				// Hook for other actions to take place
				do_action('symposium_profile_deletepost_hook', $post->subject_uid, $post->author_uid, $cid, $post->comment);	
			}

			if ( $rows_affected > 0 ) {

				// Delete any replies
				$sql = "DELETE FROM ".$wpdb->base_prefix."symposium_comments WHERE comment_parent = %d";
				$rows_affected = $wpdb->query( $wpdb->prepare($sql, $cid) );

				echo "#".$cid;
			} else {
				echo "FAILED TO DELETE ".$wpdb->last_query;
			}
		
		} else {
			echo "FAIL, INVALID PARAMETERS (".$uid.":".$cid.")";
		}
		
	}

	exit;
}



// Delete friendship
if ($_POST['action'] == 'deleteFriend') {

	global $wpdb, $current_user;

	if (is_user_logged_in()) {

		$friend = $_POST['friend'];
		
		$sql = "DELETE FROM ".$wpdb->base_prefix."symposium_friends WHERE (friend_from = %d AND friend_to = %d) OR (friend_to = %d AND friend_from = %d)";
		if (__wps__safe_param($friend)) {
			$wpdb->query( $wpdb->prepare( $sql, $friend, $current_user->ID, $friend, $current_user->ID ) );	
		}

		// Hook for further actions
		do_action('symposium_friend_removed_hook', $current_user->ID, $friend);	

	
		echo $friend;
		
	} else {
		echo "NOT LOGGED IN";
	}
	exit;
}	

// Friend request made
if ($_POST['action'] == 'addFriend') {

	global $wpdb, $current_user;

	if (is_user_logged_in()) {
	
		$friend_from = $current_user->ID;
		$friend_to = $_POST['friend_to'];;					
		$friend_message = $_POST['friend_message'];
		
		// delete any friendship between these two people first
		$sql = "DELETE FROM ".$wpdb->base_prefix."symposium_friends WHERE ((friend_from = %d AND friend_to = %d) OR (friend_to = %d AND friend_from = %d))";
		$wpdb->query($wpdb->prepare($sql, $friend_to, $current_user->ID, $friend_to, $current_user->ID));

		// add friendship request
		$wpdb->query( $wpdb->prepare( "
			INSERT INTO ".$wpdb->base_prefix."symposium_friends
			( 	friend_from, 
				friend_to,
				friend_timestamp,
				friend_message,
				friend_accepted
			)
			VALUES ( %d, %d, %s, %s, %s )", 
	        array(
	        	$friend_from, 
	        	$friend_to,
	        	date("Y-m-d H:i:s"),
	        	$friend_message,
				''
	        	) 
	        ) );


		// Filter to allow further actions to take place
		apply_filters ('__wps__friendrequest_filter', $friend_to, $friend_from, $current_user->display_name);		
		
		// send email
		$sql = "SELECT user_email FROM ".$wpdb->base_prefix."users WHERE ID = %d";
		$friend_to = $wpdb->get_var($wpdb->prepare($sql, $friend_to));
		
		$body .= "<h1>".sprintf(__("%s request", WPS_TEXT_DOMAIN), get_option(WPS_OPTIONS_PREFIX.'_alt_friend'))."</h1>";
		$body .= "<p>".sprintf(__("You have received a %s request from %s", WPS_TEXT_DOMAIN), get_option(WPS_OPTIONS_PREFIX.'_alt_friend'), $current_user->display_name)."</p>";
		$body .= "<p>".$friend_message."</p>";
		
		$profile_url = __wps__get_url('profile');
		$profile_url .= __wps__string_query($profile_url)."view=friends";
		$body .= "<p>".__("Go to", WPS_TEXT_DOMAIN)." <a href='".$profile_url."'>".get_bloginfo('name')."</a>...</p>";	
			
		if (__wps__sendmail($friend_to, sprintf(__("%s request", WPS_TEXT_DOMAIN), get_option(WPS_OPTIONS_PREFIX.'_alt_friend')), $body)) {
			$r = "OK";
		} else {
			$r = "Failed to email:".$friend_to.'.';
		}

		echo $r;		
	} else {
		echo "NOT LOGGED IN";
	}
	
	exit;
	
}

// Is someone cancelling friend request
if ($_POST['action'] == 'cancelFriend') {

	global $wpdb, $current_user;

	if (is_user_logged_in()) {

		$friend_to = $_POST['friend_to'];		
		$friend_from = $current_user->ID;
		
		if (__wps__safe_param($friend_to)) {

			$wpdb->query( $wpdb->prepare( "DELETE FROM ".$wpdb->base_prefix."symposium_friends WHERE (friend_from = ".$friend_from." AND friend_to = ".$friend_to.") OR (friend_from = ".$friend_to." AND friend_to = ".$friend_from.")" ) );	

		}
		
		echo "OK";		
	} else {
		echo "NOT LOGGED IN";
	}

	exit;
	
}

// Rejected friendship
if ($_POST['action'] == 'rejectFriend') {

	global $wpdb, $current_user;

	if (is_user_logged_in()) {

		$friend_to = $_POST['friend_to'];		
		$friend_from = $current_user->ID;

		$sql = "DELETE FROM ".$wpdb->base_prefix."symposium_friends WHERE (friend_from = %d AND friend_to = %d) OR (friend_to = %d AND friend_from = %d)";
		if (__wps__safe_param($friend_to)) {
			$wpdb->query( $wpdb->prepare( $sql, $friend_to, $current_user->ID, $friend_to, $current_user->ID ) );	
		}

		echo $friend_to;		
	} else {
		echo "NOT LOGGED IN";
	}
	
	exit;
}

// Accepted friendship
if ($_POST['action'] == 'acceptFriend') {

	global $wpdb, $current_user;

	if (is_user_logged_in()) {

		$friend_from = $current_user->ID;
		$friend_to = $_POST['friend_to'];		
	
		// Check to see if already a friend
		$sql = "SELECT COUNT(*) FROM ".$wpdb->base_prefix."symposium_friends WHERE friend_accepted = 'on' AND ((friend_from = %d AND friend_to = %d) OR (friend_to = %d AND friend_from = %d))";
		$already_a_friend = $wpdb->get_var( $wpdb->prepare ($sql, $friend_to, $current_user->ID, $friend_to, $current_user->ID));
		if ($already_a_friend >= 1) {
			// already a friend
		} else {
		
			// Delete pending request
			$sql = "DELETE FROM ".$wpdb->base_prefix."symposium_friends WHERE (friend_from = %d AND friend_to = %d) OR (friend_to = %d AND friend_from = %d)";
			if (__wps__safe_param($friend_from)) {
				$wpdb->query( $wpdb->prepare( $sql, $friend_to, $current_user->ID, $friend_to, $current_user->ID ) );	
			}
			
			// Add the two friendship rows
			$wpdb->query( $wpdb->prepare( "
				INSERT INTO ".$wpdb->base_prefix."symposium_friends
				( 	friend_from, 
					friend_to,
					friend_timestamp,
					friend_accepted,
					friend_message
				)
				VALUES ( %d, %d, %s, %s, %s )", 
		        array(
		        	$current_user->ID, 
		        	$friend_to,
		        	date("Y-m-d H:i:s"),
		        	'on',
				''
		        	) 
		        ) );
			$wpdb->query( $wpdb->prepare( "
				INSERT INTO ".$wpdb->base_prefix."symposium_friends
				( 	friend_to, 
					friend_from,
					friend_timestamp,
					friend_accepted,
					friend_message
				)
				VALUES ( %d, %d, %s, %s, %s )", 
		        array(
		        	$current_user->ID, 
		        	$friend_to,
		        	date("Y-m-d H:i:s"),
		        	'on',
				''
		        	) 
		        ) );

			// Filter to allow further actions to take place
			apply_filters ('__wps__friendaccepted_filter', $friend_to, $current_user->ID, $current_user->display_name);		

			// send email
			$friend_to_email = $wpdb->get_var($wpdb->prepare("SELECT user_email FROM ".$wpdb->base_prefix."users WHERE ID = %d", $friend_to));
			
			$body = "<h1>".sprintf(__("%s request accepted", WPS_TEXT_DOMAIN), get_option(WPS_OPTIONS_PREFIX.'_alt_friend'))."</h1>";
			$body .= "<p>".sprintf(__("Your %s request to %s has been accepted", WPS_TEXT_DOMAIN), get_option(WPS_OPTIONS_PREFIX.'_alt_friend'), $current_user->display_name)."</p>";
			
			$profile_url = __wps__get_url('profile');
			$profile_url .= __wps__string_query($profile_url)."uid=".$current_user->ID."&view=friends";
			$body .= "<p>".__("Go to", WPS_TEXT_DOMAIN)." <a href='".$profile_url."'>".get_bloginfo('name')."</a>...</p>";
			
			__wps__sendmail($friend_to_email, sprintf(__("%s request accepted", WPS_TEXT_DOMAIN), get_option(WPS_OPTIONS_PREFIX.'_alt_friend')), $body);
			
			// Tell friends
			if (!get_option(WPS_OPTIONS_PREFIX.'_wps_lite') && __wps__is_plus()) {
				$userdata = get_userdata($friend_to);
				$profile_url = __wps__get_url('profile');
				$profile_url .= __wps__string_query($profile_url);
				$post = __('Has made friends with', WPS_TEXT_DOMAIN).' <a href="'.$profile_url."uid=".$current_user->ID.'">'.$current_user->display_name.'</a>';
				$post = '<br /><div style="float:left;">'.get_avatar($current_user->ID, 32).'</div>'.$post;
				__wps__add_activity_comment($friend_to, $userdata->display_name, $friend_to, $post, 'friend');
			}

			// Hook for further actions
			do_action('symposium_friend_request_accepted_hook', $friend_to, $current_user->ID);	
			
		}
	
		echo $friend_to;		
	} else {
		echo "NOT LOGGED IN";
	}

	exit;
	
}

function __wps__profile_friends($uid, $limit_from) {

	global $wpdb, $current_user;
	wp_get_current_user();
	
	$limit_count = 10;

	$privacy = __wps__get_meta($uid, 'share');
	$is_friend = __wps__friend_of($uid, $current_user->ID);
	$html = "";	

	if ( ($uid == $current_user->ID) || (is_user_logged_in() && strtolower($privacy) == 'everyone') || (strtolower($privacy) == 'public') || (strtolower($privacy) == 'friends only' && $is_friend) || __wps__get_current_userlevel() == 5) {

		$mailpage = __wps__get_url('mail');
		if ($mailpage[strlen($mailpage)-1] != '/') { $mailpage .= '/'; }
		$q = __wps__string_query($mailpage);		

		// Friend Requests
		if ($uid == $current_user->ID) {
			
			$sql = "SELECT u1.display_name, u1.ID, f.friend_timestamp, f.friend_message, f.friend_from 
					FROM ".$wpdb->base_prefix."symposium_friends f 
					LEFT JOIN ".$wpdb->base_prefix."users u1 ON f.friend_from = u1.ID 
					WHERE f.friend_to = %d AND f.friend_accepted != 'on' ORDER BY f.friend_timestamp DESC";
	
			$requests = $wpdb->get_results($wpdb->prepare($sql, $current_user->ID));
			if ($requests) {
				
				$html .= '<h2>'.sprintf(__('%s Requests', WPS_TEXT_DOMAIN), get_option(WPS_OPTIONS_PREFIX.'_alt_friend')).'...</h2>';
				
				foreach ($requests as $request) {
				
					$html .= "<div id='request_".$request->friend_from."' style='clear:right; margin-top:8px; overflow: auto; margin-bottom: 15px; width:95%;'>";		
						$html .= "<div style='float: left; width:64px; margin-right: 15px'>";
							$html .= get_avatar($request->ID, 64);
						$html .= "</div>";
						$html .= "<div class='__wps__friend_request_info'>";
							$html .= __wps__profile_link($request->ID)."<br />";
							$html .= __wps__time_ago($request->friend_timestamp)."<br />";
							$html .= "<em>".stripslashes($request->friend_message)."</em>";
						$html .= "</div>";
						$html .= "<div style='clear: both; float:right;'>";
							$html .= '<input type="submit" title="'.$request->friend_from.'" id="rejectfriendrequest" class="__wps__button" style="'.__wps__get_extension_button_style().'" value="'.__('Reject', WPS_TEXT_DOMAIN).'" /> ';
						$html .= "</div>";
						$html .= "<div style='float:right;'>";
							$html .= '<input type="submit" title="'.$request->friend_from.'" id="acceptfriendrequest" class="__wps__button" style="'.__wps__get_extension_button_style().'" value="'.__('Accept', WPS_TEXT_DOMAIN).'" /> ';
						$html .= "</div>";
					$html .= "</div>";
				}

				$html .= '<hr />';
				
			}
		}
		
		// Friends
		$sql = "SELECT f.*, cast(m.meta_value as datetime) as last_activity 
				FROM ".$wpdb->base_prefix."symposium_friends f 
				LEFT JOIN ".$wpdb->base_prefix."usermeta m ON m.user_id = f.friend_to 
				WHERE f.friend_to > 0 AND f.friend_from = %d 
				AND m.meta_key = 'symposium_last_activity'
				AND f.friend_accepted = 'on'
				ORDER BY cast(m.meta_value as datetime) DESC LIMIT %d, %d";
		$friends = $wpdb->get_results($wpdb->prepare($sql, $uid, $limit_from, $limit_count));
		
		if ($friends) {
		
			if ($current_user->ID == $uid || __wps__get_current_userlevel() == 5) {
				$html .= '<input type="submit" id="removeAllFriends" name="Submit" class="__wps__button" style="'.__wps__get_extension_button_style().'; width:200px;" value="'.__('Remove all friends', WPS_TEXT_DOMAIN).'" />';
			}
		
			$count = 0;
		
			$inactive = get_option(WPS_OPTIONS_PREFIX.'_online');
			$offline = get_option(WPS_OPTIONS_PREFIX.'_offline');
			
			foreach ($friends as $friend) {
				
				$count++;
				
				$time_now = time();
				$last_active_minutes = strtotime($friend->last_activity);
				$last_active_minutes = floor(($time_now-$last_active_minutes)/60);
												
				$html .= "<div id='friend_".$friend->friend_to."' class='friend_div row_odd corners' style='clear:right; margin-top:8px; overflow: auto; margin-bottom: 15px; padding:6px; width:95%;'>";
				
					$html .= "<div style='width:64px; margin-right: 15px'>";
						$html .= get_avatar($friend->friend_to, 64);
					$html .= "</div>";

					// Send Mail and remove as friend
					$html .= "<div style='width:50px; height: 16px; float:right;'>";
					if ($friend->friend_accepted == 'on') {
						if ($uid == $current_user->ID) {

							$html .= "<div style='display:none;' class='friend_icons'>";
	
								$html .= "<div style='float:right;margin-left:5px;margin-right:5px;'>";
									$html .= '<img style="cursor:pointer" src="'.get_option(WPS_OPTIONS_PREFIX.'_images').'/delete.png" title="'.$friend->friend_to.'" class="frienddelete">';
									$html .= '</form>';
								$html .= "</div>";
							
								if (function_exists('__wps__mail')) {
									$html .= "<div style='float:right;'>";
										$html .= '<img style="cursor:pointer" src="'.get_option(WPS_OPTIONS_PREFIX.'_images').'/orange-tick.gif" onclick="document.location = \''.$mailpage.$q.'view=compose&to='.$friend->friend_to.'\';">';
									$html .= "</div>";
								}
								
							$html .= "</div>";
							
						}
					}
					$html .= '</div>';
										
					$html .= "<div style='padding-left:74px;'>";
						$html .= __wps__profile_link($friend->friend_to);
						$html .= "<br />";
						if ($last_active_minutes >= $offline) {
							$html .= __('Logged out', WPS_TEXT_DOMAIN).'. '.__('Last active', WPS_TEXT_DOMAIN).' '.__wps__time_ago($friend->last_activity).".";
						} else {
							if ($last_active_minutes >= $inactive) {
								$html .= __('Offline', WPS_TEXT_DOMAIN).'. '.__('Last active', WPS_TEXT_DOMAIN).' '.__wps__time_ago($friend->last_activity).".";
							} else {
								$html .= __('Last active', WPS_TEXT_DOMAIN).' '.__wps__time_ago($friend->last_activity).".";
							}
						}
						if (!get_option(WPS_OPTIONS_PREFIX.'_wps_lite')) {
							$html .= '<br />';
							// Show comment
							$sql = "SELECT cid, comment
								FROM ".$wpdb->base_prefix."symposium_comments
								WHERE author_uid = %d AND subject_uid = %d AND comment_parent = 0 AND type = 'post'
								ORDER BY cid DESC
								LIMIT 0,1";
							$comment = $wpdb->get_row($wpdb->prepare($sql, $friend->friend_to, $friend->friend_to));
							if ($comment) {
								$html .= '<div>'.__wps__buffer(__wps__make_url(stripslashes($comment->comment))).'</div>';
							}
							
							// Show latest non-status activity if applicable
							if (function_exists('__wps__forum')) {
								$sql = "SELECT cid, comment FROM ".$wpdb->base_prefix."symposium_comments
										WHERE author_uid = %d AND subject_uid = %d AND comment_parent = 0 AND type = 'forum' 
										ORDER BY cid DESC 
										LIMIT 0,1";
								$forum = $wpdb->get_row($wpdb->prepare($sql, $friend->friend_to, $friend->friend_to));
								if ($comment && $forum && $forum->cid != $comment->cid) {
									$html .= '<div>'.__wps__buffer(__wps__make_url(stripslashes($forum->comment))).'</div>';
								}
							}
							
							
						}
					$html .= "</div>";

					if ($friend->friend_accepted != 'on') {
						$html .= "<div style='float:left;'>";
							$html .= "<strong>".sprintf(__("%s request sent.", WPS_TEXT_DOMAIN), get_option(WPS_OPTIONS_PREFIX.'_alt_friend'))."</strong>";
						$html .= "</div>";
					}					

				$html .= "</div>";
								
			}

			if ($count == $limit_count) {
				$html .= "<a href='javascript:void(0)' id='friends' class='showmore_wall' title='".($limit_from+$limit_count)."'>".__("more...", WPS_TEXT_DOMAIN)."</a>";
			}
			
		} else {
			$html .= __("Nothing to show, sorry.", WPS_TEXT_DOMAIN);
		}
		
	} else {

		if (strtolower($privacy) == 'friends only') {
			$html .=  sprintf(__("Personal information only for %s.", WPS_TEXT_DOMAIN), get_option(WPS_OPTIONS_PREFIX.'_alt_friends'));
		}
		if (strtolower($privacy) == 'nobody') {
			$html .= __("Personal information is private.", WPS_TEXT_DOMAIN);
		}

	}						

	return $html;
	
}



function __wps__rrmdir_tmp($dir) {
   if (is_dir($dir)) {
	 $objects = scandir($dir);
	 foreach ($objects as $object) {
	   if ($object != "." && $object != "..") {
		 if (filetype($dir."/".$object) == "dir") __wps__rrmdir_tmp($dir."/".$object); else unlink($dir."/".$object);
	   }
	 }
	 reset($objects);
	 rmdir($dir);
   }
}  


function __wps__extract_unit($string, $start, $end)
{
	$pos = stripos($string, $start);
	$str = substr($string, $pos);
	$str_two = substr($str, strlen($start));
	$second_pos = stripos($str_two, $end);
	$str_three = substr($str_two, 0, $second_pos);
	$unit = trim($str_three); // remove whitespaces

	return $unit;
}



		
?>

	
