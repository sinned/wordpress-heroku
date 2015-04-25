<?php

include_once('../../../../wp-config.php');
global $wpdb, $current_user, $blog_id;

if (is_user_logged_in()) {

	// Include CSS from WP Symposium->Styles->CSS
	echo '<style>';
	$css = get_option(WPS_OPTIONS_PREFIX.'_css');
	$css = str_replace("[]", chr(13), stripslashes($css));
	echo $css;
	echo '</style>';
	
	if (isset($_POST['uploader_uid'])) {

		if ($_FILES["file"]["name"] != '') {

			$uploader_uid = isset($_POST['uploader_uid']) ? $_POST['uploader_uid'] : '';
			$uploader_dir = isset($_POST['uploader_dir']) ? $_POST['uploader_dir'] : '';
			$uploader_url = isset($_POST['uploader_url']) ? $_POST['uploader_url'] : '';
			$uploader_ver = isset($_POST['uploader_ver']) ? $_POST['uploader_ver'] : '';
			$uploader_tid = isset($_POST['uploader_tid']) ? $_POST['uploader_tid'] : '';
			$uploader_gid = isset($_POST['uploader_gid']) ? $_POST['uploader_gid'] : '';
			$uploader_aid = isset($_POST['uploader_aid']) ? $_POST['uploader_aid'] : '';
			$subject_uid = isset($_POST['subject_uid']) ? $_POST['subject_uid'] : '';
			
			$tmp_path = '';
			$source_path = '';
			$targetFile = '';
			$filename = $_FILES["file"]["name"];
			$filename = preg_replace('/[^A-Za-z0-9.]/','_',$filename);
			if ($uploader_ver == 'forum') {
				if ($blog_id > 1) {
					$tmp_path = get_option(WPS_OPTIONS_PREFIX.'_img_path').'/'.$blog_id.'/forum/'.$uploader_tid.'_'.$current_user->ID.'_tmp/';
				} else {
					$tmp_path = get_option(WPS_OPTIONS_PREFIX.'_img_path').'/forum/'.$uploader_tid.'_'.$current_user->ID.'_tmp/';
				}
				$targetFile =  str_replace('//','/',$tmp_path).$filename;
			}
			if ($uploader_ver == 'activity') {
				$source_path = get_option(WPS_OPTIONS_PREFIX.'_img_path')."/members/".$uploader_uid."/activity_upload/"; // Image has been uploaded to here
				$tmp_path = get_option(WPS_OPTIONS_PREFIX.'_img_path')."/members/".$subject_uid."/activity/pending/";	// Where image is copied until activity page refresh
				$targetFile =  str_replace('//','/',$tmp_path).$filename;
			}
			if ($uploader_ver == 'avatar') {
				$tmp_path = get_option(WPS_OPTIONS_PREFIX.'_img_path')."/members/".$current_user->ID."/profile/";
				$targetFile =  str_replace('//','/',$tmp_path).$filename;
			}
			if ($uploader_ver == 'group_avatar') {
				if ($blog_id > 1) {
					$tmp_path = get_option(WPS_OPTIONS_PREFIX.'_img_path')."/".$blog_id."/groups/".$uploader_gid."/profile/";
				} else {
					$tmp_path = get_option(WPS_OPTIONS_PREFIX.'_img_path')."/groups/".$uploader_gid."/profile/";
				}
				$targetFile =  str_replace('//','/',$tmp_path).$filename;
			}
			if ($uploader_ver == 'gallery') {
				$tmp_path = get_option(WPS_OPTIONS_PREFIX.'_img_path')."/members/".$current_user->ID."/media/".$uploader_aid."/";
				$uniqid = uniqid();
			
				// Work out paths to new images
				// $targetFile = path to copy of original;
				// $fullsize_targetFile = path to image shown
				// $thumbnail_targetFile = path to thumbnail
				$filename = $uniqid.'_'.$filename;
				$targetFile = $tmp_path.$filename;	
				$fullsize_targetFile =  $tmp_path.'show_'.$filename;
				$thumbnail_targetFile = $tmp_path.'thumb_'.$filename;
			}
			
			
			// If applicable, check source path, and create if not there
			if ($source_path != '') {
				if (!file_exists($source_path)) {
					if (!mkdir($source_path, 0777, true)) {
						echo 'Failed to create upload folder: '.$source_path;
					}
				}
			}
			
			if ($tmp_path != '') {
			
				// Make tmp directory if it doesn't exist
				if (!file_exists($tmp_path)) {
					if (!mkdir($tmp_path, 0777, true)) {
						echo 'Failed to create tmp upload folder: '.$tmp_path;
					}
				}

				// New get the uploaded file and copy there
				
				if ($_FILES["file"]["error"] > 0) {
					echo "Error: " . $_FILES["file"]["error"] . "<br>";
				} else {
					$allowedExts = ','.get_option(WPS_OPTIONS_PREFIX.'_image_ext').','.get_option(WPS_OPTIONS_PREFIX.'_doc_ext').','.get_option(WPS_OPTIONS_PREFIX.'_video_ext');
					//echo "Upload: " . $_FILES["file"]["name"] . "<br>";
					$ext = pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION);
					//echo "Extension: " . $ext . "<br />";
					if (strpos($allowedExts, $ext)) {
						$extAllowed = true;
					} else {
						$extAllowed = false;
					}
					//echo "Type: " . $_FILES["file"]["type"] . "<br>";
					//echo "Size: " . ($_FILES["file"]["size"] / 1024) . " kB<br>";
					//echo "Stored in: " . $_FILES["file"]["tmp_name"];
					
					if (!$extAllowed) {
						echo __('Sorry, file type not allowed.', WPS_TEXT_DOMAIN);
					} else {
						// Copy file to tmp location
						if (move_uploaded_file($_FILES["file"]["tmp_name"], $targetFile)) {
							if ($uploader_ver == 'activity') {
								// echo __('Image uploaded to '.$_FILES["file"]["tmp_name"].' and moved to '.$targetFile, WPS_TEXT_DOMAIN);
							}
							if ($uploader_ver == 'avatar') {
								// update database
								__wps__update_meta($current_user->ID, 'profile_photo', "'".$filename."'");
								echo __('Avatar updated!', WPS_TEXT_DOMAIN);
							} 
							if ($uploader_ver == 'group_avatar') {
								// update database
								$wpdb->update( $wpdb->base_prefix.'symposium_groups', 
									array( 'profile_photo' => $filename ), 
									array( 'gid' => $uploader_gid ), 
									array( '%s' ), 
									array( '%d' )
									);
								echo __('Group avatar updated!', WPS_TEXT_DOMAIN);
							} 
							if ($uploader_ver == 'forum' || $uploader_ver == 'activity') {
								echo '<div id="forum_file_list">'.$filename.' '.__('will be attached...', WPS_TEXT_DOMAIN).'</div>';
								echo '<a href="file_upload_form.php?uploader_uid='.$current_user->ID.'&uploader_tid='.$tid.'&uploader_gid='.$uploader_gid.'&uploader_aid='.$uploader_aid.'&uploader_dir='.$uploader_dir.'&uploader_url='.$uploader_url.'&uploader_ver='.$uploader_ver.'">'.__('Reset', WPS_TEXT_DOMAIN).'</a>';
							}
							if ($uploader_ver == 'gallery') {
							
								// resize to a various sizes
								$thumbnail_size = ($value = get_option(WPS_OPTIONS_PREFIX."_gallery_thumbnail_size")) ? $value : '75';
								include_once('../../'.WPS_DIR.'/SimpleImage.php');
							   	$image = new __wps__SimpleImage();
							   	$image->load($targetFile);
							   	$image->resizeToWidth(800);
							   	$image->save($fullsize_targetFile);
							   	$image->resizeToWidth($thumbnail_size);
							   	$image->save($thumbnail_targetFile);
		
							   	// Record filename of uploaded image to database
								// NB. show and thumbnail are prefixes to filename
	        		      		$wpdb->query( $wpdb->prepare( "
	     						INSERT INTO ".$wpdb->base_prefix."symposium_gallery_items
			     				( 	gid,
	     							name,
	     							owner,
			     					created,
	     							cover,
	     							original,
			     					photo,
	                		        thumbnail,
									groupid,
									title
				                        )
	     						VALUES ( %d, %s, %d, %s, %s, %s, %s, %s, %d, %s )", 
				     		        array(
	           				        	$uploader_aid, 
			           		        	$filename, 
	           				        	$current_user->ID,
	     		        			   	date("Y-m-d H:i:s"),
			     		        	   	'',
	     				        	   	'',
	     		        			   	'',
			     		        	   	'',
										0,
										''
	     						) 
	     		        		) );

			     		        // Updated gallery table
	                      		$wpdb->query( $wpdb->prepare( "UPDATE ".$wpdb->prefix."symposium_gallery SET updated = %s WHERE gid = %d", date("Y-m-d H:i:s"), $uploader_aid  ) );

								// Set album cover if not yet set
								$cover = $wpdb->get_var($wpdb->prepare("SELECT cover FROM ".$wpdb->prefix."symposium_gallery_items WHERE gid = %d", $uploader_aid));
								if (!$cover) {
									$first_item = $wpdb->get_var($wpdb->prepare("SELECT iid FROM ".$wpdb->prefix."symposium_gallery_items WHERE gid = %d ORDER BY iid LIMIT 0,1", $uploader_aid));
					      			$wpdb->query( $wpdb->prepare( "UPDATE ".$wpdb->prefix."symposium_gallery_items SET cover = 'on' WHERE iid = %d", $first_item  ) );			
								}
		
								$profile_url = __wps__get_url('profile');
								$q = __wps__string_query($profile_url);
								echo __('Image uploaded', WPS_TEXT_DOMAIN).'. <a href="file_upload_form.php?uploader_uid='.$current_user->ID.'&uploader_tid='.$tid.'&uploader_gid='.$uploader_gid.'&uploader_aid='.$uploader_aid.'&uploader_dir='.$uploader_dir.'&uploader_url='.$uploader_url.'&uploader_ver='.$uploader_ver.'">'.__('Upload another', WPS_TEXT_DOMAIN).'</a>';
								echo ', or <a target="_parent" href="'.$profile_url.$q.'?view=gallery&album_id='.$uploader_aid.'&embed=on">'.__('refresh album', WPS_TEXT_DOMAIN).'</a>?';
								
								add_to_create_activity_feed($uploader_aid);
							}
						} else {
							echo 'Failed to process '.$_FILES["file"]["tmp_name"].' > '.$targetFile;
						}
					}
				}
				
			} else {
				echo 'Sorry, file upload does not work here yet.';
			}

		} else {
			echo 'No file uploaded:<br />';
			var_dump($_FILES);
		}
		
	} else {

		$uploader_uid = isset($_GET['uploader_uid']) ? $_GET['uploader_uid'] : '';
		$uploader_dir = isset($_GET['uploader_dir']) ? stripslashes($_GET['uploader_dir']) : '';
		$uploader_url = isset($_GET['uploader_url']) ? $_GET['uploader_url'] : '';
		$uploader_ver = isset($_GET['uploader_ver']) ? $_GET['uploader_ver'] : '';
		$uploader_tid = isset($_GET['uploader_tid']) ? $_GET['uploader_tid'] : '';
		$uploader_gid = isset($_GET['uploader_gid']) ? $_GET['uploader_gid'] : '';
		$uploader_aid = isset($_GET['uploader_aid']) ? $_GET['uploader_aid'] : '';
		$subject_uid = isset($_GET['subject_uid']) ? $_GET['subject_uid'] : $current_user->ID;
		 
		if ($uploader_ver == 'forum' || $uploader_ver == 'activity') {
			echo '<div id="__wps__file_upload_basic_label" style="font-weight:bold;">'.__('Attach file', WPS_TEXT_DOMAIN).'</div>';
		} 
		if ($uploader_ver == 'avatar') {
			echo '<strong>'.__('Please ensure that the image is no bigger than 300x300 pixels.', WPS_TEXT_DOMAIN).'</strong><br />';
		}
		if ($uploader_ver == 'group_avatar') {
			echo '<strong>'.__('No bigger than 300x300 pixels.', WPS_TEXT_DOMAIN).'</strong><br />';
		}

		echo '<form action="file_upload_form.php" enctype="multipart/form-data" method="post">';
		echo '<input type="hidden" name="uploader_uid" value="'.$uploader_uid.'" />';
		echo '<input type="hidden" name="uploader_dir" value="'.$uploader_dir.'" />';
		echo '<input type="hidden" name="uploader_url" value="'.$uploader_url.'" />';
		echo '<input type="hidden" name="uploader_ver" value="'.$uploader_ver.'" />';
		echo '<input type="hidden" name="uploader_tid" value="'.$uploader_tid.'" />';
		echo '<input type="hidden" name="uploader_gid" value="'.$uploader_gid.'" />';
		echo '<input type="hidden" name="uploader_aid" value="'.$uploader_aid.'" />';
		echo '<input type="hidden" name="subject_uid" value="'.$subject_uid.'" />';
		echo '<input type="file" name="file" id="file"> ';
		echo '<input type="submit" name="submit" value="Submit">';
		echo '</form>';		
		
	}

} else {
	
	echo 'Not logged in';
}


function add_to_create_activity_feed($aid) {
	
	global $wpdb, $current_user;
	
	// Get name of album
	$sql = "SELECT name FROM ".$wpdb->base_prefix."symposium_gallery WHERE gid = %d";
	$name = $wpdb->get_var($wpdb->prepare($sql, $aid));
	
	// Work out message
	$msg = __("Added to", WPS_TEXT_DOMAIN).' '.$name.'[]'.$aid.'[]added';
	
	// First remove any older messages to avoid duplication that mention this album
	$sql = "DELETE FROM ".$wpdb->base_prefix."symposium_comments WHERE subject_uid = ".$current_user->ID." AND author_uid = ".$current_user->ID." AND comment LIKE '%".$name."%' AND type = 'gallery'";
	$wpdb->query($sql);
	
	// Now add to activity feed
	__wps__add_activity_comment($current_user->ID, $current_user->display_name, $current_user->ID, $msg, 'gallery');
	
}

?>
