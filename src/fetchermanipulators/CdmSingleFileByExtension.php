<?php

namespace mik\fetchermanipulators;

use League\CLImate\CLImate;

/**
 * @file
 * Fetcher manipulator that filters records based on the extension
 * of the CONTENTdm object's file.
 */

class CdmSingleFileByExtension extends FetcherManipulator
{
    /**
     * @var string $extension - The extension of the file as named in the
     *   CONTENTdm record's 'find' property.
     */
    public $extension;

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
        array_shift($manipulator_settings);
        $this->extensions = explode(',', $manipulator_settings[0]);
        // To get the value of $onWindows.
        parent::__construct();
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
        echo "Filtering $numRecs records through the CdmSingleFileByExtension fetcher manipulator.\n";
        // Instantiate the progress bar if we're not running on Windows.
        if (!$this->onWindows) {
            $climate = new \League\CLImate\CLImate;
            $progress = $climate->progress()->total($numRecs);
        }

        $record_num = 0;
        $filtered_records = array();
        foreach ($all_records as $record) {
            if (property_exists($record, 'find') &&
                is_string($record->find) && strlen($record->find)) {
                $ext = pathinfo($record->find, PATHINFO_EXTENSION);
                if (in_array($ext, $this->extensions)) {
                    $filtered_records[] = $record;
                }
                $record_num++;
                if ($this->onWindows) {
                    print '.';
                } else {
                    $progress->current($record_num);
                }
            }
        }
        if ($this->onWindows) {
            print "\n";
        }
        return $filtered_records;
    }
}
