<?php

namespace mik\filegetters;

/**
 * Note: Input directory contains data for a single newspaper. The CSV file contains
 * metadata for issues. The issue data should be organized in this way:
 * The_times
 *   1910
 *     1910-01
 *       1910-01-01
 *         page_01.tif
 *         page_02.tif
 */
class CsvNewspapers extends FileGetter
{
    /**
     * @var array $settings - configuration settings from confugration class.
     */
    public $settings;

    /**
     * @var array allowed_file_extensions_for_OBJ - array of file extensions when searching for Master files (for OBJ datastreams).
     * This helps handle the situaiton where the same file types are given different file extensions due to OS or applicatoin differences.
     * For example, tiff and tif for normal tiff files.
     */
    public $allowed_file_extensions_for_OBJ = array('tiff', 'tif', 'jp2');

    /**
     * Create a new CSV Fetcher Instance
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        $this->settings = $settings['FILE_GETTER'];
        $this->input_directory = $this->settings['input_directory'];
        $this->file_name_field = $this->settings['file_name_field'];
        $this->fetcher = new \mik\fetchers\Csv($settings);

        // Interate over inputDirectories to create $potentialObjFiles array.
        $potentialObjFiles = $this->getMasterFiles($this->input_directory, $this->allowed_file_extensions_for_OBJ);
        $this->OBJFilePaths = $this->determineObjItems($potentialObjFiles);
    }

    /**
     * Issue pages are the children.
     *
     * @param $record_key
     *
     * @return array
     *    An array of absolute paths to the issue's page files. This list of
     *    children is different from the one produced by the CdmNewspapers
     *    filegetter, which is a list of object pointers.
     */
    public function getChildren($record_key)
    {
        // Get the path to the issue
        $item_info = $this->fetcher->getItemInfo($record_key);
        $issue_directory = $item_info->{$this->file_name_field};
        $escaped_issue_directory = preg_replace('/\-/', '\-', $issue_directory);
        // Get an array of all the issue page paths.
        $page_paths = array();
        $directory_regex = '#' . DIRECTORY_SEPARATOR . $escaped_issue_directory . DIRECTORY_SEPARATOR . '#';
        foreach ($this->OBJFilePaths as $paths) {
            foreach ($paths as $path) {
                if (preg_match($directory_regex, $path)) {
                    $page_paths[] = $path;
                }
            }
        }
        return $page_paths;
    }

    private function getMasterFiles($inputDirectory, $allowedFileTypes)
    {
        // Use a static cache to avoid building the source path list
        // multiple times.
        static $potentialObjFiles;
        if (!isset($potentialObjFiles)) {
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

    private function determineObjItems($arrayOfFilesToPreserve)
    {
        // For newspaper issues

        # This regex will look for a pattern like /yyyy-mm-dd/ in the path that
        # represents the issue date for the newspaper.
        # Assumes publication frequency of at most one issue daily.
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

}
