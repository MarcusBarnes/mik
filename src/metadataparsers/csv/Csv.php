<?php
// src/metadataparsers/csv/Csv.php

namespace mik\metadataparsers\csv;

use mik\metadataparsers\MetadataParser;

/**
 * Templated metadata parser - Generates CSV metadata.
 */
abstract class Csv extends MetadataParser
{
    public function __construct($settings)
    {
        parent::__construct($settings);
        $fetcherClass = 'mik\\fetchers\\' . $settings['FETCHER']['class'];
        $this->fetcher = new $fetcherClass($settings);

        $this->outputFile = $this->settings['WRITER']['output_file'];

        if (isset($this->settings['MANIPULATORS']['metadatamanipulators'])) {
            $this->metadatamanipulators = $this->settings['MANIPULATORS']['metadatamanipulators'];
        } else {
            $this->metadatamanipulators = null;
        }
    }

}
