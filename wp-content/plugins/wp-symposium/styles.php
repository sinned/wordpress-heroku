<?php


	// Set dynamic styles

	global $wpdb;
	
	echo "<!-- ".WPS_WL." styles -->";
	echo "<style>";
	
	echo '.mceStatusbar { display:none !important; }';
	
	if (get_option(WPS_OPTIONS_PREFIX.'_hide_news_list') == 'on')
		echo '#__wps__news_items { display:none !important; }';

	$wp_width = get_option(WPS_OPTIONS_PREFIX.'_wp_width');
	if ($wp_width == '') { $wp_width = '100pc'; }
	$wp_alignment = get_option(WPS_OPTIONS_PREFIX.'_wp_alignment');

	echo ".__wps__wrapper {";
	if ($wp_alignment == 'Center') {
		echo "margin: 0 auto;";
	}
	if ($wp_alignment == 'Left' || $wp_alignment == 'Right') {
		echo "clear: both;";
		echo "margin: 0;";
		echo "float: ".strtolower($wp_alignment).";";
	}
	echo "  width: ".str_replace('pc', '%', $wp_width).";";
	echo "}";

	if (get_option(WPS_OPTIONS_PREFIX.'_use_styles') == "on") {
	
		$border_radius = get_option(WPS_OPTIONS_PREFIX.'_border_radius');
		$bigbutton_background = get_option(WPS_OPTIONS_PREFIX.'_bigbutton_background');
		$bigbutton_color = get_option(WPS_OPTIONS_PREFIX.'_bigbutton_color');
		$bigbutton_background_hover = get_option(WPS_OPTIONS_PREFIX.'_bigbutton_background_hover');
		$bigbutton_color_hover = get_option(WPS_OPTIONS_PREFIX.'_bigbutton_color_hover');
		$primary_color = get_option(WPS_OPTIONS_PREFIX.'_bg_color_1');
		$row_color = get_option(WPS_OPTIONS_PREFIX.'_bg_color_2');
		$row_color_alt = get_option(WPS_OPTIONS_PREFIX.'_bg_color_3');
		$text_color = get_option(WPS_OPTIONS_PREFIX.'_text_color');
		$text_color_2 = get_option(WPS_OPTIONS_PREFIX.'_text_color_2');
		$link = get_option(WPS_OPTIONS_PREFIX.'_link');
		$underline = get_option(WPS_OPTIONS_PREFIX.'_underline');
		$link_hover = get_option(WPS_OPTIONS_PREFIX.'_link_hover');
		$table_rollover = get_option(WPS_OPTIONS_PREFIX.'_table_rollover');
		$table_border = get_option(WPS_OPTIONS_PREFIX.'_table_border');
		$replies_border_size = get_option(WPS_OPTIONS_PREFIX.'_replies_border_size');
		$row_border_style = get_option(WPS_OPTIONS_PREFIX.'_row_border_style');
		$row_border_size = get_option(WPS_OPTIONS_PREFIX.'_row_border_size');
		$label = get_option(WPS_OPTIONS_PREFIX.'_label');
		$__wps__categories_background = get_option(WPS_OPTIONS_PREFIX.'_categories_background');
		$categories_color = get_option(WPS_OPTIONS_PREFIX.'_categories_color');
		$main_background = get_option(WPS_OPTIONS_PREFIX.'_main_background');
		$closed_opacity = get_option(WPS_OPTIONS_PREFIX.'_closed_opacity');
		$fontfamily = stripslashes(get_option(WPS_OPTIONS_PREFIX.'_fontfamily'));
		$fontsize = get_option(WPS_OPTIONS_PREFIX.'_fontsize');
		$headingsfamily = stripslashes(get_option(WPS_OPTIONS_PREFIX.'_headingsfamily'));
		$headingssize = get_option(WPS_OPTIONS_PREFIX.'_headingssize');

		$style = "";

		$style .= ".__wps__wrapper, 
					.__wps__wrapper p, 
					.__wps__wrapper li, 
					.__wps__wrapper td, 
					.__wps__wrapper div,
					.__wps__wrapper input[type=text], 
					.__wps__wrapper input[type=password], 
					.__wps__wrapper textarea, 
					.popup, 
					.ui-widget,
					.ui-dialog,
					 .__wps__mail_recipient_list_option
				    {".PHP_EOL;
		$style .= "	font-size: ".$fontsize."px;".PHP_EOL;
		$style .= "	color: ".$text_color.";".PHP_EOL;
		$style .= " text-shadow: none;".PHP_EOL;
		$style .= "}".PHP_EOL;
		
		$style .= ".__wps__wrapper div, .widget-area  {".PHP_EOL;
		$style .= "	font-family: ".$fontfamily.";".PHP_EOL;
		$style .= "	color: ".$text_color.";".PHP_EOL;
		$style .= "}".PHP_EOL;
		$style .= "#profile_menu div, #profile_header_panel div, #profile_body_wrapper div, .child-reply-post p, .topic-post-post p {".PHP_EOL;
		$style .= "	font-family: ".$fontfamily." !important;".PHP_EOL;
		$style .= "	color: ".$text_color."!important;".PHP_EOL;
		$style .= "}".PHP_EOL;
		
		$style .= ".__wps__wrapper, #mail_recipient_list, .__wps__mail_recipient_list_option {".PHP_EOL;
		$style .= "	background-color: ".$main_background.";".PHP_EOL;
		$style .= "}".PHP_EOL;

		$style .= ". {".PHP_EOL;
		$style .= "	color: ".$text_color." !important;".PHP_EOL;
		$style .= "}".PHP_EOL;

		$style .= ".__wps__wrapper a:link, .__wps__wrapper a:visited, .__wps__wrapper a:active,
					.widget-area a:link, .widget-area a:visited, .widget-area a:active
					{".PHP_EOL;
		$style .= "	color: ".$link." !important;".PHP_EOL;
		$style .= "	font-weight: normal !important;".PHP_EOL;
		if ($underline == "on") {
			$style .= "	text-decoration: underline !important;".PHP_EOL;
		} else {
			$style .= "	text-decoration: none !important;".PHP_EOL;
		}
		$style .= "}".PHP_EOL;

		$style .= ".__wps__wrapper a:hover {".PHP_EOL;
		$style .= "	color: ".$link_hover." !important;".PHP_EOL;
		$style .= "}".PHP_EOL;

		$style .= "body img, body input, .corners, .__wps__wrapper .row, .__wps__wrapper .reply_div, .__wps__wrapper .row_odd, .__wps__wrapper #starting-post, .__wps__wrapper .child-reply, .__wps__wrapper #profile_label {".PHP_EOL;
		$style .= "	border-radius: ".$border_radius."px !important;".PHP_EOL;
		$style .= "	-moz-border-radius: ".$border_radius."px !important;".PHP_EOL;
		$style .= "}".PHP_EOL;

		$style .= ".__wps__wrapper .label {".PHP_EOL;
		$style .= "  color: ".$label." !important;".PHP_EOL;
		$style .= "}".PHP_EOL;

		// Profile 
		$style .= ".__wps__wrapper #__wps__profile_right_column, .popup {".PHP_EOL;
		$style .= "	background-color: ".$main_background." !important;".PHP_EOL;
		$style .= "	border: ".$replies_border_size."px solid ".$primary_color." !important;".PHP_EOL;	
		$style .= "}".PHP_EOL;
		
		$style .= ".__wps__wrapper #__wps__comment, .__wps__wrapper .__wps__reply {".PHP_EOL;
		$style .= "	border: 1px solid ".$primary_color." ;".PHP_EOL;	
		$style .= "	border-radius: ".$border_radius."px;".PHP_EOL;	
		$style .= "}".PHP_EOL;
		
		// Forum or Tables (layout)

		$style .= ".__wps__wrapper #__wps__table {".PHP_EOL;
		$style .= "	border: ".$table_border."px solid ".$primary_color.";".PHP_EOL;	
		$style .= "}".PHP_EOL;
	
		$style .= ".__wps__wrapper .table_header {".PHP_EOL;
		$style .= "	background-color: ".$__wps__categories_background.";".PHP_EOL;
		$style .= "  font-weight: bold;".PHP_EOL;
	 	$style .= "  border-radius:0px;".PHP_EOL;
		$style .= "  -moz-border-radius:0px;".PHP_EOL;
		$style .= "  border: 0px".PHP_EOL;
	 	$style .= "  border-top-left-radius:".($border_radius-5)."px;".PHP_EOL;
		$style .= "  -moz-border-radius-topleft:".($border_radius-5)."px;".PHP_EOL;
	 	$style .= "  border-top-right-radius:".($border_radius-5)."px;".PHP_EOL;
		$style .= "  -moz-border-radius-topright:".($border_radius-5)."px;".PHP_EOL;
		$style .= "}".PHP_EOL;

		$style .= ".__wps__wrapper .table_topic, .__wps__wrapper #profile_name, .__wps__wrapper .topic-post-header {".PHP_EOL;
		$style .= "	font-family: ".$headingsfamily." !important;".PHP_EOL;
		$style .= "	font-size: ".$headingssize." !important;".PHP_EOL;
		$style .= "}".PHP_EOL;

		$style .= ".__wps__wrapper .table_topic {".PHP_EOL;
		$style .= "	color: ".$categories_color.";".PHP_EOL;
		$style .= "}".PHP_EOL;

		$style .= ".__wps__wrapper .table_topic:hover {".PHP_EOL;
		$style .= "	background-color: ".$table_rollover." !important;".PHP_EOL;
		$style .= "}".PHP_EOL;
		
		$style .= ".__wps__wrapper .row a, .__wps__wrapper .row_odd a {".PHP_EOL;
		if ($underline == "on") {
			$style .= "	text-decoration: underline;".PHP_EOL;
		} else {
			$style .= "	text-decoration: none;".PHP_EOL;
		}
		$style .= "}".PHP_EOL;
	
		$style .= ".__wps__wrapper .new-topic-subject-input, .__wps__wrapper .input-field, .__wps__wrapper #mail_recipient_list {".PHP_EOL;
		$style .= "	font-family: ".$fontfamily.";".PHP_EOL;
		$style .= "	border: ".$replies_border_size."px solid ".$primary_color.";".PHP_EOL;	
		$style .= "}".PHP_EOL;

		$style .= ".__wps__wrapper .new-topic-subject-text, .__wps__wrapper .reply-topic-subject-text, .__wps__wrapper .reply-topic-text {".PHP_EOL;
		$style .= "	font-family: ".$fontfamily.";".PHP_EOL;
		$style .= "}".PHP_EOL;
	
		$style .= ".__wps__wrapper #reply-topic {".PHP_EOL;
		$style .= "	border: ".$replies_border_size."px solid ".$primary_color.";".PHP_EOL;	
		$style .= "}".PHP_EOL;

		$style .= ".__wps__wrapper #reply-topic-bottom textarea {".PHP_EOL;
		$style .= "	border: 1px solid ".$primary_color.";".PHP_EOL;			
		$style .= "}".PHP_EOL;
		
		$style .= ".__wps__wrapper #new-topic-link, .__wps__wrapper #reply-topic-link, .__wps__wrapper .__wps__button,  .__wps__button, .__wps__wrapper .__wps__btn, .__wps__btn {".PHP_EOL;
		$style .= "	font-family: ".$fontfamily." !important;".PHP_EOL;
		$style .= "	font-size: ".$fontsize."px !important;".PHP_EOL;
		$style .= "	background-color: ".$bigbutton_background." !important;".PHP_EOL;
		$style .= "	color: ".$bigbutton_color." !important;".PHP_EOL;
		$style .= "}".PHP_EOL;
	
		$style .= ".__wps__wrapper #new-topic-link:hover, .__wps__wrapper #reply-topic-link:hover, .__wps__wrapper .__wps__button:hover,  .__wps__button:hover, .__wps__wrapper .__wps__btn:hover,  .__wps__btn:hover {".PHP_EOL;
		$style .= "	background-color: ".$bigbutton_background_hover." !important;".PHP_EOL;
		$style .= "	color: ".$bigbutton_color_hover." !important;".PHP_EOL;
		$style .= "}".PHP_EOL;
		$style .= ".__wps__wrapper #new-topic-link:active, .__wps__wrapper #reply-topic-link:active, .__wps__wrapper .__wps__button:active,  .__wps__button:active, .__wps__wrapper .__wps__btn:active,  .__wps__btn:active {".PHP_EOL;
		$style .= "	background-color: ".$bigbutton_background_hover." !important;".PHP_EOL;
		$style .= "	color: ".$bigbutton_color_hover." !important;".PHP_EOL;
		$style .= "}".PHP_EOL;
						
		$style .= ".__wps__wrapper .round_bottom_left {".PHP_EOL;
	 	$style .= "  border-bottom-left-radius:".($border_radius-5)."px;".PHP_EOL;
		$style .= "  -moz-border-radius-bottomleft:".($border_radius-5)."px;".PHP_EOL;
		$style .= "}".PHP_EOL;
		
		$style .= ".__wps__wrapper .round_bottom_right {".PHP_EOL;
	 	$style .= "  border-bottom-right-radius:".($border_radius-5)."px;".PHP_EOL;
		$style .= "  -moz-border-radius-bottomright:".($border_radius-5)."px;".PHP_EOL;
		$style .= "}".PHP_EOL;
		
		$style .= ".__wps__wrapper .categories_color {".PHP_EOL;
		$style .= "	color: ".$categories_color.";".PHP_EOL;
		$style .= "}";
		$style .= ".__wps__wrapper .__wps__categories_background {".PHP_EOL;
		$style .= "	background-color: ".$__wps__categories_background.";".PHP_EOL;
		$style .= "}".PHP_EOL;
		
		$style .= ".__wps__wrapper .row, .__wps__wrapper .reply_div {".PHP_EOL;
		$style .= "	background-color: ".$row_color." !important;".PHP_EOL;
		$style .= "}".PHP_EOL;

		$style .= ".__wps__wrapper .wall_reply, .__wps__wrapper .__wps__wall_reply_div, .__wps__wrapper .wall_reply_avatar, .__wps__wrapper a, ";
		$style .= ".__wps__wrapper .mailbox_message_subject, .__wps__wrapper .mailbox_message_from, .__wps__wrapper .mail_item_age, .__wps__wrapper .mailbox_message, ";
		$style .= ".__wps__wrapper .row_views ";
		$style .= " {".PHP_EOL;
		$style .= "	background-color: transparent;".PHP_EOL;
		$style .= "}".PHP_EOL;
			
			
		$style .= ".__wps__wrapper .row_odd {".PHP_EOL;
		$style .= "	background-color: ".$row_color_alt." !important;".PHP_EOL;
		$style .= "}".PHP_EOL;
	
		$style .= ".__wps__wrapper .row:hover, .__wps__wrapper .row_odd:hover {".PHP_EOL;
		$style .= "	background-color: ".$table_rollover." !important;".PHP_EOL;
		$style .= "}".PHP_EOL;
		
		$style .= ".__wps__wrapper .row_link, .__wps__wrapper .edit, .__wps__wrapper .delete {".PHP_EOL;
		$style .= "	font-size: ".$headingssize." !important;".PHP_EOL;
		$style .= "	color: ".$link." !important;".PHP_EOL;
		$style .= "}".PHP_EOL;
			
		$style .= ".__wps__wrapper .row_link:hover {".PHP_EOL;
		$style .= "	color: ".$link_hover." !important;".PHP_EOL;
		$style .= "}".PHP_EOL;
	
		$style .= ".__wps__wrapper #starting-post {".PHP_EOL;
		$style .= "	border: ".$replies_border_size."px solid ".$primary_color.";".PHP_EOL;
		$style .= "	background-color: ".$row_color.";".PHP_EOL;
		$style .= "}".PHP_EOL;
							
		$style .= ".__wps__wrapper #starting-post, .__wps__wrapper #child-posts {".PHP_EOL;
		$style .= "	border: ".$replies_border_size."px solid ".$primary_color.";".PHP_EOL;
		$style .= "}".PHP_EOL;
		
		$style .= ".__wps__wrapper .child-reply {".PHP_EOL;
		$style .= "	border-bottom: ".$replies_border_size."px dotted ".$text_color_2.";".PHP_EOL;
		$style .= "	background-color: ".$row_color_alt.";".PHP_EOL;
		$style .= "}".PHP_EOL;
		
		$style .= ".__wps__wrapper .sep, .__wps__wrapper .sep_top {".PHP_EOL;
		$style .= "	clear:both;".PHP_EOL;
		$style .= "	width:100%;".PHP_EOL;
		$style .= "	border-bottom: ".$replies_border_size."px ".$row_border_style." ".$text_color_2.";".PHP_EOL;
		$style .= "}".PHP_EOL;
		$style .= ".__wps__wrapper .sep_top {".PHP_EOL;
		$style .= "	border-bottom: 0px ;".PHP_EOL;
		$style .= "	border-top: ".$replies_border_size."px ".$row_border_style." ".$text_color_2.";".PHP_EOL;
		$style .= "}".PHP_EOL;
			
		// Alerts
		
		$style .= ".__wps__wrapper .alert {".PHP_EOL;
		$style .= "	clear:both;".PHP_EOL;
		$style .= "	padding:6px;".PHP_EOL;
		$style .= "	margin-bottom:15px;".PHP_EOL;
		$style .= "	border: 1px solid #666;".PHP_EOL;	
		$style .= "	background-color: #eee;".PHP_EOL;
		$style .= "	color: #000;".PHP_EOL;
		$style .= "}".PHP_EOL;

		$style .= ".__wps__wrapper .transparent {".PHP_EOL;
		$style .= '  -ms-filter: "progid: DXImageTransform.Microsoft.Alpha(Opacity='.($closed_opacity*100).')";'.PHP_EOL;
		$style .= "  filter: alpha(opacity=".($closed_opacity*100).");".PHP_EOL;
		$style .= "  -moz-opacity: ".$closed_opacity.";".PHP_EOL;
		$style .= "  -khtml-opacity: ".$closed_opacity.";".PHP_EOL;
		$style .= "  opacity: ".$closed_opacity.";".PHP_EOL;
		$style .= "}".PHP_EOL;
		
		// Menu Tabs
		$style .= "ul.__wps__dropdown li {".PHP_EOL;
		$style .= "  border-color: ".$text_color." !important;".PHP_EOL;
		$style .= "  color: ".$text_color." !important;".PHP_EOL;		
		$style .= "}".PHP_EOL;
		$style .= "ul.__wps__dropdown ul {".PHP_EOL;
		$style .= "  background-color: ".$row_color." !important;".PHP_EOL;		
		$style .= "}".PHP_EOL;
		$style .= "ul.__wps__dropdown ul li {".PHP_EOL;
		$style .= "  background-color: ".$row_color." !important;".PHP_EOL;		
		$style .= "  color: ".$text_color." !important;".PHP_EOL;		
		$style .= "}".PHP_EOL;
		$style .= "ul.__wps__dropdown li.__wps__dropdown_tab_on  {".PHP_EOL;
		$style .= "  border-bottom-color: ".$main_background." !important;".PHP_EOL;
		$style .= "}".PHP_EOL;
		$style .= "ul.__wps__dropdown li.__wps__dropdown_tab_off  {".PHP_EOL;
		$style .= "  border-bottom-color: ".$text_color." !important;".PHP_EOL;
		$style .= "}".PHP_EOL;
		$style .= "#__wps__menu_tabs_wrapper {".PHP_EOL;
		$style .= "  border-top:1px solid ".$text_color." !important;".PHP_EOL;
		$style .= "}".PHP_EOL;
		
		
					
		echo $style;
				
	}

	// Apply advanced CSS (via WP Admin Menu -> Styles -> CSS)	
	if (get_option(WPS_OPTIONS_PREFIX.'_css') != '') {
		echo "/* ".WPS_WL." custom styles */";
		echo str_replace("[]", chr(13), stripslashes(get_option(WPS_OPTIONS_PREFIX.'_css')));
	}

	// Apply responsive CSS (via WP Admin Menu -> Styles -> Responsive)	
	if (get_option(WPS_OPTIONS_PREFIX.'_responsive') != '') {
		echo "/* ".WPS_WL." responsive styles */";
		echo str_replace("[]", chr(13), stripslashes(get_option(WPS_OPTIONS_PREFIX.'_responsive')));
	}



	echo "</style>";
	echo "<!-- End ".WPS_WL." styles -->";

?>
