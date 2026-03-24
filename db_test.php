<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Database Diagnostic Tool</h1>";

// Your database credentials
$host = "sql100.infinityfree.com";
$dbname = "if0_41362515_examdb";
$username = "if0_41362515";
$password = "Tyzn1234";

echo "<h2>📋 Connection Details:</h2>";
echo "<ul>";
echo "<li>Host: $host</li>";
echo "<li>Database: $dbname</li>";
echo "<li>Username: $username</li>";
echo "<li>Password: " . str_repeat('*', strlen($password)) . "</li>";
echo "</ul>";

// Test connection
echo "<h2>🔌 Testing Connection...</h2>";
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color:green'>✅ Connected successfully!</p>";
    
    // Test if database exists
    $stmt = $pdo->query("SELECT DATABASE()");
    $current_db = $stmt->fetchColumn();
    echo "<p>Current database: <strong>$current_db</strong></p>";
    
    // List all tables
    echo "<h2>📊 Tables in database:</h2>";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) > 0) {
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>📄 $table</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color:orange'>⚠️ No tables found in database. You need to import your SQL.</p>";
    }
    
    // Check users table specifically
    if (in_array('users', $tables)) {
        $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        echo "<p>👥 Users table has <strong>$count</strong> records</p>";
    }
    
} catch(PDOException $e) {
    echo "<p style='color:red'>❌ Connection failed: " . $e->getMessage() . "</p>";
    
    // Try without database name
    echo "<h2>🔄 Trying without database name...</h2>";
    try {
        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "<p style='color:green'>✅ Connected to MySQL server!</p>";
        
        // List all databases
        $databases = $pdo->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>Available databases:</p>";
        echo "<ul>";
        foreach ($databases as $db) {
            $style = ($db == $dbname) ? "style='color:green; font-weight:bold'" : "";
            echo "<li $style>📁 $db " . ($db == $dbname ? "← YOUR DATABASE" : "") . "</li>";
        }
        echo "</ul>";
        
    } catch(PDOException $e2) {
        echo "<p style='color:red'>❌ Server connection failed: " . $e2->getMessage() . "</p>";
    }
}

echo "<h2>🔧 Next Steps:</h2>";
echo "<ol>";
echo "<li>If no tables exist, import your SQL using phpMyAdmin</li>";
echo "<li>Check that all required tables are present</li>";
echo "<li>Verify that your PHP files use the correct table names</li>";
echo "</ol>";
?>