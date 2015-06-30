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
     * @var string $metadataFileName - file name for metadata file to be written
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
        if (isset($this->settings['WRITER']['metadata_filename'])) {
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
     *  Write metedata file in the appropriate location.
     *  This method is meant to be overridden in child classes.
     */
    public function writeMetadataFile($metadata, $path)
    {
        $filename = $this->metadataFileName;
        if ($path !='') {
            $filecreationStatus = file_put_contents($path .'/' . $filename, $metadata);
            if ($filecreationStatus === false) {
                echo "There was a problem exporting the metadata to a file.\n";
            } else {
                echo "Exporting metadata file.\n";
            }
        }
    }

    /**
    * A test method.
    *
    * @return string Returns a message.
    */
    public function testMethod()
    {
        return "I am a method defined in the parent Writer class.\n";
    }
}
