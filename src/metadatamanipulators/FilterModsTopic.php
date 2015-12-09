<?php
// src/metadatamanipulators/FilterModsTopic.php

namespace mik\metadatamanipulators;

/**
 * FilterModsTopic - Takes the MODS topic element and splits the child node text
 * on the specified character.  The default character is the semicolon ';'.
 */
class FilterModsTopic extends MetadataManipulator
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

        // FilterModsTopic expects only one parameter.
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
        // break topic metadata on ; into seperate topic elements.
        $xml = new \DomDocument();
        $xml->loadxml($input);

        $subjectNode = $xml->getElementsByTagName($this->topLevelNodeName)->item(0);

        if(!isset($subjectNode) ) {
            // This metadatamanipulator does not apply to this input.
            // return the $input unmodified.
            $output = $input;

        } else {
            $output = $this->breakTopicMetadaOnCharacter($input);
        }

        return $output;
    }

    /**
     * Break the MODS topic element text-node metadata on 
     * the specified character and put into seperate MODS topic elements.
     *
     * @param string $xmlsnippet The initial MODS topic element.
     *
     * @param string $breakOnCharacter The charcter break the string.
     *     The default character is the semicolon ';'.
     *
     * @return string
     *     An XML string containing one or more MODS topic elements.
     */
    public function breakTopicMetadaOnCharacter($xmlsnippet, $breakOnCharacter = ';')
    {

        // Break topic metadata on ; into seperate topic elements.
        $xml = new \DomDocument();
        $xml->loadxml($xmlsnippet, LIBXML_NSCLEAN);

        $topicNode = $xml->getElementsByTagName('topic')->item(0);
        
        if (!is_object($topicNode)) {

            $xmlstring = $xmlsnippet;

        } else {

            $topicNodeAttrributes = $topicNode->attributes;
            
            $topictext = $topicNode->nodeValue;
            $topics = explode($breakOnCharacter, $topictext);

            // remove old topic node.
            $topicNodeParent = $topicNode->parentNode;
            $topicNode->parentNode->removeChild($topicNode);

            $subjectNode = $xml->getElementsByTagName($this->topLevelNodeName)->item(0);  

            foreach ($topics as $topic) {
                $topic = trim($topic);
                $newtopicElement = $xml->createElement('topic');
                foreach($topicNodeAttrributes as $atttribute){
                    $name = $atttribute->name;
                    $value = $atttribute->value;
                    $newtopicElement->setAttribute($name, $value);
                }
                $topictextNode = $xml->createTextNode($topic);
                $newtopicElement->appendChild($topictextNode);
                $subjectNode->appendChild($newtopicElement);
                unset($topictextNode);
                unset($newtopicElement);
            }

            $xmlstring = $xml->saveXML($subjectNode);
        }

        return $xmlstring;

    }
}
