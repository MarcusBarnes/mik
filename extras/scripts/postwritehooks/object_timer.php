<?php

/**
 * Post-write hook script for MIK that logs how long it takes to
 * process a top-level object (i.e., an object and all its child
 * objects). The log is written to the 'output_directory' defined
 * in the .ini file's [WRITER] section.
 *
 * Note that the elapsed time recorded for each object only includes
 * the time consumed by the metadata parser, file getter, and
 * writer. It does not include time consumed by post-write hook
 * scripts, since these are executed in the background and do not
 * block the processing of the next object.
 *
 * Also note that for the first entry in the log, the "seconds"
 * value is always 0.
 */

$record_key = trim($argv[1]);
$children_record_keys_string = trim($argv[2]);
$children_record_keys = explode(',', $children_record_keys_string);
$config_path = trim($argv[3]);
$config = parse_ini_file($config_path, true);

$object_timer_log_path = dirname($config['LOGGING']['path_to_log']) . DIRECTORY_SEPARATOR .
    'postwritehook_object_timer.log';
$object_timer_last_object_end_time_path = $config['FETCHER']['temp_directory'] . DIRECTORY_SEPARATOR . 'object_timer.dat';

$now = time();
$timestamp = date("Y-m-d H:i:s");
if (file_exists($object_timer_log_path)) {
	if (!file_exists($object_timer_last_object_end_time_path)) {
        $last_object_end_time = 0;
        $elapsed_time = 0;
	}
	else {
        $last_object_end_time = file_get_contents($object_timer_last_object_end_time_path);
        $elapsed_time = $now - $last_object_end_time;
    }
    file_put_contents($object_timer_last_object_end_time_path, $now, LOCK_EX);
    $log_entry = "$record_key\t$timestamp\t$elapsed_time\n";
    file_put_contents($object_timer_log_path, $log_entry, FILE_APPEND | LOCK_EX);
}
else {
	file_put_contents($object_timer_last_object_end_time_path, $now, LOCK_EX);
    file_put_contents($object_timer_log_path, "MIK record key\ttimestamp\tseconds\n", FILE_APPEND | LOCK_EX);
    $log_entry = "$record_key\t$timestamp\t0\n";
    file_put_contents($object_timer_log_path, $log_entry, FILE_APPEND | LOCK_EX);
}
