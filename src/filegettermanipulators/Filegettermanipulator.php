<?php

namespace mik\filegettermanipulators;

/**
 * @file
 * Filegettermanipulator Abstract class.
 */

abstract class Filegettermanipulator
{

    /**
     * Create a new Filegettermanipulator instance.
     *
     * @param array $settings configuration settings.
     * @param array $paramsArray array of manipulator paramaters provided in the configuration
     * @param string $record_key the record_key (CONTENTdm pointer, CSV row id)
     */
    public function __construct($settings, $paramsArray, $record_key)
    {
        $this->settings = $settings;
        $this->paramsArray = $paramsArray;
        $this->record_key = $record_key;
    }

    /**
     * Get the path to the master file.
     *
     * @return string
     *     A full path to the master file.
     */
    abstract public function getMasterFilePaths();
}
