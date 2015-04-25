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

global $wpdb, $blog_id;

$uid = $_POST['user_id'];
$user_login = $_POST['user_login'];
$user_email = $_POST['user_email'];

if (upload_file_is_logged_in($uid, $user_login, $user_email)) {

	if (!empty($_FILES)) {
	
		$html = '';
	
		// Save to filesystem
		$tempFile = $_FILES['file']['tmp_name'];

		// Check for paths
		$activity_dir = get_option(WPS_OPTIONS_PREFIX.'_img_path')."/members/".$uid."/activity/";
		$targetPath = get_option(WPS_OPTIONS_PREFIX.'_img_path')."/members/".$uid."/activity/pending/";
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
		
		
		
		$filename = $_FILES['file']['name'];
		$filename = strtolower(preg_replace('/[^A-Za-z0-9.]/','_',$filename));
		$targetFile =  str_replace('//','/',$targetPath) . $filename;
	
		// Remove any current pending activity images (check only)
		$directory = get_option(WPS_OPTIONS_PREFIX.'_img_path')."/members/".$uid."/activity/pending/";
		if (file_exists($directory)) {
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
		
			if (!move_uploaded_file($tempFile,$targetFile)) {
				$html .= "FAILED: Could not move ".$tempFile." to ".$targetFile;
			} else {

				// resize to a decent size
				include_once(dirname(__FILE__).'/../SimpleImage.php');
			   	$image = new __wps__SimpleImage();
			   	$image->load($targetFile);
			   	$image->resizeToWidth(640);
			   	$image->save($targetFile);
			   					
				$html = $filename;

				echo $html;
				exit;
			
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
	echo "NOT LOGGED IN ".$uid.', '.$user_login.', '.$user_email;
	exit;
}

function upload_file_is_logged_in($uid, $user_login, $user_email) {

	global $wpdb;
	$user = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM ".$wpdb->base_prefix."users WHERE ID = %d AND lcase(user_login) = %s AND lcase(user_email) = %s", $uid, $user_login, $user_email ) );
	if ($user) {
		return true;
	} else {
		return false;
	}
	
}


?>
