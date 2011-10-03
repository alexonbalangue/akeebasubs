<? /** $Id$ */ ?>
<? defined('KOOWA') or die('Restricted access');?>

<?= @helper('behavior.tooltip');?>

<!--
<script src="media://lib_koowa/js/koowa.js?<?=AKEEBASUBS_VERSIONHASH?>" />
<style src="media://com_akeebasubs/css/backend.css?<?=AKEEBASUBS_VERSIONHASH?>" />
-->

<form action="<?= @route() ?>&tmpl=component" method="get" class="-koowa-grid">
<table class="adminlist"  style="clear: both;">
	<thead>
		<tr>
			<th width="5"><?= @text('COM_UNITE_COMMONUI_NUM'); ?></th>
			<th width="5">ID</th>
			<th>
				<?= @helper('grid.sort', array('column' => 'username', 'title' => 'COM_UNITE_JUSER_USERNAME')); ?>
			</th>
			<th>
				<?= @helper('grid.sort', array('column' => 'name', 'title' => 'COM_UNITE_JUSER_NAME')); ?>
			</th>
			<th>
				<?= @helper('grid.sort', array('column' => 'email', 'title' => 'COM_UNITE_JUSER_EMAIL')); ?>
			</th>
		</tr>
		<tr>
			<td></td>
			<td></td>
			<td colspan="3">
				<?= @text('Filter:'); ?> <?= @helper('grid.search'); ?>
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
	<?php if(count($jusers)): ?>
	<?php $m = 1; $i = 0; ?>
	<?php foreach($jusers as $juser) :?>
	<?php 
		$m = 1 - $m;
		$id = (int)$juser->id;
		$username = @escape($juser->username);
		$link = "window.parent.jSelectUser('$id','$username');";
	?>
	<tr class="row<?=$m?>">
		<td><?=++$i?></td>
		<td><?=$juser->id?></td>
		<td><a href="javascript:<?=$link?>"><?=@escape($juser->username)?></a></td>
		<td><a href="javascript:<?=$link?>"><?=@escape($juser->name)?></a></td>
		<td><a href="javascript:<?=$link?>"><?=@escape($juser->email)?></a></td>
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