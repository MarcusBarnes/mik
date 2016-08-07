<?php

namespace mik\writers;
use Monolog\Logger;

class CsvCompound extends Writer
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
     * @var object CsvCompound filegetter class.
     */
    private $fileGetter;

    /**
     * @var object CsvToMods metadata parser class.
     */
    private $metadataParser;

    /**
     * Create a new newspaper writer instance
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        parent::__construct($settings);
        $this->fetcher = new \mik\fetchers\Cdm($settings);
        $fileGetterClass = 'mik\\filegetters\\' . $settings['FILE_GETTER']['class'];
        $this->fileGetter = new $fileGetterClass($settings);
        $metadataParserClass = 'mik\\metadataparsers\\' . $settings['METADATA_PARSER']['class'];
        $this->metadataParser = new $metadataParserClass($settings);
        $this->output_directory = $settings['WRITER']['output_directory'];
        $this->metadata_filename = $settings['WRITER']['metadata_filename'];
        $this->child_title = $settings['WRITER']['child_title'];
        // Default is to derive child sequence number by splitting filename on '_'.
        if (isset($settings['WRITER']['child_sequence_separator'])) {
            $this->child_sequence_separator = $settings['WRITER']['child_sequence_separator'];
        }
        else {
            $this->child_sequence_separator = '_';
        }

        // Default is to generate page-level MODS.xml files.
        if (isset($settings['WRITER']['generate_child_modsxml'])) {
            $this->generate_child_modsxml = $settings['WRITER']['generate_child_modsxml'];
        }
        else {
            $this->generate_child_modsxml = true;
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
    public function writePackages($metadata, $children, $record_id)
    {
        // If there were no datastreams explicitly set in the configuration,
        // set flag so that all datastreams in the writer class are run.
        // $this->datastreams is an empty array by default.
        $no_datastreams_setting_flag = false;
        if (count($this->datastreams) == 0) {
            $no_datastreams_setting_flag = true;
        }

        // Create a compound-level subdirectory in the output directory to correspond
        // to a compound-evel input subdirectory
        $cpd_input_dir = $this->fileGetter->getCpdSourcePath($record_id);
        if (file_exists($cpd_input_dir)) {
            $cpd_output_dir = $this->output_directory . DIRECTORY_SEPARATOR . $record_id;
            if (!file_exists($cpd_output_dir)) {
                mkdir($cpd_output_dir);
            }
        }

        $MODS_expected = in_array('MODS', $this->datastreams);
        if ($MODS_expected xor $no_datastreams_setting_flag) {
            $metadata_file_path = $cpd_output_dir . DIRECTORY_SEPARATOR . $this->metadata_filename;
            $this->writeMetadataFile($metadata, $metadata_file_path);
        }

        $children_paths = $this->fileGetter->getChildren($record_id);
        // @todo: Add error handling on mkdir and copy.
        foreach ($children_paths as $child_path) {
            $pathinfo = pathinfo($child_path);
            if (preg_match('/thumbs\.db/i', $pathinfo['basename'])) {
                continue;
            }
            // Get the sequence number from the filename. It is the last segment of the
            // child filename, split on value of $this->child_sequence_separator.
            $filename_segments = explode($this->child_sequence_separator, $pathinfo['filename']);
            $sequence_number = ltrim(end($filename_segments), '0');
            $child_output_dir = $cpd_output_dir . DIRECTORY_SEPARATOR . $sequence_number;
            mkdir($child_output_dir);

            $OBJ_expected = in_array('OBJ', $this->datastreams);
            if ($OBJ_expected xor $no_datastreams_setting_flag) {
                $extension = $pathinfo['extension'];
                $child_output_file_path = $child_output_dir . DIRECTORY_SEPARATOR .
                    'OBJ.' . $extension;
                copy($child_path, $child_output_file_path);
            }

            if ($MODS_expected xor $no_datastreams_setting_flag) {
                if ($this->generate_child_modsxml) {
                    $this->writeChildMetadataFile($metadata, $sequence_number, $child_output_dir);
                }
            }
        }
    }

    /**
     * Writes out the child-level MODS.xml file.
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
     * @param $sequence_number
     *    The child's sequence number, taken from its file's filename.
     * @param $path
     *    The path to write the XML file to.
     */
    public function writeChildMetadataFile($parent_metadata, $sequence_number, $path)
    {
        $child_title = $this->child_title;
        if (preg_match('/%parent_title%/', $this->child_title)) {
            // Get the first title element from the issue's MODS.
            $dom = new \DOMDocument;
            $dom->loadXML($parent_metadata);
            $xpath = new \DOMXPath($dom);
            $titles = $xpath->query("//mods:titleInfo/mods:title");
            $parent_title = $titles->item(0)->nodeValue;
            $child_title = preg_replace('/%parent_title%/', $parent_title, $child_title);
        }
        if (preg_match('/%sequence_number%/', $this->child_title)) {
            $child_title = preg_replace('/%sequence_number%/', $sequence_number, $child_title);
        }

        $child_mods = <<<EOQ
<mods xmlns="http://www.loc.gov/mods/v3" xmlns:mods="http://www.loc.gov/mods/v3" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xlink="http://www.w3.org/1999/xlink">
  <titleInfo>
    <title>{$child_title}</title>
  </titleInfo>
</mods>
EOQ;
        $path = $path . DIRECTORY_SEPARATOR . $this->metadata_filename;
        $doc = new \DomDocument('1.0');
        $doc->loadXML($child_mods);
        $doc->formatOutput = true;
        $metadata = $doc->saveXML();

        if ($path !='') {
            $fileCreationStatus = file_put_contents($path, $metadata);
            if ($fileCreationStatus === false) {
                $this->log->addWarning("There was a problem writing the child-level metadata to a file",
                    array('file' => $path));
            }
        }
    }

}