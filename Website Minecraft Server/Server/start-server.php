<?php
require __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';

// Ensure log file exists
if (!is_file(LOG_FILE)) {
    @file_put_contents(LOG_FILE, "=== New log started " . date('Y-m-d H:i:s') . " ===\r\n");
}

chdir(SERVER_DIR);

// Windows background start, redirect stdout/stderr to console.log
// We wrap with cmd /c so redirection applies correctly.
$cmd = 'start "" /B cmd /c "java ' . JAVA_ARGS . ' -jar "' . JAR_FILE . '" nogui >> "' . LOG_FILE . '" 2>&1"';

shell_exec($cmd);

echo "Server start command sent.\nIf nothing happens, check JAVA in PATH and jar name.";
