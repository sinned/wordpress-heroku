<?php
/**
 * Template Name: Demo profile page
 * Description: A Profile Page Template to demonstrate using classes
 *
 * @package WordPress
 * @subpackage Twenty_Eleven
 * @since Twenty Eleven 1.0
 */

get_header(); 

// include the PHP class files, the path should match your server!
require_once(ABSPATH.'wp-content/plugins/wp-symposium/class.wps_user.php');
require_once(ABSPATH.'wp-content/plugins/wp-symposium/class.wps_ui.php');

$wps_user = new wps_user();
?>

		<div id="primary">
			<div id="content" role="main">

<?php

			echo $wps_user->get_avatar().'<br />';
			echo $wps_user->get_display_name().'<br />';
			echo 'User login: '.$wps_user->get_user_login().'<br />';
			echo 'Email: '.$wps_user->get_user_email().'<br />';
			echo 'City: '.$wps_user->get_city().'<br />';
			echo 'Country: '.$wps_user->get_country();

?>

			</div><!-- #content -->
		</div><!-- #primary -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
