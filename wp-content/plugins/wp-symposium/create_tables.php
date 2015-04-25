<?php

// Create Audit tables
$table_name = $wpdb->base_prefix . "symposium_audit";
if($wpdb->get_var("show tables like '$table_name'") != $table_name) {

	$sql = "CREATE TABLE " . $table_name . " (
	  aid int(11) NOT NULL AUTO_INCREMENT,
	  type varchar(255) NOT NULL,
	  blog_id int(11) NOT NULL DEFAULT 0,
	  user_id int(11) NOT NULL,
	  current_user_id int(11) NOT NULL,
	  meta varchar(255) NOT NULL,
	  value varchar(255) NOT NULL,
	  action varchar(255) NOT NULL,
	  timestamp datetime NOT NULL,
	  PRIMARY KEY (aid)
	) CHARACTER SET utf8 COLLATE utf8_general_ci;";

    dbDelta($sql);

}

// Create Plus tables
$table_name = $wpdb->base_prefix . "symposium_following";
if($wpdb->get_var("show tables like '$table_name'") != $table_name) {

	$sql = "CREATE TABLE " . $table_name . " (
	  fid int(11) NOT NULL AUTO_INCREMENT,
	  uid int(11) NOT NULL DEFAULT 0,
	  following int(11) NOT NULL,
	  created datetime NOT NULL,
	  PRIMARY KEY (fid)
	) CHARACTER SET utf8 COLLATE utf8_general_ci;";

    dbDelta($sql);

}


// Notification Alerts
$table_name = $wpdb->base_prefix . "symposium_news";
if($wpdb->get_var("show tables like '$table_name'") != $table_name) {

	$sql = "CREATE TABLE " . $table_name . " (
	  nid int(11) NOT NULL AUTO_INCREMENT,
	  author int(11) NOT NULL DEFAULT 0,
	  subject int(11) NOT NULL DEFAULT 0,
	  added datetime NOT NULL,
	  news text NOT NULL,
	  new_item varchar(2) NOT NULL DEFAULT 'on',
	  PRIMARY KEY (nid)
	) CHARACTER SET utf8 COLLATE utf8_general_ci;";

    dbDelta($sql);

}


// Create Lounge table
$table_name = $wpdb->base_prefix . "symposium_lounge";
if($wpdb->get_var("show tables like '$table_name'") != $table_name) {

	$sql = "CREATE TABLE " . $table_name . " (
	  lid int(11) NOT NULL AUTO_INCREMENT,
	  author int(11) NOT NULL DEFAULT 0,
	  added datetime NOT NULL,
	  comment text NOT NULL,
	  PRIMARY KEY (lid)
	) CHARACTER SET utf8 COLLATE utf8_general_ci;";

    dbDelta($sql);

}

// Create groups table
$table_name = $wpdb->prefix . "symposium_groups";
if($wpdb->get_var("show tables like '$table_name'") != $table_name) {

	$sql = "CREATE TABLE " . $table_name . " (
	  gid int(11) NOT NULL AUTO_INCREMENT,
	  name varchar(256) NOT NULL,
	  description text NOT NULL,
	  last_activity datetime NOT NULL,
	  private varchar(2) NOT NULL,
	  created datetime NOT NULL,
	  forum varchar(2) NOT NULL,
	  photos varchar(2) NOT NULL,
	  wall varchar(2) NOT NULL,
	  content_private varchar(2) NOT NULL,
	  group_avatar MEDIUMBLOB NOT NULL,		  
	  PRIMARY KEY (gid)
	) CHARACTER SET utf8 COLLATE utf8_general_ci;";

    dbDelta($sql);

}

// Create group members table
$table_name = $wpdb->prefix . "symposium_group_members";
if($wpdb->get_var("show tables like '$table_name'") != $table_name) {

	$sql = "CREATE TABLE " . $table_name . " (
	  gmid int(11) NOT NULL AUTO_INCREMENT,
	  group_id int(11) NOT NULL,
	  member_id int(11) NOT NULL,
	  admin varchar(2) NOT NULL,
	  valid varchar(2) NOT NULL,
	  joined datetime NOT NULL,
	  notify varchar(2) NOT NULL DEFAULT 'on',
	  PRIMARY KEY (gmid)
	) CHARACTER SET utf8 COLLATE utf8_general_ci;";

    dbDelta($sql);

}




// Gallery
$table_name = $wpdb->base_prefix . "symposium_gallery";
if($wpdb->get_var("show tables like '$table_name'") != $table_name) {

	$sql = "CREATE TABLE " . $table_name . " (
	  gid int(11) NOT NULL AUTO_INCREMENT,
	  parent_gid int(11) NOT NULL DEFAULT 0,
	  name varchar(256) NOT NULL DEFAULT 'My album',
	  description text NOT NULL,
	  owner int(11) NOT NULL,
	  sharing varchar(16) NOT NULL DEFAULT 'friends',
	  editing varchar(16) NOT NULL DEFAULT 'owner',
	  created datetime NOT NULL,
	  updated datetime NOT NULL,
	  is_group varchar(2) NOT NULL,
	  PRIMARY KEY (gid)
	) CHARACTER SET utf8 COLLATE utf8_general_ci;";

    dbDelta($sql);

}

// Create gallery item table
$table_name = $wpdb->base_prefix . "symposium_gallery_items";
if($wpdb->get_var("show tables like '$table_name'") != $table_name) {

	$sql = "CREATE TABLE " . $table_name . " (
	  iid int(11) NOT NULL AUTO_INCREMENT,
	  gid int(11),
	  owner int(11) NOT NULL,
	  name varchar(256) NOT NULL,
	  created datetime NOT NULL,
	  cover varchar(2) NOT NULL,
	  original MEDIUMBLOB NOT NULL,		  
	  photo MEDIUMBLOB NOT NULL,		  
	  thumbnail MEDIUMBLOB NOT NULL,
	  groupid int(11) NOT NULL,
	  title varchar(256) NOT NULL,
	  photo_order int(11) NOT NULL,
	  PRIMARY KEY (iid)
	) CHARACTER SET utf8 COLLATE utf8_general_ci;";

    dbDelta($sql);	
}



// Events
$table_name = $wpdb->base_prefix . "symposium_events";
if($wpdb->get_var("show tables like '$table_name'") != $table_name) {

	$sql = "CREATE TABLE " . $table_name . " (
	  eid int(11) NOT NULL AUTO_INCREMENT,
	  event_name varchar(256) NOT NULL,
	  event_description text,
	  event_location varchar(256),
	  event_url varchar(256),
	  event_google_map varchar(2),
	  event_created datetime NOT NULL,
	  event_start datetime NOT NULL,
	  event_start_hours int(11) NOT NULL,
	  event_start_minutes int(11) NOT NULL,
	  event_end datetime NOT NULL,
	  event_end_hours int(11) NOT NULL,
	  event_end_minutes int(11) NOT NULL,
	  event_owner int(11) NOT NULL,
	  event_group int(11),
	  event_list_template text,
	  event_item_template text,
	  event_live varchar(2) NOT NULL DEFAULT 'on',
	  event_enable_places varchar(2),
	  event_max_places int(11),
	  event_show_max varchar(2),
	  event_tickets_per_booking int(11),
	  event_confirmation varchar(2),
	  event_send_email varchar(2),
	  event_email text,
	  event_cost varchar(16),
	  event_pay_link varchar(2048),
	  event_more text,
	  PRIMARY KEY (eid)
	) CHARACTER SET utf8 COLLATE utf8_general_ci;";

    dbDelta($sql);
}

$table_name = $wpdb->base_prefix . "symposium_events_bookings";
if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
	$sql = "CREATE TABLE " . $table_name . " (
	  bid int(11) NOT NULL AUTO_INCREMENT,
	  uid int(11) NOT NULL,
	  event_id int(11) NOT NULL,
	  confirmed varchar(2) NOT NULL,
	  booked datetime,
	  email_sent datetime,
	  payment_processed datetime,
	  tickets int(11),
	  PRIMARY KEY (bid)
	) CHARACTER SET utf8 COLLATE utf8_general_ci;";

    dbDelta($sql);
}


// Create like/dislike table
$table_name = $wpdb->base_prefix . "symposium_likes";
if($wpdb->get_var("show tables like '$table_name'") != $table_name) {

	$sql = "CREATE TABLE " . $table_name . " (
	vid int(11) NOT NULL AUTO_INCREMENT,
	type varchar(16) NOT NULL,
	cid int(11) NOT NULL,
	uid int(11) NOT NULL,
	liked_date datetime NOT NULL,
	PRIMARY KEY  vid (vid)
 	) CHARACTER SET utf8 COLLATE utf8_general_ci;";

	dbDelta($sql);

}

// Create voting table
$table_name = $wpdb->prefix . "symposium_topics_scores";
if($wpdb->get_var("show tables like '$table_name'") != $table_name) {

	$sql = "CREATE TABLE " . $table_name . " (
	sid int(11) NOT NULL AUTO_INCREMENT,
	tid int(11) NOT NULL,
	uid int(11) NOT NULL,
	score int(11) NOT NULL,
	topic_date datetime NOT NULL,
	PRIMARY KEY  sid (sid)
 	) CHARACTER SET utf8 COLLATE utf8_general_ci;";

	dbDelta($sql);

}


// Create tmp image upload table (if storing images in database)
$table_name = $wpdb->prefix . "symposium_topics_images";
if($wpdb->get_var("show tables like '$table_name'") != $table_name) {

	$sql = "CREATE TABLE " . $table_name . " (
	tmpid int(11) NOT NULL AUTO_INCREMENT,
	uid int(11) NOT NULL DEFAULT '0',
	tid int(11) DEFAULT '0',
	filename varchar(256) NOT NULL,
	upload mediumblob,
	PRIMARY KEY  tmpid (tmpid)
 	) CHARACTER SET utf8 COLLATE utf8_general_ci;";

	dbDelta($sql);

}

// Create Categories
$table_name = $wpdb->prefix . "symposium_cats";
if($wpdb->get_var("show tables like '$table_name'") != $table_name) {

	$sql = "CREATE TABLE " . $table_name . " (
	cid int(11) NOT NULL AUTO_INCREMENT,
	title varchar(64) NOT NULL,
	listorder int(11) NOT NULL DEFAULT '0',
	allow_new varchar(2) NOT NULL DEFAULT 'on',
	defaultcat varchar(2) NOT NULL DEFAULT '',
	PRIMARY KEY  cid (cid)
 	) CHARACTER SET utf8 COLLATE utf8_general_ci;";

	dbDelta($sql);

	$rows_affected = $wpdb->insert( $table_name, array( 'title' => 'General Topics' ) );
	$new_category_id = $wpdb->insert_id;
	$rows_affected = $wpdb->insert( $table_name, array( 'title' => 'Support Issues' ) );
	$rows_affected = $wpdb->insert( $table_name, array( 'title' => 'Feedback' ) );
} 

 
// Create Subscriptions
$table_name = $wpdb->prefix . "symposium_subs";
if($wpdb->get_var("show tables like '$table_name'") != $table_name) {

	$sql = "CREATE TABLE " . $table_name . " (
	sid int(11) NOT NULL AUTO_INCREMENT,
	uid int(11) NOT NULL,
	tid int(11) NOT NULL,
	cid int(11) NOT NULL,
	PRIMARY KEY  sid (sid)
 	) CHARACTER SET utf8 COLLATE utf8_general_ci;";

	dbDelta($sql);

}

// Create Comments (including status)
$table_name = $wpdb->base_prefix . "symposium_comments";
if($wpdb->get_var("show tables like '$table_name'") != $table_name) {

	$sql = "CREATE TABLE " . $table_name . " (
	cid int(11) NOT NULL AUTO_INCREMENT,
	subject_uid int(11) NOT NULL,
	author_uid int(11) NOT NULL,
	comment_parent int(11) NOT NULL,
	comment_timestamp timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	comment text,
	PRIMARY KEY  cid (cid)
 	) CHARACTER SET utf8 COLLATE utf8_general_ci;";

	dbDelta($sql);

}

// Create Topics
$table_name = $wpdb->prefix . "symposium_topics";
if($wpdb->get_var("show tables like '$table_name'") != $table_name) {

	$sql = "CREATE TABLE " . $table_name . " (
	tid int(11) NOT NULL AUTO_INCREMENT,
	topic_group int(11) NOT NULL DEFAULT '0',
	topic_category int(11) NOT NULL DEFAULT '0',
	topic_subject varchar(256) NOT NULL,
	topic_post text NOT NULL,
	topic_owner int(11) NOT NULL,
	topic_date datetime NOT NULL,
	topic_parent int(11) NOT NULL,
	topic_views int(11) NOT NULL,
	topic_started datetime NOT NULL,
	topic_sticky int(11) NOT NULL DEFAULT '0',
	PRIMARY KEY  tid (tid)
	) CHARACTER SET utf8 COLLATE utf8_general_ci;";

	dbDelta($sql);
	
	if (!__wps__is_wpmu()) {

		$rows_affected = $wpdb->insert( $table_name, array( 
		'topic_category' => $new_category_id, 
		'topic_subject' => 'Welcome to the Forum', 
		'topic_post' => 'Welcome to the forum - this is a demonstration post and can be deleted.',
		'topic_owner' => 1,
		'topic_date' => date("Y-m-d H:i:s"),
		'topic_views' => 0,
		'topic_parent' => 0,
		'topic_started' => date("Y-m-d H:i:s")
		 ) );
		 
		$new_topic_id = $wpdb->insert_id;
		$rows_affected = $wpdb->insert( $table_name, array( 
		'topic_category' => $new_category_id, 
		'topic_subject' => '', 
		'topic_post' => 'This is a demonstration reply.',
		'topic_owner' => 1,
		'topic_date' => date("Y-m-d H:i:s"),
		'topic_views' => 0,
		'topic_parent' => $new_topic_id,
		'topic_started' => date("Y-m-d H:i:s")
		 ) );
		$rows_affected = $wpdb->insert( $table_name, array( 
		'topic_category' => $new_category_id, 
		'topic_subject' => '', 
		'topic_post' => 'This is another demonstration reply.',
		'topic_owner' => 1,
		'topic_date' => date("Y-m-d H:i:s"),
		'topic_views' => 0,
		'topic_parent' => $new_topic_id,
		'topic_started' => date("Y-m-d H:i:s")
		 ) );
	}

} 	




// Create users meta table
$table_name = $wpdb->prefix . "symposium_usermeta";
if($wpdb->get_var("show tables like '$table_name'") != $table_name) {

	$sql = "CREATE TABLE " . $table_name . " (
	mid int(11) NOT NULL AUTO_INCREMENT,
	uid int(11) NOT NULL,
	profile_avatar mediumblob,
	PRIMARY KEY  mid (mid)
 	) CHARACTER SET utf8 COLLATE utf8_general_ci;";

    dbDelta($sql);

} 	


// Create extended usermeta table
$table_name = $wpdb->base_prefix . "symposium_extended";
if($wpdb->get_var("show tables like '$table_name'") != $table_name) {

	$sql = "CREATE TABLE " . $table_name . " (
	eid int(11) NOT NULL AUTO_INCREMENT,
	extended_name varchar(64) NOT NULL DEFAULT 'New field',
	extended_type varchar(16) NOT NULL DEFAULT 'Text',
	extended_default text,
	extended_order int(11) NOT NULL DEFAULT '0',
	PRIMARY KEY  eid (eid)
 	) CHARACTER SET utf8 COLLATE utf8_general_ci;";

    dbDelta($sql);

} 	

// Create friends table
$table_name = $wpdb->base_prefix . "symposium_friends";
if($wpdb->get_var("show tables like '$table_name'") != $table_name) {

	$sql = "CREATE TABLE " . $table_name . " (
	fid int(11) NOT NULL AUTO_INCREMENT,
	friend_from int(11) NOT NULL,
	friend_to int(11) NOT NULL,
	friend_accepted varchar(2) NOT NULL,
	friend_message text NOT NULL,
	friend_timestamp timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY  fid (fid)
 	) CHARACTER SET utf8 COLLATE utf8_general_ci;";

    dbDelta($sql);

} 	


// Create chat tables
$table_name = $wpdb->base_prefix . "symposium_chat2";
if($wpdb->get_var("show tables like '$table_name'") != $table_name) {

	$sql = "CREATE TABLE " . $table_name . " (
	id int(11) NOT NULL AUTO_INCREMENT,
	from_id varchar(255) NOT NULL,
	to_id varchar(255) NOT NULL,
	message text NOT NULL,
	sent bigint(19) NOT NULL,
	recd int(11) NOT NULL,
	system_message varchar(3),
	PRIMARY KEY  id (id)
 	) CHARACTER SET utf8 COLLATE utf8_general_ci;";

    dbDelta($sql);
} 	
$table_name = $wpdb->base_prefix . "symposium_chat2_users";
if($wpdb->get_var("show tables like '$table_name'") != $table_name) {

	$sql = "CREATE TABLE " . $table_name . " (
	id int(11) NOT NULL,
	chat_status varchar(255),
	offlineshift int(11),
	PRIMARY KEY  id (id)
 	) CHARACTER SET utf8 COLLATE utf8_general_ci;";

    dbDelta($sql);
} 	

$table_name = $wpdb->base_prefix . "symposium_chat2_typing";
if($wpdb->get_var("show tables like '$table_name'") != $table_name) {

	$sql = "CREATE TABLE " . $table_name . " (
	typing_from int(11) NOT NULL,
	typing_to int(11) NOT NULL,
	PRIMARY KEY  id (typing_from)
 	) CHARACTER SET utf8 COLLATE utf8_general_ci;";

    dbDelta($sql);
} 	

// Create mail table
$table_name = $wpdb->base_prefix . "symposium_mail";
if($wpdb->get_var("show tables like '$table_name'") != $table_name) {

	$sql = "CREATE TABLE " . $table_name . " (
	mail_mid int(11) NOT NULL AUTO_INCREMENT,
	mail_from int(11),
	mail_to int(11),
	mail_read varchar(2) NOT NULL DEFAULT '',
	mail_sent timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	mail_subject varchar(256) NOT NULL,
	mail_in_deleted varchar(2) NOT NULL DEFAULT '',
	mail_sent_deleted varchar(2) NOT NULL DEFAULT '',
	mail_message TEXT,
	PRIMARY KEY  mail_mid (mail_mid)
 	) CHARACTER SET utf8 COLLATE utf8_general_ci;";

    dbDelta($sql);

	// Mail to administrator
	$rows_affected = $wpdb->insert( $wpdb->prefix . "symposium_mail", array( 
	'mail_from' => 1, 
	'mail_to' => 1, 
	'mail_subject' => 'Welcome to '.WPS_WL.' Mail.',
	'mail_message' => 'This is an example message, from me to myself...'
	 ) );

} 	

// Library of Styles
$table_name = $wpdb->prefix . "symposium_styles";
if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
	
$sql = "CREATE TABLE " . $table_name . " (
    sid int(11) NOT NULL AUTO_INCREMENT,
	title varchar(32) NOT NULL,
	__wps__categories_background varchar(12) NOT NULL,
	categories_color varchar(12) NOT NULL,
	bigbutton_background varchar(12) NOT NULL,
	bigbutton_color varchar(12) NOT NULL,
	bigbutton_background_hover varchar(12) NOT NULL,
	bigbutton_color_hover varchar(12) NOT NULL,
	bg_color_1 varchar(12) NOT NULL,
	bg_color_2 varchar(12) NOT NULL,
	bg_color_3 varchar(12) NOT NULL,
	text_color varchar(12) NOT NULL,
	table_rollover varchar(12) NOT NULL,
	link varchar(12) NOT NULL,
	link_hover varchar(12) NOT NULL,
	table_border varchar(2) NOT NULL,
	replies_border_size varchar(2) NOT NULL,
	text_color_2 varchar(12) NOT NULL,
	row_border_style varchar(7) NOT NULL,
	row_border_size varchar(2) NOT NULL,
	border_radius varchar(2) NOT NULL,
	label varchar(12) NOT NULL,
	underline varchar(2) NOT NULL DEFAULT 'on',
	main_background varchar(12) NOT NULL DEFAULT '#fff',
	closed_opacity varchar(6) NOT NULL DEFAULT '1.0',
	fontfamily varchar(128) NOT NULL DEFAULT 'Georgia,Times',
	fontsize varchar(8) NOT NULL DEFAULT '13',
	headingsfamily varchar(128) NOT NULL DEFAULT 'Georgia,Times',
	headingssize varchar(8) NOT NULL DEFAULT '20',
	PRIMARY KEY  sid (sid)
	) CHARACTER SET utf8 COLLATE utf8_general_ci;";
	
	dbDelta($sql);

  	// Rustic
	$rows_affected = $wpdb->insert( $table_name, array( 
	'title' => 'Rustic', 
	'fontfamily' => 'HelveticaNeue-Light,Helvetica Neue Light,Helvetica Neue,sans-serif', // Body Text
	'fontsize' => '12', // Body text size
	'headingsfamily' => 'HelveticaNeue-Light,Helvetica Neue Light,Helvetica Neue,sans-serif', // Headings
	'headingssize' => '18', // Headings text size
	'main_background' =>'transparent', // Main background
	'bigbutton_background' => '#ac8139', // Button background colour
	'bigbutton_color' => '#fff', // Button text
	'bigbutton_background_hover' => '#8c6c36', // Button hover background colour
	'bigbutton_color_hover' => '#fff',  // Button hovertext
	'bg_color_1' => '#ac8139', // Tables Primary colour
	'bg_color_2' => '#efddc8', // Row colour
	'bg_color_3' => '#faf6ef', // Row alternate colour
	'table_rollover' => '#dfa462', // Row hover colour
	'text_color' => '#000', // Text primary colour
	'text_color_2' => '#ac8139', // Text seconday colour
	'link' => '#ac8139', // Link colour
	'link_hover' => '#000', // Link hover colour
	'table_border' => '0', // Table border size 
	'replies_border_size' => '0', // "Other" border size
	'row_border_style' => 'dashed', // Table border style between rows
	'row_border_size' => '0', // Table border size between rows
	'border_radius' => '3', // Corner radius size
	'label' => '#000', // Labels
	'categories_color' => '#fff', // Miscellaneous text
	'__wps__categories_background' => '#ac8139'  // Miscellaneous background
	) );
	
  	// Who Blue
	$rows_affected = $wpdb->insert( $table_name, array( 
	'title' => 'Who Blue', 
	'__wps__categories_background' => '#0072bc', 
	'categories_color' => '#fff', 
	'bigbutton_background' => '#0072bc', 
	'bigbutton_color' => '#fff', 
	'bigbutton_background_hover' => '#00aeef',
	'bigbutton_color_hover' => '#fff', 
	'bg_color_1' => '#0072bc', 
	'bg_color_2' => '#ebebeb',
	'bg_color_3' => '#fff', 
	'text_color' => '#000', 
	'table_rollover' => '#fbaf5a', 
	'link' => '#0054a5', 
	'link_hover' => '#000', 
	'table_border' => '0', 
	'replies_border_size' => '0', 
	'text_color_2' => '#0054a5', 
	'row_border_style' => 'dotted', 
	'row_border_size' => '0', 
	'border_radius' => '5',
	'label' => '#0054a5'
	) );

  	// Blue Azure
	$rows_affected = $wpdb->insert( $table_name, array( 
	'title' => 'Blue Azure', 
	'__wps__categories_background' => '#0072bc', 
	'categories_color' => '#fff', 
	'bigbutton_background' => '#0072bc', 
	'bigbutton_color' => '#fff', 
	'bigbutton_background_hover' => '#00aeef',
	'bigbutton_color_hover' => '#fff', 
	'bg_color_1' => '#0072bc', 
	'bg_color_2' => '#ebebeb',
	'bg_color_3' => '#e1e1e1', 
	'text_color' => '#000', 
	'table_rollover' => '#00aeef', 
	'link' => '#0054a5', 
	'link_hover' => '#000', 
	'table_border' => '2', 
	'replies_border_size' => '1', 
	'text_color_2' => '#0054a5', 
	'row_border_style' => 'dotted', 
	'row_border_size' => '1', 
	'border_radius' => '5',
	'label' => '#0054a5'
	) );

  	// Gothic
	$rows_affected = $wpdb->insert( $table_name, array( 
	'title' => 'Gothic', 
	'__wps__categories_background' => '#363636', 
	'categories_color' => '#fff', 
	'bigbutton_background' => '#fff', 
	'bigbutton_color' => '#000', 
	'bigbutton_background_hover' => '#c2c2c2',
	'bigbutton_color_hover' => '#000', 
	'bg_color_1' => '#000', 
	'bg_color_2' => '#363636',
	'bg_color_3' => '#464646', 
	'text_color' => '#959595', 
	'table_rollover' => '#626262', 
	'link' => '#fff', 
	'link_hover' => '#959595', 
	'table_border' => '2', 
	'replies_border_size' => '1', 
	'text_color_2' => '#c2c2c2', 
	'row_border_style' => 'dotted', 
	'row_border_size' => '1', 
	'border_radius' => '5',
	'label' => '#000'
	) );

  	// Metal
	$rows_affected = $wpdb->insert( $table_name, array( 
	'title' => 'Metal', 
	'border_radius' => '5',
	'bigbutton_background' => '#464646', 
	'bigbutton_background_hover' => '#555',
	'bigbutton_color' => '#fff', 
	'bigbutton_color_hover' => '#fff', 
	'bg_color_1' => '#7d7d7d', 
	'bg_color_2' => '#ebebeb',
	'bg_color_3' => '#e1e1e1', 
	'table_border' => '2', 
	'row_border_style' => 'dotted', 
	'row_border_size' => '1', 
	'replies_border_size' => '1', 
	'table_rollover' => '#7d7d7d', 
	'__wps__categories_background' => '#7d7d7d', 
	'categories_color' => '#fff', 
	'text_color' => '#000', 
	'text_color_2' => '#363636', 
	'link' => '#000', 
	'link_hover' => '#363636', 
	'label' => '#000'
	) );

  	// Neutral
	$rows_affected = $wpdb->insert( $table_name, array( 
	'title' => 'Neutral', 
	'border_radius' => '0',
	'bigbutton_background' => '#959595', 
	'bigbutton_background_hover' => '#c2c2c2',
	'bigbutton_color' => '#fff', 
	'bigbutton_color_hover' => '#000', 
	'bg_color_1' => '#363636', 
	'bg_color_2' => '#fff',
	'bg_color_3' => '#ebebeb', 
	'table_rollover' => '#e1e1e1', 
	'table_border' => '0', 
	'row_border_style' => 'dotted', 
	'row_border_size' => '1', 
	'replies_border_size' => '0', 
	'__wps__categories_background' => '#c2c2c2', 
	'categories_color' => '#000', 
	'text_color' => '#000', 
	'text_color_2' => '#898989', 
	'link' => '#000', 
	'link_hover' => '#363636', 
	'label' => '#000'
	) );

	// Aqua
   	$rows_affected = $wpdb->insert( $wpdb->prefix."symposium_styles", array( 
	'title' => 'Aqua', 
	'border_radius' => '5',
	'bigbutton_background' => '#B9D3EE', 
	'bigbutton_background_hover' => '#B9D3EE',
	'bigbutton_color' => '#505050', 
	'bigbutton_color_hover' => '#000', 
	'bg_color_1' => '#B9D3EE', 
	'bg_color_2' => '#fff',
	'bg_color_3' => '#fff', 
	'table_rollover' => '#F8F8F8', 
	'table_border' => '0', 
	'row_border_style' => 'dotted', 
	'row_border_size' => '1', 
	'replies_border_size' => '1', 
	'__wps__categories_background' => '#B9D3EE', 
	'categories_color' => '#505050', 
	'text_color' => '#505050', 
	'text_color_2' => '#505050', 
	'link' => '#505050', 
	'underline' => '', 
	'link_hover' => '#000', 
	'label' => '#505050'
	) );

} 

// Modify Mail table
__wps__alter_table("mail", "MODIFY", "mail_sent", "datetime", "", "");

// Modify Forum Categories table
__wps__alter_table("cats", "ADD", "cat_parent", "int(11)", "NOT NULL", "0");
__wps__alter_table("cats", "ADD", "cat_desc", "varchar(256)", "", "''");
__wps__alter_table("cats", "ADD", "level", "varchar(256)", "", "'s:60:\"Everyone,Administrator,Editor,Author,Contributor,Subscriber,\";'");
__wps__alter_table("cats", "ADD", "stub", "varchar(256)", "", "''");
__wps__alter_table("cats", "ADD", "min_rank", "int(11)", "NOT NULL", "0");
__wps__alter_table("cats", "ADD", "hide_breadcrumbs", "varchar(2)", "", "''");
__wps__alter_table("cats", "ADD", "hide_main", "varchar(2)", "", "''");

// Modify Comments table
__wps__alter_table("comments", "MODIFY", "comment_timestamp", "datetime", "", "");
__wps__alter_table("comments", "ADD", "is_group", "varchar(2)", "NOT NULL", "''");
__wps__alter_table("comments", "ADD", "type", "varchar(16)", "NOT NULL", "'post'");
__wps__alter_table("comments", "MODIFY", "comment", "text", "", "");

// Modify Friends table
__wps__alter_table("friends", "MODIFY", "friend_timestamp", "datetime", "", "");

// Modify Gallery items table
__wps__alter_table("gallery_items", "ADD", "photo_order", "int(11)", "NOT NULL", "'0'");

// Modify topics table
__wps__alter_table("topics", "ADD", "allow_replies", "varchar(2)", "NOT NULL", "'on'");
__wps__alter_table("topics", "ADD", "topic_approved", "varchar(2)", "NOT NULL", "'on'");
__wps__alter_table("topics", "ADD", "topic_answer", "varchar(2)", "", "''");
__wps__alter_table("topics", "ADD", "for_info", "varchar(2)", "", "''");
__wps__alter_table("topics", "ADD", "stub", "varchar(256)", "", "''");
__wps__alter_table("topics", "ADD", "remote_addr", "varchar(32)", "", "''");
__wps__alter_table("topics", "ADD", "http_x_forwarded_for", "varchar(32)", "", "''");

// Modify profile extended fields table
__wps__alter_table("extended", "MODIFY", "extended_name", "varchar(256)", "NOT NULL", "'New field'");
__wps__alter_table("extended", "ADD", "extended_slug", "varchar(64)", "NOT NULL", "");
__wps__alter_table("extended", "ADD", "wp_usermeta", "varchar(256)", "", "");
__wps__alter_table("extended", "ADD", "readonly", "varchar(2)", "", "''");
__wps__alter_table("extended", "ADD", "search", "varchar(2)", "", "''");

// Groups
__wps__alter_table("groups", "ADD", "profile_photo", "varchar(64)", "", "''");
__wps__alter_table("groups", "ADD", "group_forum", "varchar(2)", "", "'on'");
__wps__alter_table("groups", "ADD", "allow_new_topics", "varchar(2)", "", "'on'");
__wps__alter_table("groups", "ADD", "new_member_emails", "varchar(2)", "", "'on'");
__wps__alter_table("groups", "ADD", "add_alerts", "varchar(2)", "", "'on'");
__wps__alter_table("groups", "ADD", "default_page", "varchar(16)", "", "'activity'");
__wps__alter_table("groups", "ADD", "about_page", "text", "", "''");
__wps__alter_table("groups", "ADD", "max_members", "int(11)", "NOT NULL", "'0'");
__wps__alter_table("groups", "ADD", "group_order", "int(11)", "NOT NULL", "'99'");


// Add indexes
__wps__add_index($wpdb->prefix . "symposium_topics", 'topic_owner');
__wps__add_index($wpdb->prefix . "symposium_topics", 'topic_parent');
__wps__add_index($wpdb->prefix . "symposium_topics", 'topic_category');
__wps__add_index($wpdb->prefix . "symposium_topics", 'topic_date');
__wps__add_index($wpdb->prefix . "symposium_topics", 'topic_started');
__wps__add_index($wpdb->prefix . "symposium_cats", 'cat_parent');
__wps__add_index($wpdb->prefix . "symposium_subs", 'uid');
__wps__add_index($wpdb->prefix . "symposium_subs", 'tid');
__wps__add_index($wpdb->prefix . "symposium_usermeta", 'uid', "UNIQUE");
__wps__add_index($wpdb->prefix . "symposium_group_members", 'group_id');
__wps__add_index($wpdb->prefix . "symposium_group_members", 'member_id');
__wps__add_index($wpdb->base_prefix . "symposium_following", 'uid');
__wps__add_index($wpdb->base_prefix . "symposium_following", 'following');
__wps__add_index($wpdb->base_prefix . "symposium_news", 'subject');
__wps__add_index($wpdb->base_prefix . "symposium_news", 'author');
__wps__add_index($wpdb->base_prefix . "symposium_gallery", 'parent_gid');
__wps__add_index($wpdb->base_prefix . "symposium_gallery", 'name');
__wps__add_index($wpdb->base_prefix . "symposium_gallery_items", 'gid');
__wps__add_index($wpdb->base_prefix . "symposium_likes", 'cid');
__wps__add_index($wpdb->base_prefix . "symposium_comments", 'subject_uid');
__wps__add_index($wpdb->base_prefix . "symposium_comments", 'author_uid');
__wps__add_index($wpdb->base_prefix . "symposium_comments", 'comment_parent');
__wps__add_index($wpdb->base_prefix . "symposium_friends", 'friend_from');
__wps__add_index($wpdb->base_prefix . "symposium_friends", 'friend_to');
__wps__add_index($wpdb->base_prefix . "symposium_mail", 'mail_to');

?>
