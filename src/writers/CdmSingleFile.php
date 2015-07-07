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
        // Create root output folder
        $this->createOutputDirectory();
        $object_path = $this->outputDirectory . DIRECTORY_SEPARATOR;
        $this->writeMetadataFile($metadata, $object_path . $record_id . '.xml');

        // Retrieve the file associated with the document and write it to the
        // output folder, using the CONTENTdm pointer as the file basename.
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
