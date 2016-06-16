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
        $this->xsltPath = $this->settings['METADATA_PARSER']['xslt_path'];

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
        // Pass $objectInfo through XSLT here?
        $xsl_doc = new \DOMDocument();
        $xsl_doc->load($this->xsltPath);
        $xml_doc = new \DOMDocument();
        $xml_doc->loadXML($objectInfo);
        $xslt_proc = new \XSLTProcessor();
        $xslt_proc->importStylesheet($xsl_doc);
        $dc_xml = $xslt_proc->transformToXML($xml_doc);
        
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
        $metadata = $this->createDcXML(array(), $objectInfo);
        return $metadata;
    }
}
