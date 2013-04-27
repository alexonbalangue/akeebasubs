<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

JHtml::_('behavior.tooltip');

$editor = JFactory::getEditor();

$this->loadHelper('select');
$this->loadHelper('cparams');
?>

<form action="index.php" method="post" name="adminForm" id="adminForm" class="form form-horizontal">
	<input type="hidden" name="option" value="com_akeebasubs" />
	<input type="hidden" name="view" value="level" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="akeebasubs_level_id" value="<?php echo $this->item->akeebasubs_level_id ?>" />
	<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />

<div class="row-fluid">

	<div class="span6">
		<h3><?php echo JText::_('COM_AKEEBASUBS_LEVEL_BASIC_TITLE'); ?></h3>

		<div class="control-group">
			<label for="title_field" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_TITLE'); ?></label>
			<div class="controls">
				<input type="text" size="20" id="title_field" name="title" class="title" value="<?php echo $this->escape($this->item->title) ?>" />
			</div>
		</div>

		<div class="control-group">
			<label for="slug_field" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_SLUG'); ?></label>
			<div class="controls">
				<input id="slug_field" type="text" name="slug" class="slug" value="<?php echo  $this->item->slug; ?>" />
				<p class="help-block">
					<?php echo JText::_( 'COM_AKEEBASUBS_LEVEL_FIELD_SLUG_TIP' );?>
				</p>
			</div>
		</div>

		<div class="control-group">
			<label for="enabled" class="control-label">
				<?php echo JText::_('JPUBLISHED'); ?>
			</label>
			<div class="controls">
				<?php echo JHTML::_('select.booleanlist', 'enabled', null, $this->item->enabled); ?>
			</div>
		</div>

		<div class="control-group">
			<label for="image_field" class="control-label">
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_IMAGE'); ?>
			</label>
			<div class="controls">
				<?php if(version_compare(JVERSION, '3.0', 'lt')): ?>
				<?php echo JHTML::_('list.images', 'image', $this->item->image, null, '/'.trim(AkeebasubsHelperCparams::getParam('imagedir','images/'),'/').'/', 'swf|gif|jpg|png|bmp'); ?>
				<img class="level-image-preview" src="../<?php echo trim(AkeebasubsHelperCparams::getParam('imagedir','images/'),'/') ?>/<?php echo $this->item->image?>" name="imagelib" />
				<?php else: ?>
				<?php
					$fake_data = '<field name="image" type="media" directory="images" />';
					$fakeElement = new SimpleXMLElement($fake_data);
					$fakeForm = new JForm('fakeForm');
					$media = new JFormFieldMedia($fakeForm);
					$media->setup($fakeElement, $this->item->image);
					echo $media->input;
				?>
				<?php endif; ?>
			</div>
		</div>

		<div class="control-group">
			<label for="duration_field" class="control-label">
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_DURATION'); ?>
			</label>
			<div class="controls">
				<input type="text" size="6" id="duration_field" name="duration" value="<?php echo (int)$this->item->duration ?>" />
			</div>
		</div>

		<div class="control-group">
			<label for="fixed_date" class="control-label"><?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_FIXED_DATE')?></label>
			<div class="controls">
				<?php echo JHTML::_('calendar', $this->item->fixed_date, 'fixed_date', 'fixed_date'); ?>
				<p class="help-block">
					<?php echo JText::_( 'COM_AKEEBASUBS_LEVEL_FIELD_FIXED_DATE_TIP' );?>
				</p>
			</div>
		</div>

		<div class="control-group">
			<label for="forever" class="control-label">
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_FOREVER'); ?>
			</label>
			<div class="controls">
				<?php echo JHTML::_('select.booleanlist', 'forever', null, $this->item->forever); ?>
			</div>
		</div>

		<div class="control-group">
			<label for="price_field" class="control-label">
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_PRICE'); ?>
			</label>
			<div class="controls">
				<div class="input-<?php echo (AkeebasubsHelperCparams::getParam('currencypos','before') == 'before') ? 'prepend' : 'append' ?>">
					<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'before'): ?>
					<span class="add-on">
						<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
					</span>
					<?php endif; ?>
					<input type="text" size="15" id="price_field" name="price" value="<?php echo  $this->item->price ?>" style="float: none" />
					<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'after'): ?>
					<span class="add-on">
						<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
					</span>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<div class="control-group">
			<label for="signupfee_field" class="control-label">
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_SIGNUPFEE'); ?>
			</label>
			<div class="controls">
				<div class="input-<?php echo (AkeebasubsHelperCparams::getParam('currencypos','before') == 'before') ? 'prepend' : 'append' ?>">
					<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'before'): ?>
					<span class="add-on">
						<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
					</span>
					<?php endif; ?>
					<input type="text" size="15" id="signupfee_field" name="signupfee" value="<?php echo  $this->item->signupfee ?>" style="float: none" />
					<?php if(AkeebasubsHelperCparams::getParam('currencypos','before') == 'after'): ?>
					<span class="add-on">
						<?php echo AkeebasubsHelperCparams::getParam('currencysymbol','€')?>
					</span>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<div class="control-group">
			<label for="akeebasubs_level_id" class="control-label">
				<?php echo JText::_('COM_AKEEBASUBS_LEVELS_FIELD_LEVELGROUP'); ?>
			</label>
			<div class="controls">
				<?php echo AkeebasubsHelperSelect::levelgroups($this->item->akeebasubs_levelgroup_id); ?>
			</div>
		</div>
	</div>

	<div class="span6">
		<div class="control-group">
			<label for="only_once" class="control-label">
				<?php echo JText::_('COM_AKEEBASUBS_LEVELS_FIELD_ONLY_ONCE'); ?>
			</label>
			<div class="controls">
				<?php echo JHTML::_('select.booleanlist', 'only_once', null, $this->item->only_once); ?>
			</div>
		</div>

		<div class="control-group">
			<label for="recurring" class="control-label" title="<?php echo JText::_('COM_AKEEBASUBS_LEVELS_FIELD_RECURRING_TITLE') ?>">
				<?php echo JText::_('COM_AKEEBASUBS_LEVELS_FIELD_RECURRING'); ?>
			</label>
			<div class="controls">
				<?php echo JHTML::_('select.booleanlist', 'recurring', null, $this->item->recurring); ?>
			</div>
		</div>

		<div class="control-group">
			<label for="" class="control-label">
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_PAYMENT_PLUGINS'); ?>
			</label>
			<div class="controls">
				<?php echo AkeebasubsHelperSelect::paymentmethods('payment_plugins[]', $this->item->payment_plugins, array('id'=>'payment_plugins', 'multiple' => 'multiple', 'always_dropdown' => 1, 'default_option' => 1)) ?>
			</div>
		</div>

		<div class="control-group">
			<label for="notify1_field" class="control-label">
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_NOTIFY1'); ?>
			</label>
			<div class="controls">
				<input type="text" size="6" id="notify1_field" name="notify1" value="<?php echo  (int)$this->item->notify1 ?>" />
			</div>
		</div>

		<div class="control-group">
			<label for="notify2_field" class="control-label">
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_NOTIFY2'); ?>
			</label>
			<div class="controls">
				<input type="text" size="6" id="notify2_field" name="notify2" value="<?php echo  (int)$this->item->notify2 ?>" />
			</div>
		</div>

		<div class="control-group">
			<label for="notifyafter_field" class="control-label">
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_NOTIFYAFTER'); ?>
			</label>
			<div class="controls">
				<input type="text" size="6" id="notifyafter_field" name="notifyafter" value="<?php echo  (int)$this->item->notifyafter ?>" />
			</div>
		</div>

		<div class="control-group">
			<label for="description" class="control-label">
				<?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_DESCRIPTION'); ?>
			</label>
			<div class="controls">
			</div>
		</div>

		<?php echo $editor->display( 'description',  $this->item->description, '97%', '210', '50', '10', false ) ; ?>
	</div>

</div>

<?php
	JLoader::import('joomla.plugin.helper');
	JPluginHelper::importPlugin('akeebasubs');
	$app = JFactory::getApplication();
	$params = $this->item->params;
	if(is_array($params)) {
		$params = (object)$params;
	} else {
		$params = new stdClass();
	}
	$this->item->params = $params;
	$jResponse = $app->triggerEvent('onSubscriptionLevelFormRender', array($this->item));
	if(is_array($jResponse) && !empty($jResponse)):
?>
<hr/>
<div class="row-fluid">
	<div class="span12">
		<h3><?php echo JText::_('COM_AKEEBASUBS_LEVEL_INTEGRATION_TITLE'); ?></h3>
		<div class="tabbable">
			<ul class="nav nav-tabs">
<?php $n = 0; foreach($jResponse as $customGroup): $n++; ?>
				<li <?php if($n==1): ?>class="active"<?php endif; ?>>
					<a href="#tab<?php echo $n ?>" data-toggle="tab"><?php echo $customGroup->title ?></a>
				</li>

<?php endforeach; ?>
			</ul>
			<div class="tab-content">
<?php $n = 0; foreach($jResponse as $customGroup): $n++; ?>
				<div class="tab-pane <?php if($n==1): ?>active<?php endif; ?>" id="tab<?php echo $n ?>">
<?php echo $customGroup->html ?>
				</div>

<?php endforeach; ?>
			</div>
		</div>
	</div>
</div>

<?php endif; ?>

<hr/>
<div class="row-fluid">

	<div class="span6">
		<h3><?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_ORDERTEXT') ?></h3>
		<?php echo $editor->display( 'ordertext',  $this->item->ordertext, '97%', '391', '50', '20', false ) ; ?>
	</div>

	<div class="span6">
		<h3><?php echo JText::_('COM_AKEEBASUBS_LEVEL_FIELD_CANCELTEXT') ?></h3>
		<?php echo $editor->display( 'canceltext',  $this->item->canceltext, '97%', '391', '50', '20', false ) ; ?>
	</div>
</div>

</form>
