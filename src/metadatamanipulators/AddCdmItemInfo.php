<?php
// src/metadatamanipulators/AddCdmItemInfo.php

namespace mik\metadatamanipulators;

/**
 * AddCdmItemInfo - Adds the raw (JSON) metadata for an item from CONTENTdm
 * to an <extension> element in the MODS document. This manipulator is
 * probably specific to Simon Fraser University Library's use case.
 */
class AddCdmItemInfo extends MetadataManipulator
{
    /**
     * @var string $record_key - the unique identifier for the metadata
     *    record being manipulated.
     */
    private $record_key;

    /**
     * Create a new Metadata Instance
     */
    public function __construct($settings = null, $paramsArray, $record_key)
    {
        $this->settings = $settings;
        $this->record_key = $record_key;
    }

    /**
     * General manipulate wrapper method.
     *
     *  @param string $input XML snippett to be manipulated.
     *
     * @return string
     *     Manipulated XML snippet
     */
    public function manipulate($input)
    {

        $dom = new \DomDocument();
        $dom->loadxml($input, LIBXML_NSCLEAN);

        $xpath = new \DOMXPath($dom);

        if ($xpath->evaluate('boolean(//extension/cdmiteminfo)')) {
          // return $input;
        }

        $timestamp = date("Y-m-d H:i:s");

        // Retrieve the JSON metadata from a call to dmGetItemInfo specific
        // to the current object.
        $item_info_url = $this->settings['METADATA_PARSER']['ws_url'] .
            'dmGetItemInfo/' . $this->settings['METADATA_PARSER']['alias'] .
            '/' . $this->record_key . '/json';
        $item_info .= file_get_contents($item_info_url);

        // Define the extension element we want to add to the MODS document.
        $extension = $dom->createElement('extension');
        $cdmiteminfo = $dom->createElement('cdmiteminfo');
        $cdata = $dom->createCDATASection('$item_info');
        $cdmiteminfo->appendChild($cdata);
        $now = $dom->createAttribute('source');
        $now->value = 'Exported from CONTENDM ' . $timestamp;
        $cdmiteminfo->appendChild($now);
        $extension->appendChild($cdmiteminfo);
        $dom->appendChild($extension);

        return $dom->saveXML($dom->documentElement);

    }

}
