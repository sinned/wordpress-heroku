<?php
/**
 * Template Name: Demo mail page 1
 * Description: A Mail Page Template to demonstrate using classes
 *
 * @package WordPress
 * @subpackage Twenty_Eleven
 * @since Twenty Eleven 1.0
 */

get_header(); 

// include the PHP class files, the path should match your server!
require_once(ABSPATH.'wp-content/plugins/wp-symposium/class.wps.php');
require_once(ABSPATH.'wp-content/plugins/wp-symposium/class.wps_mail.php');

$wps = new wps();
$wps_mail = new wps_mail(); // defaults to current logged in user

/*
First we over-ride settings for mail page to ensure links to this page across go to
the correct page. Note that you will need to visit/reload this page
the first time the script is run, as various constants are set prior to this page template
loading. If you visit Admin->Installation the default values will be reset, 
which includes after upgrading, so re-visit this page at least once after visiting 
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

		$inbox = $wps_mail->get_inbox(); // defaults to 10 messages from first available
		if ($inbox) {
			foreach($inbox as $mail) {
				echo 'From: '.$mail['display_name'].'<br />';
				echo 'Subject: '.$mail['mail_subject'].'<br />';
				echo 'Date: '.$mail['mail_sent'].'<br />';
				echo 'Message: '.$mail['mail_message'].'<br /><br />';
			}
		} else {
			echo "You have no mail in your inbox.";
		}
		
		?>
			
	</div><!-- #content -->
</div><!-- #primary -->
<?php get_footer(); ?>
