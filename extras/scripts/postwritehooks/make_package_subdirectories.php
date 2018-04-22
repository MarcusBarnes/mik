<?php

/**
 * MIK post-write hook script to move a single-file package (.xml and payload file)
 * to a directory named after the package's record key. This script is described in
 * the MIK Cookbook page
 * https://github.com/MarcusBarnes/mik/wiki/Cookbook:-Generating-MADS-and-accompanying-thumbnails-for-batch-loading-entities.
 */

// MIK post-write hook script setup stuff.
$record_key = trim($argv[1]);
$children_record_keys_string = trim($argv[2]);
$config_path = trim($argv[3]);
$config = parse_ini_file($config_path, true);
$mik_output_dir = $config['WRITER']['output_directory'];

$xml_file_name = 'MADS.xml';

// Get all the files (the .xml and thumbnail) for the current object. There should be only two files.
$package_files_with_no_ext = $mik_output_dir . DIRECTORY_SEPARATOR . $record_key;
$package_files_with_ext = glob($package_files_with_no_ext . ".*");

// Create the object-level output directory.
$package_output_dir = $mik_output_dir . DIRECTORY_SEPARATOR . $record_key;
mkdir($package_output_dir);

// Move each of theh files from the default output location into the
// object-level directory.
foreach ($package_files_with_ext as $package_file) {
    $pathinfo = pathinfo($package_file);
    if ($pathinfo['extension'] == 'xml') {
        rename($mik_output_dir . DIRECTORY_SEPARATOR . basename($package_file), $package_output_dir . DIRECTORY_SEPARATOR . $xml_file_name);
    } else {
        // This file will be the thumbnail file.
        rename($mik_output_dir . DIRECTORY_SEPARATOR . basename($package_file), $package_output_dir . DIRECTORY_SEPARATOR . 'TN.' . $pathinfo['extension']);
    }
}
