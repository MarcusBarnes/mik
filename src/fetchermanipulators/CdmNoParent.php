<?php

namespace mik\fetchermanipulators;

use League\CLImate\CLImate;
use GuzzleHttp\Client;
use \Monolog\Logger;

/**
 * @file
 * Fetcher manipulator that filters for objects that do not have a parent.
 *
 * Only objects that have no parent and that are not compound objects
 * are included in the fetch. For CONTENTdm fetches, we alreadt supress
 * child objects in the query to get all objects, so the separate check
 * here for GetParent is probably redundant.
 */

class CdmNoParent extends FetcherManipulator
{
    /**
     * Create a new CdmNoParent fetchermanipulator Instance.
     *
     * @param array $settings
     *   All of the settings from the .ini file.
     *
     * @param array $manipulator_settings
     *   An array of all of the settings for the current manipulator,
     *   with the manipulator class name in the first position and
     *   the strings indicating the extensions to filter on in the
     *   remaining positions.
     */
    public function __construct($settings, $manipulator_settings)
    {
        $this->settings = $settings;
        $this->alias = $this->settings['METADATA_PARSER']['alias'];

        // To get the value of $onWindows.
        parent::__construct();

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
     * Tests each record to see if the 'filetype' propery is not 'cpd',
     * and then tests to see whether the object has no parent.
     *
     * @param array $all_records
     *   All of the records from the fetcher.
     * @return array $filtered_records
     *   An array of records that pass the test(s) defined in the
     *   fetcher manipulator.
     */
    public function manipulate($all_records)
    {
        $numRecs = count($all_records);
        echo "Filtering $numRecs records through the CdmNoParent fetcher manipulator.\n";
        // Instantiate the progress bar if we're not running on Windows.
        if (!$this->onWindows) {
            $climate = new \League\CLImate\CLImate;
            $progress = $climate->progress()->total($numRecs);
        }

        $record_num = 0;
        $filtered_records = array();
        foreach ($all_records as $record) {
            if (property_exists($record, 'key') &&
                property_exists($record, 'filetype') &&
                is_string($record->filetype) &&
                // We want only the records for Cdm objects that are not compound.
                $record->filetype != 'cpd') {
                $pointer = $record->key;

                // And that have no parent.
                if (!$this->getCdmParent($pointer)) {
                    $filtered_records[] = $record;
                }
                $record_num++;
                if ($this->onWindows) {
                    print '.';
                } else {
                    $progress->current($record_num);
                }
            }
        }
        if ($this->onWindows) {
            print "\n";
        }
        return $filtered_records;
    }

    /**
     * Get the output of the CONTENTdm web API GetParent function
     * for the current object.
     *
     * @param string $pointer
     *   The CONTENTdm pointer for the current object.
     *
     * @return string|false
     *   The output of the CONTENTdm GetParent API request,
     *   or false if the parent is -1 (no parent).
     */
    private function getCdmParent($pointer)
    {

          // Use Guzzle to fetch the output of the call to GetParent
          // for the current object.
          $url = $this->settings['METADATA_PARSER']['ws_url'] .
              'GetParent/' . $this->alias . '/' . $pointer . '/json';
          $client = new Client();
        try {
            $response = $client->get($url);
        } catch (Exception $e) {
            $this->log->addInfo(
                "CdmNoParent",
                array('HTTP request error' => $e->getMessage())
            );
            return true;
        }
          $body = $response->getBody();
          $parent_info = json_decode($body, true);

        if ($parent_info['parent'] == '-1') {
            return false;
        } else {
            return $parent_info['parent'];
        }
    }
}
