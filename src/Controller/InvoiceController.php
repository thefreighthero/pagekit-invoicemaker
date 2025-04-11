<?php

namespace Bixie\Invoicemaker\Controller;

use Bixie\Contactmanager\Model\Company;
use Bixie\Freighthero\Helpers\ShipmentHelper;
use Bixie\Freighthero\Model\Shipment;
use Bixie\Invoicemaker\Model\Invoice;
use Bixie\Twinfield\Model\PurchaseInvoice;
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


        $isCredit = $invoice->status === Invoice::STATUS_CREDIT || $invoice->amount < 0;
        $ledger_data = $invoice->get('ledger_data', []);
        $invoice_revenue = ShipmentHelper::getAmountsFromLedgerData(
            $ledger_data,
            $isCredit ? 'debit' : 'credit'
        );

        if($isCredit) {
            foreach ($invoice_revenue as &$revenue) {
                $revenue *= -1;
            }
        }

        if(strpos($invoice->ext_key, 'cm.company.') !== false) {
            $shipment = [
                'price' => $invoice_revenue['revenue_amount'],
                'cost_price' => 0,
                'bruto_margin' => 100,
                'invoice_revenue' => $invoice_revenue,
            ];
            // Find all connected purchase invoices and get the costs...

            $purchase_invoices = PurchaseInvoice::where(['invoicemaker_invoice_id = ' . $invoice->id])->get();

            $invoice_cost = 0;
            foreach($purchase_invoices as $purchase_invoice) {
                $invoice_cost += $purchase_invoice->amount_netto;
            }

            $shipment['cost_price'] = $invoice_cost;
            if($shipment['price'] > 0) {
                $shipment['bruto_margin'] = round((($shipment['price'] - $shipment['cost_price']) / $shipment['price']) * 100, 0);
            }

        } else {
            $shipment = Shipment::find(str_replace('tfh.shipment.', '', $invoice->ext_key));
        }

        $purchase_invoices = PurchaseInvoice::where('invoicemaker_invoice_id = ? ', [$invoice->id])->get();

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
                'invoice' => $invoice,
			    'purchase_invoices' => $purchase_invoices,
                'shipment' => $shipment,
                'invoice_revenue' => $invoice_revenue,
                'booking_types' => Invoice::getBookingTypes(),
			],
            'invoice' => $invoice,

		];
	}

}
