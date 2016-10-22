<?php

namespace Bixie\Invoicemaker\Controller;

use Bixie\Invoicemaker\InvoicemakerException;
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

		/** @var InvoicemakerModule $invoicemaker */
		$invoicemaker = App::module('bixie/invoicemaker');
		return [
			'$view' => [
				'title' => __('Invoices'),
				'name' => 'bixie/invoicemaker/admin/invoices.php'
			],
			'$data' => [
				'templates' => $invoicemaker->getInvoiceGroups(),
                'groups' => $invoicemaker->getTemplates(),
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
