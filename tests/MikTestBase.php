<?php

namespace mik\tests;

use PHPUnit\Framework\TestCase;

/**
 * Class MikTestBase
 * @package mik\tests
 */
class MikTestBase extends TestCase
{
    /**
     * Path to temporary directory.
     * @var string
     */
    protected $path_to_temp_dir;

    /**
     * Path to output directory.
     * @var string
     */
    protected $path_to_output_dir;

    /**
     * Path to log file.
     * @var string
     */
    protected $path_to_log;

    /**
     * Base directory for testing resources.
     * @var string
     */
    protected $asset_base_dir;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->asset_base_dir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'assets';
        if (isset($this->path_to_temp_dir) && !file_exists($this->path_to_temp_dir)) {
            mkdir($this->path_to_temp_dir);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        if (file_exists($this->path_to_temp_dir)) {
            if (false === MikTestBase::delTree($this->path_to_temp_dir)) {
                throw new Exception("Unable to remove test directory (" . $this->path_to_temp_dir . ")");
            }
        }
        if (isset($this->path_to_output_dir) && is_dir($this->path_to_output_dir)) {
            if (false === MikTestBase::delTree($this->path_to_output_dir)) {
                throw new Exception("Unable to remove test directory (" . $this->path_to_output_dir . ")");
            }
        }
    }

    /**
     * Recursively delete a directory.
     * @param $dir string
     *   Path to the directory
     * @return bool
     *
     */
    protected static function delTree($dir)
    {
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? MikTestBase::delTree("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }
}
