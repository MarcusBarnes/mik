<?php

/**
 * Script to verify that the files expected in MIK output are present.
 */

if (count($argv) == 1) {
    print "Enter 'php " . $argv[0] . " help' to see more info.\n";
    exit;
}

if (trim($argv[1]) == 'help') {
    print "A script to verify that the files in MIK output are present.\n\n";
    print "Example usage: php check_files.php --cmodel=islandora:sp_basic_image --dir=/tmp/mik_output --files=*.jpg,*.xml\n\n";
    print "Options:\n";
    print "    --cmodel : An Islandora content model PID. Required.\n";
    print "    --dir : The directory containing the files you want to check, without the trailing slash. Required.\n";
    print "    --files : A comma-separated list of files that need to be present. Required. For content
        models where the filenames are variable, use a * to indicate the filename (e.g., '*.jpg, *.xml').\n";
    print "    --log : The path to the log file containing reports of missing files. Optional (default
        is ./mik_check_files.log).\n";
    exit;
}

$options = getopt('', array('cmodel:', 'dir:', 'files:', 'log::'));
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
 * Checks that each all files identifed in $files['files'] exist for each
 * object in $options['dir'].
 *
 * Example: php check_files.php --cmodel=islandora:sp_basic_image --dir=/path/to/mikoutput --files=*.jpg,*.xml
 */
function islandora_single_file_cmodels($options) {
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
      error_log($file_lists . "\n", 3, $options['log']);
    }
    else {
       $groups_match = 'Yes';
    }
    print "Number of " . $options['files'] . " files matches: $groups_match\n";
}

/**
 * Checks the existence of MODS.xml for each issue in $options['dir'], and
 * for the existence of the files listed in $options['files'] for each page.
 * Does not check for the existence of extra files.
 *
 * Example: php check_files.php --cmodel=islandora:newspaperIssueCModel --dir=/path/to/mikoutput
 *    --files=JP2.jp2,JPEG.jpg,MODS.xml,OBJ.tiff,OCR.txt,TN.jpg
 */
function islandora_newspaper_issue_cmodel($options) {
    $file_patterns = explode(',', $options['files']);

    $all_issue_level_dirs = array();
    $files_missing = false;
    if ($issues_handle = opendir($options['dir'])) {
        while (false !== ($issues_dir = readdir($issues_handle))) {
            if ($issues_dir != "." && $issues_dir != "..") {
                $issue_dir = trim($options['dir'] . DIRECTORY_SEPARATOR . $issues_dir);
                // Test for existence of MODS.xml.
                $mods_path = $issue_dir . DIRECTORY_SEPARATOR . 'MODS.xml';
                if (!file_exists($mods_path)) {
                    error_log("$mods_path does not exist\n", 3, $options['log']);
                    $files_missing = true;
                }
                // Get all the page-level directories in $issue_dir.
                $page_dirs_pattern = trim($issue_dir) . DIRECTORY_SEPARATOR . "*";
                $page_dirs = glob($page_dirs_pattern, GLOB_ONLYDIR);
                // Now check for the existence of each of the specified files.
                foreach ($page_dirs as $page_dir) {
                    foreach ($file_patterns as $file_pattern) {
                        $path_to_file = $page_dir . DIRECTORY_SEPARATOR . $file_pattern;
                        if (!file_exists($path_to_file)) {
                            error_log("$path_to_file does not exist\n", 3, $options['log']);
                            $files_missing = true;
                        }
                    }

                }
            }
        }
        closedir($issues_handle);
    }
    if ($files_missing) {
        print "Some newspaper issues in " . $options['dir'] . " are missing one of " .
            $options['files'] . ". Details are in " . $options['log'] . "\n";
    }
    else {
        print "All newspaper issues in " . $options['dir'] . " have the files " .
            $options['files'] . "\n";
    }
}