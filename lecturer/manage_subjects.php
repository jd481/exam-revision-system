<?php
session_start();
require_once '../config/database.php';

// Ensure only lecturers access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'lecturer') {
    header("Location: ../auth/login.php");
    exit();
}

$error = '';
$lecturer_id = $_SESSION['user_id'];

// Handle delete request
if (isset($_GET['delete'])) {
    $subject_id = (int)$_GET['delete'];

    // Check if subject has papers before deleting
    $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM past_papers WHERE subject_id = ?");
    $check_stmt->execute([$subject_id]);
    $paper_count = $check_stmt->fetchColumn();
    
    if ($paper_count > 0) {
        $error = "Cannot delete subject with existing past papers. Delete the papers first.";
    } else {
        // Only allow deleting subjects that belong to this lecturer
        $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ? AND lecturer_id = ?");
        if ($stmt->execute([$subject_id, $lecturer_id])) {
            header("Location: manage_subjects.php?success=Subject deleted successfully");
            exit();
        } else {
            $error = "Error deleting subject.";
        }
    }
}

// Handle edit/update request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_id'])) {
    $subject_id = (int)$_POST['edit_id'];
    $subject_name = trim($_POST['subject_name']);
    
    if (!empty($subject_name)) {
        $stmt = $pdo->prepare("UPDATE subjects SET subject_name = ? WHERE id = ? AND lecturer_id = ?");
        if ($stmt->execute([$subject_name, $subject_id, $lecturer_id])) {
            header("Location: manage_subjects.php?success=Subject updated successfully");
            exit();
        } else {
            $error = "Error updating subject.";
        }
    } else {
        $error = "Please enter a subject name.";
    }
}

// Fetch all subjects for this lecturer with paper counts
$stmt = $pdo->prepare("
    SELECT s.*, 
           COUNT(p.id) as paper_count 
    FROM subjects s 
    LEFT JOIN past_papers p ON s.id = p.subject_id 
    WHERE s.lecturer_id = ? 
    GROUP BY s.id 
    ORDER BY s.id DESC
");
$stmt->execute([$lecturer_id]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Subjects - KAFU Exam Revision</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            background: #f0f4f0; 
        }

        /* Navigation Bar */
        .navbar {
            background: #008751;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .logo {
            font-size: 1.3rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo span {
            background: rgba(255,255,255,0.2);
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-name {
            color: white;
            text-decoration: none;
            padding: 5px 10px;
            border-radius: 4px;
            background: rgba(255,255,255,0.1);
        }

        .logout-btn {
            background: #dc3545;
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 4px;
            transition: background 0.3s;
        }

        .logout-btn:hover {
            background: #a71d2a;
        }

        /* Main Container */
        .container { 
            max-width: 900px; 
            width: 95%; 
            margin: 30px auto; 
        }

        /* Header Section */
        .page-header {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,135,81,0.1);
            border-left: 5px solid #008751;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header h2 {
            color: #008751;
            font-size: 1.8rem;
        }

        .page-header p {
            color: #666;
            margin-top: 5px;
        }

        .add-btn {
            background: #008751;
            color: white;
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.3s;
        }

        .add-btn:hover {
            background: #00663d;
        }

        /* Messages */
        .message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .success { 
            background: #e8f5e9; 
            color: #008751; 
            border: 1px solid #008751; 
        }

        .error { 
            background: #ffebee; 
            color: #c62828; 
            border: 1px solid #ef5350; 
        }

        /* Table Styles */
        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        table { 
            width: 100%; 
            border-collapse: collapse; 
        }

        th { 
            background: #008751; 
            color: white; 
            padding: 15px; 
            text-align: left; 
            font-weight: 600;
            font-size: 0.95rem;
        }

        td { 
            padding: 15px; 
            border-bottom: 1px solid #e0e0e0; 
            color: #333;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background: #f9fff9;
        }

        /* Subject Name with Icon */
        .subject-name {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .subject-icon {
            width: 35px;
            height: 35px;
            background: #e8f5e9;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #008751;
            font-weight: bold;
        }

        /* Paper Count Badge */
        .paper-count {
            background: #e8f5e9;
            color: #008751;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
            display: inline-block;
        }

        .no-papers {
            color: #999;
            font-size: 0.85rem;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
        }

        .btn-edit {
            background: #008751;
            color: white;
        }

        .btn-edit:hover {
            background: #00663d;
            transform: translateY(-2px);
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background: #a71d2a;
            transform: translateY(-2px);
        }

        .btn-view {
            background: #4a5568;
            color: white;
        }

        .btn-view:hover {
            background: #2d3748;
            transform: translateY(-2px);
        }

        /* Edit Form */
        .edit-section {
            margin-top: 25px;
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,135,81,0.1);
            border-left: 5px solid #008751;
        }

        .edit-section h3 {
            color: #008751;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .edit-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .form-group label {
            color: #555;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .edit-form input { 
            padding: 12px; 
            border: 2px solid #e0e0e0; 
            border-radius: 8px; 
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .edit-form input:focus {
            border-color: #008751;
            outline: none;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .btn-update {
            background: #008751;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
        }

        .btn-update:hover {
            background: #00663d;
        }

        .btn-cancel {
            background: #6c757d;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
        }

        .btn-cancel:hover {
            background: #545b62;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state .icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h3 {
            color: #333;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #666;
            margin-bottom: 20px;
        }

        .empty-state .add-first-btn {
            background: #008751;
            color: white;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 25px;
            display: inline-block;
            transition: background 0.3s;
        }

        .empty-state .add-first-btn:hover {
            background: #00663d;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            
            .user-menu {
                width: 100%;
                justify-content: center;
            }
            
            .page-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            th, td { 
                font-size: 14px; 
                padding: 10px; 
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                padding: 6px 10px;
                font-size: 12px;
            }
            
            .subject-icon {
                width: 25px;
                height: 25px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <div class="navbar">
        <div class="logo">
            📚 Exam Revision System
            <span>Manage Subjects</span>
        </div>
        <div class="user-menu">
            <span class="user-name">👤 <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Lecturer'); ?></span>
            <a href="../auth/logout.php" class="logout-btn">🚪 Logout</a>
        </div>
    </div>

    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h2>📋 Manage Subjects</h2>
                <p>View, edit, and organize your teaching subjects</p>
            </div>
            <a href="add_subject.php" class="add-btn">
                ➕ Add New Subject
            </a>
        </div>

        <!-- Messages -->
        <?php if (!empty($error)): ?>
            <div class="message error">
                <span>⚠️</span>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
            <div class="message success" id="successMessage">
                <span>✅</span>
                <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
        <?php endif; ?>

        <!-- Subjects Table -->
        <div class="table-container">
            <?php if (count($subjects) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Subject Name</th>
                            <th>Past Papers</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subjects as $subject): ?>
                            <tr>
                                <td>#<?php echo $subject['id']; ?></td>
                                <td>
                                    <div class="subject-name">
                                        <div class="subject-icon">
                                            <?php echo strtoupper(substr($subject['subject_name'], 0, 1)); ?>
                                        </div>
                                        <?php echo htmlspecialchars($subject['subject_name']); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($subject['paper_count'] > 0): ?>
                                        <span class="paper-count">
                                            📄 <?php echo $subject['paper_count']; ?> paper<?php echo $subject['paper_count'] > 1 ? 's' : ''; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="no-papers">No papers yet</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?edit=<?php echo $subject['id']; ?>" class="btn btn-edit">
                                            ✏️ Edit
                                        </a>
                                        <?php if ($subject['paper_count'] == 0): ?>
                                            <a href="?delete=<?php echo $subject['id']; ?>" 
                                               class="btn btn-delete"
                                               onclick="return confirm('Are you sure you want to delete this subject? This action cannot be undone.');">
                                                🗑️ Delete
                                            </a>
                                        <?php else: ?>
                                            <a href="past_papers.php?subject=<?php echo $subject['id']; ?>" 
                                               class="btn btn-view">
                                                👁️ View Papers
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <div class="icon">📚</div>
                    <h3>No Subjects Yet</h3>
                    <p>Get started by adding your first teaching subject.</p>
                    <a href="add_subject.php" class="add-first-btn">➕ Add Your First Subject</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Edit Form Section -->
        <?php
        if (isset($_GET['edit'])):
            $edit_id = (int)$_GET['edit'];
            $stmt = $pdo->prepare("SELECT * FROM subjects WHERE id = ? AND lecturer_id = ?");
            $stmt->execute([$edit_id, $lecturer_id]);
            $edit_subject = $stmt->fetch();
            if ($edit_subject):
        ?>
            <div class="edit-section">
                <h3>✏️ Edit Subject</h3>
                <form method="POST" class="edit-form">
                    <input type="hidden" name="edit_id" value="<?php echo $edit_subject['id']; ?>">
                    
                    <div class="form-group">
                        <label>Subject Name</label>
                        <input type="text" 
                               name="subject_name" 
                               value="<?php echo htmlspecialchars($edit_subject['subject_name']); ?>" 
                               required 
                               placeholder="e.g., Computer Science, Mathematics, etc."
                               autofocus>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-update">
                            ✅ Update Subject
                        </button>
                        <a href="manage_subjects.php" class="btn-cancel">
                            ❌ Cancel
                        </a>
                    </div>
                </form>
            </div>
        <?php
            endif;
        endif;
        ?>
    </div>

    <script>
        // Auto-hide success message
        const successDiv = document.getElementById('successMessage');
        if (successDiv) {
            setTimeout(() => {
                successDiv.style.transition = 'opacity 1s';
                successDiv.style.opacity = '0';
                setTimeout(() => { 
                    successDiv.style.display = 'none'; 
                }, 1000);
            }, 3000);
        }

        // Confirm delete for subjects with papers (additional safety)
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('⚠️ Are you sure you want to delete this subject? This action cannot be undone.')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>