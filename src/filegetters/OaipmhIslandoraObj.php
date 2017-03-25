<?php

/**
 * This filegetter is for use in OAI-PMH toolchains that harvest content from
 * Islandora sites. Will harvest the datastreams listed in the config option
 * [WRITER] datastream_ids. Intended as an example of a specialized, repository-specific
 * filegetter and is primarly for use in workshops and other training or testing
 * situtions.
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
        $this->datastreamIds = $settings['FILE_GETTER']['datastream_ids'];
    }

    /**
     * Placeholder method needed because it's called in the main loop in mik.
     */
    public function getChildren($record_key)
    {
        return array();
    }

    /**
     * Get the URL for the datastream (OBJ, PDF, image, etc.).
     *
     * @param string $record_key
     *
     * @return string $ds_url
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
                $islandora_host = $islandora_url_info['scheme'] . '://' . $islandora_url_info['host'] . $port;
                // Assemble the URL of each datastream listed in the config and return on the first one
                // that is available. We loop through DSIDs because not all Islandora content models
                // require an OBJ datastream, e.g., PDF, video and audio content models.
                foreach ($this->datastreamIds as $dsid) {
                    $ds_url = $islandora_host . '/islandora/object/' . $pid . '/datastream/' . $dsid . '/download';
                    // HEAD is probably more efficient than the default GET.
                    stream_context_set_default(
                        array(
                            'http' => array(
                                'method' => 'HEAD'
                             )
                        )
                    );
                    $headers = get_headers($ds_url, 1);
                    if ($headers[0] == 'HTTP/1.1 200 OK') {
                        return $ds_url;
                    }
                }
                // If no datastreams are available, return false.
                return false;
             }
         }
         // If no dc:identifiers contain what appears to be a PID (unlikely, but possible), return false.
         return false;
    }
}
