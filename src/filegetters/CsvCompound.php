<?php

namespace mik\filegetters;

class CsvCompound extends FileGetter
{
    /**
     * @var array $settings - configuration settings from confugration class.
     */
    public $settings;

    /**
     * Create a new CSV Fetcher instance.
     *
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        $this->settings = $settings['FILE_GETTER'];
        $this->input_directory = $this->settings['input_directory'];
        $this->compound_directory_field = $this->settings['compound_directory_field'];
        $this->fetcher = new \mik\fetchers\Csv($settings);
    }

    /**
     * Return a list of absolute filepaths to the pages of an issue.
     *
     * @param string $record_key
     *
     * @return array
     *    An array of absolute paths to the object's child files.
     */
    public function getChildren($record_key)
    {
        $children_paths = array();
        $cpd_input_path = $this->getCpdSourcePath($record_key);
        foreach (glob($cpd_input_path . DIRECTORY_SEPARATOR . "*") as $path) {
            $children_paths[] = $path;
        }
        return $children_paths;
    }

    /**
     * Return the absolute filepath to the compound object directory.
     *
     * @param $record_key
     *
     * @return string
     *    The absolute path to the compound object directory.
     */
    public function getCpdSourcePath($record_key)
    {
        // Get the path to the issue.
        $item_info = $this->fetcher->getItemInfo($record_key);
        $cpd_input_directory = $this->input_directory . DIRECTORY_SEPARATOR .
            $item_info->{$this->compound_directory_field};
        return $cpd_input_directory;
    }

}
