<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

JHtml::_('behavior.tooltip');
if(version_compare(JVERSION, '3.0', 'ge')) {
	JHTML::_('behavior.framework');
} else {
	JHTML::_('behavior.mootools');
}

$this->loadHelper('select');
$this->loadHelper('cparams');
$this->loadHelper('format');

$hasAjaxOrderingSupport = $this->hasAjaxOrderingSupport();
?>
<div class="row-fluid">
<div class="span12">

<form action="index.php" method="post" name="adminForm" id="adminForm">
<input type="hidden" name="option" value="com_akeebasubs" />
<input type="hidden" name="view" value="coupons" />
<input type="hidden" id="task" name="task" value="browse" />
<input type="hidden" name="hidemainmenu" id="hidemainmenu" value="0" />
<input type="hidden" name="boxchecked" id="boxchecked" value="0" />
<input type="hidden" name="filter_order" id="filter_order" value="<?php echo $this->lists->order ?>" />
<input type="hidden" name="filter_order_Dir" id="filter_order_Dir" value="<?php echo $this->lists->order_Dir ?>" />
<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />

<a href="index.php?option=com_akeebasubs&view=makecoupons" class="btn btn-success">
	<i class="icon icon-cog icon-white"></i>
	<?php echo JText::_('COM_AKEEBASUBS_TITLE_MAKECOUPONS')?>
</a>

<table class="adminlist table table-striped" id="itemsList">
	<thead>
		<tr>
			<?php if($hasAjaxOrderingSupport !== false): ?>
			<th width="20px">
				<?php echo JHtml::_('grid.sort', '<i class="icon-menu-2"></i>', 'ordering', $this->lists->order_Dir, $this->lists->order, null, 'asc', 'JGRID_HEADING_ORDERING'); ?>
			</th>
			<?php endif; ?>
			<th>
				<?php echo JHTML::_('grid.sort', 'Num', 'akeebasubs_level_id', $this->lists->order_Dir, $this->lists->order, 'browse') ?>
			</th>
			<th></th>
			<th>
				<?php echo JHTML::_('grid.sort', 'COM_AKEEBASUBS_COUPONS_FTITLE', 'title', $this->lists->order_Dir, $this->lists->order, 'browse') ?>
			</th>
			<th width="8%">
				<?php echo JHTML::_('grid.sort', 'COM_AKEEBASUBS_COUPONS_COUPON', 'coupon', $this->lists->order_Dir, $this->lists->order, 'browse') ?>
			</th>
			<th width="8%">
				<?php echo JHTML::_('grid.sort', 'COM_AKEEBASUBS_COUPONS_VALUE', 'value', $this->lists->order_Dir, $this->lists->order, 'browse') ?>
			</th>
			<th>
				<?php echo JText::_('COM_AKEEBASUBS_COUPONS_LIMITS') ?>
			</th>
			<?php if($hasAjaxOrderingSupport === false): ?>
			<th width="8%">
				<?php echo JHTML::_('grid.sort', 'JFIELD_ORDERING_LABEL', 'ordering', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
				<?php echo JHTML::_('grid.order', $this->items); ?>
			</th>
			<?php endif; ?>
			<th>
				<?php echo JHTML::_('grid.sort', 'COM_AKEEBASUBS_COUPONS_PUBLISH_UP', 'publish_up', $this->lists->order_Dir, $this->lists->order, 'browse') ?>
			</th>
			<th>
				<?php echo JHTML::_('grid.sort', 'COM_AKEEBASUBS_COUPONS_PUBLISH_DOWN', 'publish_down', $this->lists->order_Dir, $this->lists->order, 'browse') ?>
			</th>
			<th width="8%">
				<?php echo JHTML::_('grid.sort', 'JPUBLISHED', 'enabled', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
			</th>			
		</tr>
		<tr>
			<?php if($hasAjaxOrderingSupport !== false): ?>
			<td></td>
			<?php endif; ?>
			<td></td>
			<td>
				<input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this);" />
			</td>
			<td colspan="2">
				<input type="text" name="search" id="search"
					value="<?php echo $this->escape($this->getModel()->getState('search',''));?>"
					class="input-medium" onchange="document.adminForm.submit();"
					placeholder="<?php echo JText::_('COM_AKEEBASUBS_COUPONS_FTITLE') ?> / <?php echo JText::_('COM_AKEEBASUBS_COUPONS_COUPON') ?>"
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
			<td></td>
			<td></td>
			<?php if($hasAjaxOrderingSupport === false): ?>
			<td></td>
			<?php endif; ?>
			<td></td>
			<td></td>
			<td>
				<?php echo AkeebasubsHelperSelect::published($this->getModel()->getState('enabled',''), 'enabled', array('onchange'=>'this.form.submit();', 'class'=>'input-medium')) ?>
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
			<?php if($hasAjaxOrderingSupport !== false): ?>
			<td class="order nowrap center hidden-phone">
			<?php if ($this->perms->editstate) :
				$disableClassName = '';
				$disabledLabel	  = '';
				if (!$hasAjaxOrderingSupport['saveOrder']) :
					$disabledLabel    = JText::_('JORDERINGDISABLED');
					$disableClassName = 'inactive tip-top';
				endif; ?>
				<span class="sortable-handler <?php echo $disableClassName?>" title="<?php echo $disabledLabel?>" rel="tooltip">
					<i class="icon-menu"></i>
				</span>
				<input type="text" style="display:none"  name="order[]" size="5"
					value="<?php echo $coupon->ordering;?>" class="input-mini text-area-order " />
			<?php else : ?>
				<span class="sortable-handler inactive" >
					<i class="icon-menu"></i>
				</span>
			<?php endif; ?>
			</td>
			<?php endif; ?>
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
			<?php if($hasAjaxOrderingSupport === false): ?>
			<td class="order" align="center">
				<span><?php echo $this->pagination->orderUpIcon( $i, true, 'orderup', 'Move Up', $ordering ); ?></span>
				<span><?php echo $this->pagination->orderDownIcon( $i, $count, true, 'orderdown', 'Move Down', $ordering ); ?></span>
				<?php $disabled = $ordering ?  '' : 'disabled="disabled"'; ?>
				<input type="text" name="order[]" size="5" value="<?php echo $coupon->ordering;?>" <?php echo $disabled ?> class="text_area" style="text-align: center" />
			</td>
			<?php endif; ?>
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
	
</div>
</div>