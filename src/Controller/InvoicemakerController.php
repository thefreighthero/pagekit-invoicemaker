<?php

namespace Bixie\Invoicemaker\Controller;

use Bixie\Invoicemaker\Model\Invoice;
use Pagekit\Application as App;
use Bixie\Invoicemaker\InvoicemakerModule;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * @Access(admin=true)
 */
class InvoicemakerController {

	/**
	 * @Route("/", methods="GET")
	 * @Request({"filter": "array", "page":"int"})
	 */
	public function indexAction ($filter = [], $page = null) {

		/** @var InvoicemakerModule $invoicemaker */
		$invoicemaker = App::module('bixie/invoicemaker');
		return [
			'$view' => [
				'title' => __('Invoices'),
				'name' => 'bixie/invoicemaker/admin/invoices.php'
			],
			'$data' => [
				'groups' => $invoicemaker->getInvoiceGroups(),
                'templates' => $invoicemaker->getTemplates(),
                'statuses' => Invoice::getStatuses(),
				'config' => [
					'filter' => (object) $filter,
					'page' => $page
				]
			]
		];
	}


    /**
     * @Route("/download", methods="GET")
     * @Access("invoicemaker: view invoices")
     * @Request({"filter": "array"})
     * @param array $filter
     * @return StreamedResponse
     */
	public function downloadAction ($filter = []) {
        /** @var InvoicemakerModule $invoicemaker */
        $invoicemaker = App::module('bixie/invoicemaker');
        $filter = array_merge(array_fill_keys(['date_from', 'date_to', 'only_open'], ''), $filter);
        extract($filter, EXTR_SKIP);

        $date_from = new \DateTime($filter['date_from']);
        $date_to = new \DateTime($filter['date_to']);

        $query = Invoice::query();
        if ($date_from) {
            $query->where('created > :date_from', ['date_from' => $date_from]);
        }

        if ($date_to) {
            $query->where('created < :date_to', ['date_to' => $date_to]);
        }

        if (!empty($filter['only_open'])) {
            $query->where('amount - amount_paid > 0');
        }

        /** @var Invoice[] $invoices */
        $invoices = $query->get();

        if (!count($invoices)) {
            App::abort(404, 'No invoices for this date');
        }

        $filename = sprintf('invoices-%s-%s.zip', $date_from->format('Y-m-d'), $date_to->format('Y-m-d'));
        $tempfile = sys_get_temp_dir() . '/' . $filename;

        $zip = new \ZipArchive();
        if ($zip->open($tempfile, \ZipArchive::CREATE) !== true) {
            App::abort(500, 'Unable to write zip');
        }
        foreach ($invoices as $invoice) {
            if ($file = $invoice->pdf_file and $path = $invoicemaker->getPdfPath() . '/' . $file and file_exists($path)) {
                //existing file
                $zip->addFile($path, $invoice->pdf_file);
            } else {
                //generate string
                $zip->addFromString($invoice->getPdfFilename(), $invoicemaker->renderPdfString($invoice));
            }
        }
        $zip->close();

        $response = new StreamedResponse();
        $response->setCallback(function () use ($tempfile) {
            if ($stream = fopen($tempfile, 'rb')) {
                echo stream_get_contents($stream);
                fclose($stream);
                unlink($tempfile);
            }
        });
        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename,
            mb_convert_encoding($filename, 'ASCII')
        ));

        return $response;
	}


	/**
	 * @Access("system: access settings")
	 */
	public function settingsAction () {

		return [
			'$view' => [
				'title' => __('Invoicemaker settings'),
				'name' => 'bixie/invoicemaker/admin/settings.php'
			],
			'$data' => [
				'pdf_templates' => App::module('bixie/invoicemaker')->getPdfTemplates(),
				'config' => App::module('bixie/invoicemaker')->config()
			]
		];
	}


}
