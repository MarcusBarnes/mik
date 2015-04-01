<?php

namespace mik\fetchers;

class Cdm extends Fetcher
{
    /**
     * @var array $settings - configuration settings from confugration class.
     */
    public $settings;

    /**
     * Create a new CONTENTdm Fetcher Instance
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        $this->settings = $settings['FETCHER'];
    }

    /**
     *
     */
    protected $chunck_size = 100;

    /**
     *
     */
    protected $start_at = 1;

    /**
     *
     */
    protected $last_rec = 0;

    /**
     * CONTENTdm nicknames for administrative fields.
     */
    protected $admin_fields = array(
      'fullrs', 'find', 'dmaccess', 'dmimage', 'dmcreated', 'dmmodified', 'dmoclcno', 'dmrecord'
    );

    protected function setQueryMap()
    {
      $this->queryMap = array(
        'alias' => $this->settings['alias'],
        'searchstrings' => '0',
        // We ask for as little possible info at this point since we'll be doing another query 
        // on each item later.
        'fields' => 'dmcreated',
        'sortby' => 'dmcreated!dmrecord',
        'maxrecs' => $this->chunk_size,
        'start' => $start_at,
        // We only want top-level items, not pages at this point.
        'supress' => 1,
        'docptr' => 0,
        'suggest' => 0,
        'facets' => 0,
        'format' => 'json'
      );
    }

    protected function getQueryMap()
    {
      return $this->queryMap;
    }

    /**
     * Query CONTENTdm with the values in the query map and return an array of records.
     */
    public function queryContentdm()
    {
      $query_map = $this->getQueryMap();
      $qm = $query_map;
      $query = $this->settings['ws_url'] . 'dmQuery'. $qm['alias'] . '/'. $qm['searchstrings'] .
        '/'. $qm['fields'] . '/'. $qm['sortby'] . '/'. $qm['maxrecs'] . '/'. $this->start_at .
        '/'. $qm['supress'] . '/'. $qm['docptr'] . '/'.  $qm['suggest'] . '/'. $qm['facets'] .
        '/' . $qm['format'];

      // Query CONTENTdm and return records; if failure, log problem.
      if ($json = file_get_contents($query, false, NULL)) {
        return json_decode($json, true);
      } else {
        $message = date('c') . "\t". 'Query failed:' . "\t" . $query . "\n";
        return FALSE;
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
    public function getRecords()
    {
        return array(1, 2, 3, 4, 5);
    }

}
