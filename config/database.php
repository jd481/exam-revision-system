<?php
error_reporting(E_ALL);
error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', 0); // Turn off display_errors in production
ini_set('log_errors', 1);

// InfinityFree Database Credentials
$dbname = 'if0_41362515_examdb';
$username = 'if0_41362515';
$password = 'Tyzn1234';

// List of possible hosts to try (in order)
$hosts = [
    'sql100.infinityfree.com',  // Your specific host
    'localhost',                 // Common alternative
    '127.0.0.1',                 // IP version
    'mysql.infinityfree.com'     // Another common InfinityFree host
];

$connected = false;
$pdo = null;

// Try each host until one works
foreach ($hosts as $host) {
    try {
        $pdo = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 3 // 3 second timeout
            ]
        );
        $connected = true;
        break;
    } catch(PDOException $e) {
        // Log the error but continue trying other hosts
        error_log("Connection failed for host $host: " . $e->getMessage());
        continue;
    }
}

if (!$connected) {
    // Log all failed attempts
    error_log("All database connection attempts failed for user $username");
    
    // Show a user-friendly message (don't expose technical details)
    die("We're experiencing technical difficulties. Please try again later.");
}

// Optional: Test the connection with a simple query
try {
    $pdo->query("SELECT 1");
} catch(PDOException $e) {
    error_log("Connection test query failed: " . $e->getMessage());
    die("Database connection不稳定. Please try again later.");
}
?>