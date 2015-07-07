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
        $object_path = $this->outputDirectory . DIRECTORY_SEPARATOR;
        $this->writeMetadataFile($metadata, $object_path . $record_id . '.xml');

        // Retrieve the file associated with the document and write it to the
        // output folder, using the record number as the file basename.
        $source_file_path = $this->fileGetter->getFilePath($record_id);
        $source_file_extension = pathinfo($source_file_path, PATHINFO_EXTENSION);
        $dest_file_path = $object_path . DIRECTORY_SEPARATOR . $record_id . '.' . $source_file_extension;

        copy($source_file_path, $dest_file_path);
    }

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
                echo "There was a problem exporting the metadata to a file.\n";
            } else {
                $this->modsValidator->validate($path);
            }
        }
    }
    
}
