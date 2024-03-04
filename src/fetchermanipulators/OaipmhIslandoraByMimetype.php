<?php

namespace mik\fetchermanipulators;

use League\CLImate\CLImate;

/**
 * Fetcher manipulator that filters records based on the MIME type of the first
 * datastream file listed in the ['FILE_GETTER']['datastream_ids'] configuration
 * setting.
 */

class OaipmhIslandoraByMimetype extends FetcherManipulator
{
    /**
     * Create a new OaipmhIslandoraObjByMimetype fetchermanipulator Instance
     *
     * @param array $settings
     *   All of the settings from the .ini file.
     *
     * @param array $manipulator_settings
     *   An array of all of the settings for the current manipulator,
     *   with the manipulator class name in the first position and
     *   the list of allowed MiME types, separated by commas,
     *   as the second member.
     */
    public function __construct($settings, $manipulator_settings)
    {
        $this->settings = $settings;
        // $manipulator_settings[0] contains the the classname of this class.
        $params = explode('|', $manipulator_settings[1]);
        $this->dsid = $params[0];
        $this->allowed_mimetypes = explode(',', $params[1]);
        // To get the value of $onWindows.
        parent::__construct();

        $this->oai_endpoint = $settings['FETCHER']['oai_endpoint'];
        $this->datastreamIds = $settings['FILE_GETTER']['datastream_ids'];
    }

    /**
     * Performs an HTTP HEAD request to get the file's Content-Type header.
     * If the values is in the allowed list, keep the record.
     *
     * @param array $all_records
     *   All of the records from the fetcher.
     * @return array $filtered_records
     *   An array of records that pass the test(s) defined in this function.
     */
    public function manipulate($records)
    {
        $numRecs = count($records);
        echo "Filtering $numRecs records through the OaipmhIslandoraByMimetype fetcher manipulator.\n";
        // Instantiate the progress bar.
        if (!$this->onWindows) {
            $climate = new \League\CLImate\CLImate;
            $progress = $climate->progress()->total($numRecs);
        }

        $record_num = 0;
        $filtered_records = array();
        foreach ($records as $record) {
            $mimetype = $this->getMimeType($record->key);
 
            if (in_array($mimetype, $this->allowed_mimetypes)) {
                $filtered_records[] = $record;
            }
            $record_num++;
            if ($this->onWindows) {
                print '.';
            } else {
                $progress->current($record_num);
            }
        }
        if ($this->onWindows) {
            print "\n";
        }
        return $filtered_records;
    }

    /**
     * Get the URL for the datastream (OBJ, PDF, image, etc.).
     *
     * @param string $record_key
     *
     * @return string
     *    The content of the Content-Type header.
     */

    public function getMimeType($record_key)
    {
        // Get the OAI record from the temp directory.
        $raw_metadata_path = $this->settings['FETCHER']['temp_directory'] .
            DIRECTORY_SEPARATOR . $record_key . '.metadata';
        $dom = new \DOMDocument;
        $xml = file_get_contents($raw_metadata_path);
        $dom->loadXML($xml);

        // There will only be one oai:identifer element. Islandora's OAI identifiers look like
        // oai:digital.lib.sfu.ca:foo_112, 'foo_123' being the object's PID.
        $identifier = $dom->getElementsByTagNameNS('http://www.openarchives.org/OAI/2.0/', 'identifier')->item(0);
        $raw_pid = preg_replace('#.*:#', '', trim($identifier->nodeValue));
        $pid = preg_replace('/_/', ':', $raw_pid);

        // Get bits that make up the Islandora instances host plus port. Assumes that the OAI-PMH
        // endpoint is on the same host as the datastream files.
        $islandora_url_info = parse_url($this->oai_endpoint);
        if (isset($islandora_url_info['port'])) {
            $port = ':' . $islandora_url_info['port'];
        } else {
            $port = '';
        }
        $islandora_host = $islandora_url_info['scheme'] . '://' . $islandora_url_info['host'] . $port;

        // Assemble the URL of each datastream listed in the config and return on the first one
        // that is available. We loop through DSIDs because not all Islandora content models
        // require an OBJ datastream, e.g., PDF, video and audio content models.
        foreach ($this->datastreamIds as $dsid) {
            $ds_url = $islandora_host . '/islandora/object/' . $pid . '/datastream/' . $dsid . '/download';
            // HEAD is more efficient than the default GET.
            stream_context_set_default(array('http' => array('method' => 'HEAD')));
            $headers = get_headers($ds_url, 1);
            if ($dsid == $this->dsid && preg_match('#200\sOK#', $headers[0]) && isset($headers['Content-Type'])) {
                return $headers['Content-Type'];
            }
        }
    }
}
