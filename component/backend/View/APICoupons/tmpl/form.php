<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

/** @var \FOF30\View\DataView\Form $this */

\JHtml::_('jquery.framework', true);
\JHtml::_('formbehavior.chosen', 'select');

JFactory::getDocument()->addScriptDeclaration(<<< JS

;// This comment is intentionally put here to prevent badly written plugins from causing a Javascript error
// due to missing trailing semicolon and/or newline in their code.

jQuery(document).ready(function()
{
	function akeebasubsOnUsageLimitsChange()
	{
		var value = jQuery('#usage_limits').val();

		if(value == 1)
		{
			jQuery("#creation_limit").show();
			jQuery("#subscription_limit").hide().val("0");
			jQuery("#value_limit").hide().val("0");
		}
		else if(value == 2)
		{
			jQuery("#creation_limit").hide().val("0");
			jQuery("#subscription_limit").show();
			jQuery("#value_limit").hide().val("0");
		}
		else
		{
			jQuery("#creation_limit").hide().val("0");
			jQuery("#subscription_limit").hide().val("0");
			jQuery("#value_limit").show();
		}
	}

	jQuery("#usage_limits").change(akeebasubsOnUsageLimitsChange);

	akeebasubsOnUsageLimitsChange();
})

JS

);
?>
<?php if ($this->item->akeebasubs_apicoupon_id > 0):
	$rootURL = rtrim(JURI::base(), '/');
	$subpathURL = JURI::base(true);

	if (!empty($subpathURL) && ($subpathURL != '/'))
	{
		$rootURL = substr($rootURL, 0, -1 * strlen($subpathURL));
	}

	$apiURL = $rootURL . '/index.php?option=com_akeebasubs&view=APICoupon&task=create&key=' .
		urlencode($this->item->key) . '&pwd=' . urlencode($this->item->password) .
		'&format=json';
	?>
	<div class="alert alert-info">
		<?php echo JText::sprintf('COM_AKEEBASUBS_APICOUPONS_INFO_URL', $apiURL); ?>
	</div>
<?php endif; ?>

<?php echo $this->getRenderedForm(); ?>