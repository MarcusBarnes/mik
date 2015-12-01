<?php

/**
 * All post-write hook scripts get the record key as the first parameter, a comma-
 * separated list of children record keys as the second, and the path to the MIK .ini
 * file as the third.
 *
 * This is a sample script that writes some data to a file.
 */

$record_key = trim($argv[1]);
$children_record_keys_string = trim($argv[2]);
$children_record_keys = explode(',', $children_record_keys_string);
$config_path = trim($argv[3]);

$config = parse_ini_file($config_path, true);

// Write some data from the parameters to a file.
file_put_contents('/tmp/task1.txt', "Record key from task1.php: $record_key\n", FILE_APPEND);
file_put_contents('/tmp/task1.txt', "Children record key from task1.php: " . implode(',', $children_record_keys) . "\n", FILE_APPEND);
file_put_contents('/tmp/task1.txt', "Output directory from MIK config: " . $config['WRITER']['output_directory'] . "\n", FILE_APPEND);
file_put_contents('/tmp/task1.txt', "Sample post-write hook script has finished\n", FILE_APPEND);
