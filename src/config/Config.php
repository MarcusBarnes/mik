<?php
// src/config/Config.php

namespace mik\config;

use League\Csv\Reader;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

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
     * @param $type string
     *   One of 'of 'snippets', 'urls', 'paths', or 'all'.
     */
    public function validate($type = 'all')
    {
        switch ($type) {
            case 'all':
                $this->checkMappingSnippets();
                $this->checkPaths();
                $this->checkUrls();
                exit;
                break;
            case 'snippets':
                $this->checkMappingSnippets();
                exit;
                break;
            case 'urls':
                $this->checkUrls();
                exit;
                break;
            case 'paths':
                $this->checkPaths();
                exit;
                break;
        }
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
        print "Mapping snippets are OK\n";
    }

    /**
     * Tests URLs (whose setting names end in _url) in configuration files.
     */
    public function checkUrls()
    {
        $client = new Client();
        $sections = array_values($this->settings);
        foreach ($sections as $section) {
            foreach ($section as $key => $value) {
                if (preg_match('/_url$/', $key) && strlen($value)) {
                    try {
                        $response = $client->get($value);
                        $code = $response->getStatusCode();
                    }
                    catch (RequestException $e) {
                        exit("Error: The URL $value (defined in configuration setting $key) appears to be a bad URL (response code $code).\n");
                    }
                }
            }
        }
        print "URLs are OK\n";
    }

    /**
     * Tests filesystem paths (whose setting names end in _path or _directory) in configuration files.
     */
    public function checkPaths()
    {
        $sections = array_values($this->settings);
        foreach ($sections as $section) {
            foreach ($section as $key => $value) {
                if (preg_match('/(_path|_directory)$/', $key) && strlen($value)) {
                    if (!file_exists($value)) {
                        exit("Error: The path $value (defined in configuration setting $key) does not exist.\n");
                    }
                }
            }
        }
        print "Paths are OK\n";
    }

}
