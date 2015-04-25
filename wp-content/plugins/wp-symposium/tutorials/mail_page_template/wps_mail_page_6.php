<?php

/**
 * Template Name: Demo mail page 6
 * Description: A Mail Page Template to demonstrate using classes
 *
 * @package WordPress
 * @subpackage Twenty_Eleven
 * @since Twenty Eleven 1.0
 */

get_header(); 

/*
The following is shown here now for reference, but should be placed in a separate file
*/

?>

<script>

jQuery(document).ready(function() { 	

	// Show/hide compose form

	jQuery("#my-show-compose-form").click(function(){

		// First clear any values using classes we have set (from a previous reply, just in case)

		jQuery(".my-compose-form-subject").val('Subject of mail');		

		jQuery(".my-compose-form-message").html('Message...');		

		// Then show the form

		jQuery("#my-compose-form").show();

		jQuery("#my-inbox").hide();

		jQuery("#my-inbox-pages").hide();

		jQuery("#my-hide-compose-form").show();

		jQuery("#my-show-compose-form").hide();

		jQuery("#my-boxes-choice").hide();		

	});

	jQuery("#my-hide-compose-form").click(function(){

		jQuery("#my-compose-form").hide();

		jQuery("#my-inbox").show();

		jQuery("#my-inbox-pages").show();

		jQuery("#my-hide-compose-form").hide();

		jQuery("#my-show-compose-form").show();

		jQuery("#my-boxes-choice").show();		

	});

});

</script>

<?php

// include the PHP class files, the path should match your server!
require_once(ABSPATH.'wp-content/plugins/wp-symposium/class.wps.php');
require_once(ABSPATH.'wp-content/plugins/wp-symposium/class.wps_user.php');
require_once(ABSPATH.'wp-content/plugins/wp-symposium/class.wps_mail.php');
require_once(ABSPATH.'wp-content/plugins/wp-symposium/class.wps_ui.php');

$wps = new wps();
$wps_mail = new wps_mail(); // defaults to current logged in user
$wps_ui = new wps_ui();

/*
First we over-ride settings for mail page to ensure links to this page across go to
the correct page. Note that you will need to visit/reload this page
the first time the script is run, as various constants are set prior to this page template
loading. If you visit Admin->Installation the default values will be reset, 
which includes after upgrading WPS, so re-visit this page at least once after visiting 
the Installation page, to put things back to the new page. Alternatively, create a 
page that updates this (and maybe other) URLs that you can visit as admin once after upgrading WPS.

This is hardcoded to a particular page for now. If distributing to other user's this will
need to be dynamically set! Change it to make the URL of your new mail page, mine is as
per the tutorial (ie. a page called "AA Mail").

*/

$wps->set_mail_url('/aa-mail');

?>

<!--
Links to styles used in this page template - shouldn't be included in the page template really,
but is included here to keep things simple for the tutorial at www.wpsymposium.com/blog.
Should be included in the theme header.php in the <HEAD> ... </HEAD> tags.
This also assumes the .css file is also in the current theme folder along with this page file. 
-->

<link rel="stylesheet" type="text/css" href="<?php bloginfo('template_url'); ?>/wps_mail_page.css" />

<div id="primary">

	<div id="content" role="main">

	<?php

	// Set up defaults
	$mail_id = isset($_GET['mail_id']) || isset($_GET['mid']) ? $_GET['mail_id'].$_GET['mid'] : false;

	// action set, default to value of action
	$action = isset($_GET['action']) || isset($_POST['action']) ? isset($_POST['action']) ? $_POST['action'] : $_GET['action'] : 'inbox';

	// no action set, but mail id passed, clear action (ie. read message)
	$action = (!isset($_GET['action']) && !isset($_POST['action'])) && ($mail_id) ? '' : $action;

	// Action other than show in box?

	if ( $action != 'inbox' ) {
		
		// Check to see if action is to send a mail
		// Should be in a separate file that then comes back here to avoid resending if the page is refreshed!
		// But here for the sake of demonstrating and keeping it all in one file for the tutorial

		if ($action == 'sendmail') {

			if ( !$wps_mail->sendmail($_POST['my-recipient']) ) {

				echo '<p>Problem sending mail, sorry.</p>';

			}

			$action = 'inbox';

		}

		// Check to see if action is to delete a mail

		if ($action == 'delete') {

			if ( !$wps_mail->set_as_deleted($_GET['id']) ) {

				echo '<p>Problem deleting mail, sorry.</p>';

			}

			$action = 'inbox';

		}

	}

	if ( ($action == 'reply' && $mail_id) || ($action == 'inbox' || $action == 'sent') ) { 

		// If replying, get the original mail for referene

		$default_recipient = '';

		if ($action == 'reply') {

			$original = $wps_mail->get_message($_GET['mail_id']);

			if ($original) {

				$default_recipient = $original->mail_from;

				$original_subject = $original->mail_subject;

				$original_subject = (substr($original_subject, 0, 3) != 'Re:' ? 'Re: ' : '') . $original_subject;

				// Get user info for the sender

				$sender = new wps_user($original->mail_from);

				$dt=explode(' ',$original->mail_sent);

				$d=explode('-',$dt[0]);

				$t=explode(':',$dt[1]);

				$header = chr(13).chr(13).chr(13).'-------------------------------------------'.chr(13);

				$header .= 'From: '.$sender->display_name.chr(13);

				$header .= 'Date: '.$d[2].' '.substr(__wps__get_monthname($d[1]),0,3).' '.$d[0].', '.$t[0].':'.$t[1].chr(13);

				$header .= 'Subject: '.$original->mail_subject.chr(13).chr(13);

				$original_message = $header.str_replace('<br />', '', $original->mail_message);

			}

		}

		// Compose new mail form

		if ($action != 'reply') {

			echo '<a href="javascript:void(0)" id="my-show-compose-form">Compose new mail</a>';

			echo '<a href="javascript:void(0)" id="my-hide-compose-form" style="display:none;">Back to inbox...</a>';

		} else {

			echo '<a href="javascript:void(0)" id="my-show-compose-form" style="display:none;">Compose new mail</a>';

			echo '<a href="javascript:void(0)" id="my-hide-compose-form">Back to inbox...</a>';

		}

		// Show boxes

		$override_hidden_state = ($action != 'inbox' && $action != 'sent') ? "display:none;" : '';

		echo '<div id="my-boxes-choice" style="'.$override_hidden_state.'">';

			if ($action != 'sent') {

				echo '<strong>In Box</strong> | ';

				echo '<a href="'.$wps->get_mail_url().'?action=sent&start=0">Sent</a>';

			} else {

				echo '<a href="'.$wps->get_mail_url().'?action=inbox&start=0">In Box</a> | ';

				echo '<strong>Sent</strong>';

			}

		echo '</div>';

		// Compose form

		echo '<form action="" method="POST">';

		echo '<input type="hidden" name="action" value="sendmail" />';

		$override_hidden_state = $action == 'reply' ? 'style="display:block;"' : '';

		echo '<div '.$override_hidden_state.' id="my-compose-form">';

			echo '<div id="my-compose-form-to">';

				echo '<div style="float:left">';

					echo "To: ";

				echo '</div>';

				// Get list of friends IDs (have to be a friend to be able to send a mail)

				$wps_user = new wps_user();
				$friends = $wps_user->get_friends();

				// Build an array, so we can sort by display name

				$friends_array = array();

				foreach ($friends as $friend) {				

					$friend_user = new $wps_user($friend['id']);

					// Add row with id and friend's name

					$friends_array[] = array("id"=>$friend_user->id, "display_name"=>$friend_user->get_display_name());

				}

				// Add current user so can send yourself a mail (as you're not in your own friends list)

				$friends_array[] = array("id"=>$current_user->ID, "display_name"=>$current_user->display_name);

				// Now sort the array by display name

				$friends_array=__wps__sub_val_sort($friends_array, 'display_name');

				// And show the drop down list of friends (you could choose a different selection method if you prefer)

				echo '<select name="my-recipient">';

				foreach ($friends_array as $i => $row) {				

					$selected = $default_recipient == $row['id'] ? 'SELECTED' : '';

					echo '<option '.$selected.' value='.$row['id'].'>'.$row['display_name'].'</option>';

				}

				echo '</select>';

			echo '</div>';

			$subject = ($action == 'reply' && isset($original)) ? $original_subject : "Subject of mail";

			$message = ($action == 'reply' && isset($original)) ? $original_message : "Message...";

			echo $wps_ui->mail_subject($subject, "my-compose-form-subject").'<br />';

			echo $wps_ui->mail_message($message, "my-compose-form-message").'<br />';

			echo $wps_ui->mail_send_button("Send", "my-submit-button");

		echo '</div>';

		echo '</form>';

		// Inbox or Sent

		$override_show_state = $action == 'reply' ? 'style="display:none;"' : '';

		echo '<div '.$override_show_state.' id="my-inbox">';

			echo '<div id="my-inbox-header">';

				$from_or_to = $action == 'inbox' ? 'From' : 'To';

				$sent_or_received = $action == 'inbox' ? 'Received' : 'Sent';

				echo '<div class="my-inbox-from">'.$from_or_to.'</div>';

				echo '<div class="my-inbox-message">Subject</div>';

				echo '<div class="my-inbox-sent">'.$sent_or_received.'</div>';

			echo '</div>';


			$start = isset($_GET['start']) ? $_GET['start'] : 0;

			$page_length = 10;

			// Get mailbox items

			$show_sent_mail = ($action == "sent");

			$inbox = $wps_mail->get_inbox($page_length, $start, 40, "", true, 75, false, $show_sent_mail);

			if ($inbox) {

				foreach($inbox as $mail) {

					$mail_unread = $mail['mail_read'] == 'on' ? '' : 'my-inbox-unread';

					echo '<div class="my-inbox-row '.$mail_unread.'">';

						echo '<div class="my-inbox-from">';

							echo '<div class="my-inbox-avatar">';

								echo $mail['avatar'];

							echo '</div>';

							echo $mail['display_name_link'];

						echo '</div>';

						echo '<div class="my-inbox-message">';

							echo '<div class="my-inbox-subject">';

								echo '<a href="'.$wps->get_mail_url().'?mail_id='.$mail['mail_id'].'">'.$mail['mail_subject'].'</a>';

							echo '</div>';

							echo $mail['mail_message'];

						echo '</div>';

						$dt=explode(' ',$mail['mail_sent']);

						$d=explode('-',$dt[0]);

						$t=explode(':',$dt[1]);

						echo '<div class="my-inbox-sent">';

							echo $d[2].' '.substr(__wps__get_monthname($d[1]),0,3).' '.$d[0].', '.$t[0].':'.$t[1].'<br />';

							// Reply link

							echo '<a href="'.$wps->get_mail_url().'?action=reply&mail_id='.$mail['mail_id'].'">Reply</a> ';

							// Delete icon

							echo '<a href="'.$wps->get_mail_url().'?start='.$start.'&action=delete&id='.$mail['mail_id'].'"><img src="'.get_option(WPS_OPTIONS_PREFIX.'_images').'/delete.png" /></a>';

						echo '</div>';

					echo '</div>';

				}

			} else {

				echo '<div class="my-inbox-row">';

					echo "No mail to show....";

				echo '</div>';

			}

		echo '</div>';

		// Show "pages" of messages (if more than 1)

		$inbox_count = $wps_mail->get_inbox_count();

		$pages = ($inbox_count % $page_length) == 0 ? $inbox_count / $page_length : floor($inbox_count / $page_length) + 1;

		echo '<div '.$override_show_state.' id="my-inbox-pages">';

		if ($pages > 1) {

			$current_page = floor($start/$page_length)+1;

			$paging_action = ($action == 'inbox' || $action == 'sent') ? $action : 'inbox';

			if ($current_page > 1) echo '<a href="'.$wps->get_mail_url().'?action='.$paging_action.'&start=0">First</a> ';

			if ($current_page > 1) echo '<a href="'.$wps->get_mail_url().'?action='.$paging_action.'&start='.((($current_page-1)*$page_length)-$page_length).'">Previous</a> ';

			for ($p=1; $p<=$pages; $p++) {

				if ($p==$current_page) {

					echo ' <strong>'.$p.'</strong>';

				} else {

					echo ' <a href="'.$wps->get_mail_url().'?action='.$paging_action.'&start='.(($p*$page_length)-$page_length).'">'.$p.'</a>';

				}

			}

			if ($current_page < $pages) echo ' <a href="'.$wps->get_mail_url().'?action='.$paging_action.'&start='.((($current_page+1)*$page_length)-$page_length).'">Next</a>';

			if ($current_page < $pages) echo ' <a href="'.$wps->get_mail_url().'?action='.$paging_action.'&start='.(($pages*$page_length)-$page_length).'">Last</a>';

		}	

		echo '</div>';	

	} else {

		// View message	

		$id = isset($_GET['mail_id']) ? $_GET['mail_id'] : $_GET['mid']; // To ensure compatibility

		$message = $wps_mail->get_message($id); // Ge message details

		if ($message) {

			$sender = new wps_user($message->mail_from);

			$dt=explode(' ',$message->mail_sent);

			$d=explode('-',$dt[0]);

			$t=explode(':',$dt[1]);

			echo '<a href="'.$wps->get_mail_url().'">Back to inbox...</a>';

			echo '<div id="my-mail">';

				echo '<div id="my-mail-avatar">'.$sender->get_avatar(120).'</div>';

				echo '<div id="my-mail-from"><div class="my-mail-label"></span>From:</div> '.$sender->get_profile_url().'</div>';

				echo '<div id="my-mail-subject"><div class="my-mail-label">Subject:</div> '.stripslashes($message->mail_subject).'</div>';

				echo '<div id="my-mail-sent"><div class="my-mail-label">Received:</div> '.$d[2].' '.substr(__wps__get_monthname($d[1]),0,3).' '.$d[0].', '.$t[0].':'.$t[1].'</div>';

				$mail_message = __wps__make_url(stripslashes($message->mail_message));

				$mail_message = str_replace(chr(13), '<br />', $mail_message);

				echo '<div id="my-mail-message">'.$mail_message.'</div>';

			echo '</div>';

		} else {

			echo "Invalid Mail ID parameter, sorry.";

		}

		// Update mail read flag

		$wps_mail->set_as_read($message->mail_mid);

	}

	?>

	</div><!-- #content -->

</div><!-- #primary -->

<?php get_footer(); ?>

