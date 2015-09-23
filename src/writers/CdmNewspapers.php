<?php

namespace mik\writers;

class CdmNewspapers extends Writer
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
     * @var string $metadataFileName - file name for metadata file to be written.
     */
    public $metadataFileName;

    /**
     * Create a new newspaper writer Instance
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        parent::__construct($settings);
        $this->fetcher = new \mik\fetchers\Cdm($settings);
        $this->alias = $settings['WRITER']['alias'];
        // @Todo load manipulators someway based on those to be listed in config.
        $this->thumbnail = new \mik\filemanipulators\ThumbnailFromCdm($settings);
        $fileGetterClass = 'mik\\filegetters\\' . $settings['FILE_GETTER']['class'];
        $this->cdmNewspapersFileGetter = new $fileGetterClass($settings);
        if (isset($this->settings['metadata_filename'])) {
          	$this->metadataFileName = $this->settings['metadata_filename'];
        } else {
           $this->metadataFileName = 'MODS.xml';
        } 
    }

    /**
     * Write folders and files.
     */
    public function writePackages($metadata, $pages, $record_key)
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
            $page_dir = $issueObjectPath  . DIRECTORY_SEPARATOR . $page_number;
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
            $ocr_output_file_path = $page_dir . DIRECTORY_SEPARATOR . 'OCR.txt';
            file_put_contents($ocr_output_file_path, $page_object_info['full']);

            // Retrieve the file associated with the child-level object. In the case of
            // the Chinese Times and some other newspapers, this is a JPEG2000 file.
            $jp2_content = $this->cdmNewspapersFileGetter
                ->getChildLevelFileContent($page_pointer, $page_object_info);
            $jp2_output_file_path = $page_dir . DIRECTORY_SEPARATOR . 'JP2.jp2';
            file_put_contents($jp2_output_file_path, $jp2_content);

            // @ToDo: Determine if it's better to use $image_info as a parameter
            // in getThumbnailcontent and getPreviewJPGContent - as this
            // may reduce the number of API calls by 1.
            //$image_info = $this->thumbnail->getImageScalingInfo($page_pointer);

            // Get a JPEG to use as the Islandora thubnail,
            // which should be 200 pixels high. The filename should be TN.jpg.
            // See http://www.contentdm.org/help6/custom/customize2aj.asp for CONTENTdm API docs.
            // Based on a target height of 200 pixels, get the scale value.
            $thumbnail_content = $this->cdmNewspapersFileGetter
                                      ->getThumbnailcontent($page_pointer);
            $thumbnail_output_file_path = $page_dir . DIRECTORY_SEPARATOR .'TN.jpg';
            file_put_contents($thumbnail_output_file_path, $thumbnail_content);

            // Get a JPEG to use as the Islandora preview image,
            //which should be 800 pixels high. The filename should be JPG.jpg.
            $jpg_content = $this->cdmNewspapersFileGetter
                                ->getPreviewJPGContent($page_pointer);
            $jpg_output_file_path = $page_dir . DIRECTORY_SEPARATOR . 'JPEG.jpg';
            file_put_contents($jpg_output_file_path, $jpg_content);


            // For each page, we need two files that can't be downloaded from CONTENTdm: PDF.pdf and MODS.xml.

            // Create OBJ file for page.
            $filekey = $page_number - 1;
            $pathToFile = $OBJFilesArray[$filekey];
            $obj_content = $this->cdmNewspapersFileGetter
                 ->getPageOBJfileContent($pathToFile, $page_number);
            if ($obj_content != false) {
                $obj_output_file_path = $page_dir . DIRECTORY_SEPARATOR . 'OBJ.tiff';
                file_put_contents($obj_output_file_path, $obj_content);
            } else {
                // log
                echo "obj_content = false : $pathToFile\n";
            }
        }
        if ($this->cdmNewspapersFileGetter->cpd_filename) {
            $cpd_content = $this->cdmNewspapersFileGetter->getCpdFile($record_key);
            $cpd_output_file_path = $issueObjectPath  . DIRECTORY_SEPARATOR .
                $this->cdmNewspapersFileGetter->cpd_filename . '.xml';
            file_put_contents($cpd_output_file_path, $cpd_content);
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
        // There may be more than one 'dateIssued' node
        // use the one with keyDate and metadataminipulator to
        // manipulate date to yyyy-mm-dd format.
        if ($nodes->length == 1) {
            $this->issueDate = trim($nodes->item(0)->nodeValue);
        } else {
            foreach ($nodes as $item) {
                foreach ($item->attributes as $attribute) {
                    if ($attribute->name == 'keyDate' &&  $attribute->nodeValue == 'yes') {
                        $this->issueDate = $item->nodeValue;
                    }
                }
            }
            
        }
        
        //$doc->formatOutput = true;

        //$modsxml = $doc->saveXML();
        // Create a directory for each day of the newspaper by getting
        // the date value from the issue's metadata.
        //$issue_object_info = get_item_info($results_record['collection'], $results_record['pointer']);
        
        $issueObjectPath = $this->outputDirectory . DIRECTORY_SEPARATOR . $this->issueDate;
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

        $filename = $this->metadataFileName;
        if ($path !='') {
            $filecreationStatus = file_put_contents($path .'/' . $filename, $metadata);
            if ($filecreationStatus === false) {
                echo "There was a problem exporting the metadata to a file.\n";
            } else {
                // echo "Exporting metadata file.\n";
            }
        }
    }
    
}
