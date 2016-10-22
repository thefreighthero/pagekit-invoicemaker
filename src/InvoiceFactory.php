<?php


namespace Bixie\Invoicemaker;

use Bixie\Invoicemaker\Invoice\Debtor;
use Bixie\Invoicemaker\Invoice\InvoiceLineCollection;
use Bixie\Invoicemaker\Model\Invoice;
use Pagekit\Application as App;

class InvoiceFactory {
	/**
	 * @var App
	 */
	protected $app;
	/**
	 * @var InvoicemakerModule $invoicemaker
	 */
	protected $invoicemaker;

	/**
	 * InvoiceFactory constructor.
	 * @param App $app
	 */
	public function __construct ($app) {
		$this->app = $app;
		$this->invoicemaker = $app->module('bixie/invoicemaker');
	}

	/**
	 * @param $invoice_number
	 * @return Invoice|bool
	 */
	public function get ($invoice_number) {
		return Invoice::byInvoiceNumber($invoice_number);
	}

	/**
	 * @param array $data
	 * @return Debtor
	 */
	public function debtor ($data = []) {
		return new Debtor($data);
	}

	/**
	 * @param array $invoice_lines
	 * @return InvoiceLineCollection
	 */
	public function invoiceLines ($invoice_lines = []) {
		return new InvoiceLineCollection($invoice_lines);
	}

	/**
	 * @param Debtor                $debtor
	 * @param InvoiceLineCollection $lines
	 * @param string                $template_name
	 * @param string                $invoice_group
	 * @param array                 $data
	 * @return Invoice
	 */
	public function create (Debtor $debtor, InvoiceLineCollection $lines, $template_name, $invoice_group, $data =[]) {
		return $this->invoicemaker->createInvoice($debtor, $lines, $template_name, $invoice_group, $data);
	}

    public function credit ($invoice_number) {
        return $this->invoicemaker->creditInvoice($invoice_number);
	}

    /**
     * @param Invoice $invoice
     * @param array   $params
     * @return string
     */
	public function renderHtml (Invoice $invoice, $params = []) {
		return $this->invoicemaker->renderHtml($invoice, $params);
	}
}
