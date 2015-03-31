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

// @ToDo - validate that file at path exists.
$configPath = $options['config'];
$ini = parse_ini_file($configPath, TRUE);

// @ToDo - validate - is number.
$numberOfInputObjects = $options['limit'];

// Configure
//echo "The configuration file is located at: " . $configPath . "\n";
//echo "limit: " . $numberOfInputObjects . "\n";
use mik\config\Config;
$mikConfig = new Config($configPath);
//echo $mikConfig->echoPhrase("Welcome the Move to Islandora Kit project.\n");
// var_dump($mikConfig);

// Fetch Metadata
// $fetcher = 'mik\\fetcher\\' .  $ini['FETCHER']['class'];
// use $fetcher
// use mik\metadata\ModsMetadata;
print_r($ini);

// $settings = $mikConfig->settings;
// $modsMedadata = new ModsMetadata($settings);
//print_r($modsMedadata->collectionMappingArray);


//Parse metadata

//Manipulate metadata

//Get files

//Manipulate files

//Write Islandora ingest packages
