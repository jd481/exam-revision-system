<?php
echo "<h1>Directory Listing</h1>";
echo "<p>Current directory: " . __DIR__ . "</p>";
echo "<h2>Files in this directory:</h2>";
$files = scandir(__DIR__);
foreach($files as $file) {
    if ($file != "." && $file != "..") {
        echo "- " . $file . "<br>";
    }
}
?>