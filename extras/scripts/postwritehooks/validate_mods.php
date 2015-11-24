<?php

/**
 * Post-write hook script for MIK that validates the MODS XML file for each
 * single-file object.
 *
 * Note that file paths used in this script are relative to mik, not
 * this script itself.
 */

require 'vendor/autoload.php';

$path_to_schema = 'extras/scripts/mods-3-5.xsd';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$record_key = trim($argv[1]);
$children_record_keys_json = trim($argv[2]);
$config_json = trim($argv[3]);
$config = json_decode($config_json, true);
// Not used in this script.
$children_record_keys = json_decode($children_record_keys_json);

$log = new Logger('postwritehooks/validate_mods.php');
$log->pushHandler(new StreamHandler($config['LOGGING']['path_to_log'], Logger::INFO));

$path_to_mods = $config['WRITER']['output_directory'] . DIRECTORY_SEPARATOR . $record_key . '.xml';

$mods = new DOMDocument();
$mods->load($path_to_mods);
if ($mods->schemaValidate($path_to_schema)) {
  $log->addInfo("MODS file validates", array('MODS file' => $path_to_mods));
}
else {
  $log->addWarning("MODS file does not validate", array('MODS file' => $path_to_mods));
}
