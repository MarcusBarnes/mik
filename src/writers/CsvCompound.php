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
        $fetcherClass = 'mik\\fetchers\\' . $settings['FETCHER']['class'];
        $this->fetcher = new $fetcherClass($settings);
        $fileGetterClass = 'mik\\filegetters\\' . $settings['FILE_GETTER']['class'];
        $this->fileGetter = new $fileGetterClass($settings);
        $metadataParserClass = 'mik\\metadataparsers\\' . $settings['METADATA_PARSER']['class'];
        $this->metadataParser = new $metadataParserClass($settings);
        $this->output_directory = $settings['WRITER']['output_directory'];
        $this->metadata_filename = $settings['WRITER']['metadata_filename'];
        $this->compound_directory_field = $settings['FILE_GETTER']['compound_directory_field'];
        $this->child_key = $settings['FETCHER']['child_key'];
        $this->child_title = $settings['WRITER']['child_title'];
        // Default is to derive child sequence number by splitting filename on '_'.
        if (isset($settings['WRITER']['child_sequence_separator'])) {
            $this->child_sequence_separator = $settings['WRITER']['child_sequence_separator'];
        } else {
            $this->child_sequence_separator = '_';
        }
        // Default is to generate page-level MODS.xml files.
        if (isset($settings['WRITER']['generate_child_modsxml'])) {
            $this->generate_child_modsxml = $settings['WRITER']['generate_child_modsxml'];
        } else {
            $this->generate_child_modsxml = true;
        }

        // Default minimum child count is 2.
        if (isset($settings['WRITER']['min_children'])) {
            $this->min_children = $settings['WRITER']['min_children'];
        } else {
            $this->min_children = 2;
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
     * Writes out the folders and files that make up the Islandora
     * import packages.
     *
     * @param $metadata
     *   The serialized XML metadata produced by the metadata parser.
     * @param $children
     *   An array of absolute paths to the object's child files.
     * @param $record_id
     *   The value of the record key field for this object.
     */
    public function writePackages($metadata, $children, $record_id)
    {
        if (count($children) < $this->min_children) {
            $this->log->addError(
                "Number of child files is lower than configured minimum",
                array(
                    'record ID' => $record_id,
                    'number of child files' => count($children),
                    'configured minimum' => $this->min_children
                )
            );
            $this->problemLog->addError($record_id);
            return;
        }

        // If there were no datastreams explicitly set in the configuration,
        // set flag so that all datastreams in the writer class are run.
        // $this->datastreams is an empty array by default.
        $no_datastreams_setting_flag = false;
        if (count($this->datastreams) == 0) {
            $no_datastreams_setting_flag = true;
        }

        // Create a compound-level subdirectory in the output directory to correspond
        // to a compound-evel input subdirectory. We check to make sure that the row
        // contains no child sequence value so we don't create object-level directories
        // for rows that contain child metadata.
        $cpd_item_info = $this->fetcher->getItemInfo($record_id);
        $MODS_expected = in_array('MODS', $this->datastreams);

        $cpd_input_dir = $this->fileGetter->getCpdSourcePath($record_id);

        if ($this->inputValidator->validateInputType == 'realtime' && $this->inputValidator->validateInput) {
            if (!$this->inputValidator->validatePackage($record_id, $cpd_input_dir)) {
                $this->problemLog->addError($record_id);
                return;
            }
        }

        $cpd_output_dir = $this->output_directory . DIRECTORY_SEPARATOR .
            $cpd_item_info->{$this->compound_directory_field};

        // Allow source file to not exist if 'MODS' is the only member of
        // $this->datastreams (to allow for testing).
        if ($this->datastreams == array('MODS')) {
            if (!file_exists($cpd_output_dir)) {
                mkdir($cpd_output_dir);
                // Generate MODS for parent compound object.
                $this->writeMetadataFile($metadata, $cpd_output_dir);
            }
        } else {
            if (file_exists($cpd_input_dir) && strlen($cpd_item_info->{$this->child_key}) === 0) {
                if (!file_exists($cpd_output_dir)) {
                    mkdir($cpd_output_dir);
                }

                if ($MODS_expected xor $no_datastreams_setting_flag) {
                    if (file_exists($cpd_output_dir)) {
                        // Generate MODS for parent compound object.
                        $this->writeMetadataFile($metadata, $cpd_output_dir);
                    }
                }
            }
        }

        // Since child objects that have their own metadata appear in
        // the CSV file along with parent compound objects, we process
        // them outside of the foreach child loop below.
        $child_item_info = $this->fetcher->getItemInfo($record_id);
        if (strlen($child_item_info->{$this->child_key})) {
            // There are no input files so we can't create corresponding
            // output directories.
            if ($this->settings['FILE_GETTER']['input_directory'] == '' &&
                $this->datastreams == array('MODS')) {
                return;
            }
            $sequence_number = $child_item_info->{$this->child_key};
            $cpd_output_dir = $this->output_directory . DIRECTORY_SEPARATOR .
                $child_item_info->{$this->compound_directory_field};
            $child_output_dir = $cpd_output_dir . DIRECTORY_SEPARATOR . $sequence_number;
            $metadata = $this->metadataParser->metadata($record_id);
            $this->writeChildMetadataFile($metadata, $sequence_number, $child_output_dir, $child_item_info);
            // Once we've processed the child with its own metadata,
            // we're done with this row in the CSV file.
            return;
        }

        // For parent-level compound objects, get the paths to their child
        // objects and process each child, including writing out its
        // MODS file.
        $children_paths = $this->fileGetter->getChildren($record_id);
        foreach ($children_paths as $child_path) {
            // There are no input files so we can't create corresponding
            // output directories.
            if ($this->settings['FILE_GETTER']['input_directory'] == '' &&
                $this->datastreams == array('MODS')) {
                return;
            }
            $pathinfo = pathinfo($child_path);
            // Get the sequence number from the last segment of the child filename,
            // split on value of $this->child_sequence_separator.
            $filename_segments = explode($this->child_sequence_separator, $pathinfo['filename']);
            $sequence_number = end($filename_segments);

            $child_item_info = $this->fetcher->getItemInfo($record_id);
            $cpd_output_dir = $this->output_directory . DIRECTORY_SEPARATOR .
                $child_item_info->{$this->compound_directory_field};

            $child_output_dir = $cpd_output_dir . DIRECTORY_SEPARATOR . $sequence_number;
            if (!file_exists($child_output_dir)) {
                mkdir($child_output_dir);
            }
            $OBJ_expected = in_array('OBJ', $this->datastreams);
            if ($OBJ_expected xor $no_datastreams_setting_flag) {
                $extension = $pathinfo['extension'];
                $child_output_file_path = $child_output_dir . DIRECTORY_SEPARATOR .
                    'OBJ.' . $extension;
                copy($child_path, $child_output_file_path);
            }
            if ($MODS_expected xor $no_datastreams_setting_flag) {
                // Generate MODS for child that does not have its own row in the CSV file.
                if ($this->generate_child_modsxml) {
                    $this->writeChildMetadataFile('', $sequence_number, $child_output_dir);
                }
            }
        }
    }

    /**
     * Writes out the compound-level MODS.xml file.
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

        if (file_exists($path)) {
            $metadata_file_path = $path . DIRECTORY_SEPARATOR . $this->metadata_filename;
            $fileCreationStatus = file_put_contents($metadata_file_path, $metadata);
            if ($fileCreationStatus === false) {
                $this->log->addWarning(
                    "There was a problem writing the compound-level metadata to a file",
                    array('file' => $path)
                );
            }
        }
    }

    /**
     * Generates a very simple MODS.xml file for compound object's child,
     * or if the child has its own row in the CSV metadata file, write
     * out a MODS.xml file using it.
     *
     * @param $metadata
     *    An empty string (if children inherit the compound's title) or
     *    the MODS document associated with the child itself (as indicated by
     *    whether the the $child_item_info paramter is passed in.
     * @param $sequence_number
     *    The child's sequence number, taken from its file's filename.
     * @param $path
     *    The path to write the MODS.xml file to.
     * @param $child_item_info
     *     The metadata from the CSV file for the child.
     */
    public function writeChildMetadataFile($metadata, $sequence_number, $path, $child_item_info = false)
    {
        // For child that has its own row in the CSV file.
        if ($child_item_info) {
            $path = $this->output_directory . DIRECTORY_SEPARATOR .
                $child_item_info->{$this->compound_directory_field} . DIRECTORY_SEPARATOR .
                $sequence_number . DIRECTORY_SEPARATOR . $this->metadata_filename;
            $fileCreationStatus = file_put_contents($path, $metadata);
            if ($fileCreationStatus === false) {
                $this->log->addWarning(
                    "There was a problem writing the child-level metadata to its file",
                    array('file' => $path)
                );
            }
            return;
        }

        // For child that does not have its own row in the CSV file.
        $child_title = $this->child_title;
        if (preg_match('/%parent_title%/', $this->child_title)) {
            // This is lazy, but it works. If child has no metadata of its own, we get the
            // parent compound object's MODS file (already written out at this point) and
            // parse out its title. This way children can also inherit any changes to the
            // compound object metadata made by metadata manipulators. The compound object's
            // MODS file is in the parent directory of the current child object (i.e., its
            // path minus the sequence number).
            $compound_mods_dir = dirname($path);
            // Get the first title element from the compound object's MODS.
            $dom = new \DOMDocument;
            $dom->load($compound_mods_dir . DIRECTORY_SEPARATOR . $this->metadata_filename);
            $xpath = new \DOMXPath($dom);
            $titles = $xpath->query("//mods:titleInfo/mods:title");
            $parent_title = $titles->item(0)->nodeValue;
            $child_title = preg_replace('/%parent_title%/', $parent_title, $child_title);
            $child_title = htmlspecialchars($child_title, ENT_NOQUOTES|ENT_XML1);
        }
        if (preg_match('/%sequence_number%/', $this->child_title)) {
            $child_title = preg_replace('/%sequence_number%/', ltrim($sequence_number, '0'), $child_title);
        }

        $namespace = sprintf(
            'xmlns="%s" xmlns:mods="%s" xmlns:xsi="%s" xmlns:xlink="%s"',
            "http://www.loc.gov/mods/v3",
            "http://www.loc.gov/mods/v3",
            "http://www.w3.org/2001/XMLSchema-instance",
            "http://www.w3.org/1999/xlink"
        );
        $child_mods = <<<EOQ
<mods {$namespace}>
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
                $this->log->addWarning(
                    "There was a problem writing the child-level metadata to its file",
                    array('file' => $path)
                );
            }
        }
    }
}
