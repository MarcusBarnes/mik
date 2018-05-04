<?php

namespace mik\config;

use mik\tests\MikTestBase;

/**
 * Class ConfigTest
 * @package mik\config
 * @coversDefaultClass \mik\config\Config
 * @group config
 */
class ConfigTest extends MikTestBase
{
    /**
     * Path to config.ini file.
     * @var string
     */
    private $path_to_ini_file;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->path_to_temp_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mik_config_temp_dir";
        // The Config class's constructor takes a path to an ini file as a parameter.
        $this->path_to_ini_file = $this->asset_base_dir . '/csv/configtest.ini';
        $this->path_to_log = $this->path_to_temp_dir . DIRECTORY_SEPARATOR . "mik.log";
    }

    /**
     * @covers ::checkCsvFile()
     */
    public function testCheckCsvFileGood()
    {
        // Define settings here, not in a configuration file.
        $settings = array(
            'FETCHER' => array(
                'class' => 'Csv',
                'input_file' => $this->asset_base_dir . '/csv/sample_metadata.csv',
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
        $config = new Config($this->path_to_ini_file);
        $config->settings = $settings;
        $this->expectOutputRegex('/appears\sto\sbe\sOK/');
        $config->checkCsvFile();
    }

    /**
     * @covers ::checkCsvFile()
     */
    public function testCheckCsvFileRepeatedHeaderNames()
    {
        // Define settings here, not in a configuration file.
        $settings = array(
            'FETCHER' => array(
                'class' => 'Csv',
                'input_file' => $this->asset_base_dir. '/csv/sample_metadata_bad_header_row_nonunique.csv',
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
        $config = new Config($this->path_to_ini_file);
        $config->settings = $settings;
        $this->expectOutputRegex('/column\sheaders\sare\snot\sunique/');
        $config->checkCsvFile();
    }

    /**
     * @covers ::checkCsvFile()
     */
    public function testCheckCsvFileExtraColumns()
    {
        // Define settings here, not in a configuration file.
        $settings = array(
            'FETCHER' => array(
                'class' => 'Csv',
                'input_file' => $this->asset_base_dir . '/csv/sample_metadata_bad_header_row_extra_columns.csv',
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
        $config = new Config($this->path_to_ini_file);
        $config->settings = $settings;
        $this->expectOutputRegex('/does\snot\shave.*as\sthe\sheader\srow/');
        $config->checkCsvFile();
    }
}
