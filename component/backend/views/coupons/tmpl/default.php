<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

FOFTemplateUtils::addCSS('media://com_akeebasubs/css/backend.css?'.AKEEBASUBS_VERSIONHASH);
FOFTemplateUtils::addJS('media://com_akeebasubs/js/akeebajq.js?'.AKEEBASUBS_VERSIONHASH);
FOFTemplateUtils::addJS('media://com_akeebasubs/js/backend.js?'.AKEEBASUBS_VERSIONHASH);
JHtml::_('behavior.tooltip');
if(version_compare(JVERSION, '3.0', 'ge')) {
	JHTML::_('behavior.framework');
} else {
	JHTML::_('behavior.mootools');
}

$this->loadHelper('select');
$this->loadHelper('cparams');
$this->loadHelper('format');

?>
<form action="index.php" method="post" name="adminForm" id="adminForm">
<input type="hidden" name="option" value="com_akeebasubs" />
<input type="hidden" name="view" value="coupons" />
<input type="hidden" id="task" name="task" value="browse" />
<input type="hidden" name="hidemainmenu" id="hidemainmenu" value="0" />
<input type="hidden" name="boxchecked" id="boxchecked" value="0" />
<input type="hidden" name="filter_order" id="filter_order" value="<?php echo $this->lists->order ?>" />
<input type="hidden" name="filter_order_Dir" id="filter_order_Dir" value="<?php echo $this->lists->order_Dir ?>" />
<input type="hidden" name="<?php echo JFactory::getSession()->getToken();?>" value="1" />

<table class="adminlist">
	<thead>
		<tr>
			<th>
				<?php echo JHTML::_('grid.sort', 'Num', 'akeebasubs_level_id', $this->lists->order_Dir, $this->lists->order) ?>
			</th>
			<th></th>
			<th>
				<?php echo JHTML::_('grid.sort', 'COM_AKEEBASUBS_COUPONS_FTITLE', 'title', $this->lists->order_Dir, $this->lists->order) ?>
			</th>
			<th width="8%">
				<?php echo JHTML::_('grid.sort', 'COM_AKEEBASUBS_COUPONS_COUPON', 'coupon', $this->lists->order_Dir, $this->lists->order) ?>
			</th>
			<th width="8%">
				<?php echo JHTML::_('grid.sort', 'COM_AKEEBASUBS_COUPONS_VALUE', 'value', $this->lists->order_Dir, $this->lists->order) ?>
			</th>
			<th>
				<?php echo JText::_('COM_AKEEBASUBS_COUPONS_LIMITS') ?>
			</th>
			<th width="8%">
				<?php echo JHTML::_('grid.sort', 'JFIELD_ORDERING_LABEL', 'ordering', $this->lists->order_Dir, $this->lists->order); ?>
				<?php echo JHTML::_('grid.order', $this->items); ?>
			</th>
			<th>
				<?php echo JHTML::_('grid.sort', 'COM_AKEEBASUBS_COUPONS_PUBLISH_UP', 'publish_up', $this->lists->order_Dir, $this->lists->order) ?>
			</th>
			<th>
				<?php echo JHTML::_('grid.sort', 'COM_AKEEBASUBS_COUPONS_PUBLISH_DOWN', 'publish_down', $this->lists->order_Dir, $this->lists->order) ?>
			</th>
			<th width="8%">
				<?php echo JHTML::_('grid.sort', 'JPUBLISHED', 'enabled', $this->lists->order_Dir, $this->lists->order); ?>
			</th>			
		</tr>
		<tr>
			<td></td>
			<td>
				<input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this);" />
			</td>
			<td colspan="2">
				<input type="text" name="search" id="search"
					value="<?php echo $this->escape($this->getModel()->getState('search',''));?>"
					class="text_area" onchange="document.adminForm.submit();" />
				<button onclick="this.form.submit();">
					<?php echo JText::_('JSEARCH_FILTER'); ?>
				</button>
				<button onclick="document.adminForm.search.value='';this.form.submit();">
					<?php echo JText::_('JSEARCH_RESET'); ?>
				</button>
			</td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td>
				<?php echo AkeebasubsHelperSelect::published($this->getModel()->getState('enabled',''), 'enabled', array('onchange'=>'this.form.submit();')) ?>
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
		<?php if($count = count($this->items)): ?>
		<?php $i = -1; $m = 0; ?>
		<?php foreach ($this->items as $coupon) : ?>
		<?php
			$i++; $m = 1-$m;
			$checkedOut = ($coupon->locked_by != 0);
			$ordering = $this->lists->order == 'ordering';
			$coupon->published = $coupon->enabled;
		?>
		<tr class="<?php echo  'row'.$m; ?>">
			<td align="center">
				<?php echo $coupon->akeebasubs_coupon_id; ?>
			</td>
			<td align="center">
				<?php echo JHTML::_('grid.id', $i, $coupon->akeebasubs_coupon_id, $checkedOut); ?>
			</td>
			<td align="left">
				<a href="index.php?option=com_akeebasubs&view=coupon&id=<?php echo $coupon->akeebasubs_coupon_id; ?>">
					<strong><?php echo  $this->escape($coupon->title) ?></strong>
				</a>
			</td>
			<td>
				<?php echo $this->escape($coupon->coupon) ?>
			</td>
			<td align="right">
				<?php if($coupon->type == 'value'): ?>
				<span class="akeebasubs-coupon-discount-value">
				<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'before'): ?>
				<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
				<?php endif; ?>
				<?php echo  sprintf('%2.2f', (float)$coupon->value) ?>
				<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'after'): ?>
				<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
				<?php endif; ?>
				</span>
				<?php else: ?>
				<span class="akeebasubs-coupon-discount-percent">
				<?php echo  sprintf('%2.2f', (float)$coupon->value) ?> %
				</span>
				<?php endif; ?>
			</td>
			<td>
				<?php
					$limits = array();
					if($coupon->user) $limits[] = JText::_('COM_AKEEBASUBS_COUPONS_LIMITS_USERS').' ('.JFactory::getUser($coupon->user)->username.')';
					if($coupon->subscriptions) $limits[] = JText::_('COM_AKEEBASUBS_COUPONS_LIMITS_LEVELS');
					if($coupon->hitslimit) $limits[] = JText::_('COM_AKEEBASUBS_COUPONS_LIMITS_HITS');
					if($coupon->userhits) $limits[] = JText::_('COM_AKEEBASUBS_COUPONS_LIMITS_USERHITS');
					
					$strLimits = implode(', ', $limits);
				?>
				<?php echo  $strLimits ?>
			</td>
			<td class="order" align="center">
				<span><?php echo $this->pagination->orderUpIcon( $i, true, 'orderup', 'Move Up', $ordering ); ?></span>
				<span><?php echo $this->pagination->orderDownIcon( $i, $count, true, 'orderdown', 'Move Down', $ordering ); ?></span>
				<?php $disabled = $ordering ?  '' : 'disabled="disabled"'; ?>
				<input type="text" name="order[]" size="5" value="<?php echo $coupon->ordering;?>" <?php echo $disabled ?> class="text_area" style="text-align: center" />
			</td>
			<td>
				<?php echo AkeebasubsHelperFormat::date($coupon->publish_up, 'Y-m-d H:i') ?>
			</td>
			<td>
				<?php echo AkeebasubsHelperFormat::date($coupon->publish_down, 'Y-m-d H:i') ?>
			</td>
			<td align="center">
				<?php echo JHTML::_('grid.published', $coupon, $i); ?>
			</td>			
		</tr>
		<?php endforeach; ?>	
		<?php else: ?>
		<tr>
			<td colspan="20">
				<?php echo  JText::_('COM_AKEEBASUBS_COMMON_NORECORDS') ?>
			</td>
		</tr>
		<?php endif; ?>
	</tbody>
</table>

</form>