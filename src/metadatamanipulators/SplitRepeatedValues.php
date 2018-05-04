<?php
// src/metadatamanipulators/SplitRepeatedValues.php

namespace mik\metadatamanipulators;

use \Monolog\Logger;

/**
 * SplitRepeatedValues - Splits values separated by a delimter and
 * applies the field mapping to each, resulting in repeated MODS elements.
 *
 * Note that it may be necessary to add the "repeatable_wrapper_element"
 * option to your .ini file for elements that have a wrapper; for example,
 * if you are splitting data that will be placed in separate '<name><namePart>'
 * elements, you may need to add 'repeatable_wrapper_element[] = name' to
 * the [METADATA_PARSER] section of your .ini file.
 *
 * Applies to all MODS toolchains.
 */
class SplitRepeatedValues extends MetadataManipulator
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

        if (count($paramsArray) == 3) {
            $this->sourceField = $paramsArray[0];
            $this->destFieldXpath = $paramsArray[1];
            $this->delimiter = $paramsArray[2];
        } else {
            $this->log->addInfo("SplitRepeatedValues", array('Wrong parameter count' => count($paramsArray)));
        }
    }

    /**
     * General manipulate wrapper method.
     *
     * @param string $input An XML snippet to be manipulated.
     *
     * @return string
     *     Manipulated string
     */
    public function manipulate($input)
    {
        if (!strlen($input)) {
            return $input;
        }

        $dom = new \DomDocument();
        // loadxml() throws and exception if $input has already been processed
        // by this manipulator, since the XML snippet is invalid, e.g.,
        // <subject><topic>Boats</topic></subject><subject><topic>Havana</topic></subject>.
        // Wrapping the code following loadxml() in a 'finally' block lets the XPath
        // fail gracefully, allowing the subsequent 'if' blocks to execute. Probably
        // not the best way to handle this but it works.
        try {
            libxml_use_internal_errors(true);
            $dom->loadxml($input, LIBXML_NSCLEAN);
        }
        finally {
            // Test to see if the current fragment is the one identified in the config file
            // by the XPath expression.
            $xpath = new \DOMXPath($dom);
            $dest_elements = $xpath->query($this->destFieldXpath);

            if ($dest_elements->length === 1) {
                // If it is, get the value of the corresponding input field. We make a copy
                // of the source field value to explode so we don't need to remove the \ from
                // the output of preg_quoute().
                $source_field_value_to_explode = $this->getSourceFieldValue();
                $source_field_value_to_match = preg_quote($this->getSourceFieldValue(), '#');
                // If the source field value contains the delimter character, split the value
                // and add a MODS element for each repeated value. Also, to account for HTML/XML
                // entities (which contain ';', a common delimter) in the values, we apply
                // html_entity_decode() to the value that we split, then re-encode before assembling
                // the output.
                $source_field_value_to_match = html_entity_decode($source_field_value_to_match, ENT_NOQUOTES|ENT_XML1);
                $pattern = '#' . "^(.*)($source_field_value_to_match)(.*)$" . '#';
                if (strpos($this->getSourceFieldValue(), $this->delimiter) !== false) {
                    preg_match($pattern, $input, $matches);
                    if (isset($matches[1]) && isset($matches[3])) {
                        $repeated_values = explode($this->delimiter, $source_field_value_to_explode);
                        $output = '';
                        foreach ($repeated_values as &$value) {
                            $value = trim($value);
                            $value = htmlspecialchars($value, ENT_NOQUOTES|ENT_XML1);
                            // $matches[1] is the opening markup, and $matches[3] is the closing markup.
                            $output .= $matches[1] . $value . $matches[3];
                            $this->logSplit('info', $this->getSourceFieldValue(), $dest_elements->item(0), $output);
                        }
                        return $output;
                    }
                } else {
                    // If current fragment does not contain any delimiters, return it.
                    return $input;
                }
            } else {
                // If current fragment does not match theh configure XPath expression,
                // return it.
                return $input;
            }
        }
    }

    /**
     * Get the value of the source metadata field for the current object.
     *
     * @return string
     *     The value of the source metadata field.
     */
    public function getSourceFieldValue()
    {
        $raw_metadata_cache_path = $this->settings['FETCHER']['temp_directory'] .
          DIRECTORY_SEPARATOR . $this->record_key . '.metadata';
        $raw_metadata_cache = file_get_contents($raw_metadata_cache_path);

        // Cached metadata for CSV toolchains is a serialized CSV object.
        if ($this->settings['FETCHER']['class'] == 'Csv') {
            $metadata = unserialize($raw_metadata_cache);
            if (isset($metadata->{$this->sourceField})) {
                return trim($metadata->{$this->sourceField});
            }
        }
        // Cached metadata for CDM toolchains is a serialized associative array.
        // If the field is empty, its value is an empty array.
        if ($this->settings['FETCHER']['class'] == 'Cdm') {
            $metadata = unserialize($raw_metadata_cache);
            if (isset($metadata[$this->sourceField]) && is_string($metadata[$this->sourceField])) {
                return trim($metadata[$this->sourceField]);
            }
        }
        // If we haven't returned at this point, log failure.
        $this->log->addWarning("SplitRepeatedValues", array(
          'Record key' => $this->record_key,
          'Source field not set' => $this->sourceField));
    }

    /**
     * Write a successful split operation to the manipulator log.
     *
     * @param string
     *     Either 'info' or 'warning'.
     * @param string
     *     The value of the source metadata field.
     * @param object
     *     The target MODS DOM element.
     * @param string
     *     The XML output, or the failed regex pattern.
     */
    public function logSplit($level, $source_value, $element, $extra)
    {
        if ($level == 'info') {
            $this->log->addInfo(
                "SplitRepeatedValues",
                array(
                    'Record key' => $this->record_key,
                    'Source field name' => $this->sourceField,
                    'Source field value' => $source_value,
                    'Output' => $extra,
                )
            );
        }
        if ($level == 'warning') {
            $this->log->addWarning(
                "SplitRepeatedValues",
                array(
                    'Record key' => $this->record_key,
                    'Source field name' => $this->sourceField,
                    'Source field value' => $source_value,
                    'Failed regex' => $extra,
                )
            );
        }
    }
}
