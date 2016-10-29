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

        // Default Mac PHP setups may use Apple's Secure Transport
        // rather than OpenSSL, causing issues with CA verification.
        // Allow configuration override of CA verification at users own risk.
        if (isset($this->settings['SYSTEM']['verify_ca']) ){
            if($this->settings['SYSTEM']['verify_ca'] == false){
              $this->verifyCA = false;
            }
        } else {
            $this->verifyCA = true;
        }

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
     *   One of 'of 'snippets', 'urls', 'paths', 'aliases', 'input_directories', or 'all'.
     */
    public function validate($type = 'all')
    {
        switch ($type) {
            case 'all':
                $this->checkMappingSnippets();
                $this->checkPaths();
                $this->checkOaiEndpoint();
                $this->checkAliases();
                $this->checkInputDirectories();
                $this->checkUrls();
                exit;
                break;
            case 'snippets':
                $this->checkMappingSnippets();
                exit;
                break;
            case 'urls':
                $this->checkUrls();
                $this->checkOaiEndpoint();
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
            case 'input_directories';
                $this->checkInputDirectories();
                exit;
                break;
            default:
                $message = "Sorry, $type is not an allowed value for --checkconfig. " .
                    "Allowed values are 'snippets', 'urls', 'paths', 'aliases', 'input_directories', or 'all'.\n";
                exit($message);
        }
    }

    /**
     * Tests metadata mapping snippets for well-formedness.
     */
    public function checkMappingSnippets()
    {
        $fetchers = array('Cdm', 'Csv');
        if (!in_array($this->settings['FETCHER']['class'], $fetchers)) {
            return; 
        }

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
     * Tests CONTENTdm URLs (whose setting names end in _url) in configuration files.
     */
    public function checkUrls()
    {
        // This check applies only to CONTENTdm toolchains.
        if ($this->settings['FETCHER']['class'] != 'Cdm') {
            return;
        }

        $client = new Client();
        $sections = array_values($this->settings);
        $code = '404';
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
                        $response = $client->get($value, ['verify' => $this->verifyCA]);
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
     * Tests the OAI-PMH base URL ('endpoint').
     */
    public function checkOaiEndpoint()
    {
        // This check applies only to OAI-PMH toolchains.
        if ($this->settings['FETCHER']['class'] != 'Oaipmh') {
            return;
        }

        $client = new Client();
        $base_url = $this->settings['FETCHER']['oai_endpoint'];
        try {
            $response = $client->get($base_url);
            $code = $response->getStatusCode();
        }
        catch (RequestException $e) {
            exit("Error: The OAI endpoint URL $base_url appears to be invalid.\n");
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
                        print "The path $value (defined in configuration setting $key) does not exist but will be created for you.\n";
                    }
                }
            }
        }
        print "Paths are OK\n";
    }

    /**
     * Tests whether all CONTENTdm aliases are the same.
     *
     * See https://github.com/MarcusBarnes/mik/issues/146.
     */
    public function checkAliases()
    {
        // This check applies only to CONTENTdm toolchains.
        if ($this->settings['FETCHER']['class'] != 'Cdm') {
            return;
        }

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
    
    /** 
     * Checks for the existance of input_directories.
     *
     * See https://github.com/MarcusBarnes/mik/issues/169
     */
     public function checkInputDirectories()
     {
        // For Cdm toolchains, where multiple input directories are allowed.
        $filegetters = array('CdmNewspapers', 'CdmSingleFile', 'CdmPhpDocuments');
        if (in_array($this->settings['FILE_GETTER']['class'], $filegetters)) {
            if (!isset($this->settings['FILE_GETTER']['input_directories'])) {
                print "No input directories are defined in the FILE_GETTER section.\n";
                return;
            }
            if (strlen($this->settings['FILE_GETTER']['input_directories'][0]) == 0) {
                print "No input directories are defined in the FILE_GETTER section.\n";
                return;
            }
            $input_directories = $this->settings['FILE_GETTER']['input_directories'];
            foreach ($input_directories as $input_directory) {
                if (!file_exists(realpath($input_directory))) {
                    exit("Error: Can't find input directory $input_directory\n");
                }
            }
        
            print "Input directory paths are OK.\n";
        }
        
        // For Csv toolchains, where a single input directory is allowed.
        $filegetters = array('CsvSingleFile', 'CsvNewspapers');
        if (in_array($this->settings['FILE_GETTER']['class'], $filegetters)) {
            if (!isset($this->settings['FILE_GETTER']['input_directory'])) {
                print "No input directories are defined in the FILE_GETTER section.\n";
                return;
            }
            if (strlen($this->settings['FILE_GETTER']['input_directory']) == 0) {
                print "No input directories are defined in the FILE_GETTER section.\n";
                return;
            }
            $input_directory = $this->settings['FILE_GETTER']['input_directory'];
            if (!file_exists(realpath($input_directory))) {
                exit("Error: Can't find input directory $input_directory\n");
            }
        
            print "Input directory paths are OK.\n";
        }
     }

}
