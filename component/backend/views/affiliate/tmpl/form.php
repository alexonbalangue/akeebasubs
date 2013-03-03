<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

JHTML::_('behavior.tooltip');
if(version_compare(JVERSION, '3.0', 'ge')) {
	JHTML::_('behavior.framework');
} else {
	JHTML::_('behavior.mootools');
}
JHTML::_('behavior.modal');

$this->loadHelper('cparams');
$this->loadHelper('select');
$this->loadHelper('format');

?>

<form action="index.php" method="post" name="adminForm" id="adminForm" class="form form-horizontal">
	<input type="hidden" name="option" value="com_akeebasubs" />
	<input type="hidden" name="view" value="affiliate" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="akeebasubs_affiliate_id" value="<?php echo $this->item->akeebasubs_affiliate_id ?>" />
	<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />

<div class="row-fluid">
<div class="span6">
	<h3><?php echo JText::_('COM_AKEEBASUBS_AFFILIATE_BASIC_TITLE')?></h3>
	
	<?php JLoader::import('joomla.user.user'); ?>
	<div class="control-group">
		<label for="userid_visible" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_AFFILIATES_USER_ID')?></label>
		<div class="controls">
			<input type="hidden" name="user_id" id="userid" value="<?php echo $this->item->user_id?>" />
			<input type="text" name="xxx_userid" id="userid_visible" value="<?php echo $this->item->user_id ? JFactory::getUser($this->item->user_id)->username : '' ?>" disabled="disabled" />
			<button onclick="return false;" class="btn btn-mini btn-inverse modal"><?php echo JText::_('COM_AKEEBASUBS_COMMON_SELECTUSER')?></button>
			<a class="modal" style="display: none" id="userselect" href="index.php?option=com_users&amp;view=users&amp;layout=modal&amp;tmpl=component&amp;field=userid" rel="{handler: 'iframe', size: {x: 800, y: 500}}">Select</a>
		</div>
	</div>

	<div class="control-group">
		<label for="comission" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_AFFILIATES_COMISSION')?></label>
		<div class="controls">
			<div class="input-append">
				<input type="text" name="comission" id="comission" class="input-mini" value="<?php echo $this->escape($this->item->comission)?>" />
				<span class="add-on">%</span>
			</div>
		</div>
	</div>
	
	<div class="control-group">
		<label for="enabled" class="control-label">
			<?php echo JText::_('JPUBLISHED'); ?>
		</label>
		<div class="controls">
			<?php echo JHTML::_('select.booleanlist', 'enabled', null, $this->item->enabled); ?>
		</div>
	</div>
	
</div>
</div>
</form>
	
<script type="text/javascript">
function jSelectUser_userid(id, username)
{
	document.getElementById('userid').value = id;
	document.getElementById('userid_visible').value = username;
	try {
		document.getElementById('sbox-window').close();	
	} catch(err) {
		SqueezeBox.close();
	}
}
window.addEvent("domready", function() {
	$$("button.modal").each(function(el) {
		el.addEvent("click", function(e) {
			try {
				new Event(e).stop();
			} catch(anotherMTUpgradeIssue) {
				try {
					e.stop();
				} catch(WhateverIsWrongWithYouIDontCare) {
					try {
						DOMEvent(e).stop();
					} catch(NoBleepinWay) {
						alert('If you see this message, your copy of Joomla! is FUBAR');
					}
				}
			}
			SqueezeBox.fromElement($('userselect'), {
				parse: 'rel'
			});
		});
	});
});
</script>