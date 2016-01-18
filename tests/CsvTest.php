<?php

namespace mik\fetchers;

class CsvTest extends \PHPUnit_Framework_TestCase
{

    public function testGetRecords()
    {
        // We define settings here, not in a configuration file.
        $settings = array(
            'FETCHER' => array(
                'input_file' => dirname(__FILE__) . '/assets/test.csv',
                'record_key' => 'ID',
                'field_delimiter' => ',',
             )
        );
        $csv = new Csv($settings);
        $records = $csv->getRecords();
        $this->assertCount(3, $records);
    }
}
