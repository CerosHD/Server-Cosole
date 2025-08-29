<?php
// config.php — simple credentials (change these!)
const AUTH_USER = 'admin';
const AUTH_PASS = 'password'; // <- change this now

// Where to send users after login
const CONSOLE_PAGE = 'console.php';

// Optional: tweak session name
const SESSION_NAME = 'mcpanel_sess';

// --- Server paths & settings ---
const SERVER_DIR   = __DIR__;            // folder containing paper.jar (adjust if needed)
const JAR_FILE     = 'Server.jar';        // your Paper jar name
const JAVA_ARGS    = '-Xms4G -Xmx10G';    // adjust RAM
const LOG_FILE     = SERVER_DIR . DIRECTORY_SEPARATOR . 'console.log';

// --- RCON (enable in server.properties) ---
const RCON_HOST    = '127.0.0.1';
const RCON_PORT    = 25575;
const RCON_PASS    = 'Kepler187'; // must match server.properties

// Convenience
const POLL_MS      = 1500;               // console refresh interval (1.5s)

if (!defined('QUERY_HOST')) define('QUERY_HOST', '127.0.0.1');
if (!defined('QUERY_PORT')) define('QUERY_PORT', 25565);

