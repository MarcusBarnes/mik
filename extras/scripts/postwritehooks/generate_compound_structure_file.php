<?php

/**
 * Post-write hook script for MIK that generates a compound object
 * structure file from a directory structure. Used by the CSV compound
 * toolchain. Requires the 'tree' utility to be installed and executable.
 */

// Note: Post-write hook scripts don't get passed the input directory, so
// we'll need to figure out a way to pass that in - maybe populate the
// field defined in config's [WRITER][file_name_field] with the directory
// to start descending at.
$input_dir = trim($argv[1]);
// In production, this path will be in the directory created by MIK for the object.
$output_path = '/tmp/test.structure';

/*
require 'vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$record_key = trim($argv[1]);
$children_record_keys = explode(',', $argv[2]);
$config_path = trim($argv[3]);
$config = parse_ini_file($config_path, true);

$log = new Logger('postwritehooks/validate_mods.php');
$log->pushHandler(new StreamHandler($config['LOGGING']['path_to_log'], Logger::INFO));
*/

$stylesheet = 'tree_to_compound_object.xsl';


/**
 * Main script logic.
 */

/*
 * This script produces an XML file corresponding to a filesystem directory tree as follows:
 * - all directories that contain no subdirectories, only content files, are represented by <child> elements
 *   (equivalent to <page> elements in Cdm). <child> elements have a <content> attribute that
 *   contains the name of the directory where their content is stored, similar to Cdm's <pageptr> element.
 * - all directories that contain other directories (<child> and <parent>) and optionally a MODS.xml file
 *   are represented by <parent> (equivalent to <node> in Cdm). <parent> elements have a <title> attribute
 *   that contains data similar to <nodetitle> in Cdm. This value is used as the compound object's
 *   title if no MODS.xml file is present within the directory.
 */

$xsl_doc = new DOMDocument();
$xsl_doc->load($stylesheet);

$xml_doc = new DOMDocument();
$tree_output = shell_exec("tree -XU $input_dir");
$xml_doc->loadXML($tree_output);

$xslt_proc = new XSLTProcessor();
$xslt_proc->importStylesheet($xsl_doc);
$xslt_proc->registerPHPFunctions();

$output = $xslt_proc->transformToXML($xml_doc);

file_put_contents($output_path, $output);

/**
 * Removes path segments leading up to the last segment.
 *
 * Called from within the XSLT stylesheet.
 */
function get_dir_name() {
    global $input_dir;
    $dir_path = preg_replace('/(\.*)/', '', $input_dir);
    $dir_path = rtrim($dir_path, DIRECTORY_SEPARATOR);
    $base_dir_pattern = '#^.*' . DIRECTORY_SEPARATOR . '#';
    $dir_path = preg_replace($base_dir_pattern, '', $dir_path);
    $dir_path = ltrim($dir_path, DIRECTORY_SEPARATOR);
    return $dir_path;
}

