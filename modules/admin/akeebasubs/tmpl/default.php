<?php
/**
 * @package        mod_akeebasubs
 * @copyright      Copyright (c) 2011-2016 Sander Potjer
 * @license        GNU General Public License version 3 or later
 */

defined('_JEXEC') or die();

/** @var FOF30\Container\Container $container */
/** @var \FOF30\Model\DataModel\Collection $items */

$container->template->addCSS('media://com_akeebasubs/css/backend.css');
?>

<table class="adminlist table table-striped">
	<thead>
	<tr>
		<th>
			<?php echo JText::_('COM_AKEEBASUBS_COMMON_ID'); ?>
		</th>
		<th>
			<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_LEVEL'); ?>
		</th>
		<th>
			<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_USER'); ?>
		</th>
		<th>
			<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_NET_AMOUNT'); ?>
		</th>
		<th>
			<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_CREATED_ON'); ?>
		</th>
	</tr>
	</thead>
	<tbody>
	<?php if ($items->count()): ?>
		<?php $m = 1;
		$i       = 0;
		/** @var \Akeeba\Subscriptions\Admin\Model\Subscriptions $subscription */
		foreach ($items as $subscription) :

			$m        = 1 - $m;
			$rowClass = '';

			if (!$subscription->enabled)
			{
				if ($subscription->state == 'C')
				{
					$rowClass = 'pending-renewal';
				}
				else
				{
					$rowClass = 'expired';
				}
			}

			$level = $subscription->level;

			if (!is_object($level))
			{
				$level = $container->factory->model('Levels')->tmpInstance();
			}

			$user = $subscription->user;

			if (!is_object($user))
			{
				$user = $container->factory->model('Users')->tmpInstance();
			}

			?>
			<tr class="row<?php echo $m ?> <?php echo $rowClass ?>">
				<td>
					<a href="index.php?option=com_akeebasubs&view=Subscription&id=<?php echo $subscription->akeebasubs_subscription_id ?>">
						<?php echo sprintf('%05u', (int) $subscription->akeebasubs_subscription_id) ?>
					</a>
				</td>
				<td>
					<a href="index.php?option=com_akeebasubs&view=Level&id=<?php echo $subscription->akeebasubs_level_id ?>">
						<?php echo htmlspecialchars($subscription->level->title, ENT_COMPAT, 'UTF-8') ?>
					</a>
				</td>
				<td>
					<?php echo htmlspecialchars($subscription->user->username, ENT_COMPAT, 'UTF-8') ?>
				</td>
				<td>
					<?php echo sprintf('%2.2f', (float) $subscription->net_amount) ?> <?php echo $container->params->get('currencysymbol', '€') ?>
				</td>
				<td>
					<?php echo \Akeeba\Subscriptions\Admin\Helper\Format::date($subscription->created_on, 'Y-m-d H:i'); ?>
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
