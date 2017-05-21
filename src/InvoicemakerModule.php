<?php

namespace Bixie\Invoicemaker;

use Bixie\Invoicemaker\Invoice\Debtor;
use Bixie\Invoicemaker\Invoice\InvoiceLineCollection;
use Bixie\Invoicemaker\Model\Invoice;
use Bixie\Invoicemaker\Settings\InvoiceGroup;
use Bixie\Invoicemaker\Settings\Template;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Dompdf\Dompdf;
use Pagekit\Application as App;
use Pagekit\Module\Module;
use Pagekit\Util\Arr;

class InvoicemakerModule extends Module {
	/**
	 * @var InvoiceGroup[]
	 */
	protected $invoice_groups;
	/**
	 * @var Template[]
	 */
	protected $templates;
	/**
	 * @var array
	 */
	protected $pdf_templates = [];

	/**
	 * {@inheritdoc}
	 */
	public function main (App $app) {

		$app['invoicemaker.factory'] = function ($app) {
			return new InvoiceFactory($app);
		};

		$app['locator']->add('invoicemakerInvoices:', $this->getPdfPath());
		
		$this->registerPdfTemplate('default', 'bixie/invoicemaker:templates/default');
    }

	/**
	 * @return InvoiceGroup[]
	 */
	public function getInvoiceGroups () {
		if (!isset($this->invoice_groups)) {
			$this->invoice_groups = array_map(function ($data) {
			    return new InvoiceGroup($data);
			}, $this->config('invoice_groups', []));
		}
		return $this->invoice_groups;
	}

	/**
	 * @param string $invoice_group
	 * @return InvoiceGroup|bool
	 */
	public function getInvoiceGroup ($invoice_group) {
		$this->getInvoiceGroups();
		$groups = array_filter($this->invoice_groups, function ($invoiceGroup) use ($invoice_group) {
		    return $invoiceGroup->name == $invoice_group;
		});
		return isset($groups[0]) ? $groups[0] : false;
	}

	/**
	 * @return array|Template[]
	 */
	public function getTemplates () {
		if (!isset($this->templates)) {
			$this->templates = array_map(function ($data) {
			    return new Template($this, $data);
			}, $this->config('templates', []));
		}
		return $this->templates;
	}

	/**
	 * @param string $template_name
	 * @return Template|bool
	 */
	public function getTemplate ($template_name) {
		$this->getTemplates();
		$templates = array_filter($this->templates, function ($template) use ($template_name) {
			return $template->name == $template_name;
		});
		return count($templates) ? reset($templates) : false;
	}

	/**
	 * @param $name
	 * @param $path
	 */
	public function registerPdfTemplate ($name, $path) {
		$this->pdf_templates[$name] = $path;
	}

	/**
	 * @return array
	 */
	public function getPdfTemplates () {
		return array_keys($this->pdf_templates);
	}

	/**
	 * @param $name
	 * @return bool|mixed
	 */
	public function getPdfTemplate ($name) {
		return isset($this->pdf_templates[$name]) ? $this->pdf_templates[$name] : false;
	}

    /**
	 * @param Debtor                $debtor
	 * @param InvoiceLineCollection $invoice_lines
	 * @param string                $template_name
	 * @param string                $invoice_group
	 * @param array                 $data
	 * @return Invoice
	 */
	public function createInvoice (Debtor $debtor, InvoiceLineCollection $invoice_lines, $template_name, $invoice_group, $data =[]) {

		if (!$invoiceGroup = $this->getInvoiceGroup($invoice_group)) {
			throw new InvoicemakerException(sprintf('Invoicegroup %s not found', $invoice_group), 400);
		}

		if (!$template = $this->getTemplate($template_name)) {
			throw new InvoicemakerException(sprintf('Template %s not found', $template_name), 400);
		}

		$invoice = Invoice::create([
			'debtor' => $debtor,
			'invoice_lines' => $invoice_lines,
			'created' => new \DateTime(),
			'amount' => Arr::get($data, 'amount', ''),
			'ext_key' => Arr::get($data, 'ext_key', ''),
            'user_id' => Arr::get($data, 'user_id', App::user()->id),
			'template' => $template->name,
			'invoice_number' => $this->getInvoiceNumber($invoiceGroup, $data),
			'invoice_group' => $invoiceGroup->name,
			'data' => array_diff_key($data, array_flip(['amount', 'ext_key']))
		]);

		try {

			$invoice->save(['status' => ($invoice->amount < 0 ? Invoice::STATUS_CREDIT : Invoice::STATUS_INITIAL)]);

		} catch (\Exception $e) {
			if ($e instanceof UniqueConstraintViolationException) {
				throw new InvoicemakerException(sprintf('Invoice number %s already exists!', $invoice->invoice_number), $e->getCode(), $e);
			}
			throw new InvoicemakerException('Error in saving invoice to database', $e->getCode(), $e);
		}

		try {

			if ($this->renderPdfFile($invoice)) {

				$invoice->save([
					'pdf_file' => $invoice->getPdfFilename()
				]);

			}

		} catch (\Exception $e) {
			throw new InvoicemakerException('Error in creating PDF file', $e->getCode(), $e);
		}

		return $invoice;
	}

    /**
     * @param $invoice_number
     * @return Invoice
     */
    public function creditInvoice ($invoice_number) {

        if (!$invoice = Invoice::byInvoiceNumber($invoice_number)) {
            throw new InvoicemakerException(__('Invoice %invoice_number% not found!', ['%invoice_number%' => $invoice_number]));
        }
        if ($invoice->status == Invoice::STATUS_CREDITED) {
            throw new InvoicemakerException(__('Invoice %invoice_number% already has been credited by invoice %credit_invoice_number%!', [
                '%invoice_number%' => $invoice_number,
                '%credit_invoice_number%' => $invoice->get('credited_by')
            ]));
        }
        if ($invoice->amount < -1) {
            throw new InvoicemakerException(__('Invoice %invoice_number% already is a credit invoice!', ['%invoice_number%' => $invoice_number]));
        }

        $invoice_lines = $invoice->getInvoiceLines();

        $credit_invoice =  $this->createInvoice(
            $invoice->getDebtor(),
            $invoice_lines,
            $invoice->template,
            $invoice->invoice_group,
            array_merge([
                'credit_for' => $invoice->invoice_number,
                'ext_key' => $invoice->ext_key,
                'user_id' => $invoice->user_id,
                'amount' => $invoice->amount * -1,
                'amount_paid' => $invoice->amount * -1,
            ], $invoice->data)
        );

        $invoice->amount_paid = $invoice->amount;
        $invoice->payments = [[
            'amount' => $invoice->amount,
            'date' => (new \DateTime())->format(\DATE_ATOM),
            'via' => __('Credit invoice'),
            'transaction_id' => $credit_invoice->invoice_number,
        ]];
        $invoice->set('credited_by', $credit_invoice->invoice_number);
        $invoice->save(['status' => Invoice::STATUS_CREDITED]);

        return $credit_invoice;
    }

    /**
     * @param Invoice $invoice
     * @param array   $payment
     */
    public function addPayment (Invoice $invoice, $payment) {
        $transaction_id = Arr::get($payment, 'transaction_id', '');
        $edited = false;
        if ($transaction_id) {
            foreach ($invoice->payments as &$pymnt) {
                if (!empty($pymnt['transaction_id']) && $pymnt['transaction_id'] == $transaction_id) {
                    $edited = true;
                    $pymnt = array_merge($pymnt, $payment);
                    break;
                }
            }
        }
        if (!$edited) {
            $invoice->payments[] = array_merge([
                'amount' => 0,
                'date' => (new \DateTime())->format(DATE_ATOM),
                'via' => '',
                'transaction_id' => '',
            ], $payment);
        }
        $invoice->amount_paid = array_reduce($invoice->payments, function ($total, $pymnt) {
            return $total + $pymnt['amount'];
        }, 0);
    }

    /**
	 * @param $ext_key
     * @param array $wheres
	 * @return Invoice[]|bool
	 */
	public function getByExternKey ($ext_key, $wheres = []) {
		if ($invoices = Invoice::byExternKey($ext_key, $wheres)) {
			return array_values($invoices);
		}
		return [];
	}

    /**
	 * @param int $user_id
     * @param array $wheres
	 * @return Invoice[]|bool
	 */
	public function getByUserId ($user_id, $wheres = []) {
		if ($invoices = Invoice::getByUserId($user_id, $wheres)) {
			return array_values($invoices);
		}
		return [];
	}

	/**
	 * @param $ext_key
     * @param array $wheres
	 * @return float
	 */
	public function getSumByExternKey ($ext_key, $wheres = []) {
		if ($sum = Invoice::sumByExternKey($ext_key, $wheres)) {
			return (float) $sum;
		}
		return 0;
	}

	/**
	 * @param $ext_key
     * @param array $wheres
	 * @return float
	 */
	public function getOpenSumByExternKey ($ext_key, $wheres = []) {
		if ($sum = Invoice::openSumByExternKey($ext_key, $wheres)) {
			return (float) $sum;
		}
		return 0;
	}

	/**
	 * @param InvoiceGroup $invoiceGroup
	 * @param array        $data
	 * @return string
	 */
	public function getInvoiceNumber (InvoiceGroup $invoiceGroup, $data = []) {
		if ($last_invoice_number = Invoice::lastInvoiceNumber($invoiceGroup->name)) {
			return $invoiceGroup->getInvoiceNumber((intval(substr($last_invoice_number, $invoiceGroup->digits * -1), 10) + 1), $data);
		}
		return $invoiceGroup->getInvoiceNumber(1, $data);
	}

	/**
	 * @param Invoice $invoice
	 * @param array   $params
	 * @return string
	 */
	public function renderHtml (Invoice $invoice, $params = []) {
		return $this->getTemplate($invoice->template)->mergeParams($params)->renderHtml($invoice);
	}

	/**
	 * @param Invoice $invoice
	 * @return string
	 */
	public function renderPdfFile (Invoice $invoice) {
		if ($this->config['save_pdfs'] and $path = $this->getPdfPath()) {
			return file_put_contents($path . '/' . $invoice->getPdfFilename(), $this->renderPdfString($invoice)) > 0;
		}
		return false;
	}

	/**
	 * @param Invoice $invoice
	 * @return string
	 */
	public function renderPdfString (Invoice $invoice) {
		$dompdf = new Dompdf();
		$dompdf->loadHtml($this->renderHtml($invoice));
		$dompdf->setPaper('A4', 'portrait');
		$dompdf->render();
		return $dompdf->output();
	}

	/**
	 * @param Invoice $invoice
	 * @return string
	 */
	public function getDownloadKey (Invoice $invoice) {
		$session_key = $this->getSessionKey($invoice);
		App::session()->set("_bixieInvoice.downloadkey.{$invoice->id}", $session_key);
		return $session_key;
	}

	/**
	 * @param Invoice $invoice
	 * @param         $key
	 * @return bool
	 */
	public function checkDownloadKey (Invoice $invoice, $key) {
		$check_key = $this->getSessionKey($invoice);
		if ($invoice->id > 0
			and $check_key === $key
			and $key === App::session()->get("_bixieInvoice.downloadkey.{$invoice->id}")) {

			return true;
		}
		return false;
	}

	/**
	 * @return string
	 */
	public function getPdfPath () {
		$root = strtr(App::path(), '\\', '/');
		$path = $this->normalizePath($root . '/' . $this->config['pdf_path']);
		if (!is_dir($path)) {
			App::file()->makeDir($path);
		}
		return $path;
	}

	/**
	 * @param Invoice $invoice
	 * @return string
	 */
	protected function getSessionKey (Invoice $invoice) {
		return $invoice->getKey(App::session()->getId());
	}

	/**
	 * Normalizes the given path
	 * @param  string $path
	 * @return string
	 */
	protected function normalizePath ($path) {
		$path = str_replace(['\\', '//'], '/', $path);
		$prefix = preg_match('|^(?P<prefix>([a-zA-Z]+:)?//?)|', $path, $matches) ? $matches['prefix'] : '';
		$path = substr($path, strlen($prefix));
		$parts = array_filter(explode('/', $path), 'strlen');
		$tokens = [];

		foreach ($parts as $part) {
			if ('..' === $part) {
				array_pop($tokens);
			} elseif ('.' !== $part) {
				array_push($tokens, $part);
			}
		}

		return $prefix . implode('/', $tokens);
	}

}
