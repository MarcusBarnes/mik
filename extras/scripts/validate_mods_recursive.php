<?php

/**
 * Script to validate every .xml file under a directory agasint the
 * MODS 3.5 schema. Prints out paths to files that do not validate.
 * Assumes schema file is in same directory as itself.
 *
 * To validate all the .xml files in a directory and all descendent
 * directories, run:
 *   php validate_mods_recursive.php /path/to/dir
 *
 * You may want to modify $path_to_schema and $filenames_to_skip
 * to suit your own needs.
 */

// List of filenames to not validate against the MODS schema.
$filenames_to_skip = array('TECHMD');

// Relative to this script.
$path_to_schema = 'mods-3-5.xsd';

$dir = trim($argv[1]);

if (file_exists($path_to_schema)) {
	  $schema_xml = file_get_contents($path_to_schema);
}
else {
    print "Can't find MODS schema file $path_to_schema" . PHP_EOL;
    exit;
}

if (!file_exists($dir)) {
    print "Can't find source directory $dir" . PHP_EOL;
    exit;
}

$num_files_checked = 0;
$num_files_failed = 0;
$directory_iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
foreach ($directory_iterator as $filepath => $info) {
	  $filename = pathinfo($filepath, PATHINFO_FILENAME);
    if (preg_match('/\.xml/', $filepath) && !in_array($filename, $filenames_to_skip)) {
			  $num_files_checked++;
        if (!validate_mods($schema_xml, $filepath)) {
            $num_files_failed++;
        }
    }
}
print $num_files_checked . " MODS.xml files checked against the schema." . PHP_EOL;
print $num_files_failed . " files failed validation." . PHP_EOL;

function validate_mods($schema_xml, $path_to_mods) {
    $mods = new DOMDocument();
    $mods->load($path_to_mods);
    if ($mods->schemaValidateSource($schema_xml)) {
        print "MODS file $path_to_mods does not validate." . PHP_EOL;
		    return true;
    }
	  else {
		    return false;
	  }
}