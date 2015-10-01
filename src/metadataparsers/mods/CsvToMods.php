<?php
// src/metadataparsers/mods/CdmToMods.php

namespace mik\metadataparsers\mods;

use League\Csv\Reader;

class CsvToMods extends Mods
{
    /**
     * @var array $collectionMappingArray - array containing CSV
     * to MODS XML mapping.
     */
    public $collectionMappingArray;

    /**
     *  @var string $mappingCSVpath path to CSV metadata to MODS XML CSV file.
     */
    public $mappingCSVpath;

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
     * @param path to CSV file containing the Cdm to Mods mapping info.
     */
    public function __construct($settings)
    {

        parent::__construct($settings);

        $this->fetcher = new \mik\fetchers\Csv($settings);

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

    private function getMappingsArray($mappingCSVpath, $numOfFields = 3)
    {

        $filename = $mappingCSVpath;

        $reader = Reader::createFromPath($filename);
        $collectionMappingArray = array();
        foreach ($reader as $index => $row) {
            $collectionMappingArray[$row[0]] = $row;
        }

        return $collectionMappingArray;
    }

    /**
     */
    public function createModsXML($collectionMappingArray, $objectInfo)
    {
        $record_key_column = $this->record_key;
        $record_key = $objectInfo->$record_key_column;
        
        $modsString = '';

        $modsOpeningTag = '<mods xmlns="http://www.loc.gov/mods/v3" ';
        $modsOpeningTag .= 'xmlns:mods="http://www.loc.gov/mods/v3" ';
        $modsOpeningTag .= 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ';
        $modsOpeningTag .= 'xmlns:xlink="http://www.w3.org/1999/xlink">';
        
        foreach ($collectionMappingArray as $field => $fieldMappings) {
            $csvFieldName = $fieldMappings[0];
            if (property_exists($objectInfo, $csvFieldName)) {
                $fieldValue = $objectInfo->$csvFieldName;
            } elseif (preg_match("/(null)\d+/i", $field)) {
                // special source field name for mappings to static snippets
                $fiedlValue = '';
            } else {
                // log mismatch between mapping file and source fields (e.g., CDM)
                $logMessage = "Mappings file contains a row $csvFieldName that ";
                $logMessage .= "is not in source CSV file.";
                $this->log->addWarning($logMessage, array('Source fieldname' => $csvFieldName));
                continue;
            }

            // Special characters in metadata field values need to be encoded or
            // metadata creation may break.
            $fieldValue = htmlspecialchars($fieldValue, ENT_NOQUOTES|ENT_XML1);

            $xmlSnippet = $fieldMappings[1];
            if (!empty($xmlSnippet)) {
                $pattern = '/%value%/';
                $xmlSnippet = preg_replace($pattern, $fieldValue, $xmlSnippet);
                if (isset($this->metadatamanipulators)) {
                    $xmlSnippet = $this->applyMetadatamanipulators($xmlSnippet, $record_key);
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
            } else {
                // Determine if we need to store the CONTENTdm_field as an identifier.
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
     *  Takes MODS XML string and returns an array the names 
     *  of the child elements.
     *  
     *  @param string $xmlString An MODS XML string.
     *
     *  @return array of unique child node names.
     */
    private function getChildNodesFromModsXMLString($xmlString)
    {
        $xml = new \DomDocument();
        $xml->loadXML($xmlString);

        $childNodesNamesArray = array();
        foreach ($xml->documentElement->childNodes as $node) {
            $childNodesNamesArray[] = $node->nodeName;
        }

        $returnArray = array_unique($childNodesNamesArray);

        return $returnArray;
    }

    /**
     * Determine which child elements of MODS root element are wrapper elements:
     *  1) They have child elements of type XML_ELEMENT_NODE (Value: 1)
     * 
     * @param $xml object MODS XML object
     * 
     * @param $uniqueChildNodeNamesArray an array that lists the unique names of elements
     *    that are children of the root MOD element.
     * 
     * @return arrry of XML elemets that are wrapper elements (children of root element).
     */
    private function determineRepeatedWrapperChildElements($xml, $uniqueChildNodeNamesArray)
    {
        $wrapperDomNodes = array();
        // Grab the elements
        foreach ($uniqueChildNodeNamesArray as $nodeName) {
            //DOMNodeList
            $nodeListObj = $xml->getElementsByTagName($nodeName);
            if ($nodeListObj->length >= 2 && !in_array($nodeName, $this->repeatableWrapperElements)) {
                foreach ($nodeListObj as $node) {
                    if ($node->hasChildNodes() && !$node->hasAttributes()) {
                        foreach ($node->childNodes as $childNode) {
                            if ($childNode->nodeType == XML_ELEMENT_NODE) {
                                $wrapperDomNodes[] = $node;
                            }
                        }
                    }
                }
            }
        }

        return $wrapperDomNodes;

    }

    /**
     * If a (parent) wrapper element more than once, consolidate the child nodes
     * into only one parent wrapper element and return the consolidated elements.
     *
     * @param array $wrapperElementArray An array of wrapper elements.
     *
     * @return array An array of consolidated wrapper elements of type XML_ELEMENT_NODE 
     */
    private function consolidateWrapperElements($wrapperElementArray)
    {
        $consolidatedWrapperElementsArray = array();
        $elementNameTrackingArray = array();
        foreach ($wrapperElementArray as $wrapperElement) {
            if ($wrapperElement->hasAttributes()) {
                // if the wrapper element has attributes, we will
                // not consolidate and repeat it.
                $consolidatedWrapperElementsArray[] = $wrapperElement;
            } else {
                $elementName = $wrapperElement->nodeName;
                if (array_key_exists($elementName, $elementNameTrackingArray)) {
                    // $elementName is already in the tracking array
                    // push the element's childnodes to the end of the array.
                    array_push($elementNameTrackingArray[$elementName], $wrapperElement->childNodes);
                } else {
                    // $elementName is not in the tracking array, add it.
                    $elementNameTrackingArray[$elementName] = array($wrapperElement->childNodes);
                }
            }
        }

        return $elementNameTrackingArray;
    }

    /**
     * Checks an XML string for common parent wrapper elements 
     * and uses only one as appropriate.
     * 
     * @param string $modsXML 
     *     An XML snippet that can be turned into a valid XML document.
     *
     * @return string
     *     An XML snippet that can be turned into a valid XML docuement
     *     and which can be validated successfully against an XML schema
     *     such as MODS.
     */
    private function oneParentWrapperElement($xmlString)
    {

        $xml = new \DomDocument();
        $xml->loadXML($xmlString);

        // Unique names of element nodes that are children of MODS root element.
        $uniqueChildNodeNamesArray = $this->getChildNodesFromModsXMLString($xmlString);

        // Determine which child elements of MODS root element are wrapper elements:
        //  1) They have child elements of type XML_ELEMENT_NODE (Value: 1)
        $wrapperElementArray =
          $this->determineRepeatedWrapperChildElements($xml, $uniqueChildNodeNamesArray);

        // @ToDo: Verify that wrapper elements don't have different attributes.

        // remove repeated wrapper nodes.
        foreach ($wrapperElementArray as $wrapperElement) {
            $nodeName = $wrapperElement->nodeName;
            $deleteThisNode = $xml->getElementsByTagName($nodeName)->item('0');
            if(is_object($deleteThisNode) ) {
                $parentNode = $deleteThisNode->parentNode;
                $parentNode->removeChild($deleteThisNode);
                $xml->saveXML($parentNode);
            }
        }

        // consolidate nodes with one wrapper
        $consolidatedRepeatedWrapperElements =
          $this->consolidateWrapperElements($wrapperElementArray);

        // Add nodes back into $xml document.
        $modsElement = $xml->getElementsByTagName('mods')->item(0);
        foreach ($consolidatedRepeatedWrapperElements as $key => $valueArray) {
            //$wrapperElement = $xml->createElement($key);
            $wrapperElement = $xml->createElementNS('http://www.loc.gov/mods/v3', $key);
            foreach ($valueArray as $nodes) {
                foreach ($nodes as $node) {
                    $wrapperElement->appendChild($node);
                }
            }
            $modsElement->appendChild($wrapperElement);
        }

        $xmlString = $xml->saveXML();
        return $xmlString;

    }

    /**
     * Applies metadatamanipulators listed in the config to provided XML snippet.
     * @param string $xmlSnippet 
     *     An XML snippet that can be turned into a valid XML document.
     * @return string
     *     XML snippet as string that whose nodes have been manipulated if applicable.
     */
    private function applyMetadatamanipulators($xmlSnippet, $record_key)
    {
        foreach ($this->metadatamanipulators as $metadatamanipulator) {
            $metadatamanipulatorClassAndParams = explode('|', $metadatamanipulator);
            $metadatamanipulatorClassName = array_shift($metadatamanipulatorClassAndParams);
            $manipulatorParams = $metadatamanipulatorClassAndParams;
            $metdataManipulatorClass = 'mik\\metadatamanipulators\\' . $metadatamanipulatorClassName;
            $metadatamanipulator = new $metdataManipulatorClass($this->settings, $manipulatorParams,  $record_key);
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
