<?php


namespace Bixie\Invoicemaker\Settings;

use Bixie\Framework\Traits\JsonSerializableTrait;
use Pagekit\Application as App;

class InvoiceGroup implements \JsonSerializable {
	
	use JsonSerializableTrait;
	
	public $name;
	public $format;
	public $digits;

	/**
	 * @param int   $number
	 * @param array $data
	 * @return string
	 */
	public function getInvoiceNumber ($number, $data = []) {
		$invoice_number = $this->format;
		foreach ($data as $key => $value) {
			$invoice_number = str_replace('{'.$key.'}', $value, $invoice_number);
		}
		return str_replace('{invoice_number}', str_pad($number, $this->digits, '0', STR_PAD_LEFT), $invoice_number);
	}

}