<?php
// Simple script to tail reverb.log to stdout
$logFile = __DIR__ . '/reverb.log';
if (!file_exists($logFile)) {
    touch($logFile);
}

// Ensure the start.php script redirected to this file
// exec("php artisan reverb:start --host=0.0.0.0 --port=8081 > reverb.log 2>&1 &");
// Wait, I changed start.php to use proc_open but I didn't actually write to the log file in proc_open.
// Let's fix start.php to just use exec with redirection again but with a monitor.

$handle = fopen($logFile, 'r');
fseek($handle, 0, SEEK_END);

while (true) {
    $line = fgets($handle);
    if ($line !== false) {
        echo "[Reverb] " . $line;
    }
    else {
        usleep(100000); // 100ms
        clearstatcache();
    }
}
?>
