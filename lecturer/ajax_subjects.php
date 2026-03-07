<?php
session_start();
require_once '../config/database.php';

// Set JSON header
header('Content-Type: application/json');

// Only allow logged-in lecturers
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'lecturer') {
    echo json_encode(['status' => 'error', 'message' => 'Access denied']);
    exit();
}

// Default response
$response = ['status' => 'error', 'message' => 'Invalid request'];

// Process AJAX actions
if (isset($_POST['action'])) {
    $action = $_POST['action'];

    // DELETE subject
    if ($action === 'delete' && isset($_POST['id'])) {
        $id = (int)$_POST['id'];
        
        // First check if subject has any past papers
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM past_papers WHERE subject_id = ?");
        $check_stmt->execute([$id]);
        $paper_count = $check_stmt->fetchColumn();
        
        if ($paper_count > 0) {
            $response = [
                'status' => 'error', 
                'message' => 'Cannot delete subject with existing past papers. Delete the papers first.'
            ];
        } else {
            $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ? AND lecturer_id = ?");
            if ($stmt->execute([$id, $_SESSION['user_id']])) {
                $response = [
                    'status' => 'success', 
                    'message' => 'Subject deleted successfully',
                    'color' => '#008751'
                ];
            } else {
                $response = [
                    'status' => 'error', 
                    'message' => 'Error deleting subject'
                ];
            }
        }
    }

    // EDIT subject
    if ($action === 'edit' && isset($_POST['id'], $_POST['subject_name'])) {
        $id = (int)$_POST['id'];
        $subject_name = trim($_POST['subject_name']);
        
        if (!empty($subject_name)) {
            // Check if subject belongs to this lecturer
            $check_stmt = $pdo->prepare("SELECT id FROM subjects WHERE id = ? AND lecturer_id = ?");
            $check_stmt->execute([$id, $_SESSION['user_id']]);
            
            if ($check_stmt->fetch()) {
                $stmt = $pdo->prepare("UPDATE subjects SET subject_name = ? WHERE id = ?");
                if ($stmt->execute([$subject_name, $id])) {
                    $response = [
                        'status' => 'success', 
                        'message' => 'Subject updated successfully',
                        'color' => '#008751'
                    ];
                } else {
                    $response = [
                        'status' => 'error', 
                        'message' => 'Error updating subject'
                    ];
                }
            } else {
                $response = [
                    'status' => 'error', 
                    'message' => 'Subject not found or access denied'
                ];
            }
        } else {
            $response = [
                'status' => 'error', 
                'message' => 'Subject name cannot be empty'
            ];
        }
    }
    
    // ADD new subject (optional - if you want to handle AJAX add)
    if ($action === 'add' && isset($_POST['subject_name'])) {
        $subject_name = trim($_POST['subject_name']);
        
        if (!empty($subject_name)) {
            // Check if subject already exists for this lecturer
            $check_stmt = $pdo->prepare("SELECT id FROM subjects WHERE subject_name = ? AND lecturer_id = ?");
            $check_stmt->execute([$subject_name, $_SESSION['user_id']]);
            
            if ($check_stmt->fetch()) {
                $response = [
                    'status' => 'error', 
                    'message' => 'Subject already exists'
                ];
            } else {
                $stmt = $pdo->prepare("INSERT INTO subjects (subject_name, lecturer_id) VALUES (?, ?)");
                if ($stmt->execute([$subject_name, $_SESSION['user_id']])) {
                    $response = [
                        'status' => 'success', 
                        'message' => 'Subject added successfully',
                        'color' => '#008751',
                        'id' => $pdo->lastInsertId()
                    ];
                } else {
                    $response = [
                        'status' => 'error', 
                        'message' => 'Error adding subject'
                    ];
                }
            }
        } else {
            $response = [
                'status' => 'error', 
                'message' => 'Subject name cannot be empty'
            ];
        }
    }
    
    // GET subjects list (optional - for AJAX loading)
    if ($action === 'get_list') {
        $stmt = $pdo->prepare("SELECT * FROM subjects WHERE lecturer_id = ? ORDER BY subject_name");
        $stmt->execute([$_SESSION['user_id']]);
        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response = [
            'status' => 'success',
            'subjects' => $subjects
        ];
    }
}

echo json_encode($response);
?>