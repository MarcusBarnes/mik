<?php

namespace mik\writers;

use GuzzleHttp\Client;
use mik\exceptions\MikErrorException;
use Monolog\Logger;

class Oaipmh extends Writer
{
    /**
     * @var array $settings - configuration settings from confugration class.
     */
    public $settings;

    /**
     * @var object $fetcher - Fetcher registered in .ini file.
     */
    private $fetcher;

    /**
     * @var object File getter registered in .ini file.
     */
    private $fileGetter;

    /**
     * Create a new OAI-PMH writer Instance
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        parent::__construct($settings);
        $this->fetcher = new \mik\fetchers\Oaipmh($settings);
        $fileGetterClass = 'mik\\filegetters\\' . $settings['FILE_GETTER']['class'];
        $this->fileGetter = new $fileGetterClass($settings);
        $this->output_directory = $settings['WRITER']['output_directory'];

        if (isset($this->settings['WRITER']['http_timeout'])) {
            // Seconds.
            $this->httpTimeout = $this->settings['WRITER']['http_timeout'];
        } else {
            $this->httpTimeout = 60;
        }

        if (isset($this->settings['WRITER']['metadata_only'])) {
            // Seconds.
            $this->metadata_only = $this->settings['WRITER']['metadata_only'];
        } else {
            $this->metadata_only = false;
        }

        // Default Mac PHP setups may use Apple's Secure Transport
        // rather than OpenSSL, causing issues with CA verification.
        // Allow configuration override of CA verification at users own risk.
        if (isset($this->settings['SYSTEM']['verify_ca'])) {
            if ($this->settings['SYSTEM']['verify_ca'] == false) {
                $this->verifyCA = false;
            }
        } else {
            $this->verifyCA = true;
        }
    }

    /**
     * Write folders and files.
     */
    public function writePackages($metadata, $pages, $record_id)
    {
        // Create root output folder
        $this->createOutputDirectory();
        $output_path = $this->outputDirectory . DIRECTORY_SEPARATOR;

        $normalized_record_id = $this->normalizeFilename($record_id);
        $metadata_file_path = $output_path . $normalized_record_id . '.xml';
        $this->writeMetadataFile($metadata, $metadata_file_path, true);

        if ($this->metadata_only) {
              return;
        }

        // Retrieve the file associated with the document and write it to the output
        // folder using the filename or record_id identifier
        $source_file_url = $this->fileGetter->getFilePath($record_id);
        // Retrieve the PDF, etc. using Guzzle.
        if ($source_file_url) {
            $client = new Client();
            $response = $client->get(
                $source_file_url,
                ['stream' => true,
                'timeout' => $this->httpTimeout,
                'connect_timeout' => $this->httpTimeout,
                'verify' => $this->verifyCA]
            );

            // Lazy MimeType => extension mapping: use the last part of the MimeType.
            $content_types = $response->getHeader('Content-Type');
            list($type, $extension) = explode('/', $content_types[0]);
            $extension = preg_replace('/;.*$/', '', $extension);

            $content_file_path = $output_path . $normalized_record_id . '.' . $extension;

            $body = $response->getBody();
            while (!$body->eof()) {
                file_put_contents($content_file_path, $body->read(2048), FILE_APPEND);
            }
        } else {
            $this->log->addWarning(
                "No content file found in OAI-PMH record",
                array('record' => $record_id)
            );
        }
    }

    public function writeMetadataFile($metadata, $path, $overwrite = true)
    {
        // Add XML decleration
        $doc = new \DomDocument('1.0');
        $doc->loadXML($metadata);
        $doc->formatOutput = true;
        $metadata = $doc->saveXML();

        if ($path !='') {
            $fileCreationStatus = file_put_contents($path, $metadata);
            if ($fileCreationStatus === false) {
                $this->log->addWarning(
                    "There was a problem writing the metadata to a file",
                    array('file' => $path)
                );
            }
        }
    }

    /**
     * Convert %3A (:) in filenames into underscores (_).
     */
    public function normalizeFilename($string)
    {
        $string = urldecode($string);
        $string = preg_replace('/:/', '_', $string);
        return $string;
    }
}
