<?php
require __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';

header('Content-Type: text/plain; charset=utf-8');

$linesToShow = 300;
$file = LOG_FILE;

if (!is_file($file)) {
    echo "(no log file yet)";
    exit;
}

// Efficient tail: read from end in chunks
$fp = fopen($file, 'rb');
if (!$fp) { echo "(unable to read log)"; exit; }

$buffer = '';
$chunkSize = 8192;
$pos = -1;
$lineCount = 0;

fseek($fp, 0, SEEK_END);
$filesize = ftell($fp);

while ($filesize > 0 && $lineCount <= $linesToShow) {
    $readSize = ($filesize >= $chunkSize) ? $chunkSize : $filesize;
    $filesize -= $readSize;
    fseek($fp, $filesize);
    $chunk = fread($fp, $readSize);
    $buffer = $chunk . $buffer;
    $lineCount = substr_count($buffer, "\n");
    if ($filesize === 0) break;
}
fclose($fp);

$lines = explode("\n", $buffer);
if (count($lines) > $linesToShow) {
    $lines = array_slice($lines, -$linesToShow);
}
echo implode("\n", $lines);
