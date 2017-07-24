<?php

namespace mik\fetchers;

use mik\tests\MikTestBase;

/**
 * Class FetcherManipulatorTest
 * @package mik\fetchers
 * @group fetchermanipulators
 */
class FetcherManipulatorTest extends MikTestBase
{
    /**
     * Settings for tests.
     * @var array
     */
    private $settings;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->path_to_temp_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mik_csv_fetcher_temp_dir";
        $this->path_to_log = $this->path_to_temp_dir . DIRECTORY_SEPARATOR . "mik.log";
        $this->settings  = [
            'FETCHER' => [
                'input_file' => $this->asset_base_dir . '/csv/sample_metadata.csv',
                'temp_directory' => $this->path_to_temp_dir,
                'record_key' => 'ID',
                'use_cache' => false,
            ],
            'LOGGING' => [
                'path_to_log' => $this->path_to_log,
                'path_to_manipulator_log' => '',
            ],
        ];
    }

    /**
     * @covers \mik\fetchermanipulators\RandomSet
     */
    public function testRandomSetFetcherManipulator()
    {
        $settings = $this->settings + [
            'MANIPULATORS' => [
                'fetchermanipulators' => ['RandomSet|5'],
             ],
        ];
        $csv = new Csv($settings);
        $records = $csv->getRecords();
        $this->assertCount(5, $records, "Random set manipulator did not work");
    }

    /**
     * @covers \mik\fetchermanipulators\RangeSet
     */
    public function testRangeSetFetcherManipulatorLimit()
    {
        $settings = $this->settings + [
            'MANIPULATORS' => [
                'fetchermanipulators' => ['RangeSet|5,10'],
             ],
        ];
        $csv = new Csv($settings);
        $records = $csv->getRecords();
        $this->assertRegExp('/postcard_14/', $records[4]->ID);
    }

    /**
     * @covers \mik\fetchermanipulators\RangeSet
     */
    public function testRangeSetFetcherManipulatorLessThan()
    {
        $settings = $this->settings + [
            'MANIPULATORS' => [
                'fetchermanipulators' => ['RangeSet|<postcard_10'],
             ],
        ];
        $csv = new Csv($settings);
        $records = $csv->getRecords();
        $this->assertRegExp('/postcard_9/', $records[8]->ID);
    }

    /**
     * @covers \mik\fetchermanipulators\RangeSet
     */
    public function testRangeSetFetcherManipulatorGreaterThan()
    {
        $settings = $this->settings + [
            'MANIPULATORS' => [
                'fetchermanipulators' => ['RangeSet|>postcard_18'],
             ],
        ];
        $csv = new Csv($settings);
        $records = $csv->getRecords();
        $this->assertRegExp('/postcard_20/', $records[1]->ID);
    }

    /**
     * @covers \mik\fetchermanipulators\RangeSet
     */
    public function testRangeSetFetcherManipulatorBetween()
    {
        $settings = $this->settings + [
            'MANIPULATORS' => [
                'fetchermanipulators' => ['RangeSet|postcard_9@postcard_16'],
             ],
        ];
        $csv = new Csv($settings);
        $records = $csv->getRecords();
        $this->assertRegExp('/postcard_15/', $records[5]->ID);
    }

    /**
     * @covers \mik\fetchermanipulators\CsvSingleFileByExtension
     */
    public function testCsvSingleFileByExtensionFetcherManipulator()
    {
        $settings = $this->settings + [
            'FILE_GETTER' => [
                'validate_input' => 'false',
                'file_name_field' => 'File',
             ],
            'MANIPULATORS' => [
                'fetchermanipulators' => ['CsvSingleFileByExtension|jpg,jpeg'],
             ],
        ];
        $csv = new Csv($settings);
        $records = $csv->getRecords();
        $this->assertCount(8, $records, "CsvSingleFileByExtension manipulator did not work");
    }

    /**
     * @covers \mik\fetchermanipulators\CsvSingleFileByFilename
     */
    public function testCsvSingleFileByFilenameFetcherManipulator()
    {
        $settings = $this->settings + [
            'FILE_GETTER' => [
                'validate_input' => 'false',
                'file_name_field' => 'File',
             ],
            'MANIPULATORS' => [
                'fetchermanipulators' => ['CsvSingleFileByFilename|postcard_1'],
             ],
        ];
        $csv = new Csv($settings);
        $records = $csv->getRecords();
        $this->assertCount(10, $records, "CsvSingleFileByFilename manipulator did not work");
    }
}
