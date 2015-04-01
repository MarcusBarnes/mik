<?php
// src/metadataparsers/dc/Dc.php

namespace mik\metadataparsers\dc;

class Dc
{
    /**
     * @var array $settings - configuration settings from confugration class.
     */
    public $settings;
      
    /**
     * Create a new DC Instance
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
