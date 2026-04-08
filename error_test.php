<?php
// ============================================
//   RAILWAY APP DIAGNOSTIC TOOL
//   Upload this file, visit /diagnose.php
//   DELETE after debugging!
// ============================================

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$results = [];

// ── 1. PHP INFO ──────────────────────────────
$results['php'] = [
    'version'        => phpversion(),
    'os'             => PHP_OS,
    'sapi'           => php_sapi_name(),
    'max_exec_time'  => ini_get('max_execution_time') . 's',
    'memory_limit'   => ini_get('memory_limit'),
    'upload_max'     => ini_get('upload_max_filesize'),
];

// ── 2. RAILWAY ENV VARIABLES ─────────────────
$railway_vars = [
    'PORT', 'RAILWAY_ENVIRONMENT', 'RAILWAY_PROJECT_NAME',
    'RAILWAY_SERVICE_NAME', 'DATABASE_URL',
    'MYSQLHOST', 'MYSQLPORT', 'MYSQLDATABASE', 'MYSQLUSER', 'MYSQLPASSWORD',
    'PGHOST', 'PGPORT', 'PGDATABASE', 'PGUSER', 'PGPASSWORD',
    'REDIS_URL', 'APP_ENV', 'APP_KEY', 'APP_DEBUG',
];

foreach ($railway_vars as $var) {
    $val = getenv($var);
    if ($val !== false) {
        // Mask sensitive values
        $masked = in_array($var, ['MYSQLPASSWORD', 'PGPASSWORD', 'APP_KEY', 'DATABASE_URL', 'REDIS_URL'])
            ? str_repeat('*', 6) . substr($val, -4)
            : $val;
        $results['env'][$var] = ['status' => '✅ Set', 'value' => $masked];
    } else {
        $results['env'][$var] = ['status' => '❌ Missing', 'value' => null];
    }
}

// ── 3. DATABASE CONNECTION ───────────────────
function testMySQL($results) {
    $host = getenv('MYSQLHOST');
    $port = getenv('MYSQLPORT') ?: 3306;
    $db   = getenv('MYSQLDATABASE');
    $user = getenv('MYSQLUSER');
    $pass = getenv('MYSQLPASSWORD');

    if (!$host || !$db || !$user) {
        return ['status' => '⚠️ Skipped', 'message' => 'MySQL env vars not set'];
    }
    try {
        $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]);
        return ['status' => '✅ Connected', 'message' => 'MySQL connection successful'];
    } catch (PDOException $e) {
        return ['status' => '❌ Failed', 'message' => $e->getMessage()];
    }
}

function testPostgres() {
    $host = getenv('PGHOST');
    $port = getenv('PGPORT') ?: 5432;
    $db   = getenv('PGDATABASE');
    $user = getenv('PGUSER');
    $pass = getenv('PGPASSWORD');

    if (!$host || !$db || !$user) {
        return ['status' => '⚠️ Skipped', 'message' => 'Postgres env vars not set'];
    }
    try {
        $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$db", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]);
        return ['status' => '✅ Connected', 'message' => 'PostgreSQL connection successful'];
    } catch (PDOException $e) {
        return ['status' => '❌ Failed', 'message' => $e->getMessage()];
    }
}

$results['database']['mysql']    = testMySQL($results);
$results['database']['postgres'] = testPostgres();

// ── 4. FILE SYSTEM ───────────────────────────
$results['filesystem'] = [
    'current_dir'  => __DIR__,
    'writable'     => is_writable(__DIR__) ? '✅ Writable' : '❌ Not writable',
    'disk_free'    => round(disk_free_space('/') / 1024 / 1024, 2) . ' MB',
];

// ── 5. REQUIRED EXTENSIONS ───────────────────
$extensions = ['pdo', 'pdo_mysql', 'pdo_pgsql', 'mbstring', 'json', 'curl', 'openssl', 'fileinfo'];
foreach ($extensions as $ext) {
    $results['extensions'][$ext] = extension_loaded($ext) ? '✅ Loaded' : '❌ Missing';
}

// ── 6. ERROR LOG (last 30 lines) ─────────────
$log_path = __DIR__ . '/error.log';
if (file_exists($log_path)) {
    $lines = file($log_path);
    $results['error_log'] = array_slice($lines, -30);
} else {
    $results['error_log'] = ['No error.log found in current directory'];
}

// ── OUTPUT HTML ──────────────────────────────
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔍 PHP Diagnostic</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #0f172a; color: #e2e8f0; padding: 2rem; }
        h1 { color: #38bdf8; margin-bottom: 0.25rem; }
        .subtitle { color: #64748b; margin-bottom: 2rem; font-size: 0.9rem; }
        .warning { background: #7c2d12; border: 1px solid #ea580c; color: #fed7aa;
                   padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 2rem; font-size: 0.85rem; }
        .section { background: #1e293b; border-radius: 10px; padding: 1.25rem; margin-bottom: 1.5rem; }
        .section h2 { color: #7dd3fc; font-size: 1rem; margin-bottom: 1rem;
                      border-bottom: 1px solid #334155; padding-bottom: 0.5rem; }
        table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        td { padding: 0.4rem 0.6rem; border-bottom: 1px solid #1e293b; vertical-align: top; }
        td:first-child { color: #94a3b8; width: 40%; }
        .ok   { color: #4ade80; }
        .fail { color: #f87171; }
        .warn { color: #fbbf24; }
        pre { background: #0f172a; padding: 1rem; border-radius: 6px; font-size: 0.78rem;
              overflow-x: auto; color: #f87171; white-space: pre-wrap; word-break: break-all; }
        .badge { display: inline-block; padding: 0.15rem 0.5rem; border-radius: 999px;
                 font-size: 0.75rem; font-weight: 600; }
        .badge-ok   { background: #14532d; color: #4ade80; }
        .badge-fail { background: #450a0a; color: #f87171; }
        .badge-warn { background: #422006; color: #fbbf24; }
    </style>
</head>
<body>

<h1>🔍 Railway PHP Diagnostic</h1>
<p class="subtitle">Generated: <?= date('Y-m-d H:i:s T') ?></p>

<div class="warning">
    ⚠️ <strong>Security Warning:</strong> Delete this file (<code>diagnose.php</code>) immediately after debugging. It exposes sensitive server information.
</div>

<!-- PHP INFO -->
<div class="section">
    <h2>⚙️ PHP Info</h2>
    <table>
        <?php foreach ($results['php'] as $k => $v): ?>
        <tr><td><?= htmlspecialchars($k) ?></td><td><?= htmlspecialchars($v) ?></td></tr>
        <?php endforeach; ?>
    </table>
</div>

<!-- ENV VARIABLES -->
<div class="section">
    <h2>🌍 Environment Variables</h2>
    <table>
        <?php foreach ($results['env'] as $var => $info): ?>
        <tr>
            <td><?= htmlspecialchars($var) ?></td>
            <td>
                <?php
                    $cls = str_contains($info['status'], '✅') ? 'badge-ok' : 'badge-fail';
                    echo "<span class='badge $cls'>{$info['status']}</span>";
                    if ($info['value']) echo " <small style='color:#94a3b8'>" . htmlspecialchars($info['value']) . "</small>";
                ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<!-- DATABASE -->
<div class="section">
    <h2>🗄️ Database Connections</h2>
    <table>
        <?php foreach ($results['database'] as $db => $info): ?>
        <tr>
            <td><?= strtoupper($db) ?></td>
            <td>
                <?php
                    if (str_contains($info['status'], '✅')) $cls = 'badge-ok';
                    elseif (str_contains($info['status'], '⚠️')) $cls = 'badge-warn';
                    else $cls = 'badge-fail';
                    echo "<span class='badge $cls'>{$info['status']}</span> ";
                    echo "<small style='color:#94a3b8'>" . htmlspecialchars($info['message']) . "</small>";
                ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<!-- EXTENSIONS -->
<div class="section">
    <h2>🧩 PHP Extensions</h2>
    <table>
        <?php foreach ($results['extensions'] as $ext => $status): ?>
        <tr>
            <td><?= htmlspecialchars($ext) ?></td>
            <td>
                <?php
                    $cls = str_contains($status, '✅') ? 'badge-ok' : 'badge-fail';
                    echo "<span class='badge $cls'>$status</span>";
                ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<!-- FILESYSTEM -->
<div class="section">
    <h2>📁 Filesystem</h2>
    <table>
        <?php foreach ($results['filesystem'] as $k => $v): ?>
        <tr><td><?= htmlspecialchars($k) ?></td><td><?= htmlspecialchars($v) ?></td></tr>
        <?php endforeach; ?>
    </table>
</div>

<!-- ERROR LOG -->
<div class="section">
    <h2>📋 Error Log (last 30 lines)</h2>
    <pre><?php
        foreach ($results['error_log'] as $line) {
            echo htmlspecialchars($line);
        }
    ?></pre>
</div>

</body>
</html>
