<?php
// get-players-query.php — robust UDP Query (full stat) with split-packet reassembly
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
ob_start();

$resp = ['ok'=>false,'online'=>0,'max'=>0,'players'=>[],'message'=>''];

try {
    $host = defined('QUERY_HOST') ? QUERY_HOST : '127.0.0.1';
    $port = defined('QUERY_PORT') ? (int)QUERY_PORT : 25565;
    $timeoutSec = 2;   // handshake / first packet timeout
    $drainMs    = 400; // how long to keep collecting extra split packets

    // Helper: read one UDP datagram (non-blocking, with small wait)
    $readDatagram = function($sock, $waitMs) {
        $r = [$sock]; $w = null; $e = null;
        $sec = intdiv($waitMs, 1000);
        $usec = ($waitMs % 1000) * 1000;
        if (stream_select($r, $w, $e, $sec, $usec) > 0) {
            return fread($sock, 65535);
        }
        return false;
    };

    // Open UDP socket
    $sock = @stream_socket_client("udp://{$host}:{$port}", $errno, $errstr, $timeoutSec);
    if (!$sock) {
        $resp['message'] = "UDP connect failed: $errstr ($errno)";
        ob_end_clean(); echo json_encode($resp); exit;
    }
    stream_set_blocking($sock, false);

    // ---- Handshake (0x09) ----
    $sess = random_int(1, 0x7fffffff);
    $handshake = pack('C2CN', 0xFE, 0xFD, 0x09, $sess);
    fwrite($sock, $handshake);

    $pkt = $readDatagram($sock, $timeoutSec * 1000);
    if (!is_string($pkt) || strlen($pkt) < 5 || ord($pkt[0]) !== 0x09) {
        fclose($sock);
        $resp['message'] = 'No/invalid handshake response.';
        ob_end_clean(); echo json_encode($resp); exit;
    }

    // token is ASCII integer string after 1(type)+4(session), terminated by NUL
    $nulPos = strpos($pkt, "\x00", 5);
    $tokenStr = $nulPos === false ? substr($pkt, 5) : substr($pkt, 5, $nulPos - 5);
    $tokenStr = trim($tokenStr);
    if ($tokenStr === '' || !preg_match('/^-?\d+$/', $tokenStr)) {
        fclose($sock);
        $resp['message'] = 'Invalid token.';
        ob_end_clean(); echo json_encode($resp); exit;
    }
    $token = (int)$tokenStr;

    // ---- Full stat request (0x00) ----
    $fullReq = pack('C2CN', 0xFE, 0xFD, 0x00, $sess) . pack('N', $token) . "\x00\x00\x00\x00";
    fwrite($sock, $fullReq);

    // Collect one or more datagrams (split packets)
    $start = microtime(true);
    $chunks = [];

    // First packet (wait up to timeoutSec)
    $first = $readDatagram($sock, $timeoutSec * 1000);
    if (!is_string($first) || strlen($first) < 5 || ord($first[0]) !== 0x00) {
        fclose($sock);
        $resp['message'] = 'No/invalid stat response.';
        ob_end_clean(); echo json_encode($resp); exit;
    }
    $chunks[] = $first;

    // Drain additional split packets for a short window
    while ((microtime(true) - $start) * 1000 < $drainMs) {
        $more = $readDatagram($sock, 50);
        if (!is_string($more) || strlen($more) < 5) break;
        if (ord($more[0]) === 0x00) {
            $chunks[] = $more;
        }
    }
    fclose($sock);

    // Reassemble: strip the 5-byte header (type + session) from each
    $payload = '';
    foreach ($chunks as $c) {
        $payload .= substr($c, 5);
    }

    // Known quirk: some implementations include an 11-byte padding before K/V
    // Try to locate the key/value start by finding "hostname\0"
    $kvStart = strpos($payload, "hostname\x00");
    if ($kvStart === false) {
        // If not found, fall back to beginning
        $kvStart = 0;
    } else {
        // Step back one if the byte before is NUL (usual case)
        if ($kvStart > 0 && $payload[$kvStart - 1] === "\x00") $kvStart -= 1;
    }

    // Locate the player section marker
    // Standard marker: \x00\x00\x01player_\x00\x00
    $marker = "\x00\x00\x01player_\x00\x00";
    $markerPos = strpos($payload, $marker);
    if ($markerPos === false) {
        // Some servers vary slightly; loosen search
        // Find "player_" and then scan forward through NULs
        $p = strpos($payload, "player_");
        if ($p !== false) {
            // try to align to the expected marker neighborhood
            $markerPos = $p - 3;
            if ($markerPos < 0) $markerPos = $p;
        }
    }

    // Split into K/V section and player names section (if present)
    $kvRaw = $markerPos !== false ? substr($payload, $kvStart, $markerPos - $kvStart) : substr($payload, $kvStart);
    $playersRaw = $markerPos !== false ? substr($payload, $markerPos + strlen($marker)) : '';

    // Parse key/value pairs (NUL-separated, pairs of key,value)
    $kv = [];
    $parts = explode("\x00", $kvRaw);
    for ($i = 0; $i + 1 < count($parts); $i += 2) {
        $k = $parts[$i];
        $v = $parts[$i+1];
        if ($k === '' && $v === '') break; // sentinel
        if ($k !== '') $kv[$k] = $v;
    }

    if (isset($kv['numplayers'])) $resp['online'] = (int)$kv['numplayers'];
    if (isset($kv['maxplayers'])) $resp['max']    = (int)$kv['maxplayers'];

    // Parse players list (NUL-separated list ending with NUL NUL)
    if ($playersRaw !== '') {
        $names = explode("\x00", $playersRaw);
        $names = array_values(array_filter(array_map('trim', $names), function($n){ return $n !== ''; }));
        $resp['players'] = $names;
        if (!$resp['online']) $resp['online'] = count($names);
    }

    $resp['ok'] = true;
    ob_end_clean(); echo json_encode($resp);
} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['ok'=>false,'message'=>'Server error: '.$e->getMessage()]);
}
