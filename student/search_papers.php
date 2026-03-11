<?php
session_start();
require_once '../config/database.php';

/* Ensure only students access */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$student_name = $_SESSION['full_name'];
$search_results = [];
$search_term = '';
$search_type = 'all';
$total_results = 0;

// Handle search
if (isset($_GET['q']) && !empty(trim($_GET['q']))) {
    $search_term = trim($_GET['q']);
    $search_type = $_GET['type'] ?? 'all';
    
    $query = "
        SELECT past_papers.*, subjects.subject_name 
        FROM past_papers 
        JOIN subjects ON past_papers.subject_id = subjects.id
        WHERE 1=1
    ";
    
    $params = [];
    
    // Build search query based on type
    switch ($search_type) {
        case 'title':
            $query .= " AND past_papers.paper_title LIKE ?";
            $params[] = "%$search_term%";
            break;
        case 'subject':
            $query .= " AND subjects.subject_name LIKE ?";
            $params[] = "%$search_term%";
            break;
        case 'filename':
            $query .= " AND past_papers.file_path LIKE ?";
            $params[] = "%$search_term%";
            break;
        default: // 'all'
            $query .= " AND (past_papers.paper_title LIKE ? OR subjects.subject_name LIKE ? OR past_papers.file_path LIKE ?)";
            $params[] = "%$search_term%";
            $params[] = "%$search_term%";
            $params[] = "%$search_term%";
            break;
    }
    
    $query .= " ORDER BY past_papers.id DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_results = count($search_results);
}

// Get popular subjects for quick filters
$stmt = $pdo->query("
    SELECT subjects.id, subjects.subject_name, COUNT(past_papers.id) as paper_count 
    FROM subjects 
    LEFT JOIN past_papers ON subjects.id = past_papers.subject_id 
    GROUP BY subjects.id 
    HAVING paper_count > 0 
    ORDER BY paper_count DESC 
    LIMIT 5
");
$popular_subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Search Papers - Student</title>
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

        /* Search Box */
        .search-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .search-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .search-input-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .search-input {
            flex: 3;
            min-width: 250px;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .search-input:focus {
            border-color: #008751;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0,135,81,0.1);
        }

        .search-type {
            flex: 1;
            min-width: 150px;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            background: white;
            cursor: pointer;
        }

        .search-type:focus {
            border-color: #008751;
            outline: none;
        }

        .search-btn {
            flex: 0.5;
            min-width: 120px;
            padding: 15px 25px;
            background: #008751;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .search-btn:hover {
            background: #00663d;
            transform: translateY(-2px);
        }

        /* Popular Subjects */
        .popular-section {
            margin-top: 20px;
        }

        .popular-title {
            color: #666;
            margin-bottom: 10px;
            font-size: 0.9rem;
        }

        .popular-tags {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .popular-tag {
            background: #e8f5e9;
            color: #008751;
            padding: 8px 16px;
            border-radius: 20px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .popular-tag:hover {
            background: #008751;
            color: white;
            transform: translateY(-2px);
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

        .results-count {
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
            transition: all 0.3s;
            border: 1px solid #eee;
        }

        .paper-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,135,81,0.15);
            border-color: #008751;
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

        .suggestions {
            background: #f9fff9;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .suggestions h4 {
            color: #008751;
            margin-bottom: 15px;
        }

        .suggestion-items {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .suggestion-item {
            background: white;
            padding: 10px 20px;
            border-radius: 25px;
            color: #008751;
            text-decoration: none;
            border: 1px solid #008751;
            transition: all 0.3s;
        }

        .suggestion-item:hover {
            background: #008751;
            color: white;
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
            
            .search-input-group {
                flex-direction: column;
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
            <h1>🔍 Search Papers</h1>
            <p>Search for past papers by title, subject, or filename</p>
        </div>

        <!-- Search Section -->
        <div class="search-section">
            <form method="GET" class="search-form">
                <div class="search-input-group">
                    <input type="text" 
                           name="q" 
                           class="search-input" 
                           placeholder="Enter your search term..." 
                           value="<?php echo htmlspecialchars($search_term); ?>"
                           autofocus>
                    
                    <select name="type" class="search-type">
                        <option value="all" <?php echo $search_type == 'all' ? 'selected' : ''; ?>>All Fields</option>
                        <option value="title" <?php echo $search_type == 'title' ? 'selected' : ''; ?>>Paper Title</option>
                        <option value="subject" <?php echo $search_type == 'subject' ? 'selected' : ''; ?>>Subject Name</option>
                        <option value="filename" <?php echo $search_type == 'filename' ? 'selected' : ''; ?>>File Name</option>
                    </select>
                    
                    <button type="submit" class="search-btn">
                        <span>🔍</span> Search
                    </button>
                </div>
            </form>

            <!-- Popular Subjects -->
            <?php if (!empty($popular_subjects)): ?>
            <div class="popular-section">
                <div class="popular-title">Popular subjects:</div>
                <div class="popular-tags">
                    <?php foreach ($popular_subjects as $subject): ?>
                        <a href="?q=<?php echo urlencode($subject['subject_name']); ?>&type=subject" class="popular-tag">
                            <span>📚</span> <?php echo htmlspecialchars($subject['subject_name']); ?>
                            <span style="background: rgba(0,135,81,0.2); padding: 2px 6px; border-radius: 10px; font-size: 0.8rem;">
                                <?php echo $subject['paper_count']; ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Search Results -->
        <?php if (!empty($search_term)): ?>
            <div class="results-header">
                <h2>
                    <span>📄</span> 
                    Search Results for "<?php echo htmlspecialchars($search_term); ?>"
                </h2>
                <span class="results-count">
                    <?php echo $total_results; ?> paper<?php echo $total_results != 1 ? 's' : ''; ?> found
                </span>
            </div>

            <?php if (!empty($search_results)): ?>
                <div class="papers-grid">
                    <?php foreach ($search_results as $paper): ?>
                        <?php 
                        $file = "../uploads/past_papers/" . $paper['file_path'];
                        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                        $is_pdf = $extension == 'pdf';
                        ?>
                        <div class="paper-card">
                            <div class="paper-preview">
                                <?php echo $is_pdf ? '📄' : '🖼️'; ?>
                            </div>
                            <div class="paper-details">
                                <h3><?php echo htmlspecialchars($paper['paper_title']); ?></h3>
                                <div class="paper-subject">
                                    <span>📚</span> <?php echo htmlspecialchars($paper['subject_name']); ?>
                                </div>
                                <div class="paper-meta">
                                    <span>📁 <?php echo strtoupper($extension); ?></span>
                                    <span>📅 <?php echo isset($paper['uploaded_at']) ? date('M d, Y', strtotime($paper['uploaded_at'])) : 'Recently'; ?></span>
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
            <?php else: ?>
                <!-- No Results -->
                <div class="empty-state">
                    <div class="icon">🔍</div>
                    <h3>No Results Found</h3>
                    <p>We couldn't find any papers matching "<?php echo htmlspecialchars($search_term); ?>"</p>
                    
                    <div class="suggestions">
                        <h4>💡 Suggestions:</h4>
                        <div class="suggestion-items">
                            <a href="?q=exam&type=all" class="suggestion-item">exam</a>
                            <a href="?q=test&type=all" class="suggestion-item">test</a>
                            <a href="?q=final&type=all" class="suggestion-item">final</a>
                            <a href="?q=midterm&type=all" class="suggestion-item">midterm</a>
                            <a href="?q=quiz&type=all" class="suggestion-item">quiz</a>
                        </div>
                        <p style="margin-top: 20px; color: #999; font-size: 0.9rem;">
                            Try using different keywords or browse by subject instead.
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <!-- No Search Yet -->
            <div class="empty-state">
                <div class="icon">🔎</div>
                <h3>Start Your Search</h3>
                <p>Enter a search term above to find past papers</p>
                
                <?php if (!empty($popular_subjects)): ?>
                <div class="suggestions">
                    <h4>📚 Or browse popular subjects:</h4>
                    <div class="suggestion-items">
                        <?php foreach ($popular_subjects as $subject): ?>
                            <a href="?q=<?php echo urlencode($subject['subject_name']); ?>&type=subject" class="suggestion-item">
                                <?php echo htmlspecialchars($subject['subject_name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
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