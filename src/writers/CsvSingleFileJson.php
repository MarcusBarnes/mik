<?php

/**
 * Example writer class to demonstrate how to create something other
 * than packages using MODS or DC.
 *
 * Intended for demonstration purposes only, not for production.
 */


namespace mik\writers;

class CsvSingleFileJson extends Writer
{
    /**
     * @var array $settings - configuration settings from confugration class.
     */
    public $settings;
    
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
        $fileGetterClass = 'mik\\filegetters\\' . $settings['FILE_GETTER']['class'];
        $this->fileGetter = new $fileGetterClass($settings);
        $this->output_directory = $settings['WRITER']['output_directory'];
        if (isset($settings['WRITER']['preserve_content_filenames'])) {
            $this->preserve_content_filenames = $settings['WRITER']['preserve_content_filenames'];
        } else {
            $this->preserve_content_filenames = false;
        }
    }

    /**
     * Write folders and files.
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

        // Whether to use record filename from csv as identifier vs. record_id
        $preserve_content_filenames = $this->preserve_content_filenames;

        // Create root output folder
        $this->createOutputDirectory();
        $output_path = $this->outputDirectory . DIRECTORY_SEPARATOR;

        // Retrieve the file associated with the document and write it to the output
        // folder using the filename or record_id identifier
        $source_file_path = $this->fileGetter->getFilePath($record_id);

    // But first, check to see if the source file exists, and if it doesn't, log
    // that fact and skip writing the package.
        if (!file_exists($source_file_path)) {
            $this->log->addWarning(
                "Source file does not exist, skipping writing package",
                array('source_file' => $source_file_path)
            );
            return;
        }

        $source_file_name = pathinfo($source_file_path, PATHINFO_FILENAME);
        $source_file_extension = pathinfo($source_file_path, PATHINFO_EXTENSION);
        $identifier = ($preserve_content_filenames) ? $source_file_name : $record_id;

        $content_file_path = $output_path . $identifier . '.' . $source_file_extension;
        $metadata_file_path = $output_path . $identifier . '.json';

        // Do not overwrite if source and content file paths match
        $enforce_metadata_only = $source_file_path == $content_file_path;

        $JSON_expected = in_array('JSON', $this->datastreams);
        if ($JSON_expected xor $no_datastreams_setting_flag) {
            $metadata_file_path = $output_path . $identifier . '.json';
            // The default is to overwrite the metadata file.
            if ($this->overwrite_metadata_files) {
                $this->writeMetadataFile($metadata, $metadata_file_path, true);
            } else {
                // But if the config says not to, we log the existence of the file.
                if (file_exists($metadata_file_path)) {
                    $this->log->addWarning(
                        "Metadata file already exists, not overwriting it",
                        array('file' => $metadata_file_path)
                    );
                } else {
                    $this->writeMetadataFile($metadata, $metadata_file_path, true);
                }
            }
        }

        // Note that since the datastream ID of the file being copied varies,
        // we can't specify one here like we do for JSON or OBJ. This means
        // that we only write the file if no datastream IDs are specified in the
        // datastreams[] configuration option.
        if ($no_datastreams_setting_flag) {
            // The default is to overwrite the content file (but not if generating metadata only)
            if ($this->overwrite_content_files && ! $enforce_metadata_only) {
                copy($source_file_path, $content_file_path);
            } else {
                // But if the config says not to, or source and content paths match,
                // we log the existence of the file.
                if (file_exists($content_file_path)) {
                    $warning = ($enforce_metadata_only) ?
                        "Source and content paths match, generating metadata only" :
                        "Content file already exists, not overwriting it" ;
                    $this->log->addWarning(
                        $warning,
                        array('file' => $content_file_path)
                    );
                } else {
                    copy($source_file_path, $content_file_path);
                }
            }
        }
    }

    public function writeMetadataFile($metadata, $path, $overwrite = true)
    {
        // file_put_contents() overwrites by default.
        if (!$overwrite) {
            $this->log->addWarning(
                "Metadata file exists, and overwrite is set to false",
                array('file' => $path)
            );
            return;
        }

        if ($path !='') {
            $fileCreationStatus = file_put_contents($path, $metadata);
            if ($fileCreationStatus === false) {
                $this->log->addWarning(
                    "There was a problem writing the metadata to a file",
                    array('file' => $path)
                );
            }
        }
    }
}
