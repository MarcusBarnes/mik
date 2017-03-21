<?php

/**
 * This filegetter is for use in OAI-PMH toolchains that harvest content from
 * Islandora instances. It makes the following assumptions: 1) that the objects
 * discovered via the OAI-PMH havest have an OJB datastream (not true in all cases
 * but should be true of large image, basic image, etc. content models) and 2) the
 * OBJ datastream is publicly readable. The filegetter is intended as an example
 * of a specialized filegetter and is primarly for use in workshops and other
 * training situtions.
 */

namespace mik\filegetters;

use mik\exceptions\MikErrorException;
use Monolog\Logger;

class OaipmhIslandoraObj extends FileGetter
{
    /**
     * @var array $settings - configuration settings from configuration class.
     */
    public $settings;

    /**
     * Create a new OAI Single File Fetcher Instance.
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        $this->settings = $settings['FILE_GETTER'];
        $this->fetcher = new \mik\fetchers\Oaipmh($settings);
        $this->temp_directory = $this->settings['temp_directory'];

        // Set up logger.
        $this->pathToLog = $settings['LOGGING']['path_to_log'];
        $this->log = new \Monolog\Logger('OaipmhIslandoraObj filegetter');
        $this->logStreamHandler = new \Monolog\Handler\StreamHandler($this->pathToLog,
            Logger::ERROR);
        $this->log->pushHandler($this->logStreamHandler);

        $this->oai_endpoint = $settings['FETCHER']['oai_endpoint'];
    }

    /**
     * Placeholder method needed because it's called in the main loop in mik.
     */
    public function getChildren($record_key)
    {
        return array();
    }

    /**
     * Get the URL for the OBJ datastream (PDF, image, etc.).
     *
     * Does not check content model for the existence of an OBJ datastream.
     *
     * @param string $record_key
     *
     * @return string $download_url
     */
    public function getFilePath($record_key)
    {
        // Get the OAI record from the temp directory.
        $raw_metadata_path = $this->settings['temp_directory'] . DIRECTORY_SEPARATOR . $record_key . '.metadata';
        $dom = new \DOMDocument;
        $xml = file_get_contents($raw_metadata_path);
        $dom->loadXML($xml);
        // Loop through all the dc:identifer elements.
        foreach ($dom->getElementsByTagNameNS('http://purl.org/dc/elements/1.1/', 'identifier') as $identifier) {
            // This scary looking regex pattern identifies a valid Fedora PID.
            if (preg_match('/^([A-Za-z0-9]|-|\.)+:(([A-Za-z0-9])|-|\.|~|_|(%[0-9A-F]{2}))+$/', trim($identifier->nodeValue))) {
                $pid = trim($identifier->nodeValue);
                $islandora_url_info = parse_url($this->oai_endpoint);
                if (isset($islandora_url_info['port'])) {
                    $port = $islandora_url_info['port'];
                }
                else {
                    $port = '';
                }
                // Assemble the URL of the OBJ datastream and return it.
                $islandora_host = $islandora_url_info['scheme'] . '://' . $islandora_url_info['host'] . $port;
                $obj_url = $islandora_host . '/islandora/object/' . $pid . '/datastream/OBJ/download';
                return $obj_url;
             }
         }
         // If no dc:identifiers contain what appears to be a PID (unlikely, but possible), return false.
         return false;
    }
}
