<?php

/**
 * Script that uses ImageMagick's convert command to convert
 * JP2 files from CONTENTdm to a copy usable in Islandora.
 * Fixes .jp2 files under any directory structure.
 *
 * Requres ImageMagick's convert utility.
 *
 * Usage: php fixjp2s.php /path/to/islandora/ingest/packages [remove_backups]
 *
 * The script creates a backup copy of the .jp2 file before running
 * 'convert' on it. Once the script has completed, run it again with
 * the 'remove_backups' argument to delete the backup files.
 */

$dir = trim($argv[1]);

if (!file_exists($dir)) {
    print "Can't find source directory $dir, exiting." . PHP_EOL;
    exit;
}

if (isset($argv[2]) && trim($argv[2] == 'remove_backups')) {
    remove_backups($dir);
    exit;
}

/**
 * Main script logic.
 */

print "Fixing JP2 files, please be patient..." . PHP_EOL;
$directory_iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
foreach ($directory_iterator as $filepath => $info) {
    $filename = pathinfo($filepath, PATHINFO_FILENAME);
    if (preg_match('/\.jp2/', $filepath)) {
        fix_jp2($filepath);
    }
}

function fix_jp2($path_to_jp2) {
    // Make a backup copy of the JP2 file.
    if (!copy($path_to_jp2, $path_to_jp2 . '.bak')) {
        print "Could not copy $path_to_jp2 to $path_to_jp2.bak\n";
        exit(1);
    }
    exec("convert $path_to_jp2.bak $path_to_jp2", $output, $return);
    if (!$return) {
        print "$path_to_jp2 converted" . PHP_EOL;
    }
    else {
        print "Problem converting $path_to_jp2 (return code $return)" . PHP_EOL;
    }
}

/**
 * Deletes .bak files created by this script.
 */
function remove_backups($dir) {
    $directory_iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($directory_iterator as $filepath => $info) {
        if (preg_match('/\.jp2.bak$/', $filepath)) {
            if (file_exists($filepath)) {
                unlink($filepath);
                print "$filepath removed" . PHP_EOL;
            }
            else {
                print "$filepath not found" . PHP_EOL;
            }
        }
    }
}
