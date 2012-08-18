<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

JHtml::_('behavior.tooltip');

$this->loadHelper('select');

$pEnabled = JPluginHelper::getPlugin('system','admintools');

?>
<div class="akeeba-bootstrap">
	
<?php if(!$pEnabled): ?>
<div class="alert alert-error">
	<a class="close" data-dismiss="alert" href="#">×</a>
	<h3><?php echo JText::_('COM_AKEEBASUBS_CUSTOMFIELDS_ERR_NOPLUGIN_HEADER'); ?></h3>
	<p><?php echo JText::_('COM_AKEEBASUBS_CUSTOMFIELDS_ERR_NOPLUGIN_BODY'); ?></p>
</div>
<?php endif; ?>

<form action="index.php" method="post" name="adminForm" id="adminForm">
<input type="hidden" name="option" value="com_akeebasubs" />
<input type="hidden" name="view" value="customfields" />
<input type="hidden" id="task" name="task" value="browse" />
<input type="hidden" name="hidemainmenu" id="hidemainmenu" value="0" />
<input type="hidden" name="boxchecked" id="boxchecked" value="0" />
<input type="hidden" name="filter_order" id="filter_order" value="<?php echo $this->lists->order ?>" />
<input type="hidden" name="filter_order_Dir" id="filter_order_Dir" value="<?php echo $this->lists->order_Dir ?>" />
<input type="hidden" name="<?php echo JFactory::getSession()->getToken();?>" value="1" />

<table class="table table-striped">
	<thead>
		<tr>
			<th width="30">
				<?php echo JHTML::_('grid.sort', 'Num', 'akeebasubs_customfield_id', $this->lists->order_Dir, $this->lists->order, 'browse') ?>
			</th>
			<th width="20"></th>
			<th>
				<?php echo JHTML::_('grid.sort', 'COM_AKEEBASUBS_CUSTOMFIELDS_FIELD_TITLE', 'title', $this->lists->order_Dir, $this->lists->order, 'browse') ?>
			</th>
			<th width="50">
				<?php echo JHTML::_('grid.sort', 'COM_AKEEBASUBS_CUSTOMFIELDS_FIELD_TYPE', 'title', $this->lists->order_Dir, $this->lists->order, 'browse') ?>
			</th>
			<th width="80">
				<?php echo JHTML::_('grid.sort', 'COM_AKEEBASUBS_CUSTOMFIELDS_FIELD_DEFAULT', 'title', $this->lists->order_Dir, $this->lists->order, 'browse') ?>
			</th>
			<th width="8%">
				<?php echo JHTML::_('grid.sort', 'JFIELD_ORDERING_LABEL', 'ordering', $this->lists->order_Dir, $this->lists->order, 'browse') ?>
				<?php echo JHTML::_('grid.order', $this->items); ?>
			</th>
			<th width="8%">
				<?php echo JHTML::_('grid.sort', 'JPUBLISHED', 'enabled', $this->lists->order_Dir, $this->lists->order, 'browse') ?>
			</th>
		</tr>
		<tr>
			<td></td>
			<td>
				<input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this);" />
			</td>
			<td class="form-inline">
				<input type="text" name="search" id="search"
					value="<?php echo $this->escape($this->getModel()->getState('search',''));?>"
					class="text_area" onchange="document.adminForm.submit();" />
				<button class="btn btn-mini" onclick="this.form.submit();">
					<?php echo JText::_('JSEARCH_FILTER'); ?>
				</button>
				<button class="btn btn-mini" onclick="document.adminForm.search.value='';this.form.submit();">
					<?php echo JText::_('JSEARCH_RESET'); ?>
				</button>
			</td>
			<td>
				<?php echo AkeebasubsHelperSelect::fieldtypes($this->getModel()->getState('type',''), 'type', array('onchange'=>'this.form.submit();')) ?>
			</td>
			<td></td>
			<td></td>
			<td>
				<?php echo AkeebasubsHelperSelect::published($this->getModel()->getState('enabled',''), 'enabled', array('onchange'=>'this.form.submit();', 'class' => 'input-medium')) ?>
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
		<?php $i = -1; $m = 1; ?>
		<?php foreach ($this->items as $item) : ?>
		<?php
			$i++; $m = 1-$m;
			$item->published = $item->enabled;
			$ordering = $this->lists->order == 'ordering';
		?>
		<tr class="<?php echo 'row'.$m; ?>">
			<td align="center">
				<?php echo $item->akeebasubs_customfield_id; ?>
			</td>
			<td>
				<?php echo JHTML::_('grid.id', $i, $item->akeebasubs_customfield_id, false); ?>
			</td>
			<td align="left">
					<a href="index.php?option=com_akeebasubs&view=customfield&id=<?php echo $item->akeebasubs_customfield_id ?>">
						<strong><?php echo $this->escape(JText::_($item->title)) ?></strong>
					</a>
					<p class="smallsub">
						(<span><?php echo $this->escape($item->title) ?></span>)
					</p>
			</td>
			<td>
				<?php echo JText::_('COM_AKEEBASUBS_CUSTOMFIELDS_FIELD_TYPE_'.$item->type); ?>
			</td>
			<td>
				<?php echo $this->escape($item->default); ?>
			</td>
			<td class="order" align="center">
				<span><?php echo $this->pagination->orderUpIcon( $i, true, 'orderup', 'Move Up', $ordering ); ?></span>
				<span><?php echo $this->pagination->orderDownIcon( $i, $count, true, 'orderdown', 'Move Down', $ordering ); ?></span>
				<?php $disabled = $ordering ?  '' : 'disabled="disabled"'; ?>
				<input type="text" name="order[]" size="5" value="<?php echo $item->ordering;?>" <?php echo $disabled ?> class="text_area" style="text-align: center" />
			</td>
			<td align="center">
				<?php echo JHTML::_('grid.published', $item, $i); ?>
			</td>
		</tr>
		<?php endforeach ?>
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