<?php
// src/metadatamanipulators/FixModsNames.php

namespace mik\metadatamanipulators;

use \Monolog\Logger;

/**
 * SplitModsNames - Takes the MODS namePart element and splits out the names
 * on the specified character (the default character is the semicolon ';'),
 * then splits each name into namePart elements of type 'family' and 'given'
 * based on order.
 */
class FixModsNames extends MetadataManipulator
{
    /**
     * @var array $settings - configuration settings from confugration class.
     */
    public $settings;

    /**
     * @var string $topLevelNodeName - the name of the top level node of the snippet.
     */
    
    private $topLevelNodeName;
    
    /**
     * Create a new Metadata Manipulator Instance
     */
    public function __construct($settings, $paramsArray)
    {
        parent::__construct($settings);
        // Set up logger.
        $this->pathToLog = $this->settings['LOGGING']['path_to_manipulator_log'];
        $this->log = new \Monolog\Logger('config');
        $this->logStreamHandler = new \Monolog\Handler\StreamHandler($this->pathToLog,
            Logger::INFO);
        $this->log->pushHandler($this->logStreamHandler);
        $this->breakOnCharacter = $paramsArray[0];
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
        // @todo: Get all <name> elements, and for each one,
        // split the value into <namePart> elements. Within each
        // one, split repeated names on ';'. Account for any existing
        // "type" or other attributes and child "role" elements. Example:
        //
        // <name type="personal">
        //   <namePart>Mark Jordan; Marcus Barnes</namePart>
        //   <role>
        //     <roleTerm authority="marcrelator" type="text">Creator</roleTerm>
        //   </role>
        // </name>
        // <name type="personal">
        //   <namePart>Alfred E. Newman</namePart>
        //     <role>
        //       <roleTerm authority="marcrelator" type="text">Comic relief</roleTerm>
        //     </role>
        // </name>
        //
        // should be converted to:
        //
        // <name type="personal">
        //   <namePart type="given">Mark</namePart>
        //   <namePart type="famiily">Jordan</namePart>
        //   <role>
        //     <roleTerm authority="marcrelator" type="text">Creator</roleTerm>
        //   </role>
        // </name>
        // <name type="personal">
        //   <namePart type="given">Marcus</namePart>
        //   <namePart type="famiily">Barnes</namePart>
        //   <role>
        //     <roleTerm authority="marcrelator" type="text">Creator</roleTerm>
        //   </role>
        // </name>
        // <name type="personal">
        //   <namePart type="given">Alfred E.</namePart>
        //   <namePart type="family">Newman</namePart>
        //     <role>
        //       <roleTerm authority="marcrelator" type="text">Comic relief</roleTerm>
        //     </role>
        // </name>

        $xml = new \DomDocument();
        $xml->loadxml($input);
        $nameNodes = $xml->getElementsByTagName('name');
        if (!isset($nameNodes) || count($nameNodes) === 0 ) {
            // This metadatamanipulator does not apply to this input.
            // Return the $input unmodified.
            $output = $input;
        } else {
            $output = $this->splitNamePart($input, $this->breakOnCharacter);
        }

        return $output;
    }

    /**
     * Break the namePart elements within the MODS name element
     * text-node into separate namePart elements.
     *
     * @param string $xmlsnippet The initial name element.
     *
     * @param string $breakOnCharacter The charcter break the string.
     *     The default character is the semicolon ';'.
     *
     * @return string
     *     An XML string containing one or more MODS name elements.
     */
    public function splitNamePart($xmlSnippet, $breakOnCharacter = ';')
    {
        $xml = new \DomDocument();
        $xml->loadxml($xmlSnippet);
        $nameNodes = $xml->getElementsByTagName('name');
        foreach ($nameNodes as $nameNode) {
            // Get all namePart children.
            $namePartNodes = $nameNode->getElementsByTagName('namePart');
            foreach ($namePartNodes as $namePartNode) {
                // If the namePart node value contains a semicolon, split the value,
                // delete the original namePart node, and add a namePart node for
                // each of the values from the split value. If the value of namePart
                // doesn't contain $breakOnCharacter, just process it.
                // if (preg_match("/$breakOnCharacter/", $namePartNode->nodeValue)) {
                if (preg_match("/;/", $namePartNode->nodeValue)) {
                    // 1) Process each of the names and put them in a array.
                    // 2) Grab all the attributes of the original <namePart> element
                    // (assumption being that each name in its value will get
                    // the same attributes). What do we do with any "type" attributes?
                    // Presumably we will be repopulating those......
                    // 3) Delete the original <namePart> node.
                    // 4) For each name in the array, append a <namePart> node child 
                    // to the original <name> element.
                } else {
                    $processedName = $this->processName($namePartNode->nodeValue);
                    $this->log->addInfo("SplitModsName",
                        array('namePart value' => $namePartNode->nodeValue,
                            'processed value' => $processedName));
                }
            }
        }

        // testing....
        return $xmlSnippet;

        // Break topic metadata on ; into seperate topic elements.
        $xml = new \DomDocument();
        $xml->loadxml($xmlsnippet, LIBXML_NSCLEAN);

        $topicNode = $xml->getElementsByTagName('topic')->item(0);

        if (!is_object($topicNode)) {

            $xmlstring = $xmlsnippet;

        } else {

            $topictext = $topicNode->nodeValue;

            $topics = explode($breakOnCharacter, $topictext);

            // remove old topic node.
            $topicNodeParent = $topicNode->parentNode;
            $topicNode->parentNode->removeChild($topicNode);

            $subjectNode = $xml->getElementsByTagName($this->topLevelNodeName)->item(0);  

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
        }

        return $xmlstring;

    }

    /**
     * Process a name string, i.e., invert it according to some rules.
     *
     * Broken out into its own function so more complex rules can be
     * added later if necessary.
     *
     * @param string $name The name as it appears in the input metadata.
     *
     * @return string
     *     The processed version of the name string.
     */
    private function processName($name) {
        $names = explode(' ', $name);
        $family_name = array_pop($names);
        return $family_name . ', ' . implode(' ', $names);
    }

}
