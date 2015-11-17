<?php

$record_key = trim($argv[1]);

function shutdown() {
  file_put_contents('/tmp/shutdown.log', "Task 1 for record key $record_key has shut down\n", FILE_APPEND);
}
register_shutdown_function('shutdown');

file_put_contents('/tmp/task1.txt', "Record key from task1.php: $record_key\n", FILE_APPEND);
