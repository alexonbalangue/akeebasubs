<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

if(version_compare(JVERSION, '3.0', 'ge')) {
	JHTML::_('behavior.framework');
} else {
	JHTML::_('behavior.mootools');
}

$this->loadHelper('select');
$this->loadHelper('cparams');
?>

<div class="row-fluid">
<div class="span12">

<form action="index.php" method="post" name="adminForm" id="adminForm">
<input type="hidden" name="option" value="com_akeebasubs" />
<input type="hidden" name="view" value="affiliates" />
<input type="hidden" id="task" name="task" value="browse" />
<input type="hidden" name="hidemainmenu" id="hidemainmenu" value="0" />
<input type="hidden" name="boxchecked" id="boxchecked" value="0" />
<input type="hidden" name="filter_order" id="filter_order" value="<?php echo $this->lists->order ?>" />
<input type="hidden" name="filter_order_Dir" id="filter_order_Dir" value="<?php echo $this->lists->order_Dir ?>" />
<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />

<table class="adminlist table table-striped">
	<thead>
		<tr>
			<th width="8%">
				<?php echo JHTML::_('grid.sort', 'Num', 'akeebasubs_affiliate_id', $this->lists->order_Dir, $this->lists->order, 'browse') ?>
			</th>
			<th width="16"></th>
			<th>
				<?php echo JHTML::_('grid.sort', 'COM_AKEEBASUBS_AFFILIATES_USER_ID', 'user_id', $this->lists->order_Dir, $this->lists->order, 'browse') ?>
			</th>
			<th width="10%">
				<?php echo JHTML::_('grid.sort', 'COM_AKEEBASUBS_AFFILIATES_COMISSION', 'comission', $this->lists->order_Dir, $this->lists->order, 'browse') ?>
			</th>
			<th width="10%">
				<?php echo JText::_('COM_AKEEBASUBS_AFFILIATES_OUTSTANDING') ?>
			</th>
			<th width="8%">
				<?php echo JHTML::_('grid.sort', 'JPUBLISHED', 'enabled', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
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
					class="input-medium" onchange="document.adminForm.submit();"
					placeholder="<?php echo JText::_('COM_AKEEBASUBS_AFFILIATES_USER_ID')?>"
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
		<?php foreach ($this->items as $item) : ?>
		<?php
			$i++; $m = 1-$m;
			$item->published = $item->enabled;
		?>
		<tr class="<?php echo  'row'.$m; ?>">
			<td align="center">
				<?php echo $item->akeebasubs_affiliate_id; ?>
			</td>
			<td align="center">
				<?php echo JHTML::_('grid.id', $i, $item->akeebasubs_affiliate_id); ?>
			</td>
			<td>
				<span class="editlinktip hasTip" title="<?php echo $this->escape($item->username) ?>::<?php echo JText::_('COM_AKEEBASUBS_AFFILIATES_EDIT_TOOLTIP')?>">
					<?php if(AkeebasubsHelperCparams::getParam('gravatar',true)):?>
						<?php if(JURI::getInstance()->getScheme() == 'http'): ?>
							<img src="http://www.gravatar.com/avatar/<?php echo md5(strtolower($item->email))?>.jpg?s=32&d=mm" align="left" class="gravatar"  />
						<?php else: ?>
							<img src="https://secure.gravatar.com/avatar/<?php echo md5(strtolower($item->email))?>.jpg?s=32&d=mm" align="left" class="gravatar"  />
						<?php endif; ?>
					<?php endif; ?>
					<a href="index.php?option=com_akeebasubs&view=affiliate&id=<?php echo $item->akeebasubs_affiliate_id ?>" class="title">	
					<strong><?php echo $this->escape($item->username)?></strong>
					<span class="small">[<?php echo $item->user_id?>]</span>
					<br/>
					<?php echo $this->escape($item->name)?>
					<br/>
					<?php echo $this->escape($item->email)?>
					</a>
				</span>
			</td>
			<td align="right">
				<?php echo sprintf('%.02f', $item->comission) ?>%
			</td>
			<td>
				<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'before'): ?>
				<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
				<?php endif; ?>
				<?php echo sprintf('%.02f', $item->owed - $item->paid) ?>
				<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'after'): ?>
				<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
				<?php endif; ?>
			</td>
			<td>
				<?php echo JHTML::_('grid.published', $item, $i); ?>
			</td>
		</tr>
		<?php endforeach; ?>
		<?php else: ?>
		<?php endif; ?>
	</tbody>
</table>

</form>
	
</div>
</div>