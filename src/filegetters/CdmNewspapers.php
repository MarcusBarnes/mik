<?php

namespace mik\filegetters;

use GuzzleHttp\Client;
use mik\exceptions\MikErrorException;
use Monolog\Logger;

class CdmNewspapers extends FileGetter
{
    /**
     * @var string $inputDirectory - path to newspaper collection.
     */
    //public $inputDirectory;

    /**
     * @var array $inputDirectories - array of paths to files for newspaper collection.
     */
    public $inputDirectories;

    /**
     * @var array (dict) $OBJFilePaths - paths to OBJ files for collection
     */
    public $OBJFilePaths;

    /**
     * @var string $utilsUrl - CDM utils url.
     */
    public $utilsUrl;

    /**
     * @var string $alias - CDM alias
     */
    public $alias;

    /**
     * @var object $thumbnail - filemanipulators class for helping
     * create thumbnails from CDM
     */
    private $thumbnail;

    /**
     * Array of file extensions when searching for Master files (for OBJ datastreams).
     * @var array
     * This helps handle the situation where the same file types are given different file extensions due to OS or
     * application differences.
     * For example, tiff and tif for normal tiff files.
     */
    public $allowed_file_extensions_for_OBJ = array('tiff', 'tif');

    /**
     * Create a new CONTENTdm Fetcher Instance
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        parent::__construct($settings);
        $this->utilsUrl = $this->settings['utils_url'];
        $this->alias = $this->settings['alias'];

        if (!isset($this->settings['http_timeout'])) {
            // Seconds.
            $this->settings['http_timeout'] = 60;
        }

        // Default Mac PHP setups may use Apple's Secure Transport
        // rather than OpenSSL, causing issues with CA verification.
        // Allow configuration override of CA verification at users own risk.
        if (isset($settings['SYSTEM']['verify_ca'])) {
            if ($settings['SYSTEM']['verify_ca'] == false) {
                $this->verifyCA = false;
            }
        } else {
            $this->verifyCA = true;
        }

        if (isset($this->settings['allowed_file_extensions_for_OBJ'])) {
            $this->allowed_file_extensions_for_OBJ = $this->settings['allowed_file_extensions_for_OBJ'];
        }

        $this->inputDirectories = $this->settings['input_directories'];

        // Interate over inputDirectories to create $potentialObjFiles array.
        $potentialObjFiles = $this->getMasterFiles($this->inputDirectories, $this->allowed_file_extensions_for_OBJ);

        $this->OBJFilePaths = $this->determineObjItems($potentialObjFiles);

        // information and methods for thumbnail minipulation
        $this->thumbnail = new \mik\filemanipulators\ThumbnailFromCdm($settings);

        // Set up logger.
        $this->pathToLog = $settings['LOGGING']['path_to_log'];
        $this->log = new \Monolog\Logger('CdmNewspapers filegetter');
        $this->logStreamHandler = new \Monolog\Handler\StreamHandler(
            $this->pathToLog,
            Logger::ERROR
        );
        $this->log->pushHandler($this->logStreamHandler);
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
    public function getChildren($pointer)
    {
        $alias = $this->settings['alias'];
        $ws_url = $this->settings['ws_url'];
        $query_url = $ws_url . 'dmGetCompoundObjectInfo/' . $alias . '/' .  $pointer . '/json';

        $client = new Client();
        try {
            $response = $client->get(
                $query_url,
                ['timeout' => $this->settings['http_timeout'],
                'connect_timeout' => $this->settings['http_timeout'],
                'verify' => $this->verifyCA]
            );
            $item_structure = $response->getBody();
            $item_structure = json_decode($item_structure, true);
        } catch (RequestException $e) {
            $this->log->addError("CdmNewspapers Guzzle error", array('HTTP request error' => $e->getRequest()));
            if ($e->hasResponse()) {
                $this->log->addError("CdmNewspapers Guzzle error", array('HTTP request response' => $e->getResponse()));
            }
        }

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

        $key = DIRECTORY_SEPARATOR . $issueDate . DIRECTORY_SEPARATOR;
        return $this->OBJFilePaths[$key];
    }

    private function getMasterFiles($inputDirectories, $allowedFileTypes)
    {
        // Use a static cache to avoid building the source path list
        // multiple times.
        static $potentialObjFiles;
        if (!isset($potentialObjFiles) || $this->use_cache === false) {
            $potentialObjFiles = array();
            foreach ($inputDirectories as $inputDirectory) {
                $potentialFilesArray = array();
                $iterator = new \RecursiveDirectoryIterator($inputDirectory);
                $display = $allowedFileTypes;
                $iteratorIterator = new \RecursiveIteratorIterator($iterator);

                foreach ($iteratorIterator as $file) {
                    $file_parts = explode('.', $file);
                    if (in_array(strtolower(array_pop($file_parts)), $display)) {
                        $potentialFilesArray[] = $file->__toString();
                    }
                }

                $potentialObjFiles = array_merge($potentialObjFiles, $potentialFilesArray);
            }
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
            $specialDirectoryNameCases = array('Merged', 'JPG', 'Uncompressed TIFF');
            foreach ($arrayOfFilesToPreserve as $filepath) {
                //if $dateIdentifier is in $specialDirectoryName Cases
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

    public function getThumbnailcontent($page_pointer, $thumbnail_height = 200)
    {
        // Get a JPEG to use as the Islandora thumbnail,
        // which should be 200 pixels high. The filename should be TN.jpg.
        // See http://www.contentdm.org/help6/custom/customize2aj.asp for CONTENTdm API docs.
        // Based on a target height of 200 pixels, get the scale value.

        $image_info = $this->thumbnail->getImageScalingInfo($page_pointer);

        $scale = $thumbnail_height / $image_info['width'] * 100;
        $new_height = round($image_info['height'] * $scale / 100);
        $get_image_url_thumbnail = $this->utilsUrl . 'ajaxhelper/?CISOROOT=' .
          ltrim($this->alias, '/') . '&CISOPTR=' . $page_pointer .
          '&action=2&DMSCALE=' . $scale. '&DMWIDTH='. $thumbnail_height . 'DMHEIGHT=' . $new_height;

        try {
            $client = new Client();
            $response = $client->get(
                $get_image_url_thumbnail,
                ['timeout' => $this->settings['http_timeout'],
                'connect_timeout' => $this->settings['http_timeout'],
                'verify' => $this->verifyCA]
            );
            $thumbnail_content = $response->getBody();
        } catch (RequestException $e) {
            $this->log->addError("CdmNewspapers Guzzle error", array('HTTP request error' => $e->getRequest()));
            if ($e->hasResponse()) {
                $this->log->addError("CdmNewspapers Guzzle error", array('HTTP request response' => $e->getResponse()));
            }
        }

        return $thumbnail_content;
    }

    public function getPreviewJPGContent($page_pointer, $jpeg_height = 800)
    {
        // Get a JPEG to use as the Islandora preview image,
        // which should be 800 pixels high. The filename should be JPG.jpg.
        $image_info = $this->thumbnail->getImageScalingInfo($page_pointer);

        $scale = $jpeg_height / $image_info['width'] * 100;
        $new_height = round($image_info['height'] * $scale / 100);
        $get_image_url_jpg = $this->utilsUrl . 'ajaxhelper/?CISOROOT='
          . ltrim($this->alias, '/') . '&CISOPTR=' . $page_pointer
          . '&action=2&DMSCALE=' . $scale. '&DMWIDTH=' . $jpeg_height
          . '&DMHEIGHT=' . $new_height;

        $client = new Client();
        try {
            $response = $client->get(
                $get_image_url_jpg,
                ['timeout' => $this->settings['http_timeout'],
                'connect_timeout' => $this->settings['http_timeout'],
                'verify' => $this->verifyCA]
            );
            $jpg_content = $response->getBody();
            return $jpg_content;
        } catch (RequestException $e) {
            $this->log->addError("CdmNewspapers Guzzle error", array('HTTP request error' => $e->getRequest()));
            if ($e->hasResponse()) {
                $this->log->addError("CdmNewspapers Guzzle error", array('HTTP request response' => $e->getResponse()));
            }
        }
    }

    public function getChildLevelFileContent($page_pointer, $page_object_info)
    {
        // Retrieve the file associated with the child-level object. In the case of
        // the Chinese Times and some other newspapers, this is a JPEG2000 file.
        $get_file_url = $this->utilsUrl .'getfile/collection/'
            . $this->alias . '/id/' . $page_pointer . '/filename/'
            . $page_object_info['find'];

        $client = new Client();
        try {
            $response = $client->get(
                $get_file_url,
                ['timeout' => $this->settings['http_timeout'],
                'connect_timeout' => $this->settings['http_timeout'],
                'verify' => $this->verifyCA]
            );
            $content = $response->getBody();
            return $content;
        } catch (RequestException $e) {
            $this->log->addError("CdmNewspapers Guzzle error", array('HTTP request error' => $e->getRequest()));
            if ($e->hasResponse()) {
                $this->log->addError("CdmNewspapers Guzzle error", array('HTTP request response' => $e->getResponse()));
            }
        }
    }

    public function getPageOBJfileContent($pathToFile, $page_number)
    {
        // Check path page tiffs should be in the format yyyy-mm-dd-pp.
        // @ToDo - move this method to FileGetter parent class
        // to be extended in child classes such as CdmNewspapers

        $regex_pattern = '%[/\\\\][0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]-[0-9]*' . $page_number . '%';
        $result = preg_match($regex_pattern, $pathToFile);
        if ($result === 1) {
            // file_get_contents returns false on failure.
            $obj_content = file_get_contents($pathToFile);
        } else {
            // log
            // file_get_contents returns false on failure.
            $obj_content = false;
        }

        return $obj_content;
    }

    public function checkNewspaperPageFilePath($pathToFile, $page_number)
    {
        // Check path page tiffs should be in the format yyyy-mm-dd-pp.
        // @ToDo - move this method to FileGetter parent class
        // to be extended in child classes such as CdmNewspapers

        $regex_pattern = '%[/\\\\][0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]-[0-9]*' . $page_number . '%';
        $result = preg_match($regex_pattern, $pathToFile);
        if ($result === 1) {
            return true;
        } else {
            return false;
        }
    }

    public function getCpdFile($pointer)
    {
        $ws_url = $this->settings['ws_url'];
        $alias = $this->alias;
        $query_url = $ws_url . 'dmGetCompoundObjectInfo/' . $alias . '/' .  $pointer . '/xml';
        $cpd_content = file_get_contents($query_url);
        return $cpd_content;
    }
}
