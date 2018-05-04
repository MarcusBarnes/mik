<?php

namespace mik\metadataparsers\mods;

use mik\tests\MikTestBase;

/**
 * Class MetadataManipulatorNormalizeDateWithPreferenceFlagTest
 * @package mik\metadataparsers\mods
 * @coversDefaultClass \mik\metadataparsers\mods\CsvToMods
 * @group metadatamanipulators
 */
class MetadataManipulatorNormalizeDateWithPreferenceFlagTest extends MikTestBase
{

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->path_to_temp_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mik_tests_temp_dir." . time();
        @mkdir($this->path_to_temp_dir);
        $this->path_to_log = $this->path_to_temp_dir . DIRECTORY_SEPARATOR . "mik_metadataparser_test.log";
        $this->path_to_manipulator_log = $this->path_to_temp_dir .
            DIRECTORY_SEPARATOR . "mik_metadatamanipulator_test.log";
    }

    /**
     * Tests the optional 'preference' flag.
     * @covers ::metadata
     */
    public function testNormalizeDateMetadataManipulatorWithPreferenceFlag()
    {
        $settings = array(
            'FETCHER' => array(
               'class' => 'Csv',
                'input_file' => $this->asset_base_dir . '/csv/sample_metadata.normalize_date.csv',
                'temp_directory' => $this->path_to_temp_dir,
                'record_key' => 'ID',
                'use_cache' => false,
            ),
            'FILE_GETTER' => array(
                 'validate_input' => false,
                 'class' => 'CsvSingleFile',
                 'file_name_field' => 'File',
            ),
            'LOGGING' => array(
                'path_to_log' => $this->path_to_log,
                'path_to_manipulator_log' => $this->path_to_manipulator_log,
            ),
            'METADATA_PARSER' => array(
                'mapping_csv_path' => $this->asset_base_dir . '/csv/sample_mappings.csv',
            ),
            'MANIPULATORS' => array(
                'metadatamanipulators' => array('NormalizeDate|Date|dateIssued|m'),
            ),
        );

        $parser = new CsvToMods($settings);

        // Month-first tests. Day-first equivalents are tested in MetadataManipulatorTest.php.

        // Test for matches against dates like 10-29-1941 (month first).
        $mods = $parser->metadata('postcard_13');
        $this->assertRegExp(
            '#<dateIssued\sencoding="w3cdtf">1941\-10\-29</dateIssued>#',
            $mods,
            "NormalizeDate metadata manipulator for (\d\d)\-(\d\d)\-(\d\d\d\d) did not work"
        );

        // Test for matches against dates like 11/25/1925 (month first).
        $data = 'postcard_12';
        $mods = $parser->metadata($data);
        $this->assertRegExp(
            '#<dateIssued\sencoding="w3cdtf">1925\-11\-25</dateIssued>#',
            $mods,
            "NormalizeDate metadata manipulator for (\d\d)/(\d\d)/(\d\d\d\d) with preference flag did not work ($data)"
        );

        // Test for matches against dates like 1941-10-29; (good date with some puncutation).
        $data = 'postcard_14';
        $mods = $parser->metadata($data);
        $this->assertRegExp(
            '#<dateIssued\sencoding="w3cdtf">1941\-10\-29</dateIssued>#',
            $mods,
            "NormalizeDate metadata manipulator for w3cdtf date with surrounding punctuation did not work ($data)"
        );
    }
}
