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

    public function testRangeSetFetcherManipulatorLimit()
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
        $this->assertRegExp('/postcard_14/', $records[4]->ID);
    }

    public function testRangeSetFetcherManipulatorLessThan()
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
                'fetchermanipulators' => array('RangeSet|<postcard_10'),
             ),
        );
        $csv = new Csv($settings);
        $records = $csv->getRecords();
        $this->assertRegExp('/postcard_9/', $records[8]->ID);
    }

    public function testRangeSetFetcherManipulatorGreaterThan()
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
                'fetchermanipulators' => array('RangeSet|>postcard_18'),
             ),
        );
        $csv = new Csv($settings);
        $records = $csv->getRecords();
        $this->assertRegExp('/postcard_20/', $records[1]->ID);
    }

    public function testRangeSetFetcherManipulatorBetween()
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
                'fetchermanipulators' => array('RangeSet|postcard_9@postcard_16'),
             ),
        );
        $csv = new Csv($settings);
        $records = $csv->getRecords();
        $this->assertRegExp('/postcard_15/', $records[5]->ID);
    }

    public function testCsvSingleFileByExtensionFetcherManipulator()
    {
        $settings = array(
            'FETCHER' => array(
                'input_file' => dirname(__FILE__) . '/assets/csv/sample_metadata.csv',
                'record_key' => 'ID',
                'use_cache' => false,
             ),
            'FILE_GETTER' => array(
                'file_name_field' => 'File',
             ),
            'MANIPULATORS' => array(
                'fetchermanipulators' => array('CsvSingleFileByExtension|jpg|jpeg'),
             ),
        );
        $csv = new Csv($settings);
        $records = $csv->getRecords();
        $this->assertCount(8, $records, "CsvSingleFileByExtension manipulator did not work");
    }

    public function testCsvSingleFileByFilenameFetcherManipulator()
    {
        $settings = array(
            'FETCHER' => array(
                'input_file' => dirname(__FILE__) . '/assets/csv/sample_metadata.csv',
                'record_key' => 'ID',
                'use_cache' => false,
             ),
            'FILE_GETTER' => array(
                'file_name_field' => 'File',
             ),
            'MANIPULATORS' => array(
                'fetchermanipulators' => array('CsvSingleFileByFilename|postcard_1'),
             ),
        );
        $csv = new Csv($settings);
        $records = $csv->getRecords();
        $this->assertCount(10, $records, "CsvSingleFileByFilename manipulator did not work");
    }
}
