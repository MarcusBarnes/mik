<?php

/**
  * Post-Write script for MIK that applies XSLTs defined in .ini file
  * to the mods output of MIK. Before transformation of original mods
  * are saved in a subdirecory of 'output_directory' named 'original_mods' 
 */

require 'vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$record_key = trim($argv[1]);
$children_record_keys = explode(',', $argv[2]);
$config_path = trim($argv[3]);
$config = parse_ini_file($config_path, true);

$mods_backup = $config['WRITER']['output_directory'] . DIRECTORY_SEPARATOR . 'original-mods';
mkdir($mods_backup);

$path_to_success_log = $config['WRITER']['output_directory'] . DIRECTORY_SEPARATOR .
    'postwritehook_apply_xslt_success.log';
$path_to_error_log = $config['WRITER']['output_directory'] . DIRECTORY_SEPARATOR .
    'postwritehook_apply_xslt_error.log';

// Set up logging.
$info_log = new Logger('postwritehooks/apply_xslt.php');
$info_handler = new StreamHandler($path_to_success_log, Logger::INFO);
$info_log->pushHandler($info_handler);

$error_log = new Logger('postwritehooks/apply_xslt.php');
$error_handler = new StreamHandler($path_to_error_log, Logger::WARNING);
$error_log->pushHandler($error_handler);

$path_to_mods = $config['WRITER']['output_directory'] . DIRECTORY_SEPARATOR . $record_key . '.xml';
copy($path_to_mods, $mods_backup . DIRECTORY_SEPARATOR . $record_key . ".xml");
$info_log->addInfo("working on file $path_to_mods");

$xslts = $config['XSLT']['stylesheets'];
$xslt_outpath = $config['WRITER']['output_directory'] . DIRECTORY_SEPARATOR . $record_key . '.xml';

transform($path_to_mods, $xslt_outpath, $xslts, $info_log, $error_log);


function transform($path_to_mods, $xslt_outpath, $xslts, $info_log, $error_log){

    $info_log->addInfo("Beginning xslt transformations for ".$path_to_mods);
    foreach($xslts as $xslt){
        $info_log->addInfo("Applying stylesheet ". $xslt);
        $info_log->addInfo("Saxon command line: java -jar saxon9he.jar -s:$path_to_mods -xsl:$xslt  -o:$xslt_outpath");
        exec("java -jar saxon9he.jar -s:$path_to_mods -xsl:$xslt  -o:$xslt_outpath", $ret);
        $info_log->addInfo(sprintf("Output from saxon: %s", implode("\n", $ret)));
    }
}
