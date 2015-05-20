<?php
// src/metadatamanipulators/FilterModsTopic.php

namespace mik\metadatamanipulators;

class FilterModsTopic extends MetadataManipulator
{
    /**
     * @var array $settings - configuration settings from confugration class.
     */
    public $settings;
      
    /**
     * Create a new Metadata Instance
     * @param array $settings configuration settings.
     */
    public function __construct()
    {
        //$this->settings = $settings;
    }

    /**
     * General manipulate wrapper method.
     *
     * @param string $xmlsnippet
     *     XML snippett to be manipulated.
     *
     * @return string
     *     Manipulated XML snippet
     */
    public function manipulate($xmlsnippet){
        $output = $this->breakTopicMetadaOnSemiColon($xmlsnippet);

        return $output;
    }

    public function breakTopicMetadaOnSemiColon($xmlsnippet)
    {
        
        // break topic metadata on ; into seperate topic elements.
        $xml = new \DomDocument();
        $xml->loadxml($xmlsnippet);

        $topicNode = $xml->getElementsByTagName('topic')->item(0);
        $topictext = $topicNode->nodeValue;

        $topics = explode(';', $topictext);

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

    /**
    * Friendly welcome
    *
    * @param string $phrase Phrase to return
    *
    * @return string Returns the phrase passed in
    */
    public function echoPhrase($phrase)
    {
        return $phrase;
    }
}
