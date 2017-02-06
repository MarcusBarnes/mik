<?php

namespace mik\metadataparsers\mods;

class MetadataParserTest extends \PHPUnit_Framework_TestCase
{

   protected function setUp()
    {
        $this->path_to_temp_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mik_tests_temp_dir";
        @mkdir($this->path_to_temp_dir);
        $this->path_to_log = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mik_metadataparser_test.log";
        $this->path_to_mods_schema = dirname(__FILE__) . DIRECTORY_SEPARATOR . '../extras/scripts/mods-3-5.xsd';
    }

    public function testCsvToModsMetadataParser()
    {
        $settings = array(
            'FETCHER' => array(
                'input_file' => dirname(__FILE__) . '/assets/csv/sample_metadata.csv',
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
            ),
            'METADATA_PARSER' => array(
                'mapping_csv_path' => dirname(__FILE__) . '/assets/csv/sample_mappings.csv',
                'repeatable_wrapper_elements' => array('subject'),
            ),
        );
        $parser = new CsvToMods($settings);
        $mods = $parser->metadata('postcard_10');

        $dom = new \DOMDocument;
        $dom->loadXML($mods);

        $this->assertTrue($dom->schemaValidate($this->path_to_mods_schema), "MODS document generate by CSV to MODS metadata parser did not validate");
        $this->assertRegExp('#<geographic>Victoria, BC</geographic>#', $mods, "CSV to MODS metadata parser did not work");
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
