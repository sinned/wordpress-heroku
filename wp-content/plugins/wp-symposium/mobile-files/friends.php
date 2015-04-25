<?php
include_once('../wp-config.php');
include_once(dirname(__FILE__).'/mobile_check.php');

global $wpdb, $current_user;

require_once(ABSPATH.'wp-content/plugins/wp-symposium/class.wps.php');
require_once(ABSPATH.'wp-content/plugins/wp-symposium/class.wps_ui.php');
require_once(ABSPATH.'wp-content/plugins/wp-symposium/class.wps_user.php');

$wps = new wps();
$wps_user = new wps_user($wps->get_current_user_page()); // default to current user, or pass a user ID

// Redirect if not on a mobile
if (!$mobile) {
	header('Location: ./..');
}

?>
<!DOCTYPE html>
<html>
<head>
<title><?php echo get_bloginfo('name');?></title>
<meta charset="UTF-8" />
<link rel="stylesheet" type="text/css" href="style.css" />
<?php if ($big_display) { ?>
	<link rel="stylesheet" type="text/css" href="bigdisplay.css" />
<?php } ?>

</head>
<body>


<?php

if ( !is_user_logged_in() ) {

	include_once('./header_loggedout.php');

	echo '<br /><br />';
	echo '<input type="submit" onclick="location.href=\'login.php?'.$a.'\'" class="submit small blue fullwidth" value="'.__('Login', WPS_TEXT_DOMAIN).'" />';
	echo '<br /><br />';
	
} else {

	include_once('./header_loggedin.php');
	show_header('[home,forum,topics,replies]');

	$friends = $wps_user->get_friends(100);
	if ($friends) {
		foreach ($friends as $friend) {
			$f = new wps_user($friend['id']);
			echo '<div class="__wps__friends_div">';
				echo '<div class="__wps__friends_avatar">'.$f->get_avatar(128, false).'</div>';
				echo '<div class="friends_info">';
					echo '<a href="index.php?'.$a.'&uid='.$friend['id'].'">'.stripslashes($f->display_name).'</a><br />';
					echo __('Last active', WPS_TEXT_DOMAIN).' '.__wps__time_ago($friend['last_activity']).'<br />';
					$post = $f->get_latest_activity();
					$post = str_replace(__('Started a new forum topic:', WPS_TEXT_DOMAIN), __('Started ', WPS_TEXT_DOMAIN), $post);
					echo $post;
				echo '</div>';
			echo '</div>';
		}
	} else {
		echo __('No friends yet :(', WPS_TEXT_DOMAIN);
	}
	
}

include_once(dirname(__FILE__).'/footer.php');	

?>
</body>
</html>
