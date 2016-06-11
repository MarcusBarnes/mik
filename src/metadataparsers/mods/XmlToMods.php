<?php
// src/metadataparsers/mods/XmlToMods.php

namespace mik\metadataparsers\mods;

class XmlToMods extends Mods
{

    /**
     * Create a new Metadata Parser Instance
     */
    public function __construct($settings)
    {
        parent::__construct($settings);
        $this->fetcher = new \mik\fetchers\Oaipmh($settings);
        // $this->record_key = $this->fetcher->record_key;
        $this->xsltPath = $this->settings['METADATA_PARSER']['xslt_path'];

        if (isset($this->settings['MANIPULATORS']['metadatamanipulators'])) {
            $this->metadatamanipulators = $this->settings['MANIPULATORS']['metadatamanipulators'];
        } else {
            $this->metadatamanipulators = null;
        }
    }

    /**
     * @todo: Pick up the OAI record and pass it through the main XSLT.
     */
    public function createModsXML($collectionMappingArray, $objectInfo)
    {
        // @todo: Replace these two lines with XML parser to get identifier?
        // $record_key_column = $this->record_key;
        // $record_key = $objectInfo->$record_key_column;

        // @todo: Pass $objectInfo through XSLT here?
        
        // if (isset($this->metadatamanipulators)) {
        if (!is_null($this->metadatamanipulators)) {
            $mods_xml = $this->applyMetadatamanipulators($mods_xml, $record_key);
        }

        $mods_xml = $objectInfo;
        return $mods_xml;
    }

    /**
     * @todo: Loop through the registered manipulators, just like wth Cdm and CSV,
     *        but these manuipulators should apply to the entire MODS document,
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
        $metadata = $this->createModsXML(array(), $objectInfo);
        return $metadata;
    }
}
