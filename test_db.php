<?php
// DELETE THIS FILE AFTER FIXING — it exposes server info
function env($key) {
    $val = getenv($key);
    if ($val !== false && $val !== '') return $val;
    if (isset($_ENV[$key])    && $_ENV[$key]    !== '') return $_ENV[$key];
    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') return $_SERVER[$key];
    return null;
}

$vars = ['MYSQLHOST','MYSQL_HOST','MYSQLPORT','MYSQL_PORT',
         'MYSQLUSER','MYSQL_USER','MYSQLDATABASE','MYSQL_DATABASE',
         'MYSQLPASSWORD','MYSQL_PASSWORD','MYSQL_URL','DATABASE_URL',
         'RAILWAY_PUBLIC_DOMAIN'];

echo "<pre style='font-family:monospace;padding:20px'>";
foreach ($vars as $v) {
    $val = env($v);
    $display = $val ? (str_contains($v,'PASS') || str_contains($v,'URL') ? '✅ SET (hidden)' : $val) : '❌ NOT SET';
    echo str_pad($v, 25) . " => " . $display . "\n";
}
echo "</pre>";
