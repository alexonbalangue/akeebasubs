<?php
/**
 * @package   AkeebaSubs
 * @copyright Copyright (c)2010-2015 Nicholas K. Dionysopoulos
 * @license   GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

JLoader::import('joomla.plugin.helper');
JPluginHelper::importPlugin('akeebasubs');
$app = JFactory::getApplication();
$params = @json_decode($fieldValue->params);

if (empty($params))
{
	$params = new stdClass();
}

$userparams = (object)array('params' => $params);
$jResponse = $app->triggerEvent('onSubscriptionFormRender', array($userparams, array('subscriptionlevel' => -1, 'custom' => array())));
if (is_array($jResponse) && !empty($jResponse))
{
	foreach ($jResponse as $customFields):
		if (is_array($customFields) && !empty($customFields))
		{
			foreach ($customFields as $field): ?>
				<div class="control-group">
					<label for="<?php echo $field['id'] ?>" class="control-label"><?php echo $field['label'] ?></label>

					<div class="controls">
						<?php echo $field['elementHTML'] ?>
					</div>
				</div>

			<?php endforeach;
		} endforeach;
}