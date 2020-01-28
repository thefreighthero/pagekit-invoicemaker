<?php

namespace Bixie\Invoicemaker\Model;

use Pagekit\Application as App;
use Pagekit\Database\ORM\ModelTrait;

trait InvoiceModelTrait {

	use ModelTrait;

    /**
     * @Saving
     * @param $event
     * @param Invoice $invoice
     * @throws \Exception
     */
    public static function saving($event, Invoice $invoice) {
        if (!$invoice->paid_at && $invoice->amount !== 0 && $invoice->amount == $invoice->amount_paid) {
            $invoice->paid_at = new \DateTime(); //todo should this be the date of payment?
        }
        if ($invoice->paid_at && $invoice->amount !== 0 && $invoice->amount != $invoice->amount_paid) {
            $invoice->paid_at = null;
        }
    }

	/**
	 * @param $invoice_number
	 * @return Invoice|bool
	 */
	public static function byInvoiceNumber ($invoice_number) {
		return static::where(compact('invoice_number'))->first();
	}

	/**
	 * @param $ext_key
     * @param array $wheres
	 * @return array
	 */
	public static function byExternKey ($ext_key, $wheres = []) {
		return static::where(array_merge(compact('ext_key'), $wheres))->get();
	}

	/**
	 * @param $user_id
     * @param array $wheres
	 * @return array
	 */
	public static function getByUserId ($user_id, $wheres = []) {
		return static::where(array_merge(compact('user_id'), $wheres))->get();
	}

	/**
	 * @param $ext_key
     * @param array $wheres
	 * @return array
	 */
	public static function sumByExternKey ($ext_key, $wheres = []) {
		$res = self::getConnection()
			->createQueryBuilder()
			->from('@invoicemaker_invoice')
			->where(array_merge(compact('ext_key'), $wheres))
			->execute('SUM(amount) AS invoice_sum')->fetch(\PDO::FETCH_ASSOC);
		return $res['invoice_sum'];
	}

    /**
     * @param       $ext_key
     * @param array $wheres
     * @return array
     */
	public static function openSumByExternKey ($ext_key, $wheres = []) {
		$res = self::getConnection()
			->createQueryBuilder()
			->from('@invoicemaker_invoice')
			->where(array_merge(compact('ext_key'), $wheres))
			->execute('SUM(amount - amount_paid) AS open_sum')->fetch(\PDO::FETCH_ASSOC);
		return $res['open_sum'];
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
