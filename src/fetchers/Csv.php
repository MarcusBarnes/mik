<?php

namespace mik\fetchers;

use League\Csv\Reader;

class Csv extends Fetcher
{

    /**
     * @var array $fetchermanipulators - the fetchermanipulors from config,
     *   in the form fetchermanipulator_class_name|param_0|param_1|...|param_n
     */
    public $fetchermanipulators;

    /**
     * @var string $record_key - the key for the column representing unique row ids.
     */
    public $record_key;

    /**
     * Create a new CSV Fetcher Instance.
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        parent::__construct($settings);
        $this->input_file = $this->settings['input_file'];
        $this->record_key = $this->settings['record_key'];
        if (isset($settings['FETCHER']['field_delimiter'])) {
            $this->field_delimiter = $this->settings['field_delimiter'];
        } else {
            $this->field_delimiter = ',';
        }

        // Default enclosure is double quotation marks.
        if (isset($settings['FETCHER']['field_enclosure'])) {
            $this->field_enclosure = $settings['FETCHER']['field_enclosure'];
        }
        // Default escape character is \.
        if (isset($settings['FETCHER']['escape_character'])) {
            $this->escape_character = $settings['FETCHER']['escape_character'];
        }

        if (isset($settings['MANIPULATORS']['fetchermanipulators'])) {
            $this->fetchermanipulators = $settings['MANIPULATORS']['fetchermanipulators'];
        } else {
            $this->fetchermanipulators = null;
        }

        if (!$this->createTempDirectory()) {
            $this->log->addError(
                "CSV fetcher",
                array('Cannot create temp_directory')
            );
        }
    }

    /**
    * Return an array of records.
    *
    * @param $limit int
    *   The number of records to get.
    *
    * @return object The records.
    */
    public function getRecords($limit = null)
    {

        // Use a static cache to avoid reading the CSV file multiple times.
        static $filtered_records;
        if (!isset($filtered_records) || $this->use_cache == false) {
            $inputCsv = Reader::createFromPath($this->input_file);
                $inputCsv->setDelimiter($this->field_delimiter);
            if (isset($this->field_enclosure)) {
                $inputCsv->setEnclosure($this->field_enclosure);
            }
            if (isset($this->escape_character)) {
                $inputCsv->setEscape($this->escape_character);
            }
            if (is_null($limit)) {
                // Get all records.
                $limit = -1;
            }
            $records = $inputCsv
                ->addFilter(function ($row, $index) {
                    // Skip header row.
                    return $index > 0;
                })
            ->setLimit($limit)
            ->fetchAssoc();

            foreach ($records as $index => &$record) {
                // Commenting out rows only works if the # is the first
                // character in the record key field.
                if (preg_match('/^#/', $record[$this->record_key])) {
                    unset($records[$index]);
                }
                if (!is_null($record[$this->record_key]) || strlen($record[$this->record_key])) {
                    $record = (object) $record;
                    $record->key = $record->{$this->record_key};
                } else {
                    unset($records[$index]);
                }
            }

            if ($this->fetchermanipulators) {
                $filtered_records = $this->applyFetchermanipulators($records);
            } else {
                $filtered_records = $records;
            }
        }
        return $filtered_records;
    }

    /**
     * Implements fetchers\Fetcher::getNumRecs.
     *
     * Returns the number of records under consideration.
     *    For CSV, this will be the number_format(number)ber of rows of data with a unique index.
     *
     * @return total number of records
     *
     * Note that extending classes must define this method.
     */
    public function getNumRecs()
    {
        $records = $this->getRecords();
        return count($records);
    }

    /**
     * Implements fetchers\Fetcher::getItemInfo
     * Returns a hashed array or object containing a record's information.
     *
     * @param string $recordKey the unique record_key
     *      For CSV, this will the the unique id assisgned to a row of data.
     *
     * @return object The record.
     */
    public function getItemInfo($recordKey)
    {
        $raw_metadata_cache = $this->settings['temp_directory'] . DIRECTORY_SEPARATOR . $recordKey . '.metadata';
        if (!file_exists($raw_metadata_cache)) {
            $records = $this->getRecords();
            foreach ($records as $record) {
                if (strlen($record->key) && $record->key == $recordKey) {
                    $record = $this->removeEscape($record);
                    file_put_contents($raw_metadata_cache, serialize($record));
                    return $record;
                }
            }
        } else {
            return unserialize(file_get_contents($raw_metadata_cache));
        }
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

    /**
     * Removes the CSV escape character from field values.
     *
     * See https://github.com/MarcusBarnes/mik/issues/129.
     *
     * @param $record object
     *    The CSV record object.
     * @return object
     */
    private function removeEscape($record)
    {
        // League\Csv\Reader defaults, which are not set
        // explicitly in _construct.
        if (isset($this->field_enclosure)) {
            $enclosure = $this->field_enclosure;
        } else {
            $enclosure = '"';
        }
        if (isset($this->escape_character)) {
            $escape = $this->escape_character;
        } else {
            $escape = '\\';
        }

        // Backslashes need to be scaped in regex patterns,
        // as do ^ and $, etc.
        $metachars = array('\\', '^', '$', '*', '.');
        if (in_array($escape, $metachars)) {
            $escape = '\\' . $escape;
        }
        if (in_array($enclosure, $metachars)) {
            $enclosure = '\\' . $enclosure;
        }

        foreach ($record as $field_name => $field_value) {
            $pattern = '/' . $escape . $enclosure . '/';
            $field_value = preg_replace($pattern, $enclosure, $field_value);
            $record->{$field_name} = $field_value;
        }
        return $record;
    }
}
