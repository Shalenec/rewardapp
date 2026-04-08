<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "Step 1: PHP is working <br>";

require_once 'includes/config.php';
echo "Step 2: config.php loaded <br>";

$db = getDB();
echo "Step 3: DB Connected ✅";
