<?php
// src/metadatamanipulators/SimpleReplaceTemplated.php

namespace mik\metadatamanipulators;

use \Monolog\Logger;
use Ramsey\Uuid\Uuid;

/**
 * AddUuidToTemplated - Adds a UUID to the output of the Templated metadata parser,
 * similar to the AddUuidToMods.php metadata manipulator.
 *
 * Signature in .ini files is:
 *
 *   metadatamanipulators[] = "AddUuidToTemplated|/tmp/twigtemplates/mytemplate.xml"
 *
 * The template must contain the variable 'UUID', e.g. <identifier>{{ UUID }}</identifier>.
 */
class AddUuidToTemplated extends MetadataManipulator
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

        if (count($paramsArray) == 1) {
            $this->templatePath = $paramsArray[0];
            $this->templateDirectory = pathinfo($paramsArray[0], PATHINFO_DIRNAME);
            $this->templateFilename = pathinfo($paramsArray[0], PATHINFO_BASENAME);
        } else {
            $this->log->addInfo("AddUuidToTemplated", array('Wrong parameter count' => count($paramsArray)));
        }
    }

    /**
     * General manipulate wrapper method.
     *
     * @param string $input An XML file to be manipulated.
     *
     * @return string
     *     Manipulated XML.
     */
    public function manipulate($input)
    {
        if (file_exists($this->templatePath)) {
            $uuid4 = Uuid::uuid4();
            $uuid_string = $uuid4->toString();

            $loader = new \Twig_Loader_Filesystem($this->templateDirectory);
            $twig = new \Twig_Environment($loader);
            $xml_from_template = $twig->render($this->templateFilename, array('UUID' => $uuid_string));

            // Convert $input into DOM, add child from template, reserialize DOM as XML and return it.
            $dom = new \DOMDocument;
            $dom->preserveWhiteSpace = true;
            $dom->formatOutput = true;
            $dom->loadXML($input);
            $frag = $dom->createDocumentFragment();
            $frag->appendXML($xml_from_template);
            $dom->documentElement->appendChild($frag);
            $this->log->addInfo("AddUuidToTemplated", array('Record key' => $this->record_key,
                'Template applied' => $this->templatePath,
                'UUID added' => $uuid_string
            ));
            return $dom->saveXML();
        } else {
            $this->log->addWarning("AddUuidToTemplated", array('Record key' => $this->record_key,
                'Template not found' => $this->templatePath
            ));
        }
    }
}
