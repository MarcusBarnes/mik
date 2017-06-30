<?php
// src/inputvalidators/CsvNewspapers.php

namespace mik\inputvalidators;

/**
 * MikInputValidator class for the MIK CSV newspapers toolchain.
 */
class CsvNewspapers extends MikInputValidator
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

        // Default is to use - as the sequence separator in the page filename.
        // The separator is used here in a regex, so we escape it.
        if (isset($settings['WRITER']['page_sequence_separator'])) {
            $this->page_sequence_separator = $settings['WRITER']['page_sequence_separator'];
        } else {
            $this->page_sequence_separator = '-';
        }
        $this->page_sequence_separator = preg_quote($this->page_sequence_separator);

        $this->ocr_extension = '.txt';
        // Default is to not log the absence of page-level OCR files.
        if (isset($settings['WRITER']['log_missing_ocr_files'])) {
            $this->log_missing_ocr_files= $settings['WRITER']['log_missing_ocr_files'];
        } else {
            $this->log_missing_ocr_files = false;
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
        $file_name_field = $this->fileGetter->file_name_field;
        foreach ($records as $record) {
            $file_name_value = $record->{$file_name_field};
            // For this validator, we pass in the issue-level directory specified
            // in the input CSV file into validatePackage(), not the full path.
            if (!$validated = $this->validatePackage($record->key, $file_name_value)) {
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
        $issue_directory = $package_path;
        $cumulative_validation_results = array();

        // The issue directory must be named using the yyyy-mm-dd pattern.
        if (!preg_match('/^\d\d\d\d\-\d\d\-\d\d$/', $issue_directory)) {
            $this->log->addError(
                "Input validation failed",
                array(
                    'record ID' => $record_key,
                    'issue directory' => $issue_directory,
                    'error' => 'Issue directory name is not in yyyy-mm-dd format'
                )
            );
            $cumulative_validation_results[] = false;
        }

        //  Find a path that ends in the issue directory name.
        if ($issue_directory_full_path = $this->getIssueDirPath($issue_directory)) {
            if (!file_exists($issue_directory_full_path)) {
                $this->log->addError(
                    "Input validation failed",
                    array(
                        'record ID' => $record_key,
                        'issue directory' => $issue_directory,
                        'error' => 'Issue directory does not exist'
                    )
                );
                $cumulative_validation_results[] = false;
            }
            // The directory must contain at least one page file.
            $pages = $this->getPageFiles($issue_directory_full_path);
            if (count($pages) === 0) {
                $this->log->addError(
                    "Input validation failed",
                    array(
                        'record ID' => $record_key,
                        'issue directory' => $issue_directory,
                        'error' => 'Issue directory contains no page files'
                    )
                );
                $cumulative_validation_results[] = false;
            }
            if (!$this->checkOcrFiles($issue_directory_full_path, $pages)) {
                $this->log->addError(
                    "Input validation failed",
                    array(
                        'record ID' => $record_key,
                        'issue directory' => $issue_directory,
                        'error' => 'Issue directory is missing one or more OCR files'
                    )
                );
                $cumulative_validation_results[] = false;
            }
            // The page filenames must end in '-\d+'.
            if (!$this->checkPageSequenceNumbers($pages)) {
                $this->log->addError(
                    "Input validation failed",
                    array(
                        'record ID' => $record_key,
                        'issue directory' => $issue_directory,
                        'error' => 'Some pages in issue directory have invalid sequence numbers'
                    )
                );
                $cumulative_validation_results[] = false;
            }
        } else {
            $this->log->addError(
                "Input validation failed",
                array(
                    'record ID' => $record_key,
                    'issue directory' => $issue_directory,
                    'error' => 'Issue directory not found in list of possible input directories'
                )
            );
            $cumulative_validation_results[] = false;
        }

        if (in_array(false, $cumulative_validation_results)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Recurses down the input directory to find all child directories.
     *
     * @return array
     */
    private function getIssueDirectories()
    {
        static $issue_directories;
        if (!isset($issue_directories)) {
            $issue_directories = array();
            $input_directory = $this->settings['FILE_GETTER']['input_directory'];
            $iterator = new \RecursiveDirectoryIterator($input_directory);
            $iteratorIterator = new \RecursiveIteratorIterator($iterator);
            foreach ($iteratorIterator as $file) {
                if ($file->isDir()) {
                    $path = $file->getPath();
                    if (!in_array($path, $issue_directories)) {
                        $issue_directories[] = $path;
                    }
                }
            }
        }
        return $issue_directories;
    }


    /**
     * Attempts to find an input directory matching the issue-level
     * directory specified in the input file.
     *
     * @param $issue_directory string
     *    The issue-level directory name.
     *
     * @return $path string|boolean
     *    The full directory path, if found; false if not.
     */
    private function getIssueDirPath($issue_directory)
    {
        $this->issue_directories = $this->getIssueDirectories();
        foreach ($this->issue_directories as $path) {
            //  Find the first path that ends in the issue directory name.
            if (preg_match('#.*' . $issue_directory . '$#', $path)) {
                return $path;
            }
        }
        return false;
    }


    /**
     * Gets the filenames of the pages in the issue-level directory.
     *
     * @param $dir string
     *    The full path to the issue-level directory.
     *
     * @return array
     *    A list of all the page file names. Files must have one of
     *    following extensions: tif, tiff, jp2.
     */
    private function getPageFiles($dir)
    {
        $page_files = array();
        $files = $this->readDir($dir);
        foreach ($files as $file) {
            $pathinfo = pathinfo($file);
            $page_file = $pathinfo['basename'];
            $ext = $pathinfo['extension'];
            if (in_array($ext, array('tif','tiff', 'jp2'))) {
                $page_files[] = $page_file;
            }
        }
        return $page_files;
    }

    /**
     * Validates the filenames of the pages in the issue-level directory.
     *
     * @param $files array
     *    A list of all the page file names.
     *
     * @return boolean
     *    True if all files have valid sequence numbers (-\d$ at the end), false if not.
     */
    private function checkPageSequenceNumbers($files)
    {
        $valid = true;
        foreach ($files as $file) {
            $pathinfo = pathinfo($file);
            $filename = $pathinfo['filename'];
            if (!preg_match('/' . $this->page_sequence_separator . '\d+$/', $filename)) {
                $valid = false;
            }
        }
        return $valid;
    }

    /**
     * Checks for the existence of page-level OCR files.
     *
     * @param $issue_directory_path string
     *    The absolute path to the issue-level directory.
     * @param $files array
     *    A list of all the page file names in the directory.
     *
     * @return boolean
     *    True if all image files have corresponding OCR files.
     */
    private function checkOcrFiles($issue_directory_path, $files)
    {
        $valid = true;
        if (!$this->log_missing_ocr_files) {
            return $valid;
        }
        foreach ($files as $file) {
            $pathinfo = pathinfo($file);
            $filename = $pathinfo['filename'];
            $path_to_ocr_file = realpath($issue_directory_path) . DIRECTORY_SEPARATOR .
                $filename . $this->ocr_extension;
            if (!file_exists($path_to_ocr_file)) {
                $valid = false;
            }
        }
        return $valid;
    }
}
