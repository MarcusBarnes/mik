<?php
// src/metadataparsers/MetadataParser.php

namespace mik\metadataparsers;
use \Monolog\Logger;

class MetadataParser
{
    /**
     * @var array $settings - configuration settings from confugration class.
     */
    public $settings;
    
    /** 
     * @var string $pathToLog 
     *     Full path to log directory.
     */ 
    public $pathToLog;
    
    /**
     * @var obj $log 
     *    Logger object
     */
    public $log;
    
    /**
     * @var obj $logStreamHandler
     *    Logger stream handler object
     */
    public $logStreamHandler;
    
    /**
     * Create a new Metadata Instance
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        $this->settings = $settings;
        // Add logging to metadata parsers
        $this->pathToLog = $settings['LOGGING']['path_to_log'];
        // Create log channel for metadataparsers
        $this->log = new \Monolog\Logger('metadataparsers');
        $this->logStreamHandler= new \Monolog\Handler\StreamHandler($this->pathToLog, Logger::WARNING);
        $this->log->pushHandler($this->logStreamHandler);
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
