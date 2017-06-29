<?php

namespace mik\fetchers;

class FilesystemFetcher extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->path_to_temp_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mik_filesystem_fetcher_temp_dir";
        $this->path_to_log = $this->path_to_temp_dir . DIRECTORY_SEPARATOR . "mik.log";
    }

    public function testGetRecords()
    {
        // Define settings here, not in a configuration file.
        $settings = array(
            'FETCHER' => array(
                'temp_directory' => $this->path_to_temp_dir,
            ),
            'FILE_GETTER' => array(
                 'input_directory' => dirname(__FILE__) . '/assets/filesystemfetcher',
                 'validate_input' => false,
                 'class' => 'CsvSingleFile',
                 'file_name_field' => 'title',
            ),
            'LOGGING' => array(
                'path_to_log' => $this->path_to_log,
            ),
        );
        $filesystem = new Filesystem($settings);
        $records = $filesystem->getRecords();
        $this->assertCount(5, $records);
    }
    
    public function testGetNumRecs()
    {
        $settings = array(
            'FETCHER' => array(
                'temp_directory' => $this->path_to_temp_dir,
            ),
            'FILE_GETTER' => array(
                 'input_directory' => dirname(__FILE__) . '/assets/filesystemfetcher',
                 'validate_input' => false,
                 'class' => 'CsvSingleFile',
                 'file_name_field' => 'title',
            ),
            'LOGGING' => array(
                'path_to_log' => $this->path_to_log,
            ),
        );
        $filesystem = new Filesystem($settings);
        $num_records = $filesystem->getNumRecs();
        $this->assertEquals(5, $num_records);
    }

    public function testGetItemInfo()
    {
        $settings = array(
            'FETCHER' => array(
                'temp_directory' => $this->path_to_temp_dir,
            ),
            'FILE_GETTER' => array(
                 'input_directory' => dirname(__FILE__) . '/assets/filesystemfetcher',
                 'validate_input' => false,
                 'class' => 'CsvSingleFile',
                 'file_name_field' => 'title',
            ),
            'LOGGING' => array(
                'path_to_log' => $this->path_to_log,
            ),						 
        );
        $filesystem = new Filesystem($settings);
        $record = $filesystem->getItemInfo('testfile2');
        $this->assertEquals('testfile2.tif', $record->title, "Record title is not testfile2.tif");
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
