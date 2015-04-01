<?php
// src/config/Config.php

namespace mik\config;

class Config
{

    /**
     * @var Array that contains settings from parse_ini_file
     */
    public $settings;
    
    /**
     * @var The directory where item-level directories will be created.
     */
    public $outputBaseDir;

    /**
     * Create a new Config Instance
     * @param $configPath - path to configuraiton file.
     */
    public function __construct($configPath)
    {
        //echo $configPath;
        // $settings = parse_ini_file($configPath, true);
        $this->settings = parse_ini_file($configPath, true);
        // $this->settings = $settings;
        // $this->outputBaseDir = $settings['OUTPUT']['output_base_dir'];
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
