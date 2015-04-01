<?php
// src/metadatamanipulators/FilterDate.php

namespace mik\metadatamanipulators;

class FilterDate extends MetadataManipulator
{
    /**
     * Normalize a date.
     * @param string $input A date expressed as a string.
     * @return array The normalized date, or FALSE if preg_replace fails.
     */
    public function __construct($input)
    {
        if (preg_replace('/(\d\d)\-(\d\d)\-(\d\d\d\d)/', $input, $matches)) {
          return $matches[2] . '-' . $matches[1] . '-' . $matches[0];
        }
        else {
          // Log the failure and return $input.
        }
    }
}
