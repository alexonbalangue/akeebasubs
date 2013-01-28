<?php
/**
 *  @package AkeebaSubs
 *  @copyright Copyright (c)2010-2013 Nicholas K. Dionysopoulos
 *  @license GNU General Public License version 3, or later
 */

defined('_JEXEC') or die();

JHtml::_('behavior.tooltip');

$this->loadHelper('select');
$this->loadHelper('format');
$this->loadHelper('cparams');

JHTML::_('behavior.modal');

$nullDate = JFactory::getDbo()->getNullDate();

$extensions = $this->getModel()->getExtensions();
?>

<?php if(empty($extensions)): ?>
<div class="alert alert-error">
	<p>
	<?php echo JText::_('COM_AKEEBASUBS_INVOICES_MSG_EXTENSIONS_NONE'); ?>
	</p>
</div>
<?php else: ?>
<div class="alert alert-info">
	<p>
	<?php echo JText::_('COM_AKEEBASUBS_INVOICES_MSG_EXTENSIONS_SOME'); ?>
	<ul>
	<?php foreach ($extensions as $key => $extension): ?>
		<li><?php echo $extension['title'] ?></li>
	<?php endforeach; ?>
	</ul>
	</p>
<?php if(count($extensions) > 1): ?>
	<p><strong>
		<?php echo JText::_('COM_AKEEBASUBS_INVOICES_MSG_EXTENSIONS_MULTIPLE'); ?>
	</strong></p>
<?php endif; ?>
</div>
<?php endif; ?>

<form action="index.php" method="post" name="adminForm" id="adminForm">
<input type="hidden" name="option" value="com_akeebasubs" />
<input type="hidden" name="view" value="invoices" />
<input type="hidden" id="task" name="task" value="browse" />
<input type="hidden" name="hidemainmenu" id="hidemainmenu" value="0" />
<input type="hidden" name="boxchecked" id="boxchecked" value="0" />
<input type="hidden" name="filter_order" id="filter_order" value="<?php echo $this->lists->order ?>" />
<input type="hidden" name="filter_order_Dir" id="filter_order_Dir" value="<?php echo $this->lists->order_Dir ?>" />
<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />

<table class="adminlist table table-striped">
	<thead>
		<tr>
			<th width="100">
				<?php echo JHTML::_('grid.sort', 'COM_AKEEBASUBS_INVOICES_FIELD_AKEEBASUBS_SUBSCRIPTION_ID', 'akeebasubs_subscription_id', $this->lists->order_Dir, $this->lists->order, 'browse') ?>
			</th>
			<th width="20"></th>
			<th width="15%">
				<?php echo JHTML::_('grid.sort', 'COM_AKEEBASUBS_SUBSCRIPTIONS_USER', 'user_id', $this->lists->order_Dir, $this->lists->order, 'browse') ?>
			</th>
			<th width="10%">
				<?php echo JHTML::_('grid.sort', 'COM_AKEEBASUBS_INVOICES_FIELD_EXTENSION', 'extension', $this->lists->order_Dir, $this->lists->order, 'browse') ?>
			</th>
			<th width="10%">
				<?php echo JHTML::_('grid.sort', 'COM_AKEEBASUBS_INVOICES_FIELD_INVOICE_NO', 'invoice_no', $this->lists->order_Dir, $this->lists->order, 'browse') ?>
			</th>
			<th width="10%">
				<?php echo JHTML::_('grid.sort', 'COM_AKEEBASUBS_INVOICES_FIELD_INVOICE_DATE', 'invoice_date', $this->lists->order_Dir, $this->lists->order, 'browse') ?>
			</th>
			<th>
				<?php echo JText::_('COM_AKEEBASUBS_INVOICES_LBL_ACTIONS') ?>
			</th>
		</tr>
		<tr>
			<td>
				<input type="text" name="akeebasubs_subscription_id" id="akeebasubs_subscription_id"
					value="<?php echo $this->escape($this->getModel()->getState('akeebasubs_subscription_id',''));?>"
					class="input-small" onchange="document.adminForm.submit();"
					placeholder="<?php echo JText::_('COM_AKEEBASUBS_INVOICES_FIELD_AKEEBASUBS_SUBSCRIPTION_ID') ?>"
					/>
				<nobr>
					<button class="btn btn-mini" onclick="this.form.submit();">
						<?php echo JText::_('JSEARCH_FILTER'); ?>
					</button>
					<button class="btn btn-mini" onclick="document.adminForm.akeebasubs_subscription_id.value='';this.form.submit();">
						<?php echo JText::_('JSEARCH_RESET'); ?>
					</button>
				</nobr>
			</td>
			<td>
				<input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this);" />
			</td>
			<td>
				<input type="text" name="user" id="user"
					value="<?php echo $this->escape($this->getModel()->getState('user',''));?>"
					class="input-small" onchange="document.adminForm.submit();"
					placeholder="<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTIONS_USER') ?>"
					/>
				<nobr>
					<button class="btn btn-mini" onclick="this.form.submit();">
						<?php echo JText::_('JSEARCH_FILTER'); ?>
					</button>
					<button class="btn btn-mini" onclick="document.adminForm.user.value='';this.form.submit();">
						<?php echo JText::_('JSEARCH_RESET'); ?>
					</button>
				</nobr>
			</td>
			<td>
				<?php echo AkeebasubsHelperSelect::invoiceextensions('extension', $this->getModel()->getState('extension', ''), array('class' => 'input-small', 'onchange' => 'this.form.submit();')) ?>
			</td>
			<td>
				<input type="text" name="invoice_number" id="invoice_number"
					value="<?php echo $this->escape($this->getModel()->getState('invoice_number',''));?>"
					class="input-small" onchange="document.adminForm.submit();"
					placeholder="<?php echo JText::_('COM_AKEEBASUBS_INVOICES_FIELD_INVOICE_NO') ?>"
					/>
				<nobr>
					<button class="btn btn-mini" onclick="this.form.submit();">
						<?php echo JText::_('JSEARCH_FILTER'); ?>
					</button>
					<button class="btn btn-mini" onclick="document.adminForm.invoice_number.value='';this.form.submit();">
						<?php echo JText::_('JSEARCH_RESET'); ?>
					</button>
				</nobr>
			</td>
			<td>
				<?php echo JHTML::_('calendar', $this->getModel()->getState('invoice_date',''), 'invoice_date', 'invoice_date', '%Y-%m-%d', array('onchange' => 'this.form.submit();', 'class'=>'input-small')); ?>
			</td>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="20">
				<?php if($this->pagination->total > 0) echo $this->pagination->getListFooter() ?>	
			</td>
		</tr>
	</tfoot>
	<tbody>
		<?php if(count($this->items)): ?>
		<?php $m = 1; $i = -1; ?>
		<?php foreach($this->items as $invoice):?>
		<?php
			$i++;
			$m = 1 - $m;
			$email = trim($invoice->email);
			$email = strtolower($email);
			$gravatarHash = md5($email);
			$users = FOFModel::getTmpInstance('Users','AkeebasubsModel')
				->user_id($invoice->user_id)
				->getList();
			if(empty($users)) {
				$user_id = 0;
			} else {
				foreach($users as $user) {
					$user_id = $user->akeebasubs_user_id;
					break;
				}
			}
		?>
		<tr class="row<?php echo $m?> <?php echo $rowClass?>">
			<td>
				<strong><?php echo sprintf('%05u', (int)$invoice->akeebasubs_subscription_id)?></strong>
			</td>
			<td>
				<?php echo JHTML::_('grid.id', $i, $invoice->akeebasubs_subscription_id); ?>
			</td>
			<td>
				<span class="editlinktip hasTip" title="<?php echo $this->escape($invoice->username) ?>::<?php echo JText::_('COM_AKEEBASUBS_SUBSCRIPTION_USER_EDIT_TOOLTIP')?>">
					<?php if(AkeebasubsHelperCparams::getParam('gravatar',true)):?>
						<?php if(JURI::getInstance()->getScheme() == 'http'): ?>
							<img src="http://www.gravatar.com/avatar/<?php echo md5(strtolower($invoice->email))?>.jpg?s=32&d=mm" align="left" class="gravatar"  />
						<?php else: ?>
							<img src="https://secure.gravatar.com/avatar/<?php echo md5(strtolower($invoice->email))?>.jpg?s=32&d=mm" align="left" class="gravatar"  />
						<?php endif; ?>
					<?php endif; ?>
					<a href="index.php?option=com_akeebasubs&view=user&id=<?php echo $user_id ?>" class="title">	
					<strong><?php echo $this->escape($invoice->username)?></strong>
					<span class="small">[<?php echo $invoice->user_id?>]</span>
					<br/>
					<?php echo $this->escape($invoice->name)?>
					<?php if(!empty($invoice->business_name)):?>
					<br/>
					<?php echo $this->escape($invoice->business_name)?>
					&bull;
					<?php echo $this->escape($invoice->vatnumber)?>
					<?php endif; ?>
					<br/>
					<?php echo $this->escape($invoice->email)?>
					</a>
				</span>
			</td>
			<td>
				<?php echo AkeebasubsHelperFormat::formatInvoicingExtension($invoice->extension) ?>
			</td>
			<td>
				<?php if (!empty($invoice->display_number)): ?>
				<?php echo $this->escape($invoice->display_number) ?>
				<?php else: ?>
				<?php echo $this->escape($invoice->invoice_no) ?>
				<?php endif; ?>
			</td>
			<td>
				<?php echo AkeebasubsHelperFormat::date($invoice->invoice_date); ?>
			</td>
			<td>
				<?php if ($invoice->extension == 'akeebasubs'): ?>
				<a href="index.php?option=com_akeebasubs&view=invoices&task=read&id=<?php echo $this->escape($invoice->akeebasubs_subscription_id) ?>&tmpl=component" class="btn btn-info modal" rel="{handler: 'iframe', size: {x: 800, y: 500}}" title="<?php echo JText::_('COM_AKEEBASUBS_INVOICES_ACTION_PREVIEW') ?>">
					<span class="icon icon-file icon-white"></span>
				</a>
				
				<a href="index.php?option=com_akeebasubs&view=invoices&task=download&id=<?php echo $this->escape($invoice->akeebasubs_subscription_id) ?>" class="btn btn-primary" title="<?php echo JText::_('COM_AKEEBASUBS_INVOICES_ACTION_DOWNLOAD') ?>">
					<span class="icon icon-download-alt icon-white"></span>
				</a>
				
				<a href="index.php?option=com_akeebasubs&view=invoices&task=send&id=<?php echo $this->escape($invoice->akeebasubs_subscription_id) ?>" class="btn btn-success" title="<?php echo JText::_('COM_AKEEBASUBS_INVOICES_ACTION_RESEND') ?>">
					<span class="icon icon-envelope icon-white"></span>
				</a>
				
				<?php if (empty($invoice->sent_on) || ($invoice->sent_on == $nullDate)): ?>
				<span class="label">
					<span class="icon icon-white icon-warning-sign"></span> Not sent
				</span>
				<?php else: ?>
				<span class="label label-success">
					<span class="icon icon-white icon-ok"></span> Sent
				</span>
				<?php endif; ?>
				
				<a href="index.php?option=com_akeebasubs&view=invoices&task=generate&id=<?php echo $this->escape($invoice->akeebasubs_subscription_id) ?>" class="btn btn-mini btn-warning" title="<?php echo JText::_('COM_AKEEBASUBS_INVOICES_ACTION_REGENERATE') ?>">
					<span class="icon icon-retweet icon-white"></span>
				</a>
				<?php else: ?>
				<?php if(array_key_exists($invoice->extension, $extensions)): ?>
				<a class="btn" href="<?php echo sprintf($extensions[$invoice->extension]['backendurl'], $invoice->invoice_no) ?>">
					<span class="icon icon-share-alt"></span>
					<?php echo JText::_('COM_AKEEBASUBS_INVOICES_LBL_OPENEXTERNAL') ?>
				</a>
				<?php else: ?>
				<span class="label">
					<?php echo JText::_('COM_AKEEBASUBS_INVOICES_LBL_NOACTIONS') ?>
				</span>
				<?php endif; ?>
				<?php endif; ?>
			</td>
		</tr>
		<?php endforeach; ?>
		<?php endif; ?>
	</tbody>
</table>
</form>