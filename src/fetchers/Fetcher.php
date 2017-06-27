<?php

namespace mik\fetchers;

use \Monolog\Logger;

/**
 * Fetcher (abstract):
 *    Browse, read, and gather information about records
 *    that comprise a given collection.
 *
 *    Extend this abstract class with for specific implemenations.
 *    For example, see fetchers/Cdm.php and fecthers/Csv.php.
 *
 *    Note that methods marked as abstract must be defined in
 *    the extending class.
 *
 *    Abstract methods:
 *        - getNumRecs
 *        - getItemInfo
 */
abstract class Fetcher
{
    /**
     * @var array $settings - configuration settings from confugration class.
     */
    public $settings;

    /**
     * Create a new Fetcher Instance
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        $this->settings = $settings['FETCHER'];
        // Make a copy of all setting to pass to fetcher manipulators.
        $this->all_settings = $settings;

        // Set up logger.
        $this->pathToLog = $settings['LOGGING']['path_to_log'];
        if (isset($settings['LOGGING']['log_level'])) {
            eval("\$logLevel = \Monolog\Logger::" . strtoupper($settings['LOGGING']['log_level']) . ";");
        } else {
            $logLevel = Logger::INFO;
        }

        $this->log = new \Monolog\Logger('fetcher');
        $this->logStreamHandler= new \Monolog\Handler\StreamHandler($this->pathToLog, $logLevel);
        $this->log->pushHandler($this->logStreamHandler);
    }

    /**
     * Create the temp directory specified in the config file.
     */
    public function createTempDirectory()
    {
        $this->tempDirectory = $this->settings['temp_directory'];
        if (file_exists($this->tempDirectory)) {
            return true; // directory already exists.
        } else {
            // mkdir returns true if successful; false otherwise.
            return mkdir($this->tempDirectory, 0777, true);
        }
    }

    /**
     * Returns the number of records under consideration.
     *    For CONTENTdm, this will be the number of records in a collection.
     *    For CSV, this will be the number of rows of data with a unique index.
     *
     * @return total number of records
     *
     * Note that extending classes must define this method.
     */
    abstract public function getNumRecs();

    /**
     * Returns a hashed array or object containing a record's information.
     *
     * Also caches a serialized version of the raw (from source) record to disk
     * in a directory as defined in the [FETCHER] temp_directory configuration settings.
     *
     * @param string $recordKey the unique record_key
     *      For CONTENTdm, this will be the item pointer
     *      For CSV, this will the the unique id assisgned to a row of data.
     *
     * @return array or object of record info.
     */
    abstract public function getItemInfo($recordKey);

    /**
    * Return an object of records.
    *
    * @param $limit int
    *   The number of records to get - CLI optional argument.
    *
    * @return object The records.
    */
    abstract public function getRecords($limit);
}
