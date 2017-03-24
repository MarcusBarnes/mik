<?php

/**
 * Shutdown hook script for MIK that deletes the contents of the
 * temp_directory defined in the MIK .ini file.
 */

$config_path = trim($argv[1]);
$config = parse_ini_file($config_path, TRUE);
$temp_dir = $config['FETCHER']['temp_directory'];

delete_temp_files($temp_dir);

function delete_temp_files($temp_dir) {
    $temp_files = glob($temp_dir . '/*');
    foreach($temp_files as $temp_file) {
        unlink($temp_file);
    }
}
