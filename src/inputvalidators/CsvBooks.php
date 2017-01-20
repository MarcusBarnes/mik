<?php
// src/inputvalidators/CsvBooks.php

namespace mik\inputvalidators;

/**
 * MikInputValidator class for the MIK CSV single file toolchain.
 */
class CsvBooks extends MikInputValidator
{
    /**
     * Create a new MikInputValidator instance.
     *
     * @param array $settings
     *   Associative array containing this toolchain's config settings.
     */
    public function __construct($settings)
    {
        parent::__construct($settings);
        $this->fetcher = new \mik\fetchers\Csv($settings);
    }

    public function validateAll()
    {
        if (!$this->validateInput) {
            return;
        }
        if ($this->validateInputType == 'realtime') {
            return;
        }

        // Placeholder for now, so PHPUnit tests will run.
        $validation_results = array(true);

        if ($this->validateInputType == 'strict' && in_array(false, $validation_results)) {
            print "Input validation (strict mode) failed; details in " . $this->pathToLog . "\n";
            exit(1);
        }
    }

    public function validatePackage($record_key, $package_path)
    {
        if (!$this->validateInput) {
            return;
        }

        // Placeholder for now, so PHPUnit tests will run.
        return true;
    }
}
