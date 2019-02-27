<?php

namespace mik\tests\filegetters;

use mik\tests\MikTestBase;
use mik\filegetters\CsvBooks;

class CsvBooksFilegetterTest extends MikTestBase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->path_to_temp_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mik_csv_book_filegetters_temp_dir";
        parent::setUp();
        $this->path_to_output_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mik_csv_book_filegetters_output_dir";
        @mkdir($this->path_to_output_dir);
        $this->path_to_log = $this->path_to_temp_dir . DIRECTORY_SEPARATOR . "mik.log";
    }

    public function testAllowedFileExtensions()
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
            'FILE_GETTER' => array(
                'validate_input' => false,
                'class' => 'CsvBooks',
                'input_directory' => $this->asset_base_dir . '/csv/books/files_page_sequence_separator',
                'temp_directory' => $this->path_to_temp_dir,
                'file_name_field' => 'Directory',
                'use_cache' => false,
            ),
            'LOGGING' => array(
                'path_to_log' => $this->path_to_log,
            ),
        );
        $file_getter = new CsvBooks($settings);
        $this->assertEquals(
            array('tiff', 'tif', 'jp2'),
            $file_getter->allowed_file_extensions_for_OBJ,
            "File extensions for OBJ not equal."
        );

        $settings['FILE_GETTER']['allowed_file_extensions_for_OBJ'] = array('tiff', 'tif');
        $file_getter = new CsvBooks($settings);
        $this->assertEquals(
            array('tiff', 'tif'),
            $file_getter->allowed_file_extensions_for_OBJ,
            "File extensions for OBJ not equal."
        );
    }
}
