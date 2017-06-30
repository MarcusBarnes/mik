<?php

namespace mik\fetchermanipulators;

/**
 * @file
 * FetcherManipulator Abstract class.
 */

class FetcherManipulator
{

    /**
     * @var boolean $onWindows - Whether or not we are running on Windows.
     */
    public $onWindows;

    /**
     * Create a new FetcherManipulator instance.
     *
     * @param array $settings configuration settings.
     */
    public function __construct($settings = null)
    {
        // Determine whether we're running on Windows.
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->onWindows = true;
        } else {
            $this->onWindows = false;
        }
    }
}
