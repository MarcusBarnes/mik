<?php

/**
 * All post-write hook scripts get the record key as the first parameter, a JSON representation
 * of a list of children record keys as the second, and a JSON representation of the MIK .ini
 * file as the second.
 */

$record_key = trim($argv[1]);
$children_record_keys_json = trim($argv[2]);
$config_json = trim($argv[3]);
$config = json_decode($config_json, true);
$children_record_keys = json_decode($children_record_keys_json);

file_put_contents('/tmp/task1.txt', "Record key from task1.php: $record_key\n", FILE_APPEND);
file_put_contents('/tmp/task1.txt', "Children record key from task1.php: " . implode(',', $children_record_keys) . "\n", FILE_APPEND);
file_put_contents('/tmp/task1.txt', "Output directory from MIK config: " . $config['WRITER']['output_directory'] . "\n", FILE_APPEND);
file_put_contents('/tmp/task1.txt', "task1.php has finished\n", FILE_APPEND);
