<?php

/**
 * Post-write hook script for MIK that validates MODS XML files.
 */

require 'vendor/autoload.php';

// Relative to MIK, not this script.
$path_to_schema = 'extras/scripts/mods-3-5.xsd';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$record_key = trim($argv[1]);
$children_record_keys_json = trim($argv[2]);
$config_json = trim($argv[3]);
$config = json_decode($config_json, true);
$children_record_keys = json_decode($children_record_keys_json);

$log = new Logger('postwritehooks/validate_mods.php');
$log->pushHandler(new StreamHandler($config['LOGGING']['path_to_log'], Logger::INFO));

// Different MIK writers will put the MODS files in different places.
switch ($config['WRITER']['class']) {
  case 'CdmNewspapers':
    cdm_newspapers_writer($record_key, $children_record_keys, $path_to_schema, $config, $log);
    break;
  default:
    single_file_writer($record_key, $path_to_schema, $config, $log);
    break;
}

function single_file_writer($record_key, $path_to_schema, $config, $log) {
  $path_to_mods = $config['WRITER']['output_directory'] . DIRECTORY_SEPARATOR .
    $record_key . '.xml';
  $mods = new DOMDocument();
  $mods->load($path_to_mods);
  if ($mods->schemaValidate($path_to_schema)) {
    $log->addInfo("MODS file validates", array('MODS file' => $path_to_mods));
  }
  else {
    $log->addWarning("MODS file does not validate", array('MODS file' => $path_to_mods));
  }
}

function cdm_newspapers_writer($record_key, $children_record_keys, $path_to_schema, $config, $log) {
  $log->addWarning("Sorry, we can't validate MODS.xml files for newspapers yet.", array());
}
