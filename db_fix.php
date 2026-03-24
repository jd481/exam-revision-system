<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Database Connection Fix</h1>";

// Your InfinityFree credentials - CORRECT AS PER YOUR SCREENSHOT
$host = "sql100.infinityfree.com";
$dbname = "if0_41362515_examdb";
$username = "if0_41362515";
$password = "Tyzn1234";

echo "<h2>📋 Testing Different Connection Methods:</h2>";

// Method 1: Standard connection (with port)
echo "<h3>Method 1: Standard connection with port 3306</h3>";
try {
    $pdo = new PDO("mysql:host=$host;port=3306;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color:green'>✅ SUCCESS! Connected using Method 1</p>";
} catch(PDOException $e) {
    echo "<p style='color:red'>❌ Failed: " . $e->getMessage() . "</p>";
}

// Method 2: Using localhost (common on some hosting)
echo "<h3>Method 2: Using localhost</h3>";
try {
    $pdo = new PDO("mysql:host=localhost;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color:green'>✅ SUCCESS! Connected using localhost</p>";
} catch(PDOException $e) {
    echo "<p style='color:red'>❌ Failed: " . $e->getMessage() . "</p>";
}

// Method 3: Using 127.0.0.1 instead of localhost
echo "<h3>Method 3: Using 127.0.0.1</h3>";
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color:green'>✅ SUCCESS! Connected using 127.0.0.1</p>";
} catch(PDOException $e) {
    echo "<p style='color:red'>❌ Failed: " . $e->getMessage() . "</p>";
}

// Method 4: Using the exact host from InfinityFree without port
echo "<h3>Method 4: Using host without port</h3>";
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color:green'>✅ SUCCESS! Connected using host only</p>";
} catch(PDOException $e) {
    echo "<p style='color:red'>❌ Failed: " . $e->getMessage() . "</p>";
}

// Method 5: Using mysqli extension instead of PDO
echo "<h3>Method 5: Using MySQLi (alternative)</h3>";
$mysqli = new mysqli($host, $username, $password, $dbname);
if ($mysqli->connect_error) {
    echo "<p style='color:red'>❌ Failed: " . $mysqli->connect_error . "</p>";
} else {
    echo "<p style='color:green'>✅ SUCCESS! Connected using MySQLi</p>";
    $mysqli->close();
}

echo "<h2>🔧 Recommended database.php Configuration:</h2>";
echo "<pre style='background:#f4f4f4; padding:15px; border-radius:5px;'>";
echo "&lt;?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Try different hosts until one works
\$hosts = [
    'sql100.infinityfree.com',
    'localhost',
    '127.0.0.1'
];

\$dbname = 'if0_41362515_examdb';
\$username = 'if0_41362515';
\$password = 'Tyzn1234';

\$connected = false;

foreach (\$hosts as \$host) {
    try {
        \$pdo = new PDO(\"mysql:host=\$host;dbname=\$dbname;charset=utf8mb4\", \$username, \$password);
        \$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        \$connected = true;
        break;
    } catch(PDOException \$e) {
        // Try next host
        continue;
    }
}

if (!\$connected) {
    die(\"Database connection failed. Please try again later.\");
}
?>";
echo "</pre>";
?>