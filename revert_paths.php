<?php
echo "<h1>🔄 Reverting Paths</h1>";

$folders = ['student', 'lecturer', 'auth'];
$fixed = 0;

foreach ($folders as $folder) {
    if (is_dir($folder)) {
        $files = glob("$folder/*.php");
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $original = $content;
            
            // Replace __DIR__ paths with simple relative paths
            $content = preg_replace(
                "/require_once\s+__DIR__\s*\.\s*'\/\.\.\/config\/database\.php';/",
                "require_once '../config/database.php';",
                $content
            );
            
            // Also catch any other variations
            $content = str_replace(
                "require_once __DIR__ . '/../config/database.php';",
                "require_once '../config/database.php';",
                $content
            );
            
            if ($content !== $original) {
                file_put_contents($file, $content);
                echo "<p style='color:green'>✅ Reverted: $file</p>";
                $fixed++;
            }
        }
    }
}

// Fix root files
$root_files = ['index.php', 'track_download.php', 'toggle_favorite.php'];
foreach ($root_files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $original = $content;
        
        $content = str_replace(
            "require_once __DIR__ . '/config/database.php';",
            "require_once 'config/database.php';",
            $content
        );
        
        if ($content !== $original) {
            file_put_contents($file, $content);
            echo "<p style='color:green'>✅ Reverted: $file</p>";
            $fixed++;
        }
    }
}

echo "<h3>Total files reverted: $fixed</h3>";
echo "<p><a href='index.php'>Go to Homepage</a></p>";
?>