<?php
// src/metadataparsers/mods/Mods.php

namespace mik\metadataparsers\mods;

use League\Csv\Reader;
use mik\metadataparsers\MetadataParser;

abstract class Mods extends MetadataParser
{

    protected static $MODS_NAMESPACE_URI = "http://www.loc.gov/mods/v3";
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

    /**
     * Convert CSV Mappings file contents to an array.
     *
     * @param $mappingCSVpath
     *   File path to the CSV mappings file.
     * @return array
     *   Associative array of the mappings.
     */
    protected function getMappingsArray($mappingCSVpath)
    {

        $filename = $mappingCSVpath;

        $reader = Reader::createFromPath($filename);
        $this->collectionMappingArray = array();
        foreach ($reader as $index => $row) {
            $this->collectionMappingArray[$row[0]] = $row;
        }

        return $this->collectionMappingArray;
    }

    /**
     *  Create MODS XML.
     *
     *  @param array $collectionMappingArray
     *    Collection mappings
     *  @param array $objectInfo
     *    Array of info about the object that the MODS XML will be created for
     * @return string
     *    The MODS XML as a string.
     */
    abstract public function createModsXML($collectionMappingArray, $objectInfo);

    /**
     *  Writes out the serialized MODS XML file.
     *
     *  @param string $modsxml
     *     A MODS XML string.
     *  @param string $outputPath
     *     Destination path for the output file.
     */
    public function outputModsXML($modsxml, $outputPath = '')
    {
        if ($outputPath !='') {
            $filecreationStatus = file_put_contents($outputPath .'/MODS.xml', $modsxml);
            if ($filecreationStatus === false) {
                echo "There was a problem writing the MODS XML to a file.\n";
            } else {
                echo "MODS.XML file created.\n";
            }
        }
    }

    /**
     *  Takes MODS XML string and returns an array the unique element
     *  signature strings of the child elements.
     *
     *  @param string $xmlString An MODS XML string.
     *
     *  @return array of unique child node element signature strings.
     */
    public function getChildNodesFromModsXMLString($xmlString)
    {
        $xml = new \DomDocument();
        $xml->loadXML($xmlString);

        $childNodesElementSignatureArray = array();
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
     * @param $xml object
     *   MODS XML object
     *
     * @param $uniqueChildNodeSignatureArray
     *   An array that lists the unique element
     *   signatures of children of the root MOD element.
     *
     * @return array of XML elemets that are wrapper elements (children of root element).
     */
    public function determineRepeatedWrapperChildElements($xml, $uniqueChildNodeSignatureArray)
    {
        $wrapperDomNodes = array();
        // Grab the elements.
        foreach ($uniqueChildNodeSignatureArray as $nodeSignatureString) {

            // Turn node signature string into parts element name + element attributes.
            $nodeSignature = $this->elementSignatureFromString($nodeSignatureString);

            $nodeName = $nodeSignature[0];
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
     * @param array $wrapperElementArray
     *   An array of wrapper elements.
     *
     * @return array
     *   An array of consolidated wrapper elements of type XML_ELEMENT_NODE
     */
    public function consolidateWrapperElements($wrapperElementArray)
    {
        $consolidatedWrapperElementsArray = array();
        $elementNameTrackingArray = array();
        $elementSignatureTrackingArray = array();
        foreach ($wrapperElementArray as $wrapperElement) {

            $elementName = $wrapperElement->nodeName;
            $elementAttributesMap = array();
            $attributesNodeMap = $wrapperElement->attributes;
            $len = $attributesNodeMap->length;
            for($i = 0 ; $i < $len; ++$i) {
                $attributeItem = $attributesNodeMap->item($i);
                $attributeName = $attributeItem->name;
                $attributeValue = $attributeItem->value;
                $elementAttributesMap[$attributeName] = $attributeValue;
            }
            // Rather than just check element name, check element name
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
    public function signatureToString($elementSignature)
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
    public function oneParentWrapperElement($xmlString)
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

        // Remove repeated wrapper nodes.
        foreach ($wrapperElementArray as $wrapperElement) {
            $deleteThisNode = $wrapperElement;
            if (isset($deleteThisNode->parentNode)) {
                $parentNode = $deleteThisNode->parentNode;
                $parentNode->removeChild($deleteThisNode);
                $xml->saveXML($parentNode);
            }
        }

        // Consolidate nodes with one wrapper.
        $consolidatedRepeatedWrapperElements =
          $this->consolidateWrapperElements($wrapperElementArray);

        // Add nodes back into $xml document.
        $modsElement = $xml->getElementsByTagName('mods')->item(0);
        foreach ($consolidatedRepeatedWrapperElements as $key => $valueArray) {
            $elementSignature = $this->elementSignatureFromString($key);
            $elementName = $elementSignature[0];
            $elementAttributes = $elementSignature[1];

            $wrapperElement = $xml->createElementNS(MODS::MODS_NAMESPACE_URI, $elementName);
            if (!empty($elementAttributes)){
                // Add attributes and values.
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
    public function elementSignatureFromString($elementSignatureString){
            $elementNameAttributesArray = explode('?', $elementSignatureString);
            $elementName = $elementNameAttributesArray[0];
            $elementAttributeKeyValueArray = array();
            if(count($elementNameAttributesArray) > 1){
                // Element has attributes.
                $elementAttributesArray = explode('|', $elementNameAttributesArray[1]);
                // Remove empty, false, or null values from the array.
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
}