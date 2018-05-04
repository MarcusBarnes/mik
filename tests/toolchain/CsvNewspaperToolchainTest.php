<?php

/**
 * This file is named CsvNewspaperToolchainTest.php so that it is run after
 * CsvSingleFileToolchainTest.php and CsvToJsonToolchain.php. Otherwise,
 * the following errors occur:
 *
 *   There were 2 failures:
 *
 *   1) mik\writers\CsvSingleFileToolchainTest::testGetRecords
 *   Failed asserting that actual size 2 matches expected size 20.
 *
 *   /home/mark/Documents/hacking/mik/tests/CsvSingleFileToolchainTest.php:33
 *
 *   2) mik\writers\CsvToJsonToolchain::testGetRecords
 *   Failed asserting that actual size 2 matches expected size 20.
 *
 *   /home/mark/Documents/hacking/mik/tests/CsvToJsonToolchainTest.php:35
 *
 * These errors likely have something to do with the visibility of the $csv
 * fetcher class but life is too short to confirm that.
 */

namespace mik\tests\toolchain;

use mik\fetchers\Csv;
use mik\metadataparsers\mods\CsvToMods;
use mik\tests\MikTestBase;
use mik\filegetters\CsvNewspapers as CsvNewspapersGetter;
use mik\writers\CsvNewspapers as CsvNewspapersWriter;

/**
 * Class CsvNewspaperToolchainTest
 * @package mik\tests\toolchain
 */
class CsvNewspaperToolchainTest extends MikTestBase
{

    /**
     * @var string
     */
    private $path_to_mods_schema;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->path_to_temp_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mik_csv_newspaper_temp_dir";
        parent::setUp();

        $this->path_to_output_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mik_csv_newspaper_output_dir";
        @mkdir($this->path_to_output_dir);
        $this->path_to_log = $this->path_to_temp_dir . DIRECTORY_SEPARATOR . "mik.log";
        $this->path_to_mods_schema = realpath(
            $this->asset_base_dir . DIRECTORY_SEPARATOR . '../../extras/scripts/mods-3-5.xsd'
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
                'input_file' => $this->asset_base_dir . '/csv/newspapers/metadata/newspapers_metadata.csv',
                'temp_directory' => $this->path_to_temp_dir,
                'record_key' => 'Identifier',
                'use_cache' => false
             ),
            'LOGGING' => array(
                'path_to_log' => $this->path_to_log,
            ),
        );
        $csv = new Csv($settings);
        $records = $csv->getRecords();
        $this->assertCount(2, $records);
    }

    /**
     * @covers \mik\fetchers\Csv::getItemInfo()
     */
    public function testGetItemInfo()
    {
        $settings = array(
            'FETCHER' => array(
                'class' => 'Csv',
                'input_file' => $this->asset_base_dir . '/csv/newspapers/metadata/newspapers_metadata.csv',
                'record_key' => 'Identifier',
                'temp_directory' => $this->path_to_temp_dir,
                'use_cache' => false
             ),
            'LOGGING' => array(
                'path_to_log' => $this->path_to_log,
            ),
        );
        $csv = new Csv($settings);
        $record = $csv->getItemInfo('TT0001');
        $this->assertEquals('1907-08-18', $record->Date, "Record date is not 1907-08-18");
    }

    /**
     * @covers \mik\metadataparsers\mods\CsvToMods::metadata()
     */
    public function testCreateMetadata()
    {
        $settings = array(
            'FETCHER' => array(
                'class' => 'Csv',
                'input_file' => $this->asset_base_dir . '/csv/newspapers/metadata/newspapers_metadata.csv',
                'record_key' => 'Identifier',
                'temp_directory' => $this->path_to_temp_dir,
                'use_cache' => false
             ),
            'LOGGING' => array(
                'path_to_log' => $this->path_to_log,
            ),
            'METADATA_PARSER' => array(
                'mapping_csv_path' => $this->asset_base_dir . '/csv/newspapers/metadata/newspapers_mappings.csv',
            ),
        );

        $parser = new CsvToMods($settings);
        $mods = $parser->metadata('TT0002');

        $dom = new \DOMDocument;
        $dom->loadXML($mods);

        $this->assertTrue(
            $dom->schemaValidate($this->path_to_mods_schema),
            "MODS document generate by CSV to MODS metadata parser did not validate"
        );
        $date_element = <<<XML
  <originInfo>
    <dateIssued encoding="w3cdtf" keyDate="yes">1918-12-14</dateIssued>
  </originInfo>
XML;
        $this->assertContains($date_element, $mods, "CSV to MODS metadata parser did not work");
    }

    /**
     * @covers \mik\filegetters\CsvNewspapers::getChildren()
     * @covers \mik\metadataparsers\mods\CsvToMods::metadata()
     * @covers \mik\writers\CsvNewspapers::writePackages()
     */
    public function testWritePackages()
    {
        $settings = array(
            'FETCHER' => array(
                'class' => 'Csv',
                'input_file' => $this->asset_base_dir . '/csv/newspapers/metadata/newspapers_metadata.csv',
                'record_key' => 'Identifier',
                'temp_directory' => $this->path_to_temp_dir,
                'use_cache' => false
             ),
            'FILE_GETTER' => array(
                'validate_input' => false,
                'class' => 'CsvNewspapers',
                'input_directory' => $this->asset_base_dir . '/csv/newspapers/files/flat',
                'temp_directory' => $this->path_to_temp_dir,
                'file_name_field' => 'Directory',
                'use_cache' => false,
             ),
            'METADATA_PARSER' => array(
                'mapping_csv_path' => $this->asset_base_dir . '/csv/newspapers/metadata/newspapers_mappings.csv',
            ),
            'WRITER' => array(
                'output_directory' => $this->path_to_output_dir,
                'metadata_filename' => 'MODS.xml',
             ),
            'LOGGING' => array(
                'path_to_log' => $this->path_to_log,
            ),
        );

        $file_getter = new CsvNewspapersGetter($settings);
        $pages = $file_getter->getChildren('TT0002');

        $parser = new CsvToMods($settings);
        $mods = $parser->metadata('TT0002');

        $writer = new CsvNewspapersWriter($settings);
        $writer->writePackages($mods, $pages, 'TT0002');

        $written_metadata = file_get_contents($this->path_to_output_dir . DIRECTORY_SEPARATOR . 'TT0002/MODS.xml');
        $date_element = <<<XML
  <titleInfo>
    <title>Testing Times, December 14, 1918</title>
  </titleInfo>
XML;
        $this->assertContains($date_element, $written_metadata, "CSV to MODS metadata parser did not work");

        $this->assertFileExists(
            $this->path_to_output_dir . DIRECTORY_SEPARATOR . 'TT0002/3/OBJ.tif',
            "OBJ.tif file was not written by CsvNewspapers toolchain."
        );

        $this->assertFileExists(
            $this->path_to_output_dir . DIRECTORY_SEPARATOR . 'TT0002/3/OCR.txt',
            "OCR.txt file was not written by CsvNewspapers toolchain."
        );
    }
}
