<?php

/**
 * Script to add to a MODS document an <identifier> element containing a UUID.
 * If there are no <identifier> elements, adds one.
 *
 * Calls the Linux shell to generate the UUID, so won't work on Windows.
 */

$mods_XML_path = trim($argv[1]);

// Make a backup copy of the MODS file.
rename($mods_XML_path, $mods_XML_path . '.bak');

$dom = new DOMDocument;
$dom->preserveWhiteSpace = false;
$dom->formatOutput = true;
$dom->load($mods_XML_path . '.bak');

$identifiers = $dom->getElementsByTagName('identifier');

// Build the <identifier> element we are adding.
$type = $dom->createAttribute('type');
$type->value = 'uuid';
$uuid_identifier = $dom->createElement('identifier', get_uuid());
$uuid_identifier->appendChild($type);

// Figure out where to add it. If one ore more <identifier> elements
// exist in the document, add the new one before the first existing one.
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
