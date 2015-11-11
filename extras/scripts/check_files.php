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
$options['log'] = (!array_key_exists('log', $options)) ?
    './mik_check_files.log' : $options['log'];

switch ($options['cmodel']) {
    case 'islandora:sp_basic_image':
    case 'islandora:sp_large_image_cmodel':
    case 'islandora:sp_pdf':
    case 'islandora:sp-audioCModel':
    case 'islandora:sp_videoCModel':
        islandora_single_file_cmodels($options);
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

function islandora_single_file_cmodels($options) {
	if (!array_key_exists('files', $options)) {
        exit("The --files options is required.\n");
	}
	$file_patterns = explode(',', $options['files']);

    // Confirm that the directory contains the same number
    // of files for each of the entries in $options['files'].
    $all_file_pattern_counts = array();
    $all_file_pattern_globs = array();
    foreach ($file_patterns as $file_pattern) {
        $glob_pattern = $options['dir'] . DIRECTORY_SEPARATOR . trim($file_pattern);
        $file_list = glob($glob_pattern);
        sort($file_list, SORT_NATURAL);
        $all_file_pattern_globs[$file_pattern] = $file_list;
        $all_file_pattern_counts[$file_pattern] = count($file_list);
    }

    // To see if each file has the same count, reduce the number of counts
    // and if we have one value, we're good. If we don't, we have a mismatch.
    $all_file_pattern_totals = array();
    foreach ($all_file_pattern_counts as $pattern => $count) {
        $all_file_pattern_totals[] = $count;
    }
    $all_file_pattern_totals = array_unique($all_file_pattern_totals);
    if (count($all_file_pattern_totals) != 1) {
      $groups_match = 'No. Lists of all the file patterns has been written to ' . $options['log'];
      $file_lists = var_export($all_file_pattern_globs, true);
      file_put_contents($options['log'], $file_lists);
    }
    else {
       $groups_match = 'Yes';
    }
    print "Number of " . $options['files'] . " files matches: $groups_match\n";
}

function islandora_newspaper_issue_cmodel($options) {
    print_r($options);
}