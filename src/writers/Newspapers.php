<?php

namespace mik\writers;

class Newspapers extends Writer
{
    /**
     * @var array $settings - configuration settings from confugration class.
     */
    public $settings;
      
    /**
     * Create a new newspaper writer Instance
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        parent::__construct($settings);
    }

    /**
     * Write folders and files.
     */
    public function writePackages($metadata)
    {
        // Create root output folder
        $this->createOutputDirectory();
        $issueObjectPath = $this->createIssueDirectory($metadata);
        $this->writeMetadataFile($metadata, $issueObjectPath);
    }

    /**
     * Create the output directory specified in the config file.
     */
    public function createOutputDirectory()
    {
        parent::createOutputDirectory();
    }

    public function createIssueDirectory($metadata)
    {
        //value of dateIssued isuse is the the title for the directory
        
        $doc = new \DomDocument('1.0');
        $doc->loadXML($metadata);
        $nodes = $doc->getElementsByTagName('dateIssued');
        $issueDate = '0000-00-00';
        if ($nodes->length == 1) {
            $issueDate = $nodes->item(0)->nodeValue;
        } else {
          // log exception - unable to determine issue date.
        }
        
        //$doc->formatOutput = true;

        //$modsxml = $doc->saveXML();
        // Create a directory for each day of the newspaper by getting
        // the date value from the issue's metadata.
        //$issue_object_info = get_item_info($results_record['collection'], $results_record['pointer']);
        
        $issueObjectPath = $this->outputDirectory . trim($issueDate);
        if (!file_exists($issueObjectPath)) {
            mkdir($issueObjectPath);
            // return issue_object_path for use when writing files.
            return $issueObjectPath;
        }
    }

    public function writeMetadataFile($metadata, $path)
    {
        // Add XML decleration
        $doc = new \DomDocument('1.0');
        $doc->loadXML($metadata);
        $doc->formatOutput = true;
        $metadata = $doc->saveXML();

        parent::writeMetadataFile($metadata, $path);
    }
    
    /**
    * Friendly welcome
    *
    * @param string $phrase Phrase to return
    *
    * @return string Returns the phrase passed in
    */
    public function echoPhrase($phrase)
    {
        return $phrase . " (from the newspaper writer)\n";
    }
}
