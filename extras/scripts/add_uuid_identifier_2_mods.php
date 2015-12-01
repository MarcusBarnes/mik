<?php

/**
 * Script to add to a MODS document an <identifier> element containing a UUID.
 * If there are no <identifier> elements, adds one.
 *
 * Calls the Linux shell to generate the UUID, so won't work on Windows
 * (but could be modified to do so).
 */

$mods_XML_path = trim($argv[1]);

$dom = new DOMDocument;
$dom->preserveWhiteSpace = false;
$dom->formatOutput = true;
$dom->load($mods_XML_path);

// Check to see if we already have an identifier element with type
// 'uuid' and if so, exit.
$xpath = new \DOMXPath($dom);
$existing_uuid_identifiers = $xpath->query("//mods:identifier[@type='uuid']");
if ($existing_uuid_identifiers->length > 0) {
    exit;
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

/**
 * Gets the UUID by calling 'uuidgen' via the shell.
 */
function get_uuid() {
    $uuid = `uuidgen`;
    return trim($uuid);
}
