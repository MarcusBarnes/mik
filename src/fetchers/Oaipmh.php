<?php

namespace mik\fetchers;
use Phpoaipmh\Client;
use Phpoaipmh\Endpoint;

class Oaipmh extends Fetcher
{
    /**
     * @var array $settings - configuration settings from confugration class.
     */
    public $settings;

    /**
     * @var array $fetchermanipulators - the fetchermanipulors from config,
     *   in the form fetchermanipulator_class_name|param_0|param_1|...|param_n
     */
    public $fetchermanipulators;

    /**
     * Create a new OAI-PMH Fetcher Instance.
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        parent::__construct($settings);
        $this->endpoint = $settings['FETCHER']['oai_endpoint'];

        if (isset($settings['FETCHER']['metadata_prefix'])) {
            $this->metadataPrefix = $settings['FETCHER']['metadata_prefix'];
        }
        else {
            $this->metadataPrefix = 'oai_dc';
        }

        if (isset($settings['FETCHER']['from'])) {
            $this->from = $settings['FETCHER']['from'];
        }
        else {
            $this->from = null;
        }

        if (isset($settings['FETCHER']['until'])) {
            $this->until = $settings['FETCHER']['until'];
        }
        else {
            $this->until = null;
        }

        if (isset($settings['FETCHER']['set_spec'])) {
            $this->setSpec = $settings['FETCHER']['set_spec'];
        }
        else {
            $this->setSpec = null;
        }

        if (isset($settings['MANIPULATORS']['fetchermanipulators'])) {
            $this->fetchermanipulators = $settings['MANIPULATORS']['fetchermanipulators'];
        }
        else {
            $this->fetchermanipulators = null;
        }

        if (!$this->createTempDirectory()) {
            $this->log->addError("OAI-PMH fetcher",
                array('Cannot create temp_directory' => $e->getMessage()));
        }

        if (isset($settings['FETCHER']['use_cache'])) {
            $this->use_cache = $settings['FETCHER']['use_cache'];
        }
        else {
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
        // Use a static cache to avoid reading the CSV file multiple times.
        static $filtered_records;
        if (!isset($filtered_records) || $this->use_cache == false) {
            $client = new \Phpoaipmh\Client($this->endpoint);
            $endpoint = new \Phpoaipmh\Endpoint($client);
            $records = $endpoint->listRecords($this->metadataPrefix, $this->from, $this->until, $this->setSpec);
            foreach($records as $rec) {
                $identifier = urlencode($rec->header->identifier);
                file_put_contents($this->tempDirectory . DIRECTORY_SEPARATOR . $identifier . '.oai.xml', $rec->asXML());
            }

/*
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
*/

/*
            foreach ($records as $index => &$record) {
                if (!is_null($record[$this->record_key]) || strlen($record[$this->record_key])) {
                    $record = (object) $record;
                    $record->key = $record->{$this->record_key};
                }
                else {
                    unset($records[$index]);
                }
            }

            if ($this->fetchermanipulators) {
                $filtered_records = $this->applyFetchermanipulators($records);
            }
            else {
                $filtered_records = $records;
            }
*/
        }
        $filtered_records = $records;
        return $filtered_records;
    }

    /**
     * Implements fetchers\Fetcher::getNumRecs.
     *
     * Returns the number of records under consideration.
     *    For OAI-PMH, this will be the number of records stored in the temp directory.
     *
     * @return total number of records
     */
    public function getNumRecs()
    {
        $iterator = new FilesystemIterator($this->tempDir, FilesystemIterator::SKIP_DOTS);
        return iterator_count($iterator);
    }

    /**
     * @note: This function is copied from the CSV fetcher.
     *
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
        }
        else {
            return unserialize(file_get_contents($raw_metadata_cache));
        }
    }

    /**
     * @note: This function is copied from the CSV fetcher.
     * 
     * Applies the fetchermanipulator listed in the config.
     */
    private function applyFetchermanipulators($records)
    {
        foreach ($this->fetchermanipulators as $manipulator) {
            $manipulator_settings_array = explode('|', $manipulator, 2);
            $manipulator_class = '\\mik\\fetchermanipulators\\' . $manipulator_settings_array[0];
            $fetchermanipulator = new $manipulator_class($this->all_settings,
                $manipulator_settings_array);
            $records = $fetchermanipulator->manipulate($records);
        }
        return $records;
    }

    /**
     * @note: This function is copied from the CSV fetcher.
     *
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
        }
        else {
            $enclosure = '"';
        }
        if (isset($this->escape_character)) {
            $escape = $this->escape_character;
        }
        else {
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
