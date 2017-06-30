<?php
// src/config/CdmConfig.php

namespace mik\config;

/**
 *  Configuration for ContentDM source and CDM->MODS mapping in CSV.
 */
class CdmConfig extends Config
{
    /**
     * @var string $wsULR URL to the CONTENTdm web services API
     */
    public $wsUrl;

    /**
     * @var string $mappingCSVpath Path to the csv file that contains
     * the CONTENTdm to MODS mappings.
     */
    public $mappingCSVpath;

    /**
     * @var string $outputBaseDir The directory where item-level directories
     * will be created.
     */
    //public $outputBaseDir;

    /**
     * @var 1/0 $includeMigratedFromUri - Include the migrated from uri
     * into your generated metadata (e.g., MODS)
     */
    public $includeMigratedFromUri;

    /**
     * Create a new Config Instance
     * @param $configPath - path to configuraiton file.
     */
    public function __construct($configPath)
    {
        // Call Config.php constructor to include $settings and $outputBaseDir.
        parent::__construct($configPath);

        $this->wsUrl = $this->settings['API']['ws_url'];

        $this->mappingCSVpath = $this->settings['INPUT']['mapping_csv_path'];

        $this->includeMigratedFromUri = $this->settings['METADATA']['include_migrated_from_uri'];
    }
}
