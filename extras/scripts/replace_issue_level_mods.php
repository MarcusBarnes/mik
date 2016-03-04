<?php

/**
 * Script to copy issue-level MODS.xml files from a directory containing MIK
 * newspaper output to another directory containing MIK newspaper output.
 * Essentially a tool to replace botched issue-level MODS files for a group of
 * issues with a new set of issue-specific MODS.xml files arranged in issue-level
 * directories corrsponding to the target packages.
 *
 * Makes a backup copy of the MODS.xml file before modifying it.
 *
 * To replace all bad MODS.xml files in a destination directory like this:
 *
 * /some/dest/dir
 *   1923-01-05
 *      MODS.xml
 *   1923-01-08
 *      MODS.xml
 *   1923-01-10
 *      MODS.xml
 *
 * with good files in a corresponding source directory like this:
 *
 * /a/source/dir
 *   1923-01-05
 *      MODS.xml
 *   1923-01-08
 *      MODS.xml
 *   1923-01-10
 *      MODS.xml
 *
 * run:
 *
 *  php replace_issue_level_mods.php /a/source/dir /some/dest/dir
 *
 * A backup copiy of each bad MODS.xml file is made at MODS.xml.bak. To remove these
 * backup files, run:
 *
 *  php replace_issue_level_mods.php /some/dest/dir remove_backups
 */

$source_dir = trim($argv[1]);
$dest_dir = trim($argv[2]);

if (!file_exists($source_dir)) {
    print "Can't find source directory $source_dir" . PHP_EOL;
    exit;
}

if (($argv[2] != 'remove_backups') && !file_exists($dest_dir)) {
    print "Can't find destination directory $dest_dir" . PHP_EOL;
    exit;
}

if (isset($argv[2]) && $argv[2] == 'remove_backups') {
    remove_backups(trim($argv[1]));
    exit;
}

$num_files_replaced = 0;
$directory_iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source_dir));
foreach ($directory_iterator as $filepath => $info) {
    $issue_level_mods_pattern = '#[/\\\\][0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9][/\\\\]MODS\.xml$#'; 
    if (preg_match($issue_level_mods_pattern, $filepath)) {
        $source_path_parts = explode(DIRECTORY_SEPARATOR, $filepath);
        $source_path_parts = array_slice($source_path_parts, -2, 2);
        $dest_path = $dest_dir . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $source_path_parts);
        if (file_exists($dest_path)) {
            if (make_backup($filepath, $dest_path)) {
                copy_file($filepath, $dest_path);
            }
        }
    }
}
print $num_files_replaced . " MODS.xml files replaced." . PHP_EOL;

/**
 * Makes a backup of the file to be overwritten.
 */
function make_backup($source_path, $dest_path) {
    // Make a backup copy of the MODS file.
    $dest_path = $dest_path . '.bak';
    if (!copy($source_path, $dest_path)) {
        print "Warning: Could not copy $source_path to backup copy $dest_path.bak" . PHP_EOL;
        return; 
    }
    else {
        return true;
    }
}

/**
 * Copies the MODS.xml file from the source directory to the corresponding destination directory.
 */
function copy_file($source_path, $dest_path) {
    global $num_files_replaced;
    $pathinfo = pathinfo($dest_path);
    if (!file_exists($pathinfo['dirname'])) {
        print "Warning: Destination directory " . $pathinfo['dirname'] . " does not exist" . PHP_EOL;
        return false; 
    }
    if (!copy($source_path, $dest_path)) {
        print "Warning: Could not copy $source_path to bakcup $dest_path" . PHP_EOL;
        return false; 
    }
    else {
        $num_files_replaced++;
        print "$source_path -> $dest_path" . PHP_EOL;
        return true;
    }
}

/**
 * Deletes .bak files created by this script.
 */
function remove_backups($dir) {
    $directory_iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($directory_iterator as $filepath => $info) {
        if (preg_match('/MODS\.xml.bak$/', $filepath)) {
            if (file_exists($filepath)) {
                unlink($filepath);
                print "Removing $filepath" . PHP_EOL;
            }
            else {
                print "Warning: $filepath not found" . PHP_EOL;
            }
        }
    }
}
