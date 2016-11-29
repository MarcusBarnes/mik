<?php

/**
 * MIK post-write hook script that applies an XSTL stylesheet to
 * Dublin Core metadata extracted from OAI-PMH records to convert
 * then to MODS for loading into Islandora.
 */

require 'vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use mik\writers\Oaipmh;

$record_key = trim($argv[1]);
$children_record_keys = explode(',', $argv[2]);
$config_path = trim($argv[3]);
$config = parse_ini_file($config_path, true);

$path_to_stylesheet = 'extras/scripts/postwritehooks/oai_dc_to_mods.xsl';

$oai_dc_backup_dir = $config['WRITER']['output_directory'] .
    DIRECTORY_SEPARATOR . 'oai-dc-backup';
if (!file_exists($oai_dc_backup_dir)) {
    mkdir($oai_dc_backup_dir);
}

// Set up logging.
$path_to_success_log = $config['WRITER']['output_directory'] .
    DIRECTORY_SEPARATOR . 'postwritehook_oai_dc_to_mods_xslt_success.log';
$path_to_error_log = $config['WRITER']['output_directory'] .
    DIRECTORY_SEPARATOR . 'postwritehook_oai_dc_to_mods_xslt_error.log';

$info_log = new Logger('postwritehooks/oai_dc_to_mods_xslt.php');
$info_handler = new StreamHandler($path_to_success_log, Logger::INFO);
$info_log->pushHandler($info_handler);

$error_log = new Logger('postwritehooks/oai_dc_to_mods_xslt.php');
$error_handler = new StreamHandler($path_to_error_log, Logger::WARNING);
$error_log->pushHandler($error_handler);

// Instantiate the MIK writer so we can reuse its normalizeFilename() method.
$writer = new Oaipmh($config);

$normalized_record_key = $writer->normalizeFilename($record_key);
$oai_dc_path = $config['WRITER']['output_directory'] . DIRECTORY_SEPARATOR .
    $normalized_record_key . '.xml';
$oai_dc_backup_path = $oai_dc_backup_dir . DIRECTORY_SEPARATOR .
    $normalized_record_key . '.xml';
if (!copy($oai_dc_path, $oai_dc_backup_path)) {
    $error_log->addWarning("Could not copy OAI-DC file to backup directory",
    	array('OAI-DC file' => $oai_dc_path, 'Backup directory' => $oai_dc_backup_dir));
}
// Overwrite the original DC file.
$mods_path = $oai_dc_path;

/**
 * Main script logic.
 */

try {
    $xsl_doc = new DOMDocument();
    $xsl_doc->load($path_to_stylesheet);

    $xml_doc = new DOMDocument();
    $xml_doc->load($oai_dc_path);

    $xslt_proc = new XSLTProcessor();
    $xslt_proc->importStylesheet($xsl_doc);
    $xslt_proc->registerPHPFunctions();

    $output = $xslt_proc->transformToXML($xml_doc);
    file_put_contents($mods_path, $output);
    $info_log->addInfo("OAI-DC to MODS transform successful",
        array('MODS file' => $mods_path));
}
catch (Exception $e) {
	$error_log->addWarning("Problem with OAI-DC to MODS transform",
        array('MODS file' => $mods_path, 'Error' => $e-getMessage()));
}

/**
 * Converts date like 1993-01-01T08:00:00Z into dates
 * like 1993-01-01.
 *
 * @param string $input
 *   The date string to trim.
 *
 * @return string
 *   The trimmed date.
 */
function trim_date($input) {
  $date = preg_replace('/T.*$/', '', $input);
  return $date;
}

