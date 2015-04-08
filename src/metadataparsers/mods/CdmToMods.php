<?php
// src/metadataparsers/mods/CdmToMods.php

namespace mik\metadataparsers\mods;

class CdmToMods extends Mods
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
     * @var string $alias - CONTENTdm collection alias
     */
    public $alias;
    
    /**
     * @var array $objectInfo - objects info from CONTENTdm.
     * @ToDo - De-couple ModsMetadata creation from CONTENTdm?
     */
    //public $objectInfo;

    /**
     * Create a new Metadata Instance
     * @param path to CSV file containing the Cdm to Mods mapping info.
     */
    public function __construct($settings)
    {

        parent::__construct($settings);
        //print_r($this->settings);
        $this->includeMigratedFromUri = $this->settings['METADATA_PARSER']['include_migrated_from_uri'];
        $this->mappingCSVpath = $this->settings['METADATA_PARSER']['mapping_csv_path'];
        $this->wsUrl = $this->settings['METADATA_PARSER']['ws_url'];
        $this->alias = $this->settings['METADATA_PARSER']['alias'];
        $mappingCSVpath = $this->mappingCSVpath;
        $this->collectionMappingArray =
            $this->getCDMtoModsMappingArray($mappingCSVpath);
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
            $fieldAttributes = $this->getFieldAttribute($key);
            //echo $key;
            //print_r($fieldAttributes);
            //echo "\n";
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
        echo is_bool($includeMigratedFromUri);
        if ($includeMigratedFromUri === true) {
            $CONTENTdmItemUrl = '<identifier type="uri" invalid="yes" ';
            $CONTENTdmItemUrl .= 'displayLabel="Migrated From">';
            $CONTENTdmItemUrl .= 'http://content.lib.sfu.ca/cdm/ref/collection';
            $CONTENTdmItemUrl .= $collectionAlias. '/id/'. $itemId .'</identifier>';
            $modsOpeningTag .= $CONTENTdmItemUrl;
        }

        $modsString = $modsOpeningTag . '</mods>';
        return $modsString;
        //$doc = new DomDocument('1.0');
        //$doc->loadXML($mods_string);

        //$doc->formatOutput = true;

        //$modsxml = $doc->saveXML();
        
        return $modsxml;
    }

    /**
     * Gets the item's info from CONTENTdm. $alia needs to include the leading '/'.
     */
    public function getItemInfo($pointer)
    {
        $wsUrl = $this->wsUrl;
        $alias = $this->alias;
        $queryUrl = $wsUrl . 'dmGetItemInfo/' . $alias . '/' .
          $pointer . '/json';
        $response = file_get_contents($queryUrl);
        $itemInfo = json_decode($response, true);
        if (is_array($itemInfo)) {
            return $itemInfo;
        } else {
            return false;
        }
    }

    /**
     * Given the value of a field nick (e.g., 'date'), returns an array of field attribute.
     * $attributes is an optional list of field configuration attibutes to return.
     */
    /*
    Array
    (
        [name] => Birth date
        [nick] => date
        [type] => TEXT
        [size] => 0
        [find] => i8
        [req] => 0
        [search] => 1
        [hide] => 0
        [vocdb] =>
        [vocab] => 0
        [dc] => dateb
        [admin] => 0
        [readonly] => 0
    )
    */
    private function getFieldAttribute($nick, $attributes = array())
    {
        $fieldConfig = $this->getCollectionFieldConfig();
        // Loop through every field defined in $field_config.
        for ($i = 0; $i < count($fieldConfig); $i++) {
            // Pick out the field identified by the incoming 'nick' attribute.
            if ($fieldConfig[$i]['nick'] == $nick) {
                // If we only want selected attributes, filter out the ones we don't want.
                if (count($attributes)) {
                    // If we are going to modify this field's config info, we need to make a
                    // copy since $field_config is a global variable.
                    $reducedFieldConfig = $fieldConfig[$i];
                    foreach ($reducedFieldConfig as $key => $value) {
                        if (!in_array($key, $attributes)) {
                            unset($reducedFieldConfig[$key]);
                        }
                    }
                    return $reducedFieldConfig;
                } else {
                    // If we didn't filter out specific attributes, return the whole thing.
                    return $fieldConfig[$i];
                }
            }
        }
    }

    /**
     * Gets the collection's field configuration from CONTENTdm.
     */
    private function getCollectionFieldConfig()
    {
        $wsUrl = $this->wsUrl;
        $alias = $this->alias;
        $query = $wsUrl . 'dmGetCollectionFieldInfo/' . $alias . '/json';
        $json = file_get_contents($query, false, null);
        return json_decode($json, true);
    }

    public function metadata($pointer)
    {
        $objectInfo = $this->getItemInfo($pointer);
        $CONTENTdmFieldValuesArray =
          $this->createCONTENTdmFieldValuesArray($objectInfo);
        $collectionMappingArray = $this->collectionMappingArray;
        $metadata = $this->createModsXML($collectionMappingArray, $CONTENTdmFieldValuesArray);
        return $metadata;
    }
}
