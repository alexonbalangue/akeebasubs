<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

use Akeeba\Subscriptions\Admin\Helper\Select;
use Akeeba\Subscriptions\Admin\Helper\ComponentParams;
use Akeeba\Subscriptions\Admin\Helper\Image;
use Akeeba\Subscriptions\Admin\Helper\Format;

/** @var \FOF30\View\DataView\Form $this */

// Protect from unauthorized access
defined('_JEXEC') or die();

$this->addJavascriptFile('media://com_akeebasubs/js/blockui.js');

JHtml::_('behavior.tooltip');
JHtml::_('behavior.multiselect');
JHtml::_('dropdown.init');
JHtml::_('formbehavior.chosen', 'select');

echo $this->getRenderedForm();

?>

<script type="text/javascript">
	var akeebasubs_token = "<?php echo JFactory::getSession()->getFormToken();?>";

	(function($) {
		$(document).ready(function(){
			$('#toolbar-subrefresh').click(akeebasubs_refresh_integrations);
		});
	})(akeeba.jQuery);
</script>
