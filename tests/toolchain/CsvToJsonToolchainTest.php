<?php

namespace mik\tests\toolchain;

use mik\fetchers\Csv;
use mik\metadataparsers\json\CsvToJson;
use mik\tests\MikTestBase;
use mik\writers\CsvSingleFileJson;

/**
 * Class CsvToJsonToolchain
 * @package mik\tests\toolchain
 * @group toolchain
 */
class CsvToJsonToolchain extends MikTestBase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->path_to_temp_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mik_csv_json_temp_dir";
        $this->path_to_output_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mik_csv_json_output_dir";
        $this->path_to_log = $this->path_to_temp_dir . DIRECTORY_SEPARATOR . "mik.log";
    }

    /**
     * @covers \mik\fetchers\Csv::getRecords()
     */
    public function testGetRecords()
    {
        // Define settings here, not in a configuration file.
        $settings = array(
            'FETCHER' => array(
                'class' => 'Csv',
                'input_file' => $this->asset_base_dir . '/csv/sample_metadata.csv',
                'temp_directory' => $this->path_to_temp_dir,
                'record_key' => 'ID',
                'use_cache' => true,
            ),
            'FILE_GETTER' => array(
                'validate_input' => false,
                'class' => 'CsvSingleFile',
                'file_name_field' => 'File',
                'use_cache' => false,
            ),
            'LOGGING' => array(
                'path_to_log' => $this->path_to_log,
            ),
        );
        $csv = new Csv($settings);
        $records = $csv->getRecords();
        $this->assertCount(20, $records);
    }

    /**
     * @covers \mik\fetchers\Csv::getItemInfo()
     */
    public function testGetItemInfo()
    {
        $settings = array(
            'FETCHER' => array(
                'class' => 'Csv',
                'input_file' => $this->asset_base_dir . '/csv/sample_metadata.csv',
                'record_key' => 'ID',
                'temp_directory' => $this->path_to_temp_dir,
                'use_cache' => false,
            ),
            'FILE_GETTER' => array(
                'validate_input' => false,
                'class' => 'CsvSingleFile',
                'file_name_field' => 'File',
                'use_cache' => false,
            ),
            'LOGGING' => array(
                'path_to_log' => $this->path_to_log,
            ),
        );
        $csv = new Csv($settings);
        $record = $csv->getItemInfo('postcard_3');
        $this->assertEquals('1947', $record->Date, "Record date is not 1947");
    }

    /**
     * @covers \mik\metadataparsers\json\CsvToJson::metadata()
     */
    public function testCreateMetadata()
    {
        $settings = array(
            'FETCHER' => array(
                'class' => 'Csv',
                'input_file' => $this->asset_base_dir . '/csv/sample_metadata.csv',
                'record_key' => 'ID',
                'temp_directory' => $this->path_to_temp_dir,
                'use_cache' => false,
            ),
            'FILE_GETTER' => array(
                'validate_input' => false,
                'class' => 'CsvSingleFile',
                'file_name_field' => 'File',
                'use_cache' => false,
            ),
            'LOGGING' => array(
                'path_to_log' => $this->path_to_log,
            ),
        );
        $parser = new CsvToJson($settings);
        $json = $parser->metadata('postcard_1');
        $json_as_array = json_decode($json, true);
        $this->assertEquals('1954', $json_as_array['Date'], "Record date is not 1954");
    }

    /**
     * @covers \mik\writers\CsvSingleFileJson::writePackages()
     */
    public function testWritePackages()
    {
        $settings = array(
            'FETCHER' => array(
                'class' => 'Csv',
                'input_file' => dirname(__FILE__) . '/assets/csv/sample_metadata.csv',
                'record_key' => 'ID',
                'temp_directory' => $this->path_to_temp_dir,
                'use_cache' => false,
             ),
            'FILE_GETTER' => array(
                'validate_input' => false,
                'class' => 'CsvSingleFile',
                'input_directory' => $this->asset_base_dir . '/csv',
                'file_name_field' => 'File',
                'use_cache' => false,
             ),
            'WRITER' => array(
                'output_directory' => $this->path_to_output_dir,
             ),
            'LOGGING' => array(
                'path_to_log' => $this->path_to_log,
            ),
        );

        $parser = new CsvToJson($settings);
        $json = $parser->metadata('postcard_1');

        $writer = new CsvSingleFileJson($settings);
        $writer->writePackages($json, array(), 'postcard_1');

        $written_metadata = file_get_contents($this->path_to_output_dir . DIRECTORY_SEPARATOR . 'postcard_1.json');
        $written_metadata_as_array = json_decode($written_metadata, true);

        $this->assertEquals('1954', $written_metadata_as_array['Date'], "Record date is not 1954");
        $this->assertFileExists(
            $this->path_to_output_dir . DIRECTORY_SEPARATOR . 'postcard_1.jpg',
            "Postcard_1.jpg file was not written by CsvToJson toolchain."
        );
    }
}
