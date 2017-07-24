<?php

namespace mik\filegetters;

use mik\exceptions\MikErrorException;
use Monolog\Logger;

class OaipmhXpath extends FileGetter
{
    /**
     * Create a new OAI Single File Fetcher Instance
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        parent::__construct($settings);
        $this->fetcher = new \mik\fetchers\Oaipmh($settings);
        $this->temp_directory = $this->settings['temp_directory'];

        // Set up logger.
        $this->pathToLog = $settings['LOGGING']['path_to_log'];
        $this->log = new \Monolog\Logger('OaipmhXpath filegetter');
        $this->logStreamHandler = new \Monolog\Handler\StreamHandler(
            $this->pathToLog,
            Logger::ERROR
        );
        $this->log->pushHandler($this->logStreamHandler);

        // This XPath expression must return a single element, and the value
        // returned to MIK will be that element's nodeValue.
        $this->xpathExpression = $settings['FILE_GETTER']['xpath_expression'];
    }

    /**
     * Placeholder method needed because it's called in the main loop in mik.
     */
    public function getChildren($record_key)
    {
        return array();
    }

    /**
     * Get the URL for the resource file (PDF, image, etc.) using the specified XPath.
     *
     * @param string $record_key
     *
     * @return string $download_url
     */
    public function getFilePath($record_key)
    {

        // Get the OAI record from the temp directory.
        $raw_metadata_path = $this->settings['temp_directory'] . DIRECTORY_SEPARATOR . $record_key . '.metadata';
        // Parse out the dc:identifier whose value starts with 'http'.
        $dom = new \DOMDocument;
        $xml = file_get_contents($raw_metadata_path);
        $dom->loadXML($xml);
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('oai_dc', 'http://www.openarchives.org/OAI/2.0/oai_dc/');
        $xpath->registerNamespace('dc', 'http://purl.org/dc/elements/1.1/');
        $download_url_elements = $xpath->query($this->xpathExpression);

        // We are expecting only one node.
        if ($download_url_elements->length == 1) {
            $download_url = trim($download_url_elements->item(0)->nodeValue);
            return $download_url;
        } else {
            return false;
        }
    }
}
