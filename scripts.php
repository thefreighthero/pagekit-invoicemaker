<?php

return [

    'install' => function ($app) {

		$util = $app['db']->getUtility();

		if ($util->tableExists('@invoicemaker_invoice') === false) {
			$util->createTable('@invoicemaker_invoice', function ($table) {
				$table->addColumn('id', 'integer', ['unsigned' => true, 'length' => 10, 'autoincrement' => true]);
				$table->addColumn('status', 'string', ['length' => 16]);
				$table->addColumn('template', 'string', ['length' => 255]);
				$table->addColumn('created', 'datetime');
				$table->addColumn('invoice_number', 'string', ['length' => 255]);
				$table->addColumn('invoice_group', 'string', ['length' => 255]);
				$table->addColumn('amount', 'decimal', ['precision' => 9, 'scale' => 2]);
				$table->addColumn('amount_paid', 'decimal', ['precision' => 9, 'scale' => 2]);
                $table->addColumn('payments', 'json_array', ['notnull' => false]);
                $table->addColumn('ext_key', 'string', ['length' => 255, 'notnull' => false]);
				$table->addColumn('pdf_file', 'string', ['length' => 255, 'notnull' => false]);
				$table->addColumn('debtor', 'json_array', ['notnull' => false]);
				$table->addColumn('invoice_lines', 'json_array', ['notnull' => false]);
				$table->addColumn('data', 'json_array', ['notnull' => false]);
				$table->setPrimaryKey(['id']);
				$table->addIndex(['ext_key'], 'INVOICEMAKER_INVOICE_EXT_KEY');
				$table->addUniqueIndex(['invoice_number'], '@INVOICEMAKER_INVOICE_INVOICE_NUMBER');
			});
		}
		
    },

	'uninstall' => function ($app) {

        $util = $app['db']->getUtility();

        if ($util->tableExists('@invoicemaker_invoice')) {
            $util->dropTable('@invoicemaker_invoice');
        }

		// remove the config
		$app['config']->remove('bixie/invoicemaker');

	},

	'updates' => [


	]

];