<?php

namespace mik\fetchermanipulators;
use League\CLImate\CLImate;
use GuzzleHttp\Client;
use \Monolog\Logger;

/**
 * @file
 * Fetcher manipulator that filters for objects that have a parent.
 * Only objects that have no parent are included in the fetch.
 */

class CdmNoParent extends FetcherManipulator
{

    /**
     * @var string $record_key - the unique identifier for the metadata
     *    record being manipulated.
     */
    private $record_key;

    /**
     * Create a new CdmSingleFileByExtension fetchermanipulator Instance.
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
        $this->alias = $this->settings['METADATA_PARSER']['alias'];

        // To get the value of $onWindows.
        parent::__construct();

        // Set up logger.
        $this->pathToLog = $this->settings['LOGGING']['path_to_manipulator_log'];
        $this->log = new \Monolog\Logger('config');
        $this->logStreamHandler = new \Monolog\Handler\StreamHandler($this->pathToLog,
            Logger::INFO);
        $this->log->pushHandler($this->logStreamHandler);        
    }

    /**
     * Tests each record to see if the extension of the file named in
     * the 'find' field has an extension matching any in the list of
     * extensions defined as manipulator paramters.
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
            if (property_exists($record, 'find') &&
                is_string($record->dmrecord) && strlen($record->dmrecord)) {
                $pointer = $record->dmrecord;
                // We want only the records for Cdm objects that have no parent.
                if (!$this->getParent($pointer)) {
                    $filtered_records[] = $record;
                }
                $record_num++;
                if ($this->onWindows) {
                    print '.';
                }
                else {
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
     * Fetch the output of the CONTENTdm web API GetParent function
     * for the current object.
     *
     * @param string $pointer
     *   The CONTENTdm pointer for the current object.
     *
     * @return string|false
     *   The output of the CONTENTdm API request, in the format specified,
     *   or false if the parent is -1 (no parent).
     */
    private function getCdmParent($pointer)
    {
          // Use Guzzle to fetch the output of the call to dmGetItemInfo
          // for the current object.
          $url = $this->settings['METADATA_PARSER']['ws_url'] .
              'GetParent/' . $this->alias . '/' . $pointer . '/json';
          $client = new Client();
          try {
              $response = $client->get($url);
          } catch (Exception $e) {
              $this->log->addInfo("AddContentdmData",
                  array('HTTP request error' => $e->getMessage()));
              return '';
          }
          $body = $response->getBody();
          $parent_info = json_decode($body, true);
          if ($parent_info['parent'] == '-1') {
              return false;
          }
          else {
              return $parent_info['parent'];
          }
    }    

}
