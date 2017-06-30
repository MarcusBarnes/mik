<?php

namespace mik\writers;

use Monolog\Logger;

class CdmSingleFile extends Writer
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
     * @var object cdmSingleFileFileGetter - filegetter class for
     * getting files related to CDM single-file objects.
     */
    private $cdmSingleFileFileGetter;

    /**
     * @var $alias - collection alias
     */
    public $alias;

    /**
     * Create a new newspaper writer Instance.
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        parent::__construct($settings);
        $this->fetcher = new \mik\fetchers\Cdm($settings);
        $this->alias = $settings['WRITER']['alias'];
        $fileGetterClass = 'mik\\filegetters\\' . $settings['FILE_GETTER']['class'];
        $this->cdmSingleFileFileGetter = new $fileGetterClass($settings);

        // Set up logger.
        $this->pathToLog = $settings['LOGGING']['path_to_log'];
        $this->log = new \Monolog\Logger('CdmSingleFile writer');
        $this->logStreamHandler = new \Monolog\Handler\StreamHandler(
            $this->pathToLog,
            Logger::ERROR
        );
        $this->log->pushHandler($this->logStreamHandler);
    }

    /**
     * Write folders and files.
     */
    public function writePackages($metadata, $pages, $record_id)
    {

        // If there were no datastreams explicitly set in the configuration,
        // set flag so that all datastreams in the writer class are created.
        // $this->datastreams is an empty array by default.
        $no_datastreams_setting_flag = false;
        if (count($this->datastreams) == 0) {
            $no_datastreams_setting_flag = true;
        }

        // Create root output folder.
        $this->createOutputDirectory();
        $object_path = $this->outputDirectory . DIRECTORY_SEPARATOR;

        $MODS_expected = in_array('MODS', $this->datastreams);
        $DC_expected = in_array('DC', $this->datastreams);
        if ($MODS_expected xor $DC_expected xor $no_datastreams_setting_flag) {
            $this->writeMetadataFile($metadata, $object_path . $record_id . '.xml');
        }

        // Retrieve the file associated with the document from CONTENTdm and write
        // it to the output folder, using the CONTENTdm pointer as the file basename.
        // Note that since the filename of the file retrieved from CONTENTdm
        // is the same as the object's pointer, we can't specify a DSID here
        // such as MODS or OBJ. This means that we only write the file if no
        // datastream IDs are specified in the datastreams[] configuration option or
        // if OBJ (which is what the file that is not an .xml file is) is specified.
        if (!$this->cdmSingleFileFileGetter->input_directories) {
            if (in_array('OBJ', $this->datastreams) || $no_datastreams_setting_flag) {
                $temp_file_path = $this->cdmSingleFileFileGetter
                    ->getFileContent($record_id);
                // Get the filename used by CONTENTdm (stored in the 'find' field)
                // so we can grab the extension.
                $item_info = $this->fetcher->getItemInfo($record_id);
                $source_file_extension = pathinfo($item_info['find'], PATHINFO_EXTENSION);
                if ($temp_file_path) {
                    $output_file_path = $object_path . $record_id . '.' . $source_file_extension;
                    rename($temp_file_path, $output_file_path);
                } else {
                    $this->log->addInfo(
                        "Cannot rename OBJ datastream file",
                        array(
                            'Record ID' => $record_id,
                            'Temp file path' => $temp_file_path,
                            'Output file path' => $output_file_path,
                        )
                    );
                }
                return true;
            }
        }

        // If one or more input directories are configured, retrieve the master file
        // associated with the document and write it to the output folder, using the
        // CONTENTdm pointer as the file basename.
        if (count($this->cdmSingleFileFileGetter->input_directories) > 0) {
            if (in_array('OBJ', $this->datastreams) || $no_datastreams_setting_flag) {
                $master_file_path = $this->cdmSingleFileFileGetter->getMasterFilePath($record_id);
                $source_file_extension = pathinfo($master_file_path, PATHINFO_EXTENSION);
                if ($master_file_path) {
                    $output_file_path = $object_path . $record_id . '.' . $source_file_extension;
                    copy($master_file_path, $output_file_path);
                } else {
                    $this->log->addInfo(
                        "Cannot copy OBJ datastream file",
                        array(
                            'Record ID' => $record_id,
                            'Master file path' => $master_file_path,
                            'Output file path' => $output_file_path,
                        )
                    );
                }
            }
            return true;
        }
    }

    public function writeMetadataFile($metadata, $path)
    {
        // Add XML decleration
        $doc = new \DomDocument('1.0');
        $doc->loadXML($metadata);
        $doc->formatOutput = true;
        $metadata = $doc->saveXML();

        if ($path !='') {
            $filecreationStatus = file_put_contents($path, $metadata);
            if ($filecreationStatus === false) {
                echo "There was a problem exporting the metadata to a file.\n";
            } else {
                // echo "Exporting metadata file.\n";
            }
        }
    }
}
