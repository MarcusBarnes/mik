<?php
// src/metadataparsers/mods/Mods.php

namespace mik\metadataparsers\mods;

use mik\metadataparsers\MetadataParser;

class Mods extends MetadataParser
{
    /**
     * @var array $collectionMappingArray - array containing the source
     * to MODS XML mapping.
     */
    public $collectionMappingArray;

    /**
     *  @var string $mappingCSVpath path to the source to MODS XML CSV file.
     */
    public $mappingCSVpath;

    /**
     * Create a new Metadata Instance
     */
    public function __construct($settings /*, $objectInfo*/)
    {
        // Call Metadata.php contructor
        parent::__construct($settings);
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
            $xmlSnippet = $valueArray[3];

            if (is_array($fieldValue) && empty($fieldValue)) {
                // This corresponds to having no value.
                // Set field value to the empty string for use in preg_replace
                $fieldValue = '';
            }

            if (!empty($xmlSnippet) & !is_array($fieldValue)) {

                $pattern = '/%value%/';
                $xmlSnippet = preg_replace($pattern, $fieldValue, $xmlSnippet);

                $modsOpeningTag .= $xmlSnippet;
            } else {
                // 
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
