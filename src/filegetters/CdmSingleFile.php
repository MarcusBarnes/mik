<?php

namespace mik\filegetters;
use GuzzleHttp\Client;
use mik\exceptions\MikErrorException;

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
     * @var array $filegettermanipulators - array of filegettermanipulors from config.
     *   array values will be of the form
     *   filegettermanipulator_class_name|param_0|param_1|...|param_n
     */
    public $filegettermanipulators;

    /**
     * Create a new CONTENTdm Fetcher Instance
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        $this->settings = $settings;
        $this->utilsUrl = $this->settings['FILE_GETTER']['utils_url'];
        $this->alias = $this->settings['FILE_GETTER']['alias'];
        $this->temp_directory = (!isset($settings['FILE_GETTER']['temp_directory'])) ?
          '/tmp' : $settings['FILE_GETTER']['temp_directory'];
        if (isset($settings['MANIPULATORS']['filegettermanipulators'])) {
            $this->filegettermanipulators = $settings['MANIPULATORS']['filegettermanipulators'];
        } else {
            $this->filegettermanipulators = null;
        }
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
        $response = $client->get($get_file_url, ['stream' => true]);
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
        throw new \mik\exceptions\MikErrorException('WARNING', 'src/filegetters/CdmSingleFile.php', __LINE__, "No master file found for pointer $pointer", 1, $this->settings);
        return false;
    }

}

