<?php
/**
 * Template Name: Demo forum page 1
 * Description: A Forum Page Template to demonstrate using classes
 *
 * @package WordPress
 * @subpackage Twenty_Eleven
 * @since Twenty Eleven 1.0
 */

get_header(); 


// include the PHP class files, the path should match your server!
require_once(WPS_PLUGIN_DIR.'/class.wps.php');
require_once(WPS_PLUGIN_DIR.'/class.wps_user.php');
require_once(WPS_PLUGIN_DIR.'/class.wps_forum.php');

$wps = new wps(); 
$wps_forum = new wps_forum(); // Defaults to top level, can pass a category ID to set root level

/*
First we over-ride settings for forum page to ensure links to this page across go to
the correct page. Note that you will need to visit/reload this page
the first time the script is run, as various constants are set prior to this page template
loading. If you visit Admin->Installation the default values will be reset, 
which includes after upgrading WPS, so re-visit this page at least once after visiting 
the Installation page, to put things back to the new page. Alternatively, create a 
page that updates this (and maybe other) URLs that you can visit as admin once after upgrading WPS.

This is hardcoded to a particular page for now. If distributing to other user's this will
need to be dynamically set! Change it to make the URL of your new forum page, mine is as
per the tutorial (ie. a page called "AA Forum").
*/
$wps->set_forum_url('/aa-forum');
?>

<!--
Links to styles used in this page template - shouldn't be included in the page template really,
but is included here to keep things simple for the tutorial at www.wpsymposium.com/blog.
Should be included in the theme header.php in the <HEAD> ... </HEAD> tags.
This also assumes the .css file is also in the current theme folder along with this page file. 
-->
<link rel="stylesheet" type="text/css" href="<?php bloginfo('template_url'); ?>/wps_forum_page.css" />

<div id="primary">
	<div id="content" role="main">
	
	<!-- WordPress page content components -->
	<?php the_post(); ?>
	<?php get_template_part( 'content', 'page' ); ?>
	<!-- End WordPress page content components -->


	<?php	
	echo '<div id="my-forum-table">';
		echo '<div class="my-forum-row" style="background-color:#ccc">';
			echo '<div class="my-forum-title">';
				echo '<strong>CATEGORY</strong>';
			echo '</div>';
			echo '<div class="my-forum-title-topic" style="float:right">';
				echo '<strong>LAST TOPIC</strong>';
			echo '</div>';
		echo '</div>';
		$categories = $wps_forum->get_categories();
		foreach ($categories as $category) {
			echo '<div class="my-forum-row">';
				echo '<div class="my-forum-row-title">';
					echo stripslashes($category->title);
				echo '</div>';
				echo '<div class="my-forum-row-last-topic">';
					$last_topic = $wps_forum->get_topics($category->cid, 0, 1);
					if ($last_topic) {
						echo '<div class="my-forum-row-last-topic-avatar">';
							$wps_user = new wps_user($last_topic->topic_owner);
							echo '<a href="'.$wps->get_profile_url().'?uid='.$last_topic->topic_owner.'">';
							echo $wps_user->get_avatar(48);
							echo '</a>';
						echo '</div>';
						echo stripslashes($last_topic->topic_subject).'<br />';
						echo '<span class="my-forum-row-last-topic-owner">'.$last_topic->display_name.',</span> ';
						echo '<span class="my-forum-row-last-topic-started">'.__wps__time_ago($last_topic->topic_started).'</span>';
					}
				echo '</div>';
			echo '</div>';
		}
	echo '</div>';

	?>
				
	</div><!-- #content -->
</div><!-- #primary -->
<?php get_footer(); ?>
