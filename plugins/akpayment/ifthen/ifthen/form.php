<?php defined('_JEXEC') or die(); ?>
<?php
$t1 = JText::_('PLG_AKPAYMENT_IFTHEN_ENTIDADE_LABEL');
$t2 = JText::_('PLG_AKPAYMENT_IFTHEN_REFERENCIA_LABEL');
$t3 = JText::_('PLG_AKPAYMENT_IFTHEN_VALOR_LABEL');
?>
<table id="ifthen-form">
	<thead>
		<td><img src="https://www.ifthensoftware.com/Images/logoMB.jpg" border="0" height="58"/></td>
		<td align="center"><h3>Pagamento por Multibanco ou Homebanking</h3></td>
	</thead>
	<tr>
		<td colspan="2" align="center">
			<table>				
				<tr>
					<td><?php echo JText::_('PLG_AKPAYMENT_IFTHEN_ENTIDADE_LABEL') ?>:</td>
					<td><b><?php echo $data->entidade ?></b></td>
				</tr>
				<tr>
					<td><?php echo JText::_('PLG_AKPAYMENT_IFTHEN_REFERENCIA_LABEL') ?>:</td>
					<td><b><?php echo $data->referencia ?></b></td>
				</tr>
				<tr>
					<td><?php echo JText::_('PLG_AKPAYMENT_IFTHEN_VALOR_LABEL') ?>:</td>
					<td><b><?php echo str_replace('.', ',', $data->valor) . ' ' . $data->currency ?></b></td>
				</tr>
			</table>
		</td>
	</tr>
	<tfoot><td colspan="2" align="center"><h5>O talão emitido pela caixa automática faz prova de pagamento. Conserve-o.</h5></td></tfoot>
</table>