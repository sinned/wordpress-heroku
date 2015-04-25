<?php

include_once('../../../../wp-load.php');
include_once('../../../../wp-includes/wp-db.php');


if (isset($_POST['action']) && $_POST['action'] == 'after_upload_complete') {
	
	global $wpdb, $current_user;
	
	$uid = $_POST['uid'];
	$aid = $_POST['uploader_aid'];
	$user_id = $_POST['user_id'];
	$user_login = $_POST['user_login'];
	$user_email = $_POST['user_email'];
	$upload_file = str_replace("\'", "'", $_POST['uploaded_file']);
	$upload_filename = str_replace("\'", "'", $_POST['uploaded_filename']);

	$logged_in = false;
	if (is_user_logged_in()) {
		$logged_in = true;
	} else {
		if (__wps__upload_gallery_is_logged_in($uid, $user_login, $user_email)) {
			$logged_in = true;
		}
	}
	
	if ($logged_in) {

		if ($upload_file) {
	     	
			$html = '';
		
		    if ($aid != '') {
	
				if (get_option(WPS_OPTIONS_PREFIX.'_img_db') == "on") {
				
					// Save to database
	
					// Work out decent version of original filename (as uploaded)
					$filename = $upload_filename;
					$filename = preg_replace('/[^A-Za-z0-9.]/','_',$filename);
	
					// Check that upload folder exists
					if (!file_exists(WP_CONTENT_DIR."/uploads")) {
						if (!mkdir(WP_CONTENT_DIR."/uploads", 0777, true)) {
							echo '>Failed to create temporary upload folder: '.WP_CONTENT_DIR."/uploads, please create manually with permissions to allow uploads.";
							exit;
						}
					}
	
					// Move to original filename
					if (copy($upload_file,WP_CONTENT_DIR."/uploads/".$filename)) {
	
						// Get rescaled image
						// NB. we don't store the large original in the database to keep size down
						// Produce 'show' version to test if format is supported
				        $show_image = __wps__scaleImageFileToBlob(WP_CONTENT_DIR."/uploads/".$filename, 'show');
	
				        if ($show_image == '') {
				            echo 'Image type not supported';
							exit;
				        } else {
	
							// Is supported, so produce thumbnail version
					        $thumb_image = __wps__scaleImageFileToBlob(WP_CONTENT_DIR."/uploads/".$filename, 'thumb');
	
							// Deal with quotes if present
				        	$show_image = addslashes($show_image);
				        	$thumb_image = addslashes($thumb_image);
	
							// Add uploaded image into database
	   		      			$wpdb->query( $wpdb->prepare( "
	 						INSERT INTO ".$wpdb->prefix."symposium_gallery_items
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
	       				        	$aid, 
		           		        	'', 
	       				        	$uid,
	 		        			   	date("Y-m-d H:i:s"),
		     		        	   	'',
	 				        	   	'',
	 		        			   	$show_image,
		     		        	   	$thumb_image,
									0,
									''
	 								) 
	 		        		) );
				        }
	
	
						// remove temporary file
						$myFile = WP_CONTENT_DIR."/uploads/".$filename;
						unlink($myFile);	
	
						// Set album cover if not yet set
						$cover = $wpdb->get_var($wpdb->prepare("SELECT cover FROM ".$wpdb->prefix."symposium_gallery_items WHERE gid = %d", $aid));
						if (!$cover) {
							$first_item = $wpdb->get_var($wpdb->prepare("SELECT iid FROM ".$wpdb->prefix."symposium_gallery_items WHERE gid = %d ORDER BY iid LIMIT 0,1", $aid));
			      			$wpdb->query( $wpdb->prepare( "UPDATE ".$wpdb->prefix."symposium_gallery_items SET cover = 'on' WHERE iid = %d", $first_item  ) );			
						}
	
						echo 'OK'.$aid;
	
	
					} else {
						echo 'Failed to move uploaded file - check the permissions of '.WP_CONTENT_DIR.'/uploads.';
						exit;
					}
	
	
				} else {
	
					// Save to filesystem
	
					// Get directory, and upload image filename
					// Do this as many not be process in the same order as sent from jQuery fileupload
					$name = '';
					if (file_exists(dirname($upload_file))) {
						$handler = opendir(dirname($upload_file));
						while ($file = readdir($handler)) {
							if ($name == '' && $file != "." && $file != ".." && $file != ".DS_Store" && $file != 'thumbnail') {
								$name = $file;

								$upload_file = dirname($upload_file).'/'.$name;
								$upload_filename = $name;
				
								// Where the files are going
								$targetPath = get_option(WPS_OPTIONS_PREFIX.'_img_path')."/members/".$uid."/media/".$aid."/";
								$targetPath = str_replace('//','/',$targetPath);
				
								//New filename without odd characters
								$filename = $name;
								$filename = preg_replace('/[^A-Za-z0-9.]/','_',$filename);
				
								$uniqid = uniqid();
				
								// Work out paths to new images
								// $targetFile = path to copy of original;
								// $fullsize_targetFile = path to image shown
								// $thumbnail_targetFile = path to thumbnail
								$filename = $uniqid.'_'.$filename;
								$targetFile = $targetPath.$filename;
				
								$fullsize_targetFile =  $targetPath.'show_'.$filename;
								$thumbnail_targetFile = $targetPath.'thumb_'.$filename;
				
								if (!file_exists($targetPath)) {
									if (!mkdir($targetPath, 0777, true)) {
										$html = 'Failed to create temporary upload folder: '.$targetPath;
									}
								}
				
								if ($html == '') {			
									if (!copy($upload_file,$targetFile)) {
										$html .= "FAILED: Could not move ".$upload_file." to ".$targetFile;
									} else {
										
										unlink($upload_file);
										unlink(dirname($upload_file).'/thumbnail/'.$upload_filename);
				
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
			           				        	$aid, 
					           		        	$filename, 
			           				        	$uid,
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
			                      		$wpdb->query( $wpdb->prepare( "UPDATE ".$wpdb->prefix."symposium_gallery SET updated = %s WHERE gid = %d", date("Y-m-d H:i:s"), $aid  ) );
	
										// Set album cover if not yet set
										$cover = $wpdb->get_var($wpdb->prepare("SELECT cover FROM ".$wpdb->prefix."symposium_gallery_items WHERE gid = %d", $aid));
										if (!$cover) {
											$first_item = $wpdb->get_var($wpdb->prepare("SELECT iid FROM ".$wpdb->prefix."symposium_gallery_items WHERE gid = %d ORDER BY iid LIMIT 0,1", $aid));
							      			$wpdb->query( $wpdb->prepare( "UPDATE ".$wpdb->prefix."symposium_gallery_items SET cover = 'on' WHERE iid = %d", $first_item  ) );			
										}
				
										echo 'OK'.$aid;
				
									}
				
								} else {
				
					     				echo $html;
						     			exit;
				
					     		}
							}
						}
					}
				     		
		
				}
									
			} else {
	
				echo "NO ALBUM ID PASSED: ".$aid;
				exit;
	  
		  	}
		
		} else {
		
			echo __("Failed to upload the file.", WPS_TEXT_DOMAIN);
			exit;
		}
	} else {
		echo "NOT LOGGED IN";
		exit;
	}
	
}

function __wps__upload_gallery_is_logged_in($uid, $user_login, $user_email) {

	global $wpdb;
	$user = $wpdb->get_results( $wpdb->prepare( "SELECT ID FROM ".$wpdb->base_prefix."users WHERE ID = %d AND lcase(user_login) = %s AND lcase(user_email) = %s", $uid, $user_login, $user_email ) );
	if ($user) {
		return true;
	} else {
		return false;
	}
	
}

function __wps__scaleImageFileToBlob($file, $size) {

    $source_pic = $file;
	$thumbnail_size = ($value = get_option(WPS_OPTIONS_PREFIX."_gallery_thumbnail_size")) ? $value : '75';
    if ($size == 'show') {
    	$max_width = 800;
    	$max_height = 600;
    } else {
    	$max_width = $thumbnail_size;
    	$max_height = $thumbnail_size;
    }

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
