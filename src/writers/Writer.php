<?php

namespace mik\writers;

abstract class Writer
{
    /**
     * @var array $settings - configuration settings from confugration class.
     */
    public $settings;

    /**
     * @var string outputDirectory - output directory (where packages will be
     * written to)
     */
    public $outputDirectory;

    /**
     * @var string $metadataFileName - file name for metadata file to be written.
     */
    public $metadataFileName;

    /**
     * Create a new Writer Instance
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        $this->settings = $settings['WRITER'];
        $this->outputDirectory = $this->settings['output_directory'];
        if (isset($this->settings['metadata_filename'])) {
          $this->metadataFileName = $this->settings['metadata_filename'];
        }
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
