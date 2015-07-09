<?php

namespace mik\fetchermanipulators;
use League\CLImate\CLImate;

class CsvSingleFile extends FetcherManipulator
{
    /**
     * Create a new CsvSingleFile fetchermanipulator Instance
     *
     * @param $settings array
     *   The settings from the .ini file.
     */
    public function __construct($settings)
    {
        $manipulator_setting_array = explode('|', $settings['MANIPULATORS']['fetchermanipulator']);
        $this->allowed_extensions = array_slice($manipulator_setting_array, 1);
        $this->file_name_field = $settings['FILE_GETTER']['file_name_field'];
    }

    /**
     * Tests each record to see if it has one of the extensions in
     * $this->allowed_extensions.
     *
     * @param array $all_records
     *   All of the records from the fetcher.
     * @return array $filtered_records
     *   An array of records that pass the test(s) defined in this function.
     */
    public function manipulate($all_records)
    {
        // var_dump($all_records);
        $numRecs = count($all_records);
        echo "Fetching $numRecs records, filitering them.\n";
        // Instantiate the progress bar.
        $climate = new \League\CLImate\CLImate;
        $progress = $climate->progress()->total($numRecs);

        $record_num = 0;
        $filtered_records = array();
        foreach ($all_records as $record) {
            // var_dump($record);
            $ext = pathinfo($record->{$this->file_name_field}, PATHINFO_EXTENSION);
            if (in_array($ext, $this->allowed_extensions)) {
                $filtered_records[] = $record;
            }
            $record_num++;  
            $progress->current($record_num);
        }
        return $filtered_records;
    }
}