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
     * @var array $settings - configuration settings from confugration class.
     */
    public $settings;

    /**
     * Create a new Metadata Instance
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        $this->settings = $settings;
    }

    /**
     * General manipulate wrapper method.
     *
     * @param string $xmlsnippet XML snippet to be manipulated.
     *
     * @return string
     *     Manipulated XML snippet
     */
    abstract public function manipulate($xmlsnippet);

    /**
    * Friendly welcome
    *
    * @param string $phrase Phrase to return
    *
    * @return string Returns the phrase passed in
    */
    public function echoPhrase($phrase)
    {
        return $phrase;
    }
}
