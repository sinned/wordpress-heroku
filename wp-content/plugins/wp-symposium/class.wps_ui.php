<?php

// ******************************** UI CLASS ***********************************

class wps_ui {
	
	// *** MAIL
	
	function mail_subject($subject_text="Mail subject", $box_class="") {
		$html = '<input type="text" name="wps-mail-subject" class="'.$box_class.'" onblur="this.value=(this.value==\'\') ? \''.$subject_text.'\' : this.value;" onfocus="this.value=(this.value==\''.$subject_text.'\') ? \'\' : this.value;" value="'.$subject_text.'" />';
		return $html;
	}
	
	function mail_message($message_text="Message...", $box_class="") {
		$html = '<textarea name="wps-mail-message" class="'.$box_class.'" onblur="this.value=(this.value==\'\') ? \''.$message_text.'\' : this.value;" onfocus="this.value=(this.value==\''.$message_text.'\') ? \'\' : this.value;" value="'.$message_text.'">'.$message_text.'</textarea>';
		return $html;
	}

	function mail_send_button($button_text='Update', $button_class='') {
		return '<input id="mail_send_button" type="submit" class="'.$button_class.'" value="'.__($button_text, WPS_TEXT_DOMAIN).'" /> ';
	}
	
	// *** PROFILE
	
	function whatsup($whatsup_text='', $box_class='input-field') {
		$whatsup = $whatsup_text ? $whatsup_text : get_option(WPS_OPTIONS_PREFIX.'_status_label');		
		return '<input type="text" id="__wps__comment" name="status" class="'.$box_class.'" onblur ="this.value=(this.value==\'\') ? \''.addslashes($whatsup_text).'\' : this.value;" onfocus="this.value=(this.value==\''.addslashes($whatsup_text).'\') ? \'\' : this.value;" value="'.stripslashes($whatsup_text).'" />';
	}

	function whatsup_button($button_text='Update', $button_class='') {
		return '<input id="__wps__add_comment" type="submit" class="'.$button_class.'" value="'.__($button_text, WPS_TEXT_DOMAIN).'" /> ';
	}

	function friendship_add($id, $message='Add a message', $sent_message='Request sent.', $box_class='input-field') {
		$html = '';
		$html .= '<div id="addasfriend_done1_'.$id.'">';
		$html .= '<div id="add_as_friend_message">';
		$html .= '<input type="text" title="'.$id.'" id="addfriend" class="'.$box_class.'" placeholder="'.$message.'">';
		$html .= '</div></div>';
		$html .= '<div id="addasfriend_done2_'.$id.'" style="display:none">'.$sent_message.'</div>';
		return $html;
	}
	function friendship_add_button($id, $button_class='') {
		return '<input type="submit" title="'.$id.'" id="addasfriend" class="'.$button_class.'" value="'.__('Add', WPS_TEXT_DOMAIN).'" />';
	}
	function friendship_cancel($id, $cancel_text='Cancel', $done_cancel_text='Cancelled', $button_class='', $style = '') {
		$html = '<input type="submit" title="'.$id.'" id="cancelfriendrequest" class="'.$button_class.'" style="'.$style.'" value="'.$cancel_text.'" />';
		$html .= '<div id="cancelfriendrequest_done" style="display:none">'.$done_cancel_text.'</div>';
		return $html;
	}
	function friendship_cancel_link($id, $cancel_text='Cancel', $done_cancel_text='Cancelled') {
		$html = '<a title="'.$id.'" id="cancelfriendrequest" style="cursor:pointer">'.$cancel_text.'</a>';
		$html .= '<span id="cancelfriendrequest_done" style="display:none">'.$done_cancel_text.'</span>';
		return $html;
	}
	
	function activity_post($post_text='', $box_class='input-field') {
		$post_text = $post_text ? $post_text : __('Write a comment...', WPS_TEXT_DOMAIN);	
		$post_text = str_replace('\'', '`', $post_text);	
		return '<input id="__wps__comment"  type="text" name="post_comment" class="'.$box_class.'" onblur="this.value=(this.value==\'\') ? \''.$post_text.'\' : this.value;" onfocus="this.value=(this.value==\''.$post_text.'\') ? \'\' : this.value;" value="'.$post_text.'" />';		
	}

	function activity_post_button($button_text='Post', $button_class='') {
		return '<input id="__wps__add_comment" type="submit" class="'.$button_class.'" value="'.__($button_text, WPS_TEXT_DOMAIN).'" /> ';
	}
	
	function comment_post($post_text='', $box_class='input-field') {
		$post_text = $post_text ? $post_text : __('Write a comment...', WPS_TEXT_DOMAIN);		
		return '<input id="__wps__comment"  type="text" name="post_comment" class="'.$box_class.'" onblur="this.value=(this.value==\'\') ? \''.$post_text.'\' : this.value;" onfocus="this.value=(this.value==\''.$post_text.'\') ? \'\' : this.value;" value="'.$post_text.'" />';		
	}

	function comment_post_button($button_text='Post', $button_class='') {
		return '<input id="__wps__add_comment" type="submit" class="'.$button_class.'" value="'.__($button_text, WPS_TEXT_DOMAIN).'" /> ';
	}
	
	function poke_button($text='Hey!', $button_class='', $style='') {
		return '<input type="submit" class="'.$button_class.' poke-button" style="'.$style.'" value="'.$text.'" />';
	}
	
	function unfollow_button($text='Unfollow', $button_class='', $style='') {
		return '<input type="submit" ref="unfollow" class="'.$button_class.' follow-button" style="'.$style.'" value="'.$text.'" />';
	}
	
	function follow_button($text='Follow', $button_class='', $style='') {
		return '<input type="submit" ref="follow" class="'.$button_class.' follow-button" style="'.$style.'" value="'.$text.'" />';
	}
	
	function mail_button($button_text='Send Mail', $button_class='', $style='') {
		$html = '';
		$html .= '<input type="submit" class="'.$button_class.'" id="profile_send_mail_button" style="'.$style.'" value="'.__($button_text, WPS_TEXT_DOMAIN).'" />';
		return $html;
	}
	
	function profile_placeholder($view='all', $class='', $rel='') {
		$html = '<div id="force_profile_page" style="display:none; border:1px solid red;">'.$view.'</div>';
		$html .= '<div id="profile_body" rel="'.$rel.'" style="padding:0; margin: 0;" class="'.$class.'">';
		$html .= "<img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/busy.gif' />";
		$html .= '</div>';		
		return $html;
	}
	
	function facebook_connect($id, $post_text="Post to Facebook", $connect_text="Connect to Facebook", $cancel_text="Cancel") {
		return 'Please inform admin at www.wpsymposium.com';
	}
	
	// *** FORUM

	function get_breadcrumbs($current_catid=0, $param='catid', $show_home=true, $sep='&rarr;', $include_last_link=false, $return_array='', $url='', $order=100) {
		
		global $wpdb;

		$url = $url != '' ? $url : get_bloginfo('url').get_option(WPS_OPTIONS_PREFIX.'_forum_url');

		if ($return_array=='') {
			$return_array = array();
			if ($show_home) {
				$row_array['order'] = 0;
				$row_array['cat_id'] = 0;
				$row_array['this_id'] = 0;
				$row_array['cat_title'] = __('Top Level', WPS_TEXT_DOMAIN);
				$row_array['cat_description'] = __('Top Level', WPS_TEXT_DOMAIN);
				array_push($return_array,$row_array);	
			}
		}
		
		$sql = "select * from ".$wpdb->prefix."symposium_cats where cid = %d";
		$parent_cat = $wpdb->get_row($wpdb->prepare($sql, $current_catid));
		
		if (!$parent_cat) {
			return false;

		} else {
			
			$row_array['order'] = $order;
			$row_array['cat_id'] = $parent_cat->cat_parent;
			$row_array['this_id'] = $parent_cat->cid;
			$row_array['cat_title'] = $parent_cat->title;
			$row_array['cat_description'] = $parent_cat->cat_desc;
			array_push($return_array,$row_array);	

			if ($parent_cat->cat_parent > 0) {
				$order--;
				$this->get_breadcrumbs($parent_cat->cat_parent, $param, $show_home, $sep, $include_last_link, $return_array, $url, $order);
			} else {
				$trail = __wps__sub_val_sort($return_array,'order');
				$crumbs = '';
				$count = 0;
				foreach ($trail as $crumb) {
					$count++;
					if ($count < count($trail) || $include_last_link) {
						$crumbs .= "<a href='".$url.__wps__string_query($url).$param."=".$crumb['this_id']."'>".$crumb['cat_title']."</a> ";
						if ($count < count($trail)) $crumbs .= $sep." ";
					} else {
						$crumbs .= $crumb['cat_title'];
					}
				}
				echo $crumbs;
			}
		}	
		
	}	
	
	function forum_reply($button_text='Reply', $button_class='', $textarea_class='', $elastic_text=false, $post_url='#') {
		
		
		$elastic = $elastic_text ? ' elastic' : '';

		$html = '';
		$html .= '<form action="'.$post_url.'" method="POST">';
		$html .= '<textarea class="textarea_Editor '.$textarea_class.$elastic.'" name="__wps__reply_text" id="__wps__reply_text"></textarea><br />';
		$html .= '<input id="mail_send_button" type="submit" class="'.$button_class.'" value="'.__($button_text, WPS_TEXT_DOMAIN).'" /> ';
		$html .= '</form>';
		
		return $html;
		

	}
}

?>
