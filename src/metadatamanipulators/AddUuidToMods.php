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
 * Also, if an <identifier> elememt with type 'uuid' already exists,
 * this manipulator adds an additional one, since we are processing
 * indiviual <identifer> elments, not the entire document.
 *
 * This metadata manipulator takes no configuration parameters.
 */
class AddUuidToMods extends MetadataManipulator
{
    /**
     * Create a new metadata manipulator Instance.
     */
    public function __construct($settings, $paramsArray, $record_key)
    {
        parent::__construct($settings, $paramsArray, $record_key);

        // Set up logger.
        $this->pathToLog = $this->settings['LOGGING']['path_to_manipulator_log'];
        $this->log = new \Monolog\Logger('config');
        $this->logStreamHandler = new \Monolog\Handler\StreamHandler(
            $this->pathToLog,
            Logger::INFO
        );
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
     *     input is not the fragment we are interested in.
     */
    public function manipulate($input)
    {
        if (!strlen($input)) {
            return $input;
        }

        $dom = new \DomDocument();
        $dom->loadxml($input, LIBXML_NSCLEAN);

        // Test to see if the current fragment is <identifier type="uuid">.
        $xpath = new \DOMXPath($dom);
        $uuid_identifiers = $xpath->query("//identifier[@type='uuid']");
        // There should only be one <identifier type="uuid"/> fragment in the
        // incoming XML, defined in the mappings file. If there is 0, return
        // the original.
        if ($uuid_identifiers->length === 1) {
            $uuid_identifier = $uuid_identifiers->item(0);
            // If our incoming fragment is already a valid UUID v4, return it as
            // is. Note that if a identifier with type "uuid" already exists, this
            // manipulator will add a new one, since we are processing the MODS
            // on an element by element basis, not the entire MODS document.
            if (strlen($uuid_identifier->nodeValue) &&
                preg_match(
                    '/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i',
                    $uuid_identifier->nodeValue
                )) {
                $this->log->addError(
                    "AddUuidToMods",
                    array('UUID already present' => $uuid_identifier->nodeValue)
                );
                return $input;
            } // If our incoming fragment is the template element from the mappings file,
            // populate it and return it.
            else {
                try {
                    $uuid4 = Uuid::uuid4();
                    $uuid4_string = $uuid4->toString();
                } catch (UnsatisfiedDependencyException $e) {
                    // Log error and return $input.
                    $this->log->addError(
                        "AddUuidToMods",
                        array('UUID generation error' => $e->getMessage())
                    );
                }
                $uuid_identifier->nodeValue = $uuid4_string;
                return $dom->saveXML($dom->documentElement);
            }
        } else {
            // If current fragment is not <identifier type="uuid">,
            // with or without a valid UUID v4 as a value, return it unmodified.
            return $input;
        }
    }
}
