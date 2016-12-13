# Development README

## Autoloading Classes

When creating classes (or modifying the namespaces of existing classes), you may need to regenerate the autoload files. To do this, run the following from the command-line:

`composer dump-autoload`

`composer update`

## Coding Standards

Use the PSR-2 coding standard.  You can check your work using PHP Code Sniffer by issuing the following command from within the mik directory:

`vendor/bin/phpcs --standard=PSR2 yourfile.php`

## Running tests

From within the mik directory, `cd` into the 'tests' directory and run `phpunit`.
