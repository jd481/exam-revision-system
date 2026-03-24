<?php
echo "<h1>🔧 Final Path Fix for InfinityFree</h1>";

$folders = ['student', 'lecturer', 'auth'];
$fixed = 0;

foreach ($folders as $folder) {
    if (is_dir($folder)) {
        echo "<h2>Fixing $folder folder...</h2>";
        $files = glob("$folder/*.php");
        
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $original = $content;
            
            // Replace any relative paths with __DIR__ paths
            $content = preg_replace(
                "/require_once\s+['\"]\.\.\/config\/database\.php['\"];/",
                "require_once __DIR__ . '/../config/database.php';",
                $content
            );
            
            if ($content !== $original) {
                file_put_contents($file, $content);
                echo "<p style='color:green'>✅ Fixed: " . basename($file) . "</p>";
                $fixed++;
            }
        }
    }
}

// Fix root files
echo "<h2>Fixing root files...</h2>";
$root_files = ['index.php', 'track_download.php', 'toggle_favorite.php'];
foreach ($root_files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $original = $content;
        
        $content = preg_replace(
            "/require_once\s+['\"]config\/database\.php['\"];/",
            "require_once (__DIR__ . '/config/database.php');",
            $content
        );
        
        if ($content !== $original) {
            file_put_contents($file, $content);
            echo "<p style='color:green'>✅ Fixed: $file</p>";
            $fixed++;
        }
    }
}

echo "<h3>Total files fixed: $fixed</h3>";
echo "<p><a href='test_student_dashboard.php'>Run test again</a></p>";
?>