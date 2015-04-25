<?php
	
	global $wpdb;

	// Store any new values
    if( isset($_POST[ 'symposium_update' ]) && $_POST[ 'symposium_update' ] == 'symposium_facebook_menu' ) {
    	    	        
        $facebook_api = $_POST[ 'facebook_api' ];
        $facebook_secret = $_POST[ 'facebook_secret' ];

		update_option(WPS_OPTIONS_PREFIX.'_facebook_api', $facebook_api);
		update_option(WPS_OPTIONS_PREFIX.'_facebook_secret', $facebook_secret);

        // Put an settings updated message on the screen
		echo "<div class='updated slideaway'><p>".__('Facebook options saved', WPS_TEXT_DOMAIN).".</p></div>";

    } else {
	    // Get values from database  
		$facebook_api = get_option(WPS_OPTIONS_PREFIX.'_facebook_api');
		$facebook_secret = get_option(WPS_OPTIONS_PREFIX.'_facebook_secret');
    }


  	echo '<div class="wrap">';

	  	echo '<div id="icon-themes" class="icon32"><br /></div>';
		echo '<h2>'.sprintf(__('%s Options', WPS_TEXT_DOMAIN), WPS_WL).'</h2><br />';
		
		__wps__show_tabs_header('facebook');

		?>

			<h3>Installation</h3>

			<div style="margin:10px">
			<p><?php _e("A Facebook application is used to post messages to Facebook Walls - you need to create a Facebook application for your website:", WPS_TEXT_DOMAIN) ?></p>

			<ol>
				<li><?php _e('Log in to ', WPS_TEXT_DOMAIN); ?><a target='_blank' href="http://www.facebook.com">Facebook</a>.</li> 
				<li><?php _e('Go', WPS_TEXT_DOMAIN); ?> <a target='_blank' href='https://developers.facebook.com/apps'><?php _e('here', WPS_TEXT_DOMAIN); ?></a>. </li> 
				<li><?php _e('Click on ', WPS_TEXT_DOMAIN); ?><img src="<?php echo plugin_dir_url( __FILE__ ) ?>/library/create_app.gif" /><?php _e(' button', WPS_TEXT_DOMAIN); ?></li> 
				<li><?php _e('Enter an <strong>App Display Name</strong> (that will appear under Facebook Wall posts), eg: Example Web Site', WPS_TEXT_DOMAIN); ?></li> 
				<li><?php _e('You can leave <strong>App Namespace</strong> blank and ignore <strong>Web Hosting</strong>, click on Continue', WPS_TEXT_DOMAIN); ?></li> 
				<li><?php _e('Enter the security check words to continue to the next screen', WPS_TEXT_DOMAIN); ?></li> 
				<li><?php _e('Disable <strong>Sandbox Mode</strong>', WPS_TEXT_DOMAIN); ?></li> 
				<li><?php _e('Click on <strong>Website with Facebook Login</strong> and enter your site URL, eg: ', WPS_TEXT_DOMAIN); ?><?php echo str_replace('http:/', 'http://', str_replace('//', '/', get_bloginfo('wpurl').'/')); ?> <?php _e('(including trailing slash).', WPS_TEXT_DOMAIN); ?></li> 
				<li><?php _e('Click on <strong>Save Changes</strong> on Facebook', WPS_TEXT_DOMAIN); ?></li> 
				<li><?php _e('Copy and Paste the <strong>App ID</strong> and <strong>App Secret</strong> below, and click on the Save Changes button below', WPS_TEXT_DOMAIN); ?></li> 
			</ol>
			</div>
	
			<h3><?php _e('Facebook Application values', WPS_TEXT_DOMAIN) ?></h3>

			<form method="post" action=""> 
			<input type="hidden" name="symposium_update" value="symposium_facebook_menu">

			<table class="form-table __wps__admin_table"> 

				<tr valign="top"> 
				<td scope="row"><label for="facebook_api"><?php _e('Facebook Application ID', WPS_TEXT_DOMAIN); ?></label></td> 
				<td><input name="facebook_api" type="text" id="facebook_api"  value="<?php echo $facebook_api; ?>" style="width:250px" /> 
				<span class="description"><?php echo __('Also called your OAuth client_id', WPS_TEXT_DOMAIN); ?></td> 
				</tr> 

				<tr valign="top"> 
				<td scope="row"><label for="facebook_secret"><?php _e('Facebook Application Secret', WPS_TEXT_DOMAIN); ?></label></td> 
				<td><input name="facebook_secret" type="text" id="facebook_secret"  value="<?php echo $facebook_secret; ?>" style="width:250px" /> 
				<span class="description"><?php echo __('Also called your OAuth client_secret', WPS_TEXT_DOMAIN); ?></td> 
				</tr> 

			<?php
			echo '</table>';

			echo '<p class="submit" style="margin-left:6px;">';
			echo '<input type="submit" name="Submit" class="button-primary" value="'.__('Save Changes', WPS_TEXT_DOMAIN).'" />';
			echo '</p>';
			echo '</form>';
			
			echo '<h3>'.__('Example Facebook Application values', WPS_TEXT_DOMAIN).'</h3>'; 
			
			echo '<p>'.__('The following settings are used with the www.wpsymposium.com website, as an example.', WPS_TEXT_DOMAIN).'</p>';
			
			echo '<img src="'.plugin_dir_url( __FILE__ ).'/images/facebook_admin_screenshot.png" />';
			
		__wps__show_tabs_header_end();
			
	echo '</div>';
	
?>
