<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

FOFTemplateUtils::addCSS('media://com_akeebasubs/css/backend.css?'.AKEEBASUBS_VERSIONHASH);

JHTML::_('behavior.tooltip');

$this->loadHelper('cparams');
$this->loadHelper('select');
$this->loadHelper('format');

?>
<form action="index.php" method="post" name="adminForm">
<input type="hidden" name="option" value="com_akeebasubs" />
<input type="hidden" name="view" value="users" />
<input type="hidden" id="task" name="task" value="browse" />
<input type="hidden" name="hidemainmenu" id="hidemainmenu" value="0" />
<input type="hidden" name="boxchecked" id="boxchecked" value="0" />
<input type="hidden" name="filter_order" id="filter_order" value="<?php echo $this->lists->order ?>" />
<input type="hidden" name="filter_order_Dir" id="filter_order_Dir" value="<?php echo $this->lists->order_Dir ?>" />
<input type="hidden" name="<?php echo JUtility::getToken();?>" value="1" />

<table class="adminlist">
	<thead>
		<tr>
			<th width="30px">
				<?php echo  JHTML::_('grid.sort', 'COM_AKEEBASUBS_USERS_FIELD_USERID', 'user_id', $this->lists->order_Dir, $this->lists->order); ?>
			</th>
			<th width="16px"></th>
			<th>
				<?php echo  JHTML::_('grid.sort', 'COM_AKEEBASUBS_USERS_FIELD_USERNAME', 'username', $this->lists->order_Dir, $this->lists->order); ?>
			</th>
			<th>
				<?php echo  JHTML::_('grid.sort', 'COM_AKEEBASUBS_USERS_FIELD_NAME', 'name', $this->lists->order_Dir, $this->lists->order); ?>
			</th>
			<th>
				<?php echo  JHTML::_('grid.sort', 'COM_AKEEBASUBS_USERS_FIELD_EMAIL', 'email', $this->lists->order_Dir, $this->lists->order); ?>
			</th>
			<th>
				<?php echo  JHTML::_('grid.sort', 'COM_AKEEBASUBS_USERS_FIELD_BUSINESSNAME', 'businessname', $this->lists->order_Dir, $this->lists->order); ?>
			</th>
			<th>
				<?php echo  JHTML::_('grid.sort', 'COM_AKEEBASUBS_USERS_FIELD_VATNUMBER', 'vatnumber', $this->lists->order_Dir, $this->lists->order); ?>
			</th>
		</tr>
		<tr>
			<td></td>
			<td>
				<input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count( $this->items ) + 1; ?>);" />
			</td>
			<td>
				<input type="text" name="username" id="username"
					value="<?php echo $this->escape($this->getModel()->getState('username',''));?>"
					class="text_area" onchange="document.adminForm.submit();" />
				<button onclick="this.form.submit();">
					<?php echo version_compare(JVERSION, '1.6.0', 'ge') ? JText::_('JSEARCH_FILTER') : JText::_('Go'); ?>
				</button>
				<button onclick="document.adminForm.username.value='';this.form.submit();">
					<?php echo version_compare(JVERSION, '1.6.0', 'ge') ? JText::_('JSEARCH_RESET') : JText::_('Reset'); ?>
				</button>
			</td>
			<td>
				<input type="text" name="name" id="name"
					value="<?php echo $this->escape($this->getModel()->getState('name',''));?>"
					class="text_area" onchange="document.adminForm.submit();" />
				<button onclick="this.form.submit();">
					<?php echo version_compare(JVERSION, '1.6.0', 'ge') ? JText::_('JSEARCH_FILTER') : JText::_('Go'); ?>
				</button>
				<button onclick="document.adminForm.name.value='';this.form.submit();">
					<?php echo version_compare(JVERSION, '1.6.0', 'ge') ? JText::_('JSEARCH_RESET') : JText::_('Reset'); ?>
				</button>
			</td>
			<td>
				<input type="text" name="email" id="email"
					value="<?php echo $this->escape($this->getModel()->getState('email',''));?>"
					class="text_area" onchange="document.adminForm.submit();" />
				<button onclick="this.form.submit();">
					<?php echo version_compare(JVERSION, '1.6.0', 'ge') ? JText::_('JSEARCH_FILTER') : JText::_('Go'); ?>
				</button>
				<button onclick="document.adminForm.email.value='';this.form.submit();">
					<?php echo version_compare(JVERSION, '1.6.0', 'ge') ? JText::_('JSEARCH_RESET') : JText::_('Reset'); ?>
				</button>
			</td>
			<td>
				<input type="text" name="businessname" id="businessname"
					value="<?php echo $this->escape($this->getModel()->getState('businessname',''));?>"
					class="text_area" onchange="document.adminForm.submit();" />
				<button onclick="this.form.submit();">
					<?php echo version_compare(JVERSION, '1.6.0', 'ge') ? JText::_('JSEARCH_FILTER') : JText::_('Go'); ?>
				</button>
				<button onclick="document.adminForm.businessname.value='';this.form.submit();">
					<?php echo version_compare(JVERSION, '1.6.0', 'ge') ? JText::_('JSEARCH_RESET') : JText::_('Reset'); ?>
				</button>
			</td>
			<td>
				<input type="text" name="vatnumber" id="vatnumber"
					value="<?php echo $this->escape($this->getModel()->getState('vatnumber',''));?>"
					class="text_area" onchange="document.adminForm.submit();" />
				<button onclick="this.form.submit();">
					<?php echo version_compare(JVERSION, '1.6.0', 'ge') ? JText::_('JSEARCH_FILTER') : JText::_('Go'); ?>
				</button>
				<button onclick="document.adminForm.vatnumber.value='';this.form.submit();">
					<?php echo version_compare(JVERSION, '1.6.0', 'ge') ? JText::_('JSEARCH_RESET') : JText::_('Reset'); ?>
				</button>
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
		<?php foreach($this->items as $user): ?>
		<?php $i++; $m = 1-$m; ?>
		<tr class="<?php echo 'row'.$m; ?>">
			<td align="center">
				<?php echo $user->akeebasubs_user_id?>
			</td>
			<td align="center">
				<?php echo JHTML::_('grid.id', $i, $user->akeebasubs_user_id, false); ?>
			</td>
			<td align="left">
				<a href="index.php?option=com_akeebasubs&view=user&id=<?php echo $user->akeebasubs_user_id; ?>">
					<strong><?php echo $this->escape($user->username) ?></strong>
				</a>
			</td>
			<td>
				<a href="index.php?option=com_akeebasubs&view=user&id=<?php echo $user->akeebasubs_user_id; ?>">
					<?php echo $this->escape($user->name) ?>
				</a>
			</td>
			<td>
				<a href="index.php?option=com_akeebasubs&view=user&id=<?php echo $user->akeebasubs_user_id; ?>">
					<?php echo $this->escape($user->email) ?>
				</a>
			</td>
			<td>
				<?if(!empty($user->businessname)):?>
				<a href="index.php?option=com_akeebasubs&view=user&id=<?php echo $user->akeebasubs_user_id; ?>">
					<?php echo $this->escape($user->businessname) ?>
				</a>
				<?else:?>
				&mdash;
				<?endif;?>
			</td>
			<td>
				<?if(!empty($user->vatnumber)):?>
				<a href="index.php?option=com_akeebasubs&view=user&id=<?php echo $user->akeebasubs_user_id; ?>">
					<?php echo ($user->country == 'GR') ? 'EL' : $user->country ?>
					<?php echo $this->escape($user->vatnumber) ?>
				</a>
				<?else:?>
				&mdash;
				<?endif?>
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