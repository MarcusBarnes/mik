<?php

/**
 * Script to change the hostname in the URL contained in the <identifier> element
 * with a 'displayLabel' attribute with value 'Migrated from'.
 * SFU-specific fix for https://github.com/MarcusBarnes/mik/issues/176.
 * Makes a backup copy of the MODS.xml file before modifying it.
 *
 * To modify all MODS.xml in an input diretory that have identifier elements like this:
 *   <identifier type="uri" invalid="yes" displayLabel="Migrated From">http://wrong.server.com/cdm/ref/collection/CT_1930-34/id/19487</identifier>
 * to have identifier elements like this, with a new hostname:
 *   <identifier type="uri" invalid="yes" displayLabel="Migrated From">http://correct.host.net/cdm/ref/collection/CT_1930-34/id/19487</identifier>
 * run:
 *    php change_migrated_from_url.php /path/to/directory/containing/packages
 *
 * A backup copiy of each modified MODS.xml file is made at MODS.xml.bak. To remove these
 * backup files, run:
 *    php change_migrated_from_url.php /path/to/directory/containing/packages remove_backups
 */

/**
  * Change these two variables.
*/
$unwanted_host = 'content.lib.sfu.ca';
$wanted_host = '142.58.129.180';

/**
  * You should not need to change anything below this comment.
  */
$dir = trim($argv[1]);

if (isset($argv[2]) && $argv[2] == 'remove_backups') {
    remove_backups($dir);
    exit;
}

$directory_iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
foreach ($directory_iterator as $filepath => $info) {
    if (preg_match('/MODS\.xml$/', $filepath)) {
        update_migrated_from_identifier($filepath);
    }
}

/**
 * Updates the <identifier> element and makes a backup copy
 * of the modified MODS.xml file.
 */
function update_migrated_from_identifier($mods_XML_path) {
    global $unwanted_host;
    global $wanted_host;
    print "Processing $mods_XML_path...";
    $dom = new DOMDocument;
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->load($mods_XML_path);

    // Check to see if we already have an identifier element with @displayLabel
    // 'Migrated from'.
    $xpath = new \DOMXPath($dom);
    $existing_migrated_from_identifiers = $xpath->query("//mods:identifier[@displayLabel='Migrated From']");
    if ($existing_migrated_from_identifiers->length < 1) {
        print "no 'Migrated From' identifier found." . PHP_EOL;
        return;
    }

    if ($existing_migrated_from_identifiers->length > 1) {
        print "multiple 'Migrated From' identifiers found." . PHP_EOL;
        return;
    }

    // If there is one (and at this point there should only be one),
    // update its value to use the new hostname.
    if (preg_match("#$unwanted_host#", $existing_migrated_from_identifiers->item(0)->nodeValue)) {
        $updated_url = preg_replace("#$unwanted_host#", $wanted_host,
            $existing_migrated_from_identifiers->item(0)->nodeValue);
        $existing_migrated_from_identifiers->item(0)->nodeValue = $updated_url;
        print "'migrated from' identifier updated. ";
    }
    else {
        print "'migrated from' identifier not updated." . PHP_EOL;
        return;
    }

    // Make a backup copy of the MODS file.
    if (!copy($mods_XML_path, $mods_XML_path . '.bak')) {
        print "Could not copy $mods_XML_path to $mods_XML_path.bak\n";
        exit(1);
    }
    else {
        $mods_xml = $dom->saveXML();
        file_put_contents($mods_XML_path, $mods_xml);
        print "Original file is at $mods_XML_path.bak." . PHP_EOL;
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
                print "$filepath not found" . PHP_EOL;
            }
        }
    }
}
