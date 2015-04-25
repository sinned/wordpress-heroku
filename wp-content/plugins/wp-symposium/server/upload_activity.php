<?php
/*
Uploadify v2.1.4
Release Date: November 8, 2010

Copyright (c) 2010 Ronnie Garcia, Travis Nickels

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/

include_once('../../../../wp-load.php');
include_once('../../../../wp-includes/wp-db.php');

if (isset($_POST['action']) && $_POST['action'] == 'after_upload_complete') {
	
	global $wpdb, $current_user;
	
	$uid = $_POST['uid'];
	$user_id = $_POST['user_id'];
	$user_login = $_POST['user_login'];
	$user_email = $_POST['user_email'];
	$upload_file = stripslashes(str_replace("\'", "'", $_POST['uploaded_file']));
	$upload_filename = stripslashes(str_replace("\'", "'", $_POST['uploaded_filename']));

	$logged_in = false;
	if (is_user_logged_in()) {
		$logged_in = true;
	} else {
		if (upload_file_is_logged_in($uid, $user_login, $user_email)) {
			$logged_in = true;
		}
	}

	if ($logged_in) {
	
		if ($upload_file) {
		
			$html = '';
		
			// Save to filesystem
	
			// Check for paths
			$directory = get_option(WPS_OPTIONS_PREFIX.'_img_path')."/members/".$user_id."/activity_upload/"; // Imgae has been uploaded to here
			$targetPath = get_option(WPS_OPTIONS_PREFIX.'_img_path')."/members/".$uid."/activity/pending/"; // Where image is copied until activity page refresh
			$activity_dir = get_option(WPS_OPTIONS_PREFIX.'_img_path')."/members/".$uid."/activity/"; // Where image will end up
			if (!file_exists($activity_dir)) {
				if (!mkdir($activity_dir, 0777, true)) {
					$html = 'Failed to create upload folder: '.$activity_dir;
				}
			}
			if (!file_exists($targetPath)) {
				if (!mkdir($targetPath, 0777, true)) {
					$html = 'Failed to create upload folder: '.$targetPath;
				}
			}
			
			$filename = $upload_filename;
			$filename = strtolower(preg_replace('/[^A-Za-z0-9.]/','_',$filename));
			$targetFile =  str_replace('//','/',$targetPath) . $filename;
			
			// Remove any current pending activity images (check only) -- don't think this should be done
			if (false && file_exists($directory)) {
				$handler = opendir($directory);
				while ($image = readdir($handler)) {
					if ($image != "." && $image != ".." && $image != ".DS_Store") {
						@unlink($directory.'/'.$image);
					}
				}
			}
							
			// Check that extension is allowed
			$ext = explode('.', strtolower($filename));
			if (strpos(get_option(WPS_OPTIONS_PREFIX.'_image_ext').','.get_option(WPS_OPTIONS_PREFIX.'_video_ext').','.get_option(WPS_OPTIONS_PREFIX.'_doc_ext'), $ext[sizeof($ext)-1]) > 0) {

				if ($html == '') {			
					if (file_exists($upload_file)) {
						if (is_writable($targetPath)) {
							if (copy($upload_file,$targetFile)) {
								if (unlink($upload_file)) {
									if (unlink(dirname($upload_file).'/thumbnail/'.$upload_filename)) {
										// Return filename
										$html = $filename;
									} else {
										$html .= "FAILED: Could not delete thumbnail ".dirname($upload_file).'/thumbnail/'.$upload_filename;
									}
								} else {
									$html .= "FAILED: Could not delete ".$upload_file;
								}
							} else {
								$html .= "FAILED: Could not copy ".$upload_file." to ".$targetFile;
							}
						} else {
							$html .= "FAILED: ".$targetPath." is not writeable";
						}
					} else {
						$html .= "FAILED: ".$upload_file." does not exist!";
					}
				}
				
			} else {
				// Invalid extension, just remove uploaded file
				$html .= __('Invalid file extension, the following are permitted:', WPS_TEXT_DOMAIN).' '.get_option(WPS_OPTIONS_PREFIX.'_image_ext').','.get_option(WPS_OPTIONS_PREFIX.'_video_ext').','.get_option(WPS_OPTIONS_PREFIX.'_doc_ext');
			}
	
			echo $html;
			exit;
		
		} else {
		
			echo __("Failed to upload the file.", WPS_TEXT_DOMAIN);
			exit;
		}
	} else {
		exit;
	}
}

function upload_file_is_logged_in($uid, $user_login, $user_email) {

	global $wpdb;
	$user = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM ".$wpdb->base_prefix."users WHERE ID = %d AND lcase(user_login) = %s AND lcase(user_email) = %s", $uid, $user_login, $user_email ) );
	if ($user) {
		return true;
	} else {
		$user_info = get_userdata($uid);
		if ($user_info->user_login == $user_login && $user_info->user_email == $user_email) {
			return true;
		} else {
			echo "NOT LOGGED IN ".$uid.', '.$user_login.','.$user_email.'<br>';
			return false;
		}
	}
	
}


?>
