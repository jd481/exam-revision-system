<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "student") {
    header("Location: ../auth/login.php");
    exit();
}

$student_id = $_SESSION["user_id"];
$student_name = $_SESSION["full_name"];

// Get available subjects count
$stmt = $pdo->query("SELECT COUNT(*) as total FROM subjects");
$total_subjects = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get total papers available
$stmt = $pdo->query("SELECT COUNT(*) as total FROM past_papers");
$total_papers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get user's download count for the badge
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM downloads WHERE user_id = ?");
$stmt->execute([$student_id]);
$download_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get recent papers (last 5 uploaded)
$stmt = $pdo->prepare("
    SELECT past_papers.*, subjects.subject_name 
    FROM past_papers 
    JOIN subjects ON past_papers.subject_id = subjects.id 
    ORDER BY past_papers.id DESC 
    LIMIT 5
");
$stmt->execute();
$recent_papers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Student Dashboard</title>
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

        /* Welcome Section */
        .welcome-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,135,81,0.1);
            border-left: 5px solid #008751;
        }

        .welcome-section h1 {
            color: #333;
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .welcome-section h1 span {
            color: #008751;
        }

        .welcome-section p {
            color: #666;
            font-size: 1.1rem;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,135,81,0.15);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            background: #e8f5e9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: #008751;
        }

        .stat-content {
            flex: 1;
        }

        .stat-content h3 {
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #008751;
            line-height: 1.2;
        }

        /* Action Cards */
        .section-title {
            color: #008751;
            margin: 30px 0 20px;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title:after {
            content: '';
            flex: 1;
            height: 2px;
            background: linear-gradient(to right, #008751, transparent);
        }

        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .action-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            text-decoration: none;
            color: #333;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: all 0.3s;
            border: 1px solid #eee;
            text-align: center;
            display: block;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,135,81,0.15);
            border-color: #008751;
        }

        .action-icon {
            width: 80px;
            height: 80px;
            background: #e8f5e9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: #008751;
            margin: 0 auto 20px;
        }

        .action-card h3 {
            color: #008751;
            margin-bottom: 10px;
            font-size: 1.4rem;
        }

        .action-card p {
            color: #666;
            font-size: 1rem;
            line-height: 1.5;
            margin-bottom: 20px;
        }

        .action-badge {
            display: inline-block;
            padding: 6px 20px;
            background: #e8f5e9;
            color: #008751;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: bold;
        }

        /* Quick Links Grid */
        .quick-links-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .quick-link {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-decoration: none;
            color: #333;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s;
            border: 1px solid #eee;
            position: relative;
        }

        .quick-link:hover {
            transform: translateX(5px);
            border-color: #008751;
            box-shadow: 0 4px 15px rgba(0,135,81,0.1);
        }

        .quick-link-icon {
            width: 45px;
            height: 45px;
            background: #e8f5e9;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #008751;
        }

        .quick-link-content h4 {
            color: #008751;
            margin-bottom: 3px;
        }

        .quick-link-content small {
            color: #888;
            font-size: 0.8rem;
        }

        .download-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ffc107;
            color: #333;
            font-size: 0.7rem;
            font-weight: bold;
            padding: 3px 8px;
            border-radius: 20px;
            border: 2px solid white;
        }

        /* Recent Papers Table */
        .recent-papers {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .recent-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .recent-header h3 {
            color: #008751;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .view-all {
            color: #008751;
            text-decoration: none;
            padding: 5px 15px;
            border: 1px solid #008751;
            border-radius: 20px;
            font-size: 0.9rem;
            transition: all 0.3s;
        }

        .view-all:hover {
            background: #008751;
            color: white;
        }

        .papers-table {
            width: 100%;
            border-collapse: collapse;
        }

        .papers-table th {
            text-align: left;
            padding: 12px;
            background: #f5f9f5;
            color: #008751;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .papers-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            color: #555;
        }

        .papers-table tr:hover td {
            background: #f9fff9;
        }

        .subject-badge {
            background: #e8f5e9;
            color: #008751;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
            display: inline-block;
        }

        .view-btn {
            background: #008751;
            color: white;
            text-decoration: none;
            padding: 6px 15px;
            border-radius: 4px;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: background 0.3s;
        }

        .view-btn:hover {
            background: #00663d;
        }

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
            
            .welcome-section h1 {
                font-size: 1.5rem;
            }
            
            .papers-table {
                font-size: 0.85rem;
            }
            
            .papers-table th, 
            .papers-table td {
                padding: 8px;
            }
            
            .quick-links-grid {
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
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1>Welcome, <span><?php echo htmlspecialchars(explode(' ', $student_name)[0]); ?></span>!</h1>
            <p>Access past exam papers and revision materials for your courses.</p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📚</div>
                <div class="stat-content">
                    <h3>Available Subjects</h3>
                    <div class="stat-number"><?php echo $total_subjects; ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">📄</div>
                <div class="stat-content">
                    <h3>Past Papers</h3>
                    <div class="stat-number"><?php echo $total_papers; ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">📥</div>
                <div class="stat-content">
                    <h3>Your Downloads</h3>
                    <div class="stat-number"><?php echo $download_count; ?></div>
                </div>
            </div>
        </div>

        <!-- Main Action Cards -->
        <div class="section-title">
            📋 Quick Actions
        </div>

        <div class="action-grid">
            <a href="view_papers.php" class="action-card">
                <div class="action-icon">📑</div>
                <h3>View Past Papers</h3>
                <p>Browse and download past exam papers organized by subject.</p>
                <div class="action-badge"><?php echo $total_papers; ?> Available</div>
            </a>

            <a href="browse_by_subject.php" class="action-card">
                <div class="action-icon">🔍</div>
                <h3>Browse by Subject</h3>
                <p>Find past papers specific to your courses and departments.</p>
                <div class="action-badge"><?php echo $total_subjects; ?> Subjects</div>
            </a>

            <a href="search_papers.php" class="action-card">
                <div class="action-icon">🔎</div>
                <h3>Search Papers</h3>
                <p>Search for specific papers by title, subject, or year.</p>
                <div class="action-badge">Advanced Search</div>
            </a>
        </div>

        <!-- Quick Links -->
        <div class="quick-links-grid">
            <a href="recent_uploads.php" class="quick-link">
                <div class="quick-link-icon">🆕</div>
                <div class="quick-link-content">
                    <h4>Recent Uploads</h4>
                    <small>Latest papers added</small>
                </div>
            </a>

            <a href="my_downloads.php" class="quick-link">
                <div class="quick-link-icon">📥</div>
                <div class="quick-link-content">
                    <h4>My Downloads</h4>
                    <small>Papers you've accessed</small>
                </div>
                <?php if ($download_count > 0): ?>
                    <span class="download-badge"><?php echo $download_count; ?></span>
                <?php endif; ?>
            </a>

            <a href="favorites.php" class="quick-link">
                <div class="quick-link-icon">⭐</div>
                <div class="quick-link-content">
                    <h4>Favorites</h4>
                    <small>Saved for later</small>
                </div>
            </a>

            <a href="help.php" class="quick-link">
                <div class="quick-link-icon">❓</div>
                <div class="quick-link-content">
                    <h4>Help & Support</h4>
                    <small>Get assistance</small>
                </div>
            </a>
        </div>

        <!-- Recent Papers -->
        <div class="recent-papers">
            <div class="recent-header">
                <h3>
                    <span>⏱️</span> Recently Added Papers
                </h3>
                <a href="view_papers.php" class="view-all">View All →</a>
            </div>

            <?php if (count($recent_papers) > 0): ?>
                <table class="papers-table">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Paper Title</th>
                            <th>Uploaded</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_papers as $paper): ?>
                            <tr>
                                <td>
                                    <span class="subject-badge">
                                        <?php echo htmlspecialchars($paper['subject_name']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($paper['paper_title']); ?></td>
                                <td><?php echo isset($paper['uploaded_at']) ? date('M d, Y', strtotime($paper['uploaded_at'])) : 'Recently'; ?></td>
                                <td>
                                    <a href="../uploads/past_papers/<?php echo htmlspecialchars($paper['file_path']); ?>" 
                                       target="_blank" 
                                       class="view-btn">
                                        <span>👁️</span> View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    <div style="font-size: 48px; margin-bottom: 15px;">📭</div>
                    <h3 style="margin-bottom: 10px;">No Papers Available</h3>
                    <p>Check back later for uploaded past papers.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Study Tips -->
        <div style="margin-top: 30px; background: #fff3cd; border-left: 4px solid #008751; padding: 15px; border-radius: 4px;">
            <strong style="color: #008751;">💡 Study Tip:</strong> 
            <span style="color: #666;">Regular practice with past papers helps you understand exam patterns and improve your performance.</span>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>© <?php echo date('Y'); ?> Exam Revision System. All rights reserved.</p>
    </div>
</body>
</html>