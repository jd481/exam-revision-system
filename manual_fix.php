<?php
echo "<h1>🔧 Manual Path Fix</h1>";

$files_to_fix = [
    'lecturer/dashboard.php',
    'lecturer/add_subject.php',
    'lecturer/manage_subjects.php',
    'lecturer/upload_paper.php',
    'lecturer/past_papers.php',
    'lecturer/delete_paper.php',
    'student/dashboard.php',
    'student/view_papers.php',
    'student/browse_by_subject.php',
    'student/search_papers.php',
    'student/recent_uploads.php',
    'student/my_downloads.php',
    'student/favorites.php',
    'student/help.php',
    'auth/login.php',
    'auth/register.php',
    'auth/logout.php'
];

$fixed = 0;
foreach ($files_to_fix as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $original = $content;
        
        // Fix any incorrect paths
        $content = preg_replace(
            "/require_once\s+__DIR__\s*\.\s*'[^']*config[^']*';/",
            "require_once __DIR__ . '/../config/database.php';",
            $content
        );
        
        // Also fix any remaining old-style paths
        $content = preg_replace(
            "/require_once\s+['\"]\.\.\/config\/database\.php['\"];/",
            "require_once __DIR__ . '/../config/database.php';",
            $content
        );
        
        if ($content !== $original) {
            file_put_contents($file, $content);
            echo "<p style='color:green'>✅ Fixed: $file</p>";
            $fixed++;
        }
    }
}

echo "<h3>Fixed $fixed files</h3>";
echo "<p><a href='index.php'>Go to Homepage</a></p>";
?>