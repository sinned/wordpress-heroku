<?php

include_once('../../../../wp-config.php');

// Start lounge content
if ($_POST['action'] == 'menu_lounge') {

	global $current_user;
	$html = '';

	if (is_user_logged_in()) {

		// This filter allows others to add text (or whatever) above the output
		$html = apply_filters ( '__wps__lounge_filter_top', $html);

		// Display the comment form
		$html .= '<div id="__wps__lounge_add_comment_div">';
		$html .= '<input type="text" class="input-field" id="__wps__lounge_add_comment" 
			onblur="this.value=(this.value==\'\') 
			? \''.__("Add a comment..", WPS_TEXT_DOMAIN).'\' 
			: this.value;" onfocus="this.value=(this.value==\''.__("Add a comment..", WPS_TEXT_DOMAIN).'\') ? \'\' 
			: this.value;" value="'.__("Add a comment..", WPS_TEXT_DOMAIN).'">';
		$html .= '&nbsp;<input id="__wps__lounge_add_comment_button" type="submit" class="__wps__button" value="'.__('Add', WPS_TEXT_DOMAIN).'" /> ';
		$html .= '</div>';

	}

	// Prepare for the output (which is created via AJAX)
	$html .= '<div id="__wps__lounge_div">';
	$html .= "<img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/busy.gif' />";
	$html .= '</div>';

	echo $html;
	exit;	
}

// Get lounge contents
if ($_POST['action'] == 'get_comments') {

	global $wpdb, $current_user;

   	$inactive = $_POST['inactive'];
   	$offline = $_POST['offline'];
	$max_items = get_option(WPS_OPTIONS_PREFIX."_lounge_max_rows")+0;
	if ($max_items == 0) { $max_items = 15; }

	// Get comments maximum of $max_items
	$sql = "SELECT l.lid, l.comment, l.author, l.added, u.display_name, u.ID
		FROM ".$wpdb->base_prefix."symposium_lounge l 
		LEFT JOIN ".$wpdb->base_prefix."users u ON l.author = u.ID 
		ORDER BY l.lid DESC LIMIT 0,".$max_items;

	$comment_array = array();
	$comment_list = $wpdb->get_results($sql);
	foreach ($comment_list as $comment) {

		$add = array (	
			'lid' => $comment->lid,
			'comment' => $comment->comment,
			'author' => $comment->author,
			'added' => $comment->added,
			'display_name' => $comment->display_name,
			'ID' => $comment->ID,
			'last_activity' => __wps__get_meta($comment->ID, 'last_activity'),
			'status' => __wps__get_meta($comment->ID, 'status')
		);
		
		array_push($comment_array, $add);		
	}
	$comments = __wps__sub_val_sort($comment_array, 'lid', false);

	// Prepare to return comments in JSON format
	$return_arr = array();
	
	// Loop through comments, adding to array if any exist
	if ($comments) {
		foreach ($comments as $comment) {

			// Work out if they are active or not
			$time_now = time();
			if ($comment['last_activity'] && $comment['status'] != 'offline') {
				$last_active_minutes = __wps__convert_datetime($comment['last_activity']);
				$last_active_minutes = floor(($time_now-$last_active_minutes)/60);
			} else {
				$last_active_minutes = 999999999;
			}
			if ($last_active_minutes >= $offline) {
				$row_array['status'] = 'loggedout';
			} else {
				if ($last_active_minutes >= $inactive) {
					$row_array['status'] = 'inactive';
				} else {
					$row_array['status'] = 'online';
				}
			}

			$row_array['lid'] = $comment['lid'];
			$row_array['comment'] = convert_smilies(stripslashes($comment['comment']));
			$row_array['author_id'] = $comment['author'];
			if ($comment['ID'] == $current_user->ID) {
				$row_array['author'] = 'You';
			} else {
				$row_array['author'] = $comment['display_name'];
			}
			$row_array['added'] = __wps__time_ago($comment['added']);
			array_push($return_arr, $row_array);
		}	
	} 
	
	
	echo json_encode($return_arr);
	exit;

}


// Add comment
if ($_POST['action'] == 'add_comment') {

	global $wpdb, $current_user;
    
    if (is_user_logged_in()) {

        $comment = sanitize_text_field($_POST['comment']);

        if ( ($comment != __(addslashes("Add a comment.."), "wp-symposium")) && ($comment != '') ) {

            $wpdb->query( $wpdb->prepare( "
                INSERT INTO ".$wpdb->base_prefix."symposium_lounge
                ( 	author, 
                    added,
                    comment
                )
                VALUES ( %d, %s, %s )", 
                array(
                    $current_user->ID, 
                    date("Y-m-d H:i:s"),
                    $comment
                    ) 
                ) );
        }
        
    }
    
	exit;
}

// Delete comment
if ($_POST['action'] == 'delete_comment') {

	global $wpdb, $current_user;

	$comment_id = $_POST['comment_id'];

	if ( __wps__get_current_userlevel($current_user->ID) == 5 ) {
		$sql = "DELETE FROM ".$wpdb->base_prefix."symposium_lounge WHERE lid = %d";
		$rows_affected = $wpdb->query( $wpdb->prepare($sql, $comment_id) );
		if ( $rows_affected > 0 ) {	
			echo "OK";
		} else {
			echo "Failed to delete comment, please try again.";
		}
	}

	exit;
}


		
?>

	
