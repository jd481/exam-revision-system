<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Testing Individual Pages</h1>";

$pages = [
    'index.php',
    'auth/login.php',
    'auth/register.php',
    'student/dashboard.php',
    'lecturer/dashboard.php',
    'student/view_papers.php',
    'lecturer/past_papers.php'
];

foreach ($pages as $page) {
    echo "<h3>Testing: $page</h3>";
    
    // Get the content of the page
    $content = @file_get_contents("http://exam-revision-system.free.nf/$page");
    
    if ($content === false) {
        echo "<p style='color:red'>❌ Failed to load - This page has an error!</p>";
        
        // Try to include it directly to see the error
        ob_start();
        include($page);
        $output = ob_get_clean();
        
        if (empty($output)) {
            echo "<p style='color:orange'>⚠️ Page produced no output</p>";
        }
    } else {
        echo "<p style='color:green'>✅ Loaded successfully</p>";
    }
    
    echo "<hr>";
}
?>