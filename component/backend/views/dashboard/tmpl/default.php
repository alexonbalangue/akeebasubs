<?php
/**
 * @version		$Id$
 * @category	AkeebaBackup
 * @package		UNiTE
 * @subpackage	gui-component
 * @copyright	Copyright (c)2010-2011 Nicholas K. Dionysopoulos / AkeebaBackup.com
 * @license		GNU GPLv3 <http://www.gnu.org/licenses/gpl.html> or later
 */

defined('KOOWA') or die('Restricted access');?>

<?= @helper('behavior.tooltip'); ?>
<!--
<style src="media://com_akeebasubs/css/backend.css" />
-->

<div id="cpanel"  style="width:51%;float:left;">
	<?= @helper('tabs.startPane', array('id' => 'quick', 'attribs' => array('height' => '275px'))) ?>
	
	<?= @helper('tabs.startPanel', array('title' => @text('COM_AKEEBASUBS_DASHBOARD_WELCOME'))) ?>
		<?=@template('default_welcome');?>
    <?= @helper('tabs.endPanel') ?>

	<?= @helper('tabs.startPanel', array('title' => @text('COM_AKEEBASUBS_DASHBOARD_OPERATIONS'))) ?>
		<div style="margin-left: 13px; text-align:center; height:234px;max-width:575px">
			<?=@template('default_quickicons'); ?>
			<div class="clr">    
	    </div>
    <?= @helper('tabs.endPanel') ?>

	<?= @helper('tabs.endPane') ?>
</div>

<div style="width:47%;float:right;">
	<?=@helper('site::com.akeebasubs.template.helper.modules.loadposition', array('position' => 'akeebasubscriptionsstats'))?>
</div>