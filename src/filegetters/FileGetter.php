<?php

namespace mik\filegetters;

/**
 * FileGetter (abstract):
 *    Methods related to getting actual file contents.
 *
 *    Extend this abstract class with for specific implemenations.
 *    For example, see filegetters/CdmNewspapers.php.
 *
 *    Note that methods marked as abstract must be defined in 
 *    the extending class.
 */
abstract class FileGetter
{
    /**
     * @var array $settings - configuration settings from confugration class.
     */
    public $settings;
      
    /**
     * Create a new Fetcher Instance
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        $this->settings = $settings['FILE_GETTER'];
    }

}
