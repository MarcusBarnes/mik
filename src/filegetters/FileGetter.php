<?php

namespace mik\filegetters;

class FileGetter
{
    /**
     * @var array $settings - configuration settings from confugration class.
     */
    public $settings;
      
    /**
     * Create a new Fetcher Instance
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        $this->settings = $settings['FILE_GETTER'];
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
        return "I am a method defined in the parent FileGetter class.\n";
    }
}
