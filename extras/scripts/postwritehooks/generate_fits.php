<?php

/**
 * Post-write hook script for MIK that generates output from FITS for
 * each child page of a newspaper issue or book.
 */

// Relative to MIK, not this script.
require 'vendor/autoload.php';

// The full path to the FITS executable on your system.
$path_to_fits = '/usr/local/fits/fits.sh';
// This should be consistent with your Islandora FITS admin settings.
$fits_output_filename = 'TECHMD.xml';
// Filename of the page-level TIFFs.
$obj_filename = 'OBJ.tif';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// Get the incoming parameters.
$record_key = trim($argv[1]);
$children_record_keys_json = trim($argv[2]);
$config_json = trim($argv[3]);
$config = json_decode($config_json, true);
$children_record_keys = json_decode($children_record_keys_json);

// Set up logging.
$log = new Logger('postwritehooks/generatee_fits.php');
$log->pushHandler(new StreamHandler($config['LOGGING']['path_to_log'], Logger::INFO));

if (!file_exists($path_to_fits)) {
  $log->addWarning("FITS executable cannot be found", array('Path' => $path_to_fits));
}

if (count($children_record_keys)) {
  $sequence = 0;
  foreach ($children_record_keys as $child_record_key) {
    $sequence++;
    $path_to_page_dir = $config['WRITER']['output_directory'] . DIRECTORY_SEPARATOR .
      $record_key . DIRECTORY_SEPARATOR . $sequence;
    $path_to_obj = $path_to_page_dir . DIRECTORY_SEPARATOR . $obj_filename;
    $path_to_fits_output = $path_to_page_dir . DIRECTORY_SEPARATOR . $fits_output_filename;

    $cmd = "$path_to_fits -i $path_to_obj -o $path_to_fits_output";
    exec($cmd, $output, $return_var);
    if ($return_var) {
      $log->addWarning("FITS output not generated", array('OBJ file' => $path_to_obj));
    }
  }
}
