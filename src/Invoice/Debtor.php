<?php

namespace Bixie\Invoicemaker\Invoice;

use Bixie\PkFramework\Traits\JsonSerializableTrait;
use Pagekit\Application as App;

class Debtor implements \JsonSerializable {

	use JsonSerializableTrait;

	public $company = '';
	public $name = '';
	public $address_1 = '';
	public $address_2 = '';
	public $zip_code = '';
	public $city = '';
	public $county = '';
	public $state = '';
	public $country = '';
	public $email = '';
	public $debtor_id = '';
	public $debtor_vat = '';
	public $debtor_coc = '';

}
