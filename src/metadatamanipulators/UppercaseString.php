<?php
// src/metadatamanipulators/UppercaseString.php

namespace mik\metadatamanipulators;

/**
 * UppercaseString: Changes the first character of a string to uppercase.
 */
class UppercaseString extends MetadataManipulator
{
    /**
     * Upper-case the first character of a string.
     * @param string $input A string to upper case.
     *
     * @return array The modified string, or FALSE if ucfirst fails.
     */
    public function __construct($settings, $input, $record_key)
    {
        parent::__construct($settings, [], $record_key);

        return $this->manipulate($input);
    }

    /**
     * General manipulate wrapper method.
     *
     * @param string $input string to be manipulated.
     *
     * @return string
     *     Manipulated string
     */
    public function manipulate($input)
    {
        return ucfirst($input);
    }
}
