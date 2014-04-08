<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

$this->loadHelper('cparams');
$this->loadHelper('modules');
$this->loadHelper('format');

JLoader::import('joomla.utilities.date');

if (!property_exists($this, 'extensions'))
{
	$this->extensions = array();
}
?>

<div id="akeebasubs" class="subscriptions">
	<h2 class="pageTitle"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_TITLE')?></h2>
	<form action="<?php echo JRoute::_('index.php?option=com_akeebasubs&view=subscriptions') ?>" method="post" class="adminform" name="adminForm" id="adminForm">
	<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />

	<table class="table table-striped" width="100%">
		<thead>
			<tr>
				<th width="40px">
					<?php echo JText::_('COM_AKEEBASUBS_COMMON_ID')?>
				</th>
				<th width="100px">
					<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_LEVEL')?>
				</th>
				<th width="60px">
					<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_STATE')?>
				</th>
				<th width="80px">
					<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_PUBLISH_UP')?>
				</th>
				<th width="80px">
					<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_PUBLISH_DOWN')?>
				</th>
				<th width="40px">
					<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_ENABLED')?>
				</th>
				<th>
					<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_ACTIONS')?>
				</th>
			</tr>
		</thead>

		<tfoot>
			<tr>
				<td colspan="20">
					<?php if($this->pagination->total > 0) echo $this->pagination->getListFooter() ?>
				</td>
			</tr>
		</tfoot>

		<tbody>
			<?php if(count($this->items)): ?>
			<?php $m = 1; $i = 0; ?>

			<?php foreach(array('active', 'waiting', 'pending', 'expired') as $area): ?>
			<?php if (!count($this->sortTable[$area])) continue; ?>
			<tr>
				<td colspan="7">
					<h4><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_AREAHEADING_' . $area) ?></h4>
				</td>
			</tr>
			<?php foreach($this->items as $subscription):?>
			<?php
				if (!in_array($subscription->akeebasubs_subscription_id, $this->sortTable[$area]))
				{
					continue;
				}

				$m = 1 - $m;
				$email = trim($subscription->email);
				$email = strtolower($email);
				$rowClass = ($subscription->enabled) ? '' : 'expired';

				$canRenew = AkeebasubsHelperCparams::getParam('showrenew', 1) ? true : false;
				$level = $this->allLevels[$subscription->akeebasubs_level_id];
				if ($level->only_once)
				{
					$canRenew = false;
				}
				elseif (!$level->enabled)
				{
					$canRenew = false;
				}

				$jPublishUp = new JDate($subscription->publish_up);
			?>
			<tr class="row<?php echo $m?> <?php echo $rowClass?>">
				<td align="left">
					<?php echo sprintf('%05u', (int)$subscription->akeebasubs_subscription_id)?>
				</td>
				<td>
					<?php if ($level->content_url): ?>
					<a href="<?php echo $this->escape($level->content_url) ?>">
					<?php endif; ?>
					<?php echo $this->escape($subscription->title)?>
					<?php if ($level->content_url): ?>
					</a>
					<?php endif; ?>
				</td>
				<td>
					<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_STATE_'.$subscription->state)?>
				</td>
				<td>
					<?php if(empty($subscription->publish_up) || ($subscription->publish_up == '0000-00-00 00:00:00')):?>
					&mdash;
					<?php else:?>
					<?php echo AkeebasubsHelperFormat::date($subscription->publish_up) ?>
					<?php endif;?>
				</td>
				<td>
					<?php if(empty($subscription->publish_up) || ($subscription->publish_down == '0000-00-00 00:00:00')):?>
					&mdash;
					<?php else:?>
					<?php echo AkeebasubsHelperFormat::date($subscription->publish_down) ?>
					<?php endif;?>
				</td>
				<td align="center">
					<?php if($subscription->enabled):?>
					<img src="<?php echo JURI::base(); ?>/media/com_akeebasubs/images/frontend/enabled.png" align="center" title="<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_ENABLED_ACTIVE') ?>" />
					<?php elseif($jPublishUp->toUnix() >= time()):?>
					<img src="<?php echo JURI::base(); ?>/media/com_akeebasubs/images/frontend/scheduled.png" align="center" title="<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_ENABLED_PENDING') ?>" />
					<?php else:?>
					<img src="<?php echo JURI::base(); ?>/media/com_akeebasubs/images/frontend/disabled.png" align="center" title="<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_ENABLED_INACTIVE') ?>" />
					<?php endif;?>
	            </td>
	            <td>
					<a class="btn btn-mini btn-info" href="<?php echo JRoute::_('index.php?option=com_akeebasubs&view=subscription&id='.$subscription->akeebasubs_subscription_id)?>">
						<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_ACTION_VIEW')?>
					</a>

					<?php if(array_key_exists($subscription->akeebasubs_subscription_id, $this->invoices)):
					$invoice = $this->invoices[$subscription->akeebasubs_subscription_id];
					$url2 = '';
					$target = '';
					if($invoice->extension == 'akeebasubs')
					{
						$url2 = JRoute::_('index.php?option=com_akeebasubs&view=invoices&task=download&id='.$invoice->akeebasubs_subscription_id);
						$url = JRoute::_('index.php?option=com_akeebasubs&view=invoice&task=read&id='.$invoice->akeebasubs_subscription_id.'&tmpl=component');
						$target = 'target="_blank"';
					}
					elseif(array_key_exists($invoice->extension, $this->extensions))
					{
						$url = JRoute::_(sprintf($this->extensions[$invoice->extension]['backendurl'], $invoice->invoice_no));
					}
					else
					{
						$url = '';
					}
					if(!empty($url)):
					?>
					<a class="btn btn-mini" href="<?php echo $url; ?>" <?php echo $target?>>
						<span class="icon icon-eye-open"></span>
	            		<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_ACTION_INVOICE')?>
	            	</a>
					<?php endif; ?>
					<?php if(!empty($url2)):
					?>
					<a class="btn btn-mini" href="<?php echo $url2; ?>">
						<span class="icon icon-file"></span>
	            		<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_ACTION_INVOICE')?>
	            	</a>
					<?php endif; ?>
					<?php endif; ?>

					<?php if(in_array($area, array('active','expired'))
						&& ($canRenew || ($level->only_once && !empty($level->renew_url))
					)): ?>
	            	<?php
						if ($canRenew)
						{
							$slug = F0FModel::getTmpInstance('Levels','AkeebasubsModel')
								->setId($subscription->akeebasubs_level_id)
								->getItem()
								->slug;
							$renewURL = JRoute::_('index.php?option=com_akeebasubs&view=level&slug='.$slug);
						}
						else
						{
							$renewURL = $this->escape($level->renew_url);
						}

					?>
	            	<a class="btn btn-mini btn-inverse" href="<?php echo $renewURL?>">
	            		<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_ACTION_RENEW')?>
	            	</a>
	            	<?php endif;?>

		            <?php
		                if($level->recurring && $subscription->allow_cancel):
			                $cancelURL = JRoute::_('index.php?option=com_akeebasubs&view=callback&task=cancel&paymentmethod='.$subscription->processor.'&sid='.$subscription->akeebasubs_subscription_id);
			        ?>
		            <a class="btn btn-mini btn-danger" href="<?php echo $cancelURL?>">
			            <?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_ACTION_CANCEL_RECURRING')?>
		            </a>
		            <?php endif; ?>
	            </td>
			</tr>
			<?php endforeach; ?>
			<?php endforeach; ?>
			<?php else: ?>
			<tr>
				<td colspan="20">
					<?php echo JText::_('COM_AKEEBASUBS_COMMON_NORECORDS') ?>
				</td>
			</tr>
			<?php endif; ?>
		</tbody>

	</table>
	</form>
</div>