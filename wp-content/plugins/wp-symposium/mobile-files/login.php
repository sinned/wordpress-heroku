<?php
include_once('../wp-config.php');
include_once(dirname(__FILE__).'/mobile_check.php');

global $wpdb, $current_user, $wp_error;

// Re-act to GET/POST information *****************************************************************

if ( is_user_logged_in() ) {

	wp_logout();
	wp_redirect('index.php?a=1');
	
} else {

	if (isset($_POST['username']) && $_POST['username'] != '') {
		$username = $_POST['username'];
		$password = $_POST['password'];

		$creds = array();
		$creds['user_login'] = $username;
		$creds['user_password'] = $password;
		$creds['remember'] = true;
		$user = wp_signon( $creds, false );
		
		if ( is_wp_error($user) ) {
			echo "<div class='line'>Login failed, please try again.</div>";
		} else {
			
			wp_set_current_user($user->ID, $username);
			wp_redirect('index.php?a=1');
		}
	}

}

// End of POSTed information **********************************************************************	

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
                
	echo '<div class="form">';
	echo '<form action="" method="POST">';
	echo 'Username<br /><br />';
	echo '<input type="text" class="input_text" name="username" /><div style="clear:both"></div><br />';
	echo 'Password<br /><br />';
	echo '<input type="password" class="input_text" name="password" /><div style="clear:both"></div><br />';
	echo '<input type="submit" class="submit small green" value="Login" />';
	echo '</form></div>';

} else {

	include_once('./header_loggedin.php');

}

include_once(dirname(__FILE__).'/footer.php');	
?>
</body>
</html>
