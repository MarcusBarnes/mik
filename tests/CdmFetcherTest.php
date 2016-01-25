<?php

namespace mik\fetchers;

class CdmFetcher extends \PHPUnit_Framework_TestCase
{
	    protected function setUp()
    {
        $this->path_to_temp_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mik_csv_fetcher_temp_dir";
	$this->path_to_log = $this->path_to_temp_dir . DIRECTORY_SEPARATOR . "mik.log";
    }
		
    public function testGetItemInfo()
    {
        $settings = array(
            'FETCHER' => array(
                'record_key' => 'pointer',
                'ws_url' => '',
                'alias' => '',
                'temp_directory' => dirname(__FILE__) . '/assets/cdm/metadata/',
             ),
           'LOGGING' => array(
                'path_to_log' => $this->path_to_log,
            ),						 
            'FILE_GETTER' => array(
                'ws_url' => '',
                'alias' => '',
             ),
        );
        $cdm = new Cdm($settings);
        $record = $cdm->getItemInfo('17');
        $this->assertEquals('1979 04 05', $record['date'], "Record date is not 1979 04 05");
    }

}
