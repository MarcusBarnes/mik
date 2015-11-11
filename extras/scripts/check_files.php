<?php

/**
 * Script to verify that the files expected in MIK output are present.
 */

if (trim($argv[1]) == 'help') {
	print "A script to verify that the files in MIK output are present.\n\n";
	print "Example usage: php check_files.php --cmodel=islandora:sp_basic_image --dir=/tmp/mik_output --files=*.jpg,*.xml\n\n";
    print "Options:\n";
    print "    --cmodel : An Islandora content model PID. Required.\n";
    print "    --dir : The directory containing the files you want to check, without the trailing slash. Required.\n";
    print "    --files : A comma-separated list of files that need to be present. Optional
        (defaults vary by content model). For content models where the filenames are variable,
        use a * to indicate the filename (e.g., '*.jpg, *.xml').\n";
    print "    --log : The path to the log file containing reports of missing files. Optional
        (default is ./mik_check_files.log).\n";
    exit;
}

$options = getopt('', array('cmodel:', 'dir:', 'files::', 'log::'));
$options['log_path'] = (!array_key_exists('log', $options)) ?
    './mik_check_files.log' : $options['log'];

switch ($options['cmodel']) {
    case 'islandora:sp_basic_image':
        islandora_sp_basic_image($options);
        break;
    case 'islandora:sp_large_image_cmodel':
        islandora_sp_large_image_cmodel($options);
        break;        
    case 'islandora:newspaperIssueCModel':
        islandora_newspaper_issue_cmodel($options);
        break;        
    default:
        exit("Sorry, the content model " . $options['cmodel'] . " is not registered with this script.\n");
}

/**
  * Content model specific validation functions.
  */

function islandora_sp_basic_image($options) {
	$options['files'] = (!array_key_exists('files', $options)) ?
	    '*.jpg, *.xml' : $options['files'];
    // print_r($options);
	$files = explode(',', $options['files']);
	// Get a list of all of the first required file.
	$first_file_pattern = $options['dir'] . DIRECTORY_SEPARATOR . trim($files[0]);
    $first_files = glob($first_file_pattern);
    if (!count($first_files)) {
        exit("Can't find any files in " . $options['dir'] . " matching the pattern " . $first_file_pattern . "\n");
    }

    // Check 1: If we haven't exited, confirm that the directory contains the
    // same number of files for each of the entries in $options['files'].
    $file_counts = array($files[0] => count($first_files));
    array_shift($files);
    foreach ($files as $file) {
	    $pattern = $options['dir'] . DIRECTORY_SEPARATOR . trim($file);
        $file_list = glob($pattern);
        $file_counts[$file] = count($file_list);
    }

    // To see if each file has the same count, reduce the number of counts
    // and if we have one value, we're good. If we don't, we have a mismatch.
    $totals = array_values($file_counts);
    $totals = array_unique($totals);
    if (count($totals) != 1) {
      $nums_match = 'No';
    }
    else {
       $nums_match = 'Yes';
    }

    // Check 2: Get all other files in $options['files']	
    // and match each one to each entry in $first_files.
    $extensions = array();
    foreach ($files as $file) {
    	$extensions[] = pathinfo($file, PATHINFO_EXTENSION);
    }
    foreach ($first_files as $file) {
    	$filename = pathinfo($file, PATHINFO_FILENAME);
    	foreach ($extensions as $ext) {
            $file_to_check = $filename . '.' . $ext;
            $full_path = $options['dir'] . DIRECTORY_SEPARATOR . $file_to_check;
            if (file_exists($full_path)) {
                $correspond = 'Yes';
            }
            else {
            	$correspond = 'No';
            }
        }
     }

    // @todo: $correspond evaluates to Yes if each first file has a corresponding
    // other file, but not if there are extra other files. 
    print "Numbers of file types match: $nums_match.\nAll files correspond: $correspond.\n";
}

function islandora_sp_large_image_cmodel($options) {
	$options['files'] = (!array_key_exists('files', $options)) ?
	    'OBJ.tif, MODS.xml' : $options['files'];
    print_r($options);
}

function islandora_newspaper_issue_cmodel($options) {
    print_r($options);
}