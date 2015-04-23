<?php

namespace mik\filegetters;

class CdmNewspapers extends FileGetter
{
    /**
     * @var array $settings - configuration settings from confugration class.
     */
    public $settings;

    /**
     * @var string $inputDirectory - path to newspaper collection.
     */
    public $inputDirectory;

    /**
     * @var array (dict) $OBJFilePaths - paths to OBJ files for collection
     */
    public $OBJFilePaths;

    /**
     * Create a new CONTENTdm Fetcher Instance
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        $this->settings = $settings['FILE_GETTER'];
        $this->inputDirectory = $this->settings['input_directory'];
        $potentialObjFiles = $this
            ->getIssueMasterFiles($this->inputDirectory);
        $this->OBJFilePaths = $this->determineObjItems($potentialObjFiles);
    }
    
    /**
    * Friendly welcome
    *
    * @param string $phrase Phrase to return
    *
    * @return string Returns the phrase passed in
    */
    public function echoPhrase($phrase)
    {
        return $phrase . " (from the CdmNewspapers filegetter)\n";
    }

    /**
    * Return an array of records.
    *
    * @return array The records.
    */
    public function getRecords()
    {
        return array(1, 2, 3, 4, 5);
    }
    
    /**
     * Gets a compound item's children pointers. $alias needs to include the leading '/'.
     * @ToDo - clerify whether this method should be part of filegetters or fetchers.
     */
    public function getChildrenPointers($pointer)
    {
        $alias = $this->settings['alias'];
        $ws_url = $this->settings['ws_url'];
        $query_url = $ws_url . 'dmGetCompoundObjectInfo/' . $alias . '/' .  $pointer . '/json';
        $item_structure = file_get_contents($query_url);
        $item_structure = json_decode($item_structure, true);
        
        // @ToDo - deal with different item structures.
        if (isset($item_structure['page'])) {
            $children = $item_structure['page'];
        } else {
            return array();
        }
        $children_pointers = array();
        foreach ($children as $child) {
            $children_pointers[] = $child['pageptr'];
        }
        return $children_pointers;
    }

    public function getIssueLocalFilesForOBJ($issueDate)
    {
        // Get the paths to the master files (typically .TIFFs)
        // to use for the OBJ.tiff of each newspaper page.
        // Deal on an issue-by-issue bassis.

        
        //print_r($this->getIssueMasterFiles($inputDirectory, $issueDate));
        //return $arrayOfFilePaths;
        $key = '/' . $issueDate . '/';
        return $this->OBJFilePaths[$key];
    }

    private function getIssueMasterFiles($pathToIssue, $allowedFileTypes = array('tiff', 'tif'))
    {
        $potentialFilesArray = array();

        $iterator = new \RecursiveDirectoryIterator($pathToIssue);
        $display = $allowedFileTypes;
        $iteratorIterator = new \RecursiveIteratorIterator($iterator);

        foreach ($iteratorIterator as $file) {

            $file_parts = explode('.', $file);
            if (in_array(strtolower(array_pop($file_parts)), $display)) {
                $potentialFilesArray[] = $file->__toString();
            }
        }

        return $potentialFilesArray;
    }

    private function determineObjItems($arrayOfFilesToPreserve)
    {
        // For newspaper issues

        # This regex will look for a pattern like /yyyy-mm-dd/ in the path that
        # represents the issue date for the newspaper.
        # Assumes publication frequency of at most one issue daily.
        # One can use \d character class for digits 0-9
        $regex_pattern = '%[/\\\\][0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9][/\\\\]%';

        $dateForIdentifierArray = array();
        foreach ($arrayOfFilesToPreserve as $path) {
            //print $path . "\n";
            preg_match($regex_pattern, $path, $matches);
            if ($matches) {
                array_push($dateForIdentifierArray, $matches[0]);
            }
        }
        $dateForIdentifierArray = array_unique($dateForIdentifierArray);

        $dictOfItems = array();
        foreach ($dateForIdentifierArray as $dateIdentifier) {
            $tempItemList = array();
            //$pattern = "%" . $dateIdentifier ."%";
            #special directories in
            $specialDirectoryNameCases = array('Merged', 'JPG');
            foreach ($arrayOfFilesToPreserve as $filepath) {
                //if $dateIdentifier is in $specialDirectoryNameCases
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
