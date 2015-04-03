<?php
// src/config/CsvConfig.php

namespace mik\config;

/**
 * Cofiguration setup for CSV metadata with local files
 */
class CsvConfig extends Config
{

    /**
     * @var Path to the csv file that contains the CONTENTdm to MODS mappings.
     */
    //public $mappingCSVpath;

    /**
     * @var The directory where item-level directories will be created.
     */
    //public $outputBaseDir;

    /**
     * @var Include the migrated from uri 
     * into your generated metadata (e.g., MODS)
     */
    //public $includeMigratedFromUri;

    /**
     * Create a new Config Instance
     * @param $configPath - path to configuraiton file.
     */
    public function __construct($configPath)
    {
        //echo $configPath;
        //$settings = parse_ini_file($configPath, true);
        // Call Config.php constructor to include $settings and $outputBaseDir.
        parent::__construct($configPath);
        //print_r($settings);
        //$this->wsUrl = $this->settings['API']['ws_url'];
        //echo $this->wsUrl . "\n";
        //$this->mappingCSVpath = $this->settings['INPUT']['mapping_csv_path'];
        //echo $this->mappingCSVpath . "\n";
        //$this->outputBaseDir = $this->$settings['OUTPUT']['output_base_dir'];
        //echo $this->outputBaseDir . "\n";
        //$this->includeMigratedFromUri = $this->settings['METADATA']['include_migrated_from_uri'];
        //echo $this->includeMigratedFromUri . "\n";
    }
}
