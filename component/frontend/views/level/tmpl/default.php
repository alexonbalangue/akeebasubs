<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2011 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

FOFTemplateUtils::addCSS('media://com_akeebasubs/css/frontend.css?'.AKEEBASUBS_VERSIONHASH);

require_once JPATH_ADMINISTRATOR.'/components/com_akeebasubs/helpers/cparams.php';
$this->loadHelper('modules');

$script = <<<ENDSCRIPT
akeebasubs_level_id = {$this->item->akeebasubs_level_id};
ENDSCRIPT;
JFactory::getDocument()->addScriptDeclaration($script);
?>

<div id="akeebasubs">

<?php echo AkeebasubsHelperModules::loadposition('akeebasubscriptionsheader')?>

<?php if(AkeebasubsHelperCparams::getParam('stepsbar',1) && ($this->validation->price->net > 0.01)):?>
<?php echo $this->loadAnyTemplate('level/steps',array('step'=>'subscribe')); ?>
<?php endif; ?>

<?php echo $this->loadTemplate('level') ?>

<noscript>
<hr/>
<h1><?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_NOJS_HEADER')?></h1>
<p><?php echo JText::_('COM_AKEEBASUBS_LEVEL_ERR_NOJS_BODY')?></p>
<hr/>
</noscript>

<?php if(JFactory::getUser()->guest && AkeebasubsHelperCparams::getParam('allowlogin',1)):?>
	<?php echo $this->loadTemplate('login') ?>
<?php endif?>

<form action="<?php echo JRoute::_('index.php?option=com_akeebasubs&view=subscribe&layout=default&slug='.FOFInput::getString('slug','',$this->input))?>" method="post"
	id="signupForm" >
	<input type="hidden" name="_token" value="<?php echo JUtility::getToken()?>" />
	
	<?php echo $this->loadTemplate('fields'); ?>
	
	<div id="paymentmethod-container" <?php echo ($this->validation->price->gross < 0.01) ? 'style="display: none;"' : '' ?>>
		<label for="paymentmethod" class="main"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_METHOD')?></label>
		<?php echo AkeebasubsHelperSelect::paymentmethods('paymentmethod', '', array('id'=>'paymentmethod')) ?>
		<br/>
	</div>
	<label for="subscribenow" class="main">&nbsp;</label>
	<input id="subscribenow" type="submit" value="<?php echo JText::_('COM_AKEEBASUBS_LEVEL_BUTTON_SUBSCRIBE')?>" />
	<img id="ui-disable-spinner" src="<?php echo JURI::base()?>media/com_akeebasubs/images/throbber.gif" style="display: none" />

	<?php if($this->validation->price->net < 0.01): ?><div style="display:none"><?php endif ?>
	<h3 class="subs"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_COUPONANDSUMMARY')?></h3>

	<noscript>
		<p>
			<?php echo JText::_('COM_AKEEBASUBS_LEVEL_SUM_NOSCRIPT')?>
		</p>
	</noscript>

	<label class="main"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_SUM_NET')?></label>
	<span id="akeebasubs-sum-net" class="currency"><?php echo $this->validation->price->net?></span>
	<span class="currency-symbol"><?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?></span>
	<br/>
	<label class="main"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_SUM_DISCOUNT')?></label>
	<span id="akeebasubs-sum-discount" class="currency"><?php echo $this->validation->price->discount?></span>
	<span class="currency-symbol"><?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?></span>
	<br/>
	<label class="main"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_SUM_VAT')?></label>
	<span id="akeebasubs-sum-vat" class="currency"><?php echo $this->validation->price->tax?></span>
	<span class="currency-symbol"><?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?></span>
	<br/>
	<label class="main  total"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_SUM_TOTAL')?></label>
	<span id="akeebasubs-sum-total" class="currency total"><?php echo $this->validation->price->gross?></span>
	<span class="currency-symbol total"><?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?></span>
	<?php if($this->validation->price->net < 0.01): ?></div><?php endif ?>
</form>

<?php echo AkeebasubsHelperModules::loadposition('akeebasubscriptionsfooter')?>

</div>