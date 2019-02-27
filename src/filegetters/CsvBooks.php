<?php

namespace mik\filegetters;

class CsvBooks extends FileGetter
{

    /**
     * Create a new CSV Books Filegetter instance.
     *
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        parent::__construct($settings);
        $this->input_directory = $this->settings['input_directory'];
        $this->file_name_field = $this->settings['file_name_field'];
        $this->fetcher = new \mik\fetchers\Csv($settings);
        if (isset($this->settings['allowed_file_extensions_for_OBJ'])) {
            $this->allowed_file_extensions_for_OBJ = $this->settings['allowed_file_extensions_for_OBJ'];
        } else {
            $this->allowed_file_extensions_for_OBJ = array('tiff', 'tif', 'jp2');
        }
        $this->OBJFilePaths = $this->getMasterFiles($this->input_directory, $this->allowed_file_extensions_for_OBJ);
    }

    /**
     * Return a list of absolute filepaths to the pages of a book.
     *
     * @param string $record_key
     *
     * @return array
     *    An array of absolute paths to the book's page files. This list of
     *    children is different from the one produced by the CdmBook's
     *    filegetter, which is a list of object pointers.
     */
    public function getChildren($record_key)
    {
        $item_info = $this->fetcher->getItemInfo($record_key);
        $book_directory = $item_info->{$this->file_name_field};
      
        $page_paths = array();
        $book_input_path = $this->getBookSourcePath($record_key);
        foreach ($this->OBJFilePaths as $path) {
            $current_book_dirname = dirname($path);
            if ($current_book_dirname === $this->input_directory . DIRECTORY_SEPARATOR . $book_directory) {
                $page_paths[] = $path;
            }
        }
        return $page_paths;
    }

    /**
     * Recurses down a directory to find all potential page-level input files.
     *
     * @param string $inputDirectory
     *    The input directory as defined in the configuration.
     * @param array  $allowedFileTypes
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
     * Return the absolute filepath to the pages of a book.
     *
     * @param $record_key
     *
     * @return string
     *    The absolute path to the book's page files.
     */
    public function getBookSourcePath($record_key)
    {
        $item_info = $this->fetcher->getItemInfo($record_key);
        $book_directory = $item_info->{$this->file_name_field};
        $escaped_book_directory = preg_replace('/\-/', '\-', $book_directory);
        $directory_regex = preg_quote('#' . DIRECTORY_SEPARATOR . $escaped_book_directory . DIRECTORY_SEPARATOR . '#');
        foreach ($this->OBJFilePaths as $path) {
            if (preg_match($directory_regex, $path)) {
                return pathinfo($path, PATHINFO_DIRNAME);
            }
        }
    }
}
