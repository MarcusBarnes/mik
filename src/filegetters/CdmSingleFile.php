<?php

namespace mik\filegetters;

use GuzzleHttp\Client;
use mik\exceptions\MikErrorException;
use Monolog\Logger;

class CdmSingleFile extends FileGetter
{
    /**
     * @var array $settings - configuration settings from confugration class.
     */
    public $settings;

    /**
     * @var string $utilsUrl - CDM utils url.
     */
    public $utilsUrl;

    /**
     * @var string $alias - CDM alias.
     */
    public $alias;

    /**
     * Create a new CONTENTdm Fetcher Instance
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        $this->settings = $settings['FILE_GETTER'];
        $this->utilsUrl = $this->settings['utils_url'];
        $this->alias = $this->settings['alias'];
        $this->temp_directory = (!isset($settings['FILE_GETTER']['temp_directory'])) ?
          '/tmp' : $settings['FILE_GETTER']['temp_directory'];

        // Set up logger.
        $this->pathToLog = $settings['LOGGING']['path_to_log'];
        $this->log = new \Monolog\Logger('CdmSingleFile filegetter');
        $this->logStreamHandler = new \Monolog\Handler\StreamHandler($this->pathToLog,
            Logger::ERROR);
        $this->log->pushHandler($this->logStreamHandler);
    }

    /**
     * Placeholder method needed because it's called in the main loop in mik.
     * Single-file objects don't have any children.
     */
    public function getChildren($pointer)
    {
        return array();
    }

    /**
     * Retrives the file from CONTENTdm.
     *
     * @param string $pointer
     *  The CONTENTdm pointer of the object containing the file.
     *
     * @return mixed
     *  The path to the downloaded file, or false.
     */
    public function getFileContent($pointer)
    {
        $temp_file_path = $this->temp_directory . DIRECTORY_SEPARATOR . $this->alias . '_' . $pointer . '.tmp';

        // Retrieve the file associated with the object.
        $get_file_url = $this->utilsUrl .'getfile/collection/' . $this->alias
            . '/id/' . $pointer . '/filename/' . $this->alias . '_' . $pointer;
        // Create a new Guzzle client to fetch the file as a stream,
        // which will allow us to handle large files.
        $client = new Client();
        try {
            $response = $client->get($get_file_url, ['stream' => true,
                'timeout' => $this->settings['http_timeout'],
                'connect_timeout' => $this->settings['http_timeout']]
            );
            $body = $response->getBody();
            while (!$body->eof()) {
                file_put_contents($temp_file_path, $body->read(2048), FILE_APPEND);
            }
            if (file_exists($temp_file_path)) {
                return $temp_file_path;
            }
            else {
                return false;
            }
        }
        catch (RequestException $e) {
            $this->log->addError("CdmSingleFile Guzzle error", array('HTTP request error' => $e->getRequest()));
            if ($e->hasResponse()) {
                $this->log->addError("CdmSingleFile Guzzle error", array('HTTP request response' => $e->getResponse()));
            }
        }
    }
}
