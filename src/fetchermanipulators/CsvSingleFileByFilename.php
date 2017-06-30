<?php

namespace mik\fetchermanipulators;

use League\CLImate\CLImate;

/**
 * Fetcher manipulator that filters records based on a pattern in
 * the file's name.
 */

class CsvSingleFileByFilename extends FetcherManipulator
{
    /**
     * Create a new CsvSingleFileByFilename fetchermanipulator instance.
     *
     * @param array $settings
     *   All of the settings from the .ini file.
     *
     * @param array $manipulator_settings
     *   An array of all of the settings for the current manipulator,
     *   with the manipulator class name in the first position and
     *   the PHP regex pattern to match, without the leading and trailing
     *   /, as the second member.
     */
    public function __construct($settings, $manipulator_settings)
    {
        $this->allowed_pattern = $manipulator_settings[1];
        $this->file_name_field = $settings['FILE_GETTER']['file_name_field'];
        // To get the value of $onWindows.
        parent::__construct();
    }

    /**
     * Filter on pattern in file name.
     *
     * @param array $records
     *   All of the records from the fetcher.
     * @return array $filtered_records
     *   An array of records that pass the test(s) defined in this function.
     */
    public function manipulate($records)
    {
        $numRecs = count($records);
        echo "Filtering $numRecs records through the CsvSingleFileByFilename fetcher manipulator.\n";
        // Instantiate the progress bar.
        if (!$this->onWindows) {
            $climate = new \League\CLImate\CLImate;
            $progress = $climate->progress()->total($numRecs);
        }

        $record_num = 0;
        $filtered_records = array();
        foreach ($records as $record) {
            $filename = pathinfo($record->{$this->file_name_field}, PATHINFO_FILENAME);
            if (preg_match('/' . $this->allowed_pattern . '/', $filename)) {
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
}
