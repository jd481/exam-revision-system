<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
$host = "localhost";
$dbname = "exam_revision_system";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}