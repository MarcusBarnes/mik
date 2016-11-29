<?php

use Cocur\BackgroundProcess\BackgroundProcess;

class FitsTest extends \PHPUnit_Framework_TestCase
{

    protected function setUp()
    {
        global $path_to_fits;

        $dummy_output = "Some dummy output.";
        $this->path_to_fits = $path_to_fits;
        $this->path_to_dummy_file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "dummytext.phpunit.txt";
        $this->path_to_fits_output = $this->path_to_dummy_file . ".out";
        file_put_contents($this->path_to_dummy_file, $dummy_output);
    }

    /**
     * @group FITS
     */
    public function testFitsRuns()
    {
        $this->assertFileExists($this->path_to_fits);
        $this->assertFileExists($this->path_to_dummy_file);

        $cmd = $this->path_to_fits . " -i " . $this->path_to_dummy_file . " -xc -o " . $this->path_to_fits_output;
        $process = new BackgroundProcess($cmd);
        $process->run();
        sleep(10);
        $this->assertFileExists($this->path_to_fits_output);
    }

    protected function tearDown()
    {
        @unlink($this->path_to_dummy_file);
        @unlink($this->path_to_fits_output);
    }
}
