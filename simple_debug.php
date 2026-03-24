<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Simple Dashboard Debugger</h1>";

// Test 1: Check if dashboard file exists
$dashboard_file = __DIR__ . '/student/dashboard.php';
if (file_exists($dashboard_file)) {
    echo "<p style='color:green'>✅ Dashboard file exists</p>";
    
    // Test 2: Check file size
    $file_size = filesize($dashboard_file);
    echo "<p>File size: $file_size bytes</p>";
    
    // Test 3: Read first few lines to check for any hidden characters
    $lines = file($dashboard_file);
    echo "<p>First 5 lines of the file:</p>";
    echo "<pre>";
    for ($i = 0; $i < min(5, count($lines)); $i++) {
        echo htmlspecialchars($lines[$i]);
    }
    echo "</pre>";
    
} else {
    echo "<p style='color:red'>❌ Dashboard file NOT found!</p>";
    exit();
}

// Test 4: Check database connection separately
echo "<h2>Testing Database Connection</h2>";
try {
    require_once __DIR__ . '/config/database.php';
    echo "<p style='color:green'>✅ Database config loaded</p>";
    
    // Test query
    $stmt = $pdo->query("SELECT 1");
    echo "<p style='color:green'>✅ Database query works</p>";
    
    // Check if schools table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'schools'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green'>✅ schools table exists</p>";
    } else {
        echo "<p style='color:red'>❌ schools table missing</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Database error: " . $e->getMessage() . "</p>";
}

// Test 5: Try to include the dashboard and catch errors
echo "<h2>Attempting to include dashboard.php</h2>";
try {
    ob_start();
    include $dashboard_file;
    $output = ob_get_clean();
    echo "<p style='color:green'>✅ Dashboard included successfully!</p>";
    echo "<p>First 500 characters of output:</p>";
    echo "<pre>" . htmlspecialchars(substr($output, 0, 500)) . "</pre>";
} catch (ParseError $e) {
    echo "<p style='color:red'>❌ Parse error: " . $e->getMessage() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
} catch (Throwable $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
}
?>