<?php

namespace mik\metadataparsers\mods;

use mik\tests\MikTestBase;

/**
 * Class MetadataManipulatorTest
 * @package mik\metadataparsers\mods
 * @group metadatamanipulators
 */
class MetadataManipulatorTest extends MikTestBase
{

    /**
     * @var string
     */
    private $path_to_manipulator_log;

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
     * @covers \mik\metadatamanipulators\AddUuidToMods
     */
    public function testAddUuidToModsMetadataManipulator()
    {
        $settings = array(
            'FETCHER' => array(
                'class' => 'Csv',
                'input_file' => $this->asset_base_dir . '/csv/sample_metadata.csv',
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
                'metadatamanipulators' => array('AddUuidToMods'),
            ),
        );
        $parser = new CsvToMods($settings);
        $mods = $parser->metadata('postcard_10');
        $uuid_regex = "[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}";
        $this->assertRegExp(
            "#<identifier type=\"uuid\">{$uuid_regex}</identifier>#i",
            $mods,
            "AddUuidToMods metadata manipulator did not work"
        );
    }

    /**
     * If you add additional regexes and logic to the NormalizeDate metadata manipulator,
     * add corresponding tests as illustrated below. Additional rows of CSV metadata
     * containing the bad test dates should be added to assets/csv/sample_metadata.normalize_date.csv.
     * @covers \mik\metadatamanipulators\NormalizeDate
     */
    public function testNormalizeDateMetadataManipulator()
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
                'metadatamanipulators' => array('NormalizeDate|Date|dateIssued'),
            ),
        );

        $parser = new CsvToMods($settings);

        // Test for matches against dates like 24-12-1954 (day first).
        $mods = $parser->metadata('postcard_1');
        $this->assertRegExp(
            '#<dateIssued\sencoding="w3cdtf">1954\-12\-24</dateIssued>#',
            $mods,
            "NormalizeDate metadata manipulator for (\d\d)\-(\d\d)\-(\d\d\d\d) did not work"
        );

        // Test for matches against dates like 1924 12 24.
        $mods = $parser->metadata('postcard_2');
        $this->assertRegExp(
            '#<dateIssued\sencoding="w3cdtf">1924\-12\-24</dateIssued>#',
            $mods,
            "NormalizeDate metadata manipulator for (\d\d\d\d)\s+(\d\d)\s+(\d\d) did not work"
        );

        // Test for matches against dates like 1924/12/24.
        $mods = $parser->metadata('postcard_4');
        $this->assertRegExp(
            '#<dateIssued\sencoding="w3cdtf">1924\-12\-24</dateIssued>#',
            $mods,
            "NormalizeDate metadata manipulator for (\d\d\d\d)/(\d\d)/(\d\d) did not work"
        );

        // Test for matches against dates like 25/11/1925 (day first).
        $mods = $parser->metadata('postcard_11');
        $this->assertRegExp(
            '#<dateIssued\sencoding="w3cdtf">1925\-11\-25</dateIssued>#',
            $mods,
            "NormalizeDate metadata manipulator for (\d\d\)/(\d\d)/(\d\d\d\d) did not work"
        );
    }

    /**
     * @covers \mik\metadatamanipulators\AddCsvData
     */
    public function testAddCsvDataMetadataManipulator()
    {
        $settings = array(
            'FETCHER' => array(
                'class' => 'Csv',
                'input_file' => $this->asset_base_dir . '/csv/sample_metadata.csv',
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
                'metadatamanipulators' => array('AddCsvData'),
            ),
        );

        $parser = new CsvToMods($settings);

        $mods = $parser->metadata('postcard_20');
        $this->assertRegExp('#<CSVRecord.*"Date":"1907"#', $mods, "AddCsvData metadata manipulator did not work");
    }

    /**
     * @covers \mik\metadatamanipulators\SimpleReplace
     */
    public function testSimpleReplaceMetadataManipulator()
    {
        $settings = array(
            'FETCHER' => array(
                'class' => 'Csv',
                'input_file' => $this->asset_base_dir . '/csv/sample_metadata.csv',
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
                'metadatamanipulators' => array('SimpleReplace|#Vancouver,\sB\.C\.</title>#|Victoria, B.C.</title>'),
            ),
        );

        $parser = new CsvToMods($settings);

        $mods = $parser->metadata('postcard_20');
        $this->assertRegExp('#Victoria,\sB\.C\.</title>#', $mods, "SimpleReplace metadata manipulator did not work");
    }

    /**
     * @covers \mik\metadatamanipulators\InsertXmlFromTemplate
     */
    public function testInsertXMLFromTemplateManipulator()
    {
        $settings = array(
            'FETCHER' => array(
                'class' => 'Csv',
                'input_file' => $this->asset_base_dir . '/csv/insertxmlfromtemplate/metadata.csv',
                'temp_directory' => $this->path_to_temp_dir,
                'record_key' => 'Identifier',
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
                'mapping_csv_path' => $this->asset_base_dir . '/csv/insertxmlfromtemplate/mapping.csv',
            ),
            'MANIPULATORS' => array(
                'metadatamanipulators' => array(
                    'InsertXmlFromTemplate|CreatorName|' .
                    $this->asset_base_dir .
                    '/csv/insertxmlfromtemplate/creator.twg'
                ),
            ),
        );

        $name_element = <<<XML
  <name authority="local" type="personal">
    <role>
      <roleTerm authority="marcRelator" type="text">Artist</roleTerm>
    </role>
    <namePart>Ortiz-Palacios, Guillermo</namePart>
  </name>
XML;

        $parser = new CsvToMods($settings);
        $mods = $parser->metadata('1');

        $this->assertContains($name_element, $mods, "InsertXMLFromTemplate metadata manipulator failed");
    }

    /**
     * @covers \mik\metadatamanipulators\SplitRepeatedValues
     */
    public function testSplitRepeatedValuesMetadataManipulator()
    {
        $settings = array(
            'FETCHER' => array(
                'class' => 'Csv',
                'input_file' => $this->asset_base_dir . '/csv/sample_metadata.csv',
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
                'repeatable_wrapper_elements' => array('subject'),
            ),
            'MANIPULATORS' => array(
                'metadatamanipulators' => array('SplitRepeatedValues|Subjects|/subject/topic|;'),
            ),
        );

        $parser = new CsvToMods($settings);

        $mods = $parser->metadata('postcard_7');
        $subject_element_1 = <<<XML
  <subject>
    <geographic>Vancouver, BC</geographic>
  </subject>
XML;
        $this->assertContains($subject_element_1, $mods, "SplitRepeatedValues metadata manipulator did not work");
        $subject_element_2 = <<<XML
  <subject authority="lcsh">
    <topic>Storefronts</topic>
  </subject>
XML;
        $this->assertContains($subject_element_2, $mods, "SplitRepeatedValues metadata manipulator did not work");
    }
}
