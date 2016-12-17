<?php
// src/inputvalidators/MikInputValidator.php

namespace mik\inputvalidators;

use League\Csv\Reader;
use \Monolog\Logger;

class MikInputValidator
{
    /**
     * Create a new MikInputValidator instance.
     *
     * @param array $settings
     *    Associative array containing the toolchain settings.
     */
    // public function __construct($configPath)
    public function __construct($settings)
    {
        // $this->settings = parse_ini_file($configPath, true);
        $this->settings = $settings;

        // Set up logger.
        if (isset($this->settings['LOGGING']['path_to_input_validator_log'])) {
            $this->pathToLog = $this->settings['LOGGING']['path_to_input_validator_log'];
        } else {
            $this->pathToLog = dirname($this->settings['LOGGING']['path_to_log']) .
                DIRECTORY_SEPARATOR . 'input_validator.log';
        }
        $this->log = new \Monolog\Logger('input validator');
        $this->logStreamHandler= new \Monolog\Handler\StreamHandler($this->pathToLog, Logger::INFO);
        $this->log->pushHandler($this->logStreamHandler);

        if (isset($this->settings['FILE_GETTER']['validate_input'])) {
            if ($this->settings['FILE_GETTER']['validate_input'] == false) {
                $this->validateInput = false;
            }
        } else {
            $this->validateInput = true;
        }

        if (isset($this->settings['FILE_GETTER']['validate_input_type'])) {
            if ($this->settings['FILE_GETTER']['validate_input_type'] == 'strict') {
                $this->validateInputType = 'strict';
            }
        } else {
            $this->validateInputType = 'realtime';
        }

        $fileGetterClass = 'mik\\filegetters\\' . $this->settings['FILE_GETTER']['class'];
        $this->fileGetter = new $fileGetterClass($this->settings);
    }

    /**
     * Wrapper function for validating all input packages.
     */
    public function validateAll()
    {
    }

    /**
     * Wrapper function for validating a single input package.
     *
     * @param $record_key string
     *   The package's record key.
     *
     * @param $package_path string
     *   The absolute path to the package's input directory.
     */
    public function validatePackage($record_key, $package_path)
    {
    }

    /**
     * Reads a flat directory.
     */
    public function readDir($path, $dirs_only = false)
    {
        $file_list = array();
        $path = rtrim($path, DIRECTORY_SEPARATOR);
        $pattern = $path . DIRECTORY_SEPARATOR . "*";
        if ($dirs_only) {
            $file_list = glob($pattern, GLOB_ONLYDIR);
        } else {
            $file_list = glob($pattern);
        }

        return $file_list;
    }

    /**
     * Recurses a directory tree.
     */
    public function readDirRecursive($path)
    {
        $file_list = array();
        $directory_iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
        foreach ($directory_iterator as $filepath => $info) {
            $file_list[] = $filepath;
        }
        return $file_list;
    }
}
