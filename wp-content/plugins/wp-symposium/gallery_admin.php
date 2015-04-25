<div class="wrap">
<div id="icon-themes" class="icon32"><br /></div>
<?php
echo '<h2>'.sprintf(__('%s Options', WPS_TEXT_DOMAIN), WPS_WL).'</h2><br />';

__wps__show_tabs_header('gallery');
?>

<?php

	global $wpdb;
    // See if the user has posted profile settings
    if( isset($_POST[ 'symposium_gallery_updated' ]) ) {

	 	// Update Version *******************************************************************************
	 	$show_resized = (isset($_POST[ 'show_resized' ])) ? $_POST[ 'show_resized' ] : '';
	 	$thumbnail_size = (isset($_POST[ 'thumbnail_size' ])) ? $_POST[ 'thumbnail_size' ] : '75';
	 	$gallery_page_length = (isset($_POST[ 'gallery_page_length' ])) ? $_POST[ 'gallery_page_length' ] : '10';
	 	$gallery_preview = (isset($_POST[ 'gallery_preview' ])) ? $_POST[ 'gallery_preview' ] : '5';

		update_option(WPS_OPTIONS_PREFIX."_gallery_show_resized", $show_resized);
		update_option(WPS_OPTIONS_PREFIX."_gallery_thumbnail_size", $thumbnail_size);
		update_option(WPS_OPTIONS_PREFIX."_gallery_page_length", $gallery_page_length);
		update_option(WPS_OPTIONS_PREFIX."_gallery_preview", $gallery_preview);

        // Put an settings updated message on the screen
		echo "<div class='updated slideaway'><p>".__('Saved', WPS_TEXT_DOMAIN).".</p></div>";
		
    }

	// Get options
	$show_resized = ($value = get_option(WPS_OPTIONS_PREFIX."_gallery_show_resized")) ? $value : '';
	$thumbnail_size = ($value = get_option(WPS_OPTIONS_PREFIX."_gallery_thumbnail_size")) ? $value : '75';
	$gallery_page_length = ($value = get_option(WPS_OPTIONS_PREFIX."_gallery_page_length")) ? $value : '10';
	$gallery_preview = ($value = get_option(WPS_OPTIONS_PREFIX."_gallery_preview")) ? $value : '5';

	?>

	<form method="post" action=""> 
	<input type='hidden' name='symposium_gallery_updated' value='Y'>
	<table class="form-table __wps__admin_table"> 

	<tr><td colspan="2"><h2><?php _e('Options', WPS_TEXT_DOMAIN) ?></h2></td></tr>

	<tr valign="top"> 
	<td scope="row"><label for="show_resized"><?php _e('Re-size photos in slideshow', WPS_TEXT_DOMAIN); ?></label></td>
	<td>
	<input type="checkbox" name="show_resized" id="show_resized" <?php if ($show_resized == "on") { echo "CHECKED"; } ?>/>
	<span class="description"><?php echo __('Re-sizing photos will ensure that are displayed at nice size and speed up loading, but will stretch small images', WPS_TEXT_DOMAIN); ?></span></td> 
	</tr> 

	<tr valign="top"> 
	<td scope="row"><label for="thumbnail_size"><?php _e('Thumbnail size', WPS_TEXT_DOMAIN); ?></label></td> 
	<td><input name="thumbnail_size" type="text" id="thumbnail_size" style="width:50px" value="<?php echo $thumbnail_size; ?>" /> 
	<span class="description"><?php echo __('Size of gallery thumbnails', WPS_TEXT_DOMAIN); ?></td> 
	</tr> 
	
	<tr valign="top"> 
	<td scope="row"><label for="gallery_page_length"><?php _e('Page size', WPS_TEXT_DOMAIN); ?></label></td> 
	<td><input name="gallery_page_length" type="text" id="gallery_page_length" style="width:50px" value="<?php echo $gallery_page_length; ?>" /> 
	<span class="description"><?php echo __('Number of albums to show on the gallery page (shortcode)', WPS_TEXT_DOMAIN); ?></td> 
	</tr> 
	
	<tr valign="top"> 
	<td scope="row"><label for="gallery_preview"><?php _e('Preview photos', WPS_TEXT_DOMAIN); ?></label></td> 
	<td><input name="gallery_preview" type="text" id="gallery_preview" style="width:50px" value="<?php echo $gallery_preview; ?>" /> 
	<span class="description"><?php echo __('Number of photos to show on one row as an album preview on the gallery page (shortcode)', WPS_TEXT_DOMAIN); ?></td> 
	</tr> 
	
	<?php
	echo '</table>';

	?>
	<table style="margin-left:10px; margin-top:10px;">						
		<tr><td colspan="2"><h2>Shortcodes</h2></td></tr>
		<tr><td width="165px">[<?php echo WPS_SHORTCODE_PREFIX; ?>-galleries]</td>
			<td><?php echo __('Displays a gallery of all the user albums.', WPS_TEXT_DOMAIN); ?></td></tr>
	</table>
	<?php 	
	 					
	echo '<p class="submit" style="margin-left:6px;">';
	echo '<input type="submit" name="Submit" class="button-primary" value="'.__('Save Changes', WPS_TEXT_DOMAIN).'" />';
	echo '</p>';
	echo '</form>';
					  
?>

<?php __wps__show_tabs_header_end(); ?>
</div>
