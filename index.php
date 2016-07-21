<?php

return [

	'name' => 'bixie/invoicemaker',

	'type' => 'extension',

	'main' => 'Bixie\\Invoicemaker\\InvoicemakerModule',

	'autoload' => [

		'Bixie\\Invoicemaker\\' => 'src'

	],
	'routes' => [

		'/invoicemaker' => [
			'name' => '@invoicemaker',
			'controller' => [
				'Bixie\\Invoicemaker\\Controller\\InvoicemakerController',
				'Bixie\\Invoicemaker\\Controller\\InvoiceController'
			]
		],
		'/api/invoicemaker' => [
			'name' => '@invoicemaker/api',
			'controller' => [
				'Bixie\\Invoicemaker\\Controller\\InvoiceApiController'
			]
		]

	],

	'resources' => [

		'bixie/invoicemaker:' => ''

	],

	'config' => [
		'save_pdfs' => true,
		'pdf_path' => 'storage/pdf',
		'invoice_groups' => [
			[
				'name' => 'default',
				'format' => '#{invoice_number}',
				'digits' => 4
			]
		],
		'templates' => [
			[
				'name' => 'default',
				'view' => 'default',
				'title' => 'INVOICE',
				'creditor_address' => '',
				'subline' => '',
				'params' => ['pdf_background' => '']
			]
		]
	],

	'menu' => [

		'invoicemaker' => [
			'label' => 'Invoicemaker',
			'icon' => 'packages/bixie/invoicemaker/icon.svg',
			'url' => '@invoicemaker',
			'access' => 'invoicemaker: manage invoices',
			'active' => '@invoicemaker(/*)'
		],

		'invoicemaker: invoices' => [
			'label' => 'Invoices',
			'parent' => 'invoicemaker',
			'url' => '@invoicemaker',
			'access' => 'invoicemaker: manage forms',
			'active' => '@invoicemaker(/invoice/edit)?'
		],

		'invoicemaker: settings' => [
			'label' => 'Settings',
			'parent' => 'invoicemaker',
			'url' => '@invoicemaker/settings',
			'access' => 'invoicemaker: manage settings',
			'active' => '@invoicemaker/settings'
		]

	],

	'permissions' => [

		'invoicemaker: manage settings' => [
			'title' => 'Manage settings'
		],

		'invoicemaker: manage invoices' => [
			'title' => 'Manage invoices'
		]

	],

	'settings' => '@invoicemaker/settings',

	'events' => [
		'console.init' => function ($event, $console) {
			$console->add(new \Bixie\Invoicemaker\Console\Commands\TranslateCommand());
		}
	]

];
