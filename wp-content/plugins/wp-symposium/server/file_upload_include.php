<?php

function show_upload_form($uploader_dir, $uploader_url, $ver, $button_text='?', $tid=0, $gid=0, $aid=0, $subject_uid=0, $button_style='') {

	$button_text = ($button_text != '?') ? __($button_text, WPS_TEXT_DOMAIN) : '???';

	global $current_user;

	$html = '';

	$html .= "<div id='symposium_user_id' style='display:none'>".strtolower($current_user->ID)."</div>";
	$html .= "<div id='symposium_user_login' style='display:none'>".strtolower($current_user->user_login)."</div>";
	$html .= "<div id='symposium_user_email' style='display:none'>".strtolower($current_user->user_email)."</div>";			
		
	if (get_option(WPS_OPTIONS_PREFIX.'_basic_upload') == "on") {

		$html .= '<iframe id="__wps__file_upload_iframe" scrolling="no" height="100" width="100%" src="'.plugins_url().'/wp-symposium/server/file_upload_form.php?subject_uid='.$subject_uid.'&uploader_uid='.$current_user->ID.'&uploader_tid='.$tid.'&uploader_gid='.$gid.'&uploader_aid='.$aid.'&uploader_dir='.$uploader_dir.'&uploader_url='.$uploader_url.'&uploader_ver='.$ver.'"></iframe>';

	} else {


	   // The file upload form used as target for the file upload widget
	    $html .= '<form id="__wps__fileupload" action="#" method="POST" enctype="multipart/form-data">';
	        // The fileupload-buttonbar contains buttons to add/delete files and start/cancel the upload
//	        $html .= '<div class="row fileupload-buttonbar">';
//	            $html .= '<div class="span7">';
	                // The fileinput-button span is used to style the file input field as button
	                $html .= '<div class="__wps__btn success fileinput-button" style="'.$button_style.'">';
	                    $html .= '<div id="btn-span">'.$button_text.'</div>';
	                    $multiple = ($ver != 'gallery') ? '' : 'multiple';
	                    $html .= '<input type="file" name="files[]" '.$multiple.'>';
	                $html .= '</div>';
					$html .= '<div id="fileupload-info"></div>';
					if ($ver == 'activity') $html .= '<div id="fileupload-info-label" style="display:none">'.__('will be attached to your post.', WPS_TEXT_DOMAIN).'</div>';
					if ($ver == 'gallery') $html .= '<div id="fileupload-info-label" style="display:none"></div>';
					if ($ver == 'forum') $html .= '<div id="fileupload-info-label" style="display:none"></div>';
					if ($ver == 'avatar' || $ver == 'group_avatar') $html .= '<div id="fileupload-info-label" style="display:none"></div>';
//	            $html .= '</div>';
//	        $html .= '</div>';
	        // The loading indicator is shown during file processing
	        $html .= '<div class="fileupload-loading"></div>';
	        // The table listing the files available for upload/download
	        $html .= '<div style="clear:both"></div>';
	        $html .= '<table role="presentation" class="table table-striped" style="width:auto"><tbody class="files" data-toggle="modal-gallery" data-target="#modal-gallery"></tbody></table>';
	        $html .= '<input type="hidden" id="uploader_uid" name="uploader_uid" value="'.$current_user->ID.'" />';
	        $html .= '<input type="hidden" id="uploader_dir" name="uploader_dir" value="'.$uploader_dir.'" />';
	        $html .= '<input type="hidden" id="uploader_url" name="uploader_url" value="'.$uploader_url.'" />';
	        $html .= '<input type="hidden" id="uploader_ver" name="uploader_ver" value="'.$ver.'" />';
	        $html .= '<input type="hidden" id="uploader_tid" name="uploader_tid" value="'.$tid.'" />';
	        $html .= '<input type="hidden" id="uploader_gid" name="uploader_gid" value="'.$gid.'" />';
	        $html .= '<input type="hidden" id="uploader_aid" name="uploader_aid" value="'.$aid.'" />';

			$html .= '<div id="activity_file_upload_file" style="display:none"></div>';
			$html .= '<div id="btn-span-tmp" style="display:none;"></div>';

		$html .= '</form>';

		// Cropping div
		if ($ver == 'avatar') 
			$html .= '<div id="profile_image_to_crop"></div>';		


		// File upload template
		
		// During upload
		$html .= '<script id="template-upload" type="text/x-tmpl">';
		$html .= '{% for (var i=0, file; file=o.files[i]; i++) { %}';
		    $html .= '<tr class="template-upload fade">';
//		        $html .= '<td class="preview"><span class="fade"></span></td>';
		        $html .= '<td class="name"><span>{%=file.name%}</span></td>';
		        $html .= '<td class="size"><span>{%=o.formatFileSize(file.size)%}</span></td>';
		        $html .= '{% if (file.error) { %}';
		            $html .= '<td class="error" colspan="2">Error: {%=file.error%}</td>';
		        $html .= '{% } else if (o.files.valid) { %}';
			        $html .= '{% if (!i) { %}';
			            $html .= '<td>';
			                $html .= '<div class="progress progress-success progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" style="margin:0 !important;"><div class="bar" style="width:0%;"></div></div>';
		    	        $html .= '</td>';
			        $html .= '{% } %}';
		        $html .= '{% } else { %}';
		            $html .= '<td colspan="2"></td>';
		        $html .= '{% } %}';
		        $html .= '<td>{% if (!i) { %}';
//		            $html .= '<button class="btn btn-warning cancel">';
//		                $html .= '<i class="icon-ban-circle icon-white"></i>';
//		                $html .= '<span>Cancel</span>';
//		            $html .= '</button>';
		        $html .= '{% } %}</td>';
		    $html .= '</tr>';
		$html .= '{% } %}';
		$html .= '</script>';
		
		// After upload
		$html .= '<script id="template-download" type="text/x-tmpl">';
		$html .= '{% for (var i=0, file; file=o.files[i]; i++) { %}';
		    $html .= '<tr class="template-download fade" style="border:0px;">';
		        $html .= '{% if (file.error) { %}';
//		            $html .= '<td class="name"><span>{%=file.name%}</span></td>';
//		            $html .= '<td class="size"><span>{%=o.formatFileSize(file.size)%}</span></td>';
//		            $html .= '<td class="error" colspan="2">Error: {%=file.error%}</td>';
		        $html .= '{% } else { %}';
//		            $html .= '<td class="name">{%=file.name%}</td>';
//		            $html .= '<td class="size"><span>{%=o.formatFileSize(file.size)%}</span></td>';
//		            $html .= '<td colspan="2"></td>';
		        $html .= '{% } %}';
		    $html .= '</tr>';
		$html .= '{% } %}';
		$html .= '</script>';	
		
	}
	
	return $html;
		
}
		
?>
