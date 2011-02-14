<? defined('KOOWA') or die('Restricted access'); ?>
<?php JHTML::_('behavior.calendar'); ?>

<script src="media://lib_koowa/js/koowa.js" />
<style src="media://lib_koowa/css/koowa.css" />
<style src="media://com_akeebasubs/css/backend.css" />

<?= @helper('behavior.tooltip'); ?>

<form action="<?= @route() ?>" method="get" class="adminform" name="adminForm">
<table class="adminlist">
	<thead>
		<tr>
			<th width="10px"><?= @text('Num'); ?></th>
			<th width="16px"></th>
			<th>
				<?= @helper('grid.sort', array('column' => 'country', 'title' => 'COM_AKEEBASUBS_TAXRULES_COUNTRY')); ?>
			</th>
			<th>
				<?= @helper('grid.sort', array('column' => 'state', 'title' => 'COM_AKEEBASUBS_TAXRULES_STATE')); ?>
			</th>
			<th>
				<?= @helper('grid.sort', array('column' => 'city', 'title' => 'COM_AKEEBASUBS_TAXRULES_CITY')); ?>
			</th>
			<th width="30px">
				<?= @helper('grid.sort', array('column' => 'vies', 'title' => 'COM_AKEEBASUBS_TAXRULES_VIES')) ?>
			</th>
			<th width="60px">
				<?= @helper('grid.sort', array('column' => 'taxrate', 'title' => 'COM_AKEEBASUBS_TAXRULES_TAXRATE')) ?>
			</th>
			<th width="50px">
				<?= @helper('grid.sort', array('column' => 'ordering')); ?>
			</th>
			<th width="100px">
				<?= @helper('grid.sort', array('column' => 'enabled')); ?>
			</th>			
		</tr>
		<tr>
			<td></td>
			<td>
				<input type="checkbox" name="toggle" value="" onclick="checkAll(<?= count($taxrules); ?>);" />
			</td>
			<td>
				<?=@helper('admin::com.akeebasubs.template.helper.listbox.countries', array('name' => 'country', 'selected' => @$state->country, 'attribs' => array('onchange' => 'this.form.submit();', 'style' => 'width: 120px')) ) ?>
			</td>
			<td>
				<?=@helper('admin::com.akeebasubs.template.helper.listbox.states', array('name' => 'state', 'selected' => @$state->state, 'attribs' => array('onchange' => 'this.form.submit();', 'style' => 'width: 120px')) ) ?>
			</td>
			<td>
				<?= @text('Filter:'); ?> <?= @template('admin::com.default.view.list.search_form'); ?>
			</td>
			<td>
				<?= @helper('listbox.enabled', array('name'=>'vies', 'selected' => @$state->vies, 'attribs'=>array('onchange'=>'this.form.submit();'))) ?>
			</td>
			<td></td>
			<td></td>
			<td>
				<?= @helper('listbox.enabled', array('attribs'=>array('onchange'=>'this.form.submit();'))) ?>
			</td>
		</tr>
		
	</thead>
	<tfoot>
		<tr>
			<td colspan="20">
				<?= @helper('paginator.pagination', array('total' => $total)) ?>
			</td>
		</tr>
	</tfoot>
	<tbody>
		<?php if(count($taxrules)): ?>
		<?php $m = 1; $i = 0; ?>
		<?php foreach($taxrules as $taxrule):?>
		<?php
			$m = 1 - $m;
		?>
		<tr class="row<?=$m?>">
			<td align="center">
				<?= ++$i; ?>
			</td>
			<td align="center">
				<?= @helper('grid.checkbox', array('row' => $taxrule))?>
			</td>
			<td>
				<a href="<?= @route('view=taxrule&id='.$taxrule->id); ?>">
					<?=@helper('admin::com.akeebasubs.template.helper.listbox.formatCountry', array('country' => $taxrule->country) ) ?>
					<?=$taxrule->country ? ' ('.@escape($taxrule->country).')' : ''?>
				</a>
			</td>
			<td>
				<a href="<?= @route('view=taxrule&id='.$taxrule->id); ?>">
					<?=@helper('admin::com.akeebasubs.template.helper.listbox.formatState', array('state' => $taxrule->state) ) ?>
					<?=$taxrule->state ? ' ('.@escape($taxrule->state).')' : ''?>
				</a>
			</td>
			<td>
				<a href="<?= @route('view=taxrule&id='.$taxrule->id); ?>">
					<?=$taxrule->city ? @escape($taxrule->city) : '&mdash;'?>
				</a>
			</td>
			<td>
				<?=$taxrule->vies ? @text('yes') : @text('no')?>
			</td>
			<td>
				<a href="<?= @route('view=taxrule&id='.$taxrule->id); ?>">
					<?=sprintf('%02.2f', (int)$taxrule->taxrate)?> %
				</a>
			</td>
			<td align="center">
				<?= @helper('grid.order', array('row' => $taxrule)); ?>
			</td>
			<td align="center">
				<?= @helper('grid.enable', array('row' => $taxrule)) ?>
			</td>
		</tr>			
		<?php endforeach; ?>
		<?php else: ?>
		<tr>
			<td colspan="20">
				<?= @text('COM_AKEEBASUBS_COMMON_NORECORDS') ?>
			</td>
		</tr>
		<?php endif; ?>
	</tbody>
</table>
</form>