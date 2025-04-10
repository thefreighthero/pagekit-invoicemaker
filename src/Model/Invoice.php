<?php

namespace Bixie\Invoicemaker\Model;


use Bixie\Invoicemaker\Invoice\Debtor;
use Bixie\Invoicemaker\Invoice\InvoiceLineCollection;
use Pagekit\Application as App;
use Pagekit\System\Model\DataModelTrait;
use Bixie\Invoicemaker\Model\SoftDeleteTrait;

/**
 * @Entity(tableClass="@invoicemaker_invoice",eventPrefix="invoicemaker_invoice")
 */
class Invoice implements \JsonSerializable {

	use DataModelTrait, InvoiceModelTrait;

    /* Invoice booking types: REPEAT, NEW, YEAR BOOKING */
    const BOOKING_TYPE_REPEAT = "REPEAT";
    const BOOKING_TYPE_NEW  = "NEW";
    const BOOKING_TYPE_YEAR_BOOKING = "YEAR_BOOKING";

    /* Invoice initial and valid. */
    const STATUS_INITIAL = 'INITIAL';

    /* Invoice credit. */
    const STATUS_CREDIT = 'CREDIT';

    /* Invoice credited. */
    const STATUS_CREDITED = 'CREDITED';

	/** @Column(type="integer") @Id */
	public $id;
	/**
	 * @Column(type="string")
	 * @var string
	 */
	public $status = 'INITIAL';
	/**
	 * @Column(type="string")
	 * @var string
	 */
	public $template = '';
	/**
	 * @Column(type="datetime")
	 * @var \DateTime
	 */
	public $created;
	/**
	 * @Column(type="string")
	 * @var string
	 */
	public $ext_key;
	/**
	 * @Column(type="integer")
	 * @var int
	 */
	public $user_id;
	/**
	 * @Column(type="integer")
	 * @var int
	 */
	public $company_id;
	/**
	 * @Column(type="string")
	 * @var string
	 */
	public $invoice_number;
	/**
	 * @Column(type="string")
	 * @var string
	 */
	public $invoice_group;
	/**
	 * @Column(type="decimal")
	 * @var float
	 */
	public $amount = 0.00;
	/**
	 * @Column(type="decimal")
	 * @var float
	 */
	public $amount_paid = 0.00;
    /**
     * @Column(type="json_array")
     * @var array
     */
    public $payments = [];
    /**
     * @Column(type="datetime")
     * @var \DateTime
     */
    public $paid_at;
    /**
     * @Column (type="boolean")
     * @var bool
     */
    public $exported = false;

    /**
     * @Column(type="datetime")
     * @var \DateTime
     */
    public $exported_at;

    /**
     * @Column(type="string")
     * @var string
     */
    public $booking_type;

    /**
	 * @Column(type="string")
	 * @var string
	 */
	public $pdf_file;
	/**
	 * @Column(type="json_array")
	 * @var Debtor
	 */
	public $debtor;
	/**
	 * @Column(type="json_array")
	 * @var InvoiceLineCollection
	 */
	public $invoice_lines;

    /**
     * @Column(type="datetime")
     * @var \DateTime
     */
    public $deleted_at;


	/** @var array */
	protected static $properties = [
		'amount_open' => 'getAmountOpen',
		'pdf_filename' => 'getPdfFilename',
		'pdf_url' => 'getPdfUrl',
        'key' => 'getKey',
        'debtor_company' => 'getDebtorCompany',
	];

    /**
     * @return array
     */
    public static function getStatuses () {
        return [
            self::STATUS_INITIAL => __('Initial'),
            self::STATUS_CREDIT => __('Credit'),
            self::STATUS_CREDITED => __('Credited')
        ];
    }

    /**
     * @return array
     */
    public static function getBookingTypes () {
        return [
            self::BOOKING_TYPE_REPEAT => __('Repeat'),
            self::BOOKING_TYPE_NEW => __('New'),
            self::BOOKING_TYPE_YEAR_BOOKING => __('Year Booking'),
        ];
    }

	/**
	 * @return Debtor
	 */
	public function getDebtor () {
		if (!$this->debtor || !$this->debtor instanceof Debtor) {
			$this->debtor = new Debtor($this->debtor ?: []);
		}
		return $this->debtor;
	}

	/**
	 * @return InvoiceLineCollection
	 */
	public function getInvoiceLines () {
		if (!$this->invoice_lines || !$this->invoice_lines instanceof InvoiceLineCollection) {
			$this->invoice_lines = new InvoiceLineCollection($this->invoice_lines ?: []);
		}
		return $this->invoice_lines;
	}

	public function getPdfFilename () {
	    $search = ['\\', '/', ':', '*', '"', '<', '>', '|'];
		return str_replace($search, '', sprintf('%s - %s.pdf',
            $this->invoice_number,
            $this->getDebtor()->company ? : $this->getDebtor()->name
        ));
	}

	public function getAmountOpen () {
		return $this->amount - $this->amount_paid;
	}

    /**
     * @param string  $extra
     * @return string
     */
    public function getKey ($extra = '') {
        return sha1(App::system()->config('key') . '.' . $this->id . $extra);
    }

    /**
     * @return string
     */
    public function getDebtorCompany () {
        return $this->debtor->name;
    }

    /**
	 * @param bool $inline
	 * @return mixed
	 */
	public function getPdfUrl ($inline = false) {
		return App::url('@invoicemaker/api/invoice/pdf', [
			'invoice_number' => $this->invoice_number,
			'key' => App::module('bixie/invoicemaker')->getDownloadKey($this),
			'inline' => $inline]);
	}
}
