<?php

namespace mik\fetchers;

use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Common\Type;

class Excel extends Fetcher
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
     * Create a new Excel Fetcher Instance.
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        parent::__construct($settings);
        $this->input_file = $this->settings['input_file'];
        $this->record_key = $this->settings['record_key'];

        if (isset($settings['MANIPULATORS']['fetchermanipulators'])) {
            $this->fetchermanipulators = $settings['MANIPULATORS']['fetchermanipulators'];
        } else {
            $this->fetchermanipulators = null;
        }

        if (!$this->createTempDirectory()) {
            $this->log->addError("Excel fetcher", array('Cannot create temp_directory'));
        }

        if (isset($settings['FETCHER']['use_cache'])) {
            $this->use_cache = $settings['FETCHER']['use_cache'];
        } else {
            $this->use_cache = true;
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
        // Use a static cache to avoid reading the Excel file multiple times.
        static $filtered_records;
        if (!isset($filtered_records) || $this->use_cache == false) {
            $inputExcel = ReaderFactory::create(Type::XLSX);
            $inputExcel->open($this->input_file);

            $header_row = array();
            $records = array();
            foreach ($inputExcel->getSheetIterator() as $sheet) {
                $row_num = 1;
                foreach ($sheet->getRowIterator() as $row) {
                    // Metadata must be in the first sheet.
                    if ($sheet->getIndex() === 0) {
                        // Cheap way of getting the header row.
                        if (count($header_row) === 0) {
                            $header_row[] = $row;
                            $column_names = array_values($header_row[0]);
                            foreach ($column_names as &$column_name) {
                                $column_name = trim($column_name);
                            }
                        } else {
                            foreach ($row as &$metadata_value) {
                                $metadata_value = trim($metadata_value);
                            }
                            $row_assoc = array_combine($column_names, $row);
                            if (is_null($limit)) {
                                $records[] = $row_assoc;
                            } else {
                                if ($row_num <= $limit) {
                                    $records[] = $row_assoc;
                                }
                            }
                        }
                    }
                    $row_num++;
                }
            }
            $inputExcel->close();

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
     *    For Excel, this will be the number_format(number)ber of rows of data with a unique index.
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
     *      For Excel, this will the the unique id assisgned to a row of data.
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
}
