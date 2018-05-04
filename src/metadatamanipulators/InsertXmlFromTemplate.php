<?php
// src/metadatamanipulators/InsertXmlFromTemplate.php

namespace mik\metadatamanipulators;

use \Twig\Twig;
use \Monolog\Logger;

/**
 * InsertXmlFromTemplate - Generates an XML fragment from a Twig template
 * that can then be inserted into MODS.
 *
 * Applies to all MODS toolchains.
 */
class InsertXmlFromTemplate extends MetadataManipulator
{

    /**
     * Create a new metadata manipulator instance.
     */
    public function __construct($settings, $paramsArray, $record_key)
    {
        parent::__construct($settings, $paramsArray, $record_key);
        $this->record_key = $record_key;

        // Set up logger.
        $this->pathToLog = $this->settings['LOGGING']['path_to_manipulator_log'];
        $this->log = new \Monolog\Logger('config');
        $this->logStreamHandler = new \Monolog\Handler\StreamHandler(
            $this->pathToLog,
            Logger::INFO
        );
        $this->log->pushHandler($this->logStreamHandler);

        if (count($paramsArray) == 2) {
            $this->sourceField = $paramsArray[0];
            $this->templateDirectory = pathinfo($paramsArray[1], PATHINFO_DIRNAME);
            $this->templateFilename = pathinfo($paramsArray[1], PATHINFO_BASENAME);
        } else {
            $this->log->addInfo("InsertXmlFromTemplate", array('Wrong parameter count' => count($paramsArray)));
        }
    }

    /**
     * General manipulate wrapper method.
     *
     * @param string $input An XML snippet to be manipulated.
     *
     * @return string
     *     Manipulated string, or the raw input.
     */
    public function manipulate($input)
    {
        if ($this->fieldName == $this->sourceField) {
            $loader = new \Twig_Loader_Filesystem($this->templateDirectory);
            $twig = new \Twig_Environment($loader);

            $truncate_filter = new \Twig_SimpleFilter('TwigTruncate', 'mik\utilities\MikTwigExtension::TwigTruncate');
            $twig->addFilter($truncate_filter);

            $metadata = $this->getSourceMetadata();
            $xml_from_template = $twig->render($this->templateFilename, (array) $metadata);
            return trim($xml_from_template);
        } else {
            // If the current field is not the one configured to use this
            // manipulator, return it.
            return $input;
        }
    }

    /**
     * Get the source metadata for the current object.
     *
     * @return array|object
     *     The cached metadata as an array (for Cdm) or an object (for CSV)
     *     in both cases comprised of key - value pairs.
     */
    public function getSourceMetadata()
    {
        $raw_metadata_cache_path = $this->settings['FETCHER']['temp_directory'] .
            DIRECTORY_SEPARATOR . $this->record_key . '.metadata';
        $raw_metadata_cache = file_get_contents($raw_metadata_cache_path);

        // Cached metadata for CSV toolchains is a serialized CSV object.
        if ($this->settings['FETCHER']['class'] == 'Csv') {
            return unserialize($raw_metadata_cache);
        }

        // Cached metadata for CDM toolchains is a serialized associative array.
        // If the field is empty, its value is an empty array.
        if ($this->settings['FETCHER']['class'] == 'Cdm') {
            $raw_metadata_array = unserialize($raw_metadata_cache);
            // Check each field and if an array, reassign as an empty string
            // to pass to the template.
            foreach ($raw_metadata_array as $field => &$value) {
                if (is_array($value)) {
                    $value = '';
                }
            }
            return $raw_metadata_array;
        }

        // If we haven't returned at this point, log failure.
        $this->log->addWarning("InsertXmlFromTemplate", array(
            'Record key' => $this->record_key,
            'Unrecognized fetcher class' => $this->settings['FETCHER']['class']));
    }
}
