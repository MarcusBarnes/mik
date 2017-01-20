<?php

namespace mik\inputvalidators;

class CsvInputValidatorsTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->path_to_temp_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mik_csv_input_validator_temp_dir";
        @mkdir($this->path_to_temp_dir);
        $this->path_to_log = $this->path_to_temp_dir . DIRECTORY_SEPARATOR . "mik.log";
        $this->path_to_input_validator_log = $this->path_to_temp_dir . DIRECTORY_SEPARATOR . "input_validator.log";
    }

    /**
     * @group inputvalidators
     */
    public function testCsvSingleFileInputValidator()
    {
        $settings = array(
            'FETCHER' => array(
                'use_cache' => false,
                'input_file' => dirname(__FILE__) . '/assets/csv/inputvalidators/csvsinglefile/input.csv',
                'temp_directory' => $this->path_to_temp_dir,
                'record_key' => 'ID',
            ),
            'FILE_GETTER' => array(
                 'validate_input' => true,
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

    /**
     * @group inputvalidators
     */
    public function testCsvCompoundInputValidator()
    {
        $settings = array(
            'FETCHER' => array(
                'use_cache' => false,
                'input_file' => dirname(__FILE__) . '/assets/csv/inputvalidators/csvcompound/input.csv',
                'temp_directory' => $this->path_to_temp_dir,
                'record_key' => 'Identifier',
            ),
            'FILE_GETTER' => array(
                 'validate_input' => true,
                 'validate_input_type' => 'strict',
                 'class' => 'CsvCompound',
                 'input_directory' => dirname(__FILE__) . '/assets/csv/inputvalidators/csvcompound/files',
                 'compound_directory_field' => 'Directory'
            ),
            'LOGGING' => array(
                'path_to_log' => $this->path_to_log,
                'path_to_validator_log' => $this->path_to_input_validator_log,
            ),
        );

        $inputValidator = new \mik\inputvalidators\CsvCompound($settings);
        $inputValidator->validateAll();
        $log_file_entries = file($this->path_to_input_validator_log);
        $this->assertCount(5, $log_file_entries, "CSV Compound input validator log has the wrong number of entries");

        $this->assertContains(
            'files/compound1","error":"Compound object input directory contains unwanted files"',
            $log_file_entries[0],
            "CSV Compound input validator did not detect unwanted files"
        );
        $this->assertContains(
            'files/compound2","error":"Some files in the compound object directory have invalid sequence numbers"',
            $log_file_entries[1],
            "CSV Compound input validator did not find invalid child sequence numbers"
        );
        $this->assertContains(
            'files/compound4","error":"Compound object directory not found"',
            $log_file_entries[2],
            "CSV Compound input validator did not detect missing object-level directory"
        );
        $this->assertContains(
            'files/compound10","error":"Compound object input directory contains too few child files"',
            $log_file_entries[3],
            "CSV Compound input validator did not detect too few child files"
        );
    }

    /**
     * @group inputvalidators
     */
    public function testCsvNewspapersInputValidator()
    {
        $settings = array(
            'FETCHER' => array(
                'use_cache' => false,
                'input_file' => dirname(__FILE__) . '/assets/csv/inputvalidators/csvnewspapers/input.csv',
                'temp_directory' => $this->path_to_temp_dir,
                'record_key' => 'Identifier',
            ),
            'FILE_GETTER' => array(
                 'validate_input' => true,
                 'validate_input_type' => 'strict',
                 'class' => 'CsvNewspapers',
                 'input_directory' => dirname(__FILE__) . '/assets/csv/inputvalidators/csvnewspapers/files',
                 'file_name_field' => 'Directory',
            ),
            'LOGGING' => array(
                'path_to_log' => $this->path_to_log,
                'path_to_validator_log' => $this->path_to_input_validator_log,
            ),
        );

        $inputValidator = new \mik\inputvalidators\CsvNewspapers($settings);
        $inputValidator->validateAll();
        $log_file_entries = file($this->path_to_input_validator_log);
        $this->assertCount(3, $log_file_entries, "CSV Newspapers input validator log has the wrong number of entries");
        $this->assertContains(
            '"issue directory":"1900-0102","error":"Issue directory name is not in yyyy-mm-dd format"',
            $log_file_entries[0],
            "CSV Newspapers input validator did not detect non-yyyy-mm-dd directory name"
        );
        $this->assertContains(
            '"issue directory":"1900-01-03","error":"Issue directory not found in list of possible input directories"',
            $log_file_entries[1],
            "CSV Newspapers input validator did not find issue directory in input directories"
        );
        $this->assertContains(
            '"issue directory":"1900-01-04","error":"Some pages in issue directory have invalid sequence numbers"',
            $log_file_entries[2],
            "CSV Newspapers input validator did not detect invalid page sequences"
        );
    }
    
    protected function tearDown()
    {
        // Since we are running these validation tests in 'strict' mode, we
        // do not write any ouput files other than the validator log, which
        // we write to the temp directory.
        $temp_files = glob($this->path_to_temp_dir . '/*');
        foreach ($temp_files as $temp_file) {
            @unlink($temp_file);
        }
        @rmdir($this->path_to_temp_dir);
    }
}
