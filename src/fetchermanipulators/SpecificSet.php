<?php

namespace mik\fetchermanipulators;

use League\CLImate\CLImate;
use \Monolog\Logger;

/**
 * @file
 * Fetcher manipulator that filters out an enumerated set of
 * objects. Useful for testing and QA purposes. Can be used
 * within any toolchain (i.e., is not specific to CONTENTdm
 * CSV, etc.). The identifiers for each record that are
 * identified as 'record_key' in the .ini file are retrieved
 * from a plain text file contianing one ID per line. The path
 * to this file is this manipulator's sole parameter, e.g.,
 * fetchermanipulators[] = "SpecificSet|/tmp/record_ids.txt".
 *
 * The input file should contain a list of CONTENTdm pointers,
 * CSV row IDs, or whatever field is defined in the .ini file as
 * the record_key, one ID per line. Comments are allowed in this
 * file, using a '#' at the start of comment lines.
 *
 * MIK's --limit parameter applies as if this manipulator were
 * absent. If the identifiers listed in the input file match
 * records retrieved within the limit, they are included in the
 * set processed by MIK; if not, they are excluded from the set
 * processed by MIK. Since the speicifc set is by definition a
 * limit on how many records are processed, the --limit parameter
 * is not usually used in conjuction with this manipulator.
 *
 * This fetcher manipulator can also be used in 'exclude' mode,
 * where the identifiers in the input file are excluded from
 * the fetcher. This may be useful if you want to process all
 * but a specific set of objects. To use 'exclude' mode, append
 * '|exclude' to the end of the path to the input file, e.g.,
 * fetchermanipulators[] = "SpecificSet|/tmp/record_ids.txt|exclude".
 */

class SpecificSet extends FetcherManipulator
{
    /**
     * Create a new SpecificSet fetchermanipulator Instance.
     *
     * @param array $settings
     *   All of the settings from the .ini file.
     *
     * @param array $manipulator_settings
     *   An array of all of the settings for the current manipulator,
     *   with the manipulator class name in the first position and
     *   the string indicating the set size in the second.
     */
    public function __construct($settings, $manipulator_settings)
    {
        $this->settings = $settings;
        if (preg_match('/\|/', $manipulator_settings[1])) {
            $parameters = explode('|', $manipulator_settings[1]);
        } else {
            $parameters = array($manipulator_settings[1]);
        }

        $this->pathToInputFile = $parameters[0];
        $this->exclude = (isset($parameters[1]) && $parameters[1] == 'exclude') ? true : false;
        // To get the value of $onWindows.
        parent::__construct($settings);
        // Set up logger.
        $this->pathToLog = $this->settings['LOGGING']['path_to_manipulator_log'];
        $this->log = new \Monolog\Logger('config');
        $this->logStreamHandler = new \Monolog\Handler\StreamHandler(
            $this->pathToLog,
            Logger::INFO
        );
        $this->log->pushHandler($this->logStreamHandler);
    }

    /**
     * Selects a specific subset of records.
     *
     * @param array $all_records
     *   All of the records from the fetcher.
     * @return array $filtered_records
     *   An array of records that pass the test(s) defined in the fetcher manipulator.
     */
    public function manipulate($all_records)
    {
        $numRecs = count($all_records);
        echo "Filtering $numRecs records through the SpecificSet fetcher manipulator.\n";
        // Instantiate the progress bar if we're not running on Windows.
        if (!$this->onWindows) {
            $climate = new \League\CLImate\CLImate;
            $progress = $climate->progress()->total($numRecs);
        }

        $specificSet = $this->getSpecificSet();

        $record_num = 0;
        $filtered_records = array();
        foreach ($all_records as $record) {
            // If we're in 'exclude' mode, keep records that are not listed
            // in the input file.
            if ($this->exclude) {
                if (!in_array($record->key, $specificSet)) {
                    $filtered_records[] = $record;
                }
            } // Keep records that are listed in the input file.
            else {
                if (in_array($record->key, $specificSet)) {
                    $filtered_records[] = $record;
                }
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

    /**
     * Retrieves a list of object record keys from a
     * text file.
     *
     * @return array
     *   The list of record keys.
     */
    public function getSpecificSet()
    {
        if (!file_exists($this->pathToInputFile)) {
            $this->log->addInfo("SpecificSet", array(
                'Input file not found' => $this->pathToInputFile));
            return array();
        }

        $record_keys = file($this->pathToInputFile);
        $record_keys = array_filter($record_keys, array($this, 'removeComments'));

        foreach ($record_keys as &$id) {
            $id = trim($id);
        }

        return $record_keys;
    }

    /**
     * Callback function that removes elements from an array
     * that start with '#'.
     */
    private function removeComments($item)
    {
        return !preg_match('/^#/', $item);
    }
}
