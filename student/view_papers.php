<?php
session_start();
require_once '../config/database.php';

/* Ensure only students access */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$student_name = $_SESSION['full_name'];

/* Fetch subjects */
$stmt = $pdo->query("SELECT id, subject_name FROM subjects ORDER BY subject_name");
$subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Get paper count for each subject */
$subject_counts = [];
foreach ($subjects as $subject) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM past_papers WHERE subject_id = ?");
    $stmt->execute([$subject['id']]);
    $subject_counts[$subject['id']] = $stmt->fetchColumn();
}

$papers = [];
$selected_subject_name = '';

if (isset($_GET['subject_id']) && !empty($_GET['subject_id'])) {

    $subject_id = $_GET['subject_id'];

    // Get subject name
    $stmt = $pdo->prepare("SELECT subject_name FROM subjects WHERE id = ?");
    $stmt->execute([$subject_id]);
    $selected_subject_name = $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT past_papers.*, subjects.subject_name
        FROM past_papers
        JOIN subjects ON past_papers.subject_id = subjects.id
        WHERE subject_id = ?
        ORDER BY past_papers.id DESC
    ");

    $stmt->execute([$subject_id]);
    $papers = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html>
<head>
<title>View Past Papers</title>
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
        max-width: 1200px;
        width: 95%;
        margin: 30px auto;
    }

    /* Page Header */
    .page-header {
        background: white;
        padding: 25px;
        border-radius: 12px;
        margin-bottom: 25px;
        box-shadow: 0 4px 15px rgba(0,135,81,0.1);
        border-left: 5px solid #008751;
    }

    .page-header h1 {
        color: #008751;
        font-size: 1.8rem;
        margin-bottom: 10px;
    }

    .page-header p {
        color: #666;
    }

    /* Subject Selection Card */
    .selection-card {
        background: white;
        padding: 25px;
        border-radius: 12px;
        margin-bottom: 30px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }

    .selection-card h3 {
        color: #008751;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 8px;
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

    /* Subjects Grid */
    .subjects-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 15px;
        margin-top: 20px;
    }

    .subject-card {
        background: #f9fff9;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 15px;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        color: inherit;
        display: block;
    }

    .subject-card:hover {
        border-color: #008751;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,135,81,0.1);
        background: #e8f5e9;
    }

    .subject-card.selected {
        border-color: #008751;
        background: #e8f5e9;
    }

    .subject-name {
        font-weight: bold;
        color: #333;
        margin-bottom: 5px;
    }

    .subject-card:hover .subject-name {
        color: #008751;
    }

    .paper-count {
        font-size: 0.85rem;
        color: #008751;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    /* Results Header */
    .results-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 15px;
    }

    .results-header h2 {
        color: #008751;
        font-size: 1.3rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .paper-count-badge {
        background: #008751;
        color: white;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 0.9rem;
    }

    /* Papers Grid */
    .papers-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 25px;
        margin-bottom: 30px;
    }

    .paper-card {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        transition: transform 0.3s, box-shadow 0.3s;
        border: 1px solid #eee;
    }

    .paper-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,135,81,0.15);
        border-color: #008751;
    }

    .paper-preview {
        height: 200px;
        background: #f5f5f5;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border-bottom: 1px solid #eee;
        cursor: pointer;
        position: relative;
    }

    .paper-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s;
    }

    .paper-preview:hover img {
        transform: scale(1.05);
    }

    .paper-preview:hover .preview-overlay {
        opacity: 1;
    }

    .preview-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,135,81,0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s;
        color: white;
        font-size: 1.2rem;
        gap: 10px;
    }

    .pdf-preview {
        background: #e8f5e9;
        color: #008751;
        font-size: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        width: 100%;
        position: relative;
    }

    .pdf-preview:hover .preview-overlay {
        opacity: 1;
    }

    .paper-details {
        padding: 20px;
    }

    .paper-details h3 {
        color: #333;
        margin-bottom: 8px;
        font-size: 1.2rem;
    }

    .paper-subject {
        color: #008751;
        font-size: 0.9rem;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .paper-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 15px 0;
        color: #666;
        font-size: 0.85rem;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }

    .paper-actions {
        display: flex;
        gap: 10px;
    }

    .btn {
        flex: 1;
        padding: 10px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 0.9rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        transition: all 0.3s;
    }

    .btn-view {
        background: #008751;
        color: white;
    }

    .btn-view:hover {
        background: #00663d;
    }

    .btn-download {
        background: white;
        color: #008751;
        border: 1px solid #008751;
    }

    .btn-download:hover {
        background: #e8f5e9;
    }

    /* PDF Options Modal */
    .pdf-options-modal {
        display: none;
        position: fixed;
        z-index: 2000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
        overflow: auto;
    }

    .pdf-options-content {
        background: white;
        margin: 15% auto;
        padding: 30px;
        border-radius: 12px;
        max-width: 500px;
        width: 90%;
        box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        position: relative;
        border-left: 5px solid #008751;
    }

    .pdf-options-content h3 {
        color: #008751;
        margin-bottom: 20px;
        text-align: center;
        font-size: 1.5rem;
    }

    .pdf-option-btn {
        width: 100%;
        padding: 15px;
        margin: 10px 0;
        background: #008751;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        transition: all 0.3s;
    }

    .pdf-option-btn:hover {
        background: #00663d;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,135,81,0.3);
    }

    .pdf-option-btn.secondary {
        background: #6c757d;
    }

    .pdf-option-btn.secondary:hover {
        background: #545b62;
    }

    .pdf-option-btn.danger {
        background: #dc3545;
    }

    .pdf-option-btn.danger:hover {
        background: #a71d2a;
    }

    .pdf-url-display {
        background: #f5f5f5;
        padding: 10px;
        border-radius: 4px;
        margin: 10px 0;
        font-size: 0.8rem;
        word-break: break-all;
        border: 1px solid #ddd;
    }

    .close-pdf-options {
        position: absolute;
        top: 10px;
        right: 15px;
        color: #aaa;
        font-size: 24px;
        font-weight: bold;
        cursor: pointer;
    }

    .close-pdf-options:hover {
        color: #008751;
    }

    /* Modal for images */
    .image-modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.9);
        overflow: auto;
    }

    .image-modal-content {
        margin: auto;
        display: block;
        max-width: 90%;
        max-height: 90%;
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }

    .image-modal-content img {
        width: 100%;
        height: auto;
        border: 3px solid white;
        border-radius: 8px;
    }

    .close-image-modal {
        position: absolute;
        top: 15px;
        right: 35px;
        color: #f1f1f1;
        font-size: 40px;
        font-weight: bold;
        cursor: pointer;
        z-index: 1001;
    }

    .close-image-modal:hover {
        color: #008751;
    }

    .image-modal-download {
        position: absolute;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        padding: 10px 20px;
        background: #008751;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 1rem;
        display: flex;
        align-items: center;
        gap: 5px;
        text-decoration: none;
        z-index: 1001;
    }

    .image-modal-download:hover {
        background: #00663d;
    }

    /* Empty State */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 12px;
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

    .browse-btn {
        background: #008751;
        color: white;
        text-decoration: none;
        padding: 12px 30px;
        border-radius: 25px;
        display: inline-block;
        transition: background 0.3s;
    }

    .browse-btn:hover {
        background: #00663d;
    }

    /* Back Button */
    .back-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #008751;
        text-decoration: none;
        padding: 10px 0;
        transition: transform 0.3s;
    }

    .back-btn:hover {
        transform: translateX(-5px);
    }

    /* Footer */
    .footer {
        text-align: center;
        margin-top: 50px;
        padding: 20px;
        color: #666;
        font-size: 0.9rem;
        border-top: 1px solid #ddd;
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
        
        .subjects-grid {
            grid-template-columns: 1fr 1fr;
        }
        
        .papers-grid {
            grid-template-columns: 1fr;
        }
        
        .paper-preview {
            height: 150px;
        }
        
        .pdf-options-content {
            margin: 30% auto;
            padding: 20px;
        }
    }
</style>
</head>
<body>

<!-- Navigation Bar -->
<div class="navbar">
    <div class="logo">
        📚 Exam Revision System
        <span>Student Portal</span>
    </div>
    <div class="user-menu">
        <span class="user-name">👤 <?php echo htmlspecialchars($student_name); ?></span>
        <a href="../auth/logout.php" class="logout-btn">🚪 Logout</a>
    </div>
</div>

<div class="container">
    <!-- Page Header -->
    <div class="page-header">
        <h1>📑 View Past Papers</h1>
        <p>Browse and view past exam papers by subject</p>
    </div>

    <!-- Subject Selection Card -->
    <div class="selection-card">
        <h3>
            <span>📚</span> Select a Subject
        </h3>

        <!-- Custom Dropdown Selection -->
        <div class="custom-dropdown">
            <button class="dropdown-button" type="button" id="dropdownBtn">
                <span id="dropdownLabel">-- Choose a subject --</span>
                <span class="dropdown-arrow">▼</span>
            </button>
            <div class="dropdown-menu" id="dropdownMenu">
                <div class="dropdown-item" data-value="">-- Choose a subject --</div>
                <?php foreach($subjects as $subject): ?>
                    <div class="dropdown-item" data-value="<?php echo $subject['id']; ?>">
                        <?php echo htmlspecialchars($subject['subject_name']); ?> 
                        (<?php echo $subject_counts[$subject['id']] ?? 0; ?> papers)
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <form method="GET" id="subjectForm" style="display:none;">
            <input type="hidden" name="subject_id" id="subjectIdInput">
        </form>

        <!-- Quick Subject Grid -->
        <div class="subjects-grid">
            <?php foreach(array_slice($subjects, 0, 6) as $subject): ?>
                <a href="?subject_id=<?php echo $subject['id']; ?>" 
                   class="subject-card <?php echo (isset($_GET['subject_id']) && $_GET['subject_id'] == $subject['id']) ? 'selected' : ''; ?>">
                    <div class="subject-name"><?php echo htmlspecialchars($subject['subject_name']); ?></div>
                    <div class="paper-count">
                        <span>📄</span> <?php echo $subject_counts[$subject['id']] ?? 0; ?> papers
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Papers Display -->
    <?php if(isset($_GET['subject_id']) && !empty($_GET['subject_id'])): ?>
        
        <?php if($papers): ?>
            <!-- Results Header -->
            <div class="results-header">
                <h2>
                    <span>📄</span> 
                    <?php echo htmlspecialchars($selected_subject_name); ?>
                </h2>
                <span class="paper-count-badge">
                    <?php echo count($papers); ?> paper<?php echo count($papers) > 1 ? 's' : ''; ?> found
                </span>
            </div>

            <!-- Papers Grid -->
            <div class="papers-grid">
                <?php foreach($papers as $index => $paper): ?>
     <?php
    // The file_path already contains the timestamp_originalname format
    // Example: 1772723861_Lecture-Notes-Chapter1.pdf
    $file = "../uploads/past_papers/" . $paper['file_path'];
    
    // Get just the filename without path for display
    $display_filename = basename($paper['file_path']);
    
    // Check if file exists
    $file_exists = file_exists($file);
    
    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $is_pdf = $extension == "pdf";
    ?>
                    
                    <div class="paper-card">
                        <div class="paper-preview" onclick="handlePaperClick('<?php echo $file; ?>', <?php echo $is_pdf ? 'true' : 'false'; ?>)">
                            <?php if($is_pdf): ?>
                                <div class="pdf-preview">
                                    📄 PDF Document
                                    <div class="preview-overlay">
                                        <span>👁️</span> Click to View
                                    </div>
                                </div>
                            <?php else: ?>
                                <img src="<?php echo $file; ?>" alt="Paper preview" onerror="this.src='../assets/placeholder.jpg'; this.onerror=null;">
                                <div class="preview-overlay">
                                    <span>👁️</span> Click to View
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="paper-details">
                            <h3><?php echo htmlspecialchars($paper['paper_title']); ?></h3>
                            
                            <div class="paper-subject">
                                <span>📚</span> <?php echo htmlspecialchars($paper['subject_name']); ?>
                            </div>
                            
                            <div class="paper-meta">
                                <span>📅 <?php echo isset($paper['uploaded_at']) ? date('M d, Y', strtotime($paper['uploaded_at'])) : 'Recently'; ?></span>
                                <span>📁 <?php echo strtoupper($extension); ?></span>
                            </div>
                            
                            <div class="paper-actions">
                                <?php if($is_pdf): ?>
                                    <button onclick="showPDFOptions('<?php echo $file; ?>')" class="btn btn-view">
                                        <span>👁️</span> View PDF
                                    </button>
                                <?php else: ?>
                                    <button onclick="openImageModal('<?php echo $file; ?>')" class="btn btn-view">
                                        <span>👁️</span> View Image
                                    </button>
                                <?php endif; ?>
                                
                                <a href="<?php echo $file; ?>" download class="btn btn-download">
                                    <span>⬇️</span> Download
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <!-- Empty State -->
            <div class="empty-state">
                <div class="icon">📭</div>
                <h3>No Papers Available</h3>
                <p>There are currently no past papers uploaded for <?php echo htmlspecialchars($selected_subject_name); ?>.</p>
                <a href="view_papers.php" class="browse-btn">Browse Other Subjects</a>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- No Subject Selected -->
        <div class="empty-state">
            <div class="icon">👆</div>
            <h3>Select a Subject</h3>
            <p>Choose a subject from the dropdown above to view available past papers.</p>
        </div>
    <?php endif; ?>

    <!-- Back to Dashboard -->
    <a href="dashboard.php" class="back-btn">
        <span>⬅</span> Back to Dashboard
    </a>
</div>

<!-- PDF Options Modal -->
<div id="pdfOptionsModal" class="pdf-options-modal">
    <div class="pdf-options-content">
        <span class="close-pdf-options" onclick="closePDFOptions()">&times;</span>
        <h3>📄 Open PDF Document</h3>
        
        <!-- URL Display for debugging -->
        <div id="pdfUrlDisplay" class="pdf-url-display"></div>
        
        <button class="pdf-option-btn" onclick="openPDFWithGoogle()">
            <span>📚</span> Open with Google Docs
        </button>
        <button class="pdf-option-btn" onclick="openPDFWithGoogleAlt()">
            <span>📚</span> Google Docs (Alternative)
        </button>
        <button class="pdf-option-btn" onclick="openPDFInBrowser()">
            <span>🌐</span> Open in Browser
        </button>
        <button class="pdf-option-btn secondary" onclick="downloadPDF()">
            <span>⬇️</span> Download
        </button>
        <button class="pdf-option-btn secondary" onclick="testPDFUrl()">
            <span>🔍</span> Test URL
        </button>
        <button class="pdf-option-btn danger" onclick="closePDFOptions()">
            <span>❌</span> Cancel
        </button>
    </div>
</div>

<!-- Image Modal -->
<div id="imageModal" class="image-modal">
    <span class="close-image-modal" onclick="closeImageModal()">&times;</span>
    <img class="image-modal-content" id="modalImage">
    <a href="#" id="modalDownloadLink" class="image-modal-download" download>
        <span>⬇️</span> Download
    </a>
</div>

<!-- Footer -->
<div class="footer">
    <p>© <?php echo date('Y'); ?> Exam Revision System. All rights reserved.</p>
</div>

<script>
let currentPDFFile = '';

// Handle paper click based on type
function handlePaperClick(file, isPDF) {
    if (isPDF) {
        showPDFOptions(file);
    } else {
        openImageModal(file);
    }
}

// Show PDF options modal
function showPDFOptions(file) {
    currentPDFFile = file;
    
    // Display the constructed URL for debugging
    const baseUrl = window.location.origin + window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/') + 1);
    let pdfPath = file.replace('../', '');
    const fullURL = baseUrl + pdfPath;
    document.getElementById('pdfUrlDisplay').innerHTML = '<strong>PDF URL:</strong> ' + fullURL;
    
    document.getElementById('pdfOptionsModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

// Close PDF options modal
function closePDFOptions() {
    document.getElementById('pdfOptionsModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Get the correct base URL for the application
function getBaseUrl() {
    // Get the current URL path
    const path = window.location.pathname;
    // Remove '/student/view_papers.php' to get the root
    const rootPath = path.substring(0, path.indexOf('/student/'));
    return window.location.origin + rootPath;
}

// Get the full public URL for a PDF file
function getPDFUrl() {
    if (!currentPDFFile) return '';
    
    // Remove '../' from the path
    let pdfPath = currentPDFFile.replace('../', '');
    
    // Construct the full URL
    const baseUrl = getBaseUrl();
    return baseUrl + '/' + pdfPath;
}

// Open PDF with Google Docs viewer
function openPDFWithGoogle() {
    if (!currentPDFFile) return;
    
    const fullURL = getPDFUrl();
    
    // Encode the URL for Google Docs Viewer
    const encodedUrl = encodeURIComponent(fullURL);
    
    // Open with Google Docs Viewer
    window.open(`https://docs.google.com/viewer?url=${encodedUrl}&embedded=true`, '_blank');
    
    closePDFOptions();
}

// Alternative method using direct Google Docs URL
function openPDFWithGoogleAlt() {
    if (!currentPDFFile) return;
    
    const fullURL = getPDFUrl();
    
    // Alternative Google Docs URL format
    window.open(`https://docs.google.com/viewerng/viewer?url=${encodeURIComponent(fullURL)}&embedded=true`, '_blank');
    
    closePDFOptions();
}

// Open PDF directly in browser
function openPDFInBrowser() {
    if (!currentPDFFile) return;
    window.open(currentPDFFile, '_blank');
    closePDFOptions();
}

// Download PDF
function downloadPDF() {
    if (!currentPDFFile) return;
    window.open(currentPDFFile, '_blank');
    closePDFOptions();
}

// Test the PDF URL
function testPDFUrl() {
    if (!currentPDFFile) return;
    
    const fullURL = getPDFUrl();
    
    // Open in new tab to test
    window.open(fullURL, '_blank');
}

// Open image modal
function openImageModal(file) {
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImage');
    const downloadLink = document.getElementById('modalDownloadLink');
    
    modalImg.src = file;
    downloadLink.href = file;
    
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
}

// Close image modal
function closeImageModal() {
    document.getElementById('imageModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Close image modal when clicking outside
window.onclick = function(event) {
    const imageModal = document.getElementById('imageModal');
    const pdfModal = document.getElementById('pdfOptionsModal');
    
    if (event.target == imageModal) {
        closeImageModal();
    }
    if (event.target == pdfModal) {
        closePDFOptions();
    }
}

// Close modals with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeImageModal();
        closePDFOptions();
    }
});

// Custom Dropdown Handler
const dropdownBtn = document.getElementById('dropdownBtn');
const dropdownMenu = document.getElementById('dropdownMenu');
const dropdownItems = document.querySelectorAll('.dropdown-item');
const dropdownLabel = document.getElementById('dropdownLabel');
const subjectIdInput = document.getElementById('subjectIdInput');

// Toggle dropdown menu
dropdownBtn.addEventListener('click', function(e) {
    e.stopPropagation();
    dropdownBtn.classList.toggle('open');
    dropdownMenu.classList.toggle('open');
});

// Handle dropdown item selection
dropdownItems.forEach(item => {
    // Highlight currently selected item on page load
    if (new URLSearchParams(window.location.search).get('subject_id') === item.dataset.value && item.dataset.value !== '') {
        item.classList.add('selected');
        dropdownLabel.textContent = item.textContent;
    }

    // Handle click on dropdown items
    item.addEventListener('click', function(e) {
        e.stopPropagation();
        
        // Remove selected class from all items
        dropdownItems.forEach(i => i.classList.remove('selected'));
        
        // Add selected class to clicked item
        this.classList.add('selected');
        dropdownLabel.textContent = this.textContent;
        
        // Update input and submit form
        subjectIdInput.value = this.dataset.value;
        
        // Close dropdown and submit
        dropdownBtn.classList.remove('open');
        dropdownMenu.classList.remove('open');
        
        // Submit the form
        document.getElementById('subjectForm').submit();
    });
});

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.custom-dropdown')) {
        dropdownBtn.classList.remove('open');
        dropdownMenu.classList.remove('open');
    }
});

// Auto-submit form when dropdown changes (already have onchange)
// Add smooth scroll to papers when loaded
if (window.location.href.includes('subject_id')) {
    setTimeout(() => {
        document.querySelector('.results-header')?.scrollIntoView({ 
            behavior: 'smooth', 
            block: 'start' 
        });
    }, 300);
}
</script>

</body>
</html>