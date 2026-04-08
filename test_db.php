<?php
// TEMPORARY TEST — DELETE AFTER FIXING
try {
    $dsn = "mysql:host=caboose.proxy.rlwy.net;port=16657;dbname=railway;charset=utf8mb4";
    $pdo = new PDO($dsn, 'root', 'sRqXOMCllhMewkhQqgtrfLbdSsafnmOb', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo "✅ Connected successfully!";
} catch (PDOException $e) {
    echo "❌ Failed: " . $e->getMessage();
}
