<?php

namespace mik\fetchers;

namespace mik\filegetters;

namespace mik\metadataparsers\mods;

namespace mik\writers;

class CsvSingleFileToolchainTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->path_to_temp_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mik_csv_fetcher_temp_dir";
        $this->path_to_output_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mik_csv_single_file_output_dir";
        $this->path_to_log = $this->path_to_temp_dir . DIRECTORY_SEPARATOR . "mik.log";
        $this->path_to_input_validator_log = $this->path_to_temp_dir . DIRECTORY_SEPARATOR . "input_validator.log";
        $this->path_to_mods_schema = dirname(__FILE__) . DIRECTORY_SEPARATOR . '../extras/scripts/mods-3-5.xsd';
    }

    public function testGetRecords()
    {
        // Define settings here, not in a configuration file.
        $settings = array(
            'FETCHER' => array(
                'use_cache' => false,
                'input_file' => dirname(__FILE__) . '/assets/csv/sample_metadata.csv',
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
        $csv = new \mik\fetchers\Csv($settings);
        $records = $csv->getRecords();
        $this->assertCount(20, $records);
    }
    
    public function testGetItemInfo()
    {
        $settings = array(
            'FETCHER' => array(
                'input_file' => dirname(__FILE__) . '/assets/csv/sample_metadata.csv',
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
        $csv = new \mik\fetchers\Csv($settings);
        $record = $csv->getItemInfo('postcard_3');
        $this->assertEquals('1947', $record->Date, "Record date is not 1947");
    }

    public function testCreateMetadata()
    {
        $settings = array(
            'FETCHER' => array(
                'input_file' => dirname(__FILE__) . '/assets/csv/sample_metadata.csv',
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
                'mapping_csv_path' => dirname(__FILE__) . '/assets/csv/sample_mappings.csv',
            ),
        );

        $parser = new \mik\metadataparsers\mods\CsvToMods($settings);
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

    public function testWritePackages()
    {
        $settings = array(
            'FETCHER' => array(
                'input_file' => dirname(__FILE__) . '/assets/csv/sample_metadata.csv',
                'record_key' => 'ID',
                'temp_directory' => $this->path_to_temp_dir,
             ),
            'FILE_GETTER' => array(
                 'validate_input' => false,
                 'class' => 'CsvSingleFile',
                 'input_directory' => dirname(__FILE__) . '/assets/csv',
                 'file_name_field' => 'File',
             ),
            'METADATA_PARSER' => array(
                'mapping_csv_path' => dirname(__FILE__) . '/assets/csv/sample_mappings.csv',
            ),
            'WRITER' => array(
                'output_directory' => $this->path_to_output_dir,
                'preserve_content_filenames' => true,
             ),
            'LOGGING' => array(
                'path_to_log' => $this->path_to_log,
            ),
        );

        $parser = new \mik\metadataparsers\mods\CsvToMods($settings);
        $mods = $parser->metadata('postcard_1');

        $writer = new \mik\writers\CsvSingleFile($settings);
        $writer->writePackages($mods, array(), 'postcard_1');

        $written_metadata = file_get_contents($this->path_to_output_dir . DIRECTORY_SEPARATOR . 'postcard_1.xml');
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

    protected function tearDown()
    {
        $temp_files = glob($this->path_to_temp_dir . '/*');
        foreach ($temp_files as $temp_file) {
            @unlink($temp_file);
        }
        @rmdir($this->path_to_temp_dir);

        $output_files = glob($this->path_to_output_dir . '/*');
        foreach ($output_files as $output_file) {
            @unlink($output_file);
        }
        @rmdir($this->path_to_output_dir);
    }
}
