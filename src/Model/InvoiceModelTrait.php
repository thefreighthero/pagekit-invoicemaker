<?php

namespace Bixie\Invoicemaker\Model;

use Pagekit\Application as App;
use Pagekit\Database\ORM\ModelTrait;

trait InvoiceModelTrait {

	use ModelTrait;

	/**
	 * @param $invoice_number
	 * @return Invoice|bool
	 */
	public static function byInvoiceNumber ($invoice_number) {
		return static::where(compact('invoice_number'))->first();
	}

	public static function lastInvoiceNumber ($invoice_group_name) {
		if ($invoice = static::where(['invoice_group' => $invoice_group_name])->orderBy('invoice_number', 'desc')->first()) {
			return $invoice->invoice_number;
		}
		return false;
	}

}
