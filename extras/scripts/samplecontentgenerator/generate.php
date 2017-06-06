<?php

/**
 * Script to generate sample input for MIK. Usage:
 *
 * mik> php extras/scripts/samplecontentgenerator/generate.php -id [sample ID]  [output directory]
 *
 * where 'sample ID' is a string like 'issue-300' and 'output directory' is the path to where
 * the output should be saved, like '/tmp/sampleoutput'.
 *
 * Run "php extras/scripts/samplecontentgenerator/generate.php --help" for more options.
 */

require 'vendor/autoload.php';

use \Commando;
use \Twig\Twig;

$cmd = new \Commando\Command();
$cmd->option('sample_id')
    ->aka('id')
    ->require(true)
    ->describedAs('Configuration ID. A string like "issue-10".');
$cmd->option('content_model')
    ->aka('m')
    ->default('single')
    ->describedAs('An alias for groups of Islandora content models. Allowed values are ' .
        "single, compound, newspapers, books. Default is 'single'.")
    ->must(function ($cmodel) {
        $cmodels = array('single', 'newspapers', 'books', 'compound');
        return in_array($cmodel, $cmodels);
    });
$cmd->option('ini_template_name')
    ->aka('it')
    ->describedAs('.ini template filename. File must exist in the extras/scripts/samplecontentgenerator directory. ' .
      'Default is "metadata.twig".')
    ->default('ini.twig');
$cmd->option('metadata_template_name')
    ->aka('mt')
    ->describedAs('Metadata file template filename. File must exist in the extras/scripts/samplecontentgenerator directory. ' .
    'Default is "metadata.twig".')
    ->default('metadata.twig');
$cmd->option('mappings_file')
    ->aka('mf')
    ->describedAs('Mappings file filename. File must exist in the extras/scripts/samplecontentgenerator directory. ' .
    'Default is "mappings.csv".')
    ->default('mappings.csv');
$cmd->option()
    ->require(true)
    ->describedAs('Absolute or relative path to the directory to save the sample data to. Trailing slash is optional.');

$output_dir = trim(rtrim($cmd[0]));
@mkdir($output_dir);

$ini_template_dir = 'extras/scripts/samplecontentgenerator';
$ini_template_filename = 'ini.twig';
$metadata_template_filename = 'metadata.twig';

switch($cmd['content_model']) {
    case 'single':
        $class = 'CsvSingleFile';
        $metadata_values = get_metadata_values('single');
        break;
    case 'compound':
        $class = 'CsvCompound';
        $metadata_values = get_metadata_values('compound');
        break;
    case 'books':
        $class = 'CsvBooks';
        $metadata_values = get_metadata_values('books');
        break;
    case 'newspapers':
        $class = 'CsvNewspapers';
        $metadata_values = get_metadata_values('newspapers');
        break;
    default:
        echo "Unrecognized content model, exiting\n";
        exit;
}

$ini_values = array(
    'sample_id' => $cmd['sample_id'],
    'last_updated_on' => date("Y-m-d H:i:s"), 
    'class' => $class,
    'output_path' => $cmd[0], 
);


$loader = new \Twig_Loader_Filesystem($ini_template_dir);
$twig = new \Twig_Environment($loader);
$ini_content = $twig->render($cmd['ini_template_name'], $ini_values);
$ini_path = $output_dir . DIRECTORY_SEPARATOR . $cmd['sample_id'] . '.ini';

$metadata_content = $twig->render($metadata_template_filename, $metadata_values);
$metadata_path = $output_dir . DIRECTORY_SEPARATOR . $cmd['sample_id'] . '_metadata.csv';

file_put_contents($ini_path, $ini_content);
file_put_contents($metadata_path, $metadata_content);
copy('extras/scripts/samplecontentgenerator/' . $cmd['mappings_file'], $output_dir . DIRECTORY_SEPARATOR . $cmd['sample_id'] . '_mappings.csv');

generate_sample_object_input($output_dir, $cmd['sample_id'], $cmd['content_model']);

print "Sample MIK input data is in " . $output_dir . '. You will need to adjust some paths in the .ini file before using it.' . PHP_EOL;

/**
 * Populates values for use in the metadata.csv template.
 *
 * @param
 *   $cmodel string
 *      The content model alias passed in on the command line. 
 *
 * @return
 *   An array of values to pass into the template.
 */
function get_metadata_values($cmodel) {
    if ($cmodel == 'single') {
        return $metadata_values = array(
            'filename1' => 'file1.jpg',
            'filename2' => 'file2.jpg',
            'filename3' => 'file3.jpg',
            'filename4' => 'file4.jpg',
            'filename5' => 'file5.jpg',
        );
    }
    elseif ($cmodel == 'compound') {
        return $metadata_values = array(
            'filename1' => 'compoundobject1',
            'filename2' => 'compoundobject2',
            'filename3' => 'compoundobject3',
            'filename4' => 'compoundobject4',
            'filename5' => 'compoundobject5',
        );
    }
    elseif ($cmodel == 'books') {
        return $metadata_values = array(
            'filename1' => 'book1',
            'filename2' => 'book2',
            'filename3' => 'book3',
            'filename4' => 'book4',
            'filename5' => 'book5',
        );
    }
    elseif ($cmodel == 'newspapers') {
        return $metadata_values = array(
            'filename1' => '1920-06-01',
            'filename2' => '1920-06-02',
            'filename3' => '1920-06-03',
            'filename4' => '1920-06-04',
            'filename5' => '1920-06-05',
        );
    }
}

/**
 * Router function that passes off generation of input files to
 * content-model specific functions.
 *
 * @param
 *   $output_dir string
 *      The output directoty path passed in on the command line.
 *   $sample_id string
 *      The sample ID value passed in on the command line.
 *   $cmodel string
 *      The content model alias passed in on the command line. 
 */
function generate_sample_object_input($output_dir, $sample_id, $cmodel) {
    $records = file($output_dir. DIRECTORY_SEPARATOR . $sample_id . '_metadata.csv');
    // Remove the column header row.
    array_shift($records);
    switch($cmodel) {
        case 'single':
            generate_single_object_files($output_dir, $records);
            break;
        case 'compound':
            generate_compound_object_files($output_dir, $records);
            break;
        case 'books':
            generate_book_object_files($output_dir, $records);
            break;
        case 'newspapers':
            generate_newspaper_object_files($output_dir, $records);
            break;
        default:
            echo "Unrecognized content model, exiting\n";
            exit;
   }
}

/**
 * Generates sample MIK input consisting of single-file objects.
 *
 * @param
 *   $output_dir string
 *      The output directory path passed in on the command line.
 *   $records array
 *      The list of metadata records parsed from the sample metadata CSV file.
 */
function generate_single_object_files($output_dir, $records) {
    foreach($records as $row) {
        $record = explode(',', $row);
        $filename = trim($record[1], '"');
        $sample_file_path = $output_dir . DIRECTORY_SEPARATOR . $filename;
        file_put_contents($sample_file_path, "fake content"); 
    }
}

/**
 * Generates sample MIK input consisting of compound objects.
 *
 * @param
 *   $output_dir string
 *      The output directory path passed in on the command line.
 *   $records array
 *      The list of metadata records parsed from the sample metadata CSV file.
 */
function generate_compound_object_files($output_dir, $records) {
    foreach($records as $row) {
        $record = explode(',', $row);
        $dirname = trim($record[1], '"');
        $compound_object_dir = $output_dir . DIRECTORY_SEPARATOR . $dirname;
        mkdir($compound_object_dir);
        $child_filenames = array('image_01.tif', 'image_02.tif');
        foreach ($child_filenames as $filename) {
            file_put_contents($compound_object_dir . DIRECTORY_SEPARATOR . $filename, "fake content"); 
        }
    }
}

/**
 * Generates sample MIK input consisting of book objects.
 *
 * @param
 *   $output_dir string
 *      The output directory path passed in on the command line.
 *   $records array
 *      The list of metadata records parsed from the sample metadata CSV file.
 */
function generate_book_object_files($output_dir, $records) {
    foreach($records as $row) {
        $record = explode(',', $row);
        $dirname = trim($record[1], '"');
        $book_object_dir = $output_dir . DIRECTORY_SEPARATOR . $dirname;
        mkdir($book_object_dir);
        $page_filenames = array('page-01.tif', 'page-02.tif', 'page-03.tif');
        foreach ($page_filenames as $filename) {
            file_put_contents($book_object_dir . DIRECTORY_SEPARATOR . $filename, "fake content"); 
        }
    }
}

/**
 * Generates sample MIK input consisting of newspaper objects.
 *
 * @param
 *   $output_dir string
 *      The output directory path passed in on the command line.
 *   $records array
 *      The list of metadata records parsed from the sample metadata CSV file.
 */
function generate_newspaper_object_files($output_dir, $records) {
    foreach($records as $row) {
        $record = explode(',', $row);
        $dirname = trim($record[1], '"');
        $issue_object_dir = $output_dir . DIRECTORY_SEPARATOR . $dirname;
        mkdir($issue_object_dir);
        $page_filenames = array('page-01.tif', 'page-02.tif', 'page-03.tif');
        foreach ($page_filenames as $filename) {
            file_put_contents($issue_object_dir . DIRECTORY_SEPARATOR . $filename, "fake content"); 
        }
    }
}
