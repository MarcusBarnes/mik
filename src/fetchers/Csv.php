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
}
