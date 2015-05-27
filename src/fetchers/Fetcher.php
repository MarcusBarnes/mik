<?php

namespace mik\fetchers;

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
     * @return total number of records
     * Note that extending classes must define this method.
     */
    abstract public function queryTotalRec();

    /**
    * A test method.
    *
    * @return string Returns a message.
    */
    public function testMethod()
    {
        return "I am a method defined in the parent Fetcher class.\n";
    }
}
