<?php
// src/config/CsvConfig.php

namespace mik\config;

/**
 * Cofiguration setup for CSV metadata with local files
 */
class CsvConfig extends Config
{

    /**
     * Create a new Config Instance
     * @param $configPath - path to configuraiton file.
     */
    public function __construct($configPath)
    {
        // Call Config.php constructor to include $settings and $outputBaseDir.
        parent::__construct($configPath);
    }
}
