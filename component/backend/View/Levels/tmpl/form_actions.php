<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

use Akeeba\Subscriptions\Admin\Helper\ComponentParams;

JLoader::import('joomla.plugin.helper');
JPluginHelper::importPlugin('akeebasubs');
$app = JFactory::getApplication();
$params = $model->params;

if (is_array($params))
{
	$params = (object)$params;
}
else
{
	$params = new stdClass();
}

$model->params = $params;
$jResponse = $app->triggerEvent('onSubscriptionLevelFormRender', array($model));

if (is_array($jResponse) && !empty($jResponse)):
	?>
	<h3><?php echo JText::_('COM_AKEEBASUBS_LEVEL_INTEGRATION_TITLE'); ?></h3>
	<div class="tabbable">
		<ul class="nav nav-tabs">
			<?php $n = 0;
			foreach ($jResponse as $customGroup): $n++; ?>
				<li <?php if ($n == 1): ?>class="active"<?php endif; ?>>
					<a href="#tab<?php echo $n ?>" data-toggle="tab"><?php echo $customGroup->title ?></a>
				</li>
			<?php endforeach; ?>
		</ul>
		<div class="tab-content">
			<?php $n = 0;
			foreach ($jResponse as $customGroup): $n++; ?>
				<div class="tab-pane <?php if ($n == 1): ?>active<?php endif; ?>" id="tab<?php echo $n ?>">
					<?php echo $customGroup->html ?>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
<?php endif; ?>