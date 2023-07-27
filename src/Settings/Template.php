<?php


namespace Bixie\Invoicemaker\Settings;

use Bixie\PkFramework\Traits\JsonSerializableTrait;
use Bixie\Invoicemaker\InvoicemakerException;
use Bixie\Invoicemaker\InvoicemakerModule;
use Bixie\Invoicemaker\Model\Invoice;
use Pagekit\Application as App;
use Pagekit\Util\Arr;

class Template implements \JsonSerializable {

	use JsonSerializableTrait {
		__construct as serializeConstruct;
		toArray as serializeToArray;
	}

	public $name = '';
	public $pdf_template = '';
	public $title = '';
	public $credit_title = '';
	public $creditor_address = '';
	public $subline = '';
	public $params = [];

	/**
	 * @var InvoicemakerModule
	 */
	protected $invoicemaker;

	/**
	 * Template constructor.
	 * @param InvoicemakerModule $invoicemaker
	 * @param array              $data
	 */
	public function __construct (InvoicemakerModule $invoicemaker, $data) {
		$this->invoicemaker = $invoicemaker;
		self::serializeConstruct($data);
	}


	public function mergeParams (array $params) {
		$this->params = array_merge($this->params, $params);
		return $this;
	}

	/**
	 * Gets a data value.
	 * @param  string $key
	 * @param  mixed  $default
	 * @return mixed
	 */
	public function get ($key, $default = null) {
		return Arr::get((array)$this->params, $key, $default);
	}

	/**
	 * Sets a data value.
	 * @param string $key
	 * @param mixed  $value
	 */
	public function set ($key, $value) {
		if (null === $this->params) {
			$this->params = [];
		}

		Arr::set($this->params, $key, $value);
	}

	public function renderHtml (Invoice $invoice) {
		if (!$template_path = $this->invoicemaker->getPdfTemplate($this->pdf_template) or !App::locator()->get($template_path . '/invoice.php')) {
			throw new InvoicemakerException(sprintf('PDF template %s not found', $this->pdf_template));
		}
		return App::view($template_path . '/invoice.php', [
		    'invoice' => $invoice,
            'template' => $this,
//            'isCredit' => $invoice->status == Invoice::STATUS_CREDIT
            'isCredit' => (int)$invoice->amount > 0 ? false : true,
        ]);

	}

	public function markdown ($key) {
		return App::content()->applyPlugins(nl2br(isset($this->$key) ? $this->$key : $this->get($key, '')), ['markdown' => true]);
	}

	public function markdownText ($text) {
		return App::content()->applyPlugins($text, ['markdown' => true]);
	}

    /**
     * @param array $data
     * @param array $ignore
     * @return array
     */
    public function toArray ($data = [], $ignore = []) {
        return $this->serializeToArray($data, array_merge(['invoicemaker'], $ignore));
    }
}