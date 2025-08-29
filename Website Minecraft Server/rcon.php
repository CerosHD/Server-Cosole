<?php
// rcon.php — minimal RCON client for Minecraft (TCP)
class SimpleRcon {
    private string $host;
    private int $port;
    private string $password;
    private int $timeout;
    private $sock = null;
    private int $reqId = 0;

    public function __construct(string $host, int $port, string $password, int $timeout = 3) {
        $this->host = $host;
        $this->port = $port;
        $this->password = $password;
        $this->timeout = $timeout;
    }

    public function connect(): bool {
        $this->sock = @fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);
        if (!$this->sock) return false;
        stream_set_timeout($this->sock, $this->timeout);
        return $this->auth();
    }

    public function send(string $command): string|false {
        if (!$this->sock) return false;
        $this->writePacket(2, $command); // SERVERDATA_EXECCOMMAND
        $resp = $this->readPacket();
        if ($resp === false) return false;
        return $resp['payload'];
    }

    public function close(): void {
        if ($this->sock) fclose($this->sock);
        $this->sock = null;
    }

    private function auth(): bool {
        $id = $this->writePacket(3, $this->password); // SERVERDATA_AUTH
        $resp = $this->readPacket();
        if ($resp === false) return false;
        return $resp['id'] === $id;
    }

    private function writePacket(int $type, string $payload): int {
        $id = ++$this->reqId;
        $packet = pack('VVV', strlen($payload) + 10, $id, $type) . $payload . "\x00\x00";
        // pack('V') is little-endian 32-bit
        fwrite($this->sock, $packet);
        return $id;
    }

    private function readPacket(): array|false {
        $hdr = fread($this->sock, 4);
        if ($hdr === '' || $hdr === false) return false;
        $size = unpack('Vlen', $hdr)['len'];
        $data = '';
        while (strlen($data) < $size) {
            $chunk = fread($this->sock, $size - strlen($data));
            if ($chunk === false || $chunk === '') break;
            $data .= $chunk;
        }
        if (strlen($data) !== $size) return false;
        $id   = unpack('Vid', substr($data, 0, 4))['id'];
        $type = unpack('Vtype', substr($data, 4, 4))['type'];
        $payload = substr($data, 8, -2); // strip 2x null
        return ['id' => $id, 'type' => $type, 'payload' => $payload];
    }
}
