<?php

namespace mik\filegetters;

class SimpleArchive extends FileGetter
{
    /**
     * Create a new Filesystem_Subdirectory Fetcher Instance
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        parent::__construct($settings);
        $this->input_directory = $this->settings['input_directory'];
        $this->filetype = $this->settings['filetype'];
        $fetcherClass = 'mik\\fetchers\\' . $settings['FETCHER']['class'];
        $this->fetcher = new $fetcherClass($settings);
    }

    /**
     * Placeholder method needed because it's called in the main loop in mik.
     * PDF documents don't have any children.
     */
    public function getChildren($pointer)
    {
        return array();
    }

    /**
     * @param string $record_key
     *
     * @return string $path_to_file
     */
    public function getFilePath($record_key)
    {
        var_dump($this->settings);
        $objectInfo = $this->fetcher->getItemInfo($record_key);
        $contents_manifest_path = $this->input_directory . DIRECTORY_SEPARATOR . $record_key .
          DIRECTORY_SEPARATOR . 'contents';
        $contents_manifest_content = file($contents_manifest_path);
        $contents = array();
        // Searches the 'contents' for a file matching the configured filetype.
        foreach ($contents_manifest_content as $manifest_file) {
            list($contents[], $extra) = preg_split('/\t/', $manifest_file);
        }
        $filetype = $this->filetype;
        
        foreach ($contents as $file) {
            $file_split = explode(".", $file);
            if (end($file_split) == $filetype) {
                $payload_filename = $file;
            }
        }
        
 //       list($payload_filename, $extra) = preg_split('/\t/', $contents_manifest_content[0]);

        return $this->input_directory . DIRECTORY_SEPARATOR . $record_key . DIRECTORY_SEPARATOR . $payload_filename;
    }
}
