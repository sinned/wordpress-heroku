<?php
/*
WP Symposium Mail
Description: Mail component for the WP Symposium suite of plug-ins. Put [symposium-mail] on any WordPress page.
*/

// Get constants
require_once(dirname(__FILE__).'/default-constants.php');

function __wps__mail() {	
	
	global $wpdb, $current_user;
	wp_get_current_user();

	$thispage = get_permalink();
	if ($thispage[strlen($thispage)-1] != '/') { $thispage .= '/'; }
	$mail_url = get_option(WPS_OPTIONS_PREFIX.'_mail_url');
	$mail_all = get_option(WPS_OPTIONS_PREFIX.'_mail_all');

	if (isset($_GET['page_id']) && $_GET['page_id'] != '') {
		// No Permalink
		$thispage = $mail_url;
		$q = "&";
	} else {
		$q = "?";
	}
	
	$plugin_dir = WPS_PLUGIN_URL;
	
	$html = '';
	
	if (is_user_logged_in()) {

		$inbox_active = 'active';
		$sent_active = 'inactive';
		$compose_active = 'inactive';

		$template = '';
		$template .= '<div id="mail_tabs">';
		$template .= '<div id="symposium_compose_tab" class="mail_tab nav-tab-'.$compose_active.'"><a href="javascript:void(0)" class="nav-tab-'.$compose_active.'-link" style="text-decoration:none !important;">'.__('Compose', WPS_TEXT_DOMAIN).'</a></div>';
		$template .= '<div id="symposium_inbox_tab" class="mail_tab nav-tab-'.$inbox_active.'"><a href="javascript:void(0)" class="nav-tab-'.$inbox_active.'-link" style="text-decoration:none !important;">'.__('In Box', WPS_TEXT_DOMAIN).' <span id="in_unread"></span></a></div>';
		$template .= '<div id="symposium_sent_tab" class="mail_tab nav-tab-'.$sent_active.'"><a href="javascript:void(0)" class="nav-tab-'.$sent_active.'-link" style="text-decoration:none !important;">'.__('Sent Items', WPS_TEXT_DOMAIN).'</a></div>';
		$template .= '</div>';	
		
		$template .= '<div id="mail-main-div">';

			$template .= "<div id='mail_sent_message'></div>";
		
			$template .= "[compose_form]";

			$template .= "<div id='mailbox'>";
				$template .= "<div id='__wps__search'>";
					$template .= "<input id='search_inbox' type='text' style='width: 160px'>";
					$template .= "<input id='search_inbox_go' class='__wps__button message_search' type='submit' style='margin-left:10px;' value='".__('Search', WPS_TEXT_DOMAIN)."'>";
					$template .= "[unread]";
				$template .= "</div>";
				$template .= "<div>";
					$template .= "<select id='__wps__mail_bulk_action'>";
					$template .= "<option value=''>".__('Bulk action...', WPS_TEXT_DOMAIN).'</option>';
					$template .= "<option value='delete'>".__('Delete checked items', WPS_TEXT_DOMAIN).'</option>';
					$template .= "<option id='__wps__mark_all' value='readall'>".__('Mark all mail as read', WPS_TEXT_DOMAIN).'</option>';
					$template .= "<option value='deleteall'>".__('Delete all mail!', WPS_TEXT_DOMAIN).'</option>';
					$template .= "<option value='recoverall'>".__('Recover all deleted mail', WPS_TEXT_DOMAIN).'</option>';
					$template .= "</select>";
				$template .= "</div>";
				$template .= "<div id='mailbox_list'></div>";
				$template .= "<div id='messagebox'></div>";
			$template .= "</div>";
		
		$template .= '</div>';	
		
		$html .= '<div id="next_message_id" style="display:none">0</div>';
		$html .= '<div class="__wps__wrapper">'.$template.'</div>';
			
		// Compose Form	
		if (WPS_CURRENT_USER_PAGE == $current_user->ID) {
		
			$compose = '<div id="compose_form" style="display:none">';
			
				$compose .= '<div id="compose_mail_to">';

					$compose .= '<div class="send_button" style="padding:4px;">';
					$compose .= '<input type="submit" id="mail_cancel_button" class="__wps__button" value="'.__('Cancel', WPS_TEXT_DOMAIN).'" />';
					$compose .= '<input type="submit" id="mail_send_button" class="__wps__button" value="'.__('Send', WPS_TEXT_DOMAIN).'" />';
					$compose .= '</div>';
	 	
					$compose .= '<select id="mail_recipient_list">';
					$compose .= '<option class="__wps__mail_recipient_list_option" value='.$current_user->ID.'>'.$current_user->display_name.'</option>';
	
					if ($mail_all == 'on' || __wps__get_current_userlevel() == 5) {
						
						$sql = "SELECT u.ID AS friend_to, u.display_name
						FROM ".$wpdb->base_prefix."users u
						ORDER BY u.display_name";

						$friends = $wpdb->get_results($sql);
					
					} else {
						
						$sql = "SELECT f.friend_to, u.display_name
						FROM ".$wpdb->base_prefix."symposium_friends f 
						INNER JOIN ".$wpdb->base_prefix."users u ON f.friend_to = u.ID 
						WHERE f.friend_from = %d AND f.friend_accepted = 'on' 
						ORDER BY u.display_name";

						$friends = $wpdb->get_results($wpdb->prepare($sql, $current_user->ID));	

					}
					
							
					if ($friends) {
						foreach ($friends as $friend) {
							$compose .= '<option class="__wps__mail_recipient_list_option" value='.$friend->friend_to.'>'.$friend->display_name.'</option>';
						}
					}
					$compose .= '</select>';
	 			$compose .= '</div>';	
				
				$compose .= '<div class="new-topic-subject label">'.__('Subject', WPS_TEXT_DOMAIN).'</div>';
 				$compose .= "<input type='text' id='compose_subject' class='new-topic-subject-input' value='' />";
				
				$compose .= '<div id="compose_mail_message">';
					$compose .= '<div class="new-topic-subject label">'.__('Message', WPS_TEXT_DOMAIN).'</div>';
					$compose .= '<textarea class="reply-topic-subject-text" id="compose_text"></textarea>';
	 			$compose .= '</div>';
				
				$compose .= '<input type="hidden" id="compose_previous" value="" />';
		
			$compose .= "</div>";

		} else {
			
			$compose = '<div id="compose_form" style="display:none">';
				$compose .= __('New mail can only be sent by this member.', WPS_TEXT_DOMAIN).'<br /><br />';
				$compose .= '<input id="mail_cancel_button" type="submit" class="__wps__button" value="'.__('Back to mail', WPS_TEXT_DOMAIN).'" />';
			$compose .= "</div>";
			
			
		}
				
		// Replace template codes
		$html = str_replace("[compose_form]", $compose, stripslashes($html));
		$html = str_replace("[compose]", __("Compose", WPS_TEXT_DOMAIN), stripslashes($html));
		$html = str_replace("[inbox]", __("Inbox", WPS_TEXT_DOMAIN), stripslashes($html));
		$html = str_replace("[sent]", __("Sent", WPS_TEXT_DOMAIN), stripslashes($html));
		$html = str_replace("[unread]", "<input type='checkbox' id='unread_only' /> ".__("Unread only", WPS_TEXT_DOMAIN), stripslashes($html));
		

	} else {
		// Not logged in
		$html .= __('You have to login to access your mail.', WPS_TEXT_DOMAIN);
	}
	
	// Send HTML
	return $html;

}

/* ====================================================== SET SHORTCODE ====================================================== */

if (!is_admin()) {
	add_shortcode(WPS_SHORTCODE_PREFIX.'-mail', '__wps__mail');  
}



?>
