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
        if (isset($this->settings['SYSTEM']['verify_ca'])) {
            if ($this->settings['SYSTEM']['verify_ca'] == false) {
                $this->verifyCA = false;
            }
        } else {
            $this->verifyCA = true;
        }

        // Define some options for the CSV fetcher.
        if (isset($this->settings['FETCHER']['field_delimiter'])) {
            $this->csv_field_delimiter = $this->settings['FETCHER']['field_delimiter'];
        } else {
            $this->csv_field_delimiter = ',';
        }
        // Default enclosure is double quotation marks.
        if (isset($this->settings['FETCHER']['field_enclosure'])) {
            $this->csv_field_enclosure = $this->settings['FETCHER']['field_enclosure'];
        }
        // Default escape character is \.
        if (isset($this->settings['FETCHER']['escape_character'])) {
            $this->csv_escape_character = $this->settings['FETCHER']['escape_character'];
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
                $this->checkCsvFile();
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
            case 'input_directories':
                $this->checkInputDirectories();
                exit;
                break;
            case 'csv':
                $this->checkCsvFile();
                exit;
                break;
            default:
                exit;
        }
    }

    /**
     * Tests metadata mapping snippets for well-formedness.
     */
    public function checkMappingSnippets()
    {
        $parsers = array('mods\CsvToMods', 'mods\CdmToMods');
        if (!in_array($this->settings['METADATA_PARSER']['class'], $parsers)) {
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
            if (count($row) > 1 && !preg_match('/^#/', $row[0])) {
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
                    } catch (RequestException $e) {
                        if ($key == 'utils_url') {
                            $value = preg_replace('/getthumbnail$/', '', $value);
                        }
                        exit("Error: The URL $value (defined in configuration setting $key) appears" .
                            "to be a bad URL (response code $code).\n");
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
        } catch (RequestException $e) {
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
                        print "The path $value (defined in configuration setting $key) does not exist but will be " .
                            "created for you.\n";
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
        } else {
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
        $filegetters = array('CdmNewspapers', 'CdmBooks', 'CdmSingleFile', 'CdmPhpDocuments');
        if (in_array($this->settings['FILE_GETTER']['class'], $filegetters)) {
            if (isset($this->settings['FILE_GETTER']['input_directories'])) {
                $input_dirs = $this->settings['FILE_GETTER']['input_directories'];
            }

            if (is_array($input_dirs) && count($input_dirs)) {
                $input_directories = $this->settings['FILE_GETTER']['input_directories'];
                foreach ($input_directories as $input_directory) {
                    if (!file_exists(realpath($input_directory))) {
                        exit("Error: Can't find input directory $input_directory\n");
                    }
                }
                print "Input directory paths are OK.\n";
            } else {
                print "No input directory paths are defined.\n";
            }
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

    /**
     * Tests whether the CSV input file is valid.
     */
    public function checkCsvFile()
    {
        // This check applies only to CSV toolchains.
        if ($this->settings['FETCHER']['class'] != 'Csv') {
            return;
        }

        $csv_has_errors = false;

        try {
            $reader = Reader::createFromPath($this->settings['FETCHER']['input_file']);
            $reader->setDelimiter($this->csv_field_delimiter);
            if (isset($this->csv_field_enclosure)) {
                $reader->setEnclosure($this->csv_field_enclosure);
            }
            if (isset($this->csv_escape_character)) {
                $reader->setEscape($this->csv_escape_character);
            }
        } catch (Exception $re) {
            $csv_has_errors = true;
            print "Error creating CSV reader: " . $re->getMessage() . PHP_EOL;
        }

        // Fetch header row and make sure columns are unique.
        $header = $reader->fetchOne();
        $num_header_columns = count($header);
        foreach ($header as $header_value) {
            if (!strlen($header_value)) {
                $csv_has_errors = true;
                print "Error with CSV input file: it appears that one or more header labels are blank." . PHP_EOL;
            }
        }

        $header_values = array_unique($header);
        if (count($header_values) != $num_header_columns) {
            $csv_has_errors = true;
            print "Error with CSV input file: it appears that the column headers are not unique." . PHP_EOL;
        }

        // Fetch each row and make sure that it contains the correct number of columns.
        $rows = $reader->fetch();
        $row_num = 0;
        foreach ($rows as $row) {
            $row_num++;
            $columns_in_row = count($row);

            // Empty row.
            if ($columns_in_row == 1) {
                print "Row $row_num in the CSV input file appears to be empty; this is OK, just reporting it " .
                    "in case it's unintentional." . PHP_EOL;
                continue;
            }

            if ($columns_in_row != $num_header_columns) {
                $csv_has_errors = true;
                print "Error with CSV input file: it appears that row $row_num does not have " .
                    "the same number of colums ($columns_in_row) as the header row ($num_header_columns)." . PHP_EOL;
            }
        }

        if (!$csv_has_errors) {
            print "CSV input file appears to be OK.". PHP_EOL;
        }
    }
}
