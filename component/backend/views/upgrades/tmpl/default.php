<? defined('KOOWA') or die('Restricted access'); ?>

<!--
<script src="media://lib_koowa/js/koowa.js?<?=AKEEBASUBS_VERSIONHASH?>" />
<style src="media://lib_koowa/css/koowa.css?<?=AKEEBASUBS_VERSIONHASH?>" />
<style src="media://com_akeebasubs/css/backend.css?<?=AKEEBASUBS_VERSIONHASH?>" />
<script src="media://com_akeebasubs/js/akeebajq.js?<?=AKEEBASUBS_VERSIONHASH?>" />
<script src="media://com_akeebasubs/js/backend.js?<?=AKEEBASUBS_VERSIONHASH?>" />
<?if(version_compare(JVERSION, '1.6.0')):?>
<script src="media://com_akeebasubs/js/j16compat.js?<?=AKEEBASUBS_VERSIONHASH?>" />
<?endif;?>
-->

<?= @helper('behavior.tooltip'); ?>

<form action="<?= @route() ?>" method="get" class="-koowa-grid">
<table class="adminlist">
	<thead>
		<tr>
			<th width="10px"><?= @text('Num'); ?></th>
			<th width="16px"></th>
			<th>
				<?= @helper('grid.sort', array('column' => 'title', 'title' => 'COM_AKEEBASUBS_UPGRADES_FIELD_TITLE')); ?>
			</th>
			<th>
				<?= @helper('grid.sort', array('column' => 'from_id', 'title' => 'COM_AKEEBASUBS_UPGRADES_FIELD_FROM_ID')); ?>
			</th>
			<th>
				<?= @helper('grid.sort', array('column' => 'to_id', 'title' => 'COM_AKEEBASUBS_UPGRADES_FIELD_TO_ID')); ?>
			</th>
			<th>
				<?= @helper('grid.sort', array('column' => 'min_presence', 'title' => 'COM_AKEEBASUBS_UPGRADES_FIELD_MIN_PRESENCE')); ?>
			</th>
			<th>
				<?= @helper('grid.sort', array('column' => 'max_presence', 'title' => 'COM_AKEEBASUBS_UPGRADES_FIELD_MAX_PRESENCE')); ?>
			</th>
			<th>
				<?= @helper('grid.sort', array('column' => 'type', 'title' => 'COM_AKEEBASUBS_UPGRADES_FIELD_TYPE')); ?>
			</th>
			<th>
				<?= @helper('grid.sort', array('column' => 'value', 'title' => 'COM_AKEEBASUBS_UPGRADES_FIELD_VALUE')); ?>
			</th>
			<th width="8%">
				<?= @helper('grid.sort', array('column' => 'enabled')); ?>
			</th>			
			<th width="8%">
				<?= @helper('grid.sort', array('column' => 'ordering')); ?>
			</th>
		</tr>
		<tr>
			<td></td>
			<td>
				<?=@helper('grid.checkall');?>
			</td>
			<td>
				<?= @text('Filter:'); ?> <?= @helper('grid.search'); ?>
			</td>
			<td></td>
			<td></td>
			<td></td>
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
		<? if(count($upgrades)): ?>
		<? $i = 0; $m = 0; ?>
		<? foreach($upgrades as $upgrade): ?>
		<tr class="<?= 'row'.$m; ?>">
			<td align="center">
				<?= ++$i; ?>
			</td>
			<td align="center">
				<?= @helper('grid.checkbox', array('row' => $upgrade))?>
			</td>
			<td align="left">
				<a href="<?= @route('view=upgrade&id='.$upgrade->id); ?>">
					<strong><?= @escape($upgrade->title) ?></strong>
				</a>
			</td>
			<td>
				<?=@helper('admin::com.akeebasubs.template.helper.listbox.formatLevel',array('id'=>$upgrade->from_id))?>
			</td>
			<td>
				<?=@helper('admin::com.akeebasubs.template.helper.listbox.formatLevel',array('id'=>$upgrade->to_id))?>
			</td>
			<td>
				<?=(int)$upgrade->min_presence?>
			</td>
			<td>
				<?=(int)$upgrade->max_presence?>
			</td>
			<td align="center" colspan="2">
				<? if($upgrade->type == 'value'): ?>
				<span class="akeebasubs-coupon-discount-value">
				<?= sprintf('%2.2f', (float)$upgrade->value) ?>
				<?=KFactory::get('admin::com.akeebasubs.model.configs')->getConfig()->currencysymbol?>
				</span>
				<? else: ?>
				<span class="akeebasubs-coupon-discount-percent">
				<?= sprintf('%2.2f', (float)$upgrade->value) ?> %
				</span>
				<? endif; ?>
			</td>
			<td align="center">
				<?= @helper('grid.enable', array('row' => $upgrade)) ?>
			</td>			
			<td align="center">
				<?= @helper('grid.order', array('row' => $upgrade)); ?>
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