<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

$this->loadHelper('cparams');
?>

<div class="akeeba-bootstrap" style="width:100%;">

	<div class="row-fluid">
		
		<div id="cpanel" class="span6">
		<?php echo $this->loadTemplate('graphs'); ?>	
		</div>

		<div id="cpanel" class="span6">			
			<h3><?php echo JText::_('COM_AKEEBASUBS_DASHBOARD_STATS')?></h3>
			<table width="100%" class="adminlist">
				<tbody>
				<tr class="row0">
					<td width="50%"><?php echo JText::_('COM_AKEEBASUBS_DASHBOARD_STATS_LASTYEAR')?></td>
					<td align="right" width="25%">
					<?php
						echo FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
							->since((gmdate('Y')-1).'-01-01 00:00:00')
							->until((gmdate('Y')-1).'-12-31 23:59:59')
							->nozero(1)
							->paystate('C')
							->getTotal();
					?>
					</td>
					<td align="right" width="25%">
					<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'before'): ?>
					<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
					<?php endif; ?>
					<?php echo  sprintf('%.02f',
						FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
							->since((gmdate('Y')-1).'-01-01')
							->until((gmdate('Y')-1).'-12-31 23:59:59')
							->moneysum(1)
							->nozero(1)
							->paystate('C')
							->getTotal()
					)?>
					<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'after'): ?>
					<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
					<?php endif; ?>
				</td>
				</tr>
				<tr class="row1">
					<td><?php echo JText::_('COM_AKEEBASUBS_DASHBOARD_STATS_THISYEAR')?></td>
					<td align="right">
						<?php echo FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
							->since(gmdate('Y').'-01-01')
							->until(gmdate('Y').'-12-31 23:59:59')
							->nozero(1)
							->paystate('C')
							->getTotal()
						?>
					</td>
					<td align="right">
						<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'before'): ?>
						<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
						<?php endif; ?>
						<?php echo  sprintf('%.02f',
							FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
								->since(gmdate('Y').'-01-01')
								->until(gmdate('Y').'-12-31 23:59:59')
								->moneysum(1)
								->nozero(1)
								->paystate('C')
								->getTotal()
						)?>
						<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'after'): ?>
						<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
						<?php endif; ?>
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
							->nozero(1)
							->paystate('C')
							->getTotal()
						?>
					</td>
					<td align="right">
						<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'before'): ?>
						<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
						<?php endif; ?>
						<?php echo  sprintf('%.02f',
							FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
								->since($y.'-'.$m.'-01')
								->until($y.'-'.$m.'-'.$lmday.' 23:59:59')
								->moneysum(1)
								->nozero(1)
								->paystate('C')
								->getTotal()
						)?>
						<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'after'): ?>
						<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
						<?php endif; ?>
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
							->nozero(1)
							->paystate('C')
							->getTotal()
						?>
					</td>
					<td align="right">
						<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'before'): ?>
						<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
						<?php endif; ?>
						<?php echo  sprintf('%.02f',
							FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
								->since(gmdate('Y').'-'.gmdate('m').'-01')
								->until(gmdate('Y').'-'.gmdate('m').'-'.$lmday.' 23:59:59')
								->moneysum(1)
								->nozero(1)
								->paystate('C')
								->getTotal()
						)?>
						<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'after'): ?>
						<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
						<?php endif; ?>
					</td>
				</tr>
				<tr class="row0">
					<td width="50%"><?php echo JText::_('COM_AKEEBASUBS_DASHBOARD_STATS_LAST7DAYS')?></td>
					<td align="right" width="25%">
						<?php echo FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
							->since( gmdate('Y-m-d', time()-7*24*3600) )
							->until( gmdate('Y-m-d') )
							->nozero(1)
							->paystate('C')
							->getTotal()
						?>
					</td>
					<td align="right" width="25%">
						<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'before'): ?>
						<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
						<?php endif; ?>
						<?php echo  sprintf('%.02f',
							FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
								->since( gmdate('Y-m-d', time()-7*24*3600) )
								->until( gmdate('Y-m-d') )
								->moneysum(1)
								->nozero(1)
								->paystate('C')
								->getTotal()
						)?>
						<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'after'): ?>
						<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
						<?php endif; ?>
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
							->nozero(1)
							->paystate('C')
							->getTotal()
						?>
					</td>
					<td align="right" width="25%">
						<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'before'): ?>
						<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
						<?php endif; ?>
						<?php echo  sprintf('%.02f',
							FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
								->since( $yesterday )
								->until( $date->format("Y-m-d") )
								->moneysum(1)
								->nozero(1)
								->paystate('C')
								->getTotal()
						)?>
						<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'after'): ?>
						<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
						<?php endif; ?>
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
							->nozero(1)
							->paystate('C')
							->getTotal()
						?>
						</strong>
					</td>
					<td align="right" width="25%">
						<strong>
						<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'before'): ?>
						<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
						<?php endif; ?>
						<?php echo  sprintf('%.02f',
							FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
								->since( $date->format("Y-m-d") )
								->until( $expiry->format("Y-m-d") )
								->nozero(1)
								->paystate('C')
								->moneysum(1)
								->getTotal()
						)?>
						<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'after'): ?>
						<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
						<?php endif; ?>
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
							->nozero(1)
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
						<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'before'): ?>
						<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
						<?php endif; ?>
						<?php echo sprintf('%01.2f', $summoney/$daysin)?>
						<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'after'): ?>
						<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
						<?php endif; ?>
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
						<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'before'): ?>
						<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
						<?php endif; ?>
						<?php echo sprintf('%01.2f', $lmday * ($summoney/$daysin))?>
						<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'after'): ?>
						<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
						<?php endif; ?>
						</em>
					</td>
				</tr>
				<tr class="row1">
					<td width="70%" colspan="2"><?php echo JText::_('COM_AKEEBASUBS_DASHBOARD_STATS_TOTALACTIVESUBSCRIBERS')?></td>
					<td width="25%" align="right">
					<?php
						echo FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
							->getActiveSubscribers();
					?>
					</td>
				</tr>
				<tr class="row0">
					<td width="70%" colspan="2"><?php echo JText::_('COM_AKEEBASUBS_DASHBOARD_STATS_TOTALACTIVESUBSCRIPTIONS')?></td>
					<td width="25%" align="right">
					<?php
						echo FOFModel::getTmpInstance('Subscriptions','AkeebasubsModel')
							->paystate('C')
							->enabled(1)
							->getTotal();
					?>
					</td>
				</tr>
				</tbody>
			</table>
			<div style="clear: both;">&nbsp;</div>

			<?php echo FOFTemplateUtils::loadPosition('akeebasubscriptionsstats') ?>

			<h3><?php echo JText::_('COM_AKEEBASUBS_DASHBOARD_OPERATIONS')?></h3>
			<?php echo $this->loadTemplate('quickicons'); ?>

		</div>
	</div>

	<div class="row-fluid footer">
		<div class="span12">
			<?php echo $this->loadTemplate('footer'); ?>
		</div>
	</div>

</div>