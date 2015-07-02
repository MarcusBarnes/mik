<?php

namespace mik\filemanipulators;

use \Monolog\Logger;

class ValidateMods extends FileManipulator
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
     * Create a new FileManipulator Instance
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        // This path is relative to mik.
        $this->schema_location = 'extras/scripts/mods-3-5.xsd';
        $this->settings = $settings;
        // Add logging to metadata parsers
        $this->pathToLog = $settings['LOGGING']['path_to_log'];
        // Create log channel for metadataparsers
        $this->log = new \Monolog\Logger('filemanipulators');
        $this->logStreamHandler= new \Monolog\Handler\StreamHandler($this->pathToLog, Logger::INFO);
        $this->log->pushHandler($this->logStreamHandler);
    }

    public function validate($path_to_mods)
    {
        $mods = new \DomDocument('1.0');
        $mods->load($path_to_mods);
        if ($mods->schemaValidate($this->schema_location)) {
            $this->log->addInfo("MODS file validates", array('file' => $path_to_mods));
        }
        else {
            $this->log->addWarning("MODS file does not validate", array('file' => $path_to_mods));
        }
    }
}
