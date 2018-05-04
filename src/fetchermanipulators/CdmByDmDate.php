<?php

namespace mik\fetchermanipulators;

use GuzzleHttp\Client;
use League\CLImate\CLImate;

/**
 * @file
 * Fetcher manipulator that filters records based on the object's
 * dmcreated or dmmodified value.
 */

class CdmByDmDate extends FetcherManipulator
{
    /**
     * Create a new CdmByDmDate fetchermanipulator Instance.
     *
     * @param array $settings
     *   All of the settings from the .ini file.
     *
     * @param array $manipulator_settings
     *   An array of all of the settings for the current manipulator,
     *   with the manipulator class name in the first position and
     *   the string indicating the document type to filter for in the
     *   second. This string must be one of: Document, Document-PDF,
     *   Document-EAD, Postcard, Picture Cube, Monograph.
     */
    public function __construct($settings, $manipulator_settings)
    {
        if (preg_match('/\|/', $manipulator_settings[1])) {
            $parameters = explode('|', $manipulator_settings[1]);
        } else {
            $parameters = array($manipulator_settings[1]);
        }

        $this->attribute = $parameters[0];
        $this->operator = $parameters[1];
        $this->date = $parameters[2];

        $this->alias = $settings['FETCHER']['alias'];
        $this->ws_url = $settings['FETCHER']['ws_url'];

        // Default Mac PHP setups may use Apple's Secure Transport
        // rather than OpenSSL, causing issues with CA verification.
        // Allow configuration override of CA verification at users own risk.
        if (isset($this->settings['SYSTEM']['verify_ca'])) {
            if ($this->settings['SYSTEM']['verify_ca'] == false) {
                $this->verifyCA = false;
            }
        } else {
            $this->verifyCA = true;
        }

        parent::__construct();
    }

    /**
     * Tests each record to see if it has a dmcreated or dmmodified value
     * greater than, less than, or equal to the one specified in the manipulator's
     * third parameter.
     *
     * @param array $all_records
     *   All of the records from the fetcher.
     * @return array $filtered_records
     *   An array of records that pass the test(s) defined in the fetcher manipulator.
     */
    public function manipulate($all_records)
    {
        $numRecs = count($all_records);
        echo "Filtering $numRecs records through the CdmByDateCreated fetcher manipulator.\n";
        // Instantiate the progress bar if we're not running on Windows.
        if (!$this->onWindows) {
            $climate = new \League\CLImate\CLImate;
            $progress = $climate->progress()->total($numRecs);
        }

        $record_num = 0;
        $filtered_records = array();
        foreach ($all_records as $record) {
            if ($this->attribute == 'dmmodified') {
                $record->dmmodified = $this->getDmModified($record->pointer);
            }

            if ($this->operator == '>') {
                if ($record->{$this->attribute} > $this->date) {
                    $filtered_records[] = $record;
                }
            } elseif ($this->operator == '<') {
                if ($record->{$this->attribute} < $this->date) {
                    $filtered_records[] = $record;
                }
            } elseif ($this->operator == '==') {
                if ($record->{$this->attribute} == $this->date) {
                    $filtered_records[] = $record;
                }
            } // If the operator is unrecognized, do not filter records.
            else {
                $filtered_records[] = $record;
            }

            $record_num++;
            if ($this->onWindows) {
                print '.';
            } else {
                $progress->current($record_num);
            }
        }

        if ($this->onWindows) {
            print "\n";
        }

        return $filtered_records;
    }

    /**
     * Gets a CONTENTdm object's dmmodified value, which unlike
     * dmcreated is not part of the basic Cdm record.
     */
    public function getDmModified($pointer)
    {
        $query_url = $this->ws_url . 'GetItemDmmodified/' . $this->alias . '/' .
            $pointer . '/json';
        // Create a new Guzzle client to fetch the CPD (stucture)   file.
        $client = new Client();
        $response = $client->get($query_url, ['verify' => $this->verifyCA]);
        $body = $response->getBody();
        $dmmodified = json_decode($body, true);
        return $dmmodified[0];
    }
}
