<? defined('KOOWA') or die('Restricted access'); ?>

<script src="media://lib_koowa/js/koowa.js?<?=AKEEBASUBS_VERSIONHASH?>" />
<style src="media://com_akeebasubs/css/backend.css?<?=AKEEBASUBS_VERSIONHASH?>" />
<?= @helper('behavior.tooltip'); ?>

<form action="<?= @route() ?>" method="get" class="adminform" name="adminForm">

<table class="adminlist">
	<thead>
		<tr>
			<th><?= @text('Num'); ?></th>
			<th></th>
			<th>
				<?= @helper('grid.sort', array('column' => 'title', 'title' => 'COM_AKEEBASUBS_LEVELS_FIELD_TITLE')); ?>
			</th>
			<th width="8%">
				<?= @helper('grid.sort', array('column' => 'duration', 'title' => 'COM_AKEEBASUBS_LEVELS_FIELD_DURATION')); ?>
			</th>
			<th width="10%">
				<?= @helper('grid.sort', array('column' => 'price', 'title' => 'COM_AKEEBASUBS_LEVELS_FIELD_PRICE')); ?>
			</th>
			<th width="8%">
				<?= @helper('grid.sort', array('column' => 'ordering')); ?>
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
			<td>
				<?= @text('Filter:'); ?> <?= @template('admin::com.default.view.list.search_form'); ?>
			</td>
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
		<? $i = 0; $m = 0; ?>
		<? foreach ($levels as $level) : ?>
		<tr class="<?= 'row'.$m; ?>">
			<td align="center">
				<?= ++$i; ?>
			</td>
			<td align="center">
				<?= @helper('grid.checkbox', array('row' => $level))?>
			</td>
			<td align="left">
				<span class="editlinktip hasTip" title="<?= @text('edit level tooltip')?> <?= @escape($level->title); ?>::<?= @escape(substr(strip_tags($level->description), 0, 300)).'...'; ?>">
					<img src="<?= JURI::base(); ?>../images/stories/<?= $level->image;?>" width="32" height="32" class="sublevelpic" />
					<a href="<?= @route('view=level&id='.$level->id); ?>" class="subslevel">
    					<strong><?= @escape($level->title) ?></strong>
    				</a>
    			</span>
			</td>
			<td>
				<?= @escape($level->duration) ?>
			</td>
			<td align="right">
				<?= sprintf('%02.02f', (float)$level->price) ?>
				<?=KFactory::get('admin::com.akeebasubs.model.configs')->getConfig()->currencysymbol?>
			</td>
			<td align="center">
				<?= @helper('grid.order', array('row' => $level)); ?>
			</td>
			<td align="center">
				<?= @helper('grid.enable', array('row' => $level)) ?>
			</td>			
		</tr>
		<? endforeach; ?>	
	</tbody>
</table>

</form>