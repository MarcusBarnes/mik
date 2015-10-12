<?php

namespace mik\fetchermanipulators;
use League\CLImate\CLImate;

/**
 * @file
 * Fetcher manipulator that filters out a random set of
 * objects. Useful for testing and QA purposes. Can be used
 * within any toolchain (i.e., is not specific to CONTENTdm
 * CSV, etc.).
 *
 * If used with MIK's --limit parameter, the number of items
 * in the randomized set will never be larger than the value of
 * --limit (which means that the set could include every object).
 * For example, if you ask for a random set of 10 and the value
 * of --limit is 5, the set will contain 5 items.
 */

class RandomSet extends FetcherManipulator
{
    /**
     * @var int $setSize - The size of the random set.
     */
    public $setSize;

    /**
     * Create a new RandomSet fetchermanipulator Instance.
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
        $this->setSize = $manipulator_settings[1];
        // To get the value of $onWindows.
        parent::__construct();
    }

    /**
     * Selects a random sample of records.
     *
     * @param array $all_records
     *   All of the records from the fetcher.
     * @return array $filtered_records
     *   An array of records that pass the test(s) defined in the fetcher manipulator.
     */
    public function manipulate($all_records)
    {
        $numRecs = count($all_records);
        echo "Fetching $numRecs records, filtering them.\n";
        // Instantiate the progress bar if we're not running on Windows.
        if (!$this->onWindows) {
            $climate = new \League\CLImate\CLImate;
            $progress = $climate->progress()->total($numRecs);
        }

        $randomSet = $this->getRandomSet($numRecs);
        var_dump($randomSet);
        $record_num = 0;
        $filtered_records = array();
        foreach ($all_records as $record) {
            if (in_array($record_num, $randomSet)) {
                $filtered_records[] = $record;
            }
            $record_num++;
            if ($this->onWindows) {
                print '.';
            }
            else {
                $progress->current($record_num);
            }
        }
        if ($this->onWindows) {
            print "\n";
        }
        return $filtered_records;
    }

    /**
     * Generates a unique, sorted set of random integers.
     *
     * @param int $numRecs
     *   The number of records this fetcher manipulator
     *    applies to.
     * @return array
     *   The list of random integers.
     */
    public function getRandomSet($numRecs)
    {
        $ret = array();
        for ($i = 1; $i <= $this->setSize; $i++) {
            $selected = rand(0, $numRecs);
            if (!in_array($selected, $ret)) {
                $ret[] = $selected;
            }
        }
        sort($ret, SORT_NUMERIC);
        return $ret;
    }    

}
