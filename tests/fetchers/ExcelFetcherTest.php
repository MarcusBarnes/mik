<?php

namespace mik\fetchers;

use mik\tests\MikTestBase;

/**
 * Class ExcelFetcher
 * @package mik\fetchers
 * @coversDefaultClass \mik\fetchers\ExcelFetcher
 * @group fetchers
 */
class ExcelFetcherTest extends MikTestBase
{
    /**
     * Default settings holder.
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
        $this->settings = [
            'FETCHER' => [
                'input_file' => $this->asset_base_dir . '/excel/sample_metadata.xlsx',
                'record_key' => 'ID',
                'temp_directory' => $this->path_to_temp_dir,
            ],
            'FILE_GETTER' => [
                'validate_input' => false,
                'class' => 'CsvSingleFile',
                'file_name_field' => 'File',
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
        // Define settings here, not in a configuration file.
        $settings = $this->settings;
        $excel = new Excel($settings);
        $records = $excel->getRecords();
        $this->assertCount(20, $records);
    }

    /**
     * @covers ::getNumRecs()
     */
    public function testGetNumRecs()
    {
        $settings = $this->settings;
        $excel = new Excel($settings);
        $num_records = $excel->getNumRecs();
        $this->assertEquals(20, $num_records);
    }

    /**
     * @covers ::getItemInfo()
     */
    public function testGetItemInfo()
    {
        $settings = $this->settings;
        $excel = new Excel($settings);
        $record = $excel->getItemInfo('postcard_3');
        $this->assertEquals('1947', $record->Date, "Record date is not 1947");
    }
}
