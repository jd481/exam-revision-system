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
$records_per_page = 12;
$offset = ($page - 1) * $records_per_page;

// Get total favorites count
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM favorites WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_records = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get user's favorite papers
$stmt = $pdo->prepare("
    SELECT f.*, 
           p.paper_title, 
           p.file_path,
           p.uploaded_at,
           s.subject_name,
           s.id as subject_id,
           DATE_FORMAT(f.created_at, '%M %d, %Y') as favorited_date
    FROM favorites f
    JOIN past_papers p ON f.paper_id = p.id
    JOIN subjects s ON p.subject_id = s.id
    WHERE f.user_id = ?
    ORDER BY f.created_at DESC
    LIMIT ? OFFSET ?
");

$stmt->bindParam(1, $user_id, PDO::PARAM_INT);
$stmt->bindParam(2, $records_per_page, PDO::PARAM_INT);
$stmt->bindParam(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get favorite statistics
$stmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT subject_id) as unique_subjects,
        COUNT(*) as total_favorites,
        MAX(f.created_at) as last_favorite
    FROM favorites f
    JOIN past_papers p ON f.paper_id = p.id
    WHERE f.user_id = ?
");
$stmt->execute([$user_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get most favorited subjects
$stmt = $pdo->prepare("
    SELECT s.subject_name, COUNT(*) as favorite_count
    FROM favorites f
    JOIN past_papers p ON f.paper_id = p.id
    JOIN subjects s ON p.subject_id = s.id
    WHERE f.user_id = ?
    GROUP BY s.id, s.subject_name
    ORDER BY favorite_count DESC
    LIMIT 5
");
$stmt->execute([$user_id]);
$top_subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Favorites - Student</title>
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

        .favorite-badge {
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
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .favorite-badge span {
            color: #333;
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

        .favorite-date {
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

        .btn-remove {
            background: #dc3545;
            color: white;
            border: none;
        }

        .btn-remove:hover {
            background: #a71d2a;
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

        /* Notification */
        .notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #008751;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            display: none;
            align-items: center;
            gap: 10px;
            z-index: 3000;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
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
            
            .notification {
                bottom: 10px;
                right: 10px;
                left: 10px;
                width: auto;
            }
        }
    </style>
</head>
<body>
    <!-- Notification -->
    <div id="notification" class="notification">
        <span>⭐</span>
        <span id="notificationMessage"></span>
    </div>

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
                <span>⭐</span> My Favorites
            </h1>
            <p>Quick access to your saved papers</p>
        </div>

        <?php if (!empty($favorites)): ?>
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">⭐</div>
                    <div class="stat-content">
                        <h3>Total Favorites</h3>
                        <div class="stat-number"><?php echo $stats['total_favorites']; ?></div>
                        <div class="stat-label">Saved papers</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">📚</div>
                    <div class="stat-content">
                        <h3>Unique Subjects</h3>
                        <div class="stat-number"><?php echo $stats['unique_subjects']; ?></div>
                        <div class="stat-label">Subjects saved</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">⏱️</div>
                    <div class="stat-content">
                        <h3>Last Saved</h3>
                        <div class="stat-number">
                            <?php 
                            if ($stats['last_favorite']) {
                                echo date('M d', strtotime($stats['last_favorite']));
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
                    <span>🏆</span> Your Most Saved Subjects
                </h3>
                <div class="subject-tags">
                    <?php foreach ($top_subjects as $subject): ?>
                        <div class="subject-tag">
                            <?php echo htmlspecialchars($subject['subject_name']); ?>
                            <span><?php echo $subject['favorite_count']; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Favorites Grid -->
            <div class="papers-grid">
                <?php foreach ($favorites as $favorite): ?>
                    <?php 
                    $file = "../uploads/past_papers/" . $favorite['file_path'];
                    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    $is_pdf = $extension == 'pdf';
                    ?>
                    
                    <div class="paper-card" id="paper-<?php echo $favorite['paper_id']; ?>">
                        <div class="favorite-badge">
                            <span>⭐</span> Saved
                        </div>
                        
                        <div class="paper-preview">
                            <?php echo $is_pdf ? '📄' : '🖼️'; ?>
                            <div class="favorite-date">
                                📅 <?php echo $favorite['favorited_date']; ?>
                            </div>
                        </div>
                        
                        <div class="paper-details">
                            <h3><?php echo htmlspecialchars($favorite['paper_title']); ?></h3>
                            
                            <div class="paper-subject">
                                <span>📚</span> 
                                <a href="view_papers.php?subject_id=<?php echo $favorite['subject_id']; ?>" style="color: #008751; text-decoration: none;">
                                    <?php echo htmlspecialchars($favorite['subject_name']); ?>
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
                                <button onclick="removeFromFavorites(<?php echo $favorite['paper_id']; ?>)" class="btn btn-remove">
                                    <span>🗑️</span> Remove
                                </button>
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
                <div class="icon">⭐</div>
                <h3>No Favorites Yet</h3>
                <p>You haven't saved any papers to your favorites yet. Star papers while browsing to add them here!</p>
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

    <script>
    // Show notification
    function showNotification(message, isSuccess = true) {
        const notification = document.getElementById('notification');
        const messageSpan = document.getElementById('notificationMessage');
        
        messageSpan.textContent = message;
        notification.style.backgroundColor = isSuccess ? '#008751' : '#dc3545';
        notification.style.display = 'flex';
        
        setTimeout(() => {
            notification.style.display = 'none';
        }, 3000);
    }

    // Remove from favorites
    function removeFromFavorites(paperId) {
        fetch('../toggle_favorite.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'paper_id=' + paperId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove the card from the page
                const paperCard = document.getElementById('paper-' + paperId);
                paperCard.style.transition = 'opacity 0.3s';
                paperCard.style.opacity = '0';
                
                setTimeout(() => {
                    paperCard.remove();
                    
                    // Check if there are any papers left
                    const remainingCards = document.querySelectorAll('.paper-card');
                    if (remainingCards.length === 0) {
                        location.reload(); // Reload to show empty state
                    }
                }, 300);
                
                showNotification('Removed from favorites');
            } else {
                showNotification('Error removing from favorites', false);
            }
        })
        .catch(error => {
            showNotification('Error removing from favorites', false);
        });
    }
    </script>
</body>
</html>