<?php

include_once('../../../../wp-config.php');

// Calendar view

if ($_POST['action'] == 'calendar_view') {
					
	$month = $_POST['month'];
	$year = $_POST['year'];
	$day = date('d');
	$current_month = date("n");
	$current_year = date("Y");
	if ($month > 1) {
		$prev_month = $month-1;
		$prev_year = $year;
	} else {
		$prev_month = 12;
		$prev_year = $year-1;
	}
	if ($month < 12) {
		$next_month = $month+1;
		$next_year = $year;
	} else {
		$next_month = 1;
		$next_year = $year+1;
	}

	// Get events for this month
	if (get_option(WPS_OPTIONS_PREFIX."_events_hide_expired")) {
		$hide = "(event_start >= now() OR event_start = '0000-00-00 00:00:00') AND";
	} else {
		$hide = '';
	}
	
	$include = get_option(WPS_OPTIONS_PREFIX."_events_global_list");
	if ($include) {
		$sql = "SELECT e.*, u.ID, u.display_name FROM ".$wpdb->base_prefix."symposium_events e LEFT JOIN ".$wpdb->base_prefix."users u ON event_owner = ID WHERE ".$hide." event_owner IN (".$include.") AND event_live = %s ORDER BY event_start";
	} else {
		$sql = "SELECT e.*, u.ID, u.display_name FROM ".$wpdb->base_prefix."symposium_events e LEFT JOIN ".$wpdb->base_prefix."users u ON event_owner = ID WHERE ".$hide." event_live = %s ORDER BY event_start";
	}
	if (get_option(WPS_OPTIONS_PREFIX."_events_sort_order")) $sql .= " DESC";
	$events = $wpdb->get_results($wpdb->prepare($sql, 'on'));
	$array = array();
	if ($events) {
		foreach ($events as $event) {
			$event_start = strtotime($event->event_start);
			$event_end = strtotime($event->event_end);
			$array[] = array(
				'eid' => $event->eid,
				'event_name' => $event->event_name,
				'event_end' => $event->event_end,
				'event_start_day' => date('d', $event_start),
				'event_end_day' => date('d', $event_end),
				'event_start_month' => date('m', $event_start),
				'event_end_month' => date('m', $event_end),
				'event_start_year' => date('Y', $event_start),
				'event_end_year' => date('Y', $event_end),
				'event_owner' => $event->event_owner,
				'event_more' => $event->event_more,
				'event_location' => $event->event_location,
				'event_enable_places' => $event->event_enable_places,
				'event_show_max' => $event->event_show_max,
				'event_max_places' => $event->event_max_places,
				'event_cost' => $event->event_cost,
				'event_description' => $event->event_description,				
				'event_start_hours' => $event->event_start_hours,
				'event_start_minutes' => $event->event_start_minutes,
				'event_end_hours' => $event->event_end_hours,
				'event_end_minutes' => $event->event_end_minutes,
				'event_tickets_per_booking' => $event->event_tickets_per_booking
			);		
		}
	}
		
	$html = '';
	
	$html .= '<div id="__wps__event_nav">';
		$html .= '<div id="__wps__event_move" data-month="'.$prev_month.'" data-year="'.$prev_year.'"><a href="javascript:void(0);">'.__('<<', WPS_TEXT_DOMAIN).'</a></div>';
		$html .= '<div id="__wps__event_move" data-month="'.$next_month.'" data-year="'.$next_year.'"><a href="javascript:void(0);">'.__('>>', WPS_TEXT_DOMAIN).'</a></div>';
		$html .= '<div id="__wps__event_move" data-month="'.$current_month.'" data-year="'.$current_year.'"><a href="javascript:void(0);">'.__('Today', WPS_TEXT_DOMAIN).'</a></div>';
		
		$html .= '<table cellpadding="0" cellspacing="0" class="calendar" style="width:100% !important;">';
		
		/* table headings */
		$headings = array(__('Sunday', WPS_TEXT_DOMAIN),__('Monday', WPS_TEXT_DOMAIN),__('Tuesday', WPS_TEXT_DOMAIN),__('Wednesday', WPS_TEXT_DOMAIN),__('Thursday', WPS_TEXT_DOMAIN),__('Friday', WPS_TEXT_DOMAIN),__('Saturday', WPS_TEXT_DOMAIN));
		$html .= '<tr class="calendar-row"><td class="calendar-day-head">'.implode('</td><td class="calendar-day-head">',$headings).'</td></tr>';
		
		/* days and weeks vars now ... */
		$running_day = date('w',mktime(0,0,0,$month,1,$year));
		$days_in_month = date('t',mktime(0,0,0,$month,1,$year));
		$days_in_this_week = 1;
		$day_counter = 0;
		$dates_array = array();
		
		$html .= '<div id="__wps__event_title">'.date('F Y', mktime(0, 0, 0, $month, $day, $year)).'</div>';
	$html .= '</div>';
	
	/* row for week one */
	$html.= '<tr class="calendar-row">';
	
	/* print "blank" days until the first of the current week */
	for($x = 0; $x < $running_day; $x++):
	    $html.= '<td class="calendar-day-np"> </td>';
	    $days_in_this_week++;
	endfor;
	
	/* keep going with days.... */
	for($list_day = 1; $list_day <= $days_in_month; $list_day++):
	
		$current = (($list_day == $day) && ($month == $current_month)&& ($year == $current_year)) ? ' current' : '';
		$weekend = ($running_day == 0 || $running_day == 6) ? ' weekend' : '';
	    $html.= '<td class="calendar-day'.$weekend.$current.'">';
	
	        $timestamp = mktime(0,0,0,$month,$list_day,$year);
	
	        $html .= '<div class="day-number">'.$list_day.'</div>';
	        $html .= '<div id="calendar-events">';
	        foreach ($array as $i) {
	            if ( 
	            	( $i['event_end'] == '0000-00-00 00:00:00' &&
	            		( $list_day == $i['event_start_day'] && $month == $i['event_start_month'] && $year == $i['event_start_year'])
	            	)
	            	||
	            	(
		            	( ($list_day >= $i['event_start_day'] ) && ($list_day <= $i['event_end_day']) ) &&
		            	( ($month >= $i['event_start_month'] ) && ($month <= $i['event_end_month']) ) &&
	    	        	( ($year >= $i['event_start_year'] ) && ($year <= $i['event_end_year']) )
	        		)
	            ) {
	                
					$html .= '<div class="__wps__events_calendar_event">';

						if ($i['event_more']) {
							$more = str_replace(chr(10), '<br />', stripslashes($i['event_more']));
							$html .= '<div id="symposium_more_'.$i['eid'].'" title="'.stripslashes($i['event_name']).'" class="__wps__dialog_content"><div style="text-align:left">'.$more.'</div></div>';
			                $html .= '<a href="javascript:void(0)" id="symposium_event_more" rel="symposium_more_'.$i['eid'].'" class="symposium-dialog __wps__event_calendar_title" title="'.stripslashes($i['event_description']).'">'.$i['event_name'].'</a>';
						} else {
			                $html .= '<div class="__wps__event_calendar_title">'.$i['event_name'].'</div>';
						}
	
						$html .= '<div class="__wps__event_list_location">'.stripslashes($i['event_location']).'</div>';
						if (isset($i['event_cost']) && $i['event_cost'] !== '') {
							$html .= '<div class="symposium_event_cost">'.__('Cost:', WPS_TEXT_DOMAIN).' '.$i['event_cost'].'</div>';
						}
						$html .= '<div class="__wps__event_list_times">';
							if ($i['event_start_hours'] != 99) {
								$html .= $i['event_start_hours'].":".sprintf('%1$02d', $i['event_start_minutes']);
							}
							if ($i['event_end_hours'] != 99) {
								$html .= '-'.$i['event_end_hours'].":".sprintf('%1$02d', $i['event_end_minutes']);
							}
						$html .= '</div>';
	
						$html .= '<div>';
						if ($i['event_enable_places'] && $i['event_show_max']) {
							$sql = "SELECT SUM(tickets) FROM ".$wpdb->base_prefix."symposium_events_bookings WHERE event_id = %d";
							$taken = $wpdb->get_var($wpdb->prepare($sql, $event->eid));
						}
						$made_a_booking = false;
						if (is_user_logged_in() && $i['event_enable_places']) {
								// check to see if already booked
								$sql = "select b.tickets, b.confirmed, b.bid, b.payment_processed, e.event_cost FROM ".$wpdb->base_prefix."symposium_events_bookings b LEFT JOIN ".$wpdb->base_prefix."symposium_events e ON b.event_id = e.eid WHERE event_id = %d AND uid = %d";
								$ret = $wpdb->get_row($wpdb->prepare($sql, $event->eid, $current_user->ID));
								if (!$ret || !$ret->tickets) {
									if ($i['event_max_places']-$taken > 0)
										$html .= '<div class="symposium_event_button_div"><a href="javascript:void(0)" id="symposium_book_event" data-eid="'.$i['eid'].'" data-max="'.$i['event_tickets_per_booking'].'" class="symposium_book_event_button_calendar" style="margin:0">'.__('Book', WPS_TEXT_DOMAIN).'</a></div>';
								} else {
									$made_a_booking = true;
									$html .= '<div class="symposium_event_button_div"><a href="javascript:void(0)" id="symposium_cancel_event" data-eid="'.$i['eid'].'"  class="symposium_cancel_event_button_calendar" style="margin:0">'.__("Cancel", WPS_TEXT_DOMAIN).'</a></div>';
								}
								if ($ret && !$ret->confirmed && !$ret->payment_processed && $ret->tickets && $ret->event_cost )
									$html .= '<div class="symposium_event_button_div"><a href="javascript:void(0)" id="symposium_pay_event" class="symposium_pay_event_button_calendar" data-bid="'.$ret->bid.'">'.__("Pay!", WPS_TEXT_DOMAIN).'</a></div>';
								if ($ret && $ret->tickets ) {
									if ($ret->confirmed) {
										$html .= sprintf(_n('%d ticket confirmed','%d tickets confirmed', $ret->tickets, WPS_TEXT_DOMAIN), $ret->tickets);
									} else {
										$html .= sprintf(_n('Awaiting confirmation for %d ticket.','Awaiting confirmation for %d tickets.', $ret->tickets, WPS_TEXT_DOMAIN), $ret->tickets);
									}
								}								
						}
						if ($i['event_enable_places'] && $i['event_show_max'] && !$made_a_booking) {
							$html .= '<div class="__wps__event_list_places">';
								if ($i['event_max_places']-$taken > 0) {
									$html .= __('Remaining:', WPS_TEXT_DOMAIN).' '.($i['event_max_places']-$taken);
								} else {
									$html .= __('Event full', WPS_TEXT_DOMAIN);
								}
							$html .= '</div>';
						}

					$html .= '</div>';					
	            }
	        }
	        $html .= '</div>';
	
	    $html.= '</td>';
	    if($running_day == 6):
	        $html.= '</tr>';
	        if(($day_counter+1) != $days_in_month):
	            $html.= '<tr class="calendar-row">';
	        endif;
	        $running_day = -1;
	        $days_in_this_week = 0;
	    endif;
	    $days_in_this_week++; $running_day++; $day_counter++;
	endfor;
	
	/* finish the rest of the days in the week */
	if($days_in_this_week < 8):
	    for($x = 1; $x <= (8 - $days_in_this_week); $x++):
	        $html.= '<td class="calendar-day-np"> </td>';
	    endfor;
	endif;
	
	/* final row */
	$html.= '</tr>';
	
	/* end the table */
	$html.= '</table>';
	
	echo $html;

}
					
					
// Payment Received
if ($_POST['action'] == 'payment_recd') {

	global $wpdb;
	$bid = $_POST['bid'];

	$wpdb->query( $wpdb->prepare( "UPDATE ".$wpdb->base_prefix."symposium_events_bookings SET 
		payment_processed = %s
		WHERE bid = %d", 
	array( 
		date("Y-m-d H:i:s"), 
		$bid
	 ) ));
	if (WPS_DEBUG) echo $wpdb->last_query;	
	
	echo 'OK';
	exit;
}

// Payment button
if ($_POST['action'] == 'event_payment') {
	global $wpdb;
	$bid = $_POST['bid'];
	
	// Get event booking and info
	$sql = "SELECT e.eid, e.event_name, b.tickets, e.event_cost, e.event_pay_link
			FROM ".$wpdb->base_prefix."symposium_events_bookings b 
			LEFT JOIN ".$wpdb->base_prefix."symposium_events e ON b.event_id = e.eid
			WHERE bid = %d";
	$mi = $wpdb->get_row($wpdb->prepare($sql, $bid));
	if (WPS_DEBUG) echo $wpdb->last_query;

	if (is_user_logged_in()) {
		
		echo __('Booking reference:', WPS_TEXT_DOMAIN).' '.$mi->eid.'/'.$bid;
		echo '<p>'.__('If you have already paid, please do not pay again - your payment is being processed. Thank you.', WPS_TEXT_DOMAIN).'</p>';
		
		if ($mi->event_cost) {
			if ($mi->event_pay_link) {
				$pay = $mi->event_pay_link;
				$pay = str_replace('##refnumber##', $mi->eid.'/'.$bid, $pay);
				$pay = str_replace('##eventname##', $mi->event_name, $pay);
				$pay = str_replace('##userlogin##', $current_user->ID, $pay);
				$pay = str_replace('##useremail##', $current_user->user_email, $pay);
				$pay = str_replace('##quantity##', $mi->tickets, $pay);
				$pay = str_replace('##unitcost##', $mi->event_cost, $pay);
				
				echo '<p>'.$pay.'</p>';
			}
		}
		
	} else {
		echo __wps__show_login_link(__("You need to be <a href='%s'>logged in</a> to book events.", WPS_TEXT_DOMAIN));
	}
		
	exit;
}

// Resend confirmation email
if ($_POST['action'] == 'resendEmail') {
	global $wpdb;
	$bid = $_POST['bid'];

	// Get recipient info
	$sql = "SELECT u.user_email, e.event_email, e.eid, b.uid 
			FROM ".$wpdb->base_prefix."symposium_events_bookings b 
			LEFT JOIN ".$wpdb->base_prefix."users u ON b.uid = u.ID
			LEFT JOIN ".$wpdb->base_prefix."symposium_events e ON b.event_id = e.eid
			WHERE bid = %d";
	$ret = $wpdb->get_row( $wpdb->prepare($sql, $bid) );

	$event_email = $ret->event_email;
	$user_email = $ret->user_email;

	// Update confirmed and send confirmation email
	$from_email = trim(get_option(WPS_OPTIONS_PREFIX.'_from_email'));
	$from_name = html_entity_decode(trim(stripslashes(get_bloginfo('name'))), ENT_QUOTES, 'UTF-8');
	$crlf = PHP_EOL;
	$headers = "MIME-Version: 1.0" . $crlf;
	$headers .= "Content-type:text/html;charset=utf-8" . $crlf;
	$headers .= "From: " . $from_name . " <" . $from_email . ">" . $crlf;
	$event_email = __wps__events_confirm_email_fields($event_email, $ret->eid, $bid, $ret->uid);

	$wpdb->query( $wpdb->prepare( "UPDATE ".$wpdb->base_prefix."symposium_events_bookings SET 
		email_sent = %s
		WHERE bid = %d", 
	array( 
		date("Y-m-d H:i:s"), 
		$bid
	 ) ));
	if (PS_DEBUG) echo $wpdb->last_query;

	if (!wp_mail($user_email, __('Booking confirmation', WPS_TEXT_DOMAIN), $event_email, $headers))
		echo sprintf(__('Tried to send an email to %s, but it failed, sorry.', WPS_TEXT_DOMAIN), $user_email).'<br /><br />';
		
	echo 'OK';
	exit;
}

// Confirm an attendee via Attendees
if ($_POST['action'] == 'confirmAttendee') {
	global $wpdb;
	$bid = $_POST['bid'];
	
	// Get Event info
	$sql = "SELECT b.tickets, e.eid, u2.user_email as owner_email, u.display_name, u.user_email, e.event_name, e.event_email, b.uid 
			FROM ".$wpdb->base_prefix."symposium_events_bookings b 
			LEFT JOIN ".$wpdb->base_prefix."users u ON b.uid = u.ID
			LEFT JOIN ".$wpdb->base_prefix."symposium_events e ON b.event_id = e.eid
			LEFT JOIN ".$wpdb->base_prefix."users u2 ON e.event_owner = u2.ID
			WHERE bid = %d";
	$ret = $wpdb->get_row( $wpdb->prepare($sql, $bid) );
	$howmany = $ret->tickets;
	$eid = $ret->eid;
	$display_name = $ret->display_name;
	$event_name = $ret->event_name;
	$user_email = $ret->user_email;
	$event_email = $ret->event_email;
	$owner_email = $ret->owner_email;
	if (WPS_DEBUG) echo $wpdb->last_query.'<br />';
	
	// Update confirmed and send confirmation email
	$from_email = trim(get_option(WPS_OPTIONS_PREFIX.'_from_email'));
	$from_name = html_entity_decode(trim(stripslashes(get_bloginfo('name'))), ENT_QUOTES, 'UTF-8');
	$crlf = PHP_EOL;
	$headers = "MIME-Version: 1.0" . $crlf;
	$headers .= "Content-type:text/html;charset=utf-8" . $crlf;
	$headers .= "From: " . $from_name . " <" . $from_email . ">" . $crlf;
	$event_email = __wps__events_confirm_email_fields($event_email, $ret->eid, $bid, $ret->uid);

	$wpdb->query( $wpdb->prepare( "UPDATE ".$wpdb->base_prefix."symposium_events_bookings SET 
		confirmed = %s,
		email_sent = %s
		WHERE bid = %d", 
	array( 
		'on',
		date("Y-m-d H:i:s"), 
		$bid
	 ) ));
	if (WPS_DEBUG) echo $wpdb->last_query;

	if (!wp_mail($user_email, __('Booking confirmation', WPS_TEXT_DOMAIN), $event_email, $headers))
		echo sprintf(__('Tried to send an email to %s, but it failed, sorry.', WPS_TEXT_DOMAIN), $user_email).'<br /><br />';

	// Inform the organiser (for audit purposes)
	$msg = '<p>'.sprintf(__('You have confirmed the booking for %s, for %d ticket(s) for event (%s) ID:', WPS_TEXT_DOMAIN), $display_name, $howmany, stripslashes($event_name)).$eid.'<br />';
	$msg .= __('If payment is required, please follow this up.', WPS_TEXT_DOMAIN).'</p>';
	$subject = sprintf(__('Attendee confirmation for Event (%s) ID:', WPS_TEXT_DOMAIN), $event_name).$eid;
	__wps__sendmail($owner_email, $subject, $msg);
	if (WPS_DEBUG) echo '<p>'.$subject.'<br />'.$event_owner_email.'<br />'.$msg.'</p>';	
	
	echo 'OK';
	exit;	
}

// Remove an attendee via Attendees
if ($_POST['action'] == 'removeAttendee') {
	global $wpdb;
	$bid = $_POST['bid'];
	
	// Get Event info
	$sql = "SELECT event_id, display_name, event_name FROM ".$wpdb->base_prefix."symposium_events_bookings b 
			LEFT JOIN ".$wpdb->base_prefix."users u ON b.uid = u.ID
			LEFT JOIN ".$wpdb->base_prefix."symposium_events e ON b.event_id = e.eid
			WHERE bid = %d";
	$ret = $wpdb->get_row( $wpdb->prepare($sql, $bid) );
	$eid = $ret->event_id;
	$display_name = $ret->display_name;
	$event_name = $ret->event_name;
	
	// Delete booking
	$sql = "DELETE FROM ".$wpdb->base_prefix."symposium_events_bookings WHERE bid = %d";
	$rows_affected = $wpdb->query( $wpdb->prepare($sql, $bid) );
	if (WPS_DEBUG) echo $wpdb->last_query;

	// Email the event owner that the booking has been cancelled
	$sql = "SELECT event_owner, event_name, user_email FROM ".$wpdb->base_prefix."symposium_events e 
			LEFT JOIN ".$wpdb->base_prefix."users u ON e.event_owern = u.UD
			WHERE eid = %d";
	$event = $wpdb->get_row( $wpdb->prepare($sql, $eid) );
	if (WPS_DEBUG) echo '<p>'.$wpdb->last_query.'</p>';

	// Inform the organiser (for audit purposes)
	$msg = '<p>'.sprintf(__('You removed %s from event (%s) ID:', WPS_TEXT_DOMAIN), $display_name, $event_name).$eid.'<br />';
	$msg .= __('If a refund is required, please follow this up.', WPS_TEXT_DOMAIN).'</p>';
	$subject = sprintf(__('Attendee removal for Event (%s) ID:', WPS_TEXT_DOMAIN), stripslashes($event_name)).$eid;
	__wps__sendmail($event_owner_email, $subject, $msg);
	if (WPS_DEBUG) echo '<p>'.$subject.'<br />'.$event_owner_email.'<br />'.$msg.'</p>';
	
	echo 'OK';
	exit;	
}

// Cancel an event booking
if ($_POST['action'] == 'cancel_event') {
	global $wpdb,$current_user;
	$eid = $_POST['eid'];
	$sql = "DELETE FROM ".$wpdb->base_prefix."symposium_events_bookings WHERE event_id = %d AND uid = %d";
	$rows_affected = $wpdb->query( $wpdb->prepare($sql, $eid, $current_user->ID) );
	if (WPS_DEBUG) echo $wpdb->last_query;

	// Email the event owner that the event has been cancelled
	$sql = "SELECT event_owner, event_name FROM ".$wpdb->base_prefix."symposium_events WHERE eid = %d";
	$event = $wpdb->get_row( $wpdb->prepare($sql, $eid) );
	if (WPS_DEBUG) echo '<p>'.$wpdb->last_query.'</p>';
	$sql = "SELECT user_email FROM ".$wpdb->base_prefix."users WHERE ID = %d";
	$event_owner_email = $wpdb->get_var( $wpdb->prepare($sql, $event->event_owner) );
	if (WPS_DEBUG) echo '<p>'.$wpdb->last_query.'</p>';

	// Inform the organiser
	$msg = '<p>'.$current_user->display_name.sprintf(__(' has cancelled their booking for event (%s) ID:', WPS_TEXT_DOMAIN), $event->event_name).$eid.'<br />';
	$msg .= __('If a refund is required, please follow this up.', WPS_TEXT_DOMAIN).'</p>';
	$subject = sprintf(__('Attendee cancellation for Event (%s) ID:', WPS_TEXT_DOMAIN), $event->event_name).$eid;
	__wps__sendmail($event_owner_email, $subject, $msg);
	if (WPS_DEBUG) echo '<p>'.$subject.'<br />'.$event_owner_email.'<br />'.$msg.'</p>';

	echo __('Your booking has been cancelled, the event organiser has been informed.<br /><br />If you need a refund, please contact the event organiser directly.', WPS_TEXT_DOMAIN);
	exit;

}

// Register (book) with an event
if ($_POST['action'] == 'register_event') {

	global $wpdb,$current_user;

	$eid = $_POST['eid'];
	$howmany = $_POST['howmany'];
	
	// Get event mgt info
	$sql = "SELECT event_confirmation, event_cost, event_pay_link FROM ".$wpdb->base_prefix."symposium_events WHERE eid = %d";
	$mi = $wpdb->get_row($wpdb->prepare($sql, $eid));

	if (is_user_logged_in()) {
		
		$confirmed = $mi->event_confirmation ? '' : 'on';

		$wpdb->query( $wpdb->prepare( "
			INSERT INTO ".$wpdb->base_prefix."symposium_events_bookings 
			( 	uid,
				event_id, 
				confirmed, 
				booked, 
				email_sent, 
				tickets
			)
			VALUES ( %d, %d, %s, %s, %s, %d )", 
	        array(
	        	$current_user->ID, 
	        	$eid,
	        	$confirmed, 
	        	date("Y-m-d H:i:s"), 
				'',
				$howmany
	        	) 
	        ) );

		$new_bid = $wpdb->insert_id;

		if (WPS_DEBUG) echo $wpdb->last_query;			

		// Email the event owner that the event has been booked
		$sql = "SELECT * FROM ".$wpdb->base_prefix."symposium_events WHERE eid = %d";
		$event = $wpdb->get_row( $wpdb->prepare($sql, $eid) );
		if (WPS_DEBUG) echo '<p>'.$wpdb->last_query.'</p>';
		$sql = "SELECT user_email FROM ".$wpdb->base_prefix."users WHERE ID = %d";
		$event_owner_email = $wpdb->get_var( $wpdb->prepare($sql, $event->event_owner) );
		if (WPS_DEBUG) echo '<p>'.$wpdb->last_query.'</p>';
	
		// Inform the organiser
		$msg = '<p>'.$current_user->display_name.sprintf(__(' has booked %d ticket(s) for event (%s) ID:', WPS_TEXT_DOMAIN), $howmany, stripslashes($event->event_name)).$eid.'<br />';
		$msg .= __('If payment is required, please follow this up.', WPS_TEXT_DOMAIN).'</p>';
		$subject = sprintf(__('Attendee booking for Event (%s) ID:', WPS_TEXT_DOMAIN), $event->event_name).$eid;
		__wps__sendmail($event_owner_email, $subject, $msg);
		if (WPS_DEBUG) echo '<p>'.$subject.'<br />'.$event_owner_email.'<br />'.$msg.'</p>';
	
		if (!$event->event_confirmation && $event->event_send_email) {
			// Send confirmation email
			$from_email = trim(get_option(WPS_OPTIONS_PREFIX.'_from_email'));
			$from_name = html_entity_decode(trim(stripslashes(get_bloginfo('name'))), ENT_QUOTES, 'UTF-8');
			$crlf = PHP_EOL;
			$headers = "MIME-Version: 1.0" . $crlf;
			$headers .= "Content-type:text/html;charset=utf-8" . $crlf;
			$headers .= "From: " . $from_name . " <" . $from_email . ">" . $crlf;
			$event_email = __wps__events_confirm_email_fields($event->event_email, $eid, $new_bid, $current_user->ID);
			
			if (wp_mail($current_user->user_email, __('Booking confirmation', WPS_TEXT_DOMAIN), $event_email, $headers)) {
				$wpdb->query( $wpdb->prepare( "UPDATE ".$wpdb->base_prefix."symposium_events_bookings SET 
					email_sent = %s
					WHERE bid = %d", 
				array( 
					date("Y-m-d H:i:s"), 
					$new_bid
				 ) ));
			} else {
				echo sprintf(__('Tried to send an email to %s, but it failed, sorry.', WPS_TEXT_DOMAIN), $current_user->user_email).'<br /><br />';
			}
		}
	
		echo __('Your booking has been reserved, the event organiser has been informed.', WPS_TEXT_DOMAIN);
		
		if ($mi->event_cost) {
			if ($mi->event_pay_link) {
				$pay = $mi->event_pay_link;
				$pay = str_replace('##refnumber##', $eid.'/'.$new_bid, $pay);
				$pay = str_replace('##eventname##', $event->event_name, $pay);
				$pay = str_replace('##userlogin##', $current_user->ID, $pay);
				$pay = str_replace('##useremail##', $current_user->user_email, $pay);
				$pay = str_replace('##quantity##', $howmany, $pay);
				$pay = str_replace('##unitcost##', $event->event_cost, $pay);
				
				echo '<br />'.__('Please now purchase your tickets below:', WPS_TEXT_DOMAIN);
				echo '<p>'.$pay.'</p>';
			}
		}
		
	} else {

		echo __wps__show_login_link(__("You need to be <a href='%s'>logged in</a> to book events.", WPS_TEXT_DOMAIN));

	}

	exit;
}

// Update Event
if ($_POST['action'] == 'updateEvent') {

	global $wpdb;
	
	$eid = $_POST['eid'];
	$name = $_POST['name'];
	$location = $_POST['location'];
	$google_map = $_POST['google_map'];
	$desc = $_POST['desc'];
	$start_date = $_POST['start_date'];
	$start_hours = $_POST['start_hours'];
	$start_minutes = $_POST['start_minutes'];
	$end_date = $_POST['end_date'];
	$end_hours = $_POST['end_hours'];
	$end_minutes = $_POST['end_minutes'];
	$event_live	 = $_POST['event_live'];
	$enable_places = $_POST['enable_places'];
	$max_places = $_POST['max_places'];
	$show_max = $_POST['show_max'];
	$confirmation = $_POST['confirmation'];
	$tickets_per_booking = $_POST['tickets_per_booking'];
	$send_email = $_POST['send_email'];
	$email = $_POST['email'];
	$pay_link = $_POST['pay_link'];
	$cost = $_POST['cost'];
	$more = $_POST['more'];

   	$desc = strip_tags($desc);

	$more = __wps__clean_html($more);
	$email = __wps__clean_html($email);
	
	$allowedtags = array(
		'a' => array('href' => array(), 'title' => array(), 'target' => array()),
		'abbr' => array('title' => array()), 'acronym' => array('title' => array()),
		'blockquote' => array(), 
		'caption' => array(), 
		'code' => array(), 
		'pre' => array(), 
		'em' => array(), 
		'strong' => array(),
		'div' => array(), 
		'p' => array('style' => array()), 
		'ul' => array(), 
		'ol' => array(), 
		'li' => array(),
		'h1' => array(), 'h2' => array(), 'h3' => array(), 'h4' => array(), 'h5' => array(), 'h6' => array(),
		'img' => array('src' => array(), 'class' => array(), 'alt' => array(),'height' => array(),'width' => array()),
		'sup' => array(),
		'span' => array('style' => array()), 
		's' => array(), 
		'strike' => array(),
		'table' => array('style' => array(),'border' => array(),'cellspacing' => array(),'cellpadding' => array()), 
		'tbody' => array(),
		'tr' => array(),
		'td' => array('style' => array(),'valign' => array(),'align' => array(),'rowspan' => array(),'colspan' => array()), 
		'sup' => array(),
		'form' => array('action' => array(),'method' => array()), 
		'input' => array('type' => array(),'name' => array(),'value' => array(),'src' => array(),'alt' => array())
	);
   	
	$pay_link = wp_kses($pay_link, $allowedtags );	



	// Sort out dates to correct format
	if ($start_date != '') {
		$dt=explode('/',$start_date);
		$year1 = $dt[2];
		$month1 = $dt[0];
		$day1 = $dt[1];	
	} else {
		$year1 = '0000';
		$month1 = '00';
		$day1 = '00';
	}

	if ($end_date != '') {
		$dt=explode('/',$end_date);
		$year2 = $dt[2];
		$month2 = $dt[0];
		$day2 = $dt[1];	
	} else {
		$year2 = '0000';
		$month2 = '00';
		$day2 = '00';
	}

    $start = $year1."-".$month1."-".$day1." 00:00:00";
	$end = $year2."-".$month2."-".$day2." 00:00:00";
		
	if (is_user_logged_in()) {

		$wpdb->query( $wpdb->prepare( "UPDATE ".$wpdb->base_prefix."symposium_events SET 
			event_name = %s,
			event_description = %s, 
			event_location = %s, 
			event_google_map = %s,
			event_start = %s, 
			event_start_hours = %d, 
			event_start_minutes = %d, 
			event_end = %s, 
			event_end_hours = %d, 
			event_end_minutes = %d,
			event_live = %s,
			event_enable_places = %s,
			event_max_places = %d,
			event_show_max = %s,
			event_confirmation = %s,
			event_tickets_per_booking = %d,
			event_send_email = %s,
			event_email = %s,
			event_pay_link = %s,
			event_cost = %s,
			event_more = %s
			WHERE eid = %d", 
		array( 
			$name, 
			$desc,
			$location,
			$google_map,
			$start,
			$start_hours,
			$start_minutes,
			$end,
			$end_hours,
			$end_minutes,
			$event_live,
			$enable_places,
			$max_places,
			$show_max,
			$confirmation,
			$tickets_per_booking,
			$send_email,
			$email,
			$pay_link,
			$cost,
			$more,
			$eid
		 ) ));
			
	}
	
	if (WPS_DEBUG) {
		echo $wpdb->last_query;
	} else {
		echo 'OK';
	}
	exit;
	
}

// Edit Event (get details for dialog)
if ($_POST['action'] == 'editEvent') {

	global $current_user, $wpdb;
	
	$eid = $_POST['eid'];

	$sql = "SELECT * FROM ".$wpdb->base_prefix."symposium_events WHERE eid = %d";
	$event = $wpdb->get_row($wpdb->prepare($sql, $eid));

	// Prepare to return comments in JSON format
	$return_arr = array();

	
	if ($event->event_owner == $current_user->ID || __wps__get_current_userlevel() == 5) {
	
		$row_array['id'] = stripslashes($event->eid);
		$row_array['event_name'] = stripslashes($event->event_name);
		$row_array['event_owner'] = stripslashes($event->event_owner);
		$row_array['event_description'] = stripslashes($event->event_description);
		$row_array['event_location'] = stripslashes($event->event_location);
		$row_array['event_google_map'] = stripslashes($event->event_google_map);
		$row_array['start_date'] = date("m/d/Y", strtotime($event->event_start));	
		$row_array['start_hours'] = $event->event_start_hours;
		$row_array['start_minutes'] = $event->event_start_minutes;
		$row_array['end_date'] = date("m/d/Y", strtotime($event->event_end));
		$row_array['end_hours'] = $event->event_end_hours;
		$row_array['end_minutes'] = $event->event_end_minutes;
		$row_array['event_live'] = $event->event_live;
		$row_array['enable_places'] = $event->event_enable_places;
		$row_array['show_max'] = $event->event_show_max;
		$row_array['max_places'] = $event->event_max_places != null ? $event->event_max_places : 0;
		$row_array['confirmation'] = $event->event_confirmation;
		$row_array['tickets_per_booking'] = $event->event_tickets_per_booking != null ? $event->event_tickets_per_booking : 0;
		$row_array['send_email'] = $event->event_send_email;
		$row_array['email'] = stripslashes($event->event_email);
		$row_array['pay_link'] = stripslashes($event->event_pay_link);
		$row_array['cost'] = stripslashes($event->event_cost);
		$row_array['more'] = stripslashes($event->event_more);
		
		$sql = "SELECT * FROM ".$wpdb->base_prefix."symposium_events_bookings WHERE event_id = %d ORDER BY booked";
		$attendees = $wpdb->get_results($wpdb->prepare($sql, $eid));
		$a_array = array();
		if ($attendees) {
			foreach ($attendees as $attendee) {
				$a_row_array['bid'] = $attendee->bid;
				$a_row_array['uid'] = $attendee->uid;
				$a_row_array['confirmed'] = $attendee->confirmed;
				if ($attendee->email_sent != '0000-00-00 00:00:00' && $attendee->email_sent != null) {
					$a_row_array['email_sent'] = $attendee->email_sent;
				} else {
					$a_row_array['email_sent'] = '';
				}
				if ($attendee->payment_processed != '0000-00-00 00:00:00' && $attendee->payment_processed != null) {
					$a_row_array['payment_processed'] = $attendee->payment_processed;
				} else {
					$a_row_array['payment_processed'] = '';
				}
				$a_row_array['tickets'] = $attendee->tickets;
				$a_row_array['booked'] = $attendee->booked;
				$user_info = get_userdata($attendee->uid);
				$a_row_array['display_name'] = $user_info->display_name;
				$a_row_array['email'] = $user_info->user_email;

				array_push($a_array, $a_row_array);		
			}
		}
		$row_array['attendees'] = $a_array;
		array_push($return_arr, $row_array);
		
	}
	
	echo json_encode($return_arr);
	exit;
	
}

// Delete Event
if ($_POST['action'] == 'deleteEvent') {

	global $current_user, $wpdb;
	
	$eid = $_POST['eid'];

	if (is_user_logged_in()) {
		
		if ( __wps__get_current_userlevel() == 5 ) {
			$sql = "DELETE FROM ".$wpdb->base_prefix."symposium_events WHERE eid = %d";
			$rows_affected = $wpdb->query( $wpdb->prepare($sql, $eid) );
			$sql = "DELETE FROM ".$wpdb->base_prefix."symposium_events_bookings WHERE event_id = %d";
			$rows_affected = $wpdb->query( $wpdb->prepare($sql, $eid) );
		} else {
			$sql = "DELETE FROM ".$wpdb->base_prefix."symposium_events WHERE eid = %d AND event_owner = %d";
			$rows_affected = $wpdb->query( $wpdb->prepare($sql, $eid, $current_user->ID) );
			$sql = "DELETE FROM ".$wpdb->base_prefix."symposium_events_bookings WHERE event_id = %d";
			$rows_affected = $wpdb->query( $wpdb->prepare($sql, $eid) );
		}
		
	} else {
		echo __wps__show_login_link(__("You need to be <a href='%s'>logged in</a> to delete events.", WPS_TEXT_DOMAIN));
	}
	
	echo 'OK';
	exit;
	
}

// Add Event
if ($_POST['action'] == 'addEvent') {

	global $current_user, $wpdb;
	
	$name = $_POST['name'];
	$desc = $_POST['desc'];
	$location = $_POST['location'];
	$start_date = $_POST['start_date'];
	$start_hours = $_POST['start_hours'];
	$start_minutes = $_POST['start_minutes'];
	$end_date = $_POST['end_date'];
	$end_hours = $_POST['end_hours'];
	$end_minutes = $_POST['end_minutes'];
	
	// Sort out dates to correct format
	if ($start_date != '') {
		$dt=explode('/',$start_date);
		$year1 = $dt[2];
		$month1 = $dt[0];
		$day1 = $dt[1];	
	} else {
		$year1 = '0000';
		$month1 = '00';
		$day1 = '00';
	}

	if ($end_date != '') {
		$dt=explode('/',$end_date);
		$year2 = $dt[2];
		$month2 = $dt[0];
		$day2 = $dt[1];	
	} else {
		$year2 = '0000';
		$month2 = '00';
		$day2 = '00';
	}

    $start_date = $year1."-".$month1."-".$day1." 00:00:00";
	$end_date = $year2."-".$month2."-".$day2." 00:00:00";
		
	if (is_user_logged_in()) {

		$wpdb->query( $wpdb->prepare( "
			INSERT INTO ".$wpdb->base_prefix."symposium_events 
			( 	event_name,
				event_description, 
				event_location, 
				event_google_map, 
				event_created, 
				event_start, 
				event_start_hours, 
				event_start_minutes, 
				event_end, 
				event_end_hours, 
				event_end_minutes,
				event_owner,
				event_group
			)
			VALUES ( %s, %s, %s, %s, %s, %s, %d, %d, %s, %d, %d, %d, %d )", 
	        array(
	        	$name, 
	        	$desc,
	        	$location, 
	        	'', 
	        	date("Y-m-d H:i:s"), 
				$start_date,
				$start_hours,
				$start_minutes,
				$end_date,
				$end_hours,
				$end_minutes, 
				$current_user->ID, 
				0
	        	) 
	        ) );
			
	}
	
	if (WPS_DEBUG) echo $wpdb->last_query;
	
	echo 'OK';
	exit;
	
}

// Start events content
if ($_POST['action'] == 'menu_events') {

	$html = "";

	global $current_user; 
	$uid1 = $current_user->ID; // Current user
	$uid2 = $_POST['uid1']; // Which member's page is this?
	
	$privacy = __wps__get_meta($uid2, 'wall_share');		
	
	$is_friend = __wps__friend_of($uid2, $current_user->ID);
	
	if ( ($uid1 == $uid2) || (is_user_logged_in() && strtolower($privacy) == 'everyone') || (strtolower($privacy) == 'public') || (strtolower($privacy) == 'friends only' && $is_friend) || __wps__get_current_userlevel() == 5) {

		$html .= "<p class='__wps__profile_heading'>".__('Events', WPS_TEXT_DOMAIN)."</p>";

		// Create events form
		if ($uid1 == $uid2) {		

			$html .= '<input type="submit" id="__wps__create_event_button" class="__wps__button" value="'.__('Create Event', WPS_TEXT_DOMAIN).'">';
		
			$html .= '<div id="__wps__create_event_form" style="display:none">';

				$html .= '<div class="new-topic-subject label">'.__("Event Name", WPS_TEXT_DOMAIN).'</div>';
				$html .= '<input id="__wps__create_event_name" class="new-topic-subject-input" type="text" value="">';

				$html .= '<div class="new-topic-subject label">'.__("Location", WPS_TEXT_DOMAIN).'</div>';
				$html .= '<input id="__wps__create_event_location" class="new-topic-subject-input" type="text" value="">';

				$html .= '<div class="new-topic-subject label">'.__("Description", WPS_TEXT_DOMAIN).'</div>';
				$html .= '<textarea id="__wps__create_event_desc" class="new-topic-subject-text elastic"></textarea>';

				$html .= '<div>';
					$html .= '<div style="float:left; margin-right:15px;">';
						$html .= '<div class="new-topic-subject label">'.__("Start Date", WPS_TEXT_DOMAIN).'</div>';
						$html .= '<input type="text" id="event_start" style="width:100px;" class="datepicker" />';
						$html .= '<div class="new-topic-subject label">'.__("Start Time", WPS_TEXT_DOMAIN).'</div>';
						$html .= '<select id="event_start_time_hours">';
						$html .= '<option value=99>-</option>';
					 	for($i=0;$i<=23;$i++){
							$html .= '<option value='.$i.'>'.$i.'</option>';
						}
						$html .= '</select> : ';
						$html .= '<select id="event_start_time_minutes">';
						$html .= '<option value=99>-</option>';
					 	for($i=0;$i<=3;$i++){
							$html .= '<option value='.($i*15).'>'.($i*15).'</option>';
						}
						$html .= '</select>';
					$html .= '</div>';
					$html .= '<div style="float:left">';
						$html .= '<div class="new-topic-subject label">'.__("End Date", WPS_TEXT_DOMAIN).'</div>';
						$html .= '<input type="text" id="event_end" class="datepicker" />';
						$html .= '<div class="new-topic-subject label">'.__("End Time", WPS_TEXT_DOMAIN).'</div>';
						$html .= '<select id="event_end_time_hours">';
						$html .= '<option value=99>-</option>';
					 	for($i=0;$i<=23;$i++){
							$html .= '<option value='.$i.'>'.$i.'</option>';
						}
						$html .= '</select> : ';
						$html .= '<select id="event_end_time_minutes">';
						$html .= '<option value=99>-</option>';
					 	for($i=0;$i<=3;$i++){
							$html .= '<option value='.($i*15).'>'.($i*15).'</option>';
						}
						$html .= '</select>';
					$html .= '</div>';
				$html .= '</div>';

				$html .= '<div style="clear:both">';
					$html .= '<input type="submit" id="symposium_add_event_button" class="__wps__button" style="margin-top:15px" value="'.__('Create Event', WPS_TEXT_DOMAIN).'">';
					$html .= '<input type="submit" id="symposium_cancel_event_button" class="__wps__button" style="margin-top:15px" value="'.__('Cancel', WPS_TEXT_DOMAIN).'">';
				$html .= '</div>';
		
			$html .= '</div>';

		}
		
		$html .= '<div id="__wps__events_list" style="width:95%;">';
		
			if (__wps__get_current_userlevel() == 5) {
				$sql = "SELECT * FROM ".$wpdb->base_prefix."symposium_events WHERE event_owner = %d && (event_live = 'on' || event_owner = %d) ORDER BY event_start";
			} else {
				$sql = "SELECT * FROM ".$wpdb->base_prefix."symposium_events WHERE event_owner = %d ORDER BY event_start";
			}
			$events = $wpdb->get_results($wpdb->prepare($sql, $uid2, $uid1));
			if ($events) {
				foreach ($events as $event) {
					$html .= '<div class="__wps__event_list_item row">';
					
						if ( ($event->event_owner == $uid1) || (__wps__get_current_userlevel() == 5) ) {
							$html .= "<div class='__wps__event_list_item_icons'>";
							if ($event->event_live != 'on') {
								$html .= '<div style="font-style:italic;float:right;">'.__('Edit to publish', WPS_TEXT_DOMAIN).'</div>';
							}
							$html .= "<a href='javascript:void(0)' class='symposium_delete_event floatright link_cursor' style='display:none;margin-right: 5px' id='".$event->eid."'><img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/delete.png' /></a>";
							$html .= "<a href='javascript:void(0)' class='__wps__edit_event floatright link_cursor' style='display:none;margin-right: 5px' id='".$event->eid."'><img src='".get_option(WPS_OPTIONS_PREFIX.'_images')."/edit.png' /></a>";
							$html .= "</div>";
						}
					
						$html .= '<div class="__wps__event_list_name">'.stripslashes($event->event_name).'</div>';
						$html .= '<div class="__wps__event_list_location">'.stripslashes($event->event_location).'</div>';
						if ($event->event_enable_places && $event->event_show_max) {
							$sql = "SELECT SUM(tickets) FROM ".$wpdb->base_prefix."symposium_events_bookings WHERE event_id = %d";
							$taken = $wpdb->get_var($wpdb->prepare($sql, $event->eid));
							$html .= '<div class="__wps__event_list_places">';
								$html .= __('Tickets left:', WPS_TEXT_DOMAIN).' '.($event->event_max_places-$taken);
							$html .= '</div>';
						}
						$html .= '<div class="__wps__event_list_description">';
							if ($event->event_google_map == 'on') {
								$html .= "<div id='event_google_profile_map' style='float:right; margin-left:5px; width:128px; height:128px'>";
								$html .= '<a target="_blank" href="http://maps.google.co.uk/maps?f=q&amp;source=embed&amp;hl=en&amp;geocode=&amp;q='.$event->event_location.'&amp;ie=UTF8&amp;hq=&amp;hnear='.$event->event_location.'&amp;output=embed&amp;z=5" alt="Click on map to enlarge" title="Click on map to enlarge">';
								$html .= '<img src="http://maps.google.com/maps/api/staticmap?center='.$event->event_location.'&zoom=5&size=128x128&maptype=roadmap&markers=color:blue|label:&nbsp;|'.$event->event_location.'&sensor=false" />';
								$html .= "</a></div>";
							}
							$html .= str_replace(PHP_EOL, '<br />',stripslashes($event->event_description));
						$html .= '</div>';
						$html .= '<div class="__wps__event_list_dates">';
							if ($event->event_start != '0000-00-00 00:00:00') {
								$html .= date_i18n("D, d M Y", __wps__convert_datetime($event->event_start));
							}
							if ($event->event_start != $event->event_end) {
								if ($event->event_end != '0000-00-00 00:00:00') {
									$html .= ' &rarr; ';
									$html .= date_i18n("D, d M Y", __wps__convert_datetime($event->event_end));
								}
							}
						$html .= '</div>';
						$html .= '<div class="__wps__event_list_times">';
							if ($event->event_start_hours != 99) {
								$html .= __('Start: ', WPS_TEXT_DOMAIN).$event->event_start_hours.":".sprintf('%1$02d', $event->event_start_minutes);
							}
							if ($event->event_end_hours != 99) {
								$html .= ' '.__('End: ', WPS_TEXT_DOMAIN).$event->event_end_hours.":".sprintf('%1$02d', $event->event_end_minutes);
							}
						$html .= '</div>';

						if ($event->event_more) {
							$content = stripslashes($event->event_more);

							$content = __wps__youtube($content,$autoplay=0,$width=480,$height=390);
																				
							if (!get_option(WPS_OPTIONS_PREFIX.'_events_use_wysiwyg')) {
								$content = str_replace(PHP_EOL, '<br />', $content);
							}
							
							$more = '<div style="text-align:left">'.str_replace(chr(10), '<br />', stripslashes($event->event_more)).'</div>';
							$html .= '<div id="symposium_more_'.$event->eid.'" title="'.stripslashes($event->event_name).'" class="__wps__dialog_content">'.$more.'</div>';
							$html .= '<input type="submit" id="symposium_event_more" rel="symposium_more_'.$event->eid.'" class="symposium-dialog __wps__button" value="'.__("More info", WPS_TEXT_DOMAIN).'" />';
						}
						if (is_user_logged_in() && $event->event_enable_places) {
							// check to see if already booked
							$sql = "select tickets, confirmed, bid, payment_processed FROM ".$wpdb->base_prefix."symposium_events_bookings WHERE event_id = %d AND uid = %d";
							$ret = $wpdb->get_row($wpdb->prepare($sql, $event->eid, $current_user->ID));
							if ($ret && !$ret->confirmed && $ret->tickets ) {
								if (!$ret->payment_processed) {
									$html .= '<input type="submit" id="symposium_pay_event" data-bid="'.$ret->bid.'"  class="__wps__button" value="'.__("Payment", WPS_TEXT_DOMAIN).'" /><br />';
								}
								$html .= sprintf(_n('Awaiting confirmation from the organiser for %d ticket.','Awaiting confirmation from the organiser for %d tickets.', $ret->tickets, WPS_TEXT_DOMAIN), $ret->tickets);
							}
							if (!$ret || !$ret->tickets) {
								$html .= '<input type="submit" id="symposium_book_event" data-eid="'.$event->eid.'" data-max="'.$event->event_tickets_per_booking.'" class="__wps__button" value="'.__("Book", WPS_TEXT_DOMAIN).'" /><br />';
							} else {
								$html .= '<input type="submit" id="symposium_cancel_event" data-eid="'.$event->eid.'" class="__wps__button" value="'.__("Cancel", WPS_TEXT_DOMAIN).'" style="margin:0" /><br />';
							}
						}
					$html .= '</div>';
				}
			} else {
				$html .= __('No events yet.', WPS_TEXT_DOMAIN);
			}

		
		$html .= '</div>';


	}

	// This filter allows others to filter output
	$html = apply_filters ( '__wps__my_events_page_filter', $html);
	
	echo $html;
	exit;	
}

// Get events calendar
if ($_POST['action'] == 'getEvents') {
	
	$html = "EVENTS";
	echo $html;
	exit;
	
}

// Replaces confirmation email fields
function __wps__events_confirm_email_fields($text, $eid, $bid, $uid) {

	$user_info = get_userdata($uid);

	$text = str_replace('##displayname##', $user_info->display_name, $text);
	$text = str_replace('##email##', $user_info->user_email, $text);
	$text = str_replace('##refnumber##', $eid.'/'.$bid, $text);

	if (!get_option(WPS_OPTIONS_PREFIX.'_events_use_wysiwyg')) {
		$text = str_replace(PHP_EOL, '<br />', $text);
	}

	return $text;

}


function __wps__youtube($string,$autoplay=0,$width=480,$height=390)
{
    preg_match('#(?:http://)?(?:www\.)?(?:youtube\.com/(?:v/|watch\?v=)|youtu\.be/)([\w-]+)(?:\S+)?#', $string, $match);
    
    if (isset($match[1])) {
        
$embed = <<<YOUTUBE
<div align="center">
<iframe title="YouTube video player" width="$width" height="$height" src="http://www.youtube.com/embed/$match[1]?autoplay=$autoplay" frameborder="0" allowfullscreen></iframe>
</div>
YOUTUBE;
	
	    return str_replace($match[0], $embed, $string);
	    
    } else {
        
        return $string;
        
    }
}

		
?>

	
