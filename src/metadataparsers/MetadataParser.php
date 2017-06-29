<?php
// src/metadataparsers/MetadataParser.php

namespace mik\metadataparsers;

use \Monolog\Logger;

abstract class MetadataParser
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
     * Gets the metadata for a specific object.
     *
     * @param string $record_key
     *   The object's record key.
     *
     * @return string
     *   The object's metadata.
     */
    abstract public function metadata($record_key);
}
