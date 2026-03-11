<?php
session_start();
require_once '../config/database.php';

/* Ensure only students access */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$student_name = $_SESSION['full_name'];

// Pagination settings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Get total downloads count
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM downloads WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_records = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get user's downloaded papers - FIXED: Using bindParam for integers
$stmt = $pdo->prepare("
    SELECT d.*, 
           p.paper_title, 
           p.file_path,
           p.uploaded_at,
           s.subject_name,
           s.id as subject_id,
           DATE_FORMAT(d.downloaded_at, '%M %d, %Y') as download_date,
           DATE_FORMAT(d.downloaded_at, '%h:%i %p') as download_time
    FROM downloads d
    JOIN past_papers p ON d.paper_id = p.id
    JOIN subjects s ON p.subject_id = s.id
    WHERE d.user_id = ?
    ORDER BY d.downloaded_at DESC
    LIMIT ? OFFSET ?
");

// Bind parameters explicitly as integers
$stmt->bindParam(1, $user_id, PDO::PARAM_INT);
$stmt->bindParam(2, $records_per_page, PDO::PARAM_INT);
$stmt->bindParam(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$downloads = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get download statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT subject_id) as unique_subjects,
        COUNT(*) as total_downloads,
        MAX(downloaded_at) as last_download
    FROM downloads d
    JOIN past_papers p ON d.paper_id = p.id
    WHERE d.user_id = ?
");
$stmt->execute([$user_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get most downloaded subjects
$stmt = $pdo->prepare("
    SELECT s.subject_name, COUNT(*) as download_count
    FROM downloads d
    JOIN past_papers p ON d.paper_id = p.id
    JOIN subjects s ON p.subject_id = s.id
    WHERE d.user_id = ?
    GROUP BY s.id, s.subject_name
    ORDER BY download_count DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$top_subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Downloads - Student</title>
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

        /* Top Subjects */
        .top-subjects {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .top-subjects h3 {
            color: #008751;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .subject-tags {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .subject-tag {
            background: #e8f5e9;
            color: #008751;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .subject-tag span {
            background: #008751;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
        }

        /* Downloads Table */
        .downloads-table-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            margin-bottom: 30px;
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
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        tr:hover td {
            background: #f9fff9;
        }

        .paper-info h4 {
            color: #333;
            margin-bottom: 5px;
        }

        .paper-info a {
            color: #008751;
            text-decoration: none;
            font-size: 0.85rem;
        }

        .paper-info a:hover {
            text-decoration: underline;
        }

        .subject-badge {
            background: #e8f5e9;
            color: #008751;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            display: inline-block;
        }

        .download-time {
            color: #666;
            font-size: 0.9rem;
        }

        .download-time small {
            color: #999;
            font-size: 0.8rem;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
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
            margin-top: 20px;
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
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            table {
                font-size: 0.85rem;
            }
            
            td, th {
                padding: 10px;
            }
            
            .action-buttons {
                flex-direction: column;
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
                <span>📥</span> My Downloads
            </h1>
            <p>Track and access all the papers you've downloaded</p>
        </div>

        <?php if (!empty($downloads)): ?>
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📄</div>
                    <div class="stat-content">
                        <h3>Total Downloads</h3>
                        <div class="stat-number"><?php echo $stats['total_downloads']; ?></div>
                        <div class="stat-label">Papers downloaded</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">📚</div>
                    <div class="stat-content">
                        <h3>Unique Subjects</h3>
                        <div class="stat-number"><?php echo $stats['unique_subjects']; ?></div>
                        <div class="stat-label">Subjects explored</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">⏱️</div>
                    <div class="stat-content">
                        <h3>Last Download</h3>
                        <div class="stat-number">
                            <?php 
                            if ($stats['last_download']) {
                                echo date('M d', strtotime($stats['last_download']));
                            } else {
                                echo 'Never';
                            }
                            ?>
                        </div>
                        <div class="stat-label">Recent activity</div>
                    </div>
                </div>
            </div>

            <!-- Top Subjects -->
            <?php if (!empty($top_subjects)): ?>
            <div class="top-subjects">
                <h3>
                    <span>🏆</span> Your Most Downloaded Subjects
                </h3>
                <div class="subject-tags">
                    <?php foreach ($top_subjects as $subject): ?>
                        <div class="subject-tag">
                            <?php echo htmlspecialchars($subject['subject_name']); ?>
                            <span><?php echo $subject['download_count']; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Downloads Table -->
            <div class="downloads-table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Paper</th>
                            <th>Subject</th>
                            <th>Downloaded</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($downloads as $download): ?>
                            <?php 
                            $file = "../uploads/past_papers/" . $download['file_path'];
                            ?>
                            <tr>
                                <td>
                                    <div class="paper-info">
                                        <h4><?php echo htmlspecialchars($download['paper_title']); ?></h4>
                                        <a href="view_papers.php?subject_id=<?php echo $download['subject_id']; ?>">
                                            View all papers in this subject →
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    <span class="subject-badge">
                                        <?php echo htmlspecialchars($download['subject_name']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="download-time">
                                        <div><?php echo $download['download_date']; ?></div>
                                        <small><?php echo $download['download_time']; ?></small>
                                    </div>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="<?php echo $file; ?>" target="_blank" class="btn btn-view">
                                            <span>👁️</span> View
                                        </a>
                                        <a href="<?php echo $file; ?>" download class="btn btn-download">
                                            <span>⬇️</span> Again
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
                <div class="icon">📥</div>
                <h3>No Downloads Yet</h3>
                <p>You haven't downloaded any past papers yet. Start exploring and downloading papers!</p>
                <a href="view_papers.php" class="browse-btn">Browse Papers</a>
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