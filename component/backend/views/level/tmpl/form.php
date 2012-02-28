<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2012 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

FOFTemplateUtils::addCSS('media://com_akeebasubs/css/backend.css?'.AKEEBASUBS_VERSIONHASH);
FOFTemplateUtils::addJS('media://com_akeebasubs/js/akeebajq.js?'.AKEEBASUBS_VERSIONHASH);
FOFTemplateUtils::addJS('media://com_akeebasubs/js/backend.js?'.AKEEBASUBS_VERSIONHASH);
JHtml::_('behavior.tooltip');

$editor = JFactory::getEditor();

$this->loadHelper('cparams');
?>
<form action="index.php" method="post" name="adminForm">
	<input type="hidden" name="option" value="com_akeebasubs" />
	<input type="hidden" name="view" value="level" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="akeebasubs_level_id" value="<?php echo $this->item->akeebasubs_level_id ?>" />
	<input type="hidden" name="<?php echo JUtility::getToken();?>" value="1" />
	
	<fieldset id="levels-basic">
		<legend><?php echo JText::_('COM_AKEEBASUBS_LEVEL_BASIC_TITLE'); ?></legend>
		
		<label for="title_field" class="main title"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_TITLE'); ?></label>
		<input type="text" size="20" id="title_field" name="title" class="title" value="<?php echo $this->escape($this->item->title) ?>" />
		<div class="akeebasubs-clear"></div>
		
		<span class="hasTip" title="<?php echo JText::_( 'COM_AKEEBASUBS_LEVEL_FIELD_SLUG_TIP' );?>::<?php echo JText::_( 'Slug Tip' ); ?>">
		<label for="slug_field" class="main" class="mainlabel"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_SLUG'); ?></label>					
		</span>
		<input id="slug_field" type="text" name="slug" class="slug" value="<?php echo  $this->item->slug; ?>" />
		<div class="akeebasubs-clear"></div>
		
		<label for="enabled" class="main" class="mainlabel">
			<?php if(version_compare(JVERSION,'1.6.0','ge')): ?>
			<?php echo JText::_('JPUBLISHED'); ?>
			<?php else: ?>
			<?php echo JText::_('PUBLISHED'); ?>
			<?php endif; ?>
		</label>
		<span class="akeebasubs-booleangroup">
			<?php echo JHTML::_('select.booleanlist', 'enabled', null, $this->item->enabled); ?>
		</span>
		<div class="akeebasubs-clear"></div>
		
		<label for="image_field" class="main"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_IMAGE'); ?></label>
		<?php echo JHTML::_('list.images', 'image', $this->item->image, null, '/'.trim(AkeebasubsHelperCparams::getParam('imagedir',version_compare(JVERSION,'1.6.0','ge') ? 'images/' :'images/stories/'),'/').'/', 'swf|gif|jpg|png|bmp'); ?>
		<div class="akeebasubs-clear"></div>
		<img class="level-image-preview" src="../<?php echo trim(AkeebasubsHelperCparams::getParam('imagedir',version_compare(JVERSION,'1.6.0','ge') ? 'images/' :'images/stories/'),'/') ?>/<?php echo $this->item->image?>" name="imagelib" />
		<div class="akeebasubs-clear"></div>

		<label for="duration_field" class="main"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_DURATION'); ?></label>
		<input type="text" size="6" id="duration_field" name="duration" value="<?php echo (int)$this->item->duration ?>" />
		<div class="akeebasubs-clear"></div>

		<label for="price_field" class="main"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_PRICE'); ?></label>
		<span>
			<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'before'): ?>
			<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
			<?php endif; ?>
			<input type="text" size="15" id="price_field" name="price" value="<?php echo  $this->item->price ?>" style="float: none" />
			<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'after'): ?>
			<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
			<?php endif; ?>
		</span>
		<div class="akeebasubs-clear"></div>
		
		<label for="only_once" class="main mainlabel"><?php echo JText::_('COM_AKEEBASUBS_LEVELS_FIELD_ONLY_ONCE'); ?></label>
		<span class="akeebasubs-booleangroup">
			<?php echo JHTML::_('select.booleanlist', 'only_once', null, $this->item->only_once); ?>
		</span>
		<div class="akeebasubs-clear"></div>
		
		<label for="notify1_field" class="main"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_NOTIFY1'); ?></label>
		<input type="text" size="6" id="notify1_field" name="notify1" value="<?php echo  (int)$this->item->notify1 ?>" />
		<div class="akeebasubs-clear"></div>
		
		<label for="notify2_field" class="main"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_NOTIFY2'); ?></label>
		<input type="text" size="6" id="notify2_field" name="notify2" value="<?php echo  (int)$this->item->notify2 ?>" />
		<div class="akeebasubs-clear"></div>
	</fieldset>
	
	<fieldset>
		<legend><?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_DESCRIPTION') ?></legend>
		<?php echo $editor->display( 'description',  $this->item->description, '450', '210', '50', '10', false ) ; ?>
	</fieldset>

	<div class="akeebasubs-clear"></div>
		
	<fieldset class="akeebasubs-float-left">
		<legend><?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_ORDERTEXT') ?></legend>
		<?php echo $editor->display( 'ordertext',  $this->item->ordertext, '97%', '391', '50', '20', false ) ; ?>
	</fieldset>

	<fieldset class="akeebasubs-float-left">
		<legend><?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_CANCELTEXT') ?></legend>
		<?php echo $editor->display( 'canceltext',  $this->item->canceltext, '97%', '391', '50', '20', false ) ; ?>
	</fieldset>

</form>