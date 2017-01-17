<?php

namespace Bixie\Invoicemaker\Controller;

use Bixie\Invoicemaker\InvoicemakerException;
use Bixie\Invoicemaker\Model\Invoice;
use Pagekit\Application as App;
use Bixie\Invoicemaker\InvoicemakerModule;

/**
 * @Access(admin=true)
 */
class InvoicemakerController {

	/**
	 * @Route("/", methods="GET")
	 * @Request({"filter": "array", "page":"int"})
	 */
	public function indexAction ($filter = [], $page = null) {

//        foreach (Invoice::where('status = ?', ['CREDIT'])->get() as $invoice) {
//            $invoice->amount_paid = $invoice->amount;
//            $invoice->payments = [[
//                'amount' => $invoice->amount,
//                'date' => (new \DateTime())->format(\DATE_ATOM),
//                'via' => __('Credit invoice'),
//                'transaction_id' => '',
//            ]];
//            $invoice->save();
//
//        }


		/** @var InvoicemakerModule $invoicemaker */
		$invoicemaker = App::module('bixie/invoicemaker');
		return [
			'$view' => [
				'title' => __('Invoices'),
				'name' => 'bixie/invoicemaker/admin/invoices.php'
			],
			'$data' => [
				'groups' => $invoicemaker->getInvoiceGroups(),
                'templates' => $invoicemaker->getTemplates(),
                'statuses' => Invoice::getStatuses(),
				'config' => [
					'filter' => (object) $filter,
					'page' => $page
				]
			]
		];
	}


	/**
	 * @Access("system: access settings")
	 */
	public function settingsAction () {

		return [
			'$view' => [
				'title' => __('Invoicemaker settings'),
				'name' => 'bixie/invoicemaker/admin/settings.php'
			],
			'$data' => [
				'pdf_templates' => App::module('bixie/invoicemaker')->getPdfTemplates(),
				'config' => App::module('bixie/invoicemaker')->config()
			]
		];
	}


}
