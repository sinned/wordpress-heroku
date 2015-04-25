<?php
/**
 * Template Name: Demo profile page 3
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

			echo '<div style="float: left; width:590px;">';
			
			   echo '<div style="float:right; text-align:left;">';
			
			      if ($wps_user->get_city()) echo 'Lives in: '.$wps_user->get_city().'<br />';
			      if ($wps_user->get_country()) echo 'Country: '.$wps_user->get_country().'<br />';
			
			   echo '</div>';
			
			   echo '<div style="float:left;padding-right:20px;">'.$wps_user->get_avatar(120).'</div>';
			
			   echo '<span style="font-size:32px">'.$wps_user->get_display_name().'</span>';
			
			echo '</div>';

?>

			</div><!-- #content -->
		</div><!-- #primary -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>
