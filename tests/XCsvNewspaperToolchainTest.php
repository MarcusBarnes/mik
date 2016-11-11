<?php

/**
 * This file is named XCsvNewspaperToolchainTest.php so that it is run after
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

class CsvNewspaperToolchainTest extends \PHPUnit_Framework_TestCase
{
    protected $path_to_temp_dir;
    protected $path_to_output_dir;
    protected $path_to_log;
    protected $path_to_mods_schema;

    protected function setUp()
    {
        $this->path_to_temp_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mik_csv_newspaper_temp_dir";
        $this->path_to_output_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mik_csv_newspaper_output_dir";
        @mkdir($this->path_to_output_dir);
        $this->path_to_log = $this->path_to_temp_dir . DIRECTORY_SEPARATOR . "mik.log";
        $this->path_to_mods_schema = dirname(__FILE__) . DIRECTORY_SEPARATOR . '../extras/scripts/mods-3-5.xsd';
    }

    public function testGetRecords()
    {
        // Define settings here, not in a configuration file.
        $settings = array(
            'FETCHER' => array(
                'input_file' => dirname(__FILE__) . '/assets/csv/newspapers/metadata/newspapers_metadata.csv',
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
                'input_file' => dirname(__FILE__) . '/assets/csv/newspapers/metadata/newspapers_metadata.csv',
                'record_key' => 'Identifier',
                'temp_directory' => $this->path_to_temp_dir,
                'use_cache' => false
             ),
            'LOGGING' => array(
                'path_to_log' => $this->path_to_log,
            ),
        );
        $csv = new \mik\fetchers\Csv($settings);
        $record = $csv->getItemInfo('TT0001');
        $this->assertEquals('1907-08-18', $record->Date, "Record date is not 1907-08-18");
    }

    public function testCreateMetadata()
    {
        $settings = array(
            'FETCHER' => array(
                'input_file' => dirname(__FILE__) . '/assets/csv/newspapers/metadata/newspapers_metadata.csv',
                'record_key' => 'Identifier',
                'temp_directory' => $this->path_to_temp_dir,
                'use_cache' => false
             ),
            'LOGGING' => array(
                'path_to_log' => $this->path_to_log,
            ),
            'METADATA_PARSER' => array(
                'mapping_csv_path' => dirname(__FILE__) . '/assets/csv/newspapers/metadata/newspapers_mappings.csv',
            ),
        );

        $parser = new \mik\metadataparsers\mods\CsvToMods($settings);
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

    public function testWritePackages()
    {
        $settings = array(
            'FETCHER' => array(
                'input_file' => dirname(__FILE__) . '/assets/csv/newspapers/metadata/newspapers_metadata.csv',
                'record_key' => 'Identifier',
                'temp_directory' => $this->path_to_temp_dir,
                'use_cache' => false
             ),
            'FILE_GETTER' => array(
                'class' => 'CsvNewspapers',
                'input_directory' => dirname(__FILE__) . '/assets/csv/newspapers/files/flat',
                'temp_directory' => $this->path_to_temp_dir,
                'file_name_field' => 'Directory',
             ),
            'METADATA_PARSER' => array(
                'mapping_csv_path' => dirname(__FILE__) . '/assets/csv/newspapers/metadata/newspapers_mappings.csv',
            ),
            'WRITER' => array(
                'output_directory' => $this->path_to_output_dir,
                'metadata_filename' => 'MODS.xml',
             ),
            'LOGGING' => array(
                'path_to_log' => $this->path_to_log,
            ),
        );

        $file_getter = new \mik\filegetters\CsvNewspapers($settings);
        $pages = $file_getter->getChildren('TT0002');

        $parser = new \mik\metadataparsers\mods\CsvToMods($settings);
        $mods = $parser->metadata('TT0002');

        $writer = new \mik\writers\CsvNewspapers($settings);
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
            "OBJ.tif file was not written by CsvSingleFile toolchain."
        );
    }

    protected function tearDown()
    {
        $temp_files = glob($this->path_to_temp_dir . '/*');
        foreach ($temp_files as $temp_file) {
            @unlink($temp_file);
        }
        @rmdir($this->path_to_temp_dir);

        $issue_dir = $this->path_to_output_dir . '/TT0002';
        @unlink($issue_dir . '/MODS.xml');
        $page_dirs = array('1', '2', '3', '4');
        foreach ($page_dirs as $page_dir) {
            $page_dir_path = $issue_dir . '/' . $page_dir;
            @unlink($page_dir_path . '/OBJ.tif');
            @unlink($page_dir_path . '/MODS.xml');
            @rmdir($page_dir_path);
        }
        @rmdir($issue_dir);
        @rmdir($this->path_to_output_dir);
    }
}
