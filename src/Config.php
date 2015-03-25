<?php
// src/Config.php

namespace mik;

class Config
{
    /**
    * Create a new Config Instance
    */
    public function __construct()
    {
       // constructor body
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
