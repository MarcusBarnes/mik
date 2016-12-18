<?php

namespace mik\inputvalidators;

class CsvFetcher extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->path_to_temp_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mik_csv_input_validator_temp_dir";
        $this->path_to_log = $this->path_to_temp_dir . DIRECTORY_SEPARATOR . "mik.log";
        $this->path_to_input_validator_log = $this->path_to_temp_dir . DIRECTORY_SEPARATOR . "input_validator.log";
    }

    public function testCsvSingleFileInputValidator()
    {
        $settings = array(
            'FETCHER' => array(
                'input_file' => dirname(__FILE__) . '/assets/csv/inputvalidators/csvsinglefile/input.csv',
                'temp_directory' => $this->path_to_temp_dir,
                'record_key' => 'ID',
            ),
            'FILE_GETTER' => array(
                 'validate_input_type' => 'strict',
                 'class' => 'CsvSingleFile',
                 'input_directory' => dirname(__FILE__) . '/assets/csv/inputvalidators/csvsinglefile',
                 'file_name_field' => 'File',
            ),
            'LOGGING' => array(
                'path_to_log' => $this->path_to_log,
                'path_to_validator_log' => $this->path_to_input_validator_log,
            ),
        );
        $inputValidator = new \mik\inputvalidators\CsvSingleFile($settings);
        $inputValidator->validateAll();
        $log_file_entries = file($this->path_to_input_validator_log);
        $this->assertContains('"record ID":"04"', $log_file_entries[0], "CSV Single File input validator did not work");
        $this->assertCount(1, $log_file_entries, "CSV Single File input validator log has the wrong number of entries");
    }
    
    protected function tearDown()
    {
        $temp_files = glob($this->path_to_temp_dir . '/*');
        foreach ($temp_files as $temp_file) {
            @unlink($temp_file);
        }
        @rmdir($this->path_to_temp_dir);
    }
}
