<?php

namespace mik\writers;

class Newspapers extends Writer
{
    /**
     * @var array $settings - configuration settings from confugration class.
     */
    public $settings;
      
    /**
     * Create a new newspaper writer Instance
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        parent::__construct($settings);
    }

    /**
     * Write folders and files.
     */
    public function writePackages() {
        // Create root output folder
        $this->createOutputDirectory();
    }
    
    
    /**
     * Create the output directory specified in the config file.
     */
    public function createOutputDirectory()
    {
        parent::createOutputDirectory();
    }
    
    public function writeMetadataFile($metadata, $path)
    {
        parent::writeMetadataFile($metadata, $path);
    }
    
    /**
    * Friendly welcome
    *
    * @param string $phrase Phrase to return
    *
    * @return string Returns the phrase passed in
    */
    public function echoPhrase($phrase)
    {
        return $phrase . " (from the newspaper writer)\n";
    }
}
