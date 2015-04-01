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
        $this->settings = $settings['FETCHER'];
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
        return $phrase;
    }

    /**
    * A test method.
    *
    * @return string Returns a message.
    */
    public function testMethod()
    {
        return "I am a method defined in the parent Fetcher class.\n";
    }
}
