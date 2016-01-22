PHPUnit test to determine if FITS is works on your system. Only necessary to run if
you are using the generate_fits.php post-write hook script.

Before you run phpunit, you must modify phpunit.xml to use the path to fits.sh (*nix)
or fits.bat (Windows) on your system. Once you have done that, run phpunit within
this directory. This test is not run if you run the general MIK tests in this directory's
parent directory.
