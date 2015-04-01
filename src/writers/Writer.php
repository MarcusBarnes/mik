<?php

namespace mik\writers;

class Writer
{
    /**
     * @var array $settings - configuration settings from confugration class.
     */
    public $settings;
      
    /**
     * Create a new Writer Instance
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        // $this->settings = $settings['WRITER'];
    }

    /**
     * Create the output directory specified in the config file.
     */
    public function createOutputDirectory($output_directory)
    {
      if (!file_exists($output_directory)) {
        mkdir($output_directory, 0777, TRUE);
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
