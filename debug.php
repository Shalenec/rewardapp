<?php
// TEMPORARY DEBUG FILE - DELETE AFTER FIXING
// Visit: https://your-railway-url.up.railway.app/debug.php

$vars = [
    'MYSQLHOST'        => getenv('MYSQLHOST'),
    'MYSQLUSER'        => getenv('MYSQLUSER'),
    'MYSQLPASSWORD'    => getenv('MYSQLPASSWORD') ? '***SET***' : 'NOT SET',
    'MYSQLDATABASE'    => getenv('MYSQLDATABASE'),
    'MYSQLPORT'        => getenv('MYSQLPORT'),
    'MYSQL_URL'        => getenv('MYSQL_URL') ? 'SET' : 'NOT SET',
    'DATABASE_URL'     => getenv('DATABASE_URL') ? 'SET' : 'NOT SET',
    'RAILWAY_PUBLIC_DOMAIN' => getenv('RAILWAY_PUBLIC_DOMAIN'),
];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug</title>
    <style>
        body { font-family: monospace; padding: 30px; background: #f8fafc; }
        table { border-collapse: collapse; width: 600px; }
        th, td { border: 1px solid #ddd; padding: 10px 14px; text-align: left; }
        th { background: #1a56db; color: white; }
        tr:nth-child(even) { background: #f1f5f9; }
        .set { color: green; font-weight: bold; }
        .notset { color: red; font-weight: bold; }
    </style>
</head>
<body>
<h2>🔍 Railway Environment Variables</h2>
<table>
    <tr><th>Variable</th><th>Value</th></tr>
    <?php foreach ($vars as $key => $val): ?>
    <tr>
        <td><?php echo $key; ?></td>
        <td class="<?php echo $val ? 'set' : 'notset'; ?>">
            <?php echo $val ?: 'NOT SET ❌'; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<h3 style="margin-top:30px;">Test DB Connection</h3>
<?php
$host = getenv('MYSQLHOST');
$user = getenv('MYSQLUSER');
$pass = getenv('MYSQLPASSWORD');
$db   = getenv('MYSQLDATABASE');
$port = getenv('MYSQLPORT') ?: '3306';

if ($host && $user && $db) {
    try {
        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        echo '<p style="color:green;font-size:1.1rem;">✅ Database connected successfully!</p>';
    } catch (Exception $e) {
        echo '<p style="color:red;">❌ Connection failed: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
} else {
    echo '<p style="color:orange;">⚠️ MySQL env variables not set — cannot test connection.</p>';
}
?>

<p style="margin-top:30px;color:#999;font-size:0.8rem;">
    ⚠️ DELETE this file (debug.php) after fixing your database connection!
</p>
</body>
</html>
