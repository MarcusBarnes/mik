<?php

namespace mik\fetchermanipulators;
use GuzzleHttp\Client;
use League\CLImate\CLImate;

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
    public function __construct($settings, $manipulator_settings)
    {
        $manipulator_params = array_slice($manipulator_settings, 1);
        // Must be one of Document, Document-PDF, Document-EAD,
        // Postcard, Picture Cube, Monograph.
        $this->type = $manipulator_params[0];
        $this->alias = $settings['FETCHER']['alias'];
        $this->ws_url = $settings['FETCHER']['ws_url'];
    }

    /**
     * Tests each record to see if it has a .cpd file, and if so,
     * what the value of the CPD <type> element is.
     *
     * @param array $all_records
     *   All of the records from the fetcher.
     * @return array $filtered_records
     *   An array of records that pass the test(s) defined in the fetcher manipulator.
     */
    public function manipulate($all_records)
    {
        $numRecs = count($all_records);
        echo "Fetching $numRecs records, filitering them.\n";
        // Instantiate the progress bar.
        $climate = new \League\CLImate\CLImate;
        $progress = $climate->progress()->total($numRecs);

        $record_num = 0;
        $filtered_records = array();
        foreach ($all_records as $record) {
            $structure = $this->getDocumentStructure($record->pointer);
            if ($record->filetype == 'cpd' && $structure['type'] == $this->type) {
                $filtered_records[] = $record;
            }
            $record_num++;  
            $progress->current($record_num);
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
        // Create a new Guzzle client to fetch the CPD (stucture)   file.
        $client = new Client();
        $response = $client->get($query_url);
        $body = $response->getBody();
        $item_structure = json_decode($body, true);
        return $item_structure;
    }
}
