# MIK, the Move to Islandora Kit.

## Overview

The Move to Islandora Kit (MIK) converts source content files and accompanying metadata into ingest packages used by existing Islandora batch ingest modules, [Islandora Batch](https://github.com/Islandora/islandora_batch), [Islandora Newspaper Batch](https://github.com/discoverygarden/islandora_newspaper_batch), and [Islandora Book Batch](https://github.com/Islandora/islandora_book_batch). In other words, it doesn’t import objects into Islandora, it prepares content for importing into Islandora.

MIK is designed to be extensible. The base classes that convert from the source metadata to XML files for importing into Islandora, and that convert the source content files into the required directory structure for importing, can be subclassed easily. We'll be documenting how to do this in the near future.

MIK is developed by staff at Simon Fraser University Library in support of their migration from CONTENTdm to Islandora, but its longer-term purpose is as a general toolkit for preparing content for importing content into Islandora.

## Requirements

PHP 5.5.0 or higher.

## Installation

1. Clone this git repo.
2. Change into the resulting directory and install Composer by running the following command: ```curl -sS https://getcomposer.org/installer | php```
3. Run the following command: ```php composer.phar install```

## Usage

Configure your conversion job by creating an .ini file, and then run:

```mik --config=foo.ini```

## Current status

We're focusing on converting CONTENTdm collections into ingest packages for newspapers. After that, we'll move on to CONTENTdm-to-single-file Islandora objects, then after that, creating ingest packages from CSV metadata files + local filesystem content.

## Development

See README_DEV.md for coding standards, etc.

## Troubleshooting and support

If you have a question, please open an issue.
