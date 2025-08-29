<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
 require __DIR__ . '/auth.php'; require_once __DIR__ . '/config.php'; ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Minecraft Server Console</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial; background:#0f1115; color:#e6e6e6; margin:0; }
    header, .bar { display:flex; align-items:center; gap:8px; padding:14px 16px; background:#161a22; position:sticky; top:0; z-index:2; }
    h1 { font-size:18px; margin:0; }
    .grow { flex:1; }
    button { padding:8px 12px; border-radius:10px; border:0; background:#3b82f6; color:white; font-weight:600; cursor:pointer; }
    button.secondary { background:#374151; }
    button.danger { background:#ef4444; }
    button:disabled { opacity:0.6; cursor:not-allowed; }
    main { padding:16px; }
    #console { background:#0b0f17; border:1px solid #273043; border-radius:12px; padding:12px; height:55vh; overflow:auto; white-space:pre-wrap; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace; }
    .cmdbar { display:flex; gap:8px; margin-top:10px; }
    input[type="text"] { flex:1; padding:10px 12px; border-radius:10px; border:1px solid #2b3242; background:#0f1320; color:#e6e6e6; }
    .hint { color:#9aa4b2; font-size:12px; margin-top:8px; }
    a { color:#93c5fd; text-decoration:none; }
  </style>
</head>
<body>
  <header>
    <h1>MC Panel</h1>
    <div class="grow"></div>
    <a href="logout.php"><button class="secondary">Logout</button></a>
    
  </header>

  <div class="bar">
  <button id="btnStart" onclick="startServer()">Start</button>
  <button id="btnStop"  class="danger" onclick="stopServer()">Stop</button>
  <div class="grow"></div>
  <span class="hint">Players: <strong id="playerCount">–/–</strong></span>
  <span class="hint">Refresh: <?php echo (int)POLL_MS; ?> ms · Log: <?php echo htmlspecialchars(basename(LOG_FILE)); ?></span>
</div>


  <main>
    <div id="console">Waiting for log...</div>

    <form class="cmdbar" onsubmit="sendCommand(); return false;">
      <input type="text" id="cmd" placeholder="Type a console command (e.g., say Hello)">
      <button type="submit">Send</button>
    </form>
    <div class="hint">Tip: Use <code>stop</code> Commands werden über rcon gesendet</div>
    <h2 style="margin-top:16px;">Online Players</h2>
<div id="playersBox" style="background:#0b0f17;border:1px solid #273043;border-radius:12px;padding:10px;min-height:40px;">
  <div id="playerNames" class="hint">(fetching…)</div>
</div>

  </main>

  <script>
    const consoleEl = document.getElementById('console');
    const btnStart  = document.getElementById('btnStart');
    const btnStop   = document.getElementById('btnStop');

    function pollLog() {
      fetch('get-log.php', {cache:'no-store'})
        .then(r => r.ok ? r.text() : Promise.reject(r.status))
        .then(t => {
          consoleEl.textContent = t || '(log empty)';
          consoleEl.scrollTop = consoleEl.scrollHeight;
        })
        .catch(() => {});
    }

    function startServer() {
      btnStart.disabled = true;
      fetch('Server\\start-server.php', {method:'POST'})
        .then(r => r.text()).then(alertMsg => {
          alert(alertMsg || 'Start issued.');
          btnStart.disabled = false;
          setTimeout(pollLog, 1200);
        }).catch(() => btnStart.disabled = false);
    }

    function stopServer() {
      btnStop.disabled = true;
      fetch('stop-server.php', {method:'POST'})
        .then(r => r.text()).then(alertMsg => {
          alert(alertMsg || 'Stop issued.');
          btnStop.disabled = false;
        }).catch(() => btnStop.disabled = false);
    }

    function sendCommand() {
      const val = document.getElementById('cmd').value.trim();
      if (!val) return;
      fetch('send-command.php', {
        method:'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'cmd=' + encodeURIComponent(val)
      }).then(r => r.text()).then(resp => {
        document.getElementById('cmd').value = '';
        setTimeout(pollLog, 400);
      });
    }

    pollLog();
    setInterval(pollLog, <?php echo (int)POLL_MS; ?>);
    function pollPlayers() {
  fetch('get-players-query.php', { cache: 'no-store', credentials: 'same-origin' })
    .then(r => r.json())
    .then(data => {
      const pc = document.getElementById('playerCount');
      const pn = document.getElementById('playerNames');

      if (!data.ok) {
        pc.textContent = '–/–';
        pn.textContent = data.message || '(server offline)';
        return;
      }
      pc.textContent = data.online + '/' + data.max;
      pn.textContent = (data.online && data.players.length) ? data.players.join(', ') : '(no players online)';
    })
    .catch(() => {
      document.getElementById('playerCount').textContent = '–/–';
      document.getElementById('playerNames').textContent = '(error fetching)';
    });
}


  // existing timers
  pollLog();
  setInterval(pollLog, <?php echo (int)POLL_MS; ?>);

  // new players poll (every ~3s is plenty)
  pollPlayers();
  setInterval(pollPlayers, 3000);
</script>
</body>
</html>
