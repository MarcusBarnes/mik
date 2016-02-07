<?php
// src/config/Config.php

namespace mik\config;

use League\Csv\Reader;
use \Monolog\Logger;
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

        // Set up logger.
        $this->pathToLog = $this->settings['LOGGING']['path_to_log'];
        $this->log = new \Monolog\Logger('config');
        $this->logStreamHandler= new \Monolog\Handler\StreamHandler($this->pathToLog, Logger::INFO);
        $this->log->pushHandler($this->logStreamHandler);

        if (count($this->settings['CONFIG'])) {
            foreach ($this->settings['CONFIG'] as $config => $value) {
                $this->log->addInfo("MIK Configuration", array($config => $value));
            }
        }
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
                $this->checkAliases();
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
            case 'aliases':
                $this->checkAliases();
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
                    // We need to make an exception for this option since
                    // CONTENTdm returns a 404 even if the URL exists. Adding
                    // 'getthumbnail' creates a URL that returns a useful
                    // response code.
                    if ($key == 'utils_url') {
                        $value .= 'getthumbnail';
                    }
                    try {
                        $response = $client->get($value);
                        $code = $response->getStatusCode();
                    }
                    catch (RequestException $e) {
                        if ($key == 'utils_url') {
                            $value = preg_replace('/getthumbnail$/', '', $value);
                        }
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
                        exit("The path $value (defined in configuration setting $key) does not exist but will be created for you.\n");
                    }
                }
            }
        }
        print "Paths are OK\n";
    }

    /**
     * Tests whether all CONTENTdm aliases are the same. See https://github.com/MarcusBarnes/mik/issues/146.
     */
    public function checkAliases()
    {
        $sections = array_values($this->settings);
        $aliases = array();
        foreach ($sections as $section) {
            foreach ($section as $key => $value) {
                if (preg_match('/^alias$/', $key) && strlen($value)) {
                    $aliases[] = trim($value);
                }
            }
        }

        $aliases = array_unique($aliases);
        if (count($aliases) > 1) {
            $aliases_string = trim(implode(', ', $aliases));
            exit("Error: The values for CONTENTdm 'alias' settings are not unique: $aliases_string.\n");
        }
        else {
            print "CONTENTdm aliases are OK\n";
        }
    }

}
