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
        $this->output_directory = $settings['WRITER']['output_directory'];
        $this->metadata_filename = $settings['WRITER']['metadata_filename'];
        // Default is to generate page-level MODS.xml files.
        if (isset($settings['WRITER']['generate_page_modsxml'])) {
            $this->generate_page_modsxml = $settings['WRITER']['generate_page_modsxml'];
        }
        else {
            $this->generate_page_modsxml = true;
        }

        // Set up logger.
        $this->pathToLog = $this->settings['LOGGING']['path_to_log'];
        $this->log = new \Monolog\Logger('Writer');
        $this->logStreamHandler = new \Monolog\Handler\StreamHandler($this->pathToLog,
            Logger::INFO);
        $this->log->pushHandler($this->logStreamHandler);
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

        // Create an issue-level subdirectory in the output directory, but only if there is
        // a corresponding input directory.
        $issue_level_input_dir = $this->fileGetter->getIssueSourcePath($record_id);
        if (file_exists($issue_level_input_dir)) {
            $issue_level_output_dir = $this->output_directory . DIRECTORY_SEPARATOR . $record_id;
            if (!file_exists($issue_level_output_dir)) {
                mkdir($issue_level_output_dir);
            }
        }
        else {
            if ($this->datastreams != array('MODS')) {
                $this->log->addWarning("CSV Newspapers warning",
                    array('Issue-level input directory does not exist' => $issue_level_input_dir));
                return;
            }
        }

        $MODS_expected = in_array('MODS', $this->datastreams);
        if ($MODS_expected xor $no_datastreams_setting_flag) {
            $metadata_file_path = $issue_level_output_dir . DIRECTORY_SEPARATOR . $this->metadata_filename;
            $this->writeMetadataFile($metadata, $metadata_file_path);
        }

        // @todo: Add error handling on mkdir and copy.
        // @todo: Write page level MODS.xml file, after testing ingest as is.
        foreach ($pages as $page_path) {
            // Get the page number from the filename. It is the last se
            $pathinfo = pathinfo($page_path);
            $filename_segments = explode('-', $pathinfo['filename']);
            $page_number = ltrim(end($filename_segments), '0');
            $page_level_output_dir = $issue_level_output_dir . DIRECTORY_SEPARATOR . $page_number;
            mkdir($page_level_output_dir);

            if ($this->datastreams != array('MODS')) {
                $OBJ_expected = in_array('OBJ', $this->datastreams);
                if ($OBJ_expected xor $no_datastreams_setting_flag) {
                    $extension = $pathinfo['extension'];
                    $page_output_file_path = $page_level_output_dir . DIRECTORY_SEPARATOR .
                        'OBJ.' . $extension;
                    copy($page_path, $page_output_file_path);
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
                $this->log->addWarning("There was a problem writing the issue-level metadata to a file",
                    array('file' => $path));
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
        $dates = $xpath->query("//mods:originInfo/mods:dateIssued");
        $page_date = $dates->item(0)->nodeValue;

        $page_mods = <<<EOQ
<mods xmlns="http://www.loc.gov/mods/v3" xmlns:mods="http://www.loc.gov/mods/v3" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xlink="http://www.w3.org/1999/xlink">
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
                $this->log->addWarning("There was a problem writing the page-level metadata to a file",
                    array('file' => $path));
            }
        }
    }

}
