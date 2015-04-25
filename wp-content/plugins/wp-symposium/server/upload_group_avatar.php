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

	global $wpdb, $current_user, $blog_id;
	
	$uid = $_POST['uid'];
	$gid = $_POST['uploader_gid'];
	$user_id = $_POST['user_id'];
	$user_login = $_POST['user_login'];
	$user_email = $_POST['user_email'];
	$upload_file = str_replace("\'", "'", $_POST['uploaded_file']);
	$upload_filename = str_replace("\'", "'", $_POST['uploaded_filename']);

	$logged_in = false;
	if (is_user_logged_in()) {
		$logged_in = true;
	} else {
		if (upload_group_avatar_is_logged_in($uid, $user_login, $user_email)) {
			$logged_in = true;
		}
	}
	
	if ($logged_in) {

		if ($upload_file) {
	
			$html = '';
	
			if (get_option(WPS_OPTIONS_PREFIX.'_img_db') == "on") {
			
				// Save to database
	
				// Work out decent version of original filename
				$filename = $upload_filename;
				$filename = preg_replace('/[^A-Za-z0-9.]/','_',$filename);
	
				// Move to original filename
				if (copy($upload_file, WP_CONTENT_DIR."/uploads/".$filename)) {
					
					unlink($upload_file);

				    $image = __wps__scaleImageFileToBlob(WP_CONTENT_DIR."/uploads/".$filename);
	
				    if ($image == '') {
				        echo 'Image type not supported';
				    } else {
	
				        $image = addslashes($image);
			
						// update database with resized blob
						$wpdb->update( $wpdb->prefix.'symposium_groups', 
							array( 'group_avatar' => $image ), 
							array( 'gid' => $gid ), 
							array( '%s' ), 
							array( '%d' )
							);
					}
	
					// remove temporary file
					$myFile = WP_CONTENT_DIR."/uploads/".$filename;
					unlink($myFile);	

					$img_src = WP_CONTENT_URL.'/plugins/'.WPS_DIR.'/server/get_group_avatar.php?gid='.$gid."&r=".time();
				
				} else {
					$html .= '<p><span style="color:red;font-weight:bold">Failed to move uploaded file - check the permissions of '.WP_CONTENT_DIR.'/uploads.</span></p>';
				}
			
			} else {
			
				// Save to filesystem
	
				$name = $upload_filename;
				
				if ($blog_id > 1) {
					$targetPath = get_option(WPS_OPTIONS_PREFIX.'_img_path')."/".$blog_id."/groups/".$gid."/profile/";
				} else {
					$targetPath = get_option(WPS_OPTIONS_PREFIX.'_img_path')."/groups/".$gid."/profile/";
				}
				$filename = $name;
				$filename = preg_replace('/[^A-Za-z0-9.]/','_',$filename);
				$targetFile =  str_replace('//','/',$targetPath) . $filename;
			
				if (!file_exists($targetPath)) {
					if (!mkdir($targetPath, 0777, true)) {
						$html = 'Failed to create temporary upload folder: '.$targetPath;
					}
				}
					if (!file_exists($upload_file)) {
						$html = $upload_file.'<br>';
						$html .= $upload_filename.'<br>';
					}
	
				if ($html == '') {			

					if (file_exists($upload_file)) {
						if (is_writable($targetPath)) {
							if (copy($upload_file,$targetFile)) {
								if (unlink($upload_file)) {
									// remove thumbnail
									unlink(dirname($upload_file).'/thumbnail/'.$upload_filename);
									// resize to a decent size
									include_once('../../'.WPS_DIR.'/SimpleImage.php');
								   	$image = new __wps__SimpleImage();
								   	$image->load($targetFile);
								   	$image->resizeToWidth(350);
								   	$image->save($targetFile);
							   	
									// update database with filename
									$wpdb->update( $wpdb->prefix.'symposium_groups', 
										array( 'profile_photo' => $filename ), 
										array( 'gid' => $gid ), 
										array( '%s' ), 
										array( '%d' )
										);									
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
						$html .= "FAILED: ".$upload_file." does not exist";
					}
										
				}
	
				if ($blog_id > 1) {
					$img_url = get_option(WPS_OPTIONS_PREFIX.'_img_url')."/".$blog_id."/groups/".$gid."/profile/";	
				} else {
					$img_url = get_option(WPS_OPTIONS_PREFIX.'_img_url')."/groups/".$gid."/profile/";	
				}
				$img_src =  str_replace('//','/',$img_url) . $filename;
	
			}
	
			if ($html == '') {
					
				$html .= '<div style="overflow: auto;">';
						            	       
					$html .= '<div id="image_to_crop">';
					$html .= "<img src='".$img_src."' id='profile_jcrop_target' />";
					$html .= '</div>';
				
					$html .= '<div id="image_preview"> ';
					$html .= "<img src='".$img_src."' id='profile_preview' />";
					$html .= '</div>';
			
					$html .= '<div id="image_instructions"> ';
					$html .= '<p>'.__('Select an area above...', WPS_TEXT_DOMAIN).'</p>';
						$html .= '<input type="hidden" id="x" name="x" />';
						$html .= '<input type="hidden" id="y" name="y" />';
						$html .= '<input type="hidden" id="x2" name="x2" />';
						$html .= '<input type="hidden" id="y2" name="y2" />';
						$html .= '<input type="hidden" id="w" name="w" />';
						$html .= '<input type="hidden" id="h" name="h" />';
					$html .= '</div>';
				
				$html .= '</div>';
			
			
				echo $html;
	
				exit;
			
			} else {
				
				echo $html;
				exit;
				
			}
		
		} else {
		
			echo __("Failed to upload the file.", WPS_TEXT_DOMAIN);
			exit;
		}
		
	}
}

function upload_group_avatar_is_logged_in($uid, $user_login, $user_email) {

	global $wpdb;
	$user = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM ".$wpdb->base_prefix."users WHERE ID = %d AND lcase(user_login) = %s AND lcase(user_email) = %s", $uid, $user_login, $user_email ) );
	if ($user) {
		return true;
	} else {
		return false;
	}
	
}

function __wps__scaleImageFileToBlob($file) {

    $source_pic = $file;
    $max_width = 350;
    $max_height = 20000;

    list($width, $height, $image_type) = getimagesize($file);

    switch ($image_type)
    {
        case 1: $src = imagecreatefromgif($file); break;
        case 2: $src = imagecreatefromjpeg($file);  break;
        case 3: $src = imagecreatefrompng($file); break;
        default: return '';  break;
    }

    $x_ratio = $max_width / $width;
    $y_ratio = $max_height / $height;

    if( ($width <= $max_width) && ($height <= $max_height) ){
        $tn_width = $width;
        $tn_height = $height;
        }elseif (($x_ratio * $height) < $max_height){
            $tn_height = ceil($x_ratio * $height);
            $tn_width = $max_width;
        }else{
            $tn_width = ceil($y_ratio * $width);
            $tn_height = $max_height;
    }

    $tmp = imagecreatetruecolor($tn_width,$tn_height);

    /* Check if this image is PNG or GIF, then set if Transparent*/
    if(($image_type == 1) OR ($image_type==3))
    {
        imagealphablending($tmp, false);
        imagesavealpha($tmp,true);
        $transparent = imagecolorallocatealpha($tmp, 255, 255, 255, 127);
        imagefilledrectangle($tmp, 0, 0, $tn_width, $tn_height, $transparent);
    }
    imagecopyresampled($tmp,$src,0,0,0,0,$tn_width, $tn_height,$width,$height);

    /*
     * imageXXX() only has two options, save as a file, or send to the browser.
     * It does not provide you the oppurtunity to manipulate the final GIF/JPG/PNG file stream
     * So I start the output buffering, use imageXXX() to output the data stream to the browser, 
     * get the contents of the stream, and use clean to silently discard the buffered contents.
     */
    ob_start();

    switch ($image_type)
    {
        case 1: imagegif($tmp); break;
        case 2: imagejpeg($tmp, NULL, 100);  break; // best quality
        case 3: imagepng($tmp, NULL, 0); break; // no compression
        default: echo ''; break;
    }

    $final_image = ob_get_contents();

    ob_end_clean();

    return $final_image;
}

?>
