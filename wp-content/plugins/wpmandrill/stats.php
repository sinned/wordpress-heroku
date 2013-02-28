<?php if (!current_user_can('manage_options')) wp_die( __('You do not have sufficient permissions to access this page.') ); ?>
<?php wpMandrill::getConnected(); ?>
<div class="wrap">
<div class="icon32" style="background: url('<?php echo plugins_url('images/mandrill-head-icon.png',__FILE__); ?>');"><br /></div>
<h2><?php _e('Mandrill Service Report', wpMandrill::WPDOMAIN); ?></h2><?php

$stats = self::getCurrentStats();
if ( empty($stats) ) {
    echo '<p>' . __('There was a problem retrieving statistics.', self::WPDOMAIN) . '</p>';
    echo '</div>';
    return;
}

$delivered  = $stats['general']['stats']['sent'] -
                $stats['general']['stats']['hard_bounces'] - 
                $stats['general']['stats']['soft_bounces'] -
                $stats['general']['stats']['rejects'];

$lit = array();

$lit['hourly']['title']   = __('Hourly Sending Volume and Open/Click Rate',self::WPDOMAIN);
$lit['hourly']['Xtitle']  = __('Hours',self::WPDOMAIN);
$lit['hourly']['tooltip'] = __('Hour',self::WPDOMAIN);

$lit['daily']['title']    = __('Daily Sending Volume and Open/Click Rate',self::WPDOMAIN);
$lit['daily']['Xtitle']   = __('Days',self::WPDOMAIN);
$lit['daily']['tooltip']  = __('Day',self::WPDOMAIN);

$lit['subtitle']    = __('in the last 30 days',self::WPDOMAIN);
$lit['Ytitle']      = __('Open & Click Rate',self::WPDOMAIN);
$lit['SerieName']   = __('Volume',self::WPDOMAIN);
$lit['emails']      = __('emails',self::WPDOMAIN);
$lit['openrate']    = __('Open Rate',self::WPDOMAIN);
$lit['clickrate']   = __('Click Rate',self::WPDOMAIN);

?>
<div id="alltime_report">
    <h3><?php echo sprintf(__('All-time statistics since %s: ', wpMandrill::WPDOMAIN),date('m/d/Y',strtotime($stats['general']['created_at']))); ?></h3>
    
    <div id="alltime_report_canvas">
        <div class="stat_box"><?php _e('Reputation:', wpMandrill::WPDOMAIN); ?><br/><span><?=$stats['general']['reputation']?>%</span></div>
        <div class="stat_box"><?php _e('Quota:', wpMandrill::WPDOMAIN); ?><br/><span><?=$stats['general']['hourly_quota']?> <?php _e('sends/hour', wpMandrill::WPDOMAIN); ?></span></div>
        <div class="stat_box"><?php _e('Emails sent:', wpMandrill::WPDOMAIN); ?><br/><span><?=$stats['general']['stats']['sent']?></span></div>
        <div class="stat_box"><?php _e('Emails delivered:', wpMandrill::WPDOMAIN); ?><br/><span><?=$delivered?> (<?=number_format(  $delivered*100 / ( ($stats['general']['stats']['sent'])?$stats['general']['stats']['sent']:1 ) ,2); ?>%)</span></div>
        <div class="stat_box"><?php _e('Tracked opens:', wpMandrill::WPDOMAIN); ?><br/><span><?=$stats['general']['stats']['opens']?></span></div>
        <div class="stat_box"><?php _e('Tracked clicks:', wpMandrill::WPDOMAIN); ?><br/><span><?=$stats['general']['stats']['clicks']?></span></div>
        <?php
            if ( $stats['general']['stats']['rejects'] ) echo '<div class="stat_box warning">'.__('Rejects:', wpMandrill::WPDOMAIN).'<br/><span>'.$stats['general']['stats']['rejects'].'</span></div>';
            if ( $stats['general']['stats']['complaints'] ) echo '<div class="stat_box warning">'.__('Complaints:', wpMandrill::WPDOMAIN).'<br/><span>'.$stats['general']['stats']['complaints'].'</span></div>';
            if ( $stats['general']['backlog'] ) echo '<div class="stat_box warning">'.__('Current backlog:', wpMandrill::WPDOMAIN).'<br/><span>'.$stats['general']['backlog'].' emails</span></div>';
        ?>
    </div>
</div>

<div style="clear: both;"></div>
<div id="filtered_reports">
    <h3><?php _e('Filtered statistics:', wpMandrill::WPDOMAIN); ?></h3>
    <label for="filter"><?php _e('Filter by:', wpMandrill::WPDOMAIN); ?> </label>
    <select id="filter" name="filter">
        <option value="none" selected="selected" ><?php _e('No filter', wpMandrill::WPDOMAIN); ?></option>
        <optgroup label="<?php _e('Sender:', wpMandrill::WPDOMAIN); ?>">
            <?php 
                foreach ( array_keys($stats['stats']['hourly']['senders']) as $sender) {
                    echo '<option value="s:'.$sender.'">'.$sender.'</option>';
                }
            ?>            
        </optgroup>
        <optgroup label="<?php _e('Tag:', wpMandrill::WPDOMAIN); ?>">
            <?php 
                if ( isset($stats['stats']['hourly']['tags']['detailed_stats']) 
                     && is_array($stats['stats']['hourly']['tags']['detailed_stats']) ) {
                     
                    foreach ( array_keys($stats['stats']['hourly']['tags']['detailed_stats']) as $tag) {
                        echo '<option value="'.$tag.'">'.$tag.'</option>';
                    }
                    
                }
            ?>            
        </optgroup>        
    </select>
    <label for="display"><?php _e('Display:', wpMandrill::WPDOMAIN); ?> </label>
    <select id="display" name="display">
        <option value="volume"><?php _e('Total Volume per Period', wpMandrill::WPDOMAIN); ?></option>
        <option value="average"><?php _e('Average Volume per Period', wpMandrill::WPDOMAIN); ?></option>
    </select><div id="ajax-icon-container"><span id="loading_data" class="hidden"></span></div>
    <div id="filtered_reports_canvas">
        <div id="filtered_recent" style="width: 50%;height: 300px; float: left;"></div>
        <div id="filtered_oldest" style="width: 50%;height: 300px; float: left;"></div>
    </div>
    <div style="clear: both;"></div>
</div>
<br/><br/>
<div id="hourly_report"></div>
<script type="text/javascript">
function emailFormatter(v, axis) {
    return v.toFixed(axis.tickDecimals) +" emails";
}
function percentageFormatter(v, axis) {
    return v.toFixed(axis.tickDecimals) +"%";
}
function wpm_showTooltip(x, y, contents) {
	jQuery('<div id="wpm_tooltip">' + contents + '</div>').css( {
        position: 'absolute',
        display: 'none',
        top: y + 5,
        left: x + 5,
        border: '1px solid #fdd',
        padding: '2px',
        'background-color': '#fee',
        opacity: 0.80
    }).appendTo("body").fadeIn(200);
}
<?php
	// hourly stats data
	$hticks = array_keys($stats['graph']['hourly']['delivered']);
	array_walk($hticks, 'wpMandrill_transformJSArray');
	
	$hvolume = $stats['graph']['hourly']['delivered'];
	$horate  = $stats['graph']['hourly']['open_rate'];
	$hcrate  = $stats['graph']['hourly']['click_rate'];
	
	array_walk($hvolume,'wpMandrill_transformJSArray');
	array_walk($horate, 'wpMandrill_transformJSArray');
	array_walk($hcrate, 'wpMandrill_transformJSArray');

	// daily stats data
	$dticks 	= array_keys($stats['graph']['daily']['delivered']);
	array_walk($dticks, 'wpMandrill_transformJSArray');
	
	$day_keys 	= array();
	foreach(array_keys($stats['graph']['daily']['delivered']) as $day_index => $day_key) {
		$day_keys[$day_index] = $day_key;
	}
		
	$dvolume = $stats['graph']['daily']['delivered'];
	$dorate  = $stats['graph']['daily']['open_rate'];
	$dcrate  = $stats['graph']['daily']['click_rate'];
	
	array_walk($dvolume,'wpMandrill_transformJSArray', array(1, $day_keys));
	array_walk($dorate, 'wpMandrill_transformJSArray', array(1, $day_keys));
	array_walk($dcrate, 'wpMandrill_transformJSArray', array(1, $day_keys));
	
	
?>
var hvolume     = [<?=implode(',',$hvolume);?>];
var hopenrates  = [<?=implode(',',$horate);?>];
var hclickrates = [<?=implode(',',$hcrate);?>]
		
var dvolume     = [<?=implode(',',$dvolume);?>];
var dopenrates  = [<?=implode(',',$dorate);?>];
var dclickrates = [<?=implode(',',$dcrate);?>]
var dticks	    = [<?=implode(',',array_keys($stats['graph']['daily']['delivered']));?>]
jQuery(function () {
	var previousPoint = null;
	jQuery("#hourly_report_canvas").bind("plothover", function (event, pos, item) {
        if (item) {
            if (previousPoint != item.dataIndex) {
                previousPoint = item.dataIndex;
                
                jQuery("#wpm_tooltip").remove();
                var x = item.datapoint[0].toFixed(0);	                

                if ( item.seriesIndex == 0 ) {
                	var y = item.datapoint[1].toFixed(0);
                	wpm_showTooltip(item.pageX, item.pageY, item.series.label + " (at hour " + x + ") = " + y + " emails");
                } else {
                	var y = item.datapoint[1].toFixed(2);
                	wpm_showTooltip(item.pageX, item.pageY, item.series.label + " (at hour " + x + ") = " + y + "%");
                }
            }
        }
        else {
        	jQuery("#wpm_tooltip").remove();
            previousPoint = null;            
        }
	});
	jQuery("#daily_report_canvas").bind("plothover", function (event, pos, item) {
        if (item) {
            if (previousPoint != item.dataIndex) {
                previousPoint = item.dataIndex;
                
                jQuery("#wpm_tooltip").remove();
                var x = dticks[item.dataIndex];
                	
                if ( item.seriesIndex == 0 ) {
                	var y = item.datapoint[1].toFixed(0);
                	wpm_showTooltip(item.pageX, item.pageY, "Day " + x + ": " + y + " emails");
                } else {
                	var y = item.datapoint[1].toFixed(2);
                	wpm_showTooltip(item.pageX, item.pageY, item.series.label + " for " + x + ": " + y + "%");
                }
            }
        }
        else {
        	jQuery("#wpm_tooltip").remove();
            previousPoint = null;            
        }
	});
	jQuery.plot(jQuery("#hourly_report_canvas"),
	           [ { data: hvolume, label: "Volume", yaxis: 2, bars: {show: true, barWidth: 0.6, align: "center"}, lines: { show: true }},
	             { data: hopenrates, label: "Open Rate"  },
	             { data: hclickrates, label: "Click Rate" }],
	           {
	        	   series: {
	 	   			   points: { show: true },
					   lines: { show: true },
					   shadowSize: 7
	 	           },
	        	   grid: {
	 	        	  hoverable: true,
	 	        	  aboveData: true,
	 	        	  borderWidth: 0,
	 	        	  minBorderMargin: 10,
	 	        	  margin: {
	 	        		    top: 10,
	 	        		    left: 10,
	 	        		    bottom: 15,
	 	        		    right: 100
	 	        		}
	 	           },
	               xaxes: [ { ticks: [<?=implode(',',$hticks);?>] } ],
	               yaxes: [ { min: 0, tickFormatter: percentageFormatter },
	                        {
	            	   			min: 0, 
	            	   			alignTicksWithAxis: 1, //1=right, null=left
	                          	position: 'sw',
	                          	tickFormatter: emailFormatter
	                        } ],
	               legend: { position: 'ne', margin: [20, 10]}
		});
	jQuery.plot(jQuery("#daily_report_canvas"),
	           [ { data: dvolume, label: "Volume", yaxis: 2, bars: {show: true, barWidth: 0.6, align: "center"}, lines: { show: true }},
	             { data: dopenrates, label: "Open Rate"  },
	             { data: dclickrates, label: "Click Rate" }],
	           {
	        	   series: {
	 	   			   points: { show: true },
					   lines: { show: true },
					   shadowSize: 7
	 	           },
	        	   grid: {
	 	        	  hoverable: true,
	 	        	  aboveData: true,
	 	        	  borderWidth: 0,
	 	        	  minBorderMargin: 10,
	 	        	  margin: {
	 	        		    top: 10,
	 	        		    left: 10,
	 	        		    bottom: 15,
	 	        		    right: 10
	 	        		}
	 	           },
	               xaxes: [ { ticks: [<?=implode(',', $dticks);?>] } ],
	               yaxes: [ { min: 0, tickFormatter: percentageFormatter },
	                        {
			     	   		  min: 0, 
	                          alignTicksWithAxis: 1, //1=right, null=left
	                          position: 'sw',
	                          tickFormatter: emailFormatter
	                        } ],
	               legend: { position: 'ne', margin: [20, 10]}
		});
});
</script>
<h3><?=$lit['hourly']['title']; ?></h3>
<h4><?=$lit['subtitle']; ?></h4>
    <div id="hourly_report_canvas" style="height: 400px;"></div><br/><br/>
<h3><?=$lit['daily']['title']; ?></h3>
<h4><?=$lit['subtitle']; ?></h4>
    <div id="daily_report_canvas" style="height: 400px;"></div>
    <h3><a href="http://mandrillapp.com/" target="_target"><?php _e('For more detailed statistics, please visit your Mandrill Dashboard',self::WPDOMAIN); ?></a>.</h3>

		<?php
		wpMandrill::$stats = $stats;
?>