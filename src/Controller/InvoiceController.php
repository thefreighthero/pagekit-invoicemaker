<?php

namespace Bixie\Invoicemaker\Controller;

use Bixie\Contactmanager\Model\Company;
use Bixie\Invoicemaker\Model\Invoice;
use Pagekit\Application as App;

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

        $company = Company::find($invoice->company_id);

		$twinfield = App::module('bixie/twinfield');

		return [
			'$view' => [
				'title' => __('Invoice'),
				'name' => 'bixie/invoicemaker/admin/invoice.php'
			],
			'$data' => [
				'statuses' => Invoice::getStatuses(),
				'templates' => App::module('bixie/invoicemaker')->getTemplates(),
                'tfConfig' => $twinfield ? $twinfield->config() : [],
                'ledger_numbers' => array_merge($twinfield->config('ledger_numbers'), $company->get('ledger_numbers', [])),
				'invoice' => $invoice
			],
			'invoice' => $invoice
		];
	}

}
