<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

FOFTemplateUtils::addCSS('media://com_akeebasubs/css/backend.css?'.AKEEBASUBS_VERSIONHASH);
FOFTemplateUtils::addCSS('media://com_akeebasubs/css/jquery.jqplot.min.css?'.AKEEBASUBS_VERSIONHASH);
FOFTemplateUtils::addJS('media://com_akeebasubs/js/backend.js?'.AKEEBASUBS_VERSIONHASH);
FOFTemplateUtils::addJS('media://com_akeebasubs/js/excanvas.min.js?'.AKEEBASUBS_VERSIONHASH);
FOFTemplateUtils::addJS('media://com_akeebasubs/js/akeebajq.js?'.AKEEBASUBS_VERSIONHASH);
FOFTemplateUtils::addJS('media://com_akeebasubs/js/jquery.jqplot.min.js?'.AKEEBASUBS_VERSIONHASH);
FOFTemplateUtils::addJS('media://com_akeebasubs/js/jqplot.highlighter.min.js?'.AKEEBASUBS_VERSIONHASH);
FOFTemplateUtils::addJS('media://com_akeebasubs/js/jqplot.dateAxisRenderer.min.js?'.AKEEBASUBS_VERSIONHASH);
FOFTemplateUtils::addJS('media://com_akeebasubs/js/jqplot.barRenderer.min.js?'.AKEEBASUBS_VERSIONHASH);
FOFTemplateUtils::addJS('media://com_akeebasubs/js/jqplot.hermite.js?'.AKEEBASUBS_VERSIONHASH);

$this->loadHelper('cparams');
?>
<div id="cpanel"  style="width:51%;float:left;">
	<h2><?php echo JText::_('COM_AKEEBASUBS_DASHBOARD_SALES')?></h2>
	<div id="aksaleschart">
		<img src="<?php echo FOFTemplateUtils::parsePath('media://com_akeebasubs/images/throbber.gif')?>" id="akthrobber" />
		<p id="aksaleschart-nodata" style="display:none">
			<?php echo JText::_('COM_AKEEBASUBS_DASHBOARD_STATS_NODATA')?>
		</p>
	</div>
	
	<h2><?php echo JText::_('COM_AKEEBASUBS_DASHBOARD_OPERATIONS')?></h2>
	<?php echo $this->loadTemplate('quickicons'); ?>
</div>

<div style="width:47%;float:right;">
	<h2><?php echo JText::_('COM_AKEEBASUBS_DASHBOARD_STATS')?></h2>
	<table width="100%" class="adminlist">
		<tbody>
		<tr class="row0">
			<td width="50%"><?php echo JText::_('COM_AKEEBASUBS_DASHBOARD_STATS_LASTYEAR')?></td>
			<td align="right" width="25%">
			<?php
				echo FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
					->since((gmdate('Y')-1).'-01-01 00:00:00')
					->until((gmdate('Y')-1).'-12-31 23:59:59')
					->paystate('C')
					->getTotal();
			?>
			</td>
			<td align="right" width="25%">
			<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€'); ?>
			<?php echo  sprintf('%.02f',
				FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
					->since((gmdate('Y')-1).'-01-01')
					->until((gmdate('Y')-1).'-12-31 23:59:59')
					->moneysum(1)
					->paystate('C')
					->getTotal()
			)?>
		</td>
		</tr>
		<tr class="row1">
			<td><?php echo JText::_('COM_AKEEBASUBS_DASHBOARD_STATS_THISYEAR')?></td>
			<td align="right">
				<?php echo FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
					->since(gmdate('Y').'-01-01')
					->until(gmdate('Y').'-12-31 23:59:59')
					->paystate('C')
					->getTotal()
				?>
			</td>
			<td align="right">
				<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€'); ?>
				<?php echo  sprintf('%.02f',
					FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
						->since(gmdate('Y').'-01-01')
						->until(gmdate('Y').'-12-31 23:59:59')
						->moneysum(1)
						->paystate('C')
						->getTotal()
				)?>
			</td>
		</tr>
		<tr class="row0">
			<td><?php echo JText::_('COM_AKEEBASUBS_DASHBOARD_STATS_LASTMONTH')?></td>
			<td align="right">
				<?php
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
					if($y < 2011) $y = 2011;
					if($m < 1) $m = 1;
					if($lmday < 1) $lmday = 1;
				?>
				<?php echo FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
					->since($y.'-'.$m.'-01')
					->until($y.'-'.$m.'-'.$lmday.' 23:59:59')
					->paystate('C')
					->getTotal()
				?>
			</td>
			<td align="right">
				<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€'); ?>
				<?php echo  sprintf('%.02f',
					FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
						->since($y.'-'.$m.'-01')
						->until($y.'-'.$m.'-'.$lmday.' 23:59:59')
						->moneysum(1)
						->paystate('C')
						->getTotal()
				)?>
			</td>
		</tr>
		<tr class="row1">
			<td><?php echo JText::_('COM_AKEEBASUBS_DASHBOARD_STATS_THISMONTH')?></td>
			<td align="right">
				<?php
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
					if($lmday < 1) $lmday = 28;
				?>
				<?php echo FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
					->since(gmdate('Y').'-'.gmdate('m').'-01')
					->until(gmdate('Y').'-'.gmdate('m').'-'.$lmday.' 23:59:59')
					->paystate('C')
					->getTotal()
				?>
			</td>
			<td align="right">
				<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€'); ?>
				<?php echo  sprintf('%.02f',
					FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
						->since(gmdate('Y').'-'.gmdate('m').'-01')
						->until(gmdate('Y').'-'.gmdate('m').'-'.$lmday.' 23:59:59')
						->moneysum(1)
						->paystate('C')
						->getTotal()
				)?>
			</td>
		</tr>
		<tr class="row0">
			<td width="50%"><?php echo JText::_('COM_AKEEBASUBS_DASHBOARD_STATS_LAST7DAYS')?></td>
			<td align="right" width="25%">
				<?php echo FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
					->since( gmdate('Y-m-d', time()-7*24*3600) )
					->until( gmdate('Y-m-d') )
					->paystate('C')
					->getTotal()
				?>
			</td>
			<td align="right" width="25%">
				<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€'); ?>
				<?php echo  sprintf('%.02f',
					FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
						->since( gmdate('Y-m-d', time()-7*24*3600) )
						->until( gmdate('Y-m-d') )
						->moneysum(1)
						->paystate('C')
						->getTotal()
				)?>
			</td>
		</tr>
		<tr class="row1">
			<td width="50%"><?php echo JText::_('COM_AKEEBASUBS_DASHBOARD_STATS_YESTERDAY')?></td>
			<td align="right" width="25%">
				<?php
				$date = new DateTime();
				$date->setDate(gmdate('Y'), gmdate('m'), gmdate('d'));
				$date->modify("-1 day");
				$yesterday = $date->format("Y-m-d");
				$date->modify("+1 day")
				?>
				<?php echo FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
					->since( $yesterday )
					->until( $date->format("Y-m-d") )
					->paystate('C')
					->getTotal()
				?>
			</td>
			<td align="right" width="25%">
				<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€'); ?>
				<?php echo  sprintf('%.02f',
					FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
						->since( $yesterday )
						->until( $date->format("Y-m-d") )
						->moneysum(1)
						->paystate('C')
						->getTotal()
				)?>
			</td>
		</tr>
		<tr class="row0">
			<td width="50%"><strong><?php echo JText::_('COM_AKEEBASUBS_DASHBOARD_STATS_TODAY')?></strong></td>
			<td align="right" width="25%">
				<strong>
				<?php
					$expiry = clone $date;
					$expiry->modify('+1 day');
				?>
				<?php echo FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
					->since( $date->format("Y-m-d") )
					->until( $expiry->format("Y-m-d") )
					->paystate('C')
					->getTotal()
				?>
				</strong>
			</td>
			<td align="right" width="25%">
				<strong>
				<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€'); ?>
				<?php echo  sprintf('%.02f',
					FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
						->since( $date->format("Y-m-d") )
						->until( $expiry->format("Y-m-d") )
						->paystate('C')
						->moneysum(1)
						->getTotal()
				)?>
				</strong>
			</td>
		</tr>
		<tr class="row1">
			<?php
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
				if($lmday < 1) $lmday = 28;
				if($y < 2011) $y = 2011;
				$daysin = gmdate('d');
				$numsubs = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
					->since(gmdate('Y').'-'.gmdate('m').'-01')
					->until(gmdate('Y').'-'.gmdate('m').'-'.$lmday.' 23:59:59')
					->paystate('C')
					->getTotal();
				$summoney = FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
					->since(gmdate('Y').'-'.gmdate('m').'-01')
					->until(gmdate('Y').'-'.gmdate('m').'-'.$lmday.' 23:59:59')
					->moneysum(1)
					->paystate('C')
					->getTotal();
			?>
			<td width="50%"><strong><?php echo JText::_('COM_AKEEBASUBS_DASHBOARD_STATS_AVERAGETHISMONTH')?></strong></td>
			<td align="right" width="25%">
				<strong><?php echo sprintf('%01.1f', $numsubs/$daysin)?><strong>
			</td>
			<td align="right" width="25%">
				<strong>
				<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€'); ?>
				<?php echo sprintf('%01.2f', $summoney/$daysin)?>
				</strong>
			</td>
		</tr>
		<tr class="row0">
			<td width="50%"><strong><?php echo JText::_('COM_AKEEBASUBS_DASHBOARD_STATS_PROJECTION')?></strong></td>
			<td align="right" width="25%">
				<em><?php echo sprintf('%01u', $lmday * ($numsubs/$daysin))?></em>
			</td>
			<td align="right" width="25%">
				<em>
				<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€'); ?>
				<?php echo sprintf('%01.2f', $lmday * ($summoney/$daysin))?>
				</em>
			</td>
		</tr>
		</tbody>
	</table>
	
	<?php echo FOFTemplateUtils::loadPosition('akeebasubscriptionsstats') ?>
</div>

<?php
	$xday = gmdate('Y-m-d', time() - 30 * 24 * 3600);
?>
<script type="text/javascript">
(function($) {
	$(document).ready(function(){
		var url = "index.php?option=com_akeebasubs&view=subscriptions&since=<?php echo $xday?>&enabled=1&groupbydate=1&paystate=C&savestate=0&format=json";
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
				return;
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