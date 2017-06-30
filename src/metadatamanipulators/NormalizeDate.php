<?php
// src/metadatamanipulators/NormalizeDate.php

namespace mik\metadatamanipulators;

use \Monolog\Logger;

/**
 * NormalizeDate - Normalize a date from the raw source metadata
 * (i.e., the metadata stored in $settings['FETCHER']['temp_directory'])
 * for use within MODS' originInfo/dateIssued, dateCreated, dateCaptured,
 * dateValid, dateModified, copyrightDate, and dateOther child elements.
 *
 * Applies to all MODS toolchains.
 */
class NormalizeDate extends MetadataManipulator
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
            $this->sourceDateField = $paramsArray[0];
            $this->destDateElement = $paramsArray[1];
        } elseif (count($paramsArray) == 3) {
            $this->sourceDateField = $paramsArray[0];
            $this->destDateElement = $paramsArray[1];
            $this->preference = $paramsArray[2];
        } else {
            $this->log->addInfo("NormalizeDate", array('Wrong parameter count' => count($paramsArray)));
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

            // This is the metadata stored in $settings['FETCHER']['temp_directory'],
            // not in the incoming sMODS element.
            $this->sourceDateFieldValue = $this->getSourceDateFieldValue();

            /**
             * In the series of if/elseif blocks below, see if the value of the date field
             * in the incoming metadata matches our pattern, and if it does, replace the
             * value of the target MODS element with a w3cdtf version of the date value.
             *
             * @todo: When 'ca.'' is present, add 'qualifier' attribute with values 'approximate',
             * 'inferred', 'questionable'. Set a default (maybe configurable) date in this case?
             */

            /**
             * Check for dates like 1-8-1930. Leading zeros on day and month optional,
             * as are the position of month and day.
             */
            if (preg_match('/^(\d\d?)\-(\d\d?)\-(\d\d\d\d)$/', $this->sourceDateFieldValue, $matches)) {
                // This pattern is often interpreted in two ways (US and UK dates) so we allow
                // it to take an optional 'preference' flag. Value of 'm' indicates that the
                // first part of the incoming date value is month.
                if (isset($this->preference) && $this->preference == 'm') {
                    // Interpret as mm-dd-yyyy. Reassemble the value as yyyy-mm-dd.
                    list($month, $day, $year) = explode('-', $matches[0]);
                } else {
                    // Interpret as dd-mm-yyyy (this is the default). Reassemble the value as yyyy-mm-dd.
                    list($day, $month, $year) = explode('-', $matches[0]);
                }
                // Reassemble the parent and child elements.
                if (strlen($day)=== 1) {
                    $day = '0' . $day;
                }
                if (strlen($month) === 1) {
                    $month = '0' . $month;
                }
                $date_element->nodeValue = $year . '-' . $month . '-' . $day;
                $origin_info_element->appendChild($date_element);
                // Convert the back to the snippet and return it.
                $this->logNormalization($this->sourceDateFieldValue, $origin_info_element, $dom);
                $this->logInvalidDate($year, $month, $day);
                return $dom->saveXML($origin_info_element);
            } /**
             * Check for dates like 1/8/1930. Leading zeros on day and month optional, as are
             * the position of month and day.
             */
            elseif (preg_match('#^(\d\d?)/(\d\d?)/(\d\d\d\d)$#', $this->sourceDateFieldValue, $matches)) {
                // Another pattern that can be interpreted in two ways (US and UK dates).
                if (isset($this->preference) && $this->preference == 'm') {
                    // Interpret as mm/dd/yyyy. Reassemble the value as yyyy-mm-dd.
                    list($month, $day, $year) = explode('/', $matches[0]);
                } else {
                    // Interpret as dd/mm/yyyy (this is the default). Reassemble the value as yyyy-mm-dd.
                    list($day, $month, $year) = explode('/', $matches[0]);
                }
                // Reassemble the parent and child elements.
                if (strlen($day) === 1) {
                    $day = '0' . $day;
                }
                if (strlen($month) === 1) {
                    $month = '0' . $month;
                }
                $date_element->nodeValue = $year . '-' . $month . '-' . $day;
                $origin_info_element->appendChild($date_element);
                // Convert the back to the snippet and return it.
                $this->logNormalization($this->sourceDateFieldValue, $origin_info_element, $dom);
                $this->logInvalidDate($year, $month, $day);
                return $dom->saveXML($origin_info_element);
            } /**
             * Check for dates like 1930 10 15. Assumes leading zeros on month and day and that
             * the incoming date is in format yyyy mm dd.
             */
            elseif (preg_match('/^(\d\d\d\d)\s+(\d\d)\s+(\d\d)$/', $this->sourceDateFieldValue, $matches)) {
                list($year, $month, $day) = preg_split('/\s+/', $matches[0]);
                // Reassemble the value as yyyy-mm-dd.
                $date_element->nodeValue = $year . '-' . $month . '-' . $day;
                $origin_info_element->appendChild($date_element);
                $this->logNormalization($this->sourceDateFieldValue, $origin_info_element, $dom);
                $this->logInvalidDate($year, $month, $day);
                return $dom->saveXML($origin_info_element);
            } /**
             * Check for dates like 1930/10/15. Assumes leading zeros on month and day and that
             * the incoming date is in format yyyy/mm/dd.
             */
            elseif (preg_match('#^(\d\d\d\d)/(\d\d)/(\d\d)$#', $this->sourceDateFieldValue, $matches)) {
                list($year, $month, $day) = explode('/', $matches[0]);
                // Reassemble the value as yyyy-mm-dd.
                $date_element->nodeValue = $year . '-' . $month . '-' . $day;
                $origin_info_element->appendChild($date_element);
                $this->logNormalization($this->sourceDateFieldValue, $origin_info_element, $dom);
                $this->logInvalidDate($year, $month, $day);
                return $dom->saveXML($origin_info_element);
            } /**
             * Check for dates in the format we want yyyy-mm-dd but which have puncutation
             * around the date, and removes the puncutation.
             */
            elseif (preg_match('#^[.,;:]?(\d\d\d\d\-\d\d\-\d\d)[.,;:]?$#', $this->sourceDateFieldValue, $matches)) {
                $date_element->nodeValue = $matches[1];
                $origin_info_element->appendChild($date_element);
                $this->logNormalization($this->sourceDateFieldValue, $origin_info_element, $dom);
                list($year, $month, $day) = explode('-', $matches[1]);
                $this->logInvalidDate($year, $month, $day);
                return $dom->saveXML($origin_info_element);
            } /**
             * Check for date value that is empty or not string. Just log it.
             */
            elseif (!is_string($this->sourceDateFieldValue) || !strlen($this->sourceDateFieldValue)) {
                $this->log->addWarning(
                    "NormalizeDate",
                    array(
                        'Record key' => $this->record_key,
                        'Message' => 'Source date value is empty or not a string'
                        )
                );
                return $input;
            } else {
                $this->log->addWarning(
                    "NormalizeDate",
                    array(
                        'Record key' => $this->record_key,
                        'Source date value does not match any pattern' => $this->sourceDateFieldValue,
                        )
                );
                return $input;
            }
        } else {
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
        $raw_metadata_cache_path = $this->settings['FETCHER']['temp_directory'] .
          DIRECTORY_SEPARATOR . $this->record_key . '.metadata';
        $raw_metadata_cache = file_get_contents($raw_metadata_cache_path);

        // Cached metadata for CSV toolchains is a serialized CSV object.
        if ($this->settings['FETCHER']['class'] == 'Csv') {
            $metadata = unserialize($raw_metadata_cache);
            if (isset($metadata->{$this->sourceDateField})) {
                return trim($metadata->{$this->sourceDateField});
            }
        }
        // Cached metadata for CDM toolchains is a serialized associative array.
        // If the field is empty, its value is an empty array.
        if ($this->settings['FETCHER']['class'] == 'Cdm') {
            $metadata = unserialize($raw_metadata_cache);
            if (isset($metadata[$this->sourceDateField]) && is_string($metadata[$this->sourceDateField])) {
                return trim($metadata[$this->sourceDateField]);
            }
        }
        // If we haven't returned at this point, log failure.
        $this->log->addWarning("NormalizeDate", array(
          'Record key' => $this->record_key,
          'Source date field not set' => $this->sourceDateField));
    }

    /**
     * Write a successful normalization entry to the manipulator log.
     *
     * @param string
     *     The value of the source date field.
     * @param object
     *     The target MODS DOM element.
     * @param object
     *     The DOM.
     */
    public function logNormalization($source_value, $element, $dom)
    {
        $this->log->addInfo(
            "NormalizeDate",
            array(
               'Record key' => $this->record_key,
               'Source date value' => $source_value,
               'Normalized MODS XML element' => $dom->saveXML($element),
            )
        );
    }

    /**
     * Validates a date and logs invalid ones.
     *
     * @param int
     *     The normalized year.
     * @param int
     *     The normalized month.
     * @param int
     *     The normalized day.
     */
    public function logInvalidDate($year, $month, $day)
    {
        if (checkdate($month, $day, $year)) {
            return;
        }

        $this->log->addWarning(
            "NormalizeDate",
            array(
               'Record key' => $this->record_key,
               'Normalized date value is not a valid date' => $year . '-' . $month . '-' . $day,
            )
        );
    }
}
