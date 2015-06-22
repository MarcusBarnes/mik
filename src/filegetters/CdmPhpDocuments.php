<?php

namespace mik\filegetters;
use GuzzleHttp\Client;

class CdmPhpDocuments extends FileGetter
{
    /**
     * @var array $settings - configuration settings from confugration class.
     */
    public $settings;

    /**
     * @var string $utilsUrl - CDM utils url.
     */
    public $utilsUrl;

    /**
     * @var string $alias - CDM alias
     */
    public $alias;

    /**
     * Create a new CONTENTdm Fetcher Instance
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        $this->settings = $settings['FILE_GETTER'];
        $this->utilsUrl = $this->settings['utils_url'];
        $this->alias = $this->settings['alias'];
        $this->temp_directory = (!isset($settings['FILE_GETTER']['temp_directory'])) ?
          '/tmp' : $settings['FILE_GETTER']['temp_directory'];
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
        return $phrase . " (from the CdmPhPDocuments filegetter)\n";
    }

    /**
     * Placeholder method needed because it's called in the main loop in mik.
     */
    public function getChildren($pointer)
    {
        return array();
    }

    /**
     * Gets a PHP document's structure.
     */
    public function getDocumentStructure($pointer)
    {
        $alias = $this->settings['alias'];
        $ws_url = $this->settings['ws_url'];
        $query_url = $ws_url . 'dmGetCompoundObjectInfo/' . $alias . '/' .  $pointer . '/json';
        $item_structure = file_get_contents($query_url);
        $item_structure = json_decode($item_structure, true);
        
        return $item_structure;
    }

    public function getDocumentLevelPDFContent($pointer)
    {
        $document_structure = $this->getDocumentStructure($pointer);

        $temp_file_path = $this->temp_directory . DIRECTORY_SEPARATOR . $this->alias . '_' . $pointer . '.tmp';

        // Retrieve the file associated with the object. In the case of PDF Documents,
        // the file is a single PDF comprised of all the page-level PDFs joined into a
        // single PDF file using the (undocumented) CONTENTdm API call below.
        $get_file_url = $this->utilsUrl .'getdownloaditem/collection/'
            . $this->alias . '/id/' . $pointer . '/type/compoundobject/show/1/cpdtype/document-pdf/filename/'
            . $document_structure['page'][0]['pagefile'] . '/width/0/height/0/mapsto/pdf/filesize/0/title/'
            . urlencode($document_structure['page'][0]['pagetitle']);
        $client = new Client();
        $response = $client->get($get_file_url, ['stream' => true]);
        $body = $response->getBody();
        while (!$body->eof()) {
          file_put_contents($temp_file_path, $body->read(1024), FILE_APPEND);
        }
        if (file_exists($temp_file_path)) {
          return $temp_file_path;
        }
        else {
          return false;
        }
    }
}
