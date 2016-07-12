<?php

namespace Bixie\Invoicemaker\Invoice;

use Bixie\Framework\Traits\JsonSerializableTrait;
use Pagekit\Application as App;


class InvoiceLine implements \JsonSerializable {

	use	JsonSerializableTrait;

	public $type;
	public $description;
	public $vat_perc;
	public $base;
	public $units;
	public $per_unit;
	public $amount;

}