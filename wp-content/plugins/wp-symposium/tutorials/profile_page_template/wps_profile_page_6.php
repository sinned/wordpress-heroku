<?php
/**
 * Template Name: Demo profile page 6
 * Description: A Profile Page Template to demonstrate using classes
 *
 * @package WordPress
 * @subpackage Twenty_Eleven
 * @since Twenty Eleven 1.0
 */

get_header(); 

// include the PHP class files, the path should match your server!
require_once(ABSPATH.'wp-content/plugins/wp-symposium/class.wps.php');
require_once(ABSPATH.'wp-content/plugins/wp-symposium/class.wps_user.php');
require_once(ABSPATH.'wp-content/plugins/wp-symposium/class.wps_ui.php');

$wps = new wps();
$wps_user = new wps_user($wps->get_current_user_page()); // default to current user, or pass a user ID
$wps_ui = new wps_ui();

/*
First we over-ride settings for profile page to ensure links to other members go to
the correct page. Note that you will need to visit/reload this page 
the first time the script is run, as various constants are set prior to this page template
loading. If you visit Admin->Installation the default values will be reset, 
which includes after upgrading, so re-visit this page at least once after visiting 
the Installation page, to put things back to the new page. Alternatively, create a 
page that updates this (and maybe other) URLs that you can visit as admin once after upgrading WPS.

This is hardcoded to a particular page for now. If distributing to other user's this will
need to be dynamically set! Change it to make the URL of your new profile page, mine is as
per the tutorial (ie. a page called "AA Profile").

If you are using WPS Permalinks, make sure you update the Member Profile permalink on the
Installation page.
*/

$wps->set_profile_url('/aa-profile');
?>

<!--
Links to styles used in this page template - shouldn't be included in the page template really,
but is included here to keep things simple for the tutorial at www.wpsymposium.com/blog.
Should be included in the theme header.php in the <HEAD> ... </HEAD> tags.
This also assumes the .css file is also in the current theme folder along with this page file. 
-->
<link rel="stylesheet" type="text/css" href="<?php bloginfo('template_url'); ?>/wps_profile_page.css" />

<div id="primary">
	<div id="content" role="main">
	<?php
	// Sidebar
	echo '<div id="my-sidebar">';
		echo '<div id="my-profile-box">';

			// Show avatar
			echo $wps_user->get_avatar(204);
			
			if ($wps_user->get_id() != $current_user->ID) {

				// Friends?
				if ($wps_user->is_friend()) {
					echo 'You are friends.<br />';
				} else {
					// Pending friend?
					if ($wps_user->is_pending_friend()) {
						echo 'Friendship requested.';
						echo $wps_ui->friendship_cancel($wps_user->get_id(), 'Cancel', 'Cancelled!', 'my-submit-button');
					} else {
						// Not a friend
						echo 'Make friends with '.$wps_user->get_display_name().'...';
						echo $wps_ui->friendship_add($wps_user->get_id(), "Type a message, hit return!", "Request sent.", "my-input-box");
					}
				}
				
				// Poke(tm) button
				echo '<div style="margin-top:10px; margin-bottom:10px">'.$wps_ui->poke_button(get_option(WPS_OPTIONS_PREFIX.'_poke_label'), "my-submit-button").'</div>';
	
			}

			// Show profile information if permitted to do so
			if ($wps_user->is_permitted('personal')) { // defaults to activity permission, set to "personal" for personal info

				// Location
				if ($wps_user->get_city()) echo '<span class="my-info-label">Lives in:</span> '.$wps_user->get_city();
				if ($wps_user->get_city() && ($wps_user->get_country())) echo ', ';
				if ($wps_user->get_country()) echo $wps_user->get_country();
				if ($wps_user->get_city() || $wps_user->get_country()) echo '<br />';
				
				// Date of birth
				if ($wps_user->get_dob_day() && $wps_user->get_dob_month() && $wps_user->get_dob_year()) {
					echo '<span class="my-info-label">Born:</span> '.$wps_user->get_dob_day().' '.__wps__get_monthname($wps_user->get_dob_month()).' '.$wps_user->get_dob_year().'<br />';
				}
				
				// Extended fields
				$extended = $wps_user->get_extended();
				foreach ($extended as $row) {
					if ($row['type'] != 'Checkbox') {
						echo '<span class="my-info-label">'.stripslashes($row['name']).':</span> '.stripslashes($row['value']).'<br />';					
					} else {
						echo $row['name'].'<br />';
					}
				}
				
				// List friends
				$friends = $wps_user->get_friends();
				if ($friends) {
					echo '<div id="my-friends-list">';
						echo '<div style="font-weight:bold; margin-bottom:6px;">Friends</div>';
						foreach ($friends AS $friend) {
							if ($friend['id']) {
								echo '<div class="my-friends-list-item">';
									$friend_user = new wps_user($friend['id']);
									echo '<div style="float:left;margin-right:6px">'.$friend_user->get_avatar(44).'</div>';
									echo $friend_user->get_profile_url().'<br />';
									echo '<span class="my-info-label">Active: '.__wps__time_ago($friend_user->get_last_activity()).'</span>';
								echo '</div>';
							}
						}
					echo '</div>';
				} else {
					echo '<div id="my-friends-list">No friends</div>';
				}

			}
						
		echo '</div>';				
	echo '</div>';		
	
	// The member's page "header"
	echo '<div id="my-header-div">';

		echo '<div id="my-display-name">'.$wps_user->get_display_name().'</div>';

		// Show latest activity post by the member, if permitted
		if ($wps_user->is_permitted()) { // defaults to activity permission, set to "personal" for personal info
			echo '<div>';
			echo $wps_user->get_latest_activity().' '.__wps__time_ago($wps_user->get_latest_activity_age());
			echo '</div>';	
		}
			
		// Insert activity post form elements if on own profile page
		if ($wps_user->get_id() == $current_user->ID) {
			$box = $wps_ui->whatsup("What's up?", "my-input-box");  // parameters are optional
			$button = $wps_ui->whatsup_button("Post", "my-submit-button");  // parameters are optional
			echo '<div style="float:left;margin-top:10px;margin-right:10px;">'.$box.'</div>';
			echo '<div style="float:left;margin-top:10px;">'.$button.'</div>';
			// Add Facebook Connect plugin (plugin needs to be activated)
			echo '<div style="clear:both;padding-top:5px;">'.$wps_ui->facebook_connect($current_user->ID).'</div>';
		}	
		
	echo '</div>';
	
	// The content area
	echo '<div id="my-content" class="__wps__wrapper">';	
		if ($wps_user->is_permitted()) { 

			// Show menu of choices
			echo '<div id="my-menu">';
				if ($wps_user->get_id() == $current_user->ID) {
					echo '<div id="menu_wall" class="__wps__my_profile_menu">My Activity</div>';
				} else {
					echo '<div id="menu_wall" class="__wps__my_profile_menu">Activity</div>';
				}
				echo '<div id="menu_activity" class="__wps__my_profile_menu">Friends Activity</div>';
				echo '<div id="menu_all" class="__wps__my_profile_menu">All Activity</div>';

				// Additional items
				echo '<div id="menu_friends" class="__wps__profile_menu __wps__profile_menu_icon"><img src="'.$wps->get_images_url().'/tutorial_images/friends.png" /></div>';
				echo '<div id="menu_groups" class="__wps__profile_menu __wps__profile_menu_icon"><img src="'.$wps->get_images_url().'/tutorial_images/groups.png" /></div>';
				if ($wps_user->get_id() == $current_user->ID) {
					echo '<div id="menu_avatar" class="__wps__profile_menu __wps__profile_menu_icon"><img src="'.$wps->get_images_url().'/tutorial_images/upload_avatar.png" /></div>';
					echo '<div id="menu_personal" class="__wps__profile_menu __wps__profile_menu_icon"><img src="'.$wps->get_images_url().'/tutorial_images/edit_profile.png" /></div>';
					echo '<div id="menu_settings" class="__wps__profile_menu __wps__profile_menu_icon"><img src="'.$wps->get_images_url().'/tutorial_images/edit_settings.png" /></div>';
				}
	      		    
			echo '</div>';
			
			// Placeholder for content, with default view (wall, activity, all)
			echo '<div style="clear:both;">';
			echo $wps_ui->profile_placeholder("wall", "my-placeholder");
			echo '</div>';
							
		} else {
			echo '&nbsp;Content is only available to friends.';
		}
	echo '</div>';
	?>
	
	</div><!-- #content -->
</div><!-- #primary -->
<?php get_footer(); ?>
