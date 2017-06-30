<?php

namespace mik\writers;

use \Monolog\Logger;

abstract class Writer
{
    /**
     * @var array $settings - configuration settings from confugration class.
     */
    public $settings;

    /**
     * @var string $outputDirectory - output directory (where packages will be
     * written to)
     */
    public $outputDirectory;

    /**
     * @var string $metadataFileName - file name for metadata file to be written.
     */
    public $metadataFileName;

    /**
     * @var bool $overwrite_metadata_files - Overwrite the metadata file if it exists.
     */
    public $overwrite_metadata_files;

    /**
     * @var bool $overwrite_content_files - Overwrite the content file if it exists.
     */
    public $overwrite_content_files;

    /**
     * @var array $datastreams - array of expected datastreams; empty if no datastreams
     * in the config were set.
     */
    public $datastreams = array();

    /**
     * @var $OBJ_file_extension - the file extension to use for the OBJ datastream.
     * Default to using TIFF for newspaper OBJ files if not stated explicitily in the
     * configuration settings. (See the constructor.)
     */
    public $OBJ_file_extension;

    /**
     * Create a new Writer Instance
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        clearstatcache();
        $this->settings = $settings;
        $this->outputDirectory = $this->settings['WRITER']['output_directory'];
        if (isset($this->settings['WRITER']['metadata_filename'])) {
            $this->metadataFileName = $this->settings['WRITER']['metadata_filename'];
        }

        if (isset($this->settings['WRITER']['datastreams'])) {
            $this->datastreams = $this->settings['WRITER']['datastreams'];
        }

        if (isset($this->settings['WRITER']['skip_obj'])) {
            $this->skip_obj = $this->settings['WRITER']['skip_obj'];
        } else {
            // default flag to false - do not skip OBJ file creation.
            $this->skip_obj = false;
        }

        if (isset($this->settings['WRITER']['OBJ_file_extension'])) {
            $this->OBJ_file_extension = $this->settings['WRITER']['OBJ_file_extension'];
        }

        // Default is to overwrite metadata and content files.
        $this->overwrite_metadata_files = true;
        if (isset($this->settings['WRITER']['overwrite_metadata_files'])) {
            if ($this->settings['WRITER']['overwrite_metadata_files'] == false) {
                $this->overwrite_metadata_files = false;
            }
        }

        $this->overwrite_content_files = true;
        if (isset($this->settings['WRITER']['overwrite_content_files'])) {
            if ($this->settings['WRITER']['overwrite_content_files'] == false) {
                $this->overwrite_content_files = false;
            }
        }

        // Instantiate input validator class. By convention, input validators are named the
        // same as the file getter class, but we provide the option to use custom validators.
        if (isset($settings['FILE_GETTER']['input_validator_class'])) {
            $input_validator_class = $settings['FILE_GETTER']['input_validator_class'];
        } else {
            $input_validator_class = $settings['FILE_GETTER']['class'];
        }
        try {
            $inputValidatorClass = 'mik\\inputvalidators\\' . $input_validator_class;
            if (class_exists($inputValidatorClass)) {
                $this->inputValidator = new $inputValidatorClass($this->settings);
            }
        } catch (Exception $exception) {
            $log->addError(
                'ErrorException',
                array(
                  'message' => 'problem instantiating inputValidatorClass',
                  'details' => $exception
                )
            );
        }

        // Set up logger.
        $this->pathToLog = $settings['LOGGING']['path_to_log'];
        $this->log = new \Monolog\Logger('writer');
        $this->logStreamHandler= new \Monolog\Handler\StreamHandler($this->pathToLog, Logger::INFO);
        $this->log->pushHandler($this->logStreamHandler);

        // Set up problem logger.
        $this->pathToProblemLog = dirname($settings['LOGGING']['path_to_log']) . DIRECTORY_SEPARATOR .
            'problem_records.log';
        $this->problemLog = new \Monolog\Logger('ProblemRecords');
        $this->problemLogStreamHandler= new \Monolog\Handler\StreamHandler($this->pathToProblemLog, Logger::ERROR);
        $this->problemLog->pushHandler($this->problemLogStreamHandler);
    }

    /**
     * Create the output directory specified in the config file.
     */
    public function createOutputDirectory()
    {
        $outputDirectory = $this->outputDirectory;
        if (!file_exists($outputDirectory)) {
            // mkdir returns true if successful; false otherwise.
            $result = mkdir($outputDirectory, 0777, true);
        } else {
            $result = true; // directory already exists.
        }
        return $result;
    }

    /**
     *  Write metedata file to the appropriate location.
     *
     * @param string $metadata
     *   The XML file that is to be written.
     *
     * @param string $path
     *   The absolute path to write the metadata file to.
     */
    abstract public function writeMetadataFile($metadata, $path);

     /**
     * Write folders and files.
     *
     * @param string $metadata
     *   The XML file that is to be written.
     *
     * @param array $pages
     *   An array of page ...
     *
     * @param string $record_key
     *   The unique key for this object.
     *
     */
    abstract public function writePackages($metadata, $pages, $record_key);
}
