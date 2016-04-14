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
    print "    --issue_level_metadata : Used only with the islandora:newspaperIssueCModel content model.
         The name of the metadata file to check existence of at the issue level (default is MODS.xml).\n";
    exit;
}

$options = getopt('', array('cmodel:', 'dir:', 'files:', 'log::', 'issue_level_metadata::'));
$options['log'] = (!array_key_exists('log', $options)) ?
    './mik_check_files.log' : $options['log'];

// Append a timestamp to the log.
error_log("Log produced by " . $argv[0] . " on " . date('l jS \of F Y h:i:s A') . "\n", 3, $options['log']);

// Check to see if the specified directory exists and if not, exit.
if (!file_exists($options['dir'])) {
    print "Sorry, " . $options['dir'] . " does not appear to exist.\n";
    exit;
}

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
    case 'islandora:bookCModel':
        islandora_book_cmodel($options);
        break;
    default:
        exit("Sorry, the content model " . $options['cmodel'] . " is not registered with this script.\n");
}

/**
 * Checks that each all files identifed in $options['files'] exist for each
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
	$all_files_we_expect = array();
    foreach ($file_patterns as $file_pattern) {
        $glob_pattern = $options['dir'] . DIRECTORY_SEPARATOR . trim($file_pattern);
        $file_list = glob($glob_pattern);
				$all_files_we_expect = array_merge($all_files_we_expect, $file_list);
        sort($file_list, SORT_NATURAL);
        $all_file_pattern_globs[$file_pattern] = $file_list;
        $all_file_pattern_counts[$file_pattern] = count($file_list);
    }

	// We need to figure out if there are any files in the directory that don't
	// belong (I'm looking at you thumbs.db). Get a list of all files in the
	// directory so we can compare it to just the files we expect to be there.
	$all_files_pattern = $options['dir'] . DIRECTORY_SEPARATOR . '*.*';
	$all_files = glob($all_files_pattern);
	$unexpected_files = array_diff($all_files, $all_files_we_expect);

    // Since we can have multiple non-XML extensions in one directory as long
	// as they both belong to the same content model (e.g., *.tif and *.jp2
	// in one islandora:sp_large_image_cmodel batch) let's get the count of
	// all *.xml files and see if the remainder add up to its count.
    $count_files_xml = $all_file_pattern_counts['*.xml'];
    print "There are $count_files_xml .xml files\n";
	// Remove the .xml list so we don't count it twice.
	unset($all_file_pattern_counts['*.xml']);
	$count_files_other_patterns = 0;
	foreach ($all_file_pattern_counts as $pattern => $count) {
		$count_files_other_patterns = $count_files_other_patterns + $count;
	}

	if ($count_files_xml == $count_files_other_patterns) {
	    $groups_match = 'Yes';
	}
	else {
		$groups_match = 'No. Lists of all the file patterns has been written to ' . $options['log'];
	    $file_lists = var_export($all_file_pattern_globs, true);
        error_log($file_lists . "\n", 3, $options['log']);
	}

    print "Number of " . $options['files'] . " files matches: $groups_match\n";
	if ($num_unexecpted_files = count($unexpected_files)) {
	    print "Number of unexpected files detected: $num_unexecpted_files. See the log for more detail.\n";
		error_log("Unexpected files:\n", 3, $options['log']);
		$unexpected_file_list = array_values($unexpected_files);
		$unexpected_file_list = var_export($unexpected_file_list, true);
        error_log($unexpected_file_list . "\n", 3, $options['log']);
	}
	else {
		print "No unexpected files detected.\n";
	}
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
    $options['issue_level_metadata'] = (!array_key_exists('issue_level_metadata', $options)) ?
        'MODS.xml' : $options['issue_level_metadata'];
    $all_issue_level_dirs = array();
    $files_missing = false;
    $pages_missing = false;
    $extra_files_in_issues_dir = false;
    $extra_files_in_issue_dir = false;
    $extra_files_in_pages_dir = false;
    $bad_ocr_encoding = false;
    if ($issues_handle = opendir($options['dir'])) {
        while (false !== ($issues_dir = readdir($issues_handle))) {
            // Check to make sure that there are no files in the issues directory
            // other than MODS.xml and TN.jpg.
            if (is_file($options['dir'] . DIRECTORY_SEPARATOR . $issues_dir)) {
                error_log($options['dir'] . DIRECTORY_SEPARATOR . $issues_dir . " should not exist.\n", 3, $options['log']);
                $extra_files_in_issues_dir = true;
            }

            if ($issues_dir != "." && $issues_dir != "..") {
                $issue_dir = trim($options['dir'] . DIRECTORY_SEPARATOR . $issues_dir);
                // Test for existence of MODS.xml.
                if (is_dir($issue_dir)) {
                    $metadata_path = $issue_dir . DIRECTORY_SEPARATOR . $options['issue_level_metadata'];
                    if (!file_exists($metadata_path)) {
                        error_log("$metadata_path does not exist.\n", 3, $options['log']);
                        $files_missing = true;
                    }
                    // Issue-level check for TN.jpg hard-coded for now.
                    $tn_path = $issue_dir . DIRECTORY_SEPARATOR . 'TN.jpg';
                    if (!file_exists($tn_path)) {
                        error_log("$tn_path does not exist.\n", 3, $options['log']);
                        $files_missing = true;
                    }
                }

                // Check for files other than MODS.xml and TN.jpg in $issue_dir.
                if (is_dir($issue_dir)) {
                    $issue_dir_contents = scandir($issue_dir);
                    foreach ($issue_dir_contents as $issue_dir_file) {
                        // To whoever needs to debug or maintain this... please forgive me. I am not a monster.
                        $issue_level_metadata_file = $issue_dir . DIRECTORY_SEPARATOR . $options['issue_level_metadata'];
                        if (is_file($issue_dir . DIRECTORY_SEPARATOR . $issue_dir_file) &&
                                ($issue_dir . DIRECTORY_SEPARATOR . $issue_dir_file != $issue_level_metadata_file)) {
                            $issue_level_tn_file = $issue_dir . DIRECTORY_SEPARATOR . 'TN.jpg';
                            if (is_file($issue_dir . DIRECTORY_SEPARATOR . $issue_dir_file) &&
                                ($issue_dir . DIRECTORY_SEPARATOR . $issue_dir_file != $issue_level_tn_file)) {
                                error_log($issue_dir . DIRECTORY_SEPARATOR . $issue_dir_file .
                                    " should not exist.\n", 3, $options['log']);
                                $extra_files_in_issue_dir = true;
                            }
                        }
                    }
                }

                // Get all the page-level directories in $issue_dir.
                $page_dirs_pattern = trim($issue_dir) . DIRECTORY_SEPARATOR . "*";
                $page_dirs = glob($page_dirs_pattern, GLOB_ONLYDIR);

                // Count the number of page_dirs against expected number from issue-level MODS.XML
                $mods_path = $issue_dir . DIRECTORY_SEPARATOR . $options['issue_level_metadata'];
                $expectedNumPageDirs = expectedNumPageDirFromModsXML($mods_path);
                $numPageDirs = count($page_dirs);
                if ($expectedNumPageDirs != $numPageDirs) {
                    $error_msg = "For issue $issue_dir, ";
                    $error_msg .= "the number of directories for newspaper pages ($numPageDirs) ";
                    $error_msg .= " does not match the expected number ($expectedNumPageDirs)\n";
                    error_log($error_msg, 3, $options['log']);
                    $pages_missing = true;
                }

                // Now check for the existence of each of the specified files.
                foreach ($page_dirs as $page_dir) {
                    foreach ($file_patterns as $file_pattern) {
                        $path_to_file = $page_dir . DIRECTORY_SEPARATOR . $file_pattern;
                        if (!file_exists($path_to_file) && !is_dir($path_to_file) && $path_to_file != $options['log']) {
                            error_log("$path_to_file does not exist.\n", 3, $options['log']);
                            $files_missing = true;
                        }
                    }

                    // Check for extraneous files in the page directory.
                    $page_dir_contents = scandir($page_dir);
                    // Remove . and ..
                    $page_dir_contents = array_slice($page_dir_contents, 2);
                    foreach ($page_dir_contents as $page_dir_file) {
                        if (!in_array($page_dir_file, $file_patterns)) {
                            error_log($page_dir . DIRECTORY_SEPARATOR . $page_dir_file .
                                " should not exist.\n", 3, $options['log']);
                            $extra_files_in_pages_dir = true;
                        }
                    }

                    // Check each OCR.txt file to ensure it's encoded in UTF-8.
                    $path_to_ocr_file = $page_dir . DIRECTORY_SEPARATOR . 'OCR.txt';
                    if (file_exists($path_to_ocr_file)) {
                        $ocr_content = file_get_contents($path_to_ocr_file);
                        if (!mb_check_encoding($ocr_content, 'UTF-8')) {
                            error_log("$path_to_ocr_file is not valid UTF-8\n", 3, $options['log']);
                            $bad_ocr_encoding  = true;
                        }
                    }
                }
            }
        }
        closedir($issues_handle);
        clearstatcache();
    }

    if ($extra_files_in_issues_dir) {
        print "** Files exist in ". $options['dir'] . " that should not be present.\n";
    }
    else {
        print "There are no unexpected files in " . $options['dir'] . ".\n";
    }

    if ($extra_files_in_issue_dir) {
        print "** Files exist in one or more issue-level directories that should not be present.\n";
    }
    else {
        print "There are no unexpected files in any issue-level directories.\n";
    }

    if ($extra_files_in_pages_dir) {
        print "** Files exist in one or more newspaper page directories that should not be present.\n";
    }
    else {
        print "There are no unexpected files in any newspaper page directories.\n";
    }

    if ($files_missing) {
        print "** Some newspaper issues in " . $options['dir'] . " are missing one of " .
            $options['files'] . ".\n";
    }
    else {
        print "All newspaper issues in " . $options['dir'] . " have the files " .
            $options['files'] . ".\n";
    }

    if ($pages_missing) {
        print "** There is a mismatch between the number of newspaper pages in " . $options['dir']
            . " and the number of newspaper pages expected based on the CPD.XML contained in the issue level MODS XML.\n";
    } else {
        print "All of expected newspaper pages are present.\n";
    }

    if (in_array('OCR.txt', $file_patterns)) {
        if ($bad_ocr_encoding) {
            print "** Some OCR.txt files in " . $options['dir'] . " appear not to be valid UTF-8.\n";
        }
        else {
            print "All OCR.txt files in " . $options['dir'] . " appear to be valid UTF-8.\n";
        }
    }


    print "More detail may be available in " . $options['log'] . ".\n";
}

/**
 * Checks the existence of MODS.xml for each issue in $options['dir'], and
 * for the existence of the files listed in $options['files'] for each page.
 * Does not check for the existence of extra files.
 *
 * Example: php check_files.php --cmodel=islandora:bookCModel --dir=/path/to/mikoutput
 *    --files=JP2.jp2,JPEG.jpg,MODS.xml,OBJ.tiff,OCR.txt,TN.jpg,TECHMD.xml --log=/tmp/mylog.txt
 */
function islandora_book_cmodel($options) {
    $file_patterns = explode(',', $options['files']);
    $options['book_level_metadata'] = (!array_key_exists('book_level_metadata', $options)) ?
        'MODS.xml' : $options['book_level_metadata'];
    // $all_issue_level_dirs = array();
    $files_missing = false;
    $pages_missing = false;
    $extra_files_in_books_dir = false;
    $extra_files_in_book_dir = false;
    $extra_files_in_pages_dir = false;
    $bad_ocr_encoding = false;
    if ($books_handle = opendir($options['dir'])) {
        while (false !== ($books_dir = readdir($books_handle))) {
            // Check to make sure that there are no files in the issues directory
            // other than MODS.xml and TN.jpg.
            if (is_file($options['dir'] . DIRECTORY_SEPARATOR . $books_dir)) {
                error_log($options['dir'] . DIRECTORY_SEPARATOR . $books_dir . " should not exist.\n", 3, $options['log']);
                $extra_files_in_books_dir = true;
            }

            if ($books_dir != "." && $books_dir != "..") {
                $book_dir = trim($options['dir'] . DIRECTORY_SEPARATOR . $books_dir);
                // Test for existence of MODS.xml.
                if (is_dir($book_dir)) {
                    $metadata_path = $book_dir . DIRECTORY_SEPARATOR . $options['book_level_metadata'];
                    if (!file_exists($metadata_path)) {
                        error_log("$metadata_path does not exist.\n", 3, $options['log']);
                        $files_missing = true;
                    }
                    // Issue-level check for TN.jpg hard-coded for now.
                    $tn_path = $book_dir . DIRECTORY_SEPARATOR . 'TN.jpg';
                    if (!file_exists($tn_path)) {
                        error_log("$tn_path does not exist.\n", 3, $options['log']);
                        $files_missing = true;
                    }
                }

                // Check for files other than MODS.xml and TN.jpg in $issue_dir.
                if (is_dir($book_dir)) {
                    $book_dir_contents = scandir($book_dir);
                    foreach ($book_dir_contents as $book_dir_file) {
                        // To whoever needs to debug or maintain this... please forgive me. I am not a monster.
                        $book_level_metadata_file = $book_dir . DIRECTORY_SEPARATOR . $options['book_level_metadata'];
                        if (is_file($book_dir . DIRECTORY_SEPARATOR . $book_dir_file) &&
                                ($book_dir . DIRECTORY_SEPARATOR . $book_dir_file != $book_level_metadata_file)) {
                            $book_level_tn_file = $book_dir . DIRECTORY_SEPARATOR . 'TN.jpg';
                            if (is_file($book_dir . DIRECTORY_SEPARATOR . $book_dir_file) &&
                                ($book_dir . DIRECTORY_SEPARATOR . $book_dir_file != $book_level_tn_file)) {
                                error_log($book_dir . DIRECTORY_SEPARATOR . $book_dir_file .
                                    " should not exist.\n", 3, $options['log']);
                                $extra_files_in_book_dir = true;
                            }
                        }
                    }
                }

                // Get all the page-level directories in $book_dir.
                $page_dirs_pattern = trim($book_dir) . DIRECTORY_SEPARATOR . "*";
                $page_dirs = glob($page_dirs_pattern, GLOB_ONLYDIR);

                // Count the number of page_dirs against expected number from issue-level MODS.XML
                $mods_path = $book_dir . DIRECTORY_SEPARATOR . $options['book_level_metadata'];
                $expectedNumPageDirs = expectedNumPageDirFromModsXML($mods_path);
                $numPageDirs = count($page_dirs);
                if ($expectedNumPageDirs != $numPageDirs) {
                    $error_msg = "For issue $book_dir, ";
                    $error_msg .= "the number of directories for book pages ($numPageDirs) ";
                    $error_msg .= " does not match the expected number ($expectedNumPageDirs)\n";
                    error_log($error_msg, 3, $options['log']);
                    $pages_missing = true;
                }

                // Now check for the existence of each of the specified files.
                foreach ($page_dirs as $page_dir) {
                    foreach ($file_patterns as $file_pattern) {
                        $path_to_file = $page_dir . DIRECTORY_SEPARATOR . $file_pattern;
                        if (!file_exists($path_to_file) && !is_dir($path_to_file) && $path_to_file != $options['log']) {
                            error_log("$path_to_file does not exist.\n", 3, $options['log']);
                            $files_missing = true;
                        }
                    }

                    // Check for extraneous files in the page directory.
                    $page_dir_contents = scandir($page_dir);
                    // Remove . and ..
                    $page_dir_contents = array_slice($page_dir_contents, 2);
                    foreach ($page_dir_contents as $page_dir_file) {
                        if (!in_array($page_dir_file, $file_patterns)) {
                            error_log($page_dir . DIRECTORY_SEPARATOR . $page_dir_file .
                                " should not exist.\n", 3, $options['log']);
                            $extra_files_in_pages_dir = true;
                        }
                    }

                    // Check each OCR.txt file to ensure it's encoded in UTF-8.
                    $path_to_ocr_file = $page_dir . DIRECTORY_SEPARATOR . 'OCR.txt';
                    if (file_exists($path_to_ocr_file)) {
                        $ocr_content = file_get_contents($path_to_ocr_file);
                        if (!mb_check_encoding($ocr_content, 'UTF-8')) {
                            error_log("$path_to_ocr_file is not valid UTF-8\n", 3, $options['log']);
                            $bad_ocr_encoding  = true;
                        }
                    }
                }
            }
        }
        closedir($books_handle);
        clearstatcache();
    }

    if ($extra_files_in_books_dir) {
        print "** Files exist in ". $options['dir'] . " that should not be present.\n";
    }
    else {
        print "There are no unexpected files in " . $options['dir'] . ".\n";
    }

    if ($extra_files_in_book_dir) {
        print "** Files exist in one or more book-level directories that should not be present.\n";
    }
    else {
        print "There are no unexpected files in any book-level directories.\n";
    }

    if ($extra_files_in_pages_dir) {
        print "** Files exist in one or more book page directories that should not be present.\n";
    }
    else {
        print "There are no unexpected files in any book page directories.\n";
    }

    if ($files_missing) {
        print "** Some books in " . $options['dir'] . " are missing one of " .
            $options['files'] . ".\n";
    }
    else {
        print "All books in " . $options['dir'] . " have the files " .
            $options['files'] . ".\n";
    }

    if ($pages_missing) {
        print "** There is a mismatch between the number of books in " . $options['dir']
            . " and the number of book pages expected based on the CPD.XML contained in the issue level MODS XML.\n";
    } else {
        print "All of expected book pages are present.\n";
    }

    if (in_array('OCR.txt', $file_patterns)) {
        if ($bad_ocr_encoding) {
            print "** Some OCR.txt files in " . $options['dir'] . " appear not to be valid UTF-8.\n";
        }
        else {
            print "All OCR.txt files in " . $options['dir'] . " appear to be valid UTF-8.\n";
        }
    }

    print "More detail may be available in " . $options['log'] . ".\n";
}


/**
 * Determines the expected number of pages in an issue by checking the CDP data stored
 * in the issue-level MODS.xml
 */
function expectedNumPageDirFromModsXML($mods_path) {
    if (file_exists($mods_path)) {
        $xml = simplexml_load_file($mods_path);
        $resultString = $xml->extension->CONTENTdmData->dmGetCompoundObjectInfo->__toString();
        $xmlElement = simplexml_load_string($resultString);
        $pages = $xmlElement->page;
        return count($pages);
    }
}
