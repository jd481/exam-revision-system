<?php
session_start();
require_once '../config/database.php';

/* Ensure only students access */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$student_name = $_SESSION['full_name'];

// Pagination settings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 12;
$offset = ($page - 1) * $records_per_page;

// Get total count for pagination
$stmt = $pdo->query("SELECT COUNT(*) as total FROM past_papers");
$total_records = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get recent papers with pagination - FIXED SYNTAX
$stmt = $pdo->prepare("
    SELECT past_papers.*, subjects.subject_name
    FROM past_papers 
    JOIN subjects ON past_papers.subject_id = subjects.id
    ORDER BY past_papers.id DESC
    LIMIT :limit OFFSET :offset
");

// Bind parameters as integers
$stmt->bindParam(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$recent_papers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get stats for the header
$stmt = $pdo->query("SELECT COUNT(DISTINCT subject_id) as subject_count FROM past_papers");
$subject_count = $stmt->fetch(PDO::FETCH_ASSOC)['subject_count'];

$stmt = $pdo->query("SELECT COUNT(*) as total_papers FROM past_papers");
$total_papers = $stmt->fetch(PDO::FETCH_ASSOC)['total_papers'];

// Get this week's uploads
$stmt = $pdo->query("
    SELECT COUNT(*) as weekly_count 
    FROM past_papers 
    WHERE uploaded_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
");
$weekly_count = $stmt->fetch(PDO::FETCH_ASSOC)['weekly_count'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Recent Uploads - Student</title>
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
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .page-header p {
            color: #666;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            background: #e8f5e9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #008751;
        }

        .stat-content h3 {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .stat-number {
            font-size: 1.8rem;
            font-weight: bold;
            color: #008751;
        }

        .stat-label {
            color: #888;
            font-size: 0.8rem;
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
            transition: all 0.3s;
            border: 1px solid #eee;
            position: relative;
        }

        .paper-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,135,81,0.15);
            border-color: #008751;
        }

        .new-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #ffc107;
            color: #333;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            z-index: 1;
        }

        .paper-preview {
            height: 150px;
            background: #e8f5e9;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #008751;
            font-size: 48px;
            border-bottom: 1px solid #eee;
            position: relative;
        }

        .upload-date {
            position: absolute;
            bottom: 10px;
            left: 10px;
            background: rgba(0,0,0,0.5);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
        }

        .paper-details {
            padding: 20px;
        }

        .paper-details h3 {
            color: #333;
            margin-bottom: 8px;
            font-size: 1.2rem;
            padding-right: 60px;
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

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .page-link {
            padding: 8px 15px;
            background: white;
            border: 1px solid #008751;
            color: #008751;
            text-decoration: none;
            border-radius: 4px;
            transition: all 0.3s;
        }

        .page-link:hover {
            background: #008751;
            color: white;
        }

        .page-link.active {
            background: #008751;
            color: white;
        }

        .page-link.disabled {
            opacity: 0.5;
            pointer-events: none;
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
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .papers-grid {
                grid-template-columns: 1fr;
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
            <h1>
                <span>⏱️</span> Recent Uploads
            </h1>
            <p>Browse the latest past papers added to the system</p>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📚</div>
                <div class="stat-content">
                    <h3>Active Subjects</h3>
                    <div class="stat-number"><?php echo $subject_count ?? 0; ?></div>
                    <div class="stat-label">With papers</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📄</div>
                <div class="stat-content">
                    <h3>Total Papers</h3>
                    <div class="stat-number"><?php echo $total_papers ?? 0; ?></div>
                    <div class="stat-label">In database</div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">✨</div>
                <div class="stat-content">
                    <h3>This Week</h3>
                    <div class="stat-number"><?php echo $weekly_count ?? 0; ?></div>
                    <div class="stat-label">New uploads</div>
                </div>
            </div>
        </div>

        <?php if (!empty($recent_papers)): ?>
            <!-- Papers Grid -->
            <div class="papers-grid">
                <?php foreach ($recent_papers as $paper): ?>
                    <?php 
                    $file = "../uploads/past_papers/" . $paper['file_path'];
                    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    $is_pdf = $extension == 'pdf';
                    
                    // Check if uploaded within last 7 days
                    $upload_date = strtotime($paper['uploaded_at']);
                    $is_new = (time() - $upload_date) < 7 * 24 * 60 * 60;
                    ?>
                    
                    <div class="paper-card">
                        <?php if ($is_new): ?>
                            <div class="new-badge">✨ NEW</div>
                        <?php endif; ?>
                        
                        <div class="paper-preview">
                            <?php echo $is_pdf ? '📄' : '🖼️'; ?>
                            <div class="upload-date">
                                📅 <?php echo isset($paper['uploaded_at']) ? date('M d, Y', strtotime($paper['uploaded_at'])) : 'Recently'; ?>
                            </div>
                        </div>
                        
                        <div class="paper-details">
                            <h3><?php echo htmlspecialchars($paper['paper_title']); ?></h3>
                            
                            <div class="paper-subject">
                                <span>📚</span> 
                                <a href="view_papers.php?subject_id=<?php echo $paper['subject_id']; ?>" style="color: #008751; text-decoration: none;">
                                    <?php echo htmlspecialchars($paper['subject_name']); ?>
                                </a>
                            </div>
                            
                            <div class="paper-meta">
                                <span>📁 <?php echo strtoupper($extension); ?></span>
                            </div>
                            
                            <div class="paper-actions">
                                <a href="<?php echo $file; ?>" target="_blank" class="btn btn-view">
                                    <span>👁️</span> View
                                </a>
                                <a href="<?php echo $file; ?>" download class="btn btn-download">
                                    <span>⬇️</span> Download
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page-1; ?>" class="page-link">← Previous</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" 
                           class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page+1; ?>" class="page-link">Next →</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- Empty State -->
            <div class="empty-state">
                <div class="icon">📭</div>
                <h3>No Recent Uploads</h3>
                <p>There are no papers uploaded yet. Check back later for new materials.</p>
                <a href="dashboard.php" class="btn-view" style="display: inline-block; padding: 12px 30px; text-decoration: none; border-radius: 25px;">Go to Dashboard</a>
            </div>
        <?php endif; ?>

        <!-- Back to Dashboard -->
        <a href="dashboard.php" class="back-btn">
            <span>⬅</span> Back to Dashboard
        </a>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>© <?php echo date('Y'); ?> Exam Revision System. All rights reserved.</p>
    </div>
</body>
</html>