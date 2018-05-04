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
        if (isset($manipulator_settings[2])) {
            $this->outputFile = $manipulator_settings[2];
            $now = date("F j, Y, g:i a");
            $message = "# Output of the MIK Random Set fetcher manipulator, generated $now" . PHP_EOL;
            if (file_exists($this->outputFile)) {
                $message = PHP_EOL . $message;
            }
            file_put_contents($this->outputFile, $message, FILE_APPEND);
        }
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
        echo "Filtering $numRecs records through the RandomSet fetcher manipulator.\n";
        // Instantiate the progress bar if we're not running on Windows.
        if (!$this->onWindows) {
            $climate = new \League\CLImate\CLImate;
            $progress = $climate->progress()->total($numRecs);
        }

        $randomSet = $this->getRandomSet($numRecs);

        $record_num = 0;
        $filtered_records = array();
        foreach ($all_records as $record) {
            if (in_array($record_num, $randomSet)) {
                $filtered_records[] = $record;
            }

            if (isset($this->outputFile)) {
                if ($record_num < count($randomSet) - 1) {
                    $record->key = $record->key . PHP_EOL;
                    file_put_contents($this->outputFile, $record->key, FILE_APPEND);
                }
                if ($record_num === count($randomSet)) {
                    file_put_contents($this->outputFile, $record->key, FILE_APPEND);
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
        if ($this->setSize >= $numRecs) {
            $this->setSize = $numRecs;
        }

        $randomSet = array();
        $discards = array();
        for ($i = 0; $i < $this->setSize; ++$i) {
            $selected = mt_rand(0, $numRecs - 1);
            if (!in_array($selected, $randomSet)) {
                $randomSet[] = $selected;
            }
        }
        // If the number of randomly chosen records is less than
        // the number required to be in $randomSet, choose the
        // shortfail from the records that are not in $randomSet.
        if (count($randomSet) < $this->setSize) {
            $shortfall = $this->setSize - count($randomSet);
            // Get an array of the auto-incremented record numbers of
            // all the items that didn't make it into $randomSet.
            $unchosen_record_nums = array_diff(range(0, $numRecs - 1), $randomSet);
            // Now we have a list of record numbers that we can draw from
            // if we need some extras for making up a full $set. We
            // reset the indexes of this array before we pass it off
            // to getExtraRandom().
            $unchosen_record_nums = array_values($unchosen_record_nums);
            $extras = $this->getExtraRandom($unchosen_record_nums, $shortfall);
            sort($randomSet, SORT_NUMERIC);
            $randomSet = array_merge($randomSet, $extras);
        }
        sort($randomSet, SORT_NUMERIC);
        return $randomSet;
    }

    /**
     * Generates a unique set of integers randomly
     * selected from $discards.
     *
     * @param array $discards
     *   Array containing the integers between 1 and
     *   $numRecs not selected in getRandomSet().
     * @param int $quantity
     *   The number of elements from $notSelected to
     *   return.
     * @return array
     *   The list of integers randomly selected from
     *   $discards.
     */
    public function getExtraRandom($discards, $quantity)
    {
        shuffle($discards);
        $flipped_discards = array_flip($discards);
        return (array) array_rand($flipped_discards, $quantity);
    }
}
