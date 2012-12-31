<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

JHTML::_('behavior.tooltip');

$this->loadHelper('cparams');
$this->loadHelper('select');
$this->loadHelper('format');

$hasAjaxOrderingSupport = $this->hasAjaxOrderingSupport();
?>

<div class="row-fluid">
<div class="span12">

<form action="index.php" method="post" name="adminForm" id="adminForm">
<input type="hidden" name="option" value="com_akeebasubs" />
<input type="hidden" name="view" value="upgrades" />
<input type="hidden" id="task" name="task" value="browse" />
<input type="hidden" name="hidemainmenu" id="hidemainmenu" value="0" />
<input type="hidden" name="boxchecked" id="boxchecked" value="0" />
<input type="hidden" name="filter_order" id="filter_order" value="<?php echo $this->lists->order ?>" />
<input type="hidden" name="filter_order_Dir" id="filter_order_Dir" value="<?php echo $this->lists->order_Dir ?>" />
<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />

<table class="adminlist table table-striped" id="itemsList">
	<thead>
		<tr>
			<?php if($hasAjaxOrderingSupport !== false): ?>
			<th width="20px">
				<?php echo JHtml::_('grid.sort', '<i class="icon-menu-2"></i>', 'ordering', $this->lists->order_Dir, $this->lists->order, null, 'asc', 'JGRID_HEADING_ORDERING'); ?>
			</th>
			<?php endif; ?>
			<th width="10px"><?php echo JText::_('Num'); ?></th>
			<th width="16px"></th>
			<th>
				<?php echo  JHTML::_('grid.sort', 'COM_AKEEBASUBS_UPGRADES_FIELD_TITLE', 'title', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
			</th>
			<th>
				<?php echo  JHTML::_('grid.sort', 'COM_AKEEBASUBS_UPGRADES_FIELD_FROM_ID', 'from_id', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
			</th>
			<th>
				<?php echo  JHTML::_('grid.sort', 'COM_AKEEBASUBS_UPGRADES_FIELD_TO_ID', 'to_id', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
			</th>
			<th>
				<?php echo  JHTML::_('grid.sort', 'COM_AKEEBASUBS_UPGRADES_FIELD_MIN_PRESENCE', 'min_presence', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
			</th>
			<th>
				<?php echo  JHTML::_('grid.sort', 'COM_AKEEBASUBS_UPGRADES_FIELD_MAX_PRESENCE', 'max_presence', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
			</th>
			<th>
				<?php echo  JHTML::_('grid.sort', 'COM_AKEEBASUBS_UPGRADES_FIELD_VALUE', 'value', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
			</th>
			<th>
				<?php echo  JHTML::_('grid.sort', 'COM_AKEEBASUBS_UPGRADES_FIELD_COMBINE', 'combine', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
			</th>
			<th width="8%">
				<?php echo JHTML::_('grid.sort', 'JPUBLISHED', 'enabled', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
			</th>
			<?php if($hasAjaxOrderingSupport === false): ?>
			<th width="8%">
				<?php echo JHTML::_('grid.sort', 'JFIELD_ORDERING_LABEL', 'ordering', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
				<?php echo JHTML::_('grid.order', $this->items); ?>
			</th>
			<?php endif; ?>
		</tr>
		<tr>
			<?php if($hasAjaxOrderingSupport !== false): ?>
			<td></td>
			<?php endif; ?>
			<td></td>
			<td>
				<input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this);" />
			</td>
			<td>
				<input type="text" name="search" id="search"
					value="<?php echo $this->escape($this->getModel()->getState('search',''));?>"
					class="input-medium" onchange="document.adminForm.submit();"
					placeholder="<?php echo JText::_('COM_AKEEBASUBS_UPGRADES_FIELD_TITLE')?>"
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
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td>
				<?php echo AkeebasubsHelperSelect::published($this->getModel()->getState('enabled',''), 'enabled', array('onchange'=>'this.form.submit();', 'class'=>'input-medium')) ?>
			</td>
			<?php if($hasAjaxOrderingSupport === false): ?>
			<td></td>
			<?php endif; ?>
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
		<?php $m = 1; $i = -1; ?>
		<?php foreach($this->items as $upgrade): ?>
		<?php
			$i++; $m = 1-$m;
			$checkedOut = ($upgrade->locked_by != 0);
			$ordering = $this->lists->order == 'ordering';
			$upgrade->published = $upgrade->enabled;
		?>
		<tr class="<?php echo 'row'.$m; ?>">
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
					value="<?php echo $upgrade->ordering;?>" class="input-mini text-area-order " />
			<?php else : ?>
				<span class="sortable-handler inactive" >
					<i class="icon-menu"></i>
				</span>
			<?php endif; ?>
			</td>
			<?php endif; ?>
			<td align="center">
				<?php echo $upgrade->akeebasubs_upgrade_id; ?>
			</td>
			<td align="center">
				<?php echo JHTML::_('grid.id', $i, $upgrade->akeebasubs_upgrade_id, $checkedOut); ?>
			</td>
			<td align="left">
				<a href="index.php?option=com_akeebasubs&view=upgrade&id=<?php echo $upgrade->akeebasubs_upgrade_id; ?>">
					<strong><?php echo $this->escape($upgrade->title) ?></strong>
				</a>
			</td>
			<td>
				<?php echo AkeebasubsHelperFormat::formatLevel($upgrade->from_id) ?>
			</td>
			<td>
				<?php echo AkeebasubsHelperFormat::formatLevel($upgrade->to_id) ?>
			</td>
			<td>
				<?php echo(int)$upgrade->min_presence?>
			</td>
			<td>
				<?php echo(int)$upgrade->max_presence?>
			</td>
			<td align="center">
				<?php if($upgrade->type == 'value'): ?>
				<span class="akeebasubs-coupon-discount-value">
				<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'before'): ?>
				<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
				<?php endif; ?>
				<?php echo sprintf('%2.2f', (float)$upgrade->value) ?>
				<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'after'): ?>
				<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
				<?php endif; ?>
				</span>
				<?php else: ?>
				<span class="akeebasubs-coupon-discount-percent<?php if($upgrade->type == 'lastpercent'): ?> akeebasubs-coupon-discount-lastpercent<?php endif; ?>">
				<?php echo sprintf('%2.2f', (float)$upgrade->value) ?> %
				</span>
				<?php endif; ?>
			</td>
			<td align="center">
				<?php
				if($upgrade->combine) {
					if(version_compare(JVERSION, '1.6.0', 'ge')) {
						echo JHtml::_('image', 'admin/tick.png', null, null, true);
					} else {
						echo JHtml::_('image.administrator', 'images/tick.png', null, null, true);
					}
				} else {
					if(version_compare(JVERSION, '1.6.0', 'ge')) {
						echo JHtml::_('image', 'admin/publish_x.png', null, null, true);
					} else {
						echo JHtml::_('image.administrator', 'images/publish_x.png', null, null, true);
					}
				}
				?>
			</td>
			<td align="center">
				<?php echo JHTML::_('grid.published', $upgrade, $i); ?>
			</td>
			<?php if($hasAjaxOrderingSupport === false): ?>
			<td class="order" align="center">
				<span><?php echo $this->pagination->orderUpIcon( $i, true, 'orderup', 'Move Up', $ordering ); ?></span>
				<span><?php echo $this->pagination->orderDownIcon( $i, $count, true, 'orderdown', 'Move Down', $ordering ); ?></span>
				<?php $disabled = $ordering ?  '' : 'disabled="disabled"'; ?>
				<input type="text" name="order[]" size="5" value="<?php echo $upgrade->ordering;?>" <?php echo $disabled ?> class="text_area" style="text-align: center" />
			</td>
			<?php endif; ?>
		</tr>
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
</div>