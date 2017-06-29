<?php

/**
 * MIK post-write hook script to move a single-file package
 * (.xml and payload file) to a specific directory.
 */

// Directories must already exist. You will need to adjust these.
$destinations = array(
    'pdf' => '/tmp/filesystemfetcher/pdf',
    'tif' => '/tmp/filesystemfetcher/largeimage',
    'jp2' => '/tmp/filesystemfetcher/largeimage',
    'jpg' => '/tmp/filesystemfetcher/basicimage',
);

// MIK post-write hook script setup stuff.
$record_key = trim($argv[1]);
$children_record_keys_string = trim($argv[2]);
$config_path = trim($argv[3]);
$config = parse_ini_file($config_path, true);

// Define various file paths.
$mik_output_dir = $config['WRITER']['output_directory'];
$file_path_with_no_ext = $mik_output_dir . DIRECTORY_SEPARATOR . $record_key;
$files_with_name = glob($file_path_with_no_ext . ".*");
$file_path = $files_with_name[0];
$ext = pathinfo($file_path, PATHINFO_EXTENSION);

// Move the payload and .xml file to the configured directory. Payload file first.
rename($file_path, $destinations[$ext] . DIRECTORY_SEPARATOR . basename($file_path));
// Then MODS XML file.
$mods_dest_path = $destinations[$ext] . DIRECTORY_SEPARATOR . $record_key . '.xml';
rename($mik_output_dir . DIRECTORY_SEPARATOR . $record_key . '.xml', $mods_dest_path);
