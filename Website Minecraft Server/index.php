<?php
// login.php — shows form and handles login
require_once __DIR__ . '/config.php';

session_name(SESSION_NAME);
session_start();

// Generate CSRF token if missing
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF check
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $error = 'Invalid session. Please refresh and try again.';
    } else {
        $user = trim($_POST['user'] ?? '');
        $pass = $_POST['pass'] ?? '';

        if ($user === AUTH_USER && $pass === AUTH_PASS) {
            // success
            session_regenerate_id(true);
            $_SESSION['loggedin'] = true;
            $_SESSION['user'] = $user;
            unset($_SESSION['login_attempts']);
            header('Location: ' . CONSOLE_PAGE);
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Minecraft Panel Login</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial; background:#0f1115; color:#e6e6e6; display:grid; place-items:center; height:100vh; margin:0; }
    .card { background:#161a22; padding:24px; border-radius:14px; width: min(360px, 92vw); box-shadow: 0 8px 30px rgba(0,0,0,.35); }
    h1 { margin:0 0 12px; font-size:20px; }
    .row { margin:10px 0; }
    input { width:90%; padding:10px 12px; border-radius:10px; border:1px solid #2b3242; background:#0f1320; color:#e6e6e6; }
    button { width:100%; padding:10px 12px; border-radius:10px; border:0; background:#3b82f6; color:white; font-weight:600; cursor:pointer; }
    button:hover { filter:brightness(1.05); }
    .error { color:#fca5a5; margin:8px 0 0; font-size:14px; min-height:18px; }
    .hint { color:#9aa4b2; font-size:12px; margin-top:8px; }
  </style>
</head>
<body>
  <form class="card" method="post" autocomplete="off">
    <h1>Sign in</h1>
    <div class="row">
      <label>
        <input name="user" type="text" placeholder="Username" required autofocus>
      </label>
    </div>
    <div class="row">
      <label>
        <input name="pass" type="password" placeholder="Password" required>
      </label>
    </div>
    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf'], ENT_QUOTES); ?>">
    <div class="row">
      <button type="submit">Login</button>
    </div>
    <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES); ?></div>
  </form>
</body>
</html>
