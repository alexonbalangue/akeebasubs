<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

JHTML::_('behavior.calendar');
JHTML::_('behavior.tooltip');

$this->loadHelper('cparams');
$this->loadHelper('select');
$this->loadHelper('format');

$hasAjaxOrderingSupport = $this->hasAjaxOrderingSupport();
?>
<div class="row-fluid">
	<div class="span12">
		<a href="index.php?option=com_akeebasubs&view=taxconfigs" class="btn btn-primary">
			<i class="icon-white icon-plane"></i>
			<?php echo JText::_('COM_AKEEBASUBS_TITLE_TAXCONFIGS') ?>
		</a>
	</div>
</div>

<div class="row-fluid">
<div class="span12">

<form action="index.php" method="post" name="adminForm" id="adminForm">
<input type="hidden" name="option" value="com_akeebasubs" />
<input type="hidden" name="view" value="taxrules" />
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

			<th width="10px"><?php echo  JText::_('Num'); ?></th>
			<th width="16px"></th>
			<th>
				<?php echo  JHTML::_('grid.sort', 'COM_AKEEBASUBS_TAXRULES_LEVEL', 'country', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
			</th>
			<th>
				<?php echo  JHTML::_('grid.sort', 'COM_AKEEBASUBS_TAXRULES_COUNTRY', 'country', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
			</th>
			<th>
				<?php echo  JHTML::_('grid.sort', 'COM_AKEEBASUBS_TAXRULES_STATE', 'state', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
			</th>
			<th>
				<?php echo  JHTML::_('grid.sort', 'COM_AKEEBASUBS_TAXRULES_CITY', 'city', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
			</th>
			<th width="30px">
				<?php echo  JHTML::_('grid.sort', 'COM_AKEEBASUBS_TAXRULES_VIES', 'vies', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
			</th>
			<th width="60px">
				<?php echo  JHTML::_('grid.sort', 'COM_AKEEBASUBS_TAXRULES_TAXRATE', 'taxrate', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
			</th>
			<?php if($hasAjaxOrderingSupport === false): ?>
			<th width="50px">
				<?php echo JHTML::_('grid.sort', 'JFIELD_ORDERING_LABEL', 'ordering', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
				<?php echo JHTML::_('grid.order', $this->items); ?>
			</th>
			<?php endif; ?>
			<th width="100px">
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
			<td>
				<?php echo AkeebasubsHelperSelect::levels('akeebasubs_level_id', $this->getModel()->getState('akeebasubs_level_id',''), array('onchange'=>'this.form.submit();', 'class'=>'input-medium', 'include_all' => 1, 'include_clear' => 1)); ?>
			</td>
			<td>
				<?php echo AkeebasubsHelperSelect::countries($this->getModel()->getState('country',''), 'country', array('onchange'=>'this.form.submit();', 'class'=>'input-medium')); ?>
			</td>
			<td>
				<?php echo AkeebasubsHelperSelect::states($this->getModel()->getState('state',''), 'state', array('onchange'=>'this.form.submit();', 'class'=>'input-medium')); ?>
			</td>
			<td>
				<input type="text" name="search" id="search"
					value="<?php echo $this->escape($this->getModel()->getState('search',''));?>"
					class="input-medium" onchange="document.adminForm.submit();"
					placeholder="<?php echo JText::_('COM_AKEEBASUBS_TAXRULES_CITY')?>" />
				<nobr>
				<button class="btn btn-mini" onclick="this.form.submit();">
					<?php echo JText::_('JSEARCH_FILTER'); ?>
				</button>
				<button class="btn btn-mini" onclick="document.adminForm.search.value='';this.form.submit();">
					<?php echo JText::_('JSEARCH_RESET'); ?>
				</button>
				</nobr>
			</td>
			<td>
				<?php echo AkeebasubsHelperSelect::published($this->getModel()->getState('vies',''), 'vies', array('onchange'=>'this.form.submit();', 'class'=>'input-medium')) ?>
			</td>
			<td></td>
			<?php if($hasAjaxOrderingSupport === false): ?>
			<td></td>
			<?php endif; ?>
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
		<?php $m = 1; $i = -1; ?>
		<?php foreach($this->items as $taxrule):?>
		<?php
			$i++; $m = 1-$m;
			$checkedOut = ($taxrule->locked_by != 0);
			$ordering = $this->lists->order == 'ordering';
			$taxrule->published = $taxrule->enabled;
		?>
		<tr class="row<?php echo $m?>">
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
					value="<?php echo $taxrule->ordering;?>" class="input-mini text-area-order " />
			<?php else : ?>
				<span class="sortable-handler inactive" >
					<i class="icon-menu"></i>
				</span>
			<?php endif; ?>
			</td>
			<?php endif; ?>
			<td align="center">
				<?php echo $taxrule->akeebasubs_taxrule_id; ?>
			</td>
			<td align="center">
				<?php echo JHTML::_('grid.id', $i, $taxrule->akeebasubs_taxrule_id, $checkedOut); ?>
			</td>
			<td>
				<a href="index.php?option=com_akeebasubs&view=taxrule&id=<?php echo $taxrule->akeebasubs_taxrule_id; ?>">
					<?php if ($taxrule->akeebasubs_level_id == 0): ?>
					<?php echo JText::_('COM_AKEEBASUBS_TAXRULES_LEVEL_ALL'); ?>
					<?php else: ?>
					<?php echo AkeebasubsHelperSelect::formatLevel($taxrule->akeebasubs_level_id); ?>
					<?php endif; ?>
				</a>
			</td>
			<td>
				<a href="index.php?option=com_akeebasubs&view=taxrule&id=<?php echo $taxrule->akeebasubs_taxrule_id; ?>">
					<?php echo AkeebasubsHelperSelect::formatCountry($taxrule->country) ?>
					<?php echo $taxrule->country ? ' ('.$this->escape($taxrule->country).')' : ''?>
				</a>
			</td>
			<td>
				<a href="index.php?option=com_akeebasubs&view=taxrule&id=<?php echo $taxrule->akeebasubs_taxrule_id; ?>">
					<?php echo AkeebasubsHelperSelect::formatState($taxrule->state) ?>
					<?php echo $taxrule->state ? ' ('.$this->escape($taxrule->state).')' : ''?>
				</a>
			</td>
			<td>
				<a href="index.php?option=com_akeebasubs&view=taxrule&id=<?php echo $taxrule->akeebasubs_taxrule_id; ?>">
					<?php echo $taxrule->city ? $this->escape($taxrule->city) : '&mdash;'?>
				</a>
			</td>
			<td>
				<?php echo $taxrule->vies ? JText::_('jyes') : JText::_('jno')?>
			</td>
			<td>
				<a href="index.php?option=com_akeebasubs&view=taxrule&id=<?php echo $taxrule->akeebasubs_taxrule_id; ?>">
					<nobr><?php echo sprintf('%02.2f', $taxrule->taxrate)?> %</nobr>
				</a>
			</td>
			<?php if($hasAjaxOrderingSupport === false): ?>
			<td class="order" align="center">
				<span><?php echo $this->pagination->orderUpIcon( $i, true, 'orderup', 'Move Up', $ordering ); ?></span>
				<span><?php echo $this->pagination->orderDownIcon( $i, $count, true, 'orderdown', 'Move Down', $ordering ); ?></span>
				<?php $disabled = $ordering ?  '' : 'disabled="disabled"'; ?>
				<input type="text" name="order[]" size="5" value="<?php echo $taxrule->ordering;?>" <?php echo $disabled ?> class="text_area" style="text-align: center" />
			</td>
			<?php endif; ?>
			<td align="center">
				<?php echo JHTML::_('grid.published', $taxrule, $i); ?>
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