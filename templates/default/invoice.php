<?php
/**
 * @var Bixie\Invoicemaker\Settings\Template  $template
 * @var Bixie\Invoicemaker\Model\Invoice      $invoice
 */
use Pagekit\Application as App;
use Bixie\Framework\Helpers\DateHelper;

?>
<html>
<head>
	<style><?= file_get_contents(App::locator()->get('bixie/invoicemaker:templates/default/style.css')) ?></style>
</head>
<body>
<?php if ($template->get('pdf_background')) : ?>
	<div id="background"><img src="<?=App::locator()->get($template->get('pdf_background'))?>" height="100%" width="100%"></div>
<?php endif; ?>

<div id="creditor"><?= $template->markdown('creditor_address') ?></div>

<h1><?= $template->title ?></h1>

<strong><?= __('To:') ?></strong><br/>
<?php if ($invoice->getDebtor()->company) : ?><div><?= $invoice->getDebtor()->company ?></div><?php endif; ?>
<?php if ($invoice->getDebtor()->name) : ?><div><?= $invoice->getDebtor()->name ?></div><?php endif; ?>
<?php if ($invoice->getDebtor()->address_1) : ?><div><?= $invoice->getDebtor()->address_1 ?></div><?php endif; ?>
<?php if ($invoice->getDebtor()->address_2) : ?><div><?= $invoice->getDebtor()->address_2 ?></div><?php endif; ?>
<?php if ($invoice->getDebtor()->zip_code || $invoice->getDebtor()->city) : ?>
	<div>
	<?php if ($invoice->getDebtor()->zip_code) : ?><?= $invoice->getDebtor()->zip_code . ' ' ?><?php endif; ?>
	<?php if ($invoice->getDebtor()->city) : ?><?= $invoice->getDebtor()->city ?><?php endif; ?>
	</div>
<?php endif; ?>
<?php if ($invoice->getDebtor()->country) : ?><div><?= $invoice->getDebtor()->country ?></div><?php endif; ?>
<?php if ($invoice->getDebtor()->debtor_id) : ?><div><strong><?= __('ID') ?></strong>: <?= $invoice->getDebtor()->debtor_id ?></div><?php endif; ?>
<?php if ($invoice->getDebtor()->debtor_vat) : ?><div><strong><?= __('VAT') ?></strong>: <?= $invoice->getDebtor()->debtor_vat ?></div><?php endif; ?>
<?php if ($invoice->getDebtor()->debtor_coc) : ?><div><strong><?= __('COC') ?></strong>: <?= $invoice->getDebtor()->debtor_coc ?></div><?php endif; ?>

<hr/>

<div><strong><?= __('Invoice date') ?></strong>: <?= DateHelper::format($invoice->created, 'mediumDate') ?></div>
<div><strong><?= __('Invoice number') ?></strong>: <?= $invoice->invoice_number ?></div>

<hr/>

<table>
	<thead>
	<tr>
		<th align="left"><?= __('Description') ?></th>
		<th align="right"><?= __('Vat') ?></th>
		<th align="right"><?= __('Units') ?></th>
		<th align="right"><?= __('Per unit') ?></th>
		<th align="right"><?= __('Base') ?></th>
		<th align="right"><?= __('Amount') ?></th>
	</tr>
	</thead>
	<tbody>
<?php foreach ($invoice->getInvoiceLines()->all() as $invoiceLine) : ?>

	<tr class="<?= $invoiceLine->type ?>">
		<td><?= $invoiceLine->description ?></td>
		<td align="right"><?php if ($invoiceLine->vat_perc != '') : ?><?= $invoiceLine->vat_perc ?>%<?php endif; ?></td>
		<td align="right"><?= $invoiceLine->units ?></td>
		<td align="right"><?= $invoiceLine->per_unit ?></td>
		<td align="right"><?= $invoiceLine->base ?></td>
		<td align="right"><?= $invoiceLine->amount ?></td>
	</tr>

<?php endforeach; ?>

</tbody>

</table>

<div id="subline"><?= $template->markdown('subline') ?></div>

</body>
</html>
