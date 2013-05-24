<?
/**
 * @package		mod_akeebasubs
 * @copyright 	Copyright (c) 2011-2013 Sander Potjer
 * @license 	GNU General Public License version 3 or later
 */

defined('_JEXEC') or die();

FOFTemplateUtils::addCSS('media://com_akeebasubs/css/backend.css?'.AKEEBASUBS_VERSIONHASH);
?>

<table class="adminlist">
	<thead>
		<tr>
			<th>
				<?php echo JText::_( 'COM_AKEEBASUBS_COMMON_ID' ); ?>
			</th>
			<th>
				<?php echo JText::_( 'COM_AKEEBASUBS_SUBSCRIPTIONS_LEVEL' ); ?>
			</th>
			<th>
				<?php echo JText::_( 'COM_AKEEBASUBS_SUBSCRIPTIONS_USER' ); ?>
			</th>
			<th>
				<?php echo JText::_( 'COM_AKEEBASUBS_SUBSCRIPTION_NET_AMOUNT' ); ?>
			</th>
			<th>
				<?php echo JText::_( 'COM_AKEEBASUBS_SUBSCRIPTION_CREATED_ON' ); ?>
			</th>
        </tr>
	</thead>
	<tbody>
		<?php if(count($items)): ?>
		<?php $m = 1; $i = 0; ?>
		<?php foreach ($items as $subscription) : ?>
		<?php
			$m = 1 - $m;
			$rowClass = '';
			if (!$subscription->enabled)
			{
				if($subscription->state == 'C')
				{
					$rowClass = 'pending-renewal';
				}
				else
				{
					$rowClass = 'expired';
				}
			}

			$users = FOFModel::getTmpInstance('Users','AkeebasubsModel')
				->user_id($subscription->user_id)
				->getList();
			if(empty($users)) {
				$user_id = 0;
			} else {
				foreach($users as $user) {
					$user_id = $user->akeebasubs_user_id;
					break;
				}
			}
		?>
        <tr class="row<?php echo $m?> <?php echo $rowClass?>">
	        <td>
	      		<a href="index.php?option=com_akeebasubs&view=subscription&id=<?php echo $subscription->akeebasubs_subscription_id ?>">
					<?php echo sprintf('%05u', (int)$subscription->akeebasubs_subscription_id)?>
				</a>
	        </td>
	        <td>
	          	<a href="index.php?option=com_akeebasubs&view=level&id=<?php echo $subscription->akeebasubs_level_id ?>">
					<?php echo htmlspecialchars($subscription->title, ENT_COMPAT, 'UTF-8')?>
				</a>
	        </td>
	        <td>
				<a href="<?php echo JRoute::_('index.php?option=com_akeebasubs&view=user&id='.$user_id)?>" class="title">
					<strong><?php echo htmlspecialchars($subscription->username, ENT_COMPAT, 'UTF-8')?></strong>
					<br/><?php echo htmlspecialchars($subscription->name, ENT_COMPAT, 'UTF-8')?>
				</a>
	        </td>
	        <td>
				<?php echo sprintf('%2.2f', (float)$subscription->net_amount) ?> <?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
	        </td>
	        <td>
				<?php echo AkeebasubsHelperFormat::date($subscription->created_on, 'Y-m-d H:i'); ?>
	        </td>
		</tr>
		<?php endforeach; ?>
		<?php else: ?>
		<tr>
			<td colspan="5">
				<?php echo JText::_('COM_AKEEBASUBS_COMMON_NORECORDS') ?>
			</td>
		</tr>
		<?php endif; ?>
	</tbody>
</table>