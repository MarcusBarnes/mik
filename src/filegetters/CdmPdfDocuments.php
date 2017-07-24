<?php

namespace mik\filegetters;

use GuzzleHttp\Client;
use mik\exceptions\MikErrorException;
use Monolog\Logger;

class CdmPdfDocuments extends FileGetter
{
    /**
     * @var string $utilsUrl - CDM utils url.
     */
    public $utilsUrl;

    /**
     * @var string $alias - CDM alias
     */
    public $alias;

    /**
     * Create a new CONTENTdm Fetcher Instance
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        parent::__construct($settings);
        $this->utilsUrl = $this->settings['utils_url'];
        $this->alias = $this->settings['alias'];
        $this->temp_directory = (!isset($settings['FILE_GETTER']['temp_directory'])) ?
          '/tmp' : $settings['FILE_GETTER']['temp_directory'];

        if (!isset($this->settings['http_timeout'])) {
            // Seconds.
            $this->settings['http_timeout'] = 60;
        }

        // Default Mac PHP setups may use Apple's Secure Transport
        // rather than OpenSSL, causing issues with CA verification.
        // Allow configuration override of CA verification at users own risk.
        if (isset($settings['SYSTEM']['verify_ca'])) {
            if ($settings['SYSTEM']['verify_ca'] == false) {
                $this->verifyCA = false;
            }
        } else {
            $this->verifyCA = true;
        }

        // Set up logger.
        $this->pathToLog = $settings['LOGGING']['path_to_log'];
        $this->log = new \Monolog\Logger('CdmPhpDocuments filegetter');
        $this->logStreamHandler = new \Monolog\Handler\StreamHandler(
            $this->pathToLog,
            Logger::ERROR
        );
        $this->log->pushHandler($this->logStreamHandler);
    }

    /**
     * Placeholder method needed because it's called in the main loop in mik.
     * PDF documents don't have any children.
     */
    public function getChildren($pointer)
    {
        return array();
    }

    /**
     * Gets a PHP document's structure.
     */
    public function getDocumentStructure($pointer)
    {
        $alias = $this->settings['alias'];
        $ws_url = $this->settings['ws_url'];
        $query_url = $ws_url . 'dmGetCompoundObjectInfo/' . $alias . '/' .  $pointer . '/json';
        $item_structure = file_get_contents($query_url);
        $item_structure = json_decode($item_structure, true);

        return $item_structure;
    }

    /**
     * Retrives the PDF file from CONTENTdm.
     *
     * @param string $pointer
     *  The CONTENTdm pointer of the object containing the PDF file.
     *
     * @return mixed
     *  The path to the downloaded PDF, or false.
     */
    public function getDocumentLevelPDFContent($pointer)
    {
        $document_structure = $this->getDocumentStructure($pointer);

        $temp_file_path = $this->temp_directory . DIRECTORY_SEPARATOR . $this->alias . '_' . $pointer . '.tmp';

        // Retrieve the file associated with the object. In the case of PDF Documents,
        // the file is a single PDF comprised of all the page-level PDFs joined into a
        // single PDF file using the (undocumented) CONTENTdm API call below.
        // Document-PDFs with only one page have a different structure than multiplage documents.
        if (array_key_exists('pagefile', $document_structure['page'])) {
            $get_file_url = $this->utilsUrl .'getdownloaditem/collection/'
                . $this->alias . '/id/' . $pointer . '/type/compoundobject/show/1/cpdtype/document-pdf/filename/'
                . $document_structure['page']['pagefile'] . '/width/0/height/0/mapsto/pdf/filesize/0/title/'
                . urlencode($document_structure['page']['pagetitle']);
        } else {
            $get_file_url = $this->utilsUrl .'getdownloaditem/collection/'
                . $this->alias . '/id/' . $pointer . '/type/compoundobject/show/1/cpdtype/document-pdf/filename/'
                . $document_structure['page'][0]['pagefile'] . '/width/0/height/0/mapsto/pdf/filesize/0/title/'
                . urlencode($document_structure['page'][0]['pagetitle']);
        }
        // Create a new Guzzle client to fetch the PDF as a stream,
        // which will allow us to handle large PDF files.
        $client = new Client();
        try {
            $response = $client->get($get_file_url, ['stream' => true,
                'timeout' => $this->settings['http_timeout'],
                'connect_timeout' => $this->settings['http_timeout'],
                'verify' => $this->verifyCA]);
            $body = $response->getBody();
            while (!$body->eof()) {
                file_put_contents($temp_file_path, $body->read(2048), FILE_APPEND);
            }
            if (file_exists($temp_file_path)) {
                return $temp_file_path;
            } else {
                return false;
            }
        } catch (RequestException $e) {
            $this->log->addError("CdmPhpDocuments Guzzle error", array('HTTP request error' => $e->getRequest()));
            if ($e->hasResponse()) {
                $this->log->addError(
                    "CdmPhpDocuments Guzzle error",
                    array('HTTP request response' => $e->getResponse())
                );
            }
        }
    }
}
