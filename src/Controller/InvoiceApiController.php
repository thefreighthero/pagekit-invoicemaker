<?php

namespace Bixie\Invoicemaker\Controller;

use Pagekit\Application as App;
use Pagekit\Application\Exception;
use Bixie\Invoicemaker\InvoicemakerModule;
use Bixie\Invoicemaker\Model\Invoice;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Generator\UrlGenerator;

/**
 * @Route("invoice", name="invoice")
 */
class InvoiceApiController {

	/**
	 * @Route("/", methods="GET")
	 * @Request({"filter": "array", "page":"int"})
	 * @Access("invoicemaker: view invoices")
	 */
	public function indexAction ($filter = [], $page = 0) {
		$query = Invoice::query()->select('*, amount - amount_paid AS amount_open');
		$filter = array_merge(array_fill_keys(['template', 'invoice_group', 'company_id', 'user_id', 'only', 'status', 'search', 'ext_key', 'order', 'limit'], ''), $filter);

		extract($filter, EXTR_SKIP);

		$user = App::user();
		if (!$user->hasAccess('invoicemaker: manage invoices')) {
		    $user_id = $user->id;
        }

		if (!empty($template)) {
			$query->where('template = :template', compact('template'));
		}

		if (!empty($invoice_group)) {
			$query->where('invoice_group = :invoice_group', compact('invoice_group'));
		}

		if (!empty($ext_key)) {
			$query->where('ext_key = :ext_key', compact('ext_key'));
		}

		if (!empty($user_id)) {
			$query->where('user_id = :user_id', compact('user_id'));
		}

		if (!empty($company_id)) {
			$query->where('company_id = :company_id', compact('company_id'));
		}

		if (!empty($status)) {
			$query->where('status = :status', compact('status'));
		}

		if (!empty($only_open)) {
			$query->where('amount - amount_paid > 0');
		}

        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->orWhere(['invoice_number LIKE :search', 'ext_key LIKE :search', 'debtor LIKE :search'], ['search' => "%{$search}%"]);
            });
        }

        if (!preg_match('/^(invoice_number|ext_key|invoice_group|template|amount|amount_open|created)\s(asc|desc)$/i', $order, $order)) {
			$order = [1 => 'invoice_number', 2 => 'desc'];
		}

		$limit = (int)$limit ?: 20;
		$count = $query->count();
		$pages = ceil($count / $limit);
		$page = max(0, min($pages - 1, $page));

		$invoices = array_values($query->offset($page * $limit)->limit($limit)->orderBy($order[1], $order[2])->get());

		return compact('invoices', 'pages', 'count');

	}

	/**
	 * @Route("/", methods="POST")
	 * @Route("/{id}", methods="POST", requirements={"id"="\d+"})
	 * @Request({"invoice": "array", "id": "int"}, csrf=true)
	 * @Access("invoicemaker: manage invoices")
	 */
	public function saveAction ($data, $id = 0) {

		if (!$invoice = Invoice::find($id)) {
			$invoice = Invoice::create();
			unset($data['id']);
		}

		try {

			$invoice->save($data);

		} catch (Exception $e) {
			App::abort(400, $e->getMessage());
		}

		return ['message' => 'success', 'invoice' => $invoice];
	}

	/**
	 * @Route("/{id}", methods="DELETE", requirements={"id"="\d+"})
	 * @Request({"id": "int"}, csrf=true)
	 * @Access("invoicemaker: manage invoices")
	 */
	public function deleteAction ($id) {
		if ($invoice = Invoice::find($id)) {

			$invoice->delete();
		}

		return ['message' => 'success'];
	}

	/**
	 * @Route("/bulk", methods="DELETE")
	 * @Request({"ids": "array"}, csrf=true)
	 * @Access("invoicemaker: manage invoices")
	 */
	public function bulkDeleteAction ($ids = []) {
		foreach (array_filter($ids) as $id) {
			$this->deleteAction($id);
		}

		return ['message' => 'success'];
	}

	/**
	 * @Route("/credit/{id}", methods="POST", name="credit")
	 * @Request({"id": "integer"}, csrf=true)
	 * @Access("invoicemaker: manage invoices")
	 */
	public function creditAction($id) {
		/** @var InvoicemakerModule $invoicemaker */
		$invoicemaker = App::module('bixie/invoicemaker');

		if (!$invoice = Invoice::find($id)) {
			App::abort(404, __('Invoice not found'));
		}
        try {

            if ($credit_invoice = $invoicemaker->creditInvoice($invoice->invoice_number)) {

                return [
                    'invoice' => $invoice,
                    'credit_invoice' => $credit_invoice,
                ];

            }

        } catch (\Exception $e) {
            App::abort(500, __('Error in creating Credit invoice'));
        }

	}

	/**
	 * @Route("/rerender/{id}", name="rerender")
	 * @Request({"id": "integer"})
	 * @Access("invoicemaker: manage invoices")
	 */
	public function reRenderPdfAction($id) {
		/** @var InvoicemakerModule $invoicemaker */
		$invoicemaker = App::module('bixie/invoicemaker');

		if (!$invoice = Invoice::find($id)) {
			App::abort(404, __('Invoice not found'));
		}

		try {

			if ($invoicemaker->renderPdfFile($invoice)) {

				$invoice->save([
					'pdf_file' => $invoice->getPdfFilename()
				]);

			}

		} catch (\Exception $e) {
			App::abort(500, __('Error in creating PDF file'));
		}

		return ['message' => 'success'];
	}

	/**
	 * @Route("/pdf/{invoice_number}", name="pdf")
	 * @Request({"invoice_number": "string", "key": "string", "inline": "bool"})
	 * @Access("invoicemaker: view invoices")
	 * @param integer $invoice_number Invoice bumber
	 * @param string  $key            session key
	 * @param bool    $inline
	 * @return StreamedResponse|BinaryFileResponse
	 */
	public function pdfAction($invoice_number, $key, $inline = false) {
		/** @var InvoicemakerModule $invoicemaker */
		$invoicemaker = App::module('bixie/invoicemaker');

		if (!$invoice = Invoice::byInvoiceNumber($invoice_number)) {
			App::abort(404, __('Invoice not found'));
		}

		if (!$invoicemaker->checkDownloadKey($invoice, $key)) {
			App::abort(403, __('Key not valid.'));
		}

		if ($filename = $invoice->pdf_file and $path = $invoicemaker->getPdfPath() . '/' . $filename) {
			//existing file
			$response = new BinaryFileResponse($path);

		} else {
			//generate stream
			$filename = $invoice->getPdfFilename();
			$response = new StreamedResponse();
			$response->setCallback(function () use ($invoicemaker, $invoice) {
				echo $invoicemaker->renderPdfString($invoice);
			});
			$response->setStatusCode(200);
			$response->headers->set('Content-Type', 'application/pdf; charset=utf-8');

		}

		$response->headers->set('Content-Disposition', $response->headers->makeDisposition(
			($inline ? ResponseHeaderBag::DISPOSITION_INLINE: ResponseHeaderBag::DISPOSITION_ATTACHMENT),
			$filename,
			mb_convert_encoding($filename, 'ASCII')
		));

		return $response;

	}

	/**
	 * @Route("/html/{invoice_number}", name="html")
	 * @Request({"invoice_number": "string", "key": "string"})
	 * @param integer $invoice_number Invoice bumber
	 * @param string  $key            session key
	 * @return StreamedResponse
	 */
	public function htmlAction($invoice_number, $key) {
		/** @var InvoicemakerModule $invoicemaker */
		$invoicemaker = App::module('bixie/invoicemaker');

		if (!$invoice = Invoice::byInvoiceNumber($invoice_number)) {
			App::abort(404, __('Invoice not found'));
		}

		if (!$invoicemaker->checkDownloadKey($invoice, $key)) {
			App::abort(400, __('Key not valid.'));
		}

		$filename = $invoice->getPdfFilename();

		$response = new StreamedResponse();
		$response->setCallback(function () use ($invoicemaker, $invoice) {
			echo str_replace(App::path(), App::url()->getStatic('/', [], UrlGenerator::ABSOLUTE_URL), $invoicemaker->renderHtml($invoice));
		});
		$response->setStatusCode(200);
		$response->headers->set('Content-Type', 'text/html; charset=utf-8');
		$response->headers->set('Content-Disposition', $response->headers->makeDisposition(
			ResponseHeaderBag::DISPOSITION_INLINE,
			$filename,
			mb_convert_encoding($filename, 'ASCII')
		));

		return $response;

	}

}
