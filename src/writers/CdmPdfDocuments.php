<?php

namespace mik\writers;

class CdmPdfDocuments extends Writer
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
    private $cdmPhpDocumentsFileGetter;

    /**
     * @var $alias - collection alias
     */
    public $alias;

    /**
     * Create a new newspaper writer Instance
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        parent::__construct($settings);
        $this->fetcher = new \mik\fetchers\Cdm($settings);
        $this->alias = $settings['WRITER']['alias'];
        $fileGetterClass = 'mik\\filegetters\\' . $settings['FILE_GETTER']['class'];
        $this->cdmPhpDocumentsFileGetter = new $fileGetterClass($settings);
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

        // Create root output folder.
        $this->createOutputDirectory();
        $object_path = $this->outputDirectory . DIRECTORY_SEPARATOR;

        $MODS_expected = in_array('MODS', $this->datastreams);
        $DC_expected = in_array('DC', $this->datastreams);
        if ($MODS_expected xor $DC_expected xor $no_datastreams_setting_flag) {
            $this->writeMetadataFile($metadata, $object_path . $record_id . '.xml');
        }

        // Retrieve the PDF file associated with the document and write it to the
        // output folder, using the CONTENTdm pointer as the file basename.
        $OBJ_expected = in_array('OBJ', $this->datastreams);
        if ($OBJ_expected xor $no_datastreams_setting_flag) {
            $temp_file_path = $this->cdmPhpDocumentsFileGetter
                ->getDocumentLevelPDFContent($record_id);
            if ($temp_file_path) {
                $pdf_output_file_path = $object_path . $record_id . '.pdf';
                rename($temp_file_path, $pdf_output_file_path);
            } else {
              // @todo: Log failure.
            }
        }

        // https://github.com/Islandora/islandora_batch only allows two files per
        // object, the MODS (or DC) file and the OBJ file. Therefore, we can't
        // use thumbnails for single-file object batch loading.
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
