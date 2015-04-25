<div class="wrap">
<div id="icon-themes" class="icon32"><br /></div>
<?php
echo '<h2>'.sprintf(__('%s Options', WPS_TEXT_DOMAIN), WPS_WL).'</h2><br />';

__wps__show_tabs_header('events');

	global $wpdb;
    // See if the user has posted profile settings
    if( isset($_POST[ 'symposium_events_updated' ]) ) {

	 	// Update *******************************************************************************
		update_option(WPS_OPTIONS_PREFIX."_events_global_list", $_POST[ 'symposium_events_global_list' ]);
		update_option(WPS_OPTIONS_PREFIX.'_events_user_places', isset($_POST[ 'symposium_events_user_places' ]) ? $_POST[ 'symposium_events_user_places' ] : '');
		update_option(WPS_OPTIONS_PREFIX.'_events_use_wysiwyg', isset($_POST[ 'symposium_events_use_wysiwyg' ]) ? $_POST[ 'symposium_events_use_wysiwyg' ] : '');
		update_option(WPS_OPTIONS_PREFIX.'_events_hide_expired', isset($_POST[ 'symposium_events_hide_expired' ]) ? $_POST[ 'symposium_events_hide_expired' ] : '');
		update_option(WPS_OPTIONS_PREFIX.'_events_sort_order', isset($_POST[ 'symposium_events_sort_order' ]) ? $_POST[ 'symposium_events_sort_order' ] : '');
		update_option(WPS_OPTIONS_PREFIX.'_events_calendar', isset($_POST[ 'symposium_events_calendar' ]) ? $_POST[ 'symposium_events_calendar' ] : 'list');

		// Included roles
		if (isset($_POST['dir_level'])) {
	   		$range = array_keys($_POST['dir_level']);
	   		$level = '';
   			foreach ($range as $key) {
				$level .= $_POST['dir_level'][$key].',';
	   		}
		} else {
			$level = '';
		}

		update_option(WPS_OPTIONS_PREFIX.'_events_profile_include', serialize($level));

        // Put an settings updated message on the screen
		echo "<div class='updated slideaway'><p>".__('Saved', WPS_TEXT_DOMAIN).".</p></div>";
		
    }

	// Get option value
	$__wps__events_global_list = get_option(WPS_OPTIONS_PREFIX."_events_global_list") ? get_option(WPS_OPTIONS_PREFIX."_events_global_list") : '';

	?>

	<form method="post" action=""> 
	<input type='hidden' name='symposium_events_updated' value='Y'>
	<table class="form-table __wps__admin_table"> 

	<tr><td colspan="2"><h2><?php _e('Options', WPS_TEXT_DOMAIN) ?></h2></td></tr>

	<tr valign="top"> 
	<td scope="row"><label for="symposium_events_global_list"><?php _e('Global events list', WPS_TEXT_DOMAIN); ?></label></td>
	<td><input name="symposium_events_global_list" type="text" style="width:150px" id="symposium_events_global_list" value="<?php echo $__wps__events_global_list; ?>" class="regular-text" /> 
	<span class="description"><?php echo __('Limits the members included when using [symposium-events]. Enter User IDs (comma separated) or leave blank for all.', WPS_TEXT_DOMAIN); ?></span></td> 
	</tr> 

	<tr valign="top"> 
	<td scope="row"><label for="symposium_events_sort_order"><?php echo __('Reverse list order', WPS_TEXT_DOMAIN); ?></label></td> 
	<td><input type="checkbox" name="symposium_events_sort_order" id="symposium_events_sort_order" <?php if (get_option(WPS_OPTIONS_PREFIX.'_events_sort_order') == "on") { echo "CHECKED"; } ?>/>
	<span class="description"><?php echo __('Select to reverse list order (by start date)', WPS_TEXT_DOMAIN); ?></td> 
	</tr> 
			
	<tr valign="top"> 
	<td scope="row"><label for="symposium_events_calendar"><?php echo __('Style of display', WPS_TEXT_DOMAIN); ?></label></td> 
	<td>
	<select name="symposium_events_calendar">
		<option value="list" <?php if (get_option(WPS_OPTIONS_PREFIX.'_events_calendar') != "calendar") { echo "SELECTED"; } ?>><?php _e('List', WPS_TEXT_DOMAIN); ?></option>
		<option value="calendar" <?php if (get_option(WPS_OPTIONS_PREFIX.'_events_calendar') == "calendar") { echo "SELECTED"; } ?>><?php _e('Calendar', WPS_TEXT_DOMAIN); ?></option>
	</select>
	<span class="description"><?php echo __('Display the global events page as a list or as a calendar', WPS_TEXT_DOMAIN); ?></td> 
	</tr> 
			
	<tr valign="top"> 
	<td scope="row"><label for="symposium_events_hide_expired"><?php echo __('Hide expired events', WPS_TEXT_DOMAIN); ?></label></td> 
	<td><input type="checkbox" name="symposium_events_hide_expired" id="symposium_events_hide_expired" <?php if (get_option(WPS_OPTIONS_PREFIX.'_events_hide_expired') == "on") { echo "CHECKED"; } ?>/>
	<span class="description"><?php echo __('Do not display events that have finished (by end date)', WPS_TEXT_DOMAIN); ?></td> 
	</tr> 
			
	<tr valign="top"> 
	<td scope="row"><label for="symposium_events_user_places"><?php echo __('Non-admin event manager', WPS_TEXT_DOMAIN); ?></label></td> 
	<td><input type="checkbox" name="symposium_events_user_places" id="symposium_events_user_places" <?php if (get_option(WPS_OPTIONS_PREFIX.'_events_user_places') == "on") { echo "CHECKED"; } ?>/>
	<span class="description"><?php echo __('Can non-administrators set up event bookings (or just list basic information)?', WPS_TEXT_DOMAIN); ?></td> 
	</tr> 
			
	<tr valign="top"> 
	<td scope="row"><label for="symposium_events_use_wysiwyg"><?php echo __('Use WYSIWYG editor', WPS_TEXT_DOMAIN); ?></label></td> 
	<td><input type="checkbox" name="symposium_events_use_wysiwyg" id="symposium_events_use_wysiwyg" <?php if (get_option(WPS_OPTIONS_PREFIX.'_events_use_wysiwyg') == "on") { echo "CHECKED"; } ?>/>
	<span class="description"><?php echo __('Use WYSIWYG editor for more information and confirmation email (not summary)?', WPS_TEXT_DOMAIN); ?></td> 
	</tr> 

	<tr><td colspan="2"><h2><?php _e('Profile Menu Items', WPS_TEXT_DOMAIN) ?></h2></td></tr>
			
	<tr valign="top"> 
	<td scope="row"><label for="dir_level"><?php echo __('Roles who get "My Events" on profile page', WPS_TEXT_DOMAIN) ?></label></td> 
	<td>
	<?php

		// Get list of roles
		global $wp_roles;
		$all_roles = $wp_roles->roles;

		$dir_roles = get_option(WPS_OPTIONS_PREFIX.'_events_profile_include');

		foreach ($all_roles as $role) {
			echo '<input type="checkbox" name="dir_level[]" value="'.$role['name'].'"';
			if (strpos(strtolower($dir_roles), strtolower($role['name']).',') !== FALSE) {
				echo ' CHECKED';
			}
			echo '> '.$role['name'].'<br />';
		}	

	?>
	</td></tr>
	
	<?php
	echo '</table>';

	?>
	<table style="margin-left:10px; margin-top:10px;">						
		<tr><td colspan="2"><h2>Shortcodes</h2></td></tr>
		<tr><td width="165px">[<?php echo WPS_SHORTCODE_PREFIX; ?>-events]</td>
			<td><?php echo __('Show all events on a site.', WPS_TEXT_DOMAIN); ?></td></tr>
	</table>
	<?php 	
	 					
	echo '<p class="submit" style="margin-left:12px">';
	echo '<input type="submit" name="Submit" class="button-primary" value="'.__('Save Changes', WPS_TEXT_DOMAIN).'" />';
	echo '</p>';
	echo '</form>';
					  
?>

<?php __wps__show_tabs_header_end(); ?>
</div>
