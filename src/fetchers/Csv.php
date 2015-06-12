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
    }

    /**
    * Return an array of records.
    *
    * @return array The records.
    */
    public function getRecords()
    {
        $inputData = Reader::createFromPath($this->input_file);
        $data = $inputData
            ->addFilter(function ($row, $index) {
                    return $index > 0; // Skip header row.
            })
            ->setLimit()
            ->fetchAssoc();

        $csv = new \stdClass;
        $csv->records = $data;

        return $csv;
    }

    /**
     * Implements fetchers\Fetcher::queryTotalRec.
     * 
     * Returns the number of records under consideration.
     *    For CSV, this will be the number of rows of data with a unique index.
     *
     * @return total number of records
     *
     * Note that extending classes must define this method.
     */
    public function queryTotalRec()
    {
        // @ToDo - implmentation specific details
        return false;
    }

    /**
     * Implements fetchers\Fetcher::getItemInfo
     * Returns a hashed array or object containing a record's information.
     *
     * @param string $recordKey the unique record_key
     *      For CONTENTdm, this will be the item pointer
     *      For CSV, this will the the unique id assisgned to a row of data.
     *
     * @return array or object of record info.
     */
    public function getItemInfo($recordKey)
    {
        // @ToDo - implementation specific details.
        return false;
    }

}
