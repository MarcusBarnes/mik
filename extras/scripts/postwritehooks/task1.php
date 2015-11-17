<?php

/**
 * All post-write hook scripts get the record key as the first parameter and
 * a JSON representation of the MIK .ini file as the second.
 */

$record_key = trim($argv[1]);
$config_json = trim($argv[2]);
$config = json_decode($config_json, true);

file_put_contents('/tmp/task1.txt', "Record key from task1.php: $record_key\n", FILE_APPEND);
file_put_contents('/tmp/task1.txt', "Output directory from MIK config: " . $config['WRITER']['output_directory'] . "\n", FILE_APPEND);
file_put_contents('/tmp/task1.txt', $argv[0] . " has finished"  . "\n", FILE_APPEND);
