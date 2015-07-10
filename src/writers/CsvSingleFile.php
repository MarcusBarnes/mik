<?php

namespace mik\writers;

class CsvSingleFile extends Writer
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
     * @var object $modsValidator - filemanipulator class for validating
     * the MODS file. 
     */
    private $modsValidator;

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
        $this->modsValidator = new \mik\filemanipulators\ValidateMods($settings);
    }

    /**
     * Write folders and files.
     */
    public function writePackages($metadata, $pages, $record_id)
    {
        // Create root output folder
        $this->createOutputDirectory();
        $output_path = $this->outputDirectory . DIRECTORY_SEPARATOR;
        $metadata_file_path = $output_path . $record_id . '.xml';

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

        // Retrieve the file associated with the document and write it to the
        // output folder, using the record number as the file basename.
        $source_file_path = $this->fileGetter->getFilePath($record_id);
        $source_file_extension = pathinfo($source_file_path, PATHINFO_EXTENSION);
        $content_file_path = $output_path . $record_id . '.' . $source_file_extension;

        // The default is to overwrite the content file.
        if ($this->overwrite_content_files) {
            copy($source_file_path, $content_file_path);
        }
        else {
            // But if the config says not to, we log the existence of the file.
            if (file_exists($content_file_path)) {
                $this->log->addWarning("Content file already exists, not overwriting it",
                    array('file' => $content_file_path));
            }
            else {
                copy($source_file_path, $content_file_path);
            }
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
            } else {
                $this->modsValidator->validate($path);
            }
        }
    }
    
}
