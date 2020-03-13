<?php

namespace mik\metadataparsers\templated;

use mik\fetchers\Csv;
use mik\tests\MikTestBase;
use mik\writers\CsvSingleFile;

/**
 * Class MetadataManipulatorAddUuidToTemplatedTest
 * @package mik\metadataparsers\templated
 * @coversDefaultClass \mik\metadataparsers\templated\Templated
 * @group metadatamanipulators
 */
class MetadataManipulatorAddUuidToTemplatedTest extends MikTestBase
{

    /**
     * Log path.
     * @var string
     */
    private $path_to_input_validator_log;

    /**
     * Path to MODS schema
     * @var string
     */
    private $path_to_mods_schema;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->path_to_temp_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mik_templated_temp_dir";
        $this->path_to_output_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mik_templated_output_dir";
        $this->path_to_log = $this->path_to_temp_dir . DIRECTORY_SEPARATOR . "mik.log";
        $this->path_to_mods_schema = realpath(
            $this->asset_base_dir . DIRECTORY_SEPARATOR . '/../../extras/scripts/mods-3-5.xsd'
        );
        $this->path_to_manipulator_log = $this->path_to_temp_dir .
            DIRECTORY_SEPARATOR . "mik_metadatamanipulator_test.log";
    }

    /**
     * @covers ::metadata
     * @covers ::populateTemplate
     */
    public function testCreateMetadata()
    {
        $settings = array(
            'FETCHER' => array(
                'class' => 'Csv',
                'input_file' => $this->asset_base_dir . '/csv/sample_metadata.csv',
                'record_key' => 'ID',
                'temp_directory' => $this->path_to_temp_dir,
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
                'template' => $this->asset_base_dir . '/templated_metadata_parser/templated_mods_twig.xml',
            ),
            'MANIPULATORS' => array(
                'metadatamanipulators' => array(
                    'AddUuidToTemplated|' . $this->asset_base_dir .
                        '/templated_metadata_parser/adduuidtotemplatedtest.xml'
                 ),
            ),
        );

        $parser = new Templated($settings);
        $mods = $parser->metadata('postcard_1');

        $dom = new \DOMDocument;
        $dom->loadXML($mods);

        $this->assertTrue(
            $dom->schemaValidate($this->path_to_mods_schema),
            "MODS document generate by Templated metadata parser did not validate"
        );
        $identifier_element = '<identifier type="uuid">';
        $this->assertContains($identifier_element, $mods, "AddUuidToTemplated metadata manipulator did not work");
    }
}
