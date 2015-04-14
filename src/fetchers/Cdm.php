<?php

namespace mik\fetchers;

class Cdm extends Fetcher
{

    /**
     * @var array $settings - configuration settings from confugration class.
     */
    public $settings;

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
            return json_decode($json);
        } else {
            $message = date('c') . "\t". 'Query failed:' . "\t" . $query . "\n";
            // @todo: Log failure.
            return false;
        }
    }

    /**
     * Query CDM for total records for a colletion.
     */
    public function queryTotalRec()
    {
        $qm = $this->browseQueryMap;
        $query = $this->settings['ws_url'] . 'dmQueryTotalRecs/'
          . $this->settings['alias'] . '|0/xml';
        //return $query;
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
    * Friendly welcome
    *
    * @param string $phrase Phrase to return
    *
    * @return string Returns the phrase passed in
    */
    public function echoPhrase($phrase)
    {
        return $phrase . " (from the Cdm fetcher)\n";
    }

    /**
    * Return an array of records.
    *
    * @return array The records.
    */
    public function getRecords($limit)
    {
        // return array(1, 2, 3, 4, 5);
        return $this->queryContentdm($limit);
    }
}
