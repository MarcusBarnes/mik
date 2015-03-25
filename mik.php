<?php
// mik.php
/**
 * Main Move to Islandora Kit script.
 **/

// Use composer to load vendor and project classes.
require 'vendor/autoload.php';


use mik\Config;

$mikConfig = new Config();

echo $mikConfig->echoPhrase("Welcome the Move to Islandora Kit project.\n");