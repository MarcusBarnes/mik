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
     * @return string
     *     Manipulated XML snippet
     */
    public function manipulate($input = '')
    {
        // Define the XML we want to add to the MODS document.
        $now = date("Y-m-d H:i:s");
        $output = '';
        $output .= '<extension><cdmiteminfo source="Exported from CONTNETdm ';
        $output .= $now;
        $output .= '"></cdmiteminfo></extension>';

        return $output;
    }

}
