<? defined('KOOWA') or die('Restricted access'); ?>

<!--
<script src="media://lib_koowa/js/koowa.js?<?=AKEEBASUBS_VERSIONHASH?>" />
<style src="media://com_akeebasubs/css/backend.css?<?=AKEEBASUBS_VERSIONHASH?>" />
<script src="media://com_akeebasubs/js/jquery.js?<?=AKEEBASUBS_VERSIONHASH?>" />
<script src="media://com_akeebasubs/js/backend.js?<?=AKEEBASUBS_VERSIONHASH?>" />
<?if(version_compare(JVERSION, '1.6.0')):?>
<script src="media://com_akeebasubs/js/j16compat.js?<?=AKEEBASUBS_VERSIONHASH?>" />
<?endif;?>
-->
<?= @helper('behavior.tooltip'); ?>

<form action="<?= @route() ?>" method="get" class="adminform" name="adminForm">

<table class="adminlist">
	<thead>
		<tr>
			<th><?= @text('Num'); ?></th>
			<th></th>
			<th>
				<?= @helper('grid.sort', array('column' => 'title', 'title' => 'COM_AKEEBASUBS_COUPONS_FTITLE')); ?>
			</th>
			<th width="8%">
				<?= @helper('grid.sort', array('column' => 'coupon', 'title' => 'COM_AKEEBASUBS_COUPONS_COUPON')); ?>
			</th>
			<th width="8%">
				<?= @helper('grid.sort', array('column' => 'value', 'title' => 'COM_AKEEBASUBS_COUPONS_VALUE')); ?>
			</th>
			<th>
				<?= @text('COM_AKEEBASUBS_COUPONS_LIMITS') ?>
			</th>
			<th width="8%">
				<?= @helper('grid.sort', array('column' => 'ordering')); ?>
			</th>
			<th>
				<?= @helper('grid.sort', array('column' => 'publish_up', 'title' => 'COM_AKEEBASUBS_COUPONS_PUBLISH_UP')); ?>
			</th>
			<th>
				<?= @helper('grid.sort', array('column' => 'publish_down', 'title' => 'COM_AKEEBASUBS_COUPONS_PUBLISH_DOWN')); ?>
			</th>
			<th width="8%">
				<?= @helper('grid.sort', array('column' => 'enabled')); ?>
			</th>			
		</tr>
		<tr>
			<td></td>
			<td>
				<input type="checkbox" name="toggle" value="" onclick="akeebasubs_checkall();" />
			</td>
			<td colspan="2">
				<?= @text('Filter:'); ?> <?= @template('admin::com.default.view.list.search_form'); ?>
			</td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td>
				<td><?= @helper('listbox.enabled', array('attribs'=>array('onchange'=>'this.form.submit();'))) ?></td>
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
		<? if(count($coupons)): ?>
		<? $i = 0; $m = 0; ?>
		<? foreach ($coupons as $coupon) : ?>
		<tr class="<?= 'row'.$m; ?>">
			<td align="center">
				<?= ++$i; ?>
			</td>
			<td align="center">
				<?= @helper('grid.checkbox', array('row' => $coupon))?>
			</td>
			<td align="left">
				<a href="<?= @route('view=coupon&id='.$coupon->id); ?>">
					<strong><?= @escape($coupon->title) ?></strong>
				</a>
			</td>
			<td>
				<?= @escape($coupon->coupon) ?>
			</td>
			<td align="right">
				<? if($coupon->type == 'value'): ?>
				<span class="akeebasubs-coupon-discount-value">
				<?= sprintf('%2.2f', (float)$coupon->value) ?>
				<?=KFactory::get('admin::com.akeebasubs.model.configs')->getConfig()->currencysymbol?>
				</span>
				<? else: ?>
				<span class="akeebasubs-coupon-discount-percent">
				<?= sprintf('%2.2f', (float)$coupon->value) ?> %
				</span>
				<? endif; ?>
			</td>
			<td>
				<?php
					$limits = array();
					if($coupon->users) $limits[] = JText::_('COM_AKEEBASUBS_COUPONS_LIMITS_USERS');
					if($coupon->subscriptions) $limits[] = JText::_('COM_AKEEBASUBS_COUPONS_LIMITS_LEVELS');
					if($coupon->hitslimit) $limits[] = JText::_('COM_AKEEBASUBS_COUPONS_LIMITS_HITS');
					
					$strLimits = implode(', ', $limits);
				?>
				<?= $strLimits ?>
			</td>
			<td align="center">
				<?= @helper('grid.order', array('row' => $coupon)); ?>
			</td>
			<td>
				<?= @helper('date.format', array('date' => $coupon->publish_up, 'format' => '%Y-%m-%d %H:%M')) ?>
			</td>
			<td>
				<?= @helper('date.format', array('date' => $coupon->publish_down, 'format' => '%Y-%m-%d %H:%M')) ?>
			</td>
			<td align="center">
				<?= @helper('grid.enable', array('row' => $coupon)) ?>
			</td>			
		</tr>
		<? endforeach; ?>	
		<? else: ?>
		<tr>
			<td colspan="20">
				<?= @text('COM_AKEEBASUBS_COMMON_NORECORDS') ?>
			</td>
		</tr>
		<? endif; ?>
	</tbody>
</table>

</form>