<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

use Akeeba\Subscriptions\Admin\Helper\ComponentParams;

$fieldName = (string) $fieldElement['name'];

?>
<div
	class="input-<?php echo (ComponentParams::getParam('currencypos', 'before') == 'before') ? 'prepend' : 'append' ?>">
	<?php if (ComponentParams::getParam('currencypos', 'before') == 'before'): ?>
		<span class="add-on">
			<?php echo ComponentParams::getParam('currencysymbol', '€') ?>
		</span>
	<?php endif; ?>
	<input type="text" size="15" id="<?php echo $fieldName ?>" name="<?php echo $fieldName ?>" value="<?php echo $fieldValue ?>"
		   style="float: none"/>
	<?php if (ComponentParams::getParam('currencypos', 'before') == 'after'): ?>
		<span class="add-on">
			<?php echo ComponentParams::getParam('currencysymbol', '€') ?>
		</span>
	<?php endif; ?>
</div>