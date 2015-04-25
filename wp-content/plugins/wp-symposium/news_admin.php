<div class="wrap">
<div id="icon-themes" class="icon32"><br /></div>

<?php
echo '<h2>'.sprintf(__('%s Options', WPS_TEXT_DOMAIN), WPS_WL).'</h2><br />';

__wps__show_tabs_header('alerts');

if (isset($_POST['__wps__alerts_update'])) {
	// React to POSTed information
	if (isset($_POST['__wps__news_polling'])) {
		update_option(WPS_OPTIONS_PREFIX.'_news_polling', $_POST['__wps__news_polling']);
	}
	if (isset($_POST['__wps__news_x_offset'])) {
		update_option(WPS_OPTIONS_PREFIX.'_news_x_offset', $_POST['__wps__news_x_offset']);
	}
	if (isset($_POST['__wps__news_y_offset'])) {
		update_option(WPS_OPTIONS_PREFIX.'_news_y_offset', $_POST['__wps__news_y_offset']);
	}
	update_option(WPS_OPTIONS_PREFIX.'_hide_news_list', isset($_POST['hide_news_list']) ? $_POST['hide_news_list'] : '');					
}
?>


<form action="" method="POST">

	<input type="hidden" name="__wps__alerts_update" value="yes" />

	<table class="form-table __wps__admin_table">

		<tr><td colspan="2"><h2><?php _e('Options', WPS_TEXT_DOMAIN) ?></h2></td></tr>
	
		<tr><td colspan="2">
				<?php _e('The Alerts plugin updates a DIV (which can be in a WordPress menu item or embedded in a theme) that notifies the member of news/notifications such as new 	mail/friends/activity/etc - notifications can be added by other plugins.', WPS_TEXT_DOMAIN); ?><br />
				<?php _e('Depending on the theme you are using, the position of the list of alerts may not be exactly as you require.', WPS_TEXT_DOMAIN); ?><br />
				<?php _e('To move the list of alerts left/right or up/down, change the offset values below. Use negative values to move left/up and positive values to move right/down.', WPS_TEXT_DOMAIN); ?>
		</td></tr>

		<tr valign="top"> 
		<td scope="row"><label for="__wps__news_x_offset"><?php _e('Horizontal offset', WPS_TEXT_DOMAIN); ?></label></td>
		<td>
		<input type="text" name="__wps__news_x_offset" id="use_chat" value="<?php echo get_option(WPS_OPTIONS_PREFIX."_news_x_offset"); ?>"/>
		<span class="description"><?php echo __('Move the position of the list of alerts left/right', WPS_TEXT_DOMAIN); ?></span></td> 
		</tr> 	
		
		<tr valign="top"> 
		<td scope="row"><label for="__wps__news_y_offset"><?php _e('Vertical offset', WPS_TEXT_DOMAIN); ?></label></td>
		<td>
		<input type="text" name="__wps__news_y_offset" id="use_chat" value="<?php echo get_option(WPS_OPTIONS_PREFIX."_news_y_offset"); ?>"/>
		<span class="description"><?php echo __('Move the position of the list of alerts up/down', WPS_TEXT_DOMAIN); ?></span></td> 
		</tr> 	
		
		<tr valign="top"> 
		<td scope="row"><label for="__wps__news_polling"><?php _e('Polling interval (seconds)', WPS_TEXT_DOMAIN); ?></label></td>
		<td>
		<input type="text" name="__wps__news_polling" id="use_chat" value="<?php echo get_option(WPS_OPTIONS_PREFIX."_news_polling"); ?>"/>
		<span class="description"><?php echo __('Change the polling interval to reduce load on your server', WPS_TEXT_DOMAIN); ?></span></td> 
		</tr> 	

		<tr><td colspan="2">
				<?php _e('If your theme causes issues with the drop-down list, an alternative is to hide the drop-down list, and the user will be taken to the Alerts page if the menu item is clicked:', WPS_TEXT_DOMAIN); ?><br />
		</td></tr>

		<tr valign="top"> 
		<td scope="row"><label for="hide_news_list"><?php echo __('Hide Alerts list', WPS_TEXT_DOMAIN); ?></label></td>
		<td>
		<input type="checkbox" name="hide_news_list" id="hide_news_list" <?php if (get_option(WPS_OPTIONS_PREFIX.'_hide_news_list') == "on") { echo "CHECKED"; } ?>/>
		<span class="description"><?php echo __('Hide if problems are occurring, the menu item will go to the Alerts page instead', WPS_TEXT_DOMAIN); ?></span></td> 
		</tr> 
		
		<tr><td colspan="2"><h2><?php _e('Implementing', WPS_TEXT_DOMAIN) ?></h2></td></tr>
		
		<tr><td colspan="2">
			<strong><?php _e('To add as a menu item', WPS_TEXT_DOMAIN); ?></strong>
			<ol>
				<li><a href="post-new.php?post_type=page">Create a WordPress page</a> (to display the history when the menu item itself is clicked on), and make the page title "Alerts" (or what you want to call it). This is not what appears on the menu, but may appear as your page title when the page is viewed.</li>
				<li>Enter the shortcode [symposium-alerts] on to the page (note: hyphen, not an underscore)</li>
				<li>Visit the <a href="admin.php?page=symposium_debug">Installation page</a> to complete the new page setup.</li>
				<li><a href="nav-menus.php">Edit your site menu</a>, and add the newly created page to the menu. Change the navigation label of your new menu item to that shown in the yellow box below.</li>
			</ol>
			
			<div style="border:1px dotted #333; padding:6px; border-radius:3px; width: 400px; font-family: courier; text-align: center; margin: 20px auto 20px; background-color: #ff9">
			&lt;div id='__wps__alerts'&gt;Alerts&lt;/div&gt;
			</div>
			
			<strong><?php _e('To add to a theme template', WPS_TEXT_DOMAIN); ?></strong>
			<ol>
				<li>Edit your theme, sidebar, etc and add the code shown in the yellow box above to position where the alerts will appear.</li>
			</ol>
		</td></tr>

		<tr><td colspan="2"><h2><?php _e('Shortcodes', WPS_TEXT_DOMAIN) ?></h2></td></tr>

        <tr valign="top"> 
            <td scope="row">
                [<?php echo WPS_SHORTCODE_PREFIX; ?>-alerts]
            </td>
            <td>
            <?php echo __('Displays a member\'s most recent alerts.', WPS_TEXT_DOMAIN).'<br />'; ?>
            <?php echo '<strong>'.__('Parameters', WPS_TEXT_DOMAIN).'</strong><br />'; ?>
            <?php echo __('<div style="width:75px;float:left;">count:</div>over-ride the default number of alerts shown (50)', WPS_TEXT_DOMAIN).'<br />'; ?>
            <?php echo '<strong>'.__('Example', WPS_TEXT_DOMAIN).'</strong><br />'; ?>
            <?php echo sprintf(__('[%s-alerts count=200]', WPS_TEXT_DOMAIN), WPS_SHORTCODE_PREFIX); ?>
            </td>
        </tr>
            
	</table> 
	
	<p style="margin-left:6px"> 
	<input type="submit" name="Submit" class="button-primary" value="<?php echo __('Save Changes', WPS_TEXT_DOMAIN); ?>" /> 
	</p> 
	
</form> 

<?php __wps__show_tabs_header_end(); ?>

</div>
