<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Error Checker</h1>";

// Check if error log exists and is readable
$log_files = [
    '/home/vol4_2/infinityfree.com/if0_41362515/htdocs/php_errors.log',
    '/tmp/php_errors.log',
    'error_log',
    'php_error.log'
];

foreach ($log_files as $log) {
    if (file_exists($log)) {
        echo "<h3>Found log: $log</h3>";
        if (is_readable($log)) {
            $lines = file($log);
            $last_lines = array_slice($lines, -20);
            echo "<pre>";
            foreach ($last_lines as $line) {
                echo htmlspecialchars($line) . "<br>";
            }
            echo "</pre>";
        }
    }
}

// Test database connection
echo "<h2>Testing Database Connection</h2>";
try {
    require_once 'config/database.php';
    echo "✅ Database connected<br>";
    
    // Test schools table
    $stmt = $pdo->query("SELECT * FROM schools");
    $schools = $stmt->fetchAll();
    echo "✅ Schools table accessible (" . count($schools) . " records)<br>";
    
    // Test users table for school_id column
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'school_id'");
    if ($stmt->rowCount() > 0) {
        echo "✅ school_id column exists in users table<br>";
    } else {
        echo "❌ school_id column MISSING in users table<br>";
    }
    
} catch(Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Test key files
echo "<h2>Testing Key Files</h2>";
$files_to_check = [
    'index.php',
    'auth/login.php',
    'auth/register.php',
    'student/dashboard.php',
    'lecturer/dashboard.php',
    'config/database.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists<br>";
    } else {
        echo "❌ $file MISSING<br>";
    }
}
?>