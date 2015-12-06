<?php

/**
 * Post-write hook script for MIK that generates output from FITS for
 * each child page of a newspaper issue or book.
 */

/**
 * You will need to adjust the variables in this next section.
 */

// The full path to the FITS executable on your system.
$path_to_fits = '/home/mark/Documents/hacking/fits/fits-0.8.10/fits.sh';
// This should be consistent with your Islandora FITS admin settings.
$fits_output_filename = 'TECHMD.xml';
// Filename of the page-level TIFFs.
$obj_filename = 'OBJ.tiff';
// The CONTENTdm 'nick' for the field that contains the data used
// to create the issue-level output directories.
$item_info_field_for_issues = 'date';

// Include all the components.
require 'vendor/autoload.php';
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use GuzzleHttp\Client;

// Get the parameters passed in from MIK.
$record_key = trim($argv[1]);
$children_record_keys = explode(',', $argv[2]);
$config_path = trim($argv[3]);
$config = parse_ini_file($config_path, true);

// Set up logging.
$log = new Logger('postwritehooks/generate_fits.php');
$log->pushHandler(new StreamHandler($config['LOGGING']['path_to_log'], Logger::INFO));

if (!file_exists($path_to_fits)) {
  $log->addWarning("FITS executable cannot be found", array('Path' => $path_to_fits));
}

if (count($children_record_keys)) {
  foreach ($children_record_keys as $child_record_key) {
    if (!$issue_dir = get_issue_dir_name($record_key, $item_info_field_for_issues, $config)) {
      $log->addWarning("FITS output not generated because issue directory not retrievable from CONTENTdm",
        array('Issue dir' => $record_key));
      exit;
    }
    $path_to_issue_dir = $config['WRITER']['output_directory'] . DIRECTORY_SEPARATOR . $issue_dir;
    $page_dirs = glob($path_to_issue_dir . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);
    foreach ($page_dirs as $path_to_page_dir) {
      $path_to_obj = $path_to_page_dir . DIRECTORY_SEPARATOR . $obj_filename;
      if (file_exists($path_to_obj)) {
        $path_to_fits_output = $path_to_page_dir . DIRECTORY_SEPARATOR . $fits_output_filename;
        $cmd = "$path_to_fits -i $path_to_obj -o $path_to_fits_output";
        exec($cmd, $output, $return_var);
        if ($return_var) {
          $log->addWarning("FITS output not generated due to error with FITS",
            array('FITS return value' => $return_var, 'OBJ file' => $path_to_obj));
        }
        else {
          $log->addInfo("FITS output generated",
            array('OBJ file' => $path_to_obj, 'FITS output' => $path_to_fits_output));
        }
      }
      else {
        $log->addWarning("FITS output not generated because OBJ file not found",
          array('OBJ file' => $path_to_obj));
      }
    }
  }
}

/**
 * Get the string identifying the issue-level directory where the
 * page-level subdirectories are.
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
function get_issue_dir_name($record_key, $item_info_field_for_issues, $config) {
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
