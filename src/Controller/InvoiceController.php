<?php

namespace Bixie\Invoicemaker\Controller;

use Bixie\Invoicemaker\InvoicemakerModule;
use Pagekit\Application as App;
use Bixie\Invoicemaker\Model\Invoice;

/**
 * @Access("invoicemaker: manage invoices", admin=true)
 * @Route("invoice", name="invoice")
 */
class InvoiceController {

	/**
	 * @Route("/edit", name="edit")
	 * @Request({"id": "int"})
	 */
	public function editAction ($id = 0) {

		/** @var InvoicemakerModule $invoicemaker */
		$invoicemaker = App::module('bixie/invoicemaker');

		if (!$invoice = Invoice::find($id)) {

			if ($id == 0) {
				$invoice = Invoice::create();
			}

		}

		if (!$invoice) {
			App::abort(404, __('Invoice not found.'));
		}

		return [
			'$view' => [
				'title' => __('Invoice'),
				'name' => 'bixie/invoicemaker/admin/invoice.php'
			],
			'$data' => [
				'pdf_templates' => $invoicemaker->getPdfTemplates(),
				'templates' => $invoicemaker->getInvoiceGroups(),
				'invoice' => $invoice
			],
			'invoice' => $invoice
		];
	}

}
