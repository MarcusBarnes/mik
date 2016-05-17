<?php
// src/metadatamanipulators/FilterModsnamePart.php

namespace mik\metadatamanipulators;

/**
 * FilterModsnamePart - Takes the MODS namePart element and splits the child node text
 * on the specified character.  The default character is the semicolon ';'.
 */
class FilterModsnamePart extends MetadataManipulator
{
    /**
     * @var string $topLevelNodeName - the name of the top level node of the snippet.
     */
    private $topLevelNodeName;

    /**
     * Create a new Metadata Instance
     */
    public function __construct($settings = null, $paramsArray, $record_key)
    {
        parent::__construct($settings, $paramsArray, $record_key);

        // FilterModsnamePart expects only one parameter.
        if (count($paramsArray) == 1) {
            $this->topLevelNodeName = $paramsArray[0];
        } else {
          // log that the number of parameters does not meet the assumption for
          // for this metadatamanipulator.
        }
    }

    /**
     * General manipulate wrapper method.
     *
     * @param string $input XML snippett to be manipulated.
     *
     * @return string
     *     Manipulated XML snippet
     */
    public function manipulate($input)
    {
        // break namePart metadata on ; into seperate namePart elements.
        $xml = new \DomDocument();
        $xml->loadxml($input);

        $subjectNode = $xml->getElementsByTagName($this->topLevelNodeName)->item(0);

        if(!isset($subjectNode) ) {
            // This metadatamanipulator does not apply to this input.
            // return the $input unmodified.
            $output = $input;

        } else {
            $output = $this->breaknamePartMetadaOnCharacter($input);
        }

        return $output;
    }

    /**
     * Break the MODS namePart element text-node metadata on 
     * the specified character and put into seperate MODS namePart elements.
     *
     * @param string $xmlsnippet The initial MODS namePart element.
     *
     * @param string $breakOnCharacter The charcter break the string.
     *     The default character is the semicolon ';'.
     *
     * @return string
     *     An XML string containing one or more MODS namePart elements.
     */
    public function breaknamePartMetadaOnCharacter($xmlsnippet, $breakOnCharacter = ';')
    {

        // Break namePart metadata on ; into seperate namePart elements.
        $xml = new \DomDocument();
        $xml->loadxml($xmlsnippet, LIBXML_NSCLEAN);

        $namePartNode = $xml->getElementsByTagName('namePart')->item(0);
        
        if (!is_object($namePartNode)) {

            $xmlstring = $xmlsnippet;

        } else {

            $namePartNodeAttrributes = $namePartNode->attributes;
            
            $nameParttext = $namePartNode->nodeValue;
            $nameParts = explode($breakOnCharacter, $nameParttext);

            // remove old namePart node.
            $namePartNodeParent = $namePartNode->parentNode;
            $namePartNode->parentNode->removeChild($namePartNode);

            $subjectNode = $xml->getElementsByTagName($this->topLevelNodeName)->item(0);  

            foreach ($nameParts as $namePart) {
                $namePart = trim($namePart);
                $newnamePartElement = $xml->createElement('namePart');
                foreach($namePartNodeAttrributes as $atttribute){
                    $name = $atttribute->name;
                    $value = $atttribute->value;
                    $newnamePartElement->setAttribute($name, $value);
                }
                $nameParttextNode = $xml->createTextNode($namePart);
                $newnamePartElement->appendChild($nameParttextNode);
                $subjectNode->appendChild($newnamePartElement);
                unset($nameParttextNode);
                unset($newnamePartElement);
            }

            $xmlstring = $xml->saveXML($subjectNode);
        }

        return $xmlstring;

    }
}
