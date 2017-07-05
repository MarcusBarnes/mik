<?php

/**
 * Script that clones MIK and IIPQA, generates sample input data for MIK,
 * runs MIK on the sample data, and then runs IIPQA on MIK's output.
 * Useful for automated "smoke tests".
 *
 * Usage:
 * php mik_integration_tests.php [outputdir]
 *
 * Default value of [outputdir] is '/tmp/mikintegrationtests'.
 * If the 'mik' or 'iipqa' directories do not exist in [outputdir],
 * they will be cloned from Github and installed using composer.
 *
 * The output of MIK and IIPQA will be in [outputdir]/output.
 *
 * Note that this script does not clean up after itself, i.e.,
 * none of the temporary files generated by MIK are deleted.
 */

if (count($argv) === 2) {
    $install_dir = trim($argv[1]);
}
else {
    $install_dir = '/tmp/mikintegrationtests';
}

if (!file_exists($install_dir)) {
    mkdir($install_dir);
}
chdir($install_dir);

if (file_exists($install_dir . DIRECTORY_SEPARATOR . 'output')) {
    exec("rm -rf " . $install_dir . DIRECTORY_SEPARATOR . "output");
}

// Install MIK if it's not present.
if (!file_exists($install_dir . DIRECTORY_SEPARATOR . 'mik')) {
    exec('git clone https://github.com/MarcusBarnes/mik.git mik');
    chdir($install_dir . DIRECTORY_SEPARATOR . 'mik');
    exec('composer install');
}
else {
    print "MIK is present, not reinstalling.\n";
}

// Install the Islandora Ingest Package QA Tool if it's not present.
if (!file_exists($install_dir . DIRECTORY_SEPARATOR . 'iipqa')) {
    chdir($install_dir);
    exec('git clone https://github.com/mjordan/iipqa.git iipqa');
    chdir($install_dir . DIRECTORY_SEPARATOR . 'iipqa');
    exec('composer install');
}
else {
    print "IIPQA is present, not reinstalling.\n";
}

// The output location for MIK.
$output_dir = $install_dir . DIRECTORY_SEPARATOR . 'output';
@mkdir($output_dir);
// The output location for the sample content generator script.
$generated_data_base_path = $output_dir . DIRECTORY_SEPARATOR . 'data';
@mkdir($generated_data_base_path);
// The location that all logs are moved to.
$log_base_dir = $install_dir . DIRECTORY_SEPARATOR . 'output' . DIRECTORY_SEPARATOR . 'logs';
@mkdir($log_base_dir);

$content_models = array('single', 'compound', 'newspapers', 'books');

foreach ($content_models as $cmodel) {
    chdir($install_dir . DIRECTORY_SEPARATOR . 'mik');
    $log_dir = $log_base_dir . DIRECTORY_SEPARATOR . $cmodel;
    @mkdir($log_dir);
    $generated_data_output_path = $generated_data_base_path . DIRECTORY_SEPARATOR . $cmodel;
    exec('php extras/scripts/samplecontentgenerator/generate.php -m ' .
        $cmodel . ' --id ' . $cmodel . ' ' . $generated_data_output_path);
    exec('mv ' . $generated_data_output_path . DIRECTORY_SEPARATOR . $cmodel . '.ini ' . $install_dir . DIRECTORY_SEPARATOR . 'mik');
    exec('mv ' . $generated_data_output_path . DIRECTORY_SEPARATOR . $cmodel . '_mappings.csv ' . $install_dir . DIRECTORY_SEPARATOR . 'mik');
    exec('mv ' . $generated_data_output_path . DIRECTORY_SEPARATOR . $cmodel . '_metadata.csv ' . $install_dir . DIRECTORY_SEPARATOR . 'mik');
    $output_dir = $output_dir . DIRECTORY_SEPARATOR . $cmodel . '_output';

    // Run MIK.
    print "MIK is generating output for content model '" . $cmodel . "' into " . $generated_data_output_path . '_output' . "\n";
    exec('./mik -c ' . $cmodel . '.ini');
    exec('mv ' . $generated_data_output_path . '_output/*.log ' . $log_dir);

    // Run iipqa.
    chdir($install_dir . DIRECTORY_SEPARATOR . 'iipqa');
    exec('./iipqa -s -m ' . $cmodel . ' -l ' . $log_dir . DIRECTORY_SEPARATOR . 'iipqa.log ' .
        $generated_data_output_path . '_output', $output, $ret);
    if ($ret === 0) {
        print "IIPQA checks for '$cmodel' are OK.\n";
    }
    else {
        print "IIPQA apears to have found a problem with the '$cmodel' packages.\n";
    }
}

print "Configuration files generated by this script are in the mik directory, and MIK's temporary files have not been deleted.\n";
