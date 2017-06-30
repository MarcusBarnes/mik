<?php
// src/metadatamanipulators/SimpleReplace.php

namespace mik\metadatamanipulators;

use \Monolog\Logger;

/**
 * SimpleReplace - Allows the application of preg_replace() to
 * MODS elements. Unlike other metadata manipulators, this one does
 * not manipulate the DOM, it applies regexes to the input XML as a
 * string. Applies to all MODS toolchains.
 *
 * Signature in .ini files is:
 *   metadatamanipulators[] = "SimpleReplace|/pattern/|replacement text"
 * e.g.,
 *   metadatamanipulators[] = "SimpleReplace|/<title>Page/|<title>Part"
 */
class SimpleReplace extends MetadataManipulator
{

    /**
     * Create a new metadata manipulator instance.
     */
    public function __construct($settings, $paramsArray, $record_key)
    {
        parent::__construct($settings, $paramsArray, $record_key);
        $this->record_key = $record_key;

        // Set up logger.
        $this->pathToLog = $this->settings['LOGGING']['path_to_manipulator_log'];
        $this->log = new \Monolog\Logger('config');
        $this->logStreamHandler = new \Monolog\Handler\StreamHandler(
            $this->pathToLog,
            Logger::INFO
        );
        $this->log->pushHandler($this->logStreamHandler);

        if (count($paramsArray) == 2) {
            // A PHP preg_ pattern to match the original value on.
            $this->replacePattern = $paramsArray[0];
            // A replacement string.
            $this->replacementText = $paramsArray[1];
        } elseif (count($paramsArray) == 1) {
            // A PHP preg_ pattern to match the original value on.
            $this->replacePattern = $paramsArray[0];
            // Empty replacement.
            $this->replacementText = '';
        } else {
            $this->log->addInfo("SimpleReplace", array('Wrong parameter count' => count($paramsArray)));
        }
    }

    /**
     * General manipulate wrapper method.
     *
     * @param string $input An XML snippet to be manipulated.
     *
     * @return string
     *     Manipulated string
     */
    public function manipulate($input)
    {
        if (preg_match($this->replacePattern, $input)) {
            $modified_input = preg_replace($this->replacePattern, $this->replacementText, $input);
            $this->log->addInfo("SimpleReplace", array('Record key' => $this->record_key,
                'Input' => $input,
                'Modified version' => $modified_input
                ));
            return $modified_input;
        } else {
            // If current fragment does not match our regex, return it.
            return $input;
        }
    }
}
