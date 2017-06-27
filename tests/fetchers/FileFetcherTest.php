<?php

namespace mik\fetchers;

use PHPUnit\Framework\TestCase;

/**
 * Class FileFetcherTest
 * @package mik\fetchers
 * @group FileFetcher
 * @coversDefaultClass \mik\fetchers\FileFetcher
 */
class FileFetcherTest extends TestCase
{

    /**
     * Temporary directory.
     *
     * @var string
     */
    private $temp_dir;

    /**
     * Path to log file.
     * @var string
     */
    private $log_path;

    private $settings;


    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->temp_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mik_csv_fetcher_temp_dir";
        $this->log_path = $this->temp_dir . DIRECTORY_SEPARATOR . "mik.log";
        $this->settings = [
            'FETCHER' => [
                'source_directory' => realpath(dirname(__FILE__) . '/../assets/filefetcher'),
            ],
            'LOGGING' => [
                'path_to_log' => $this->log_path,
                'log_level' => 'DEBUG',
            ],
        ];
    }

    /**
     * @covers ::parseSettings()
     * @expectedException \Exception
     */
    public function testMissingDirectory()
    {
        $settings = $this->settings;
        unset($settings['FETCHER']['source_directory']);
        $fetcher = new FileFetcher($settings);
    }

    /**
     * @covers ::parseSettings()
     * @expectedException \Exception
     */
    public function testInvalidDirectory()
    {
        $settings = $this->settings;
        $settings['FETCHER']['source_directory'] = dirname(__FILE__) . '/assets/filefetch';

        $fetcher = new FileFetcher($settings);
    }

    /**
     * @covers ::parseSettings()
     * @expectedException \Exception
     */
    public function testInvalidRegex()
    {
        $settings = $this->settings;
        $settings['FETCHER']['source_file_regex'] = '/as.\$';

        $fetcher = new FileFetcher($settings);
    }

    /**
     * @covers ::parseSettings()
     * @covers ::getFileList()
     * @covers ::iterateDirectory()
     * @covers ::getRecords()
     * @covers ::getNumRecs()
     */
    public function testGetAllRecords()
    {
        $settings = $this->settings;

        $fetcher = new FileFetcher($settings);
        $records = $fetcher->getRecords();
        $this->assertCount(4, $records, "Returned list is incorrect.");
        $count = $fetcher->getNumRecs();
        $this->assertEquals(4, $count, "Count is incorrect.");
    }

    /**
     * @covers ::parseSettings()
     * @covers ::getFileList()
     * @covers ::iterateDirectory()
     * @covers ::getRecords()
     * @covers ::getNumRecs()
     */
    public function testGetAllRecordsRecursive()
    {
        // Define settings here, not in a configuration file.
        $settings = $this->settings;
        $settings['FETCHER']['recurse_directories'] = true;

        $fetcher = new FileFetcher($settings);
        $records = $fetcher->getRecords();
        $this->assertCount(6, $records, "Returned list is incorrect.");
        $count = $fetcher->getNumRecs();
        $this->assertEquals(6, $count, "Count is incorrect.");
    }

    /**
     * @covers ::parseSettings()
     * @covers ::getFileList()
     * @covers ::iterateDirectory()
     * @covers ::getRecords()
     * @covers ::getNumRecs()
     */
    public function testGetLimitRecords()
    {
        $settings = $this->settings;

        $fetcher = new FileFetcher($settings);
        $records = $fetcher->getRecords(3);
        $this->assertCount(3, $records, "Returned list is incorrect.");
        $count = $fetcher->getNumRecs();
        $this->assertEquals(4, $count, "Count is incorrect.");
    }

    /**
     * @covers ::parseSettings()
     * @covers ::getFileList()
     * @covers ::iterateDirectory()
     * @covers ::getRecords()
     * @covers ::getNumRecs()
     */
    public function testGetXmlRecords()
    {
        $settings = $this->settings;
        $settings['FETCHER']['source_file_regex'] = "/.xml$/";

        $fetcher = new FileFetcher($settings);
        $records = $fetcher->getRecords();
        $this->assertCount(2, $records, "Returned list is incorrect.");
        $count = $fetcher->getNumRecs();
        $this->assertEquals(2, $count, "Count is incorrect.");
    }

    /**
     * @covers ::parseSettings()
     * @covers ::getFileList()
     * @covers ::iterateDirectory()
     * @covers ::getRecords()
     * @covers ::getNumRecs()
     */
    public function testGetXmlRecordsRecurse()
    {
        $settings = $this->settings;
        $settings['FETCHER']['source_file_regex'] = '/.*\.xml$/';
        $settings['FETCHER']['recurse_directories'] = true;

        $fetcher = new FileFetcher($settings);
        $records = $fetcher->getRecords();
        $this->assertCount(3, $records, "Returned list is incorrect.");
        $count = $fetcher->getNumRecs();
        $this->assertEquals(3, $count, "Count is incorrect.");
    }

    /**
     * @covers ::parseSettings()
     * @covers ::getFileList()
     * @covers ::iterateDirectory()
     * @covers ::getItemInfo()
     */
    public function testGetItemInfo()
    {
        $settings = $this->settings;

        $record_id = $settings['FETCHER']['source_directory'] . '/file1.xml';

        $fetcher = new FileFetcher($settings);
        $record = $fetcher->getItemInfo($record_id);
        $this->assertEquals('file1.xml', $record['filename'], 'Filename does not match.');
    }

    protected function tearDown()
    {
        $temp_files = glob($this->temp_dir . '/*');
        foreach ($temp_files as $temp_file) {
            @unlink($temp_file);
        }
        @rmdir($this->temp_dir);
    }

}