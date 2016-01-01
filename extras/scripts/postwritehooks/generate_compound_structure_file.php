<?php

/**
 * Post-write hook script for MIK that generates a compound object
 * structure file from a directory structure. Used by the CSV compound
 * toolchain.
 */

$input_dir = trim($argv[1]);
$output_path = '/tmp/test.structure';

/*
require 'vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use GuzzleHttp\Client;

$record_key = trim($argv[1]);
$children_record_keys = explode(',', $argv[2]);
$config_path = trim($argv[3]);
$config = parse_ini_file($config_path, true);

$log = new Logger('postwritehooks/validate_mods.php');
$log->pushHandler(new StreamHandler($config['LOGGING']['path_to_log'], Logger::INFO));
*/


/**
 * Main script logic.
 */

$dom = new DOMDocument();
$dom->loadXML('<islandora_compound_structure />');
$root = $dom->firstChild;

// $dir will need to be changed to the value indicated in the CSV file (?).

/*
 * This script produces an XML file corresponding to a filesystem directory tree as follows:
 * - all directories that contain no subdirectories are represented by <_child> elements
 *   (equivalent to <page> elements in Cdm). @todo: <_child> elements need to have a single <child_id>
 *   element that contains an ID similar to Cdm's <pageptr> element.
 * - all directories that contain other directories (<_child> and <_parent>) are represented
 *   by <_parent> (equivalent to <node> in Cdm). @todo: <_parent> elements can have a <parent_title> child
 *   element that contains data similar to <nodetitle> in Cdm.
 */

foreach (glob($input_dir . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) as $path) {
    if (is_dir($path)) {
        $type = get_directory_type($path);
        $dir_name = get_dir_name($path);
        // $top_level_object_element = $dom->createElement($dir_name);
        $top_level_object_element = $dom->createElement($dir_name . '_' . $type);
        $top_level_object_element = append_child_dirs($dom, $top_level_object_element, $path);
        $root->appendChild($top_level_object_element);
    }
}

$dom->formatOutput = true;
$dom->save($output_path);


/**
 * Recursive function to add child directories to the XML.
 */
function append_child_dirs($dom, $element, $path) {
    foreach (glob($path . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) as $dir) {
        if (is_dir($dir)) {
            $type = get_directory_type($dir);
            $dir_name = get_dir_name($dir);
            // $child = $dom->createElement($dir_name);
            $child = $dom->createElement($dir_name . '_' . $type);
            $child = append_child_dirs($dom, $child, $path . DIRECTORY_SEPARATOR . $dir_name);
            $element->appendChild($child);
        }
    }
    return $element;
}

function get_directory_type($dir) {
    $type = 'child';
    foreach (glob($dir . DIRECTORY_SEPARATOR . '*') as $member) {
        if (is_dir($member)) {
            $type = 'parent';
        }
    }
    return $type;
}

/**
 * Removes path segments leading up to the last segment.
 */
function get_dir_name($path) {
    $dir_path = preg_replace('/(\.*)/', '', $path);
    $dir_path = rtrim($dir_path, DIRECTORY_SEPARATOR);
    $base_dir_pattern = '#^.*' . DIRECTORY_SEPARATOR . '#';
    $dir_path = preg_replace($base_dir_pattern, '', $dir_path);
    $dir_path = ltrim($dir_path, DIRECTORY_SEPARATOR);
    return $dir_path;
}