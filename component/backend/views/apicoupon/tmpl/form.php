<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

$this->loadHelper('select');
JFactory::getDocument()->addScriptDeclaration('
akeeba.jQuery(document).ready(function(){
	akeeba.jQuery("#usage_limits").change(function(){
		var value = akeeba.jQuery(this).val();
		if(value == 1){
			akeeba.jQuery("#creation_limit_field").show();
			akeeba.jQuery("#subscription_limit_field").hide().val("0");
			akeeba.jQuery("#value_limit_field").hide().val("0");
		}
		else if(value == 2){
			akeeba.jQuery("#creation_limit_field").hide().val("0");
			akeeba.jQuery("#subscription_limit_field").show();
			akeeba.jQuery("#value_limit_field").hide().val("0");
		}
		else{
			akeeba.jQuery("#creation_limit_field").hide().val("0");
			akeeba.jQuery("#subscription_limit_field").hide().val("0");
			akeeba.jQuery("#value_limit_field").show();
		}
	}).change();
})
');
?>
<form action="index.php" method="post" name="adminForm" id="adminForm" class="form form-horizontal">
	<input type="hidden" name="option" value="com_akeebasubs" />
	<input type="hidden" name="view" value="apicoupon" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="akeebasubs_apicoupon_id" value="<?php echo $this->item->akeebasubs_apicoupon_id ?>" />
	<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />

	<div class="row-fluid">
		<div class="span6">
			<h3><?php echo JText::_('COM_AKEEBASUBS_COUPON_BASIC_TITLE')?></h3>

			<div class="control-group">
				<label for="title_field" class="control-label">
					<?php echo  JText::_('COM_AKEEBASUBS_COUPON_FIELD_TITLE'); ?>
				</label>
				<div class="controls">
					<input type="text" size="30" id="title_field" name="title" value="<?php echo  $this->escape($this->item->title) ?>" />
				</div>
			</div>
			<div class="control-group">
				<label for="key_field" class="control-label">
					<?php echo  JText::_('COM_AKEEBASUBS_APICOUPONS_KEY'); ?>
				</label>
				<div class="controls">
					<input type="text" size="25" id="key_field" name="key" value="<?php echo  $this->escape($this->item->key) ?>" />
				</div>
			</div>
			<div class="control-group">
				<label for="password_field" class="control-label">
					<?php echo  JText::_('COM_AKEEBASUBS_APICOUPONS_PWD'); ?>
				</label>
				<div class="controls">
					<input type="text" size="20" id="password_field" name="value" value="<?php echo  $this->escape($this->item->password) ?>" />
				</div>
			</div>
			<div class="control-group">
				<label for="type_field" class="control-label">
					<?php echo  JText::_('COM_AKEEBASUBS_COUPON_FIELD_TYPE'); ?>
				</label>
				<div class="controls">
					<?php echo AkeebasubsHelperSelect::coupontypes('type',$this->item->type) ?>
				</div>
			</div>
			<div class="control-group">
				<label for="value_field" class="control-label">
					<?php echo  JText::_('COM_AKEEBASUBS_COUPON_FIELD_VALUE'); ?>
				</label>
				<div class="controls">
					<input type="text" size="20" id="value_field" name="value" value="<?php echo  $this->escape($this->item->value) ?>" />
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
		</div>

		<div class="span6">
			<h3><?php echo JText::_('COM_AKEEBASUBS_COUPONS_LIMITS')?></h3>

			<div class="control-group">
				<label for="subscriptions_field" class="control-label"><?php echo  JText::_('COM_AKEEBASUBS_COUPON_FIELD_SUBSCRIPTIONS'); ?></label>
				<div class="controls">
					<?php echo AkeebasubsHelperSelect::levels('subscriptions[]', empty($this->item->subscriptions) ? '-1' : explode(',',$this->item->subscriptions), array('multiple' => 'multiple', 'size' => 3)) ?>
				</div>
			</div>

			<div class="control-group">
				<label for="usage_limits" class="control-label"><?php echo  JText::_('COM_AKEEBASUBS_APICOUPONS_FIELD_USAGE_LIMITS'); ?></label>
				<div class="controls">
					<?php
						$selected = $this->item->creation_limit ? 1 : ($this->item->subscription_limit ? 2 : 3);
						echo AkeebasubsHelperSelect::apicouponLimits('usage_limits', $selected)?>

						<input type="text" style="width: 50px; display:none" id="creation_limit_field" name="creation_limit" value="<?php echo  $this->escape($this->item->creation_limit) ?>" />
						<input type="text" style="width: 50px; display:none" id="subscription_limit_field" name="subscription_limit" value="<?php echo  $this->escape($this->item->subscription_limit ) ?>" />
						<input type="text" style="width: 50px; display:none" id="value_limit_field" name="value_limit" value="<?php echo  $this->escape($this->item->value_limit ) ?>" />

				</div>
			</div>
		</div>
	</div>
</form>