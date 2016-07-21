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

	/**
	 * @param $ext_key
	 * @return array
	 */
	public static function byExternKey ($ext_key) {
		return static::where(compact('ext_key'))->get();
	}

	/**
	 * @param $ext_key
	 * @return array
	 */
	public static function sumByExternKey ($ext_key) {
		$res = self::getConnection()
			->createQueryBuilder()
			->from('@invoicemaker_invoice')
			->where(compact('ext_key'))
			->execute('SUM(amount) AS invoice_sum')->fetch(\PDO::FETCH_ASSOC);
		return $res['invoice_sum'];
	}

	/**
	 * @param $invoice_group_name
	 * @return bool
	 */
	public static function lastInvoiceNumber ($invoice_group_name) {
		if ($invoice = static::where(['invoice_group' => $invoice_group_name])->orderBy('invoice_number', 'desc')->first()) {
			return $invoice->invoice_number;
		}
		return false;
	}

}
