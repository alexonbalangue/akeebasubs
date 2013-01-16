<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

// Protect from unauthorized access
defined('_JEXEC') or die();

JHtml::_('behavior.tooltip');
if(version_compare(JVERSION, '3.0', 'ge')) {
	JHTML::_('behavior.framework');
} else {
	JHTML::_('behavior.mootools');
}
JHtml::_('behavior.modal');

$this->loadHelper('cparams');
$this->loadHelper('select');
$this->loadHelper('params');
?>

<form action="index.php" method="post" name="adminForm" id="adminForm" class="form form-horizontal">
	<input type="hidden" name="option" value="com_akeebasubs" />
	<input type="hidden" name="view" value="relation" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="akeebasubs_relation_id" value="<?php echo $this->item->akeebasubs_relation_id ?>" />
	<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />

	<div class="row-fluid">
		<div class="span6">
			<h3><?php echo JText::_('COM_AKEEBASUBS_RELATION_BASIC_TITLE')?></h3>
			
			<div class="control-group">
				<label for="source_level_id" class="control-label">
					<?php echo  JText::_('COM_AKEEBASUBS_RELATIONS_FIELD_SOURCE_LEVEL_ID'); ?>
				</label>
				<div class="controls">
					<?php echo AkeebasubsHelperSelect::levels('source_level_id', $this->item->source_level_id) ?>
				</div>
			</div>

			<div class="control-group">
				<label for="target_level_id" class="control-label">
					<?php echo  JText::_('COM_AKEEBASUBS_RELATIONS_FIELD_TARGET_LEVEL_ID'); ?>
				</label>
				<div class="controls">
					<?php echo AkeebasubsHelperSelect::levels('target_level_id', $this->item->target_level_id) ?>
				</div>
			</div>

			<div class="control-group">
				<label for="mode" class="control-label">
					<?php echo  JText::_('COM_AKEEBASUBS_RELATIONS_FIELD_MODE'); ?>
				</label>
				<div class="controls">
					<?php echo AkeebasubsHelperSelect::relationmode('mode', empty($this->item->mode) ? 'flexi' : $this->item->mode, array('onchange' => 'akeebasubs_relations_mode_onChange();')) ?>
				</div>
			</div>

			<div class="control-group">
				<label for="type" class="control-label">
					<?php echo  JText::_('COM_AKEEBASUBS_RELATIONS_FIELD_TYPE'); ?>
				</label>
				<div class="controls">
					<?php echo AkeebasubsHelperSelect::coupontypes('type',$this->item->type) ?>			
				</div>
			</div>
			
			<div class="control-group">
				<label for="expiration" class="control-label">
					<?php echo  JText::_('COM_AKEEBASUBS_RELATIONS_FIELD_EXPIRATION'); ?>
				</label>
				<div class="controls">
					<?php echo AkeebasubsHelperSelect::flexiexpiration('expiration',$this->item->expiration) ?>			
				</div>
			</div>
			
			<div class="control-group">
				<label for="combine" class="control-label">
					<?php echo JText::_('COM_AKEEBASUBS_RELATIONS_FIELD_COMBINE'); ?>
				</label>
				<div class="controls">
					<?php echo JHTML::_('select.booleanlist', 'combine', null, $this->item->combine); ?>
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
		
		<div class="span6" style="display: none" id="akeebasubs-relations-fixed">
			<h3><?php echo JText::_('COM_AKEEBASUBS_RELATION_FIXED_TITLE')?></h3>
			
			<div class="control-group">
				<label for="amount" class="control-label">
					<?php echo  JText::_('COM_AKEEBASUBS_RELATIONS_FIELD_AMOUNT'); ?>
				</label>
				<div class="controls">
					<input type="text" size="20" id="amount" name="amount" value="<?php echo  $this->escape($this->item->amount) ?>" />
				</div>
			</div>
			
		</div>
		
		<div class="span6" style="display: none" id="akeebasubs-relations-flexi">
			<h3><?php echo JText::_('COM_AKEEBASUBS_RELATION_FLEXI_TITLE')?></h3>
			
			<div class="control-group">
				<label class="control-label">
					<?php echo JText::_('COM_AKEEBASUBS_RELATION_FLEXIBLE_TITLE'); ?>
				</label>
				<div class="controls">
					<input type="text" name="flex_amount" class="input-mini" value="<?php echo $this->escape($this->item->flex_amount) ?>" />
					<span><?php echo JText::_('COM_AKEEBASUBS_RELATION_FLEXI_PRE') ?></span>
					<br/>
					<input type="text" name="flex_period" class="input-mini" value="<?php echo $this->escape($this->item->flex_period) ?>" />
					<?php echo AkeebasubsHelperSelect::flexiperioduoms('flex_uom',$this->item->flex_uom,array('class' => 'input-small')) ?>
					<span><?php echo JText::_('COM_AKEEBASUBS_RELATION_FLEXI_POST') ?></span>
				</div>
			</div>

			<div class="control-group">
				<label class="control-label">
					<?php echo JText::_('COM_AKEEBASUBS_RELATION_LOWTHRESHOLD_TITLE'); ?>
				</label>
				<div class="controls">
					<input type="text" name="low_amount" class="input-mini" value="<?php echo $this->escape($this->item->low_amount) ?>" />
					<span><?php echo JText::_('COM_AKEEBASUBS_RELATION_LOW_PRE') ?></span>
					<br/>
					<input type="text" name="low_threshold" class="input-mini" value="<?php echo $this->escape($this->item->low_threshold) ?>" />
					<span><?php echo JText::_('COM_AKEEBASUBS_RELATION_LOW_POST') ?></span>
				</div>
			</div>

			<div class="control-group">
				<label class="control-label">
					<?php echo JText::_('COM_AKEEBASUBS_RELATION_HIGHTHRESHOLD_TITLE'); ?>
				</label>
				<div class="controls">
					<input type="text" name="high_amount" class="input-mini" value="<?php echo $this->escape($this->item->high_amount) ?>" />
					<span><?php echo JText::_('COM_AKEEBASUBS_RELATION_HIGH_PRE') ?></span>
					<br/>
					<input type="text" name="high_threshold" class="input-mini" value="<?php echo $this->escape($this->item->high_threshold) ?>" />
					<span><?php echo JText::_('COM_AKEEBASUBS_RELATION_HIGH_POST') ?></span>
				</div>
			</div>

			<div class="control-group">
				<label for="flex_timecalculation" class="control-label">
					<?php echo  JText::_('COM_AKEEBASUBS_RELATIONS_FIELD_FLEX_TIMECALCULATION'); ?>
				</label>
				<div class="controls">
					<?php echo AkeebasubsHelperSelect::flexitimecalc('flex_timecalculation',empty($this->item->flex_timecalculation) ? 'current' : $this->item->flex_timecalculation) ?>
				</div>
			</div>

			<div class="control-group">
				<label for="flex_rounding" class="control-label">
					<?php echo  JText::_('COM_AKEEBASUBS_RELATIONS_FIELD_TIME_ROUNDING'); ?>
				</label>
				<div class="controls">
					<?php echo AkeebasubsHelperSelect::flexirounding('time_rounding',empty($this->item->time_rounding) ? 'round' : $this->item->time_rounding) ?>
				</div>
			</div>

		</div>
	</div>

</form>

<script type="text/javascript">
	window.addEvent("domready", function() {
		akeebasubs_relations_mode_onChange();
	});

	
	function akeebasubs_relations_mode_onChange()
	{
		(function($) {
			var mode = $('#mode').val();
			$('#akeebasubs-relations-fixed').css('display','none');
			$('#akeebasubs-relations-flexi').css('display','none');
			if(mode == 'fixed')
			{
				$('#akeebasubs-relations-fixed').css('display','block');
			}
			else if(mode == 'flexi')
			{
				$('#akeebasubs-relations-flexi').css('display','block');
			}
		})(akeeba.jQuery);
	}
</script>