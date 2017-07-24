<?php

namespace mik\fetchers;

use mik\tests\MikTestBase;

/**
 * Class CsvFetcher
 * @package mik\fetchers
 * @coversDefaultClass \mik\fetchers\Csv
 * @group fetchers
 */
class CsvFetcherTest extends MikTestBase
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
        $this->path_to_temp_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mik_csv_fetcher_temp_dir";
        parent::setUp();
        $this->path_to_log = $this->path_to_temp_dir . DIRECTORY_SEPARATOR . "mik.log";
        // Define settings here, not in a configuration file.
        $this->settings = [
            'FETCHER' => [
                'input_file' => $this->asset_base_dir . '/csv/sample_metadata.csv',
                'temp_directory' => $this->path_to_temp_dir,
                'record_key' => 'ID',
                'use_cache' => false,
            ],
            'FILE_GETTER' => [
                'validate_input' => false,
                'class' => 'CsvSingleFile',
                'file_name_field' => 'File',
                'use_cache' => false,
            ],
            'LOGGING' => [
                'path_to_log' => $this->path_to_log,
            ],
        ];
    }

    /**
     * @covers ::getRecords()
     */
    public function testGetRecords()
    {
        $settings = $this->settings;
        $csv = new Csv($settings);
        $records = $csv->getRecords();
        $this->assertCount(20, $records);
    }

    /**
     * @covers ::getNumRecs()
     */
    public function testGetNumRecs()
    {
        $settings = $this->settings;
        $csv = new Csv($settings);
        $num_records = $csv->getNumRecs();
        $this->assertEquals(20, $num_records);
    }

    /**
     * @covers ::getItemInfo()
     */
    public function testGetItemInfo()
    {
        $settings = $this->settings;
        $csv = new Csv($settings);
        $record = $csv->getItemInfo('postcard_3');
        $this->assertEquals('1947', $record->Date, "Record date is not 1947");
    }
}
