<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

echo "<h1>Minimal Test</h1>";

// Test 1: Session
echo "<h2>Session Test</h2>";
$_SESSION['test'] = 'working';
echo "Session test: " . ($_SESSION['test'] ?? 'not working') . "<br>";

// Test 2: Database
echo "<h2>Database Test</h2>";
try {
    require_once 'config/database.php';
    
    // Simple query
    $stmt = $pdo->query("SELECT 1");
    echo "✅ Database query successful<br>";
    
    // Check users
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $count = $stmt->fetchColumn();
    echo "✅ Users table has $count records<br>";
    
} catch(Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

echo "<h2>Test Complete</h2>";
?>