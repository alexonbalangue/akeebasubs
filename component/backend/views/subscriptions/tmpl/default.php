<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

AkeebaStrapper::addJSfile('media://com_akeebasubs/js/blockui.js?'.AKEEBASUBS_VERSIONHASH);

JHTML::_('behavior.tooltip');

$this->loadHelper('cparams');
$this->loadHelper('select');
$this->loadHelper('format');
$this->loadHelper('image');

$couponsRaw = FOFModel::getTmpInstance('Coupons','AkeebasubsModel')
	->savestate(0)
	->limit(0)
	->limitstart(0)
	->getList();
$coupons = array();
foreach($couponsRaw as $coupon) {
	$coupons[$coupon->akeebasubs_coupon_id] = $coupon->coupon;
}
unset($couponsRaw);

$upgradesRaw = FOFModel::getTmpInstance('Upgrades','AkeebasubsModel')
	->savestate(0)
	->limit(0)
	->limitstart(0)
	->getList();
$upgrades = array();
foreach($upgradesRaw as $upgrade) {
	$upgrades[$upgrade->akeebasubs_upgrade_id] = $upgrade->title;
}
unset($upgradesRaw);

?>

<div class="row-fluid">
<div class="span12">

<form action="index.php" method="post" name="adminForm" id="adminForm">
<input type="hidden" name="option" value="com_akeebasubs" />
<input type="hidden" name="view" value="subscriptions" />
<input type="hidden" id="task" name="task" value="browse" />
<input type="hidden" name="hidemainmenu" id="hidemainmenu" value="0" />
<input type="hidden" name="boxchecked" id="boxchecked" value="0" />
<input type="hidden" name="filter_order" id="filter_order" value="<?php echo $this->lists->order ?>" />
<input type="hidden" name="filter_order_Dir" id="filter_order_Dir" value="<?php echo $this->lists->order_Dir ?>" />
<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />

<table class="adminlist table table-striped">
	<thead>
		<tr>
			<th width="16px"></th>
			<th>
				<?php echo JHTML::_('grid.sort', 'COM_AKEEBASUBS_COMMON_ID', 'akeebasubs_subscription_id', $this->lists->order_Dir, $this->lists->order, 'browse') ?>
			</th>
			<th>
				<?php echo JHTML::_('grid.sort', 'COM_AKEEBASUBS_SUBSCRIPTIONS_LEVEL', 'akeebasubs_level_id', $this->lists->order_Dir, $this->lists->order, 'browse') ?>
			</th>
			<th>
				<?php echo JHTML::_('grid.sort', 'COM_AKEEBASUBS_SUBSCRIPTIONS_USER', 'user_id', $this->lists->order_Dir, $this->lists->order, 'browse') ?>
			</th>
			<th width="30px">
				<?php echo JHTML::_('grid.sort', 'COM_AKEEBASUBS_SUBSCRIPTIONS_STATE', 'state', $this->lists->order_Dir, $this->lists->order, 'browse') ?>
			</th>
			<th width="60px">
				<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_DISCOUNT') ?>
			</th>
			<th width="60px">
				<?php echo JHTML::_('grid.sort', 'COM_AKEEBASUBS_SUBSCRIPTIONS_AMOUNT', 'gross_amount', $this->lists->order_Dir, $this->lists->order, 'browse') ?>
			</th>
			<th width="120px">
				<?php echo JHTML::_('grid.sort', 'COM_AKEEBASUBS_SUBSCRIPTIONS_PUBLISH_UP', 'publish_up', $this->lists->order_Dir, $this->lists->order, 'browse') ?><br/>
				<?php echo JHTML::_('grid.sort', 'COM_AKEEBASUBS_SUBSCRIPTIONS_PUBLISH_DOWN', 'publish_down', $this->lists->order_Dir, $this->lists->order, 'browse') ?>
			</th>
			<th width="120px">
				<?php echo JHTML::_('grid.sort', 'COM_AKEEBASUBS_SUBSCRIPTION_CREATED_ON', 'created_on', $this->lists->order_Dir, $this->lists->order, 'browse') ?>
			</th>
			<th width="8%">
				<?php echo JHTML::_('grid.sort', 'JPUBLISHED', 'enabled', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
			</th>
		</tr>
		<tr>
			<td>
				<input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this);" />
			</td>
			<td></td>
			<td>
				<?php echo AkeebasubsHelperSelect::subscriptionlevels($this->getModel()->getState('level',''), 'level', array('onchange'=>'this.form.submit();', 'class'=>'input-medium')) ?>
			</td>
			<td>
				<input type="text" name="search" id="search"
					value="<?php echo $this->escape($this->getModel()->getState('search',''));?>"
					class="input-small" onchange="document.adminForm.submit();"
					placeholder="<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_USER') ?>"
					/>
				<nobr>
				<button class="btn btn-mini" onclick="this.form.submit();">
					<?php echo JText::_('JSEARCH_FILTER'); ?>
				</button>
				<button class="btn btn-mini" onclick="document.adminForm.search.value='';this.form.submit();">
					<?php echo JText::_('JSEARCH_RESET'); ?>
				</button>
				</nobr>
			</td>
			<td  colspan="2">
				<?php echo AkeebasubsHelperSelect::paystates($this->getModel()->getState('paystate',''), 'paystate', array('onchange'=>'this.form.submit();', 'class'=>'input-medium')) ?>

				<input type="text" name="paykey" id="paykey"
					value="<?php echo $this->escape($this->getModel()->getState('paykey',''));?>"
					onchange="document.adminForm.submit();"
					class="input-medium"
					title="<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_PROCESSOR_KEY')?>"
					placeholder="<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_PROCESSOR_KEY')?>"
				/>

				<?php echo AkeebasubsHelperSelect::discountmodes('filter_discountmode', $this->getModel()->getState('filter_discountmode','') , array('onchange'=>'this.form.submit();', 'class'=>'input-medium')) ?>
				<input type="text" name="filter_discountcode" id="paykey"
					value="<?php echo $this->escape($this->getModel()->getState('filter_discountcode',''));?>"
					class="input-medium" onchange="document.adminForm.submit();"
					title="<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_DISCOUNTCODE')?>"
					placeholder="<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_DISCOUNTCODE')?>" />
			</td>
			<td></td>
			<td>
				<?php echo JHTML::_('calendar', $this->getModel()->getState('publish_up',''), 'publish_up', 'publish_up', '%Y-%m-%d', array('onchange' => 'this.form.submit();', 'class'=>'input-small')); ?>
				<br/><?php echo JHTML::_('calendar', $this->getModel()->getState('publish_down',''), 'publish_down', 'publish_down', '%Y-%m-%d', array('onchange' => 'this.form.submit();', 'class'=>'input-small')); ?>
			</td>
			<td>
				<?php echo JHTML::_('calendar', $this->getModel()->getState('since',''), 'since', 'since', '%Y-%m-%d', array('onchange' => 'this.form.submit();', 'class'=>'input-small')); ?>
			</td>
			<td>
				<?php echo AkeebasubsHelperSelect::published($this->getModel()->getState('enabled',''), 'enabled', array('onchange'=>'this.form.submit();', 'class'=>'input-small')) ?>
			</td>
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
		<?php $m = 1; $i = -1; ?>
		<?php foreach($this->items as $subscription):?>
		<?php
			$i++;
			$m = 1 - $m;
			$email = trim($subscription->email);
			$email = strtolower($email);
			$gravatarHash = md5($email);
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

			$subscription->published = $subscription->enabled;

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
			<td align="center">
				<?php echo JHTML::_('grid.id', $i, $subscription->akeebasubs_subscription_id); ?>
			</td>
			<td align="left">
				<span class="editlinktip hasTip" title="#<?php echo (int)$subscription->akeebasubs_subscription_id?>::<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_EDIT_TOOLTIP')?>">
					<a href="index.php?option=com_akeebasubs&view=subscription&id=<?php echo $subscription->akeebasubs_subscription_id ?>" class="title">
						<strong><?php echo sprintf('%05u', (int)$subscription->akeebasubs_subscription_id)?></strong>
	    			</a>
    			</span>
			</td>
			<td>
				<span class="editlinktip hasTip" title="<?php echo $this->escape($subscription->title); ?>::<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_LEVEL_EDIT_TOOLTIP')?>">
					<img src="<?php echo AkeebasubsHelperImage::getURL($subscription->image)?>" width="32" height="32" class="sublevelpic" />
					<a href="index.php?option=com_akeebasubs&view=level&id=<?php echo $subscription->akeebasubs_level_id ?>" class="subslevel">
    					<?php echo $this->escape($subscription->title)?>
    				</a>
    			</span>
			</td>
			<td>
				<span class="editlinktip hasTip" title="<?php echo $this->escape($subscription->username) ?>::<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_USER_EDIT_TOOLTIP')?>">
					<?php if(AkeebasubsHelperCparams::getParam('gravatar',true)):?>
						<?php if(JURI::getInstance()->getScheme() == 'http'): ?>
							<img src="http://www.gravatar.com/avatar/<?php echo md5(strtolower($subscription->email))?>.jpg?s=32&d=mm" align="left" class="gravatar"  />
						<?php else: ?>
							<img src="https://secure.gravatar.com/avatar/<?php echo md5(strtolower($subscription->email))?>.jpg?s=32&d=mm" align="left" class="gravatar"  />
						<?php endif; ?>
					<?php endif; ?>
					<a href="index.php?option=com_akeebasubs&view=user&id=<?php echo $user_id ?>" class="title">
					<strong><?php echo $this->escape($subscription->username)?></strong>
					<span class="small">[<?php echo $subscription->user_id?>]</span>
					<br/>
					<?php echo $this->escape($subscription->name)?>
					<?php if(!empty($subscription->business_name)):?>
					<br/>
					<?php echo $this->escape($subscription->business_name)?>
					&bull;
					<?php echo $this->escape($subscription->vatnumber)?>
					<?php endif; ?>
					<br/>
					<?php echo $this->escape($subscription->email)?>
					</a>
				</span>
			</td>
			<td class="akeebasubs-subscription-paymentstatus">
				<span class="akeebasubs-payment akeebasubs-payment-<?php echo strtolower($subscription->state) ?> hasTip"
					title="<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_STATE_'.$subscription->state)?>::<?php echo $subscription->processor?> &bull; <?php echo $subscription->processor_key?>">
				</span>

				<span class="akeebasubs-subscription-processor">
					<?php echo $this->escape($subscription->processor) ?>
				</span>

			</td>
			<td>
				<?php if($subscription->akeebasubs_coupon_id > 0):?>
				<span class="akeebasubs-subscription-discount-coupon" title="<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_DISCOUNT_COUPON') ?>">
					<span class="discount-icon"></span>
					<?php echo $this->escape($coupons[$subscription->akeebasubs_coupon_id]) ?>
				</span>
				<?php elseif($subscription->akeebasubs_upgrade_id > 0):?>
				<span class="akeebasubs-subscription-discount-upgrade" title="<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_DISCOUNT_UPGRADE') ?>">
					<span class="discount-icon"></span>
					<?php echo $this->escape($upgrades[$subscription->akeebasubs_upgrade_id]) ?>
				</span>
				<?php else: ?>
				<span class="akeebasubs-subscription-discount-none">
					<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_DISCOUNT_NONE') ?>
				</span>
				<?php endif; ?>
			</td>

			<td class="akeebasubs-subscription-amount">
				<?php if($subscription->net_amount > 0): ?>

				<?php if($subscription->discount_amount > 0): ?>
				<span class="akeebasubs-subscription-netamount">
				<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'before'): ?>
				<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
				<?php endif; ?>
				<?php echo sprintf('%2.2f', (float)$subscription->prediscount_amount) ?>
				<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'after'): ?>
				<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
				<?php endif; ?>
				</span>
				<span class="akeebasubs-subscription-discountamount">
				<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'before'): ?>
				<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
				<?php endif; ?>
				- <?php echo sprintf('%2.2f', (float)$subscription->discount_amount) ?>
				<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'after'): ?>
				<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
				<?php endif; ?>
				</span>
				<?php else: ?>
				<span class="akeebasubs-subscription-netamount">
				<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'before'): ?>
				<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
				<?php endif; ?>
				<?php echo sprintf('%2.2f', (float)$subscription->net_amount) ?>
				<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'after'): ?>
				<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
				<?php endif; ?>
				</span>
				<?php endif; ?>
				<span class="akeebasubs-subscription-taxamount">
				<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'before'): ?>
				<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
				<?php endif; ?>
				<?php echo sprintf('%2.2f', (float)$subscription->tax_amount) ?>
				<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'after'): ?>
				<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
				<?php endif; ?>
				</span>
				<?php endif; ?>
				<span class="akeebasubs-subscription-grossamount">
				<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'before'): ?>
				<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
				<?php endif; ?>
				<?php echo sprintf('%2.2f', (float)$subscription->gross_amount) ?>
				<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'after'): ?>
				<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
				<?php endif; ?>
				</span>
			</td>
			<td>
				<div class="akeebasubs-susbcription-publishup">
					<?php echo AkeebasubsHelperFormat::date($subscription->publish_up, 'Y-m-d H:i') ?>
				</div>
				<div class="akeebasubs-susbcription-publishdown">
					<?php echo AkeebasubsHelperFormat::date($subscription->publish_down, 'Y-m-d H:i') ?>
				</div>
			</td>
			<td>
				<?php echo AkeebasubsHelperFormat::date($subscription->created_on, 'Y-m-d H:i') ?>
			</td>
			<td align="center">
				<?php echo JHTML::_('grid.published', $subscription, $i); ?>
			</td>
		</tr>
		<?php endforeach;?>
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

<div id="refreshMessage" style="display:none">
	<h3><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_SUBREFRESH_TITLE');?></h3>
	<p><img id="asriSpinner" src="<?php echo JURI::base()?>../media/com_akeebasubs/images/throbber.gif" align="center" /></p>
	<p><span id="asriPercent">0</span><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_SUBREFRESH_PROGRESS')?></p>
</div>

</div>
</div>

<script type="text/javascript">
var akeebasubs_token = "<?php echo JFactory::getSession()->getFormToken();?>";

(function($) {
	$(document).ready(function(){
		$('#toolbar-subrefresh').click(akeebasubs_refresh_integrations);
	});
})(akeeba.jQuery);
</script>