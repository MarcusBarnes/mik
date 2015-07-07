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
    public function __construct($params)
    {
        $this->type = $params[0];
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
    public function manipulate($records)
    {
        // @todo: Add test logic.
        return $records;
    }
}
