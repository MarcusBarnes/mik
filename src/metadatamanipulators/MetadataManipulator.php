<?php
// src/metadatamanipulators/MetadataManipulator.php

namespace mik\metadatamanipulators;

/**
 * MetadataManipulator (abstract):
 *    Methods related to manipulating metadata (typically in XML snippets).
 *
 *    Extend this abstract class with for specific implemenations.
 *    For example, see metadatamanipulators/FilterModsTopics.php.
 *
 *    Note that methods marked as abstract must be defined in 
 *    the extending class.
 *
 *    Abstract methods:
 *        - manipulate
 */
abstract class MetadataManipulator
{
    /**
     * @var array $settings - configuration settings from configuration class.
     */
    public $settings;

    /**
     * Create a new Metadata Instance
     * @param array $settings configuration settings.
     * @param array $paramsArray array of manipulator paramaters provided in the configuration
     * @param string $record_key the record_key (CONTENTdm pointer, CSV row id)
     */
    public function __construct($settings, $paramsArray, $record_key)
    {
        $this->settings = $settings;
        // 0 is a valid record key; we need to eplicitly type cast it to a string.
        $this->session_file_path = $this->settings['FILE_GETTER']['temp_directory'] .
            DIRECTORY_SEPARATOR . (string) $record_key . '.dat';
    }

    /**
     * General manipulate wrapper method.
     *
     * @param string $input A string, typically an XML snippet to be manipulated.
     *
     * @return string
     *     Manipulated string
     */
    abstract public function manipulate($input);

    /**
     * Write out the "session" data.
     *
     * @param mixed $data The data the metadata manipulator wants
     *    to save between invocations of itself.
     * @param bool $append
     *    A flag indicating whether the data should overwrite the
     *    existing session data (the default) or append to it (true).
     * @return bool
     *    Returns true is file_get_contents succeeds, false if
     *    it fails.
     */
    public function writeSession($data, $append = false) {
        if ($append) {
            if (file_put_contents($this->session_file_path, $data, FILE_APPEND)) {
                return true;
            }
        }
        else {
            if (file_put_contents($this->session_file_path, $data)) {
                return true;
            }
        }
    }

    /**
     * Retrieve the "session" data.
     *
     * @return mixed
     *     The contents of the session file for the current object.
     *     Returns false if file_get_contents() fails.
     */
    public function readSession() {
        return file_get_contents($this->session_file_path);
    }    
}
