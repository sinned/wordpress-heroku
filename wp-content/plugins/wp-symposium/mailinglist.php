<?php
/*
WP Symposium Reply-by-Email
Description: Allows replies to forum notifications by email.
*/


// Get constants
require_once(dirname(__FILE__).'/default-constants.php');

/* ====================================================================== MAIN =========================================================================== */



// get any waiting emails and act upon them
function __wps__mailinglist() {

}


// add custom time to cron
function __wps__mailinglist_filter_cron_schedules( $schedules ) {
	$schedules['__wps__mailinglist_interval'] = array(
		'interval' => get_option(WPS_OPTIONS_PREFIX.'_mailinglist_cron'),
		'display' => sprintf(__('%s reply-by-email interval', WPS_TEXT_DOMAIN), WPS_WL)
	);
	return $schedules;
}
add_filter( 'cron_schedules', '__wps__mailinglist_filter_cron_schedules' );

// send automatic scheduled email
if ( !wp_next_scheduled('__wps__mailinglist_hook') ) {
	wp_schedule_event( time(), '__wps__mailinglist_interval', '__wps__mailinglist_hook' ); // Schedule event
}

// This is what is run
function __wps__mailinglist_hook_function() {
	__wps__check_pop3(false,true);
}
add_action('__wps__mailinglist_hook', '__wps__mailinglist_hook_function');
 

function __wps__check_pop3($output=false,$process=true) {
	
	if (function_exists('__wps__mailinglist')) {
		
		if (!isset($_SESSION['__wps__mailinglist_lock']) || $_SESSION['__wps__mailinglist_lock'] != 'locked') {
			
			$_SESSION['__wps__mailinglist_lock'] = 'locked';
			
			require_once(WPS_PLUGIN_DIR.'/class.wps_forum.php');
			$wps_forum = new wps_forum();
			
			global $wpdb;
			
			if ($output) {
				if ($process) {
					echo '<h3>'.__('Processing waiting email...', WPS_TEXT_DOMAIN).'</h3>';
				} else {
					echo '<h3>'.__('Checking for waiting email, but not processing...', WPS_TEXT_DOMAIN).'</h3>';
				}
			}
		
			$server = get_option(WPS_OPTIONS_PREFIX.'_mailinglist_server');
			$port = get_option(WPS_OPTIONS_PREFIX.'_mailinglist_port');
			$username = get_option(WPS_OPTIONS_PREFIX.'_mailinglist_username');
			$password = get_option(WPS_OPTIONS_PREFIX.'_mailinglist_password');
			
			if ($mbox = imap_open ("{".$server.":".$port."/pop3}INBOX", $username, $password) ) {
				
				if ($output) echo __('Connected', WPS_TEXT_DOMAIN).', ';
				
				$num_msg = imap_num_msg($mbox);
				if ($output) echo __('number of messages found', WPS_TEXT_DOMAIN).': '.$num_msg.'<br /><br />';
		
				$carimap = array("=C3=A9", "=C3=A8", "=C3=AA", "=C3=AB", "=C3=A7", "=C3=A0", "=20", "=C3=80", "=C3=89", "\n", "> ");
				$carhtml = array("é", "è", "ê", "ë", "ç", "à", "&nbsp;", "À", "É", "<br>", "");
				
				if ($num_msg > 0) {
					
					if ($output) {
						echo '<table class="widefat">';
						echo '<thead>';
						echo '<tr>';
						echo '<th style="font-size:1.2em">'.__('From', WPS_TEXT_DOMAIN).'</th>';
						echo '<th style="font-size:1.2em">'.__('Date', WPS_TEXT_DOMAIN).'</th>';
						echo '<th style="font-size:1.2em">'.__('Topic ID', WPS_TEXT_DOMAIN).'</th>';
						echo '<th style="font-size:1.2em" width="50%">'.__('Snippet', WPS_TEXT_DOMAIN).'</th>';
						echo '</tr>';
						echo '</thead>';
						echo '<tbody>';
					}

					for ($i = 1; $i <= $num_msg; ++$i) {

						// Get email info
						$header = imap_header($mbox, $i);
		        		$prettydate = date("jS F Y H:i:s", $header->udate);
		        		$email = $header->from[0]->mailbox.'@'.$header->from[0]->host;
						$subject = $header->subject;
						
						// check email address is a registered email address
						$sql = "SELECT ID FROM ".$wpdb->base_prefix."users WHERE user_email = %s";
						$emailcheck = $wpdb->get_var($wpdb->prepare($sql, $email));
						
						if ($emailcheck) {						
		
							// Note user ID and get display_name
							$uid = $emailcheck;
							$sql = "SELECT display_name FROM ".$wpdb->base_prefix."users WHERE ID = %s";
							$display_name = $wpdb->get_var($wpdb->prepare($sql, $uid));
						
							$x = strpos($subject, '#TID=');
							if ($x !== FALSE) {
								
								// Get TID and continue
								$tid = substr($subject, $x+5, 1000);
								$x = strpos($tid, ' ');
								$tid = substr($tid, 0, $x);
								
								$sql = "SELECT tid FROM ".$wpdb->prefix."symposium_topics WHERE tid = %d";
								$tidcheck = $wpdb->get_var($wpdb->prepare($sql, $tid));
								
								if ($tidcheck) {
									
									// Get message to add as a reply					
									$body = imap_fetchbody($mbox, $i, "1.1");
									if ($body == "") {
									    $body = imap_fetchbody($mbox, $i, "1");
									}
									$body = quoted_printable_decode($body);
									$body = imap_utf8($body);
					  				$body = str_replace($carimap, $carhtml, $body);
					
									$divider = get_option(WPS_OPTIONS_PREFIX.'_mailinglist_divider');
									$divider_bottom = get_option(WPS_OPTIONS_PREFIX.'_mailinglist_divider_bottom');
									$x = strpos($body, $divider);
									$y = strpos($body, $divider_bottom);
									
									if ($x && $y) {
					
										$body = substr($body, $x+strlen($divider), strlen($body)-$x-strlen($divider)-1);
										$x = strpos($body, $divider_bottom);
										$body = trim(quoted_printable_decode(substr($body, 0, $x)));
										if (substr($body, 0, 4) == '<br>') { $body = substr($body, 4, strlen($body)-4); }
										
										// Replace <script> tags
										if (strpos($body, '<') !== FALSE) { str_replace('<', '&lt;', $body); }
										if (strpos($body, '>') !== FALSE) { str_replace('>', '&gt;', $body); }
										
										$snippet = trim(substr(quoted_printable_decode($body), 0, 100));
	
										// get category for topic
										$sql = "SELECT topic_category from ".$wpdb->prefix."symposium_topics WHERE tid = %d";
										$cid = $wpdb->get_var($wpdb->prepare($sql, $tid));
										
										// insert as a new reply
										if ($process) {

											if ($wps_forum->add_reply($tid, $body, $uid, true)) {
	
												$snippet .= '<span style="color:green">'.__('Added to forum.', WPS_TEXT_DOMAIN).'</span>';

						        				// Delete from mailbox
												imap_delete($mbox, $i);
	
											} else {
												
												$snippet = '<span style="color:red">'.__('Failed to add to forum', WPS_TEXT_DOMAIN).' '.$tid.'</span>';
												$snippet .= '<br>'.$subject;
												
											}		
											
										} else {
											$snippet ='<span style="color:green">'.__('Not added, just checking.', WPS_TEXT_DOMAIN).'</span>';
											$snippet .= '<br>'.$subject;
										}
										
														
									} else {
										
										$snippet = '<span style="color:red">'.__('Empty reply. No boundaries found', WPS_TEXT_DOMAIN).'</span>';
										
									}
									
									
								} else {
		
									$tid = '<span style="color:red">'.__('Topic ID not found', WPS_TEXT_DOMAIN).': '.$tid.'</span>';
									$snippet = $subject;
									
								}
								
							} else {
								
								$tid = '<span style="color:red">'.__('No TID found in subject', WPS_TEXT_DOMAIN).'.</span>';
								$snippet = '';
								
							}
							
							
						} else {
							
							$email = '<span style="color:red">'.$email.' '.__('not found in users', WPS_TEXT_DOMAIN).'.</span>';
							$tid = '';
							$snippet = '';
							
						}
		
						if ($output) {
							echo '<tr>';
							echo '<td>'.$email.'</td>';
							echo '<td>'.$prettydate.'</td>';
							echo '<td>'.$tid.'</td>';
							echo '<td>'.$snippet.'</td>';
							echo '</tr>';
						}
		
					}
					    		
					if ($output) echo '</tbody></table>';
											
				} else {
					
					if ($output) echo __('No messages found', WPS_TEXT_DOMAIN).'.';
					
				}

				// purge deleted mail
				imap_expunge($mbox);
				// close the mailbox
				imap_close($mbox); 
				
			} else {
			
				if ($output) echo __('Problem connecting to mail server', WPS_TEXT_DOMAIN).': ' . imap_last_error().' '.__('(or no internet connection)', WPS_TEXT_DOMAIN).'.<br />';		
				if ($output) echo __('Check your mail server address and port number, username and password', WPS_TEXT_DOMAIN).'.';
				
			}
			
			$_SESSION['__wps__mailinglist_lock'] = '';
			
		} else {
			if ($output) echo __('Currently processing, please try again in a few minutes.', WPS_TEXT_DOMAIN).'.<br />';		
		}
	}
}
	

// ----------------------------------------------------------------------------------------------------------------------------------------------------------


// Add "Alerts" to admin menu via hook
function __wps__add_mailinglist_to_admin_menu()
{
	$hidden = get_option(WPS_OPTIONS_PREFIX.'_long_menu') == "on" ? '_hidden': '';
	add_submenu_page('symposium_debug'.$hidden, __('Reply by Email', WPS_TEXT_DOMAIN), __('Reply by Email', WPS_TEXT_DOMAIN), 'manage_options', WPS_DIR.'/mailinglist_admin.php');
}
add_action('__wps__admin_menu_hook', '__wps__add_mailinglist_to_admin_menu');


?>