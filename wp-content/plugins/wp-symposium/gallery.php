<?php
/*
WP Symposium Gallery
Description: Photo Albums for WP Symposium. Add [symposium-galleries] to display all galleries across the site.
*/



// Get constants
require_once(dirname(__FILE__).'/default-constants.php');

function __wps__gallery(){}

/* ====================================================== ADMIN ====================================================== */

require_once(WPS_PLUGIN_DIR . '/functions.php');


/* ====================================================== ADD PLUGIN JAVASCRIPT TO WORDPRESS ====================================================== */

function __wps__gallery_init()
{

}
add_action('init', '__wps__gallery_init');

/* ===================================================================== WIDGETS ======================================================================== */

add_action( 'widgets_init', '__wps__gallery_load_widgets' );

function __wps__gallery_load_widgets() {
	register_widget( 'Gallery_Widget' );
}

class Gallery_Widget extends WP_Widget {

	function Gallery_Widget() {
		/* Widget settings. */
		$widget_ops = array( 'classname' => 'widget_gallery', 'description' => __('Shows albums that have had new items recently uploaded into them.', WPS_TEXT_DOMAIN) );
		
		/* Widget control settings. */
		$control_ops = array( 'id_base' => 'gallery-widget' );
		
		/* Create the widget. */
		$this->WP_Widget( 'gallery-widget', WPS_WL_SHORT.': '.__('Gallery', WPS_TEXT_DOMAIN), $widget_ops, $control_ops );
	}
	
	// This is shown on the page
	function widget( $args, $instance ) {
		global $wpdb, $current_user;
		wp_get_current_user();
	
		extract( $args );
				
		// Get options
		$wtitle = apply_filters('widget_title', $instance['wtitle'] );
		$albumcount = apply_filters('widget_postcount', $instance['albumcount'] );
		
		// Start widget
		echo $before_widget;
		echo $before_title . $wtitle . $after_title;
		
		if (get_option(WPS_OPTIONS_PREFIX.'_ajax_widgets') == 'on') {		
			// Parameters for AJAX
			echo '<div id="symposium_Gallery_Widget">';
			echo "<img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/busy.gif' />";
			echo '<div id="symposium_Gallery_Widget_albumcount" style="display:noneX">'.$albumcount.'</div>';
			echo '</div>';
		} else {
			__wps__do_Gallery_Widget($albumcount);			
		}

		
		// End content
		
		echo $after_widget;
		// End widget
	}
	
	// This updates the stored values
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		
		/* Strip tags (if needed) and update the widget settings. */
		$instance['wtitle'] = strip_tags( $new_instance['wtitle'] );
		$instance['albumcount'] = strip_tags( $new_instance['albumcount'] );
		return $instance;
	}
	
	// This is the admin form for the widget
	function form( $instance ) {

		/* Set up some default widget settings. */
		$defaults = array( 'wtitle' => 'Recent photos', 'albumcount' => '5' );
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>

		<p>
			<label for="<?php echo $this->get_field_id( 'wtitle' ); ?>"><?php echo __('Widget Title', WPS_TEXT_DOMAIN); ?>:</label>
			<input id="<?php echo $this->get_field_id( 'wtitle' ); ?>" name="<?php echo $this->get_field_name( 'wtitle' ); ?>" value="<?php echo $instance['wtitle']; ?>" />
		<br /><br />
			<label for="<?php echo $this->get_field_id( 'albumcount' ); ?>"><?php echo __('Max number of albums', WPS_TEXT_DOMAIN); ?>:</label>
			<input id="<?php echo $this->get_field_id( 'albumcount' ); ?>" name="<?php echo $this->get_field_name( 'albumcount' ); ?>" value="<?php echo $instance['albumcount']; ?>" style="width: 30px" />
		</p>
		<?php
	}

}

// Shared function for AJAX and NON-AJAX mode of widget
function __wps__do_Gallery_Widget($albumcount) {
	
	global $wpdb, $current_user;
	
	$shown_aid = "";
	$shown_count = 0;

	// Get profile URL worked out
	$profile_url = __wps__get_url('profile');
	$q = __wps__string_query($profile_url);

	// Content of widget
	$sql = "SELECT * FROM ".$wpdb->base_prefix."symposium_gallery g INNER JOIN ".$wpdb->base_prefix."users u ON g.owner = u.ID WHERE is_group != 'on' ORDER BY updated DESC LIMIT 0,50";
	$albums = $wpdb->get_results($sql);
		
	if ($albums) {

		echo "<div id='__wps__gallery_recent_activity'>";
			
			foreach ($albums as $album)
			{
				if ($shown_count < $albumcount) {

					if (strpos($shown_aid, $album->gid.",") === FALSE) { 

						if ( (is_user_logged_in() && strtolower($album->sharing) == 'everyone') || (strtolower($album->sharing) == 'public') || (strtolower($album->sharing) == 'friends only' && __wps__friend_of($album->owner, $current_user->ID)) ) {

							echo "<div class='__wps__gallery_recent_activity_row'>";		
								echo "<div class='__wps__gallery_recent_activity_row_avatar'>";
									echo get_avatar($album->owner, 32);
								echo "</div>";
								echo "<div class='__wps__gallery_recent_activity_row_post'>";
 									$text = __('added to ', WPS_TEXT_DOMAIN)." <a href='".$profile_url.$q."uid=".$album->owner."&embed=on&album_id=".$album->gid."'>".stripslashes($album->name)."</a>";
									echo "<a href='".$profile_url.$q."uid=".$album->owner."'>".$album->display_name."</a> ".$text." ".__wps__time_ago($album->updated);
								echo "</div>";
							echo "</div>";
						
							$shown_count++;
							$shown_aid .= $album->gid.",";							
						}
					}
				} else {
					break;
				}
			}

		echo "</div>";

	}
}

// Add [symposium-galleries] shortcode for site wide list of albums
function __wps__show_gallery() {
	
	global $wpdb, $current_user;
		
	$html = '';
	$html .= "<div class='__wps__wrapper'>";

	$term = "";
	if (isset($_GET['term'])) { $term .= strtolower($_GET['term']); }	
		
	$html .= "<div style='padding:0px'>";
	$html .= '<input type="text" id="gallery_member" autocomplete="off" name="gallery_member" class="gallery_member_box" value="'.$term.'" style="margin-right:10px" />';
	$html .= '<input id="gallery_go_button" type="submit" class="__wps__button" value="'.__("Search", WPS_TEXT_DOMAIN).'" />';
	$html .= "</div>";	
	
	$sql = "SELECT g.*, u.display_name FROM ".$wpdb->base_prefix."symposium_gallery g
			INNER JOIN ".$wpdb->base_prefix."users u ON u.ID = g.owner
			WHERE g.name LIKE '%".$term."%' 
			   OR u.display_name LIKE '%".$term."%' 
			ORDER BY gid DESC 
			LIMIT 0,50";

	$albums = $wpdb->get_results($sql);
	
	$album_count = 0;	
	$total_count = 0;
	
	if ($albums) {
		
		$page_length = (get_option(WPS_OPTIONS_PREFIX."_gallery_page_length") != '') ? get_option(WPS_OPTIONS_PREFIX."_gallery_page_length") : 10;

		$html .= "<div id='symposium_gallery_albums'>";
		
		foreach ($albums AS $album) {

			$total_count++;				

			// check for privacy
			if ( ($album->owner == $current_user->ID) || (strtolower($album->sharing) == 'public') || (is_user_logged_in() && strtolower($album->sharing) == 'everyone') || (strtolower($album->sharing) == 'friends only' && __wps__friend_of($album->owner, $current_user->ID)) || __wps__get_current_userlevel() == 5) {

				$sql = "SELECT COUNT(iid) FROM ".$wpdb->base_prefix."symposium_gallery_items WHERE gid = %d";
				$photo_count = $wpdb->get_var($wpdb->prepare($sql, $album->gid));	
				
				if ($photo_count > 0) {

					$sql = "SELECT * FROM ".$wpdb->base_prefix."symposium_gallery_items WHERE gid = %d ORDER BY photo_order DESC";
					$photos = $wpdb->get_results($wpdb->prepare($sql, $album->gid));	
					
					// Check that at least one actually exists
					$tmpDir = get_option(WPS_OPTIONS_PREFIX.'_img_path');
					$img_exists = false;
					if ($photos && get_option(WPS_OPTIONS_PREFIX.'_img_db') != "on") {
						foreach ($photos as $photo) {
		                	$img_src = '/members/'.$album->owner.'/media/'.$album->gid.'/'.$photo->name;
		                	if (file_exists($tmpDir.$img_src)) {
		                	    $img_exists = true;
		                	    break;
		                	}
						}
					} else {
						$img_exists = true;
					}
					
					if ($img_exists) {
						
						$html .= "<div id='__wps__album_content' style='padding-bottom:30px;'>";
	
							$html .= "<div id='wps_gallery_album_name_".$album->gid."' class='topic-post-header'>".stripslashes($album->name)."</div>";
							$html .= "<p>".__wps__profile_link($album->owner)."</p>";
				
							if ($photos) {
								
								$album_count++;
								
								$cnt = 0;
			
								$thumbnail_size = (get_option(WPS_OPTIONS_PREFIX."_gallery_thumbnail_size") != '') ? get_option(WPS_OPTIONS_PREFIX."_gallery_thumbnail_size") : 75;
								$html .= '<div id="wps_comment_plus" style="width:98%;height:'.($thumbnail_size+10).'px;overflow:hidden; ">';
					
								$preview_count = (get_option(WPS_OPTIONS_PREFIX."_gallery_preview") != '') ? get_option(WPS_OPTIONS_PREFIX."_gallery_preview") : 5;
					       		foreach ($photos as $photo) {
					       		    
					       		    $cnt++;
					              					
									if (get_option(WPS_OPTIONS_PREFIX.'_img_db') == "on") {
										
										$img_src = WP_CONTENT_URL."/plugins/wp-symposium/get_album_item.php?iid=".$photo->iid."&size=photo";
										$thumb_src = WP_CONTENT_URL."/plugins/wp-symposium/get_album_item.php?iid=".$photo->iid."&size=thumbnail";
										
									} else {

					                	$tmp_src = '/members/'.$album->owner.'/media/'.$album->gid.'/thumb_'.$photo->name;
              							if (file_exists($tmpDir.$tmp_src)) { 

											if (get_option(WPS_OPTIONS_PREFIX."_gallery_show_resized") == 'on') {
							                	$img_src = get_option(WPS_OPTIONS_PREFIX.'_img_url').'/members/'.$album->owner.'/media/'.$album->gid.'/show_'.$photo->name;
											} else {
							                	$img_src = get_option(WPS_OPTIONS_PREFIX.'_img_url').'/members/'.$album->owner.'/media/'.$album->gid.'/'.$photo->name;
											}
						        	        $thumb_src = get_option(WPS_OPTIONS_PREFIX.'_img_url').'/members/'.$album->owner.'/media/'.$album->gid.'/thumb_'.$photo->name;
					        	        
              							} else {
              							    $img_src = __wps__siteURL().get_option(WPS_OPTIONS_PREFIX.'_images').'/broken_file_link.png';
						        	        $thumb_src = __wps__siteURL().get_option(WPS_OPTIONS_PREFIX.'_images').'/broken_file_link.png';
              							}              							    
              							
									}
					
					               	$html .= '<div class="__wps__photo_outer">';
					           			$html .= '<div class="__wps__photo_inner">';
					      						$html .= '<div class="__wps__photo_cover">';
												$html .= '<a class="__wps__photo_cover_action wps_gallery_album" data-owner="'.$album->owner.'" data-iid="'.$photo->iid.'" data-name="'.stripslashes($photo->title).'" href="'.$img_src.'" rev="'.$cnt.'" rel="symposium_gallery_photos_'.$album->gid.'" title="'.stripslashes($album->name).'">';
					        						$thumbnail_size = (get_option(WPS_OPTIONS_PREFIX."_gallery_thumbnail_size") != '') ? get_option(WPS_OPTIONS_PREFIX."_gallery_thumbnail_size") : 75;
					        						$html .= '<img class="__wps__photo_image" style="width:'.$thumbnail_size.'px; height:'.$thumbnail_size.'px;" src="'.$thumb_src.'" />';
					        					$html .= '</a>';
					     						$html .= '</div>';
					       					$html .= '</div>';
					     				$html .= '</div>';
			
						       		if (count($photos) > $preview_count && $cnt == $preview_count) {
						       		    $html .= '<div id="wps_gallery_comment_more" style="cursor:pointer">'.__('more...', WPS_TEXT_DOMAIN).'<div style="clear:both"></div></div>';
						       		}   		
					      				
					       		}
					       		
					       		$html .= '</div>';							
							
							} else {
							
						      	 $html .= __("No photos yet.", WPS_TEXT_DOMAIN);
						     
							}
			
						$html .= '</div>';	
					}
				}	
	
				if ($album_count == $page_length) { break; }
				
			}
		
		}
	
		$html .= "<div style='clear:both;text-align:center; margin-top:20px; width:100%'><a href='javascript:void(0)' id='showmore_gallery'>".__("more...", WPS_TEXT_DOMAIN)."</a></div>";
		
		$html .= '</div>';
		
		
	}

	// Stores start value for more
	$html .= '<div id="symposium_gallery_start" style="display:none">'.$total_count.'</div>';
	$html .= '<div id="symposium_gallery_page_length" style="display:none">'.$page_length.'</div>';


	$html .= '</div>';
	return $html;
}
if (!is_admin()) {
	add_shortcode(WPS_SHORTCODE_PREFIX.'-galleries', '__wps__show_gallery');  
}

/* ====================================================== HOOKS/FILTERS INTO WORDPRESS/WP Symposium ====================================================== */

// Add plugin to admin menu via hook
function __wps__add_gallery_to_admin_menu()
{
	$hidden = get_option(WPS_OPTIONS_PREFIX.'_long_menu') == "on" ? '_hidden': '';
	add_submenu_page('symposium_debug'.$hidden, __('Gallery', WPS_TEXT_DOMAIN), __('Gallery', WPS_TEXT_DOMAIN), 'manage_options', WPS_DIR.'/gallery_admin.php');
}
add_action('__wps__admin_menu_hook', '__wps__add_gallery_to_admin_menu');

// ----------------------------------------------------------------------------------------------------------------------------------------------------------

// Add Menu item to Profile Menu through filter provided
// The menu picks up the id of div with id of menu_ (eg: menu_GALLERY) and will then run
// 'path-to/wp-symposium-GALLERY/ajax/GALLERY_functions.php' when clicked.
// It will pass $_POST['action'] set to menu_GALLERY to then be acted upon.

// ----------------------------------------------------------------------------------------------------------------------------------------------------------

function __wps__add_gallery_menu($html,$uid1,$uid2,$privacy,$is_friend,$extended,$share)  
{  
	global $current_user;

	if ( ($uid1 == $uid2) || (is_user_logged_in() && strtolower($privacy) == 'everyone') || (strtolower($privacy) == 'public') || (strtolower($privacy) == 'friends only' && $is_friend) || __wps__get_current_userlevel() == 5) {
  
		if ($uid1 == $uid2) {
			if (get_option(WPS_OPTIONS_PREFIX.'_menu_gallery'))
				$html .= '<div id="menu_gallery" class="__wps__profile_menu">'.(($t = get_option(WPS_OPTIONS_PREFIX.'_menu_gallery_text')) != '' ? $t :  __('My Gallery', WPS_TEXT_DOMAIN)).'</div>';  
		} else {
			if (get_option(WPS_OPTIONS_PREFIX.'_menu_gallery_other'))
				$html .= '<div id="menu_gallery" class="__wps__profile_menu">'.(($t = get_option(WPS_OPTIONS_PREFIX.'_menu_gallery_other_text')) != '' ? $t :  __('Gallery', WPS_TEXT_DOMAIN)).'</div>';  
		}
	}
	return $html;
}  
add_filter('__wps__profile_menu_filter', '__wps__add_gallery_menu', 10, 7);

function __wps__add_gallery_menu_tabs($html,$title,$value,$uid1,$uid2,$privacy,$is_friend,$extended,$share)  
{  
	if ($value == 'gallery') {

		global $current_user;
	
		if ( ($uid1 == $uid2) || (is_user_logged_in() && strtolower($privacy) == 'everyone') || (strtolower($privacy) == 'public') || (strtolower($privacy) == 'friends only' && $is_friend) || __wps__get_current_userlevel() == 5)	  
			$html .= '<li id="menu_gallery" class="__wps__profile_menu" href="javascript:void(0)">'.$title.'</li>';		
	} 
	
	return $html;

}  
add_filter('__wps__profile_menu_tabs', '__wps__add_gallery_menu_tabs', 10, 9);


?>
