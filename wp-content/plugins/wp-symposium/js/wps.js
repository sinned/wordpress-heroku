jQuery(document).ready(function() { 

	/* Set global javascript variables */

	var show_js_errors = false; // Show errors returned from .ajax calls
	// Force current user ID if using permalinks
	if (jQuery('#__wps__current_user_page').length) {
		__wps__.current_user_page = jQuery('#__wps__current_user_page').html();
	}


/*
	   +------------------------------------------------------------------------------------------+
	   |                                          SHARED                                          |
	   +------------------------------------------------------------------------------------------+
	*/

	// Basic file upload alternative

	if (__wps__.current_user_id > 0) {
		jQuery('#basic_file_upload').live('click', function() {
			window.open('http://www.google.com/', 'asdas', 'toolbars=0,width=400,height=320,left=200,top=200,scrollbars=1,resizable=1');
		});
	}


	// Show/hide a div (with passed ID)
	jQuery(".symposium_expand").click(function() {
		jQuery(this).hide();
		jQuery(this).next(".expand_this").slideDown("slow");
	});

	// Get translated strings
	var symposium_clear = __wps__.clear;
	var symposium_update = __wps__.update;
	var symposium_cancel = __wps__.cancel;
	var write_a_comment = __wps__.write_a_comment;
	var btn_add = __wps__.add;
	var show_original = __wps__.show_original;
	var add_a_comment = __wps__.add_a_comment;
	var pleasewait = __wps__.pleasewait;
	var saving = __wps__.saving;
	var more = __wps__.more;
	var browseforfile = __wps__.browseforfile;
	var attachfile = __wps__.attachfile;
	var attachimage = __wps__.attachimage;
	var whatsup = __wps__.whatsup;
	var symposium_next = __wps__.next;
	var symposium_likes = __wps__.likes;
	var symposium_dislikes = __wps__.dislikes;
	var areyousure = __wps__.areyousure;

	// Centre in screen
	jQuery.fn.inmiddle = function() {
		this.css("position", "absolute");
		this.css("top", (jQuery(window).height() - this.height()) / 2 + jQuery(window).scrollTop() + "px");
		this.css("left", (jQuery(window).width() - this.width()) / 2 + jQuery(window).scrollLeft() + "px");
		return this;
	}

	// Check if really want to delete	    
	jQuery(".delete").click(function() {
		var answer = confirm(areyousure);
		return answer // answer is a boolean
	});
	jQuery(".deletebutton").live('click', function() {
		var answer = confirm(areyousure);
		return answer // answer is a boolean
	});

	// Global dialog
	jQuery('.symposium-dialog').live('click', function() {
		var id = jQuery(this).attr("rel");
		var title = jQuery('#' + id).attr("title");
		var str = '<div style="width:100%; text-align:center">' + jQuery('#' + id).html() + '</div>';
		jQuery("#dialog").html(str).dialog({
			title: title,
			width: 800,
			height: 500,
			modal: true,
			buttons: {
				"OK": function() {
					jQuery("#dialog").dialog('close');
				}
			}
		});
	});

/*
	   +------------------------------------------------------------------------------------------+
	   |                                       WIDGETS (AJAX)                                     |
	   +------------------------------------------------------------------------------------------+
	*/

	if (jQuery("#symposium_summary_Widget").length) {
		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/widget_functions.php",
			data: ({
				action: "symposium_summary_Widget",
				show_loggedout: jQuery("#symposium_summary_Widget_show_loggedout").html(),
				form: jQuery("#symposium_summary_Widget_form").html(),
				login_url: jQuery("#symposium_summary_Widget_login_url").html(),
				show_avatar: jQuery("#symposium_summary_Widget_show_avatar").html(),
				show_avatar_size: jQuery("#symposium_summary_Widget_show_avatar_size").html(),
				login_username: jQuery("#symposium_summary_Widget_login_username").html(),
				login_password: jQuery("#symposium_summary_Widget_login_password").html(),
				login_remember_me: jQuery("#symposium_summary_Widget_login_remember_me").html(),
				login_button: jQuery("#symposium_summary_Widget_login_button").html(),
				login_forgot: jQuery("#symposium_summary_Widget_login_forgot").html(),
				login_register: jQuery("#symposium_summary_Widget_login_register").html(),
			}),
			type: "POST",
			dataType: "html",
			async: true,
			success: function(str) {
				if (str.substring(0, 4) == 'FAIL') {
					jQuery("#dialog").html(str).dialog({
						title: __wps__.site_title + ' debug',
						width: 800,
						height: 500,
						modal: true
					});
				} else {
					jQuery("#symposium_summary_Widget").html(str);
				}
			}
		});
	}

	if (jQuery("#symposium_friends_Widget").length) {
		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/widget_functions.php",
			data: ({
				action: "symposium_friends_Widget",
				count: jQuery("#symposium_friends_count").html(),
				desc: jQuery("#symposium_friends_desc").html(),
				mode: jQuery("#symposium_friends_mode").html(),
				show_light: jQuery("#symposium_friends_show_light").html(),
				show_mail: jQuery("#symposium_friends_show_mail").html()
			}),
			type: "POST",
			dataType: "html",
			async: true,
			success: function(str) {
				if (str.substring(0, 4) == 'FAIL') {
					jQuery("#dialog").html(str).dialog({
						title: __wps__.site_title + ' debug info',
						width: 800,
						height: 500,
						modal: true
					});
				} else {
					jQuery("#symposium_friends_Widget").html(str);
				}
			}
		});
	}

	if (jQuery("#__wps__Forumexperts_Widget").length) {
		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/widget_functions.php",
			data: ({
				action: "Forumexperts_Widget",
				cat_id: jQuery("#__wps__Forumexperts_Widget_cat_id").html(),
				cat_id_exclude: jQuery("#__wps__Forumexperts_Widget_cat_id_exclude").html(),
				timescale: jQuery("#__wps__Forumexperts_Widget_timescale").html(),
				postcount: jQuery("#__wps__Forumexperts_Widget_postcount").html(),
				groups: jQuery("#__wps__Forumexperts_Widget_groups").html()
			}),
			type: "POST",
			dataType: "html",
			async: true,
			success: function(str) {
				if (str.substring(0, 4) == 'FAIL') {
					jQuery("#dialog").html(str).dialog({
						title: __wps__.site_title + ' debug info',
						width: 800,
						height: 500,
						modal: true
					});
				} else {
					jQuery("#__wps__Forumexperts_Widget").html(str);
				}
			}
		});
	}

	if (jQuery("#__wps__Forumnoanswer_Widget").length) {
		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/widget_functions.php",
			data: ({
				action: "Forumnoanswer_Widget",
				preview: jQuery("#__wps__Forumnoanswer_Widget_preview").html(),
				cat_id: jQuery("#__wps__Forumnoanswer_Widget_cat_id").html(),
				cat_id_exclude: jQuery("#__wps__Forumnoanswer_Widget_cat_id_exclude").html(),
				timescale: jQuery("#__wps__Forumnoanswer_Widget_timescale").html(),
				postcount: jQuery("#__wps__Forumnoanswer_Widget_postcount").html(),
				groups: jQuery("#__wps__Forumnoanswer_Widget_groups").html()
			}),
			type: "POST",
			dataType: "html",
			async: true,
			success: function(str) {
				if (str.substring(0, 4) == 'FAIL') {
					jQuery("#dialog").html(str).dialog({
						title: __wps__.site_title + ' debug info',
						width: 800,
						height: 500,
						modal: true
					});
				} else {
					jQuery("#__wps__Forumnoanswer_Widget").html(str);
				}
			}
		});
	}

	if (jQuery("#__wps__recent_Widget").length) {
		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/widget_functions.php",
			data: ({
				action: "recent_Widget",
				count: jQuery("#__wps__recent_Widget_count").html(),
				desc: jQuery("#__wps__recent_Widget_desc").html(),
				show_light: jQuery("#__wps__recent_Widget_show_light").html(),
				show_mail: jQuery("#__wps__recent_Widget_show_mail").html()
			}),
			type: "POST",
			dataType: "html",
			async: true,
			success: function(str) {
				if (str.substring(0, 4) == 'FAIL') {
					jQuery("#dialog").html(str).dialog({
						title: __wps__.site_title + ' debug info',
						width: 800,
						height: 500,
						modal: true
					});
				} else {
					jQuery("#__wps__recent_Widget").html(str);
				}
			}
		});
	}

	if (jQuery("#symposium_members_Widget").length) {
		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/widget_functions.php",
			data: ({
				action: "members_Widget",
				count: jQuery("#symposium_members_Widget_count").html()
			}),
			type: "POST",
			dataType: "html",
			async: true,
			success: function(str) {
				if (str.substring(0, 4) == 'FAIL') {
					jQuery("#dialog").html(str).dialog({
						title: __wps__.site_title + ' debug info',
						width: 800,
						height: 500,
						modal: true
					});
				} else {
					jQuery("#symposium_members_Widget").html(str);
				}
			}
		});
	}

	if (jQuery("#__wps__friends_status_Widget").length) {
		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/widget_functions.php",
			data: ({
				action: "friends_status_Widget",
				postcount: jQuery("#__wps__friends_status_postcount").html(),
				preview: jQuery("#__wps__friends_status_preview").html(),
				forum: jQuery("#__wps__friends_status_forum").html()
			}),
			type: "POST",
			dataType: "html",
			async: true,
			success: function(str) {
				if (str.substring(0, 4) == 'FAIL') {
					jQuery("#dialog").html(str).dialog({
						title: __wps__.site_title + ' debug info',
						width: 800,
						height: 500,
						modal: true
					});
				} else {
					jQuery("#__wps__friends_status_Widget").html(str);
				}
			}
		});
	}

	if (jQuery("#symposium_Recentactivity_Widget").length) {
		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/widget_functions.php",
			data: ({
				action: "Recentactivity_Widget",
				postcount: jQuery("#symposium_Recentactivity_Widget_postcount").html(),
				preview: jQuery("#symposium_Recentactivity_Widget_preview").html(),
				forum: jQuery("#symposium_Recentactivity_Widget_forum").html()
			}),
			type: "POST",
			dataType: "html",
			async: true,
			success: function(str) {
				if (str.substring(0, 4) == 'FAIL') {
					jQuery("#dialog").html(str).dialog({
						title: __wps__.site_title + ' debug info',
						width: 800,
						height: 500,
						modal: true
					});
				} else {
					jQuery("#symposium_Recentactivity_Widget").html(str);
				}
			}
		});
	}

	if (jQuery("#__wps__Forumrecentposts_Widget").length) {
		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/widget_functions.php",
			data: ({
				action: "__wps__Forumrecentposts_Widget",
				postcount: jQuery("#__wps__Forumrecentposts_Widget_postcount").html(),
				preview: jQuery("#__wps__Forumrecentposts_Widget_preview").html(),
				cat_id: jQuery("#__wps__Forumrecentposts_Widget_cat_id").html(),
				show_replies: jQuery("#__wps__Forumrecentposts_Widget_show_replies").html(),
				incl_cat: jQuery("#__wps__Forumrecentposts_Widget_incl_cat").html(),
				incl_parent: jQuery("#__wps__Forumrecentposts_Widget_incl_parent").html(),
				just_own: jQuery("#__wps__Forumrecentposts_Widget_just_own").html()
			}),
			type: "POST",
			dataType: "html",
			async: true,
			success: function(str) {
				if (str.substring(0, 4) == 'FAIL') {
					jQuery("#dialog").html(str).dialog({
						title: __wps__.site_title + ' debug info',
						width: 800,
						height: 500,
						modal: true
					});
				} else {
					jQuery("#__wps__Forumrecentposts_Widget").html(str);
				}
			}
		});
	}

	if (jQuery("#symposium_Gallery_Widget").length) {
		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/gallery_functions.php",
			data: ({
				action: "Gallery_Widget",
				albumcount: jQuery("#symposium_Gallery_Widget_albumcount").html()
			}),
			type: "POST",
			dataType: "html",
			async: true,
			success: function(str) {
				if (str.substring(0, 4) == 'FAIL') {
					jQuery("#dialog").html(str).dialog({
						title: __wps__.site_title + ' debug info',
						width: 800,
						height: 500,
						modal: true
					});
				} else {
					jQuery("#symposium_Gallery_Widget").html(str);
				}
			}
		});
	}

	
	if (jQuery("#__wps__Alerts_Widget").length) {
		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/widget_functions.php",
			data: ({
				action: "Alerts_Widget",
				postcount: jQuery("#__wps__Alerts_Widget_postcount").html()
			}),
			type: "POST",
			dataType: "html",
			async: true,
			success: function(str) {
				if (str.substring(0, 4) == 'FAIL') {
					jQuery("#dialog").html(str).dialog({
						title: __wps__.site_title + ' debug info',
						width: 800,
						height: 500,
						modal: true
					});
				} else {
					jQuery("#__wps__Alerts_Widget").html(str);
				}
			}
		});
	}		
	


/*
	   +------------------------------------------------------------------------------------------+
	   |                                     MEMBER DIRECTORY                                     |
	   +------------------------------------------------------------------------------------------+
	*/



	// Show advanced search
	jQuery('#symposium_show_advanced').live('click', function() {
		jQuery('#symposium_advanced_search').toggle();
		jQuery('#symposium_show_advanced').hide();
	});

	// Show mail link on friend hover
	jQuery('.members_row').live('mouseenter mouseleave', function(event) {
		if (event.type == 'mouseenter') {
			jQuery(this).find(".mail_icon").show();
		} else {
			jQuery(this).find(".mail_icon").hide();
		}
	});

	jQuery('#symposium_member').live('keydown', function(e) {
		var keyCode = e.keyCode || e.which;

		if (keyCode == 9 || keyCode == 27) {
			jQuery('#symposium_member_list').hide();
		}

	});

	// Order by
	jQuery('#symposium_members_orderby').change(function() {
		jQuery("#symposium_directory_start").html(0);
		symposium_do_member_search(true);
	});

	jQuery('#members_go_button').live('click', function() {
		jQuery("#symposium_directory_start").html(0);
		symposium_do_member_search(true);
	});
	jQuery('#symposium_member').live('keypress', function(e) {
		if (e.keyCode == 13) {
			jQuery("#symposium_directory_start").html(0);
			symposium_do_member_search(true);
		}
	});

	// Search
	jQuery('#showmore_directory').live('click', function() {
		symposium_do_member_search(false);
	});

	function symposium_do_member_search(clear) {
		
		if (__wps__.dir_full_ver) {
			jQuery('#symposium_members_orderby_div').hide();
		}

		if (clear) {
			jQuery('#__wps__members').html("<img src='" + __wps__.images_url + "/busy.gif' />");
		} else {
			jQuery('#showmore_directory_div').html("<br /><img src='" + __wps__.images_url + "/busy.gif' />");
		}

		// check for extended fields
		if (jQuery(".symposium_extended_search").length && jQuery("#symposium_advanced_search").css("display") != 'none') {
			var extended = new Array();
			jQuery(".symposium_extended_search").each(function(index) {
				var eid = jQuery(this).attr("id");
				switch (jQuery(this).attr("rel")) {
				case 'list':
					var value = jQuery(this).val();
					break;
				case 'checkbox':
					if (jQuery(this).is(":checked")) {
						var value = 'on';
					} else {
						var value = '';
					};
					break;
				}
				extended.push(jQuery(this).attr("rel") + '|' + eid + '|' + value);
			});
		} else {
			var extended = '';
		}

		var page_length = jQuery('#symposium_directory_page_length').html();
		var start = jQuery("#symposium_directory_start").html();

		var friends = '';
		if (jQuery("#symposium_member_friends").is(":checked")) {
			var friends = 'on';
		};

		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/members_functions.php",
			type: "POST",
			data: ({
				action: "getMembers",
				friends: friends,
				start: start,
				orderby: jQuery('#symposium_members_orderby').val(),
				page_length: jQuery('#symposium_directory_page_length').html(),
				term: jQuery('#symposium_member').val(),
				extended: extended,
				roles: jQuery('#__wps__directory_roles').html()
			}),
			dataType: "html",
			async: true,
			success: function(str) {

				if (__wps__.debug == 'on') {
					alert(str);
				}

				if (clear) {
					jQuery("#__wps__members").html('');
				}
				jQuery('#showmore_directory_div').remove();
				var new_start = parseFloat(start) + parseFloat(page_length) + 1;
				jQuery("#symposium_directory_start").html(new_start);

				if (start == 0) {
					jQuery('#__wps__members').html(str);
				} else {
					jQuery(str).appendTo('#__wps__members').hide().slideDown("slow");
				}

			},
			error: function(xhr, ajaxOptions, thrownError) {
				if (show_js_errors) {
					alert(xhr.status);
					alert(xhr.statusText);
					alert(thrownError);
				}
			}
		});
	}

	// Autocomplete
	if (jQuery("input#symposium_member").length) {

		jQuery("input#symposium_member").autocomplete({
			source: function(request,response) {
				jQuery.ajax({
					url: __wps__.plugin_url + "ajax/members_functions.php",
					data: {
						term: request.term,
						roles: jQuery('#__wps__directory_roles').html()
					},
					dataType: "json",
					success: function(data) {
						response(data);
					}
				});				
			},
			minLength: 3,
			focus: function(event, ui) {
				jQuery("input#symposium_member").val(ui.item.name);
				return false;
			},
			select: function(event, ui) {
				jQuery(".__wps__pleasewait").inmiddle().show().delay(3000).fadeOut("slow");
				var q = symposium_q(__wps__.profile_url);
				window.location.href = __wps__.profile_url + q + 'uid=' + ui.item.value;
				return false;
			}
		}).data("uiAutocomplete")._renderItem = function(ul, item) {
			var group = "<a>";
			group += "<div style='height:40px; overflow:hidden'>";
			group += "<div style=\'float:left; background-color:#fff; margin-right: 8px; width:40px; height:40px; \'>";
			group += item.avatar;
			group += "</div>";
			group += "<div>" + item.name + "</div>";
			var sep = (item.city != '' && item.country != '') ? ', ' : '';
			group += "<div style='font-size:80%'>" + item.city + sep + item.country + "</div>";
			group += "<br style='clear:both' />";
			group += "</div>";
			group += "</a>";
			return jQuery("<li></li>").data("item.autocomplete", item).append(group).appendTo(ul);
		};
	}



/*
	   +------------------------------------------------------------------------------------------+
	   |                                           MAIL                                           |
	   +------------------------------------------------------------------------------------------+
	*/
	// Switch tabs
	jQuery("#symposium_compose_tab").live('click', function() {

		jQuery('#symposium_compose_tab').removeClass("nav-tab-inactive");
		jQuery('#symposium_compose_tab a').removeClass("nav-tab-inactive-link");
		jQuery('#symposium_compose_tab').addClass("nav-tab-active");
		jQuery('#symposium_compose_tab a').addClass("nav-tab-active-link");

		jQuery('#symposium_inbox_tab').removeClass("nav-tab-active");
		jQuery('#symposium_inbox_tab a').removeClass("nav-tab-active-link");
		jQuery('#symposium_inbox_tab').addClass("nav-tab-inactive");
		jQuery('#symposium_inbox_tab a').addClass("nav-tab-inactive-link");

		jQuery('#symposium_sent_tab').removeClass("nav-tab-active");
		jQuery('#symposium_sent_tab a').removeClass("nav-tab-active-link");
		jQuery('#symposium_sent_tab').addClass("nav-tab-inactive");
		jQuery('#symposium_sent_tab a').addClass("nav-tab-inactive-link");

		// Clear the form
		jQuery('#mail_recipient_id').html('');
		jQuery('#compose_recipient_name').val('');
		jQuery('#compose_subject').val('');
		jQuery('#compose_text').val('');
		jQuery('#compose_previous').val('');
		jQuery("#mail_sent_message").hide();
		jQuery("#compose_form").show();

		jQuery("#compose_form").show();
		jQuery("#mail-main-div #mailbox").hide();

	});
	jQuery("#symposium_inbox_tab").live('click', function() {

		jQuery('#symposium_compose_tab').removeClass("nav-tab-active");
		jQuery('#symposium_compose_tab a').removeClass("nav-tab-active-link");
		jQuery('#symposium_compose_tab').addClass("nav-tab-inactive");
		jQuery('#symposium_compose_tab a').addClass("nav-tab-inactive-link");

		jQuery('#symposium_inbox_tab').removeClass("nav-tab-inactive");
		jQuery('#symposium_inbox_tab a').removeClass("nav-tab-inactive-link");
		jQuery('#symposium_inbox_tab').addClass("nav-tab-active");
		jQuery('#symposium_inbox_tab a').addClass("nav-tab-active-link");

		jQuery('#symposium_sent_tab').removeClass("nav-tab-active");
		jQuery('#symposium_sent_tab a').removeClass("nav-tab-active-link");
		jQuery('#symposium_sent_tab').addClass("nav-tab-inactive");
		jQuery('#symposium_sent_tab a').addClass("nav-tab-inactive-link");

		jQuery("#compose_form").hide();
		jQuery("#mail-main-div #mailbox").show();

		change_tray();

	});
	jQuery("#symposium_sent_tab").live('click', function() {

		jQuery('#symposium_compose_tab').removeClass("nav-tab-active");
		jQuery('#symposium_compose_tab a').removeClass("nav-tab-active-link");
		jQuery('#symposium_compose_tab').addClass("nav-tab-inactive");
		jQuery('#symposium_compose_tab a').addClass("nav-tab-inactive-link");

		jQuery('#symposium_inbox_tab').removeClass("nav-tab-active");
		jQuery('#symposium_inbox_tab a').removeClass("nav-tab-active-link");
		jQuery('#symposium_inbox_tab').addClass("nav-tab-inactive");
		jQuery('#symposium_inbox_tab a').addClass("nav-tab-inactive-link");

		jQuery('#symposium_sent_tab').removeClass("nav-tab-inactive");
		jQuery('#symposium_sent_tab a').removeClass("nav-tab-inactive-link");
		jQuery('#symposium_sent_tab').addClass("nav-tab-active");
		jQuery('#symposium_sent_tab a').addClass("nav-tab-active-link");

		jQuery("#compose_form").hide();
		jQuery("#mail-main-div #mailbox").show();

		change_tray();

	});

	// Go straight to compose form
	if (__wps__.view == 'compose') {

		jQuery(".__wps__pleasewait").inmiddle().show();

		var mail_to = __wps__.to;
		jQuery('#mail_recipient_list').val(mail_to);

		jQuery("#compose_form").show();
		jQuery("#mail-main-div #mailbox").hide();

		jQuery('#compose_subject').focus();
		jQuery(".__wps__pleasewait").fadeOut("slow");

		__wps__.view = 'in';

		// Switch tabs
		jQuery('#symposium_compose_tab').removeClass("nav-tab-inactive");
		jQuery('#symposium_compose_tab a').removeClass("nav-tab-inactive-link");
		jQuery('#symposium_compose_tab').addClass("nav-tab-active");
		jQuery('#symposium_compose_tab a').addClass("nav-tab-active-link");

		jQuery('#symposium_inbox_tab').removeClass("nav-tab-active");
		jQuery('#symposium_inbox_tab a').removeClass("nav-tab-active-link");
		jQuery('#symposium_inbox_tab').addClass("nav-tab-inactive");
		jQuery('#symposium_inbox_tab a').addClass("nav-tab-inactive-link");

		jQuery('#symposium_sent_tab').removeClass("nav-tab-active");
		jQuery('#symposium_sent_tab a').removeClass("nav-tab-active-link");
		jQuery('#symposium_sent_tab').addClass("nav-tab-inactive");
		jQuery('#symposium_sent_tab a').addClass("nav-tab-inactive-link");

	};

	// Default load	
	if (jQuery("#compose_form").length && __wps__.view != 'compose') {

		// Load box on first page load
		jQuery('#mailbox_list').html("<img src='" + __wps__.images_url + "/busy.gif' />");
		jQuery('#messagebox').html("<img src='" + __wps__.images_url + "/busy.gif' />");

		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/mail_functions.php",
			type: "POST",
			data: ({
				uid: __wps__.current_user_page,
				action: "getBox",
				tray: "in",
				unread: jQuery("#unread_only").is(":checked"),
				term: "",
				start: jQuery('#next_message_id').html(),
				length: 5
			}),
			dataType: "html",
			async: true,
			success: function(str) {

				if (strpos(str, 'mail_mid')) {

					var html = "";

					var msg_count = 0;

					var template = __wps__.template_mail_tray;
                    

					template = template.replace(/\\/g, '');
					template = template.replace(/&lt;/g, '<');
					template = template.replace(/&gt;/g, '>');
					template = template.replace(/\[\]/g, '');

					var rows = jQuery.parseJSON(str);
					jQuery.each(rows, function(i, row) {

						msg_count++;
						if (msg_count == 1) {
							jQuery('#next_message_id').html(row.next_message_id);
						} else {

							if (html == "") {

								// Check for default mail ID
								var mail_id = row.mail_mid;
								if (__wps__.mail_id != '') {
									mail_id = __wps__.mail_id;
								}

								// Show first message as default message
								jQuery.ajax({
									url: __wps__.plugin_url + "ajax/mail_functions.php",
									type: "POST",
									data: ({
										action: "getMailMessage",
										tray: "in",
										mid: mail_id
									}),
									dataType: "html",
									async: true,
									success: function(str) {
										var details = str.split("[split]");
										if (details[2] == "in") {
											jQuery("#" + details[0]).removeClass("row");
											jQuery("#" + details[0]).addClass(row.mail_read);
										}
										jQuery("#messagebox").html(details[3]);
										if (details[1] > 0) {
											jQuery("#in_unread").html('(' + details[1] + ')');
										} else {
											jQuery("#in_unread").html('');
										}
										jQuery(".__wps__pleasewait").fadeOut("slow");
									},
									error: function(xhr, ajaxOptions, thrownError) {
										if (show_js_errors) {
											alert(xhr.status);
											alert(xhr.statusText);
											alert(thrownError);
										}
									}
								});
							}

							var new_item = template;
							new_item = new_item.replace(/mail_mid/, row.mail_mid);
							new_item = new_item.replace(/mail_read/, row.mail_read);
							new_item = new_item.replace(/\[mail_sent\]/, row.mail_sent);
							new_item = new_item.replace(/\[mail_from\]/, row.mail_from);
							new_item = new_item.replace(/\[mail_subject\]/, row.mail_subject);
							new_item = new_item.replace(/\[bulk_action\]/, "<input type='checkbox' data-mid='"+row.mail_mid+"' class='bulk_action'>");

							if (row.message != null) {
								new_item = new_item.replace(/\[mail_message\]/, row.message);
							} else {
								new_item = new_item.replace(/\[mail_message\]/, '');
							}
							html += new_item;

						}

					});

					html += '<div id="show_more_mail" style="text-align:center; padding:6px; cursor:pointer;">' + more + '</div>';
					jQuery('#mailbox_list').html(html);

				} else {

					jQuery('#mailbox_list').html('');
					jQuery('#messagebox').html('');

				}

			},
			error: function(xhr, ajaxOptions, thrownError) {
				if (show_js_errors) {
					alert(xhr.status);
					alert(xhr.statusText);
					alert(thrownError);
				}
			}
		});

	}
	
	// Mail bulk actions
	jQuery(".bulk_action").live('click', function() {
		if (jQuery('.bulk_action').is(":checked")) {
			jQuery('#__wps__mail_bulk_action').val('');
		} else {
			jQuery('#__wps__mail_bulk_action').val('');
		}
	});
	jQuery('#__wps__mail_bulk_action').change(function() {
		var action = jQuery('#__wps__mail_bulk_action').val();
		// bulk: delete all
		if (action == 'deleteall') {
			if (confirm(areyousure)) {
				// check which tray
				var tray = 'in';
				if (jQuery("#symposium_sent_tab").hasClass("nav-tab-active"))
					var tray = 'sent';
				// do the delete
				jQuery(".__wps__pleasewait").inmiddle().show();
				jQuery.ajax({
					url: __wps__.plugin_url + "ajax/mail_functions.php",
					type: "POST",
					data: ({
						action: "bulk_delete",
						data: '',
						tray: tray,
						uid: __wps__.current_user_id,
						scope: 'all'
					}),
					dataType: "html",
					async: false,
					success: function(str) {
						if (str != 'OK') jQuery("#dialog").html(str).dialog({ title: __wps__.site_title+' debug info', width: 800, height: 500, modal: true });
					}
				});
				change_tray();
				jQuery(".__wps__pleasewait").fadeOut("slow");
			}
		}

		// bulk: recover all
		if (action == 'recoverall') {
			if (confirm(areyousure)) {
				// check which tray
				var tray = 'in';
				if (jQuery("#symposium_sent_tab").hasClass("nav-tab-active"))
					var tray = 'sent';
				// do the delete
				jQuery(".__wps__pleasewait").inmiddle().show();
				jQuery.ajax({
					url: __wps__.plugin_url + "ajax/mail_functions.php",
					type: "POST",
					data: ({
						action: "bulk_recover",
						tray: tray,
						uid: __wps__.current_user_id
					}),
					dataType: "html",
					async: false,
					success: function(str) {
						if (str != 'OK') jQuery("#dialog").html(str).dialog({ title: __wps__.site_title+' debug info', width: 800, height: 500, modal: true });
					}
				});
				change_tray();
				jQuery(".__wps__pleasewait").fadeOut("slow");
			}
		}

		// bulk: mark all as read
		if (action == 'readall') {
			if (confirm(areyousure)) {
				// do the mark
				jQuery(".__wps__pleasewait").inmiddle().show();
				// check which tray
				var tray = 'in';
				if (jQuery("#symposium_sent_tab").hasClass("nav-tab-active")) {
					alert('You cannot mark sent mail as read!');
				} else {
					jQuery.ajax({
						url: __wps__.plugin_url + "ajax/mail_functions.php",
						type: "POST",
						data: ({
							action: "bulk_readall",
							uid: __wps__.current_user_id
						}),
						dataType: "html",
						async: false,
						success: function(str) {
							if (str != 'OK') jQuery("#dialog").html(str).dialog({ title: __wps__.site_title+' debug info', width: 800, height: 500, modal: true });
						}
					});
					change_tray();
				}
				jQuery(".__wps__pleasewait").fadeOut("slow");
			}
		}
						
								
		// bulk: delete checked
		if (action == 'delete') {
			if (jQuery('.bulk_action').is(":checked")) {
				if (confirm(areyousure)) {
					var bulk_list = [];
					jQuery('.bulk_action').each(function(){
						var this_mail = jQuery(this);
						if (typeof this_mail != 'undefined' && this_mail.attr("data-mid") != 'undefined' && this_mail.is(":checked")) {
							bulk_list.push(this_mail.attr("data-mid"));
							if (action == 'delete') {
								jQuery(this_mail).hide();
								jQuery('#'+this_mail.attr("data-mid")).hide();
							}
						}
					});	
					jQuery('.bulk_action').attr('checked', false);
					// check which tray
					var tray = 'in';
					if (jQuery("#symposium_sent_tab").hasClass("nav-tab-active"))
						var tray = 'sent';
					// do the delete
					jQuery(".__wps__pleasewait").inmiddle().show();
					jQuery.ajax({
						url: __wps__.plugin_url + "ajax/mail_functions.php",
						type: "POST",
						data: ({
							action: "bulk_delete",
							data: bulk_list,
							tray: tray,
							uid: __wps__.current_user_id,
							scope: 'marked'
						}),
						dataType: "html",
						async: false,
						success: function(str) {
							if (str != 'OK') jQuery("#dialog").html(str).dialog({ title: __wps__.site_title+' debug info', width: 800, height: 500, modal: true });
						}
					});
					change_tray();
					jQuery(".__wps__pleasewait").fadeOut("slow");
				}	
			} else {
				jQuery('#__wps__mail_bulk_action').val('');
			}
		}				
	});

	// Clicked on More...
	jQuery("#show_more_mail").live('click', function() {
		
		var tray = 'in';
		if (jQuery("#symposium_sent_tab").hasClass("nav-tab-active")) {
			var tray = 'sent';
		};

		jQuery('#show_more_mail').html("<img src='" + __wps__.images_url + "/busy.gif' />");

		var template = __wps__.template_mail_tray;

		template = template.replace(/\\/g, '');
		template = template.replace(/&lt;/g, '<');
		template = template.replace(/&gt;/g, '>');
		template = template.replace(/\[\]/g, '');

		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/mail_functions.php",
			type: "POST",
			data: ({
				uid: __wps__.current_user_page,
				action: "getBox",
				tray: tray,
				unread: jQuery("#unread_only").is(":checked"),
				term: "",
				start: jQuery('#next_message_id').html()
			}),
			dataType: "html",
			async: true,
			success: function(str) {

				if (strpos(str, 'mail_mid')) {

					var msg_count = 0;
					var html = "";
					var rows = jQuery.parseJSON(str);
					jQuery.each(rows, function(i, row) {

						msg_count++;
						if (msg_count == 1) {
							jQuery('#next_message_id').html(row.next_message_id);
						} else {

							var new_item = template;
							new_item = new_item.replace(/mail_mid/, row.mail_mid);
							new_item = new_item.replace(/mail_read/, row.mail_read);
							new_item = new_item.replace(/\[mail_sent\]/, row.mail_sent);
							new_item = new_item.replace(/\[mail_from\]/, row.mail_from);
							new_item = new_item.replace(/\[mail_subject\]/, row.mail_subject);
							new_item = new_item.replace(/\[mail_message\]/, row.message);
							new_item = new_item.replace(/\[bulk_action\]/, "<input type='checkbox' data-mid='"+row.mail_mid+"' class='bulk_action'>");
							html += new_item;

						}

					});

					html += '<div id="show_more_mail" style="text-align:center; padding:6px; cursor:pointer;">' + more + '</div>';
					
					jQuery('#show_more_mail').remove();
					jQuery(html).appendTo('#mailbox_list');

				} else {
					// No more...
					jQuery('#show_more_mail').remove();
				}
			},
			error: function(xhr, ajaxOptions, thrownError) {
				if (show_js_errors) {
					alert(xhr.status);
					alert(xhr.statusText);
					alert(thrownError);
				}
			}
		});

	});

	// Send
	jQuery("#mail_send_button").live('click', function() {

		var recipient_id = jQuery("#mail_recipient_list").val();

		jQuery("#compose_form").hide();
		jQuery('#mail_sent_message').show().html("<img src='" + __wps__.images_url + "/busy.gif' />");
		jQuery("#mail_office").show();

		// Strip out HTML tags, and then replace URL with a hyperlink
		var msg = jQuery('#compose_text').val().replace(/(<([^>]+)>)/ig, '');
		var exp = /(\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/ig;
		msg = msg.replace(exp, "<a href='$1'>$1</a>");

		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/mail_functions.php",
			type: "POST",
			data: ({
				action: "sendMail",
				compose_recipient_id: recipient_id,
				compose_subject: jQuery('#compose_subject').val().replace(/(<([^>]+)>)/ig, ''),
				compose_text: msg,
				compose_previous: jQuery('#compose_previous').val()
			}),
			dataType: "html",
			async: true,
			success: function(str) {
				jQuery("#mail_sent_message").html(str);
				jQuery("#mail_sent_message").delay(1000).slideUp("slow");
			},
			error: function(xhr, ajaxOptions, thrownError) {
				if (show_js_errors) {
					alert(xhr.status);
					alert(xhr.statusText);
					alert(thrownError);
				}
			}
		});

		// Show InBox...
		jQuery('#symposium_compose_tab').removeClass("nav-tab-active");
		jQuery('#symposium_compose_tab a').removeClass("nav-tab-active-link");
		jQuery('#symposium_compose_tab').addClass("nav-tab-inactive");
		jQuery('#symposium_compose_tab a').addClass("nav-tab-inactive-link");

		jQuery('#symposium_inbox_tab').removeClass("nav-tab-inactive");
		jQuery('#symposium_inbox_tab a').removeClass("nav-tab-inactive-link");
		jQuery('#symposium_inbox_tab').addClass("nav-tab-active");
		jQuery('#symposium_inbox_tab a').addClass("nav-tab-active-link");

		jQuery('#symposium_sent_tab').removeClass("nav-tab-active");
		jQuery('#symposium_sent_tab a').removeClass("nav-tab-active-link");
		jQuery('#symposium_sent_tab').addClass("nav-tab-inactive");
		jQuery('#symposium_sent_tab a').addClass("nav-tab-inactive-link");

		jQuery("#compose_form").hide();
		jQuery("#mail-main-div #mailbox").show();

		change_tray();

	});


	// Delete message
	jQuery(".message_delete").live('click', function() {

		if (confirm(areyousure)) {

			var tray = 'in';
			if (jQuery("#symposium_sent_tab").hasClass("nav-tab-active")) {
				var tray = 'sent';
			};

			var mail_id = jQuery(this).attr("id");

			jQuery.ajax({
				url: __wps__.plugin_url + "ajax/mail_functions.php",
				type: "POST",
				data: ({
					action: "deleteMail",
					mid: mail_id,
					tray: tray
				}),
				dataType: "html",
				async: true,
				success: function(str) {
					jQuery("#messagebox").html(str);
					jQuery("#" + mail_id).slideUp("slow");
				},
				error: function(xhr, ajaxOptions, thrownError) {
					if (show_js_errors) {
						alert(xhr.status);
						alert(xhr.statusText);
						alert(thrownError);
					}
				}
			});

		}

	});

	// Reply
	jQuery(".message_reply").live('click', function() {

		var mail_id = jQuery(this).attr("title");
		var mail_from = jQuery(this).attr("id");
		var mail_name = jQuery(this).attr("rel");

		// Change tab to Compose
		jQuery('#symposium_compose_tab').removeClass("nav-tab-inactive");
		jQuery('#symposium_compose_tab a').removeClass("nav-tab-inactive-link");
		jQuery('#symposium_compose_tab').addClass("nav-tab-active");
		jQuery('#symposium_compose_tab a').addClass("nav-tab-active-link");

		jQuery('#symposium_inbox_tab').removeClass("nav-tab-active");
		jQuery('#symposium_inbox_tab a').removeClass("nav-tab-active-link");
		jQuery('#symposium_inbox_tab').addClass("nav-tab-inactive");
		jQuery('#symposium_inbox_tab a').addClass("nav-tab-inactive-link");

		jQuery('#symposium_sent_tab').removeClass("nav-tab-active");
		jQuery('#symposium_sent_tab a').removeClass("nav-tab-active-link");
		jQuery('#symposium_sent_tab').addClass("nav-tab-inactive");
		jQuery('#symposium_sent_tab a').addClass("nav-tab-inactive-link");

		if (jQuery('#mail_recipient_list option[value=' + mail_from + ']').length == 0) {
			jQuery('#mail_recipient_list').append('<option value=' + mail_from + '>' + mail_name + '</option>');
		}

		jQuery('#mail_recipient_list').val(mail_from);
		jQuery('#compose_recipient_name').val('');
		jQuery('#compose_text').val('');
		jQuery('#compose_previous').val('');
		jQuery("#mail_sent_message").hide();

		jQuery("#compose_form").show();
		jQuery("#mail-main-div #mailbox").hide();

		jQuery(".__wps__pleasewait").inmiddle().show();

		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/mail_functions.php",
			type: "POST",
			data: ({
				action: "getReply",
				mail_id: mail_id,
				recipient_id: mail_from,
				mail_name: mail_name
			}),
			dataType: "html",
			async: true,
			success: function(str) {
				var detail = jQuery.parseJSON(str);
				jQuery("#compose_subject").val(detail[0].subject);
				jQuery("#compose_text").val(detail[0].message);

				jQuery(".__wps__pleasewait").fadeOut("slow");

			},
			error: function(xhr, ajaxOptions, thrownError) {
				if (show_js_errors) {
					alert(xhr.status);
					alert(xhr.statusText);
					alert(thrownError);
				}
			}
		});

	});

	// Search
	jQuery("#search_inbox_go").live('click', function() {
		symposium_do_mail_search();
	});
	jQuery('#search_inbox').live('keypress', function(e) {
		if (e.keyCode == 13) {
			symposium_do_mail_search();
		}
	});

	function symposium_do_mail_search() {
		var term = jQuery("#search_inbox").val();

		var tray = 'in';
		if (jQuery("#symposium_sent_tab").hasClass("nav-tab-active")) {
			var tray = 'sent';
		};

		if (term != '') {
			jQuery('#mailbox_list').html("<img src='" + __wps__.images_url + "/busy.gif' />");

			jQuery.ajax({
				url: __wps__.plugin_url + "ajax/mail_functions.php",
				type: "POST",
				data: ({
					uid: __wps__.current_user_page,
					action: "getBox",
					tray: tray,
					unread: jQuery("#unread_only").is(":checked"),
					term: term
				}),
				dataType: "html",
				async: true,
				success: function(str) {
					var html = "";
					var rows = jQuery.parseJSON(str);
					var first_mail_mid = '';
					jQuery.each(rows, function(i, row) {
						if (row.mail_mid != undefined) {
							if (first_mail_mid == '') {
								first_mail_mid = row.mail_mid;
							}
							html += "<div id='" + row.mail_mid + "' class='mail_item " + row.mail_read + "'>";
							html += "<div class='mail_item_age'>" + row.mail_sent + "</div>";
							html += "<strong>" + row.mail_from + "</strong><br />";
							html += "<span class='mailbox_message_subject'>" + row.mail_subject + "</span><br />";
							html += "<span class='mailbox_message'>" + row.message + "</span>";
							html += "</div>";
						}
					});
					jQuery('#mailbox_list').html(html);

					// Load first retrieved message
					if (first_mail_mid != '') {

						jQuery('#messagebox').html("<img src='" + __wps__.images_url + "/busy.gif' />");

						// Show first message as default message
						jQuery.ajax({
							url: __wps__.plugin_url + "ajax/mail_functions.php",
							type: "POST",
							data: ({
								action: "getMailMessage",
								tray: "in",
								mid: first_mail_mid
							}),
							dataType: "html",
							async: true,
							success: function(str) {
								var details = str.split("[split]");
								jQuery("#messagebox").html(details[3]);
							},
							error: function(xhr, ajaxOptions, thrownError) {
								if (show_js_errors) {
									alert(xhr.status);
									alert(xhr.statusText);
									alert(thrownError);
								}
							}
						});

					} else {

						jQuery("#messagebox").html('');

					}

				},
				error: function(xhr, ajaxOptions, thrownError) {
					if (show_js_errors) {
						alert(xhr.status);
						alert(xhr.statusText);
						alert(thrownError);
					}
				}
			});
		}
	}

	// Toggle Unread only
	jQuery("#unread_only").live('click', function() {
		change_tray();
	});
	// Change tray
	jQuery(".mail_tray").live('click', function() {
		change_tray();
	});

	function change_tray() {

		jQuery("#search_inbox").val('');
		jQuery('#__wps__mail_bulk_action').val('');

		var tray = 'in';
		if (jQuery("#symposium_sent_tab").hasClass("nav-tab-active")) {
			var tray = 'sent';
		};

		if (tray == 'in') {
			jQuery("#__wps__mark_all").attr('disabled', false);
		} else {
			jQuery("#__wps__mark_all").attr('disabled', true);
		}

		jQuery('#mailbox_list').html("<img src='" + __wps__.images_url + "/busy.gif' />");
		jQuery('#messagebox').html("<img src='" + __wps__.images_url + "/busy.gif' />");

		var template = __wps__.template_mail_tray;

		template = template.replace(/\\/g, '');
		template = template.replace(/&lt;/g, '<');
		template = template.replace(/&gt;/g, '>');
		template = template.replace(/\[\]/g, '');

		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/mail_functions.php",
			type: "POST",
			data: ({
				uid: __wps__.current_user_page,
				action: "getBox",
				unread: jQuery("#unread_only").is(":checked"),
				tray: tray,
				term: ""
			}),
			dataType: "html",
			async: true,
			success: function(str) {

				if (strpos(str, 'mail_mid')) {

					var msg_count = 0;
					var html = "";
					var rows = jQuery.parseJSON(str);
					jQuery.each(rows, function(i, row) {


						msg_count++;
						if (msg_count == 1) {
							jQuery('#next_message_id').html(row.next_message_id);
						} else {

							if (html == "") {
								// Show first message as default message
								jQuery.ajax({
									url: __wps__.plugin_url + "ajax/mail_functions.php",
									type: "POST",
									data: ({
										action: "getMailMessage",
										tray: tray,
										mid: row.mail_mid
									}),
									dataType: "html",
									async: true,
									success: function(str) {
										var details = str.split("[split]");
										if (details[2] == "in") {
											jQuery("#" + details[0]).removeClass("row");
											jQuery("#" + details[0]).addClass("row_odd");
											if (details[1] > 0) {
												jQuery("#in_unread").html('(' + details[1] + ')');
											} else {
												jQuery("#in_unread").html('');
											}
										}
										jQuery("#messagebox").html(details[3]);
										jQuery(".__wps__pleasewait").fadeOut("slow");
									},
									error: function(xhr, ajaxOptions, thrownError) {
										if (show_js_errors) {
											alert(xhr.status);
											alert(xhr.statusText);
											alert(thrownError);
										}
									}
								});
							}

							var new_item = template;
							new_item = new_item.replace(/mail_mid/, row.mail_mid);
							new_item = new_item.replace(/mail_read/, row.mail_read);
							new_item = new_item.replace(/\[mail_sent\]/, row.mail_sent);
							new_item = new_item.replace(/\[mail_from\]/, row.mail_from);
							new_item = new_item.replace(/\[mail_subject\]/, row.mail_subject);
							new_item = new_item.replace(/\[bulk_action\]/, "<input type='checkbox' data-mid='"+row.mail_mid+"' class='bulk_action'>");
							if (row.message != null) {
								new_item = new_item.replace(/\[mail_message\]/, row.message);
							} else {
								new_item = new_item.replace(/\[mail_message\]/, '');
							}
							html += new_item;

						}

					});
					html += '<div id="show_more_mail" style="text-align:center; padding:6px; cursor:pointer;">' + more + '</div>';
					jQuery('#mailbox_list').html(html);

				} else {

					jQuery('#mailbox_list').html('');
					jQuery('#messagebox').html('');

				}
			},
			error: function(xhr, ajaxOptions, thrownError) {
				if (show_js_errors) {
					alert(xhr.status);
					alert(xhr.statusText);
					alert(thrownError);
				}
			}
		});
	}

	// React to click on message list
	jQuery(".mail_item").live('click', function() {
		
		jQuery('#messagebox').html("<img src='" + __wps__.images_url + "/busy.gif' />");

		var mail_mid = jQuery(this).attr("id");

		var tray = 'in';
		if (jQuery("#sent").is(":checked")) {
			var tray = 'sent';
		};

		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/mail_functions.php",
			type: "POST",
			data: ({
				action: "getMailMessage",
				tray: tray,
				'mid': mail_mid
			}),
			dataType: "html",
			async: true,
			success: function(str) {
				var details = str.split("[split]");
				if (details[2] == "in") {
					jQuery("#" + details[0]).removeClass("row");
					jQuery("#" + details[0]).addClass("row_odd");
				}
				jQuery("#messagebox").html(details[3]);
				if (details[1] > 0 && tray == 'in') {
					jQuery("#in_unread").html('(' + details[1] + ')');
				} else {
					jQuery("#in_unread").html('');
				}
				jQuery(".__wps__pleasewait").fadeOut("slow");
			},
			error: function(xhr, ajaxOptions, thrownError) {
				if (show_js_errors) {
					alert(xhr.status);
					alert(xhr.statusText);
					alert(thrownError);
				}
			}
		});
	
	});


	// Cancel on Compose
	jQuery("#mail_cancel_button").live('click', function() {
		jQuery("#compose_form").hide();
		jQuery("#mail-main-div #mailbox").show();

		// Show InBox...
		jQuery('#symposium_compose_tab').removeClass("nav-tab-active");
		jQuery('#symposium_compose_tab a').removeClass("nav-tab-active-link");
		jQuery('#symposium_compose_tab').addClass("nav-tab-inactive");
		jQuery('#symposium_compose_tab a').addClass("nav-tab-inactive-link");

		jQuery('#symposium_inbox_tab').removeClass("nav-tab-inactive");
		jQuery('#symposium_inbox_tab a').removeClass("nav-tab-inactive-link");
		jQuery('#symposium_inbox_tab').addClass("nav-tab-active");
		jQuery('#symposium_inbox_tab a').addClass("nav-tab-active-link");

		jQuery('#symposium_sent_tab').removeClass("nav-tab-active");
		jQuery('#symposium_sent_tab a').removeClass("nav-tab-active-link");
		jQuery('#symposium_sent_tab').addClass("nav-tab-inactive");
		jQuery('#symposium_sent_tab a').addClass("nav-tab-inactive-link");

		jQuery("#compose_form").hide();
		jQuery("#mail-main-div #mailbox").show();

		change_tray();

	});




/*
	   +------------------------------------------------------------------------------------------+
	   |                                         PROFILE                                          |
	   +------------------------------------------------------------------------------------------+
	*/

	// Act on "view" parameter on first page load
	if ((jQuery("#profile_body").length) && (__wps__.embed != 'on')) {

		var menu_id = 'menu_' + __wps__.view;

		if (menu_id == 'menu_in') {
			menu_id = 'menu_friends';
		}

		if (jQuery('#force_profile_page').length && __wps__.view == 'in') {
			menu_id = 'menu_' + jQuery('#force_profile_page').html();
		}

		// Override if sending to a specific post
		if (__wps__.post != '') {
			menu_id = 'menu_wall';
		}

		if ((menu_id == 'menu_extended') || (menu_id == 'menu_wall') || (menu_id == 'menu_activity') || (menu_id == 'menu_all') || (menu_id == 'menu_mentions') || (menu_id == 'menu_groups') || (menu_id == 'menu_friends') || (menu_id == 'menu_avatar') || (menu_id == 'menu_personal') || (menu_id == 'menu_settings')) {
			
			var ajax_path = __wps__.plugin_url + "ajax/profile_functions.php";			

		} else if ((menu_id == 'menu_gallery') || (menu_id == 'menu_events') || (menu_id == 'menu_plus') || (menu_id == 'menu_plus_me') || (menu_id == 'menu_lounge')) {
			
			var ajax_part = menu_id.replace(/menu_/g, "");

			if (strpos(ajax_part, '->') !== false) {
				ajax_sub = ajax_part.substring(0, strpos(ajax_part, '->'));
				var ajax_path = __wps__.plugin_url + "ajax/" + ajax_sub + "_functions.php";
				menu_id = menu_id.replace(/->/g, "_");
			} else {
				ajax_part = ajax_part.replace(/_me/g, "");
				var ajax_path = __wps__.plugin_url + "ajax/" + ajax_part + "_functions.php";
			}

		} else if (menu_id == 'menu_news') {

			var ajax_path = __wps__.plugin_url + "ajax/news_functions.php";			

		} else if (jQuery('#force_profile_page').length) {
			
			// force the page content
			var ajax_path = __wps__.plugins+'/wp-symposium-'+jQuery('#force_profile_page').html()+'/ajax/wp-symposium-'+jQuery('#force_profile_page').html()+'_functions.php';
			menu_id = 'wp-symposium-'+jQuery('#force_profile_page').html();
			
		} else {
			// passed in URL
			var ajax_path = __wps__.plugins+'/wp-symposium-'+__wps__.view+'/ajax/wp-symposium-'+__wps__.view+'_functions.php';
			menu_id = 'wp-symposium-'+__wps__.view;
		}
		

		if (menu_id == 'menu_') {
			menu_id = 'menu_wall';
			ajax_path = __wps__.plugin_url + "ajax/profile_functions.php";
		}

		// Passed parameter?
		var rel = jQuery("#profile_body").attr("rel");
		if (typeof rel === 'undefined' || rel === false) { rel = ''; }

		// Highlight default menu choice	      	
		jQuery('#' + menu_id).addClass('__wps__profile_current');

		// Now do AJAX stuff
		jQuery.ajax({
			url: ajax_path,
			type: "POST",
			data: ({
				action: menu_id,
				post: __wps__.post,
				limit_from: 0,
				uid1: __wps__.current_user_page,
				rel: rel
			}),
			dataType: "html",
			async: true,
			success: function(str) {

				jQuery('#profile_body').html(str);
				
				var user_id = jQuery("#symposium_user_id").html();
				var user_login = jQuery("#symposium_user_login").html();
				var user_email = jQuery("#symposium_user_email").html();

				// Set up auto-expanding textboxes
				if (jQuery(".elastic").length) {
					jQuery('.elastic').elastic();
				}
				
				// Reduce width of status/reply fields on activity to take into account background edit icon
				if (jQuery('.__wps__reply').length) {
					var w = jQuery('.__wps__reply').css('width').replace(/px/g, "");
					jQuery('.__wps__reply').css('width', (w-30)+'px');
				}
				if (jQuery('#__wps__comment').length) {
					var p = jQuery('#symposium_add_status').css('width').replace(/px/g, "");
					jQuery('#__wps__comment').css('width', (p-5)+'px');
				}

				// Init file upload
				__wps__init_file_upload();

			},
			error: function(xhr, ajaxOptions, thrownError) {
				//alert(xhr.status);
				//alert(xhr.statusText);
				//alert(thrownError);
			}

		});


	}
	
	// For [symposium-stream]
	// 1. re-size comment box
	if (jQuery('.__wps__reply').length) {
		var w = jQuery('.__wps__reply').css('width').replace(/px/g, "");
		jQuery('.__wps__reply').css('width', (w-30)+'px');
	}
	if (jQuery('#__wps__comment').length) {
		var p = jQuery('#__wps__comment').parent().css('width').replace(/px/g, "");
		jQuery('#__wps__comment').css('width', (p-5)+'px');
	}
	// 2. Activity file upload (loads with page shortcode)
	__wps__init_file_upload();
	
	jQuery('.symposium_wall_replies').live('mouseenter mouseleave', function(event) {
		if (event.type == 'mouseenter') {
			jQuery("#__wps__reply_div_" + jQuery(this).attr("id")).hide().slideDown("fast");
		} else {
			jQuery("#__wps__reply_div_" + jQuery(this).attr("id")).slideUp("fast");
		}
	});

	// Show mail/delete link on friend hover
	jQuery('.friend_div').live('mouseenter mouseleave', function(event) {
		if (event.type == 'mouseenter') {
			jQuery(this).find(".friend_icons").show();
		} else {
			jQuery(this).find(".friend_icons").hide();
		}
	});

	// Remove all friends
	jQuery("#removeAllFriends").live('click', function() {

		if (confirm(areyousure)) {

			jQuery.ajax({
				url: __wps__.plugin_url + "ajax/profile_functions.php",
				type: "POST",
				data: ({
					action: 'remove_all_friends',
					uid: __wps__.current_user_page
				}),
				dataType: "html",
				async: false,
				success: function(str) {
					jQuery("#dialog").html('All friends removed!');
					jQuery("#dialog").dialog({
						title: __wps__.site_title,
						width: 200,
						height: 150,
						modal: true,
						buttons: {
							"OK": function() {
								jQuery("#dialog").dialog('close');
								window.location.href = window.location.href;
							}
						}
					});
				}
			});
			
		}
	});
	
	// Remove avatar
	jQuery("#symposium_remove_avatar").live('click', function() {
		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/profile_functions.php",
			type: "POST",
			data: ({
				action: 'remove_avatar',
				uid: __wps__.current_user_page
			}),
			dataType: "html",
			async: false,
			success: function(str) {
				window.location.href = window.location.href;
			}
		});
	});

	// Poke
	jQuery(".poke-button").live('click', function() {
		jQuery("#dialog").html('Message sent!');
		jQuery("#dialog").dialog({
			title: __wps__.site_title,
			width: 200,
			height: 175,
			modal: true,
			buttons: {
				"OK": function() {
					jQuery("#dialog").dialog('close');
				}
			}
		});

		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/profile_functions.php",
			type: "POST",
			data: ({
				action: 'send_poke',
				recipient: __wps__.current_user_page
			}),
			dataType: "html",
			async: false,
			success: function(str) {}
		});
	});

	

	// Clicked on show more...
	jQuery(".showmore_wall").live('click', function() {

		var limit_from = jQuery(this).attr("title");
		jQuery(this).html("<img src='" + __wps__.images_url + "/busy.gif' />");

		var menu_id = 'menu_' + jQuery(this).attr("id");

		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/profile_functions.php",
			type: "POST",
			data: ({
				action: menu_id,
				post: '',
				limit_from: limit_from,
				uid1: __wps__.current_user_page
			}),
			dataType: "html",
			async: true,
			success: function(str) {
				jQuery('.showmore_wall').remove();
				if (jQuery("#profile_body").length) {
					jQuery(str).appendTo('#profile_body').hide().slideDown("slow");
				} else {
					jQuery(str).appendTo('.__wps__wrapper').hide().slideDown("slow");
				}
			}
		});

	});

	// Menu choices
	jQuery(".__wps__profile_menu").click(function() {

		// Check if using horizontal menu (tabs)
		if (jQuery(".__wps__top_menu").length) {
			jQuery('.__wps__top_menu').removeClass('__wps__dropdown_tab_on').addClass('__wps__dropdown_tab_off');
			jQuery(this).closest('.__wps__top_menu').removeClass('__wps__dropdown_tab_off').addClass('__wps__dropdown_tab_on');
			
			jQuery(this).parent().hide();
		}

		jQuery('.__wps__profile_menu').removeClass('__wps__profile_current');
		jQuery(this).addClass('__wps__profile_current');

		var menu_id = jQuery(this).attr("id");
		var menu_slug = jQuery(this).attr("name");		
		jQuery('#profile_body').html("<img src='" + __wps__.images_url + "/busy.gif' />");

		if (!(jQuery("#profile_body").length)) {
			var view = menu_id.replace(/menu_/g, "");
			var q = symposium_q(__wps__.profile_url);
			window.location.href = __wps__.profile_url + q + 'view=' + view;
		}

		if ((menu_id == 'menu_extended') || (menu_id == 'menu_wall') || (menu_id == 'menu_activity') || (menu_id == 'menu_all') || (menu_id == 'menu_mentions') || (menu_id == 'menu_groups') || (menu_id == 'menu_friends') || (menu_id == 'menu_avatar') || (menu_id == 'menu_personal') || (menu_id == 'menu_settings')) {
			
			var ajax_path = __wps__.plugin_url + "ajax/profile_functions.php";			

		} else if (menu_id == 'menu_news') {

			var ajax_path = __wps__.plugin_url + "ajax/news_functions.php";			

		} else if ((menu_id == 'menu_gallery') || (menu_id == 'menu_events') || (menu_id == 'menu_plus') || (menu_id == 'menu_plus_me') || (menu_id == 'menu_lounge')) {
			
			var ajax_part = menu_id.replace(/menu_/g, "");

			if (strpos(ajax_part, '->') !== false) {
				ajax_sub = ajax_part.substring(0, strpos(ajax_part, '->'));
				var ajax_path = __wps__.plugin_url + "ajax/" + ajax_sub + "_functions.php";
				menu_id = menu_id.replace(/->/g, "_");
			} else {
				ajax_part = ajax_part.replace(/_me/g, "");
				var ajax_path = __wps__.plugin_url + "ajax/" + ajax_part + "_functions.php";
			}
			
		} else {
			
			// added on menu item (ie. ID = wp-symposium-example)
			var ajax_path = __wps__.plugins+'/'+menu_id+'/ajax/'+menu_id+'_functions.php';

		}

		jQuery.ajax({
			url: ajax_path,
			type: "POST",
			data: ({
				action: menu_id,
				post: '',
				limit_from: 0,
				menu_slug:menu_slug,
				uid1: __wps__.current_user_page,
				display_name: __wps__.current_user_display_name
			}),
			dataType: "html",
			async: true,
			success: function(str) {

				jQuery('#profile_body').html(str);

				var user_id = jQuery("#symposium_user_id").html();
				var user_login = jQuery("#symposium_user_login").html();
				var user_email = jQuery("#symposium_user_email").html();

				// Set up auto-expanding textboxes
				if (jQuery(".elastic").length) {
					jQuery('.elastic').elastic();
				}

				// Reduce width of status/reply fields on activity to take into account background edit icon
				if (jQuery('.__wps__reply').length) {
					var w = jQuery('.__wps__reply').css('width').replace(/px/g, "");
					jQuery('.__wps__reply').css('width', (w-30)+'px');
				}
				if (jQuery('#__wps__comment').length) {
					var p = jQuery('#__wps__comment').parent().css('width').replace(/px/g, "");
					jQuery('#__wps__comment').css('width', (p-5)+'px');
				}

				// Init file upload
				__wps__init_file_upload();

			}
		});


	});

	if (jQuery("#profile_jcrop_target").length) {
		jQuery('#profile_jcrop_target').Jcrop({
			onChange: showPreview,
			onSelect: showPreview
		});
	}

	function showProfilePreview(coords) {
		var rx = 100 / coords.w;
		var ry = 100 / coords.h;

		jQuery('#x').val(coords.x);
		jQuery('#y').val(coords.y);
		jQuery('#x2').val(coords.x2);
		jQuery('#y2').val(coords.y2);
		jQuery('#w').val(coords.w);
		jQuery('#h').val(coords.h);

		jQuery('#profile_preview').css({
			width: Math.round(rx * jQuery('#profile_jcrop_target').width()) + 'px',
			height: Math.round(ry * jQuery('#profile_jcrop_target').height()) + 'px',
			marginLeft: '-' + Math.round(rx * coords.x) + 'px',
			marginTop: '-' + Math.round(ry * coords.y) + 'px'
		});
	};

	// Save profile avatar
	jQuery("#saveProfileAvatar").live('click', function() {
		

		//if (jQuery("#w").val() > 0) {
		jQuery(".__wps__notice").inmiddle().show();

		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/profile_functions.php",
			type: "POST",
			data: ({
				action: "saveProfileAvatar",
				uid: __wps__.current_user_page,
				x: jQuery("#x").val(),
				y: jQuery("#y").val(),
				w: jQuery("#w").val(),
				h: jQuery("#h").val()
			}),
			dataType: "html",
			async: true,
			success: function(str) {
				if (str.length <= 1 || trim(str) == '' || trim(str) == '0') {
					location.reload();
				} else {
					jQuery(".__wps__notice").fadeOut("slow");
					alert('Oops: ' + str);
				}
			},
			error: function(xhr, ajaxOptions, thrownError) {
				if (show_js_errors) {
					alert(xhr.status);
					alert(xhr.statusText);
					alert(thrownError);
				}
			}
		});

		//} else {
		//	alert('Please select an area in your uploaded image');
		//}
	});

	// Show delete link on wall post hover
	jQuery('.wall_post_div').live('mouseenter mouseleave', function(event) {
		if (event.type == 'mouseenter') {
			jQuery(this).find(".report_post_top").show();
			jQuery(this).find(".delete_post_top").show();
		} else {
			jQuery(this).find(".report_post_top").hide();
			jQuery(this).find(".delete_post_top").hide();
		}
	});

	// Show delete link on reply hover
	jQuery('.wall_reply').live('mouseenter mouseleave', function(event) {
		if (event.type == 'mouseenter') {
			jQuery(this).find(".report_post").show();
			jQuery(this).find(".delete_reply").show();
		} else {
			jQuery(this).find(".report_post").hide();
			jQuery(this).find(".delete_reply").hide();
		}
	});

	// View all comments
	jQuery(".view_all_comments").live('click', function() {
		var parent_comment_id = jQuery(this).attr("title");
		jQuery('#' + parent_comment_id).find(".reply_div").slideDown("slow");
	});

	// Delete a reply
	jQuery(".delete_post").live('click', function() {

		var comment_id = jQuery(this).attr("title");
		if (jQuery(this).attr("rel") == 'post') {
			jQuery('#post_' + comment_id).slideUp("slow");
		} else {
			jQuery('#' + comment_id).slideUp("slow");
		}

		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/profile_functions.php",
			type: "POST",
			data: ({
				action: "deletePost",
				cid: comment_id
			}),
			dataType: "html",
			async: false,
			success: function(str) {
				if (str.substring(0, 4) == 'FAIL') {
					alert("delete_post:" + str);
				}
			},
			error: function(xhr, ajaxOptions, thrownError) {
				if (show_js_errors) {
					alert(xhr.status);
					alert(xhr.statusText);
					alert(thrownError);
				}
			}
		});

	});

	// new post on activity stream
	jQuery("#__wps__add_comment").live('click', function() {
		symposium_add_comment_to_profile();
	});
	jQuery('#__wps__comment').live('keypress', function(e) {
		if (e.keyCode == 13 && (!jQuery("#__wps__add_comment").length)) {
			symposium_add_comment_to_profile();
		}
	});

	function symposium_add_comment_to_profile() {
		
		var comment_text = jQuery("#__wps__comment").val().replace(/(<([^>]+)>)/ig, '');
		if (comment_text == whatsup)
			comment_text = '';
		// Video
		if (typeof jQuery('#activity_youtube_embed_button_used') !== "undefined")
			var code = jQuery('#activity_youtube_embed_button_used').attr('title');
		if (typeof code === "undefined")
			code = '';
		// Image
		var img = '';
		if (jQuery("#activity_file_upload_file").length) {
			if (jQuery("#activity_file_upload_file").html() != '') {
				img = 'yes';
			}
		}
		
		if (comment_text != '' || code != '' || img != '') {
            
			if (code != '') {
	    	    var width = '100%';
		        var height = 250;
	
	        	var iFrame = '<iframe style="margin-bottom:0px;" width="'+width+'" height="'+height+'" src="http://www.youtube.com/embed/'+code+'?wmode=transparent" frameborder="0" allowfullscreen></iframe>';
	        	comment_text = comment_text + '<br /><br />' + iFrame;
	        	jQuery('#activity_youtube_embed_button_used').attr('id', 'activity_youtube_embed_button');
	        	jQuery('#activity_youtube_embed_button').attr('title', '');
			}

			comment_text = comment_text.replace(/\n/g, '<br>');
            
			// temporarily remove and re-show after 3 seconds

			jQuery('#symposium_add_status').hide();
			setTimeout(function() {
				if (jQuery('#btn-span-tmp').html() != '') {
					jQuery('#btn-span').html(jQuery('#btn-span-tmp').html());
				}
				jQuery('#fileupload-info').html('');
				jQuery('#__wps__comment').css('height', '50px');
				jQuery('#symposium_add_status').slideDown('fast');
			}, 3000);

			var comment = "<div class='add_wall_post_div' style='"
			if (__wps__.row_border_size != '') {
				comment = comment + " border-top:" + __wps__.row_border_size + "px " + __wps__.row_border_style + " " + __wps__.text_color_2 + ";";
			}
			comment = comment + "'>";
			comment = comment + "<div class='add_wall_post'>";
			comment = comment + "<div class='add_wall_post_text'>";
			var q = symposium_q(__wps__.profile_url);
			comment = comment + '<a href="' + __wps__.profile_url + q + 'uid=' + __wps__.current_user_id + '">';
			comment = comment + __wps__.current_user_display_name + '</a><br />';
			comment = comment + comment_text;

			// Check for uploaded image
			if (jQuery('#__wps__file_upload_iframe').length) {
				var bfu = jQuery("#__wps__file_upload_iframe").contents().find('#forum_file_list').html();
				if (typeof bfu != 'undefined') {
					comment = comment + "<div id='myimage'><img id='symposium_tmp' src='" + __wps__.images_url + "/busy.gif' /></div>";
					jQuery('#__wps__file_upload_iframe').remove();
				}
			}
			if (jQuery("#activity_file_upload_file").length) {
				if (jQuery("#activity_file_upload_file").html() != '') {
					comment = comment + "<div id='myimage'><img id='symposium_tmp' src='" + __wps__.images_url + "/busy.gif' /></div>";
				}
			}
					
			comment = comment + "</div>";
			comment = comment + "</div>";
			comment = comment + "<div class='add_wall_post_avatar'>";
			comment = comment + "<img src='" + jQuery('#__wps__current_user_avatar img:first').attr('src') + "' style='width:64px; height:64px' />";
			comment = comment + "</div>";

			jQuery("#__wps__comment").val('');
			jQuery(comment).insertAfter('#symposium_add_status').hide().slideDown('fast');


			var facebook_post = 0;
			if (jQuery("#post_to_facebook").length) {
				if (jQuery("#post_to_facebook").is(":checked")) {
					if (__wps__.current_user_page == __wps__.current_user_id) {
						facebook_post = 1;
					}
				}
			}

			jQuery.ajax({
				url: __wps__.plugin_url + "ajax/profile_functions.php",
				type: "POST",
				data: ({
					action: "addStatus",
					subject_uid: __wps__.current_user_page,
					parent: 0,
					text: comment_text,
					facebook: facebook_post
				}),
				dataType: "html",
				async: true,
				success: function(str) {
                    
					if (str) {
						jQuery('#myimage:first').html('<a target="_blank" href="'+str+'" rev="1" rel="symposium_tmp_activity_image" data-owner="'+__wps__.current_user_id+'" data-name="" data-iid="1" class="wps_gallery_album"><img class="profile_activity_image" src="' + str + '" /></a>');
					}
					jQuery('#activity_youtube_embed_button_used').attr('id', 'activity_youtube_embed_button');
					jQuery('#activity_youtube_embed_button').val("YouTube");
					jQuery('#activity_file_upload_file').html('');
					jQuery(".__wps__notice").fadeOut("slow");

				},
				error: function(xhr, ajaxOptions, thrownError) {
					alert(xhr.status);
					alert(xhr.statusText);
					alert(thrownError);
				}
			});
			

		}
	}

	// new reply/comment
	jQuery(".symposium_add_reply").live('click', function() {
		jQuery(this).parent().hide();
		var t = this;
		setTimeout(function() {
			jQuery('.__wps__reply').css('height', '44px');
			jQuery(t).parent().slideDown('fast');
		}, 3000);
		symposium_add_comment(this);
	});
	jQuery('.__wps__reply').live('keypress', function(e) {
		if (e.keyCode == 13 && (!jQuery(".symposium_add_reply").length)) {
			jQuery(this).parent().hide();
			var t = this;
			setTimeout(function() {
				jQuery('.__wps__reply').css('height', '44px');
				jQuery(t).parent().slideDown('fast');
			}, 3000);
			symposium_add_comment(this);
		}
	});

	function symposium_add_comment(comment_trigger) {

		var comment_id = jQuery(comment_trigger).attr("title");
		var author_id = jQuery('#symposium_author_' + comment_id).val();
		var comment_text = jQuery("#__wps__reply_" + comment_id).val().replace(/(<([^>]+)>)/ig, '');

		if (comment_text != '' && comment_text != '\n' && comment_text != write_a_comment && comment_text != write_a_comment + '\n') {

			comment_text = comment_text.replace(/\n/g, '<br>');

			var comment = "<div class='reply_div'>";
			comment = comment + "<div class='__wps__wall_reply_div'";
			if (__wps__.bg_color_2 != '') {
				comment = comment + " style='background-color:" + __wps__.bg_color_2 + "'";
			}
			comment = comment + ">";
			comment = comment + "<div class='wall_reply'>";
			var q = symposium_q(__wps__.profile_url);
			comment = comment + '<a href="' + __wps__.profile_url + q + 'uid=' + __wps__.current_user_id + '">';
			comment = comment + __wps__.current_user_display_name + '</a><br />';
			comment = comment + comment_text;
			comment = comment + "</div>";
			comment = comment + "</div>";
			comment = comment + "<div class='wall_reply_avatar'>";
			comment = comment + "<img src='" + jQuery('#__wps__current_user_avatar img:first').attr('src') + "' style='width:40px; height:40px' />";
			comment = comment + "</div>";
			comment = comment + "</div>";

			jQuery(comment).appendTo('#__wps__comment_' + comment_id);
			jQuery("#__wps__reply_" + comment_id).val('');

			jQuery.ajax({
				url: __wps__.plugin_url + "ajax/profile_functions.php",
				type: "POST",
				data: ({
					action: "addComment",
					uid: author_id,
					parent: comment_id,
					text: comment_text
				}),
				dataType: "html",
				async: true,
				success: function(str) {
					if (__wps__.debug) {
						jQuery("#dialog").html(str).dialog({
							title: __wps__.site_title + ' debug info',
							width: 800,
							height: 500,
							modal: true
						});
					}
				}
			});
		} else {
			jQuery("#__wps__reply_" + comment_id).val('');
		}
	}

	// update settings
	jQuery("#updateSettingsButton").live('click', function() {

		var display_name = jQuery("#display_name").val().replace(/(<([^>]+)>)/ig, '');
		var user_firstname = jQuery("#user_firstname").val().replace(/(<([^>]+)>)/ig, '');
		var user_lastname = jQuery("#user_lastname").val().replace(/(<([^>]+)>)/ig, '');
		var user_email = jQuery("#user_email").val().replace(/(<([^>]+)>)/ig, '');
		var signature = jQuery("#signature").val().replace(/(<([^>]+)>)/ig, '');
		var profile_label = jQuery("#__wps__profile_label").val().replace(/(<([^>]+)>)/ig, '');

		if (signature.length > 128) {
			jQuery("#dialog").html('Maximum length for signatures is 128 characters.');
			jQuery("#dialog").dialog({
				title: 'Signature',
				width: 600,
				height: 225,
				modal: true,
				buttons: {
					"OK": function() {
						jQuery("#dialog").dialog('close');
					}
				}
			});
		}

		if (display_name == '') {
			jQuery("#display_name").effect("highlight", {}, 4000);
		} else {

			if (user_email == '') {
				jQuery("#user_email").effect("highlight", {}, 4000);
			} else {

				jQuery(".__wps__notice").inmiddle().show();

				if (jQuery("#notify_new_messages").is(":checked")) {
					var notify_new_messages = 'on';
				} else {
					var notify_new_messages = '';
				}

				if (jQuery("#notify_new_wall").is(":checked")) {
					var notify_new_wall = 'on';
				} else {
					var notify_new_wall = '';
				}

				if (jQuery("#notify_likes").is(":checked")) {
					var notify_likes = 'on';
				} else {
					var notify_likes = '';
				}

				if (jQuery("#forum_all").is(":checked")) {
					var forum_all = 'on';
				} else {
					var forum_all = '';
				}

				if (jQuery("#trusted").length) {
					if (jQuery("#trusted").is(":checked")) {
						var trusted = 'on';
					} else {
						var trusted = '';
					}
				} else {
					var trusted = jQuery("#trusted_hidden").val();
				}

				jQuery.ajax({
					url: __wps__.plugin_url + "ajax/profile_functions.php",
					type: "POST",
					data: ({
						action: "updateSettings",
						uid: __wps__.current_user_page,
						trusted: trusted,
						bar_position: jQuery("#bar_position").val(),
						user_firstname: user_firstname,
						user_lastname: user_lastname,
						display_name: display_name,
						user_email: user_email,
						signature: signature,
						notify_new_messages: notify_new_messages,
						notify_new_wall: notify_new_wall,
						notify_likes: notify_likes,
						forum_all: forum_all,
						profile_label: profile_label,
						xyz1: jQuery("#xyz1").val(),
						xyz2: jQuery("#xyz2").val()
					}),
					dataType: "html",
					async: true,
					success: function(str) {
						if (str == 'PASSWORD CHANGED') { /* when password changes, have to log in again, can't work out why */
							window.location.href = window.location.href;
						}
						if (str != "OK") {
							jQuery("#dialog").html(str);
							jQuery("#dialog").dialog({
								title: __wps__.site_title,
								width: 300,
								height: 175,
								modal: true,
								buttons: {
									"OK": function() {
										jQuery("#dialog").dialog('close');
									}
								}
							});
						}
						jQuery(".__wps__notice").fadeOut("slow");
					},
					error: function(xhr, ajaxOptions, thrownError) {
						if (show_js_errors) {
							alert(xhr.status);
							alert(xhr.statusText);
							alert(thrownError);
						}
					}
				});
			}
		}

	});

	// update personal
	jQuery("#updatePersonalButton").live('click', function() {
		jQuery(".__wps__notice").inmiddle().show();

		var extended = '';

		jQuery('.eid_value').each(function(index) {
			var title = jQuery(this).attr("title");
			var value = jQuery(this).val().replace(/(<([^>]+)>)/ig, '');

			if (value == 'on')
				value = jQuery(this).is(":checked");

			extended += title + '[]';
			extended += value + '[|]';
		});
		
		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/profile_functions.php",
			type: "POST",
			data: ({
				action: "updatePersonal",
				uid: __wps__.current_user_page,
				dob_day: jQuery("#dob_day").val(),
				dob_month: jQuery("#dob_month").val(),
				dob_year: jQuery("#dob_year").val(),
				city: jQuery("#city").val().replace(/(<([^>]+)>)/ig, ''),
				country: jQuery("#country").val().replace(/(<([^>]+)>)/ig, ''),
				share: jQuery("#share").val(),
				wall_share: jQuery("#wall_share").val(),
				rss_share: jQuery("#rss_share").val(),
				chat_sound: jQuery("#chat_sound").val(),
				extended: extended
			}),
			dataType: "html",
			async: true,
			success: function(str) {
				if (str != 'OK') {
					jQuery("#dialog").html(str).dialog({
						title: __wps__.site_title + ' debug info',
						width: 800,
						height: 500,
						modal: true
					});
				}
				jQuery(".__wps__notice").fadeOut("slow");
			},
			error: function(xhr, ajaxOptions, thrownError) {
				if (show_js_errors) {
					alert(xhr.status);
					alert(xhr.statusText);
					alert(thrownError);
				}
			}
		});

	});

	// delete a friend
	jQuery(".frienddelete").live('click', function() {
		jQuery(".__wps__notice").inmiddle().show();

		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/profile_functions.php",
			type: "POST",
			data: ({
				action: "deleteFriend",
				friend: jQuery(this).attr("title")
			}),
			dataType: "html",
			async: true,
			success: function(str) {
				if (str != 'NOT LOGGED IN') {
					jQuery("#friend_" + str).slideUp("slow");
				}
				jQuery(".__wps__notice").fadeOut("slow");
			},
			error: function(xhr, ajaxOptions, thrownError) {
				if (show_js_errors) {
					alert(xhr.status);
					alert(xhr.statusText);
					alert(thrownError);
				}
			}
		});

	});

	// send mail (via send mail button)
	jQuery("#profile_send_mail_button").live('mousedown', function() {
		var q = symposium_q(__wps__.mail_url);
		document.location = __wps__.mail_url + q + 'view=compose&to=' + __wps__.current_user_page;
	});

	// add a friend request
	jQuery("#addasfriend").live('click', function() {
		addasfriend(this, '');
	});
	jQuery(".addasfriend").live('click', function() {
		addasfriend(this, jQuery(this).prev().val());
	});
	jQuery('#addfriend').live('keypress', function(e) {
		if (e.keyCode == 13) {
			addasfriend(this, '');
		}
	});
	jQuery('.addfriend_text').live('keypress', function(e) {
		if (e.keyCode == 13) {
			addasfriend(this, jQuery(this).val());
		}
	});

	function addasfriend(id, msg) {
		var uid = jQuery(id).attr("title");
		if (msg == '') msg = jQuery('#addfriend').val();

		jQuery("#addasfriend_done1_" + uid).hide();
		jQuery("#addasfriend_done2_" + uid).show();

		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/profile_functions.php",
			type: "POST",
			data: ({
				action: "addFriend",
				friend_to: uid,
				friend_message: msg
			}),
			dataType: "html",
			async: true,
			success: function(str) {
				jQuery("#dialog").html(__wps__.request_sent);
				jQuery("#dialog").dialog({
					title: __wps__.site_title,
					width: 600,
					height: 225,
					modal: true,
					buttons: {
						"OK": function() {
							jQuery("#dialog").dialog('close');
						}
					}
				});
			},
			error: function(xhr, ajaxOptions, thrownError) {
				if (show_js_errors) {
					alert(xhr.status);
					alert(xhr.statusText);
					alert(thrownError);
				}
			}
		});

	};

	// cancel a friend request
	jQuery("#cancelfriendrequest").live('click', function() {
		jQuery(".__wps__notice").inmiddle().show();

		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/profile_functions.php",
			type: "POST",
			data: ({
				action: "cancelFriend",
				friend_to: jQuery(this).attr("title")
			}),
			dataType: "html",
			async: true,
			success: function(str) {
				if (str != 'NOT LOGGED IN') {
					jQuery("#cancelfriendrequest").hide();
					jQuery("#cancelfriendrequest_done").show();
				}
				jQuery(".__wps__notice").fadeOut("slow");
			},
			error: function(xhr, ajaxOptions, thrownError) {
				if (show_js_errors) {
					alert(xhr.status);
					alert(xhr.statusText);
					alert(thrownError);
				}
			}
		});

	});

	// reject a friend request
	jQuery("#rejectfriendrequest").live('click', function() {
		jQuery(".__wps__notice").inmiddle().show();

		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/profile_functions.php",
			type: "POST",
			data: ({
				action: "rejectFriend",
				friend_to: jQuery(this).attr("title")
			}),
			dataType: "html",
			async: true,
			success: function(str) {
				if (str != 'NOT LOGGED IN') {
					jQuery("#request_" + str).slideUp("slow");
				}
				jQuery(".__wps__notice").fadeOut("slow");
			},
			error: function(xhr, ajaxOptions, thrownError) {
				if (show_js_errors) {
					alert(xhr.status);
					alert(xhr.statusText);
					alert(thrownError);
				}
			}
		});

	});

	// accept a friend request
	jQuery("#acceptfriendrequest").live('click', function() {

		jQuery("#request_" + jQuery(this).attr("title")).slideUp("slow");

		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/profile_functions.php",
			type: "POST",
			data: ({
				action: "acceptFriend",
				friend_to: jQuery(this).attr("title")
			}),
			dataType: "html",
			async: true,
			success: function(str) {},
			error: function(xhr, ajaxOptions, thrownError) {
				if (show_js_errors) {
					alert(xhr.status);
					alert(xhr.statusText);
					alert(thrownError);
				}
			}
		});

	});

	// clear all current subscriptions
	jQuery("#symposium_clear_all_subs").live('click', function() {

		if (confirm(areyousure)) {

			jQuery("#dialog").html('All subscriptions cleared.');
			jQuery("#dialog").dialog({
				title: 'Preferences',
				width: 600,
				height: 225,
				modal: true,
				buttons: {
					"OK": function() {
						jQuery("#dialog").dialog('close');
					}
				}
			});

			jQuery.ajax({
				url: __wps__.plugin_url + "ajax/profile_functions.php",
				type: "POST",
				data: ({
					action: "clearSubs"
				}),
				dataType: "html",
				async: true,
				success: function(str) {
					if (str != 'OK') {
						jQuery("#dialog").html(str).dialog({
							title: __wps__.site_title + ' debug info',
							width: 800,
							height: 500,
							modal: true
						});
					}
				},
				error: function(xhr, ajaxOptions, thrownError) {
					if (show_js_errors) {
						alert(xhr.status);
						alert(xhr.statusText);
						alert(thrownError);
					}
				}
			});
		}

	});

/*
	   +------------------------------------------------------------------------------------------+
	   |                                       PROFILE PLUS                                       |
	   +------------------------------------------------------------------------------------------+
	*/

	// Horizontal menu/tabs (also shared with Groups)
	
    jQuery("ul.__wps__dropdown li").hover(function(){
    
        jQuery(this).addClass("hover");
        jQuery('ul:first',this).css('visibility', 'visible').show();
    
    }, function(){
    
        jQuery(this).removeClass("hover");
        jQuery('ul:first',this).css('visibility', 'hidden');
    
    });
    
    
	// Scrolling Profile Menu
	if (__wps__.profile_menu_scrolls == 'on') {
		
		var el = jQuery('#profile_menu');
		if (el.length) {

			el.parent().css('position', 'relative');
			var elpos = el.offset().top;
			var t = el.position().top;
			var d = parseInt(__wps__.profile_menu_delta);
			el.css('position', 'absolute');
			el.css('top', '0px');
			jQuery('.__wps__wrapper').addClass('__wps__wrapper-min-height');
			
			jQuery(window).scroll(function() {

				el.parent().css('position', 'relative');
				var elpos = el.offset().top;
				var t = el.position().top;
				var d = parseInt(__wps__.profile_menu_delta);
				el.css('position', 'absolute');
				el.css('top', '0px');

				var wrapper_top = jQuery('#profile_wrapper').offset().top;
				var y = jQuery(this).scrollTop();
				if (y > wrapper_top - d) {
					el.css('position', 'fixed');
					el.css('top', d);
				} else {
					el.css('position', 'absolute');
					el.css('top', '0px');
				}			

			});
						
		}
		
	} else {

		var el = jQuery('#profile_menu');
		if (el.length) {

			var elpos = el.offset().top;
			var w = jQuery('#profile_wrapper');
			el.parent().css('position', 'relative');
			el.css('position', 'absolute');
			el.css('top', '0px');
				
		}

	}

	// Likes/Dislikes on activity
	jQuery(".wall_add_like").live('click', function() {
		var cid = jQuery(this).attr('rel');
		var choice = jQuery(this).attr("data-action");
		jQuery("#__wps__likes_" + cid).html('<strong>' + jQuery(this).attr('title') + '</strong>').effect("slide");
		jQuery(this).parent().hide();

		// Store choice
		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/plus_functions.php",
			type: "POST",
			data: ({
				action: "likeDislike",
				choice: choice,
				cid: cid
			}),
			dataType: "html",
			async: true,
			success: function(str) {
				if (str != 'OK') jQuery("#dialog").html(str).dialog({ title: __wps__.site_title+' debug info', width: 800, height: 500, modal: true });
			}
		});
	})


	jQuery("#symposium_show_likes").live('click', function() {
		var cid = jQuery(this).attr('rel');
		var str = '';
		var busy = "<img id='symposium_tmp' src='" + __wps__.images_url + "/busy.gif' />";
		str += '<div id="__wps__who_dislikes" style="width:49%;float:right;"><strong>' + symposium_dislikes + '</strong><br /><br />' + busy + '</div>';
		str += '<div id="__wps__who_likes" style="width:49%;"><strong>' + symposium_likes + '</strong><br /><br />' + busy + '</div>';
		jQuery("#dialog").html(str).dialog({
			title: __wps__.site_title,
			width: 600,
			height: 300,
			modal: true
		});

		// Get those who like and dislike
		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/plus_functions.php",
			type: "POST",
			data: ({
				action: "getLikesDislikes",
				type: "like",
				cid: cid
			}),
			dataType: "html",
			async: true,
			success: function(str) {
				if (str != 'None') {
					var rows = jQuery.parseJSON(str);
					var likes = '';
					jQuery.each(rows, function(i, row) {
						likes += '<div style="height:28px;">';
						likes += '<div style="padding-right:6px; float:left;">' + row.avatar + '</div>';
						likes += row.display_name;
						likes += '</div>';
					});
					likes = '<strong>' + symposium_likes + '</strong><br /><br />' + likes;
					jQuery('#__wps__who_likes').html(likes);
				} else {
					jQuery('#__wps__who_likes').html('<strong>' + symposium_likes + '</strong>');
				}
			}
		});
		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/plus_functions.php",
			type: "POST",
			data: ({
				action: "getLikesDislikes",
				type: "dislike",
				cid: cid
			}),
			dataType: "html",
			async: true,
			success: function(str) {
				if (str != 'None') {
					var rows = jQuery.parseJSON(str);
					var dislikes = '';
					jQuery.each(rows, function(i, row) {
						dislikes += '<div style="height:28px;">';
						dislikes += '<div style="padding-right:6px; float:left;">' + row.avatar + '</div>';
						dislikes += row.display_name;
						dislikes += '</div>';
					});
					dislikes = '<strong>' + symposium_dislikes + '</strong><br /><br />' + dislikes;
					jQuery('#__wps__who_dislikes').html(dislikes);
				} else {
					jQuery('#__wps__who_dislikes').html('<strong>' + symposium_dislikes + '</strong>');
				}
			}
		});
	})

	// Info on what a usertag is
	jQuery("#symposium_tag").live('click', function() {
		jQuery("#symposium_tag_info").show("fast");
	})

	// YouTube embed
	jQuery("#activity_youtube_embed_button").live('click', function() {
		var m = '';
		m += 'Enter the YouTube video URL:<br /><br />';
		m += '<input type="text" id="activity_youtube_embed" style="width:430px" />';
		jQuery("#dialog").html(m);
		jQuery("#dialog").dialog({
			title: __wps__.site_title,
			width: 500,
			height: 190,
			modal: true,
			buttons: {
				"OK": function() {
					var src = jQuery("#activity_youtube_embed").val();
					if (src != '') {
						if(src.indexOf('/embed/') != -1) {
							video_id = src.split('/embed/')[1];
							video_id = video_id.split('"')[0];
						} else {
							if(src.indexOf('http://youtu.be/') == 0) {
								video_id = src.split('//')[1];
								video_id = video_id.split('/')[1];
							} else {							
								var video_id = src.split('v=')[1];
								if (typeof video_id !== "undefined") {
									if(video_id.indexOf('&') != -1) {
									  video_id = video_id.split('&')[0];
									}                    
								} else {
									video_id = src;
								}
							}
						}
					} else {
						jQuery("#dialog").dialog('close');
					}
					if (video_id != '') {
						jQuery('#activity_youtube_embed_button').attr('id', 'activity_youtube_embed_button_used');
						jQuery('#activity_youtube_embed_button_used').attr('title', video_id);
						jQuery('#activity_youtube_embed_button_used').css('cursor', 'default');
						jQuery('#activity_youtube_embed_button_used').val("Done!");
						jQuery("#dialog").dialog('close');
					}
				},
				"Cancel": function() {
					jQuery("#dialog").dialog('close');
				}
			}
		});
	})

	
	// Combined search
	if (jQuery("input#__wps__member_small").length) {

		jQuery("input#__wps__member_small").autocomplete({
			source: __wps__.plugin_url + "ajax/plus_functions.php",
			minLength: 1,
			focus: function(event, ui) {
				jQuery("input#__wps__member_small").val(ui.item.name);
				return false;
			},
			select: function(event, ui) {
				jQuery(".__wps__pleasewait").inmiddle().show().delay(3000).fadeOut("slow");
				switch (ui.item.type) {
				case 'topic':
					var q = symposium_q(__wps__.forum_url);
					window.location.href = __wps__.forum_url + q + 'show=' + ui.item.id;
					break;
				case 'post':
					window.location.href = ui.item.url;
					break;
				case 'page':
					window.location.href = ui.item.url;
					break;
				case 'amember':
					var q = symposium_q(__wps__.profile_url);
					window.location.href = __wps__.profile_url + q + 'uid=' + ui.item.value;
					break;
				case 'group':
					var q = symposium_q(__wps__.group_url);
					window.location.href = __wps__.group_url + q + 'gid=' + ui.item.value;
					break;
				case 'gallery':
					var q = symposium_q(__wps__.profile_url);
					window.location.href = __wps__.profile_url + q + 'uid=' + ui.item.owner + '&embed=on&album_id=' + ui.item.id;
					break;
				}
				return false;
			}
		}).data("uiAutocomplete")._renderItem = function(ul, item) {

			if (item.type == 'sep') {

				return jQuery('<li class="__wps__autocomplete_sep"></li>').append(item.name).appendTo(ul);

			} else {

				var group = "<a>";
				group += "<div style='height:40px; overflow: hidden; width:250px;'>";
				group += "<div style=\'float:left; background-color:#fff; margin-right: 8px; width:40px; height:40px; \'>";
				group += item.avatar;
				group += "</div>";
				group += "<div style='height:20px;overflow:hidden;'>" + item.name + "</div>";
				var sep = (item.city != '' && item.country != '') ? ', ' : '';
				group += "<div style='font-size:80%'>" + item.city + sep + item.country + "</div>";
				group += "<br style='clear:both' />";
				group += "</div>";
				group += "</a>";
				return jQuery('<li class="__wps__autocomplete_item"></li>').data("item.autocomplete", item).append(group).appendTo(ul);

			}
		};

	}

	jQuery('.__wps__follow').live('mouseenter mouseleave', function(event) {
		if (event.type == 'mouseenter' && __wps__.current_user_id > 0) {

			var display_name = jQuery(this).attr("title");
			var id = jQuery(this).attr("id");
			var rel = jQuery(this).attr("rel");
			var rev = jQuery(this).attr("rev");
			var src = jQuery(this).attr("src");

			if (id != jQuery('#nobody_user').html()) {

				var xPos = event.pageX - (jQuery('#__wps__follow_box').width() / 2);
				var yPos = event.pageY - (jQuery('#__wps__follow_box').height() / 2);
				var html = '<img id="symposium_plus_box_avatar" src="' + src + '" style="width:20px;height:20px;float:right;" />';
				var q = symposium_q(__wps__.profile_url);
				
				html += '<a style="font-size:14px;" href="' + __wps__.profile_url + q + 'uid=' + id + '">' + display_name + '</a><br />';
				if (rel == 'friend' || id == __wps__.current_user_id) {
					if (strpos(__wps__.mail_url, 'INVALID PLUGIN URL REQUESTED') === false && id != __wps__.current_user_id) {
						html += '<input id="symposium_plus_sendmail" ref="' + id + '" type="text" class="__wps__hover_input" onblur = "this.value=(this.value==\'\') ? \'' + __wps__.sendmail + '\' : this.value;"  onfocus= "this.value=(this.value==\'' + __wps__.sendmail + '\') ? \'\' : this.value;"  value  = "' + __wps__.sendmail + '" />';
					} else {
						html += '<input id="symposium_plus_post" type="text" class="__wps__hover_input"  onfocus= "this.value= \'\'"  value  = "' + __wps__.whatsup + '" />';
					}
				} else {
					if (rel == 'pending') {
						var q = symposium_q(__wps__.profile_url);
						html += '<div style="margin-top:4px;margin-bottom:8px;"><a href="' + __wps__.profile_url + q + 'view=friends">' + __wps__.friendpending + '</a></div>';
					} else {
						html += '<input id="symposium_plus_addasafriend" title="' + id + '" type="text" class="__wps__hover_input"  onblur = "this.value=(this.value==\'\') ? \'' + __wps__.addasafriend + '...\' : this.value;"  onfocus= "this.value=(this.value==\'' + __wps__.addasafriend + '...\') ? \'\' : this.value;"  value  = "' + __wps__.addasafriend + '..." />';
					}
				}
				html += "<div class='__wps__hover_box_icons' style='clear:both'>";
					html += "<img id='symposium_plus_profile' ref='" + id + "' title='" + __wps__.profile_info + "' style='float:left; margin-right:5px; cursor:pointer' src='" + __wps__.images_url + "/profile.png' />";
					html += "<img id='symposium_plus_friends' ref='" + id + "' title='" + __wps__.plus_friends + "' style='float:left; margin-right:5px; cursor:pointer' src='" + __wps__.images_url + "/friends.png' />";
					if (id == __wps__.current_user_id) {
						html += "<img id='symposium_following_who' title='" + __wps__.plus_follow_who + "' style='float:left; margin-right:5px; cursor:pointer' src='" + __wps__.images_url + "/fav-who.png' />";
						html += "<img id='symposium_plus_mail' title='" + __wps__.plus_mail + "' style='float:left; margin-right:5px; cursor:pointer' src='" + __wps__.images_url + "/mail.png' />";
					} else {
						if (rev == 'following') {
							html += "<img id='symposium_following' title='" + __wps__.unfollow + "' ref='" + id + "' style='float:left; margin-right:5px; cursor:pointer' src='" + __wps__.images_url + "/fav-on.png' />";
						} else {
							html += "<img id='symposium_following' title='" + __wps__.follow + "' ref='" + id + "' style='float:left; margin-right:5px; cursor:pointer' src='" + __wps__.images_url + "/fav-off.png' />";
						}
						if (__wps__.wps_use_poke) {
							html += "<img id='__wps__attention' title='" + __wps__.attention + "' ref='" + id + "' style='float:left; margin-right:5px; cursor:pointer' src='" + __wps__.images_url + "/attention.png' />";
						}
					}
					if (strpos(__wps__.forum_url, 'INVALID PLUGIN URL REQUESTED') === false) {
						html += "<img id='symposium_forum_search' title='" + __wps__.forumsearch + "' rel='" + display_name + "' ref='" + id + "' style='float:left; margin-right:5px; cursor:pointer' src='" + __wps__.images_url + "/search2.png' />";
					}
					if (__wps__.gallery_url != '') {
						html += '<img style="cursor:pointer" rel="' + display_name + '" id="symposium_gallery_search" src="' + __wps__.images_url + '/gallery.png" title="' + __wps__.gallerysearch + '" /></a>';
					}
				html += "</div>";

				jQuery('#__wps__follow_box').html(html);
				jQuery('#__wps__follow_box').css({
					'z-index': 999999,
					'position': 'absolute',
					'top': yPos,
					'left': xPos
				}).show();

			}
		}
	});
	jQuery('#symposium_plus_box_avatar').live('mouseenter mouseleave', function(event) {
		if (event.type == 'mouseenter') {
			jQuery('#__wps__follow_box').css({
				'height': '280px'
			});
			jQuery('#symposium_plus_box_avatar').css({
				'width': '184px',
				'height': '184px'
			});
		}
	});
	jQuery('#__wps__follow_box').live('mouseenter mouseleave', function(event) {
		if (event.type == 'mouseleave') {
			jQuery(this).hide();
			jQuery('#__wps__follow_box').css({
				'height': '87px'
			});
		}
	});

	// Go to friends page
	jQuery("#symposium_plus_friends").live('click', function() {
		jQuery(".__wps__pleasewait").inmiddle().show();
		var q = symposium_q(__wps__.profile_url);
		window.location = __wps__.profile_url + q + 'uid=' + jQuery(this).attr("ref") + '&view=friends';
	});

	// Go to profile information page
	jQuery("#symposium_plus_profile").live('click', function() {
		jQuery(".__wps__pleasewait").inmiddle().show();
		var q = symposium_q(__wps__.profile_url);
		window.location = __wps__.profile_url + q + 'uid=' + jQuery(this).attr("ref") + '&view=extended';
	});

	// Post a status on pressing return (via hover box, if mail plugin not activated)
	jQuery('#symposium_plus_post').live('keypress', function(e) {
		if (e.keyCode == 13) {
			jQuery(".__wps__pleasewait").inmiddle().show();
			var message = jQuery(this).val().replace(/(<([^>]+)>)/ig, '');
			jQuery(this).val('');

			if (message != '' && message != jQuery('#symposium_whatsup').html()) {

				// Add to wall if on page
				if (jQuery("#__wps__wall").css("display") != 'none' && __wps__.current_user_page == __wps__.current_user_id) {
					var comment = "<div class='add_wall_post_div'>";
					comment = comment + "<div class='add_wall_post'>";
					comment = comment + "<div class='add_wall_post_text'>";
					var q = symposium_q(__wps__.profile_url);
					comment = comment + '<a href="' + __wps__.profile_url + q + 'uid=' + __wps__.current_user_id + '">';
					comment = comment + __wps__.current_user_display_name + '</a><br />';
					comment = comment + message;
					comment = comment + "</div>";
					comment = comment + "</div>";
					comment = comment + "<div class='add_wall_post_avatar'>";
					comment = comment + "<img src='" + jQuery('#__wps__current_user_avatar img:first').attr('src') + "' style='width:64px; height:64px' />";
					comment = comment + "</div>";
					comment = comment + "</div>";
					jQuery(comment).prependTo('#__wps__wall');
				}

				// Update status
				jQuery.ajax({
					url: __wps__.plugin_url + "ajax/profile_functions.php",
					type: "POST",
					data: ({
						action: "addStatus",
						text: message,
						facebook: 0
					}),
					dataType: "html",
					async: true,
					success: function(str) {
						jQuery(".__wps__pleasewait").hide();
						jQuery('.__wps__follow_box').hide();

						if (__wps__.current_user_page != __wps__.current_user_id || jQuery("#__wps__wall").length == 0) {
							jQuery("#dialog").html(__wps__.whatsup_done + '<br /><br />' + message);
							jQuery("#dialog").dialog({
								title: __wps__.site_title,
								width: 300,
								height: 250,
								modal: true,
								buttons: {
									"OK": function() {
										jQuery("#dialog").dialog('close');
									},
									"View": function() {
										jQuery(".__wps__pleasewait").inmiddle().show();
										window.location = __wps__.profile_url;
									}
								}
							});
						}
					},
					error: function(xhr, ajaxOptions, thrownError) {
						if (show_js_errors) {
							alert(xhr.status);
							alert(xhr.statusText);
							alert(thrownError);
						}
					}
				});


			}

		}
	});

	// Following who?
	jQuery("#symposium_following_who").live('click', function() {
		jQuery(".__wps__pleasewait").inmiddle().show();
		var q = symposium_q(__wps__.profile_url);
		window.location = __wps__.profile_url + q + 'view=plus';
	});

	// Go to mail (via hover box)
	jQuery("#symposium_plus_mail").live('click', function() {
		jQuery(".__wps__pleasewait").inmiddle().show();
		window.location = __wps__.mail_url;
	});

	// Gallery search (via hover box)
	jQuery("#symposium_gallery_search").live('click', function() {
		jQuery(".__wps__pleasewait").inmiddle().show();
		var q = symposium_q(__wps__.gallery_url);
		window.location = __wps__.gallery_url + q + 'term=' + jQuery(this).attr("rel");
	});

	// Forum search (via hover box)
	jQuery("#symposium_forum_search").live('click', function() {
		do_show_search();
		jQuery("#search-box-input").val(jQuery(this).attr("rel"));
		do_forum_search();
	});

	// Grab attention
	jQuery("#__wps__attention").live('click', function() {
		var avatar = "<img style='float:left; width:48px; height:48px;margin-right:5px;' src='" + jQuery('#symposium_plus_box_avatar').attr("src") + "' />";
		jQuery("#dialog").html(avatar + __wps__.sent);
		jQuery("#dialog").dialog({
			title: __wps__.site_title,
			width: 200,
			height: 175,
			modal: true,
			buttons: {
				"OK": function() {
					jQuery("#dialog").dialog('close');
				}
			}
		});

		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/profile_functions.php",
			type: "POST",
			data: ({
				action: 'send_poke',
				recipient: jQuery(this).attr("ref")
			}),
			dataType: "html",
			async: true,
			success: function(str) {}
		});
	});

	// Toggle following (via profile header)
	jQuery(".follow-button").live('click', function() {

		jQuery(".__wps__pleasewait").inmiddle().show();
		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/plus_functions.php",
			type: "POST",
			data: ({
				action: 'toggle_following',
				following: __wps__.current_user_page
			}),
			dataType: "html",
			async: true,
			success: function(str) {
				if (str != 'NOT LOGGED IN') {
					jQuery(".follow-button").val(str);
				}
			}
		});
		jQuery(".__wps__pleasewait").hide();

	});

	// Toggle following (via hover box)
	jQuery("#symposium_following").live('click', function() {
		jQuery(".__wps__pleasewait").inmiddle().show();
		if (jQuery(this).attr("src") == __wps__.images_url + '/fav-on.png') {
			// Remove from following
			jQuery(this).attr("src", __wps__.images_url + '/fav-off.png');
			jQuery.ajax({
				url: __wps__.plugin_url + "ajax/plus_functions.php",
				type: "POST",
				data: ({
					action: 'toggle_following',
					following: jQuery(this).attr("ref")
				}),
				dataType: "html",
				async: false
			});
		} else {
			// Add to following
			jQuery(this).attr("src", __wps__.images_url + '/fav-on.png');
			jQuery.ajax({
				url: __wps__.plugin_url + "ajax/plus_functions.php",
				type: "POST",
				data: ({
					action: 'toggle_following',
					following: jQuery(this).attr("ref")
				}),
				dataType: "html",
				async: false
			});
		}
		location.reload();
	});

	// Send mail on pressing return
	jQuery('#symposium_plus_sendmail').live('keypress', function(e) {
		if (e.keyCode == 13) {
			jQuery(".__wps__sending").inmiddle().show();
			var recipient_id = jQuery(this).attr("ref");
			var message = jQuery(this).val().replace(/(<([^>]+)>)/ig, '');
			jQuery(this).val('');

			jQuery.ajax({
				url: __wps__.plugin_url + "ajax/mail_functions.php",
				type: "POST",
				data: ({
					action: "sendMail",
					compose_recipient_id: recipient_id,
					compose_subject: __wps__.privatemail,
					compose_text: message,
					compose_previous: ''
				}),
				dataType: "html",
				async: true,
				success: function(str) {
					jQuery(".__wps__sending").hide();
					jQuery('.__wps__follow_box').hide();
					var avatar = "<img style='float:left; width:48px; height:48px;margin-right:10px;' src='" + jQuery('#symposium_plus_box_avatar').attr("src") + "' />";
					jQuery("#dialog").html(avatar + message);
					jQuery("#dialog").dialog({
						title: __wps__.privatemailsent,
						width: 300,
						height: 250,
						modal: true,
						buttons: {
							"OK": function() {
								jQuery("#dialog").dialog('close');
							}
						}
					});
				},
				error: function(xhr, ajaxOptions, thrownError) {
					if (show_js_errors) {
						alert(xhr.status);
						alert(xhr.statusText);
						alert(thrownError);
					}
				}
			});

		}
	});

	// Add as a friend on pressing return
	jQuery('#symposium_plus_addasafriend').live('keypress', function(e) {
		if (e.keyCode == 13) {
			jQuery(".__wps__pleasewait").inmiddle().show();
			addasfriend(this, jQuery('#symposium_plus_addasafriend').val());
			jQuery(".__wps__pleasewait").hide();
		}
	});


/*
	   +------------------------------------------------------------------------------------------+
	   |                                          FORUM                                           |
	   +------------------------------------------------------------------------------------------+
	*/


	// If using AJAX, set up Forum deep linking
	if (__wps__.forum_ajax == 'on') {

		jQuery(window).bind('hashchange', function(e) {

			var tmp_loc = window.location.href.replace(/\//g, '').toLowerCase();
			var tmp_url = __wps__.forum_url.replace(/\//g, '').toLowerCase();
			if (strpos(tmp_loc, tmp_url) !== false) {
				var hash = window.location.hash.replace(/#/g, '');
				if (hash == '') {
					if (tmp_loc == tmp_url) {
						getForum(0);
					} else {
						var params = tmp_loc.split('?');
						var pieces = params[1].split('&');
						var goto_cid = false;
						var cid = 0;
						var goto_tid = false;
						for (var num = 0; num < pieces.length; num++) {
							var piece = pieces[num].split('=');
							if (piece[0] == 'cid') {
								goto_cid = true;
								cid = piece[1];
							}
							if (piece[0] == 'tid' || piece[0] == 'show') {
								goto_tid = true;
								var tid = piece[1];
							}

						}
						if (goto_tid == true) {
							getTopic(tid);
						} else {
							if (jQuery("#new-topic").length == 0 && goto_cid == false && goto_tid == false) {
								getForum(__wps__.cat_id);
							}
							if (goto_cid == true && goto_tid == false) {
								getForum(cid);
							}
						}
					}
				} else {
					var pieces = hash.split(',');
					var goto_cid = false;
					var cid = 0;
					var goto_tid = false;
					for (var num = 0; num < pieces.length; num++) {
						var piece = pieces[num].split('=');
						if (piece[0] == 'cid') {
							if (__wps__.cat_id != piece[1]) {
								goto_cid = true;
								cid = piece[1];
							}
						}
						if (piece[0] == 'tid') {
							goto_tid = true;
							var tid = piece[1];
						}

					}
					if (goto_tid == true) {
						getTopic(tid);
					} else {
						if (jQuery("#new-topic").length == 0 && goto_cid == false && goto_tid == false) {
							getForum(__wps__.cat_id);
						}
						if (goto_cid == true && goto_tid == false) {
							getForum(cid);
						}
					}
				}
			}

			// BB Toolbar
			__wps__evoke_bbcode_toolbar();

		});
	}

	// On page load, get forum top level, but first check for deep linking	
	if (jQuery("#__wps__forum_div").length) {

		// Make sure reply field is empty
		jQuery('#__wps__reply_text').val('');
		if (jQuery("#__wps__forum_div").html() == '') {

			var sub = "getForum";
			if (__wps__.show_tid > 0) {
				var sub = "getTopic";
			}

			var hash = window.location.hash.replace(/#/g, '');
			if (hash != '') {

				var pieces = hash.split(',');
				var goto_cid = false;
				var goto_tid = false;
				for (var num = 0; num < pieces.length; num++) {
					var piece = pieces[num].split('=');
					if (piece[0] == 'cid') {
						__wps__.cat_id = piece[1];
						goto_cid = true;
					}
					if (piece[0] == 'tid') {
						goto_tid = true;
						__wps__.show_tid = piece[1];
					}

				}
				if (goto_cid == true && goto_tid == false) {
					sub = "getForum";
				}
				if (goto_tid == true) {
					sub = "getTopic";
				}
			}

			jQuery(".__wps__pleasewait").inmiddle().show();

			// Check for permalink over-rides
			if (jQuery("#symposium_perma_topic_id").length) {
				__wps__.show_tid = jQuery("#symposium_perma_topic_id").html();
				if (jQuery("#symposium_perma_cat_id").length) {
					__wps__.cat_id = jQuery("#symposium_perma_cat_id").html();
				}
				if (__wps__.show_tid != '') {
					sub = "getTopic";
				} else {
					sub = "getForum";
				}
			}
			var show_tid = __wps__.show_tid;
			if (show_tid == '') show_tid = '0';

			jQuery.ajax({
				url: __wps__.plugin_url + "ajax/forum_functions.php",
				type: "POST",
				data: ({
					action: sub,
					limit_from: 0,
					cat_id: __wps__.cat_id,
					topic_id: __wps__.show_tid,
					group_id: __wps__.current_group
				}),
				dataType: "html",
				async: true,
				success: function(str) {

					jQuery(".__wps__pleasewait").fadeOut("slow");

					if (str != 'DONTSHOW') {
						str = trim(str);

						if (strpos(str, "[|]", 0)) {
							var details = str.split("[|]");
							jQuery(document).attr('title', details[0]);
							str = details[1];

							// Strip out redundant <br /> tags that occur with lists in TinyMCE
							if (__wps__.wps_wysiwyg == 'skipping_this_as_caused_layout_problems_on') {
								str = str.replace(/(<ul.*?>)(.*)(?=<\/ul>)/gi, function(x, y, z) {
									return y + z.replace(/\<br \/\>/gi, '')
								});
								str = str.replace('<br /><ul>', '<ul>').replace('</ul><br />', '</ul>');
								str = str.replace('<br /><ol>', '<ol>').replace('</ol><br />', '</ol>');
							}

						}
						jQuery("#__wps__forum_div").html(str);

						// Enable file uploading
						__wps__init_file_upload();

						// Init TinyMCE
						if (__wps__.wps_wysiwyg == 'on') {
							if (sub == 'getForum') {
								tiny_mce_init('new_topic_text');
								if (typeof tinyMCE.get("new_topic_text") != 'undefined') tinyMCE.get('new_topic_text').setContent('');
							} else {
								tiny_mce_init('__wps__reply_text');
								if (typeof tinyMCE.get("__wps__reply_text") != 'undefined') tinyMCE.get('__wps__reply_text').setContent('');
							}
						}

					}

				},
				error: function(xhr, ajaxOptions, thrownError) {
					if (show_js_errors) {
						alert(xhr.status);
						alert(xhr.statusText);
						alert(thrownError);
					}
				}

			});

		} else {
			
			var str = jQuery("#__wps__forum_div").html();
			// strip out and apply page title if available
			if (strpos(str, "[|]", 0)) {
				var details = str.split("[|]");
				jQuery(document).attr('title', details[0]);
				str = details[1];
				jQuery("#__wps__forum_div").html(str);
			}

			// content already there, i.e. not AJAX loaded
			tiny_mce_init('new_topic_text');
			tiny_mce_init('__wps__reply_text');

		}

		// Enable file uploading
		if (__wps__.show_tid == 0) {
			jQuery("#symposium_perma_topic_id").html() != '' ? __wps__.show_tid = jQuery("#symposium_perma_topic_id").html() : 0;
		} // If not using AJAX
		if (!__wps__.show_tid && __wps__.show_tid != 0) __wps__.show_tid = 0;

		// Enable file uploading
		__wps__init_file_upload();

		// BB Toolbar
		__wps__evoke_bbcode_toolbar();		

	}

	// Set up auto-expanding textboxes
	if (jQuery(".elastic").length) {
		jQuery('.elastic').elastic();
	}

	// Clicked on a social network icon
	jQuery(".symposium_social_share").live('click', function() {
		var destination = jQuery(this).attr("id");
		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/forum_functions.php",
			type: "POST",
			data: ({
				action: "socialShare",
				destination: destination
			}),
			dataType: "html",
			async: true
		});
	});

	// Comment on a reply
	jQuery(".quick-comment-box-show-link").live('click', function() {
		jQuery(this).hide();
		jQuery(this).parent().parent().find(".quick-comment-box").show();
		if (jQuery(".elastic").length) {
			jQuery('.elastic').elastic();
		}
	});
	jQuery(".quick-comment-box-add").live('click', function() {
		var comment = jQuery(this).parent().find(".quick-comment-box-comment").val();
		comment = comment.replace(/\</g, "&lt;").replace(/\>/g, "&gt;").replace(/\n/g, "<br />");

		jQuery(this).parent().find(".quick-comment-box-comment").val('');
		jQuery(this).parent().hide();

		var html = "<div class='reply-comments-reply'>";
		var a = jQuery('#__wps__current_user_avatar').html();
		if (a && a.length > 0) {
			a = a.replace(/200/g, '32');
			a = a.replace(/196/g, '32');
			html += a;
		} else {
			// Problem retrieving user avatar - is there a wp_footer action to run __wps__lastactivity() ?
		}

		html += "<div style='margin-left: 45px;'>";
		html += comment;
		html += "</div>";

		// Add to the page
		jQuery(html).appendTo(jQuery(this).parent().parent().find(".reply-comments-box"));
		var comment_link = jQuery(this).parent().parent().find('.quick-comment-box-show-link');
		// Show after a pause to avoid multiple posts due to inpatience
		setTimeout(function() {
			comment_link.show('slow');
		}, 3000);	

		// Now add to database
		// If successful, send out email notifications
		// No need to wait for emails to be sent, refresh page if not using AJAX or refreshing the page

		var rid = jQuery(this).attr("rel");
		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/forum_functions.php",
			type: "POST",
			data: ({
				action: "replycomment",
				'tid': jQuery('#__wps__reply_tid').val(),
				'cid': jQuery('#__wps__reply_cid').val(),
				'rid': rid,
				'comment_text': comment,
				'group_id': __wps__.current_group
			}),
			dataType: "html",
			async: true,
			success: function(str) {

				// Stored in database, so tell members via email
				if (__wps__.debug == 'on') {
					alert('STR: ' + str);
				}

				jQuery.ajax({
					url: __wps__.plugin_url + "ajax/forum_functions.php",
					type: "POST",
					data: ({
						action: "replycommentemails",
						'rid': str
					}),
					async: true,
					success: function(str) {
						if (__wps__.debug) {
							jQuery("#dialog").html(str).dialog({
								title: __wps__.site_title + ' debug info (send replies)',
								width: 800,
								height: 500,
								modal: true
							});
						}
					},
					error: function(xhr, ajaxOptions, thrownError) {
						if (show_js_errors) {
							alert(xhr.status);
							alert(xhr.statusText);
							alert(thrownError);
						}
					}
				});

			},
			error: function(xhr, ajaxOptions, thrownError) {
				if (__wps__.debug) {
					alert(xhr.status);
					alert(xhr.statusText);
					alert(thrownError);
				}
			}

		});

	});
	

	// Answer accepted
	jQuery(".forum_post_answer").live('click', function() {
		var tid = jQuery(this).attr("id");

		jQuery(this).text("");
		jQuery("<img id='symposium_tmp' src='" + __wps__.images_url + "/busy.gif' />").prependTo(this);

		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/forum_functions.php",
			type: "POST",
			data: ({
				action: "acceptAnswer",
				tid: tid
			}),
			dataType: "html",
			async: true,
			success: function(str) {

				var row = jQuery.parseJSON(str)[0];
				jQuery("#dialog").html(row.message);
				jQuery("#dialog").dialog({
					title: row.title,
					width: 600,
					height: 225,
					modal: true,
					buttons: {
						"OK": function() {
							jQuery("#dialog").dialog('close');
						}
					}
				});

			}
		});

		jQuery('.forum_post_answer').hide();
		jQuery('#symposium_accepted_answer').hide();
		jQuery("#symposium_tmp").hide();
		jQuery(this).show();
		jQuery("<img id='symposium_tmp' src='" + __wps__.images_url + "/tick.png' />").prependTo(this);

	});

	// Remove uploaded image
	jQuery(".remove_forum_post").live('click', function() {

		var folder = jQuery(this).attr("id");
		var file = jQuery(this).attr("title");
		var me = this;
		jQuery(this).attr('src', __wps__.images_url + "/busy.gif");

		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/forum_functions.php",
			type: "POST",
			data: ({
				action: "removeUploadedImage",
				folder: folder,
				file: file
			}),
			dataType: "html",
			async: true,
			success: function(str) {
				if (str == 'OK') {
					jQuery(me).parent().hide();
				} else {
					jQuery("#dialog").html(str).dialog({
						title: __wps__.site_title + ' debug info',
						width: 800,
						height: 500,
						modal: true
					});
				}
			}
		});
	});

	// Warning
	jQuery(".symposium_report").live('click', function() {

		var code = jQuery(this).attr('title');
		var str = '<p>Please provide as much information about your report to the site administrator as possible.</p>';
		str += '<p style="margin-top:3px"><em>Ref: ' + code + '</em></p>';
		str += '<p style="margin-top:3px"><em>URL: ' + document.location.href + '</em></p>';
		str += '<textarea id="report_text" style="margin:auto;margin-top:10px;width:98%; height:200px"></textarea>';
		jQuery("#dialog").html(str);
		jQuery("#dialog").dialog({
			title: __wps__.site_title,
			width: 600,
			height: 400,
			modal: true,
			buttons: {
				"Report": function() {

					jQuery.ajax({
						url: __wps__.plugin_url + "ajax/ajax_functions.php",
						type: "POST",
						data: ({
							action: "sendReport",
							report_text: jQuery('#report_text').val(),
							url: document.location.href,
							code: code
						}),
						dataType: "html",
						async: true,
						success: function(str) {
							jQuery("#dialog").html('Your report has been sent to the site administrator.');
							jQuery("#dialog").dialog({
								title: __wps__.site_title,
								width: 650,
								height: 150,
								modal: true,
								buttons: {}
							});
						},
						error: function(xhr, ajaxOptions, thrownError) {
							if (show_js_errors) {
								alert(xhr.status);
								alert(xhr.statusText);
								alert(thrownError);
							}
						}
					});
					jQuery(this).dialog("close");
				},
				"Cancel": function() {
					jQuery(this).dialog("close");
				}
			}
		});

	});

	// Share permalink
	jQuery("#share_permalink").live('click', function() {
		var str = 'Copy and Paste the following:';
		str += '<br /><input type="text" style="width:550px;" value="' + jQuery(this).attr("title") + '" />';
		jQuery("#dialog").html(str);
		jQuery("#dialog").dialog({
			title: __wps__.site_title,
			width: 650,
			height: 150,
			modal: true,
			buttons: {}
		});
	});

	// Clicked on show more...
	jQuery("#showmore_forum").live('click', function() {

		var details = jQuery(this).attr("title").split(",");
		limit_from = details[0];
		cat_id = details[1];

		jQuery('#showmore_forum').html("<img src='" + __wps__.images_url + "/busy.gif' />");

		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/forum_functions.php",
			type: "POST",
			data: ({
				action: "getForum",
				limit_from: limit_from,
				cat_id: cat_id,
				group_id: __wps__.current_group
			}),
			dataType: "html",
			async: true,
			success: function(str) {
				jQuery('#showmore_forum').remove();
				jQuery(str).appendTo('#__wps__table').hide().slideDown("slow");
			}
		});

	});

	// Click on drop-down list to change category
	jQuery("#__wps__change_forum_category").live('change', function() {	
		var cat_id = jQuery(this).val();
		if (cat_id >= 0) {
			getForum(cat_id);
		}
	});	
	
	// Click on category title to drill down
	jQuery(".category_title").live('click', function() {
		getForum(jQuery(this).attr("title"));
	});

	function getForum(id) {

		__wps__.cat_id = id;

		jQuery(".__wps__pleasewait").inmiddle().show();

		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/forum_functions.php",
			type: "POST",
			data: ({
				action: "getForum",
				cat_id: id,
				group_id: __wps__.current_group
			}),
			dataType: "html",
			async: true,
			success: function(str) {

				str = trim(str);
				if (strpos(str, "[|]", 0)) {
					var details = str.split("[|]");
					jQuery(document).attr('title', details[0]);
					str = details[1];
				}

				if (jQuery("#__wps__forum_div").length) {
					jQuery("#__wps__forum_div").html(str);
				} else {
					jQuery("#group_body").html(str);
				}

				// Enable file uploading
				__wps__init_file_upload();

				//window.scrollTo(0,0);
				jQuery(".__wps__pleasewait").fadeOut("slow");

				// Set up auto-expanding textboxes
				if (jQuery(".elastic").length) {
					jQuery('.elastic').elastic();
				}

				// Init TinyMCE
				tiny_mce_init('new_topic_text');

				jQuery(".__wps__pleasewait").fadeOut("slow");

			},
			error: function(xhr, ajaxOptions, thrownError) {
				if (show_js_errors) {
					alert(xhr.status);
					alert(xhr.statusText);
					alert(thrownError);
				}
			}

		});

	}

	// Click on topic subject title
	jQuery(".topic_subject").live('click', function() {
		getTopic(jQuery(this).attr("title"));
	});
	function getTopic(id) {

		jQuery(".__wps__pleasewait").inmiddle().show();

		var topic_id = id;
		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/forum_functions.php",
			type: "POST",
			data: ({
				action: "getTopic",
				topic_id: topic_id,
				group_id: __wps__.current_group
			}),
			dataType: "html",
			async: true,
			success: function(str) {
				str = trim(str);
				if (strpos(str, "[|]", 0)) {
					var details = str.split("[|]");
					jQuery(document).attr('title', details[0]);
					str = details[1];
				}

				// Show the content
				if (jQuery("#__wps__forum_div").length) {
					jQuery("#__wps__forum_div").html(str);
				} else {
					jQuery("#group_body").html(str);
				}

				// Enable file uploading
				__wps__init_file_upload();

				//window.scrollTo(0,0);
				jQuery(".__wps__pleasewait").fadeOut("slow");

				// Set up auto-expanding textboxes
				if (jQuery(".elastic").length) {
					jQuery('.elastic').elastic();
				}

				// Init TinyMCE
				tiny_mce_init('__wps__reply_text');

			},
			error: function(xhr, ajaxOptions, thrownError) {
				if (show_js_errors) {
					alert(xhr.status);
					alert(xhr.statusText);
					alert(thrownError);
				}
			}

		});

	}

	// Fav Icon
	jQuery("#fav_link").live('click', function() {

		if (jQuery('#fav_link').attr('src') == __wps__.images_url + '/fav-on.png') {
			jQuery('#fav_link').attr({
				src: __wps__.images_url + '/fav-off.png'
			});
		} else {
			jQuery('#fav_link').attr({
				src: __wps__.images_url + '/fav-on.png'
			});
		}
		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/forum_functions.php",
			type: "POST",
			data: ({
				action: "toggleFav",
				tid: jQuery(this).attr("title")
			}),
			dataType: "html",
			async: true,
			success: function(str) { }
		});

	});

	
	// Show favourites list
	jQuery("#show_favs").live('click', function() {

		jQuery("#dialog").html("<img src='" + __wps__.images_url + "/busy.gif' />");
		jQuery("#dialog").dialog({
			title: __wps__.site_title,
			width: 850,
			height: 500,
			modal: true,
			buttons: {}
		});

		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/forum_functions.php",
			type: "POST",
			data: ({
				action: "getFavs",
				tid: jQuery(this).attr("title")
			}),
			dataType: "html",
			async: true,
			success: function(str) {
				jQuery("#dialog").html(str);
			},
			error: function(xhr, ajaxOptions, thrownError) {
				if (show_js_errors) {
					alert(xhr.status);
					alert(xhr.statusText);
					alert(thrownError);
				}
			}

		});
	});
	// Delete a favourite
	jQuery(".__wps__delete_fav").live('click', function() {

		jQuery(".__wps__notice").inmiddle().fadeIn();

		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/forum_functions.php",
			type: "POST",
			data: ({
				action: "toggleFav",
				tid: jQuery(this).attr("title")
			}),
			dataType: "html",
			async: true,
			success: function(str) {
				jQuery("#fav_" + str).slideUp("slow");
			},
			error: function(xhr, ajaxOptions, thrownError) {
				if (show_js_errors) {
					alert(xhr.status);
					alert(xhr.statusText);
					alert(thrownError);
				}
			}

		});

		jQuery(".__wps__notice").delay(100).fadeOut("slow");

	});
	// Delete fav link
	jQuery('.fav_row').live('mouseenter mouseleave', function(event) {
		if (event.type == 'mouseenter') {
			jQuery(this).find(".__wps__delete_fav").show();
		} else {
			jQuery(this).find(".__wps__delete_fav").hide();
		}
	});

	// Show activity list
	jQuery("#show_activity").live('click', function() {

		jQuery("#dialog").html("<img src='" + __wps__.images_url + "/busy.gif' />");
		jQuery("#dialog").dialog({
			title: __wps__.site_title,
			width: 850,
			height: 500,
			modal: true,
			buttons: {}
		});

		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/forum_functions.php",
			type: "POST",
			data: ({
				action: "getActivity"
			}),
			dataType: "html",
			async: true,
			success: function(str) {
				jQuery("#dialog").html(str);
			},
			error: function(xhr, ajaxOptions, thrownError) {
				if (show_js_errors) {
					alert(xhr.status);
					alert(xhr.statusText);
					alert(thrownError);
				}
			}

		});
	});
	// Show all activity list
	jQuery("#show_all_activity").live('click', function() {

		jQuery("#dialog").html("<img src='" + __wps__.images_url + "/busy.gif' />");
		jQuery("#dialog").dialog({
			title: __wps__.site_title,
			width: 850,
			height: 500,
			modal: true,
			buttons: {}
		});

		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/forum_functions.php",
			type: "POST",
			data: ({
				action: "getAllActivity",
				gid: __wps__.current_group
			}),
			dataType: "html",
			async: true,
			success: function(str) {
				jQuery("#dialog").html(str);
			},
			error: function(xhr, ajaxOptions, thrownError) {
				if (show_js_errors) {
					alert(xhr.status);
					alert(xhr.statusText);
					alert(thrownError);
				}
			}

		});
	});
	// Show all activity threads
	jQuery("#show_threads_activity").live('click', function() {
		
		jQuery("#dialog").html("<img src='" + __wps__.images_url + "/busy.gif' />");
		jQuery("#dialog").dialog({
			title: __wps__.site_title,
			width: 850,
			height: 500,
			modal: true,
			buttons: {}
		});

		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/forum_functions.php",
			type: "POST",
			data: ({
				action: "getThreadsActivity",
				gid: __wps__.current_group
			}),
			dataType: "html",
			async: true,
			success: function(str) {
				jQuery("#dialog").html(str);
			},
			error: function(xhr, ajaxOptions, thrownError) {
				if (show_js_errors) {
					alert(xhr.status);
					alert(xhr.statusText);
					alert(thrownError);
				}
			}

		});
	});

	// Show search
	jQuery("#show_search").live('click', function() {
		do_show_search();
	});

	function do_show_search() {
		var search_form = "<div id='search-box' style='clear:both;margin-top:6px;'>";
		search_form += "<input type='text' id='search-box-input' style='width:50%; float: left; ' />";
		search_form += "<input type='submit' class='__wps__button' style='margin-top:2px; margin-left:10px;' id='search-box-button' value='"+__wps__.go+"' />";
		search_form += "</div>";
		search_form += "<div id='search-internal' style='clear:both;padding-left:6px;'></div>";
		jQuery("#dialog").html(search_form);
		jQuery("#dialog").dialog({
			title: __wps__.site_title,
			width: 850,
			height: 500,
			modal: true,
			buttons: {}
		});
	}

	// Do search on pressing return
	jQuery('#search-box-input').live('keypress', function(e) {
		if (e.keyCode == 13) {
			do_forum_search();
		}
	});
	// Do search on pressing button
	jQuery("#search-box-button").live('click', function() {
		do_forum_search();
	});

	function do_forum_search() {
		jQuery("#search-internal").html("<img src='" + __wps__.images_url + "/busy.gif' />");

		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/forum_functions.php",
			type: "POST",
			data: ({
				action: "getSearch",
				term: jQuery("#search-box-input").val()
			}),
			dataType: "html",
			async: true,
			success: function(str) {
				jQuery("#search-internal").hide().html(str).fadeIn("slow");
			},
			error: function(xhr, ajaxOptions, thrownError) {
				if (show_js_errors) {
					alert(xhr.status);
					alert(xhr.statusText);
					alert(thrownError);
				}
			}

		});
	}

	// Edit topic (AJAX)
	jQuery('#starting-post').live('mouseenter mouseleave', function(event) {
		if (event.type == 'mouseenter') {
			jQuery(this).find(".symposium_report").show();
			jQuery(this).find(".edit").show();
		} else {
			jQuery(this).find(".symposium_report").hide();
			jQuery(this).find(".edit").hide();
		}
	});

	// Edit the topic
	jQuery("#edit-this-topic").live('click', function() {

		var tid = jQuery(this).attr("title");
		jQuery("#dialog").html("<img src='" + __wps__.images_url + "/busy.gif' />");
		var h = 430;
		if (__wps__.wps_wysiwyg == 'on') {
			h = 580;
		}
		jQuery("#dialog").dialog({
			title: __wps__.site_title,
			width: 600,
			height: h,
			modal: true,
			closeOnEscape: false,
			open: function(event, ui) { jQuery(".ui-dialog-titlebar-close").hide(); },
			buttons: {
				"Update": function() {
					jQuery(".__wps__notice").inmiddle().show();
					var tid = jQuery(".edit-topic-tid").attr("id");
					var parent = jQuery(".edit-topic-parent").attr("id");
					var topic_subject = jQuery(".new-topic-subject-input").val();
					var topic_post = jQuery("#edit_topic_text").val();
					if (__wps__.wps_wysiwyg == 'on') {
						var topic_post = tinyMCE.get('edit_topic_text').getContent();
					}
					var topic_category = jQuery(".new-category").val();

					if (parent == 0) {
						jQuery(".topic-post-header").html(topic_subject.replace(/\</g, "&lt;").replace(/\>/g, "&gt;"));
						if (__wps__.wps_wysiwyg != 'on') {
							jQuery(".topic-post-post").html(topic_post.replace(/\</g, "&lt;").replace(/\>/g, "&gt;").replace(/\n/g, "<br />"));
						} else {
							jQuery(".topic-post-post").html(topic_post);
						}
					} else {
						if (__wps__.wps_wysiwyg != 'on') {
							jQuery("#child_" + tid).html("<p>" + topic_post.replace(/\</g, "&lt;").replace(/\>/g, "&gt;").replace(/\n/g, "<br />") + "</p>");
						} else {
							jQuery("#child_" + tid).html(topic_post);
						}
					}

					jQuery.ajax({
						url: __wps__.plugin_url + "ajax/forum_functions.php",
						type: "POST",
						data: ({
							action: "updateEditDetails",
							'tid': tid,
							'topic_subject': topic_subject,
							'topic_post': topic_post,
							'topic_category': topic_category
						}),
						dataType: "html",
						async: true,
						success: function(str) {
							jQuery(".__wps__notice").fadeOut("fast");
						},
						error: function(xhr, ajaxOptions, thrownError) {
							if (show_js_errors) {
								alert(xhr.status);
								alert(xhr.statusText);
								alert(thrownError);
							}
						}
					});
					jQuery("#edit-topic-div").html(window.html_tmp);
					jQuery(this).dialog("close");

				},
				"Cancel": function() {
					jQuery("#edit-topic-div").html(window.html_tmp);
					jQuery(this).dialog("close");

				}

			}
		});


		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/forum_functions.php",
			type: "POST",
			data: ({
				action: "getEditDetails",
				tid: tid
			}),
			dataType: "html",
			async: true,
			success: function(str) {

				jQuery("#dialog").html(jQuery("#edit-topic-div").html());

				window.html_tmp = jQuery("#edit-topic-div").html();
				jQuery("#edit-topic-div").html('');
				jQuery(".new-category-div").show();
				var details = str.split("[split]");
				jQuery(".new-topic-subject-input").val(details[0]);
				jQuery(".new-topic-subject-input").removeAttr("disabled");
				jQuery("#edit_topic_text").html(details[1]);
				jQuery(".edit-topic-parent").attr("id", details[2]);
				jQuery(".new-category").val(details[4]);

				// Init TinyMCE
				tiny_mce_init('edit_topic_text');

			},
			error: function(xhr, ajaxOptions, thrownError) {
				if (show_js_errors) {
					alert(xhr.status);
					alert(xhr.statusText);
					alert(thrownError);
				}
			}

		});
	});

	// Edit a reply
	jQuery(".edit_forum_reply").live('click', function() {

		var tid = jQuery(this).attr("id");
		jQuery("#dialog").html("<img src='" + __wps__.images_url + "/busy.gif' />");
		var h = 430;
		if (__wps__.wps_wysiwyg == 'on') {
			h = 580;
		}
		jQuery("#dialog").dialog({
			title: __wps__.site_title,
			width: 600,
			height: h,
			modal: true,
			closeOnEscape: false,
			open: function(event, ui) { jQuery(".ui-dialog-titlebar-close").hide(); },
			buttons: {
				"Update": function() {
					var tid = jQuery(".edit-topic-tid").attr("id");
					var parent = jQuery(".edit-topic-parent").attr("id");
					var topic_subject = jQuery(".new-topic-subject-input").val();
					var topic_post = jQuery("#edit_topic_text").val();
					if (__wps__.wps_wysiwyg == 'on') {
						var topic_post = tinyMCE.get('edit_topic_text').getContent();
					}
					var topic_category = jQuery(".new-category").val();

					var bbcodes_ok = __wps__bbcodes_ok(topic_post);
					if (!bbcodes_ok) {
						alert(__wps__.bbcode_problem);
					} else {

						jQuery(".__wps__notice").inmiddle().show();

						if (parent == 0) {
							jQuery(".topic-post-header").html(topic_subject);
							if (__wps__.wps_wysiwyg != 'on') {
								jQuery(".topic-post-post").html(topic_post.replace(/\</g, "&lt;").replace(/\>/g, "&gt;").replace(/\n/g, "<br />"));
							} else {
								jQuery(".topic-post-post").html(topic_post);
							}
						} else {
							if (__wps__.wps_wysiwyg != 'on') {
								jQuery("#child_" + tid).html("<p>" + topic_post.replace(/\</g, "&lt;").replace(/\>/g, "&gt;").replace(/\n/g, "<br />") + "</p>");
							} else {
								jQuery("#child_" + tid).html("<p>" + topic_post + "</p>");
							}
						}

						jQuery.ajax({
							url: __wps__.plugin_url + "ajax/forum_functions.php",
							type: "POST",
							data: ({
								action: "updateEditDetails",
								'tid': tid,
								'topic_subject': topic_subject,
								'topic_post': topic_post,
								'topic_category': topic_category
							}),
							dataType: "html",
							async: true,
							success: function(str) {
								jQuery(".__wps__notice").fadeOut("fast");
							},
							error: function(xhr, ajaxOptions, thrownError) {
								if (show_js_errors) {
									alert(xhr.status);
									alert(xhr.statusText);
									alert(thrownError);
								}
							}
						});
						jQuery("#edit-topic-div").html(window.html_tmp);
						jQuery(this).dialog("close");
					}
				},
				"Cancel": function() {
					jQuery("#edit-topic-div").html(window.html_tmp);
					jQuery(this).dialog("close");
				}
			}
		});

		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/forum_functions.php",
			type: "POST",
			data: ({
				action: "getEditDetails",
				tid: tid
			}),
			dataType: "html",
			async: true,
			success: function(str) {
				jQuery("#dialog").html(jQuery("#edit-topic-div").html());
				window.html_tmp = jQuery("#edit-topic-div").html();
				jQuery("#edit-topic-div").html('');
				jQuery(".new-category-div").hide();
				var details = str.split("[split]");
				jQuery(".new-topic-subject-input").val(details[0]);
				jQuery(".new-topic-subject-input").attr("disabled", "enabled");
				jQuery("#edit_topic_text").html(details[1]);
				jQuery(".edit-topic-parent").attr("id", details[2]);
				jQuery(".edit-topic-tid").attr("id", details[3]);

				// Init TinyMCE
				tiny_mce_init('edit_topic_text');

			},
			error: function(xhr, ajaxOptions, thrownError) {
				if (show_js_errors) {
					alert(xhr.status);
					alert(xhr.statusText);
					alert(thrownError);
				}
			}

		});

	});

	// Add new reply to a topic
	jQuery("#quick-reply-warning").live('click', function() {

		if (!__wps__.use_wp_editor) {

			var reply_text = jQuery('#__wps__reply_text').val().replace(/[\n\r]$/, "");

			if (__wps__.wps_wysiwyg == 'on') {
				var reply_text = tinyMCE.get('__wps__reply_text').getContent();
			}

		} else {
			var reply_text = tinyMCE.activeEditor.getContent();
		}

		if (reply_text == '') {
			if (__wps__.wps_wysiwyg != 'on') {
				jQuery("#__wps__reply_text").css('border', '1px solid red').effect("highlight", {}, 4000);
			}
		} else {

			// Check for unclosed BB Codes
			var bbcodes_ok = __wps__bbcodes_ok(reply_text);
			if (!bbcodes_ok) {
				alert(__wps__.bbcode_problem);
			} else {

				// Temporarily hide
				jQuery('#reply-topic-bottom').hide();

				if (__wps__.wps_forum_refresh != 'on') {

					// As it's AJAX, reshow reply form after a little pause
					setTimeout(function() {
						jQuery('#reply-topic-bottom').slideDown('fast');
					}, 3000);

					var html = "<div class='child-reply' style='overflow:hidden'>";
					html += "<div class='avatar'>";
					var a = jQuery('#__wps__current_user_avatar').html();
					if (a && a.length > 0) {
						a = a.replace(/200/g, '64');
						a = a.replace(/196/g, '64');
						html += a;
					} else {
						// Problem retrieving user avatar - is there a wp_footer action to run __wps__lastactivity() ?
					}
					html += "</div>";
					html += "<div style='padding-left: 85px;'>";
					html += "<div class='child-reply-post'>";
					if (__wps__.wps_wysiwyg == 'on') {
						html += reply_text;
					} else {
						html += reply_text.replace(/\</g, "&lt;").replace(/\>/g, "&gt;").replace(/(<([^>]+)>)/ig, '').replace(/\n/g, "<br />");
					}
					html += "</div>";
					html += "<br class='clear' />";
					html += "</div>";
					if (jQuery('#__wps__file_upload_iframe').length) {
						var bfu = jQuery("#__wps__file_upload_iframe").contents().find('#forum_file_list').html();
						if (typeof bfu != 'undefined') {
							html += bfu;
							jQuery('#__wps__file_upload_iframe').remove();
						}
					}
					if (jQuery('#forum_file_list').length) {
						html += jQuery('#forum_file_list').html().replace(/<.*?>/g, '');
					}
					html += "</div>";
					html += "<div class='sep'></div>";
					jQuery(html).appendTo('#child-posts');
					if (__wps__.wps_wysiwyg != 'on') {
						jQuery('#__wps__reply_text').val('');
					} else {
						if (!__wps__.use_wp_editor) {
							tinyMCE.get('__wps__reply_text').setContent('');
						} else {
							tinyMCE.activeEditor.setContent('');
						}
					}
					jQuery('#forum_file_list').html('');

				}

				// Default to answered?
				var answered = '';
				if (jQuery('#quick-reply-answer').is(":checked")) {
					var answered = 'on';
				}

				if (__wps__.wps_forum_refresh == 'on') {
					jQuery(".__wps__pleasewait").inmiddle().show();
				}

				// Process the reply
				jQuery.ajax({
					url: __wps__.plugin_url + "ajax/forum_functions.php",
					type: "POST",
					data: ({
						action: "reply",
						'tid': jQuery('#__wps__reply_tid').val(),
						'cid': jQuery('#__wps__reply_cid').val(),
						'reply_text': reply_text,
						'group_id': __wps__.current_group,
						'answered': answered
					}),
					dataType: "html",
					async: true,
					success: function(str) {

						// Stored in database, so tell members via email
						if (__wps__.debug == 'on') {
							alert('STR: ' + str);
						}

						jQuery.ajax({
							url: __wps__.plugin_url + "ajax/forum_functions.php",
							type: "POST",
							data: ({
								action: "forumReplyEmails",
								'tid': str
							}),
							async: true,
							success: function(str) {
								if (__wps__.debug) {
									jQuery("#dialog").html(str).dialog({
										title: __wps__.site_title + ' debug info (send replies)',
										width: 800,
										height: 500,
										modal: true
									});
								}

								// Reload page, if applicable
								if (__wps__.wps_forum_refresh == 'on' && __wps__.debug != 'on') {
									location.reload();
								}

							},
							error: function(xhr, ajaxOptions, thrownError) {
								if (show_js_errors) {
									alert(xhr.status);
									alert(xhr.statusText);
									alert(thrownError);
								}
							}
						});


					},
					error: function(xhr, ajaxOptions, thrownError) {
						if (__wps__.debug) {
							alert(xhr.status);
							alert(xhr.statusText);
							alert(thrownError);
						}
					}

				});

			}
		
		}

	});


	// Show delete links on hover
	jQuery('.row').live('mouseenter mouseleave', function(event) {
		if (event.type == 'mouseenter') {
			jQuery(this).find(".delete_topic").show()
		} else {
			jQuery(this).find(".delete_topic").hide();
		}
	});
	jQuery('.row_odd').live('mouseenter mouseleave', function(event) {
		if (event.type == 'mouseenter') {
			jQuery(this).find(".delete_topic").show()
		} else {
			jQuery(this).find(".delete_topic").hide();
		}
	});
	jQuery('.child-reply').live('mouseenter mouseleave', function(event) {
		if (event.type == 'mouseenter') {
			jQuery(this).find(".symposium_report").show();
			jQuery(this).find(".delete_forum_reply").show();
			jQuery(this).find(".edit_forum_reply").show();
		} else {
			jQuery(this).find(".symposium_report").hide();
			jQuery(this).find(".delete_forum_reply").hide();
			jQuery(this).find(".edit_forum_reply").hide();
		}
	});

	// Delete reply
	jQuery('.delete_forum_reply').live('click', function(event) {

		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/forum_functions.php",
			type: "POST",
			data: ({
				action: "deleteReply",
				topic_id: jQuery(this).attr("id")
			}),
			dataType: "html",
			async: true,
			success: function(str) {
				if (jQuery('#reply' + str).length) {
					jQuery('#reply' + str).slideUp("slow");
				}
				if (jQuery('#comment' + str).length) {
					jQuery('#comment' + str).slideUp("slow");
				}
			}
		});

	});

	// Delete topic
	jQuery(".delete_topic").live('click', function() {

		if (confirm(areyousure)) {

			var topic_id = jQuery(this).attr("id");
			jQuery('#row'+topic_id).slideUp("slow");

			jQuery.ajax({
				url: __wps__.plugin_url + "ajax/forum_functions.php",
				type: "POST",
				data: ({
					action: "deleteTopic",
					topic_id: topic_id
				}),
				dataType: "html",
				async: false,
				success: function(str) {
				}
			});

		}

	});


	// BB Code toolbar
	__wps__evoke_bbcode_toolbar();

	// Show new topic and reply topic forms
	jQuery("#new-topic-button").live('click', function() {
		jQuery("#new-topic").show();
		jQuery("#forum_options").hide();
		jQuery("#share_link").hide();
		jQuery(".__wps__forum_table").hide();
		jQuery(".__wps__subscribe_option").hide();
		jQuery("#new-topic-button").hide();
		jQuery("#forum_breadcrumbs").hide();
		jQuery("#__wps__forum_dropdown").hide();
		// BB Toolbar
		__wps__evoke_bbcode_toolbar();
	});
	jQuery("#cancel_post").live('click', function() {
		jQuery("#new-topic").hide();
		jQuery("#forum_options").show();
		jQuery("#share_link").show();
		jQuery(".__wps__forum_table").show();
		jQuery(".__wps__subscribe_option").show();
		jQuery("#new-topic-button").show();
		jQuery("#forum_breadcrumbs").show();
		jQuery("#__wps__forum_dropdown").show();
	});

	// Post a new topic
	jQuery("#symposium_new_post").live('click', function() {

		var subject = jQuery('#new_topic_subject').val();
		var text = jQuery('#new_topic_text').val();
		if (__wps__.wps_wysiwyg == 'on') {
			var text = tinyMCE.get('new_topic_text').getContent();
		}

		var category = 0;
		if (jQuery("#new_topic_category").length) {
			category = jQuery('#new_topic_category').val();
		}

		if (subject == '') {
			jQuery("#new_topic_subject").css('border', '1px solid red').effect("highlight", {}, 4000);
		} else {

			if (text == '') {
				if (__wps__.wps_wysiwyg != 'on') {
					jQuery("#new_topic_text").css('border', '1px solid red').effect("highlight", {}, 4000);
				}
			} else {

				// Check for unclosed BB Codes
				var bbcodes_ok = __wps__bbcodes_ok(text);
				if (!bbcodes_ok) {
					alert(__wps__.bbcode_problem);
				} else {

					jQuery(".__wps__pleasewait").inmiddle().show();

					var subscribed = '';
					if (jQuery('#new_topic_subscribe').is(":checked")) {
						var subscribed = 'on'
					}
					var info_only = '';
					if (jQuery('#info_only').is(":checked")) {
						var info_only = 'on'
					}

					jQuery.ajax({
						url: __wps__.plugin_url + "ajax/forum_functions.php",
						type: "POST",
						data: ({
							action: "forumNewPost",
							'subject': subject,
							'text': text,
							'category': category,
							'subscribed': subscribed,
							'info_only': info_only,
							'group_id': __wps__.current_group
						}),
						async: false,
						success: function(str) {

							var details = str.split("[|]");
							var new_tid = details[0];
							var url = details[1];
							
							// Stored in database, so tell members via email
							jQuery.ajax({
								url: __wps__.plugin_url + "ajax/forum_functions.php",
								type: "POST",
								data: ({
									action: "forumNewPostEmails",
									'new_tid': new_tid,
									'cat_id': category,
									'group_id': __wps__.current_group
								}),
								async: true,
								success: function(str) {
									if (__wps__.debug) {
										jQuery("#dialog").html(str).dialog({
											title: __wps__.site_title + ' debug info',
											width: 800,
											height: 500,
											modal: true
										});
									}
								},
								error: function(xhr, ajaxOptions, thrownError) {
									if (show_js_errors) {
										alert(xhr.status);
										alert(xhr.statusText);
										alert(thrownError);
									}
								}
							});

							// Redirect, no need to wait for email's to be sent out, can happen in background
							// But add a little pause to make sure script above has been initiated
							if (!__wps__.debug) {
								setTimeout(function() { window.location.href = url; },3000);
							}
						},
						error: function(xhr, ajaxOptions, thrownError) {
							if (show_js_errors) {
								alert(xhr.status);
								alert(xhr.statusText);
								alert(thrownError);
							}
						}
					});
				}
			}
		}
	});


	// Has a checkbox been clicked? If so, check if one for symposium (AJAX)
	jQuery("input[type='checkbox']").live('click', function() {

		var checkbox = jQuery(this).attr("id");

		// Toggle for info only
		if (checkbox == "symposium_for_info") {
			var value = '';
			if (jQuery(this).is(":checked")) {
				value = 'on';
				jQuery(".forum_post_answer").hide();
			} else {
				jQuery(".forum_post_answer").show();
			}
			jQuery.ajax({
				url: __wps__.plugin_url + "ajax/forum_functions.php",
				type: "POST",
				data: ({
					action: "toggleForInfo",
					tid: jQuery(this).attr("title"),
					value: value
				}),
				dataType: "html",
				async: true
			});
		};

		// Subscribe to New Forum Topics in a category
		if (checkbox == "symposium_subscribe") {
			jQuery(".__wps__notice").inmiddle().fadeIn();
			if (jQuery(this).is(":checked")) {

				jQuery.ajax({
					url: __wps__.plugin_url + "ajax/forum_functions.php",
					type: "POST",
					data: ({
						action: "updateForumSubscribe",
						'cid': jQuery(this).attr("title"),
						"value": 1
					}),
					error: function(xhr, ajaxOptions, thrownError) {
						if (show_js_errors) {
							alert(xhr.status);
							alert(xhr.statusText);
							alert(thrownError);
						}
					}
				});

			} else {

				jQuery.ajax({
					url: __wps__.plugin_url + "ajax/forum_functions.php",
					type: "POST",
					data: ({
						action: "updateForumSubscribe",
						'cid': jQuery(this).attr("title"),
						"value": 0
					}),
					error: function(xhr, ajaxOptions, thrownError) {
						if (show_js_errors) {
							alert(xhr.status);
							alert(xhr.statusText);
							alert(thrownError);
						}
					}
				});

			}
			jQuery(".__wps__notice").delay(100).fadeOut("slow");
		}

		// Subscribe to Topic Posts
		if (checkbox == "subscribe") {
			jQuery(".__wps__notice").inmiddle().fadeIn();
			if (jQuery(this).is(":checked")) {

				jQuery.ajax({
					url: __wps__.plugin_url + "ajax/forum_functions.php",
					type: "POST",
					data: ({
						action: "updateForum",
						'tid': jQuery(this).attr("title"),
						'value': 1
					}),
					error: function(xhr, ajaxOptions, thrownError) {
						if (show_js_errors) {
							alert(xhr.status);
							alert(xhr.statusText);
							alert(thrownError);
						}
					}
				});

			} else {

				jQuery.ajax({
					url: __wps__.plugin_url + "ajax/forum_functions.php",
					type: "POST",
					data: ({
						action: "updateForum",
						'tid': jQuery(this).attr("title"),
						'value': 0
					}),
					error: function(xhr, ajaxOptions, thrownError) {
						if (show_js_errors) {
							alert(xhr.status);
							alert(xhr.statusText);
							alert(thrownError);
						}
					}
				});

			}
			jQuery(".__wps__notice").delay(100).fadeOut("slow");
		}

		// Sticky Topics
		if (checkbox == "sticky") {
			jQuery(".__wps__notice").inmiddle().fadeIn();
			if (jQuery(this).is(":checked")) {
				jQuery.ajax({
					url: __wps__.plugin_url + "ajax/forum_functions.php",
					type: "POST",
					data: ({
						action: "updateForumSticky",
						'tid': jQuery(this).attr("title"),
						'value': 1
					}),
					error: function(xhr, ajaxOptions, thrownError) {
						if (show_js_errors) {
							alert(xhr.status);
							alert(xhr.statusText);
							alert(thrownError);
						}
					}
				});

			} else {

				jQuery.ajax({
					url: __wps__.plugin_url + "ajax/forum_functions.php",
					type: "POST",
					data: ({
						action: "updateForumSticky",
						'tid': jQuery(this).attr("title"),
						'value': 0
					}),
					error: function(xhr, ajaxOptions, thrownError) {
						if (show_js_errors) {
							alert(xhr.status);
							alert(xhr.statusText);
							alert(thrownError);
						}
					}
				});

			}
			jQuery(".__wps__notice").delay(100).fadeOut("slow");
		}

		// Digest
		if (checkbox == "symposium_digest") {
			jQuery(".__wps__notice").inmiddle().fadeIn();
			if (jQuery(this).is(":checked")) {

				jQuery.ajax({
					url: __wps__.plugin_url + "ajax/forum_functions.php",
					type: "POST",
					data: ({
						action: "updateDigest",
						'value': 'on'
					}),
					error: function(xhr, ajaxOptions, thrownError) {
						if (show_js_errors) {
							alert(xhr.status);
							alert(xhr.statusText);
							alert(thrownError);
						}
					}
				});

			} else {

				jQuery.ajax({
					url: __wps__.plugin_url + "ajax/forum_functions.php",
					type: "POST",
					data: ({
						action: "updateDigest",
						'value': ''
					}),
					error: function(xhr, ajaxOptions, thrownError) {
						if (show_js_errors) {
							alert(xhr.status);
							alert(xhr.statusText);
							alert(thrownError);
						}
					}
				});
			}
			jQuery(".__wps__notice").delay(100).fadeOut("slow");
		}

		// Replies
		if (checkbox == "replies") {
			jQuery(".__wps__notice").inmiddle().fadeIn();
			if (jQuery(this).is(":checked")) {

				jQuery.ajax({
					url: __wps__.plugin_url + "ajax/forum_functions.php",
					type: "POST",
					data: ({
						action: "updateTopicReplies",
						'tid': jQuery(this).attr("title"),
						'value': 'on'
					}),
					error: function(xhr, ajaxOptions, thrownError) {
						if (show_js_errors) {
							alert(xhr.status);
							alert(xhr.statusText);
							alert(thrownError);
						}
					}
				});

			} else {

				jQuery.ajax({
					url: __wps__.plugin_url + "ajax/forum_functions.php",
					type: "POST",
					data: ({
						action: "updateTopicReplies",
						'tid': jQuery(this).attr("title"),
						'value': ''
					}),
					error: function(xhr, ajaxOptions, thrownError) {
						if (show_js_errors) {
							alert(xhr.status);
							alert(xhr.statusText);
							alert(thrownError);
						}
					}
				});

			}
			jQuery(".__wps__notice").delay(100).fadeOut("slow");
		}

	});

	// Score
	jQuery(".forum_post_score_change").live('click', function() {

		if (__wps__.current_user_id == 0) {
			alert('Please log in to register your vote.');
		} else {

			var change = jQuery(this).attr("title");
			var tid = jQuery(this).attr("id");
			var score = parseFloat(jQuery('#forum_score_' + tid).html());

			if (change == 'novote') {

				jQuery("#dialog").html(jQuery("#symposium_novote").html());
				jQuery("#dialog").dialog({
					title: jQuery('#symposium_novote_dialog').html(),
					width: 600,
					height: 175,
					modal: true,
					buttons: {
						"OK": function() {
							jQuery("#dialog").dialog('close');
						}
					}
				});

			} else {

				if (change == 'plus') {
					var change = 1;
				} else {
					var change = -1;
				}

				var vote_off = parseFloat(jQuery('#symposium_forum_vote_remove').html());
				var new_score = score + change;

				if (vote_off != 0 && vote_off == new_score) {
					jQuery('#reply' + tid).html(jQuery('#symposium_forum_vote_remove_msg').html())
				}

				jQuery('#forum_score_' + tid).html("<img src='" + __wps__.images_url + "/busy.gif' />");

				jQuery.ajax({
					url: __wps__.plugin_url + "ajax/forum_functions.php",
					type: "POST",
					data: ({
						action: "updateTopicScore",
						'tid': tid,
						'change': change
					}),
					success: function(str) {
						var rows = jQuery.parseJSON(str);
						jQuery.each(rows, function(i, row) {

							var score = row.score;
							if (score > 0) {
								score = '+' + score;
							}
							jQuery('#forum_score_' + tid).html(score);
							if (row.str != 'OK') {
								jQuery("#dialog").html(row.str);
								jQuery("#dialog").dialog({
									title: jQuery('#symposium_novote_dialog').html(),
									width: 600,
									height: 225,
									modal: true,
									buttons: {
										"OK": function() {
											jQuery("#dialog").dialog('close');
										}
									}
								});
							}
						})
					},
					error: function(xhr, ajaxOptions, thrownError) {
						if (show_js_errors) {
							alert(xhr.status);
							alert(xhr.statusText);
							alert(thrownError);
						}
					}
				});

			}

		}

	});



/*
	   +------------------------------------------------------------------------------------------+
	   |                                          PANEL                                           |
	   +------------------------------------------------------------------------------------------+
	*/



	if (jQuery("#__wps__notification_bar").length) {

		// Quick check on polling frequency
		if (__wps__.panel_enabled) {
			
			// Chat ========================
			
			//MINIMIZE, MAXIMIZE, CLOSE CHAT WINDOW			
				
				//minimize function
			jQuery('.minimize_chatbox').live('click',function(){
				//remove chat,message area			
				jQuery(this).closest('.chatbox').find('.chat_area,.chat_message').css('height','0px');		
				jQuery(this).closest('.chatbox').css('height','25px');
				
				//replace minimize icon
				jQuery(this).css('display','none');
				jQuery(this).closest('.chatbox').find('.maximize_chatbox').css('display','inline');
				jQuery(this).closest('.chatbox').data('chatbox_status',2);
				UpdateChatWindowStatus();
				return false;
			});
			
			
			//maximize function
			jQuery('.maximize_chatbox').live('click',function(){
				//remove chat,message area	
				jQuery(this).closest('.chatbox').find('.chat_area').css('height','200px');		
				jQuery(this).closest('.chatbox').find('.chat_message').css('height','55px');		
				jQuery(this).closest('.chatbox').css('height','300px');
				jQuery(this).closest('.chatbox').find('.header_bg_blink').removeClass("header_bg_blink").addClass("header_bg_default");
				
				//replace minimize icon
				jQuery(this).css('display','none');
				jQuery(this).closest('.chatbox').find('.minimize_chatbox').css('display','inline');
				jQuery(this).closest('.chatbox').find('.header .new_message').remove();
				jQuery(this).closest('.chatbox').find('.chat_message textarea').focus();
				jQuery(this).closest('.chatbox').data('chatbox_status',1);
				UpdateChatWindowStatus();
				return false;
			});
			
			//clear function
			jQuery('.clear_chatbox').live('click',function(){
				jQuery(this).closest('.chatbox').find('.chat_area').html('<p class="system">'+__wps__.pleasewait+'...</p>');
				var to_id = jQuery(this).closest('.chatbox').attr('title');
				var datastring = 'from_id='+__wps__.current_user_id+'&to_id='+to_id;
				jQuery.ajax({
					type: "POST",
					url: __wps__.plugin_url+'ajax/chat/clear_chat.php',	
					async: true,					
					data: datastring,
					success: function(i){			
						if (i != '1')
							alert(i);
					}
				});
			});
			
			//popup function
			jQuery('.popup_chatbox').live('click',function(){
				var url = __wps__.plugin_url+'ajax/chat/popup.html?heartBeat='+heartBeat+'&id='+__wps__.current_user_id+'&partner='+jQuery(this).attr('title')+'&partner_id='+jQuery(this).attr('rel')+'&url='+__wps__.plugin_url+'&chat_sound='+__wps__.chat_sound;
		        var windowName = "popUp"+Math.floor(Math.random()*1000);
		        var windowSizeArray = [ "width=216,height=280","width=216,height=280,scrollbars=no" ]
		        var w = window.open(url, windowName, windowSizeArray);
		        event.preventDefault();		    		
		    });

			//close function
			jQuery('.close_chatbox').live('click',function(){
				var closed_pos = parseInt(jQuery(this).closest('.chatbox').css('right'));
				chatboxcount --;
				jQuery(this).closest('.chatbox').remove();		
				//set nu position for all appearing chat window
				jQuery('.chatbox').each(function(){
					var prev_pos = parseInt(jQuery(this).css('right'));
					if(prev_pos != 10 && (prev_pos > closed_pos )){
						var nu_pos = prev_pos - 210;
						jQuery(this).css('right',nu_pos+'px');
					}
				});

				UpdateChatWindowStatus();
				return false;
			});		
			
			//ON USER CLICK POP UP CHAT
		
			jQuery('.__wps__chat_user').live('click',function(){
				var substr = jQuery(this).attr('alt').split('|');
				var user_id = substr[0];
				var user_name = substr[1];
				
				//check if a windows is already open with this user first!
				if(jQuery('div[title="'+user_id+'"]').length > 0){					
					//alert('You\'re already chatting with him/her!');
				}else{
					PopupChat(user_id,user_name,1,1);
				}
					
			});
			
			// set focus in Message area
			jQuery('.chatbox').live('click',function(){
				jQuerytextarea = jQuery('.chat_message textarea',this);		
				jQuerytextarea.focus();
			});
			
		
			
			//HIGHLIGHT Active chat window
			jQuery('.chat_message textarea').live('focus',function() {
				var chatbox = jQuery(this).closest('.chatbox');
				this_chatbox_headerbg = jQuery('.header',chatbox);
				this_chatbox_headerbg.removeClass("header_bg_blink").addClass("header_bg_default");
				chatbox.removeClass("cb_default").addClass("cb_highlight"); // add highligt to chat window
				chatbox.data('focused',1);		   // enable focus variable
				chatbox.data('havenewmessage',0); // clear new message
				chatbox.data('playedsound',1); // clear new message
			});
			jQuery('.chat_message textarea').live('blur',function() {
				var chatbox = jQuery(this).closest('.chatbox');
				chatbox.removeClass("cb_highlight").addClass("cb_default"); // remove highligt of chat window
				chatbox.data('focused',0);	// disable focus variable
			});
			
			//SEND MESSAGE ON ENTER		
			jQuery('.chat_message textarea').live('keypress', function (e) {

				if (e.keyCode == 13 && !e.shiftKey) {
					e.preventDefault();
					
					//add to MySQL DB with AJAX and PHP
					var to_id = jQuery(this).closest('.chatbox').attr('title');
					var this_chat_window_id = jQuery(this).closest('.chatbox').attr('id');
					var this_textarea = jQuery(this);
					var datastring = 'from_id='+__wps__.current_user_id+'&to_id='+to_id+'&message='+this_textarea.val();
					
					if (this_textarea.val() != __wps__.sending+'... '+__wps__.pleasewait+'  ') {

						if (this_textarea.val() != '') {
					
							// show sending message
							this_textarea.val(__wps__.sending+'... '+__wps__.pleasewait+'  ').attr('disabled', 'disabled');
		
							jQuery.ajax({
								type: "POST",
								url: __wps__.plugin_url+'ajax/chat/send_message.php',	
								async: true,					
								data: datastring,
								success: function(i){			
									if(i == 1){
										//if success, reload chat area
										this_textarea.val('').removeAttr('disabled');							
									}else{					
										//if error,  print it into chat
										print_to_chat(this_chat_window_id,'<p><span class="error">Error! Message not sent!</span></p>');
										//uncomment to print mysql error to chat
										print_to_chat(i);								
									}
								}
							});
							
						}
						
					}
					
				}else{
				}				
			});
			
			//LOOP OF LIFE - checks every ... seconds if there's a new message
			function liveChat(__wps__first_load){
				//go through all popped up window and reload messages, mark those messages as received			
				jQuery('.chatbox').each(function(){												
					var this_chatbox = jQuery(this);
					var this_chatbox_chat_area = jQuery('.chat_area',this);		
					var this_chatbox_headerbg = jQuery('.header',this);				
					var this_chatbox_header = jQuery('.header p',this);				
					var this_chatbox_max_btn  = jQuery('.header .maximize_chatbox',this);				
					var this_chatbox_id = jQuery(this).attr('title');
					var this_newmessage = jQuery('.header p .new_message',this);				
					//v1.2 -----------------------------------------------------
					
					var this_chatbox_textarea  = jQuery('.chat_message textarea',this);			

					// typing?
					if (this_chatbox_textarea.val() != '') {
						is_typing = 1;
					} else {
						is_typing = 0;
					}

					//v1.2 -----------------------------------------------------								
					jQuery.ajax({
						type: "POST",
						url: __wps__.plugin_url+'ajax/chat/load_message.php',								
						data: 'own_id='+__wps__.current_user_id+'&partner_id='+this_chatbox_id+'&typing='+is_typing,
						async: true,					
						success: function(i){	

							//reload messages in chat area
							if(i != 0){			

								// is typing?		
								if (i.indexOf('#(') >= 0) {
									var start_pos = i.indexOf('#(') + 2;
									var end_pos = i.indexOf(')#',start_pos);
									var is_typing = i.substring(start_pos,end_pos);
									if (is_typing == 0) {
										this_chatbox_textarea.parent().find(".chat_user_replying").hide();
									} else {
										this_chatbox_textarea.parent().find(".chat_user_replying").show();
									}
								}
															
								// id of last message			
								var current_chat = this_chatbox_chat_area.html();
								var old_id = false;
								if (current_chat.indexOf('#[') >= 0) {
									var start_pos = current_chat.indexOf('#[') + 2;
									var end_pos = current_chat.indexOf(']#',start_pos);
									old_id = current_chat.substring(start_pos,end_pos);
								}
															
								var new_chat = i.replace(/\\\'/g,'\'').replace(/\\\"/g,'\"');								
								start_pos = new_chat.indexOf('#[') + 2;
								end_pos = new_chat.indexOf(']#',start_pos);
								var new_id = new_chat.substring(start_pos,end_pos);
								
								// who sent last message?

								if (new_chat.indexOf('#{') >= 0) {
									start_pos = new_chat.indexOf('#{') + 2;
									end_pos = new_chat.indexOf('}#',start_pos);
									var last_id = new_chat.substring(start_pos,end_pos);
								}

								if (old_id) {
									
									if (old_id != new_id){

										// show chat
										this_chatbox_textarea.parent().find(".chat_user_replying").hide();
																												
										var chat_output = i;
										chat_output = i.replace(/\\\'/g,'\'').replace(/\\\"/g,'\"');
										chat_output = chat_output.replace(/#\(/g,'<div style="clear:both;height:2px;width:2px;display:none;">#(').replace(/\)#/g,')#</div>');
										chat_output = chat_output.replace(/#\[/g,'<div style="clear:both;height:2px;width:2px;display:none;">#[').replace(/\]#/g,']#</div>');
										chat_output = chat_output.replace(/#\{/g,'<div style="clear:both;height:2px;width:2px;display:none;">#{').replace(/\}#/g,'}#</div>');
										this_chatbox_chat_area.html(chat_output);	
										
										// scroll to bottom
										this_chatbox_chat_area.animate({scrollTop: 9999999},200);
							
										if (last_id != __wps__.current_user_id) {

											this_chatbox.data('havenewmessage',1);
											this_chatbox.data('playedsound', 0);
											
											// alert user?
											if (this_chatbox.data('focused') != 1 && this_chatbox.data('havenewmessage') == 1) { // blinking chat window if not focused and have a new message
												this_chatbox_headerbg.removeClass("header_bg_default").addClass("header_bg_blink");
											}					
											if (this_chatbox.data('havenewmessage') == 1) { // blinking chat window if not focused and have a new message
												if (__wps__.chat_sound != 'none' && this_chatbox.data('playedsound') == 0) { // sound
													this_chatbox.data('playedsound', 1);
													jQuery("#player_div").empty();
													jQuery("#player_div").prepend(__wps__insertPlayer(__wps__.plugin_url+'ajax/chat/flash/player.swf', __wps__.plugin_url+'/ajax/chat/flash/'+__wps__.chat_sound));
												}
											}	
	
										}					
									
									}
									
								} else {

										// show chat
																		
										var chat_output = i;
										chat_output = i.replace(/\\\'/g,'\'').replace(/\\\"/g,'\"');
										chat_output = chat_output.replace(/#\(/g,'<div style="clear:both;height:2px;width:2px;display:none;">#(').replace(/\)#/g,')#</div>');
										chat_output = chat_output.replace(/#\[/g,'<div style="clear:both;display:none;">#[').replace(/\]#/g,']#</div>');
										chat_output = chat_output.replace(/#\{/g,'<div style="clear:both;display:none;">#{').replace(/\}#/g,'}#</div>');
										this_chatbox_chat_area.html(chat_output);	

										// scroll to bottom
										this_chatbox_chat_area.animate({scrollTop: 9999999},200);

								}									
								
							}else{
								this_chatbox_chat_area.html('');
							}
						}
					});	
					
				});
				
				
				//check if current user has a new message (to_id = current_user_id) which haven't been received and there's no popup with that user
				jQuery.ajax({
					type: "POST",
					url: __wps__.plugin_url+'ajax/chat/load_popup_message.php',	
					async: true,					
					data: 'own_id='+__wps__.current_user_id,
					success: function(o){	
						if(o != 0){
							//there's a new message, so just open up a new chat window and message will be loaded automatically
							var substr = o.split(';;;');		
	
							//check if a windows is already open with this user first!
							if(jQuery('div[title="'+substr[0]+'"]').length == 0){					
								PopupChat(substr[0],substr[1],1,__wps__first_load);
							}												
							
						}
					}
				});
				
				__wps__first_load = 0;
				
				//and start the loop again
				
				t=setTimeout(function() {
				    liveChat(__wps__first_load);
				}, heartBeat);
			}
			
			var __wps__first_load = 1;					
			liveChat(__wps__first_load);	//start the chat
			
			// Set up icon actions ******************************************************
			
			// Hover/click on logout?
			jQuery("#__wps__logout").mouseenter(function() {
				jQuery("#__wps__logout_div").show();
			});
			jQuery("#__wps__logout_div").mouseleave(function() {
				jQuery("#__wps__logout_div").fadeOut('slow');
			});

			// Click on change online status?
			jQuery("#__wps__online_status").click(function() {
				var status = jQuery("#__wps__online_status").is(":checked");
				jQuery.ajax({
					url: __wps__.plugin_url + "ajax/bar_functions.php",
					type: "POST",
					data: ({
						action: 'symposium_status',
						status: status
					}),
					dataType: "html",
					async: false
				});
			});

			// Click on logout link
			jQuery("#__wps__logout-link").click(function() {
				if (confirm(areyousure)) {
					jQuery.ajax({
						url: __wps__.plugin_url + "ajax/ajax_functions.php",
						type: "POST",
						data: ({
							action: 'symposium_logout'
						}),
						dataType: "html",
						async: false,
						success: function(str) {
							window.location.href = __wps__.site_url;
						}
					});
				} else {
					jQuery("#__wps__logout_div").hide();
				}
			});

			// Email icon
			if (jQuery("#__wps__email_box").css("display") != "none") {
				jQuery("#__wps__email_box").click(function() {
					window.location.href = __wps__.mail_url;
				});

			}

			// Icon Actions
			if (jQuery("#__wps__friends_box").css("display") != "none") {
				jQuery("#__wps__friends_box").click(function() {
					var q = symposium_q(__wps__.profile_url);
					window.location.href = __wps__.profile_url + q + 'view=friends';
				});
				jQuery("#__wps__online_box").click(function() {
					jQuery('#__wps__who_online').show();
				});
				jQuery("#__wps__who_online_close").click(function() {
					jQuery('#__wps__who_online').hide();
				});
			}

			// Scheduled checks for unread mail ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
			if (__wps__.current_user_id > 0) {

				// Clear locking cookies
				eraseCookie('wps_bar_check');

				// Check for notifications, unread mail, friend requests, etc
				bar_polling();
			}
			
		}
	}


	function do_online_friends_check() {
		

		// Friends Online ******************************************************
		if (jQuery("#__wps__friends_box").css("display") != "none") {

			jQuery.ajax({
				url: __wps__.plugin_url + "ajax/bar_functions.php",
				type: "POST",
				data: ({
					action: "symposium_friendrequests"
				}),
				dataType: "html",
				async: true,
				success: function(str) {
					if (str > 0) {
						jQuery("#__wps__friends_box").html(str);
						jQuery("#__wps__friends_box").removeClass("__wps__friends_box-none");
						jQuery("#__wps__friends_box").addClass("__wps__friends_box-new");
					}
				},
				error: function(xhr, ajaxOptions, thrownError) {
					if (show_js_errors) {
						alert(xhr.status);
						alert(xhr.statusText);
						alert(thrownError);
					}
				}

			});

					
			jQuery.ajax({
				url: __wps__.plugin_url + "ajax/bar_functions.php",
				type: "POST",
				data: ({
					action: "symposium_getfriendsonline",
					uid: __wps__.current_user_id,
					inactive: __wps__.inactive,
					offline: __wps__.offline,
					use_chat: __wps__.use_chat
				}),
				dataType: "html",
				async: true,
				success: function(str) {
					if (str != '') {
						var split = str.split("[split]");
						jQuery("#__wps__online_box").html(split[0]);
						jQuery("#__wps__friends_online_list").html(split[1]);
						if (split[0] > 0) {
							jQuery("#__wps__online_box").removeClass("__wps__online_box-none");
							jQuery("#__wps__online_box").addClass("__wps__online_box");
						} else {
							jQuery("#__wps__online_box").removeClass("__wps__online_box");
							jQuery("#__wps__online_box").addClass("__wps__online_box-none");
						}
					}
				},
				error: function(xhr, ajaxOptions, thrownError) {
					if (show_js_errors) {
						alert(xhr.status);
						alert(xhr.statusText);
						alert(thrownError);
					}
				}

			});

		}
	}

	function do_bar_check() {

		var bar_check_cookie = readCookie('wps_bar_check');

		if (bar_check_cookie == 'lock') {
			// Still processing previous
			//alert('BAR LOCKED');
		} else {
			// Set cookie (to avoid over-lapping checks)
			createCookie('wps_bar_check', 'lock', 1);
			//alert('CREATE BAR LOCK');
			// Email ******************************************************
			if (jQuery("#__wps__email_box").css("display") != "none") {

				jQuery.ajax({
					url: __wps__.plugin_url + "ajax/bar_functions.php",
					type: "POST",
					data: ({
						action: "symposium_getunreadmail"
					}),
					dataType: "html",
					async: true,
					success: function(str) {
						if (str > 0) {
							jQuery("#__wps__email_box").html(str);
							jQuery("#__wps__email_box").removeClass("__wps__email_box-read");
							jQuery("#__wps__email_box").addClass("__wps__email_box-unread");
						}
						eraseCookie('wps_bar_check');
						//alert('CLEAR BAR LOCK');
					},
					error: function(xhr, ajaxOptions, thrownError) {
						if (show_js_errors) {
							alert(xhr.status);
							alert(xhr.statusText);
							alert(thrownError);
						}
					}
				});

			}
		}
	}

	function removeHTMLTags(strInputCode) {
		strInputCode = strInputCode.replace(/&(lt|gt);/g, function(strMatch, p1) {
			return (p1 == "lt") ? "<" : ">";
		});
		var strTagStrippedText = strInputCode.replace(/<\/?[^>]+(>|$)/g, "");
		return strTagStrippedText;
	}

	// Cookies


	function createCookie(name, value, days) {
		if (days) {
			var date = new Date();
			date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
			var expires = "; expires=" + date.toGMTString();
		} else var expires = "";
		document.cookie = name + "=" + value + expires + "; path=/";
	}

	function readCookie(name) {
		var nameEQ = name + "=";
		var ca = document.cookie.split(';');
		for (var i = 0; i < ca.length; i++) {
			var c = ca[i];
			while (c.charAt(0) == ' ') c = c.substring(1, c.length);
			if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
		}
		return null;
	}

	function eraseCookie(name) {
		createCookie(name, "", -1);
	}


	// Form validations


	function validate_form(thisform) {
		form_id = thisform.id;

		// Login
		if ((form_id) == "symposium_login") {
			var r = true;
			with(thisform) {
				if (forgotten_email.value == '') {
					if (username.value == '' || username.value == null) {
						jQuery("#username-warning").show("slow");
						username.focus();
					} else {
						jQuery("#username-warning").hide("slow");
					}
					if (pwd.value == '' || pwd.value == null) {
						jQuery("#pwd-warning").show("slow");
						username.focus();
					} else {
						jQuery("#pwd-warning").hide("slow");
					}
				}
			}
			// return false to avoid submit, redirect handled in jQuery
			return false;
		}

		// Registration
		if ((form_id) == "symposium_registration") {
			var r = true;
			with(thisform) {
				if ((pwd.value != '' || pwd2.value != null) && (pwd.value != pwd2.value)) {
					jQuery("#password2-warning").show("slow");
					pwd.focus();
					r = false;
				} else {
					jQuery("#password2-warning").hide("slow");
				}
				if (pwd.value == '' || pwd.value == null) {
					jQuery("#password-warning").show("slow");
					pwd.focus();
					r = false;
				} else {
					jQuery("#password-warning").hide("slow");
				}
				if (youremail.value == '' || youremail.value == null) {
					jQuery("#youremail-warning").show("slow");
					youremail.focus();
					r = false;
				} else {
					jQuery("#youremail-warning").hide("slow");
				}
				var reg = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;
				if (reg.test(youremail.value) == false) {
					jQuery("#youremail-warning").show("slow");
					youremail.focus();
					r = false;
				} else {
					jQuery("#youremail-warning").hide("slow");
				}
				if (display_name.value == '' || display_name.value == null) {
					jQuery("#display_name-warning").show("slow");
					display_name.focus();
					r = false;
				} else {
					jQuery("#display_name-warning").hide("slow");
				}
				if (username.value == '' || username.value == null) {
					jQuery("#username-warning").show("slow");
					username.focus();
					r = false;
				} else {
					jQuery("#username-warning").hide("slow");
				}
			}
			return r;
		}

		// Forum	
		if ((form_id) == "start-new-topic") {
			with(thisform) {
				if (new_topic_subject.value == '' || new_topic_subject.value == null) {
					jQuery(".new-topic-subject-warning").show("slow");
					new_topic_subject.focus();
					return false;
				}
				if (new_topic_text.value == '' || new_topic_text.value == null) {
					jQuery(".new_topic_text-warning").show("slow");
					new_topic_text.focus();
					return false;
				}
			}
		}
		if ((form_id) == "start-reply-topic") {
			with(thisform) {
				if (reply_text.value == '' || reply_text.value == null) {
					jQuery(".reply_text-warning").show("slow");
					reply_text.focus();
					return false;
				}
			}
		}


	}

	function strpos(haystack, needle, offset) {
		var i = (haystack + '').indexOf(needle, (offset || 0));
		return i === -1 ? false : i;
	}

	function trim(s) {
		var l = 0;
		var r = s.length - 1;
		while (l < s.length && s[l] == ' ') {
			l++;
		}
		while (r > l && s[r] == ' ') {
			r -= 1;
		}
		return s.substring(l, r + 1);
	}

	function bar_polling() {
		do_bar_check();
		do_online_friends_check();
		if (__wps__.wps_lite != 'on') {
			setTimeout(bar_polling, __wps__.bar_polling * 1000);
		}
	}


/*
	   +------------------------------------------------------------------------------------------+
	   |                                        ALERTS                                            |
	   +------------------------------------------------------------------------------------------+
	*/

	if (jQuery("#__wps__alerts").length && __wps__.wps_alerts_activated == 1) {

		// Start regular checks for lounge contents
		if (__wps__.is_admin == 0) {
			news_polling();
		}

		// Show/hide news events as drop down below menu item
		jQuery('#__wps__alerts').live('mouseenter', function(event) {
			// Only show if list is present
			if (!(jQuery('#__wps__news_items').is(':visible'))) {
				if (event.type == 'mouseenter') {
					jQuery("#__wps__news_items").show();

					jQuery.ajax({
						url: __wps__.plugin_url + "ajax/news_functions.php",
						type: "POST",
						data: ({
							action: 'clear_read_news'
						}),
						dataType: "html",
						success: function(str) {},
						error: function(xhr, ajaxOptions, thrownError) {
							if (show_js_errors) {
								alert(xhr.status);
								alert(xhr.statusText);
								alert(thrownError);
							}
						}
					});
				}
			} else {
				jQuery("#__wps__news_items").hide();
				jQuery("#__wps__news_highlight").remove();
			}
		});
		jQuery('#__wps__news_items').live('mouseleave', function(event) {
			if (event.type == 'mouseleave') {
				jQuery("#__wps__news_items").hide();
				jQuery("#__wps__news_highlight").remove();
			}
		});

		// Clear all notifications
		jQuery('#symposium_clear_news').live('click', function(event) {
			jQuery(".__wps__news_item").remove();
			jQuery("#__wps__news_highlight").remove();
			jQuery.ajax({
				url: __wps__.plugin_url + "ajax/news_functions.php",
				type: "POST",
				data: ({
					action: 'delete_all_news'
				}),
				dataType: "html",
				success: function(str) {}
			});
		});

	}

	function news_polling() {

		// Only do if not admin and Alerts is ativated
		if (__wps__.is_admin == 0) {

			// Don't poll if showing drop-down list of items
			if (!(jQuery('#__wps__news_items').is(':visible'))) {

				var news_items = '';

				jQuery.ajax({
					url: __wps__.plugin_url + "ajax/news_functions.php",
					type: "POST",
					data: ({
						action: 'get_news'
					}),
					dataType: "html",
					success: function(str) {

						jQuery("#__wps__news_items").remove();
						if (str != '[]') {

							var rows = jQuery.parseJSON(str);

							var items = "<div id='__wps__news_items' style='display:none;'>";

							var row_count = 0;
							var new_count = 0;
							var url = '';

							jQuery.each(rows, function(i, row) {
								if (row.nid > 0) {
									if (row.new_item == 'on') {
										new_count++;
									}
									if (row_count < 10) {
										row_count++;
										items += "<div class='__wps__news_item'>";
										if (row.new_item == 'on') {
											items += "<span class='__wps__news_item_newitem'>";
										}
										items += stripslashes(row.news);
										if (row.new_item == 'on') {
											items += "</span>";
										}
										items += "</div>";
									}
								} else {
									url = row.news;
								}
							});

							if (url != '') {
								items += "<div class='__wps__news_item'>";
								items += '<a id="symposium_clear_news" style="float:right" href="javascript:void(0)">' + symposium_clear + '</a>';
								items += "<a style='float:left' href='" + url + "'>" + more + "</a>";
								items += "</div>";
							}

							items += '</div>';

							if (new_count > 0) {
								if (jQuery("#__wps__news_highlight").length > 0) {
									jQuery("#__wps__news_highlight").html(new_count);
								} else {
									jQuery('<span id="__wps__news_highlight">' + new_count + '</span>').appendTo('#__wps__alerts');
								}
							} else {
								jQuery("#__wps__news_highlight").hide();
							}
							jQuery('body').append(items);
							var __wps__news_y_offset = parseInt(jQuery("#__wps__news_y_offset").html());
							var __wps__news_x_offset = parseInt(jQuery("#__wps__news_x_offset").html());

							var h = parseInt(jQuery('#__wps__alerts').parent().css('height').replace(/px/g, ''));
							var o = jQuery('#__wps__alerts').parent().offset();
							var t = o.top+h;
							var l = o.left;
							jQuery("#__wps__news_items").css('top', t + __wps__news_y_offset);
							jQuery("#__wps__news_items").css('left', l + __wps__news_x_offset);
							
						}
					},
					error: function(xhr, ajaxOptions, thrownError) {
						if (show_js_errors) {
							alert(xhr.status);
							alert(xhr.statusText);
							alert(thrownError);
						}
					}
				});

			}

		}

		// Repeat check every 5 seconds
		if (__wps__.wps_lite != 'on') {
			var polling = jQuery('#__wps__news_polling').html();
			if (polling < 1) {
				polling = 5;
			}
			setTimeout(news_polling, polling * 1000);
		}

	}

	function stripslashes(str) {
		str = str.replace(/\\'/g, '\'');
		str = str.replace(/\\"/g, '"');
		str = str.replace(/\\0/g, '\0');
		str = str.replace(/\\\\/g, '\\');
		return str;
	}

/*
	   +------------------------------------------------------------------------------------------+
	   |                                        EVENTS                                            |
	   +------------------------------------------------------------------------------------------+
	*/



	// Act on default view on profile page being for Events
	if (__wps__.view == 'wps_events' && __wps__.embed == 'on') {

		jQuery('#profile_body').html("<img src='" + __wps__.images_url + "/busy.gif' />");

		var menu_id = 'menu_events';
		var ajax_path = __wps__.plugin_url + "ajax/events_functions.php";

		jQuery.ajax({
			url: ajax_path,
			type: "POST",
			data: ({
				action: menu_id,
				post: '',
				limit_from: 0,
				uid1: __wps__.current_user_page
			}),
			dataType: "html",
			async: true,
			success: function(str) {
				jQuery('#profile_body').hide().html(str).fadeIn("slow");
			}
		});
	}
	
	// Calendar view
	if (jQuery("#__wps__events_calendar").length) {
		var d = new Date();
		jQuery(".__wps__pleasewait").inmiddle().show();
		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/events_functions.php",
			type: "POST",
			data: ({
				action: "calendar_view",
				month: d.getMonth()+1,
				year: d.getFullYear()
			}),
			dataType: "html",
			async: true,
			success: function(str) {
				jQuery("#__wps__events_calendar").html(str);
				jQuery(".__wps__pleasewait").hide();
			}
		});		
	}
	jQuery("#__wps__event_move").live('click', function() {
		jQuery(".__wps__pleasewait").inmiddle().show();
		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/events_functions.php",
			type: "POST",
			data: ({
				action: "calendar_view",
				month: jQuery(this).data('month'),
				year: jQuery(this).data('year'),
			}),
			dataType: "html",
			async: true,
			success: function(str) {
				jQuery("#__wps__events_calendar").html(str);
				jQuery(".__wps__pleasewait").hide();
			}
		});		
	});
				
	// Payment	
	jQuery("#symposium_pay_event").live('click', function() {
		jQuery(".__wps__pleasewait").inmiddle().show();
		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/events_functions.php",
			type: "POST",
			data: ({
				action: "event_payment",
				'bid': jQuery(this).data('bid')
			}),
			dataType: "html",
			async: true,
			success: function(str) {
				jQuery(".__wps__pleasewait").hide();
				jQuery("#dialog").html(str).dialog({
					title: __wps__.site_title,
					width: 400,
					height: 300,
					modal: true,
					buttons: {
						"OK": function() {
							jQuery("#dialog").dialog('close');
						}
					}
				});
			}
		});
	});

	// Cancel
	jQuery("#symposium_cancel_event").live('click', function() {
		var answer = confirm(areyousure);
		if (answer) {			
			jQuery('.symposium_cancel_event_button').hide();
			jQuery('.symposium_pay_event_button').hide();
			jQuery(".__wps__pleasewait").inmiddle().show();
			jQuery.ajax({
				url: __wps__.plugin_url + "ajax/events_functions.php",
				type: "POST",
				data: ({
					action: "cancel_event",
					'eid': jQuery(this).data('eid')
				}),
				dataType: "html",
				async: true,
				success: function(str) {
					jQuery(".__wps__pleasewait").hide();
					jQuery("#dialog").html(str).dialog({
						title: __wps__.site_title,
						width: 400,
						height: 300,
						modal: true,
						buttons: {
							"OK": function() {
								jQuery("#dialog").dialog('close');
							}
						}
					});
				}
			});
		}
	});

	// Book
	jQuery("#symposium_book_event").live('click', function() {

		jQuery('.symposium_book_event_button').hide();

		// dialog from events.php
		var events_howmany = __wps__.events_howmany;

		jQuery(this).hide();
		var str = events_howmany;
		str += ' <select id="symposium_events_how_many">';
		for (i = 1; i <= jQuery(this).data('max'); i++) {
			str += '<option value=' + i + '>' + i;
		}
		str += '</select><br /><br />';
		str += '<input id="symposium_book_event_next" data-eid="' + jQuery(this).data('eid') + '" type="submit" class="__wps__button" value="' + symposium_next + ' &gt;" />';
		jQuery("#dialog").html(str).dialog({
			title: __wps__.site_title,
			width: 400,
			height: 300,
			modal: true
		});
	});
	jQuery("#symposium_book_event_next").live('click', function() {
		jQuery(".__wps__pleasewait").inmiddle().show();
		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/events_functions.php",
			type: "POST",
			data: ({
				action: "register_event",
				'eid': jQuery(this).data('eid'),
				'howmany': jQuery('#symposium_events_how_many').val()
			}),
			dataType: "html",
			async: true,
			success: function(str) {
				jQuery(".__wps__pleasewait").hide();
				jQuery("#dialog").html(str).dialog({
					title: __wps__.site_title,
					width: 400,
					height: 300,
					modal: true,
					buttons: {
						"OK": function() {
							jQuery("#dialog").dialog('close');
						}
					}
				});
			}
		});
	});

	/* Show/hide edit and delete icons */
	jQuery('.__wps__event_list_item').live('mouseenter mouseleave', function(event) {
		if (event.type == 'mouseenter') {
			jQuery(this).find(".link_cursor").show();
		} else {
			jQuery(this).find(".link_cursor").hide();
		}
	});

	/* Create button */
	jQuery("#__wps__create_event_button").live('click', function() {
		jQuery("#__wps__create_event_button").hide();
		jQuery("#__wps__events_list").hide();
		jQuery("#__wps__create_event_form").show();
		jQuery(".datepicker").datepicker({
			showButtonPanel: true
		});
	});

	/* Cancel button */
	jQuery("#symposium_cancel_event_button").live('click', function() {
		jQuery("#__wps__create_event_button").show();
		jQuery("#__wps__events_list").show();
		jQuery("#__wps__create_event_form").hide();
	});

	/* Create (save) button */
	jQuery("#symposium_add_event_button").live('click', function() {
		var name = jQuery("#__wps__create_event_name").val().replace(/(<([^>]+)>)/ig, '');
		if (name == '') {
			jQuery("#__wps__create_event_name").css('border', '1px solid red').effect("highlight", {}, 4000);
		} else {
			// submit to database
			var desc = jQuery("#__wps__create_event_desc").val().replace(/(<([^>]+)>)/ig, '');
			var location = jQuery("#__wps__create_event_location").val().replace(/(<([^>]+)>)/ig, '');
			var start_date = jQuery("#event_start").val();
			var end_date = jQuery("#event_end").val();
			var start_hours = jQuery("#event_start_time_hours").val();
			var start_minutes = jQuery("#event_start_time_minutes").val();
			var end_hours = jQuery("#event_end_time_hours").val();
			var end_minutes = jQuery("#event_end_time_minutes").val();

			var d1 = Date.parse(start_date);
			var d2 = Date.parse(end_date);
			if (d2 < d1) {

				jQuery("#event_start").css('border', '1px solid red').effect("highlight", {}, 4000);
				jQuery("#event_end").css('border', '1px solid red').effect("highlight", {}, 4000);

			} else {

				jQuery.ajax({
					url: __wps__.plugin_url + "ajax/events_functions.php",
					type: "POST",
					data: ({
						action: "addEvent",
						'name': name,
						'desc': desc,
						'location': location,
						'start_date': start_date,
						'start_hours': start_hours,
						'start_minutes': start_minutes,
						'end_date': end_date,
						'end_hours': end_hours,
						'end_minutes': end_minutes
					}),
					dataType: "html",
					async: true,
					success: function(str) {
						if (str == 'OK') {
							jQuery(".__wps__pleasewait").inmiddle().show();
							var q = symposium_q(__wps__.profile_url);
							var reload_page = __wps__.profile_url + q + "uid=" + __wps__.current_user_page + "&embed=on&view=wps_events";
							window.location.href = reload_page;
						} else {
							jQuery("#dialog").html(str).dialog({
								title: __wps__.site_title + ' debug info',
								width: 800,
								height: 500,
								modal: true
							});
						}
					},
					error: function(xhr, ajaxOptions, thrownError) {
						if (show_js_errors) {
							alert(xhr.status);
							alert(xhr.statusText);
							alert(thrownError);
						}
					}
				});
			}
		}
	});

	/* Delete event */
	jQuery(".symposium_delete_event").live('click', function() {

		var answer = confirm(areyousure);
		if (answer) {
			var event_id = jQuery(this).attr("id");
			jQuery(this).parent().parent().slideUp("slow");

			jQuery.ajax({
				url: __wps__.plugin_url + "ajax/events_functions.php",
				type: "POST",
				data: ({
					action: "deleteEvent",
					'eid': event_id
				}),
				dataType: "html",
				async: true,
				success: function(str) {
					if (str != 'OK') {
						jQuery("#dialog").html(str).dialog({
							title: __wps__.site_title + ' debug info',
							width: 800,
							height: 500,
							modal: true
						});
					}
				},
				error: function(xhr, ajaxOptions, thrownError) {
					if (show_js_errors) {
						alert(xhr.status);
						alert(xhr.statusText);
						alert(thrownError);
					}
				}
			});
		}
	});

	// Edit event
	jQuery(".__wps__edit_event").live('click', function() {

		// Get translated strings
		var events_max_places = __wps__.events_max_places;
		var events_enable_places = __wps__.events_enable_places;
		var events_show_max = __wps__.events_show_max;
		var events_confirmation = __wps__.events_confirmation;
		var events_tickets_per_booking = __wps__.events_tickets_per_booking;
		var events_tab_1 = __wps__.events_tab_1;
		var events_tab_2 = __wps__.events_tab_2;
		var events_tab_3 = __wps__.events_tab_3;
		var events_tab_4 = __wps__.events_tab_4;
		var events_send_email = __wps__.events_send_email;
		var events_pay_link = __wps__.events_pay_link;
		var events_cost = __wps__.events_cost;
		var events_replacements = __wps__.events_replacements;
		var events_labels = __wps__.events_labels.split('|');

		jQuery("#dialog").html("<img src='" + __wps__.images_url + "/busy.gif' />");

		var event_id = jQuery(this).attr("id");
		var event_owner = 'x';

		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/events_functions.php",
			type: "POST",
			data: ({
				action: "editEvent",
				eid: event_id
			}),
			dataType: "html",
			async: false,
			success: function(str) {

				var rows = jQuery.parseJSON(str);
				var html = '<div id="__wps__edit_event_eid" style="display:none;">' + event_id + '</div>';

				jQuery.each(rows, function(i, row) {

					html += '<div id="tabs_wrapper" style="width:100%; height:425px">';

					html += '<div id="tabs" style="border:0; overflow:auto;">';
					html += '<ul>';
					html += '<li>';
					html += '<a id="tab-1" href="#tabs-1">' + events_tab_1 + '</a>';
					html += '</li>';
					html += '<li>';
					html += '<a id="tab-2" href="#tabs-2">' + events_tab_2 + '</a>';
					html += '</li>';
					if (__wps__.events_user_places || __wps__.current_user_level == 5) {
						html += '<li>';
						html += '<a id="tab-3" href="#tabs-3">' + events_tab_3 + '</a>';
						html += '</li>';
						html += '<li>';
						html += '<a id="tab-4" href="#tabs-4">' + events_tab_4 + '</a>';
						html += '</li>';
					}
					html += '</ul>';
					html += '<div id="tabs-1">';

					html += '<div id="__wps__edit_event">';

					if (__wps__.events_user_places || __wps__.current_user_level == 5) {
						html += "<div style='width:48%; float:right; text-align:left;'>";
						html += "<div style='clear:both'>" + events_enable_places + " <input type='checkbox' style='float:right' id='__wps__edit_event_enable_places'";
						if (row.enable_places) {
							html += ' CHECKED';
						}
						html += "></div>";
						html += "<div style='clear:both;'>" + events_show_max + " <input type='checkbox' style='float:right' id='__wps__edit_event_show_max'";
						if (row.show_max) {
							html += ' CHECKED';
						}
						html += "></div>";
						html += "<div style='clear:both;'>" + events_confirmation + " <input type='checkbox' style='float:right' id='__wps__edit_event_confirmation'";
						if (row.confirmation) {
							html += ' CHECKED';
						}
						html += "></div>";
						html += "<div style='clear:both;'>" + events_send_email + " <input type='checkbox' style='float:right' id='__wps__edit_event_send_email'";
						if (row.send_email) {
							html += ' CHECKED';
						}
						html += "></div>";
						html += "<div style='clear:both;'>" + events_max_places + " <input type='text' id='__wps__edit_event_max_places' style='float:right; width:30px' value='" + row.max_places + "'></div>";
						html += "<div style='clear:both;'>" + events_tickets_per_booking + " <input type='text' id='__wps__edit_event_tickets_per_booking' style='float:right; width:30px' value='" + row.tickets_per_booking + "'></div>";
						html += "<div style='clear:both;'>" + events_cost + " <input type='text' id='__wps__edit_event_cost' style='float:right; width:30px' value='" + row.cost + "'></div>";
						html += "<div style='clear:both; '>" + events_pay_link + " <a href='javascript:void()' id='symposium_event_help'><img src='" + __wps__.images_url + "/info.png' /></a><a style='display:none' href='javascript:void()' id='symposium_event_help_close'><img src='" + __wps__.images_url + "/edit.png' /></a><br />";
						html += '<div id="event_tag_help" style="display:none; width:370px; height:102px;">';
						html += '##refnumber##<br />';
						html += '##eventname##<br />';
						html += '##userlogin##<br />';
						html += '##useremail##<br />';
						html += '##quantity##<br />';
						html += '##unitcost##';
						html += '</div>';
						html += "<textarea id='__wps__edit_event_pay_link' style='font-size:11px;font-family:courier new;letter-spacing:-1px;width:370px; height:102px;'>" + row.pay_link + "</textarea>";
						html += "</div>";
						html += '</div>';
					}

					if (__wps__.events_user_places || __wps__.current_user_level == 5) {
						html += "<div style='width:50%'>";
					} else {
						html += "<div style='width:100%'>";
					}

					html += "<input type='text' id='__wps__edit_event_name' class='__wps__edit_event_input' value='" + row.event_name.replace(/'/g, '&apos;') + "'><br />";
					html += "<input type='text' id='__wps__edit_event_location' class='__wps__edit_event_input' value='" + row.event_location.replace(/'/g, '&apos;') + "'><br />";
					html += "<textarea type='text' id='__wps__edit_event_desc' class='__wps__edit_event_textarea'>" + row.event_description.replace(/'/g, '&apos;') + "</textarea><br /><br />";

					html += '</div>';

					html += '<div style="margin-top:-13px; text-align:left;width:700px;">';
					html += '<input type="text" id="__wps__edit_event_start" style="width:100px" class="datepicker" value="';
					if (row.start_date != '01/01/1970' && row.start_date != '11/30/-0001') {
						html += row.start_date
					};
					html += '" /> ';
					html += '<select id="__wps__edit_event_start_time_hours">';
					html += '<option value=99>-</option>';
					for (i = 0; i <= 23; i++) {
						html += '<option value=' + i;
						if (i == row.start_hours) {
							html += ' SELECTED';
						}
						html += '>' + i + '</option>';
					}
					html += '</select> : ';
					html += '<select id="__wps__edit_event_start_time_minutes">';
					html += '<option value=99>-</option>';
					for (i = 0; i <= 3; i++) {
						html += '<option value=' + (i * 15);
						if (i * 15 == row.start_minutes) {
							html += ' SELECTED';
						}
						html += '>' + (i * 15) + '</option>';
					}
					html += '</select>';
					html += ' &rarr; ';
					html += '<input type="text" id="__wps__edit_event_end" style="width:100px" class="datepicker" value="';
					if (row.end_date != '01/01/1970' && row.end_date != '11/30/-0001') {
						html += row.end_date
					};
					html += '" /> ';
					html += '<select id="__wps__edit_event_end_time_hours">';
					html += '<option value=99>-</option>';
					for (i = 0; i <= 23; i++) {
						html += '<option value=' + i;
						if (i == row.end_hours) {
							html += ' SELECTED';
						}
						html += '>' + i + '</option>';
					}
					html += '</select> : ';
					html += '<select id="__wps__edit_event_end_time_minutes">';
					html += '<option value=99>-</option>';
					for (i = 0; i <= 3; i++) {
						html += '<option value=' + (i * 15);
						if (i * 15 == row.end_minutes) {
							html += ' SELECTED';
						}
						html += '>' + (i * 15) + '</option>';
					}
					html += '</select>';

					html += '<div style="clear: both; margin-top:7px;">';
					html += '<input id="symposium_event_update_status" type="checkbox" ';
					if (row.event_live == 'on') {
						html += 'CHECKED';
					}
					html += ' /> Published?';
					html += '<input style="margin-left:25px" id="event_google_map_status" type="checkbox" ';
					if (row.event_google_map == 'on') {
						html += 'CHECKED';
					}
					html += ' /> Google Map?';
					html += '</div>';

					html += '</div>';

					html += '</div>';

					html += '</div>'; // End of tab-1
					html += '<div id="tabs-2">';
					if (__wps__.events_use_wysiwyg) {
						html += '<textarea id="edit_event_more">' + row.more + '</textarea>';
					} else {
						html += '<textarea id="edit_event_more" style="width:98%;height:356px">' + row.more + '</textarea>';
					}
					html += '</div>'; // End of tab-2
					if (__wps__.events_user_places || __wps__.current_user_level == 5) {
						html += '<div id="tabs-3">';

						html += '<div style="float:right; width:200px">';
						html += events_replacements + '<br /><br />';
						html += '##displayname##<br />';
						html += '##email##<br />';
						html += '##refnumber##<br />';
						html += '</div>';
						if (__wps__.events_use_wysiwyg) {
							html += '<textarea id="edit_event_text">' + row.email + '</textarea>';
						} else {
							html += '<textarea id="edit_event_text" style="width:560px;height:356px">' + row.email + '</textarea>';
						}

						html += '</div>'; // End of tab-3
						html += '<div id="tabs-4">';
						var attendees = row.attendees;
						html += '<div style="overflow:auto; width:100%; height:368px">';
						html += '<table style="width:100%" cellspacing="1" cellpadding="2">';
						html += '<tr style="font-weight:strong; text-decoration:underline;">';
						html += '<td>' + events_labels[0] + '</td>';
						html += '<td>' + events_labels[1] + '</td>';
						html += '<td>' + events_labels[2] + '</td>';
						html += '<td>' + events_labels[3] + '</td>';
						html += '<td style="text-align:center">' + events_labels[4] + '</td>';
						html += '<td>' + events_labels[5] + '</td>';
						html += '<td>' + events_labels[6] + '</td>';
						html += '</tr>';
						var total = 0;
						jQuery.each(attendees, function(i, attendee) {
							html += '<tr id="attendee_row_' + attendee.bid + '">';
							html += '<td>' + row.id + '/' + attendee.bid + '</td>';
							var q = symposium_q(__wps__.profile_url);
							var url = __wps__.profile_url + q + "uid=" + attendee.uid;
							html += '<td><a target="_blank" href="' + url + '">' + attendee.display_name + '</a></td>';
							html += '<td>' + attendee.booked + '</td>';
							html += '<td>' + attendee.email_sent + '</td>';
							html += '<td style="text-align:center">' + attendee.tickets + '</td>';
							html += '<td>' + attendee.payment_processed + '</td>';
							html += '<td>';
							html += '<a href="mailto:' + attendee.email + '"><img src="' + __wps__.images_url + '/orange-tick.gif" /></a>&nbsp;&nbsp;';
							html += '<img id="symposium_events_resend_email" data-bid="' + attendee.bid + '" style="cursor:pointer" src="' + __wps__.images_url + '/update.png" />&nbsp;&nbsp;';
							html += '<img id="symposium_events_remove_attendee" data-bid="' + attendee.bid + '" style="cursor:pointer" src="' + __wps__.images_url + '/delete.png" />&nbsp;&nbsp;';
							if (!attendee.payment_processed) {
								html += '<img id="symposium_events_payment_processed" data-bid="' + attendee.bid + '" style="cursor:pointer" src="' + __wps__.images_url + '/cash.png" />&nbsp;&nbsp;';
							}
							if (!attendee.confirmed) {
								html += '<img id="symposium_events_confirm_attendee" data-bid="' + attendee.bid + '" style="cursor:pointer; width:15px;height:15px" src="' + __wps__.images_url + '/tick.png" />&nbsp;&nbsp;';
							}
							html += '</td>';
							html += '</tr>';
							total += parseInt(attendee.tickets);
						});
						html += '<tr><td colspan="4">&nbsp;</td><td style="text-align:center;">Total: ' + total + '</td><td>&nbsp;</td></tr>';
						html += '<tr><td colspan="7" align="right"><br /><strong>' + events_labels[6] + ':</strong><br />';
						var g = '&nbsp;&nbsp;&nbsp;';
						html += '<img src="' + __wps__.images_url + '/orange-tick.gif" /> ' + events_labels[8] + g;
						html += '<img src="' + __wps__.images_url + '/update.png" /> ' + events_labels[9] + g;
						html += '<img src="' + __wps__.images_url + '/delete.png" /> ' + events_labels[10] + g;
						html += '<img src="' + __wps__.images_url + '/cash.png" /> ' + events_labels[11] + g;
						html += '<img style="width:15px;height:15px" src="' + __wps__.images_url + '/tick.png" /> ' + events_labels[7] + g;
						html += '</td></tr>';
						html += '</table>';
						html += '</div>';
						html += '</div>'; // End of tab-4
					}
					html += '</div>';

					html += '<div style="clear: both; float: left; margin-left:5px;">';
					html += '<input type="submit" class="symposium_event_update_button __wps__button" value="' + symposium_update + '" />';
					html += '<div style="float: right;" id="symposium_edit_wait"></div>';
					html += '</div>';

					event_owner = row.event_owner;

				});

				jQuery("#dialog").dialog({
					title: __wps__.site_title + ' Event ID:' + event_id,
					width: 850,
					height: 550,
					modal: true,
					buttons: {},
					close: function(ev, ui) {
						jQuery(".__wps__pleasewait").inmiddle().show();
						var q = symposium_q(__wps__.profile_url);
						var reload = __wps__.profile_url + q + "uid=" + event_owner + "&embed=on&view=wps_events";
						window.location.href = reload;
					}
				});
				jQuery('#dialog').html(html);
				if (__wps__.events_use_wysiwyg) {
					tiny_mce_init('edit_event_text');
					tiny_mce_init('edit_event_more');
				}
				jQuery(".datepicker").datepicker({
					showButtonPanel: true
				});
				jQuery("#tabs").tabs();
				jQuery("#tabs a").click(function() {
					var tab = jQuery(this).attr('id');
				});
			},
			error: function(xhr, ajaxOptions, thrownError) {
				if (show_js_errors) {
					alert(xhr.status);
					alert(xhr.statusText);
					alert(thrownError);
				}
			}

		});
	});

	// Make as paid
	jQuery("#symposium_events_payment_processed").live('click', function() {
		jQuery(".__wps__pleasewait").inmiddle().show();
		var bid = jQuery(this).data("bid");
		jQuery(this).hide();
		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/events_functions.php",
			type: "POST",
			data: ({
				action: "payment_recd",
				'bid': bid
			}),
			dataType: "html",
			async: true,
			success: function(str) {
				jQuery(".__wps__pleasewait").hide();
				if (str != 'OK') {
					jQuery("#dialog").html(str).dialog({
						title: __wps__.site_title,
						width: 400,
						height: 300,
						modal: true,
						buttons: {
							"OK": function() {
								jQuery("#dialog").dialog('close');
							}
						}
					});
				}
			}
		});
	});

	// Re-send confirmation email
	jQuery("#symposium_events_resend_email").live('click', function() {
		jQuery(".__wps__pleasewait").inmiddle().show();
		var bid = jQuery(this).data("bid");
		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/events_functions.php",
			type: "POST",
			data: ({
				action: "resendEmail",
				'bid': bid
			}),
			dataType: "html",
			async: true,
			success: function(str) {
				jQuery(".__wps__pleasewait").hide();
				if (str != 'OK') {
					jQuery("#dialog").html(str).dialog({
						title: __wps__.site_title,
						width: 400,
						height: 300,
						modal: true,
						buttons: {
							"OK": function() {
								jQuery("#dialog").dialog('close');
							}
						}
					});
				}
			}
		});
	});

	// Remove attendee
	jQuery("#symposium_events_remove_attendee").live('click', function() {
		if (confirm(areyousure)) {
			jQuery(".__wps__pleasewait").inmiddle().show();
			var bid = jQuery(this).data("bid");
			jQuery.ajax({
				url: __wps__.plugin_url + "ajax/events_functions.php",
				type: "POST",
				data: ({
					action: "removeAttendee",
					'bid': bid
				}),
				dataType: "html",
				async: true,
				success: function(str) {
					jQuery(".__wps__pleasewait").hide();
					if (str != 'OK') {
						jQuery("#dialog").html(str).dialog({
							title: __wps__.site_title,
							width: 400,
							height: 300,
							modal: true,
							buttons: {
								"OK": function() {
									jQuery("#dialog").dialog('close');
								}
							}
						});
					}
					jQuery("#attendee_row_" + bid).hide('slow');

				}
			});
		}
	});

	// Confirm attendee
	jQuery("#symposium_events_confirm_attendee").live('click', function() {
		jQuery(".__wps__pleasewait").inmiddle().show();
		var bid = jQuery(this).data("bid");
		jQuery(this).hide();
		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/events_functions.php",
			type: "POST",
			data: ({
				action: "confirmAttendee",
				'bid': bid
			}),
			dataType: "html",
			async: true,
			success: function(str) {
				jQuery(".__wps__pleasewait").hide();
				if (str != 'OK') {
					jQuery("#dialog").html(str).dialog({
						title: __wps__.site_title,
						width: 400,
						height: 300,
						modal: true,
						buttons: {
							"OK": function() {
								jQuery("#dialog").dialog('close');
							}
						}
					});
				}

			}
		});
	});

	// Help for Payment HTML
	jQuery("#symposium_event_help").live('click', function() {
		jQuery('#__wps__edit_event_pay_link').hide();
		jQuery('#symposium_event_help').hide();
		jQuery('#symposium_event_help_close').show();
		jQuery('#event_tag_help').show();
	});
	jQuery("#symposium_event_help_close").live('click', function() {
		jQuery('#__wps__edit_event_pay_link').show();
		jQuery('#symposium_event_help').show();
		jQuery('#symposium_event_help_close').hide();
		jQuery('#event_tag_help').hide();
	});


	// Update event
	jQuery(".symposium_event_update_button").live('click', function() {

		jQuery(".__wps__pleasewait").inmiddle().show();

		if (__wps__.events_user_places || __wps__.current_user_level == 5) {
			if (__wps__.events_use_wysiwyg) {
				var more = tinyMCE.get('edit_event_more').getContent();
				var email = tinyMCE.get('edit_event_text').getContent();
			} else {
				var more = jQuery('#edit_event_more').val();
				var email = jQuery('#edit_event_text').val();
			}
			var max_places = jQuery('#__wps__edit_event_max_places').val();
			var cost = jQuery('#__wps__edit_event_cost').val();
			var pay_link = jQuery('#__wps__edit_event_pay_link').val();
			var tickets_per_booking = jQuery('#__wps__edit_event_tickets_per_booking').val();
			var enable_places = '';
			if (jQuery('#__wps__edit_event_enable_places:checked').val() == 'on') {
				enable_places = 'on';
			}
			var show_max = '';
			if (jQuery('#__wps__edit_event_show_max:checked').val() == 'on') {
				show_max = 'on';
			}
			var confirmation = '';
			if (jQuery('#__wps__edit_event_confirmation:checked').val() == 'on') {
				confirmation = 'on';
			}
			var send_email = '';
			if (jQuery('#__wps__edit_event_send_email:checked').val() == 'on') {
				send_email = 'on';
			}
		} else {
			var max_places = 0;
			var cost = 0;
			var tickets_per_booking = 0;
			var more = '';
			var pay_link = '';
			var enable_places = '';
			var show_max = '';
			var confirmation = '';
			var send_email = '';
			var email = '';
		}


		var eid = jQuery('#__wps__edit_event_eid').html();
		var name = jQuery('#__wps__edit_event_name').val();
		var location = jQuery('#__wps__edit_event_location').val();
		var google_map = jQuery('#__wps__edit_event_location').val();
		var desc = jQuery('#__wps__edit_event_desc').val();
		var start = jQuery('#__wps__edit_event_start').val();
		var start_hours = jQuery('#__wps__edit_event_start_time_hours').find(':selected').val();
		var start_minutes = jQuery('#__wps__edit_event_start_time_minutes').find(':selected').val();
		var end = jQuery('#__wps__edit_event_end').val();
		var end_hours = jQuery('#__wps__edit_event_end_time_hours').find(':selected').val();
		var end_minutes = jQuery('#__wps__edit_event_end_time_minutes').find(':selected').val();
		var event_live = '';
		if (jQuery('#symposium_event_update_status:checked').val() == 'on') {
			event_live = 'on';
		}

		var google_map = '';
		if (jQuery('#event_google_map_status:checked').val() == 'on') {
			google_map = 'on';
		}


		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/events_functions.php",
			type: "POST",
			data: ({
				action: "updateEvent",
				'eid': eid,
				'name': name,
				'location': location,
				'google_map': google_map,
				'desc': desc,
				'start_date': start,
				'start_hours': start_hours,
				'start_minutes': start_minutes,
				'end_date': end,
				'end_hours': end_hours,
				'end_minutes': end_minutes,
				'event_live': event_live,
				'enable_places': enable_places,
				'max_places': max_places,
				'show_max': show_max,
				'confirmation': confirmation,
				'tickets_per_booking': tickets_per_booking,
				'send_email': send_email,
				'email': email,
				'pay_link': pay_link,
				'cost': cost,
				'more': more
			}),
			dataType: "html",
			async: true,
			success: function(str) {
				if (__wps__.debug) {
					jQuery("#dialog").html(str).dialog({
						title: __wps__.site_title + ' debug info',
						width: 800,
						height: 500,
						modal: true
					});
				}
				jQuery(".__wps__pleasewait").hide();

			},
			error: function(xhr, ajaxOptions, thrownError) {
				if (show_js_errors) {
					alert(xhr.status);
					alert(xhr.statusText);
					alert(thrownError);
				}
			}

		});

	});

/*
	   +------------------------------------------------------------------------------------------+
	   |                                        GALLERY                                           |
	   +------------------------------------------------------------------------------------------+
	*/

	// autocomplete
	if (jQuery("input#gallery_member").length) {
		jQuery("input#gallery_member").autocomplete({
			source: __wps__.plugin_url + "ajax/gallery_functions.php",
			minLength: 3,
			focus: function(event, ui) {
				jQuery("input#gallery_member").val(ui.item.name);
				return false;
			},
			select: function(event, ui) {
				jQuery(".__wps__pleasewait").inmiddle().show().delay(3000).fadeOut("slow");
				var q = symposium_q(__wps__.profile_url);
				window.location.href = __wps__.profile_url + q + 'uid=' + ui.item.owner + '&embed=on&album_id=' + ui.item.id;
				return false;
			}
		}).data("uiAutocomplete")._renderItem = function(ul, item) {
			var group = "<a>";
			group += "<div style='height:40px; overflow:hidden'>";
			group += "<div style=\'float:left; background-color:#fff; margin-right: 8px; width:40px; height:40px; \'>";
			group += item.avatar;
			group += "</div>";
			group += "<div>" + item.display_name + "</div>";
			group += "<div style='font-size:80%'>" + item.name + "</div>";
			group += "<br style='clear:both' />";
			group += "</div>";
			group += "</a>";
			return jQuery("<li></li>").data("item.autocomplete", item).append(group).appendTo(ul);
		};
	}


	// Act on "album_id" parameter, load album straight away (remember to set embed=on on hyperlink)
	if (__wps__.album_id > 0 && __wps__.embed == 'on') {

		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/gallery_functions.php",
			type: "POST",
			data: ({
				action: 'menu_gallery',
				album_id: __wps__.album_id,
				uid1: __wps__.current_user_page
			}),
			dataType: "html",
			success: function(str) {
				jQuery('#profile_body').html(str);
				
				var user_login = jQuery("#symposium_user_login").html();
				var user_email = jQuery("#symposium_user_email").html();
						
				// File upload
				__wps__init_file_upload();

			}
		});


	}

	jQuery('#gallery_go_button').live('click', function() {
		jQuery("#symposium_gallery_start").html('0');
		symposium_do_gallery_search();
	});
	jQuery('#gallery_member').live('keypress', function(e) {
		if (e.keyCode == 13) {
			jQuery("#symposium_gallery_start").html('0');
			symposium_do_gallery_search();
		}
	});

	// Search
	jQuery('#showmore_gallery').live('click', function() {
		jQuery(this).html("<br /><img src='" + __wps__.images_url + "/busy.gif' />");
		symposium_do_gallery_search();
	});

	function symposium_do_gallery_search() {

		var page_length = jQuery('#symposium_gallery_page_length').html();
		var start = jQuery("#symposium_gallery_start").html();

		if (start == 0) {
			jQuery('#symposium_gallery_albums').html("<br /><img src='" + __wps__.images_url + "/busy.gif' />");
		}

		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/gallery_functions.php",
			type: "POST",
			data: ({
				action: "getGallery",
				start: start,
				term: jQuery('#gallery_member').val()
			}),
			dataType: "html",
			async: true,
			success: function(str) {
				var details = str.split("[split]");
				str = details[1];
				var new_start = parseFloat(start) + parseFloat(details[0]);
				jQuery("#symposium_gallery_start").html(new_start);
				if (start == 0) {
					jQuery('#symposium_gallery_albums').html(str);
				} else {
					jQuery('#symposium_gallery_albums').html(jQuery('#symposium_gallery_albums').html() + str);
					jQuery('#showmore_gallery').remove();
				}

			},
			error: function(xhr, ajaxOptions, thrownError) {
				if (show_js_errors) {
					alert(xhr.status);
					alert(xhr.statusText);
					alert(thrownError);
				}
			}

		});
	}

	// Function to display gallery dialog box, following click on an image hyperlink

	function prepare_colorbox(t) {

		var rel = jQuery(t).attr("rel");
		var div_title = "#wps_gallery_album_name_" + rel.replace('symposium_gallery_photos_', '');
		var album_title = '';
		if (typeof jQuery(t).attr("title") != 'undefined') {
			album_title = jQuery(t).attr("title");
			if (album_title == '' && jQuery(div_title).html() != '') {
				album_title = jQuery(div_title).html();
			}
		}

		// Get list and number of images in this album
		var max_images = -1;
		var list = new Array();
		var ids = new Array();
		var names = new Array();
		var owner = 0;
		jQuery("a[rel='" + rel + "']").each(function(index) {
			if (jQuery.inArray(jQuery(this).attr("data-iid"), ids) == -1) {
				max_images++;
				if (typeof jQuery(this).attr("data-name") != 'undefined') {
					list.push(jQuery(this).attr("href"));
					ids.push(jQuery(this).attr("data-iid"));
					names.push(jQuery(this).attr("data-name"));
				}

				// Record owner
				if (typeof jQuery(this).attr("data-owner") != 'undefined') owner = jQuery(this).attr("data-owner");
			}
		})

		var current_image = jQuery(t).attr("rev");

		jQuery("#dialog").html("<img src='" + __wps__.images_url + "/busy.gif' />");
		if (ids[0] == 0 || album_title == '') {
			jQuery("#dialog").dialog({
				zIndex: 10000,
				title: '',
				width: 650,
				height: 600,
				modal: true,
				buttons: {},
				open: function(event, ui) {
					jQuery('body').css('overflow-x', 'hidden');
					jQuery('.ui-widget-overlay').css('width', '100%');
				},
				close: function(event, ui) {
					jQuery('body').css('overflow', 'auto');
				}
			});
		} else {
			jQuery("#dialog").dialog({
				zIndex: 10000,
				title: album_title,
				width: 1000,
				height: 600,
				modal: true,
				buttons: {},
				open: function(event, ui) {
					jQuery('body').css('overflow-x', 'hidden');
					jQuery('.ui-widget-overlay').css('width', '100%');
				},
				close: function(event, ui) {
					jQuery('body').css('overflow', 'auto');
				}
			});
		}

		// Build HTML			
		var html = '';
		html += '<div id="__wps__photo_gallery"';
		if (max_images == 0) html += ' style="height:440px"';		
		html += '>';

		// Info
		html += '<input id="symposium_current_image" style="display:none" value="' + current_image + '" />';
		html += '<input id="symposium_max_images" style="display:none" value="' + max_images + '" />';
		html += '<input id="__wps__album_title" style="display:none" value="' + album_title + '" />';

		if (ids[0] != 0) {
			html += '<div id="__wps__photo_comments">';

			// Management
			if ((owner == __wps__.current_user_id || __wps__.current_user_level == 5) && album_title != '') {
				html += '<div id="__wps__photo_management" style="margin-bottom:10px">';
				var gallery_labels = __wps__.gallery_labels.split('|');

				html += '<div id="__wps__rename_photo" style="margin-bottom:5px"><input type="text" id="__wps__rename" value="" /><input type="submit" id="__wps__rename_button" class="__wps__button" value="' + gallery_labels[0] + '" /></div>';
				html += '<div id="__wps__rename_photo_confirm" style="margin-bottom:11px;display:none;">' + gallery_labels[1] + '</div>';
				html += '<div id="__wps__photo_order" style="clear:both">' + gallery_labels[2] + ' <a id="__wps__photo_order_save" style="text-decoration:underline" href="javascript:void(0);">' + gallery_labels[3] + '</a>.' + '</div>';
				html += '<div class="__wps__photo_delete" style="cursor:pointer; text-decoration:underline;">' + gallery_labels[4] + '</div>';
				html += '<div class="__wps__photo_select_cover_button" style="cursor:pointer; text-decoration:underline;">' + gallery_labels[5] + '</div>';
				html += '</div>';
			}

			// Actions
			if (__wps__.current_user_id > 0 && album_title != '') {
				html += '<div id="__wps__photo_actions">';
				html += '<a href="" target="_blank" id="show_original">' + show_original + '</a>';
				html += '</div>';
			}

			// Preload images
			for (var i = 0; i < list.length; i++) {
				var url = list[i];
				html += '<img id="cache_' + i + '" style="display:none;width:20px;height:20px" src="' + url + '" />';
			}

			// Comments
			if (__wps__.current_user_id > 0 && album_title != '') {
				html += '<div id="__wps__add_a_comment_label">'+add_a_comment+'</div>';
				html += '<textarea id="__wps__photo_add_comment" class="elastic"></textarea>';
				html += '<textarea id="__wps__photo_add_comment_old" style="display:none"></textarea>';
					
				html += '<input id="__wps__photo_submit_comment" type="button" class="__wps__button" value="' + btn_add + '" />';
				html += '<input id="__wps__photo_submit_comment_cancel" type="button" class="__wps__button" style="display:none" value="' + jQuery('#__wps__cancel').html() + '" />';
			}
			if (album_title != '') {
				html += '<div id="__wps__photo_comments_container"></div>';
			}

			html += '</div>';
		}

		html += '<div id="__wps__photo_container">';

		// Photo frame
		html += '<div id="__wps__photo_frame">';
		html += '</div>';

		// Thumbnails
		html += '<div id="__wps__photo_thumbnails"';
		if (max_images == 0) {
			html += ' style="display:none;"';
			jQuery("#dialog").height('450px');
		}
		html += '>';
		for (var i = 0; i < list.length; i++) {
			var url = list[i].replace('show_', 'thumb_');
			var name = names[i];
			html += '<img id="thumb_' + i + '" title="' + name + '" rel="' + ids[i] + '" class="__wps__photo_thumbnail __wps__photo_thumbnail_border_off " src="' + url + '" />';
		}
		html += '</div>';
		
		html += '</div>';

		html += '</div>';

		jQuery("#dialog").html(html);

		// Sortable thumbnails
		if (owner == __wps__.current_user_id && album_title != '') {
			jQuery('#__wps__photo_thumbnails').sortable();
			jQuery('#__wps__photo_order_save').live('click', function() {
				var order = '';
				jQuery('#__wps__photo_thumbnails').children(jQuery('.__wps__photo_thumbnails')).each(function(i) {
					order += jQuery(this).attr('rel') + ',';
				});

				jQuery("#__wps__photo_order").html("<img src='" + __wps__.images_url + "/busy.gif' />");
				jQuery.ajax({
					url: __wps__.plugin_url + "ajax/gallery_functions.php",
					type: "POST",
					data: ({
						action: 'symposium_reorder_photos',
						album_id: rel,
						order: order
					}),
					async: false,
					dataType: "html",
					success: function(str) {
						if (str != 'OK') {
							jQuery("#__wps__photo_order").html(str).effect("highlight", {}, 3000);
						}
					}
				});
			});
		}

		if (__wps__.use_elastic == 'on' && jQuery(".elastic").length) {
			jQuery('.elastic').elastic();
		}

		return false;

	}

	jQuery("#__wps__photo_submit_comment").live('click', function() {
		var comment = jQuery('#__wps__photo_add_comment').val();
		jQuery('#__wps__photo_add_comment').val('');
		jQuery('#__wps__photo_add_comment').css('height', '40px');

		if (comment != '') {

		  var current = parseInt(jQuery("#symposium_current_image").val());
			var id = jQuery("#thumb_" + current).attr("rel");
			jQuery('#__wps__photo_comments_container').html("<img src='" + __wps__.images_url + "/busy.gif' />");

			if (this.value != jQuery('#__wps__update').html()) {
				// Add
			  jQuery.ajax({
				  url: __wps__.plugin_url + "ajax/gallery_functions.php",
				  type: "POST",
				  data: ({
			  		action: 'symposium_add_photo_comment',
			  		photo_id: id,
				  	comment: comment
				  }),
				  async: false,
				  dataType: "html",
				  success: function(str) {
					  symposium_show_photo_comments(jQuery("#thumb_" + current));
				  }
			  });

		  } else {
			// Update
		    jQuery('#__wps__photo_submit_comment_cancel').hide();
        	jQuery("#__wps__photo_submit_comment").val(btn_add);
		    jQuery('#__wps__photo_add_comment').val('');
				
				jQuery.ajax({
				  url: __wps__.plugin_url + "ajax/gallery_functions.php",
				  type: "POST",
				  data: ({
			  		action: 'symposium_update_photo_comment',
			  		photo_id: id,
				  	comment: comment,
					  old_comment: jQuery('#__wps__photo_add_comment_old').val()
				  }),
				  async: false,
				  dataType: "html",
				  success: function(str) {
						jQuery("#__wps__add_a_comment_label").show();
					  symposium_show_photo_comments(jQuery("#thumb_" + current));
				  }
			  });
			}
		}

	})

	jQuery(".wps_gallery_album").live('click', function() {
		prepare_colorbox(this);
		var current = parseInt(jQuery(this).attr("rev")) - 1;
		var t = jQuery("#thumb_" + current);
		display_gallery_item(t);
		return false;
	})

	jQuery(".__wps__photo_thumbnail").live('click', function() {
		var current = jQuery(this).attr("id").replace('thumb_', '');
		jQuery("#symposium_current_image").val(current);
		display_gallery_item(jQuery("#thumb_" + current));
	})

	jQuery(".__wps__photo").live('click', function() {
		var current = parseInt(jQuery("#symposium_current_image").val());
		var max = parseInt(jQuery("#symposium_max_images").val());
		if (current < max) {
			current++;
		} else {
			current = 0;
		}
		jQuery("#symposium_current_image").val(current);
		if (jQuery("#thumb_" + current).length) {
			display_gallery_item(jQuery("#thumb_" + current));
		}
	})

	// Set up left/right arrows
	jQuery("body").keydown(function(e) {

		if (!jQuery('#__wps__photo_add_comment').is(':focus')) {

			if ((e.keyCode == 39 || e.keyCode == 13) && jQuery("#__wps__photo_gallery").length) {
				var current = parseInt(jQuery("#symposium_current_image").val());
				var max = parseInt(jQuery("#symposium_max_images").val());
				if (current < max) {
					current++;
				} else {
					current = 0;
				}
				jQuery("#symposium_current_image").val(current);
				if (jQuery("#thumb_" + current).length) {
					display_gallery_item(jQuery("#thumb_" + current));
				}
			}
			if (e.keyCode == 37 && jQuery("#__wps__photo_gallery").length) {
				var current = parseInt(jQuery("#symposium_current_image").val());
				var max = parseInt(jQuery("#symposium_max_images").val());
				if (current > 0) {
					current--;
				} else {
					current = max;
				}
				jQuery("#symposium_current_image").val(current);
				if (jQuery("#thumb_" + current).length) {
					display_gallery_item(jQuery("#thumb_" + current));
				}
			}

		}
	});

	function display_gallery_item(t) {

		// Show waiting...
		var middle_of_photo_frame_height = (jQuery("#__wps__photo_frame").height() / 2);
		jQuery("#__wps__photo_frame").html("<img style='margin-top:" + middle_of_photo_frame_height + "px' src='" + __wps__.images_url + "/busy.gif' />");

		// Load the image
		var url = jQuery(t).attr("src").replace('thumb_', 'show_');
		var img = new Image();
		img.src = url;
		var w = img.width;
		var h = img.height;

		// Increase wait in steps until image is loaded
		if (w > 0) {
			symposium_show_image(t, url, w, h);
		} else {
			setTimeout(function() {
				var w = img.width;
				var h = img.height;
				if (w > 0) {
					symposium_show_image(t, url, w, h);
				} else {
					setTimeout(function() {
						var w = img.width;
						var h = img.height;
						if (w > 0) {
							symposium_show_image(t, url, w, h);
						} else {
							setTimeout(function() {
								var w = img.width;
								var h = img.height;
								symposium_show_image(t, url, w, h);
							}, 3000);
						}
					}, 1500);
				}
			}, 1000);
		}

	}

	function symposium_show_image(t, url, w, h) {

		var photo_frame_width = jQuery("#__wps__photo_frame").width();
		var photo_frame_height = jQuery("#__wps__photo_frame").height();

		var photo_width = photo_frame_width;
		var max_photo_height = photo_frame_height;
		var width_factor = w / photo_frame_width;

		if (w > photo_frame_width || h > photo_frame_height) {
			w = w / width_factor;
			h = h / width_factor;
			if (h > max_photo_height) {
				height_factor = h / max_photo_height;
				w = w / height_factor;
				h = h / height_factor;
			}
			var photo_margin_top = 0;
		}

		if (h < photo_frame_height) {
			photo_margin_top = (photo_frame_height - h) / 2;
		}
		var show_image = '<img class="__wps__photo" width=0 height=0 style="margin-top:' + photo_margin_top + 'px; width:' + w + 'px; height:' + h + 'px;" src="' + url + '" />';

		jQuery("#symposium_current_image").val(jQuery(t).attr("id").replace('thumb_', ''));
		jQuery("#__wps__photo_frame").html(show_image);

		jQuery(".__wps__photo_thumbnail").addClass("__wps__photo_thumbnail_border_off").removeClass("__wps__photo_thumbnail_border_on");
		jQuery(t).toggleClass("__wps__photo_thumbnail_border_off").toggleClass("__wps__photo_thumbnail_border_on");

		// Photo title
		var album_title = jQuery('#__wps__album_title').val();
		if (jQuery(t).attr('title')) {
			var photo_title = jQuery(t).attr('title').replace(/(<([^>]+)>)/ig, '');
			if (album_title != '' && photo_title != '') album_title += ' - ';
			jQuery("#dialog").dialog('option', 'title', album_title + photo_title);
		} else {
			jQuery("#dialog").dialog('option', 'title', album_title);
		}
		// Set current name (for editing)
		var current_image = jQuery("#symposium_current_image").val();
		var current_name = jQuery('#thumb_' + current_image).attr('title');
		current_name = current_name.replace(/(<([^>]+)>)/ig, '');
		jQuery('#__wps__rename').val(current_name);

		// Actions
		jQuery("#show_original").attr("href", url.replace('show_', ''));

		symposium_show_photo_comments(t);
		jQuery('.__wps__photo_delete').show();
		jQuery('.__wps__photo_select_cover_button').show();
		jQuery('#__wps__rename_photo').show();
		jQuery('#__wps__rename_photo_confirm').hide();


	}

	function symposium_show_photo_comments(t) {
		// Comments
		jQuery('#__wps__photo_comments_container').html("<img src='" + __wps__.images_url + "/busy.gif' />");
		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/gallery_functions.php",
			type: "POST",
			data: ({
				action: 'symposium_get_photo_comments',
				photo_id: t.attr("rel")
			}),
			dataType: "html",
			success: function(str) {
				if (str != '[]') {
					var html = '';
					var rows = jQuery.parseJSON(str);
					jQuery.each(rows, function(i, row) {
						html += '<div class="__wps__photo_comment hover_row">';
						html += '<div class="__wps__photo_comment_avatar">';
						html += row.avatar;
						html += '</div>';
						html += '<div>';
						if (row.author_id == __wps__.current_user_id || __wps__.current_user_level == 5) {
							html += '<div class="hover_child" style="width:50px;float:right;display:none;">';
							var edit_icon = "<a title='Edit' id='" + row.ID + "' href='javascript:void(0);' class='__wps__photo_comment_edit_icon'><img src='" + __wps__.images_url + "/edit.png' style='width:16px;height:16px;' /></a>";
							var del_icon = "<a title='Delete' id='" + row.ID + "' href='javascript:void(0);' class='__wps__photo_comment_del_icon'><img src='" + __wps__.images_url + "/delete.png' style='width:16px;height:16px;margin-left:6px;' /></a>";
							html += edit_icon;
							html += del_icon;
							html += '</div>';
						}
						html += row.display_name_link + ', ' + row.timestamp + '<br />';
						html += row.comment.replace(/\n/g, "<br />");
						html += '</div>';
						html += '</div>';

					})

				} else {
					html = '';
				}
				jQuery('#__wps__photo_comments_container').html(html);

			}
		});
	}

	// Show icons on hover
	jQuery('.hover_row').live('mouseenter mouseleave', function(event) {
		if (event.type == 'mouseenter') {
			jQuery(this).find(".hover_child").show()
		} else {
			jQuery(this).find(".hover_child").hide();
		}
	});

	// Edit photo comment
  jQuery(".__wps__photo_comment_edit_icon").live('click', function() {
		jQuery("#__wps__add_a_comment_label").hide();
		jQuery("#__wps__photo_submit_comment").val(jQuery('#__wps__update').html());
		jQuery('#__wps__photo_submit_comment_cancel').show();
		var id = this.id;
		jQuery(".__wps__pleasewait").inmiddle().show();
		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/gallery_functions.php",
			type: "POST",
			data: ({
				action: '__wps__get_gallery_comment',
				cid: id
			}),
			dataType: "html",
			async: false,
			success: function(str) {
				jQuery(".__wps__pleasewait").hide();
				str = trim(str);
				jQuery('#__wps__photo_add_comment').val(str);
				jQuery('#__wps__photo_add_comment_old').val(str);
				jQuery('#__wps__photo_add_comment').effect("highlight", {}, 3000);;
			}
		});
	});
	
	// Cancel edit photo comment
  jQuery("#__wps__photo_submit_comment_cancel").live('click', function() {
		jQuery(this).hide();
    jQuery("#__wps__photo_submit_comment").val(btn_add);
		jQuery('#__wps__photo_add_comment').val('');
		jQuery("#__wps__add_a_comment_label").show();
	});
	
	// Delete photo comment
  jQuery(".__wps__photo_comment_del_icon").live('click', function() {
		var id = this.id;
		jQuery(".__wps__pleasewait").inmiddle().show();
		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/gallery_functions.php",
			type: "POST",
			data: ({
				action: '__wps__delete_gallery_comment',
				cid: id
			}),
			dataType: "html",
			success: function(str) {
				jQuery(".__wps__pleasewait").hide();
				var current = parseInt(jQuery("#symposium_current_image").val());
				symposium_show_photo_comments(jQuery("#thumb_" + current));
			}
		});

	});

	// Stretch div on activity stream
	jQuery("#wps_gallery_comment_more").live('click', function() {
		jQuery(this).hide();
		jQuery(this).parent().parent().find("#wps_comment_plus").css("overflow", "visible");
	});

	// Manage album (select cover)
	jQuery(".__wps__photo_select_cover_button").live('click', function() {

		var current = jQuery("#symposium_current_image").val();
		var t = this;
		// loop through to current image
		var cnt = 0;
		jQuery('#__wps__photo_thumbnails').children(jQuery('.__wps__photo_thumbnails')).each(function(i) {
			if (cnt == current) {

				var tmp = jQuery(t).html();
				jQuery(t).html("<img src='" + __wps__.images_url + "/busy.gif' />");
				var item_id = jQuery(this).attr("rel");
				jQuery.ajax({
					url: __wps__.plugin_url + "ajax/gallery_functions.php",
					type: "POST",
					data: ({
						action: 'menu_gallery_select_cover',
						item_id: item_id
					}),
					dataType: "html",
					async: false,
					success: function(str) {
						jQuery(t).slideUp('fast').html(tmp);
					}
				});

			}
			cnt++;
		});

	});

	// Change sharing status
	jQuery("#gallery_share").live('change', function() {

		jQuery('#__wps__album_sharing_save').show();

		__wps__.album_id = jQuery(this).attr("title");

		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/gallery_functions.php",
			type: "POST",
			data: ({
				action: 'menu_gallery_change_share',
				album_id: __wps__.album_id,
				new_share: jQuery("#gallery_share").val()
			}),
			dataType: "html",
			success: function(str) {
				jQuery('#__wps__album_sharing_save').hide();
				if (str != 'OK') {
					jQuery("#dialog").html(str).dialog({
						title: __wps__.site_title + ' debug info',
						width: 800,
						height: 500,
						modal: true
					});
				}
			}
		});

	});

	// Delete all
	jQuery(".__wps__photo_delete_all").live('click', function() {

		if (confirm("Are you sure?")) {

			__wps__.album_id = jQuery(this).attr("rel");

			jQuery.ajax({
				url: __wps__.plugin_url + "ajax/gallery_functions.php",
				type: "POST",
				data: ({
					action: 'menu_gallery_manage_delete_all',
					album_id: __wps__.album_id
				}),
				dataType: "html",
				success: function(str) {
					jQuery('.__wps__photo_row').slideUp("slow");
					var q = symposium_q(__wps__.profile_url);
					var reload_page = __wps__.profile_url + q + "uid=" + __wps__.current_user_page + "&view=gallery";
					window.location.href = reload_page;
				}
			});

		}

	});

	// Delete
	jQuery(".__wps__photo_delete").live('click', function() {

		var current = jQuery("#symposium_current_image").val();
		var t = this;

		var tmp = jQuery(t).html();
		jQuery(t).html("<img src='" + __wps__.images_url + "/busy.gif' />");

		// loop through to current image
		var cnt = 0;
		jQuery('#__wps__photo_thumbnails').children(jQuery('.__wps__photo_thumbnails')).each(function(i) {
			if (cnt == current) {
				if (confirm("Are you sure?")) {
					// Delete this thumbnail and image
					var item_id = jQuery(this).attr("rel");
					jQuery(this).remove();
					jQuery("#__wps__photo_frame").children().fadeOut('slow');

					// Delete image on the page (and surrounding DIVs)
					jQuery('a[data-iid="' + item_id + '"]').parent().parent().parent().remove();
					jQuery('a[data-iid="' + item_id + '"]').parent().parent().remove();
					jQuery('a[data-iid="' + item_id + '"]').parent().remove();
					jQuery('a[data-iid="' + item_id + '"]').remove();

					jQuery.ajax({
						url: __wps__.plugin_url + "ajax/gallery_functions.php",
						type: "POST",
						data: ({
							action: 'menu_gallery_manage_delete',
							item_id: item_id
						}),
						dataType: "html",
						async: false,
						success: function(str) {
							jQuery(t).slideUp('fast').html(tmp);
						}
					});
				}
			}
			cnt++;
		});

	});

	// Rename photo
	jQuery("#__wps__rename_button").live('click', function() {

		var current = jQuery("#symposium_current_image").val();
		var t = this;

		jQuery("#__wps__rename_photo").hide();
		jQuery("#__wps__rename_photo_confirm").show().effect("highlight", {}, 3000);

		// loop through to current image
		var cnt = 0;
		jQuery('#__wps__photo_thumbnails').children(jQuery('.__wps__photo_thumbnails')).each(function(i) {
			if (cnt == current) {

				// Delete this thumbnail and image
				var item_id = jQuery(this).attr("rel");
				var new_name = jQuery("#__wps__rename").val();
				jQuery('#thumb_' + current).attr('title', new_name);
				// Rename image on the page
				jQuery('a[data-iid="' + item_id + '"]').attr('data-name', new_name);
				// Update dialog title
				jQuery("#dialog").dialog('option', 'title', jQuery('#__wps__album_title').val() + ' - ' + new_name);

				jQuery.ajax({
					url: __wps__.plugin_url + "ajax/gallery_functions.php",
					type: "POST",
					data: ({
						action: 'menu_gallery_manage_rename',
						item_id: item_id,
						new_name: new_name
					}),
					dataType: "html",
					async: false,
					success: function(str) {}
				});

			}
			cnt++;
		});

	});

	// Click on album cover
	jQuery(".__wps__album_cover_action").live('click', function() {

		__wps__.album_id = jQuery(this).attr("title");
		symposium_show_album();

	});

	// Back to top
	jQuery("#__wps__gallery_top").live('click', function() {
		__wps__.album_id = 0;
		symposium_show_album();
	});

	// Up a level
	jQuery("#symposium_gallery_up").live('click', function() {
		__wps__.album_id = jQuery(this).attr("title");
		symposium_show_album();
	});

	// Function to show album (for above)	


	function symposium_show_album() {

		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/gallery_functions.php",
			type: "POST",
			data: ({
				action: 'menu_gallery',
				album_id: __wps__.album_id,
				uid1: __wps__.current_user_page
			}),
			dataType: "html",
			success: function(str) {
				jQuery('#profile_body').html(str);
				
				var user_login = jQuery("#symposium_user_login").html();
				var user_email = jQuery("#symposium_user_email").html();
				
				// File upload
				__wps__init_file_upload();

			}
		});
	}

	// Toggle new album form
	jQuery(".symposium_new_album_button").live('click', function() {

		jQuery('.symposium_new_album_button').hide();
		jQuery('.__wps__photo_delete_all').hide();
		jQuery("#__wps__create_gallery").show();
		jQuery("#__wps__album_covers").hide();
		jQuery("#__wps__album_content").hide();

		if (__wps__.album_id > 0) {
			jQuery(".__wps__create_sub_gallery").show();
		} else {
			jQuery(".__wps__create_sub_gallery").hide();
		}
	});
	jQuery("#symposium_cancel_album").live('click', function() {
		jQuery('.symposium_new_album_button').show();
		jQuery('.__wps__photo_delete_all').show();
		jQuery('.album_name').show();
		jQuery("#__wps__album_covers").show();
		jQuery("#__wps__album_content").show();
		jQuery("#__wps__create_gallery").hide();
	});

	// Create new album
	jQuery("#symposium_new_album").live('click', function() {

		jQuery(".__wps__pleasewait").inmiddle().show();
		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/gallery_functions.php",
			type: "POST",
			data: ({
				action: 'create_album',
				name: jQuery("#symposium_new_album_title").val(),
				sub_album: jQuery("#__wps__create_sub_gallery_select").is(":checked"),
				parent: jQuery("#__wps__create_sub_gallery_select").attr("title")
			}),
			dataType: "html",
			success: function(str) {
				var q = symposium_q(__wps__.profile_url);
				var reload_page = __wps__.profile_url + q + "uid=" + __wps__.current_user_page + "&embed=on&album_id=" + str;
				window.location.href = reload_page;
			}
		});
	});



/*
	   +------------------------------------------------------------------------------------------+
	   |                                        LOUNGE                                            |
	   +------------------------------------------------------------------------------------------+
	*/

	// Start regular checks for lounge contents
	lounge_polling();

	// Add comment via button
	jQuery("#__wps__lounge_add_comment_button").live('click', function() {
		add_comment_to_lounge();
	});

	// Add comment via Return on keyboard
	jQuery('#__wps__lounge_add_comment').live('keypress', function(e) {
		if (e.keyCode == 13) {
			add_comment_to_lounge();
		}
	});

	// Delete comment via trash icon
	jQuery(".__wps__lounge_del_icon").live('click', function() {
		var comment_id = jQuery(this).attr("id");
		jQuery("#comment_" + comment_id).slideUp("slow");
		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/lounge_functions.php",
			type: "POST",
			data: ({
				action: 'delete_comment',
				comment_id: comment_id
			}),
			dataType: "html",
			success: function(str) {
				if (str != "OK") {
					jQuery("#dialog").html(str).dialog({
						title: __wps__.site_title + ' debug info',
						width: 800,
						height: 500,
						modal: true
					});
				}
			},
			error: function(xhr, ajaxOptions, thrownError) {
				if (show_js_errors) {
					alert(xhr.status);
					alert(xhr.statusText);
					alert(thrownError);
				}
			}

		});
	});




	function add_comment_to_lounge() {

		var new_comment = jQuery('#__wps__lounge_add_comment').val();

		if (jQuery('#__wps__lounge_add_comment').val() != 'Add a comment..') {
			var items = '';
			items += '<div id="__wps__lounge_comment">';
			items += '<div class="__wps__lounge_new_comment __wps__lounge_new_comment_you">' + new_comment + '</div>';
			items += '<div class="__wps__lounge_new_status">';
			items += '<img style="float: left" src="' + __wps__.images_url + '/online.gif">';
			items += '</div>';
			items += '<div class="__wps__lounge_new_author">';
			items += 'You';
			items += '</div>';
			jQuery(items).prependTo('#__wps__lounge_div');
			jQuery('#__wps__lounge_add_comment').val('');
		}

		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/lounge_functions.php",
			type: "POST",
			data: ({
				action: 'add_comment',
				comment: new_comment
			}),
			dataType: "html",
			success: function(str) {},
			error: function(xhr, ajaxOptions, thrownError) {
				if (show_js_errors) {
					alert(xhr.status);
					alert(xhr.statusText);
					alert(thrownError);
				}
			}

		});
	}

	function lounge_polling() {

		if (jQuery("#__wps__lounge_div").length) {

			jQuery.ajax({
				url: __wps__.plugin_url + "ajax/lounge_functions.php",
				type: "POST",
				data: ({
					action: 'get_comments',
					inactive: __wps__.inactive,
					offline: __wps__.offline
				}),
				dataType: "html",
				success: function(str) {

					// AJAX function return JSON array of comments to create HTML
					var items = "";
					var rows = jQuery.parseJSON(str);

					jQuery.each(rows, function(i, row) {
						items += '<div id="comment_' + row.lid + '" class="__wps__lounge_comment">';
						if (__wps__.current_user_level == 5) {
							var del_icon = "<a title='Delete' id='" + row.lid + "' href='javascript:void(0);' class='__wps__lounge_del_icon'><img src='" + __wps__.images_url + "/delete.png' style='width:16px;height:16px' /></a>";
							items += del_icon;
						}

						items += '<div class="__wps__lounge_new_comment';
						if (row.author_id == __wps__.current_user_id) {
							items += ' __wps__lounge_new_comment_you';
						}
						items += '">' + row.comment + '</div>';
						items += '<div class="__wps__lounge_new_status">';
						items += '<img style="float: left" src="' + __wps__.images_url + '/' + row.status + '.gif">';
						items += '</div>';
						items += '<div class="__wps__lounge_new_author">';
						items += row.author + ' ' + row.added;
						items += '</div>';
						items += '</div>';
					});
					jQuery('#__wps__lounge_div').html(items);

				},
				error: function(xhr, ajaxOptions, thrownError) {
					if (show_js_errors) {
						alert(xhr.status);
						alert(xhr.statusText);
						alert(thrownError);
					}
				}

			});
		}

		// Repeat check every 5 seconds
		setTimeout(lounge_polling, 5000);
	}


/*
	   +------------------------------------------------------------------------------------------+
	   |                                       RSS FEED                                           |
	   +------------------------------------------------------------------------------------------+
	*/


	jQuery("#__wps__rss_icon").live('click', function() {
		var str = "Use the following to receive an RSS feed of this member's activity:";
		str += '<br /><input type="text" style="width:650px;" value="' + __wps__.plugin_url + 'activity.php?uid=' + __wps__.current_user_page + '" />';
		str += '<br /><a href=' + __wps__.plugin_url + 'activity.php?uid=' + __wps__.current_user_page + ' target="_blank">View</a>';
		jQuery("#dialog").html(str);
		jQuery("#dialog").dialog({
			title: __wps__.site_title,
			width: 700,
			height: 175,
			modal: true,
			buttons: {}
		});

	});


/*
	   +------------------------------------------------------------------------------------------+
	   |                                        WIDGET: VOTE                                      |
	   +------------------------------------------------------------------------------------------+
*/

	if (jQuery(".symposium_answer").length) {
		jQuery(".symposium_answer").click(function() {

			var vote_answer = jQuery(this).attr("title");
			jQuery("#__wps__vote_thankyou").slideDown("fast").effect("highlight", {}, 3000);

			jQuery.ajax({
				url: __wps__.plugin_url + "ajax/widget_functions.php",
				type: "POST",
				data: ({
					action: "doVote",
					vote: vote_answer
				}),
				dataType: "html",
				async: true,
				success: function(str) {},
				error: function(xhr, ajaxOptions, thrownError) {
					if (show_js_errors) {
						alert(xhr.status);
						alert(xhr.statusText);
						alert(thrownError);
					}
				}
			});
			jQuery(".__wps__pleasewait").fadeOut("slow");
		});
	}

	if (jQuery("#__wps__chartcontainer").length) {

		var yes = parseFloat(jQuery('#__wps__chart_yes').html());
		var no = parseFloat(jQuery('#symposium_chart_no').html());

		if (jQuery("#symposium_chart_counts").html() != 'on') {
			if (yes > 0) {
				if (no > 0) {
					yes = Math.floor(yes / (yes + no) * 100);
					no = 100 - yes;
				} else {
					yes = 100;
					no = 0;
				}
			} else {
				yes = 0;
				if (no > 0) {
					no = 100;
				} else {
					no = 0;
				}
			}
		}

		var myData = new Array(['Yes', yes], ['No', no]);
		var bar_type = 'bar';
		if (jQuery("#symposium_chart_type").html() != '') {
			bar_type = jQuery("#symposium_chart_type").html();
		}
		var myChart = new JSChart('__wps__chartcontainer', bar_type, jQuery('#symposium_chart_key').html());
		myChart.setDataArray(myData);
		var myColors = new Array('#09f', '#06a')
		if (bar_type == 'bar') {
			myChart.colorizeBars(myColors);
		} else {
			myChart.colorizePie(myColors);
		}
		myChart.setSize(200, 200);
		myChart.setTitleFontSize(14);
		myChart.setTitle("");
		myChart.setAxisNameX("");
		myChart.setAxisNameY("");
		myChart.setAxisPaddingTop(15);
		myChart.setAxisPaddingBottom(15);
		myChart.setAxisPaddingLeft(0);
		if (jQuery("#symposium_chart_counts").html() != 'on') {
			if (bar_type == 'bar') {
				myChart.setBarValuesSuffix('%');
			} else {
				myChart.setPieValuesSuffix('%');
			}
		}
		myChart.draw();

	}


/*
	   +------------------------------------------------------------------------------------------+
	   |                                          GROUP                                           |
	   +------------------------------------------------------------------------------------------+
	*/

	// Menu choices
	jQuery(".__wps__group_menu").live('click', function() {
		
		// Check if using horizontal menu (tabs)
		if (jQuery(".__wps__top_menu").length) {
			jQuery('.__wps__top_menu').removeClass('__wps__dropdown_tab_on').addClass('__wps__dropdown_tab_off');
			jQuery(this).closest('.__wps__top_menu').removeClass('__wps__dropdown_tab_off').addClass('__wps__dropdown_tab_on');
			
			jQuery(this).parent().hide();
		}

		jQuery('.__wps__group_menu').removeClass('__wps__profile_current');
		jQuery(this).addClass('__wps__profile_current');

		
		var menu_id = jQuery(this).attr("id");

		if (menu_id == 'group_menu_all') {
			window.location.href = __wps__.groups_url;
		} else {

			jQuery('#group_body').html("<img src='" + __wps__.images_url + "/busy.gif' />");

			if (menu_id != 'group_menu_forum') {

				jQuery.ajax({
					url: __wps__.plugin_url + "ajax/group_functions.php",
					type: "POST",
					data: ({
						action: menu_id,
						post: '',
						limit_from: 0,
						uid1: __wps__.current_group
					}),
					dataType: "html",
					success: function(str) {
						jQuery('#group_body').html(str);

						var user_login = jQuery("#symposium_user_login").html();
						var user_email = jQuery("#symposium_user_email").html();

						// Set up auto-expanding textboxes
						if (jQuery(".elastic").length) {
							jQuery('.elastic').elastic();
						}

						// Adjust width of textboxes (for background image)
						if (jQuery('#__wps__group_comment').length) {
							var w = jQuery('#__wps__group_comment').css('width').replace(/px/g, "");
							jQuery('#__wps__group_comment').css('width', (w-30)+'px');
						}
						if (jQuery('.__wps__group_reply').length) {
							var w = jQuery('.__wps__group_reply').css('width').replace(/px/g, "");
							jQuery('.__wps__group_reply').css('width', (w-30)+'px');
						}
						
						// File upload (for group avatar)
						__wps__init_file_upload();

					}
				});

			} else {

				jQuery.ajax({
					url: __wps__.plugin_url + "ajax/forum_functions.php",
					type: "POST",
					data: ({
						action: 'getForum',
						limit_from: 0,
						cat_id: __wps__.cat_id,
						topic_id: __wps__.show_tid,
						group_id: __wps__.current_group
					}),
					dataType: "html",
					async: true,
					success: function(str) {

						str = trim(str);

						if (strpos(str, "[|]", 0)) {
							var details = str.split("[|]");
							jQuery(document).attr('title', details[0]);
							str = details[1];
						}

						jQuery("#group_body").html(str);

						var user_login = jQuery("#symposium_user_login").html();
						var user_email = jQuery("#symposium_user_email").html();

						// File upload
						__wps__init_file_upload();

						// Set up auto-expanding textboxes
						if (jQuery(".elastic").length) {
							jQuery('.elastic').elastic();
						}

						// Init TinyMCE
						tiny_mce_init('new_topic_text');

					},
					error: function(xhr, ajaxOptions, thrownError) {
						if (show_js_errors) {
							alert(xhr.status);
							alert(xhr.statusText);
							alert(thrownError);
						}
					}
				});

			}
		}

	});
	
	// Edit group about page
	jQuery("#__wps__about_group_edit").live('click', function() {

		jQuery("#dialog").html("<img src='" + __wps__.images_url + "/busy.gif' />").dialog({
			title: __wps__.site_title,
			width: 850,
			height: 550,
			modal: true,
			buttons: {
				"Update": function() {
					jQuery(".__wps__notice").inmiddle().show();
					var message = tinyMCE.get('__wps__about_group_edit_textarea').getContent();
					jQuery("#__wps__group_about_page").html(message);
					jQuery(this).dialog("close");

					jQuery.ajax({
						url: __wps__.plugin_url + "ajax/group_functions.php",
						type: "POST",
						data: ({
							action: "update_welcome_message",
							'message': message,
							'gid': __wps__.current_group
						}),
						dataType: "html",
						async: true,
						success: function(str) {
							if (str != 'OK') jQuery("#dialog").html(str).dialog({ title: __wps__.site_title+' debug info', width: 800, height: 500, modal: true });
							jQuery(".__wps__notice").fadeOut("fast");
						},
						error: function(xhr, ajaxOptions, thrownError) {
							if (show_js_errors) {
								alert(xhr.status);
								alert(xhr.statusText);
								alert(thrownError);
							}
						}
					});
				},
				"Cancel": function() {
					jQuery("#dialog").dialog('close');
				}
			}
		});	
		
		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/group_functions.php",
			type: "POST",
			data: ({
				action: 'group_menu_about_edit',
				group_id: __wps__.current_group
			}),
			dataType: "html",
			async: true,
			success: function(str) {
				jQuery("#dialog").html(str);
				tiny_mce_init('__wps__about_group_edit_textarea');
			}
		});
		
	});

	function showPreview(coords) {
		var rx = 100 / coords.w;
		var ry = 100 / coords.h;

		jQuery('#x').val(coords.x);
		jQuery('#y').val(coords.y);
		jQuery('#x2').val(coords.x2);
		jQuery('#y2').val(coords.y2);
		jQuery('#w').val(coords.w);
		jQuery('#h').val(coords.h);

		jQuery('#profile_preview').css({
			width: Math.round(rx * jQuery('#profile_jcrop_target').width()) + 'px',
			height: Math.round(ry * jQuery('#profile_jcrop_target').height()) + 'px',
			marginLeft: '-' + Math.round(rx * coords.x) + 'px',
			marginTop: '-' + Math.round(ry * coords.y) + 'px'
		});
	};
	
	// Act on "view" parameter on first page load (forum or activity post)
	if ( (__wps__.current_group > 0) && ((__wps__.cat_id != '') && (__wps__.cat_id)) || ((__wps__.post != '') && (__wps__.current_group > 0)) ) {

		if (__wps__.post != '') {

			// Activity parameter passed, show activity
			
			jQuery.ajax({
				url: __wps__.plugin_url + "ajax/group_functions.php",
				type: "POST",
				data: ({
					action: 'group_menu_wall',
					post: __wps__.post,
					limit_from: 0,
					uid1: __wps__.current_group
				}),
				dataType: "html",
				success: function(str) {
					jQuery('#group_body').html(str);
	
					// Set up auto-expanding textboxes
					if (jQuery(".elastic").length) {
						jQuery('.elastic').elastic();
					}

					// Adjust width of textboxes (for background image)
					if (jQuery('#__wps__group_comment').length) {
						var w = jQuery('#__wps__group_comment').css('width').replace(/px/g, "");
						jQuery('#__wps__group_comment').css('width', (w-30)+'px');
					}
					if (jQuery('.__wps__group_reply').length) {
						var w = jQuery('.__wps__group_reply').css('width').replace(/px/g, "");
						jQuery('.__wps__group_reply').css('width', (w-30)+'px');
					}	
	
				}
			});
					
		} else {
			
		  // Forum parameter passed, show forum
		  
		  var sub = "getForum";
		  if (__wps__.show_tid > 0) {
			  var sub = "getTopic";
		  }

		  jQuery.ajax({
			  url: __wps__.plugin_url + "ajax/forum_functions.php",
			  type: "POST",
			  data: ({
				  action: sub,
				  limit_from: 0,
				  cat_id: __wps__.cat_id,
				  topic_id: __wps__.show_tid,
				  group_id: __wps__.current_group
			  }),
			  dataType: "html",
			  async: true,
			  success: function(str) {
				  if (str != 'DONTSHOW') {
					  str = trim(str);
  
					  if (strpos(str, "[|]", 0)) {
						  var details = str.split("[|]");
						  jQuery(document).attr('title', details[0]);
						  str = details[1];
					  }
  
					  jQuery("#group_body").html(str);
  
					  var user_login = jQuery("#symposium_user_login").html();
					  var user_email = jQuery("#symposium_user_email").html();

					  // File upload
					  __wps__init_file_upload();
  
					  // Set up auto-expanding textboxes
					  if (jQuery(".elastic").length) {
						  jQuery('.elastic').elastic();
					  }
  
					  // Init TinyMCE
					  if (sub == 'getForum') {
						  tiny_mce_init('new_topic_text');
					  } else {
						  tiny_mce_init('__wps__reply_text');
					  }
 
				  }
  
			  },
			  error: function(xhr, ajaxOptions, thrownError) {
				  if (show_js_errors) {
					  alert(xhr.status);
					  alert(xhr.statusText);
					  alert(thrownError);
				  }
			  }
  
		  });
		
		}
		
	} else {
		
		// Load defaut page
		if (jQuery("#force_group_page").length) {

			if (jQuery("#force_group_page").html() != '') {

				var menu_id = 'group_menu_' + jQuery('#force_group_page').html();

				if (menu_id == 'group_menu_about') {
					
					// Default to about page
					jQuery.ajax({
						url: __wps__.plugin_url + "ajax/group_functions.php",
						type: "POST",
						data: ({
							action: 'group_menu_about',
							uid1: __wps__.current_group
						}),
						dataType: "html",
						success: function(str) {
							jQuery('#group_body').html(str);	
				
							// Init TinyMCE
							tiny_mce_init('__wps__about_group_edit_div');

						}
					});

										
				}
				
				if (menu_id == 'group_menu_activity') {
					
					// Default to activity

					jQuery.ajax({
						url: __wps__.plugin_url + "ajax/group_functions.php",
						type: "POST",
						data: ({
							action: 'group_menu_wall',
							post: '',
							limit_from: 0,
							uid1: __wps__.current_group
						}),
						dataType: "html",
						success: function(str) {
							jQuery('#group_body').html(str);
	
							// Set up auto-expanding textboxes
							if (jQuery(".elastic").length) {
								jQuery('.elastic').elastic();
							}

							// Adjust width of textboxes (for background image)
							if (jQuery('#__wps__group_comment').length) {
								var w = jQuery('#__wps__group_comment').css('width').replace(/px/g, "");
								jQuery('#__wps__group_comment').css('width', (w-30)+'px');
							}
							if (jQuery('.__wps__group_reply').length) {
								var w = jQuery('.__wps__group_reply').css('width').replace(/px/g, "");
								jQuery('.__wps__group_reply').css('width', (w-30)+'px');
							}	
						}
					});
				
				}
				
				if (menu_id == 'group_menu_forum') {

					// Default to forum
					
					jQuery.ajax({
						url: __wps__.plugin_url + "ajax/forum_functions.php",
						type: "POST",
						data: ({
							action: 'getForum',
							limit_from: 0,
							cat_id: __wps__.cat_id,
							topic_id: __wps__.show_tid,
							group_id: __wps__.current_group
						}),
						dataType: "html",
						async: true,
						success: function(str) {
	
							str = trim(str);
	
							if (strpos(str, "[|]", 0)) {
								var details = str.split("[|]");
								jQuery(document).attr('title', details[0]);
								str = details[1];
							}
	
							jQuery("#group_body").html(str);
	
							var user_login = jQuery("#symposium_user_login").html();
							var user_email = jQuery("#symposium_user_email").html();
	
						    // File upload
						    __wps__init_file_upload();
	
							// Set up auto-expanding textboxes
							if (jQuery(".elastic").length) {
								jQuery('.elastic').elastic();
							}
	
							// Init TinyMCE
							tiny_mce_init('new_topic_text');
	
						},
						error: function(xhr, ajaxOptions, thrownError) {
							if (show_js_errors) {
								alert(xhr.status);
								alert(xhr.statusText);
								alert(thrownError);
							}
						}
					});
							
					
				}

			}
		}
	}

	// Delete group member
	jQuery(".delete_group_member").live('click', function() {

		if (confirm("Are you sure?")) {

			var id = jQuery(this).attr("title");
			jQuery(this).parent().parent().slideUp("slow");

			jQuery.ajax({
				url: __wps__.plugin_url + "ajax/group_functions.php",
				type: "POST",
				data: ({
					action: "member_delete",
					group_id: __wps__.current_group,
					id: id
				}),
				dataType: "html",
				success: function(str) {}
			});

		}
	});


	// Clicked on show more...
	jQuery("#showmore_group_wall").live('click', function() {

		var limit_from = jQuery(this).attr("title");
		jQuery('#showmore_group_wall').html("<img src='" + __wps__.images_url + "/busy.gif' />");

		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/group_functions.php",
			type: "POST",
			data: ({
				action: menu_id,
				post: '',
				limit_from: limit_from,
				uid1: __wps__.current_group,
				uid2: __wps__.current_user_id
			}),
			dataType: "html",
			success: function(str) {
				jQuery('#showmore_group_wall').remove();
				jQuery(str).appendTo('#group_body').hide().slideDown("slow");

			}
		});

	});

	// new post to group wall
	jQuery("#symposium_group_add_comment").live('click', function() {
		jQuery(this).parent().hide();
		var t = this;
		setTimeout(function() {
			jQuery(t).parent().slideDown('fast');
		}, 3000);		
		symposium_add_group_comment();
	});
	jQuery('#__wps__group_comment').live('keypress', function(e) {
		if (e.keyCode == 13 && (!jQuery("#symposium_group_add_comment").length)) {
			jQuery(this).parent().hide();
			var t = this;
			setTimeout(function() {
				jQuery(t).parent().slideDown('fast');
			}, 3000);
			symposium_add_group_comment();
		}
	});

	function symposium_add_group_comment() {

		var comment_text = jQuery("#__wps__group_comment").val();
		jQuery("#__wps__group_comment").val('');

		var comment = "<div class='add_wall_post_div' style='"
		if (__wps__.row_border_size != '') {
			comment = comment + " border-top:" + __wps__.row_border_size + "px " + __wps__.row_border_style + " " + __wps__.text_color_2 + ";";
		}
		comment = comment + "'>";
		comment = comment + "<div class='add_wall_post'>";
		comment = comment + "<div class='add_wall_post_text'>";
		var q = symposium_q(__wps__.profile_url);
		comment = comment + '<a href="' + __wps__.profile_url + q + 'uid=' + __wps__.current_user_id + '">';
		comment = comment + __wps__.current_user_display_name + '</a><br />';

		comment_text = comment_text.replace(/\n/g, '<br>');

		comment = comment + comment_text;
		comment = comment + "</div>";
		comment = comment + "</div>";
		comment = comment + "<div class='add_wall_post_avatar'>";
		comment = comment + "<img src='" + jQuery('#__wps__current_user_avatar img:first').attr('src') + "' style='width:64px; height:64px' />";
		comment = comment + "</div>";

		jQuery("#__wps__comment").val('');
		//jQuery(comment).prependTo('#__wps__wall');
		jQuery(comment).insertAfter('#symposium_add_status').hide().slideDown('fast');
		

		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/group_functions.php",
			type: "POST",
			data: ({
				action: "group_addStatus",
				subject_uid: __wps__.current_group,
				author_uid: __wps__.current_user_id,
				parent: 0,
				text: comment_text
			}),
			dataType: "html",
			async: true
		});
	}

	// new reply
	jQuery(".reply_field-button").live('click', function() {
		jQuery(this).parent().hide();
		var t = this;
		setTimeout(function() {
			jQuery(t).parent().slideDown('fast');
		}, 3000);
		symposium_add_group_reply(this);
	});
	jQuery('.__wps__group_reply').live('keypress', function(e) {
		if (e.keyCode == 13 && (!jQuery(".reply_field-button").length)) {
			jQuery(this).parent().hide();
			var t = this;
			setTimeout(function() {
				jQuery(t).parent().slideDown('fast');
			}, 3000);
			symposium_add_group_reply(this);
		}
	});

	function symposium_add_group_reply(t) {

		var comment_id = jQuery(t).attr("title");
		var author_id = jQuery('#symposium_author_' + comment_id).val();
		var comment_text = jQuery("#__wps__reply_" + comment_id).val();

		jQuery("#__wps__reply_" + comment_id).val('');

		var comment = "<div class='reply_div'>";
		comment = comment + "<div class='__wps__wall_reply_div'";
		if (__wps__.bg_color_2 != '') {
			comment = comment + " style='background-color:" + __wps__.bg_color_2 + "'";
		}
		comment = comment + ">";
		comment = comment + "<div class='wall_reply'>";
		var q = symposium_q(__wps__.profile_url);
		comment = comment + '<a href="' + __wps__.profile_url + q + 'uid=' + __wps__.current_user_id + '">';
		comment = comment + __wps__.current_user_display_name + '</a><br />';
		comment_text = comment_text.replace(/\n/g, '<br>');
		comment = comment + comment_text;
		comment = comment + "</div>";
		comment = comment + "</div>";
		comment = comment + "<div class='wall_reply_avatar'>";
		comment = comment + "<img src='" + jQuery('#__wps__current_user_avatar img:first').attr('src') + "' style='width:40px; height:40px' />";
		comment = comment + "</div>";
		comment = comment + "</div>";

		jQuery(comment).appendTo('#__wps__comment_' + comment_id);
		jQuery("#__wps__reply_" + comment_id).val('');

		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/group_functions.php",
			type: "POST",
			data: ({
				action: "group_addComment",
				uid: __wps__.current_group,
				parent: comment_id,
				text: comment_text
			}),
			dataType: "html",
			async: true
		});
	}


	// update group settings
	jQuery("#updateGroupSettingsButton").live('click', function() {

		var complete = true;
		var groupname = jQuery("#groupname").val();
		var groupdesc = jQuery("#groupdescription").val();
		if (groupname == '') {
			jQuery("#groupname").effect("highlight", {}, 4000);
			complete = false;
		}
		if (groupdesc == '') {
			jQuery("#groupdescription").effect("highlight", {}, 4000);
			complete = false;
		}
		if (!complete) {
			jQuery("#dialog").html('Please complete group name and description').dialog({ title: __wps__.site_title, width: 300, height: 200, modal: true, buttons: { "OK": function() { jQuery(this).dialog("close"); } } });
		} else {
			
			jQuery(".__wps__notice").inmiddle().show();
	
			var is_private = '';
			if (jQuery("#private").is(":checked")) {
				is_private = 'on';
			}
	
			var content_private = '';
			if (jQuery("#content_private").is(":checked")) {
				var content_private = 'on';
			}
	
			var group_forum = '';
			if (jQuery("#group_forum").is(":checked")) {
				group_forum = 'on';
			}
	
			var default_page = jQuery("#default_page").val();
	
			var allow_new_topics = '';
			if (jQuery("#allow_new_topics").is(":checked")) {
				allow_new_topics = 'on';
			}
	
			var new_member_emails = '';
			if (jQuery("#new_member_emails").is(":checked")) {
				new_member_emails = 'on';
			}
	
			var add_alerts = '';
			if (jQuery("#add_alerts").is(":checked")) {
				add_alerts = 'on';
			}
	
			var group_admin = jQuery("#transfer_admin").val();
	
			jQuery("#group_name").html(jQuery("#groupname").val());
			jQuery("#group_description").html(jQuery("#groupdescription").val());
	
			jQuery.ajax({
				url: __wps__.plugin_url + "ajax/group_functions.php",
				type: "POST",
				data: ({
					action: "updateGroupSettings",
					gid: __wps__.current_group,
					groupname: jQuery("#groupname").val(),
					groupdescription: jQuery("#groupdescription").val(),
					is_private: is_private,
					content_private: content_private,
					group_forum: group_forum,
					default_page: default_page,
					allow_new_topics: allow_new_topics,
					max_members: jQuery('#max_members').val(),
					group_admin: group_admin,
					new_member_emails: new_member_emails,
					add_alerts: add_alerts,
					x: jQuery("#x").val(),
					y: jQuery("#y").val(),
					w: jQuery("#w").val(),
					h: jQuery("#h").val()
				}),
				dataType: "html",
				async: true,
				success: function(str) {
					location.reload();
				},
				error: function(xhr, ajaxOptions, thrownError) {
					if (show_js_errors) {
						alert(xhr.status);
						alert(xhr.statusText);
						alert(thrownError);
					}
				}
	
			});
			
		}

	});

	// Join group
	jQuery("#groups_join_button").live('click', function() {

		jQuery(".__wps__pleasewait").inmiddle().show();

		jQuery("#groups_join_button").hide();

		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/group_functions.php",
			type: "POST",
			data: ({
				action: "joinGroup",
				gid: __wps__.current_group
			}),
			dataType: "html",
			async: false,
			success: function(str) {
				if (__wps__.debug) {
					jQuery("#dialog").html(str).dialog({
						title: __wps__.site_title + ' debug info',
						width: 800,
						height: 500,
						modal: true
					});
				}
				jQuery("#groups_join_button_done").effect("highlight", {}, 3000);
				location.reload();
			}
		});

	});

	// Delete group
	jQuery("#groups_delete_button").live('click', function() {

		var answer = confirm("This cannot be un-done - are you really sure?");

		if (answer) {

			jQuery("#groups_delete_button").hide();
			jQuery("#groups_delete_button_done").effect("highlight", {}, 3000);

			jQuery(".__wps__pleasewait").inmiddle().show();

			jQuery.ajax({
				url: __wps__.plugin_url + "ajax/group_functions.php",
				type: "POST",
				data: ({
					action: "deleteGroup",
					gid: __wps__.current_group
				}),
				dataType: "html",
				async: false,
				success: function(str) {
					window.location.href = __wps__.groups_url;
				}
			});

		}

	});

	// Delete group request
	jQuery("#groups_delete_button_request").live('click', function() {

		var group_id = jQuery(this).attr('title');
		var str = '<p>Why do you want to delete this group?<br />Note: this cannot be reversed!';
		str += '<br /><em>Ref: ' + group_id + '</em></p>';
		str += '<textarea id="request_text" style="width:100%; height:180px"></textarea>';
		jQuery("#dialog").html(str);
		jQuery("#dialog").dialog({
			title: __wps__.site_title,
			width: 600,
			height: 400,
			modal: true,
			buttons: {
				"Delete Group": function() {
					jQuery.ajax({
						url: __wps__.plugin_url + "ajax/group_functions.php",
						type: "POST",
						data: ({
							action: "requestDelete",
							request_text: jQuery('#request_text').val(),
							group_id: group_id
						}),
						dataType: "html",
						async: true,
						success: function(str) {
							jQuery("#dialog").html('Your request for this group to be deleted has been sent to the site administrator.');
							jQuery("#dialog").dialog({
								title: __wps__.site_title,
								width: 650,
								height: 150,
								modal: true,
								buttons: {}
							});
						},
						error: function(xhr, ajaxOptions, thrownError) {
							if (show_js_errors) {
								alert(xhr.status);
								alert(xhr.statusText);
								alert(thrownError);
							}
						}
					});
					jQuery(this).dialog("close");
				},
				"Cancel": function() {
					jQuery(this).dialog("close");
				}
			}
		});

	});

	// Leave group
	jQuery("#groups_leave_button").live('click', function() {

		if (confirm(areyousure)) {

			jQuery(".__wps__pleasewait").inmiddle().show();
			jQuery("#groups_leave_button").hide();

			jQuery.ajax({
				url: __wps__.plugin_url + "ajax/group_functions.php",
				type: "POST",
				data: ({
					action: "leaveGroup",
					gid: __wps__.current_group
				}),
				dataType: "html",
				async: false,
				success: function(str) {
					jQuery("#groups_leave_button_done").effect("highlight", {}, 3000);
					location.reload();
				}

			});
		}

	});

	// Subscribe/unsubscribe
	jQuery("#group_notify").live('click', function() {
		jQuery(".__wps__notice").inmiddle().show();

		if (jQuery("#group_notify").is(":checked")) {
			var group_notify = 'on';
		} else {
			var group_notify = '';
		}

		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/group_functions.php",
			type: "POST",
			data: ({
				action: "group_subscribe",
				notify: group_notify,
				gid: __wps__.current_group
			}),
			dataType: "html",
			async: true,
			success: function(str) {
				jQuery(".__wps__notice").fadeOut("slow");
			}
		});
	});

	// reject a group request
	jQuery("#rejectgrouprequest").live('click', function() {
		jQuery(".__wps__notice").inmiddle().show();

		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/group_functions.php",
			type: "POST",
			data: ({
				action: "rejectGroup",
				uid: jQuery(this).attr("title"),
				gid: __wps__.current_group
			}),
			dataType: "html",
			async: true,
			success: function(str) {
				if (str != 'NOT LOGGED IN') {
					jQuery("#request_" + str).slideUp("slow");
				}
				jQuery(".__wps__notice").fadeOut("slow");
			},
			error: function(xhr, ajaxOptions, thrownError) {
				if (show_js_errors) {
					alert(xhr.status);
					alert(xhr.statusText);
					alert(thrownError);
				}
			}

		});

	});

	// accept a group request
	jQuery("#acceptgrouprequest").live('click', function() {
		jQuery(".__wps__notice").inmiddle().show();

		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/group_functions.php",
			type: "POST",
			data: ({
				action: "acceptGroup",
				uid: jQuery(this).attr("title"),
				gid: __wps__.current_group
			}),
			dataType: "html",
			async: true,
			success: function(str) {
				if (str != 'NOT LOGGED IN') {
					jQuery("#request_" + str).slideUp("slow");
				}
				jQuery(".__wps__notice").fadeOut("slow");
			},
			error: function(xhr, ajaxOptions, thrownError) {
				if (show_js_errors) {
					alert(xhr.status);
					alert(xhr.statusText);
					alert(thrownError);
				}
			}

		});

	});

/*
	   +------------------------------------------------------------------------------------------+
	   |                                          GROUPS                                          |
	   +------------------------------------------------------------------------------------------+
	*/

	if (jQuery("input#__wps__group").length) {

		jQuery("input#__wps__group").autocomplete({
			source: __wps__.plugin_url + "ajax/groups_functions.php",
			minLength: 3,
			focus: function(event, ui) {
				jQuery("input#group").val(ui.item.name);
				jQuery("input#group_id").val(ui.item.value);
				return false;
			},
			select: function(event, ui) {
				jQuery("input#group").val(ui.item.name);
				jQuery("input#group_id").val(ui.item.value);
				var q = symposium_q(__wps__.group_url);
				window.location.href = __wps__.group_url + q + 'gid=' + ui.item.value;
				return false;
			}
		}).data("uiAutocomplete")._renderItem = function(ul, item) {
			var group = "<a>";
			group += "<div style='height:40px; overflow:hidden'>";
			group += "<div style=\'float:left; background-color:#fff; margin-right: 8px; width:40px; height:40px; \'>";
			group += item.avatar;
			group += "</div>";
			group += "<div>" + item.name + "</div>";
			group += "<br style='clear:both' />";
			group += "</div>";
			group += "</a>";
			return jQuery("<li></li>").data("item.autocomplete", item).append(group).appendTo(ul);
		};
		
		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/groups_functions.php",
			type: "POST",
			data: ({
				action: "getGroups",
				page: 1,
				me: __wps__.current_user_id
			}),
			dataType: "html",
			async: true,
			success: function(str) {
				jQuery("#__wps__groups").html(str);
			},
			error: function(xhr, ajaxOptions, thrownError) {
				if (show_js_errors) {
					alert(xhr.status);
					alert(xhr.statusText);
					alert(thrownError);
				}
			}

		});
	};

	jQuery("#groups_go_button").click(function() {

		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/groups_functions.php",
			type: "POST",
			data: ({
				action: "getGroups",
				page: 1,
				me: __wps__.current_user_id,
				term: jQuery("#__wps__group").val()
			}),
			dataType: "html",
			async: true,
			success: function(str) {
				jQuery("#__wps__groups").html(str);
			},
			error: function(xhr, ajaxOptions, thrownError) {
				if (show_js_errors) {
					alert(xhr.status);
					alert(xhr.statusText);
					alert(thrownError);
				}
			}

		});
		
	});


	jQuery("#show_create_group_button").click(function() {
		jQuery("#show_create_group_button").hide();
		jQuery("#groups_results").hide();
		jQuery("#create_group_form").fadeIn("slow");
	});

	jQuery("#cancel_create_group_button").click(function() {
		jQuery("#show_create_group_button").show();
		jQuery("#create_group_form").hide();
		jQuery("#groups_results").fadeIn("slow");
	});

	jQuery("#create_group_button").click(function() {

		var name_of_group = jQuery('#name_of_group').val();
		var description_of_group = jQuery('#description_of_group').val();

		if (name_of_group != '') {

			jQuery.ajax({
				url: __wps__.plugin_url + "ajax/groups_functions.php",
				type: "POST",
				data: ({
					action: "createGroup",
					me: __wps__.current_user_id,
					name_of_group: name_of_group,
					description_of_group: description_of_group
				}),
				dataType: "html",
				async: true,
				success: function(str) {
					var q = symposium_q(__wps__.group_url);
					window.location.href = __wps__.group_url + q + 'gid=' + trim(str);
				},
				error: function(xhr, ajaxOptions, thrownError) {
					if (show_js_errors) {
						alert(xhr.status);
						alert(xhr.statusText);
						alert(thrownError);
					}
				}
			});

		}

	});


	jQuery("#symposium_group_invites_button").live('click', function() {

		jQuery(".__wps__pleasewait").inmiddle().show();

		var emails = jQuery('#symposium_group_invites').val();

		jQuery.ajax({
			url: __wps__.plugin_url + "ajax/group_functions.php",
			type: "POST",
			data: ({
				action: "sendInvites",
				emails: emails,
				group_id: __wps__.current_group
			}),
			dataType: "html",
			async: true,
			success: function(str) {
				jQuery(".__wps__pleasewait").hide();
				jQuery('#symposium_group_invites_button').hide();
				jQuery('#symposium_group_invites').hide();
				jQuery('#symposium_group_invites_sent').html(str).show();
			},
			error: function(xhr, ajaxOptions, thrownError) {
				if (show_js_errors) {
					alert(xhr.status);
					alert(xhr.statusText);
					alert(thrownError);
				}
			}

		});
	});


});

/*
	   +------------------------------------------------------------------------------------------+
	   |                                     SHARED FUNCTIONS                                     |
	   +------------------------------------------------------------------------------------------+
	*/

function trim(s) {
	var l = 0;
	var r = s.length - 1;
	while (l < s.length && s[l] == ' ') {
		l++;
	}
	while (r > l && s[r] == ' ') {
		r -= 1;
	}
	return s.substring(l, r + 1);
}

// sort out ? or & in WPS link
function symposium_q(u) {

	var q = '?';
	if (u.indexOf('?') > 0) {
		q = '&';
	}
	return q;
}


/*
	   +------------------------------------------------------------------------------------------+
	   |                                     EXTERNAL SCRIPTS                                     |
	   +------------------------------------------------------------------------------------------+
	*/

/**
 *	@name							Elastic
 *	@descripton						Elastic is Jquery plugin that grow and shrink your textareas automaticliy
 *	@version						1.6.4
 *	@requires						Jquery 1.2.6+
 *
 *	@author							Jan Jarfalk
 *	@author-email					jan.jarfalk@unwrongest.com
 *	@author-website					http://www.unwrongest.com
 *
 *	@licens							MIT License - http://www.opensource.org/licenses/mit-license.php
 */

(function(jQuery) {

	jQuery.fn.extend({
		elastic: function() {

			//	We will create a div clone of the textarea
			//	by copying these attributes from the textarea to the div.
			var mimics = ['paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft', 'fontSize', 'lineHeight', 'fontFamily', 'width', 'fontWeight'];

			return this.each(function() {

				// Elastic only works on textareas
				if (this.type != 'textarea') {
					return false;
				}

				var $textarea = jQuery(this),
					$twin = jQuery('<div />').css({
						'position': 'absolute',
						'display': 'none',
						'word-wrap': 'break-word'
					}),
					lineHeight = parseInt($textarea.css('line-height'), 10) || parseInt($textarea.css('font-size'), '10'),
					minheight = parseInt($textarea.css('height'), 10) || lineHeight * 3,
					maxheight = parseInt($textarea.css('max-height'), 10) || Number.MAX_VALUE,
					goalheight = 0,
					i = 0;

				// Opera returns max-height of -1 if not set
				if (maxheight < 0) {
					maxheight = Number.MAX_VALUE;
				}

				// Append the twin to the DOM
				// We are going to meassure the height of this, not the textarea.
				$twin.appendTo($textarea.parent());

				// Copy the essential styles (mimics) from the textarea to the twin
				var i = mimics.length;
				while (i--) {
					$twin.css(mimics[i].toString(), $textarea.css(mimics[i].toString()));
				}


				// Sets a given height and overflow state on the textarea


				function setHeightAndOverflow(height, overflow) {
					curratedHeight = Math.floor(parseInt(height, 10));
					if ($textarea.height() != curratedHeight) {
						$textarea.css({
							'height': curratedHeight + 'px',
							'overflow': overflow
						});

					}
				}


				// This function will update the height of the textarea if necessary 


				function update() {

					// Get curated content from the textarea.
					var textareaContent = $textarea.val().replace(/&/g, '&amp;').replace(/  /g, '&nbsp;').replace(/<|>/g, '&gt;').replace(/\n/g, '<br />');

					var twinContent = $twin.html();

					if (textareaContent + '&nbsp;' != twinContent) {

						// Add an extra white space so new rows are added when you are at the end of a row.
						$twin.html(textareaContent + '&nbsp;');

						// Change textarea height if twin plus the height of one line differs more than 3 pixel from textarea height
						if (Math.abs($twin.height() + lineHeight - $textarea.height()) > 3) {

							var goalheight = $twin.height() + lineHeight;
							if (goalheight >= maxheight) {
								setHeightAndOverflow(maxheight, 'auto');
							} else if (goalheight <= minheight) {
								setHeightAndOverflow(minheight, 'hidden');
							} else {
								setHeightAndOverflow(goalheight, 'hidden');
							}

						}

					}

				}

				// Hide scrollbars
				$textarea.css({
					'overflow': 'hidden'
				});

				// Update textarea size on keyup
				$textarea.keyup(function() {
					update();
				});

				// And this line is to catch the browser paste event
				$textarea.live('input paste', function(e) {
					setTimeout(update, 250);
				});

				// Run update once when elastic is initialized
				update();

			});

		}
	});
})(jQuery);

/* FILE UPLOAD */
function __wps__init_file_upload() {

//	if (jQuery('#__wps__fileupload').length) {
		
	    // Initialize the jQuery File Upload widget:
	    jQuery('#__wps__fileupload').fileupload({
	        // Uncomment the following to send cross-domain cookies:
	        //xhrFields: {withCredentials: true},
	        url: __wps__.plugin_url+'/server/php/',
	        autoUpload: true,
	        maxNumberOfFiles: 100,
	        sequentialUploads: true
	    });
	
	
	    // Enable iframe cross-domain access via redirect option:
	    jQuery('#__wps__fileupload').fileupload(
	        'option',
	        'redirect',
	        window.location.href.replace(
	            /\/[^\/]*$/,
	            '/cors/result.html?%s'
	        )
	    );
	
	    // Load existing files:
	    jQuery('#__wps__fileupload').fileupload('option', {
	        maxFileSize: 50000000,
	        acceptFileTypes: /(\.|\/)(pdf|txt|zip|rar|mp4|mp3|avi|mpg|mpeg|wmv|mov|vob|gif|jpe?g|png|tif|tiff|doc|docx|xls|xlsx|ppt|pptx|)$/i,
	        process: [
	            {
	                action: 'load',
	                fileTypes: /^image\/(gif|jpeg|png)$/,
	                maxFileSize: 20000000 // 20MB
	            },
	            {
	                action: 'resize',
	                maxWidth: 640,
	                maxHeight: 800
	            },
	            {
	                action: 'save'
	            }
	        ]
	    });

		jQuery('#__wps__fileupload').bind('fileuploadstop', function (e) {
			if (jQuery('#uploader_ver').val() == 'gallery') {
				jQuery(".__wps__pleasewait").inmiddle().show();
				var q = symposium_q(__wps__.profile_url);
				var href = __wps__.profile_url + q + 'uid=' + __wps__.current_user_id + '&view=gallery';
				window.location.href = href;
			}
		});
	
		jQuery('#__wps__fileupload').bind('fileuploaddone', function (e, data) { 
			
			ver = jQuery('#uploader_ver').val();
			
			jQuery('#btn-span-tmp').html(jQuery('#btn-span').html());
			jQuery('#activity_file_upload_file').html(data.files[0].name);
			if (jQuery('#fileupload-info-label').html() != '')
				jQuery('#fileupload-info').html(data.files[0].name+' '+jQuery('#fileupload-info-label').html());
	
			jQuery.ajax({
				url: __wps__.plugin_url + 'server/upload_'+ver+'.php',
				type: "POST",
				data: ({
					action: "after_upload_complete",
					uid: __wps__.current_user_page,
					user_id: __wps__.current_user_id,
					user_login: jQuery("#symposium_user_login").html(),
					user_email: jQuery("#symposium_user_email").html(),
					uploaded_file: __wps__.wps_content_dir+'/members/'+__wps__.current_user_id+'/'+ver+'_upload/'+data.files[0].name,
					uploaded_filename: data.files[0].name,
					uploader_tid: jQuery("#uploader_tid").val(),
					uploader_gid: jQuery("#uploader_gid").val(),
					uploader_aid: jQuery("#uploader_aid").val()
				}),
				dataType: "html",
				async: false,
				success: function(str) {
					if (str.substring(0, 5) == 'Error' || str.substring(0, 6) == 'FAILED') {
						alert(str);
					} else {
						// avatar crop
						if (jQuery('#profile_image_to_crop').length) {
							jQuery('#fileupload-info').hide();
							jQuery('#btn-span').parent().hide();
							jQuery('#profile_image_to_crop').html(str);
							jQuery('#profile_jcrop_target').Jcrop({
								onChange: showProfilePreview,
								onSelect: showProfilePreview
							});
						}
						// group avatar crop
						if (jQuery('#group_image_to_crop').length) {
							jQuery('#fileupload-info').hide();
							jQuery('#btn-span').parent().hide();
							jQuery('#group_image_to_crop').html(str);
							jQuery('#profile_jcrop_target').Jcrop({
								onChange: showProfilePreview,
								onSelect: showProfilePreview
							});
						}					
						// forum list
						if (jQuery('#forum_file_list').length) {
							if (str != '') {
								jQuery('#forum_file_list').html(str);
							}
						}					
					}
				},
				error: function(xhr, ajaxOptions, thrownError) {
					alert(xhr.status+', '+xhr.statusText);
				}
	
			});
			
		});
		
//	}
	

	function showProfilePreview(coords) {
		var rx = 100 / coords.w;
		var ry = 100 / coords.h;
	
		jQuery('#x').val(coords.x);
		jQuery('#y').val(coords.y);
		jQuery('#x2').val(coords.x2);
		jQuery('#y2').val(coords.y2);
		jQuery('#w').val(coords.w);
		jQuery('#h').val(coords.h);
	
		jQuery('#profile_preview').css({
			width: Math.round(rx * jQuery('#profile_jcrop_target').width()) + 'px',
			height: Math.round(ry * jQuery('#profile_jcrop_target').height()) + 'px',
			marginLeft: '-' + Math.round(rx * coords.x) + 'px',
			marginTop: '-' + Math.round(ry * coords.y) + 'px'
		});
	};
	
}



	// CHAT FUNCTIONS

	history.navigationMode = 'compatible';	//for Opera to support unload function
	var heartBeat = __wps__.chat_polling * 1000;	//chat auto refresh time in miliseconds	
	if (heartBeat < 1000) heartBeat = 1000;
	var windowscount = 0;	
	var chatboxcount = 0;
	
	function print_to_chat(window_id,text){
		jQuery('#'+window_id+' .chat_area').append(text);
	}

	function __wps__insertPlayer(playerpath, filename){
		
			var mp3html = '<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" ';
			mp3html += 'width="1" height="1" ';
			mp3html += 'codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab">';
			mp3html += '<param name="movie" value="'+playerpath+'?';
			mp3html += 'showDownload=false&file=' + filename + '&autoStart=true';
			mp3html += '&backColor=ffffff&frontColor=ffffff';
			mp3html += '&repeatPlay=false&songVolume=50" />';
			mp3html += '<param name="wmode" value="transparent" />';
			mp3html += '<embed wmode="transparent" width="1" height="1" ';
			mp3html += 'src="' + playerpath + '?'
			mp3html += 'showDownload=false&file=' + filename + '&autoStart=true';
			mp3html += '&backColor=ffffff&frontColor=ffffff';
			mp3html += '&repeatPlay=false&songVolume=50" ';
			mp3html += 'type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" />';
			mp3html += '</object>';
			return mp3html;
			
	}
			
	function PopupChat(partner_id,partner_username,chatbox_status,__wps__first_load){
		windowscount ++;
		chatboxcount ++;
		var wctr = windowscount;
		var q = symposium_q(__wps__.profile_url);
		var href = __wps__.profile_url + q + 'uid=' + partner_id;
		
		jQuery('body').append('<div class="chatbox cb_default" id="chat_window_'+wctr+'" title="'+partner_id+'">'+
			'<div class="header header_bg_default" title="'+partner_username+'">'+
				'<p class="chat_name"><a style="float:left;" href="'+href+'">'+partner_username+'</a></p>'+
				'<a href="#" class="close_chatbox" title="close chat window"><img src="'+__wps__.images_url+'/close.png" alt="Close" /></a>'+
				'<a href="#" class="popup_chatbox" rel="'+partner_id+'" title="'+partner_username+'"><img src="'+__wps__.images_url+'/popup.gif" alt="Popup" /></a>'+
				'<a href="#" class="clear_chatbox" title="clear chat window"><img src="'+__wps__.images_url+'/delete.png" alt="Clear" /></a>'+
				'<a href="#" class="minimize_chatbox" title="minimize chat window">_</a>'+
				'<a href="#" class="maximize_chatbox" title="maximize chat window">&#8254;</a>'+
			'</div>'+
			'<div class="chat_area" title="'+partner_username+'">'+
			'</div>'+
			'<div class="chat_message" title="Type your message here">'+
				'<div class="chat_user_replying"><img src="'+__wps__.images_url+'/chat_replying.png" alt="Typing..." /></div>' +
				'<textarea></textarea>'+
			'</div>'+
		'</div>');
		if (chatbox_status == 2) {
			jQuery('#chat_window_'+wctr).css('height','0px');		
			jQuery('#chat_window_'+wctr).css('height','25px');
			jQuery('#chat_window_'+wctr+',.minimize_chatbox').css('display','none');
			jQuery('#chat_window_'+wctr+',.maximize_chatbox').css('display','inline');
		}
		var nu_w_pos = 0;
		if (chatboxcount == 1) {
			nu_w_pos = 10;
		} else {
			nu_w_pos = ((chatboxcount-1) * 213)+10;
		}	
		nu_w_pos += 202;
		jQuery('#chat_window_'+wctr).css('right',nu_w_pos+'px');
		jQuery('#chat_window_'+wctr).data('chatbox_status',chatbox_status);
		jQuery('#chat_window_'+wctr).data('partner_id',partner_id);
		jQuery('#chat_window_'+wctr).data('partner_username',partner_username);
		print_to_chat('chat_window_'+wctr,'<p class="system">'+__wps__.pleasewait+'...</p>');

		if (!__wps__first_load && __wps__.chat_sound != 'none') { // sound
			jQuery("#player_div").empty();
			jQuery("#player_div").prepend(__wps__insertPlayer(__wps__.plugin_url+'/ajax/chat/flash/player.swf', __wps__.plugin_url+'/ajax/chat/flash/'+__wps__.chat_sound));
		} 
		       
		UpdateChatWindowStatus();
		return false;
	}
	
	function UpdateChatWindowStatus(){
		var chatboxdata = [];
		jQuery('.chatbox').each(function(){
			var this_chatbox = jQuery(this);
			chatboxdata.push({ partner_id:this_chatbox.data('partner_id'),partner_username : this_chatbox.data('partner_username'),chatbox_status:this_chatbox.data('chatbox_status')});
		});	
		jQuery.ajax({
			type: "POST",
			url: __wps__.plugin_url+'ajax/chat/set_status.php',
			async: false,					
			data: ({ chatbox_status: chatboxdata }),
			//data: 'chatbox_status=1',
			success: function(i){	
			}
		});
		return false;
	}	

// TinyMCE ***************************************************************************


function tiny_mce_init(editor_id) {

	if (!__wps__.use_wp_editor) {

		if (__wps__.wps_wysiwyg == 'on' || editor_id == '__wps__about_group_edit_textarea' || editor_id == 'edit_event_text' || editor_id == 'edit_event_more') {

			var css = __wps__.wps_wysiwyg_css;
			if (editor_id == 'edit_topic_text' || editor_id == '__wps__about_group_edit_textarea') {
				// revert to default to tie in with dialog white background
				css = __wps__.plugins + "/wp-symposium/tiny_mce/themes/advanced/skins/wps.css";
			}

			var my_width = __wps__.wps_wysiwyg_width;
			var my_height = __wps__.wps_wysiwyg_height;
			var toolbar1 = __wps__.wps_wysiwyg_1;
			var toolbar2 = __wps__.wps_wysiwyg_2;
			var toolbar3 = __wps__.wps_wysiwyg_3;
			var toolbar4 = __wps__.wps_wysiwyg_4;
			if (editor_id == 'edit_event_text' || editor_id == 'edit_event_more' || editor_id == '__wps__about_group_edit_textarea') {
				my_width = '565px';
				my_height = '355px';
				toolbar1 = 'bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,emotions|,fontselect,fontsizeselect,|,removeformat,fullscreen';
				if (__wps__.debug) toolbar1 += ',code';
				toolbar2 = 'cut,copy,paste,pastetext,pasteword,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,youtubeIframe,|,forecolor,backcolor';
				toolbar3 = 'tablecontrols';
				toolbar4 = '';
			}
			if (editor_id == 'edit_event_more') {
				my_width = '790px';
			}
			if (editor_id == '__wps__about_group_edit_textarea') {
				my_width = '820px';
				my_height  = '410px';
			}

			if (__wps__.include_context == 'on') {
				include_context = 'contextmenu,';
			} else {
				include_context = '';
			}

			tinyMCE.init({
				// General options
				theme: "advanced",
				plugins: "fullscreen,youtubeIframe,safari,pagebreak,table,emotions,inlinepopups,insertdatetime,preview,media,searchreplace," + include_context + "paste,directionality,visualchars,nonbreaking",
				mode: "exact",
				elements: editor_id,
				relative_urls: false,
				remove_script_host: false,
				width: my_width,
				height: my_height,
				force_br_newlines: true,
				force_p_newlines: false,
				content_css: css,
				skin: __wps__.wps_wysiwyg_skin,
				theme_advanced_buttons1: toolbar1,
				theme_advanced_buttons2: toolbar2,
				theme_advanced_buttons3: toolbar3,
				theme_advanced_buttons4: toolbar4,
				theme_advanced_toolbar_location: "top",
				theme_advanced_toolbar_align: "left",
				theme_advanced_statusbar_location: "none",
				theme_advanced_resizing: true
			});

		}
	}
}


jQuery.fn.extend({
  insertAtCaret: function(myValueStart, myValueEnd){
  var obj;
  if( typeof this[0].name !='undefined' ) obj = this[0];
  else obj = this;
  
  var url = '';
  if (myValueStart.indexOf("???") > 0) {
  	var url=prompt(__wps__.bbcode_url,"");
  	if (url==null) { url = ''; }
  	if (url.indexOf("http") < 0) { url = 'http://' + url; }
  }

  var text = '';
  if (myValueStart.indexOf("!!!") > 0) {
  	var text=prompt(__wps__.bbcode_label,"");
  	if (text==null) { text = ''; }
  }

  myValueStart = myValueStart.replace('???', url);
  myValueStart = myValueStart.replace('!!!', text);

  if (jQuery.browser.msie) {
    obj.focus();
    sel = document.selection.createRange();
    sel.text = myValueStart + myValueEnd;
    obj.focus();
    }
  else if (jQuery.browser.mozilla || jQuery.browser.webkit) {
    var startPos = obj.selectionStart;
    var endPos = obj.selectionEnd;
    var scrollTop = obj.scrollTop;
    var newPos = startPos + myValueStart.length + (endPos-startPos) + myValueEnd.length;
    obj.value = obj.value.substring(0, startPos)+myValueStart+obj.value.substring(startPos,endPos)+myValueEnd+obj.value.substring(endPos,obj.value.length);
    obj.focus();
    obj.selectionStart = newPos;
    obj.selectionEnd = newPos;
    obj.scrollTop = scrollTop;
  } else {
    obj.value += myValueStart + myValueEnd;
    obj.focus();
   }
 }
})

function __wps__evoke_bbcode_toolbar() {
	if (jQuery(".__wps__toolbar").length) {
		jQuery(".__wps__toolbar_bold").live('click', function() { jQuery('#'+jQuery(this).attr('rel')).insertAtCaret('[b]','[/b]'); exit; });
		jQuery(".__wps__toolbar_italic").live('click', function() { jQuery('#'+jQuery(this).attr('rel')).insertAtCaret('[i]','[/i]'); exit; });
		jQuery(".__wps__toolbar_underline").live('click', function() { jQuery('#'+jQuery(this).attr('rel')).insertAtCaret('[u]','[/u]'); exit; });
		jQuery(".__wps__toolbar_quote").live('click', function() { jQuery('#'+jQuery(this).attr('rel')).insertAtCaret('[quote]','[/quote]'); exit; });
		jQuery(".__wps__toolbar_code").live('click', function() { jQuery('#'+jQuery(this).attr('rel')).insertAtCaret('[code]','[/code]'); exit; });
		jQuery(".__wps__toolbar_url").live('click', function() { jQuery('#'+jQuery(this).attr('rel')).insertAtCaret('[url=???]!!!','[/url]'); exit; });
	}
}


function __wps__bbcodes_ok(reply_text) {
	var bold_on = reply_text.match(/\[b\]/g);  
	if (bold_on == null) { bold_on = 0 } else { bold_on = bold_on.length }
	var bold_off = reply_text.match(/\[\/b\]/g);  
	if (bold_off == null) { bold_off = 0 } else { bold_off = bold_off.length }
	var underline_on = reply_text.match(/\[u\]/g);  
	if (underline_on == null) { underline_on = 0 } else { underline_on = underline_on.length }
	var underline_off = reply_text.match(/\[\/u\]/g);  
	if (underline_off == null) { underline_off = 0 } else { underline_off = underline_off.length }
	var italics_on = reply_text.match(/\[i\]/g);  
	if (italics_on == null) { italics_on = 0 } else { italics_on = italics_on.length }
	var italics_off = reply_text.match(/\[\/i\]/g);  
	if (italics_off == null) { italics_off = 0 } else { italics_off = italics_off.length }
	var url_on = reply_text.match(/\[url=(.*?)\]/g);  
	if (url_on == null) { url_on = 0 } else { url_on = url_on.length }
	var url_off = reply_text.match(/\[\/url\]/g);  
	if (url_off == null) { url_off = 0 } else { url_off = url_off.length }
	var quote_on = reply_text.match(/\[quote\]/g);  
	if (quote_on == null) { quote_on = 0 } else { quote_on = quote_on.length }
	var quote_off = reply_text.match(/\[\/quote\]/g);  
	if (quote_off == null) { quote_off = 0 } else { quote_off = quote_off.length }
	var code_on = reply_text.match(/\[code\]/g);  
	if (code_on == null) { code_on = 0 } else { code_on = code_on.length }
	var code_off = reply_text.match(/\[\/code\]/g);  
	if (code_off == null) { code_off = 0 } else { code_off = code_off.length }

	var bbcodes_ok = true;
	if(bold_on != bold_off) { bbcodes_ok = false; }
	if(underline_on != underline_off) { bbcodes_ok = false; }
	if(italics_on != italics_off) { bbcodes_ok = false; }
	if(url_on != url_off) { bbcodes_ok = false; }
	if(quote_on != quote_off) { bbcodes_ok = false; }
	if(code_on != code_off) { bbcodes_ok = false; }
	return bbcodes_ok;

}	

