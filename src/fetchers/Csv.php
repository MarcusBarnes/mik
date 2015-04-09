<?php

namespace mik\fetchers;

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
    }

    /**
    * Return an array of records.
    *
    * @return array The records.
    */
    public function getRecords()
    {
        $data = array();
        $file = $this->settings['input_file'];
        ini_set('auto_detect_line_endings', TRUE);
        $handle = fopen($file, 'r');
        while (($row = fgetcsv($handle)) !== FALSE) {
            $data[] = $row;
        }
        ini_set('auto_detect_line_endings', FALSE);
        return $data;
    }
}
