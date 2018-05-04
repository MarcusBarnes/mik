<?php

namespace mik\fetchermanipulators;

use League\CLImate\CLImate;

/**
 * Fetcher manipulator that filters records based on the file extension
 * of the file in the ['FILE_GETTER']['file_name_field'] configuration
 * setting.
 */

class CsvSingleFileByExtension extends FetcherManipulator
{
    /**
     * Create a new CsvSingleFile fetchermanipulator Instance
     *
     * @param array $settings
     *   All of the settings from the .ini file.
     *
     * @param array $manipulator_settings
     *   An array of all of the settings for the current manipulator,
     *   with the manipulator class name in the first position and
     *   the list of allowed extensions, without the leading period,
     *   as the second member.
     */
    public function __construct($settings, $manipulator_settings)
    {
        // We remove the first member of $manipulator_settings since it contains
        // the classname of this class.
        $this->allowed_extensions = explode(',', $manipulator_settings[1]);
        $this->file_name_field = $settings['FILE_GETTER']['file_name_field'];
        // To get the value of $onWindows.
        parent::__construct();
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
    public function manipulate($records)
    {
        $numRecs = count($records);
        echo "Filtering $numRecs records through the CsvSingleFileByExtension fetcher manipulator.\n";
        // Instantiate the progress bar.
        if (!$this->onWindows) {
            $climate = new \League\CLImate\CLImate;
            $progress = $climate->progress()->total($numRecs);
        }

        $record_num = 0;
        $filtered_records = array();
        foreach ($records as $record) {
            // var_dump($record);
            $ext = pathinfo($record->{$this->file_name_field}, PATHINFO_EXTENSION);
            if (in_array($ext, $this->allowed_extensions)) {
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
