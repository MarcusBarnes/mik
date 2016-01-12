# MIK, the Move to Islandora Kit.

## Overview

The Move to Islandora Kit (MIK) converts source content files and accompanying metadata into ingest packages used by existing Islandora batch ingest modules, [Islandora Batch](https://github.com/Islandora/islandora_batch), [Islandora Newspaper Batch](https://github.com/discoverygarden/islandora_newspaper_batch), and [Islandora Book Batch](https://github.com/Islandora/islandora_book_batch). In other words, it doesn’t import objects into Islandora, it prepares content for importing into Islandora:

![MIK overview](https://dl.dropboxusercontent.com/u/1015702/linked_to/MIK_overview_%20general.png)

MIK is designed to be extensible. The base classes that convert the source metadata to XML files for importing into Islandora, and that convert the source content files into the required directory structure for importing, can be subclassed easily. MIK also uses plugins (known as "manipulators") and a set of "hook" scripts that allow functionality that can be turned off or on for specific jobs.

MIK is developed by staff at Simon Fraser University Library in support of their migration from CONTENTdm to Islandora, but its longer-term purpose is as a general toolkit for preparing content for importing content into Islandora. So MIK should really stand for "Move [stuff into] Islandora Kit."

## Documentation

We are continuing to improve our documentation, which is on the MIK [wiki](https://github.com/MarcusBarnes/mik/wiki). Please let us know if you have any quetions, suggestions or you would like to assist.

## Troubleshooting and support

If you have a question, please open a Github issue.

## Islandora content that has been prepared using MIK

* Some of Emily Carr University of Art and Design's collections in [Arca](http://arcabc.ca/) were migrated from CONTENTdm using MIK
  * [Academic Calendars](http://arcabc.ca/islandora/object/ecuad:cals)
  * [Wosk Masterworks Print Collection](arcabc.ca/islandora/object/ecuad:wosk)

More to come as Simon Fraser University completes its migration.

## Installation

It's easy. Instructions are [available on the wiki](https://github.com/MarcusBarnes/mik/wiki/Installation).

## Usage

Typical workflow is to 1) configure your toolchain (defined below) by creating an .ini file, 2) check your configuration options and then 3) run MIK to perform the conversion of your source content into Islandora ingest packages. When MIK finishes running, you can import your content into Islandora using [Islandora Batch](https://github.com/Islandora/islandora_batch), [Islandora Newspaper Batch](https://github.com/discoverygarden/islandora_newspaper_batch), or [Islandora Book Batch](https://github.com/Islandora/islandora_book_batch). 

### 1. Configure your toolchain

In a nutshell, this means create an .ini file for MIK. Details are provided on the [wiki](https://github.com/MarcusBarnes/mik/wiki).

### 2. Check your configuration

To check your configuration options, run MIK and include the `--checkconfig` option with a value of of 'snippets', 'urls', 'paths', or 'all':

* `./mik --config=foo.ini --checkconfig=snippets` checks your metadata mappings snippets for well formedness (not validity againt a schema).
* `./mik --config=foo.ini --checkconfig=urls` checks all URLs in your config file to make sure they are accessible.
* `./mik --config=foo.ini --checkconfig=paths` checks to make sure that all the paths to files and directories in your configuration file exist (except for `[LOGGING] path_to_log`, which is created as needed)
* `./mik --config=foo.ini --checkconfig=all` checks all of the above.

### 3. Convert your source content into Islandora ingest packages

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

4. Load your content into Islandora.

## Current status

Until April 2016, when our migration from CONTENTdm to Islandora will be complete, we will be working on the 0.9 release of MIK. We aim for a 1.0 release of MIK in the summer of 2016. Please note that the only differences between version 0.9 and 1.0 will be the addition of more features, automated tests, and code cleanup. Version 0.9 is already being used in production. 

So far, we have "toolchains" (complete sets of MIK fetchers, metadata parsers, file getters, etc.) for creating Islandora import packages from the following:

* CONTENTdm
  * newspapers
  * multi-file PDFs
  * single-file objects (images, audio, etc.)
  * books
* CSV
  * metadata and content files from a local filesystem for single-file objects

## Roadmap

* Version 0.9 (- April 2016)
  * Toolchain for CONTENTdm generic (non-book and non-newspaper) compound objects
  * Complete end-user and developer documentation
* Version 1.0 (May - August 2016)
  * Toolchains for CSV newspapers, books, and generic compound objects
  * Code cleanup
  * Automated tests for CSV toolchains and possibly CONTENTdm toolchains

## Development

We are focused on completing our migration in April, but once the dust settles, we welcome community development partners. Some features that would be really great to see include:

* a toolchain to migrate from DSpace to Islandora
* a toolchaing to generate Hydra import packages (yes, it's called Move to Islandora Kit but it's flexible enough to create other types of ingest packages)

MIK is designed to be extensible. If you have an idea for a useful manipulator or post-write hook script, please let us know.

README_DEV.md contains some inforation on coding standards, etc.

