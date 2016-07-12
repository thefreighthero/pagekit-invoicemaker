<?php


namespace Bixie\Invoicemaker\Invoice;


class InvoiceLineCollection implements \IteratorAggregate, \Countable, \JsonSerializable {

	/**
	 * @var InvoiceLine[]
	 */
	protected $invoice_lines;

	/**
	 * Constructor.
	 * @param InvoiceLine[] $invoice_lines
	 */
	public function __construct (array $invoice_lines = []) {
		foreach ($invoice_lines as $data) {
			$this->add(new InvoiceLine($data));
		}
	}

	/**
	 * Adds type to collection.
	 * @param InvoiceLine $invoice_line
	 */
	public function add (InvoiceLine $invoice_line) {
		$this->invoice_lines[] = $invoice_line;
	}

	/**
	 * @return InvoiceLine[]
	 */
	public function all () {
		return $this->invoice_lines;
	}

	/**
	 * Countable interface implementation.
	 * @return int
	 */
	public function count () {
		return count($this->invoice_lines);
	}

	/**
	 * IteratorAggregate interface implementation.
	 * @return \ArrayIterator
	 */
	public function getIterator () {
		return new \ArrayIterator($this->invoice_lines);
	}

	/**
	 * Specify data which should be serialized to JSON
	 * @link  http://php.net/manual/en/jsonserializable.jsonserialize.php
	 * @return mixed data which can be serialized by <b>json_encode</b>,
	 * which is a value of any type other than a resource.
	 * @since 5.4.0
	 */
	function jsonSerialize () {
		return array_map(function ($invoiceLine) {
		    return $invoiceLine->toArray();
		}, $this->invoice_lines);
	}
}