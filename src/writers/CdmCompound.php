<?php

namespace mik\writers;

use mik\exceptions\MikErrorException;
use Monolog\Logger;

class CdmCompound extends Writer
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
     * @var object cdmCompoundFileGetter - filegetter class for
     * getting files related to CDM compound objects.
     */
    private $cdmCompoundFileGetter;

    /**
     * @var object cdmSingleFileGetter - filegetter class for
     * getting files related to CDM single file objects.
     */
    private $cdmSingleFileGetter;
    
    /**
     * @var $alias - collection alias
     */
    public $alias;
   
    /**
     * @var string $cdmCpdFileName - file name for the CONTENTdm .cpd file,
     * including its extension. If absent, no .cpd file is written.
     */
    public $cdmCpdFileName;

    /**
     * @var string $metadataFileName - file name for metadata file to be written.
     */
    public $metadataFileName;

    /**
     * @var object metadataparser - metadata parser object
     */
    public $metadataParser;

    /**
     * @var string $parentObjectOutputPath - path to a child object's parent.
     */
    public $parentObjectOutputPath;
    /**
     * Create a new compound object writer instance
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        parent::__construct($settings);
        $this->fetcher = new \mik\fetchers\Cdm($settings);
        $this->alias = $settings['WRITER']['alias'];
        $fileGetterClass = 'mik\\filegetters\\' . $settings['FILE_GETTER']['class'];
        $this->cdmCompoundFileGetter = new $fileGetterClass($settings);
        $this->cdmSingleFileGetterSettings = $settings;
        $this->cdmSingleFileGetter = new \mik\filegetters\CdmSingleFile($this->cdmSingleFileGetterSettings);
        if (isset($this->settings['metadata_filename'])) {
            $this->metadataFileName = $this->settings['metadata_filename'];
        } else {
            $this->metadataFileName = 'MODS.xml';
        }
        
        $metadtaClass = 'mik\\metadataparsers\\' . $settings['METADATA_PARSER']['class'];
        $this->metadataParser = new $metadtaClass($settings);

        // Set up logger.
        $this->pathToLog = $settings['LOGGING']['path_to_log'];
        $this->log = new \Monolog\Logger('CdmCompound writer');
        $this->logStreamHandler = new \Monolog\Handler\StreamHandler(
            $this->pathToLog,
            Logger::ERROR
        );
        $this->log->pushHandler($this->logStreamHandler);
    }

    /**
     * Write folders and files.
     */
    public function writePackages($metadata, $children, $record_key)
    {
        // Create root output folder.
        $this->createOutputDirectory();
        $this->parentObjectOutputPath = $this->createObjectOutputDirectory($record_key);
        $this->writeMetadataFile($metadata, $this->parentObjectOutputPath);

        $object_structure = $this->cdmCompoundFileGetter->getDocumentStructure($record_key);

        if (strlen($this->cdmCpdFileName)) {
            $object_structure_path = $this->parentObjectOutputPath . DIRECTORY_SEPARATOR . $this->cdmCpdFileName;
            file_put_contents($object_structure_path, $object_structure);
        }
       
        foreach ($children as $child_pointer) {
            $childObjectPath = $this->createObjectOutputDirectory($child_pointer, true);
            // We can use the CdmSingleFile filegetter class since CONTENTdm
            // compound objects are made up of single file objects.
            // $this->cdmSingleFileGetter = new CdmSingleFile($this->cdmSingleFileGetterSettings);
            $temp_file_path = $this->cdmSingleFileGetter->getFileContent($child_pointer);

            try {
                // Get the filename used by CONTENTdm (stored in the 'find' field)
                // so we can grab the extension.
                $item_info = $this->fetcher->getItemInfo($child_pointer);
                $source_file_extension = pathinfo($item_info['find'], PATHINFO_EXTENSION);
                $output_file_path = $childObjectPath . DIRECTORY_SEPARATOR . 'OBJ' . '.' . $source_file_extension;
                rename($temp_file_path, $output_file_path);
            } catch (Exception $e) {
                $this->log->addError(
                    "CdmCommpound writer error",
                    array('Error writing child content file' => $e->getMessage())
                );
            }

            // Write out the children's metadata file.
            try {
                $child_metadata = $this->metadataParser->metadata($child_pointer);
                $this->writeMetadataFile($child_metadata, $childObjectPath);
            } catch (Exception $e) {
                $this->log->addError(
                    "CdmCommpound writer error",
                    array('Error writing child metadata file' => $e->getMessage())
                );
            }
        }
    }
    
    /**
     * Create the output directory specified in the config file.
     */
    public function createOutputDirectory()
    {
        parent::createOutputDirectory();
    }

    /**
     * Create the output directory specified in the config file.
     *
     * @param string
     *    The object's pointer.
     * @param boolean
     *    Whether or not the object is a child of the ....
     *
     * @return
     *    The path to the directory just created.
     */
    public function createObjectOutputDirectory($pointer, $is_child = false)
    {
        if ($is_child) {
            $path = $this->parentObjectOutputPath . DIRECTORY_SEPARATOR . $pointer;
        } else {
            $path = $this->outputDirectory . DIRECTORY_SEPARATOR . $pointer;
        }

        if (!file_exists($path)) {
            // mkdir returns true if successful; false otherwise.
            if (mkdir($path, 0777, true)) {
                $result = $path; // directory already exists.
            } else {
                return false;
            }
        } else {
            $result = $path; // directory already exists.
        }
        return $result;
    }

    public function writeMetadataFile($metadata, $path)
    {
        // Add XML decleration
        $doc = new \DomDocument('1.0');
        $doc->loadXML($metadata);
        $doc->formatOutput = true;
        $metadata = $doc->saveXML();

        $filename = $this->metadataFileName;
        if ($path !='') {
            $filecreationStatus = file_put_contents($path . DIRECTORY_SEPARATOR . $filename, $metadata);
            if ($filecreationStatus === false) {
                echo "There was a problem exporting the metadata to a file.\n";
            } else {
                // echo "Exporting metadata file.\n";
            }
        }
    }
}
