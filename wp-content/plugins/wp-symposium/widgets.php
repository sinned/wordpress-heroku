<?php
/*
WP Symposium Widgets
Widgets for use with WP Symposium.
*/

/** Add our function to the widgets_init hook. **/

add_action( 'widgets_init', '__wps__load_widgets' );

function __wps__load_widgets() {
	register_widget( '__wps__forumrecentposts_Widget' );
	register_widget( '__wps__forumexperts_Widget' );
	register_widget( '__wps__forumnoanswer_Widget' );
	register_widget( '__wps__members_Widget' );
	register_widget( '__wps__summary_Widget' );
	register_widget( '__wps__friends_Widget' );
	register_widget( '__wps__recent_Widget' );
	register_widget( '__wps__friends_status_Widget' );
	register_widget( '__wps__alerts_Widget' );
}

/** Profile: Friends Recent Posts ************************************************************************* **/
class __wps__friends_status_Widget extends WP_Widget {

	function __wps__friends_status_Widget() {
		/* Widget settings. */
		$widget_ops = array( 'classname' => 'widget_friends_status', 'description' => 'Shows friends posts (not replies, ie. their status). Does not include Group posts. Observes privacy settings.' );
		
		/* Widget control settings. */
		$control_ops = array( 'id_base' => 'friends_status-widget' );
		
		/* Create the widget. */
		$this->WP_Widget( 'friends_status-widget', WPS_WL_SHORT.': '.sprintf(__('%s Status', WPS_TEXT_DOMAIN), get_option(WPS_OPTIONS_PREFIX.'_alt_friends')), $widget_ops, $control_ops );
	}
	
	// This is shown on the page
	function widget( $args, $instance ) {
		global $wpdb, $current_user;
		wp_get_current_user();
	
		extract( $args );
				
		// Get options
		$wtitle = apply_filters('widget_title', $instance['wtitle'] );
		$postcount = apply_filters('widget_postcount', $instance['postcount'] );
		$preview = apply_filters('widget_preview', $instance['preview'] );
		$forum = apply_filters('widget_forum', $instance['forum'] );
		
		// Start widget
		echo $before_widget;
		echo $before_title . $wtitle . $after_title;

		if (get_option(WPS_OPTIONS_PREFIX.'_ajax_widgets') == 'on') {
			// Parameters for AJAX
			echo '<div id="__wps__friends_status_Widget">';
			echo __('Loading...', WPS_TEXT_DOMAIN);
			echo '<div id="__wps__friends_status_postcount" style="display:none">'.$postcount.'</div>';
			echo '<div id="__wps__friends_status_preview" style="display:none">'.$preview.'</div>';
			echo '<div id="__wps__friends_status_forum" style="display:none">'.$forum.'</div>';
			echo '</div>';
		} else {
			__wps__do_friends_status_Widget($postcount,$preview,$forum);
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
		$instance['postcount'] = strip_tags( $new_instance['postcount'] );
		$instance['preview'] = strip_tags( $new_instance['preview'] );
		$instance['forum'] = strip_tags( $new_instance['forum'] );

		return $instance;
	}
	
	// This is the admin form for the widget
	function form( $instance ) {

		/* Set up some default widget settings. */
		$defaults = array( 'wtitle' => 'What are friends saying?', 'postcount' => '5', 'preview' => '60', 'forum' => 'on' );
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>

		<p>
			<label for="<?php echo $this->get_field_id( 'wtitle' ); ?>"><?php echo __('Widget Title', WPS_TEXT_DOMAIN); ?>:</label>
			<input id="<?php echo $this->get_field_id( 'wtitle' ); ?>" name="<?php echo $this->get_field_name( 'wtitle' ); ?>" value="<?php echo $instance['wtitle']; ?>" />
		<br /><br />
			<label for="<?php echo $this->get_field_id( 'postcount' ); ?>"><?php echo __('Max number of posts', WPS_TEXT_DOMAIN); ?>:</label>
			<input id="<?php echo $this->get_field_id( 'postcount' ); ?>" name="<?php echo $this->get_field_name( 'postcount' ); ?>" value="<?php echo $instance['postcount']; ?>" style="width: 30px" />
		<br /><br />
			<label for="<?php echo $this->get_field_id( 'preview' ); ?>"><?php echo __('Max length of preview', WPS_TEXT_DOMAIN); ?>:</label>
			<input id="<?php echo $this->get_field_id( 'preview' ); ?>" name="<?php echo $this->get_field_name( 'preview' ); ?>" value="<?php echo $instance['preview']; ?>" style="width: 30px" />
		<br /><br />
			<label for="<?php echo $this->get_field_id( 'forum' ); ?>"><?php echo __('Include site activity', WPS_TEXT_DOMAIN); ?>:</label>
			<input type="checkbox" id="<?php echo $this->get_field_id( 'forum' ); ?>" name="<?php echo $this->get_field_name( 'forum' ); ?>"
			<?php if ($instance['forum'] == 'on') { echo " CHECKED"; } ?>
			/>
		</p>
		<?php
	}
}

/** Recently Online ************************************************************************* **/
class __wps__recent_Widget extends WP_Widget {

	function __wps__recent_Widget() {
		/* Widget settings. */
		$widget_ops = array( 'classname' => 'widget_symposium_recent', 'description' => 'Shows members recently online.' );
		
		/* Widget control settings. */
		$control_ops = array( 'id_base' => 'symposium_recent-widget' );
		
		/* Create the widget. */
		$this->WP_Widget( 'symposium_recent-widget', WPS_WL_SHORT.': '.__('Recently Online', WPS_TEXT_DOMAIN), $widget_ops, $control_ops );
	}
	
	// This is shown on the page
	function widget( $args, $instance ) {
		global $wpdb, $current_user;
		wp_get_current_user();
			
		extract( $args );
		
		// Get options
		$__wps__recent_title = apply_filters('widget_title', $instance['symposium_recent_title'] );
		$__wps__recent_count = apply_filters('widget_symposium_members_count', $instance['symposium_recent_count'] );
		$__wps__recent_desc = apply_filters('widget_symposium_recent_desc', $instance['symposium_recent_desc'] );
		$__wps__recent_show_light = apply_filters('widget_symposium_recent_show_light', $instance['symposium_recent_show_light'] );
		$__wps__recent_show_mail = apply_filters('widget_symposium_recent_show_mail', $instance['symposium_recent_show_mail'] );
		
		// Start widget
		echo $before_widget;
		echo $before_title . $__wps__recent_title . $after_title;

		if (get_option(WPS_OPTIONS_PREFIX.'_ajax_widgets') == 'on') {
			// Parameters for AJAX
			echo '<div id="__wps__recent_Widget">';
			echo __('Loading...', WPS_TEXT_DOMAIN);
			echo '<div id="__wps__recent_Widget_count" style="display:none">'.$__wps__recent_count.'</div>';
			echo '<div id="__wps__recent_Widget_desc" style="display:none">'.$__wps__recent_desc.'</div>';
			echo '<div id="__wps__recent_Widget_show_light" style="display:none">'.$__wps__recent_show_light.'</div>';
			echo '<div id="__wps__recent_Widget_show_mail" style="display:none">'.$__wps__recent_show_mail.'</div>';
			echo '</div>';	
			
		} else {
			do_recent_Widget($__wps__recent_count,$__wps__recent_desc,$__wps__recent_show_light,$__wps__recent_show_mail);
		}
		// End content
	
		echo $after_widget;
		// End widget

	}
	
	// This updates the stored values
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		
		/* Strip tags (if needed) and update the widget settings. */
		$instance['symposium_recent_title'] = strip_tags( $new_instance['symposium_recent_title'] );
		$instance['symposium_recent_count'] = strip_tags( $new_instance['symposium_recent_count'] );
		$instance['symposium_recent_desc'] = strip_tags( $new_instance['symposium_recent_desc'] );
		$instance['symposium_recent_show_light'] = strip_tags( $new_instance['symposium_recent_show_light'] );
		$instance['symposium_recent_show_mail'] = strip_tags( $new_instance['symposium_recent_show_mail'] );
		return $instance;
	}
	
	// This is the admin form for the widget
	function form( $instance ) {

		/* Set up some default widget settings. */
		$defaults = array( 'symposium_recent_title' => 'Recently Online', 'symposium_recent_count' => '5', 'symposium_recent_desc' => 'on', 'symposium_recent_show_light' => '', 'symposium_recent_show_mail' => 'on' );
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>

		<p>
			<label for="<?php echo $this->get_field_id( 'symposium_recent_title' ); ?>"><?php echo __('Widget Title', WPS_TEXT_DOMAIN); ?>:</label>
			<input id="<?php echo $this->get_field_id( 'symposium_recent_title' ); ?>" name="<?php echo $this->get_field_name( 'symposium_recent_title' ); ?>" value="<?php echo $instance['symposium_recent_title']; ?>" />
		<br /><br />
			<label for="<?php echo $this->get_field_id( 'symposium_recent_count' ); ?>"><?php echo __('Max number shown', WPS_TEXT_DOMAIN); ?>:</label>
			<input id="<?php echo $this->get_field_id( 'symposium_recent_count' ); ?>" name="<?php echo $this->get_field_name( 'symposium_recent_count' ); ?>" value="<?php echo $instance['symposium_recent_count']; ?>" style="width: 30px" />
		<br /><br />
			<label for="<?php echo $this->get_field_id( 'symposium_recent_desc' ); ?>"><?php echo __('Show details as list', WPS_TEXT_DOMAIN); ?>:</label>
			<input type="checkbox" id="<?php echo $this->get_field_id( 'symposium_recent_desc' ); ?>" name="<?php echo $this->get_field_name( 'symposium_recent_desc' ); ?>"
			<?php if ($instance['symposium_recent_desc'] == 'on') { echo " CHECKED"; } ?>
			/>
		<?php if ($instance['symposium_recent_desc'] == 'on') { ?>
		<br /><br />
			<label for="<?php echo $this->get_field_id( 'symposium_recent_show_light' ); ?>"><?php echo __('Show online status indicator', WPS_TEXT_DOMAIN); ?>:</label>
			<input type="checkbox" id="<?php echo $this->get_field_id( 'symposium_recent_show_light' ); ?>" name="<?php echo $this->get_field_name( 'symposium_recent_show_light' ); ?>"
			<?php if ($instance['symposium_recent_show_light'] == 'on') { echo " CHECKED"; } ?>
			/>
		<br /><br />
			<label for="<?php echo $this->get_field_id( 'symposium_recent_show_mail' ); ?>"><?php echo __('Show mail link', WPS_TEXT_DOMAIN); ?>:</label>
			<input type="checkbox" id="<?php echo $this->get_field_id( 'symposium_recent_show_mail' ); ?>" name="<?php echo $this->get_field_name( 'symposium_recent_show_mail' ); ?>"
			<?php if ($instance['symposium_recent_show_mail'] == 'on') { echo " CHECKED"; } ?>
			/>
		<?php } else { ?>
			<input type="hidden" id="<?php echo $this->get_field_id( 'symposium_recent_show_light' ); ?>" name="<?php echo $this->get_field_name( 'symposium_recent_show_light' ); ?>" value="<?php echo $instance['symposium_recent_show_light']; ?>" style="width: 30px" />
			<input type="hidden" id="<?php echo $this->get_field_id( 'symposium_recent_show_mail' ); ?>" name="<?php echo $this->get_field_name( 'symposium_recent_show_mail' ); ?>" value="<?php echo $instance['symposium_recent_show_mail']; ?>" style="width: 30px" />
		<?php }  ?>
		</p>
		<?php
	}

}

/** Profile: Recent Posts ************************************************************************* **/
class __wps__recentactivity_Widget extends WP_Widget {

	function __wps__recentactivity_Widget() {
		/* Widget settings. */
		$widget_ops = array( 'classname' => 'widget_recentactivity', 'description' => 'Shows recent member posts (not replies, ie. their status). Does not include Group posts. Observes privacy settings.' );
		
		/* Widget control settings. */
		$control_ops = array( 'id_base' => 'recentactivity-widget' );
		
		/* Create the widget. */
		$this->WP_Widget( 'recentactivity-widget', WPS_WL_SHORT.': '.__('Recent Activity', WPS_TEXT_DOMAIN), $widget_ops, $control_ops );
	}
	
	// This is shown on the page
	function widget( $args, $instance ) {
		global $wpdb, $current_user;
		wp_get_current_user();
	
		extract( $args );
				
		// Get options
		$wtitle = apply_filters('widget_title', $instance['wtitle'] );
		$postcount = apply_filters('widget_postcount', $instance['postcount'] );
		$preview = apply_filters('widget_preview', $instance['preview'] );
		$forum = apply_filters('widget_forum', $instance['forum'] );
		
		// Start widget
		echo $before_widget;
		echo $before_title . $wtitle . $after_title;

		if (get_option(WPS_OPTIONS_PREFIX.'_ajax_widgets') == 'on') {
			// Parameters for AJAX
			echo '<div id="symposium_Recentactivity_Widget">';
			echo __('Loading...', WPS_TEXT_DOMAIN);
			echo '<div id="symposium_Recentactivity_Widget_postcount" style="display:none">'.$postcount.'</div>';
			echo '<div id="symposium_Recentactivity_Widget_preview" style="display:none">'.$preview.'</div>';
			echo '<div id="symposium_Recentactivity_Widget_forum" style="display:none">'.$forum.'</div>';
			echo '</div>';
		} else {
			__wps__do_Recentactivity_Widget($postcount,$preview,$forum);
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
		$instance['postcount'] = strip_tags( $new_instance['postcount'] );
		$instance['preview'] = strip_tags( $new_instance['preview'] );
		$instance['forum'] = strip_tags( $new_instance['forum'] );

		return $instance;
	}
	
	// This is the admin form for the widget
	function form( $instance ) {

		/* Set up some default widget settings. */
		$defaults = array( 'wtitle' => 'What are members saying?', 'postcount' => '5', 'preview' => '60', 'forum' => 'on' );
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>

		<p>
			<label for="<?php echo $this->get_field_id( 'wtitle' ); ?>"><?php echo __('Widget Title', WPS_TEXT_DOMAIN); ?>:</label>
			<input id="<?php echo $this->get_field_id( 'wtitle' ); ?>" name="<?php echo $this->get_field_name( 'wtitle' ); ?>" value="<?php echo $instance['wtitle']; ?>" />
		<br /><br />
			<label for="<?php echo $this->get_field_id( 'postcount' ); ?>"><?php echo __('Max number of posts', WPS_TEXT_DOMAIN); ?>:</label>
			<input id="<?php echo $this->get_field_id( 'postcount' ); ?>" name="<?php echo $this->get_field_name( 'postcount' ); ?>" value="<?php echo $instance['postcount']; ?>" style="width: 30px" />
		<br /><br />
			<label for="<?php echo $this->get_field_id( 'preview' ); ?>"><?php echo __('Max length of preview', WPS_TEXT_DOMAIN); ?>:</label>
			<input id="<?php echo $this->get_field_id( 'preview' ); ?>" name="<?php echo $this->get_field_name( 'preview' ); ?>" value="<?php echo $instance['preview']; ?>" style="width: 30px" />
		<br /><br />
			<label for="<?php echo $this->get_field_id( 'forum' ); ?>"><?php echo __('Include site activity', WPS_TEXT_DOMAIN); ?>:</label>
			<input type="checkbox" id="<?php echo $this->get_field_id( 'forum' ); ?>" name="<?php echo $this->get_field_name( 'forum' ); ?>"
			<?php if ($instance['forum'] == 'on') { echo " CHECKED"; } ?>
			/>
		</p>
		<?php
	}
}

/** New Members ************************************************************************* **/
class __wps__members_Widget extends WP_Widget {

	function __wps__members_Widget() {
		/* Widget settings. */
		$widget_ops = array( 'classname' => 'widget_symposium_members', 'description' => 'Shows recent new members.' );
		
		/* Widget control settings. */
		$control_ops = array( 'id_base' => 'symposium_members-widget' );
		
		/* Create the widget. */
		$this->WP_Widget( 'symposium_members-widget', WPS_WL_SHORT.': '.__('Latest New Members', WPS_TEXT_DOMAIN), $widget_ops, $control_ops );
	}
	
	// This is shown on the page
	function widget( $args, $instance ) {
		global $wpdb, $current_user;
		wp_get_current_user();
	
		extract( $args );
		
		// Get options
		$__wps__members_count_title = apply_filters('widget_title', $instance['symposium_members_count_title'] );
		$__wps__members_count = apply_filters('widget_symposium_members_count', $instance['symposium_members_count'] );
		
		// Start widget
		echo $before_widget;
		echo $before_title . $__wps__members_count_title . $after_title;

		if (get_option(WPS_OPTIONS_PREFIX.'_ajax_widgets') == 'on') {
			// Parameters for AJAX
			echo '<div id="symposium_members_Widget">';
			echo __('Loading...', WPS_TEXT_DOMAIN);
			echo '<div id="symposium_members_Widget_count" style="display:none">'.$__wps__members_count.'</div>';
			echo '</div>';
		} else {
			__wps__do_members_Widget($__wps__members_count);
		}
		
		// End content
		
		echo $after_widget;
		// End widget
	}
	
	// This updates the stored values
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		
		/* Strip tags (if needed) and update the widget settings. */
		$instance['symposium_members_count_title'] = strip_tags( $new_instance['symposium_members_count_title'] );
		$instance['symposium_members_count'] = strip_tags( $new_instance['symposium_members_count'] );
		return $instance;
	}
	
	// This is the admin form for the widget
	function form( $instance ) {

		/* Set up some default widget settings. */
		$defaults = array( 'symposium_members_count_title' => 'New Members', 'symposium_members_count' => '5' );
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>

		<p>
			<label for="<?php echo $this->get_field_id( 'symposium_members_count_title' ); ?>"><?php echo __('Widget Title', WPS_TEXT_DOMAIN); ?>:</label>
			<input id="<?php echo $this->get_field_id( 'symposium_members_count_title' ); ?>" name="<?php echo $this->get_field_name( 'symposium_members_count_title' ); ?>" value="<?php echo $instance['symposium_members_count_title']; ?>" />
		<br /><br />
			<label for="<?php echo $this->get_field_id( 'symposium_members_count' ); ?>"><?php echo __('Max number shown', WPS_TEXT_DOMAIN); ?>:</label>
			<input id="<?php echo $this->get_field_id( 'symposium_members_count' ); ?>" name="<?php echo $this->get_field_name( 'symposium_members_count' ); ?>" value="<?php echo $instance['symposium_members_count']; ?>" style="width: 30px" />
		</p>
		<?php
	}

}

/** Friends ************************************************************************* **/
class __wps__friends_Widget extends WP_Widget {

	function __wps__friends_Widget() {
		/* Widget settings. */
		$widget_ops = array( 'classname' => 'widget_symposium_friends', 'description' => 'Shows a member friends, when logged in.' );
		
		/* Widget control settings. */
		$control_ops = array( 'id_base' => 'symposium_friends-widget' );
		
		/* Create the widget. */
		$this->WP_Widget( 'symposium_friends-widget', WPS_WL_SHORT.': '.sprintf(__('Your %s', WPS_TEXT_DOMAIN), get_option(WPS_OPTIONS_PREFIX.'_alt_friends')), $widget_ops, $control_ops );
	}
	
	// This is shown on the page
	function widget( $args, $instance ) {
		global $wpdb, $current_user;
		wp_get_current_user();
		
		if (is_user_logged_in()) {
	
			extract( $args );
			
			// Get options
			$__wps__friends_count_title = apply_filters('widget_title', $instance['symposium_friends_count_title'] );
			$__wps__friends_count = apply_filters('widget_symposium_friends_count', $instance['symposium_friends_count'] );
			$__wps__friends_desc = apply_filters('widget_symposium_friends_desc', $instance['symposium_friends_desc'] );
			$__wps__friends_mode = apply_filters('widget_symposium_friends_mode', $instance['symposium_friends_mode'] );
			$__wps__friends_show_light = apply_filters('widget_symposium_friends_show_light', $instance['symposium_friends_show_light'] );
			$__wps__friends_show_mail = apply_filters('widget_symposium_friends_show_mail', $instance['symposium_friends_show_mail'] );
			
			// Start widget
			echo $before_widget;
			echo $before_title . $__wps__friends_count_title . $after_title;

			if (get_option(WPS_OPTIONS_PREFIX.'_ajax_widgets') == 'on') {
				// Parameters for AJAX
				echo '<div id="symposium_friends_Widget">';
				echo __('Loading...', WPS_TEXT_DOMAIN);
				echo '<div id="symposium_friends_count" style="display:none">'.$__wps__friends_count.'</div>';
				echo '<div id="symposium_friends_desc" style="display:none">'.$__wps__friends_desc.'</div>';
				echo '<div id="symposium_friends_mode" style="display:none">'.$__wps__friends_mode.'</div>';
				echo '<div id="symposium_friends_show_light" style="display:none">'.$__wps__friends_show_light.'</div>';
				echo '<div id="symposium_friends_show_mail" style="display:none">'.$__wps__friends_show_mail.'</div>';
				echo '</div>';	
			} else {
				__wps__do_friends_Widget($__wps__friends_count,$__wps__friends_desc,$__wps__friends_mode,$__wps__friends_show_light,$__wps__friends_show_mail);	
			}

			// End content
		
			echo $after_widget;
			// End widget
		}
	}
	
	// This updates the stored values
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		
		/* Strip tags (if needed) and update the widget settings. */
		$instance['symposium_friends_count_title'] = strip_tags( $new_instance['symposium_friends_count_title'] );
		$instance['symposium_friends_count'] = strip_tags( $new_instance['symposium_friends_count'] );
		$instance['symposium_friends_desc'] = strip_tags( $new_instance['symposium_friends_desc'] );
		$instance['symposium_friends_mode'] = strip_tags( $new_instance['symposium_friends_mode'] );
		$instance['symposium_friends_show_light'] = strip_tags( $new_instance['symposium_friends_show_light'] );
		$instance['symposium_friends_show_mail'] = strip_tags( $new_instance['symposium_friends_show_mail'] );
		return $instance;
	}
	
	// This is the admin form for the widget
	function form( $instance ) {

		/* Set up some default widget settings. */
		$defaults = array( 'symposium_friends_count_title' => 'Your Friends', 'symposium_friends_count' => '5', 'symposium_friends_desc' => 'on', 'symposium_friends_mode' => 'all', 'symposium_friends_show_light' => '', 'symposium_friends_show_mail' => 'on' );
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>

		<p>
			<label for="<?php echo $this->get_field_id( 'symposium_friends_count_title' ); ?>"><?php echo __('Widget Title', WPS_TEXT_DOMAIN); ?>:</label>
			<input id="<?php echo $this->get_field_id( 'symposium_friends_count_title' ); ?>" name="<?php echo $this->get_field_name( 'symposium_friends_count_title' ); ?>" value="<?php echo $instance['symposium_friends_count_title']; ?>" />
		<br /><br />
			<label for="<?php echo $this->get_field_id( 'symposium_friends_count' ); ?>"><?php echo __('Max number shown', WPS_TEXT_DOMAIN); ?>:</label>
			<input id="<?php echo $this->get_field_id( 'symposium_friends_count' ); ?>" name="<?php echo $this->get_field_name( 'symposium_friends_count' ); ?>" value="<?php echo $instance['symposium_friends_count']; ?>" style="width: 30px" />
		<br /><br />
			<label for="<?php echo $this->get_field_id( 'symposium_friends_desc' ); ?>"><?php echo __('Show details as list', WPS_TEXT_DOMAIN); ?>:</label>
			<input type="checkbox" id="<?php echo $this->get_field_id( 'symposium_friends_desc' ); ?>" name="<?php echo $this->get_field_name( 'symposium_friends_desc' ); ?>"
			<?php if ($instance['symposium_friends_desc'] == 'on') { echo " CHECKED"; } ?>
			/>
		<?php if ($instance['symposium_friends_desc'] == 'on') { ?>
		<br /><br />
			<label for="<?php echo $this->get_field_id( 'symposium_friends_show_light' ); ?>"><?php echo __('Show online status indicator', WPS_TEXT_DOMAIN); ?>:</label>
			<input type="checkbox" id="<?php echo $this->get_field_id( 'symposium_friends_show_light' ); ?>" name="<?php echo $this->get_field_name( 'symposium_friends_show_light' ); ?>"
			<?php if ($instance['symposium_friends_show_light'] == 'on') { echo " CHECKED"; } ?>
			/>
		<br /><br />
			<label for="<?php echo $this->get_field_id( 'symposium_friends_show_mail' ); ?>"><?php echo __('Show mail link', WPS_TEXT_DOMAIN); ?>:</label>
			<input type="checkbox" id="<?php echo $this->get_field_id( 'symposium_friends_show_mail' ); ?>" name="<?php echo $this->get_field_name( 'symposium_friends_show_mail' ); ?>"
			<?php if ($instance['symposium_friends_show_mail'] == 'on') { echo " CHECKED"; } ?>
			/>
		<?php } else { ?>
			<input type="hidden" id="<?php echo $this->get_field_id( 'symposium_friends_show_light' ); ?>" name="<?php echo $this->get_field_name( 'symposium_friends_show_light' ); ?>" value="<?php echo $instance['symposium_friends_show_light']; ?>" style="width: 30px" />
			<input type="hidden" id="<?php echo $this->get_field_id( 'symposium_friends_show_mail' ); ?>" name="<?php echo $this->get_field_name( 'symposium_friends_show_mail' ); ?>" value="<?php echo $instance['symposium_friends_show_mail']; ?>" style="width: 30px" />
		<?php }  ?>
		<br /><br />
			<label for="<?php echo $this->get_field_id( 'symposium_friends_mode' ); ?>"><?php echo __('Show', WPS_TEXT_DOMAIN); ?>:</label>
			<select id="<?php echo $this->get_field_id( 'symposium_friends_mode' ); ?>" name="<?php echo $this->get_field_name( 'symposium_friends_mode' ); ?>">
				<option value='all'
					<?php if ($instance['symposium_friends_mode'] == 'all') { echo " SELECTED"; } ?>
					><?php _e("All", WPS_TEXT_DOMAIN); ?>
				<option value='split'
					<?php if ($instance['symposium_friends_mode'] == 'split') { echo " SELECTED"; } ?>
					><?php _e("Online/offline split", WPS_TEXT_DOMAIN); ?>
				<option value='online'
					<?php if ($instance['symposium_friends_mode'] == 'online') { echo " SELECTED"; } ?>
					><?php _e("Online only", WPS_TEXT_DOMAIN); ?>					
			</select>
		</p>
		<?php
	}

}

/** Forum: Recent Posts ************************************************************************* **/
class __wps__forumrecentposts_Widget extends WP_Widget {

	function __wps__forumrecentposts_Widget() {
		/* Widget settings. */
		$widget_ops = array( 'classname' => 'widget_forumrecentposts', 'description' => 'Shows a number of recent posts.' );
		
		/* Widget control settings. */
		$control_ops = array( 'id_base' => 'forumrecentposts-widget' );
		
		/* Create the widget. */
		$this->WP_Widget( 'forumrecentposts-widget', WPS_WL_SHORT.': '.__('Latest Forum Posts', WPS_TEXT_DOMAIN), $widget_ops, $control_ops );
	}
	
	// This is shown on the page
	function widget( $args, $instance ) {
		global $wpdb, $current_user;
		wp_get_current_user();
	
		extract( $args );
		
		// Get options
		$wtitle = apply_filters('widget_title', $instance['wtitle'] );
		$postcount = apply_filters('widget_postcount', $instance['postcount'] );
		$preview = apply_filters('widget_preview', $instance['preview'] );
		$cat_id = apply_filters('widget_cat_id', $instance['cat_id'] );
		$show_replies = apply_filters('widget_show_replies', $instance['show_replies'] );
		$incl_cat = apply_filters('widget_incl_cat', $instance['incl_cat'] );
		$incl_parent = apply_filters('widget_incl_parent', $instance['incl_parent'] );
		$just_own = apply_filters('widget_just_own', $instance['just_own'] );
		
		// Start widget
		echo $before_widget;
		echo $before_title . $wtitle . $after_title;

		if (get_option(WPS_OPTIONS_PREFIX.'_ajax_widgets') == 'on') {
			// Parameters for AJAX
			echo '<div id="__wps__Forumrecentposts_Widget">';
			echo __('Loading...', WPS_TEXT_DOMAIN);
			echo '<div id="__wps__Forumrecentposts_Widget_postcount" style="display:none">'.$postcount.'</div>';
			echo '<div id="__wps__Forumrecentposts_Widget_preview" style="display:none">'.$preview.'</div>';
			echo '<div id="__wps__Forumrecentposts_Widget_cat_id" style="display:none">'.$cat_id.'</div>';
			echo '<div id="__wps__Forumrecentposts_Widget_show_replies" style="display:none">'.$show_replies.'</div>';
			echo '<div id="__wps__Forumrecentposts_Widget_incl_cat" style="display:none">'.$incl_cat.'</div>';
			echo '<div id="__wps__Forumrecentposts_Widget_incl_parent" style="display:none">'.$incl_parent.'</div>';
			echo '<div id="__wps__Forumrecentposts_Widget_just_own" style="display:none">'.$just_own.'</div>';
			echo '</div>';
		} else {
			__wps__do_Forumrecentposts_Widget($postcount,$preview,$cat_id,$show_replies,$incl_cat,$incl_parent,$just_own);			
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
		$instance['postcount'] = strip_tags( $new_instance['postcount'] );
		$instance['preview'] = strip_tags( $new_instance['preview'] );
		$instance['cat_id'] = strip_tags( $new_instance['cat_id'] );
		$instance['show_replies'] = strip_tags( $new_instance['show_replies'] );
		$instance['incl_cat'] = strip_tags( $new_instance['incl_cat'] );
		$instance['incl_parent'] = strip_tags( $new_instance['incl_parent'] );
		$instance['just_own'] = strip_tags( $new_instance['just_own'] );
		return $instance;
	}
	
	// This is the admin form for the widget
	function form( $instance ) {

		/* Set up some default widget settings. */
		$defaults = array( 'wtitle' => 'Recent Forum Posts', 'show_replies' => 'on', 'postcount' => '3', 'cat_id' => '0', 'preview' => '30', 'incl_cat' => '', 'just_own' => '' );
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>

		<p>
			<label for="<?php echo $this->get_field_id( 'wtitle' ); ?>"><?php echo __('Widget Title', WPS_TEXT_DOMAIN); ?>:</label>
			<input id="<?php echo $this->get_field_id( 'wtitle' ); ?>" name="<?php echo $this->get_field_name( 'wtitle' ); ?>" value="<?php echo $instance['wtitle']; ?>" />
		<br /><br />
			<label for="<?php echo $this->get_field_id( 'just_own' ); ?>"><?php echo __('Just member\'s own posts', WPS_TEXT_DOMAIN); ?>:</label>
			<input type="checkbox" id="<?php echo $this->get_field_id( 'just_own' ); ?>" name="<?php echo $this->get_field_name( 'just_own' ); ?>"
			<?php if ($instance['just_own'] == 'on') { echo " CHECKED"; } ?>
			/>
		<br /><br />
			<label for="<?php echo $this->get_field_id( 'show_replies' ); ?>"><?php echo __('Show replies', WPS_TEXT_DOMAIN); ?>:</label>
			<input type="checkbox" id="<?php echo $this->get_field_id( 'show_replies' ); ?>" name="<?php echo $this->get_field_name( 'show_replies' ); ?>"
			<?php if ($instance['show_replies'] == 'on') { echo " CHECKED"; } ?>
			/>
		<br /><br />
			<label for="<?php echo $this->get_field_id( 'incl_parent' ); ?>"><?php echo __('Show parent (replies)', WPS_TEXT_DOMAIN); ?>:</label>
			<input type="checkbox" id="<?php echo $this->get_field_id( 'incl_parent' ); ?>" name="<?php echo $this->get_field_name( 'incl_parent' ); ?>"
			<?php if (isset($instance['incl_parent']) && $instance['incl_parent'] == 'on') { echo " CHECKED"; } ?>
			/>
		<br /><br />
			<label for="<?php echo $this->get_field_id( 'incl_cat' ); ?>"><?php echo __('Include category', WPS_TEXT_DOMAIN); ?>:</label>
			<input type="checkbox" id="<?php echo $this->get_field_id( 'incl_cat' ); ?>" name="<?php echo $this->get_field_name( 'incl_cat' ); ?>"
			<?php if ($instance['incl_cat'] == 'on') { echo " CHECKED"; } ?>
			/>
		<br /><br />
			<label for="<?php echo $this->get_field_id( 'postcount' ); ?>"><?php echo __('Max number of posts', WPS_TEXT_DOMAIN); ?>:</label>
			<input id="<?php echo $this->get_field_id( 'postcount' ); ?>" name="<?php echo $this->get_field_name( 'postcount' ); ?>" value="<?php echo $instance['postcount']; ?>" style="width: 30px" />
		<br /><br />
			<label for="<?php echo $this->get_field_id( 'cat_id' ); ?>"><?php echo __('Category ID (0 for all)', WPS_TEXT_DOMAIN); ?>:</label>
			<input id="<?php echo $this->get_field_id( 'cat_id' ); ?>" name="<?php echo $this->get_field_name( 'cat_id' ); ?>" value="<?php echo $instance['cat_id']; ?>" style="width: 30px" />
		<br /><br />
			<label for="<?php echo $this->get_field_id( 'preview' ); ?>"><?php echo __('Max length of preview', WPS_TEXT_DOMAIN); ?>:</label>
			<input id="<?php echo $this->get_field_id( 'preview' ); ?>" name="<?php echo $this->get_field_name( 'preview' ); ?>" value="<?php echo $instance['preview']; ?>" style="width: 30px" />
		</p>
		<?php
	}

}

/** Login/Summary Widget ************************************************************************* **/
class __wps__summary_Widget extends WP_Widget {

	function __wps__summary_Widget() {
		/* Widget settings. */
		$widget_ops = array( 'classname' => 'widget_symposium_summary', 'description' => sprintf('When logged in, shows a summary of the %s user.', WPS_WL) );
		
		/* Widget control settings. */
		$control_ops = array( 'id_base' => 'symposium_summary-widget' );
		
		/* Create the widget. */
		$this->WP_Widget( 'symposium_summary-widget', WPS_WL_SHORT.': '.__('Summary', WPS_TEXT_DOMAIN), $widget_ops, $control_ops );
	}
	
	// This is shown on the page
	function widget( $args, $instance ) {
		global $wpdb, $current_user;
		wp_get_current_user();

	
		extract( $args );
		
		// Get options
		$wtitle = apply_filters('widget_title', $instance['wtitle'] );
		$show_loggedout = apply_filters('widget_show_loggedout', $instance['show_loggedout'] );
		$show_form = apply_filters('widget_show_form', $instance['show_form'] );
		$login_url = apply_filters('widget_logi_url', $instance['login_url'] );
		$show_avatar = apply_filters('widget_show_avatar', $instance['show_avatar'] );
		$show_avatar_size = apply_filters('widget_show_avatar_size', $instance['show_avatar_size'] );
		$login_username = apply_filters('widget_login_username', $instance['login_username'] );
		$login_password = apply_filters('widget_login_password', $instance['login_password'] );
		$login_remember_me = apply_filters('widget_login_remember_me', $instance['login_remember_me'] );
		$login_button = apply_filters('widget_login_button', $instance['login_button'] );
		$login_forgot = apply_filters('widget_login_forgot', $instance['login_forgot'] );
		$login_register = apply_filters('widget_login_register', $instance['login_register'] );
		
		// Start widget
		echo $before_widget;
		echo $before_title . $wtitle . $after_title;

		if (get_option(WPS_OPTIONS_PREFIX.'_ajax_widgets') == 'on') {
			// Parameters for AJAX
			echo '<div id="symposium_summary_Widget">';
			echo __('Loading...', WPS_TEXT_DOMAIN);
			echo '<div id="symposium_summary_Widget_show_loggedout" style="display:none">'.$show_loggedout.'</div>';
			echo '<div id="symposium_summary_Widget_form" style="display:none">'.$show_form.'</div>';
			echo '<div id="symposium_summary_Widget_login_url" style="display:none">'.$login_url.'</div>';
			echo '<div id="symposium_summary_Widget_show_avatar" style="display:none">'.$show_avatar.'</div>';
			echo '<div id="symposium_summary_Widget_show_avatar_size" style="display:none">'.$show_avatar_size.'</div>';
			echo '<div id="symposium_summary_Widget_login_username" style="display:none">'.$login_username.'</div>';
			echo '<div id="symposium_summary_Widget_login_password" style="display:none">'.$login_password.'</div>';
			echo '<div id="symposium_summary_Widget_login_remember_me" style="display:none">'.$login_remember_me.'</div>';
			echo '<div id="symposium_summary_Widget_login_button" style="display:none">'.$login_button.'</div>';
			echo '<div id="symposium_summary_Widget_login_forgot" style="display:none">'.$login_forgot.'</div>';
			echo '<div id="symposium_summary_Widget_login_register" style="display:none">'.$login_register.'</div>';
			echo '</div>';
		} else {
			__wps__do_summary_Widget($show_loggedout,$show_form,$login_url,$show_avatar,$login_username,$login_password,$login_remember_me,$login_button,$login_forgot,$login_register,$show_avatar_size);	
		}

		echo $after_widget;
		// End widget
		

	}
	
	// This updates the stored values
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		
		/* Strip tags (if needed) and update the widget settings. */
		$instance['wtitle'] = strip_tags( $new_instance['wtitle'] );
		$instance['show_loggedout'] = strip_tags( $new_instance['show_loggedout'] );
		$instance['show_form'] = strip_tags( $new_instance['show_form'] );
		$instance['login_url'] = strip_tags( $new_instance['login_url'] );
		$instance['show_avatar'] = strip_tags( $new_instance['show_avatar'] );
		$instance['show_avatar_size'] = strip_tags( $new_instance['show_avatar_size'] );
		$instance['login_username'] = strip_tags( $new_instance['login_username'] );
		$instance['login_password'] = strip_tags( $new_instance['login_password'] );
		$instance['login_remember_me'] = strip_tags( $new_instance['login_remember_me'] );
		$instance['login_button'] = strip_tags( $new_instance['login_button'] );
		$instance['login_forgot'] = strip_tags( $new_instance['login_forgot'] );
		$instance['login_register'] = strip_tags( $new_instance['login_register'] );
			
		return $instance;
	}
	
	// This is the admin form for the widget
	function form( $instance ) {

		/* Set up some default widget settings. */
		$defaults = array( 'show_avatar_size' => '100', 'wtitle' => 'Welcome...', 'show_loggedout' => 'on', 'show_loggedout' => '', 'login_username' => __('Username', WPS_TEXT_DOMAIN), 'login_password' => __('Password', WPS_TEXT_DOMAIN), 'login_remember_me' => __('Remember me?', WPS_TEXT_DOMAIN), 'login_button' => __('Login', WPS_TEXT_DOMAIN), 'login_forgot' => __('Forgotten password?', WPS_TEXT_DOMAIN), 'login_register' => __('Register', WPS_TEXT_DOMAIN) );
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>

		<p>
			<label for="<?php echo $this->get_field_id( 'wtitle' ); ?>"><?php echo __('Widget Title', WPS_TEXT_DOMAIN); ?>:</label>
			<input id="<?php echo $this->get_field_id( 'wtitle' ); ?>" name="<?php echo $this->get_field_name( 'wtitle' ); ?>" value="<?php echo $instance['wtitle']; ?>" />
		<br /><br />
			<label for="<?php echo $this->get_field_id( 'login_username' ); ?>"><?php echo __('Username prompt', WPS_TEXT_DOMAIN); ?>:</label><br />
			<?php $login_username = (isset($instance['login_username'])) ? $instance['login_username'] : ''; ?>
			<input id="<?php echo $this->get_field_id( 'login_username' ); ?>" name="<?php echo $this->get_field_name( 'login_username' ); ?>" value="<?php echo $login_username; ?>" /><br />
			<label for="<?php echo $this->get_field_id( 'login_password' ); ?>"><?php echo __('Password prompt', WPS_TEXT_DOMAIN); ?>:</label><br />
			<?php $login_password = (isset($instance['login_password'])) ? $instance['login_password'] : ''; ?>
			<input id="<?php echo $this->get_field_id( 'login_password' ); ?>" name="<?php echo $this->get_field_name( 'login_password' ); ?>" value="<?php echo $login_password; ?>" /><br />
			<label for="<?php echo $this->get_field_id( 'login_remember_me' ); ?>"><?php echo __('Remember me prompt', WPS_TEXT_DOMAIN); ?>:</label><br />
			<?php $login_remember_me = (isset($instance['login_remember_me'])) ? $instance['login_remember_me'] : ''; ?>
			<input id="<?php echo $this->get_field_id( 'login_remember_me' ); ?>" name="<?php echo $this->get_field_name( 'login_remember_me' ); ?>" value="<?php echo $login_remember_me; ?>" /><br />
			<label for="<?php echo $this->get_field_id( 'login_button' ); ?>"><?php echo __('Button text', WPS_TEXT_DOMAIN); ?>:</label><br />
			<?php $login_button = (isset($instance['login_button'])) ? $instance['login_button'] : ''; ?>
			<input id="<?php echo $this->get_field_id( 'login_button' ); ?>" name="<?php echo $this->get_field_name( 'login_button' ); ?>" value="<?php echo $login_button; ?>" /><br />
			<label for="<?php echo $this->get_field_id( 'login_forgot' ); ?>"><?php echo __('Forgot password prompt', WPS_TEXT_DOMAIN); ?>:</label><br />
			<?php $login_forgot = (isset($instance['login_forgot'])) ? $instance['login_forgot'] : ''; ?>
			<input id="<?php echo $this->get_field_id( 'login_forgot' ); ?>" name="<?php echo $this->get_field_name( 'login_forgot' ); ?>" value="<?php echo $login_forgot; ?>" /><br />
			<label for="<?php echo $this->get_field_id( 'login_register' ); ?>"><?php echo __('Register prompt', WPS_TEXT_DOMAIN); ?>:</label><br />
			<?php $login_register = (isset($instance['login_register'])) ? $instance['login_register'] : ''; ?>
			<input id="<?php echo $this->get_field_id( 'login_register' ); ?>" name="<?php echo $this->get_field_name( 'login_register' ); ?>" value="<?php echo $login_register; ?>" /><br />
		<br /><br />
			<input type="checkbox" id="<?php echo $this->get_field_id( 'show_avatar' ); ?>" name="<?php echo $this->get_field_name( 'show_avatar' ); ?>"
			<?php if (isset($instance['show_avatar']) && $instance['show_avatar'] == 'on') { echo " CHECKED"; } ?>
			/>
			<label for="<?php echo $this->get_field_id( 'show_avatar' ); ?>"><?php echo __('Show avatar', WPS_TEXT_DOMAIN); ?></label><br />
			<label for="<?php echo $this->get_field_id( 'show_avatar_size' ); ?>"><?php echo __('Size of avatar (in pixels, eg: 100)', WPS_TEXT_DOMAIN); ?>:</label><br />
			<?php $show_avatar_size = (isset($instance['show_avatar_size'])) ? $instance['show_avatar_size'] : ''; ?>
			<input id="<?php echo $this->get_field_id( 'show_avatar_size' ); ?>" style="width: 50px" name="<?php echo $this->get_field_name( 'show_avatar_size' ); ?>" value="<?php echo $show_avatar_size; ?>" /><br />
		<br />
			<input type="checkbox" id="<?php echo $this->get_field_id( 'show_loggedout' ); ?>" name="<?php echo $this->get_field_name( 'show_loggedout' ); ?>"
			<?php if ($instance['show_loggedout'] == 'on') { echo " CHECKED"; } ?>
			/>
			<label for="<?php echo $this->get_field_id( 'show_loggedout' ); ?>"><?php echo __('Show Login/Logout links', WPS_TEXT_DOMAIN); ?></label>
		<br />
			<input type="checkbox" id="<?php echo $this->get_field_id( 'show_form' ); ?>" name="<?php echo $this->get_field_name( 'show_form' ); ?>"
			<?php 
			$show_form = (isset($instance['show_form'])) ? $instance['show_form'] : '';
			if ($show_form == 'on') { echo " CHECKED"; } 
			?>
			/>
			<label for="<?php echo $this->get_field_id( 'show_form' ); ?>"><?php echo __('Show Login Form', WPS_TEXT_DOMAIN); ?></label>
		<br /><br />
			<label for="<?php echo $this->get_field_id( 'login_url' ); ?>"><?php echo __('Login URL (if using login form)', WPS_TEXT_DOMAIN); ?>:</label>
			<?php $login_url = (isset($instance['login_url'])) ? $instance['login_url'] : ''; ?>
			<input id="<?php echo $this->get_field_id( 'login_url' ); ?>" name="<?php echo $this->get_field_name( 'login_url' ); ?>" value="<?php echo $login_url; ?>" /><br />
			<?php echo __('Leave blank for current page (if the current page has values after # in the URL, they are not included as not passed to WordPress authentication).', WPS_TEXT_DOMAIN); ?>
		</p>
		<?php
	}

}

/** Forum: Needs answering ************************************************************************* **/
class __wps__forumnoanswer_Widget extends WP_Widget {

	function __wps__forumnoanswer_Widget() {
		/* Widget settings. */
		$widget_ops = array( 'classname' => 'widget_forumrnoanswer', 'description' => 'Shows recent posts without an answer.' );
		
		/* Widget control settings. */
		$control_ops = array( 'id_base' => 'forumnoanswer-widget' );
		
		/* Create the widget. */
		$this->WP_Widget( 'forumnoanswer-widget', WPS_WL_SHORT.': '.__('Topics without an accepted answer', WPS_TEXT_DOMAIN), $widget_ops, $control_ops );
	}
	
	// This is shown on the page
	function widget( $args, $instance ) {
		global $wpdb, $current_user;
		wp_get_current_user();
	
		extract( $args );
		
		// Get options
		$wtitle = apply_filters('widget_title', $instance['wtitle'] );
		$preview = apply_filters('widget_preview', $instance['preview'] );
		$cat_id = apply_filters('widget_cat_id', $instance['cat_id'] );
		$cat_id_exclude = apply_filters('widget_cat_id_exclude', $instance['cat_id_exclude'] );
		$timescale = apply_filters('widget_timescale', $instance['timescale'] );
		$postcount = apply_filters('widget_postcount', $instance['postcount'] );
		$groups = apply_filters('widget_groups', $instance['groups'] );
		
		// Start widget
		echo $before_widget;
		echo $before_title . $wtitle . $after_title;

		if (get_option(WPS_OPTIONS_PREFIX.'_ajax_widgets') == 'on') {
			// Parameters for AJAX
			echo '<div id="__wps__Forumnoanswer_Widget">';
			echo __('Loading...', WPS_TEXT_DOMAIN);
			echo '<div id="__wps__Forumnoanswer_Widget_preview" style="display:none">'.$preview.'</div>';
			echo '<div id="__wps__Forumnoanswer_Widget_cat_id" style="display:none">'.$cat_id.'</div>';
			echo '<div id="__wps__Forumnoanswer_Widget_cat_id_exclude" style="display:none">'.$cat_id_exclude.'</div>';
			echo '<div id="__wps__Forumnoanswer_Widget_timescale" style="display:none">'.$timescale.'</div>';
			echo '<div id="__wps__Forumnoanswer_Widget_postcount" style="display:none">'.$postcount.'</div>';
			echo '<div id="__wps__Forumnoanswer_Widget_groups" style="display:none">'.$groups.'</div>';
			echo '</div>';
		} else {
			__wps__do_Forumnoanswer_Widget($preview,$cat_id,$cat_id_exclude,$timescale,$postcount,$groups);			
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
		$instance['preview'] = strip_tags( $new_instance['preview'] );
		$instance['cat_id'] = strip_tags( $new_instance['cat_id'] );
		$instance['cat_id_exclude'] = strip_tags( $new_instance['cat_id_exclude'] );
		$instance['timescale'] = strip_tags( $new_instance['timescale'] );
		$instance['postcount'] = strip_tags( $new_instance['postcount'] );
		$instance['groups'] = strip_tags( $new_instance['groups'] );
		return $instance;
	}
	
	// This is the admin form for the widget
	function form( $instance ) {

		/* Set up some default widget settings. */
		$defaults = array( 'wtitle' => 'Topics without an accepted answer', 'cat_id' => '0', 'cat_id_exclude' => '0', 'preview' => '30', 'timescale' => 30, 'postcount' => 100, 'groups' => '' );
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>
		
		<p>
			<label for="<?php echo $this->get_field_id( 'wtitle' ); ?>"><?php echo __('Widget Title', WPS_TEXT_DOMAIN); ?>:</label>
			<input id="<?php echo $this->get_field_id( 'wtitle' ); ?>" name="<?php echo $this->get_field_name( 'wtitle' ); ?>" value="<?php echo $instance['wtitle']; ?>" />
		<br /><br />
			<label for="<?php echo $this->get_field_id( 'cat_id' ); ?>"><?php echo __('<strong>Categories to include</strong><br />List IDs, comma separated. (0 for all)', WPS_TEXT_DOMAIN); ?>:</label>
			<input id="<?php echo $this->get_field_id( 'cat_id' ); ?>" name="<?php echo $this->get_field_name( 'cat_id' ); ?>" value="<?php echo $instance['cat_id']; ?>" />
		<br /><br />
			<label for="<?php echo $this->get_field_id( 'cat_id_exclude' ); ?>"><?php echo __('<strong>Categories to exclude</strong><br />List IDs, comma separated. (0 for none)', WPS_TEXT_DOMAIN); ?>:</label>
			<input id="<?php echo $this->get_field_id( 'cat_id_exclude' ); ?>" name="<?php echo $this->get_field_name( 'cat_id_exclude' ); ?>" value="<?php echo $instance['cat_id_exclude']; ?>" />
		<br /><br />
			<input type="checkbox" id="<?php echo $this->get_field_id( 'groups' ); ?>" name="<?php echo $this->get_field_name( 'groups' ); ?>"
			<?php
			$groups = (isset($instance['groups'])) ? $instance['groups'] : '';
			if ($groups == 'on') { echo " CHECKED"; } ?>
			/>
			<?php if (function_exists('__wps__groups')) { ?>
			<label for="<?php echo $this->get_field_id( 'groups' ); ?>"><?php echo __('Include groups', WPS_TEXT_DOMAIN); ?></label>
			<?php } ?>
		<br /><br />
			<label for="<?php echo $this->get_field_id( 'preview' ); ?>"><?php echo __('Max length of preview', WPS_TEXT_DOMAIN); ?>:</label>
			<input id="<?php echo $this->get_field_id( 'preview' ); ?>" name="<?php echo $this->get_field_name( 'preview' ); ?>" value="<?php echo $instance['preview']; ?>" style="width: 30px" />
		<br /><br />
			<label for="<?php echo $this->get_field_id( 'timescale' ); ?>"><?php echo __('Time period (days)', WPS_TEXT_DOMAIN); ?>:</label>
			<input id="<?php echo $this->get_field_id( 'timescale' ); ?>" name="<?php echo $this->get_field_name( 'timescale' ); ?>" value="<?php echo $instance['timescale']; ?>" style="width: 30px" />
		<br /><br />
			<label for="<?php echo $this->get_field_id( 'postcount' ); ?>"><?php echo __('Maximum number of posts', WPS_TEXT_DOMAIN); ?>:</label>
			<input id="<?php echo $this->get_field_id( 'postcount' ); ?>" name="<?php echo $this->get_field_name( 'postcount' ); ?>" value="<?php echo $instance['postcount']; ?>" style="width: 30px" />
		</p>
		<?php
	}

}

/** Forum: Top experts ************************************************************************* **/
class __wps__forumexperts_Widget extends WP_Widget {

	function __wps__forumexperts_Widget() {
		/* Widget settings. */
		$widget_ops = array( 'classname' => 'widget_forumexperts', 'description' => 'Shows top members with answers accepted.' );
		
		/* Widget control settings. */
		$control_ops = array( 'id_base' => 'forumexperts-widget' );
		
		/* Create the widget. */
		$this->WP_Widget( 'forumexperts-widget', WPS_WL_SHORT.': '.__('Top Experts', WPS_TEXT_DOMAIN), $widget_ops, $control_ops );
	}
	
	// This is shown on the page
	function widget( $args, $instance ) {
		global $wpdb, $current_user;
		wp_get_current_user();
	
		extract( $args );
		
		// Get options
		$wtitle = apply_filters('widget_title', $instance['wtitle'] );
		$cat_id = apply_filters('widget_cat_id', $instance['cat_id'] );
		$cat_id_exclude = apply_filters('widget_cat_id_exclude', $instance['cat_id_exclude'] );
		$timescale = apply_filters('widget_timescale', $instance['timescale'] );
		$postcount = apply_filters('widget_postcount', $instance['postcount'] );
		$groups = apply_filters('widget_groups', $instance['groups'] );
		
		// Start widget
		echo $before_widget;
		echo $before_title . $wtitle . $after_title;

		if (get_option(WPS_OPTIONS_PREFIX.'_ajax_widgets') == 'on') {
			// Parameters for AJAX
			echo '<div id="__wps__Forumexperts_Widget">';
			echo __('Loading...', WPS_TEXT_DOMAIN);
			echo '<div id="__wps__Forumexperts_Widget_cat_id" style="display:none">'.$cat_id.'</div>';
			echo '<div id="__wps__Forumexperts_Widget_cat_id_exclude" style="display:none">'.$cat_id_exclude.'</div>';
			echo '<div id="__wps__Forumexperts_Widget_timescale" style="display:none">'.$timescale.'</div>';
			echo '<div id="__wps__Forumexperts_Widget_postcount" style="display:none">'.$postcount.'</div>';
			echo '<div id="__wps__Forumexperts_Widget_groups" style="display:none">'.$groups.'</div>';
			echo '</div>';
		} else {
		__wps__do_Forumexperts_Widget($cat_id,$cat_id_exclude,$timescale,$postcount,$groups);			
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
		$instance['cat_id'] = strip_tags( $new_instance['cat_id'] );
		$instance['cat_id_exclude'] = strip_tags( $new_instance['cat_id_exclude'] );
		$instance['timescale'] = strip_tags( $new_instance['timescale'] );
		$instance['postcount'] = strip_tags( $new_instance['postcount'] );
		$instance['groups'] = strip_tags( $new_instance['groups'] );
		return $instance;
	}
	
	// This is the admin form for the widget
	function form( $instance ) {

		/* Set up some default widget settings. */
		$defaults = array( 'wtitle' => 'Top Experts', 'cat_id' => '0', 'cat_id_exclude' => '0', 'timescale' => 30, 'postcount' => 10, 'groups' => '' );
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>
		
		<p>
			<label for="<?php echo $this->get_field_id( 'wtitle' ); ?>"><?php echo __('Widget Title', WPS_TEXT_DOMAIN); ?>:</label>
			<input id="<?php echo $this->get_field_id( 'wtitle' ); ?>" name="<?php echo $this->get_field_name( 'wtitle' ); ?>" value="<?php echo $instance['wtitle']; ?>" />
		<br /><br />
			<label for="<?php echo $this->get_field_id( 'cat_id' ); ?>"><?php echo __('<strong>Categories to include</strong><br />List IDs, comma separated. (0 for all)', WPS_TEXT_DOMAIN); ?>:</label>
			<input id="<?php echo $this->get_field_id( 'cat_id' ); ?>" name="<?php echo $this->get_field_name( 'cat_id' ); ?>" value="<?php echo $instance['cat_id']; ?>" />
		<br /><br />
			<label for="<?php echo $this->get_field_id( 'cat_id_exclude' ); ?>"><?php echo __('<strong>Categories to exclude</strong><br />List IDs, comma separated. (0 for none)', WPS_TEXT_DOMAIN); ?>:</label>
			<input id="<?php echo $this->get_field_id( 'cat_id_exclude' ); ?>" name="<?php echo $this->get_field_name( 'cat_id_exclude' ); ?>" value="<?php echo $instance['cat_id_exclude']; ?>" />
		<br /><br />
			<input type="checkbox" id="<?php echo $this->get_field_id( 'groups' ); ?>" name="<?php echo $this->get_field_name( 'groups' ); ?>"
			<?php
			$groups = (isset($instance['groups'])) ? $instance['groups'] : '';
			if ($groups == 'on') { echo " CHECKED"; } ?>
			/>
			<?php if (function_exists('__wps__groups')) { ?>
			<label for="<?php echo $this->get_field_id( 'groups' ); ?>"><?php echo __('Include groups', WPS_TEXT_DOMAIN); ?></label>
			<?php } ?>
		<br /><br />
			<label for="<?php echo $this->get_field_id( 'timescale' ); ?>"><?php echo __('Time period (days)', WPS_TEXT_DOMAIN); ?>:</label>
			<input id="<?php echo $this->get_field_id( 'timescale' ); ?>" name="<?php echo $this->get_field_name( 'timescale' ); ?>" value="<?php echo $instance['timescale']; ?>" style="width: 30px" />
		<br /><br />
			<label for="<?php echo $this->get_field_id( 'postcount' ); ?>"><?php echo __('Maximum number of experts', WPS_TEXT_DOMAIN); ?>:</label>
			<input id="<?php echo $this->get_field_id( 'postcount' ); ?>" name="<?php echo $this->get_field_name( 'postcount' ); ?>" value="<?php echo $instance['postcount']; ?>" style="width: 30px" />
		</p>
		<?php
	}

}

/** Alerts: Latest alerts ************************************************************************* **/
class __wps__alerts_Widget extends WP_Widget {
 

	function __wps__alerts_Widget() {
		/* Widget settings. */
		$widget_ops = array( 'classname' => 'widget_alerts', 'description' => 'Shows recent alerts for the member.' );
		
		/* Widget control settings. */
		$control_ops = array( 'id_base' => 'alerts-widget' );
		
		/* Create the widget. */
		$this->WP_Widget( 'alerts-widget', WPS_WL_SHORT.': '.__('Recent Alerts', WPS_TEXT_DOMAIN), $widget_ops, $control_ops );
	}
	
	// This is shown on the page
	function widget( $args, $instance ) {
		global $wpdb, $current_user;
		wp_get_current_user();
	
		if (is_user_logged_in()) {
			
	 		extract( $args );
			
			// Get options
			$wtitle = apply_filters('widget_title', $instance['wtitle'] );
			$postcount = apply_filters('widget_postcount', $instance['postcount'] );
			
			// Start widget
			echo $before_widget;
			echo $before_title . $wtitle . $after_title;

			if (get_option(WPS_OPTIONS_PREFIX.'_ajax_widgets') == 'on') {
				// Parameters for AJAX
				echo '<div id="__wps__Alerts_Widget">';
				echo __('Loading...', WPS_TEXT_DOMAIN);
				echo '<div id="__wps__Alerts_Widget_postcount" style="display:none">'.$postcount.'</div>';
				echo '</div>';
			} else {
				__wps__do_Alerts_Widget($postcount);			
			}

			// End content
			
			echo $after_widget;
			// End widget
		}
	}
	
	// This updates the stored values
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		
		/* Strip tags (if needed) and update the widget settings. */
		$instance['wtitle'] = strip_tags( $new_instance['wtitle'] );
		$instance['postcount'] = strip_tags( $new_instance['postcount'] );
		return $instance;
	}
	
	// This is the admin form for the widget
	function form( $instance ) {

		/* Set up some default widget settings. */
		$defaults = array( 'wtitle' => 'Recent Alerts', 'postcount' => 10 );
		$instance = wp_parse_args( (array) $instance, $defaults ); ?>
		<?php '<p>'._e('This widget is only shown to logged in users.', WPS_TEXT_DOMAIN).'</p>'; ?>
		<p>
			<label for="<?php echo $this->get_field_id( 'wtitle' ); ?>"><?php echo __('Widget Title', WPS_TEXT_DOMAIN); ?>:</label>
			<input id="<?php echo $this->get_field_id( 'wtitle' ); ?>" name="<?php echo $this->get_field_name( 'wtitle' ); ?>" value="<?php echo $instance['wtitle']; ?>" />
		<br /><br />
			<label for="<?php echo $this->get_field_id( 'postcount' ); ?>"><?php echo __('Maximum number of experts', WPS_TEXT_DOMAIN); ?>:</label>
			<input id="<?php echo $this->get_field_id( 'postcount' ); ?>" name="<?php echo $this->get_field_name( 'postcount' ); ?>" value="<?php echo $instance['postcount']; ?>" style="width: 30px" />
		</p>
		<?php
	}

}


?>
