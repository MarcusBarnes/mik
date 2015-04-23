<?php

namespace mik\writers;

class Newspapers extends Writer
{
    /**
     * @var array $settings - configuration settings from confugration class.
     */
    public $settings;
    
    /**
     * @var object $fetcher - fetcher class for item info methods.
     */
    private $fetcher;
    
    /**
     * @var object $thumbnail - filemanipulators class for helping
     * create thumbnails from CDM
     */
    private $thumbnail;
    
    /**
     * @var object cdmNewspapersFileGetter - filegetter class for 
     * getting files related to CDM Newspaper issues.
     */
    private $cdmNewspapersFileGetter;
    
    /**
     *  @var $issueDate - newspaper issue date.
     */
    public $issueDate = '0000-00-00';

    /**
     * @var $alias - collection alias
     */
    public $alias;

    /**
     * Create a new newspaper writer Instance
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        parent::__construct($settings);
        $this->fetcher = new \mik\fetchers\Cdm($settings);
        $this->alias = $settings['WRITER']['alias'];
        $this->thumbnail = new \mik\filemanipulators\ThumbnailFromCdm($settings);
        $this->cdmNewspapersFileGetter = new \mik\filegetters\CdmNewspapers($settings);
    }

    /**
     * Write folders and files.
     */
    public function writePackages($metadata, $pages)
    {
        // Create root output folder
        $this->createOutputDirectory();
        $issueObjectPath = $this->createIssueDirectory($metadata);
        $this->writeMetadataFile($metadata, $issueObjectPath);
        
        // filegetter for OBJ.tiff files for newspaper issue pages
        $OBJFilesArray = $this->cdmNewspapersFileGetter
                 ->getIssueLocalFilesForOBJ($this->issueDate);

        $page_number = 0;
        foreach ($pages as $page_pointer) {
            $page_number++;

            // Create subdirectory for each page of newspaper issue
            $page_object_info = $this->fetcher->getItemInfo($page_pointer);
            $page_dir = $issueObjectPath  . '/' . $page_number;
            // Create a directory for each day of the newspaper.
            if (!file_exists($page_dir)) {
                mkdir($page_dir, 0777, true);
            }

            if (isset($page_object_info['code']) && $page_object_info['code'] == '-2') {
                continue;
            }

            print "Exporting files for issue " . $this->issueDate
              . ', page ' . $page_number . "\n";

            // Write out $page_object_info['full'], which we'll use as the OCR datastream.
            $ocr_output_file_path = $page_dir . '/OCR.txt';
            file_put_contents($ocr_output_file_path, $page_object_info['full']);

            // Retrieve the file associated with the child-level object. In the case of
            // the Chinese Times and some other newspapers, this is a JPEG2000 file.
            $get_file_url = 'http://content.lib.sfu.ca/utils/getfile/collection/'
              . $this->alias . '/id/' . $page_pointer . '/filename/' . $page_object_info['find'];
            $jp2_content = file_get_contents($get_file_url);
            $jp2_output_file_path = $page_dir . '/JP2.jp2';
            file_put_contents($jp2_output_file_path, $jp2_content);

            //$image_info = get_image_scaling_info($results_record['collection'], $page_pointer);
            $image_info = $this->thumbnail->getImageScalingInfo($page_pointer);

            // Get a JPEG to use as the Islandora thubnail,
            // which should be 200 pixels high. The filename should be TN.jpg.
            // See http://www.contentdm.org/help6/custom/customize2aj.asp for CONTENTdm API docs.
            // Based on a target height of 200 pixels, get the scale value.
            $thumbnail_height = 200;
            $scale = $thumbnail_height / $image_info['width'] * 100;
            $new_height = round($image_info['height'] * $scale / 100);
            $get_image_url_thumbnail = 'http://content.lib.sfu.ca/utils/ajaxhelper/?CISOROOT=' .
              ltrim($this->alias, '/') . '&CISOPTR=' . $page_pointer .
              '&action=2&DMSCALE=' . $scale. '&DMWIDTH='. $thumbnail_height . 'DMHEIGHT=' . $new_height;
            $thumbnail_content = file_get_contents($get_image_url_thumbnail);
            $thumbnail_output_file_path = $page_dir . '/TN.jpg';
            file_put_contents($thumbnail_output_file_path, $thumbnail_content);

            // Get a JPEG to use as the Islandora preview image,
            //which should be 800 pixels high. The filename should be JPG.jpg.
            $jpeg_height = 800;
            $scale = $jpeg_height / $image_info['width'] * 100;
            $new_height = round($image_info['height'] * $scale / 100);
            $get_image_url_jpg = 'http://content.lib.sfu.ca/utils/ajaxhelper/?CISOROOT=' .
              ltrim($this->alias, '/') . '&CISOPTR=' . $page_pointer .
              '&action=2&DMSCALE=' . $scale. '&DMWIDTH=' . $jpeg_height . '&DMHEIGHT=' . $new_height;
            $jpg_content = file_get_contents($get_image_url_jpg);
            $jpg_output_file_path = $page_dir . '/JPEG.jpg';
            file_put_contents($jpg_output_file_path, $jpg_content);

            // For each page, we need two files that can't be downloaded from CONTENTdm: PDF.pdf and MODS.xml.

            // Create OBJ file for page.
            $filekey = $page_number - 1;
            $pathToFile = $OBJFilesArray[$filekey];
            // Check path page tiffs should be in the format yyyy-mm-dd-
            $regex_pattern = '%[/\\\\][0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]-[0-9]*' . $page_number . '%';
            $result = preg_match($regex_pattern, $pathToFile);
            if ($result === 1) {
                $obj_content = file_get_contents($pathToFile);
                $obj_output_file_path = $page_dir . '/OBJ.tiff';
                file_put_contents($obj_output_file_path, $obj_content);
            } else {
                // log
            }
        }
    }

    /**
     * Create the output directory specified in the config file.
     */
    public function createOutputDirectory()
    {
        parent::createOutputDirectory();
    }

    public function createIssueDirectory($metadata)
    {
        //value of dateIssued isuse is the the title for the directory
        
        $doc = new \DomDocument('1.0');
        $doc->loadXML($metadata);
        $nodes = $doc->getElementsByTagName('dateIssued');
        if ($nodes->length == 1) {
            $this->issueDate = trim($nodes->item(0)->nodeValue);
        } else {
          // log exception - unable to determine issue date.
        }
        
        //$doc->formatOutput = true;

        //$modsxml = $doc->saveXML();
        // Create a directory for each day of the newspaper by getting
        // the date value from the issue's metadata.
        //$issue_object_info = get_item_info($results_record['collection'], $results_record['pointer']);
        
        $issueObjectPath = $this->outputDirectory . $this->issueDate;
        if (!file_exists($issueObjectPath)) {
            mkdir($issueObjectPath);
            // return issue_object_path for use when writing files.
            return $issueObjectPath;
        }
    }

    public function writeMetadataFile($metadata, $path)
    {
        // Add XML decleration
        $doc = new \DomDocument('1.0');
        $doc->loadXML($metadata);
        $doc->formatOutput = true;
        $metadata = $doc->saveXML();

        parent::writeMetadataFile($metadata, $path);
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
        return $phrase . " (from the newspaper writer)\n";
    }
}
