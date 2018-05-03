<?php
// src/metadatamanipulators/InsertXmlFromTemplate.php

namespace mik\metadatamanipulators;

use \Twig\Twig;
use \Monolog\Logger;

/**
 * InsertXmlFromTemplate - Generates an XML fragment from a Twig template
 * that can then be inserted into MODS.
 *
 * Applies to all MODS toolchains.
 */
class AddContentdmSourceUrl extends MetadataManipulator
{

    /**
     * Create a new metadata manipulator instance.
     */
    public function __construct($settings, $paramsArray, $record_key)
    {
        parent::__construct($settings, $paramsArray, $record_key);
        $this->record_key = $record_key;
        $this->alias = $this->settings['METADATA_PARSER']['alias'];

        // Set up logger.
        $this->pathToLog = $this->settings['LOGGING']['path_to_manipulator_log'];
        $this->log = new \Monolog\Logger('config');
        $this->logStreamHandler = new \Monolog\Handler\StreamHandler(
            $this->pathToLog,
            Logger::INFO
        );
        $this->log->pushHandler($this->logStreamHandler);

        if (count($paramsArray) == 1) {
            $this->templateDirectory = pathinfo($paramsArray[0], PATHINFO_DIRNAME);
            $this->templateFilename = pathinfo($paramsArray[0], PATHINFO_BASENAME);
        } else {
            $this->log->addInfo("AddContentdmSourceUrl", array('Wrong parameter count' => count($paramsArray)));
        }
    }

    /**
     * General manipulate wrapper method.
     *
     * @param string $input
     *    An XML snippet (not used by this manipulator).
     *
     * @return string
     *     Manipulated string, or the raw input.
     */
    public function manipulate($input)
    {
        $loader = new \Twig_Loader_Filesystem($this->templateDirectory);
        $twig = new \Twig_Environment($loader);

        $xml_from_template = $twig->render($this->templateFilename, array('alias' => $this->alias, 'pointer' => $this->record_key));
        return trim($xml_from_template);
    }
}
