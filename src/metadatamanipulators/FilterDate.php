<?php
// src/metadatamanipulators/FilterDate.php

namespace mik\metadatamanipulators;

/**
 * FilterDate - Normalize an input date.
 */
class FilterDate extends MetadataManipulator
{
    /**
     * @var array $settings - configuration settings.
     */
    public $settings;
    
    
    /**
     * Normalize a date.
     *
     * @param string $input A date expressed as a string.
     *
     * @return string The normalized date, or FALSE if preg_replace fails.
     */
    public function __construct($settings = null, $input)
    {
        $this->settings = $settings;

        return $this->manipulate($input);
    }

    /**
     * General manipulate wrapper method.
     *
     * @param string $input A string, typically an XML snippet to be manipulated.
     *
     * @return string
     *     Manipulated string
     */
     public function manipulate($input)
     {
        if (preg_replace('/(\d\d)\-(\d\d)\-(\d\d\d\d)/', $input, $matches)) {
            return $matches[2] . '-' . $matches[1] . '-' . $matches[0];
        } else {
            // Log the failure and return $input.
        }
     }
}
