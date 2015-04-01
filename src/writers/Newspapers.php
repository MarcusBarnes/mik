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
        $this->settings = $settings['WRITER'];
    }

    /**
     * Create the output directory specified in the config file.
     */
    public function createOutputDirectory()
    {
      if (!file_exists($this->settings['output_directory'])) {
        mkdir($this->settings['output_directory'], 0777, TRUE);
      }
      return $this->settings;
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
