<?php
// auth.php — gatekeeper for protected pages
require_once __DIR__ . '/config.php';

session_name(SESSION_NAME);
session_start();

if (empty($_SESSION['loggedin'])) {
    header('Location: login.php');
    exit;
}
