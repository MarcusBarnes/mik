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
     * Configuration settings from configuration class.
     * @var array
     */
    public $settings;

    /**
     * Whether to use a static cache (if available), should be disabled for PHPUnit tests.
     * @var boolean
     */
    protected $use_cache = true;

    /**
     * Create a new Fetcher Instance
     * @param array $settings configuration settings.
     */
    public function __construct($settings)
    {
        $this->settings = $settings['FILE_GETTER'];
        if (isset($this->settings['use_cache'])) {
            $this->use_cache = $this->settings['use_cache'];
        }
    }
}
