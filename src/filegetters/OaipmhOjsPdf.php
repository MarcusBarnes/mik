<?php

namespace mik\filegetters;

use GuzzleHttp\Client;
use mik\exceptions\MikErrorException;
use Monolog\Logger;

class OaipmhOjsPdf extends FileGetter
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

        if (isset($settings['SYSTEM']['verify_ca'])) {
            if ($settings['SYSTEM']['verify_ca'] == false) {
                $this->verifyCA = false;
            }
        } else {
            $this->verifyCA = true;
        }

        // Set up logger.
        $this->pathToLog = $settings['LOGGING']['path_to_log'];
        $this->log = new \Monolog\Logger('OaipmhOjsPdf filegetter');
        $this->logStreamHandler = new \Monolog\Handler\StreamHandler(
            $this->pathToLog,
            Logger::ERROR
        );
        $this->log->pushHandler($this->logStreamHandler);
    }

    /**
     * Placeholder method needed because it's called in the main loop in mik.
     */
    public function getChildren($record_key)
    {
        return array();
    }

    /**
     * Get the URL for the PDF from OJS by scaping a bunch'o HTML. Got to love OAI-PMH.
     *
     * OJS's DC record includes the URL of the article in the dc:identifer element.
     * We then need to parse the a few web pages to get the PDF download URL.
     *
     * @param string $record_key
     *
     * @return string $path_to_file
     */
    public function getFilePath($identifier)
    {

        // Get the OAI record from the temp directory.
        $raw_metadata_path = $this->settings['temp_directory'] . DIRECTORY_SEPARATOR . $identifier . '.metadata';
        // Parse out the dc:identifier whose value starts with 'http'.
        $dom = new \DOMDocument;
        $xml = file_get_contents($raw_metadata_path);
        $dom->loadXML($xml);
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('oai_dc', 'http://www.openarchives.org/OAI/2.0/oai_dc/');
        $xpath->registerNamespace('dc', 'http://purl.org/dc/elements/1.1/');
        $dc_identifiers = $xpath->query("/record/metadata/oai_dc:dc/dc:identifier");
        if ($dc_identifiers->length > 0) {
            foreach ($dc_identifiers as $identifier) {
                if (preg_match('/^http/', $identifier->nodeValue)) {
                    $article_url = $identifier->nodeValue;
                    break;
                }
            }
        }

        // From the HTML at that location (snippet below), get the value of the <a> tage with the text value "PDF":
        $client = new Client();
        $response = $client->get($article_url, [$this->verifyCA]);
        $body = $response->getBody();

        $dom = new \DOMDocument;
        $dom->loadHTML((string) $body);
        $xpath = new \DOMXPath($dom);
        $file_urls = $xpath->query("//a[@class='file']");
        if ($file_urls->length > 0) {
            foreach ($file_urls as $file_url) {
                if (preg_match('/PDF/', $file_url->nodeValue)) {
                    $pdf_galley_url = $file_url->getAttribute('href');
                    break;
                }
            }
        }

        // From the document at $pdf_galley_url, get the href value from the <a> with id="pdfDownloadLink":
        $client = new Client();
        $response = $client->get($pdf_galley_url);
        $body = $response->getBody();

        $dom = new \DOMDocument;
        $dom->loadHTML((string) $body);
        $xpath = new \DOMXPath($dom);
        $pdf_download_urls = $xpath->query("//a[@id='pdfDownloadLink']");
        $pdf_download_url = $pdf_download_urls->item(0)->getAttribute('href');

        return $pdf_download_url;
    }
}
