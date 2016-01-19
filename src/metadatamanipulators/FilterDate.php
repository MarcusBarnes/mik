<?php
// src/metadatamanipulators/FilterDate.php

namespace mik\metadatamanipulators;
use \Monolog\Logger;

/**
 * FilterDate - Normalize an input date.
 */
class FilterDate extends MetadataManipulator
{

    /**
     * Normalize a date.
     *
     * @param string $input A date expressed as a string.
     *
     * @return string The normalized date, or FALSE if preg_replace fails.
     */
    public function __construct($settings = null, $paramsArray, $record_key)
    {
        parent::__construct($settings, $paramsArray, $record_key);
        $this->record_key = $record_key;

        // Set up logger.
        $this->pathToLog = $this->settings['LOGGING']['path_to_manipulator_log'];
        $this->log = new \Monolog\Logger('config');
        $this->logStreamHandler = new \Monolog\Handler\StreamHandler($this->pathToLog,
            Logger::INFO);
        $this->log->pushHandler($this->logStreamHandler);

        if (count($paramsArray) == 2) {
            // $this->dateElement = $paramsArray[0];
            // $this->patternToMatch = $paramsArray[1];
            $this->dateField = $paramsArray[0];
            $this->dateElementXPath = trim($paramsArray[1]);           
        } else {
            $this->log->addInfo("FilterDate", array('Wrong parameter count' => count($paramsArray)));
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
        $dom = new \DomDocument();
        $dom->loadxml($input, LIBXML_NSCLEAN);

        // Test to see if the current fragment is the one identified in the config file.
        $xpath = new \DOMXPath($dom);
        $date_created_elements = $xpath->query($this->dateElementXPath);

        if ($date_created_elements->length === 1) {
            $this->log->addInfo("FilterDate", array('Incoming XML snippet' => $input));
            // Get the child node, which we will repopulate below if its value
            // matches our regex.
            $date_created_element = $date_created_elements->item(0);
            // Get its parent so we can reconstruct it for sending back to the
            // metadata parser.
            $origin_info_element = $date_created_element->parentNode;
            // Get the raw metadata (in the CSV toolchain, it will be a serialized object) so
            // we can make decisions based on any value in it.
            $raw_metadata_cache_path = $this->settings['FETCHER']['temp_directory'] .
                DIRECTORY_SEPARATOR . $this->record_key . '.metadata';
            $raw_metadata_cache = file_get_contents($raw_metadata_cache_path);
            $metadata = unserialize($raw_metadata_cache);
            // See if the value of the field in the raw metadata matches our
            // pattern, and if it does, replace the value of the target MODS element
            // with a fixed version of the date value.
            $this->log->addInfo("FilterDate", array('Date value' => $metadata->{$this->dateField}));
            // Note: this logic is specific to dates that come in as \d\d-\d\d-\d\d\d\d.
            if (preg_match('/(\d\d)\-(\d\d)\-(\d\d\d\d)/', $metadata->{$this->dateField}, $matches)) {
                $this->log->addInfo("FilterDate", array('Message' => 'Match successful'));
                $date_created_element->nodeValue = $matches[3] . '-' . $matches[2] . '-' . $matches[1];
                // Reassemble the parent and child elements.
                $origin_info_element->appendChild($date_created_element);
                // Convert the back to the snippet and return it.
                $this->log->addInfo("FilterDate", array('XML to return' => $dom->saveXML($origin_info_element)));
                return $dom->saveXML($origin_info_element);
            }
            else {
                return $input;
            }
        }
        else {
            // If current fragment does not match our XPath expression, return it.
            return $input;
        }
     }
}