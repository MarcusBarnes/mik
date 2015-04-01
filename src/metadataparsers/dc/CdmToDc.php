<?php
// src/metadataparsers/dc/CdmToDc.php

namespace mik\metadataparsers\cdmtodc;

class CdmToDc
{
    /**
     * @var array $settings - configuration settings from confugration class.
     */
    public $settings;
      
    /**
     * Create a new CdmToDc Instance
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        $this->settings = $settings;
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
