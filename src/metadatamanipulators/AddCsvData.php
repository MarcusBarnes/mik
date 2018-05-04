<?php
// src/metadatamanipulators/AddCsvData.php

namespace mik\metadatamanipulators;

use \Monolog\Logger;

/**
 * AddCsvData - Adds the metadata from the CSV record for the object
 * to an <extension> element in the MODS. This manipulator adds all
 * the fields from the row, not just the ones mapped to MODS.
 *
 * Note that this manipulator doesn't add the <extension> fragment, it
 * only populates it with data from the CSV file. The mappings file
 * must contain a row that adds the following element to your MODS:
 * '<extension><CSVData></CSVData></extension>', e.g.,
 * null5,<extension><CSVData></CSVData></extension>.
 *
 * This metadata manipulator takes no configuration parameters.
 */
class AddCsvData extends MetadataManipulator
{
    /**
     * @var string $record_key - the unique identifier for the metadata
     *    record being manipulated.
     */
    private $record_key;

    /**
     * Create a new metadata manipulator Instance.
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
    }

    /**
     * General manipulate wrapper method.
     *
     *  @param string $input The XML fragment to be manipulated. We are only
     *     interested in the <extension><CSVData> fragment added in the
     *     MIK mappings file.
     *
     * @return string
     *     One of the manipulated XML fragment, the original input XML if the
     *     input is not the fragment we are interested in, or an empty string,
     *     which as the effect of removing the empty <extension><CSVData>
     *     fragement from our MODS (if there was an error, for example, we don't
     *     want empty extension elements in our MODS documents).
     */
    public function manipulate($input)
    {
        if (!strlen($input)) {
            return $input;
        }

        $dom = new \DomDocument();
        $dom->loadxml($input, LIBXML_NSCLEAN);

        // Test to see if the current fragment is <extension><CSVData>.
        $xpath = new \DOMXPath($dom);
        $csvdatas = $xpath->query("//extension/CSVData");

        // There should only be one <CSVData> fragment in the incoming
        // XML. If there is 0 or more than 1, return the original.
        if ($csvdatas->length === 1) {
            $csvdata = $csvdatas->item(0);

            $csvid = $dom->createElement('id_in_csv', $this->record_key);
            $csvdata->appendChild($csvid);

            $timestamp = date("Y-m-d H:i:s");

          // Add the <CSVRecord> element.
            $csvrecord = $dom->createElement('CSVRecord');
            $now = $dom->createAttribute('timestamp');
            $now->value = $timestamp;
            $csvrecord->appendChild($now);
            $mimetype = $dom->createAttribute('mimetype');
            $mimetype->value = 'application/json';
            $csvrecord->appendChild($mimetype);

            try {
                $metadata_path = $this->settings['FETCHER']['temp_directory'] . DIRECTORY_SEPARATOR .
                  $this->record_key . '.metadata';
                $metadata_contents = file_get_contents($metadata_path);
                $metadata_contents = unserialize($metadata_contents);
                $metadata_contents = json_encode($metadata_contents);
            } catch (Exception $e) {
                $message = "Problem creating <CSVRecord> element for object " . $this->record_key . ":" .
                    $e->getMessage();
                $this->log->addInfo("AddCsvData", array('CSV metadata warning' => $message));
                return '';
            }

          // If the metadata contains the CDATA end delimiter, log and return.
            if (preg_match('/\]\]>/', $metadata_contents)) {
                $message = "CSV metadata for object " . $this->record_key . ' contains the CDATA end delimiter ]]>';
                $this->log->addInfo("AddCsvData", array('CSV metadata warning' => $message));
                return '';
            }

          // If we've made it this far, add the metadata to <CcvData> as
          // CDATA and return the modified XML fragment.
            if (strlen($metadata_contents)) {
                $cdata = $dom->createCDATASection($metadata_contents);
                $csvrecord->appendChild($cdata);
                $csvdata->appendChild($csvrecord);
            }

            return $dom->saveXML($dom->documentElement);
        } else {
            // If current fragment is not <extension><CSVData>, return it
            // unmodified.
            return $input;
        }
    }
}
