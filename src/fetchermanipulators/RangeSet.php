<?php

namespace mik\fetchermanipulators;

use League\CLImate\CLImate;
use \Monolog\Logger;

/**
 * @file
 * Fetcher manipulator that filters out range of objects
 * based on the value of their record keys. Can be used
 * within any toolchain (i.e., is not specific to CONTENTdm
 * CSV, etc.). Three types of ranges are available:
 *
 * Less than:
 * fetchermanipulators[] = "RangeSet|<50".
 *
 * Greater than:
 * fetchermanipulators[] = "RangeSet|>50".
 *
 * Between:
 * fetchermanipulators[] = "RangeSet|50@60".
 *
 * In these examples, the less than range selects objects
 * whose record key is less than 50, the greater than range
 * selects objects whose record key is greater than 50, and
 * the between range selects objects whose record keys are
 * between 50 and 60. Note that the ranges are not "less than
 * or equal to", etc. - the range boundary is not included
 * in the resulting record set. Comparisons to determine inclusion
 * in the range are performed using PHP's strnatcmp() function.
 *
 * A fourth type of range is not based on the value of objects'
 * record keys but on their position in the complete record set.
 * This range uses a syntax similar to SQL's LIMIT:
 *
 * fetchermanipulators[] = "RangeSet|50,100".
 *
 * This example selects a set of 50 records starting at
 * the 100th record in the entire set of records.
 */

class RangeSet extends FetcherManipulator
{
    /**
     * Create a new RangeSet fetchermanipulator Instance.
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

        $this->range = $parameters[0];
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
     * Selects a subset of records based on the value of their record keys.
     *
     * @param array $all_records
     *   All of the records from the fetcher.
     * @return array $filtered_records
     *   An array of records that pass the test(s) defined in the fetcher manipulator.
     */
    public function manipulate($all_records)
    {
        $numRecs = count($all_records);
        echo "Filtering $numRecs records through the RangeSet fetcher manipulator.\n";
        // Instantiate the progress bar if we're not running on Windows.
        if (!$this->onWindows) {
            $climate = new \League\CLImate\CLImate;
            $progress = $climate->progress()->total($numRecs);
        }

        // Determine what type of range test we will apply.
        if (preg_match('/^>/', $this->range)) {
            $filter = 'greaterThan';
        }
        if (preg_match('/^</', $this->range)) {
            $filter = 'lessThan';
        }
        if (preg_match('/@/', $this->range)) {
            $filter = 'between';
        }
        // The limit filter uses position in full record set,
        // not value of record key.
        if (preg_match('/,/', $this->range)) {
            $filter = 'limit';
        }

        $record_num = 0;
        $filtered_records = array();
        foreach ($all_records as $record) {
            $record_num++;
            if ($this->{$filter}($record->key, $this->range, $record_num)) {
                $filtered_records[] = $record;
            }

            if ($this->onWindows) {
                print '.';
            } else {
                $progress->current($record_num);
            }
        }

        if ($this->onWindows) {
            print "\n";
        }

        if (count($filtered_records) === 0) {
            $this->log->addError("RangeSet", array(
                'Empty record set' => "The range " . $this->range . " has filtered out all records."));
        }
        return $filtered_records;
    }

    public function greaterThan($record_key, $range)
    {
        $boundary = substr($range, 1);
        $score = strnatcmp($record_key, $boundary);
        if ($score > 0) {
            return true;
        }
    }

    public function lessThan($record_key, $range)
    {
        $boundary = substr($range, 1);
        $score = strnatcmp($record_key, $boundary);
        if ($score < 0) {
            return true;
        }
    }

    public function between($record_key, $range)
    {
        list($lower_boundary, $upper_boundary) = explode('@', $range);
        if (strnatcmp($record_key, $lower_boundary) > 0 && strnatcmp($record_key, $upper_boundary) < 0) {
            return true;
        }
    }

    public function limit($record_key, $range, $recnum)
    {
        list($limit, $offset) = explode(',', $range);
        static $limit_count = 1;
        if ($recnum >= $offset && $limit_count <= $limit) {
            $limit_count++;
            return true;
        }
    }
}
