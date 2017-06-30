<?php

namespace mik\filegetters;

use GuzzleHttp\Client;
use mik\exceptions\MikErrorException;
use Monolog\Logger;

class CdmCompound extends FileGetter
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
     * Gets a compound item's children pointers.
     */
    public function getChildren($pointer)
    {
        $item_structure = $this->getDocumentStructure($pointer);

        $children_pointers = array();
        if (strlen($item_structure)) {
            $structure = simplexml_load_string($item_structure);
            if ($structure->code == '-2') {
                return $children_pointers;
            } else {
                $pages = $structure->xpath('//page');
                foreach ($pages as $page) {
                    $children_pointers[] = (string) $page->pageptr;
                }
            }
        }

        return $children_pointers;
    }


    /**
     * Gets a compound document's structure.
     */
    public function getDocumentStructure($pointer, $format = 'xml')
    {
        $alias = $this->settings['alias'];
        $ws_url = $this->settings['ws_url'];

        if ($format == 'json') {
            $query_url = $ws_url . 'dmGetCompoundObjectInfo/' . $alias . '/' .  $pointer . '/json';
        }
        if ($format == 'xml') {
            $query_url = $ws_url . 'dmGetCompoundObjectInfo/' . $alias . '/' .  $pointer . '/xml';
        }

        $client = new Client();
        try {
            $response = $client->get(
                $query_url,
                ['timeout' => $this->settings['http_timeout'],
                'connect_timeout' => $this->settings['http_timeout'],
                'verify' => $this->verifyCA]
            );
            $item_structure = $response->getBody();
        } catch (RequestException $e) {
            $this->log->addError("CdmCompound Guzzle error", array('HTTP request error' => $e->getRequest()));
            if ($e->hasResponse()) {
                $this->log->addError("CdmCompound Guzzle error", array('HTTP request response' => $e->getResponse()));
            }
        }

        if ($format == 'json') {
            return json_decode($item_structure, true);
        }
        if ($format == 'xml') {
            return $item_structure;
        }
        return false;
    }
}
