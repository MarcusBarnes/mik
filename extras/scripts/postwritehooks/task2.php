<?php

$record_key = trim($argv[1]);

function shutdown() {
  file_put_contents('/tmp/shutdown.log', "Task 2 for record key $record_key has shut down\n", FILE_APPEND);
}
register_shutdown_function('shutdown');

file_put_contents('/tmp/task2.txt', "Record key from task2.php: $record_key\n", FILE_APPEND);
