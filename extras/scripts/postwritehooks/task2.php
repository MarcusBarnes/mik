<?php

/**
 * All post-write hook scripts get the record key as the first parameter and
 * a JSON representation of the MIK .ini file as the second.
 */

$record_key = trim($argv[1]);
$config_json = trim($argv[2]);
$config = json_decode($config_json, true);

$path_to_file = $config['WRITER']['output_directory'] . DIRECTORY_SEPARATOR . $record_key . '.xml';

file_put_contents('/tmp/task2.txt', $argv[0] . " processing file $path_to_file\n", FILE_APPEND);
if (file_exists($path_to_file)) {
    $file_size = filesize($path_to_file);
    file_put_contents('/tmp/task2.txt', "File size: $file_size"  . "\n", FILE_APPEND);
}
else {
    file_put_contents('/tmp/task2.txt', "File not found"  . "\n", FILE_APPEND);
}
