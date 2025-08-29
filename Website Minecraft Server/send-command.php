<?php
require __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';
require __DIR__ . '/rcon.php';

$cmd = trim($_POST['cmd'] ?? '');
if ($cmd === '') {
    http_response_code(400);
    echo "No command.";
    exit;
}

$rcon = new SimpleRcon(RCON_HOST, RCON_PORT, RCON_PASS, 3);
if (!$rcon->connect()) {
    http_response_code(500);
    echo "RCON connect failed.";
    exit;
}

$out = $rcon->send($cmd);
$rcon->close();

echo $out !== false ? $out : "Command sent.";
