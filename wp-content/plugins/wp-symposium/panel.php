<?php
/*
WP Symposium Panel
Description: Panel bottom corner of screen to display new mail, friends online, etc. Also controls live chat windows and online status.
*/

// Get constants
require_once(dirname(__FILE__).'/default-constants.php');

/* ====================================================== PHP FUNCTIONS ====================================================== */

// Adds notification bar
function __wps__add_notification_bar()  
{  

   	global $wpdb, $current_user;
	wp_get_current_user();

	$plugin = WPS_PLUGIN_URL;

	if ( is_user_logged_in() ) {

		$use_chat = get_option(WPS_OPTIONS_PREFIX.'_use_chat');
		if (get_option(WPS_OPTIONS_PREFIX.'_wps_lite')) 
			$use_chat = ''; 
		$inactive = get_option(WPS_OPTIONS_PREFIX.'_online');
		$offline = get_option(WPS_OPTIONS_PREFIX.'_offline');
		if (get_option(WPS_OPTIONS_PREFIX.'_use_styles') == "on")
			$border_radius = get_option(WPS_OPTIONS_PREFIX.'_border_radius');


		?>
			
		<style>

			<?php if (get_option(WPS_OPTIONS_PREFIX.'_use_styles') == "on") { 
				echo '.header_bg_blink {';
					echo 'background-color: '.get_option(WPS_OPTIONS_PREFIX.'_categories_background');
				echo '}';
			} ?>
			
			.__wps__online_box {
				<?php if (isset($border_radius)) { ?>
					border-radius: <?php echo $border_radius; ?>px;
					-moz-border-radius: <?php echo $border_radius; ?>px;
				<?php } ?>
				<?php if (!function_exists('__wps__profile')) {
					echo 'display: none';
				}?>
			}
			.__wps__online_box-none {
				<?php if (isset($border_radius)) { ?>
					border-radius: <?php echo $border_radius; ?>px;
					-moz-border-radius: <?php echo $border_radius; ?>px;
				<?php } ?>
				<?php if (!function_exists('__wps__profile')) {
					echo 'display: none';
				}?>
			}
			
			#__wps__logout {
				background-image:url('<?php echo get_option(WPS_OPTIONS_PREFIX.'_images'); ?>/logout.gif');
				<?php if (isset($border_radius)) { ?>
					border-radius: <?php echo $border_radius; ?>px;
					-moz-border-radius: <?php echo $border_radius; ?>px;
				<?php } ?>
			}
										
			.__wps__email_box {
				<?php if (isset($border_radius)) { ?>
					border-radius: <?php echo $border_radius; ?>px;
					-moz-border-radius: <?php echo $border_radius; ?>px;
				<?php } ?>
				<?php if (!function_exists('__wps__mail')) {
					echo 'display: none';
				}?>
			}
			.__wps__email_box-read {
				background-image:url('<?php echo get_option(WPS_OPTIONS_PREFIX.'_images'); ?>/email.gif');
			}
			.__wps__email_box-unread {
				background-image:url('<?php echo get_option(WPS_OPTIONS_PREFIX.'_images'); ?>/emailunread.gif');
			}

			.__wps__friends_box {
				<?php if (isset($border_radius)) { ?>
					border-radius: <?php echo $border_radius; ?>px;
					-moz-border-radius: <?php echo $border_radius; ?>px;
				<?php } ?>
				<?php if (!function_exists('__wps__profile')) {
					echo 'display: none';
				}?>
			}
			.__wps__friends_box-none {
				background-image:url('<?php echo get_option(WPS_OPTIONS_PREFIX.'_images'); ?>/friends.gif');
			}
			.__wps__friends_box-new {
				background-image:url('<?php echo get_option(WPS_OPTIONS_PREFIX.'_images'); ?>/friendsnew.gif');
			}
			.corners {
				<?php if (isset($border_radius)) { ?>
					border-radius: <?php echo $border_radius; ?>px;
					-moz-border-radius: <?php echo $border_radius; ?>px;
				<?php } ?>
			}
		</style>

		
		<?php
		
		
		echo "<!-- NOTIFICATION BAR -->";

			if (is_user_logged_in()) {

				// DIV for who's online
				echo "<div id='__wps__who_online'>";
				
					echo "<div id='__wps__who_online_header'>";
						echo "<div id='__wps__who_online_close'><img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/close.png' alt='".__("Close", WPS_TEXT_DOMAIN)."' /></div>";
						echo "<div id='__wps__who_online_close_label'>".sprintf(__("%s Status", WPS_TEXT_DOMAIN), get_option(WPS_OPTIONS_PREFIX.'_alt_friends'))."</div>";
					echo "</div>";
					echo "<div id='__wps__friends_online_list'></div>";
													
				echo "</div>";
								
				// Logout button DIV
				echo "<div id='__wps__logout_div'>";
					echo "<div id='__wps__online_status_div'>";
						echo "<input type='checkbox' id='__wps__online_status' ";
						if (__wps__get_meta($current_user->ID, 'status') == "offline") { echo " CHECKED"; }
						echo "> ".__("Appear offline?", WPS_TEXT_DOMAIN);
					echo "</div>";
					echo "<div id='__wps__online_status_div'>";
						echo "<img style='float: left; margin-left: 1px; margin-right: 5px;' src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/close.png' alt='".__("Logout", WPS_TEXT_DOMAIN)."' />";
						echo "<a id='__wps__logout-link' href='javascript:void(0);'>".__("Logout", WPS_TEXT_DOMAIN)."</a>";
					echo "</div>";
				echo "</div>";

				echo '<div id="__wps__notification_bar" >';

					// Log out
					echo "<div id='__wps__logout'>".__('Logout', WPS_TEXT_DOMAIN)."</div>";

					// Pending Friends
					if (function_exists('__wps__profile')) {
						echo "<div id='__wps__friends_box' title='".sprintf(__("Go to %s", WPS_TEXT_DOMAIN), get_option(WPS_OPTIONS_PREFIX.'_alt_friends'))."' class='__wps__friends_box __wps__friends_box-none'>";
					} else {
						echo "<div id='__wps__friends_box' style='display:none'>";
					}
					echo "</div>";
					
					// Unread Mail
					if (function_exists('__wps__mail')) {
						echo "<div id='__wps__email_box' title='".__("Go to Mail", WPS_TEXT_DOMAIN)."' class='__wps__email_box __wps__email_box-read'>";
					} else {
						echo "<div id='__wps__email_box' style='display:none'>";
					}
					echo "</div>";
	
					// Friends Status/Online
					echo "<div id='__wps__online_box' class='__wps__online_box-none'></div>";
							
			echo "</div>";

		} 	

		// Re-open any windows (and add DIV for sound alert)
		echo '<div id="player_div"></div>';
		if ((get_option(WPS_OPTIONS_PREFIX.'__wps__add_notification_bar_activated') || get_option(WPS_OPTIONS_PREFIX.'__wps__add_notification_bar_network_activated'))) {
			// re-open any previous chatboxes
			@session_start();
			if (isset($_SESSION['chatbox_status'])) {
				print '<script type="text/javascript">';
				print 'jQuery(function() {';
				foreach ($_SESSION['chatbox_status'] as $openedchatbox) {
					if (isset($openedchatbox['partner_id']) && isset($openedchatbox['partner_username']) && isset($openedchatbox['chatbox_status'])) 
						print 'PopupChat('.$openedchatbox['partner_id'].',"'.$openedchatbox['partner_username'].'",'.$openedchatbox['chatbox_status'].',1);';
				}
				print "});";
				print '</script>';
			}
		}
	}

}  

if (!is_admin()) {
	add_action('wp_footer', '__wps__add_notification_bar', 1);
}


?>
