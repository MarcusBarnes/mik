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

        $identifiers = $xml_doc->getElementsByTagNameNS('http://www.openarchives.org/OAI/2.0/', 'identifier');
        $record_key = urlencode($identifiers->item(0)->nodeValue);

        if (!is_null($this->metadatamanipulators)) {
            $dc_xml = $this->applyMetadatamanipulators($dc_xml, $record_key);
        }

        return $dc_xml;
    }

    /**
     * Applies metadatamanipulators listed in the config to provided serialized XML document.
     *
     * @param string $xml
     *     The XML document as it was rendered by the Twig template.
     * @param string $record_key
     *   The current item's record_key.
     *
     * @return string
     *     The modified XML document.
     */
    private function applyMetadatamanipulators($xml, $record_key)
    {
        foreach ($this->metadatamanipulators as $metadatamanipulator) {
            $metadatamanipulatorClassAndParams = explode('|', $metadatamanipulator);
            $metadatamanipulatorClassName = array_shift($metadatamanipulatorClassAndParams);
            $manipulatorParams = $metadatamanipulatorClassAndParams;
            $metdataManipulatorClass = 'mik\\metadatamanipulators\\' . $metadatamanipulatorClassName;
            $metadatamanipulator = new $metdataManipulatorClass($this->settings, $manipulatorParams, $record_key);
            $modified_xml = $metadatamanipulator->manipulate($xml);
        }

        return $modified_xml;
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
