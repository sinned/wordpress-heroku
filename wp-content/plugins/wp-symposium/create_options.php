<?php

// Create/Update options ***************************************************************
 	
	// Add new core wp_options if don't yet exist
	if (get_option(WPS_OPTIONS_PREFIX.'_categories_background') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_categories_background', '#0072bc'); 
	if (get_option(WPS_OPTIONS_PREFIX.'_categories_color') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_categories_color', '#fff'); 
	if (get_option(WPS_OPTIONS_PREFIX.'_bigbutton_background') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_bigbutton_background', '#0072bc'); 
	if (get_option(WPS_OPTIONS_PREFIX.'_bigbutton_color') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_bigbutton_color', '#fff'); 
	if (get_option(WPS_OPTIONS_PREFIX.'_bigbutton_background_hover') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_bigbutton_background_hover', '#00aeef');
	if (get_option(WPS_OPTIONS_PREFIX.'_bigbutton_color_hover') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_bigbutton_color_hover', '#fff'); 
	if (get_option(WPS_OPTIONS_PREFIX.'_bg_color_1') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_bg_color_1', '#0072bc'); 
	if (get_option(WPS_OPTIONS_PREFIX.'_bg_color_2') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_bg_color_2', '#ebebeb');
	if (get_option(WPS_OPTIONS_PREFIX.'_bg_color_3') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_bg_color_3', '#fff'); 
	if (get_option(WPS_OPTIONS_PREFIX.'_text_color') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_text_color', '#000'); 
	if (get_option(WPS_OPTIONS_PREFIX.'_table_rollover') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_table_rollover', '#fbaf5a'); 
	if (get_option(WPS_OPTIONS_PREFIX.'_link') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_link', '#0054a5'); 
	if (get_option(WPS_OPTIONS_PREFIX.'_link_hover') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_link_hover', '#000'); 
	if (get_option(WPS_OPTIONS_PREFIX.'_table_border') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_table_border', 2); 
	if (get_option(WPS_OPTIONS_PREFIX.'_replies_border_size') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_replies_border_size', 1); 
	if (get_option(WPS_OPTIONS_PREFIX.'_text_color_2') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_text_color_2', '#0054a5'); 
	if (get_option(WPS_OPTIONS_PREFIX.'_row_border_style') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_row_border_style', 'dotted'); 
	if (get_option(WPS_OPTIONS_PREFIX.'_row_border_size') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_row_border_size', 1); 
	if (get_option(WPS_OPTIONS_PREFIX.'_border_radius') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_border_radius', 2);
	if (get_option(WPS_OPTIONS_PREFIX.'_label') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_label', '#000');
	if (get_option(WPS_OPTIONS_PREFIX.'_footer') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_footer', __('Please don\'t reply to this email', WPS_TEXT_DOMAIN));
	if (get_option(WPS_OPTIONS_PREFIX.'_send_summary') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_send_summary', 'on');
	if (get_option(WPS_OPTIONS_PREFIX.'_forum_url') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_forum_url', __('Important: Please Visit Installation Page!', WPS_TEXT_DOMAIN));	 			  
	if (get_option(WPS_OPTIONS_PREFIX.'_from_email') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_from_email', 'noreply@example.com');
	if (get_option(WPS_OPTIONS_PREFIX.'_underline') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_underline', 'on');
	if (get_option(WPS_OPTIONS_PREFIX.'_preview1') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_preview1', 0);
	if (get_option(WPS_OPTIONS_PREFIX.'_preview2') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_preview2', 100);
	if (get_option(WPS_OPTIONS_PREFIX.'_include_admin') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_include_admin', 'on');
	if (get_option(WPS_OPTIONS_PREFIX.'_oldest_first') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_oldest_first', 'on');
	if (get_option(WPS_OPTIONS_PREFIX.'_wp_width') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_wp_width', '100%');
	if (get_option(WPS_OPTIONS_PREFIX.'_main_background') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_main_background', '#fff');
	if (get_option(WPS_OPTIONS_PREFIX.'_closed_opacity') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_closed_opacity', '1.0');
	if (get_option(WPS_OPTIONS_PREFIX.'_closed_word') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_closed_word', __('closed', WPS_TEXT_DOMAIN));
	if (get_option(WPS_OPTIONS_PREFIX.'_fontfamily') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_fontfamily', 'Georgia,Times');
	if (get_option(WPS_OPTIONS_PREFIX.'_headingsfamily') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_headingsfamily', 'Arial,Helvetica');
	if (get_option(WPS_OPTIONS_PREFIX.'_fontsize') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_fontsize', 13);
	if (get_option(WPS_OPTIONS_PREFIX.'_headingssize') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_headingssize', 20);
	if (get_option(WPS_OPTIONS_PREFIX.'_jquery') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_jquery', 'on');
	if (get_option(WPS_OPTIONS_PREFIX.'_jqueryui') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_jqueryui', 'on');
	if (get_option(WPS_OPTIONS_PREFIX.'_tinymce') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_tinymce', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_jwplayer') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_jwplayer', 'on');
	if (get_option(WPS_OPTIONS_PREFIX.'_emoticons') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_emoticons', 'on');
	if (get_option(WPS_OPTIONS_PREFIX.'_moderation') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_moderation', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_mail_url') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_mail_url', __('Important: Please Visit Installation Page!', WPS_TEXT_DOMAIN));
	if (get_option(WPS_OPTIONS_PREFIX.'_online') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_online', 3);
	if (get_option(WPS_OPTIONS_PREFIX.'_offline') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_offline', 15);
	if (get_option(WPS_OPTIONS_PREFIX.'_wp_alignment') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_wp_alignment', 'Center');
	if (get_option(WPS_OPTIONS_PREFIX.'_enable_password') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_enable_password', 'on');
	if (get_option(WPS_OPTIONS_PREFIX.'_members_url') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_members_url', __('Important: Please Visit Installation Page!', WPS_TEXT_DOMAIN));
	if (get_option(WPS_OPTIONS_PREFIX.'_sharing') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_sharing', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_use_styles') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_use_styles', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_show_wall_extras') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_show_wall_extras', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_use_chat') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_use_chat', 'on');
	if (get_option(WPS_OPTIONS_PREFIX.'_bar_polling') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_bar_polling', 120);
	if (get_option(WPS_OPTIONS_PREFIX.'_chat_polling') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_chat_polling', 10);
	if (get_option(WPS_OPTIONS_PREFIX.'_chatroom_banned') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_chatroom_banned', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_profile_google_map') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_profile_google_map', 150);
	if (get_option(WPS_OPTIONS_PREFIX.'_use_poke') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_use_poke', 'on');
	if (get_option(WPS_OPTIONS_PREFIX.'_poke_label') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_poke_label', 'Hey!');
	if (get_option(WPS_OPTIONS_PREFIX.'_motd') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_motd', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_reminder') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_reminder', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_profile_url') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_profile_url', __('Important: Please Visit Installation Page!', WPS_TEXT_DOMAIN));
	if (get_option(WPS_OPTIONS_PREFIX.'_groups_url') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_groups_url', __('Important: Please Visit Installation Page!', WPS_TEXT_DOMAIN));
	if (get_option(WPS_OPTIONS_PREFIX.'_group_url') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_group_url', __('Important: Please Visit Installation Page!', WPS_TEXT_DOMAIN));
	if (get_option(WPS_OPTIONS_PREFIX.'_group_all_create') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_group_all_create', 'on');
	if (get_option(WPS_OPTIONS_PREFIX.'_group_invites') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_group_invites', 'on');
	if (get_option(WPS_OPTIONS_PREFIX.'_group_invites_max') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_group_invites_max', 10);
	if (get_option(WPS_OPTIONS_PREFIX.'_profile_avatars') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_profile_avatars', 'on');
	if (get_option(WPS_OPTIONS_PREFIX.'_img_db') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_img_db', '');	
	if (get_option(WPS_OPTIONS_PREFIX.'_img_path') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_img_path', WP_CONTENT_DIR.'/wps-content');

	$img_url = WP_CONTENT_URL."/wps-content";
	$img_url = str_replace(__wps__siteURL(), '', $img_url); 
	if (get_option(WPS_OPTIONS_PREFIX.'_img_url') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_img_url', $img_url);
	if (get_option(WPS_OPTIONS_PREFIX.'_img_upload') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_img_upload', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_img_crop') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_img_crop', 'on');
	if (get_option(WPS_OPTIONS_PREFIX.'_forum_ranks') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_forum_ranks', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_forum_ajax') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_forum_ajax', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_forum_login') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_forum_login', 'on');
	if (get_option(WPS_OPTIONS_PREFIX.'_initial_friend') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_initial_friend', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_initial_groups') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_initial_groups', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_template_profile_header') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_template_profile_header', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_template_profile_body') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_template_profile_body', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_template_page_footer') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_template_page_footer', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_template_email') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_template_email', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_template_forum_header') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_template_forum_header', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_template_forum_category') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_template_forum_category', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_template_forum_topic') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_template_forum_topic', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_template_mail_tray') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_template_mail_tray', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_template_mail_message') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_template_mail_message', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_template_group') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_template_group', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_template_group_forum_category') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_template_group_forum_category', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_template_group_forum_topic') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_template_group_forum_topic', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_facebook_api') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_facebook_api', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_facebook_secret') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_facebook_secret', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_css') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_css', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_responsive') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_responsive', '/* Default CSS here assumes use of default WPS templates */[][]/* resolutions up to 568px */[]@media screen and (max-width: 568px) {[]    /* Hide forum replies and views count */[]    .__wps__wrapper .row_replies, .__wps__wrapper .row_views {  display: none; }[]    /* Hide avatars */[]    .__wps__wrapper .avatar_last_topic,[]    .__wps__wrapper .avatar_first_topic { display: none; }[]    /* Move over text */[]    .__wps__wrapper .last_topic_text,[]    .__wps__wrapper .first_topic { padding-left: 0 !important; }[]    .__wps__wrapper .row_topic_text { display: none; }[]    .__wps__wrapper .reply-comments { margin-left: 0 !important; }[]    /* Hide the forum options */[]    .__wps__wrapper  #forum_options,[]    .__wps__wrapper  #share_link,[]    .__wps__wrapper  #__wps__forum_dropdown { display: none; }[]    /* Hide avatars and info on forum topic */[]    .__wps__wrapper  #top_of_first_post .avatar { display: none; }[]    .__wps__wrapper  .avatar { display: none; }[]    .__wps__wrapper  .__wps__reply_box { padding-left: 0 !important; }[]    .__wps__wrapper  .reply-comments-box-text { margin-left: 0 !important; }[]}');
	if (get_option(WPS_OPTIONS_PREFIX.'_mobile_topics') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_mobile_topics', 20);
	if (get_option(WPS_OPTIONS_PREFIX.'_bump_topics') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_bump_topics', 'on');
	if (get_option(WPS_OPTIONS_PREFIX.'_show_dob') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_show_dob', 'on');
	if (get_option(WPS_OPTIONS_PREFIX.'_use_votes') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_use_votes', 'on');
	if (get_option(WPS_OPTIONS_PREFIX.'_use_votes_remove') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_use_votes_remove', 0);
	if (get_option(WPS_OPTIONS_PREFIX.'_show_buttons') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_show_buttons', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_show_admin') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_show_admin', 'on');
	if (get_option(WPS_OPTIONS_PREFIX.'_forumlatestposts_count') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_forumlatestposts_count', 100);
	if (get_option(WPS_OPTIONS_PREFIX.'_redirect_wp_profile') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_redirect_wp_profile', 'on');
	if (get_option(WPS_OPTIONS_PREFIX.'_striptags') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_striptags', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_forum_uploads') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_forum_uploads', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_forum_thumbs') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_forum_thumbs', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_forum_thumbs_size') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_forum_thumbs_size', 400);
	if (get_option(WPS_OPTIONS_PREFIX.'_forum_info') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_forum_info', 'on');
	if (get_option(WPS_OPTIONS_PREFIX.'_forum_stars') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_forum_stars', 'on');
	if (get_option(WPS_OPTIONS_PREFIX.'_use_votes_min') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_use_votes_min', 10);
	if (get_option(WPS_OPTIONS_PREFIX.'_use_answers') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_use_answers', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_image_ext') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_image_ext', '*.jpg,*.gif,*.png,*.jpeg');
	if (get_option(WPS_OPTIONS_PREFIX.'_video_ext') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_video_ext', '*.mp4');
	if (get_option(WPS_OPTIONS_PREFIX.'_doc_ext') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_doc_ext', '*.pdf,*.txt,*.zip');

	if (get_option(WPS_OPTIONS_PREFIX.'_menu_profile') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_menu_profile', 'on');
	if (get_option(WPS_OPTIONS_PREFIX.'_menu_my_activity') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_menu_my_activity', 'on');
	if (get_option(WPS_OPTIONS_PREFIX.'_menu_friends_activity') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_menu_friends_activity', 'on');
	if (get_option(WPS_OPTIONS_PREFIX.'_menu_all_activity') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_menu_all_activity', 'on');
	if (get_option(WPS_OPTIONS_PREFIX.'_menu_friends') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_menu_friends', 'on');
	if (get_option(WPS_OPTIONS_PREFIX.'_menu_profile_other') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_menu_profile_other', 'on');
	if (get_option(WPS_OPTIONS_PREFIX.'_menu_my_activity_other') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_menu_my_activity_other', 'on');
	if (get_option(WPS_OPTIONS_PREFIX.'_menu_friends_activity_other') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_menu_friends_activity_other', 'on');
	if (get_option(WPS_OPTIONS_PREFIX.'_menu_all_activity_other') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_menu_all_activity_other', 'on');
	if (get_option(WPS_OPTIONS_PREFIX.'_menu_friends_other') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_menu_friends_other', 'on');	
	if (get_option(WPS_OPTIONS_PREFIX.'_menu_avatar') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_menu_avatar', 'on');
	if (get_option(WPS_OPTIONS_PREFIX.'_menu_details') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_menu_details', 'on');
	if (get_option(WPS_OPTIONS_PREFIX.'_menu_settings') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_menu_settings', 'on');
		
	if (get_option(WPS_OPTIONS_PREFIX.'_menu_texthtml') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_menu_texthtml', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_mail_all') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_mail_all', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_elastic') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_elastic', 'on');
	if (get_option(WPS_OPTIONS_PREFIX.'_profile_show_unchecked') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_profile_show_unchecked', 'on');
	$images = WPS_PLUGIN_URL."/images";
	$images = str_replace(__wps__siteURL(), '', $images); 
	if (get_option(WPS_OPTIONS_PREFIX.'_images') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_images', $images);
	if (get_option(WPS_OPTIONS_PREFIX.'_show_dir_buttons') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_show_dir_buttons', 'on');
	if (get_option(WPS_OPTIONS_PREFIX.'_dir_page_length') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_dir_page_length', 25);
	if (get_option(WPS_OPTIONS_PREFIX.'_wps_lite') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_wps_lite', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_wps_profile_default') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_wps_profile_default', 'activity');
	if (get_option(WPS_OPTIONS_PREFIX.'_wps_default_privacy') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_wps_default_privacy', 'Friends only');
	if (get_option(WPS_OPTIONS_PREFIX.'_wps_panel_all') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_wps_panel_all', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_wps_default_forum') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_wps_default_forum', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_wps_use_gravatar') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_wps_use_gravatar', 'on');
	if (get_option(WPS_OPTIONS_PREFIX.'_wps_time_out') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_wps_time_out', 0);
	if (get_option(WPS_OPTIONS_PREFIX.'_wps_js_file') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_wps_js_file', 'wps.min.js');
	if (get_option(WPS_OPTIONS_PREFIX.'_wps_css_file') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_wps_css_file', 'wps.min.css');
	if (get_option(WPS_OPTIONS_PREFIX.'_allow_reports') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_allow_reports', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_ajax_widgets') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_ajax_widgets', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_status_label') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_status_label', __('What`s up?', WPS_TEXT_DOMAIN));
	if (get_option(WPS_OPTIONS_PREFIX.'_jscharts') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_jscharts', 'on');
	if (get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_1') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_1', 'bold,italic,|,fontselect,fontsizeselect,forecolor,backcolor,|,bullist,numlist,|,link,unlink,|,image,media,|,emotions');
	if (get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_2') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_2', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_3') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_3', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_4') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_4', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_css') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_css', str_replace(__wps__siteURL(), '', WPS_PLUGIN_URL."/tiny_mce/themes/advanced/skins/wps.css"));
	if (get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_skin') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_skin', 'cirkuit');
	if (get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_width') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_width', 563);
	if (get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_height') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_height', 300);
	if (get_option(WPS_OPTIONS_PREFIX.'_forum_refresh') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_forum_refresh', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_subject_mail_new') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_subject_mail_new', __('New Mail Message: [subject]', WPS_TEXT_DOMAIN));
	if (get_option(WPS_OPTIONS_PREFIX.'_subject_forum_new') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_subject_forum_new', __('New Forum Topic', WPS_TEXT_DOMAIN));
	if (get_option(WPS_OPTIONS_PREFIX.'_subject_forum_reply') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_subject_forum_reply', __('New Forum Reply', WPS_TEXT_DOMAIN));
	if (get_option(WPS_OPTIONS_PREFIX.'_profile_comments') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_profile_comments', 'on');
	if (get_option(WPS_OPTIONS_PREFIX.'_forum_login_form') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_forum_login_form', 'on');
	if (get_option(WPS_OPTIONS_PREFIX.'_forum_lock') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_forum_lock', 30);
	if (get_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_3') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_use_wysiwyg_3', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_dir_level') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_dir_level', 's:60:\"Everyone,Administrator,Editor,Author,Contributor,Subscriber,\";');		
	if (get_option(WPS_OPTIONS_PREFIX.'_viewer') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_viewer', 's:16:\"s:9:\"everyone,\";\";');
	if (get_option(WPS_OPTIONS_PREFIX.'_forum_editor') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_forum_editor', 's:59:\"s:51:\"Administrator,Editor,Author,Contributor,Subscriber,\";\";');
	if (get_option(WPS_OPTIONS_PREFIX.'_forum_reply') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_forum_reply', 's:59:\"s:51:\"Administrator,Editor,Author,Contributor,Subscriber,\";\";');
	if (get_option(WPS_OPTIONS_PREFIX.'_rewrite_forum_single') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_rewrite_forum_single', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_rewrite_forum_single_target') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_rewrite_forum_single_target', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_rewrite_forum_double') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_rewrite_forum_double', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_rewrite_forum_double_target') === false)	
		update_option(WPS_OPTIONS_PREFIX.'_rewrite_forum_double_target', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_rss_share') === false)
		update_option(WPS_OPTIONS_PREFIX.'_rss_share', ''); 
	
	// Set default values for if not yet set
	if (get_option(WPS_OPTIONS_PREFIX.'_long_menu') === false) 
		update_option(WPS_OPTIONS_PREFIX.'_long_menu', 'on');
	if (get_option(WPS_OPTIONS_PREFIX.'_alt_friend') === false) 
		update_option(WPS_OPTIONS_PREFIX.'_alt_friend', 'Friend');
	if (get_option(WPS_OPTIONS_PREFIX.'_alt_friends') === false) 
		update_option(WPS_OPTIONS_PREFIX.'_alt_friends', 'Friends');
	if (get_option(WPS_OPTIONS_PREFIX.'_alt_everyone') === false) 
		update_option(WPS_OPTIONS_PREFIX.'_alt_everyone', 'Everyone');
	if (get_option(WPS_OPTIONS_PREFIX.'_dir_atoz_order') === false) 
		update_option(WPS_OPTIONS_PREFIX.'_dir_atoz_order', 'last_activity');

	// Reply by email
	if (get_option(WPS_OPTIONS_PREFIX.'_mailinglist_server') === false)
		update_option(WPS_OPTIONS_PREFIX.'_mailinglist_server', 'mail.example.com');
	if (get_option(WPS_OPTIONS_PREFIX.'_mailinglist_port') === false)
		update_option(WPS_OPTIONS_PREFIX.'_mailinglist_port', 110);
	if (get_option(WPS_OPTIONS_PREFIX.'_mailinglist_username') === false)
		update_option(WPS_OPTIONS_PREFIX.'_mailinglist_username', 'username');
	if (get_option(WPS_OPTIONS_PREFIX.'_mailinglist_password') === false)
		update_option(WPS_OPTIONS_PREFIX.'_mailinglist_password', '');
	if (get_option(WPS_OPTIONS_PREFIX.'_mailinglist_prompt') === false)
		update_option(WPS_OPTIONS_PREFIX.'_mailinglist_prompt', 'To reply, enter your reply text between the two lines of stars below, everything else will be ignored!');
	if (get_option(WPS_OPTIONS_PREFIX.'_mailinglist_divider') === false)
		update_option(WPS_OPTIONS_PREFIX.'_mailinglist_divider', 'ENTER TEXT BELOW HERE **********');
	if (get_option(WPS_OPTIONS_PREFIX.'_mailinglist_divider_bottom') === false)
		update_option(WPS_OPTIONS_PREFIX.'_mailinglist_divider_bottom', 'ENTER TEXT ABOVE HERE **********');
	if (get_option(WPS_OPTIONS_PREFIX.'_mailinglist_cron') === false)
		update_option(WPS_OPTIONS_PREFIX.'_mailinglist_cron', 900);
	if (get_option(WPS_OPTIONS_PREFIX.'_mailinglist_from') === false)
		update_option(WPS_OPTIONS_PREFIX.'_mailinglist_from', 'forum@example.com');	
	
	// Profile menu text
	if (get_option(WPS_OPTIONS_PREFIX.'_menu_profile_text') === false) 
		update_option(WPS_OPTIONS_PREFIX.'_menu_profile_text', __('My Profile', WPS_TEXT_DOMAIN));
	if (get_option(WPS_OPTIONS_PREFIX.'_menu_my_activity_text') === false) 
		update_option(WPS_OPTIONS_PREFIX.'_menu_my_activity_text', __('My Activity', WPS_TEXT_DOMAIN));
	if (get_option(WPS_OPTIONS_PREFIX.'_menu_friends_activity_text') === false) 
		update_option(WPS_OPTIONS_PREFIX.'_menu_friends_activity_text', __('Friends Activity', WPS_TEXT_DOMAIN));
	if (get_option(WPS_OPTIONS_PREFIX.'_menu_all_activity_text') === false) 
		update_option(WPS_OPTIONS_PREFIX.'_menu_all_activity_text', __('All Activity', WPS_TEXT_DOMAIN));
	if (get_option(WPS_OPTIONS_PREFIX.'_menu_friends_text') === false) 
		update_option(WPS_OPTIONS_PREFIX.'_menu_friends_text', __('My Friends', WPS_TEXT_DOMAIN));
	if (get_option(WPS_OPTIONS_PREFIX.'_menu_mentions_text') === false) 
		update_option(WPS_OPTIONS_PREFIX.'_menu_mentions_text', __('Forum @mentions', WPS_TEXT_DOMAIN));
	if (get_option(WPS_OPTIONS_PREFIX.'_menu_groups_text') === false) 
		update_option(WPS_OPTIONS_PREFIX.'_menu_groups_text', __('My Groups', WPS_TEXT_DOMAIN));
	if (get_option(WPS_OPTIONS_PREFIX.'_menu_events_text') === false) 
		update_option(WPS_OPTIONS_PREFIX.'_menu_events_text', __('My Events', WPS_TEXT_DOMAIN));
	if (get_option(WPS_OPTIONS_PREFIX.'_menu_gallery_text') === false) 
		update_option(WPS_OPTIONS_PREFIX.'_menu_gallery_text', __('My Gallery', WPS_TEXT_DOMAIN));
	if (get_option(WPS_OPTIONS_PREFIX.'_menu_following_text') === false) 
		update_option(WPS_OPTIONS_PREFIX.'_menu_following_text', __('I am Following', WPS_TEXT_DOMAIN));
	if (get_option(WPS_OPTIONS_PREFIX.'_menu_followers_text') === false) 
		update_option(WPS_OPTIONS_PREFIX.'_menu_followers_text', __('My Followers', WPS_TEXT_DOMAIN));
	if (get_option(WPS_OPTIONS_PREFIX.'_menu_lounge_text') === false) 
		update_option(WPS_OPTIONS_PREFIX.'_menu_lounge_text', __('The Lounge', WPS_TEXT_DOMAIN));
	if (get_option(WPS_OPTIONS_PREFIX.'_menu_avatar_text') === false) 
		update_option(WPS_OPTIONS_PREFIX.'_menu_avatar_text', __('Profile Photo', WPS_TEXT_DOMAIN));
	if (get_option(WPS_OPTIONS_PREFIX.'_menu_details_text') === false) 
		update_option(WPS_OPTIONS_PREFIX.'_menu_details_text', __('Profile Details', WPS_TEXT_DOMAIN));
	if (get_option(WPS_OPTIONS_PREFIX.'_menu_settings_text') === false) 
		update_option(WPS_OPTIONS_PREFIX.'_menu_settings_text', __('Community Settings', WPS_TEXT_DOMAIN));
	if (get_option(WPS_OPTIONS_PREFIX.'_menu_profile_other_text') === false) 
		update_option(WPS_OPTIONS_PREFIX.'_menu_profile_other_text', __('Profile', WPS_TEXT_DOMAIN));
	if (get_option(WPS_OPTIONS_PREFIX.'_menu_my_activity_other_text') === false) 
		update_option(WPS_OPTIONS_PREFIX.'_menu_my_activity_other_text', __('Activity', WPS_TEXT_DOMAIN));
	if (get_option(WPS_OPTIONS_PREFIX.'_menu_friends_activity_other_text') === false) 
		update_option(WPS_OPTIONS_PREFIX.'_menu_friends_activity_other_text', __('Friends Activity', WPS_TEXT_DOMAIN));
	if (get_option(WPS_OPTIONS_PREFIX.'_menu_all_activity_other_text') === false) 
		update_option(WPS_OPTIONS_PREFIX.'_menu_all_activity_other_text', __('All Activity', WPS_TEXT_DOMAIN));
	if (get_option(WPS_OPTIONS_PREFIX.'_menu_friends_other_text') === false) 
		update_option(WPS_OPTIONS_PREFIX.'_menu_friends_other_text', __('Friends', WPS_TEXT_DOMAIN));
	if (get_option(WPS_OPTIONS_PREFIX.'_menu_mentions_other_text') === false) 
		update_option(WPS_OPTIONS_PREFIX.'_menu_mentions_other_text', __('Forum @mentions', WPS_TEXT_DOMAIN));
	if (get_option(WPS_OPTIONS_PREFIX.'_menu_groups_other_text') === false) 
		update_option(WPS_OPTIONS_PREFIX.'_menu_groups_other_text', __('Groups', WPS_TEXT_DOMAIN));
	if (get_option(WPS_OPTIONS_PREFIX.'_menu_events_other_text') === false) 
		update_option(WPS_OPTIONS_PREFIX.'_menu_events_other_text', __('Events', WPS_TEXT_DOMAIN));
	if (get_option(WPS_OPTIONS_PREFIX.'_menu_gallery_other_text') === false) 
		update_option(WPS_OPTIONS_PREFIX.'_menu_gallery_other_text', __('Gallery', WPS_TEXT_DOMAIN));
	if (get_option(WPS_OPTIONS_PREFIX.'_menu_following_other_text') === false) 
		update_option(WPS_OPTIONS_PREFIX.'_menu_following_other_text', __('Following', WPS_TEXT_DOMAIN));
	if (get_option(WPS_OPTIONS_PREFIX.'_menu_followers_other_text') === false) 
		update_option(WPS_OPTIONS_PREFIX.'_menu_followers_other_text', __('Followers', WPS_TEXT_DOMAIN));
	if (get_option(WPS_OPTIONS_PREFIX.'_menu_lounge_other_text') === false) 
		update_option(WPS_OPTIONS_PREFIX.'_menu_lounge_other_text', __('The Lounge', WPS_TEXT_DOMAIN));

	// Add fields to user meta
	if (get_option(WPS_OPTIONS_PREFIX.'_plus_lat') === false)
		update_option(WPS_OPTIONS_PREFIX.'_plus_lat', 0); 
	if (get_option(WPS_OPTIONS_PREFIX.'_plus_long') === false)
		update_option(WPS_OPTIONS_PREFIX.'_plus_long', 0); 
	
	// Add option
	if (get_option(WPS_OPTIONS_PREFIX.'_wps_show_hoverbox') === false)
		update_option(WPS_OPTIONS_PREFIX.'_wps_show_hoverbox', 'on');
	
	// Default offset that can be changed via admin page
	if (get_option(WPS_OPTIONS_PREFIX."_news_x_offset") === FALSE) {
		update_option(WPS_OPTIONS_PREFIX."_news_x_offset", 0);
		update_option(WPS_OPTIONS_PREFIX."_news_y_offset", 0);
	}
	
	if (get_option(WPS_OPTIONS_PREFIX."_news_polling") === FALSE) {
		update_option(WPS_OPTIONS_PREFIX."_news_polling", 60);
	}
	
	// Set up default option values
	if (get_option(WPS_OPTIONS_PREFIX."_gallery_show_resized") === FALSE)
		update_option(WPS_OPTIONS_PREFIX."_gallery_show_resized", 'on');
	if (get_option(WPS_OPTIONS_PREFIX."_gallery_thumbnail_size") === FALSE)
		update_option(WPS_OPTIONS_PREFIX."_gallery_thumbnail_size", 75);
	if (get_option(WPS_OPTIONS_PREFIX."_gallery_page_length") === FALSE)
		update_option(WPS_OPTIONS_PREFIX."_gallery_page_length", 10);
	if (get_option(WPS_OPTIONS_PREFIX."_gallery_preview") === FALSE)
		update_option(WPS_OPTIONS_PREFIX."_gallery_preview", 5);

	// Default forum ranks
	if (get_option(WPS_OPTIONS_PREFIX.'_forum_ranks') == '') {
		update_option(WPS_OPTIONS_PREFIX.'_forum_ranks', "on;Emperor;0;Monarch;200;Lord;150;Duke;125;Count;100;Earl;75;Viscount;50;Bishop;25;Baron;10;Knight;5;Peasant;0");
	}
			
			
$default_menu_structure = '[Profile]
View Profile=viewprofile
Profile Details=details
Community Settings=settings
Upload Avatar=avatar
[Activity]
My Activity=activitymy
Friends Activity=activityfriends
All Activity=activityall
[Social%f]
My Friends=myfriends
My Groups=mygroups
The Lounge=lounge
My @mentions=mentions
Who I am Following=following
My Followers=followers
[More]
My Events=events
My Gallery=gallery';

$default_menu_structure_other = '[Profile]
View Profile=viewprofile
Profile Details=details
Community Settings=settings
Upload Avatar=avatar
[Activity]
Activity=activitymy
Friends Activity=activityfriends
All Activity=activityall
[Social]
Friends=myfriends
Groups=mygroups
The Lounge=lounge
@mentions=mentions
Following=following
Followers=followers
[More]
Events=events
Gallery=gallery';

	if (get_option(WPS_OPTIONS_PREFIX.'_profile_menu_structure') === false) 
		update_option(WPS_OPTIONS_PREFIX.'_profile_menu_structure', $default_menu_structure);
	if (get_option(WPS_OPTIONS_PREFIX.'_profile_menu_structure_other') === false) 
		update_option(WPS_OPTIONS_PREFIX.'_profile_menu_structure_other', $default_menu_structure_other);			
	
	// Group menu text
	$default_menu_structure = '[Group]
Welcome=welcome
Settings=settings
Invite=invites
[Activity]
Group Activity=activity
Group Forum=forum
[Members]
Directory=members';

	if (get_option(WPS_OPTIONS_PREFIX.'_group_menu_structure') === false) 
		update_option(WPS_OPTIONS_PREFIX.'_group_menu_structure', $default_menu_structure);
	
	if (get_option(WPS_OPTIONS_PREFIX.'_template_profile_header') == '') {
		update_option(WPS_OPTIONS_PREFIX.'_template_profile_header', "<div id='profile_header_div'>[]<div id='profile_label'>[profile_label]</div>[]<div id='profile_header_panel'>[]<div id='profile_photo' class='corners'>[avatar,200]</div>[]<div id='profile_details'>[]<div id='profile_name'>[display_name]</div>[]<p>[location]<br />[born]</p>[]</div>[]</div>[]</div>[]<div id='profile_actions_div'>[actions][poke][follow]</div>");
	}
	if (get_option(WPS_OPTIONS_PREFIX.'_template_profile_body') == '') {
		update_option(WPS_OPTIONS_PREFIX.'_template_profile_body', "<div id='profile_wrapper'>[]<div id='force_profile_page' style='display:none'>[default]</div>[]<div id='profile_body_wrapper'>[]<div id='profile_body'>[page]</div>[]</div>[]<div id='profile_menu'>[menu]</div>[]</div>");
	}
	if (get_option(WPS_OPTIONS_PREFIX.'_template_page_footer') == '') {
		update_option(WPS_OPTIONS_PREFIX.'_template_page_footer', "<div id='powered_by_wps'>[]<a href='http://www.wpsymposium.com' target='_blank'>[powered_by_message] v[version]</a>[]</div>");
	}
	if (get_option(WPS_OPTIONS_PREFIX.'_template_mail_tray') == '') {
		update_option(WPS_OPTIONS_PREFIX.'_template_mail_tray', "<div class='bulk_actions'>[bulk_action]</div>[]<div id='mail_mid' class='mail_item mail_read'>[]<div class='mailbox_message_from'>[mail_from]</div>[]<div class='mail_item_age'>[mail_sent]</div>[]<div class='mailbox_message_subject'>[mail_subject]</div>[]<div class='mailbox_message'>[mail_message]</div>[]</div>");
	}
	if (get_option(WPS_OPTIONS_PREFIX.'_template_mail_message') == '') {
		update_option(WPS_OPTIONS_PREFIX.'_template_mail_message', "<div id='message_header'><div id='message_header_delete'>[reply_button][delete_button]</div><div id='message_header_avatar'>[avatar,44]</div>[mail_subject]<br />[mail_recipient] [mail_sent]</div><div id='message_mail_message'>[message]</div>");
	}
	if (get_option(WPS_OPTIONS_PREFIX.'_template_email') == '') {
		update_option(WPS_OPTIONS_PREFIX.'_template_email', "<style> body { background-color: #fff; } </style>[]<div style='margin: 20px;'>[][message][]<br /><hr />[][footer]<br />[]<a href='http://www.wpsymposium.com' target='_blank'>[powered_by_message] v[version]</a>[]</div>");
	}
	if (get_option(WPS_OPTIONS_PREFIX.'_template_forum_header') == '') {
		update_option(WPS_OPTIONS_PREFIX.'_template_forum_header', "[breadcrumbs][new_topic_button][new_topic_form][][digest][subscribe][][forum_options][][sharing]");
	}
	if (get_option(WPS_OPTIONS_PREFIX.'_template_group') == '') {
		update_option(WPS_OPTIONS_PREFIX.'_template_group', "<div id='group_header_div'><div id='group_header_panel'>[]<div id='group_details'>[]<div id='group_name'>[group_name]</div>[]<div id='group_description'>[group_description]</div>[]<div style='padding-top: 15px;padding-bottom: 15px;'>[actions]</div>[]</div></div>[]<div id='group_photo' class='corners'>[avatar,170]</div>[]</div>[]<div id='group_wrapper'>[]<div id='force_group_page' style='display:none'>[default]</div>[]<div id='group_body_wrapper'>[]<div id='group_body'>[page]</div>[]</div>[]<div id='group_menu'>[menu]</div>[]</div>");
	}
	if (get_option(WPS_OPTIONS_PREFIX.'_template_forum_category') == '') {
		update_option(WPS_OPTIONS_PREFIX.'_template_forum_category', "<div class='row_topic'>[category_title]<br />[category_desc]</div>[]<div class='row_startedby'>[]<div class='row_views'>[post_count]</div>[]<div class='row_topic row_replies'>[topic_count]</div>[]<div class='avatar avatar_last_topic'>[avatar,64]</div>[]<div class='last_topic_text'>[replied][subject][ago]<br />[subject_text]</div>[]</div>");
	}
	if (get_option(WPS_OPTIONS_PREFIX.'_template_forum_topic') == '') {
		update_option(WPS_OPTIONS_PREFIX.'_template_forum_topic', "<div class='avatar avatar_first_topic'>[avatarfirst,64]</div>[]<div class='first_topic'>[]<div class='row_views'>[views]</div>[]<div class='row_replies'>[replies]</div>[]<div class='row_topic'>[topic_title]</div>[]<div class='first_topic_text'>[startedby][started]</div>[]<div class='row_startedby'>[]<div class='last_reply'>[]<div class='avatar avatar_last_topic'>[avatar,48]</div>[]<div class='last_topic_text'>[replied][ago].<br />[topic]</div>[]</div>[]</div></div>");
	}
	if (get_option(WPS_OPTIONS_PREFIX.'_template_group_forum_category') == '') {
		update_option(WPS_OPTIONS_PREFIX.'_template_group_forum_category', "<div class='avatar avatar_first_topic'>[avatarfirst,64]</div>[]<div class='first_topic'>[]<div class='row_views'>[views]</div>[]<div class='row_replies'>[replies]</div>[]<div class='row_topic'>[topic_title]</div>[]<div class='first_topic_text'>[startedby][started]</div>[]<div class='row_startedby'>[]<div class='last_reply'>[]<div class='avatar avatar_last_topic'>[avatar,48]</div>[]<div class='last_topic_text'>[replied][ago].<br />[topic]</div>[]</div>[]</div></div>");
	}
	if (get_option(WPS_OPTIONS_PREFIX.'_template_group_forum_topic') == '') {
		update_option(WPS_OPTIONS_PREFIX.'_template_group_forum_topic', "<div class='avatar avatar_first_topic'>[avatarfirst,64]</div>[]<div class='first_topic'>[]<div class='row_views'>[views]</div>[]<div class='row_replies'>[replies]</div>[]<div class='row_topic'>[topic_title]</div>[]<div class='first_topic_text'>[startedby][started]</div>[]<div class='row_startedby'>[]<div class='last_reply'>[]<div class='avatar avatar_last_topic'>[avatar,48]</div>[]<div class='last_topic_text'>[replied][ago].<br />[topic]</div>[]</div>[]</div></div>");
	}

	


		
?>