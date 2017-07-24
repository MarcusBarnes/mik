<?php

namespace mik\metadataparsers\mods;

use mik\tests\MikTestBase;

/**
 * Class MetadataParserTest
 * @package mik\metadataparsers\mods
 * @group metadataparsers
 */
class MetadataParserTest extends MikTestBase
{

    /**
     * Path to the MODS schema.
     * @var string
     */
    private $path_to_mods_schema;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->path_to_temp_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mik_tests_temp_dir";
        @mkdir($this->path_to_temp_dir);
        $this->path_to_log = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mik_metadataparser_test.log";
        // Now we use assets with is /tests/assets, so back up twice.
        $this->path_to_mods_schema = realpath(
            $this->asset_base_dir . DIRECTORY_SEPARATOR . '/../../extras/scripts/mods-3-5.xsd'
        );
    }

    /**
     * @covers \mik\metadataparsers\mods\CsvToMods
     */
    public function testCsvToModsMetadataParser()
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
            ),
            'METADATA_PARSER' => array(
                'mapping_csv_path' => $this->asset_base_dir . '/csv/sample_mappings.csv',
                'repeatable_wrapper_elements' => array('subject'),
            ),
        );
        $parser = new CsvToMods($settings);
        $mods = $parser->metadata('postcard_10');

        $dom = new \DOMDocument;
        $dom->loadXML($mods);

        $this->assertTrue(
            $dom->schemaValidate($this->path_to_mods_schema),
            "MODS document generate by CSV to MODS metadata parser did not validate"
        );
        $this->assertRegExp(
            '#<geographic>Victoria, BC</geographic>#',
            $mods,
            "CSV to MODS metadata parser did not work"
        );
    }
}
