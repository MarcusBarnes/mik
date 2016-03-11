<?php

/**
 * Script to validate every .xml file under a directory against the
 * MODS 3.5 schema, or a individual file. Prints out paths to files
 * that do not validate.
 *
 * If MODS schema file does not exist, attempts to download it from
 * Library of Congress.
 *
 * To validate all the .xml files in a directory and all descendent
 * directories, run:
 *   php validate_mods_xml.php /path/to/dir
 *
 * To validate a specific file, run:
 *   php validate_mods_xml.php /path/to/file.xml
 *
 * You may want to modify $path_to_schema and $filenames_to_skip
 * to suit your own needs.
 */

// List of filenames to not validate against the MODS schema.
$filenames_to_skip = array('TECHMD');

// Only used if schema does not exist at $path_to_schema.
$schema_url = 'http://www.loc.gov/standards/mods/v3/mods-3-5.xsd';
// Relative to this script.
$path_to_schema = 'mods-3-5-local.xsd';

$input_path = trim($argv[1]);
$dir = trim($argv[1]);

if (!file_exists($path_to_schema)) {
    if (download_schema($path_to_schema, $schema_url)) {
        print "Schema file successfully downloaded." . PHP_EOL;
    }
}

// If the path is to a file, not a directory, just validate the file.
if (file_exists($input_path) && is_file($input_path)) {
    if (validate_mods($path_to_schema, $input_path)) {
        print "OK, $input_path validates." . PHP_EOL;
    }
    else {
        print "$input_path does not validate." . PHP_EOL;
    }
    // We're done.
    exit;
}  

// If the path is to a directory, continue.
$dir = $input_path;
if (!file_exists($dir)) {
    print "Can't find source directory $dir, exiting." . PHP_EOL;
    exit;
}

/**
 * Main script logic.
 */

print "Validating, please be patient..." . PHP_EOL;
$num_files_checked = 0;
$num_files_failed = 0;
$directory_iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
foreach ($directory_iterator as $filepath => $info) {
    $filename = pathinfo($filepath, PATHINFO_FILENAME);
    if (preg_match('/\.xml/', $filepath) && !in_array($filename, $filenames_to_skip)) {
        $num_files_checked++;
        if (!validate_mods($path_to_schema, $filepath)) {
            $num_files_failed++;
        }
    }
}
print $num_files_checked . " MODS.xml files checked against the schema." . PHP_EOL;
print $num_files_failed . " files failed validation." . PHP_EOL;

/**
 * Validate the MODS file against the schema.
 *
 * @param string $path_to_schema
 *   The path to the schema file.
 * @param string $path_to_mods
 *   The path to the MODS file to validate.
 *
 * @return boolean
 *   True on successful validation, false on failure.
 */
function validate_mods($path_to_schema, $path_to_mods) {
    static $schema_xml = null;
    if ($schema_xml == null) {
        $schema_xml = file_get_contents($path_to_schema);
    }
    $mods = new DOMDocument();
    $mods->load($path_to_mods);
    if (@$mods->schemaValidateSource($schema_xml)) {
        return true;
    }
    else {
        print "MODS file $path_to_mods does not validate." . PHP_EOL;
        return false;
    }
}

/**
 * Download the MODS schema file from Library of Congress.
 *
 * @param string $path_to_schema
 *   The path to the schema file.
 * @param string $schema_url
 *   The URL to the schema file to download.
 *
 * @return boolean
 *   True on successful download and saving of the schema file.
 */
function download_schema($path_to_schema, $schema_url) {
    print "Can't find MODS schema file $path_to_schema, attempting to download it from $schema_url..." . PHP_EOL;
    if ($schema_contents = @file_get_contents($schema_url)) {
        if (!file_put_contents('mods-3-5.xsd', $schema_contents)) {
            print "Retrieved MODS schema file $schema_url but cannot write it to $path_to_schema, exiting." . PHP_EOL;
            exit;
        }
    }
    else {
        print "Can't retrieve schema from $schema_url, exiting. You may want to check your network connection." . PHP_EOL;
        exit;
    }
    return true;
}
