<?php

namespace mik\fetchers;

class Filesystem extends Fetcher
{
    private $record_count;
    
    /**
     * Create a new Filesystem Fetcher instance.
     *
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        parent::__construct($settings);
        $this->input_directory = rtrim($settings['FILE_GETTER']['input_directory'], DIRECTORY_SEPARATOR);

        $this->record_key = 'ID';

        if (isset($settings['MANIPULATORS']['fetchermanipulators'])) {
            $this->fetchermanipulators = $settings['MANIPULATORS']['fetchermanipulators'];
        } else {
            $this->fetchermanipulators = null;
        }

        if (!$this->createTempDirectory()) {
            $this->log->addError("Filesystem fetcher", array('Cannot create temp_directory'));
        }
    }

    /**
    * Return an array of records.
    *
    * @param $limit int
    *   The number of records to get.
    *
    * @return array The records.
    */
    public function getRecords($limit = null)
    {
        $all_files = scandir($this->input_directory);
        $entries = array_diff($all_files, array('.', '..'));

        foreach ($entries as $entry) {
            $record = new \stdClass;
            $path_to_entry = $this->input_directory . DIRECTORY_SEPARATOR . $entry;
            $record->ID = pathinfo($path_to_entry, PATHINFO_FILENAME);
            $record->title = $entry;
            $record->key = $record->{$this->record_key};
            if (is_file($path_to_entry)) {
                $records[] = $record;
            }
        }

        // @todo: If there is a limit, slice the $records array.

        if ($this->fetchermanipulators) {
            $filtered_records = $this->applyFetchermanipulators($records);
        } else {
            $filtered_records = $records;
        }

        $this->record_count = count($filtered_records);
        return $filtered_records;
    }

    /**
     * Implements fetchers\Fetcher::getNumRecs.
     *
     * Returns the number of records under consideration.
     *
     * @return total number of records
     *
     * Note that extending classes must define this method.
     */
    public function getNumRecs()
    {
        static $num_recs;
        if (!isset($num_recs) || !isset($this->record_count)) {
            $num_recs = count($this->getRecords());
        }
        return $num_recs;
    }

    /**
     * Implements fetchers\Fetcher::getItemInfo
     *
     * @param string $recordKey the unique record_key
     *
     * @return object The record.
     */
    public function getItemInfo($record_key)
    {
        $record = new \stdClass;
        $record->key = $record_key;
        // Getting the filename by globbing for it is brittle and hackish, but
        // in the absence of an explicit value in input file, it'll have to do.
        $file_path_with_no_ext = $this->input_directory . DIRECTORY_SEPARATOR . $record_key;
        $files_with_name = glob($file_path_with_no_ext . ".*");
        $record->title = basename($files_with_name[0]);
        return $record;
    }

    /**
     * Applies the fetchermanipulator listed in the config.
     */
    private function applyFetchermanipulators($records)
    {
        foreach ($this->fetchermanipulators as $manipulator) {
            $manipulator_settings_array = explode('|', $manipulator);
            $manipulator_class = '\\mik\\fetchermanipulators\\' . $manipulator_settings_array[0];
            $fetchermanipulator = new $manipulator_class($this->all_settings,
                $manipulator_settings_array);
            $records = $fetchermanipulator->manipulate($records);
        }
        return $records;
    }
}
