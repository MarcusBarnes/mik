# MIK, the Move to Islandora Kit.

## Overview

The Move to Islandora Kit (MIK) converts source content files and accompanying metadata into ingest packages used by existing Islandora batch ingest modules, [Islandora Batch](https://github.com/Islandora/islandora_batch), [Islandora Newspaper Batch](https://github.com/discoverygarden/islandora_newspaper_batch), and [Islandora Book Batch](https://github.com/Islandora/islandora_book_batch). In other words, it doesn’t import objects into Islandora, it prepares content for importing into Islandora.

MIK is designed to be extensible. The base classes that convert from the source metadata to XML files for importing into Islandora, and that convert the source content files into the required directory structure for importing, can be subclassed easily. We'll be documenting how to do this in the near future.

MIK is developed by staff at Simon Fraser University Library in support of their migration from CONTENTdm to Islandora, but its longer-term purpose is as a general toolkit for preparing content for importing content into Islandora.

Additional information is available on the MIK [wiki](https://github.com/MarcusBarnes/mik/wiki).

## Requirements

PHP 5.5.0 or higher.

## Installation

### Linux/OS X

1. Clone this git repo.
2. Change into the resulting directory and install Composer by running the following command: `curl -sS https://getcomposer.org/installer | php`
3. Run the following command: `php composer.phar install`

### Windows
1. Install PHP and ensure that you can run `php -v` from a command prompt (you will need to adjust your PATH so it can find php.exe).
2. Make sure the required extensions are enabled by adding these lines to your php.ini:

  ```
  extension_dir = "ext"
  extension=php_openssl.dll
  extension=php_mbstring.dll
  ```
3. Clone this git repo (or download the zip if you don't have git).
4. Install composer using "Composer-Setup.exe" linked from the [Composer website](https://getcomposer.org/doc/00-intro.md).
5. In the mik directory, run `composer install`

## Usage

Typical workflow is to 1) configure your toolchain (defined below) by creating an .ini file, 2) check your configuration options and then 3) run MIK to perform the conversion of your source content into Islandora ingest packages.

### Configure your toolchain

In a nutshell, this means create an .ini file for MIK. Details are provided on the [wiki](https://github.com/MarcusBarnes/mik/wiki). 

### Check your configuration

To check your configuration options, run MIK and include the `--checkconfig` option with a value of of 'snippets', 'urls', 'paths', or 'all':

* `./mik --config=foo.ini --checkconfig=snippets` checks your metadata mappings snippets for well formedness (not validity againt a schema).
* `./mik --config=foo.ini --checkconfig=urls` checks all URLs in your config file to make sure they are accessible.
* `./mik --config=foo.ini --checkconfig=paths` checks to make sure that all the paths to files and directories in your configuration file exist (except for `[LOGGING] path_to_log`, which is created as needed)
* `./mik --config=foo.ini --checkconfig=all` checks all of the above.

### Convert your source content into Islandora ingest packages

Once you have checked your configuration options, you can run MIK to perform the data conversion:

```./mik --config=foo.ini```

On Windows, you'll need to run:

```php mik --config=foo.ini```

The `--config` option is required, but you can also add a `--limit` option if you only want to create a specific number of import packages. This option is useful for testing. For example:

```./mik --config=foo.ini --limit=10```

Once MIK starts running, it will display its progress:

```
./mik --config=foo.ini
Creating 10 Islandora ingest packages. Please be patient.
===================================================>                          56%
```

and when finished will tell you where your ingest packages have been saved and where your log file is.

## Current status

So far, we have "toolchains" (complete sets of MIK metadata parsers, file getters, etc.) for converting the following into Islandora import packages:

* CONTENTdm newspapers
* CONTENTdm multi-file PDFs
* CONTENTdm single-file objects
* CSV metadata and content files from a local filesystem

We are now focusing on toolchains for remaining CONTENTdm object types.

## Development

See README_DEV.md for coding standards, etc.

## Troubleshooting and support

If you have a question, please open an issue.
