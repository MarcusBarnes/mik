<?php

namespace mik\fetchers;
namespace mik\filegetters;
namespace mik\metadataparsers\json;
namespace mik\writers;

class CsvToJsonToolchain extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->path_to_temp_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mik_csv_fetcher_temp_dir";
        $this->path_to_output_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mik_csv_json_output_dir";
        $this->path_to_log = $this->path_to_temp_dir . DIRECTORY_SEPARATOR . "mik.log";
    }

    public function testGetRecords()
    {
        // Define settings here, not in a configuration file.
        $settings = array(
            'FETCHER' => array(
                'input_file' => dirname(__FILE__) . '/assets/csv/sample_metadata.csv',
                'temp_directory' => $this->path_to_temp_dir,
                'record_key' => 'ID',
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
            'LOGGING' => array(
                'path_to_log' => $this->path_to_log,
            ),						 
        );
        $csv = new \mik\fetchers\Csv($settings);
        $record = $csv->getItemInfo('postcard_3');
        $this->assertEquals('1947', $record->Date, "Record date is not 1947");
    }

    public function testCreateJson()
    {
        $settings = array(
            'FETCHER' => array(
                'input_file' => dirname(__FILE__) . '/assets/csv/sample_metadata.csv',
                'record_key' => 'ID',
                'temp_directory' => $this->path_to_temp_dir,
             ),
            'LOGGING' => array(
                'path_to_log' => $this->path_to_log,
            ),						 
        );
        $parser = new \mik\metadataparsers\json\CsvToJson($settings);
        $json = $parser->metadata('postcard_1');
        $json_as_array = json_decode($json, true);
        $this->assertEquals('1954', $json_as_array['Date'], "Record date is not 1954");
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
                 'class' => 'CsvSingleFile',
                 'input_directory' => dirname(__FILE__) . '/assets/csv',
                 'file_name_field' => 'File',
             ),
            'WRITER' => array(
                'output_directory' => $this->path_to_output_dir,
                'preserve_content_filenames' => true,
             ),
            'LOGGING' => array(
                'path_to_log' => $this->path_to_log,
            ),						 
        );

        $parser = new \mik\metadataparsers\json\CsvToJson($settings);
        $json = $parser->metadata('postcard_1');

        $writer = new \mik\writers\CsvSingleFileJson($settings);
        $writer->writePackages($json, array(), 'postcard_1');

        $written_metadata = file_get_contents($this->path_to_output_dir . DIRECTORY_SEPARATOR . 'postcard_1.json');
        $written_metadata_as_array = json_decode($written_metadata, true);

        $this->assertEquals('1954', $written_metadata_as_array['Date'], "Record date is not 1954");
        $this->assertFileExists($this->path_to_output_dir . DIRECTORY_SEPARATOR . 'postcard_1.jpg',
            "Postcard_1.jpg file was not written by CsvToJson toolchain.");
    }

}

