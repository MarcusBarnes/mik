<?php
// src/metadataparsers/mods/Mods.php

namespace mik\metadataparsers\mods;

use mik\metadataparsers\MetadataParser;

class Mods extends MetadataParser
{
    /**
     * @var array $collectionMappingArray - array containing CONTENTdm
     * to MODS XML mapping.
     */
    public $collectionMappingArray;

    /**
     * @var bool $include_migrated_from_uri
     */
    public $includeMigratedFromUri;

    /**
     *  @var string $mappingCSVpath path to CONTENTdm to MODS XML CSV file.
     */
    public $mappingCSVpath;
    
    /**
     * @var array $objectInfo - objects info from CONTENTdm.
     * @ToDo - De-couple ModsMetadata creation from CONTENTdm?
     */
    //public $objectInfo;

    /**
     * Create a new Metadata Instance
     * @param path to CSV file containing the Cdm to Mods mapping info.
     */
    public function __construct($settings /*, $objectInfo*/)
    {
        // Call Metadata.php contructor
        parent::__construct($settings);
        //print_r($this->settings);
        //$this->includeMigratedFromUri = $this->settings['METADATA_PARSER']['include_migrated_from_uri'];
        //$this->mappingCSVpath = $this->settings['METADATA_PARSER']['mapping_csv_path'];
        //$mappingCSVpath = $this->mappingCSVpath;
        // $this->collectionMappingArray =
           // $this->getCDMtoModsMappingArray($mappingCSVpath);
        //$this->objectInfo = $objectInfo;

    }

    private function getMappingsArray($mappingCSVpath)
    {
        return $collectionMappingArray;
    }

    public function createModsXML($collectionMappingArray, $sourceFieldValuesArray, $record_key)
    {
        $modsString = '';

        $modsOpeningTag = '<mods xmlns="http://www.loc.gov/mods/v3" ';
        $modsOpeningTag .= 'xmlns:mods="http://www.loc.gov/mods/v3" ';
        $modsOpeningTag .= 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ';
        $modsOpeningTag .= 'xmlns:xlink="http://www.w3.org/1999/xlink">';
        $devTempArray = array();
        foreach ($collectionMappingArray as $key => $valueArray) {
            $sourceFieldKey = $valueArray[0];
            $fieldValue = $sourceFieldValuesArray[$sourcefieldKey];
            $xmlSnippet = $valueArray[4];

            if (is_array($fieldValue) && empty($fieldValue)) {
                // The JSON returned was like "key": {}.
                // This appears in the object_info array as "key"=>array().
                // This corresponds to having no value.
                // Set field value to the empty string for use in preg_replace
                $fieldValue = '';
            }

            if (!empty($xmlSnippet) & !is_array($fieldValue)) {

                $pattern = '/%value%/';
                $xmlSnippet = preg_replace($pattern, $fieldValue, $xmlSnippet);

                $modsOpeningTag .= $xmlSnippet;
            } else {
                // Determine if we need to store the CONTENTdm_field as an identifier.
            }
        }

        $modsString = $modsOpeningTag . '</mods>';

        $doc = new DomDocument('1.0');
        $doc->loadXML($mods_string);

        $doc->formatOutput = true;

        $modsxml = $doc->saveXML();
        
        return $modsxml;
    }

    public function outputModsXML($modsxml, $outputPath = '')
    {
        /**
         * $modsxml - MODS xml string - required.
         * $outputPath - output path for writing to a file.
         */
        if ($outputPath !='') {
            $filecreationStatus = file_put_contents($outputPath .'/MODS.xml', $modsxml);
            if ($filecreationStatus === false) {
                echo "There was a problem writing the MODS XML to a file.\n";
            } else {
                echo "MODS.XML file created.\n";
            }
        }
    }
}
