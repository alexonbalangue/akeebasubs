<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

JHtml::_('behavior.tooltip');

?>

<div class="akeeba-bootstrap">
<div class="row-fluid">
<div class="span12">

<form action="index.php" method="post" name="adminForm" id="adminForm">
	<input type="hidden" name="option" value="com_akeebasubs" />
	<input type="hidden" name="view" value="levelgroup" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="akeebasubs_levelgroup_id" value="<?php echo $this->item->akeebasubs_levelgroup_id ?>" />
	<input type="hidden" name="<?php echo JFactory::getSession()->getToken();?>" value="1" />
	
	<div class="well">
		<label for="title_field" class="main title"><?php echo JText::_('COM_AKEEBASUBS_LEVELGROUPS_FIELD_TITLE'); ?></label>
		<input type="text" size="20" id="title_field" name="title" class="title" value="<?php echo $this->escape($this->item->title) ?>" />
		<div class="akeebasubs-clear"></div>

		<label for="enabled" class="main" class="mainlabel">
			<?php echo JText::_('JPUBLISHED'); ?>
		</label>
		<span class="akeebasubs-booleangroup">
			<?php echo JHTML::_('select.booleanlist', 'enabled', null, $this->item->enabled); ?>
		</span>
		<div class="akeebasubs-clear"></div>
	</div>
</form>
	
</div>
</div>
</div>