<?php
// src/metadataparsers/dc/OaiToMods.php

namespace mik\metadataparsers\mods;

class OaiToMods extends Mods
{

    /**
     * Create a new Metadata Parser Instance
     */
    public function __construct($settings)
    {
        parent::__construct($settings);
        $this->fetcher = new \mik\fetchers\Oaipmh($settings);

        if (isset($this->settings['MANIPULATORS']['metadatamanipulators'])) {
            $this->metadatamanipulators = $this->settings['MANIPULATORS']['metadatamanipulators'];
        } else {
            $this->metadatamanipulators = null;
        }
    }

    /**
     * Parse the MODS XML out of the raw OAI record.
     *
     * @param array $array
     *   Placeholder array.
     * @param string $objectInfo
     *   The raw OAI record XML.
     *
     * @return string
     *   The MODS XML.
     */
    public function createModsXML($array, $objectInfo)
    {
        $xml_doc = new \DOMDocument();
        $xml_doc->loadXML($objectInfo);
        $xpath = new \DOMXPath($xml_doc);
        $xpath->registerNamespace("oai", "http://www.openarchives.org/OAI/2.0/");
        $xpath->registerNamespace("mods", "http://www.loc.gov/mods/v3");
        $result = $xpath->query('//oai:metadata/*', $xml_doc);
        $mods_xml_nodelist = $result->item(0);
        $mods_xml = $xml_doc->saveXML($mods_xml_nodelist);

        if (!is_null($this->metadatamanipulators)) {
            $mods_xml = $this->applyMetadatamanipulators($mods_xml, $record_key);
        }

        return $mods_xml;
    }

    /**
     * @todo: Loop through the registered manipulators, just like wth Cdm and CSV,
     *        but these manuipulators should apply to the entire XML document,
     *        not snippets.
     *
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
            $metadatamanipulator = new $metdataManipulatorClass($this->settings, $manipulatorParams, $record_key);
            $xmlSnippet = $metadatamanipulator->manipulate($xmlSnippet);
        }
        return $xmlSnippet;
    }

    /**
     * {@inheritdoc}
     */
    public function metadata($record_key)
    {
        $objectInfo = $this->fetcher->getItemInfo($record_key);
        $metadata = $this->createModsXML(array(), $objectInfo);
        return $metadata;
    }
}
