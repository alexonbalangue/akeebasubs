<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();
?>

<?php if (!$this->hasGeoIPPlugin): ?>
	<div class="well">
		<h3><?php echo JText::_('COM_AKEEBASUBS_GEOIP_LBL_GEOIPPLUGINSTATUS') ?></h3>

		<p><?php echo JText::_('COM_AKEEBASUBS_GEOIP_LBL_GEOIPPLUGINMISSING') ?></p>

		<a class="btn btn-primary" href="https://www.akeebabackup.com/download/akgeoip.html" target="_blank">
			<span class="icon icon-white icon-download-alt"></span>
			<?php echo JText::_('COM_AKEEBASUBS_GEOIP_LBL_DOWNLOADGEOIPPLUGIN') ?>
		</a>
	</div>
<?php elseif ($this->geoIPPluginNeedsUpdate): ?>
	<div class="well well-small">
		<h3><?php echo JText::_('COM_AKEEBASUBS_GEOIP_LBL_GEOIPPLUGINEXISTS') ?></h3>

		<p><?php echo JText::_('COM_AKEEBASUBS_GEOIP_LBL_GEOIPPLUGINCANUPDATE') ?></p>

		<a class="btn btn-small"
		   href="index.php?option=com_akeebasubs&view=cpanel&task=updategeoip&<?php echo JFactory::getSession()->getFormToken(); ?>=1">
			<span class="icon icon-retweet"></span>
			<?php echo JText::_('COM_AKEEBASUBS_GEOIP_LBL_UPDATEGEOIPDATABASE') ?>
		</a>
	</div>
<?php endif; ?>