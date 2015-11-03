<?php
/** 
 * Creates a file suitable for use with the SpecifieSet fetcher manipulator:
 * https://github.com/MarcusBarnes/mik/wiki/Fetcher-manipulator:-SpecificSet
 *
 * The first argument is the path to problem_records log file and the second argument 
 * is the path to the file for output. 
 * This script can be used via command line or via an HTTP GET request.
 *
 * Example usage from the command line:
 * 
 * > php specificsetfromproblemrecords.php /path/to/problem_records.log /path/to/file/to/use/for/specificset.txt
 *
 * Example usage using a HTTP GET requests:
 * http://yourhost/specificsetfromproblemrecords.php?inputfilepath=/path/to/problem/records.log&outputfilepath=/path/to/file/to/use/specificset.txt
 */

if (PHP_SAPI === 'cli') {
    $problemRecordsLogPath = $argv[1];
    $specificSetFilePath = $argv[2];
} else {
    $problemRecordsLogPath = realpath($_GET['inputfilepath']);
    $specificSetFilePath = realpath($_GET['outputfilepath']);
}

if (!is_file($problemRecordsLogPath)) {

    echo "There was a problem with the path to the problem_records log file: " . $problemRecordsLogPath; 
	exit();
}

$fileInHandler = fopen($problemRecordsLogPath, 'r') or die("Unable to read the problem_records log." . PHP_EOL);;
$fileOutHandler = fopen($specificSetFilePath, 'w') or die("Unable to create output file." . PHP_EOL);
if ($fileInHandler) {
    while (($line = fgets($fileInHandler)) !== false) {
       $result = extractRecordId($line);
       if ($result != false) {
           fwrite($fileOutHandler, $result . "\n");       
       }
    }

    fclose($fileInHandler);
    fclose($fileOutHandler);
}

echo "The input file for the SpecificSet fetcher manipulator has been written to: " . $specificSetFilePath . PHP_EOL;


function extractRecordId($line){
    // Create RegEx for handling lines like this.  If they don't have ProblemRecords.ERROR scip the line.
    // [2015-10-30 14:51:57] ProblemRecords.ERROR: 4 [] []
    $pattern = '/ProblemRecords.ERROR:\s([0-9]+)\s\[\]/';
    preg_match($pattern, $line, $matches);
    
    if(isset($matches[1])){
        // record_key from the RegEx.
    	return $matches[1];
    } else {
 
    	return false;
    }
}