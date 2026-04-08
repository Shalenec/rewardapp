<?php
// ============================================
//   DEEP ERROR FINDER
//   Visit /debug.php to see exact app errors
//   DELETE after debugging!
// ============================================

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$errors = [];

// ── 1. TEST DB CONNECTION + QUERIES ──────────
$host = getenv('MYSQLHOST');
$port = getenv('MYSQLPORT') ?: 3306;
$db   = getenv('MYSQLDATABASE');
$user = getenv('MYSQLUSER');
$pass = getenv('MYSQLPASSWORD');

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5,
    ]);

    // Check tables exist
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $db_result = [
        'status' => '✅ Connected',
        'tables' => $tables ?: ['⚠️ No tables found — database is empty!'],
    ];
} catch (PDOException $e) {
    $db_result = [
        'status'  => '❌ Failed',
        'message' => $e->getMessage(),
    ];
}

// ── 2. FIND YOUR MAIN APP FILES ───────────────
$root     = '/var/www/html';
$mainFiles = ['index.php', 'app.php', 'config.php', 'bootstrap.php', '.env', 'wp-config.php'];
$found    = [];
foreach ($mainFiles as $f) {
    $path = $root . '/' . $f;
    $found[$f] = file_exists($path) ? '✅ Exists' : '❌ Not found';
}

// All PHP files in root
$phpFiles = glob($root . '/*.php') ?: [];

// ── 3. TRY TO INCLUDE index.php AND CATCH ERRORS ──
ob_start();
$include_error = null;
try {
    // Temporarily override error handler to catch warnings too
    set_error_handler(function($errno, $errstr, $errfile, $errline) {
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    });

    if (file_exists($root . '/index.php')) {
        // Don't actually include — just parse/lint it
        $lint = shell_exec("php -l " . escapeshellarg($root . '/index.php') . " 2>&1");
    }
    restore_error_handler();
} catch (Throwable $e) {
    $include_error = [
        'message' => $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine(),
        'trace'   => $e->getTraceAsString(),
    ];
}
ob_end_clean();

// ── 4. PHP ERROR LOG ─────────────────────────
$log_locations = [
    '/var/www/html/error.log',
    '/var/log/apache2/error.log',
    '/var/log/php_errors.log',
    '/tmp/php_errors.log',
    ini_get('error_log'),
];

$log_output = [];
foreach ($log_locations as $log) {
    if ($log && file_exists($log) && is_readable($log)) {
        $lines = file($log);
        $last  = array_slice($lines, -40);
        $log_output[$log] = $last;
        break;
    }
}
if (empty($log_output)) {
    $log_output = ['No log files found' => ['Try checking Railway logs directly in the dashboard']];
}

// ── 5. APACHE ERROR LOG ──────────────────────
$apache_log = '/var/log/apache2/error.log';
$apache_output = [];
if (file_exists($apache_log) && is_readable($apache_log)) {
    $lines = file($apache_log);
    $apache_output = array_slice($lines, -40);
} else {
    $apache_output = ['Apache log not accessible — check Railway dashboard logs'];
}

// ── 6. .ENV FILE CHECK ───────────────────────
$env_file = $root . '/.env';
$env_content = null;
if (file_exists($env_file)) {
    $raw = file_get_contents($env_file);
    // Mask passwords
    $env_content = preg_replace('/(PASSWORD|SECRET|KEY|TOKEN)\s*=\s*\S+/i', '$1=******', $raw);
} else {
    $env_content = '❌ No .env file found';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🐛 Deep Error Finder</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #0f172a; color: #e2e8f0; padding: 2rem; }
        h1 { color: #f472b6; margin-bottom: 0.25rem; }
        .subtitle { color: #64748b; margin-bottom: 2rem; font-size: 0.875rem; }
        .warning { background: #7c2d12; border: 1px solid #ea580c; color: #fed7aa;
                   padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 2rem; font-size: 0.85rem; }
        .section { background: #1e293b; border-radius: 10px; padding: 1.25rem; margin-bottom: 1.5rem; }
        .section h2 { color: #7dd3fc; font-size: 1rem; margin-bottom: 1rem;
                      border-bottom: 1px solid #334155; padding-bottom: 0.5rem; }
        table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        td { padding: 0.4rem 0.6rem; border-bottom: 1px solid #334155; vertical-align: top; }
        td:first-child { color: #94a3b8; width: 40%; }
        pre { background: #0f172a; padding: 1rem; border-radius: 6px; font-size: 0.78rem;
              overflow-x: auto; color: #f87171; white-space: pre-wrap; word-break: break-all;
              max-height: 400px; overflow-y: auto; }
        pre.green { color: #4ade80; }
        .badge { display: inline-block; padding: 0.15rem 0.5rem; border-radius: 999px;
                 font-size: 0.75rem; font-weight: 600; }
        .badge-ok   { background: #14532d; color: #4ade80; }
        .badge-fail { background: #450a0a; color: #f87171; }
        .badge-warn { background: #422006; color: #fbbf24; }
        .critical { background: #450a0a; border: 1px solid #f87171;
                    padding: 1rem; border-radius: 8px; margin-bottom: 1rem; }
        .critical h3 { color: #f87171; margin-bottom: 0.5rem; }
        .tag { display: inline-block; background: #1e3a5f; color: #7dd3fc;
               padding: 0.1rem 0.4rem; border-radius: 4px; font-size: 0.75rem; margin: 2px; }
    </style>
</head>
<body>

<h1>🐛 Deep Error Finder</h1>
<p class="subtitle">Generated: <?= date('Y-m-d H:i:s T') ?></p>

<div class="warning">
    ⚠️ <strong>Delete debug.php immediately after fixing your issue!</strong>
</div>

<!-- DB CONNECTION -->
<div class="section">
    <h2>🗄️ Database Connection & Tables</h2>
    <?php if ($db_result['status'] === '✅ Connected'): ?>
        <p style="color:#4ade80; margin-bottom:1rem">✅ Connected successfully</p>
        <p style="color:#94a3b8; font-size:0.85rem; margin-bottom:0.5rem">Tables found:</p>
        <?php if (empty($db_result['tables'])): ?>
            <div class="critical"><h3>⚠️ Database is EMPTY</h3><p>No tables found. You may need to run migrations or import your SQL file.</p></div>
        <?php else: ?>
            <?php foreach ($db_result['tables'] as $t): ?>
                <span class="tag"><?= htmlspecialchars($t) ?></span>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php else: ?>
        <div class="critical">
            <h3>❌ Database Connection Failed</h3>
            <pre><?= htmlspecialchars($db_result['message']) ?></pre>
        </div>
    <?php endif; ?>
</div>

<!-- APP FILES -->
<div class="section">
    <h2>📁 App Files in /var/www/html</h2>
    <table>
        <?php foreach ($found as $file => $status): ?>
        <tr>
            <td><?= htmlspecialchars($file) ?></td>
            <td><span class="badge <?= str_contains($status, '✅') ? 'badge-ok' : 'badge-fail' ?>"><?= $status ?></span></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <p style="margin-top:1rem; color:#94a3b8; font-size:0.85rem">All PHP files found:</p>
    <?php foreach ($phpFiles as $f): ?>
        <span class="tag"><?= htmlspecialchars(basename($f)) ?></span>
    <?php endforeach; ?>
</div>

<!-- SYNTAX CHECK -->
<div class="section">
    <h2>🔍 index.php Syntax Check</h2>
    <?php if (isset($lint)): ?>
        <pre class="<?= str_contains($lint, 'No syntax errors') ? 'green' : '' ?>"><?= htmlspecialchars($lint) ?></pre>
    <?php else: ?>
        <p style="color:#fbbf24">⚠️ index.php not found to lint</p>
    <?php endif; ?>
</div>

<!-- INCLUDE ERROR -->
<?php if ($include_error): ?>
<div class="section">
    <h2>💥 App Crash Error</h2>
    <div class="critical">
        <h3><?= htmlspecialchars($include_error['message']) ?></h3>
        <p style="color:#94a3b8; font-size:0.85rem">
            File: <?= htmlspecialchars($include_error['file']) ?>:<?= $include_error['line'] ?>
        </p>
        <pre><?= htmlspecialchars($include_error['trace']) ?></pre>
    </div>
</div>
<?php endif; ?>

<!-- .ENV FILE -->
<div class="section">
    <h2>📄 .env File Contents</h2>
    <pre><?= htmlspecialchars($env_content) ?></pre>
</div>

<!-- PHP ERROR LOG -->
<div class="section">
    <h2>📋 PHP Error Log</h2>
    <?php foreach ($log_output as $path => $lines): ?>
        <p style="color:#64748b; font-size:0.8rem; margin-bottom:0.5rem">📍 <?= htmlspecialchars($path) ?></p>
        <pre><?php foreach ($lines as $line) echo htmlspecialchars($line); ?></pre>
    <?php endforeach; ?>
</div>

<!-- APACHE LOG -->
<div class="section">
    <h2>🌐 Apache Error Log</h2>
    <pre><?php foreach ($apache_output as $line) echo htmlspecialchars($line); ?></pre>
</div>

</body>
</html>
