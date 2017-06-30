<?php

namespace mik\writers;

use Monolog\Logger;

class CsvBooks extends Writer
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
     * @var object - filegetter class for CSV books.
     */
    private $fileGetter;

    /**
     * Create a new book writer instance.
     *
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

        $book_level_input_dir = $this->fileGetter->getBookSourcePath($record_id);

        if ($this->inputValidator->validateInputType == 'realtime' && $this->inputValidator->validateInput) {
            if (!$this->inputValidator->validatePackage($record_id, $book_level_input_dir)) {
                $this->problemLog->addError($record_id);
                return;
            }
        }

        // Create a book-level subdirectory in the output directory.
        $book_level_output_dir = $this->output_directory . DIRECTORY_SEPARATOR . $record_id;
        if (!file_exists($book_level_output_dir)) {
            mkdir($book_level_output_dir);
        }

        // Report a missing input dir only when the book level input dir is missing
        // and all datastreams are expected, or when both MODS and OBJ are explicit.
        // But don't report a missing input dir when MODS is the only datastream and
        // the input directory setting is empty.
        if (!file_exists($book_level_input_dir)) {
            if ($this->settings['FILE_GETTER']['input_directory'] !== '' &&
                ($this->datastreams != array('MODS') xor $no_datastreams_setting_flag)) {
                if ($no_datastreams_setting_flag) {
                    $this->log->addWarning(
                        "CSV Books warning",
                        array('Book-level input directory does not exist' => $book_level_input_dir)
                    );
                    return;
                }
            }
        }

        $MODS_expected = in_array('MODS', $this->datastreams);
        if ($MODS_expected xor $no_datastreams_setting_flag) {
            $metadata_file_path = $book_level_output_dir . DIRECTORY_SEPARATOR .
                $this->metadata_filename;
            $this->writeMetadataFile($metadata, $metadata_file_path);
        }

        // @todo: Add error handling on mkdir and copy.
        // @todo: Write page level MODS.xml file, after testing ingest as is.
        foreach ($pages as $page_path) {
            // Get the page number from the filename. It is the last segment.
            $pathinfo = pathinfo($page_path);
            $filename_segments = explode($this->page_sequence_separator, $pathinfo['filename']);

            $page_number = ltrim(end($filename_segments), '0');
            $page_level_output_dir = $book_level_output_dir . DIRECTORY_SEPARATOR . $page_number;
            mkdir($page_level_output_dir);

            $OBJ_expected = in_array('OBJ', $this->datastreams);
            if ($OBJ_expected xor $no_datastreams_setting_flag) {
                $extension = $pathinfo['extension'];
                $page_output_file_path = $page_level_output_dir . DIRECTORY_SEPARATOR .
                    'OBJ.' . $extension;
                copy($page_path, $page_output_file_path);
            }

            if ($MODS_expected xor $no_datastreams_setting_flag) {
                if ($this->generate_page_modsxml) {
                    $this->writePageMetadataFile($metadata, $page_number, $page_level_output_dir);
                }
            }
        }
    }

    /**
     * Writes out the book-level MODS.xml file.
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
                    "There was a problem writing the book-level metadata to a file",
                    array('file' => $path)
                );
            }
        }
    }

    /**
     * Generates a very simple MODS.xml file for a book page.
     *
     * @param $parent_metadata
     *    The MODS document associated with the page's parent book.
     * @param $page_number
     *    The page's page number, taken from its file's filename.
     * @param $path
     *    The path to write the XML file to.
     */
    public function writePageMetadataFile($parent_metadata, $page_number, $path)
    {
        // Get the first title element from the book's MODS.
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
