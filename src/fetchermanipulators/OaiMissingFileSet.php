<?php

namespace mik\fetchermanipulators;
use League\CLImate\CLImate;
use \Monolog\Logger;

/**
 * @file
 * Fetcher manipulator that filters out record keys that have
 * corresponding payload files in the output directory. In other
 * words, this manipulator limits the MIK job to records that
 * do not have a file in the output directory. Useful for resuming
 * a failed job, etc. Applies to OAI-PMH toolchains.
 *
 * MIK's --limit parameter applies as if this manipulator were
 * absent. If the identifiers listed in the input file match
 * records retrieved within the limit, they are included in the
 * set processed by MIK; if not, they are excluded from the set
 * processed by MIK. Since the speicifc set is by definition a
 * limit on how many records are processed, the --limit parameter
 * is not usually used in conjuction with this manipulator.
 */

class OaiMissingFileSet extends FetcherManipulator
{
    /**
     * Create a new OaiMissingFileSet fetchermanipulator Instance.
     *
     * @param array $settings
     *   All of the settings from the .ini file.
     *
     * @param array $manipulator_settings
     *   This manipulator takes no parameters, this is a placeholder.
     */
    public function __construct($settings, $manipulator_settings)
    {
        $this->settings = $settings;
        $this->outputDirectory = $this->settings['WRITER']['output_directory'];

        // To get the value of $onWindows.
        parent::__construct($settings);
        // Set up logger.
        $this->pathToLog = $this->settings['LOGGING']['path_to_manipulator_log'];
        $this->log = new \Monolog\Logger('OaiMissingFileSet');
        $this->logStreamHandler = new \Monolog\Handler\StreamHandler($this->pathToLog,
            Logger::INFO);
        $this->log->pushHandler($this->logStreamHandler);
    }

    /**
     * Selects a specific subset of records.
     *
     * @param array $all_records
     *   All of the records from the fetcher.
     * @return array $filtered_records
     *   An array of records that have corresponding files in the output directory.
     */
    public function manipulate($all_records)
    {
        $numRecs = count($all_records);
        // Instantiate the progress bar if we're not running on Windows.
        if (!$this->onWindows) {
            $climate = new \League\CLImate\CLImate;
            $progress = $climate->progress()->total($numRecs);
        }

        $record_keys_with_files = $this->getRecordKeysWithFiles();
        $num_recs_with_files = count($record_keys_with_files);
        if ($num_recs_with_files == $numRecs) {
            return array();
        }
        if ($num_recs_with_files == 0) {
            return $all_records;
        } else {
            $num_missing_files = count($all_records) - $num_recs_with_files;
            echo "The OaiMissingFileSet fetcher manipulator detects $num_missing_files missing files. MIK will retrieve them now.\n";
        }

        $record_num = 0;
        $filtered_records = array();
        foreach ($all_records as $record) {
            if (!in_array($record->key, $record_keys_with_files)) {
                $filtered_records[] = $record;
            }
        }

        return $filtered_records;
    }

    /**
     * Populates a list of object record keys from the files present
     * in the output directory.
     *
     * @return array
     *   The list of record keys (i.e., OAI-PMH identifiers) that
     *   have a corresponding file in the output directory.
     */
    public function getRecordKeysWithFiles()
    {
        $record_keys_with_files = array();
        foreach ($this->getFileList() as &$file) {
            $filename = pathinfo($file, PATHINFO_FILENAME);
            $record_keys_with_files[] = $this->denormalizeFilename($filename);
	}

        return $record_keys_with_files;
    }

    /**
     * Reads the output directory and returns a list of files that do not
     * end in .xml or .log.
     *
     * @return array
     *   An array of absolutute file paths.
     */
    public function getFileList()
    {
        $file_list = array();
        $filtered_file_list = array();
        $pattern = $this->outputDirectory . DIRECTORY_SEPARATOR . "*";
        $file_list = glob($pattern);
        foreach ($file_list as $file_path) {
            if (!preg_match('/\.(xml|log)$/', $file_path) ) {
                $filtered_file_list[] = $file_path;
            }
        }

        return $filtered_file_list;
    }

    /**
     * Names of files retrieved in OAI-PMH toolchaines are normalized
     * to convert %3A (:) into underscores (_). This function converts
     * them back so that filenames will match OAI-PMH identifiers.
     */
    public function denormalizeFilename($string)
    {
        $string = preg_replace('/_/', ':', $string);
        $string = urlencode($string);

        return $string;
    }

}
