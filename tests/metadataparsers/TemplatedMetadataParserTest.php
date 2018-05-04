<?php

namespace mik\metadataparsers\templated;

use mik\fetchers\Csv;
use mik\tests\MikTestBase;
use mik\writers\CsvSingleFile;

/**
 * Class TemplatedMetadataParserTest
 * @package mik\metadataparsers\templated
 * @coversDefaultClass \mik\metadataparsers\templated\Templated
 * @group metadataparsers
 */
class TemplatedMetadataParserTest extends MikTestBase
{

    /**
     * Log path.
     * @var string
     */
    private $path_to_input_validator_log;

    /**
     * Path to MODS schema
     * @var string
     */
    private $path_to_mods_schema;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->path_to_temp_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mik_templated_temp_dir";
        $this->path_to_output_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mik_templated_output_dir";
        $this->path_to_log = $this->path_to_temp_dir . DIRECTORY_SEPARATOR . "mik.log";
        $this->path_to_input_validator_log = $this->path_to_temp_dir . DIRECTORY_SEPARATOR . "input_validator.log";
        $this->path_to_mods_schema = realpath(
            $this->asset_base_dir . DIRECTORY_SEPARATOR . '/../../extras/scripts/mods-3-5.xsd'
        );
    }

    /**
     *  Duplicates \mik\tests\fetchers\CsvFetcherTest::testGetRecords
     */
    public function testGetRecords()
    {
        // Define settings here, not in a configuration file.
        $settings = array(
            'FETCHER' => array(
                'use_cache' => false,
                'input_file' => $this->asset_base_dir . '/csv/sample_metadata.csv',
                'temp_directory' => $this->path_to_temp_dir,
                'record_key' => 'ID',
             ),
            'FILE_GETTER' => array(
                 'validate_input' => false,
                 'class' => 'CsvSingleFile',
                 'file_name_field' => 'File',
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
     * Duplicates \mik\tests\fetchers\CsvFetcherTest::testGetItemInfo
     */
    public function testGetItemInfo()
    {
        $settings = array(
            'FETCHER' => array(
                'input_file' => $this->asset_base_dir . '/csv/sample_metadata.csv',
                'record_key' => 'ID',
                'temp_directory' => $this->path_to_temp_dir,
             ),
            'FILE_GETTER' => array(
                 'validate_input' => false,
                 'class' => 'CsvSingleFile',
                 'file_name_field' => 'File',
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
     * @covers ::metadata
     * @covers ::populateTemplate
     */
    public function testCreateMetadata()
    {
        $settings = array(
            'FETCHER' => array(
                'class' => 'Csv',
                'input_file' => $this->asset_base_dir . '/csv/sample_metadata.csv',
                'record_key' => 'ID',
                'temp_directory' => $this->path_to_temp_dir,
             ),
            'FILE_GETTER' => array(
                 'validate_input' => false,
                 'class' => 'CsvSingleFile',
                 'file_name_field' => 'File',
             ),
            'LOGGING' => array(
                'path_to_log' => $this->path_to_log,
            ),
            'METADATA_PARSER' => array(
                'template' => $this->asset_base_dir . '/templated_metadata_parser/templated_mods_twig.xml',
            ),
        );

        $parser = new Templated($settings);
        $mods = $parser->metadata('postcard_1');

        $dom = new \DOMDocument;
        $dom->loadXML($mods);

        $this->assertTrue(
            $dom->schemaValidate($this->path_to_mods_schema),
            "MODS document generate by Templated metadata parser did not validate"
        );
        $date_element = <<<XML
  <originInfo>
    <dateIssued encoding="w3cdtf">1954</dateIssued>
  </originInfo>
XML;
        $this->assertContains($date_element, $mods, "Templated metadata parser did not work");
    }

    /**
     * Covers CsvSingleFile Writer
     */
    public function testWritePackages()
    {
        $settings = array(
            'FETCHER' => array(
                'class' => 'Csv',
                'input_file' => $this->asset_base_dir . '/csv/sample_metadata.csv',
                'record_key' => 'ID',
                'temp_directory' => $this->path_to_temp_dir,
             ),
            'FILE_GETTER' => array(
                 'validate_input' => false,
                 'class' => 'CsvSingleFile',
                 'input_directory' => $this->asset_base_dir . '/csv',
                 'file_name_field' => 'File',
             ),
            'METADATA_PARSER' => array(
                'template' => $this->asset_base_dir . '/templated_metadata_parser/templated_mods_twig.xml',
            ),
            'WRITER' => array(
                'output_directory' => $this->path_to_output_dir,
                'preserve_content_filenames' => true,
             ),
            'LOGGING' => array(
                'path_to_log' => $this->path_to_log,
            ),
        );

        $parser = new Templated($settings);
        $mods = $parser->metadata('postcard_1');

        $writer = new CsvSingleFile($settings);
        $writer->writePackages($mods, array(), 'postcard_1');

        $date_element = <<<XML
  <originInfo>
    <dateIssued encoding="w3cdtf">1954</dateIssued>
  </originInfo>
XML;
        $this->assertContains($date_element, $mods, "Templated metadata parser did not work");

        $this->assertFileExists(
            $this->path_to_output_dir . DIRECTORY_SEPARATOR . 'postcard_1.jpg',
            "Postcard_1.jpg file was not written by CsvSingleFile toolchain."
        );
    }
}
