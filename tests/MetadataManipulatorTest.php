<?php

namespace mik\metadataparsers\mods;

class MetadataManipulatorTest extends \PHPUnit_Framework_TestCase
{

   protected function setUp()
    {
        $this->path_to_temp_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mik_tests_temp_dir";
        @mkdir($this->path_to_temp_dir);
        $this->path_to_log = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mik_metadataparser_test.log";
        $this->path_to_manipulator_log = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mik_metadatamanipulator_test.log";
    }

    public function testAddUuidToModsMetadataManipulator()
    {
        $settings = array(
            'FETCHER' => array(
                'input_file' => dirname(__FILE__) . '/assets/csv/sample_metadata.csv',
                'temp_directory' => $this->path_to_temp_dir,
                'record_key' => 'ID',
                'use_cache' => false,
            ),
            'LOGGING' => array(
                'path_to_log' => $this->path_to_log,
                'path_to_manipulator_log' => $this->path_to_manipulator_log,
            ),
            'METADATA_PARSER' => array(
                'mapping_csv_path' => dirname(__FILE__) . '/assets/csv/sample_mappings.csv',
                'repeatable_wrapper_elements' => array('subject'),
            ),
            'MANIPULATORS' => array(
                'metadatamanipulators' => array('AddUuidToMods'),
            ),
        );
        $parser = new CsvToMods($settings);
        $mods = $parser->metadata('postcard_10');
        $this->assertRegExp('#<identifier type="uuid">[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}</identifier>#i', $mods, "AddUuidToMods metadata manipulator did not work");
    }

}
