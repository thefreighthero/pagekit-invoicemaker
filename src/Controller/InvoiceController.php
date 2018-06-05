<?php

namespace Bixie\Invoicemaker\Controller;

use Pagekit\Application as App;
use Bixie\Invoicemaker\Model\Invoice;

/**
 * @Access("invoicemaker: view invoices", admin=true)
 * @Route("invoice", name="invoice")
 */
class InvoiceController {

	/**
	 * @Route("/edit", name="edit")
	 * @Request({"id": "int"})
	 */
	public function editAction ($id = 0) {

		if (!$invoice = Invoice::find($id)) {

			if ($id == 0) {
				$invoice = Invoice::create();
			}

		}

		if (!$invoice) {
			App::abort(404, __('Invoice not found'));
		}

		return [
			'$view' => [
				'title' => __('Invoice'),
				'name' => 'bixie/invoicemaker/admin/invoice.php'
			],
			'$data' => [
				'statuses' => Invoice::getStatuses(),
				'templates' => App::module('bixie/invoicemaker')->getTemplates(),
				'invoice' => $invoice
			],
			'invoice' => $invoice
		];
	}

}
