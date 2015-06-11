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
     * @var array $settings - configuration settings from confugration class.
     */
    public $settings;

    /**
     * Create a new Metadata Instance
     */
    public function __construct()
    {
        //$this->settings = $settings;
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
        $output = $this->breakTopicMetadaOnCharacter($input);

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

        // break topic metadata on ; into seperate topic elements.
        $xml = new \DomDocument();
        $xml->loadxml($xmlsnippet);

        $topicNode = $xml->getElementsByTagName('topic')->item(0);
        $topictext = $topicNode->nodeValue;

        $topics = explode($breakOnCharacter, $topictext);

        // remove old topic node.
        $topicNodeParent = $topicNode->parentNode;
        $topicNode->parentNode->removeChild($topicNode);

        $subjectNode = $xml->getElementsByTagName('subject')->item(0);

        foreach ($topics as $topic) {
            $topic = trim($topic);
            $newtopicElement = $xml->createElement('topic');
            $topictextNode = $xml->createTextNode($topic);
            $newtopicElement->appendChild($topictextNode);
            $subjectNode->appendChild($newtopicElement);
            unset($topictextNode);
            unset($newtopicElement);
        }

        $xmlstring = $xml->saveXML($subjectNode);

        return $xmlstring;

    }
}
