<?php
// src/inputvalidators/CsvSingleFile.php

namespace mik\inputvalidators;

/**
 * MikInputValidator class for the MIK CSV single file toolchain.
 */
class CsvSingleFile extends MikInputValidator
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

        $validation_results = array();
        $records = $this->fetcher->getRecords();
        $file_name_field = $this->fileGetter->file_name_field;
        foreach ($records as $record) {
            $file_name_value = $record->{$file_name_field};
            $package_path = $this->settings['FILE_GETTER']['input_directory'] .
                DIRECTORY_SEPARATOR . $file_name_value;
            if (!$validated = $this->validatePackage($record->key, $package_path)) {
                $validation_results[] = false;
            }
        }
        return $validation_results;
    }

    public function validatePackage($record_key, $package_path)
    {
        // The only check we make is that the file named in the CSV
        // record exists.
        if (file_exists($package_path) && !is_dir($package_path)) {
            return true;
        } else {
            $this->log->addError(
                "Input validation failed",
                array(
                    'record ID' => $record_key,
                    'package path' => $package_path,
                    'error' => 'File not found'
                )
            );
            return false;
        }
    }
}
