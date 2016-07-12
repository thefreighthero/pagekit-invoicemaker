# Bixie Invoice Maker

Create invoices in PDF.

Simple example:
```
		$app = App::getInstance();
		try {

			$debtor = $app['invoicemaker.factory']->debtor([
				'name' => 'P. Test',
				'address_1' => 'Straat 34',
				'address_2' => '3 hoog',
				'zip_code' => '2423 SF',
				'city' => 'Daazo',
				'country' => 'NL',
				'debtor_id' => 'TFH-224j2',
				'debtor_vat' => 'NLB3423422 B02',
				'debtor_coc' => '24923'
			]);
			$invoice_lines = $app['invoicemaker.factory']->invoiceLines([
				[
					'type' => 'spec',
					'description' => 'Spec 1',
					'vat_perc' => 21,
					'base' => 25.26,
					'units' => 5,
					'per_unit' => 64.45,
					'amount' => 347.51
				],
				[
					'type' => 'spec',
					'description' => 'Spec 2',
					'vat_perc' => 21,
					'base' => 5.26,
					'units' => 5,
					'per_unit' => 54.45,
					'amount' => 277.51
				],
				[
					'type' => 'sub',
					'description' => 'Subtotal',
					'amount' => 625.02
				],
				[
					'type' => 'vat',
					'description' => 'Vat 21%',
					'amount' => 131.25
				],
				[
					'type' => 'total',
					'description' => 'Total',
					'amount' => 756.46
				]
			]);
			$invoice = $app['invoicemaker.factory']->create($debtor, $invoice_lines, 'default', 'default', [
				'amount' => 756.46,
				'debtor_id' => 'TFH-224j2',
				'ext_id' => 'tfh.shipment.532'
			]);
				
            echo sprintf('Invoice %s created.', $invoice->invoice_number);
		} catch (InvoicemakerException $e) {
			echo $e->getMessage();
		}

```