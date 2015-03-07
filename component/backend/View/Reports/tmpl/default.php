<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

/** @var \FOF30\View\DataView\Html $this */

defined('_JEXEC') or die;

$this->getContainer()->platform->getDocument()->addStyleSheet(
	$this->getContainer()->template->parsePath('media://com_akeebasubs/css/backend.css')
);
?>

<div id="cpanel">
	<a href="index.php?option=com_akeebasubs&view=Reports&task=invoices" class="btn cpanel-icon">
		<span class="icon icon-list ak-icon"></span>
		<br/>
		<span><?php echo JText::_('COM_AKEEBASUBS_REPORTS_INVOICES');?></span>
	</a>

	<a href="index.php?option=com_akeebasubs&view=Reports&task=vies" class="btn cpanel-icon">
		<span class="icon icon-briefcase"></span>
		<br/>
		<span><?php echo JText::_('COM_AKEEBASUBS_REPORTS_VIES');?></span>
	</a>

	<a href="index.php?option=com_akeebasubs&view=Reports&task=vatmoss" class="btn cpanel-icon">
		<span class="icon icon-list"></span>
		<br/>
		<span><?php echo JText::_('COM_AKEEBASUBS_REPORTS_VATMOSS');?></span>
	</a>
</div>