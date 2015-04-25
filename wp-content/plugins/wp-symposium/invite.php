<?php

/* This page will be visited by a redirect sent via email. */

include_once('../../../wp-config.php');
	
// If already logged in, then go straight to URL redirect
if (is_user_logged_in() && isset($_GET['u'])) {
	wp_redirect($_GET['u']);
} else {
	// Not logged in, so display login form and option to register if not a member
	?>

	<style>*{margin:0;padding:0;}html{background:#fbfbfb!important;}body{padding-top:30px;font-family:sans-serif;font-size:12px;}form{margin-left:8px;padding:26px 24px 46px;font-weight:normal;-moz-border-radius:3px;-khtml-border-radius:3px;-webkit-border-radius:3px;border-radius:3px;background:#fff;border:1px solid #e5e5e5;-moz-box-shadow:rgba(200,200,200,0.7) 0 4px 10px -1px;-webkit-box-shadow:rgba(200,200,200,0.7) 0 4px 10px -1px;-khtml-box-shadow:rgba(200,200,200,0.7) 0 4px 10px -1px;box-shadow:rgba(200,200,200,0.7) 0 4px 10px -1px;}form .forgetmenot{font-weight:normal;float:left;margin-bottom:0;}.button-primary{font-family:sans-serif;padding:3px 10px;border:none;font-size:13px;border-width:1px;border-style:solid;-moz-border-radius:11px;-khtml-border-radius:11px;-webkit-border-radius:11px;border-radius:11px;cursor:pointer;text-decoration:none;margin-top:-3px;}#login form p{margin-bottom:0;}label{color:#777;font-size:14px;}form .forgetmenot label{font-size:12px;line-height:19px;}form .submit,.alignright{float:right;}form p{margin-bottom:24px;}h1 a{background:url(../images/logo-login.png) no-repeat top center;width:326px;height:67px;text-indent:-9999px;overflow:hidden;padding-bottom:15px;display:block;}#login{width:320px;margin:7em auto;}#login_error,.message{margin:0 0 16px 8px;border-width:1px;border-style:solid;padding:12px;-moz-border-radius:3px;-khtml-border-radius:3px;-webkit-border-radius:3px;border-radius:3px;}#nav,#backtoblog{text-shadow:rgba(255,255,255,1) 0 1px 0;margin:0 0 0 16px;padding:16px 16px 0;}#backtoblog{padding:12px 16px 0;}body form .input{font-family:"HelveticaNeue-Light","Helvetica Neue Light","Helvetica Neue",sans-serif;font-weight:200;font-size:24px;width:97%;padding:3px;margin-top:2px;margin-right:6px;margin-bottom:16px;border:1px solid #e5e5e5;background:#fbfbfb;outline:none;-moz-box-shadow:inset 1px 1px 2px rgba(200,200,200,0.2);-webkit-box-shadow:inset 1px 1px 2px rgba(200,200,200,0.2);box-shadow:inset 1px 1px 2px rgba(200,200,200,0.2);}input{color:#555;}.clear{clear:both;}#pass-strength-result{font-weight:bold;border-style:solid;border-width:1px;margin:12px 0 6px;padding:6px 5px;text-align:center;}
	</style>
		
	<div class="login" style="width:400px; margin: 80px auto;">
	
		<form name="loginform" id="loginform" action="<?php echo site_url('wp-login.php', 'login_post') ?>" method="post">
			<p><?php _e('To continue, please log in or register.', WPS_TEXT_DOMAIN); ?></p>
			<p> 
				<label><?php _e('Username', WPS_TEXT_DOMAIN) ?><br /> 
				<input type="text" name="log" id="user_login" class="input" value="" size="20" tabindex="10" /></label> 
			</p> 
			<p> 
				<label><?php _e('Username', WPS_TEXT_DOMAIN) ?><br /> 
				<input type="password" name="pwd" id="user_pass" class="input" value="" size="20" tabindex="20" /></label> 
			</p> 
			<p class="forgetmenot"><label><input name="rememberme" type="checkbox" id="rememberme" value="forever" tabindex="90" /> <?php esc_attr_e('Remember Me', WPS_TEXT_DOMAIN); ?></label></p> 
			<p class="submit"> 
				<input type="submit" name="wp-submit" id="wp-submit" class="button-primary" value="<?php esc_attr_e('Log In', WPS_TEXT_DOMAIN); ?>" tabindex="100" />
				<input type="hidden" name="redirect_to" value="<?php echo $_GET['u']; ?>" /> 
				<input type="hidden" name="testcookie" value="1" /> 
			</p> 
		</form> 

		<p id="nav">
		<?php if ( isset($_GET['checkemail']) && in_array( $_GET['checkemail'], array('confirm', 'newpass') ) ) : ?>
		<?php elseif ( get_option('users_can_register') ) : ?>
		<a href="<?php echo site_url('wp-login.php?action=register', 'login') ?>"><?php _e('Register', WPS_TEXT_DOMAIN) ?></a> |
		<a href="<?php echo site_url('wp-login.php?action=lostpassword', 'login') ?>" title="<?php _e('Password Lost and Found', WPS_TEXT_DOMAIN) ?>"><?php _e('Lost your password?', WPS_TEXT_DOMAIN) ?></a>
		<?php else : ?>
		<a href="<?php echo site_url('wp-login.php?action=lostpassword', 'login') ?>" title="<?php _e('Password Lost and Found', WPS_TEXT_DOMAIN) ?>"><?php _e('Lost your password?', WPS_TEXT_DOMAIN) ?></a>
		<?php endif; ?>
		</p>
	
	</div>
	
	<?php
}

	
?>
