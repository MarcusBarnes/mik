<?php

namespace mik\fetchers;

class Fetcher
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
}
