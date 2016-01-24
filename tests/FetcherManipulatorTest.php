<?php

namespace mik\fetchers;

class FetcherManipulatorTest extends \PHPUnit_Framework_TestCase
{
    public function testRandomSetFetcherManipulator()
    {
        $settings = array(
            'FETCHER' => array(
                'input_file' => dirname(__FILE__) . '/assets/csv/sample_metadata.csv',
                'record_key' => 'ID',
                'use_cache' => false,
             ),
            'MANIPULATORS' => array(
                'fetchermanipulators' => array('RandomSet|5'),
             ),
        );
        $csv = new Csv($settings);
        $records = $csv->getRecords();
        $this->assertCount(5, $records, "Random set manipulator did not work");
    }

    public function testRangeSetFetcherManipulator()
    {
        $settings = array(
            'FETCHER' => array(
                'input_file' => dirname(__FILE__) . '/assets/csv/sample_metadata.csv',
                'record_key' => 'ID',
                'use_cache' => false,
             ),
            'LOGGING' => array(
                'path_to_manipulator_log' => '',
             ),
            'MANIPULATORS' => array(
                'fetchermanipulators' => array('RangeSet|5,10'),
             ),
        );
        $csv = new Csv($settings);
        $records = $csv->getRecords();
        $this->assertRegExp('/Aerial/', $records[4]->Title);
    }
}
