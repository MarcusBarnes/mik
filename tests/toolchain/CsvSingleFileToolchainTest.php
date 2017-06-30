<?php

namespace mik\tests\toolchain;

use mik\fetchers\Csv;
use mik\metadataparsers\mods\CsvToMods;
use mik\tests\MikTestBase;
use mik\writers\CsvSingleFile;

/**
 * Class CsvSingleFileToolchainTest
 * @package mik\tests\toolchain
 * @group toolchain
 */
class CsvSingleFileToolchainTest extends MikTestBase
{

    /**
     * Path to validator log.
     * @var string
     */
    private $path_to_input_validator_log;

    /**
     * Path to MODS schema.
     * @var
     */
    private $path_to_mods_schema;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->path_to_temp_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mik_csv_fetcher_temp_dir";
        $this->path_to_output_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mik_csv_single_file_output_dir";
        $this->path_to_log = $this->path_to_temp_dir . DIRECTORY_SEPARATOR . "mik.log";
        $this->path_to_input_validator_log = $this->path_to_temp_dir . DIRECTORY_SEPARATOR . "input_validator.log";
        $this->path_to_mods_schema = realpath(
            $this->asset_base_dir . DIRECTORY_SEPARATOR . '/../../extras/scripts/mods-3-5.xsd'
        );
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
                'use_cache' => false,
                'input_file' => $this->asset_base_dir . '/csv/sample_metadata.csv',
                'temp_directory' => $this->path_to_temp_dir,
                'record_key' => 'ID',
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
     * @covers \mik\metadataparsers\mods\CsvToMods::metadata()
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
            'METADATA_PARSER' => array(
                'mapping_csv_path' => $this->asset_base_dir . '/csv/sample_mappings.csv',
            ),
        );

        $parser = new CsvToMods($settings);
        $mods = $parser->metadata('postcard_1');

        $dom = new \DOMDocument;
        $dom->loadXML($mods);

        $this->assertTrue(
            $dom->schemaValidate($this->path_to_mods_schema),
            "MODS document generate by CSV to MODS metadata parser did not validate"
        );
        $date_element = <<<XML
  <originInfo>
    <dateIssued encoding="w3cdtf">1954</dateIssued>
  </originInfo>
XML;
        $this->assertContains($date_element, $mods, "CSV to MODS metadata parser did not work");
    }

    /**
     * @covers \mik\writers\CsvSingleFile::writePackages()
     */
    public function testWritePackages()
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
                'input_directory' => $this->asset_base_dir . '/csv',
                'file_name_field' => 'File',
                'use_cache' => false,
             ),
            'METADATA_PARSER' => array(
                'mapping_csv_path' => $this->asset_base_dir . '/csv/sample_mappings.csv',
            ),
            'WRITER' => array(
                'output_directory' => $this->path_to_output_dir,
             ),
            'LOGGING' => array(
                'path_to_log' => $this->path_to_log,
            ),
        );

        $parser = new CsvToMods($settings);
        $mods = $parser->metadata('postcard_1');

        $writer = new CsvSingleFile($settings);
        $writer->writePackages($mods, array(), 'postcard_1');

        $date_element = <<<XML
  <originInfo>
    <dateIssued encoding="w3cdtf">1954</dateIssued>
  </originInfo>
XML;
        $this->assertContains($date_element, $mods, "CSV to MODS metadata parser did not work");

        $this->assertFileExists(
            $this->path_to_output_dir . DIRECTORY_SEPARATOR . 'postcard_1.jpg',
            "Postcard_1.jpg file was not written by CsvSingleFile toolchain."
        );
    }
}
