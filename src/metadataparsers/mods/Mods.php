<?php
// src/metadataparsers/mods/Mods.php

namespace mik\metadataparsers\mods;

use mik\metadataparsers\MetadataParser;

abstract class Mods extends MetadataParser
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

    /**
     *  Create MODS XML
     *  @param array $colletionMappyingArray collection mappings
     *  @param array $objectInfo array of info. about the object that the MODS XML will be created for
     */
    abstract public function createModsXML($collectionMappingArray, $objectInfo);

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
