<?php

/**
 * Script that traverses a directory and deletes unwanted files.
 *
 * Usage: php remove_files.php -d=/target/directory -l=/path/to/log/file
 *
 * Options: -d  The path, either full or relative to this script, of the
 *              directory to traverse down in search of the files to delete.
 *          -l  The path, either full or relative to this script, of the log
 *              file where entries for deletion, either successful or not,
 *              are written. If the -l option is not present, messages will
 *              be printed to STDOUT.
 */

// All files with names in this list will be deleted.
$files_to_remove = array(
    '.Thumbs.db',
    '.DS_Store',
);

$options = getopt("d:l:");
$dir = $options['d'];
if (array_key_exists('l', $options)) {
    $log_path = $options['l'];
    if (!is_writable(dirname($log_path))) {
        exit("Exiting: Log path $log_path is not writable.\n");
    }
    if (file_exists($log_path) && !is_writable(dirname($log_path))) {
        exit("Exiting: Log file $log_path exists but is not writable.\n");
    }
    if (!file_exists($log_path)) {
        exit("Exiting: Log file $log_path does not exist.\n");
    }
}

if (count($options) === 0) {
    exit("Exiting: No options specified\n");
}
if (!file_exists($dir)) {
    exit("Exiting: $dir does not exist\n");
}
if (!is_dir($dir)) {
    exit("Exiting: $dir is not a directory\n");
}

$directory_iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
foreach ($directory_iterator as $filepath => $info) {
    $filename = basename($filepath);
    if (is_file($filepath) && in_array($filename, $files_to_remove)) {
        if (unlink($filepath)) {
            if (array_key_exists('l', $options)) {
                error_log("Deleting $filepath\n", 3, $log_path);
            }
            else {
                print "Deleting $filepath\n";
            }
        }
        else {
            $unlink_error = error_get_last();
            if (array_key_exists('l', $options)) {
                error_log("Could not delete $filepath: " . $unlink_error['message'] . "\n", 3, $log_path);
            }
            else {
                print "Could not delete $filepath: " . $unlink_error['message'] . "\n";
            }
        }
    }
}
