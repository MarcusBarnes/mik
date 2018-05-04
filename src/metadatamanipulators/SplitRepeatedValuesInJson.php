<?php
// src/metadatamanipulators/SplitRepeatedValuesInJson.php

/**
 * Example metadata manipulator class that demonstrates manipulating
 * JSON metadata.
 *
 * Applies to the demonstration CsvToJson toolchain only. Not intended for production.
 *
 */

namespace mik\metadatamanipulators;

use \Monolog\Logger;

/**
 * SplitRepeatedValuesInJson - Splits values separated by a delimter and
 * returns the values as an array.
 *
 * Applies to the demonstration CsvToJson toolchain only. Not intended for production.
 */
class SplitRepeatedValuesInJson extends MetadataManipulator
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
            $this->field_name = $paramsArray[0];
            $this->delimiter = $paramsArray[1];
        } else {
            $this->log->addInfo("SplitRepeatedValuesInJson", array('Wrong parameter count' => count($paramsArray)));
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
        if (preg_match('/' . $this->delimiter . '/', $input)) {
            $return_array = explode($this->delimiter, $input);
            $this->logSplit($input, $return_array);
            foreach ($return_array as &$value) {
                $value = trim($value);
            }
            return $return_array;
        } else {
            return $input;
        }
    }

    /**
     * Write a successful split operation to the manipulator log.
     *
     * @param string
     *     The value of the source metadata field.
     * @param array
     *     The value after it has been manipulated (split).
     */
    public function logSplit($source_value, $output)
    {
        $this->log->addInfo(
            "SplitRepeatedValuesInJson",
            array(
               'Record key' => $this->record_key,
               'Source field name' => $this->field_name,
               'Source value' => $source_value,
               'Output' => $output,
            )
        );
    }
}
