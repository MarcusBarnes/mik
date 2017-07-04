<?php

namespace mik\fetchers;

use mik\tests\MikTestBase;

/**
 * Class FilesystemFetcherTest
 * @package mik\fetchers
 * @coversDefaultClass \mik\fetchers\Filesystem
 * @group fetchers
 */
class FilesystemFetcherTest extends MikTestBase
{
    /**
     * Test specific settings.
     * @var array
     */
    private $settings;

    protected function setUp()
    {
        $this->path_to_temp_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mik_filesystem_fetcher_temp_dir";
        parent::setUp();
        $this->path_to_log = $this->path_to_temp_dir . DIRECTORY_SEPARATOR . "mik.log";
        $this->settings = [
            'FETCHER' => [
                'temp_directory' => $this->path_to_temp_dir,
                'use_cache' => false,
            ],
            'FILE_GETTER' => [
                'input_directory' => $this->asset_base_dir . '/filesystemfetcher',
                'validate_input' => false,
                'class' => 'CsvSingleFile',
                'file_name_field' => 'title',
                'use_cache' => false,
            ],
            'LOGGING' => [
                'path_to_log' => $this->path_to_log,
            ],
        ];
    }

    /**
     * @covers ::getRecords()
     */
    public function testGetRecords()
    {
        // Define settings here, not in a configuration file.
        $settings = $this->settings;
        $filesystem = new Filesystem($settings);
        $records = $filesystem->getRecords();
        $this->assertCount(5, $records);
    }

    /**
     * @covers ::getNumRecs()
     */
    public function testGetNumRecs()
    {
        $settings = $this->settings;
        $filesystem = new Filesystem($settings);
        $num_records = $filesystem->getNumRecs();
        $this->assertEquals(5, $num_records);
    }

    /**
     * @covers ::getItemInfo()
     */
    public function testGetItemInfo()
    {
        $settings = $this->settings;
        $filesystem = new Filesystem($settings);
        $record = $filesystem->getItemInfo('testfile2');
        $this->assertEquals('testfile2.tif', $record->title, "Record title is not testfile2.tif");
    }
}
