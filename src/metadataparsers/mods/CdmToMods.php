<?php
// src/metadataparsers/mods/CdmToMods.php

namespace mik\metadataparsers\mods;

class CdmToMods extends Mods
{
    /**
     *  @var array $CONTENTdmFieldValuesArray array with field values of proper name
     *  as $keys rather than 'nick' keys.
     */
    public $CONTENTdmFieldValuesArray;

    /**
     * @var bool $include_migrated_from_uri
     */
    public $includeMigratedFromUri;

    /**
     * @var string $alias - CONTENTdm collection alias
     */
    public $alias;

    /**
     * @var array $metadatamanipulators - array of metadatamanipulors from config.
     *   array values will be of the form
     *   metadatamanipulator_class_name|param_0|param_1|...|param_n
     */
    public $metadatamanipulators;

    /**
     * @var array $repeatableWrapperElements - array of wrapper elements
     *that can be repeated (not consolidated) set in the config.
     */
    public $repeatableWrapperElements;

    /**
     * @var object $fetcher fetcher object for access to public methods as needed.
     */
    public $fetcher;

    /**
     * Create a new Metadata Instance
     * @param path to CSV file containing the Cdm to Mods mapping info.
     */
    public function __construct($settings)
    {

        parent::__construct($settings);

        $this->fetcher = new \mik\fetchers\Cdm($settings);
        $this->includeMigratedFromUri = $this->settings['METADATA_PARSER']['include_migrated_from_uri'];
        $this->mappingCSVpath = $this->settings['METADATA_PARSER']['mapping_csv_path'];
        $this->wsUrl = $this->settings['METADATA_PARSER']['ws_url'];
        $this->alias = $this->settings['METADATA_PARSER']['alias'];
        if (isset($this->settings['METADATA_PARSER']['repeatable_wrapper_elements'])) {
            $this->repeatableWrapperElements = $this->settings['METADATA_PARSER']['repeatable_wrapper_elements'];
        } else {
            $this->repeatableWrapperElements = array();
        }
        $mappingCSVpath = $this->mappingCSVpath;
        $this->collectionMappingArray =
            $this->getMappingsArray($mappingCSVpath);
        if (isset($this->settings['MANIPULATORS']['metadatamanipulators'])) {
            $this->metadatamanipulators = $this->settings['MANIPULATORS']['metadatamanipulators'];
        } else {
            $this->metadatamanipulators = null;
        }
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
            $name = trim($fieldAttributes['name']);
            $CONTENTdmFieldValuesArray[$name] = $value;
        }
        return $CONTENTdmFieldValuesArray;
    }

    /**
     *  {@inheritdoc}
     */
    public function createModsXML($collectionMappingArray, $objectInfo)
    {
        $CONTENTdmFieldValuesArray = $this->CONTENTdmFieldValuesArray;

        $pointer = $objectInfo['pointer'];

        $modsOpeningTag = sprintf(
            '<mods xmlns="%s" xmlns:mods="%s" xmlns:xsi="%s" xmlns:xlink="%s">',
            MODS::$MODS_NAMESPACE_URI,
            MODS::$MODS_NAMESPACE_URI,
            "http://www.w3.org/2001/XMLSchema-instance",
            "http://www.w3.org/1999/xlink"
        );

        foreach ($collectionMappingArray as $key => $valueArray) {
            if (preg_match('/^#/', $valueArray[0])) {
                continue;
            }
            $CONTENTdmField = $valueArray[0];
            if (isset($CONTENTdmFieldValuesArray[$CONTENTdmField])) {
                $fieldValue = $CONTENTdmFieldValuesArray[$CONTENTdmField];
            } elseif (preg_match("/(null)\d+/i", $key)) {
                // Special source field name for mappings to static snippets.
                $fieldValue = '';
            } else {
                // Log mismatch between mapping file and source fields (e.g., CDM).
                $logMessage = "Mappings file contains a row $CONTENTdmField that ";
                $logMessage .= "is not in source CONTENTdm metadata for this object.";
                $this->log->addWarning($logMessage, array('Source fieldname' => $CONTENTdmField));
                continue;
            }

            if (is_array($fieldValue) && empty($fieldValue)) {
                // The JSON returned was like "key": {}.
                // This appears in the object_info array as "key"=>array().
                // This corresponds to having no value.
                // Set field value to the empty string for use in preg_replace
                $fieldValue = '';
            }

            // Special characters in metadata field values need to be encoded or
            // metadata creation may break.
            $fieldValue = htmlspecialchars($fieldValue, ENT_NOQUOTES|ENT_XML1);
            if (isset($valueArray[1])) {
                $xmlSnippet = trim($valueArray[1]);
            } else {
                // If $valueArray[1] is not set, then there coule be
                // issues with the mappings file or there may be
                // newline in the mappings file.
                $xmlSnippet = '';
            }

            if (!empty($xmlSnippet) & !is_array($fieldValue)) {
                // @ToDo - move into metadatamanipulator
                // check fieldValue for <br> characters.  If present, wrap in fieldValue
                // is cdata section <![CDATA[$fieldValue]]>
                $pattern = '/<br>/';
                $result = preg_match($pattern, $fieldValue);
                if ($result === 1) {
                    $fieldValue = '<![CDATA[' . $fieldValue . ']]>';
                }

                $stringToReplace = '%value%';
                $xmlSnippet = str_replace($stringToReplace, $fieldValue, $xmlSnippet);
                if (isset($this->metadatamanipulators)) {
                    $xmlSnippet = $this->applyMetadatamanipulators($xmlSnippet, $pointer, $CONTENTdmField);
                }
                $modsOpeningTag .= $xmlSnippet;
            } else {
                // Determine if we need to store the CONTENTdm_field as an identifier.
            }
        }

        $includeMigratedFromUri = $this->includeMigratedFromUri;
        $itemId = $pointer;
        $collectionAlias = $this->alias;
        if ($includeMigratedFromUri == true) {
            $CONTENTdmItemUrl = '<identifier type="uri" invalid="yes" ';
            $CONTENTdmItemUrl .= 'displayLabel="Migrated From">';
            $CONTENTdmItemUrl .= 'http://content.lib.sfu.ca/cdm/ref/collection/';
            $CONTENTdmItemUrl .= $collectionAlias. '/id/'. $itemId .'</identifier>';
            $modsOpeningTag .= $CONTENTdmItemUrl;
        }

        $modsString = $modsOpeningTag . '</mods>';

        $modsString = $this->oneParentWrapperElement($modsString);

        $doc = new \DomDocument('1.0');
        $doc->loadXML($modsString, LIBXML_NSCLEAN);
        $doc->formatOutput = true;
        $modsxml = $doc->saveXML();

        return $modsxml;
    }

    /**
     * Creates basic page level mods for compounds items such as newspapers and books.
     *
     * @param string $page_pointer
     *   CONTENTdm page level pointer.
     * @param string $page_title
     *   title for the page.
     * @param string $xmlSnippet
     *   ???
     *
     * @return string
     *   MODS Page level XML.
     */
    public function createPageLevelModsXML(
        $page_pointer,
        $page_title,
        $xmlSnippet = '<extension><CONTENTdmData></CONTENTdmData></extension>'
    ) {

        $modsOpeningTag = sprintf(
            '<mods xmlns="%s" xmlns:mods="%s" xmlns:xsi="%s" xmlns:xlink="%s">',
            Mods::$MODS_NAMESPACE_URI,
            Mods::$MODS_NAMESPACE_URI,
            "http://www.w3.org/2001/XMLSchema-instance",
            "http://www.w3.org/1999/xlink"
        );

        $modsOpeningTag .= '<titleInfo><title>' . $page_title . '</title></titleInfo>';

        $includeMigratedFromUri = $this->includeMigratedFromUri;
        $collectionAlias = $this->alias;
        if ($includeMigratedFromUri == true) {
            $CONTENTdmItemUrl = '<identifier type="uri" invalid="yes" ';
            $CONTENTdmItemUrl .= 'displayLabel="Migrated From">';
            $CONTENTdmItemUrl .= 'http://content.lib.sfu.ca/cdm/ref/collection/';
            $CONTENTdmItemUrl .= $collectionAlias . '/id/'. $page_pointer .'</identifier>';
            $modsOpeningTag .= $CONTENTdmItemUrl;
        }

        if (isset($this->metadatamanipulators)) {
            $xmlSnippet = $this->applyMetadatamanipulators($xmlSnippet, $page_pointer, '');
            $modsOpeningTag .= $xmlSnippet;
        }

        if (in_array('AddUuidToMods', $this->metadatamanipulators)) {
            $xmlSnippet = "<identifier type='uuid'/>";
            // Add the abililty to apply known metadata manipulator when conditionally used?
            $xmlSnippet = $this->applyMetadatamanipulators($xmlSnippet, $page_pointer, '');
            $modsOpeningTag .= $xmlSnippet;
        }

        $modsString = $modsOpeningTag . '</mods>';

        $doc = new \DomDocument('1.0');
        $doc->loadXML($modsString, LIBXML_NSCLEAN);
        $doc->formatOutput = true;
        $modsxml = $doc->saveXML();

        return $modsxml;
    }

    /**
     * Applies metadatamanipulators listed in the config to provided XML snippet.
     * @param string $xmlSnippet
     *     An XML snippet that can be turned into a valid XML document.
     * @param string $record_key
     *     The item's record key value.
     * @param string $field_name
     *     The field name of the current field.
     *
     * @return string
     *     XML snippet as string that whose nodes have been manipulated if applicable.
     */
    private function applyMetadatamanipulators($xmlSnippet, $record_key, $field_name)
    {
        foreach ($this->metadatamanipulators as $metadatamanipulator) {
            $metadatamanipulatorClassAndParams = explode('|', $metadatamanipulator);
            $metadatamanipulatorClassName = array_shift($metadatamanipulatorClassAndParams);
            $manipulatorParams = $metadatamanipulatorClassAndParams;
            $metdataManipulatorClass = 'mik\\metadatamanipulators\\' . $metadatamanipulatorClassName;
            $metadatamanipulator = new $metdataManipulatorClass($this->settings, $manipulatorParams, $record_key);
            $metadatamanipulator->fieldName = $field_name;
            $xmlSnippet = $metadatamanipulator->manipulate($xmlSnippet);
        }

        return $xmlSnippet;
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
        $objectInfo = $this->fetcher->getItemInfo($pointer);
        $objectInfo['pointer'] = $pointer;
        $this->CONTENTdmFieldValuesArray = $this->createCONTENTdmFieldValuesArray($objectInfo);
        $collectionMappingArray = $this->collectionMappingArray;
        $metadata = $this->createModsXML($collectionMappingArray, $objectInfo);
        return $metadata;
    }
}
