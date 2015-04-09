<?php

namespace mik\fetchers;

class CsvTest extends \PHPUnit_Framework_TestCase
{

    public function testGetRecords()
    {
        // We define settings here, not in a settings file.
        $settings = array('FETCHER' => array('input_file' => dirname(__FILE__) . '/assets/test.csv'));
        $a = new Csv($settings);
        $records = $a->getRecords();
        $this->assertCount(3, $records);
    }
}
