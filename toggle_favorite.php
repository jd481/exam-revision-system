<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

// Check if paper_id was provided
if (!isset($_POST['paper_id'])) {
    echo json_encode(['success' => false, 'message' => 'No paper ID provided']);
    exit();
}

$user_id = $_SESSION['user_id'];
$paper_id = (int)$_POST['paper_id'];

try {
    // First, check if the paper exists
    $stmt = $pdo->prepare("SELECT id FROM past_papers WHERE id = ?");
    $stmt->execute([$paper_id]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Paper not found']);
        exit();
    }
    
    // Check if already favorited
    $stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND paper_id = ?");
    $stmt->execute([$user_id, $paper_id]);
    $is_favorited = $stmt->fetch();
    
    if ($is_favorited) {
        // Remove from favorites
        $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND paper_id = ?");
        $stmt->execute([$user_id, $paper_id]);
        echo json_encode(['success' => true, 'action' => 'removed', 'message' => 'Removed from favorites']);
    } else {
        // Add to favorites
        $stmt = $pdo->prepare("INSERT INTO favorites (user_id, paper_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $paper_id]);
        echo json_encode(['success' => true, 'action' => 'added', 'message' => 'Added to favorites']);
    }
    
} catch (PDOException $e) {
    error_log("Favorite toggle error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>