<?php

/**
 * Shutdown hook script for MIK that combines all the XML files
 * in an output directory into one big XML file. Useful for preparing
 * content for importing in Drupal 8 using Migrate Plus.
 */

$config_path = trim($argv[1]);
$config = parse_ini_file($config_path, TRUE);
$temp_dir = $config['FETCHER']['temp_directory'];

$wrapper_element_name = 'modsCollection';

$dir = $config['WRITER']['output_directory'];
$dir = rtrim($dir, DIRECTORY_SEPARATOR);
$xml_files = glob($dir . '/*.xml');

$output_file_path_temp = $dir . '/metadata.temp';
$output_file_path = $dir . '/metadata.xml';

file_put_contents($output_file_path_temp, '<?xml version="1.0"?>' . "\n" .'<' . $wrapper_element_name . '>' . "\n");

foreach ($xml_files as $xml_file_path) {
    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->load($xml_file_path);
    $dom->formatOutput = true;
    $xml_file_content = $dom->saveXML($dom->documentElement);
    file_put_contents($output_file_path_temp, $xml_file_content . "\n", FILE_APPEND);
    unlink($xml_file_path);
}

file_put_contents($output_file_path_temp, '</' . $wrapper_element_name . '>', FILE_APPEND);
rename($output_file_path_temp, $output_file_path);
