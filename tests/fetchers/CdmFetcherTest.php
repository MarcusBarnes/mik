<?php

namespace mik\fetchers;

use mik\tests\MikTestBase;

/**
 * Class CdmFetcher
 * @package mik\fetchers
 * @coversDefaultClass \mik\fetchers\Cdm
 * @group fetchers
 */
class CdmFetcherTest extends MikTestBase
{

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->path_to_temp_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mik_cdm_fetcher_temp_dir";
        $this->path_to_log = $this->path_to_temp_dir . DIRECTORY_SEPARATOR . "mik.log";
    }

    /**
     * @covers ::getItemInfo()
     */
    public function testGetItemInfo()
    {
        $settings = array(
            'FETCHER' => array(
                'record_key' => 'pointer',
                'ws_url' => '',
                'alias' => '',
                'temp_directory' => $this->asset_base_dir . '/cdm/metadata/',
                'use_cache' => false,
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
