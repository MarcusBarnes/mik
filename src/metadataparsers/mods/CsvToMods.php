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
                if (!strlen($fieldValue)) {
                    continue;
                }
            } elseif (preg_match("/(null)\d+/i", $field)) {
                // Special source field name for mappings to static snippets
                $fiedlValue = '';
            } else {
                // Log mismatch between mapping file and source fields (e.g., CDM)
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
     *  Takes MODS XML string and returns an array the unique element 
     *  signature strings of the child elements.
     *
     *  @param string $xmlString An MODS XML string.
     *
     *  @return array of unique child node element signature strings.
     */
    private function getChildNodesFromModsXMLString($xmlString)
    {
        $xml = new \DomDocument();
        $xml->loadXML($xmlString);

        //$elementSignature = array($elementName, $elementAttributesMap);
        //$this->signatureToString($elementSignature); 


        $childNodesElementSignatureStringArray = array();
        foreach ($xml->documentElement->childNodes as $node) {
            $elementName = $node->nodeName;
            
            $elementAttributesMap = array();
            $attributesNodeMap = $node->attributes;
            $len = $attributesNodeMap->length;
            for($i = 0 ; $i < $len; ++$i) {
                $attributeItem = $attributesNodeMap->item($i);
                $attributeName = $attributeItem->name;
                $attributeValue = $attributeItem->value;
                $elementAttributesMap[$attributeName] = $attributeValue;
            }            
            $elementSignature = array($elementName, $elementAttributesMap);
            
            $childNodesElementSignatureArray[] = $this->signatureToString($elementSignature);
        }

        $returnArray = array_unique($childNodesElementSignatureArray);

        return $returnArray;
    }

    /**
     * Determine which child elements of MODS root element are wrapper elements:
     *  1) They have child elements of type XML_ELEMENT_NODE (Value: 1)
     *
     * @param $xml object MODS XML object
     *
     * @param $uniqueChildNodeSignatureArray an array that lists the unique element 
     * signatures of children of the root MOD element.
     *
     * @return arrry of XML elemets that are wrapper elements (children of root element).
     */
    private function determineRepeatedWrapperChildElements($xml, $uniqueChildNodeSignatureArray)
    {
        $wrapperDomNodes = array();
        // Grab the elements
        foreach ($uniqueChildNodeSignatureArray as $nodeSignatureString) {
            
            // turn node signature string into parts element name + element attributes
            $nodeSignature = $this->elementSignatureFromString($nodeSignatureString);
            
            $nodeName = $nodeSignature[0];
            $nodeAttributes = $nodeSignature[1];
            //DOMNodeList
            $nodeListObj = $xml->getElementsByTagName($nodeName);
            if ($nodeListObj->length >= 2 && !in_array($nodeName, $this->repeatableWrapperElements)) {
                
                // The element name appears more than once and 
                // is not set as an allowed repeatable wrapper element.
                foreach ($nodeListObj as $node) {
                    if ($node->hasChildNodes() /*&& !$node->hasAttributes()*/) {
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
        $elementSignatureTrackingArray = array();
        foreach ($wrapperElementArray as $wrapperElement) {
            $name = $wrapperElement->nodeName;
            $attributesNodeMap = $wrapperElement->attributes;
            $length = $attributesNodeMap->length;
            for($i=0; $i < $length; ++$i){            
               $attributeItem = $attributesNodeMap->item($i);
               $attributeName = $attributeItem->name;
               $attributeValue = $attributeItem->value;
            }

            $elementName = $wrapperElement->nodeName;
            // store elements attributes (with values) in hashed array
            // array('attributeName' =? 'attributeValue')
            $elementAttributesMap = array();
            $attributesNodeMap = $wrapperElement->attributes;
            $len = $attributesNodeMap->length;
            for($i = 0 ; $i < $len; ++$i) {
                $attributeItem = $attributesNodeMap->item($i);
                $attributeName = $attributeItem->name;
                $attributeValue = $attributeItem->value;
                $elementAttributesMap[$attributeName] = $attributeValue;
            }
            // dev - rather than just check element name, check element name
            // and element attributes.
            $elementSignature = array($elementName, $elementAttributesMap);
            
            if (in_array($elementSignature, $elementSignatureTrackingArray)) {
                // $elementName is already in the tracking array
                // push the element's childnodes to the end of the array.
                $keyFromSignature = $this->signatureToString($elementSignature); 
                array_push($elementNameTrackingArray[$keyFromSignature], $wrapperElement->childNodes);
            } else {
                // $elementSignature is not in the tracking array, add it.
                array_push($elementSignatureTrackingArray, $elementSignature);

                $keyFromSignature = $this->signatureToString($elementSignature);   
                 // $elementName is not in the tracking array, add it.
                $elementNameTrackingArray[$keyFromSignature] = array($wrapperElement->childNodes);
            }
        }

        return $elementNameTrackingArray;
    }


    /**
     * Turns an signature to a string for use 
     */
    private function signatureToString($elementSignature)
    {   

        $signatureString = $elementSignature[0];

        $attributesKeyValuesArray = $elementSignature[1];
        
        if(count($attributesKeyValuesArray) > 0){

            $signatureString .=  "?";

            foreach($attributesKeyValuesArray as $key => $value) {

               $signatureString .= $key . "=" . $value . "|";
            }
        }
        
        return $signatureString;  
        
    }

    /**
     * Checks an XML string for common parent wrapper elements
     * and uses only one as appropriate.
     *
     * @param string $xmlString
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
        // array returned is is the list of unique child element signatures 
        // (that is, keeps track of attributes)
        $uniqueChildNodeSignatureArray = $this->getChildNodesFromModsXMLString($xmlString);

        // Determine which child elements of MODS root element are wrapper elements:
        //  1) They have child elements of type XML_ELEMENT_NODE (Value: 1)
        $wrapperElementArray =
          $this->determineRepeatedWrapperChildElements($xml, $uniqueChildNodeSignatureArray);

        // remove repeated wrapper nodes.        
        foreach ($wrapperElementArray as $wrapperElement) {
            $deleteThisNode = $wrapperElement;
            if (isset($deleteThisNode->parentNode)) {
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
            // $elementSignature array(elementName, array(attributeName=>attributeKey))
            $elementSignature = $this->elementSignatureFromString($key);
            $elementName = $elementSignature[0];
            $elementAttributes = $elementSignature[1];

            //$wrapperElement = $xml->createElement($key);
            $wrapperElement = $xml->createElementNS('http://www.loc.gov/mods/v3', $elementName);
            if(!empty($elementAttributes)){
                // add attributes and values
                foreach($elementAttributes as $key => $value) {
                    $wrapperElement->setAttribute($key, $value);
                }

            }

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
     * Turn elementSignatureString into element name and attributes (with values)
     * @param string $elementSignatureString example elementName?attribute0=value0|attribute1=value1|attribute2=value2
     * @return array [elementNmae, array(attribute0=>value0, attribute1=value1, attribute2=value2, )]
     */
    private function elementSignatureFromString($elementSignatureString){

            $elementNameAttributesArray = explode('?', $elementSignatureString);

            $elementName = $elementNameAttributesArray[0];
            
            $elementAttributeKeyValueArray = array();
            if(count($elementNameAttributesArray) > 1){
                // element has attributes.
                $elementAttributesArray = explode('|', $elementNameAttributesArray[1]);
                // remove empty, false, or null values from the array.
                $elementAttributesArray = array_filter($elementAttributesArray);
                foreach($elementAttributesArray as $attributeValue){
                    $attributeValuePair = explode('=', $attributeValue);
                    $attributeName = $attributeValuePair[0];
                    $attributeValue = $attributeValuePair[1];
                    $elementAttributeKeyValueArray[$attributeName] = $attributeValue;

                }
            }
             
            return array($elementName, $elementAttributeKeyValueArray);
    
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
