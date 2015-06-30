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
     * Create a new CSV Fetcher Instance.
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        $this->settings = $settings['FETCHER'];
        $this->input_file = $this->settings['input_file'];
        $this->record_key = $this->settings['record_key'];
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
        static $csv;
        if (!isset($csv)) {
	    $inputData = Reader::createFromPath($this->input_file);
            $num_rows = count($inputData);
            if (is_null($limit)) {
                $limit = -1;
            }
	    $data = $inputData
		->addFilter(function ($row, $index) {
	            return $index > 0; // Skip header row.
		})
		->setLimit($limit)
		->fetchAssoc();

	    $csv = new \stdClass;
	    $csv->records = $data;

	    foreach ($csv->records as &$record) {
	      $record = (object) $record;
	      $record->key = $record->{$this->record_key};
	    }
        }
        return $csv;
    }

    /**
     * Implements fetchers\Fetcher::getNumRecs.
     * 
     * Returns the number of records under consideration.
     *    For CSV, this will be the number of rows of data with a unique index.
     *
     * @return total number of records
     *
     * Note that extending classes must define this method.
     */
    public function getNumRecs()
    {
        $csv = $this->getRecords();
        return count($csv);
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
        $csv = $this->getRecords();
        foreach ($csv->records as $record) {
          if (strlen($record->key) && $record->key == $record_key) {
            return $record;
          }
        }
    }
}
