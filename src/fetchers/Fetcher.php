<?php

namespace mik\fetchers;
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
        // $this->settings = $settings['FETCHER'];
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
