<?php
session_start();
require_once 'config/database.php';

echo "<h1>School System Test</h1>";

// Show all schools
$stmt = $pdo->query("SELECT * FROM schools");
$schools = $stmt->fetchAll();
echo "<h2>Schools:</h2>";
echo "<pre>";
print_r($schools);
echo "</pre>";

// Show users with their schools
$stmt = $pdo->query("
    SELECT u.id, u.full_name, u.email, u.role, s.school_name 
    FROM users u
    JOIN schools s ON u.school_id = s.id
    LIMIT 5
");
$users = $stmt->fetchAll();
echo "<h2>Users with Schools:</h2>";
echo "<pre>";
print_r($users);
echo "</pre>";
?>