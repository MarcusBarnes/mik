<?php

namespace mik\filegetters;

class CsvSingleFile extends FileGetter
{
    /**
     * @var array $settings - configuration settings from confugration class.
     */
    public $settings;

    /**
     * Create a new CSV Fetcher Instance
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        $this->settings = $settings['FILE_GETTER'];
        $this->input_directory = $this->settings['input_directory'];
        $this->file_name_field = $this->settings['file_name_field'];
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
        $objectInfo = $this->fetcher->getItemInfo($record_key);
        $file_name_field = $this->file_name_field;
        $file_name = $objectInfo->$file_name_field;
        return $this->input_directory . DIRECTORY_SEPARATOR . $file_name;
    }
}
