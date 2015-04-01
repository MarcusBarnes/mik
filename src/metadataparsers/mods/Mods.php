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
    public $objectInfo;

    /**
     * Create a new Metadata Instance
     * @param path to CSV file containing the Cdm to Mods mapping info.
     */
    public function __construct($settings /*, $objectInfo*/)
    {
        // Call Metadata.php contructor
        parent::__construct($settings);
        //print_r($this->settings);
        $this->includeMigratedFromUri = 
            $this->settings['METADATA']['include_migrated_from_uri'];
        $this->mappingCSVpath = $this->settings['INPUT']['mapping_csv_path'];
        $mappingCSVpath = $this->mappingCSVpath;
        // $this->collectionMappingArray =
           // $this->getCDMtoModsMappingArray($mappingCSVpath);
        //$this->objectInfo = $objectInfo;

    }

    private function getCDMtoModsMappingArray($mappingCSVpath)
    {
        // @ToDo Properly document an appropriate fields for the CSV file
        // that contains the CONTENTdm to MODS XML mapping.
        // field_names assumes that the csv file has certain fields.
        $fieldNamesArray = array(
          0 => "CONTENTdm_field",
          1 => "content_type",
          2 => "DCTERMS_mapping",
          3 => "language",
          4 => "MODS_mapping",
          5 => "mapping_notes"
        );

        $numOfFields = count($fieldNamesArray);
        $filename = $mappingCSVpath;
        
        $fp = fopen($filename, 'r') or die("Unable to open file.");
        $collectionMappingArray = array();
        while ($csvLine = fgetcsv($fp)) {
            $tempArray = array();
            for ($i = 0; $i < $numOfFields; $i++) {
                 $tempArray[] = $csvLine[$i];
            }
            // Use CONTENTdm_field as Key
            $collectionMappingArray[$tempArray[0]] = $tempArray;
        }

        fclose($fp) or die("Unable to close file.");


        return $collectionMappingArray;
    }

    /**
     *  @param $objectInfo CONTENTdm get_item_info
     */
    private function createCONTENTdmFieldValuesArray($objectInfo)
    {
        // Create array with field values of proper name as $keys rather than 'nick' keys
        $CONTENTdmFieldValuesArray = array();
        foreach ($objectInfo as $key => $value) {
            // $key is the 'nick'
            $fieldAttributes = get_field_attribute($key);
            $name = $fieldAttributes['name'];
            $CONTENTdmFieldValuesArray[$name] = $value;
        }
        return $CONTENTdmFieldValuesArray;
    }

    public function createModsXML($collectionMappingArray, $CONTENTdmFieldValuesArray)
    {
        $modsString = '';

        $modsOpeningTag = '<mods xmlns="http://www.loc.gov/mods/v3" ';
        $modsOpeningTag .= 'xmlns:mods="http://www.loc.gov/mods/v3" ';
        $modsOpeningTag .= 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ';
        $modsOpeningTag .= 'xmlns:xlink="http://www.w3.org/1999/xlink">';
        $devTempArray = array();
        foreach ($collectionMappingArray as $key => $valueArray) {
            $CONTENTdmField = $valueArray[0];
            $fieldValue = $CONTENTdmFieldValuesArray[$CONTENTdmField];
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

        $includeMigratedFromUri = $this->includeMigratedFromUri;
        if ($includeMigratedFromUri === true) {
            $CONTENTdmItemUrl = '<identifier type="uri" invalid="yes" ';
            $CONTENTdmItemUrl .= 'displayLabel="Migrated From">';
            $CONTENTdmItemUrl .= 'http://content.lib.sfu.ca/cdm/ref/collection';
            $CONTENTdmItemUrl .= $collectionAlias. '/id/'. $itemId .'</identifier>';
            $modsOpeningTag .= $CONTENTdmItemUrl;
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
