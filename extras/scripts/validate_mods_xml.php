<?php
// Provide path to MODS XML file via command line or
// via GET request.
if (PHP_SAPI === 'cli') {
    $modsXMLPath = $argv[1];
} else {
    $modsXMLPath = $_GET['modsXMLPath'];
}

// For nicer error messages see Mark A.'s note on 
// http://php.net/manual/en/domdocument.schemavalidate.php
$xml = new DOMDocument;
$xml->Load($modsXMLPath);
if ($xml->schemaValidate('mods-3-5.xsd')) {
    echo "This document is valid MODS.\n";
}
