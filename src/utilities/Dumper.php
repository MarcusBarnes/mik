<?php

namespace mik\utilities;

use mik\exceptions\MikErrorException;

/**
 * Utility to dump the value of a variable out to a log.
 */
class Dumper
{
    /**
     * Utility function to dump a variable to a log.
     *
     * @param $variable mixed
     *   The variable to dump.
     * @param $label string
     *   A label to dump along with the value. Defaults to ''.
     * @param $dest string
     *   The full path to a log file. Defaults to the system's
     *   temp directory with a filename of 'mik_dumper.txt'.
     */
    public function dump($variable, $label = null, $destination = null)
    {
        if (is_null($destination)) {
            $destination = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "mik_dumper.txt";
        }
        $value = var_export($variable, true) . "\n";
        if (!is_null($label)) {
            $value = $label . ":" . PHP_EOL . $value;
        }
        file_put_contents($destination, $value, FILE_APPEND);
    }
}
