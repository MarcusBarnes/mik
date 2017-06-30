<?php

namespace mik\writers;

use Monolog\Logger;

class CdmBooks extends Writer
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
     * @var object metadataparser - metadata parser object
     */
    public $metadataParser;

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

        // If OBJ_file_extension was not set in the configuration, default to tiff.
        if (!isset($this->OBJ_file_extension)) {
            $this->OBJ_file_extension = 'tiff';
        }

        $metadtaClass = 'mik\\metadataparsers\\' . $settings['METADATA_PARSER']['class'];
        $this->metadataParser = new $metadtaClass($settings);

        // Set up logger.
        $this->pathToLog = $this->settings['LOGGING']['path_to_log'];
        $this->log = new \Monolog\Logger('Writer');
        $this->logStreamHandler = new \Monolog\Handler\StreamHandler(
            $this->pathToLog,
            Logger::INFO
        );
        $this->log->pushHandler($this->logStreamHandler);
    }

    /**
     * Write folders and files.
     */
    public function writePackages($metadata, $pages, $record_key)
    {
        // Create root output folder
        $this->createOutputDirectory();

        $issueObjectPath = $this->createIssueDirectory($record_key);
        $this->writeMetadataFile($metadata, $issueObjectPath);

        $cpdData = $this->cdmNewspapersFileGetter->getCpdFile($record_key);
        $pagePtrPageTitleMap = $this->cpdPagePtrPageTitleMap($cpdData);

        // If there were no datastreams explicitly set in the configuration,
        // set flag so that all datastreams in the writer class are run.
        // $this->datareams is an empty array by default.
        $no_datastreams_setting_flag = false;
        if (count($this->datastreams) == 0) {
            $no_datastreams_setting_flag = true;
        }

        // filegetter for OBJ.tiff files for monograph pages
        $OBJ_expected = in_array('OBJ', $this->datastreams);
        if ($OBJ_expected xor $no_datastreams_setting_flag) {
             $OBJFilesArray = $this->cdmNewspapersFileGetter
                 ->getIssueLocalFilesForOBJ($record_key);
            // Array of paths to tiffs for OBJ for newspaper issue pages may not be sorted
            // on some systems.  Sort.
            sort($OBJFilesArray);
        } else {
            // No OBJ source files
            $OBJFilesArray = array();
        }

        $sub_dir_num = 0;
        foreach ($pages as $page_pointer) {
            $sub_dir_num++;

            // Create subdirectory for each page of newspaper issue
            $page_object_info = $this->fetcher->getItemInfo($page_pointer);

            //var_dump($page_object_info);

            if ($OBJ_expected xor $no_datastreams_setting_flag) {
                $filekey = $sub_dir_num - 1;
                $pathToFile = $OBJFilesArray[$filekey];

                // Infer the numbered directory name from the OBJ file name.
                $directoryNumber = $this->directoryNameFromFileName($pathToFile);
            } else {
                // Infer the numbered directory name from  $page_object_info
                $directoryNumber = $this->directoryNameFromPageObjectInfo($pagePtrPageTitleMap, $page_object_info);
            }

            // left trim leading left zero padded numbers
            $directoryNumber = ltrim($directoryNumber, "0");

            // In some cases, the page numbering may start from zero in which
            // case ltrim 0 will result in an empty string.
            if ($directoryNumber == '') {
                $directoryNumber = '0';
            }

            $page_dir = $issueObjectPath  . DIRECTORY_SEPARATOR . $directoryNumber;
            //var_dump($page_dir);
            // Create a directory for each day of the newspaper.
            if (!file_exists($page_dir)) {
                mkdir($page_dir, 0777, true);
            }

            if (isset($page_object_info['code']) && $page_object_info['code'] == '-2') {
                continue;
            }

            print "Exporting files for book " . $record_key
              . ', page ' . $directoryNumber . "\n";



            // Write out $page_object_info['full'], which we'll use as the OCR datastream.
            $ocr_output_file_path = $page_dir . DIRECTORY_SEPARATOR . 'OCR.txt';
            $OCR_expected = in_array('OCR', $this->datastreams);
            if ($OCR_expected xor $no_datastreams_setting_flag) {
                $ocr_output_file_path = $page_dir . DIRECTORY_SEPARATOR . 'OCR.txt';
                if (isset($page_object_info['full'])) {
                    $ocr = $page_object_info['full'];
                } elseif (isset($page_object_info['summar'])) {
                    $ocr = $page_object_info['summar'];
                }
                file_put_contents($ocr_output_file_path, $ocr);
            }

            // Retrieve the file associated with the child-level object. In the case of
            // the Chinese Times and some other newspapers, this is a JPEG2000 file.
            $JP2_expected = in_array('JP2', $this->datastreams);
            if ($JP2_expected xor $no_datastreams_setting_flag) {
                $jp2_content = $this->cdmNewspapersFileGetter
                    ->getChildLevelFileContent($page_pointer, $page_object_info);
                $jp2_output_file_path = $page_dir . DIRECTORY_SEPARATOR . 'JP2.jp2';
                file_put_contents($jp2_output_file_path, $jp2_content);
            }

            // @ToDo: Determine if it's better to use $image_info as a parameter
            // in getThumbnailcontent and getPreviewJPGContent - as this
            // may reduce the number of API calls by 1.
            //$image_info = $this->thumbnail->getImageScalingInfo($page_pointer);

            // Get a JPEG to use as the Islandora thubnail,
            // which should be 200 pixels high. The filename should be TN.jpg.
            // See http://www.contentdm.org/help6/custom/customize2aj.asp for CONTENTdm API docs.
            // Based on a target height of 200 pixels, get the scale value.
            $TN_expected = in_array('TN', $this->datastreams);
            if ($TN_expected xor $no_datastreams_setting_flag) {
                $thumbnail_content = $this->cdmNewspapersFileGetter
                                      ->getThumbnailcontent($page_pointer);
                $thumbnail_output_file_path = $page_dir . DIRECTORY_SEPARATOR .'TN.jpg';
                file_put_contents($thumbnail_output_file_path, $thumbnail_content);
                if ($sub_dir_num == 1) {
                    // Use the first thumbnail for the first page as thumbnail for the
                    // entire newspaper issue.
                    $book_thumbnail_path = $issueObjectPath  . DIRECTORY_SEPARATOR . 'TN.jpg';
                    copy($thumbnail_output_file_path, $book_thumbnail_path);
                }
            }

            // Get a JPEG to use as the Islandora preview image,
            //which should be 800 pixels high. The filename should be JPG.jpg.
            $JPEG_expected = in_array('JPEG', $this->datastreams);
            if ($JPEG_expected xor $no_datastreams_setting_flag) {
                $jpg_content = $this->cdmNewspapersFileGetter
                                ->getPreviewJPGContent($page_pointer);
                $jpg_output_file_path = $page_dir . DIRECTORY_SEPARATOR . 'JPEG.jpg';
                file_put_contents($jpg_output_file_path, $jpg_content);
            }

            $OBJ_expected = in_array('OBJ', $this->datastreams);
            if ($OBJ_expected xor $no_datastreams_setting_flag) {
                // OBJ from source files - either TIFF, jpeg, or jp2
                $pathToPageOK = $this->cdmNewspapersFileGetter
                   ->checkBookPageFilePath($pathToFile, $sub_dir_num);

                if ($pathToPageOK) {
                    $obj_output_file_path = $page_dir . DIRECTORY_SEPARATOR . 'OBJ.' . $this->OBJ_file_extension;
                    // assumes that the source destination is on a l
                    copy($pathToFile, $obj_output_file_path);
                }
            } elseif (!$this->skip_obj) {
                // The OBJ datastream is required:
                // see: https://github.com/Islandora/islandora_paged_content/blob/7.x/xml/islandora_pageCModel_ds_composite_model.xml
                // Only skip if the skip_obj flag is set to true in the WRITER seection of the configuration file.
                // If using tiff datastream from external source, datastreams[] = OBJ
                // in the WRITER section of the configuration file or do not include datastreams[] option to create
                // all datastreams
                // MODS, OBJ, OCR, JPEG, JP2
                if ($JP2_expected) {
                    $obj_jp2_output_file_path = $page_dir . DIRECTORY_SEPARATOR . 'OBJ.jp2';
                    copy($jp2_output_file_path, $obj_jp2_output_file_path);
                } elseif ($JPEG_expected) {
                    $obj_jpeg_output_file_path = $page_dir . DIRECTORY_SEPARATOR . 'OBJ.jpg';
                    copy($jpg_output_file_path, $obj_jpeg_output_file_path);
                } else {
                    // Create an exception since OJB are required in Islandora Book packages.
                    throw new \Exception("OBJ datastream is required - no OBJ datastream created.  Please " .
                        "check your configuration.");
                }
            }

            // Write outut page level MODS.XML
            $MODS_expected = in_array('MODS', $this->datastreams);
            if ($MODS_expected xor $no_datastreams_setting_flag) {
                /*
                if(isset($page_object_info['title'])) {
                    $page_title = $page_object_info['title'];
                } else {
                    $page_title = 'Page ' . $directoryNumber;
                }
                */
                $page_title = 'Page ' . $directoryNumber;
                $this->writePageLevelMetadaFile($page_pointer, $page_title, $page_dir);
            }
        }
    }

    /**
     * Infer the numbered name for the book page subdirectory from the OJB file name.
     */
    public function directoryNameFromFileName($pathToOBJfile)
    {

          $path_parts = pathinfo($pathToOBJfile);
          //book_name_string001
          $filename = $path_parts['filename'];
          $regex = '%[0-9]*$%';
          preg_match($regex, $filename, $matches);
          // remove possible left zero padded number.
          $pageNumber = ltrim($matches[0]);
          return $pageNumber;
    }

    /**
     * Infer the number name for the book page subdirectory from the page_object_info metadata
     */
    public function directoryNameFromPageObjectInfo($pagePtrPageTitleMap, $page_object_info)
    {

        $pageptr = $page_object_info['dmrecord'];
        $pagetitle = $pagePtrPageTitleMap[$pageptr];
        // Page 312
        $regex = '%[0-9]*$%';
        preg_match($regex, $pagetitle, $matches);
        $pageNumber = ltrim($matches[0]);
        return $pageNumber;
    }

    /**
     *  Takes book level meta
     */
    public function cpdPagePtrPageTitleMap($cpdData)
    {
        $doc = new \DomDocument('1.0');
        $doc->loadXML($cpdData);
        $pages = $doc->getElementsByTagName('page');

        $returnMap = array();
        foreach ($pages as $page_info) {
            $childNodes = $page_info->childNodes;
            unset($pageptr);
            unset($pagetitle);
            foreach ($childNodes as $child) {
                if ($child->nodeName == 'pageptr') {
                    $pageptr = $child->nodeValue;
                }
                if ($child->nodeName == 'pagetitle') {
                    $pagetitle = $child->nodeValue;
                }
            }
            if (isset($pageptr) && isset($pagetitle)) {
                $returnMap[$pageptr] = $pagetitle;
            }
        }

        return $returnMap;
    }

    /**
     * Create the output directory specified in the config file.
     */
    public function createOutputDirectory()
    {
        parent::createOutputDirectory();
    }

    public function createIssueDirectory($record_key)
    {
        $issueObjectPath = $this->outputDirectory . DIRECTORY_SEPARATOR . $record_key;

        // if the book level directory already exists, we are dealing with a possible
        // duplicate (or more) upload into CDM.  Create additional directories with
        // #record_key.\d# naming convention and log possible duplicate Cdm
        // object so that the best choice(s) for the issue are selected during QA prior
        // to batch ingest into Islandora or other systems.
        $multipleBookNumber = 0;
        while (file_exists($issueObjectPath) == true) {
            // log that the issue directory already exists and may indicate that the
            // book may already exit in the output directory or that more than one Cdm
            // pointer refers to the same newspaper issue (multiple Cdm upload for same
            // book).
            $this->log->addInfo(
                "CdmBook writer",
                array(
                    'Book already exits in output directory:' => $issueObjectPath,
                    'pointer:' => $record_key
                )
            );

            $multipleBookNumber += 1;

            $issueObjectPath = $this->outputDirectory . DIRECTORY_SEPARATOR . $record_key;
            $issueObjectPath .= "." . $multipleBookNumber;
        }

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
            $filecreationStatus = file_put_contents($path . DIRECTORY_SEPARATOR . $filename, $metadata);
            if ($filecreationStatus === false) {
                echo "There was a problem exporting the metadata to a file.\n";
            } else {
                // echo "Exporting metadata file.\n";
            }
        }
    }

    public function writePageLevelMetadaFile($page_pointer, $page_title, $page_dir)
    {
        $metadata = $this->metadataParser->createPageLevelModsXML($page_pointer, $page_title);
        //$metadata = '<mods>'. gettype($this->metadataParser) . '</mods>';
        //$metadata = '<mods></mods>';

        // Add XML decleration
        $doc = new \DomDocument('1.0');
        $doc->loadXML($metadata);
        $doc->formatOutput = true;
        $metadata = $doc->saveXML();

        $filename = $this->metadataFileName;
        if ($page_dir != '') {
            $filecreationStatus = file_put_contents($page_dir . DIRECTORY_SEPARATOR . $filename, $metadata);

            if ($filecreationStatus === false) {
                echo "There was a problem exporting the metadata to a file.\n";
                return false;
            } else {
                // echo "Exporting metadata file.\n";
                return true;
            }
        }
    }
}
