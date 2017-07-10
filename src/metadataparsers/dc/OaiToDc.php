<?php
// src/metadataparsers/dc/OaiToDc.php

namespace mik\metadataparsers\dc;

class OaiToDc extends Dc
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
     * Pass the OAI record and through the configured XSLT.
     */
    public function createDcXML($MappingArray, $objectInfo)
    {
        $xml_doc = new \DOMDocument();
        $xml_doc->loadXML($objectInfo);
        $xpath = new \DOMXPath($xml_doc);
        $xpath->registerNamespace("oai", "http://www.openarchives.org/OAI/2.0/");
        $xpath->registerNamespace("oai_dc", "http://www.openarchives.org/OAI/2.0/oai_dc/");
        $result = $xpath->query('//oai:metadata/*', $xml_doc);
        $dc_xml_nodelist = $result->item(0);
        $dc_xml = $xml_doc->saveXML($dc_xml_nodelist);

        if (!is_null($this->metadatamanipulators)) {
            $dc_xml = $this->applyMetadatamanipulators($dc_xml, $record_key);
        }

        return $dc_xml;
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
        $metadata = $this->createDcXML(array(), $objectInfo);
        return $metadata;
    }
}
