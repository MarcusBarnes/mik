<?php

namespace mik\filemanipulators;

class ThumbnailFromCdm extends FileManipulator
{
    /**
     * @var array $settings - configuration settings from confugration class.
     */
    public $settings;

    /**
     * @var $alias - collection alias.
     */
    public $alias;

    /**
     * @var $wsUrl - web services URL.
     */
    public $wsUrl;

    /**
     * Create a new FileManipulator Instance
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        $this->settings = $settings['FILE_GETTER'];
        if (isset($this->settings['alias'])) {
            $this->alias = $this->settings['alias'];
        } else {
            $this->alias = null;
        }
        if (isset($this->settings['ws_url'])) {
            $this->wsUrl = $this->settings['ws_url'];
        } else {
            $this->wsUrl = null;
        }
    }

    public function getImageScalingInfo($pointer)
    {
        $alias = $this->alias;
        $wsUrl = $this->wsUrl;
        $image_info_url = $wsUrl .'dmGetImageInfo/' . $alias . '/' . $pointer . '/xml';
        $data = file_get_contents($image_info_url);
        $image_info = new \SimpleXMLElement($data);
        $image_details = array(
          'type' => (string) $image_info->type,
          'width' => (string) $image_info->width,
          'height' => (string) $image_info->height,
        );
        return $image_details;
    }
}
