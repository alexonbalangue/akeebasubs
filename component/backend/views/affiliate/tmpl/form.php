<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
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

<div class="akeeba-bootstrap">

<form action="index.php" method="post" name="adminForm" id="adminForm">
	<input type="hidden" name="option" value="com_akeebasubs" />
	<input type="hidden" name="view" value="affiliate" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="akeebasubs_affiliate_id" value="<?php echo $this->item->akeebasubs_affiliate_id ?>" />
	<input type="hidden" name="<?php echo JFactory::getSession()->getToken();?>" value="1" />

<div class="row-fluid">
<div class="span6">
<div class="well">
	<legend><?php echo JText::_('COM_AKEEBASUBS_AFFILIATE_BASIC_TITLE')?></legend>
	
	<?php jimport('joomla.user.user'); ?>
	<label for="userid_visible" class="main"><?php echo JText::_('COM_AKEEBASUBS_AFFILIATES_USER_ID')?></label>
	<input type="hidden" name="user_id" id="userid" value="<?php echo $this->item->user_id?>" />
	<input type="text" name="xxx_userid" id="userid_visible" value="<?php echo $this->item->user_id ? JFactory::getUser($this->item->user_id)->username : '' ?>" disabled="disabled" />
	<button onclick="return false;" class="btn btn-mini modal"><?php echo JText::_('COM_AKEEBASUBS_COMMON_SELECTUSER')?></button>
	<a class="modal" style="display: none" id="userselect" href="index.php?option=com_users&amp;view=users&amp;layout=modal&amp;tmpl=component&amp;field=userid" rel="{handler: 'iframe', size: {x: 800, y: 500}}">Select</a>
	<div class="akeebasubs-clear"></div>
	
	<label for="comission" class="main"><?php echo JText::_('COM_AKEEBASUBS_AFFILIATES_COMISSION')?></label>
	<input type="text" name="comission" id="comission" value="<?php echo $this->escape($this->item->comission)?>" />
	<div class="akeebasubs-clear"></div>
	
	<label for="enabled" class="main" class="mainlabel">
		<?php echo JText::_('JPUBLISHED'); ?>
	</label>
	<?php echo JHTML::_('select.booleanlist', 'enabled', null, $this->item->enabled); ?>
	<div class="akeebasubs-clear"></div>
</div>
</div>
</div>
</form>
	
</div>

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