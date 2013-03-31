<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

JHtml::_('behavior.tooltip');

$this->loadHelper('select');

$pEnabled = JPluginHelper::getPlugin('akeebasubs','customfields');

$hasAjaxOrderingSupport = $this->hasAjaxOrderingSupport();
?>

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
<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />

<table class="table table-striped" id="itemsList">
	<thead>
		<tr>
			<?php if($hasAjaxOrderingSupport !== false): ?>
			<th width="20px">
				<?php echo JHtml::_('grid.sort', '<i class="icon-menu-2"></i>', 'ordering', $this->lists->order_Dir, $this->lists->order, null, 'asc', 'JGRID_HEADING_ORDERING'); ?>
			</th>
			<?php endif; ?>
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
			<?php if($hasAjaxOrderingSupport === false): ?>
			<th width="8%">
				<?php echo JHTML::_('grid.sort', 'JFIELD_ORDERING_LABEL', 'ordering', $this->lists->order_Dir, $this->lists->order, 'browse') ?>
				<?php echo JHTML::_('grid.order', $this->items); ?>
			</th>
			<?php endif; ?>
			<th width="8%">
				<?php echo JHTML::_('grid.sort', 'JPUBLISHED', 'enabled', $this->lists->order_Dir, $this->lists->order, 'browse') ?>
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
			<td class="form-inline">
				<input type="text" name="search" id="search"
					value="<?php echo $this->escape($this->getModel()->getState('search',''));?>"
					class="input-medium" onchange="document.adminForm.submit();"
					placeholder="<?php echo JText::_('COM_AKEEBASUBS_CUSTOMFIELDS_FIELD_TITLE') ?>"
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
			<td>
				<?php echo AkeebasubsHelperSelect::fieldtypes($this->getModel()->getState('type',''), 'type', array('onchange'=>'this.form.submit();','class' => 'input-medium')) ?>
			</td>
			<td></td>
			<?php if($hasAjaxOrderingSupport === false): ?>
			<td></td>
			<?php endif; ?>
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
					value="<?php echo $item->ordering;?>" class="input-mini text-area-order " />
			<?php else : ?>
				<span class="sortable-handler inactive" >
					<i class="icon-menu"></i>
				</span>
			<?php endif; ?>
			</td>
			<?php endif; ?>
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
						(<span><?php echo $this->escape($item->slug) ?></span>)
					</p>
			</td>
			<td>
				<?php echo JText::_('COM_AKEEBASUBS_CUSTOMFIELDS_FIELD_TYPE_'.$item->type); ?>
			</td>
			<td>
				<?php echo $this->escape($item->default); ?>
			</td>
			<?php if($hasAjaxOrderingSupport === false): ?>
			<td class="order" align="center">
				<span><?php echo $this->pagination->orderUpIcon( $i, true, 'orderup', 'Move Up', $ordering ); ?></span>
				<span><?php echo $this->pagination->orderDownIcon( $i, $count, true, 'orderdown', 'Move Down', $ordering ); ?></span>
				<?php $disabled = $ordering ?  '' : 'disabled="disabled"'; ?>
				<input type="text" name="order[]" size="5" value="<?php echo $item->ordering;?>" <?php echo $disabled ?> class="text_area" style="text-align: center" />
			</td>
			<?php endif; ?>
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
