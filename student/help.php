<?php
session_start();
require_once '../config/database.php';

/* Ensure only students access */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../auth/login.php");
    exit();
}

$student_name = $_SESSION['full_name'];
$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['email'] ?? '';

// Check for session success message
$contact_success = '';
if (isset($_SESSION['contact_success'])) {
    $contact_success = $_SESSION['contact_success'];
    unset($_SESSION['contact_success']);
}

// Get user stats for personalized help
$stmt = $pdo->prepare("SELECT COUNT(*) as download_count FROM downloads WHERE user_id = ?");
$stmt->execute([$user_id]);
$download_count = $stmt->fetch(PDO::FETCH_ASSOC)['download_count'];

$stmt = $pdo->prepare("SELECT COUNT(*) as favorite_count FROM favorites WHERE user_id = ?");
$stmt->execute([$user_id]);
$favorite_count = $stmt->fetch(PDO::FETCH_ASSOC)['favorite_count'];

// Handle contact form submission
$contact_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $contact_type = $_POST['contact_type'] ?? 'general';
    
    if (empty($name) || empty($email) || empty($message)) {
        $contact_error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $contact_error = 'Please enter a valid email address.';
    } else {
        // Create contact_messages table if it doesn't exist
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS contact_messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL,
                subject VARCHAR(200),
                message TEXT NOT NULL,
                contact_type VARCHAR(50) DEFAULT 'general',
                status VARCHAR(20) DEFAULT 'unread',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            )
        ");
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO contact_messages (user_id, name, email, subject, message, contact_type) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $name, $email, $subject, $message, $contact_type]);
            
            // Set success message in session and redirect to clear form
            $_SESSION['contact_success'] = 'Your message has been sent successfully! We\'ll respond within 24 hours.';
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
            
        } catch (PDOException $e) {
            $contact_error = 'Error sending message. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Help & Support - Student</title>
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

        /* Quick Stats */
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
            font-size: 1.5rem;
            font-weight: bold;
            color: #008751;
        }

        /* Help Grid */
        .help-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .help-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: 1px solid #eee;
            transition: transform 0.3s;
        }

        .help-card:hover {
            transform: translateY(-5px);
            border-color: #008751;
        }

        .help-icon {
            width: 60px;
            height: 60px;
            background: #e8f5e9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: #008751;
            margin-bottom: 15px;
        }

        .help-card h3 {
            color: #008751;
            margin-bottom: 15px;
            font-size: 1.3rem;
        }

        .help-card p {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .help-list {
            list-style: none;
        }

        .help-list li {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #555;
        }

        .help-list li:last-child {
            border-bottom: none;
        }

        .help-list li span {
            color: #008751;
            font-weight: bold;
        }

        /* FAQ Section */
        .faq-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .faq-section h2 {
            color: #008751;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .faq-item {
            margin-bottom: 15px;
            border: 1px solid #eee;
            border-radius: 8px;
            overflow: hidden;
        }

        .faq-question {
            background: #f9fff9;
            padding: 15px 20px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: bold;
            color: #333;
            transition: background 0.3s;
        }

        .faq-question:hover {
            background: #e8f5e9;
        }

        .faq-question.active {
            background: #008751;
            color: white;
        }

        .faq-answer {
            padding: 0 20px;
            max-height: 0;
            overflow: hidden;
            transition: all 0.3s ease;
            background: white;
            color: #666;
            line-height: 1.6;
        }

        .faq-answer.show {
            padding: 20px;
            max-height: 300px;
            border-top: 1px solid #eee;
        }

        .arrow {
            transition: transform 0.3s;
        }

        .faq-question.active .arrow {
            transform: rotate(180deg);
            color: white;
        }

        /* Contact Section */
        .contact-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .contact-section h2 {
            color: #008751;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .contact-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: #f9fff9;
            border-radius: 8px;
            border: 1px solid #eee;
            cursor: pointer;
            transition: all 0.3s;
        }

        .contact-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,135,81,0.2);
            border-color: #008751;
        }

        .contact-icon {
            width: 45px;
            height: 45px;
            background: #e8f5e9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #008751;
        }

        .contact-details h4 {
            color: #333;
            margin-bottom: 5px;
        }

        .contact-details p {
            color: #666;
            font-size: 0.9rem;
        }

        .contact-details a {
            color: #008751;
            text-decoration: none;
        }

        .contact-details a:hover {
            text-decoration: underline;
        }

        /* Contact Modal */
        .contact-modal {
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

        .contact-modal-content {
            background: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            position: relative;
            border-left: 5px solid #008751;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .contact-modal-content h3 {
            color: #008751;
            margin-bottom: 20px;
            font-size: 1.5rem;
            text-align: center;
        }

        .close-modal {
            position: absolute;
            top: 15px;
            right: 20px;
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close-modal:hover {
            color: #008751;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #008751;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0,135,81,0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .success-message {
            background: #e8f5e9;
            color: #008751;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
            border: 1px solid #008751;
            font-size: 1.1rem;
        }

        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #ef5350;
        }

        .contact-submit-btn {
            background: #008751;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: bold;
            transition: all 0.3s;
        }

        .contact-submit-btn:hover {
            background: #00663d;
            transform: translateY(-2px);
        }

        .contact-cancel-btn {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .contact-cancel-btn:hover {
            background: #545b62;
            transform: translateY(-2px);
        }

        /* Video Tutorials */
        .videos-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        .videos-section h2 {
            color: #008751;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .video-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .video-item {
            text-align: center;
            padding: 20px;
            background: #f9fff9;
            border-radius: 8px;
            border: 1px solid #eee;
            transition: all 0.3s;
            cursor: pointer;
        }

        .video-item:hover {
            border-color: #008751;
            transform: translateY(-3px);
        }

        .video-thumb {
            width: 80px;
            height: 80px;
            background: #e8f5e9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: #008751;
            margin: 0 auto 15px;
        }

        .video-item h4 {
            color: #333;
            margin-bottom: 5px;
        }

        .video-item p {
            color: #666;
            font-size: 0.85rem;
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
            
            .help-grid {
                grid-template-columns: 1fr;
            }
            
            .contact-grid {
                grid-template-columns: 1fr;
            }
            
            .video-grid {
                grid-template-columns: 1fr;
            }
            
            .contact-modal-content {
                margin: 20% auto;
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
            <h1>
                <span>❓</span> Help & Support
            </h1>
            <p>Get assistance with using the Exam Revision System</p>
        </div>

        <!-- Quick Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📥</div>
                <div class="stat-content">
                    <h3>Your Downloads</h3>
                    <div class="stat-number"><?php echo $download_count; ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⭐</div>
                <div class="stat-content">
                    <h3>Your Favorites</h3>
                    <div class="stat-number"><?php echo $favorite_count; ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📚</div>
                <div class="stat-content">
                    <h3>Available Subjects</h3>
                    <div class="stat-number">
                        <?php
                        $stmt = $pdo->query("SELECT COUNT(*) FROM subjects");
                        echo $stmt->fetchColumn();
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Help Grid -->
        <div class="help-grid">
            <div class="help-card">
                <div class="help-icon">📖</div>
                <h3>Getting Started</h3>
                <ul class="help-list">
                    <li><span>1.</span> Browse subjects from the dashboard</li>
                    <li><span>2.</span> Click "View Past Papers" to see available papers</li>
                    <li><span>3.</span> Use the dropdown to filter by subject</li>
                    <li><span>4.</span> Click "View" to open papers directly</li>
                    <li><span>5.</span> Click "Download" to save papers to your device</li>
                </ul>
            </div>

            <div class="help-card">
                <div class="help-icon">⭐</div>
                <h3>Using Favorites</h3>
                <ul class="help-list">
                    <li><span>⭐</span> Click the star button to save papers</li>
                    <li><span>📋</span> Access saved papers from "Favorites" page</li>
                    <li><span>🗑️</span> Click again to remove from favorites</li>
                    <li><span>📊</span> See your most-saved subjects</li>
                </ul>
            </div>

            <div class="help-card">
                <div class="help-icon">📥</div>
                <h3>Downloads</h3>
                <ul class="help-list">
                    <li><span>📄</span> PDFs open in browser or download</li>
                    <li><span>🖼️</span> Images can be viewed or downloaded</li>
                    <li><span>📊</span> Track your download history</li>
                    <li><span>⏱️</span> See when you downloaded each paper</li>
                </ul>
            </div>
        </div>

        <!-- FAQ Section -->
        <div class="faq-section">
            <h2>
                <span>❓</span> Frequently Asked Questions
            </h2>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    How do I find papers for my subject?
                    <span class="arrow">▼</span>
                </div>
                <div class="faq-answer">
                    Use the "Browse by Subject" link on your dashboard, or select a subject from the dropdown menu on the "View Past Papers" page. You can also use the search feature to find specific papers by title or subject.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    Can I download papers to study offline?
                    <span class="arrow">▼</span>
                </div>
                <div class="faq-answer">
                    Yes! Click the "Download" button on any paper to save it to your device. Downloaded papers can be accessed anytime, even without an internet connection. Your download history is tracked in the "My Downloads" page.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    What file formats are supported?
                    <span class="arrow">▼</span>
                </div>
                <div class="faq-answer">
                    The system supports PDF files and common image formats (JPG, JPEG, PNG). PDFs can be viewed directly in your browser or with Google Docs Viewer for better compatibility.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    How do I save papers to favorites?
                    <span class="arrow">▼</span>
                </div>
                <div class="faq-answer">
                    While viewing papers, click the star button (⭐) next to any paper to add it to your favorites. Access all your saved papers from the "Favorites" page. Click the star again to remove papers from favorites.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFAQ(this)">
                    Why can't I see any papers for my subject?
                    <span class="arrow">▼</span>
                </div>
                <div class="faq-answer">
                    Papers are uploaded by lecturers. If no papers are available for your subject, check back later or contact your lecturer. You can also browse other subjects that might have relevant materials.
                </div>
            </div>
        </div>

        <!-- Contact Section -->
        <div class="contact-section">
            <h2>
                <span>📞</span> Contact Support
            </h2>
            <div class="contact-grid">
                <div class="contact-item" onclick="openContactModal('email')">
                    <div class="contact-icon">📧</div>
                    <div class="contact-details">
                        <h4>Email Support</h4>
                        <p>jumbaderick1@gmail.com</p>
                        <p style="font-size: 0.8rem; color: #008751;">Click to send message</p>
                    </div>
                </div>
                
                <div class="contact-item" onclick="openContactModal('chat')">
                    <div class="contact-icon">💬</div>
                    <div class="contact-details">
                        <h4>Live Chat</h4>
                        <p>Mon-Fri, 9am - 5pm</p>
                        <p style="font-size: 0.8rem; color: #008751;">Coming soon</p>
                    </div>
                </div>
                
                <div class="contact-item" onclick="openContactModal('problem')">
                    <div class="contact-icon">⚠️</div>
                    <div class="contact-details">
                        <h4>Report a Problem</h4>
                        <p>Submit a ticket</p>
                        <p style="font-size: 0.8rem; color: #008751;">Click to report</p>
                    </div>
                </div>
                
                <div class="contact-item">
                    <div class="contact-icon">👨‍🏫</div>
                    <div class="contact-details">
                        <h4>Your Lecturer</h4>
                        <p>Contact your lecturer directly</p>
                        <p style="font-size: 0.8rem;">for subject-specific questions</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Modal -->
        <div id="contactModal" class="contact-modal">
            <div class="contact-modal-content">
                <span class="close-modal" onclick="closeContactModal()">&times;</span>
                <h3>📧 Contact Support</h3>
                
                <?php if ($contact_success): ?>
                    <div class="success-message">
                        ✅ <?php echo $contact_success; ?>
                    </div>
                    <div style="text-align: center; margin-top: 20px;">
                        <button onclick="closeContactModal()" class="contact-submit-btn">Close</button>
                    </div>
                <?php else: ?>
                    <?php if ($contact_error): ?>
                        <div class="error-message">
                            ⚠️ <?php echo $contact_error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" id="contactForm">
                        <div class="form-group">
                            <label for="name">Your Name *</label>
                            <input type="text" id="name" name="name" required 
                                   value="<?php echo htmlspecialchars($student_name); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Your Email *</label>
                            <input type="email" id="email" name="email" required 
                                   value="<?php echo htmlspecialchars($user_email); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="contact_type">Contact Type</label>
                            <select id="contact_type" name="contact_type">
                                <option value="general">General Question</option>
                                <option value="technical">Technical Issue</option>
                                <option value="feedback">Feedback</option>
                                <option value="bug">Report a Bug</option>
                                <option value="feature">Feature Request</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <input type="text" id="subject" name="subject" 
                                   placeholder="Brief description of your issue">
                        </div>
                        
                        <div class="form-group">
                            <label for="message">Message *</label>
                            <textarea id="message" name="message" rows="5" required 
                                      placeholder="Please describe your issue or question in detail..."></textarea>
                        </div>
                        
                        <div style="display: flex; gap: 10px; justify-content: flex-end;">
                            <button type="button" onclick="closeContactModal()" class="contact-cancel-btn">Cancel</button>
                            <button type="submit" name="contact_submit" class="contact-submit-btn">Send Message</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Video Tutorials -->
        <div class="videos-section">
            <h2>
                <span>🎥</span> Video Tutorials
            </h2>
            <div class="video-grid">
                <div class="video-item" onclick="alert('Video tutorial: How to browse papers - Coming soon!')">
                    <div class="video-thumb">📚</div>
                    <h4>Browsing Papers</h4>
                    <p>2:30 min</p>
                </div>
                
                <div class="video-item" onclick="alert('Video tutorial: Using favorites - Coming soon!')">
                    <div class="video-thumb">⭐</div>
                    <h4>Using Favorites</h4>
                    <p>1:45 min</p>
                </div>
                
                <div class="video-item" onclick="alert('Video tutorial: Downloading papers - Coming soon!')">
                    <div class="video-thumb">📥</div>
                    <h4>Downloading</h4>
                    <p>2:15 min</p>
                </div>
                
                <div class="video-item" onclick="alert('Video tutorial: Search tips - Coming soon!')">
                    <div class="video-thumb">🔍</div>
                    <h4>Search Tips</h4>
                    <p>3:00 min</p>
                </div>
            </div>
        </div>

        <!-- Back to Dashboard -->
        <a href="dashboard.php" class="back-btn">
            <span>⬅</span> Back to Dashboard
        </a>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>© <?php echo date('Y'); ?> Exam Revision System. All rights reserved.</p>
        <p style="margin-top: 5px; font-size: 0.8rem;">
            Version 1.0 | Last updated: <?php echo date('F Y'); ?>
        </p>
    </div>

    <script>
    // Toggle FAQ answers
    function toggleFAQ(element) {
        const answer = element.nextElementSibling;
        const isActive = element.classList.contains('active');
        
        // Close all other FAQs
        document.querySelectorAll('.faq-question').forEach(q => {
            if (q !== element) {
                q.classList.remove('active');
                q.nextElementSibling.classList.remove('show');
            }
        });
        
        // Toggle current FAQ
        if (isActive) {
            element.classList.remove('active');
            answer.classList.remove('show');
        } else {
            element.classList.add('active');
            answer.classList.add('show');
        }
    }

    // Contact modal functions
    function openContactModal(type) {
        const modal = document.getElementById('contactModal');
        const typeSelect = document.getElementById('contact_type');
        const subjectInput = document.getElementById('subject');
        
        // Pre-fill based on contact type
        if (type === 'email') {
            if (subjectInput) subjectInput.value = 'Email Support Inquiry';
            if (typeSelect) typeSelect.value = 'general';
        } else if (type === 'problem') {
            if (subjectInput) subjectInput.value = 'Problem Report: ';
            if (typeSelect) typeSelect.value = 'bug';
        } else if (type === 'chat') {
            alert('Live chat is coming soon! Please use email support for now.');
            return;
        }
        
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    function closeContactModal() {
        document.getElementById('contactModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('contactModal');
        if (event.target == modal) {
            closeContactModal();
        }
    }

    // Close with Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeContactModal();
        }
    });
    </script>
</body>
</html>