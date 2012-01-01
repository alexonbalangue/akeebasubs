<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

FOFTemplateUtils::addCSS('media://com_akeebasubs/css/backend.css?'.AKEEBASUBS_VERSIONHASH);
FOFTemplateUtils::addJS('media://com_akeebasubs/js/backend.js?'.AKEEBASUBS_VERSIONHASH);
FOFTemplateUtils::addJS('media://com_akeebasubs/js/akeebajq.js?'.AKEEBASUBS_VERSIONHASH);

JHTML::_('behavior.tooltip');

$this->loadHelper('cparams');
$this->loadHelper('select');
$this->loadHelper('format');

?>
<form action="index.php" method="post" name="adminForm">
<input type="hidden" name="option" value="com_akeebasubs" />
<input type="hidden" name="view" value="upgrades" />
<input type="hidden" id="task" name="task" value="browse" />
<input type="hidden" name="hidemainmenu" id="hidemainmenu" value="0" />
<input type="hidden" name="boxchecked" id="boxchecked" value="0" />
<input type="hidden" name="filter_order" id="filter_order" value="<?php echo $this->lists->order ?>" />
<input type="hidden" name="filter_order_Dir" id="filter_order_Dir" value="<?php echo $this->lists->order_Dir ?>" />
<input type="hidden" name="<?php echo JUtility::getToken();?>" value="1" />

<table class="adminlist">
	<thead>
		<tr>
			<th width="10px"><?php echo JText::_('Num'); ?></th>
			<th width="16px"></th>
			<th>
				<?php echo  JHTML::_('grid.sort', 'COM_AKEEBASUBS_UPGRADES_FIELD_TITLE', 'title', $this->lists->order_Dir, $this->lists->order); ?>
			</th>
			<th>
				<?php echo  JHTML::_('grid.sort', 'COM_AKEEBASUBS_UPGRADES_FIELD_FROM_ID', 'from_id', $this->lists->order_Dir, $this->lists->order); ?>
			</th>
			<th>
				<?php echo  JHTML::_('grid.sort', 'COM_AKEEBASUBS_UPGRADES_FIELD_TO_ID', 'to_id', $this->lists->order_Dir, $this->lists->order); ?>
			</th>
			<th>
				<?php echo  JHTML::_('grid.sort', 'COM_AKEEBASUBS_UPGRADES_FIELD_MIN_PRESENCE', 'min_presence', $this->lists->order_Dir, $this->lists->order); ?>
			</th>
			<th>
				<?php echo  JHTML::_('grid.sort', 'COM_AKEEBASUBS_UPGRADES_FIELD_MAX_PRESENCE', 'max_presence', $this->lists->order_Dir, $this->lists->order); ?>
			</th>
			<th>
				<?php echo  JHTML::_('grid.sort', 'COM_AKEEBASUBS_UPGRADES_FIELD_VALUE', 'value', $this->lists->order_Dir, $this->lists->order); ?>
			</th>
			<th width="8%">
				<?php echo JHTML::_('grid.sort', 'Ordering', 'ordering', $this->lists->order_Dir, $this->lists->order); ?>
				<?php echo JHTML::_('grid.order', $this->items); ?>
			</th>			
			<th width="8%">
				<?php if(version_compare(JVERSION,'1.6.0','ge')):?>
				<?php echo JHTML::_('grid.sort', 'JPUBLISHED', 'enabled', $this->lists->order_Dir, $this->lists->order); ?>
				<?php else: ?>
				<?php echo JHTML::_('grid.sort', 'PUBLISHED', 'enabled', $this->lists->order_Dir, $this->lists->order); ?>
				<?php endif; ?>
			</th>
		</tr>
		<tr>
			<td></td>
			<td>
				<input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count( $this->items ) + 1; ?>);" />
			</td>
			<td>
				<input type="text" name="search" id="search"
					value="<?php echo $this->escape($this->getModel()->getState('search',''));?>"
					class="text_area" onchange="document.adminForm.submit();" />
				<button onclick="this.form.submit();">
					<?php echo version_compare(JVERSION, '1.6.0', 'ge') ? JText::_('JSEARCH_FILTER') : JText::_('Go'); ?>
				</button>
				<button onclick="document.adminForm.search.value='';this.form.submit();">
					<?php echo version_compare(JVERSION, '1.6.0', 'ge') ? JText::_('JSEARCH_RESET') : JText::_('Reset'); ?>
				</button>
			</td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
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
				<?php echo sprintf('%2.2f', (float)$upgrade->value) ?>
				<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
				</span>
				<?php else: ?>
				<span class="akeebasubs-coupon-discount-percent">
				<?php echo sprintf('%2.2f', (float)$upgrade->value) ?> %
				</span>
				<?php endif; ?>
			</td>
			<td align="center">
				<?php echo JHTML::_('grid.published', $upgrade, $i); ?>
			</td>
			<td class="order" align="center">
				<span><?php echo $this->pagination->orderUpIcon( $i, true, 'orderup', 'Move Up', $ordering ); ?></span>
				<span><?php echo $this->pagination->orderDownIcon( $i, $count, true, 'orderdown', 'Move Down', $ordering ); ?></span>
				<?php $disabled = $ordering ?  '' : 'disabled="disabled"'; ?>
				<input type="text" name="order[]" size="5" value="<?php echo $upgrade->ordering;?>" <?php echo $disabled ?> class="text_area" style="text-align: center" />
			</td>
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