<?php
// mik.php
/**
 * Main Move to Islandora Kit script.
 **/

// Use composer to load vendor and project classes.
require 'vendor/autoload.php';

// Get command line options.
// Assumes --longopts.
// Required --config path/to/config/ini_file
// Optional --limit=10 (the number of input objects to proces)
// If no --limit is provided, process all input object in a collection.
$options = getopt('', array('config:', 'limit::'));

$configPath = $options['config'];
if (!file_exists($options['config'])) {
  exit("Sorry, can't find " . $options['config'] . "\n");
}

$ini = parse_ini_file($configPath, TRUE);
// print_r($ini);

if (isset($options['limit'])) {
  $numberOfInputObjects = $options['limit'];
}
else {
  $numberOfInputObjects = NULL;
}

// Configure
use mik\config\Config;
$mikConfig = new Config($configPath);
$settings = $mikConfig->settings;

// Fetch records
$fetcherClass = 'mik\\fetchers\\' . $ini['FETCHER']['class'];
$fetcher = new $fetcherClass($settings);
echo $fetcher->echoPhrase("The $fetcherClass class has been loaded.");
echo $fetcher->testMethod();

// Write Islandora ingest packages
$writerClass = 'mik\\writers\\' . $ini['WRITER']['class'];
$writer = new $writerClass($settings);
$writer->createOutputDirectory();

foreach ($fetcher->getRecords()->records as $record) {
  $record_id = $record->pointer;
  // Parse metadata
  $metadtaClass = 'mik\\metadataparsers\\' . $ini['METADATA_PARSER']['class'];
  $parser = new $metadtaClass($settings);
  echo $parser->echoPhrase("The $metadtaClass class been loaded for CONTENTdm record $record_id.\n");

  // Manipulate metadata
  // Classes are loaded in metadata parsers.

  // Get files
  $fileGetterClass = 'mik\\filegetters\\' . $ini['FILE_GETTER']['class'];
  $fileGetter = new $fileGetterClass($settings);
  echo $fileGetter->echoPhrase("The $fileGetterClass class been loaded for record $record_id.");

  // Manipulate files
  // Classes are loaded in file getters.

  echo $writer->echoPhrase("The $writerClass class been loaded for record $record_id.");

}
