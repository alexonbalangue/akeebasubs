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

<div style="float:left;">
	<div class="icon">
		<a href="<?= @route('view=level') ?>">
			<img alt="<?= @text('COM_AKEEBASUBS_DASHBOARD_ADD_LEVEL');?>"
				src="media://com_akeebasubs/images/dashboard/folder_new.png" />
			<span><?= @text('COM_AKEEBASUBS_DASHBOARD_ADD_LEVEL');?></span>
		</a>
	</div>
</div>

<div style="float:left;">
	<div class="icon">
		<a href="<?= @route('view=subscription') ?>">
			<img alt="<?= @text('COM_AKEEBASUBS_DASHBOARD_ADD_SUBSCRIPTION');?>"
				src="media://com_akeebasubs/images/dashboard/bookmark_add.png" />
			<span><?= @text('COM_AKEEBASUBS_DASHBOARD_ADD_SUBSCRIPTION');?></span>
		</a>
	</div>
</div>

<div style="float:left;">
	<div class="icon">
		<a href="<?= @route('view=coupon') ?>">
			<img alt="<?= @text('COM_AKEEBASUBS_DASHBOARD_ADD_COUPON');?>"
				src="media://com_akeebasubs/images/dashboard/money.png" />
			<span><?= @text('COM_AKEEBASUBS_DASHBOARD_ADD_COUPON');?></span>
		</a>
	</div>
</div>

<div style="float:left;">
	<div class="icon">
		<a href="<?= @route('view=tools') ?>">
			<img alt="<?= @text('COM_AKEEBASUBS_DASHBOARD_TOOLS');?>"
				src="media://com_akeebasubs/images/dashboard/db_update.png" />
			<span><?= @text('COM_AKEEBASUBS_DASHBOARD_TOOLS');?></span>
		</a>
	</div>
</div>

<?php echo LiveUpdate::getIcon(); ?>