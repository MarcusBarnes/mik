<?php

namespace mik\filegetters;

class CsvNewspapers extends FileGetter
{
    /**
     * @var array allowed_file_extensions_for_OBJ - array of file extensions when searching
     * for master files (for OBJ datastreams).
     */
    public $allowed_file_extensions_for_OBJ = array('tiff', 'tif', 'jp2');

    /**
     * Create a new CSV Fetcher instance.
     *
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        parent::__construct($settings);
        $this->input_directory = $this->settings['input_directory'];
        $this->file_name_field = $this->settings['file_name_field'];
        $this->fetcher = new \mik\fetchers\Csv($settings);

        // Interate over inputDirectories to create $potentialObjFiles array.
        $potentialObjFiles = $this->getMasterFiles($this->input_directory, $this->allowed_file_extensions_for_OBJ);
        $this->OBJFilePaths = $this->determineObjItems($potentialObjFiles);
    }

    /**
     * Return a list of absolute filepaths to the pages of an issue.
     *
     * @param string $record_key
     *
     * @return array
     *    An array of absolute paths to the issue's page files. This list of
     *    children is different from the one produced by the CdmNewspapers
     *    filegetter, which is a list of object pointers.
     */
    public function getChildren($record_key)
    {
        $page_paths = array();
        $issue_input_path = $this->getIssueSourcePath($record_key);
        foreach ($this->OBJFilePaths as $paths) {
            foreach ($paths as $path) {
                // If there's a match, we expect it to start at position 0.
                if (strpos($path, $issue_input_path) === 0) {
                    $page_paths[] = $path;
                }
            }
        }
        return $page_paths;
    }

    /**
     * Recurses down a directory to find all potential page-level input files.
     *
     * @param string $inputDirectory
     *    The input directory as defined in the configuration.
     * @param array $allowedFileTypes
     *    The list of file types (e.g. extensions) to look for.
     *
     * @return array
     *    A list of absolute paths to all the found files.
     */
    private function getMasterFiles($inputDirectory, $allowedFileTypes)
    {
        if ($inputDirectory == '') {
            return array();
        }

        // Use a static cache to avoid building the source path list
        // multiple times.
        static $potentialObjFiles;
        if (!isset($potentialObjFiles) || $this->use_cache === false) {
            $potentialObjFiles = array();
            $potentialFilesArray = array();
            $iterator = new \RecursiveDirectoryIterator($inputDirectory);
            $iteratorIterator = new \RecursiveIteratorIterator($iterator);

            foreach ($iteratorIterator as $file) {
                $file_parts = explode('.', $file);
                if (in_array(strtolower(array_pop($file_parts)), $allowedFileTypes)) {
                    $potentialFilesArray[] = $file->__toString();
                }
            }

            $potentialObjFiles = array_merge($potentialObjFiles, $potentialFilesArray);
            $potentialObjFiles = array_unique($potentialObjFiles);
        }

        return $potentialObjFiles;
    }

    /**
     * Filters out paths to files that do not have a yyyy-mm-dd date in their parent directories.
     *
     * @param array $arrayOfFilesToPreserve
     *    The list of file types (e.g. extensions) to look for.
     *
     * @return array
     *    An associative array with keys containing dates in yyyy-mm-dd
     *    format and values containing paths to files with the key date.
     */
    private function determineObjItems($arrayOfFilesToPreserve)
    {
        // This regex will look for a pattern like /yyyy-mm-dd/ in the path that
        // represents the issue date for the newspaper. Assumes publication frequency
        // of at most one issue daily.
        $regex_pattern = '%[/\\\\][0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9][/\\\\]%';

        $dateForIdentifierArray = array();
        foreach ($arrayOfFilesToPreserve as $path) {
            preg_match($regex_pattern, $path, $matches);
            if ($matches) {
                array_push($dateForIdentifierArray, $matches[0]);
            }
        }
        $dateForIdentifierArray = array_unique($dateForIdentifierArray);

        $dictOfItems = array();
        foreach ($dateForIdentifierArray as $dateIdentifier) {
            $tempItemList = array();
            foreach ($arrayOfFilesToPreserve as $filepath) {
                if (stristr($filepath, $dateIdentifier)) {
                    array_push($tempItemList, $filepath);
                }
            }

            if (count($tempItemList) > 0) {
                $dictOfItems[$dateIdentifier] = $tempItemList;
            }
        }
        return $dictOfItems;
    }

    /**
     * Return a list of absolute filepaths to the pages of an issue.
     *
     * @param $record_key
     *
     * @return string
     *    The absolute paths to the issue's page files.
     */
    public function getIssueSourcePath($record_key)
    {
        // Get the path to the issue.
        $item_info = $this->fetcher->getItemInfo($record_key);
        $issue_directory = $item_info->{$this->file_name_field};
        $escaped_issue_directory = preg_replace('/\-/', '\-', $issue_directory);
        $directory_regex = '#' . DIRECTORY_SEPARATOR . $escaped_issue_directory . DIRECTORY_SEPARATOR . '#';
        foreach ($this->OBJFilePaths as $paths) {
            foreach ($paths as $path) {
                if (preg_match($directory_regex, $path)) {
                    return pathinfo($path, PATHINFO_DIRNAME);
                }
            }
        }
    }
}
