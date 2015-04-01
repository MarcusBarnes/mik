<?php
// src/metadatamanipulators/UppercaseString.php

namespace mik\metadatamanipulators;

class UppercaseString extends MetadataManipulator
{
    /**
     * Upper-case the first character of a string.
     * @param string $input A string to upper case.
     * @return array The modified string, or FALSE if ucfirst fails.
     */
    public function __construct($input)
    {
        return ucfirst($input);
    }
}
