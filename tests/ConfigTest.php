<?php

namespace mik\config;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->path_to_temp_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mik_config_temp_dir";
        // The Config class's constructor takes a path to an ini file as a parameter.
        $this->path_to_ini_file = dirname(__FILE__) . '/assets/csv/configtest.ini';
        $this->path_to_log = $this->path_to_temp_dir . DIRECTORY_SEPARATOR . "mik.log";
    }

    public function testCheckCsvFileGood()
    {
        // Define settings here, not in a configuration file.
        $settings = array(
            'FETCHER' => array(
                'class' => 'Csv',
                'input_file' => dirname(__FILE__) . '/assets/csv/sample_metadata.csv',
                'temp_directory' => $this->path_to_temp_dir,
                'record_key' => 'ID',
             ),
            'LOGGING' => array(
                'path_to_log' => $this->path_to_log,
            ),
        );
        $config = new Config($this->path_to_ini_file);
        $config->settings = $settings;
        $this->expectOutputRegex('/appears\sto\sbe\sOK/');
        $config->checkCsvFile();
    }
    
    public function testCheckCsvFileRepeatedHeaderNames()
    {
        // Define settings here, not in a configuration file.
        $settings = array(
            'FETCHER' => array(
                'class' => 'Csv',
                'input_file' => dirname(__FILE__) . '/assets/csv/sample_metadata_bad_header_row_nonunique.csv',
                'temp_directory' => $this->path_to_temp_dir,
                'record_key' => 'ID',
             ),
            'LOGGING' => array(
                'path_to_log' => $this->path_to_log,
            ),
        );
        $config = new Config($this->path_to_ini_file);
        $config->settings = $settings;
        $this->expectOutputRegex('/column\sheaders\sare\snot\sunique/');
        $config->checkCsvFile();
    }

    public function testCheckCsvFileExtraColumns()
    {
        // Define settings here, not in a configuration file.
        $settings = array(
            'FETCHER' => array(
                'class' => 'Csv',
                'input_file' => dirname(__FILE__) . '/assets/csv/sample_metadata_bad_header_row_extra_columns.csv',
                'temp_directory' => $this->path_to_temp_dir,
                'record_key' => 'ID',
             ),
            'LOGGING' => array(
                'path_to_log' => $this->path_to_log,
            ),
        );
        $config = new Config($this->path_to_ini_file);
        $config->settings = $settings;
        $this->expectOutputRegex('/does\snot\shave.*as\sthe\sheader\srow/');
        $config->checkCsvFile();
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
