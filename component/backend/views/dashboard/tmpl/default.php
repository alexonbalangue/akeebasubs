<?php
/**
 * @version		$Id$
 * @category	AkeebaBackup
 * @package		UNiTE
 * @subpackage	gui-component
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('Restricted access');?>

<?= @helper('behavior.tooltip'); ?>
<!--
<style src="media://com_akeebasubs/css/backend.css?<?=AKEEBASUBS_VERSIONHASH?>" />
<style src="media://com_akeebasubs/css/jquery.jqplot.min.css?<?=AKEEBASUBS_VERSIONHASH?>" />
<script src="media://com_akeebasubs/js/backend.js?<?=AKEEBASUBS_VERSIONHASH?>" />
<script src="media://com_akeebasubs/js/excanvas.min.js?<?=AKEEBASUBS_VERSIONHASH?>" />
<script src="media://com_akeebasubs/js/jquery.js?<?=AKEEBASUBS_VERSIONHASH?>" />
<script src="media://com_akeebasubs/js/jquery.jqplot.min.js?<?=AKEEBASUBS_VERSIONHASH?>" />
<script src="media://com_akeebasubs/js/jqplot.highlighter.min.js?<?=AKEEBASUBS_VERSIONHASH?>" />
<script src="media://com_akeebasubs/js/jqplot.dateAxisRenderer.min.js?<?=AKEEBASUBS_VERSIONHASH?>" />
<script src="media://com_akeebasubs/js/jqplot.barRenderer.min.js?<?=AKEEBASUBS_VERSIONHASH?>" />
<script src="media://com_akeebasubs/js/jqplot.hermite.js?<?=AKEEBASUBS_VERSIONHASH?>" />
-->

<div id="cpanel"  style="width:51%;float:left;">
	<?= @helper('tabs.startPane', array('id' => 'quick', 'attribs' => array('height' => '275px'))) ?>
	
	<?= @helper('tabs.startPanel', array('title' => @text('COM_AKEEBASUBS_DASHBOARD_WELCOME'))) ?>
		<?=@template('default_welcome');?>
    <?= @helper('tabs.endPanel') ?>

	<?= @helper('tabs.startPanel', array('title' => @text('COM_AKEEBASUBS_DASHBOARD_OPERATIONS'))) ?>
		<div style="margin-left: 13px; text-align:center; height:234px;max-width:575px">
			<?=@template('default_quickicons'); ?>
			<div class="clr">    
	    </div>
    <?= @helper('tabs.endPanel') ?>

	<?= @helper('tabs.endPane') ?>
</div>

<div style="width:47%;float:right;">
	<?= @helper('tabs.startPane', array('id' => 'stats', 'attribs' => array('height' => '300px'))) ?>
	
	<?= @helper('tabs.startPanel', array('title' => @text('COM_AKEEBASUBS_DASHBOARD_SALES'))) ?>
		<div id="aksaleschart">
			<img src="media://com_akeebasubs/images/throbber.gif" id="akthrobber" />
			<p id="aksaleschart-nodata" style="display:none">
				<?=@text('COM_AKEEBASUBS_DASHBOARD_STATS_NODATA')?>
			</p>
		</div>
	<?= @helper('tabs.endPanel') ?>
	
	<?= @helper('tabs.startPanel', array('title' => @text('COM_AKEEBASUBS_DASHBOARD_STATS'))) ?>
		<table width="100%" class="adminlist">
			<tbody>
			<tr class="row0">
				<td width="50%"><?=@text('COM_AKEEBASUBS_DASHBOARD_STATS_LASTYEAR')?></td>
				<td align="right" width="25%">
					<?= KFactory::tmp('admin::com.akeebasubs.model.subscriptions')
						->publish_up((gmdate('Y')-1).'-01-01 00:00:00')
						->publish_down((gmdate('Y')-1).'-12-31 23:59:59')
						->paystate('C')
						->getTotal()
					?>
				</td>
				<td align="right" width="25%">
					<?=KFactory::get('admin::com.akeebasubs.model.configs')->getConfig()->currencysymbol?>
					<?= sprintf('%.02f',
						KFactory::tmp('admin::com.akeebasubs.model.subscriptions')
							->publish_up((gmdate('Y')-1).'-01-01')
							->publish_down((gmdate('Y')-1).'-12-31 23:59:59')
							->moneysum(1)
							->paystate('C')
							->getTotal()
					)?>
				</td>
			</tr>
			<tr class="row1">
				<td><?=@text('COM_AKEEBASUBS_DASHBOARD_STATS_THISYEAR')?></td>
				<td align="right">
					<?= KFactory::tmp('admin::com.akeebasubs.model.subscriptions')
						->publish_up(gmdate('Y').'-01-01')
						->publish_down(gmdate('Y').'-12-31 23:59:59')
						->paystate('C')
						->getTotal()
					?>
				</td>
				<td align="right">
					<?=KFactory::get('admin::com.akeebasubs.model.configs')->getConfig()->currencysymbol?>
					<?= sprintf('%.02f',
						KFactory::tmp('admin::com.akeebasubs.model.subscriptions')
							->publish_up(gmdate('Y').'-01-01')
							->publish_down(gmdate('Y').'-12-31 23:59:59')
							->moneysum(1)
							->paystate('C')
							->getTotal()
					)?>
				</td>
			</tr>
			<tr class="row0">
				<td><?=@text('COM_AKEEBASUBS_DASHBOARD_STATS_LASTMONTH')?></td>
				<td align="right">
					<?
						$y = gmdate('Y');
						$m = gmdate('m');
						if($m == 1) {
							$m = 12; $y -= 1;
						} else {
							$m -= 1;
						}
						switch($m) {
							case 1: case 3: case 5: case 7: case 8: case 10: case 12:
								$lmday = 31; break;
							case 4: case 6: case 9: case 11:
								$lmday = 30; break;
							case 2:
								if( !($y % 4) && ($y % 400) ) {
									$lmday = 29;
								} else {
									$lmday = 28;
								}
						}
					?>
					<?= KFactory::tmp('admin::com.akeebasubs.model.subscriptions')
						->publish_up($y.'-'.$m.'-01')
						->publish_down($y.'-'.$m.'-'.$lmday.' 23:59:59')
						->paystate('C')
						->getTotal()
					?>
				</td>
				<td align="right">
					<?=KFactory::get('admin::com.akeebasubs.model.configs')->getConfig()->currencysymbol?>
					<?= sprintf('%.02f',
						KFactory::tmp('admin::com.akeebasubs.model.subscriptions')
							->publish_up($y.'-'.$m.'-01')
							->publish_down($y.'-'.$m.'-'.$lmday.' 23:59:59')
							->moneysum(1)
							->paystate('C')
							->getTotal()
					)?>
				</td>
			</tr>
			<tr class="row1">
				<td><?=@text('COM_AKEEBASUBS_DASHBOARD_STATS_THISMONTH')?></td>
				<td align="right">
					<?
						switch(gmdate('m')) {
							case 1: case 3: case 5: case 7: case 8: case 10: case 12:
								$lmday = 31; break;
							case 4: case 6: case 9: case 11:
								$lmday = 30; break;
							case 2:
								$y = gmdate('Y');
								if( !($y % 4) && ($y % 400) ) {
									$lmday = 29;
								} else {
									$lmday = 28;
								}
						}
					?>
					<?= KFactory::tmp('admin::com.akeebasubs.model.subscriptions')
						->publish_up(gmdate('Y').'-'.gmdate('m').'-01')
						->publish_down(gmdate('Y').'-'.gmdate('m').'-'.$lmday.' 23:59:59')
						->paystate('C')
						->getTotal()
					?>
				</td>
				<td align="right">
					<?=KFactory::get('admin::com.akeebasubs.model.configs')->getConfig()->currencysymbol?>
					<?= sprintf('%.02f',
						KFactory::tmp('admin::com.akeebasubs.model.subscriptions')
							->publish_up(gmdate('Y').'-'.gmdate('m').'-01')
							->publish_down(gmdate('Y').'-'.gmdate('m').'-'.$lmday.' 23:59:59')
							->moneysum(1)
							->paystate('C')
							->getTotal()
					)?>
				</td>
			</tr>
			<tr class="row0">
				<td width="50%"><?=@text('COM_AKEEBASUBS_DASHBOARD_STATS_LAST7DAYS')?></td>
				<td align="right" width="25%">
					<?= KFactory::tmp('admin::com.akeebasubs.model.subscriptions')
						->publish_up( gmdate('Y-m-d', time()-7*24*3600) )
						->publish_down( gmdate('Y-m-d') )
						->paystate('C')
						->getTotal()
					?>
				</td>
				<td align="right" width="25%">
					<?=KFactory::get('admin::com.akeebasubs.model.configs')->getConfig()->currencysymbol?>
					<?= sprintf('%.02f',
						KFactory::tmp('admin::com.akeebasubs.model.subscriptions')
							->publish_up( gmdate('Y-m-d', time()-7*24*3600) )
							->publish_down( gmdate('Y-m-d') )
							->moneysum(1)
							->paystate('C')
							->getTotal()
					)?>
				</td>
			</tr>
			<tr class="row1">
				<td width="50%"><?=@text('COM_AKEEBASUBS_DASHBOARD_STATS_YESTERDAY')?></td>
				<td align="right" width="25%">
					<?
					$date = new DateTime();
					$date->setDate(gmdate('Y'), gmdate('m'), gmdate('d'));
					$date->modify("-1 day");
					$yesterday = $date->format("Y-m-d");
					$date->modify("+1 day")
					?>
					<?= KFactory::tmp('admin::com.akeebasubs.model.subscriptions')
						->publish_up( $yesterday )
						->publish_down( $date->format("Y-m-d") )
						->paystate('C')
						->getTotal()
					?>
				</td>
				<td align="right" width="25%">
					<?=KFactory::get('admin::com.akeebasubs.model.configs')->getConfig()->currencysymbol?>
					<?= sprintf('%.02f',
						KFactory::tmp('admin::com.akeebasubs.model.subscriptions')
							->publish_up( $yesterday )
							->publish_down( $date->format("Y-m-d") )
							->moneysum(1)
							->paystate('C')
							->getTotal()
					)?>
				</td>
			</tr>
			<tr class="row0">
				<td width="50%"><strong><?=@text('COM_AKEEBASUBS_DASHBOARD_STATS_TODAY')?></strong></td>
				<td align="right" width="25%">
					<strong>
					<?php
						$expiry = clone $date;
						$expiry->modify('+1 day');
					?>
					<?= KFactory::tmp('admin::com.akeebasubs.model.subscriptions')
						->publish_up( $date->format("Y-m-d") )
						->publish_down( $expiry->format("Y-m-d") )
						->paystate('C')
						->getTotal()
					?>
					</strong>
				</td>
				<td align="right" width="25%">
					<strong>
					<?=KFactory::get('admin::com.akeebasubs.model.configs')->getConfig()->currencysymbol?>
					<?= sprintf('%.02f',
						KFactory::tmp('admin::com.akeebasubs.model.subscriptions')
							->publish_up( $date->format("Y-m-d") )
							->publish_down( $expiry->format("Y-m-d") )
							->paystate('C')
							->moneysum(1)
							->getTotal()
					)?>
					</strong>
				</td>
			</tr>
			<tr class="row1">
				<?
					switch(gmdate('m')) {
						case 1: case 3: case 5: case 7: case 8: case 10: case 12:
							$lmday = 31; break;
						case 4: case 6: case 9: case 11:
							$lmday = 30; break;
						case 2:
							$y = gmdate('Y');
							if( !($y % 4) && ($y % 400) ) {
								$lmday = 29;
							} else {
								$lmday = 28;
							}
					}
					$daysin = gmdate('d');
					$numsubs = KFactory::tmp('admin::com.akeebasubs.model.subscriptions')
						->publish_up(gmdate('Y').'-'.gmdate('m').'-01')
						->publish_down(gmdate('Y').'-'.gmdate('m').'-'.$lmday.' 23:59:59')
						->paystate('C')
						->getTotal();
					$summoney = KFactory::tmp('admin::com.akeebasubs.model.subscriptions')
						->publish_up(gmdate('Y').'-'.gmdate('m').'-01')
						->publish_down(gmdate('Y').'-'.gmdate('m').'-'.$lmday.' 23:59:59')
						->moneysum(1)
						->paystate('C')
						->getTotal();
				?>
				<td width="50%"><strong><?=@text('COM_AKEEBASUBS_DASHBOARD_STATS_AVERAGETHISMONTH')?></strong></td>
				<td align="right" width="25%">
					<strong><?=sprintf('%01.1f', $numsubs/$daysin)?><strong>
				</td>
				<td align="right" width="25%">
					<strong>
					<?=KFactory::get('admin::com.akeebasubs.model.configs')->getConfig()->currencysymbol?>
					<?=sprintf('%01.2f', $summoney/$daysin)?>
					</strong>
				</td>
			</tr>
			<tr class="row0">
				<td width="50%"><strong><?=@text('COM_AKEEBASUBS_DASHBOARD_STATS_PROJECTION')?></strong></td>
				<td align="right" width="25%">
					<em><?=sprintf('%01u', $lmday * ($numsubs/$daysin))?></em>
				</td>
				<td align="right" width="25%">
					<em>
					<?=KFactory::get('admin::com.akeebasubs.model.configs')->getConfig()->currencysymbol?>
					<?=sprintf('%01.2f', $lmday * ($summoney/$daysin))?>
					</em>
				</td>
			</tr>
			</tbody>
		</table>
	<?= @helper('tabs.endPanel') ?>
	
	<?= @helper('tabs.endPane') ?>
	
	<?=@helper('site::com.akeebasubs.template.helper.modules.loadposition', array('position' => 'akeebasubscriptionsstats'))?>
</div>

<?php
	$xday = gmdate('Y-m-d', time() - 30 * 24 * 3600);
?>
<script type="text/javascript">
(function($) {
	$(document).ready(function(){
		var url = "<?=str_replace('&amp;','&',@route('view=subscriptions&since='.$xday.'&enabled=1&groupbydate=1&paystate=C&format=json'))?>";
		$.jqplot.config.enablePlugins = true;
		$.getJSON(url, function(data){
			var salesPoints = [];
			var subsPoints = [];
			$.each(data, function(index, item){
				salesPoints.push([item.date, parseInt(item.net * 100) * 1 / 100]);
				subsPoints.push([item.date, item.subs * 1]);
			});
			if(salesPoints.length == 0) {
				$('#akthrobber').hide();
				$('#aksaleschart-nodata').show();
			}
			plot1 = $.jqplot('aksaleschart', [subsPoints, salesPoints], {
				show: true,
				axes:{
					xaxis:{renderer:$.jqplot.DateAxisRenderer,tickInterval:'1 week'},
					yaxis:{min: 0,tickOptions:{formatString:'%.2f'}},
					y2axis:{min: 0,tickOptions:{formatString:'%u'}}
				},
			    series:[ 
			    	{
			    		yaxis: 'y2axis',
			    		lineWidth:1,
			    		renderer:$.jqplot.BarRenderer,
			    		rendererOptions:{barPadding: 0, barMargin: 0, barWidth: 5, shadowDepth: 0, varyBarColor: 0},
			    		markerOptions: {
			    			style:'none'
			    		},
			    		color: '#aae0aa'
			    	},
			        {
			        	lineWidth:3,
			        	markerOptions:{
			        		style:'filledCircle',
			        		size:8
			        	},
			        	renderer: $.jqplot.hermiteSplineRenderer,
			        	rendererOptions:{steps: 60, tension: 0.6}
			        }
			    ],
			    highlighter: {sizeAdjust: 7.5},
			    axesDefaults:{useSeriesColor: true}
			});
		});
	});
})(akeeba.jQuery);
</script>