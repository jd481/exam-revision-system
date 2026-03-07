<?php
session_start();
require_once '../config/database.php';

// Ensure only lecturers access
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "lecturer") {
    header("Location: ../auth/login.php");
    exit();
}

$lecturer_id = $_SESSION["user_id"];
$lecturer_name = $_SESSION["full_name"];

// Get total subjects count for this lecturer
$stmt = $pdo->prepare("SELECT COUNT(*) as total_subjects FROM subjects WHERE lecturer_id = ?");
$stmt->execute([$lecturer_id]);
$total_subjects = $stmt->fetch(PDO::FETCH_ASSOC)['total_subjects'] ?? 0;

// Get total papers uploaded
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total_papers 
    FROM past_papers 
    JOIN subjects ON past_papers.subject_id = subjects.id
    WHERE subjects.lecturer_id = ?
");
$stmt->execute([$lecturer_id]);
$total_papers = $stmt->fetch(PDO::FETCH_ASSOC)['total_papers'] ?? 0;

// Get recent activity (last 5 papers)
$stmt = $pdo->prepare("
    SELECT past_papers.*, subjects.subject_name 
    FROM past_papers 
    JOIN subjects ON past_papers.subject_id = subjects.id
    WHERE subjects.lecturer_id = ?
    ORDER BY past_papers.id DESC 
    LIMIT 5
");
$stmt->execute([$lecturer_id]);
$recent_papers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Lecturer Dashboard - KAFU Exam Revision</title>
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

        /* Navbar */
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
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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

        .stat-label {
            color: #888;
            font-size: 0.9rem;
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
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
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
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,135,81,0.15);
            border-color: #008751;
        }

        .action-icon {
            width: 70px;
            height: 70px;
            background: #e8f5e9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: #008751;
            margin: 0 auto 20px;
        }

        .action-card h3 {
            color: #008751;
            margin-bottom: 10px;
            font-size: 1.3rem;
        }

        .action-card p {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .action-badge {
            display: inline-block;
            margin-top: 15px;
            padding: 5px 15px;
            background: #e8f5e9;
            color: #008751;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
        }

        /* Recent Activity */
        .recent-activity {
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

        .activity-table {
            width: 100%;
            border-collapse: collapse;
        }

        .activity-table th {
            text-align: left;
            padding: 12px;
            background: #f5f9f5;
            color: #008751;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .activity-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            color: #555;
        }

        .activity-table tr:hover td {
            background: #f9fff9;
        }

        .paper-link {
            color: #008751;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .paper-link:hover {
            text-decoration: underline;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #888;
        }

        .empty-state p {
            margin-top: 10px;
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
            
            .action-grid {
                grid-template-columns: 1fr;
            }
            
            .activity-table {
                font-size: 0.9rem;
            }
            
            .activity-table th, 
            .activity-table td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <div class="navbar">
        <div class="logo">
            📚 Exam Revision System
            <span>Lecturer Portal</span>
        </div>
        <div class="user-menu">
            <span class="user-name">👤 <?php echo htmlspecialchars($lecturer_name); ?></span>
            <a href="../auth/logout.php" class="logout-btn">🚪 Logout</a>
        </div>
    </div>

    <div class="container">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h1>Welcome back, <span><?php echo htmlspecialchars(explode(' ', $lecturer_name)[0]); ?></span>!</h1>
            <p>Manage your subjects and past papers from your dashboard.</p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📚</div>
                <div class="stat-content">
                    <h3>Total Subjects</h3>
                    <div class="stat-number"><?php echo $total_subjects; ?></div>
                    <div class="stat-label">Active subjects</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">📄</div>
                <div class="stat-content">
                    <h3>Past Papers</h3>
                    <div class="stat-number"><?php echo $total_papers; ?></div>
                    <div class="stat-label">Uploaded documents</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">⏱️</div>
                <div class="stat-content">
                    <h3>Quick Actions</h3>
                    <div class="stat-number">⚡</div>
                    <div class="stat-label">Ready to go</div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="section-title">
            📋 Quick Actions
        </div>

        <div class="action-grid">
            <a href="add_subject.php" class="action-card">
                <div class="action-icon">➕</div>
                <h3>Add Subject</h3>
                <p>Create a new subject for your students to access past papers.</p>
                <div class="action-badge">Create New</div>
            </a>

            <a href="manage_subjects.php" class="action-card">
                <div class="action-icon">📋</div>
                <h3>Manage Subjects</h3>
                <p>View, edit, or remove your existing subjects.</p>
                <div class="action-badge"><?php echo $total_subjects; ?> Total</div>
            </a>

            <a href="upload_paper.php" class="action-card">
                <div class="action-icon">📤</div>
                <h3>Upload Paper</h3>
                <p>Upload past exam papers for your subjects.</p>
                <div class="action-badge">New Upload</div>
            </a>

            <a href="past_papers.php" class="action-card">
                <div class="action-icon">📑</div>
                <h3>View Papers</h3>
                <p>See all your uploaded past papers in one place.</p>
                <div class="action-badge"><?php echo $total_papers; ?> Papers</div>
            </a>
        </div>

        <!-- Recent Activity -->
        <div class="section-title">
            ⏳ Recent Uploads
        </div>

        <div class="recent-activity">
            <?php if (count($recent_papers) > 0): ?>
                <div class="recent-header">
                    <h3>Recently Added Past Papers</h3>
                    <a href="past_papers.php" class="view-all">View All →</a>
                </div>
                <table class="activity-table">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>File Name</th>
                            <th>Upload Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_papers as $paper): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($paper['subject_name']); ?></td>
                                <td><?php echo htmlspecialchars($paper['file_name'] ?? $paper['file_path']); ?></td>
                                <td><?php echo isset($paper['uploaded_at']) ? date('M d, Y', strtotime($paper['uploaded_at'])) : 'Recently'; ?></td>
                                <td>
                                    <a href="../uploads/past_papers/<?php echo htmlspecialchars($paper['file_path']); ?>" 
                                       target="_blank" 
                                       class="paper-link">
                                        👁️ View
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <div style="font-size: 48px; margin-bottom: 15px;">📭</div>
                    <h3 style="color: #666; margin-bottom: 10px;">No Papers Yet</h3>
                    <p style="color: #888;">Start by adding subjects and uploading past papers.</p>
                    <a href="upload_paper.php" style="display: inline-block; margin-top: 15px; padding: 10px 25px; background: #008751; color: white; text-decoration: none; border-radius: 25px;">Upload Your First Paper</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Tips (Optional) -->
    <div style="max-width: 1200px; margin: 20px auto; padding: 0 20px;">
        <div style="background: #fff3cd; border-left: 4px solid #008751; padding: 15px; border-radius: 4px;">
            <strong style="color: #008751;">💡 Pro Tip:</strong> 
            <span style="color: #666;">Regularly upload past papers to help students prepare better. Organize them by subject for easy access.</span>
        </div>
    </div>
</body>
</html>