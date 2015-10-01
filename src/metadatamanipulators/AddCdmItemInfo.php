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
     * Create a new Metadata Instance
     */
    public function __construct($settings = null, $paramsArray)
    {
        parent::__construct($settings);
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
        // Grab the MODS so we can get the value of the identifier element,
        // which contains the URL with the alias and pointer.
        $xml = new \DomDocument();
        $xml->loadxml($input);
        // Has not been added to the MODS yet....
        $identifierNode = $xml->getElementsByTagName('identifier')->item(0);

        // Define the XML fragment we want to add to the MODS document.
        $now = date("Y-m-d H:i:s");
        $output = '';
        $output .= '<extension><cdmiteminfo source="Exported from CONTENTdm ';
        $output .= $now;
        $output .= '"><![CDATA[';
        // This is where we'd insert the JSON from a call to dmGetItemInfo
        // specific to the current object. The following is an abbreviated
        // sample of that JSON.
        $output .= '{"title":"Stanley Park, Vancouver, Canada"}';
        $output .= ']]></cdmiteminfo></extension>';

        return $output;
    }

}
