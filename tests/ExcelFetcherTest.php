<?php

namespace mik\fetchers;

class ExcelFetcher extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->path_to_temp_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mik_csv_fetcher_temp_dir";
        $this->path_to_log = $this->path_to_temp_dir . DIRECTORY_SEPARATOR . "mik.log";
    }

    public function testGetRecords()
    {
        // Define settings here, not in a configuration file.
        $settings = array(
            'FETCHER' => array(
                'input_file' => dirname(__FILE__) . '/assets/excel/sample_metadata.xlsx',
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
        $excel = new Excel($settings);
        $records = $excel->getRecords();
        $this->assertCount(20, $records);
    }
    
    public function testGetNumRecs()
    {
        $settings = array(
            'FETCHER' => array(
                'input_file' => dirname(__FILE__) . '/assets/excel/sample_metadata.xlsx',
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
        $excel = new Excel($settings);
        $num_records = $excel->getNumRecs();
        $this->assertEquals(20, $num_records);
    }

    public function testGetItemInfo()
    {
        $settings = array(
            'FETCHER' => array(
                'input_file' => dirname(__FILE__) . '/assets/excel/sample_metadata.xlsx',
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
        $excel = new Excel($settings);
        $record = $excel->getItemInfo('postcard_3');
        $this->assertEquals('1947', $record->Date, "Record date is not 1947");
    }

    protected function tearDown()
    {
        $temp_files = glob($this->path_to_temp_dir . '/*');
        foreach($temp_files as $temp_file) {
            @unlink($temp_file);
        }
        @rmdir($this->path_to_temp_dir);
    }

}
