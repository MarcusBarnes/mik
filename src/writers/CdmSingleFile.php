<?php

namespace mik\writers;

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
        // is the same as the object's pointer, we can't specify a DSID here like
        // we do for MODS or OBJ. This means that we only write the file if no
        // datastream IDs are specified in the datastreams[] configuration option
        // or if OBJ is specified.
        if (in_array('OBJ', $this->datastreams) || $no_datastreams_setting_flag) {
            if (!$this->cdmSingleFileFileGetter->input_directories) {
                $temp_file_path = $this->cdmSingleFileFileGetter
                    ->getFileContent($record_id);
                // Get the filename used by CONTENTdm (stored in the 'find' field)
                // so we can grab the extension.
                $item_info = $this->fetcher->getItemInfo($record_id);
                $source_file_extension = pathinfo($item_info['find'], PATHINFO_EXTENSION);
                if ($temp_file_path) {
                  $output_file_path = $object_path . $record_id . '.' . $source_file_extension;
                  rename($temp_file_path, $output_file_path);
                }
                else {
                  // @todo: Log failure.
                }
            }
        }

        // If one or more input directories are configured, retrieve the master file
        // associated with the document and write it to the output folder, using the
        // CONTENTdm pointer as the file basename.
        if (in_array('OBJ', $this->datastreams) || $no_datastreams_setting_flag) {
            if ($this->cdmSingleFileFileGetter->input_directories) {
                $master_file_path = $this->cdmSingleFileFileGetter->getMasterFilePath($record_id);
                $source_file_extension = pathinfo($master_file_path, PATHINFO_EXTENSION);
                if ($master_file_path) {
                  $output_file_path = $object_path . $record_id . '.' . $source_file_extension;
                  copy($master_file_path, $output_file_path);
                }
                else {
                  // @todo: Log failure.
                }
            }
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
