<?php

namespace mik\writers;
use Monolog\Logger;

class CsvNewspapers extends Writer
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
     * @var object cdmPhpDocumentsFileGetter - filegetter class for
     * getting files related to CDM PHP documents.
     */
    private $fileGetter;

    /**
     * Create a new newspaper writer Instance
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        parent::__construct($settings);
        $this->fetcher = new \mik\fetchers\Cdm($settings);
        $fileGetterClass = 'mik\\filegetters\\' . $settings['FILE_GETTER']['class'];
        $this->fileGetter = new $fileGetterClass($settings);
        $this->output_directory = $settings['WRITER']['output_directory'];

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
        // a corresponding inputdirectory.
        $issue_level_input_dir = $this->fileGetter->getIssueSourcePath($record_id);
        if (file_exists($issue_level_input_dir)) {
            $issue_level_output_dir = $this->output_directory . DIRECTORY_SEPARATOR . $record_id;
            if (!file_exists($issue_level_output_dir)) {
                mkdir($issue_level_output_dir);
            }
        }
        else {
            $this->log->addWarning("CSV Newspapers warning",
                array('Issue-level input directory does not exist' => $issue_level_input_dir));
            return;
        }

        $MODS_expected = in_array('MODS', $this->datastreams);
        $DC_expected = in_array('DC', $this->datastreams);
        if ($MODS_expected xor $DC_expected xor $no_datastreams_setting_flag) {
            $metadata_file_path = $issue_level_output_dir . DIRECTORY_SEPARATOR . 'MODS.xml';
            // The default is to overwrite the metadata file.
            if ($this->overwrite_metadata_files) {
                $this->writeMetadataFile($metadata, $metadata_file_path, true);
            }
            else {
                // But if the config says not to, we log the existence of the file.
                if (file_exists($metadata_file_path)) {
                    $this->log->addWarning("Metadata file already exists, not overwriting it",
                        array('file' => $metadata_file_path));
                }
                else {
                    $this->writeMetadataFile($metadata, $metadata_file_path, true);
                }
            }
        }

        foreach ($pages as $page_path) {
            // Get the page number from the filename. It is the last se
            $pathinfo = pathinfo($page_path);
            $filename_segments = explode('-', $pathinfo['filename']);
            $page_number = ltrim(end($filename_segments), '0');
            $page_level_output_dir = $issue_level_output_dir . DIRECTORY_SEPARATOR . $page_number;
            mkdir($page_level_output_dir);
            $extension = $pathinfo['extension'];
            $page_output_file_path = $page_level_output_dir . DIRECTORY_SEPARATOR . 'OBJ.' . $extension;
            copy($page_path, $page_output_file_path);
        }
    }

    public function writeMetadataFile($metadata, $path, $overwrite = true)
    {
        // file_put_contents() overwrites by default.
        if (!$overwrite) {
            $this->log->addWarning("Metadata file exists, and overwrite is set to false",
                array('file' => $path));
            return;
        }

        // Add XML decleration
        $doc = new \DomDocument('1.0');
        $doc->loadXML($metadata);
        $doc->formatOutput = true;
        $metadata = $doc->saveXML();

        if ($path !='') {
            $fileCreationStatus = file_put_contents($path, $metadata);
            if ($fileCreationStatus === false) {
                $this->log->addWarning("There was a problem writing the metadata to a file",
                    array('file' => $path));
            }
        }
    }

}
