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
JHTML::_('behavior.mootools');
JHTML::_('behavior.modal');

$this->loadHelper('cparams');
$this->loadHelper('select');
$this->loadHelper('format');

?>

<form action="index.php" method="post" name="adminForm">
	<input type="hidden" name="option" value="com_akeebasubs" />
	<input type="hidden" name="view" value="affiliate" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="akeebasubs_affiliate_id" value="<?php echo $this->item->akeebasubs_affiliate_id ?>" />
	<input type="hidden" name="<?php echo JUtility::getToken();?>" value="1" />

<fieldset id="coupons-basic" style="width: 48%; float: left;">
	<legend><?php echo JText::_('COM_AKEEBASUBS_AFFILIATE_BASIC_TITLE')?></legend>
	
	<?php jimport('joomla.user.user'); ?>
	<label for="userid_visible" class="main"><?php echo JText::_('COM_AKEEBASUBS_AFFILIATES_USER_ID')?></label>
	<input type="hidden" name="user_id" id="userid" value="<?php echo $this->item->user_id?>" />
	<input type="text" name="xxx_userid" id="userid_visible" value="<?php echo $this->item->user_id ? JFactory::getUser($this->item->user_id)->username : '' ?>" disabled="disabled" />
	<button onclick="return false;" class="modal"><?php echo JText::_('COM_AKEEBASUBS_COMMON_SELECTUSER')?></button>
	<?php if(version_compare(JVERSION, '1.6.0', 'ge')): ?>
	<a class="modal" style="display: none" id="userselect" href="index.php?option=com_users&amp;view=users&amp;layout=modal&amp;tmpl=component&amp;field=userid" rel="{handler: 'iframe', size: {x: 800, y: 500}}">Select</a>
	<?php else: ?>
	<a class="modal" style="display: none" id="userselect" href="index.php?option=com_akeebasubs&amp;view=jusers&amp;tmpl=component" rel="{handler: 'iframe', size: {x: 800, y: 500}}">Select</a>
	<?php endif; ?>
	<div class="akeebasubs-clear"></div>
	
	<label for="comission" class="main"><?php echo JText::_('COM_AKEEBASUBS_AFFILIATES_COMISSION')?></label>
	<input type="text" name="comission" id="comission" value="<?php echo $this->escape($this->item->comission)?>" />
	<div class="akeebasubs-clear"></div>
	
	<label for="enabled" class="main" class="mainlabel">
		<?php if(version_compare(JVERSION,'1.6.0','ge')): ?>
		<?php echo JText::_('JPUBLISHED'); ?>
		<?php else: ?>
		<?php echo JText::_('PUBLISHED'); ?>
		<?php endif; ?>
	</label>
	<?php echo JHTML::_('select.booleanlist', 'enabled', null, $this->item->enabled); ?>
	
</fieldset>

<div class="akeebasubs-clear"></div>

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
			new Event(e).stop();
			SqueezeBox.fromElement($('userselect'));
		});
	});
});
</script>