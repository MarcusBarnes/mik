<?php

namespace mik\fetchers;
use League\Csv\Reader;

class Csv extends Fetcher
{
    /**
     * @var array $settings - configuration settings from confugration class.
     */
    public $settings;

    /**
     * @var array $fetchermanipulators - the fetchermanipulors from config,
     *   in the form fetchermanipulator_class_name|param_0|param_1|...|param_n
     */
    public $fetchermanipulators;

    /**
     * Create a new CSV Fetcher Instance.
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        $this->settings = $settings['FETCHER'];
        // We make a copy of all setting to pass to fetcher manipulators.
        $this->all_settings = $settings;
        $this->input_file = $this->settings['input_file'];
        $this->record_key = $this->settings['record_key'];
        $this->field_delimiter = $this->settings['field_delimiter'];

        if (isset($settings['MANIPULATORS']['fetchermanipulators'])) {
            $this->fetchermanipulators = $settings['MANIPULATORS']['fetchermanipulators'];
        }
        else {
            $this->fetchermanipulators = null;
        }
    }

    /**
    * Return an array of records.
    *
    * @param $limit int
    *   The number of records to get.
    *
    * @return object The records.
    */
    public function getRecords($limit = null)
    {
        // Use a static cache to avoid reading the CSV file multiple times.
        static $filtered_records;
        if (!isset($filtered_records)) {
    	    $inputCsv = Reader::createFromPath($this->input_file);
                $inputCsv->setDelimiter($this->field_delimiter);
                if (is_null($limit)) {
                    // Get all records.
                    $limit = -1;
                }
    	    $records = $inputCsv   
    		->addFilter(function ($row, $index) {
                    // Skip header row.
    	            return $index > 0;
    		})
    		->setLimit($limit)
    		->fetchAssoc();

    	    foreach ($records as $index => &$record) {
                if (!is_null($record[$this->record_key]) || strlen($record[$this->record_key])) {
                    $record = (object) $record;
                    $record->key = $record->{$this->record_key};
                }
                else {
                    unset($records[$index]);
                }
    	    }

            if ($this->fetchermanipulators) {
                $filtered_records = $this->applyFetchermanipulators($records);
            }
            else {
                $filtered_records = $records;
            }
        }
        return $filtered_records;
    }

    /**
     * Implements fetchers\Fetcher::getNumRecs.
     * 
     * Returns the number of records under consideration.
     *    For CSV, this will be the number_format(number)ber of rows of data with a unique index.
     *
     * @return total number of records
     *
     * Note that extending classes must define this method.
     */
    public function getNumRecs()
    {
        $records = $this->getRecords();
        return count($records);
    }

    /**
     * Implements fetchers\Fetcher::getItemInfo
     * Returns a hashed array or object containing a record's information.
     *
     * @param string $recordKey the unique record_key
     *      For CSV, this will the the unique id assisgned to a row of data.
     *
     * @return object The record.
     */
    public function getItemInfo($record_key)
    {
        $records = $this->getRecords();
        foreach ($records as $record) {
          if (strlen($record->key) && $record->key == $record_key) {
            return $record;
          }
        }
    }

    /**
     * Applies the fetchermanipulator listed in the config.
     */
    private function applyFetchermanipulators($records)
    {
        foreach ($this->fetchermanipulators as $manipulator) {
            $manipulator_settings_array = explode('|', $manipulator, 2);
            $manipulator_class = '\\mik\\fetchermanipulators\\' . $manipulator_settings_array[0];
            $fetchermanipulator = new $manipulator_class($this->all_settings,
                $manipulator_settings_array);
            $records = $fetchermanipulator->manipulate($records);
        }
        return $records;
    }    
}
