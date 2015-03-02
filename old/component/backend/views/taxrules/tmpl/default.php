<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();
?>
<div class="row-fluid" style="margin-bottom: 1.5em">
	<div class="span12">
		<a href="index.php?option=com_akeebasubs&view=taxconfigs" class="btn btn-primary">
			<i class="icon-white icon-plane"></i>
			<?php echo JText::_('COM_AKEEBASUBS_TITLE_TAXCONFIGS') ?>
		</a>
	</div>
</div>
<?php
echo $this->getRenderedForm();