<?php
/*
Plugin Name: CloudFlare
Plugin URI: http://www.cloudflare.com/wiki/CloudFlareWordPressPlugin
Description: CloudFlare integrates your blog with the CloudFlare platform.
Version: 1.3.14
Author: Ian Pye, Jerome Chen, James Greene, Simon Moore (CloudFlare Team)
License: GPLv2
*/

/*
This program is free software; you can redistribute it and/or modify 
it under the terms of the GNU General Public License as published by 
the Free Software Foundation; version 2 of the License.

This program is distributed in the hope that it will be useful, 
but WITHOUT ANY WARRANTY; without even the implied warranty of 
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the 
GNU General Public License for more details. 

You should have received a copy of the GNU General Public License 
along with this program; if not, write to the Free Software 
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA 

Plugin adapted from the Akismet WP plugin.

*/	

define('CLOUDFLARE_VERSION', '1.3.14');
define('CLOUDFLARE_API_URL', 'https://www.cloudflare.com/api_json.html'); 

require_once("ip_in_range.php");

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo "Hi there!  I'm just a plugin, not much I can do when called directly.";
	exit;
}

function cloudflare_init() {
    global $cf_api_host, $cf_api_port, $is_cf;

    $cf_api_host = "ssl://www.cloudflare.com";
    $cf_api_port = 443;
    
    $is_cf = (isset($_SERVER["HTTP_CF_CONNECTING_IP"]))? TRUE: FALSE;    

    if (strpos($_SERVER["REMOTE_ADDR"], ":") === FALSE) {
    
        $cf_ip_ranges = array("199.27.128.0/21","173.245.48.0/20","103.21.244.0/22","103.22.200.0/22","103.31.4.0/22","141.101.64.0/18","108.162.192.0/18","190.93.240.0/20","188.114.96.0/20","197.234.240.0/22","198.41.128.0/17","162.158.0.0/15","104.16.0.0/12");
    	// IPV4: Update the REMOTE_ADDR value if the current REMOTE_ADDR value is in the specified range.
        foreach ($cf_ip_ranges as $range) {
            if (ipv4_in_range($_SERVER["REMOTE_ADDR"], $range)) {
                if ($_SERVER["HTTP_CF_CONNECTING_IP"]) {
                    $_SERVER["REMOTE_ADDR"] = $_SERVER["HTTP_CF_CONNECTING_IP"];
                }
                break;
            }
        }        
    }
    else {
        $cf_ip_ranges = array("2400:cb00::/32","2606:4700::/32","2803:f800::/32","2405:b500::/32","2405:8100::/32");
        $ipv6 = get_ipv6_full($_SERVER["REMOTE_ADDR"]);
        foreach ($cf_ip_ranges as $range) {
            if (ipv6_in_range($ipv6, $range)) {
                if ($_SERVER["HTTP_CF_CONNECTING_IP"]) {
                    $_SERVER["REMOTE_ADDR"] = $_SERVER["HTTP_CF_CONNECTING_IP"];
                }
                break;
            }
        }
    }
        
    // Let people know that the CF WP plugin is turned on.
    if (!headers_sent()) {
        header("X-CF-Powered-By: WP " . CLOUDFLARE_VERSION);
    }
    add_action('admin_menu', 'cloudflare_config_page');
}
add_action('init', 'cloudflare_init',1);

function cloudflare_admin_init() {
    
}

add_action('admin_init', 'cloudflare_admin_init');

function cloudflare_config_page() {
	if ( function_exists('add_submenu_page') ) {
		add_submenu_page('plugins.php', __('CloudFlare Configuration'), __('CloudFlare'), 'manage_options', 'cloudflare', 'cloudflare_conf');
    }
}

function load_cloudflare_keys () {
    global $cloudflare_api_key, $cloudflare_api_email, $cloudflare_zone_name;
    $cloudflare_api_key = get_option('cloudflare_api_key');
    $cloudflare_api_email = get_option('cloudflare_api_email');
    $cloudflare_zone_name = get_option('cloudflare_zone_name');
}

function cloudflare_conf() {

    if ( function_exists('current_user_can') && !current_user_can('manage_options') )
        die(__('Cheatin&#8217; uh?'));
    global $cloudflare_zone_name, $cloudflare_api_key, $cloudflare_api_email, $is_cf;
    global $wpdb;
    
    load_cloudflare_keys();
    
    $messages = array(
        'ip_restore_on' => array('text' => __('Plugin Status: True visitor IP is being restored')),
        'comment_spam_on' => array('text' => __('Plugin Status: CloudFlare will be notified when you mark comments as spam')),
        'comment_spam_off' => array('text' => __('Plugin Status: CloudFlare will NOT be notified when you mark comments as spam, enter your API details below')),
        'curl_not_installed' => array('text' => __('Plugin Status: cURL is not installed/enabled on your server. You will not be able to toggle <a href="https://support.cloudflare.com/hc/en-us/articles/200168246-What-does-CloudFlare-Development-mode-mean-" target="_blank">development mode</a> via this plugin. If you wish to do this, please contact your server administrator or hosting provider for assistance with installing cURL.')),
        'dev_mode_on' => array('text' => __('Development mode is On. Happy blogging!')),
        'dev_mode_off' => array('text' => __('Development mode is Off. Happy blogging!')),
        'manual_entry' => array('text' => __('Enter your CloudFlare domain name, e-mail address and API key below')),
        'options_saved' => array('text' => __('Options Saved')),
    );
    
    $notices = array();
    $warnings = array();
    $errors = array();
    
    $notices[] = 'ip_restore_on';
    
    $curl_installed = function_exists('curl_init');
    if (!$curl_installed) $warnings[] = 'curl_not_installed';
    
    // get raw domain - may include www.
    $urlparts = parse_url(site_url());
    $raw_domain = $urlparts["host"];
    
    // if we don't have a domain name already populated
    if (empty($cloudflare_zone_name)) {
    	
    	if ($curl_installed && !empty($cloudflare_api_key) && !empty($cloudflare_api_email)) {
			// Attempt to get the matching host from CF
			$getDomain = get_domain($cloudflare_api_key, $cloudflare_api_email, $raw_domain);
	
			// If not found, default to pulling the domain via client side.
			if (is_wp_error($getDomain)) {
				$messages['get_domain_failed'] = array('text' => __('Unable to automatically get domain - ' . $getDomain->get_error_message() . ' - please tell us your domain in the form below'));
				$warnings[] = 'get_domain_failed';
			}
			else {
				update_option('cloudflare_zone_name', esc_sql($getDomain));
				update_option('cloudflare_zone_name_set_once', "TRUE");
				load_cloudflare_keys();
			}
		}
    }

    $db_results = array();
               
	if ( isset($_POST['submit']) 
         && check_admin_referer('cloudflare-db-api','cloudflare-db-api-nonce') ) {
        
		if ( function_exists('current_user_can') && !current_user_can('manage_options') ) {
			die(__('Cheatin&#8217; uh?'));
        }
        
		$zone_name = $_POST['cloudflare_zone_name'];
		$key = $_POST['key'];
		$email = $_POST['email'];
        $dev_mode = esc_sql($_POST["dev_mode"]);
        
		if ( empty($zone_name) ) {
			$zone_status = 'empty';
			$zone_message = 'Your domain name has been cleared.';
			delete_option('cloudflare_zone_name');
		} else {
			$zone_message = 'Your domain name has been saved.';
			update_option('cloudflare_zone_name', esc_sql($zone_name));
            update_option('cloudflare_zone_name_set_once', "TRUE");
        }
        
		if ( empty($key) ) {
			$key_status = 'empty';
			$key_message = 'Your key has been cleared.';
			delete_option('cloudflare_api_key');
		} else {
			$key_message = 'Your key has been verified.';
			update_option('cloudflare_api_key', esc_sql($key));
            update_option('cloudflare_api_key_set_once', "TRUE");
        }

		if ( empty($email) || !is_email($email) ) {
			$email_status = 'empty';
			$email_message = 'Your email has been cleared.';
			delete_option('cloudflare_api_email');
		} else {
			$email_message = 'Your email has been verified.';
			update_option('cloudflare_api_email', esc_sql($email));
            update_option('cloudflare_api_email_set_once', "TRUE");
        }
        
        // update the values
        load_cloudflare_keys();

        if ($curl_installed) {
            if ($cloudflare_api_key != "" && $cloudflare_api_email != "" && $cloudflare_zone_name != "" && $dev_mode != "") {
                
                $result = set_dev_mode(esc_sql($cloudflare_api_key), esc_sql($cloudflare_api_email), $cloudflare_zone_name, $dev_mode);
                
                if (is_wp_error($result)) {
					error_log($result->get_error_message());
					$messages['set_dev_mode_failed'] = array('text' => __('Unable to set development mode - ' . $result->get_error_message() . ' - try logging into cloudflare.com to set development mode'));
            		$errors[] = 'set_dev_mode_failed';
				}
				else {
					if ($dev_mode && $result->result == 'success') {
                    	$notices[] = 'dev_mode_on';
                	}
                	else if (!$dev_mode && $result->result == 'success') {
                    	$notices[] = 'dev_mode_off';
                	}
				}
                
                
            }
        }
        
        $notices[] = 'options_saved';
    }
    
	if ($curl_installed) {
		if (!empty($cloudflare_api_key) && !empty($cloudflare_api_email) && !empty($cloudflare_zone_name)) {
			$dev_mode = get_dev_mode_status($cloudflare_api_key, $cloudflare_api_email, $cloudflare_zone_name);
		
			if (is_wp_error($dev_mode)) {
				$messages['get_dev_mode_failed'] = array('text' => __('Unable to get current development mode status - ' . $dev_mode->get_error_message()));
				$errors[] = 'get_dev_mode_failed';
			}
		}
		else {
			$warnings[] = 'manual_entry';
		}
	}
	
	if (!empty($cloudflare_api_key) && !empty($cloudflare_api_email)) $notices[] = 'comment_spam_on';
    else $warnings[] = 'comment_spam_off';
	
    ?>
    <div class="wrap">

    <?php if ($is_cf) { ?>
        <h3>You are currently using CloudFlare!</h3>
    <?php } ?>
    
    <?php if ($notices) { foreach ( $notices as $m ) { ?>
    <div class="updated" style="border-left-color: #7ad03a; padding: 10px;"><?php echo $messages[$m]['text']; ?></div>
    <?php } } ?>
    
    <?php if ($warnings) { foreach ( $warnings as $m ) { ?>
    <div class="updated" style="border-left-color: #ffba00; padding: 10px;"><em><?php echo $messages[$m]['text']; ?></em></div>
    <?php } } ?>
    
    <?php if ($errors) { foreach ( $errors as $m ) { ?>
    <div class="updated" style="border-left-color: #dd3d36; padding: 10px;"><b><?php echo $messages[$m]['text']; ?></b></div>
    <?php } } ?>

    <h4><?php _e('CLOUDFLARE WORDPRESS PLUGIN:'); ?></h4>

CloudFlare has developed a plugin for WordPress. By using the CloudFlare WordPress Plugin, you receive: 
<ol>
<li>Correct IP Address information for comments posted to your site</li>
<li>Better protection as spammers from your WordPress blog get reported to CloudFlare</li>
<li>If cURL is installed, you can enter your CloudFlare API details so you can toggle <a href="https://support.cloudflare.com/hc/en-us/articles/200168246-What-does-CloudFlare-Development-mode-mean-" target="_blank">Development mode</a> on/off using the form below</li>
</ol>

<h4>VERSION COMPATIBILITY:</h4>

The plugin is compatible with WordPress version 2.8.6 and later. The plugin will not install unless you have a compatible platform.

<h4>THINGS YOU NEED TO KNOW:</h4>

<ol>
<li>The main purpose of this plugin is to ensure you have no change to your originating IPs when using CloudFlare. Since CloudFlare acts a reverse proxy, connecting IPs now come from CloudFlare's range. This plugin will ensure you can continue to see the originating IP. Once you install the plugin, the IP benefit will be activated.</li>
 
<li>Every time you click the 'spam' button on your blog, this threat information is sent to CloudFlare to ensure you are constantly getting the best site protection.</li>

<li>We recommend that any user on CloudFlare with WordPress use this plugin. </li>

<li>NOTE: This plugin is complementary to Akismet and W3 Total Cache. We recommend that you continue to use those services.</li> 

</ol>

<h4>MORE INFORMATION ON CLOUDFLARE:</h4>

CloudFlare is a service that makes websites load faster and protects sites from online spammers and hackers. Any website with a root domain (ie www.mydomain.com) can use CloudFlare. On average, it takes less than 5 minutes to sign up. You can learn more here: <a href="http://www.cloudflare.com/">CloudFlare.com</a>.

    <hr />

    <form action="" method="post" id="cloudflare-conf">
    <?php wp_nonce_field('cloudflare-db-api','cloudflare-db-api-nonce'); ?>
    <?php if (get_option('cloudflare_api_key') && get_option('cloudflare_api_email')) { ?>
    <?php } else { ?> 
        <p><?php printf(__('Input your API key from your CloudFlare Accounts Settings page here. To find your API key, log in to <a href="%1$s">CloudFlare</a> and go to \'Account\'.'), 'https://www.cloudflare.com/my-account.html'); ?></p>
    <?php } ?>
    <h3><label for="cloudflare_zone_name"><?php _e('CloudFlare Domain Name'); ?></label></h3>
    <p>
    	<input id="cloudflare_zone_name" name="cloudflare_zone_name" type="text" size="50" maxlength="255" value="<?php echo $cloudflare_zone_name; ?>" style="font-family: 'Courier New', Courier, mono; font-size: 1.5em;" /> (<?php _e('<a href="https://www.cloudflare.com/my-websites">Get this?</a>'); ?>)
    </p>
    <p>E.g. Enter domain.com not www.domain.com / blog.domain.com</p>
    <?php if (isset($zone_message)) echo sprintf('<p>%s</p>', $zone_message); ?>
    <h3><label for="key"><?php _e('CloudFlare API Key'); ?></label></h3>
    <p>
    	<input id="key" name="key" type="text" size="50" maxlength="48" value="<?php echo get_option('cloudflare_api_key'); ?>" style="font-family: 'Courier New', Courier, mono; font-size: 1.5em;" /> (<?php _e('<a href="https://www.cloudflare.com/my-account.html">Get this?</a>'); ?>)
    </p>
    <?php if (isset($key_message)) echo sprintf('<p>%s</p>', $key_message); ?>
    
    <h3><label for="email"><?php _e('CloudFlare API Email'); ?></label></h3>
    <p>
    	<input id="email" name="email" type="text" size="50" maxlength="48" value="<?php echo get_option('cloudflare_api_email'); ?>" style="font-family: 'Courier New', Courier, mono; font-size: 1.5em;" /> (<?php _e('<a href="https://www.cloudflare.com/my-account.html">Get this?</a>'); ?>)
    </p>
    <?php if (isset($key_message)) echo sprintf('<p>%s</p>', $key_message); ?>
    
    <h3><label for="dev_mode"><?php _e('Development Mode'); ?></label> <span style="font-size:9pt;">(<a href="https://support.cloudflare.com/hc/en-us/articles/200168246-What-does-CloudFlare-Development-mode-mean-" " target="_blank">What is this?</a>)</span></h3>

    <?php if ($curl_installed) { ?>
    <div style="font-family: 'Courier New', Courier, mono; font-size: 1.5em;">
    <input type="radio" name="dev_mode" value="0" <?php if ($dev_mode == "off") echo "checked"; ?>> Off
    <input type="radio" name="dev_mode" value="1" <?php if ($dev_mode == "on") echo "checked"; ?>> On
    </div>
    <?php } ?>
    </p>
    <p class="submit"><input type="submit" name="submit" value="<?php _e('Update options &raquo;'); ?>" /></p>
    </form>

        <?php //    </div> ?>
    </div>
    <?php
}

// Now actually allow CF to see when a comment is approved/not-approved.
function cloudflare_set_comment_status($id, $status) {
         
         global $cf_api_host, $cf_api_port, $cloudflare_api_key, $cloudflare_api_email; 
    if (!$cf_api_host || !$cf_api_port) {
        return;
    }
    load_cloudflare_keys();
    if (!$cloudflare_api_key || !$cloudflare_api_email) {
        return;
    }

    // ajax/external-event.html?email=ian@cloudflare.com&t=94606855d7e42adf3b9e2fd004c7660b941b8e55aa42d&evnt_v={%22dd%22:%22d%22}&evnt_t=WP_SPAM
    $comment = get_comment($id);
    $value = array("a" => $comment->comment_author, 
                   "am" => $comment->comment_author_email,
                   "ip" => $comment->comment_author_IP,
                   "con" => substr($comment->comment_content, 0, 100));
    $url = "/ajax/external-event.html?evnt_v=" . urlencode(json_encode($value)) . "&u=$cloudflare_api_email&tkn=$cloudflare_api_key&evnt_t=";

    // If spam, send this info over to CloudFlare.
    if ($status == "spam") {
        $url .= "WP_SPAM";
        $fp = @fsockopen($cf_api_host, $cf_api_port, $errno, $errstr, 30);
        if ($fp) {
            $out = "GET $url HTTP/1.1\r\n";
            $out .= "Host: www.cloudflare.com\r\n";
            $out .= "Connection: Close\r\n\r\n";
            fwrite($fp, $out);
            $res = "";
            while (!feof($fp)) {
                $res .= fgets($fp, 128);
            }
            fclose($fp);
        }
    }
}

add_action('wp_set_comment_status', 'cloudflare_set_comment_status', 1, 2);

function get_dev_mode_status($token, $email, $zone) {
    
    $fields = array(
        'a'=>"zone_load",
        'tkn'=>$token,
        'email'=>$email,
        'z'=>$zone
    );
    
    $result = cloudflare_curl(CLOUDFLARE_API_URL, $fields, true);
    
    if (is_wp_error($result)) {
    	error_log($result->get_error_message());
    	return $result;
	}
	
    if ($result->response->zone->obj->zone_status_class == "status-dev-mode") {
        return "on";
    }

    return "off";
}

function set_dev_mode($token, $email, $zone, $value) {
    
    $fields = array(
    	'a'=>"devmode",
        'tkn'=>$token,
        'email'=>$email,
        'z'=>$zone,
        'v'=>$value
	);
	
    $result = cloudflare_curl(CLOUDFLARE_API_URL, $fields, true);
    
    if (is_wp_error($result)) {
    	error_log($result->get_error_message());
		return $result;
	}
	
	return $result;
}

function get_domain($token, $email, $raw_domain) {
    
    $fields = array(
    	'a'=>"zone_load_multi",
        'tkn'=>$token,
        'email'=>$email
    );

	$result = cloudflare_curl(CLOUDFLARE_API_URL, $fields, true);
	
	if (is_wp_error($result)) {
		error_log($result->get_error_message());
		return $result;
	}
	
    $zone_count = $result->response->zones->count;
    $zone_names = array();
    
    if ($zone_count < 1) {
    	return new WP_Error('match_domain', 'API did not return any domains');
    }
    else {
        for ($i = 0; $i < $zone_count; $i++) {
            $zone_names[] = $result->response->zones->objs[$i]->zone_name;
        }
        
        $match = match_domain_to_zone($raw_domain, $zone_names);
        
        if (is_null($match)) {
        	return new WP_Error('match_domain', 'Unable to automatically find your domain (no match)');
        }
        else {
        	return $match;
        }
    }
}

/**
* @param $domain		string		the domain portion of the WP URL
* @param $zone_names	array		an array of zone_names to compare against
* 
* @returns null|string null in the case of a failure, string in the case of a match
*/
function match_domain_to_zone($domain, $zones) {

	$splitDomain = explode('.', $domain);
	$totalParts = count($splitDomain);
	
	// minimum parts for a complete zone match will be 2, e.g. blah.com
	for ($i = 0; $i <= ($totalParts - 2); $i++) {
		$copy = $splitDomain;
		$currentDomain = implode('.', array_splice($copy, $i));
		foreach ($zones as $zone_name) {
			if (strtolower($currentDomain) == strtolower($zone_name)) {
            	return $zone_name;
        	}
        }
	}
	
	return null;
}

/**
* @param $url		string		the URL to curl
* @param $fields	array		an associative array of arguments for POSTing
* @param $json 		boolean		attempt to decode response as JSON
* 
* @returns WP_ERROR|string|object in the case of an error, otherwise a $result string or JSON object
*/
function cloudflare_curl($url, $fields = array(), $json = true) {

	$ch = curl_init();
	
    curl_setopt($ch,CURLOPT_URL,$url);

	if (!empty($fields)) {
	
		$fields_string = '';
		
		foreach($fields as $key=>$value) { 
        	$fields_string .= $key.'='.$value.'&';
    	}
    	rtrim($fields_string,'&');
	
		curl_setopt($ch,CURLOPT_POST,count($fields));
    	curl_setopt($ch,CURLOPT_POSTFIELDS,$fields_string);
	}

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    
    $result = curl_exec($ch);
    
    if ($result == false) {
    	$curl_error = curl_error($ch);
    	$curl_error_number = curl_errno($ch);
    	
    	// Attempt to detect a possible invalid cert bundle on the server - CURLE_SSL_CACERT (60)
    	// Ref: http://curl.haxx.se/libcurl/c/libcurl-errors.html
    	if ($curl_error_number == 60) {
    		return new WP_Error('curl', sprintf('cURL returned an SSL related error when contacting the CloudFlare API using HTTPS. It is possible that your server\'s CA cert bundle is out of date. You should contact your server administrator or hosting provider and ask them to check that you have the latest CA Cert bundle installed. cURL Error: %d - %s', $curl_error_number, $curl_error), $curl_error_number);
    	}
    	else {
    		return new WP_Error('curl', sprintf('cURL request failed: %d - %s', $curl_error_number, $curl_error), $curl_error_number);
    	}
    }
    
    if ($json == true) {
    	$result = json_decode($result);
    	// not a perfect test, but better than nothing perhaps
    	if ($result == null) {
    		return new WP_Error('json_decode', sprintf('Unable to decode JSON response'), $result);
    	}
    	// check for the CloudFlare API failure response
    	if (property_exists($result, 'result') && $result->result !== 'success') {
			$msg = 'Unknown Error';
			if (property_exists($result, 'msg') && !empty($result->msg)) $msg = $result->msg;
 			return new WP_Error('cloudflare', $msg);
		}
    }
    
    curl_close($ch);
    
    return $result;
}
?>
