<?php

/**
 * Post-write hook script for MIK that applies an XSTL stylesheet to
 * Dublin Core metadata extracted from OAI-PMH records to convert them
 * to MODS for loading into Islandora.
 */

require 'vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$record_key = trim($argv[1]);
$children_record_keys = explode(',', $argv[2]);
$config_path = trim($argv[3]);
$config = parse_ini_file($config_path, true);

$oai_dc_backup_dir = $config['WRITER']['output_directory'] . DIRECTORY_SEPARATOR . 'oai-dc-backup';
if (!file_exists($oai_dc_backup_dir)) {
    mkdir($oai_dc_backup_dir);
}

$path_to_success_log = $config['WRITER']['output_directory'] . DIRECTORY_SEPARATOR .
    'postwritehook_oai_dc_to_mods_xslt_success.log';
$path_to_error_log = $config['WRITER']['output_directory'] . DIRECTORY_SEPARATOR .
    'postwritehook_oai_dc_to_mods_xslt_error.log';

// Set up logging.
$info_log = new Logger('postwritehooks/oai_dc_to_mods_xslt.php');
$info_handler = new StreamHandler($path_to_success_log, Logger::INFO);
$info_log->pushHandler($info_handler);

$error_log = new Logger('postwritehooks/oai_dc_to_mods_xslt.php');
$error_handler = new StreamHandler($path_to_error_log, Logger::WARNING);
$error_log->pushHandler($error_handler);

$oai_dc_path = $config['WRITER']['output_directory'] . DIRECTORY_SEPARATOR . $record_key . '.xml';
copy($oai_dc_path, $oai_dc_backup_dir);
$info_log->addInfo("Copying $oai_dc_path to $oai_dc_backup_dir");

$mods_path = $oai_dc_path;
$path_to_stylesheet = 'extras/scripts/postwritehooks/oai_dc_to_mods.xsl';

/**
 * Main script logic.
 */

$xsl_doc = new DOMDocument();
$xsl_doc->load($path_to_stylesheet);

$xml_doc = new DOMDocument();
$xml_doc->load($oai_dc_path);

$xslt_proc = new XSLTProcessor();
$xslt_proc->importStylesheet($xsl_doc);
$xslt_proc->registerPHPFunctions();

$output = $xslt_proc->transformToXML($xml_doc);
$info_log->addInfo("Creating $mods_path: $output");

file_put_contents($mods_path, $output);
