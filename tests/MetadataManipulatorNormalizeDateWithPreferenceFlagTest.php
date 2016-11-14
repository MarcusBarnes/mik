<?php

namespace mik\metadataparsers\mods;

class MetadataManipulatorNormalizeDateWithPreferenceFlagTest extends \PHPUnit_Framework_TestCase
{

   protected function setUp()
    {
        $this->path_to_temp_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mik_tests_temp_dir." . time();
        @mkdir($this->path_to_temp_dir);
        $this->path_to_log = $this->path_to_temp_dir . DIRECTORY_SEPARATOR . "mik_metadataparser_test.log";
        $this->path_to_manipulator_log = $this->path_to_temp_dir . DIRECTORY_SEPARATOR . "mik_metadatamanipulator_test.log";
    }

    /**
     * Tests the optional 'preference' flag.
     */
    public function testNormalizeDateMetadataManipulatorWithPreferenceFlag()
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
                'metadatamanipulators' => array('NormalizeDate|Date|dateIssued|m'),
            ),
        );

        $parser = new CsvToMods($settings);

        // Month-first tests. Day-first equivalents are tested in MetadataManipulatorTest.php.

        // Test for matches against dates like 10-29-1941 (month first).
        $mods = $parser->metadata('postcard_13');
        $this->assertRegExp('#<dateIssued\sencoding="w3cdtf">1941\-10\-29</dateIssued>#', $mods,
            "NormalizeDate metadata manipulator for (\d\d)\-(\d\d)\-(\d\d\d\d) did not work");

        // Test for matches against dates like 11/25/1925 (month first).
        $data = 'postcard_12';
        $mods = $parser->metadata($data);
        $this->assertRegExp('#<dateIssued\sencoding="w3cdtf">1925\-11\-25</dateIssued>#', $mods,
            "NormalizeDate metadata manipulator for (\d\d)/(\d\d)/(\d\d\d\d) with preference flag did not work ($data)");

        // Test for matches against dates like 1941-10-29; (good date with some puncutation).
        $data = 'postcard_14';
        $mods = $parser->metadata($data);
        $this->assertRegExp('#<dateIssued\sencoding="w3cdtf">1941\-10\-29</dateIssued>#', $mods,
            "NormalizeDate metadata manipulator for w3cdtf date with surrounding punctuation did not work ($data)");
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
