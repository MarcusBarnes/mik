<?php

namespace mik\filegetters;

use GuzzleHttp\Client;
use mik\exceptions\MikErrorException;
use Monolog\Logger;

class CdmSingleFile extends FileGetter
{
    /**
     * @var string $utilsUrl - CDM utils url.
     */
    public $utilsUrl;

    /**
     * @var string $alias - CDM alias.
     */
    public $alias;

    /**
     * @var array $filegettermanipulators - array of filegettermanipulors from config.
     *   array values will be of the form
     *   filegettermanipulator_class_name|param_0|param_1|...|param_n
     */
    public $filegettermanipulators;

    /**
     * Configurable http timeout.
     * @var int
     */
    private $http_timeout = 60;

    /**
     * Create a new CONTENTdm Fetcher Instance
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        parent::__construct($settings);
        $this->utilsUrl = $this->settings['utils_url'];
        $this->alias = $this->settings['alias'];
        $this->temp_directory = (!isset($this->settings['temp_directory'])) ?
            '/tmp' : $this->settings['temp_directory'];

        if (isset($this->settings['input_directories'])) {
            $this->input_directories = $this->settings['input_directories'];
        } else {
            $this->input_directories = false;
        }

        if (isset($settings['MANIPULATORS']['filegettermanipulators'])) {
            $this->filegettermanipulators = $settings['MANIPULATORS']['filegettermanipulators'];
        } else {
            $this->filegettermanipulators = null;
        }

        if (isset($this->settings['http_timeout'])) {
            // Seconds.
            $this->http_timeout = $this->settings['http_timeout'];
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
        $this->log = new \Monolog\Logger('CdmSingleFile filegetter');
        $this->logStreamHandler = new \Monolog\Handler\StreamHandler(
            $this->pathToLog,
            Logger::ERROR
        );
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
                'timeout' => $this->http_timeout,
                'connect_timeout' => $this->http_timeout,
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
            $this->log->addError("CdmSingleFile Guzzle error", array('HTTP request error' => $e->getRequest()));
            if ($e->hasResponse()) {
                $this->log->addError("CdmSingleFile Guzzle error", array('HTTP request response' => $e->getResponse()));
            }
        }
    }

    /**
     * Retrives the master file (corresponding to the OBJ datastream) for the current object.
     *
     * @param string $pointer
     *  The CONTENTdm pointer of the object.
     *
     * @return mixed
     *  The path to the file, or false.
     */
    public function getMasterFilePath($pointer)
    {
        // Loop through all applicable filegettermanipulators to
        // determine possible locations for the file.
        foreach ($this->filegettermanipulators as $fmanipulator) {
            $filegettermanipulatorClassAndParams = explode('|', $fmanipulator);
            $filegettermanipulatorClassName = array_shift($filegettermanipulatorClassAndParams);
            $manipulatorParams = $filegettermanipulatorClassAndParams;
            $filegetterManipulatorClass = 'mik\\filegettermanipulators\\' . $filegettermanipulatorClassName;
            $filegettermanipulator = new $filegetterManipulatorClass($this->settings, $manipulatorParams, $pointer);
            if ($potentialFilesArray = $filegettermanipulator->getMasterFilePaths()) {
                foreach ($potentialFilesArray as $potentialMasterFilePath) {
                    // Take the path to the first file that exists.
                    if (file_exists($potentialMasterFilePath)) {
                        return $potentialMasterFilePath;
                    }
                }
            }
        }
        // Throw an exception if no master file was found.
        throw new \mik\exceptions\MikErrorException(
            'WARNING',
            'src/filegetters/CdmSingleFile.php',
            __LINE__,
            "No master file found for pointer $pointer",
            1,
            $this->settings
        );
        return false;
    }
}
