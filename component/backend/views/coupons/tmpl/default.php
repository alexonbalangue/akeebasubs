<? defined('KOOWA') or die('Restricted access'); ?>

<!--
<script src="media://lib_koowa/js/koowa.js" />
<style src="media://com_akeebasubs/css/backend.css" />
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
				<input type="checkbox" name="toggle" value="" onclick="checkAll(<?= count($coupons); ?>);" />
			</td>
			<td colspan="2">
				<?= @text('Filter:'); ?> <?= @template('admin::com.default.view.list.search_form'); ?>
			</td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
			<td></td>
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
				<?= @helper('date.format', array('date' => $coupon->publish_up)) ?>
			</td>
			<td>
				<?= @helper('date.format', array('date' => $coupon->publish_down)) ?>
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