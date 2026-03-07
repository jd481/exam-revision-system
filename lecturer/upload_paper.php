<?php
session_start();
require_once '../config/database.php';

// Ensure only lecturers access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'lecturer') {
    header("Location: ../auth/login.php");
    exit();
}

$lecturer_id = $_SESSION['user_id'];
$message = "";
$message_type = ""; // 'success' or 'error'

/* Fetch subjects created by this lecturer */
$stmt = $pdo->prepare("SELECT id, subject_name FROM subjects WHERE lecturer_id = ? ORDER BY subject_name");
$stmt->execute([$lecturer_id]);
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Handle form submission */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $subject_id = $_POST["subject_id"] ?? "";
    $title = trim($_POST["title"] ?? "");

    if (empty($subject_id) || empty($title)) {
        $message = "Please fill in all fields.";
        $message_type = "error";
    } 
    elseif(isset($_FILES["paper_file"]) && $_FILES["paper_file"]["error"] == 0){

        $file = $_FILES["paper_file"]["name"];
        $tmp = $_FILES["paper_file"]["tmp_name"];
        $size = $_FILES["paper_file"]["size"];

        /* Correct upload folder */
        $uploadDir = "../uploads/past_papers/";

        /* Ensure folder exists */
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        /* Allowed file types */
        $allowed_types = ['jpg','jpeg','png','pdf'];
        $file_extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        /* Validate file type */
        if (!in_array($file_extension, $allowed_types)) {

            $message = "Only JPG, JPEG, PNG, and PDF files are allowed.";
            $message_type = "error";

        /* Validate file size (20MB max) */
        } elseif ($size > 20 * 1024 * 1024) {

            $message = "File is too large. Maximum size is 20MB.";
            $message_type = "error";

        } else {

            /* Safer filename */
            $safe_name = preg_replace("/[^a-zA-Z0-9\._-]/", "", $file);
            $filename = time() . "_" . $safe_name;

            $destination = $uploadDir . $filename;

            if(move_uploaded_file($tmp, $destination)){

                $stmt = $pdo->prepare("
                    INSERT INTO past_papers 
                    (lecturer_id, subject_id, paper_title, file_path) 
                    VALUES (?, ?, ?, ?)
                ");

                $stmt->execute([
                    $lecturer_id,
                    $subject_id,
                    $title,
                    $filename
                ]);

                $message = "Past paper uploaded successfully!";
                $message_type = "success";
                
                // Clear form data on success
                $_POST = array();
                
            } else {

                $message = "Upload failed. Please try again.";
                $message_type = "error";

            }
        }

    } else {

        $message = "Please select a file.";
        $message_type = "error";

    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Upload Past Paper - Exam Revision</title>
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
        max-width: 500px;
        width: 95%;
        margin: 40px auto;
    }

    /* Page Header */
    .page-header {
        background: white;
        padding: 25px;
        border-radius: 12px;
        margin-bottom: 25px;
        box-shadow: 0 4px 15px rgba(0,135,81,0.1);
        border-left: 5px solid #008751;
        text-align: center;
    }

    .page-header h2 {
        color: #008751;
        font-size: 1.8rem;
        margin-bottom: 10px;
    }

    .page-header p {
        color: #666;
    }

    /* Upload Form */
    .upload-form {
        background: white;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        color: #555;
        font-weight: 600;
        font-size: 0.95rem;
    }

    .form-group label i {
        color: #008751;
        margin-right: 5px;
    }

    select, input[type="text"], input[type="file"] {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 1rem;
        transition: all 0.3s;
        background: #f9f9f9;
    }

    select:focus, input[type="text"]:focus, input[type="file"]:focus {
        border-color: #008751;
        outline: none;
        background: white;
        box-shadow: 0 0 0 3px rgba(0,135,81,0.1);
    }

    select {
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 15px center;
        background-size: 15px;
    }

    /* File Input Styling */
    .file-input-wrapper {
        position: relative;
    }

    .file-input-wrapper input[type="file"] {
        padding: 10px;
        background: white;
    }

    .file-input-wrapper small {
        display: block;
        margin-top: 8px;
        color: #666;
        font-size: 0.85rem;
    }

    /* Requirements Box */
    .requirements {
        background: #e8f5e9;
        padding: 15px;
        border-radius: 8px;
        margin: 20px 0;
        border-left: 4px solid #008751;
    }

    .requirements h4 {
        color: #008751;
        margin-bottom: 10px;
        font-size: 1rem;
    }

    .requirements ul {
        list-style: none;
        padding-left: 0;
    }

    .requirements li {
        color: #555;
        font-size: 0.9rem;
        margin-bottom: 5px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .requirements li:before {
        content: "✓";
        color: #008751;
        font-weight: bold;
    }

    /* Custom Dropdown */
    .custom-dropdown {
        position: relative;
        width: 100%;
    }

    .dropdown-button {
        width: 100%;
        padding: 15px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 1rem;
        color: #333;
        background: #f9f9f9;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .dropdown-button:hover {
        border-color: #008751;
        background: white;
    }

    .dropdown-button:focus {
        border-color: #008751;
        outline: none;
        background: white;
        box-shadow: 0 0 0 3px rgba(0,135,81,0.1);
    }

    .dropdown-arrow {
        font-size: 0.8rem;
        color: #008751;
        transition: transform 0.3s;
    }

    .dropdown-button.open .dropdown-arrow {
        transform: rotate(180deg);
    }

    .dropdown-menu {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 2px solid #008751;
        border-top: none;
        border-radius: 0 0 8px 8px;
        max-height: 300px;
        overflow-y: auto;
        z-index: 1000;
        display: none;
        box-shadow: 0 4px 15px rgba(0,135,81,0.1);
    }

    .dropdown-menu.open {
        display: block;
    }

    .dropdown-item {
        padding: 15px;
        cursor: pointer;
        color: #333;
        transition: all 0.2s;
        border-bottom: 1px solid #e0e0e0;
    }

    .dropdown-item:last-child {
        border-bottom: none;
    }

    .dropdown-item:hover {
        background: #e8f5e9;
        color: #008751;
        padding-left: 20px;
    }

    .dropdown-item.selected {
        background: #e8f5e9;
        color: #008751;
        font-weight: bold;
    }

    /* Submit Button */
    .submit-btn {
        width: 100%;
        padding: 15px;
        background: #008751;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 1.1rem;
        font-weight: bold;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        transition: all 0.3s;
        margin-top: 20px;
    }

    .submit-btn:hover {
        background: #00663d;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,135,81,0.3);
    }

    .submit-btn:active {
        transform: translateY(0);
    }

    .submit-btn i {
        font-size: 1.2rem;
    }

    /* Message Alerts */
    .message {
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 25px;
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

    .message.success {
        background: #e8f5e9;
        color: #008751;
        border: 1px solid #008751;
    }

    .message.error {
        background: #ffebee;
        color: #c62828;
        border: 1px solid #ef5350;
    }

    /* Quick Actions */
    .quick-actions {
        display: flex;
        gap: 15px;
        margin-top: 25px;
        justify-content: center;
    }

    .quick-action-btn {
        background: white;
        color: #008751;
        text-decoration: none;
        padding: 12px 20px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.95rem;
        transition: all 0.3s;
        border: 1px solid #008751;
    }

    .quick-action-btn:hover {
        background: #008751;
        color: white;
        transform: translateY(-2px);
    }

    /* No Subjects Message */
    .no-subjects {
        text-align: center;
        padding: 30px;
    }

    .no-subjects .icon {
        font-size: 48px;
        margin-bottom: 15px;
        opacity: 0.5;
    }

    .no-subjects h3 {
        color: #333;
        margin-bottom: 10px;
    }

    .no-subjects p {
        color: #666;
        margin-bottom: 20px;
    }

    .add-subject-link {
        background: #008751;
        color: white;
        text-decoration: none;
        padding: 12px 25px;
        border-radius: 25px;
        display: inline-block;
        transition: background 0.3s;
    }

    .add-subject-link:hover {
        background: #00663d;
    }

    /* Responsive */
    @media (max-width: 600px) {
        .navbar {
            flex-direction: column;
            gap: 10px;
            text-align: center;
        }
        
        .user-menu {
            width: 100%;
            justify-content: center;
        }
        
        .upload-form {
            padding: 20px;
        }
        
        .quick-actions {
            flex-direction: column;
        }
        
        .quick-action-btn {
            justify-content: center;
        }
    }
</style>
</head>
<body>

<!-- Navigation Bar -->
<div class="navbar">
    <div class="logo">
        📚 Exam Revision System
        <span>Upload Paper</span>
    </div>
    <div class="user-menu">
        <span class="user-name">👤 <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Lecturer'); ?></span>
        <a href="../auth/logout.php" class="logout-btn">🚪 Logout</a>
    </div>
</div>

<div class="container">
    <!-- Page Header -->
    <div class="page-header">
        <h2>📤 Upload Past Paper</h2>
        <p>Share past exam papers with your students</p>
    </div>

    <!-- Check if subjects exist -->
    <?php if (count($subjects) > 0): ?>
        
        <!-- Upload Form -->
        <div class="upload-form">
            
            <!-- Message Display -->
            <?php if($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <span><?php echo $message_type == 'success' ? '✅' : '⚠️'; ?></span>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <!-- Subject Selection -->
                <div class="form-group">
                    <label><i>📚</i> Select Subject</label>
                    <div class="custom-dropdown">
                        <button type="button" class="dropdown-button" id="dropdownBtnUpload">
                            <span id="dropdownLabelUpload">-- Choose a subject --</span>
                            <span class="dropdown-arrow">▼</span>
                        </button>
                        <div class="dropdown-menu" id="dropdownMenuUpload">
                            <div class="dropdown-item" data-value="">-- Choose a subject --</div>
                            <?php foreach($subjects as $subject): ?>
                                <div class="dropdown-item" data-value="<?php echo $subject['id']; ?>">
                                    <?php echo htmlspecialchars($subject['subject_name']); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <input type="hidden" name="subject_id" id="subjectIdInputUpload">
                </div>

                <!-- Paper Title -->
                <div class="form-group">
                    <label><i>📌</i> Paper Title</label>
                    <input type="text" 
                           name="title" 
                           placeholder="e.g., Computer Science Final Exam 2023" 
                           value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>"
                           required>
                </div>

                <!-- File Upload -->
                <div class="form-group">
                    <label><i>📎</i> Upload File</label>
                    <div class="file-input-wrapper">
                        <input type="file" 
                               name="paper_file" 
                               accept=".jpg,.jpeg,.png,.pdf" 
                               required>
                        <small>Supported formats: JPG, JPEG, PNG, PDF (Max 20MB)</small>
                    </div>
                </div>

                <!-- Requirements Box -->
                <div class="requirements">
                    <h4>📋 File Requirements:</h4>
                    <ul>
                        <li>Maximum file size: 20MB</li>
                        <li>Allowed formats: JPG, JPEG, PNG, PDF</li>
                        <li>Use clear, descriptive titles</li>
                        <li>Files are automatically renamed for security</li>
                    </ul>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="submit-btn">
                    <span>📤</span>
                    Upload Paper
                </button>
            </form>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <a href="manage_subjects.php" class="quick-action-btn">
                    <span>📋</span>
                    Manage Subjects
                </a>
                <a href="past_papers.php" class="quick-action-btn">
                    <span>📑</span>
                    View My Papers
                </a>
            </div>
        </div>

    <?php else: ?>
        <!-- No Subjects Message -->
        <div class="upload-form">
            <div class="no-subjects">
                <div class="icon">📚</div>
                <h3>No Subjects Found</h3>
                <p>You need to create a subject before uploading past papers.</p>
                <a href="add_subject.php" class="add-subject-link">➕ Add Your First Subject</a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Recent Uploads Preview (Optional Enhancement) -->
    <?php if (count($subjects) > 0): 
        // Get recent uploads for this lecturer
        $recent_stmt = $pdo->prepare("
            SELECT past_papers.*, subjects.subject_name 
            FROM past_papers 
            JOIN subjects ON past_papers.subject_id = subjects.id
            WHERE subjects.lecturer_id = ?
            ORDER BY past_papers.id DESC 
            LIMIT 3
        ");
        $recent_stmt->execute([$lecturer_id]);
        $recent_papers = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($recent_papers) > 0): 
    ?>
        <div style="margin-top: 30px; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
            <h3 style="color: #008751; margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                <span>⏱️</span> Recent Uploads
            </h3>
            <div style="display: grid; gap: 10px;">
                <?php foreach ($recent_papers as $paper): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: #f9fff9; border-radius: 6px;">
                        <div>
                            <strong style="color: #333;"><?php echo htmlspecialchars($paper['subject_name']); ?></strong>
                            <span style="color: #666; margin-left: 10px;">- <?php echo htmlspecialchars($paper['paper_title']); ?></span>
                        </div>
                        <a href="../uploads/past_papers/<?php echo htmlspecialchars($paper['file_path']); ?>" 
                           target="_blank"
                           style="color: #008751; text-decoration: none;">
                            👁️ View
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php 
        endif; 
    endif; 
    ?>
</div>

<script>
// Auto-hide message after 5 seconds
setTimeout(function() {
    const messageDiv = document.querySelector('.message');
    if (messageDiv) {
        messageDiv.style.transition = 'opacity 1s';
        messageDiv.style.opacity = '0';
        setTimeout(() => {
            messageDiv.style.display = 'none';
        }, 1000);
    }
}, 5000);

// File size validation on client side
document.querySelector('input[type="file"]').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const maxSize = 20 * 1024 * 1024; // 20MB in bytes
        if (file.size > maxSize) {
            alert('File is too large! Maximum size is 20MB.');
            this.value = ''; // Clear the file input
        }
    }
});

// Confirmation before leaving with unsaved changes
let formChanged = false;
document.querySelectorAll('select, input[type="text"], input[type="file"]').forEach(element => {
    element.addEventListener('change', () => formChanged = true);
});

window.addEventListener('beforeunload', function(e) {
    if (formChanged) {
        e.preventDefault();
        e.returnValue = '';
    }
});

// Reset form changed flag on submit
document.querySelector('form').addEventListener('submit', function() {
    formChanged = false;
});

// Custom dropdown handler for upload page
const dropdownBtnUpload = document.getElementById('dropdownBtnUpload');
const dropdownMenuUpload = document.getElementById('dropdownMenuUpload');
const dropdownItemsUpload = document.querySelectorAll('#dropdownMenuUpload .dropdown-item');
const dropdownLabelUpload = document.getElementById('dropdownLabelUpload');
const subjectIdInputUpload = document.getElementById('subjectIdInputUpload');

if(dropdownBtnUpload) {
    dropdownBtnUpload.addEventListener('click', function(e) {
        e.stopPropagation();
        dropdownBtnUpload.classList.toggle('open');
        dropdownMenuUpload.classList.toggle('open');
    });
}

if(dropdownItemsUpload.length) {
    dropdownItemsUpload.forEach(item => {
        // Pre-select if post value matches
        if (item.dataset.value === '<?php echo $_POST['subject_id'] ?? ''; ?>') {
            item.classList.add('selected');
            dropdownLabelUpload.textContent = item.textContent;
            subjectIdInputUpload.value = item.dataset.value;
        }

        item.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdownItemsUpload.forEach(i => i.classList.remove('selected'));
            this.classList.add('selected');
            dropdownLabelUpload.textContent = this.textContent;
            subjectIdInputUpload.value = this.dataset.value;
            dropdownBtnUpload.classList.remove('open');
            dropdownMenuUpload.classList.remove('open');
        });
    });
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.custom-dropdown')) {
        dropdownBtnUpload?.classList.remove('open');
        dropdownMenuUpload?.classList.remove('open');
    }
});
</script>

</body>
</html>