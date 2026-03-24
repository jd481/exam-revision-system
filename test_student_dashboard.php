<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

echo "<h1>Testing Student Dashboard Line by Line</h1>";

// Line 1-2: Already tested
echo "Line 1-2: ✅ error reporting<br>";

// Line 3: session_start() - already tested
echo "Line 3: ✅ session_start()<br>";

// Line 4: require_once
echo "Line 4: Testing require_once... ";
require_once 'config/database.php';
echo "✅ Success<br>";

// Line 5: auth check
echo "Line 5: Testing auth check... ";
if (!isset($_SESSION['user_id'])) {
    echo "⚠️ Not logged in - but that's OK for test<br>";
} else {
    echo "✅ User is logged in<br>";
}

// Try to include the actual dashboard
echo "<h2>Attempting to include real dashboard:</h2>";
try {
    include 'student/dashboard.php';
    echo "<p style='color:green'>✅ Dashboard loaded!</p>";
} catch (Throwable $e) {
    echo "<p style='color:red'>❌ Error on line " . $e->getLine() . ": " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . "</p>";
}
?>