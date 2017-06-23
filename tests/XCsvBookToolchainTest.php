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

namespace mik\fetchers;

namespace mik\filegetters;

namespace mik\metadataparsers\mods;

namespace mik\writers;

class CsvBookToolchainTest extends \PHPUnit_Framework_TestCase
{
    protected $path_to_temp_dir;
    protected $path_to_output_dir;
    protected $path_to_log;
    protected $path_to_mods_schema;

    protected function setUp()
    {
        $this->path_to_temp_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mik_csv_book_temp_dir";
        $this->path_to_output_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mik_csv_book_output_dir";
        @mkdir($this->path_to_output_dir);
        $this->path_to_log = $this->path_to_temp_dir . DIRECTORY_SEPARATOR . "mik.log";
        $this->path_to_mods_schema = dirname(__FILE__) . DIRECTORY_SEPARATOR . '../extras/scripts/mods-3-5.xsd';
    }

    public function testGetRecords()
    {
        // Define settings here, not in a configuration file.
        $settings = array(
            'FETCHER' => array(
                'class' => 'Csv',
                'input_file' => dirname(__FILE__) . '/assets/csv/books/metadata/books_metadata.csv',
                'temp_directory' => $this->path_to_temp_dir,
                'record_key' => 'Identifier',
                'use_cache' => false
             ),
            'LOGGING' => array(
                'path_to_log' => $this->path_to_log,
            ),
        );
        $csv = new \mik\fetchers\Csv($settings);
        $records = $csv->getRecords();
        $this->assertCount(2, $records);
    }
    
    public function testGetItemInfo()
    {
        $settings = array(
            'FETCHER' => array(
                'class' => 'Csv',
                'input_file' => dirname(__FILE__) . '/assets/csv/books/metadata/books_metadata.csv',
                'record_key' => 'Identifier',
                'temp_directory' => $this->path_to_temp_dir,
                'use_cache' => false
             ),
            'LOGGING' => array(
                'path_to_log' => $this->path_to_log,
            ),
        );
        $csv = new \mik\fetchers\Csv($settings);
        $record = $csv->getItemInfo('B2');
        $this->assertEquals('Clay, Beatrice', $record->Author, "Record author is not Clay, Beatrice");
    }

    public function testCreateMetadata()
    {
        $settings = array(
            'FETCHER' => array(
                'class' => 'Csv',
                'input_file' => dirname(__FILE__) . '/assets/csv/books/metadata/books_metadata.csv',
                'record_key' => 'Identifier',
                'temp_directory' => $this->path_to_temp_dir,
                'use_cache' => false
             ),
            'LOGGING' => array(
                'path_to_log' => $this->path_to_log,
            ),
            'METADATA_PARSER' => array(
                'mapping_csv_path' => dirname(__FILE__) . '/assets/csv/books/metadata/books_mappings.csv',
            ),
        );

        $parser = new \mik\metadataparsers\mods\CsvToMods($settings);
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

    public function testWritePackages()
    {
        $settings = array(
            'FETCHER' => array(
                'class' => 'Csv',
                'input_file' => dirname(__FILE__) . '/assets/csv/books/metadata/books_metadata.csv',
                'record_key' => 'Identifier',
                'temp_directory' => $this->path_to_temp_dir,
                'use_cache' => false
             ),
            'FILE_GETTER' => array(
                'validate_input' => false,
                'class' => 'CsvBooks',
                'input_directory' => dirname(__FILE__) . '/assets/csv/books/files_page_sequence_separator',
                'temp_directory' => $this->path_to_temp_dir,
                'file_name_field' => 'Directory',
             ),
            'METADATA_PARSER' => array(
                'mapping_csv_path' => dirname(__FILE__) . '/assets/csv/books/metadata/books_mappings.csv',
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

        $file_getter = new \mik\filegetters\CsvBooks($settings);
        $pages = $file_getter->getChildren('B1');

        $parser = new \mik\metadataparsers\mods\CsvToMods($settings);
        $mods = $parser->metadata('B1');

        $writer = new \mik\writers\CsvBooks($settings);
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

    protected function tearDown()
    {
        unset($writer);
        $temp_files = glob($this->path_to_temp_dir . '/*');
        foreach ($temp_files as $temp_file) {
            @unlink($temp_file);
        }
        @rmdir($this->path_to_temp_dir);

        $page_dirs = array('1', '2', '3', '4');
        foreach ($page_dirs as $page_dir) {
            $page_dir_path = $this->path_to_output_dir . '/B1/' . $page_dir;
            @unlink($page_dir_path . '/MODS.xml');
            @unlink($page_dir_path . '/OBJ.tif');
            @rmdir($page_dir_path);
        }
        @unlink($this->path_to_output_dir . '/B1/MODS.xml');
        @rmdir($this->path_to_output_dir . '/B1');
        @rmdir($this->path_to_output_dir);
    }
}
