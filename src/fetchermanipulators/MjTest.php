<?php

namespace mik\fetchermanipulators;
use League\CLImate\CLImate;

class MjTest extends FetcherManipulator
{
    /**
     * Create a new MjTest fetchermanipulator Instance
     *
     * @param $settings array
     *   The settings from the .ini file.
     */
    public function __construct($settings, $manipulator_settings)
    {
        $manipulator_params = array_slice($manipulator_settings, 1);
        $this->allowed_extensions = explode('|', $manipulator_params[0]);
        $this->file_name_field = $settings['FILE_GETTER']['file_name_field'];
    }

    /**
     * Filter on file extension.
     *
     * @param array $records
     *   All of the records from the fetcher.
     * @return array $filtered_records
     *   An array of records that pass the test(s) defined in this function.
     */
    public function manipulate($records)
    {
        $numRecs = count($records);
        echo "Filtering $numRecs records through the MjTest manipulator.\n";
        // Instantiate the progress bar.
        $climate = new \League\CLImate\CLImate;
        $progress = $climate->progress()->total($numRecs);

        $record_num = 0;
        $filtered_records = array();
        foreach ($records as $record) {
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
