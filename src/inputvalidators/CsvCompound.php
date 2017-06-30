<?php
// src/inputvalidators/CsvCompound.php

namespace mik\inputvalidators;

/**
 * MikInputValidator class for the MIK CSV compound object toolchain.
 */
class CsvCompound extends MikInputValidator
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
        $this->unwantedFiles = array(
            'Thumbs.db',
            '.Thumbs.db',
            'DS_Store',
            '.DS_Store',
        );

        // Default is to derive child sequence number by splitting filename on '_'.
        if (isset($settings['WRITER']['child_sequence_separator'])) {
            $this->child_sequence_separator = $settings['WRITER']['child_sequence_separator'];
        } else {
            $this->child_sequence_separator = '_';
        }

        // Default minimum child count is 2.
        if (isset($settings['WRITER']['min_children'])) {
            $this->min_children = $settings['WRITER']['min_children'];
        } else {
            $this->min_children = 2;
        }
    }

    /**
     * Wrapper function for validating all input packages.
     *
     * @return array
     *   A list of boolean values, one for each package.
     */
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
        $compound_directory_field = $this->fileGetter->compound_directory_field;
        foreach ($records as $record) {
            $compound_directory_value = $record->{$compound_directory_field};
            $package_path = $this->settings['FILE_GETTER']['input_directory'] .
                DIRECTORY_SEPARATOR . $compound_directory_value;
            if (!$validated = $this->validatePackage($record->key, $package_path)) {
                $validation_results[] = false;
            }
        }
        return $validation_results;
    }

    /**
     * Wrapper function for validating a single input package.
     *
     * @param $record_key string
     *   The package's record key.
     *
     * @param $package_path string
     *   The the package's input directory name (not full path).
     *
     * @return boolean
     *    True if all tests pass for the package, false if any tests failed.
     */
    public function validatePackage($record_key, $package_path)
    {
        // We don't want to revalidate the same compound object directory
        // more than once, so we keep track of ones we've validated here.
        static $package_path_validated = array();
        if (in_array($package_path, $package_path_validated)) {
            return true;
        }

        $cumulative_validation_results = array();

        // Compound directory must exist, and be a directory.
        if (file_exists($package_path) && is_dir($package_path)) {
            $cumulative_validation_results[] = true;

            $children = $this->getChildrenFiles($package_path);

            // Compound directory cannot contain Thumbs.db, etc. The CsvCompound
            // filegetter checks for the presence of these files and skips them.
            // However, if we don't check for them here, the checks against
            // $this->min_children and for valid sequence numbering will fail
            // because of the presence of Thumbs.db, etc.
            $intersection = array_intersect($this->unwantedFiles, $children);
            if (count($intersection)) {
                $this->log->addError(
                    "Input validation failed",
                    array(
                        'record ID' => $record_key,
                        'compound object directory' => $package_path,
                        'error' => 'Compound object input directory contains unwanted files'
                    )
                );
                $cumulative_validation_results[] = false;
            }

            // Compound directory must contain at least n files, where n is
            // defined in $settings['WRITER']['min_children']; (as per #291).
            if (count($children) < $this->min_children) {
                $this->log->addError(
                    "Input validation failed",
                    array(
                        'record ID' => $record_key,
                        'compound object directory' => $package_path,
                        'error' => 'Compound object input directory contains too few child files'
                    )
                );
                $cumulative_validation_results[] = false;
            }

            // Files in compound directory must be named such that their last
            // filename segment is numeric.
            if (!$this->checkChildSequenceNumbers($children)) {
                $this->log->addError(
                    "Input validation failed",
                    array(
                        'record ID' => $record_key,
                        'compound object directory' => $package_path,
                        'error' => 'Some files in the compound object directory have invalid sequence numbers'
                    )
                );
                $cumulative_validation_results[] = false;
            }
        } else {
            $this->log->addError(
                "Input validation failed",
                array(
                    'record ID' => $record_key,
                    'compound object directory' => $package_path,
                    'error' => 'Compound object directory not found'
                )
            );
            $cumulative_validation_results[] = false;
        }

        $package_path_validated[] = $package_path;

        if (in_array(false, $cumulative_validation_results)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Gets the filenames of the child files in the compound-level directory.
     *
     * @param $dir string
     *    The full path to the compound-level directory.
     *
     * @return array
     *    A list of all the child file names.
     */
    private function getChildrenFiles($dir)
    {
        $files = $this->readDir($dir);
        foreach ($files as &$file) {
            $file = basename($file);
        }
        return $files;
    }

    /**
     * Validates the filenames of the children in the compound-level directory.
     *
     * @param $files array
     *    A list of all the child file names.
     *
     * @return boolean
     *    True if all files have valid sequence numbers (e.g., _\d$ at the end), false if not.
     */
    private function checkChildSequenceNumbers($files)
    {
        $valid = true;
        foreach ($files as $file) {
            if (!in_array($file, $this->unwantedFiles)) {
                $pathinfo = pathinfo($file);
                $filename = $pathinfo['filename'];
                if (!preg_match('/' . $this->child_sequence_separator . '\d+$/', $filename)) {
                    $valid = false;
                }
            }
        }
        return $valid;
    }
}
