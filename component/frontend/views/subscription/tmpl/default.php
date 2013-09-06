<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

FOFTemplateUtils::addCSS('media://com_akeebasubs/css/frontend.css?'.AKEEBASUBS_VERSIONHASH);

$this->loadHelper('cparams');
$this->loadHelper('modules');
$this->loadHelper('format');

JLoader::import('joomla.utilities.date');
JLoader::import('joomla.plugin.helper');
JPluginHelper::importPlugin('akeebasubs');

$app        = JFactory::getApplication();
$jPublishUp = new JDate($this->item->publish_up);
?>

<div id="akeebasubs">

<table class="table table-striped">
	<tr>
		<td class="subscription-label"><?php echo JText::_('COM_AKEEBASUBS_COMMON_ID')?></td>
		<td class="subscription-info">
			<strong><?php echo sprintf('%05u', $this->item->akeebasubs_subscription_id)?></strong>
		</td>
	</tr>
	<tr>
		<td class="subscription-label"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_USER')?></td>
		<td class="subscription-info">
			<strong><?php echo JFactory::getUser($this->item->user_id)->username?></strong>
			(<em><?php echo JFactory::getUser($this->item->user_id)->name?></em>)
		</td>
	</tr>
	<tr>
		<td class="subscription-label"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_LEVEL')?></td>
		<td class="subscription-info">
			<?php echo FOFModel::getTmpInstance('Levels','AkeebasubsModel')->setId($this->item->akeebasubs_level_id)->getItem()->title?>
		</td>
	</tr>
	<tr>
		<td class="subscription-label"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_PUBLISH_UP')?></td>
		<td class="subscription-info">
			<?php echo AkeebasubsHelperFormat::date($this->item->publish_up) ?>
		</td>
	</tr>
	<tr>
		<td class="subscription-label"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_PUBLISH_DOWN')?></td>
		<td class="subscription-info">
			<?php echo AkeebasubsHelperFormat::date($this->item->publish_down) ?>
		</td>
	</tr>
	<tr>
		<td class="subscription-label"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_ENABLED')?></td>
		<td class="subscription-info">
			<?php if($this->item->enabled):?>
			<img src="<?php echo JURI::base(); ?>/media/com_akeebasubs/images/frontend/enabled.png" align="center" title="<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_ENABLED_ACTIVE') ?>" />
			<?php elseif($jPublishUp->toUnix() >= time()):?>
			<img src="<?php echo JURI::base(); ?>/media/com_akeebasubs/images/frontend/scheduled.png" align="center" title="<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_ENABLED_PENDING') ?>" />
			<?php else:?>
			<img src="<?php echo JURI::base(); ?>/media/com_akeebasubs/images/frontend/disabled.png" align="center" title="<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_ENABLED_INACTIVE') ?>" />
			<?php endif;?>
		</td>
	</tr>
	<tr>
		<td class="subscription-label"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_STATE')?></td>
		<td class="subscription-info"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_STATE_'.$this->item->state)?></td>
	</tr>
	<tr>
		<td class="subscription-label"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_AMOUNT_PAID')?></td>
		<td class="subscription-info">
			<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'before'): ?>
			<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
			<?php endif; ?>
			<?php echo sprintf('%2.02F',$this->item->gross_amount)?>
			<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'after'): ?>
			<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
			<?php endif; ?>
		</td>
	</tr>
	<tr>
		<td class="subscription-label"><?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_SUBSCRIBED_ON')?></td>
		<td class="subscription-info">
			<?php echo AkeebasubsHelperFormat::date($this->item->created_on) ?>
		</td>
	</tr>
</table>

<?php
$args      = array(
				array(
					'useredit'          => true,
					'subscriptionlevel' => $this->item->akeebasubs_level_id,
					'subcustom'         => $this->item->params
				)
			 );

$jResponse = $app->triggerEvent('onSubscriptionFormRenderPerSubFields', $args);
@ob_start();
if(is_array($jResponse) && !empty($jResponse)) foreach($jResponse as $customFields):
	if(is_array($customFields) && !empty($customFields)) foreach($customFields as $field):

		$customField_class = '';
		$validationClass   = '';

		if(array_key_exists('isValid', $field)) {
			$customField_class = $field['isValid'] ? (array_key_exists('validLabel', $field) ? 'success' : '') : 'error';
		}

		if(array_key_exists('validationClass', $field)){
			$validationClass = $field['validationClass'];
		}

		?>
		<div class="control-group <?php echo $customField_class ?>">
			<label for="<?php echo $field['id']?>" class="control-label">
				<?php echo $field['label']?>
			</label>
			<div class="controls">
				<?php echo $field['elementHTML']?>
				<?php if(array_key_exists('validLabel', $field)):?>
					<span id="<?php echo $field['id']?>_valid" class="help-inline <?php echo $validationClass ?>"
					      style="<?php if(!$field['isValid'] || !$apply_validation):?>display:none<?php endif?>">
					  <?php echo $field['validLabel']?>
					</span>
				<?php endif;?>
				<?php if(array_key_exists('invalidLabel', $field)):?>
					<span id="<?php echo $field['id']?>_invalid" class="help-inline <?php echo $validationClass ?>"
					      style="<?php if($field['isValid'] || !$apply_validation):?>display:none<?php endif?>">
					  <?php echo $field['invalidLabel']?>
					</span>
				<?php endif;?>
			</div>
		</div>

	<?php
	endforeach;
endforeach;
$subfieldsHTML = trim(@ob_get_clean());
if(!empty($subfieldsHTML)):
	// Do I have to inject any plugin validators?
	require_once JPATH_ROOT.'/components/com_akeebasubs/helpers/js.php';
	AkeebasubsHelperJs::deployValidator();
?>
	<fieldset>
		<legend><?php echo JText::_('COM_AKEEBASUBS_LEVEL_PERSUBFIELDS')?></legend>
		<form class="form form-horizontal">
		<?php echo $subfieldsHTML ?>
		</form>
	</fieldset>
<?php endif; ?>

<div class="akeebasubs-goback">
	<p>
		<a class="btn btn-large btn-primary" href="<?php echo JRoute::_('index.php?option=com_akeebasubs&view=subscriptions')?>">
			<span class="icon-white icon-arrow-left"></span>
			<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_TITLE')?>
		</a>
	</p>
</div>

	<?php // Subscription ID, so it's available for JS stuff ?>
	<input type="hidden" id="akeebasubs_subscription_id" value="<?php echo $this->item->akeebasubs_subscription_id?>"/>
	<input type="hidden" id="akeebasubs_level_id" value="<?php echo $this->item->akeebasubs_level_id?>"/>
</div>