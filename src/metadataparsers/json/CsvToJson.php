<?php
// src/metadataparsers/json/CsvToJson.php

/**
 * Example metadata parser class to demonstrate how to create something other
 * than MODS or DC metadata.
 *
 * Intended for demonstration purposes only, not for production.
 */

namespace mik\metadataparsers\json;

use League\Csv\Reader;

class CsvToJson extends Json
{
    /**
     * @var array $metadatamanipulators - array of metadatamanimpulors from config.
     */
    public $metadatamanipulators;

    /**
     * Create a new Metadata Instance
     *
     * @param $settings
     */
    public function __construct($settings)
    {

        parent::__construct($settings);

        $this->fetcher = new \mik\fetchers\Csv($settings);
        $this->record_key = $this->fetcher->record_key;

        if (isset($this->settings['MANIPULATORS']['metadatamanipulators'])) {
            $this->metadatamanipulators = $this->settings['MANIPULATORS']['metadatamanipulators'];
        } else {
            $this->metadatamanipulators = null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createJson($objectInfo)
    {
        $record_key_column = $this->record_key;
        $record_key = $objectInfo->$record_key_column;

        foreach ($objectInfo as $label => &$value) {
            if (isset($this->metadatamanipulators)) {
                $value = $this->applyMetadatamanipulators($value, $record_key, $label);
            }
        }

        $json = json_encode($objectInfo);
        return $json;
    }

    /**
     * {@inheritdoc}
     *
     * Returns the serialized JSON metadata as a string.
     */
    public function metadata($record_key)
    {
        $objectInfo = $this->fetcher->getItemInfo($record_key);
        $metadata = $this->createJson($objectInfo);
        return $metadata;
    }

    /**
     * Applies metadatamanipulators listed in the config to provided XML snippet.
     *
     * @param string $value
     *     The CSV field value.
     * @param string $record_key
     *     The item's record key value.
     * @param string $field_name
     *     The CSV field name.
     *
     * @return string
     *     The field value after all manipulators have been applied to it.
     */
    private function applyMetadatamanipulators($value, $record_key, $field_name)
    {
        foreach ($this->metadatamanipulators as $metadatamanipulator) {
            $metadatamanipulatorClassAndParams = explode('|', $metadatamanipulator);
            $metadatamanipulatorClassName = array_shift($metadatamanipulatorClassAndParams);
            $manipulatorParams = $metadatamanipulatorClassAndParams;
            $metdataManipulatorClass = 'mik\\metadatamanipulators\\' . $metadatamanipulatorClassName;
            $metadatamanipulator = new $metdataManipulatorClass($this->settings,
                $manipulatorParams,
                $record_key,
                $field_name
            );
            $value = $metadatamanipulator->manipulate($value);
        }

        return $value;
    }
}
