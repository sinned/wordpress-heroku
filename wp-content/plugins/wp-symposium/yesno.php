<?php
/*
Yes/No Widget for WP Symposium
Adds a WP Symposium Widget to display a Yes/No vote with chart (bar or pie). Requires a licence from http://www.jscharts.com to remove small JS Charts logo. Requires WP Symposium core plugin to be activated.   
*/


/** Add our function to the widgets_init hook. **/

add_action( 'widgets_init', '__wps__load_widget_yesno_vote' );

function __wps__load_widget_yesno_vote() {
	register_widget( '__wps__vote_Widget' );
}


/** Vote ************************************************************************* **/
class __wps__vote_Widget extends WP_Widget {

	function __wps__vote_Widget() {
		/* Widget settings. */
		$widget_ops = array( 'classname' => '__wps__widget_vote', 'description' => 'Allows members to vote on a YES/NO question.' );
		
		/* Widget control settings. */
		$control_ops = array( 'id_base' => '__wps__vote_widget' );
		
		/* Create the widget. */
		$this->WP_Widget( '__wps__vote_widget', WPS_WL_SHORT.': '.__('Vote', WPS_TEXT_DOMAIN), $widget_ops, $control_ops );
	}
	
	// This is shown on the page
	function widget( $args, $instance ) {
		
		global $wpdb, $current_user;
		wp_get_current_user();
			
		extract( $args );

		// Get options
		$__wps__vote_question = apply_filters('__wps__widget_vote_question', $instance['symposium_vote_question'] );
		$__wps__vote_forum = apply_filters('widget___wps__vote_forum', $instance['__wps__vote_forum'] );
		$__wps__vote_counts = apply_filters('__wps__widget_vote_counts', $instance['symposium_vote_counts'] );
		$__wps__vote_type = apply_filters('__wps__widget_vote_type', $instance['symposium_vote_type'] );
		$__wps__vote_key = apply_filters('__wps__widget_vote_key', $instance['symposium_vote_key'] );
		
		// Start widget
		echo $before_widget;
		echo $before_title . $__wps__vote_question . $after_title;
		
		// Content of widget

		echo '<div id="__wps__chartcontainer">Chart of results</div>';
		echo '<div id="symposium_chart_type" style="display:none">'.$__wps__vote_type.'</div>';
		echo '<div id="symposium_chart_counts" style="display:none">'.$__wps__vote_counts.'</div>';
		echo '<div id="symposium_chart_key" style="display:none">'.$__wps__vote_key.'</div>';

		// Store values
		$__wps__vote_yes = get_option(WPS_OPTIONS_PREFIX."_vote_yes");
		if ($__wps__vote_yes != false) {
			$__wps__vote_yes = (int) $__wps__vote_yes;
		} else {
		    update_option(WPS_OPTIONS_PREFIX."_vote_yes", 0);	    	   	
			$__wps__vote_yes = 0;
		}
		$__wps__vote_no = get_option(WPS_OPTIONS_PREFIX."_vote_no");
		if ($__wps__vote_no != false) {
			$__wps__vote_no = (int) $__wps__vote_no;
		} else {
		    update_option(WPS_OPTIONS_PREFIX."_vote_no", 0);	    	   	
			$__wps__vote_no = 0;
		}

		echo '<div id="__wps__chart_yes" style="display:none">'.$__wps__vote_yes.'</div>';
		echo '<div id="symposium_chart_no" style="display:none">'.$__wps__vote_no.'</div>';
			
		if (is_user_logged_in()) {
			
			$voted = __wps__get_meta($current_user->ID, 'widget_voted');
			if ($voted == "on") {
				
				echo "<p>";
				echo __('Thank you for voting', WPS_TEXT_DOMAIN).".";
				if ($__wps__vote_forum != '') {
					echo "<br /><a href='".$__wps__vote_forum."'>".__('Discuss this on the forum', WPS_TEXT_DOMAIN)."...</a>";
				}
				echo "</p>";

			} else {
			
			
				echo "<div id='__wps__vote_forum'>";
					echo "<p>".__('Your vote', WPS_TEXT_DOMAIN).": ";
					echo "<a href='javascript:void(0)' title='yes' class='symposium_answer' value='".__("Yes", WPS_TEXT_DOMAIN)."'>".__("Yes", WPS_TEXT_DOMAIN)."</a> ".__('or', WPS_TEXT_DOMAIN)." ";
					echo "<a href='javascript:void(0)' title='no' class='symposium_answer' value='".__("No", WPS_TEXT_DOMAIN)."'>".__("No", WPS_TEXT_DOMAIN)."</a>";
					if ($__wps__vote_forum != '') {
						echo "<br /><a href='".$__wps__vote_forum."'>".__('Discuss this on the forum', WPS_TEXT_DOMAIN)."...</a>";
					}
					echo "</p>";
				echo "</div>";
				
				echo "<div id='__wps__vote_thankyou'>";
					echo "<p>".__("Thank you for voting, refresh the page for latest results", WPS_TEXT_DOMAIN);
					if ($__wps__vote_forum != '') {
						echo "<br /><a href='".$__wps__vote_forum."'>".__('Discuss this on the forum', WPS_TEXT_DOMAIN)."...</a>";
					}
					echo "</p>";
				echo "</div>";
		
			}
			
		} else {
			
			echo "<p>".__("Log in to vote...", WPS_TEXT_DOMAIN)."</p>";
			
		}
				
		// End content
		
		echo $after_widget;
		// End widget
	}
	
	// This updates the stored values
	function update( $new_instance, $old_instance ) {

		global $wpdb;

		$instance = $old_instance;

		// Reset
		if (strip_tags( $new_instance['symposium_reset_votes'] ) == 'on' ) {
			update_option( "symposium_vote_yes", 0 );
			update_option( "symposium_vote_no", 0 );
			$users = $wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->base_prefix."users", ''));
			foreach ($users as $user) {
				__wps__update_meta($user->ID, 'widget_voted', '');
			}
		}
		
		/* Strip tags (if needed) and update the widget settings. */
		$instance['symposium_vote_question'] = strip_tags( $new_instance['symposium_vote_question'] );
		$instance['__wps__vote_forum'] = strip_tags( $new_instance['__wps__vote_forum'] );
		$instance['symposium_vote_counts'] = strip_tags( $new_instance['symposium_vote_counts'] );
		$instance['symposium_vote_type'] = strip_tags( $new_instance['symposium_vote_type'] );
		$instance['symposium_vote_key'] = strip_tags( $new_instance['symposium_vote_key'] );
		return $instance;
	}
	
	// This is the admin form for the widget
	function form( $instance ) {

		/* Set up some default widget settings. */
		$defaults = array( 'symposium_vote_question' => __('A yes/no question...', WPS_TEXT_DOMAIN), '__wps__vote_forum' => '', 'symposium_vote_counts' => '', 'symposium_vote_type' => 'bar', 'symposium_vote_key' => '' );
		$instance = wp_parse_args( (array) $instance, $defaults ); 

		$__wps__vote_yes = get_option(WPS_OPTIONS_PREFIX."_vote_yes");
		$__wps__vote_no = get_option(WPS_OPTIONS_PREFIX."_vote_no");

		echo "<p><span style='font-weight:bold'>".__('Results so far', WPS_TEXT_DOMAIN)."</span><br />";
		echo __("Yes", WPS_TEXT_DOMAIN).": ".$__wps__vote_yes."<br />";
		echo __("No", WPS_TEXT_DOMAIN).": ".$__wps__vote_no."</p>";
		?>		
		<p>
			<?php
			$msg = 'The latest free version of JSChart introduced a watermark - the JSChart logo.<br />
To remove the logo you need to purchase a domain licence from Jumpeye Components.<br />
Once purchased, you will be provided with a domain key that will look something like: 5322c8a55773740e665f7ecb627d9373 (this is just an example).<br />
Copy and paste this domain key below. 
Make sure that the domain you set with Jumpeye Components matches the domain of your website!';
			?>
			<span class="__wps__tooltip" title="<?php echo $msg ?>">?</span>
			<label 	for="<?php echo $this->get_field_id( 'symposium_vote_key' ); ?>"><?php echo __('Domain key', WPS_TEXT_DOMAIN); ?>:
			<br /></label>
			<input 	id="<?php echo $this->get_field_id( 'symposium_vote_key' ); ?>" 
					name="<?php echo $this->get_field_name( 'symposium_vote_key' ); ?>" 
					value="<?php echo $instance['symposium_vote_key']; ?>" />
		<br /><br />
		<label 	for="<?php echo $this->get_field_id( 'symposium_vote_question' ); ?>"><?php echo __('Question', WPS_TEXT_DOMAIN); ?>:<br /></label>
			<input 	id="<?php echo $this->get_field_id( 'symposium_vote_question' ); ?>" 
					name="<?php echo $this->get_field_name( 'symposium_vote_question' ); ?>" 
					value="<?php echo $instance['symposium_vote_question']; ?>" />
		<br /><br />
			<label 	for="<?php echo $this->get_field_id( '__wps__vote_forum' ); ?>"><?php echo __('Forum Link', WPS_TEXT_DOMAIN); ?>:<br /></label>
			<input 	id="<?php echo $this->get_field_id( '__wps__vote_forum' ); ?>" 
					name="<?php echo $this->get_field_name( '__wps__vote_forum' ); ?>" 
					value="<?php echo $instance['__wps__vote_forum']; ?>" />
		<br /><br />
			<label for="<?php echo $this->get_field_id( 'symposium_vote_counts' ); ?>"><?php echo __('Show values', WPS_TEXT_DOMAIN); ?>:</label>
			<input type="checkbox" id="<?php echo $this->get_field_id( 'symposium_vote_counts' ); ?>" name="<?php echo $this->get_field_name( 'symposium_vote_counts' ); ?>"
			<?php if ($instance['symposium_vote_counts'] == 'on') { echo " CHECKED"; } ?>
			/>
			<br /><em>(if not, percentages shown)</em>
		<br /><br />
			<label for="<?php echo $this->get_field_id( 'symposium_vote_type' ); ?>"><?php echo __('Chart type', WPS_TEXT_DOMAIN); ?>:</label>
			<select type="checkbox" id="<?php echo $this->get_field_id( 'symposium_vote_type' ); ?>" name="<?php echo $this->get_field_name( 'symposium_vote_type' ); ?>">
				<option value="pie" <?php if ($instance['symposium_vote_type'] == 'pie') { echo " SELECTED"; } ?> >Pie</option>
				<option value="bar" <?php if ($instance['symposium_vote_type'] == 'bar') { echo " SELECTED"; } ?> >Bar</option>
			</select>
		<br /><br />
			<label for="<?php echo $this->get_field_id( 'symposium_reset_votes' ); ?>"><?php echo __('Reset votes?', WPS_TEXT_DOMAIN); ?>:</label>
			<input type="checkbox" id="<?php echo $this->get_field_id( 'symposium_reset_votes' ); ?>" name="<?php echo $this->get_field_name( 'symposium_reset_votes' ); ?>"
			 />
		</p>
		<?php
	}

}

?>
