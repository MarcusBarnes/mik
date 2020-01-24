<?php
// src/metadataparsers/templated/Xslt.php

namespace mik\metadataparsers\templated;

use mik\metadataparsers\MetadataParser;
use \Monolog\Logger;

/**
 * Xslt metadata parser - Generates MODS or DC XML by applying a stylesheet to a source XML file.
 */
class Xslt extends MetadataParser
{
    public function __construct($settings)
    {
        parent::__construct($settings);
        $fetcherClass = 'mik\\fetchers\\' . $settings['FETCHER']['class'];
        $this->fetcher = new $fetcherClass($settings);

        $this->xslt_stylesheet_path = realpath($settings['METADATA_PARSER']['stylesheet']);

        if (isset($this->settings['MANIPULATORS']['metadatamanipulators'])) {
            $this->metadatamanipulators = $this->settings['MANIPULATORS']['metadatamanipulators'];
        } else {
            $this->metadatamanipulators = null;
        }
    }

    /**
     * {@inheritdoc}
     *
     *  Returns the output of the template.
     */
    public function metadata($record_key)
    {
        $objectInfo = $this->fetcher->getItemInfo($record_key);
        $metadata = $this->applyXslt($record_key, $objectInfo);
        return $metadata;
    }

    /**
     * Applies metadatamanipulators listed in the config to provided serialized XML document.
     *
     * @param string $record_key
     *   The current item's record_key.
     * @param string $xml
     *     The XML document as it was rendered by the Twig template.
     *
     * @return string
     *     The modified XML document.
     */
    private function applyMetadatamanipulators($record_key, $xml)
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
     * Applies an XSLT transform to an XML string.
     *
     * @param string $record_key
     *   The record key.
     * @param string $input_xml
     *   The input XML file as an XML string.
     *
     * @return string
     *   The transformed XML.
     */
    public function applyXslt($xslt, $input_xml)
    {
        $xslt = file_get_contents(realpath($this->xslt_stylesheet_path));
        try {
            $xsl_doc = new \DOMDocument();
            $xsl_doc->loadXML($xslt);
            $xml_doc = new \DOMDocument();
            $xml_doc->loadXML($input_xml);
            $xslt_proc = new \XSLTProcessor();
            $xslt_proc->importStylesheet($xsl_doc);
            $output_xml = $xslt_proc->transformToXML($xml_doc);
        } catch (exception $e) {
            print $e->getMessage();
            return false;
        }

        if (isset($this->metadatamanipulators)) {
            $modified_xml = $this->applyMetadatamanipulators($record_key, $xml_from_template);
            $output_xml = $modified_xml;
        }

        return $output_xml;
    }
}
