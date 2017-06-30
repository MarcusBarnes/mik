<?php

namespace mik\fetchers;

use Phpoaipmh\Client;
use Phpoaipmh\Endpoint;

class Oaipmh extends Fetcher
{

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
        } else {
            $this->metadataPrefix = 'oai_dc';
        }

        if (isset($settings['FETCHER']['from'])) {
            $this->from = $settings['FETCHER']['from'];
        } else {
            $this->from = null;
        }

        if (isset($settings['FETCHER']['until'])) {
            $this->until = $settings['FETCHER']['until'];
        } else {
            $this->until = null;
        }

        if (isset($settings['FETCHER']['set_spec'])) {
            $this->setSpec = $settings['FETCHER']['set_spec'];
        } else {
            $this->setSpec = null;
        }

        if (isset($settings['MANIPULATORS']['fetchermanipulators'])) {
            $this->fetchermanipulators = $settings['MANIPULATORS']['fetchermanipulators'];
        } else {
            $this->fetchermanipulators = null;
        }

        if (!$this->createTempDirectory()) {
            $this->log->addError(
                "OAI-PMH fetcher",
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
    * @return array The records.
    */
    public function getRecords($limit = null)
    {
        static $filtered_records;
        if (!isset($filtered_records) || $this->use_cache == false) {
            $client = new Client($this->endpoint);
            $endpoint = new Endpoint($client);
            $records = $endpoint->listRecords($this->metadataPrefix, $this->from, $this->until, $this->setSpec);
            foreach ($records as $rec) {
                $identifier = urlencode($rec->header->identifier);
                file_put_contents($this->tempDirectory . DIRECTORY_SEPARATOR . $identifier .
                    '.metadata', $rec->asXML());
                // MIK expects each record to be an object with a ->key property.
                $record = new \stdClass();
                $record->key = $identifier;
                $filtered_records[] = $record;
            }

            if (!is_null($limit)) {
                $filtered_records = array_slice($filtered_records, 0, $limit, true);
            }

            // Unlike other fetchers, we apply manipulators to $filtered_records,
            // not $records.
            if (count($this->fetchermanipulators)) {
                $filtered_records = $this->applyFetchermanipulators($filtered_records);
            }
        }
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
        $iterator = new \FilesystemIterator($this->tempDirectory, \FilesystemIterator::SKIP_DOTS);
        return iterator_count($iterator);
    }

    /**
     * @note: This function is copied from the CSV fetcher and will need to
     *        be updated for the OAI fetcher.
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
        return file_get_contents($raw_metadata_cache);
    }

    /**
     * Applies the fetchermanipulator listed in the config.
     *
     * @note: Each member of $records contains only an identifier. Therefore,
     *     OAI-PMH toolchain fetcher manipulators will need to retrieve the full
     *     record in order to inspect the contents of the OAI-PMH records.
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
}
