<?php
include_once('../../../../wp-config.php');

if (get_option(WPS_OPTIONS_PREFIX.'__wps__gallery_activated') || get_option(WPS_OPTIONS_PREFIX.'__wps__gallery_network_activated')) {

	// Member/Gallery search (autocomplete)
	if (isset($_GET['term'])) {
			
		global $wpdb, $current_user;	
		$return_arr = array();
		$term = $_GET['term'];
	
		$sql = "SELECT g.gid, g.owner, g.name, u.display_name, g.sharing FROM ".$wpdb->base_prefix."symposium_gallery g
		LEFT JOIN ".$wpdb->base_prefix."users u ON g.owner = u.ID
		WHERE ( ( name LIKE '%".$term."%') OR ( display_name LIKE '%".$term."%') ) AND u.display_name is not null
		ORDER BY name LIMIT 0,25";
		
		$list = $wpdb->get_results($sql);
		
		if ($list) {
			foreach ($list as $item) {
	
				// check for privacy
				if ( ($item->owner == $current_user->ID) || (strtolower($item->sharing) == 'public') || (is_user_logged_in() && strtolower($item->sharing) == 'everyone') || (strtolower($item->sharing) == 'public') || (strtolower($item->sharing) == 'friends only' && __wps__friend_of($item->owner, $current_user->ID)) || __wps__get_current_userlevel() == 5) {
					
					$row_array['id'] = $item->gid;	
					$row_array['owner'] = $item->owner;
					$row_array['display_name'] = $item->display_name;
					$row_array['name'] = $item->name;
					$row_array['avatar'] = get_avatar($item->owner, 40);
					
			        array_push($return_arr,$row_array);
			        
				}
			}
		}
	
		echo json_encode($return_arr);
		exit;
	
	}
	
	
	// Update to alerts and then redirect
	if (isset($_GET['href'])) {
		
		global $wpdb, $current_user;
		
		$num = isset($_GET['num']) ? $_GET['num'] : 0;
		$aid = $_GET['aid'];

		// Add to activity feed
		add_to_create_activity_feed($aid);
			
		// Then re-direct
		$href = __wps__get_url('profile');
		$href .= __wps__string_query($href);
		$href .= "uid=".$current_user->ID."&embed=on&album_id=".$aid;
		
		wp_redirect( $href ); 
		exit;	
		
	}

	
	// Re-order thumbnails
	if ($_POST['action'] == 'symposium_reorder_photos') {
		global $wpdb,$current_user;
		if (is_user_logged_in()) {
			$album_id = str_replace('symposium_gallery_photos_', '', $_POST['album_id']);
			$order = explode(",", $_POST['order']);		
			for($i=0;$i < sizeof($order);$i++){
				$wpdb->query( $wpdb->prepare( "UPDATE ".$wpdb->base_prefix."symposium_gallery_items SET photo_order = %d WHERE iid = %d AND gid = %d AND owner = %d", ($i+1), $order[$i], $album_id, $current_user->ID  ) );  
			};
			echo __('Order saved, reload page to view new order.', WPS_TEXT_DOMAIN);
			
		} else {
			echo 'NOT LOGGED IN';
		}
	}
	
	// Comments for photo
	if ($_POST['action'] == 'symposium_get_photo_comments') {
	
		global $wpdb;	
		$photo_id = $_POST['photo_id'];
	
		$sql = "SELECT c.*, u.display_name FROM ".$wpdb->base_prefix."symposium_comments c 
			LEFT JOIN ".$wpdb->base_prefix."users u ON c.author_uid = u.ID 
			WHERE c.comment_parent = 0 AND c.type = 'photo' AND c.subject_uid = %d ORDER BY c.cid DESC";
	
		$comments = $wpdb->get_results($wpdb->prepare($sql, $photo_id));	
		
		$comments_array = array();
		foreach ($comments as $comment) {
			$add = array (
				'ID' => $comment->cid,
				'author_id' => $comment->author_uid,
				'avatar' => get_avatar($comment->author_uid, 32),
				'display_name' => $comment->display_name,
				'display_name_link' => __wps__profile_link($comment->author_uid),
				'comment' => __wps__buffer(__wps__make_url(stripslashes($comment->comment))),
				'timestamp' => __wps__time_ago($comment->comment_timestamp)
			);
			array_push($comments_array, $add);
		}
		
		echo json_encode($comments_array);
	
		exit;
		
	}	
	
	// Delete comment from photo
	if ($_POST['action'] == '__wps__delete_gallery_comment') {
	
		global $wpdb, $current_user;
		
		if (is_user_logged_in()) {
				
			$cid = $_POST['cid'];
			$sql = "SELECT subject_uid, author_uid, comment FROM ".$wpdb->base_prefix."symposium_comments WHERE cid = %d";
			$c = $wpdb->get_row($wpdb->prepare($sql, $cid));
			
			$author_id = $c->author_uid;
			$photo_id = $c->subject_uid;
			$comment = $c->comment;
	
			$sql = "DELETE FROM ".$wpdb->base_prefix."symposium_comments WHERE (cid = %d AND type='photo') OR (author_uid = %d AND comment LIKE '%%%s' AND type='gallery')";
			$wpdb->query($wpdb->prepare($sql, $cid, $author_id, $photo_id.'[]'.$comment));
	
		}
	
	}
	
	// Update comment on photo
	if ($_POST['action'] == 'symposium_update_photo_comment') {
	
		global $wpdb, $current_user;
		
		if (is_user_logged_in()) {
				
			$photo_id = $_POST['photo_id'];
			$comment = $_POST['comment'];
			$old_comment = $_POST['old_comment'];
	
			$sql = "UPDATE ".$wpdb->base_prefix."symposium_comments SET comment = %s WHERE subject_uid = %d AND comment = %s AND type = 'photo'";
			$wpdb->query($wpdb->prepare($sql, $comment, $photo_id, $old_comment));
	
			$sql = "SELECT cid,comment FROM ".$wpdb->base_prefix."symposium_comments WHERE comment LIKE '%%[]%d[]%s' AND type = 'gallery'";
			$o = str_replace("'", '_', $old_comment);
			$o = str_replace("\\", '_', $o);
			$sql = str_replace('%s', $o, $sql);
			$c = $wpdb->get_row($wpdb->prepare($sql, $photo_id));
	
			echo $wpdb->last_query;
	
			$new_c = str_replace('[]'.$photo_id.'[]'.$old_comment, '[]'.$photo_id.'[]'.$comment, $c->comment);
			$sql = "UPDATE ".$wpdb->base_prefix."symposium_comments SET comment = %s WHERE cid = %d";
			$wpdb->query($wpdb->prepare($sql, $new_c, $c->cid));
	
			echo $wpdb->last_query;
	
			exit;
		}
	
	}
	
	
	// Get photo gallery (for editing comment)
	
	if ($_POST['action'] == '__wps__get_gallery_comment') {
	
		global $wpdb, $current_user;
		
		if (is_user_logged_in()) {
				
			$cid = $_POST['cid'];
			$sql = "SELECT comment FROM ".$wpdb->base_prefix."symposium_comments WHERE cid = %d LIMIT 0,1";
			$c = $wpdb->get_var($wpdb->prepare($sql, $cid));
			$c = stripslashes($c);
	
			echo $c;
			exit;
	
		}
	
	}	
		
	// Add comment to photo
	if ($_POST['action'] == 'symposium_add_photo_comment') {
	
		global $wpdb, $current_user;
		
		if (is_user_logged_in()) {
				
			$photo_id = $_POST['photo_id'];
			$comment = $_POST['comment'];
		
			// Insert comment
			$wpdb->query( $wpdb->prepare( "
			INSERT INTO ".$wpdb->base_prefix."symposium_comments
			( 	subject_uid, 
				author_uid,
				comment_parent, 
				comment_timestamp, 
				comment, 
				is_group, 
				type
			)
			VALUES ( %d, %d, %d, %s, %s, %s, %s )", 
			array(
				$photo_id,
				$current_user->ID, 
				0,
				date("Y-m-d H:i:s"), 
				$comment, 
				'',
				'photo'
				) 
			) );
	
			// Get name of photo
			$sql = "SELECT gid,title FROM ".$wpdb->base_prefix."symposium_gallery_items WHERE iid = %d";
			$photo = $wpdb->get_row($wpdb->prepare($sql, $photo_id));
		
			// Work out message
			$msg = __("Commented on", WPS_TEXT_DOMAIN).' '.$photo->title.'[]'.$photo->gid.'[]comment[]'.$photo_id.'[]'.$comment;
		
			// Now add to activity feed
			__wps__add_activity_comment($current_user->ID, $current_user->display_name, $current_user->ID, $msg, 'gallery');
			
		}
			
	}
	
	
	
	// Search gallery (shortcode)
	if ($_POST['action'] == 'getGallery') {
		
		global $wpdb, $current_user;
		
		$start = $_POST['start'];
		$term = $_POST['term'];
	
		$sql = "SELECT g.*, u.display_name FROM ".$wpdb->base_prefix."symposium_gallery g
				INNER JOIN ".$wpdb->base_prefix."users u ON g.owner = u.ID
				WHERE g.name LIKE '%".$term."%' 
				   OR u.display_name LIKE '%".$term."%' 
				ORDER BY gid DESC 
				LIMIT ".$start.",50";
		$albums = $wpdb->get_results($sql);
	
		$album_count = 0;	
		$total_count = 0;	
		$html = '';
	
		if ($albums) {
	
			$page_length = (get_option(WPS_OPTIONS_PREFIX."_gallery_page_length") != '') ? get_option(WPS_OPTIONS_PREFIX."_gallery_page_length") : 10;
	
			$html .= "<div id='symposium_gallery_albums'>";
			
			foreach ($albums AS $album) {
	
				$total_count++;	
				
				// check for privacy
				if ( ($album->owner == $current_user->ID) || (strtolower($album->sharing) == 'public') || (is_user_logged_in() && strtolower($album->sharing) == 'everyone') || (strtolower($album->sharing) == 'public') || (strtolower($album->sharing) == 'friends only' && __wps__friend_of($album->owner, $current_user->ID)) || __wps__get_current_userlevel() == 5) {
	
					$sql = "SELECT COUNT(iid) FROM ".$wpdb->base_prefix."symposium_gallery_items WHERE gid = %d";
					$photo_count = $wpdb->get_var($wpdb->prepare($sql, $album->gid));				
		
					if ($photo_count > 0) {
						
						$html .= "<div id='__wps__album_content' style='margin-bottom:30px'>";
					
						$html .= "<div id='wps_gallery_album_name_".$album->gid."' class='topic-post-header'>".stripslashes($album->name)."</div>";
						$html .= "<p>".__wps__profile_link($album->owner)."</p>";
			
						$sql = "SELECT * FROM ".$wpdb->base_prefix."symposium_gallery_items WHERE gid = %d ORDER BY photo_order";
						$photos = $wpdb->get_results($wpdb->prepare($sql, $album->gid));	
					
						if ($photos) {
		
							global $blog_id;
							$blog_path = ($blog_id > 1) ? '/'.$blog_id : '';
	
							$album_count++;
							
							$cnt = 0;
							$thumbnail_size = (get_option(WPS_OPTIONS_PREFIX."_gallery_thumbnail_size") != '') ? get_option(WPS_OPTIONS_PREFIX."_gallery_thumbnail_size") : 75;
							$html .= '<div id="wps_comment_plus" style="width:98%;height:'.($thumbnail_size+10).'px;overflow:hidden; ">';
				
							$preview_count = (get_option(WPS_OPTIONS_PREFIX."_gallery_preview") != '') ? get_option(WPS_OPTIONS_PREFIX."_gallery_preview") : 5;
				       		foreach ($photos as $photo) {
				       		    
				       		    $cnt++;
				              					
								// Filesystem
								if (get_option(WPS_OPTIONS_PREFIX.'_img_db') == "on") {
									$img_src = WP_CONTENT_URL."/plugins/wp-symposium/get_album_item.php?iid=".$photo->iid."&size=photo";
									$thumb_src = WP_CONTENT_URL."/plugins/wp-symposium/get_album_item.php?iid=".$photo->iid."&size=thumbnail";
								} else {
	
									if (get_option(WPS_OPTIONS_PREFIX."_gallery_show_resized") == 'on') {
					                	$img_src = get_option(WPS_OPTIONS_PREFIX.'_img_url').$blog_path.'/members/'.$album->owner.'/media/'.$album->gid.'/show_'.$photo->name;
									} else {
					                	$img_src = get_option(WPS_OPTIONS_PREFIX.'_img_url').$blog_path.'/members/'.$album->owner.'/media/'.$album->gid.'/'.$photo->name;
									}
				        	        $thumb_src = get_option(WPS_OPTIONS_PREFIX.'_img_url').$blog_path.'/members/'.$album->owner.'/media/'.$album->gid.'/thumb_'.$photo->name;
								}
				
				               	$html .= '<div class="__wps__photo_outer">';
				           			$html .= '<div class="__wps__photo_inner">';
				      					$html .= '<div class="__wps__photo_cover">';
											$html .= '<a class="__wps__photo_cover_action wps_gallery_album" data-owner="'.$album->owner.'" data-iid="'.$photo->iid.'" data-name="'.stripslashes($photo->title).'" href="'.$img_src.'" rev="'.$cnt.'" rel="symposium_gallery_photos_'.$album->gid.'" title="'.stripslashes($album->name).'">';
					        					$html .= '<img class="__wps__photo_image" style="width:'.$thumbnail_size.'px; height:'.$thumbnail_size.'px;" src="'.$thumb_src.'" />';
					        				$html .= '</a>';
				     					$html .= '</div>';
				       				$html .= '</div>';
				     			$html .= '</div>';
		
					       		if ($cnt == $preview_count) {
					       		    $html .= '<div id="wps_gallery_comment_more" style="cursor:pointer">'.__('more...', WPS_TEXT_DOMAIN).'<div style="clear:both"></div></div>';
					       		}   		
				      				
				       		}
				       		
				       		$html .= '</div>';
						
						} else {
						
					      	 $html .= __("No photos yet.", WPS_TEXT_DOMAIN);
					     
						}
		
						$html .= '</div>';
					
						if ($album_count == $page_length) { break; }
						
					}
				}
	
			}
			$html .= "<div style='clear:both;text-align:center; margin-top:20px; width:100%'><a href='javascript:void(0)' id='showmore_gallery'>".__("more...", WPS_TEXT_DOMAIN)."</a></div>";
			
			$html .= '</div>';
				
		} else {
			$html .= '<div style="clear:both;text-align:center; width:100%;">'.__('No albums to show', WPS_TEXT_DOMAIN).".</div>";
		}
		
		$html = $total_count."[split]".$html;
		echo $html;	
		exit;
	}
	
	// Select cover photo for album
	if ($_POST['action'] == 'menu_gallery_select_cover') {
		
		global $wpdb;
		if (isset($_POST['item_id'])) { $item_id = $_POST['item_id']; } else { $item_id = 0; }
		$sql = "SELECT gid FROM ".$wpdb->base_prefix."symposium_gallery_items WHERE iid = %d";
		$gid = $wpdb->get_var($wpdb->prepare($sql, $item_id));
	
		if ($item_id > 0 && $gid > 0) {
			$wpdb->query( $wpdb->prepare( "UPDATE ".$wpdb->base_prefix."symposium_gallery_items SET cover = 'on' WHERE gid = %d AND iid = %d", $gid, $item_id  ) );  
			$wpdb->query( $wpdb->prepare( "UPDATE ".$wpdb->base_prefix."symposium_gallery_items SET cover = '' WHERE gid = %d AND iid != %d", $gid, $item_id  ) );  
			echo 'OK';
		} else {
			echo 'No item ID passed';
		}
	
		exit;
	}
	
	// Change sharing status
	if ($_POST['action'] == 'menu_gallery_change_share') {
	
		global $wpdb;
	
		if (isset($_POST['album_id'])) { $album_id = $_POST['album_id']; } else { $album_id = 0; }
		if (isset($_POST['new_share'])) { $new_share = $_POST['new_share']; } else { $new_share = ''; }
	
		if ($album_id > 0 && $new_share != '') {
			$wpdb->query( $wpdb->prepare( "UPDATE ".$wpdb->base_prefix."symposium_gallery SET sharing = %s WHERE gid = %d", $new_share, $album_id  ) );  
			echo 'OK';
		} else {
			echo 'Wrong parameters';
		}
	
		exit;
	}
	
	
	// Delete photo
	if ($_POST['action'] == 'menu_gallery_manage_delete') {
	
	    global $wpdb, $current_user;
		
	    $item_id = 0;
	    if (isset($_POST['item_id'])) { $item_id = $_POST['item_id']; }
	 
	    if ($item_id != 0) {
	
			// Get owner
			$this_owner = stripslashes($wpdb->get_var($wpdb->prepare("SELECT owner FROM ".$wpdb->base_prefix."symposium_gallery_items WHERE iid = %d", $item_id)));
		
			// check to see if storing in filesystem or database
			if (get_option(WPS_OPTIONS_PREFIX.'_img_db') == "on") {
	
				// when deleting item, the fields in the record are deleted too, nothing to do here
	
			} else {
		
				// delete files (and from filesystem)
		
				// get album ID
			    $sql = "SELECT gid, name FROM ".$wpdb->base_prefix."symposium_gallery_items WHERE iid = %d";
		    	$photo = $wpdb->get_row($wpdb->prepare($sql, $item_id));	
		
				// remove from album table
				$wpdb->query( $wpdb->prepare( "DELETE FROM ".$wpdb->base_prefix."symposium_gallery_items WHERE iid = %d", $item_id  ) );  
				// remove comments
				$wpdb->query( $wpdb->prepare( "DELETE FROM ".$wpdb->base_prefix."symposium_comments WHERE subject_uid = %d AND type = 'photo'", $item_id  ) );  
		
				// delete files...
				$thumb_src = WP_CONTENT_DIR.'/wps-content/members/'.$this_owner.'/media/'.$photo->gid.'/thumb_'.$photo->name;
				$show_src = WP_CONTENT_DIR.'/wps-content/members/'.$this_owner.'/media/'.$photo->gid.'/show_'.$photo->name;
				$original_src = WP_CONTENT_DIR.'/wps-content/members/'.$this_owner.'/media/'.$photo->gid.'/'.$photo->name;
				if (file_exists($thumb_src))
					unlink($thumb_src);	
				if (file_exists($show_src))
					unlink($show_src);	
				if (file_exists($original_src))
					unlink($original_src);	
		
			}
		
			// Rebuild activity entry
			add_to_create_activity_feed($photo->gid);
			
			echo __('Photo deleted.', WPS_TEXT_DOMAIN);
	
	    } else {
	      echo __('No item ID passed', WPS_TEXT_DOMAIN);
	    }
	
	    exit;   
	    
	}
	
	// Delete all photos in an album
	if ($_POST['action'] == 'menu_gallery_manage_delete_all') {
	
	    global $wpdb, $current_user;
		
	    $album_id = 0;
	    if (isset($_POST['album_id'])) { $album_id = $_POST['album_id']; }
	 
	    if ($album_id != 0) {
	
			// First delete the album...
	   		// Check for children albums first
			if (__wps__get_current_userlevel() == 5) {
				$sql = "SELECT * FROM ".$wpdb->base_prefix."symposium_gallery WHERE parent_gid = %d ORDER BY updated DESC";
				$albums = $wpdb->get_results($wpdb->prepare($sql, $album_id));	
			} else {
				$sql = "SELECT * FROM ".$wpdb->base_prefix."symposium_gallery WHERE owner = %d AND parent_gid = %d ORDER BY updated DESC";
				$albums = $wpdb->get_results($wpdb->prepare($sql, $current_user->ID, $album_id));	
			}
		
			if ($albums) {
		      	echo __('Please delete sub albums first.', WPS_TEXT_DOMAIN);
			} else {
		
		  		$sql = "SELECT * FROM ".$wpdb->base_prefix."symposium_gallery_items WHERE gid = %d";
		  		$photos = $wpdb->get_results($wpdb->prepare($sql, $album_id));	
		  		if ($photos) {
					
					// Delete photos in this album
					// Get owner
					$this_owner = stripslashes($wpdb->get_var($wpdb->prepare("SELECT owner FROM ".$wpdb->base_prefix."symposium_gallery WHERE gid = %d", $album_id)));
				
					// check to see if storing in filesystem or database
					if (get_option(WPS_OPTIONS_PREFIX.'_img_db') == "on") {
				
						$wpdb->query( $wpdb->prepare( "DELETE FROM ".$wpdb->base_prefix."symposium_gallery_items WHERE gid = %d AND groupid=0", $album_id  ) );  
				
					} else {
				
				
						// physically delete files from filesystem within album folder
						$dir = WP_CONTENT_DIR.'/wps-content/members/'.$this_owner.'/media/'.$album_id;
						if (file_exists($dir)) {					
							$handle = opendir($dir);
							while (($file = readdir($handle)) !== false) {
								if (!is_dir($file)) {
									//unlink($dir.'/'.$file);	
								}
							}
							closedir($handle);
						}
				
						$wpdb->query( $wpdb->prepare( "DELETE FROM ".$wpdb->base_prefix."symposium_gallery_items WHERE gid = %d AND groupid=0", $album_id  ) );  
						
					}
					
					// Delete entire entry from activity
					// First get name of album
					$sql = "SELECT name FROM ".$wpdb->base_prefix."symposium_gallery WHERE gid = %d";
					$name = $wpdb->get_var($wpdb->prepare($sql, $album_id));
					// Then delete
					$sql = "DELETE FROM ".$wpdb->base_prefix."symposium_comments WHERE subject_uid = ".$current_user->ID." AND author_uid = %d AND comment LIKE '%".$name."%' AND (type = 'gallery' OR type = 'photo')";
					$wpdb->query($wpdb->prepare($sql, $current_user->ID));
					
		  		}
		
				// Now delete the album
				// Get owner
				$this_owner = stripslashes($wpdb->get_var($wpdb->prepare("SELECT owner FROM ".$wpdb->base_prefix."symposium_gallery WHERE gid = %d", $album_id)));
	
	  			$wpdb->query("DELETE FROM ".$wpdb->base_prefix."symposium_gallery_items WHERE gid = ".$album_id." AND owner = ".$this_owner);    
	   			$wpdb->query("DELETE FROM ".$wpdb->base_prefix."symposium_gallery WHERE gid = ".$album_id." AND owner = ".$this_owner);
	
				// if using filesystem, remove the folder
				$dir = WP_CONTENT_DIR.'/wps-content/members/'.$this_owner.'/media/'.$album_id;
				if (file_exists($dir)) {
					__wps__rrmdir_tmp($dir);
				}
	
	       		echo 'OK';
		
			}
			
	    } else {
	      echo __('No item ID passed', WPS_TEXT_DOMAIN);
	    }
	
	    exit;   
	    
	}
	
	// Rename photo title
	if ($_POST['action'] == 'menu_gallery_manage_rename') {
	
	    global $wpdb, $current_user;
		
	    $item_id = 0;
	    if (isset($_POST['item_id'])) { $item_id = $_POST['item_id']; }
	 
	    $new_name = '';
	    if (isset($_POST['new_name'])) { $new_name = $_POST['new_name']; }
	
	    if ($item_id != 0 && $new_name != '') {
	      $wpdb->query( $wpdb->prepare( "UPDATE ".$wpdb->base_prefix."symposium_gallery_items SET title = %s WHERE iid = %d", $new_name, $item_id  ) );  
	      echo 'OK';
	    } else {
	      echo __('Please enter a title', WPS_TEXT_DOMAIN);
	    }
	
	    exit;   
	    
	}
	
	// List albums / Create album form
	if ($_POST['action'] == 'menu_gallery') {
	
		global $wpdb, $current_user;
		
		$album_id = 0;
		if (isset($_POST['album_id'])) { $album_id = $_POST['album_id']; }
		$user_page = $_POST['uid1'];
		$user_id = $current_user->ID;
		
		$html = "<p class='__wps__profile_heading'>".__('Gallery', WPS_TEXT_DOMAIN)."</p>";
		
	    if ($album_id == 0 && $user_page == $user_id) {
			$html .= '<input type="submit" class="symposium_new_album_button __wps__button" value="'.__("Create", WPS_TEXT_DOMAIN).'" />';	
		}
	
		// Get current album
		$owner = 0;
		if ($album_id > 0) {
			
			// Breadcrumb
		    $sql = "SELECT * FROM ".$wpdb->base_prefix."symposium_gallery WHERE gid = %d";
		    $this_album = $wpdb->get_row($wpdb->prepare($sql, $album_id));	   
		    $owner = $this_album->owner;   	
		    
			$html .= '<div id="__wps__gallery_breadcrumb">';
		
				$html .= '<a href="javascript:void(0);" id="__wps__gallery_top">'.__('All albums', WPS_TEXT_DOMAIN).'</a>';
		
			   	if ($this_album->parent_gid != 0) {
					$sql = "SELECT gid, name FROM ".$wpdb->base_prefix."symposium_gallery WHERE gid = %d";
					$parent_album = $wpdb->get_row($wpdb->prepare($sql, $this_album->parent_gid));	      	
					$html .= '&nbsp;&rarr;&nbsp;<a href="javascript:void(0);" title="'.$parent_album->gid.'" id="symposium_gallery_up">'.stripslashes($parent_album->name).'</a>';
			    }           	
	
				$html .= '&nbsp;&rarr;&nbsp;<strong>'.stripslashes($this_album->name).'</strong>';
				if ($album_id != 0 && ($user_page == $user_id || __wps__get_current_userlevel($current_user->ID) == 5)) {
					$html .= '<div style="float:right"><a href="javascript:void(0);" rel="'.$album_id.'" type="submit" class="__wps__photo_delete_all">'.__('Delete this album', WPS_TEXT_DOMAIN).'</a>';
					$html .= '<br /><a href="javascript:void(0);" class="symposium_new_album_button">'.__("Create sub album", WPS_TEXT_DOMAIN).'</a></div>';	
		   	  	}
	
			$html .= '</div>';
		
		}
	
	   	$html .= "<div id='__wps__album_covers'>";
	
	   	$sql = "SELECT * FROM ".$wpdb->base_prefix."symposium_gallery WHERE owner = %d AND (parent_gid = %d OR parent_gid = 0) ORDER BY updated DESC";
	    $albums = $wpdb->get_results($wpdb->prepare($sql, $user_page, $album_id));	
	       
		// Show album covers
	   	if ($albums) {
	
			$html = apply_filters('__wps__gallery_header', $html);
	 
	       	foreach ($albums as $album) {
	
				// check for privacy
				if ( ($album->owner == $current_user->ID) || (strtolower($album->sharing) == 'public') || (is_user_logged_in() && strtolower($album->sharing) == 'everyone') || (strtolower($album->sharing) == 'friends only' && __wps__friend_of($album->owner, $current_user->ID)) || __wps__get_current_userlevel() == 5) {
	
					// Get cover image
			     	$sql = "SELECT * FROM ".$wpdb->base_prefix."symposium_gallery_items WHERE gid = %d AND cover = 'on'";
					$cover = $wpdb->get_row($wpdb->prepare($sql, $album->gid));
					
					if ($cover) {
		
						if (get_option(WPS_OPTIONS_PREFIX.'_img_db') == "on") {
							// Database
							$thumb_src = WP_CONTENT_URL."/plugins/wp-symposium/get_album_item.php?iid=".$cover->iid."&size=thumbnail";
						} else {
							// Filesystem
							if (file_exists(get_option(WPS_OPTIONS_PREFIX.'_img_path').'/members/'.$cover->owner.'/media/'.$album->gid.'/thumb_'.$cover->name)) {
				        	    $thumb_src = get_option(WPS_OPTIONS_PREFIX.'_img_url').'/members/'.$cover->owner.'/media/'.$album->gid.'/thumb_'.$cover->name;
							} else {
								$thumb_src = get_option(WPS_OPTIONS_PREFIX.'_images').'/broken_file_link.png';
							}
						}
						
					} else {
						$thumb_src = get_option(WPS_OPTIONS_PREFIX.'_images')."/unknown.jpg";
					}
		
		       		// Show cover
		        	if ($album->parent_gid == $album_id) {
						$html .= '<div class="__wps__album_outer">';
		   				$html .= '<div class="__wps__album_inner">';
								$html .= '<div class="__wps__album_cover">';
		 							$html .= '<a class="__wps__album_cover_action" href="javascript:void(0);" title="'.$album->gid.'">';
		 								$html .= '<img class="__wps__album_image" src="'.$thumb_src.'" />';
		 								$html .= '</a>';
									$html .= '</div>';
		 						$html .= '</div>';
		 					$html .= '<div class="__wps__album_title">'.stripslashes($album->name).'</div>';
		  				$html .= '</div>';
					}
				}
	       
			}
	       		
	    } else {
	
	    	if ($user_page == $user_id) {
	        	$html .= "<div class='symposium_new_album_button __wps__menu_gallery_alert'>".__("Start by creating an album", WPS_TEXT_DOMAIN)."</div>";
	        } else {
	        	$html .= __("No albums yet.", WPS_TEXT_DOMAIN);
	       	}
	       		
	    }
	   	
	 	$html .= "</div>";
	
		// Show contents of album (so long as in an album)
		if ($album_id > 0) {
			$html .= "<div id='__wps__album_content'>";
	   
	  		$sql = "SELECT * FROM ".$wpdb->base_prefix."symposium_gallery_items WHERE gid = %d ORDER BY photo_order";
	  		$photos = $wpdb->get_results($wpdb->prepare($sql, $album_id));	
	
	    	if ($user_page == $user_id) {
	
				// Sharing for this album
				$share = $this_album->sharing;
				$album_owner = $this_album->owner;
		
				$html .= __('Share with:', WPS_TEXT_DOMAIN).' ';
				$html .= '<select title = '.$album_id.' id="gallery_share">';
					$html .= "<option value='nobody'";
						if ($share == 'nobody') { $html .= ' SELECTED'; }
						$html .= '>'.__('Nobody', WPS_TEXT_DOMAIN).'</option>';
					$html .= "<option value='friends only'";
						if ($share == 'friends only') { $html .= ' SELECTED'; }
						$html .= '>'.sprintf(__('%s Only', WPS_TEXT_DOMAIN), get_option(WPS_OPTIONS_PREFIX.'_alt_friends')).'</option>';
					$html .= "<option value='everyone'";
						if ($share == 'everyone') { $html .= ' SELECTED'; }
						$html .= '>'.stripslashes(get_option(WPS_OPTIONS_PREFIX.'_alt_everyone')).'</option>';
					$html .= "<option value='public'";
						if ($share == 'public') { $html .= ' SELECTED'; }
						$html .= '>'.__('Public', WPS_TEXT_DOMAIN).'</option>';
				$html .= '</select>';
				$html .= " <img id='__wps__album_sharing_save' style='display:none' src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/busy.gif' /><br />";
				
				// Show maximum file upload size as set in PHP.INI to admin's
				if (__wps__get_current_userlevel($current_user->ID) == 5) {
					$html .= '<p>As set in PHP.INI, the upload_max_filesize is: '.ini_get('upload_max_filesize').'<br />(this message is only shown to site administrators)</p>';
				} else {
					$html .= '<p>'.__('The maximum size of uploaded files is', WPS_TEXT_DOMAIN).' '.ini_get('upload_max_filesize').'.</p>';
				}

				include_once('../server/file_upload_include.php');
				$html .= show_upload_form(
					WP_CONTENT_DIR.'/wps-content/members/'.$current_user->ID.'/gallery_upload/', 
					WP_CONTENT_URL.'/wps-content/members/'.$current_user->ID.'/gallery_upload/',
					'gallery',
					__('Upload photo(s)', WPS_TEXT_DOMAIN),
					0,
					0,
					$album_id
				);
				$html .= "<div id='__wps__gallery_flag' style='display:none'></div>"; // So that __wps__init_file_upload() knows it's the gallery
	
			}
	  	
	    	if ($photos) {
	
				$cnt=0;
		
		       	foreach ($photos as $photo) {
	
					$cnt++;
					
		            // Add photo
					$thumbnail_size = ($value = get_option(WPS_OPTIONS_PREFIX."_gallery_thumbnail_size")) ? $value : '75';
		
					// DB or Filesystem
					if (get_option(WPS_OPTIONS_PREFIX.'_img_db') == "on") {
						$img_src = WP_CONTENT_URL."/plugins/wp-symposium/get_album_item.php?iid=".$photo->iid."&size=photo";
						$thumb_src = WP_CONTENT_URL."/plugins/wp-symposium/get_album_item.php?iid=".$photo->iid."&size=thumbnail";
					} else {
	
						$file_check = get_option(WPS_OPTIONS_PREFIX.'_img_path').'/members/'.$user_page.'/media/'.$album_id.'/thumb_'.$photo->name;
						if (file_exists($file_check)) {
	
							if (get_option(WPS_OPTIONS_PREFIX."_gallery_show_resized") == 'on') {
				               	$img_src = get_option(WPS_OPTIONS_PREFIX.'_img_url').'/members/'.$user_page.'/media/'.$album_id.'/show_'.$photo->name;
							} else {
				               	$img_src = get_option(WPS_OPTIONS_PREFIX.'_img_url').'/members/'.$user_page.'/media/'.$album_id.'/'.$photo->name;
							}
			        	    $thumb_src = get_option(WPS_OPTIONS_PREFIX.'_img_url').'/members/'.$user_page.'/media/'.$album_id.'/thumb_'.$photo->name;
	
						} else {
							$img_src = get_option(WPS_OPTIONS_PREFIX.'_images').'/broken_file_link.png';						
							$thumb_src = get_option(WPS_OPTIONS_PREFIX.'_images').'/broken_file_link.png';						
						}
					}
	
		            $html .= '<div class="__wps__photo_outer">';
	           			$html .= '<div class="__wps__photo_inner">';
							$html .= '<div class="__wps__photo_cover">';
							$html .= '<a class="__wps__photo_cover_action wps_gallery_album" data-owner="'.$owner.'" data-iid="'.$photo->iid.'" data-name="'.stripslashes($photo->title).'" href="'.$img_src.'" rev="'.$cnt.'" rel="symposium_gallery_photos_'.$album_id.'" title="'.stripslashes($this_album->name).'">';
								$html .= '<img class="__wps__photo_image" style="width:'.$thumbnail_size.'px; height:'.$thumbnail_size.'px;" src="'.$thumb_src.'" />';
							$html .= '</a>';
							$html .= '</div>';
						$html .= '</div>';
					$html .= '</div>';
		      				
		       	}
	  	
	    	} else {
	  	
	          	 	$html .= __("No photos yet.", WPS_TEXT_DOMAIN);
	         
	    	}
	   
			$html .= "</div>";
		}	
	
		// Create new album form
		if ($album_id != '') {
			$this_album = stripslashes($wpdb->get_var($wpdb->prepare("SELECT name FROM ".$wpdb->base_prefix."symposium_gallery WHERE gid = %d", $album_id)));
			$this_id = $album_id; 
		} else {
			$this_album = 'None';
			$this_id = 0;
		}
	
		$html .= "<div id='__wps__create_gallery'>";
	
			$html .= '<div class="new-topic-subject label">'.__("Name of new album", WPS_TEXT_DOMAIN).'</div>';
			$html .= "<input id='symposium_new_album_title' class='new-topic-subject-input' type='text'>";
	
			if ($this_id > 0) {
				$html .= "<div class='__wps__create_sub_gallery label'>";
				$html .= "<input type='checkbox' title='".$this_id."' id='__wps__create_sub_gallery_select' CHECKED> ".__("Create as a sub-album of ".$this_album, WPS_TEXT_DOMAIN);
				$html .= "</div>";
			}
			
			$html .= "<div style='margin-top:10px'>";
			$html .= '<input id="symposium_new_album" type="submit" class="__wps__button" style="float: left" value="'.__("Create", WPS_TEXT_DOMAIN).'" />';
			$html .= '<input id="symposium_cancel_album" type="submit" class="__wps__button clear" onClick="javascript:void(0)" value="'.__("Cancel", WPS_TEXT_DOMAIN).'" />';
			$html .= "</div>";
	
		$html .= "</div>";
	
		echo $html;
		exit;
		
	}
	
	// Create album
	if ($_POST['action'] == 'create_album') {
	
		global $wpdb, $current_user;
        
        if (is_user_logged_in()) {
		
            $name = sanitize_text_field($_POST['name']);
            $sub_album = $_POST['sub_album'];
            if ($sub_album == 'true') {
                $parent = $_POST['parent'];
            } else {
                $parent = 0;
            }

            // Create new album
            $wpdb->query( $wpdb->prepare( "
            INSERT INTO ".$wpdb->base_prefix."symposium_gallery
            ( 	parent_gid, 
                name,
                description, 
                owner, 
                sharing, 
                editing, 
                created, 
                updated, 
                is_group
            )
            VALUES ( %d, %s, %s, %d, %s, %s, %s, %s, %s )", 
            array(
                $parent, 
                $name,
                '', 
                $current_user->ID, 
                'everyone', 
                'nobody', 
                date("Y-m-d H:i:s"),
                date("Y-m-d H:i:s"),
                ''
                ) 
            ) );

            echo $wpdb->insert_id;
            
        }
		exit;
	
	}		
	
	// Widget
	if ($_POST['action'] == 'Gallery_Widget') {
	
		$albumcount = $_POST['albumcount'];
		__wps__do_Gallery_Widget($albumcount);
	}
	
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

	
?>

	
