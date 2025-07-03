<?php

namespace Bixie\Invoicemaker\Model;

use Bixie\Contactmanager\Model\Company;
use Bixie\Freighthero\Helpers\ShipmentHelper;
use Bixie\Freighthero\Model\Shipment;
use Bixie\Taskmanager\Model\Task;
use Pagekit\Application as App;
use Pagekit\Database\ORM\ModelTrait;

trait InvoiceModelTrait {

	use SoftDeleteTrait, ModelTrait {
        SoftDeleteTrait::delete insteadof ModelTrait; // Use delete from SoftDeleteTrait
        SoftDeleteTrait::query insteadof ModelTrait; // Use query from SoftDeleteTrait
        create as modelCreate;
    }

    /**
     * @param array $data
     * @return Invoice
     */
    public static function create ($data = []) {
        // Resolve Invoice status
        $company = Company::find($data['company_id']);

        $booking_type = null;
        if ($company->year_booking) {
            $booking_type = Invoice::BOOKING_TYPE_YEAR_BOOKING;
        } else {
            $invoicesCountCompanyHas = Invoice::where("company_id = " . $data["company_id"])->count();
            if ($invoicesCountCompanyHas == 0) {
                $booking_type = Invoice::BOOKING_TYPE_NEW;
            } else {
                $booking_type = Invoice::BOOKING_TYPE_REPEAT;
            }
        }

        $requiredFields = [
            'status' => Invoice::STATUS_INITIAL,
            'created' => new \DateTime(),
            'user_id' => App::user()->id, // Or fetch user ID as needed
            'company_id' => $data['company_id'] ?? 0, // Use $data or provide default value
            'invoice_number' => '', // This might need to be generated based on some logic
            'invoice_group' => '',
            'amount' => 0.00, // Default value, adjust as needed
            'amount_paid' => 0.00,
            'payments' => [],
            'exported' => false,
            'booking_type' => $booking_type, // Default booking type
        ];

        // Merge passed data with the required fields
        $data = array_merge($requiredFields, $data);

        /** @var Invoice $invoice */
        $invoice = self::modelCreate($data);

        return $invoice;
    }
    
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
     * @saved
     * @param $event
     * @param Invoice $invoice
     * @throws \Exception
     */
    public static function saved($event, Invoice $invoice) {

        if (stripos($invoice->ext_key, 'tfh.shipment') === false
            || $invoice->template == App::module('bixie/freighthero')->config('invoicemaker_template_advance')) {
            return;
        }
        $total_amount = App::module('bixie/invoicemaker')->getSumByExternKey($invoice->ext_key);
        $open_amount = App::module('bixie/invoicemaker')->getOpenSumByExternKey($invoice->ext_key);

        $id = str_replace('tfh.shipment.', '', $invoice->ext_key);
        $shipment = Shipment::find($id);
        $payed = $shipment->get('invoice_payed');
        if (!$payed && $open_amount <= 0 && $total_amount >= $shipment->price) {
            $shipment->set('invoice_payed', true);
            ShipmentHelper::saveShipment($shipment, [], [
                ['type' => 'SHIPMENT_SET_VALUE', 'payload' => [
                    'key' => 'invoice_payed', 'value' => true,
                ], 'timestamp' => time(),],
            ]);
            //set task completed
            if ($preset_ids = App::module('bixie/freighthero')->config('preset_invoice_paid')
                and $task = Task::where([
                    'status' => Task::STATUS_TODO,
                    'ext_key' => 'tfh.shipment.' . $shipment->id,
                    ])->whereIn('preset_id', $preset_ids)->first()) {
                App::module('bixie/taskmanager')->saveTask([
                    'id' => $task->id,
                    'status' => Task::STATUS_DONE,
                ]);
            }
        }
        if ($payed && $open_amount > 0) {
            $shipment->set('invoice_payed', false);
            ShipmentHelper::saveShipment($shipment, [], [
                ['type' => 'SHIPMENT_SET_VALUE', 'payload' => [
                    'key' => 'invoice_payed', 'value' => false,
                ], 'timestamp' => time(),],
            ]);
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
