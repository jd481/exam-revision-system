<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

if (isset($_POST['paper_id'])) {
    $user_id = $_SESSION['user_id'];
    $paper_id = (int)$_POST['paper_id'];
    
    try {
        // Check if download already tracked
        $stmt = $pdo->prepare("SELECT id FROM downloads WHERE user_id = ? AND paper_id = ?");
        $stmt->execute([$user_id, $paper_id]);
        
        if (!$stmt->fetch()) {
            // Insert new download record
            $stmt = $pdo->prepare("INSERT INTO downloads (user_id, paper_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $paper_id]);
            echo json_encode(['success' => true, 'message' => 'Download tracked']);
        } else {
            echo json_encode(['success' => true, 'message' => 'Already tracked']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No paper ID provided']);
}
?>