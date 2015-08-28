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

        // Set up logger.
        $this->pathToLog = $settings['LOGGING']['path_to_log'];
        $this->log = new \Monolog\Logger('writer');
        $this->logStreamHandler= new \Monolog\Handler\StreamHandler($this->pathToLog, Logger::INFO);
        $this->log->pushHandler($this->logStreamHandler);
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
    abstract function writePackages($metadata, $pages, $record_key);

}
