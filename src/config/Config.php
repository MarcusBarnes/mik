<?php
// src/config/Config.php

namespace mik\config;

use League\Csv\Reader;
use GuzzleHttp\Client;

class Config
{

    /**
     * @var Array that contains settings from parse_ini_file.
     */
    public $settings;
    
    /**
     * Create a new Config instance.
     *
     * @param $configPath - path to configuraiton file.
     */
    public function __construct($configPath)
    {
        $this->settings = parse_ini_file($configPath, true);
    }

    /**
     * Wrapper function for calling other functions that validate configuration data.
     *
     * @param $configPath - path to configuraiton file.
     */
    public function validate()
    {
        $this->checkMappingSnippets();
        // $this->checkUrls();
        $this->checkPaths();
    }

    /**
     * Tests metadata mapping snippets for well-formedness.
     */
    public function checkMappingSnippets()
    {
        ini_set('display_errors', false);
        $path = $this->settings['METADATA_PARSER']['mapping_csv_path'];
        // First test that the mappings file exists.
        if (!file_exists($path)) {
            exit("Error: Can't find mappings file at $path\n");
        }

        $reader = Reader::createFromPath($path);
        foreach ($reader as $index => $row) {
            if (count($row) > 1) {
                if (strlen($row[1])) {
                    $doc = new \DOMDocument();
                    if (!@$doc->loadXML($row[1])) {
                        exit("Error: Mapping snippet $row[1] appears to be not well formed\n");
                    }
                }
            }
        }
    }

    /**
     * Tests URLs in configuration files.
     */
    public function checkUrls()
    {
        $client = new Client();

        $sections = array_values($this->settings);
        foreach ($sections as $section) {
            foreach ($section as $key => $value) {
                if (preg_match('/_url/', $key) && strlen($value)) {
                    print "Value is $value\n";
                    $response = $client->get($value);
                    $code = $response->getStatusCode();
                    print "code is $code\n";
                }
            }
        }
    }

    /**
     * Tests filesystem paths in configuration files.
     */
    public function checkPaths()
    {
        // @todo: write code!
    }

}
