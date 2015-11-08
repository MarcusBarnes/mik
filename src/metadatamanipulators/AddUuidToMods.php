<?php
// src/metadatamanipulators/AddUuidToMods.php

namespace mik\metadatamanipulators;
use \Monolog\Logger;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;

/**
 * AddUuidToMods - Adds a UUID v4 (random) to a MODS <identifier> element.
 *
 * Note that this manipulator doesn't add the <identifier> fragment, it
 * only populates it with a UUID. The mappings file must contain a
 * row that adds the following element to your MODS:
 * null5,<identifier type="uuid"></identifier>.
 *
 * This metadata manipulator takes no configuration parameters.
 */
class AddUuidToMods extends MetadataManipulator
{
    /**
     * Create a new metadata manipulator Instance.
     */
    public function __construct($settings = null, $paramsArray, $record_key)
    {
        parent::__construct($settings, $paramsArray, $record_key);

        // Set up logger.
        $this->pathToLog = $this->settings['LOGGING']['path_to_manipulator_log'];
        $this->log = new \Monolog\Logger('config');
        $this->logStreamHandler = new \Monolog\Handler\StreamHandler($this->pathToLog,
            Logger::INFO);
        $this->log->pushHandler($this->logStreamHandler);
    }

    /**
     * General manipulate wrapper method.
     *
     *  @param string $input The XML fragment to be manipulated. We are only
     *     interested in the <identifier type="uuid"> fragment added in the
     *     MIK mappings file.
     *
     * @return string
     *     One of the manipulated XML fragment, the original input XML if the
     *     input is not the fragment we are interested in, or an empty string,
     *     which as the effect of removing the empty <identifier type="uuid">
     *     fragement from our MODS (if there was an error, for example, we don't
     *     want empty identifier elements in our MODS documents).
     */
    public function manipulate($input)
    {
        $dom = new \DomDocument();
        $dom->loadxml($input, LIBXML_NSCLEAN);

        // Test to see if the current fragment is <identifier type="uuid">.
        $xpath = new \DOMXPath($dom);
        $uuid_identifiers = $xpath->query("//identifier[@type='uuid'");

        // There should only be one <identifier type="uuid"> fragment in the
        // incoming XML. If there is 0 or more than 1, return the original.
        if ($uuid_identifiers->length === 1) {
            $uuid_identifier = $uuid_identifiers->item(0);
            try {
                $uuid4 = Uuid::uuid4();
                $uuid4_string = $uuid4->toString();
            } catch (UnsatisfiedDependencyException $e) {
                // @todo: Log error and return $input.
            }
            $uuid_identifier->nodeValue = $uuid4_string;

            return $dom->saveXML($dom->documentElement);
        }
        else {
            // If current fragment is not <identifier type="uuid">,
            // return it unmodified.
            return $input;
        }
    }
}