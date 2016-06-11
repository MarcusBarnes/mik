<?php

namespace mik\writers;

use GuzzleHttp\Client;
use mik\exceptions\MikErrorException;
use Monolog\Logger;

class OaipmhOjsPdf extends Writer
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
    }

    /**
     * Write folders and files.
     */
    public function writePackages($metadata, $pages, $record_id)
    {
        // Create root output folder
        $this->createOutputDirectory();
        $output_path = $this->outputDirectory . DIRECTORY_SEPARATOR;

        // Retrieve the file associated with the document and write it to the output
        // folder using the filename or record_id identifier
        $source_file_url = $this->fileGetter->getFilePath($record_id);
        $source_file_extension = 'pdf';

        $content_file_path = $output_path . $record_id . '.' . $source_file_extension;
        $metadata_file_path = $output_path . $record_id . '.xml';

        $this->writeMetadataFile($metadata, $metadata_file_path, true);

        // Retrieve the PDF using Guzzle.
        $client = new Client();
        $response = $client->get($source_file_url,
            ['stream' => true,
            'timeout' => $this->httpTimeout,
            'connect_timeout' => $this->httpTimeout]
        );
        $body = $response->getBody();
        while (!$body->eof()) {
            file_put_contents($content_file_path, $body->read(2048), FILE_APPEND);
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
                $this->log->addWarning("There was a problem writing the metadata to a file",
                    array('file' => $path));
            }
        }
    }
    
}
