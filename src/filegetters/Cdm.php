<?php

namespace mik\filegetters;

class Cdm extends FileGetter
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
    * Friendly welcome
    *
    * @param string $phrase Phrase to return
    *
    * @return string Returns the phrase passed in
    */
    public function echoPhrase($phrase)
    {
        return $phrase . " (from the Cdm filegetter)\n";
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
    
    /**
     * Gets a compound item's children pointers. $alias needs to include the leading '/'.
     * @ToDo - clerify whether this method should be part of filegetters or fetchers.
     */
    public function getChildrenPointers($pointer)
    {
        $alias = $this->settings['alias'];
        $ws_url = $this->settings['ws_url'];
        $query_url = $ws_url . 'dmGetCompoundObjectInfo/' . $alias . '/' .  $pointer . '/json';
        $item_structure = file_get_contents($query_url);
        $item_structure = json_decode($item_structure, true);
        
        // @ToDo - deal with different item structures.
        if (isset($item_structure['page'])) {
            $children = $item_structure['page'];
        } else {
            return array();
        }
        $children_pointers = array();
        foreach ($children as $child) {
            $children_pointers[] = $child['pageptr'];
        }
        return $children_pointers;
    }
}
