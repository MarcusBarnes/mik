<?php

class FetcherTest extends PHPUnit_Framework_TestCase
{

    public function testEchoPhrase()
    {
        $settings = array();
        $a = new Fetcher($settings);

        $this->assertEquals("It's fun to test code.", $a->echoPhrase("It's fun to test code."));
    }
}
