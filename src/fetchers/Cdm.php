<?php

namespace mik\fetchers;

class Cdm extends Fetcher
{

    /**
     * @var array $settings - configuration settings from confugration class.
     */
    public $settings;

    /**
     * @var string $key - record identifier, id, key
     * For CONTENTdm, this is the pointer CONTENTdm property.
     * For csv input, this may be the row number.
     */
    public $key;

    /**
     *
     */
    protected $chunk_size = 100;

    /**
     *
     */
    protected $start_at = 1;

    /**
     *
     */
    protected $last_rec = 0;
    
    /**
     *
     */
    public $totalRecordsInCollection;

    /**
     * @var string $fetchermanipulators - the fetchermanipulors from config,
     *   in the form fetchermanipulator_class_name|param_0|param_1|...|param_n
     */
    public $fetchermanipulators;

    /**
     * Define a template for the CONTENTdm query. Some values, i.e.,
     * alias, maxrecs, and start, are taken from either configuration
     * values or from object properties.
     */
    protected $browseQueryMap = array(
        // 'alias' => 'foo',
        'searchstrings' => '0',
        // We ask for as little possible info at this point since we'll
        // be doing another query on each item later.
        'fields' => 'dmcreated',
        'sortby' => 'dmcreated!dmrecord',
        // 'maxrecs' => 1000,
        // 'start' => 1,
        // We only want top-level items, not children, at this point.
        'supress' => 1,
        'docptr' => 0,
        'suggest' => 0,
        'facets' => 0,
        'format' => 'json'
      );

    /**
     * Create a new CONTENTdm Fetcher Instance.
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        $this->settings = $settings['FETCHER'];
        // We make a copy of all setting to pass to fetcher manipulators.
        $this->all_settings = $settings;
        $this->key = $this->settings['record_key'];
        $this->thumbnail = new \mik\filemanipulators\ThumbnailFromCdm($settings);

        if (isset($settings['MANIPULATORS']['fetchermanipulators'])) {
            $this->fetchermanipulators = $settings['MANIPULATORS']['fetchermanipulators'];
        }
        else {
            $this->fetchermanipulators = null;
        }
    }

    /**
     * CONTENTdm nicknames for administrative fields.
     */
    protected $admin_fields = array(
      'fullrs', 'find', 'dmaccess', 'dmimage', 'dmcreated', 'dmmodified', 'dmoclcno', 'dmrecord'
    );

    /**
     * Query CONTENTdm with the values in the query map and return an array of records.
     * @todo: account for CONTENTdm's limit of only returning 1024 records per query.
     */
    public function queryContentdm($limit)
    {
        // Limit the number of records.
        if ($limit != null && $limit >= 0) {
            $this->chunk_size = $limit;
        }

        $qm = $this->browseQueryMap;
        $query = $this->settings['ws_url'] . 'dmQuery/'. $this->settings['alias'] .
            '/'. $qm['searchstrings'] . '/'. $qm['fields'] . '/'. $qm['sortby'] .
            '/'. $this->chunk_size . '/'. $this->start_at . '/'. $qm['supress'] .
            '/'. $qm['docptr'] . '/'.  $qm['suggest'] . '/'. $qm['facets'] .
            '/' . $qm['format'];

        // Query CONTENTdm and return records; if failure, log problem.
        if ($json = file_get_contents($query, false, null)) {
            $output = json_decode($json);
            $output = $this->addKeyPropertyForRecords($output);
            return $output;
        } else {
            $message = date('c') . "\t". 'Query failed:' . "\t" . $query . "\n";
            // @todo: Log failure.
            return false;
        }
    }
    
    /**
     * Adds key property to record properties.
     * In the case of CONTENTdm, this will be the value of the pointer property.
     * @param $propertiesOfRecordsArray array
     * @return array
     */
    private function addKeyPropertyForRecords($propertiesOfRecordsObj)
    {

        $arrayOfRecordObjects = array();

        foreach ($propertiesOfRecordsObj->records as $recordProperties) {

            if (isset($recordProperties->pointer)) {
                $record_key = $this->key;
                $recordProperties->key = $recordProperties->$record_key;
            }
            $arrayOfRecordObjects[] = $recordProperties;
        }

        $propertiesOfRecordsObj->records = $arrayOfRecordObjects;

        return $propertiesOfRecordsObj;
    }

    /**
     * Query CDM for total records for a colletion.
     */
    public function getNumRecs()
    {
        $qm = $this->browseQueryMap;
        $query = $this->settings['ws_url'] . 'dmQueryTotalRecs/'
          . $this->settings['alias'] . '|0/xml';
        // Query CONTENTdm and return records; if failure, log problem.
        if ($xml = file_get_contents($query, false, null)) {
            $doc = new \DomDocument('1.0');
            $doc->loadXML($xml);
            return $doc->getElementsByTagName('total')->item(0)->nodeValue;
        } else {
            $message = date('c') . "\t". 'Query failed:' . "\t" . $query . "\n";
            // @todo: Log failure.
            return false;
        }

    }
    
    /**
     * Gets the item's info from CONTENTdm.
     *
     * @param string $pointer
     *  The CONTENTdm pointer for the current object.
     *
     * @return array|bool 
     */
    public function getItemInfo($pointer)
    {
        $wsUrl = $this->settings['ws_url'];
        $alias = $this->settings['alias'];
        $queryUrl = $wsUrl . 'dmGetItemInfo/' . $alias . '/' .
          $pointer . '/json';
        $response = file_get_contents($queryUrl);
        $itemInfo = json_decode($response, true);
        if (is_array($itemInfo)) {
            return $itemInfo;
        } else {
            return false;
        }
    }

    /**
    * Return an array of records.
    *
    * @param int $limit
    *   The number of records to return.
    *
    * @return array The records.
    */
    public function getRecords($limit)
    {
        $results = $this->queryContentdm($limit);
        if ($this->fetchermanipulators) {
            $filtered_records = $this->applyFetchermanipulators($results->records);
        }
        else {
            $filtered_records = $results->records;
        }
        return $filtered_records;
    }

    /**
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

}
