<?php

/**
 * Script to add to a MODS document an <identifier> element containing a UUID.
 * Makes a backup copy of the MODS.xml file before modifying it.
 *
 * You should run this from the main MIK directory so the autoloading works.
 */
 
require 'vendor/autoload.php';
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;

$dir = trim($argv[1]);

$directory_iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
foreach ($directory_iterator as $filepath => $info) {
    if (preg_match('/MODS\.xml$/', $filepath)) {
        add_uuid($filepath);
    }
}

/**
 * Adds an <identifier> element containing a UUID to the file
 * identifed in $mods_XML_path.
 */
function add_uuid($mods_XML_path) {
	  print "Processing $mods_XML_path...";
    $dom = new DOMDocument;
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->load($mods_XML_path);

    // Check to see if we already have an identifier element with type
    // 'uuid' and if so, exit.
    $xpath = new \DOMXPath($dom);
    $existing_uuid_identifiers = $xpath->query("//mods:identifier[@type='uuid']");
    if ($existing_uuid_identifiers->length > 0) {
			  print "already has one or more identifiers containing a UUID." . PHP_EOL;
        return;
    }

    // If there were none, continue.

    // Make a backup copy of the MODS file.
    if (!copy($mods_XML_path, $mods_XML_path . '.bak')) {
        print "Could not copy $mods_XML_path to $mods_XML_path.bak\n";
        exit(1);
    }

    // Build the <identifier> element we are adding.
    $type = $dom->createAttribute('type');
    $type->value = 'uuid';
    $uuid_identifier = $dom->createElement('identifier', get_uuid());
    $uuid_identifier->appendChild($type);

    // Figure out where to add it. If one ore more <identifier> elements
    // exist in the document, add the new one before the first existing one.
    $identifiers = $dom->getElementsByTagName('identifier');
    if ($identifiers->length) {
        $dom->documentElement->insertBefore($uuid_identifier, $identifiers->item(0));
    }
    else {
        // If none exist, append it to the end of the document.
        $dom->documentElement->appendChild($uuid_identifier);
    }

    $mods_xml = $dom->saveXML();
    file_put_contents($mods_XML_path, $mods_xml);
		print "identifier containing a UUID added. Original file is at $mods_XML_path . '.bak'." . PHP_EOL;
}

/**
 * Generates a v4 UUID.
 */
function get_uuid() {
    $uuid4 = Uuid::uuid4();
    $uuid4_string = $uuid4->toString();
    return $uuid4_string;
}
