<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2014 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

JHtml::_('behavior.tooltip');
if(version_compare(JVERSION, '3.0', 'ge')) {
	JHTML::_('behavior.framework');
} else {
	JHTML::_('behavior.mootools');
}
?>
<div class="row-fluid">
	<div class="span12">
		<a href="index.php?option=com_akeebasubs&view=makecoupons" class="btn btn-success">
			<i class="icon icon-cog icon-white"></i>
			<?php echo JText::_('COM_AKEEBASUBS_TITLE_MAKECOUPONS')?>
		</a>
	</div>
</div>

<?php echo $this->getRenderedForm(); ?>