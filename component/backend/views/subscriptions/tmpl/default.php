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
			<th width="60px">
				<?= @helper('grid.sort', array('column' => 'akeebasubs_subscription_id', 'title' => 'COM_AKEEBASUBS_COMMON_ID')); ?>
			</th>
			<th>
				<?= @helper('grid.sort', array('column' => 'akeebasubs_level_id', 'title' => 'COM_AKEEBASUBS_SUBSCRIPTIONS_LEVEL')); ?>
			</th>
			<th>
				<?= @helper('grid.sort', array('column' => 'user_id', 'title' => 'COM_AKEEBASUBS_SUBSCRIPTIONS_USER')); ?>
			</th>
			<th width="30px">
				<?= @helper('grid.sort', array('column' => 'state', 'title' => 'COM_AKEEBASUBS_SUBSCRIPTIONS_STATE')) ?>
			</th>
			<th width="60px">
				<?= @helper('grid.sort', array('column' => 'gross_amount', 'title' => 'COM_AKEEBASUBS_SUBSCRIPTIONS_AMOUNT')) ?>
			</th>
			<th width="120px">
				<?= @helper('grid.sort', array('column' => 'publish_up', 'title' => 'COM_AKEEBASUBS_SUBSCRIPTIONS_PUBLISH_UP')) ?>
			</th>
			<th width="120px">
				<?= @helper('grid.sort', array('column' => 'publish_down', 'title' => 'COM_AKEEBASUBS_SUBSCRIPTIONS_PUBLISH_DOWN')) ?>
			</th>
			<th width="100px">
				<?= @helper('grid.sort', array('column' => 'enabled')); ?>
			</th>			
		</tr>
		<tr>
			<td></td>
			<td>
				<input type="checkbox" name="toggle" value="" onclick="checkAll(<?= count($subscriptions); ?>);" />
			</td>
			<td></td>
			<td><?=@helper('admin::com.akeebasubs.template.helper.listbox.levels', array('name' => 'level', 'attribs' => array('onchange' => 'this.form.submit();')) ) ?></td>
			<td>
				<?= @text('Filter:'); ?> <?= @template('admin::com.default.view.list.search_form'); ?>
			</td>
			<td></td>
			<td></td>
			<td><?php echo JHTML::_('calendar', @$state->publish_up, 'publish_up', 'publish_up', '%Y-%m-%d', array('onchange' => 'this.form.submit();')); ?></td>
			<td><?php echo JHTML::_('calendar', @$state->publish_down, 'publish_down', 'publish_down', '%Y-%m-%d', array('onchange' => 'this.form.submit();')); ?></td>
			<td><?= @helper('listbox.enabled', array('attribs'=>array('onchange'=>'this.form.submit();'))) ?></td>
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
		<?php if(count($subscriptions)): ?>
		<?php $m = 1; $i = 0; ?>
		<?php foreach($subscriptions as $subscription):?>
		<?php
			$m = 1 - $m;
			$email = trim($subscription->email);
			$email = strtolower($email);
			$gravatarHash = md5($email);
			$rowClass = ($subscription->enabled) ? '' : 'expired'
		?>
		<tr class="row<?=$m?> <?=$rowClass?>">
			<td align="center">
				<?= ++$i; ?>
			</td>
			<td align="center">
				<?= @helper('grid.checkbox', array('row' => $subscription))?>
			</td>
			<td align="left">
				<span class="editlinktip hasTip" title="#<?=(int)$subscription->id?>::<?= @text('COM_AKEEBASUBS_SUBSCRIPTION_EDIT_TOOLTIP')?>">
					<a href="<?= @route('view=subscription&id='.$subscription->id); ?>" class="title">
						<?=sprintf('%05u', (int)$subscription->id)?>
	    			</a>
    			</span>
			</td>
			<td>
				<span class="editlinktip hasTip" title="<?= @escape($subscription->title); ?>::<?= @text('COM_AKEEBASUBS_SUBSCRIPTION_LEVEL_EDIT_TOOLTIP')?>">
					<img src="<?= JURI::base(); ?>../images/stories/<?= $subscription->image;?>" width="32" height="32" class="sublevelpic" />
					<a href="<?= @route('view=level&id='.$subscription->akeebasubs_level_id); ?>" class="subslevel">
    					<?=@escape($subscription->title)?>
    				</a>
    			</span>
			</td>
			<td>
				<span class="editlinktip hasTip" title="<?= @escape($subscription->username) ?>::<?= @text('COM_AKEEBASUBS_SUBSCRIPTION_USER_EDIT_TOOLTIP')?>">
					<img src="http://www.gravatar.com/avatar/<?=$gravatarHash?>.jpg?s=32&d=mm" align="left" class="gravatar"  />
					<a href="<?= @route('view=user&id='.$subscription->user_id); ?>" class="title">	
						<strong><?=@escape($subscription->username)?></strong>
						<span class="small">[<?=$subscription->user_id?>]</span>
						<br/>
						<?=@escape($subscription->name)?>
						<? if(!empty($subscription->business_name)):?>
						<br/>
						<?=@escape($subscription->business_name)?>
						&bull;
						<?=@escape($subscription->vatnumber)?>
						<?php endif; ?>
						<br/>
						<?=@escape($subscription->email)?>
					</a>
				</span>
			</td>
			<td class="akeebasubs-subscription-paymentstatus">
				<span class="akeebasubs-payment akeebasubs-payment-<?= strtolower($subscription->state) ?> hasTip"
				title="<?=@text('COM_AKEEBASUBS_SUBSCRIPTION_STATE_'.$subscription->state)?>::<?=$subscription->processor?> &bull; <?=$subscription->processor_key?>"></span>
			</td>
			<td class="akeebasubs-subscription-amount">
				<?php if($subscription->net_amount > 0): ?>
				<span class="akeebasubs-subscription-netamount">
				<?= sprintf('%2.2f', (float)$subscription->net_amount) ?> <?=KFactory::get('admin::com.akeebasubs.model.configs')->getConfig()->currencysymbol?>
				</span>
				<span class="akeebasubs-subscription-taxamount">
				<?= sprintf('%2.2f', (float)$subscription->tax_amount) ?> <?=KFactory::get('admin::com.akeebasubs.model.configs')->getConfig()->currencysymbol?>
				</span>
				<?php endif; ?>
				<span class="akeebasubs-subscription-grossamount">
				<?= sprintf('%2.2f', (float)$subscription->gross_amount) ?> <?=KFactory::get('admin::com.akeebasubs.model.configs')->getConfig()->currencysymbol?>
				</span>
			</td>
			<td>
				
				<?= @helper('date.format', array('date' => $subscription->publish_up, 'format' => '%Y-%m-%d %H:%M' )) ?>
			</td>
			<td>
				<?= @helper('date.format', array('date' => $subscription->publish_down, 'format' => '%Y-%m-%d %H:%M' )) ?>
			</td>
			<td align="center">
				<?= @helper('grid.enable', array('row' => $subscription)) ?>
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