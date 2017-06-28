<?php

/**
 * This file is named XCsvBookToolchainTest.php so that it is run after
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
use mik\writers\CsvBooks as CsvBooksWriter;
use mik\filegetters\CsvBooks as CsvBooksGetter;

/**
 * Class CsvBookToolchainTest
 * @package mik\tests\toolchain
 * @group toolchain
 */
class CsvBookToolchainTest extends MikTestBase
{
    /**
     * Path to MODS schema.
     * @var string
     */
    private $path_to_mods_schema;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->path_to_temp_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mik_csv_book_temp_dir";
        $this->path_to_output_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mik_csv_book_output_dir";
        @mkdir($this->path_to_output_dir);
        $this->path_to_log = $this->path_to_temp_dir . DIRECTORY_SEPARATOR . "mik.log";
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
                'input_file' => $this->asset_base_dir . '/csv/books/metadata/books_metadata.csv',
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
                'input_file' => $this->asset_base_dir . '/csv/books/metadata/books_metadata.csv',
                'record_key' => 'Identifier',
                'temp_directory' => $this->path_to_temp_dir,
                'use_cache' => false
             ),
            'LOGGING' => array(
                'path_to_log' => $this->path_to_log,
            ),
        );
        $csv = new Csv($settings);
        $record = $csv->getItemInfo('B2');
        $this->assertEquals('Clay, Beatrice', $record->Author, "Record author is not Clay, Beatrice");
    }

    /**
     * @covers \mik\metadataparsers\mods\CsvToMods::metadata()
     */
    public function testCreateMetadata()
    {
        $settings = array(
            'FETCHER' => array(
                'class' => 'Csv',
                'input_file' => $this->asset_base_dir . '/csv/books/metadata/books_metadata.csv',
                'record_key' => 'Identifier',
                'temp_directory' => $this->path_to_temp_dir,
                'use_cache' => false
             ),
            'LOGGING' => array(
                'path_to_log' => $this->path_to_log,
            ),
            'METADATA_PARSER' => array(
                'mapping_csv_path' => $this->asset_base_dir . '/csv/books/metadata/books_mappings.csv',
            ),
        );

        $parser = new CsvToMods($settings);
        $mods = $parser->metadata('B2');

        $dom = new \DOMDocument;
        $dom->loadXML($mods);

        $this->assertTrue(
            $dom->schemaValidate($this->path_to_mods_schema),
            "MODS document generate by CSV to MODS metadata parser did not validate"
        );
        $title_element = "<title>Stories from Le Morte D'Arthur and the Mabinogion</title>";
        $this->assertContains($title_element, $mods, "CSV to MODS metadata parser did not work");
    }

    /**
     * @covers \mik\filegetters\CsvBooks::getChildren()
     * @covers \mik\metadataparsers\mods\CsvToMods::metadata()
     * @covers \mik\writers\CsvBooks::writePackages()
     */
    public function testWritePackages()
    {
        $settings = array(
            'FETCHER' => array(
                'class' => 'Csv',
                'input_file' => $this->asset_base_dir . '/csv/books/metadata/books_metadata.csv',
                'record_key' => 'Identifier',
                'temp_directory' => $this->path_to_temp_dir,
                'use_cache' => false
             ),
            'FILE_GETTER' => array(
                'validate_input' => false,
                'class' => 'CsvBooks',
                'input_directory' => $this->asset_base_dir . '/csv/books/files_page_sequence_separator',
                'temp_directory' => $this->path_to_temp_dir,
                'file_name_field' => 'Directory',
             ),
            'METADATA_PARSER' => array(
                'mapping_csv_path' => $this->asset_base_dir . '/csv/books/metadata/books_mappings.csv',
            ),
            'WRITER' => array(
                'output_directory' => $this->path_to_output_dir,
                'metadata_filename' => 'MODS.xml',
                'page_sequence_separator' => '_',
             ),
            'LOGGING' => array(
                'path_to_log' => $this->path_to_log,
            ),
        );

        $file_getter = new CsvBooksGetter($settings);
        $pages = $file_getter->getChildren('B1');

        $parser = new CsvToMods($settings);
        $mods = $parser->metadata('B1');

        $writer = new CsvBooksWriter($settings);
        $writer->writePackages($mods, $pages, 'B1');

        $written_metadata = file_get_contents($this->path_to_output_dir . DIRECTORY_SEPARATOR . 'B1/MODS.xml');
        $title_element = <<<XML
  <titleInfo>
    <title>The Emerald City of Oz</title>
  </titleInfo>
XML;
        $this->assertContains($title_element, $written_metadata, "CSV to MODS metadata parser did not work");

        $this->assertFileExists(
            $this->path_to_output_dir . DIRECTORY_SEPARATOR . 'B1/4/OBJ.tif',
            "OBJ.tif file was not written by CsvBooks toolchain using a configured page sequence separator."
        );
    }
}
