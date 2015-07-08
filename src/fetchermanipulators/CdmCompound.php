<?php

namespace mik\fetchermanipulators;

class CdmCompound extends FetcherManipulator
{
/**
     * @var string $type - The CONTENTdm compound object type.
     */
    public $type;

    /**
     * Create a new CdmCompound fetchermanipulator Instance
     * @param array $params The CONTENTdm compound object type.
     *    A single-member array containing one of these strings:
     *    Document, Document-PDF, Document-EAD, Postcard,
     *    Picture Cube, Monograph.
     */
    public function __construct($settings)
    {
        $types = explode('|', $settings['MANIPULATORS']['fetchermanipulator']);
        // Must be one of Document, Document-PDF, Document-EAD,
        // Postcard, Picture Cube, Monograph.
        $this->type = $types[1];
        $this->alias = $settings['FETCHER']['alias'];
        $this->ws_url = $settings['FETCHER']['ws_url'];
    }

    /**
     * Tests each record to see if it has a .cpd file, and if so,
     * what the value of the CPD <type> element is.
     *
     * @param array $records
     *   All of the records from the fetcher.
     * @return array $records
     *   An array of records that pass the test.
     */
    public function manipulate($all_records)
    {
        $filtered_records = array();
        foreach ($all_records->records as $record) {
          $structure = $this->getDocumentStructure($record->pointer);
          if ($record->filetype == 'cpd' && $structure['type'] == 'Document-PDF') {
              $filtered_records[] = $record;
          }
        }
        return $filtered_records;
    }

    /**
     * Gets a CONTENTdm compound document's structure.
     */
    public function getDocumentStructure($pointer)
    {
        $query_url = $this->ws_url . 'dmGetCompoundObjectInfo/' . $this->alias . '/' .
            $pointer . '/json';
        $item_structure = file_get_contents($query_url);
        $item_structure = json_decode($item_structure, true);
        return $item_structure;
    }
}
