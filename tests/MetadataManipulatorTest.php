<?php

namespace mik\metadataparsers\mods;

class MetadataManipulatorTest extends \PHPUnit_Framework_TestCase
{

   protected function setUp()
    {
        $this->path_to_temp_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mik_tests_temp_dir";
        @mkdir($this->path_to_temp_dir);
        $this->path_to_log = $this->path_to_temp_dir . DIRECTORY_SEPARATOR . "mik_metadataparser_test.log";
        $this->path_to_manipulator_log = $this->path_to_temp_dir . DIRECTORY_SEPARATOR . "mik_metadatamanipulator_test.log";
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
            ),
            'MANIPULATORS' => array(
                'metadatamanipulators' => array('AddUuidToMods'),
            ),
        );
        $parser = new CsvToMods($settings);
        $mods = $parser->metadata('postcard_10');
        $this->assertRegExp('#<identifier type="uuid">[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}</identifier>#i',
            $mods, "AddUuidToMods metadata manipulator did not work");
    }

    /**
     * If you add additional regexes and logic to the NormalizeDate metadata manipulator,
     * add corresponding tests as illustrated below. Additional rows of CSV metadata
     * containing the bad test dates should be added to assets/csv/sample_metadata.normalize_date.csv.
     */
    public function testNormalizeDateMetadataManipulator()
    {
        $settings = array(
            'FETCHER' => array(
                'class' => 'Csv',
                'input_file' => dirname(__FILE__) . '/assets/csv/sample_metadata.normalize_date.csv',
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
            ),
            'MANIPULATORS' => array(
                'metadatamanipulators' => array('NormalizeDate|Date|dateIssued'),
            ),
        );

        $parser = new CsvToMods($settings);

        // Test for matches against dates like 24-12-1954.
        $mods = $parser->metadata('postcard_1');
        $this->assertRegExp('#<dateIssued\sencoding="w3cdtf">1954\-12\-24</dateIssued>#', $mods,
            "NormalizeDate metadata manipulator for (\d\d)\-(\d\d)\-(\d\d\d\d) did not work");

        // Test for matches against dates like 1924 12 24.
        $mods = $parser->metadata('postcard_2');
        $this->assertRegExp('#<dateIssued\sencoding="w3cdtf">1924\-12\-24</dateIssued>#', $mods,
            "NormalizeDate metadata manipulator for (\d\d\d\d)\s+(\d\d)\s+(\d\d) did not work");
    }
}
