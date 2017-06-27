<?php

namespace mik\fetchers;

/**
 * Class FileFetcher
 * @package mik\fetchers
 */
class FileFetcher extends Fetcher
{

    /**
     * The directory to start in.
     *
     * @var string
     */
    private $source_directory;

    /**
     * Regular expression to filter files against.
     *
     * @var string
     */
    private $source_file_regex;

    /**
     * Whether to recurse into any found sub-directories.
     *
     * @var boolean
     */
    private $recurse_directories;

    /**
     * Array of objects collected.
     *
     * Keys are 'filename' and 'fullpath'
     *
     * @var array
     */
    private $file_listing = null;

    /**
     * Is this a Mac, so we disregard .DS_Store files.
     *
     * @var bool
     */
    private $OS_Mac = false;

    /**
     * @inheritDoc
     */
    public function __construct(array $settings)
    {
        parent::__construct($settings);
        $this->parseSettings($settings);
        if (strtolower(substr(PHP_OS, 0, 6)) == "darwin") {
            $this->OS_Mac = true;
        }
    }


    /**
     * @inheritDoc
     */
    public function getNumRecs()
    {
        $files = $this->getFileList();
        return count($files);
    }

    /**
     * @inheritDoc
     */
    public function getItemInfo($recordKey)
    {
        $files = $this->getFileList();
        if (!array_key_exists($recordKey, $files)) {
            $msg = sprintf("Key %s not found in file listing.", $recordKey);
            $this->log->error($msg);
            throw new \Exception($msg);
        }
        return $files[$recordKey];
    }

    /**
     * @inheritDoc
     */
    public function getRecords($limit = null)
    {
        $files = $this->getFileList();
        if (!is_null($limit) && is_numeric($limit)) {
            return array_slice($files, 0, $limit);
        }
        return $files;
    }

    /**
     * Collect files into $this->file_listing.
     *
     * @var boolean $force
     *   Recreating listing even if it exists.
     * @return array
     *   Array of DirectoryIterators.
     */
    private function getFileList($force = false)
    {
        if (is_null($this->file_listing) || $force) {
            $files = $this->iterateDirectory($this->source_directory);
            $this->file_listing = $files;
        }
        return $this->file_listing;
    }

    /**
     * Iterate over a set of directories.
     *
     * @param $directory
     *   Directory to work in.
     * @return array
     *   New list of files.
     * @throws \Exception
     *   Finds duplicate key
     */
    private function iterateDirectory($directory)
    {
        $files = [];
        $this->log->debug("iterateDirectory ({$directory})");
        if ($this->recurse_directories) {
            $RDIterator = new \RecursiveDirectoryIterator($directory,
                \FilesystemIterator::KEY_AS_PATHNAME |
                \FilesystemIterator::CURRENT_AS_FILEINFO |
                \FilesystemIterator::SKIP_DOTS
            );
            $iterator = new \RecursiveIteratorIterator($RDIterator);
        } else {
            $iterator = new \DirectoryIterator($directory);
        }
        if (!is_null($this->source_file_regex) && !empty($this->source_file_regex)) {
            $iterator = new \RegexIterator($iterator, $this->source_file_regex);
        }
        foreach ($iterator as $name => $fileInfo) {
            if ($fileInfo->isFile() && (!$this->OS_Mac || strtoupper($fileInfo->getFilename()) != '.DS_STORE')) {
                $this->log->debug("Adding file " . $fileInfo->getPathname() . " to listing.");
                if (array_key_exists($fileInfo->getPathname(), $files)) {
                    $this->log->error("Duplicate file/path found -> " . $fileInfo->getPathname());
                    throw new \Exception("Duplicate file/path found -> " . $fileInfo->getPathname());
                }
                $files[$fileInfo->getPathname()] = ['filename' => $fileInfo->getFilename(), 'fullpath' => $fileInfo->getPathname() ];
            }
        }
        return $files;
    }

    /**
     * Parse for required settings.
     *
     * @param array $settings
     * @throws \Exception
     *
     */
    private function parseSettings(array $settings)
    {
        if (isset($settings['FETCHER']['source_directory'])) {
            $sourceDirectory = realpath($settings['FETCHER']['source_directory']);
        } else {
            $this->log->error("FileFetcher requires a 'source_directory' to be set.");
            throw new \Exception("FileFetcher requires a 'source_directory' to be set.");
        }
        if (!file_exists($sourceDirectory) || !is_dir($sourceDirectory)) {
            $this->log->error('FileFetcher.source_directory cannot be found or is not a directory.');
            throw new \Exception('FileFetcher.source_directory cannot be found or is not a directory.');
        }
        $this->source_directory = $sourceDirectory;
        if (!isset($settings['FETCHER']['source_file_regex'])) {
            $this->log->info("No source_file_regex specified, fetching all files.");
            $this->source_file_regex = null;
        } else if (@preg_match($settings['FETCHER']['source_file_regex'], 'Wubalubalubdub') === false) {
            $this->log->error("FileFetcher.source_file_regex pattern fails to compile.");
            throw new \Exception("FileFetcher.source_file_regex pattern fails to compile.");
        } else {
            $this->source_file_regex = $settings['FETCHER']['source_file_regex'];
        }
        if (!isset($settings['FETCHER']['recurse_directories']) || !is_bool($settings['FETCHER']['recurse_directories'])) {
            $this->recurse_directories = false;
        } else {
            $this->recurse_directories = (bool)$settings['FETCHER']['recurse_directories'];
        }
        if ($this->recurse_directories) {
            $this->log->info("Recurse directories set to TRUE");
        }

    }
}