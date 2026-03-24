<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Error Finder</h1>";

// Test 1: Basic PHP
echo "<h2>Test 1: Basic PHP</h2>";
echo "✅ PHP is working<br>";

// Test 2: Session
echo "<h2>Test 2: Session</h2>";
session_start();
echo "✅ Session started<br>";

// Test 3: Database connection
echo "<h2>Test 3: Database connection</h2>";
try {
    require_once 'config/database.php';
    echo "✅ Database config loaded<br>";
    
    $stmt = $pdo->query("SELECT 1");
    echo "✅ Database query successful<br>";
    
    // Check if school_id exists in users
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'school_id'");
    if ($stmt->rowCount() > 0) {
        echo "✅ school_id column exists<br>";
    } else {
        echo "❌ school_id column MISSING<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 4: File includes
echo "<h2>Test 4: File includes</h2>";
$files = [
    'student/dashboard.php',
    'lecturer/dashboard.php',
    'auth/login.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists<br>";
    } else {
        echo "❌ $file MISSING<br>";
    }
}

echo "<h2>Test Complete</h2>";
echo "<p><a href='auth/login.php'>Try Login</a></p>";
?>