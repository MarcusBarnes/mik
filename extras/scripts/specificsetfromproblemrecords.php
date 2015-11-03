<?php
// Provide path to problem_records log file 
// Output file to specificed output path suitable for 
//via command line or  via GET request.
if (PHP_SAPI === 'cli') {
    $problemRecordsLogPath = $argv[1];
    $specificSetFilePath = $argv[2];
} else {
    $problemRecordsLogPath = $_GET['inputfilepath'];
    $specificSetFilePath = $_GET['outputfilepath'];
}

echo "Process the log file: " . PHP_EOL . $problemRecordsLogPath . PHP_EOL;
echo  "and output the recods_keys in a format suitalbe for the SpecificSet fetcher manipulator in this file:" . PHP_EOL;
echo $specificSetFilePath; 

// Create RegEx for handling lines like this.  If they don't have ProblemRecords.ERROR scip the line.
// [2015-10-30 14:51:57] ProblemRecords.ERROR: 4 [] []

//$fileInHandler = fopen($problemRecordsPath,'r');
//$fileOutHandler = fopen($specificSetFilePath,'w');
/*
while (!feof($fileInHandler)) {
    $line = fgets($fileInHandler);

	echo count($line) . PHP_EOL;    
 }
 */
 //fclose($fw);
 //fclose($fh);