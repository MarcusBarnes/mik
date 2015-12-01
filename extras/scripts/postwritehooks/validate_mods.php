<?php

/**
 * Post-write hook script for MIK that validates MODS XML files.
 * Works for single-file Islandora import packages as well as newspaper
 * issue packages, and can be extended to handle the MODS.xml files
 * created by other MIK toolchains.
 */

require 'vendor/autoload.php';

// Relative to MIK, not this script.
$path_to_schema = 'extras/scripts/mods-3-5.xsd';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use GuzzleHttp\Client;

$record_key = trim($argv[1]);
$children_record_keys = explode(',', $argv[2]);
$config_path = trim($argv[3]);
$config = parse_ini_file($config_path, true);

$mods_filename = 'MODS.xml';
// The CONTENTdm 'nick' for the field that contains the data used
// to create the issue-level output directories.
$item_info_field_for_issues = 'date';

$log = new Logger('postwritehooks/validate_mods.php');
$log->pushHandler(new StreamHandler($config['LOGGING']['path_to_log'], Logger::INFO));

// Different MIK writers will put the MODS files in different places. We need
// to determine that type of writer is being used and hand off the task of finding
// and validating the MODS files to the appropriate callback.
switch ($config['WRITER']['class']) {
  case 'CdmNewspapers':
    cdm_newspapers_writer($record_key, $children_record_keys, $path_to_schema, $mods_filename, $item_info_field_for_issues, $config, $log);
    break;
  default:
    single_file_writer($record_key, $path_to_schema, $config, $log);
    break;
}

/**
 * Callback to validate the MODS file for each single-file object.
 *
 * @param string $record_key
 *   The value of the record key (pointer) for the current newspaper parent object.
 *
 * @param string $path_to_schema
 *   The path to the MODS schema file.
 *
 * @param array $config
 *   The MIK configuration settings.
 *
 * @param object $log
 *   The Monolog logger object.
 */
function single_file_writer($record_key, $path_to_schema, $config, $log) {
  $path_to_mods = $config['WRITER']['output_directory'] . DIRECTORY_SEPARATOR .
    $record_key . '.xml';
  validate_mods($path_to_schema, $path_to_mods, $log);
}

/**
 * Callback to iterate through a newspaper issue directory and validate all
 * the MODS files therein.
 *
 * @param string $record_key
 *   The value of the record key (pointer) for the current newspaper parent object.
 *
 * @param array $children_record_keys
 *   A list of all the newspaper issue's page pointers.
 *
 * @param string $path_to_schema
 *   The path to the MODS schema file.
 *
 * @param string $mods_filename
 *   The name of the XML file containing the MODS data, including extension.
 *
 * @param string $item_info_field_for_issues
 *   The CONTENTdm nick for the field that contains the string used
 *   to create the issue-level directories in the MIK output.
 *
 * @param array $config
 *   The MIK configuration settings.
 *
 * @param object $log
 *   The Monolog logger object.
 */
function cdm_newspapers_writer($record_key, $children_record_keys, $path_to_schema, $mods_filename, $item_info_field_for_issues, $config, $log) {
  $issue_dir = get_issue_dir($record_key, $item_info_field_for_issues, $config);
  $dir = $config['WRITER']['output_directory'] . DIRECTORY_SEPARATOR . $issue_dir;
  $directory_iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
  foreach ($directory_iterator as $filepath => $info) {
    if (preg_match('/' . $mods_filename . "$/", $filepath)) { 
      validate_mods($path_to_schema, $filepath, $log);
    }
  }
}

/**
 * Validates the specified MODS XML file against the schema and logs
 * the result.
 *
 * @param string $path_to_schema
 *   The path to the MODS schema file.
 *
 * @param string $path_to_mods
 *   The path to the MODS file to be validated.
 *
 * @param object $log
 *   The Monolog logger object.
 */
function validate_mods($path_to_schema, $path_to_mods, $log) {
  $mods = new DOMDocument();
  $mods->load($path_to_mods);
  if ($mods->schemaValidate($path_to_schema)) {
    $log->addInfo("MODS file validates", array('MODS file' => $path_to_mods));
  }
  else {
    $log->addWarning("MODS file does not validate", array('MODS file' => $path_to_mods));
  }
}

/**
 * Get the string identifying the issue-level directory where the
 * page-level subdirectories are within the output directory for
 * newspapers.
 *
 * @param string $record_key
 *   The CONTENTdm object's pointer.
 *
 * @param string $item_info_field_for_issues
 *   The CONTENTdm nick for the field that contains the string used
 *   to create the issue-level directories in the MIK output.
 *
 * @param array $config
 *   The MIK configuration settings.
 *
 * @return string|bool
 *   The value of the CONTENTdm field specified in $item_info_field_for_issues,
 *   or false if the field is not populated for this object.
 */
function get_issue_dir($record_key, $item_info_field_for_issues, $config) {
  // Use Guzzle to fetch the output of the call to GetParent
  // for the current object.
  $url = $config['METADATA_PARSER']['ws_url'] .
    'dmGetItemInfo/' . $config['METADATA_PARSER']['alias'] . '/' . $record_key. '/json';
  $client = new Client();
  try {
    $response = $client->get($url);
  } catch (Exception $e) {
    $this->log->addInfo("CdmNoParent",
      array('HTTP request error' => $e->getMessage()));
    return false;
  }
  $body = $response->getBody();
  $item_info = json_decode($body, true);

  if (is_string($item_info_field_for_issues) && strlen($item_info[$item_info_field_for_issues])) {
    return $item_info[$item_info_field_for_issues];
  }
  else {
    return false;
  }
}
