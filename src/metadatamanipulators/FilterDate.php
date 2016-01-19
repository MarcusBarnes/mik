<?php
// src/metadatamanipulators/FilterDate.php

namespace mik\metadatamanipulators;
use \Monolog\Logger;

/**
 * FilterDate - Normalize a date from the source metadata for use
 * within MODS' originInfo/dateIssued, dateCreated, dateCaptured,
 * dateValid, dateModified, copyrightDate, and dateOther child elements.
 *
 * Applies to all MODS toolchains.
 */
class FilterDate extends MetadataManipulator
{

    /**
     * Create a new metadata manipulator instance.
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
            $this->sourceDateField = $paramsArray[0];
            $this->destDateElement = $paramsArray[1];           
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
        $date_elements = $xpath->query('/originInfo/' . $this->destDateElement);

        // There should only be one target date element.
        if ($date_elements->length === 1) {
            // Get the child node, which we will repopulate below if its value
            // matches our regex.
            $date_element = $date_elements->item(0);
            // Get its parent so we can reconstruct it for sending back to the
            // metadata parser.
            $origin_info_element = $date_element->parentNode;

            $source_date_field_value = $this->getSourceDateFieldValue();

            // See if the value of the date field in the raw metadata matches our
            // pattern, and if it does, replace the value of the target MODS element
            // with a w3cdtf version of the date value.
            // Note: this logic is specific to dates that come in as \d\d-\d\d-\d\d\d\d.
            if (preg_match('/^(\d\d)\-(\d\d)\-(\d\d\d\d)$/', $source_date_field_value, $matches)) {
                $date_element->nodeValue = $matches[3] . '-' . $matches[2] . '-' . $matches[1];
                // Reassemble the parent and child elements.
                $origin_info_element->appendChild($date_element);
                // Convert the back to the snippet and return it.
                $this->log->addInfo("FilterDate",
                    array(
                        'Record key' => $this->record_key,
                        'Source date value' => $source_date_field_value,                        
                        'Normalized MODS XML element' => $dom->saveXML($origin_info_element),
                        )
                );
                return $dom->saveXML($origin_info_element);
            }
            else {
                $this->log->addWarning("FilterDate",
                    array(
                        'Record key' => $this->record_key,
                        'Source date value does not match any pattern' => $source_date_field_value,                        
                        )
                );
                return $input;
            }
        }
        else {
            // If current fragment does not match our XPath expression, return it.
            return $input;
        }
     }

    /**
     * Get the value of the source date field for the current object.
     *
     * @return string
     *     The value of the source date field.
     */
     public function getSourceDateFieldValue()
     {
        // Get the raw metadata (in the CSV toolchain, it will be a serialized object) so
        // we can make decisions based on any value in it.
        $raw_metadata_cache_path = $this->settings['FETCHER']['temp_directory'] .
            DIRECTORY_SEPARATOR . $this->record_key . '.metadata';
        $raw_metadata_cache = file_get_contents($raw_metadata_cache_path);

        if ($this->settings['FETCHER']['class'] == 'Csv') {
            $metadata = unserialize($raw_metadata_cache);
            return $metadata->{$this->sourceDateField};
        }
        if ($this->settings['FETCHER']['class'] == 'Cdm') {
            $metadata = json_decode($raw_metadata_cache, true);
            return $metadata[$this->sourceDateField];
        }        
     }     
}