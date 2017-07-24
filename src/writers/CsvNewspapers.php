<?php

namespace mik\writers;

use Monolog\Logger;

class CsvNewspapers extends Writer
{
    /**
     * @var array $settings - settings from the confugration class.
     */
    public $settings;

    /**
     * @var object $fetcher - fetcher class for item info methods.
     */
    private $fetcher;

    /**
     * @var object - filegetter class for CSV newspaper issues.
     */
    private $fileGetter;

    /**
     * Create a new newspaper writer instance.
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        parent::__construct($settings);
        $fileGetterClass = 'mik\\filegetters\\' . $settings['FILE_GETTER']['class'];
        $this->fileGetter = new $fileGetterClass($settings);
        $fetcherClass = 'mik\\fetchers\\' . $settings['FETCHER']['class'];
        $this->fetcher = new $fetcherClass($settings);
        $this->output_directory = $settings['WRITER']['output_directory'];
        $this->metadata_filename = $settings['WRITER']['metadata_filename'];
        // Default is to generate page-level MODS.xml files.
        if (isset($settings['WRITER']['generate_page_modsxml'])) {
            $this->generate_page_modsxml = $settings['WRITER']['generate_page_modsxml'];
        } else {
            $this->generate_page_modsxml = true;
        }
        // Default is to use - as the sequence separator in the page filename.
        if (isset($settings['WRITER']['page_sequence_separator'])) {
            $this->page_sequence_separator = $settings['WRITER']['page_sequence_separator'];
        } else {
            $this->page_sequence_separator = '-';
        }

        // Set up logger.
        $this->pathToLog = $this->settings['LOGGING']['path_to_log'];
        $this->log = new \Monolog\Logger('Writer');
        $this->logStreamHandler = new \Monolog\Handler\StreamHandler(
            $this->pathToLog,
            Logger::INFO
        );
        $this->log->pushHandler($this->logStreamHandler);

        $this->ocr_extension = '.txt';
        // Default is to not log the absence of page-level OCR files.
        if (isset($settings['WRITER']['log_missing_ocr_files'])) {
            $this->log_missing_ocr_files= $settings['WRITER']['log_missing_ocr_files'];
        } else {
            $this->log_missing_ocr_files = false;
        }
    }

    /**
     * Write folders and files.
     *
     * @param $metadata
     * @param $pages
     * @param $record_id
     */
    public function writePackages($metadata, $pages, $record_id)
    {
        // If there were no datastreams explicitly set in the configuration,
        // set flag so that all datastreams in the writer class are run.
        // $this->datastreams is an empty array by default.
        $no_datastreams_setting_flag = false;
        if (count($this->datastreams) == 0) {
            $no_datastreams_setting_flag = true;
        }
        $file_name_field = $this->fileGetter->file_name_field;
        $record = $this->fetcher->getItemInfo($record_id);
        if ($this->inputValidator->validateInputType == 'realtime' && $this->inputValidator->validateInput) {
            if (!$this->inputValidator->validatePackage($record_id, $record->{$file_name_field})) {
                $this->problemLog->addError($record_id);
                return;
            }
        }

        // Create an issue-level subdirectory in the output directory.
        $issue_level_output_dir = $this->output_directory . DIRECTORY_SEPARATOR . $record_id;
        if (!file_exists($issue_level_output_dir)) {
            mkdir($issue_level_output_dir);
        }

        // Report a missing input dir only when the issue level input dir is missing
        // and all datastreams are expected, or when both MODS and OBJ are explicit.
        // But don't report a missing input dir when MODS is the only datastream and
        // the input directory setting is empty.
        $issue_level_input_dir = $this->fileGetter->getIssueSourcePath($record_id);
        if (!file_exists($issue_level_input_dir)) {
            if ($this->settings['FILE_GETTER']['input_directory'] !== '' &&
                ($this->datastreams != array('MODS') xor $no_datastreams_setting_flag)) {
                if ($no_datastreams_setting_flag) {
                    $this->log->addWarning(
                        "CSV Newspapers warning",
                        array('Issue-level input directory does not exist' => $issue_level_input_dir)
                    );
                    return;
                }
            }
        }

        $MODS_expected = in_array('MODS', $this->datastreams);
        if ($MODS_expected xor $no_datastreams_setting_flag) {
            $metadata_file_path = $issue_level_output_dir . DIRECTORY_SEPARATOR .
                $this->metadata_filename;
            $this->writeMetadataFile($metadata, $metadata_file_path);
        }

        // @todo: Add error handling on mkdir and copy.
        foreach ($pages as $page_path) {
            // Get the sequence number from the last segment of the filename.
            $pathinfo = pathinfo($page_path);
            $filename_segments = explode($this->page_sequence_separator, $pathinfo['filename']);
            $page_number = ltrim(end($filename_segments), '0');
            $page_level_output_dir = $issue_level_output_dir . DIRECTORY_SEPARATOR . $page_number;
            mkdir($page_level_output_dir);

            if ($this->datastreams != array('MODS')) {
                $OBJ_expected = in_array('OBJ', $this->datastreams);
                if ($OBJ_expected xor $no_datastreams_setting_flag) {
                    $extension = $pathinfo['extension'];
                    $page_output_path = $page_level_output_dir . DIRECTORY_SEPARATOR .
                        'OBJ.' . $extension;
                    copy($page_path, $page_output_path);
                }
            }

            // If the datastreams lis only 'MODS' we're generating metadata only.
            if ($this->datastreams != array('MODS')) {
                $OCR_expected = in_array('OCR', $this->datastreams);
                if ($OCR_expected xor $no_datastreams_setting_flag) {
                    $ocr_input_path = $pathinfo['dirname'] . DIRECTORY_SEPARATOR .
                        $pathinfo['filename'] . $this->ocr_extension;
                    $ocr_output_path = $page_level_output_dir . DIRECTORY_SEPARATOR .
                        'OCR' . $this->ocr_extension;
                    if (file_exists($ocr_input_path)) {
                        copy($ocr_input_path, $ocr_output_path);
                    } else {
                        if ($this->log_missing_ocr_files) {
                            $this->log->addWarning(
                                "CSV Newspapers warning",
                                array('Page-level OCR file does not exist' => $ocr_input_path)
                            );
                        }
                    }
                }
            }

            if ($MODS_expected xor $no_datastreams_setting_flag) {
                if ($this->generate_page_modsxml) {
                    $this->writePageMetadataFile($metadata, $page_number, $page_level_output_dir);
                }
            }
        }
    }

    /**
     * Writes out the issue-level MODS.xml file.
     *
     * @param $metadata
     *    The MODS XML document produced by the metadta parser.
     * @param $path
     *    The path to write the XML file to.
     */
    public function writeMetadataFile($metadata, $path)
    {
        // Add XML decleration
        $doc = new \DomDocument('1.0');
        $doc->loadXML($metadata);
        $doc->formatOutput = true;
        $metadata = $doc->saveXML();

        if ($path !='') {
            $fileCreationStatus = file_put_contents($path, $metadata);
            if ($fileCreationStatus === false) {
                $this->log->addWarning(
                    "There was a problem writing the issue-level metadata to a file",
                    array('file' => $path)
                );
            }
        }
    }

    /**
     * Generates a very simple MODS.xml file for a newspaper page.
     *
     * @param $parent_metadata
     *    The MODS document associated with the page's parent issue.
     * @param $page_number
     *    The page's page number, taken from its file's filename.
     * @param $path
     *    The path to write the XML file to.
     */
    public function writePageMetadataFile($parent_metadata, $page_number, $path)
    {
        // Get the first title element from the issue's MODS.
        $dom = new \DOMDocument;
        $dom->loadXML($parent_metadata);
        $xpath = new \DOMXPath($dom);
        $titles = $xpath->query("//mods:titleInfo/mods:title");
        $page_title = $titles->item(0)->nodeValue . ', page ' . $page_number;
        $page_title = htmlspecialchars($page_title, ENT_NOQUOTES|ENT_XML1);
        $dates = $xpath->query("//mods:originInfo/mods:dateIssued");
        $page_date = $dates->item(0)->nodeValue;

        $namespace = sprintf(
            'xmlns="%s" xmlns:mods="%s" xmlns:xsi="%s" xmlns:xlink="%s"',
            "http://www.loc.gov/mods/v3",
            "http://www.loc.gov/mods/v3",
            "http://www.w3.org/2001/XMLSchema-instance",
            "http://www.w3.org/1999/xlink"
        );
        $page_mods = <<<EOQ
<mods {$namespace}>
  <titleInfo>
    <title>{$page_title}</title>
  </titleInfo>
  <originInfo>
    <dateIssued encoding="w3cdtf">{$page_date}</dateIssued>
  </originInfo>
</mods>
EOQ;
        $path = $path . DIRECTORY_SEPARATOR . $this->metadata_filename;
        $doc = new \DomDocument('1.0');
        $doc->loadXML($page_mods);
        $doc->formatOutput = true;
        $metadata = $doc->saveXML();

        if ($path !='') {
            $fileCreationStatus = file_put_contents($path, $metadata);
            if ($fileCreationStatus === false) {
                $this->log->addWarning(
                    "There was a problem writing the page-level metadata to a file",
                    array('file' => $path)
                );
            }
        }
    }
}
