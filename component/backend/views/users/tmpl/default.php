<? defined('KOOWA') or die('Restricted access'); ?>

<!--
<script src="media://lib_koowa/js/koowa.js?<?=AKEEBASUBS_VERSIONHASH?>" />
<style src="media://lib_koowa/css/koowa.css?<?=AKEEBASUBS_VERSIONHASH?>" />
<style src="media://com_akeebasubs/css/backend.css?<?=AKEEBASUBS_VERSIONHASH?>" />
-->

<?= @helper('behavior.tooltip'); ?>

<form action="<?= @route() ?>" method="get" class="adminform" name="adminForm">
<table class="adminlist">
	<thead>
		<tr>
			<th width="30px">
				<?= @helper('grid.sort', array('column' => 'user_id', 'title' => 'COM_AKEEBASUBS_USERS_FIELD_USERID')); ?>
			</th>
			<th width="16px"></th>
			<th>
				<?= @helper('grid.sort', array('column' => 'username', 'title' => 'COM_AKEEBASUBS_USERS_FIELD_USERNAME')); ?>
			</th>
			<th>
				<?= @helper('grid.sort', array('column' => 'name', 'title' => 'COM_AKEEBASUBS_USERS_FIELD_NAME')); ?>
			</th>
			<th>
				<?= @helper('grid.sort', array('column' => 'email', 'title' => 'COM_AKEEBASUBS_USERS_FIELD_EMAIL')); ?>
			</th>
			<th>
				<?= @helper('grid.sort', array('column' => 'businessname', 'title' => 'COM_AKEEBASUBS_USERS_FIELD_BUSINESSNAME')); ?>
			</th>
			<th>
				<?= @helper('grid.sort', array('column' => 'vatnumber', 'title' => 'COM_AKEEBASUBS_USERS_FIELD_VATNUMBER')); ?>
			</th>
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
		<? if(count($users)): ?>
		<? $i = 0; $m = 0; ?>
		<? foreach($users as $user): ?>
		<tr class="<?= 'row'.$m; ?>">
			<td align="center">
				<?=$user->id?>
			</td>
			<td align="center">
				<?= @helper('grid.checkbox', array('row' => $user))?>
			</td>
			<td align="left">
				<a href="<?= @route('view=user&id='.$user->id); ?>">
					<strong><?= @escape($user->username) ?></strong>
				</a>
			</td>
			<td>
				<a href="<?= @route('view=user&id='.$user->id); ?>">
					<?= @escape($user->name) ?>
				</a>
			</td>
			<td>
				<a href="<?= @route('view=user&id='.$user->id); ?>">
					<?= @escape($user->email) ?>
				</a>
			</td>
			<td>
				<?if(!empty($user->businessname)):?>
				<a href="<?= @route('view=user&id='.$user->id); ?>">
					<?= @escape($user->businessname) ?>
				</a>
				<?else:?>
				&mdash;
				<?endif;?>
			</td>
			<td>
				<?if(!empty($user->vatnumber)):?>
				<a href="<?= @route('view=user&id='.$user->id); ?>">
					<?= ($user->country == 'GR') ? 'EL' : $user->country ?>
					<?= @escape($user->vatnumber) ?>
				</a>
				<?else:?>
				&mdash;
				<?endif?>
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