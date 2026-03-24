<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Fixing File Paths Automatically</h1>";

$folders_to_fix = ['student', 'lecturer', 'auth'];
$fixed_count = 0;
$error_count = 0;

foreach ($folders_to_fix as $folder) {
    if (is_dir($folder)) {
        echo "<h2>📁 Checking $folder folder...</h2>";
        $files = glob("$folder/*.php");
        
        if (count($files) == 0) {
            echo "<p style='color:orange'>⚠️ No PHP files found in $folder</p>";
            continue;
        }
        
        foreach ($files as $file) {
            $content = file_get_contents($file);
            $original = $content;
            
            // Replace various incorrect require patterns
            $patterns = [
                // Pattern 1: require_once '../config/database.php';
                "/require_once\s+['\"]\.\.\/config\/database\.php['\"];\s*/",
                
                // Pattern 2: require_once "../config/database.php";
                "/require_once\s+['\"]\.\.\/config\/database\.php['\"];\s*/",
                
                // Pattern 3: include '../config/database.php';
                "/include\s+['\"]\.\.\/config\/database\.php['\"];\s*/",
                
                // Pattern 4: include_once '../config/database.php';
                "/include_once\s+['\"]\.\.\/config\/database\.php['\"];\s*/",
                
                // Pattern 5: require('../config/database.php');
                "/require\s*\(\s*['\"]\.\.\/config\/database\.php['\"]\s*\)\s*;\s*/",
                
                // Pattern 6: include('../config/database.php');
                "/include\s*\(\s*['\"]\.\.\/config\/database\.php['\"]\s*\)\s*;\s*/"
            ];
            
            $replacement = "require_once __DIR__ . '/../config/database.php';";
            
            foreach ($patterns as $pattern) {
                $content = preg_replace($pattern, $replacement . "\n", $content);
            }
            
            // Also check for any remaining '../config/' patterns
            if (strpos($content, '../config/') !== false) {
                $content = str_replace('../config/', __DIR__ . '/../config/', $content);
            }
            
            // Add error reporting if missing
            if (strpos($content, 'error_reporting') === false) {
                $content = preg_replace(
                    '/<\?php/',
                    "<?php\nerror_reporting(E_ALL);\nini_set('display_errors', 1);",
                    $content,
                    1
                );
            }
            
            if ($content !== $original) {
                if (file_put_contents($file, $content)) {
                    echo "<p style='color:green'>✅ Fixed: " . basename($file) . "</p>";
                    $fixed_count++;
                } else {
                    echo "<p style='color:red'>❌ Failed to write: " . basename($file) . "</p>";
                    $error_count++;
                }
            }
        }
    } else {
        echo "<p style='color:orange'>⚠️ Folder '$folder' not found</p>";
    }
}

// Also check root files
echo "<h2>📁 Checking root files...</h2>";
$root_files = ['index.php', 'track_download.php', 'toggle_favorite.php'];
foreach ($root_files as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $original = $content;
        
        // Fix root file paths
        $content = str_replace(
            "require_once 'config/database.php';",
            "require_once __DIR__ . '/config/database.php';",
            $content
        );
        
        if ($content !== $original) {
            file_put_contents($file, $content);
            echo "<p style='color:green'>✅ Fixed: $file</p>";
            $fixed_count++;
        }
    }
}

echo "<h2>📊 Summary</h2>";
echo "<ul>";
echo "<li>✅ Files fixed: $fixed_count</li>";
if ($error_count > 0) {
    echo "<li style='color:red'>❌ Errors: $error_count</li>";
}
echo "</ul>";

if ($fixed_count > 0 || $error_count > 0) {
    echo "<h3>✅ Path fixes applied!</h3>";
} else {
    echo "<h3>✅ No fixes needed - all files already use __DIR__</h3>";
}

echo "<p style='margin-top: 20px;'>";
echo "<a href='index.php' style='background: #008751; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏠 Go to Homepage</a> ";
echo "<a href='student/dashboard.php' style='background: #008751; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>👨‍🎓 Test Student Dashboard</a>";
echo "</p>";
?>