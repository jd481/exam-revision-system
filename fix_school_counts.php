<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once(__DIR__ . '/config/database.php');

echo "<h1>🔧 Fix School Counts</h1>";

// Get lecturer's school
$lecturer_id = $_SESSION['user_id'] ?? 1; // Adjust as needed
$stmt = $pdo->prepare("SELECT school_id FROM users WHERE id = ?");
$stmt->execute([$lecturer_id]);
$correct_school_id = $stmt->fetchColumn();

if (!$correct_school_id) {
    $correct_school_id = 2; // Default to School of Computing
}

echo "<p>Correct school_id should be: <strong>$correct_school_id</strong></p>";

// Fix subjects
$stmt = $pdo->prepare("UPDATE subjects SET school_id = ? WHERE school_id IS NULL OR school_id = 0");
$stmt->execute([$correct_school_id]);
echo "<p>✅ Updated " . $stmt->rowCount() . " subjects</p>";

// Fix papers
$stmt = $pdo->prepare("UPDATE past_papers SET school_id = ? WHERE school_id IS NULL OR school_id = 0");
$stmt->execute([$correct_school_id]);
echo "<p>✅ Updated " . $stmt->rowCount() . " papers</p>";

// Show current counts
$stmt = $pdo->prepare("SELECT COUNT(*) FROM subjects WHERE school_id = ?");
$stmt->execute([$correct_school_id]);
$subject_count = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM past_papers WHERE school_id = ?");
$stmt->execute([$correct_school_id]);
$paper_count = $stmt->fetchColumn();

echo "<h2>Current Counts for School ID $correct_school_id:</h2>";
echo "<p>Subjects: $subject_count</p>";
echo "<p>Papers: $paper_count</p>";
?>