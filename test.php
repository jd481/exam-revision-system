<?php
echo "Current directory: " . __DIR__ . "<br>";
echo "Auth folder exists: " . (is_dir('auth') ? 'YES' : 'NO') . "<br>";
echo "auth/login.php exists: " . (file_exists('auth/login.php') ? 'YES' : 'NO') . "<br>";
echo "auth/register.php exists: " . (file_exists('auth/register.php') ? 'YES' : 'NO') . "<br>";
echo "config folder exists: " . (is_dir('config') ? 'YES' : 'NO') . "<br>";
echo "config/database.php exists: " . (file_exists('config/database.php') ? 'YES' : 'NO') . "<br>";
?>