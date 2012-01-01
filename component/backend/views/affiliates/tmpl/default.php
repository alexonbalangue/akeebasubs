<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

FOFTemplateUtils::addCSS('media://com_akeebasubs/css/backend.css?'.AKEEBASUBS_VERSIONHASH);
JHtml::_('behavior.mootools');

$this->loadHelper('select');
$this->loadHelper('cparams');
?>
<form action="index.php" method="post" name="adminForm">
<input type="hidden" name="option" value="com_akeebasubs" />
<input type="hidden" name="view" value="affiliates" />
<input type="hidden" id="task" name="task" value="browse" />
<input type="hidden" name="hidemainmenu" id="hidemainmenu" value="0" />
<input type="hidden" name="boxchecked" id="boxchecked" value="0" />
<input type="hidden" name="filter_order" id="filter_order" value="<?php echo $this->lists->order ?>" />
<input type="hidden" name="filter_order_Dir" id="filter_order_Dir" value="<?php echo $this->lists->order_Dir ?>" />
<input type="hidden" name="<?php echo JUtility::getToken();?>" value="1" />

<table class="adminlist">
	<thead>
		<tr>
			<th width="8%">
				<?php echo JHTML::_('grid.sort', 'Num', 'akeebasubs_affiliate_id', $this->lists->order_Dir, $this->lists->order) ?>
			</th>
			<th width="16"></th>
			<th>
				<?php echo JHTML::_('grid.sort', 'COM_AKEEBASUBS_AFFILIATES_USER_ID', 'user_id', $this->lists->order_Dir, $this->lists->order) ?>
			</th>
			<th width="10%">
				<?php echo JHTML::_('grid.sort', 'COM_AKEEBASUBS_AFFILIATES_COMISSION', 'comission', $this->lists->order_Dir, $this->lists->order) ?>
			</th>
			<th width="10%">
				<?php echo JText::_('COM_AKEEBASUBS_AFFILIATES_OUTSTANDING') ?>
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
			<td colspan="2">
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
					<?php if(AkeebasubsHelperCparams::getParam('gravatar')):?>
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
				<?php echo sprintf('%.02f', $item->owed - $item->paid) ?>
				<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€') ?>
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