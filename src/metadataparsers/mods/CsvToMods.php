<?php
// src/metadataparsers/mods/CsvToMods.php

namespace mik\metadataparsers\mods;

class CsvToMods extends Mods
{
    /**
     * @var array $metadatamanipulators - array of metadatamanimpulors from config.
     */
    public $metadatamanipulators;

    /**
     * @var array $repeatableWrapperElements - array of wrapper elements
     *that can be repeated (not consolidated) set in the config.
     */
    public $repeatableWrapperElements;

    /**
     * Create a new Metadata Instance
     * @param path to CSV file containing the CSV to Mods mapping info.
     */
    public function __construct($settings)
    {

        parent::__construct($settings);

        // Fetcher could be Csv or Excel.
        $fetcherClass = 'mik\\fetchers\\' . $settings['FETCHER']['class'];
        $this->fetcher = new $fetcherClass($settings);

        $this->record_key = $this->fetcher->record_key;

        $this->mappingCSVpath = $this->settings['METADATA_PARSER']['mapping_csv_path'];

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
     * {@inheritdoc}
     */
    public function createModsXML($collectionMappingArray, $objectInfo)
    {
        $record_key_column = $this->record_key;
        $record_key = $objectInfo->$record_key_column;

        $modsOpeningTag = sprintf('<mods xmlns="%s" xmlns:mods="%s" xmlns:xsi="%s" xmlns:xlink="%s">',
            MODS::$MODS_NAMESPACE_URI, MODS::$MODS_NAMESPACE_URI, "http://www.w3.org/2001/XMLSchema-instance",
            "http://www.w3.org/1999/xlink");
        //$modsOpeningTag = '<mods xmlns="http://www.loc.gov/mods/v3" ';
        //$modsOpeningTag .= 'xmlns:mods="http://www.loc.gov/mods/v3" ';
        //$modsOpeningTag .= 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ';
        //$modsOpeningTag .= 'xmlns:xlink="http://www.w3.org/1999/xlink">';

        foreach ($collectionMappingArray as $field => $fieldMappings) {
            if (preg_match('/^#/', $fieldMappings[0])) {
              continue;
            }
            $csvFieldName = $fieldMappings[0];
            if (property_exists($objectInfo, $csvFieldName)) {
                $fieldValue = $objectInfo->$csvFieldName;
                if (!strlen($fieldValue)) {
                    continue;
                }
            } elseif (preg_match("/(null)\d+/i", $field)) {
                // Special source field name for mappings to static snippets
                $fieldValue = '';
            } else {
                // Log mismatch between mapping file and source CSV fields.
                $logMessage = "Mappings file contains a row that ";
                $logMessage .= "is not in source CSV row for this object.";
                $this->log->addWarning($logMessage, array('Record key' => $record_key,
                    'Missing field in metadata' => $csvFieldName));
                continue;
            }

            // Special characters in metadata field values need to be encoded or
            // metadata creation may break.
            $fieldValue = htmlspecialchars($fieldValue, ENT_NOQUOTES|ENT_XML1);
            $fieldValue = trim($fieldValue);

            $xmlSnippet = trim($fieldMappings[1]);
            if (!empty($xmlSnippet)) {
                $pattern = '/%value%/';
                $xmlSnippet = preg_replace($pattern, $fieldValue, $xmlSnippet);
                if (isset($this->metadatamanipulators)) {
                    $xmlSnippet = $this->applyMetadatamanipulators($xmlSnippet, $record_key, $csvFieldName);
                }

                $modsOpeningTag .= $xmlSnippet;

            } elseif (!empty($xmlSnippet) & !is_array($fieldValue)) {
                // @ToDo - move into metadatamanipulator
                // check fieldValue for <br> characters.  If present, wrap in fieldValue
                // is cdata section <![CDATA[$fieldValue]]>
                $pattern = '/<br>/';
                $result = preg_match($pattern, $fieldValue);
                if ($result === 1) {
                    $fieldValue = '<![CDATA[' . $fieldValue . ']]>';
                }

                // @ToDo - determine appropriate metadata filters
                $pattern = '/%value%/';
                $xmlSnippet = preg_replace($pattern, $fieldValue, $xmlSnippet);
                $modsOpeningTag .= $xmlSnippet;
            }
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
            $metadatamanipulator = new $metdataManipulatorClass($this->settings, $manipulatorParams,  $record_key);
            $metadatamanipulator->fieldName = $field_name;
            $xmlSnippet = $metadatamanipulator->manipulate($xmlSnippet);
        }

        return $xmlSnippet;
    }

    /**
     */
    public function metadata($record_key)
    {
        $objectInfo = $this->fetcher->getItemInfo($record_key);
        $collectionMappingArray = $this->collectionMappingArray;
        $metadata = $this->createModsXML($collectionMappingArray, $objectInfo);
        return $metadata;
    }
}