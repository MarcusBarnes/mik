<?php
// src/metadataparsers/dc/Dc.php

namespace mik\metadataparsers\dc;

use mik\metadataparsers\MetadataParser;

abstract class Dc extends MetadataParser
{
    /**
     * @var array $settings - configuration settings from confugration class.
     */
    public $settings;

    /**
     * Create a new DC Instance
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        $this->settings = $settings;
    }
}
