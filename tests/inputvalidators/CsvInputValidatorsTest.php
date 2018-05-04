<?php

namespace mik\inputvalidators;

use mik\tests\MikTestBase;

/**
 * Class CsvInputValidatorsTest
 * @package mik\inputvalidators
 * @group inputvalidators
 */
class CsvInputValidatorsTest extends MikTestBase
{
    /**
     * Path to validator log.
     * @var string
     */
    private $path_to_input_validator_log;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->path_to_temp_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mik_csv_input_validator_temp_dir";
        @mkdir($this->path_to_temp_dir);
        $this->path_to_log = $this->path_to_temp_dir . DIRECTORY_SEPARATOR . "mik.log";
        $this->path_to_input_validator_log = $this->path_to_temp_dir . DIRECTORY_SEPARATOR . "input_validator.log";
    }

    /**
     * @covers \mik\inputvalidators\CsvSingleFile
     */
    public function testCsvSingleFileInputValidator()
    {
        $settings = array(
            'FETCHER' => array(
                'class' => 'Csv',
                'use_cache' => false,
                'input_file' => $this->asset_base_dir . '/csv/inputvalidators/csvsinglefile/input.csv',
                'temp_directory' => $this->path_to_temp_dir,
                'record_key' => 'ID',
            ),
            'FILE_GETTER' => array(
                 'validate_input' => true,
                 'validate_input_type' => 'strict',
                 'class' => 'CsvSingleFile',
                 'input_directory' => $this->asset_base_dir . '/csv/inputvalidators/csvsinglefile',
                 'file_name_field' => 'File',
            ),
            'LOGGING' => array(
                'path_to_log' => $this->path_to_log,
                'path_to_validator_log' => $this->path_to_input_validator_log,
            ),
        );
        $inputValidator = new CsvSingleFile($settings);
        $inputValidator->validateAll();
        $log_file_entries = file($this->path_to_input_validator_log);
        $this->assertContains('"record ID":"04"', $log_file_entries[0], "CSV Single File input validator did not work");
        $this->assertCount(1, $log_file_entries, "CSV Single File input validator log has the wrong number of entries");
    }

    /**
     * @covers \mik\inputvalidators\CsvCompound
     */
    public function testCsvCompoundInputValidator()
    {
        $this->markTestSkipped(
            'Something in the test or the \mik\writers\CsvCompound is broken'
        );

        $settings = array(
            'FETCHER' => array(
                'class' => 'Csv',
                'use_cache' => false,
                'input_file' => $this->asset_base_dir . '/csv/inputvalidators/csvcompound/input.csv',
                'temp_directory' => $this->path_to_temp_dir,
                'record_key' => 'Identifier',
            ),
            'FILE_GETTER' => array(
                 'validate_input' => true,
                 'validate_input_type' => 'strict',
                 'class' => 'CsvCompound',
                 'input_directory' => $this->asset_base_dir . '/csv/inputvalidators/csvcompound/files',
                 'compound_directory_field' => 'Directory'
            ),
            'LOGGING' => array(
                'path_to_log' => $this->path_to_log,
                'path_to_validator_log' => $this->path_to_input_validator_log,
            ),
        );

        $inputValidator = new CsvCompound($settings);
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
     * @covers \mik\inputvalidators\CsvNewspapers
     */
    public function testCsvNewspapersInputValidator()
    {
        $settings = array(
            'FETCHER' => array(
                'class' => 'Csv',
                'use_cache' => false,
                'input_file' => $this->asset_base_dir . '/csv/inputvalidators/csvnewspapers/input.csv',
                'temp_directory' => $this->path_to_temp_dir,
                'record_key' => 'Identifier',
            ),
            'FILE_GETTER' => array(
                 'validate_input' => true,
                 'validate_input_type' => 'strict',
                 'class' => 'CsvNewspapers',
                 'input_directory' => $this->asset_base_dir . '/csv/inputvalidators/csvnewspapers/files',
                 'file_name_field' => 'Directory',
            ),
            'WRITER' => array(
                'log_missing_ocr_files' => true,
            ),
            'LOGGING' => array(
                'path_to_log' => $this->path_to_log,
                'path_to_validator_log' => $this->path_to_input_validator_log,
            ),
        );

        $inputValidator = new CsvNewspapers($settings);
        $inputValidator->validateAll();
        $log_file_entries = file($this->path_to_input_validator_log);
        $this->assertCount(6, $log_file_entries, "CSV Newspapers input validator log has the wrong number of entries");
        $this->assertContains(
            '"issue directory":"1900-0102","error":"Issue directory name is not in yyyy-mm-dd format"',
            $log_file_entries[1],
            "CSV Newspapers input validator did not detect non-yyyy-mm-dd directory name"
        );
        $this->assertContains(
            '"issue directory":"1900-01-03","error":"Issue directory not found in list of possible input directories"',
            $log_file_entries[3],
            "CSV Newspapers input validator did not find issue directory in input directories"
        );
        $this->assertContains(
            '"issue directory":"1900-01-04","error":"Some pages in issue directory have invalid sequence numbers"',
            $log_file_entries[5],
            "CSV Newspapers input validator did not detect invalid page sequences"
        );
        $this->assertContains(
            '"issue directory":"1900-01-04","error":"Issue directory is missing one or more OCR files"',
            $log_file_entries[4],
            "CSV Newspapers input validator did not detect missing OCR files"
        );
    }

    /**

     * @covers \mik\inputvalidators\CsvBooks
     */
    public function testCsvBooksInputValidator()
    {
        $settings = array(
            'FETCHER' => array(
                'class' => 'Csv',
                'use_cache' => false,
                'input_file' => $this->asset_base_dir . '/csv/inputvalidators/csvbooks/input.csv',
                'temp_directory' => $this->path_to_temp_dir,
                'record_key' => 'Identifier',
            ),
            'FILE_GETTER' => array(
                 'validate_input' => true,
                 'validate_input_type' => 'strict',
                 'class' => 'CsvBooks',
                 'input_directory' => $this->asset_base_dir . '/csv/inputvalidators/csvbooks/files',
                 'file_name_field' => 'Directory',
            ),
            'LOGGING' => array(
                'path_to_log' => $this->path_to_log,
                'path_to_validator_log' => $this->path_to_input_validator_log,
            ),
        );

        $inputValidator = new CsvBooks($settings);
        $inputValidator->validateAll();
        $log_file_entries = file($this->path_to_input_validator_log);
        $this->assertCount(5, $log_file_entries, "CSV Books input validator log has the wrong number of entries");

        $this->assertContains(
            'files/book1","error":"Some files in the book object directory have invalid sequence numbers"',
            $log_file_entries[0],
            "CSV Books input validator did not detect invalid sequence numbers"
        );
        $this->assertContains(
            'files/book2","error":"Some files in the book object directory have invalid extensions"',
            $log_file_entries[1],
            "CSV Books input validator did not find invalid page file extensions"
        );
        $this->assertContains(
            'files/book3","error":"Book object input directory contains unwanted files"',
            $log_file_entries[2],
            "CSV Books input validator did not detect unwanted files"
        );
        $this->assertContains(
            'files/book3","error":"Some files in the book object directory have invalid extensions"',
            $log_file_entries[3],
            "CSV Books input validator did not find invalid page file extensions"
        );
        $this->assertContains(
            'files/book4","error":"Book object directory not found"',
            $log_file_entries[4],
            "CSV Books input validator did not detect empty book-level directory"
        );
    }
}
