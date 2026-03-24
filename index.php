<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
session_start();

// If user is already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'lecturer') {
        header("Location: lecturer/dashboard.php");
    } elseif ($_SESSION['role'] === 'student') {
        header("Location: student/dashboard.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Revision System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e8f5e9 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Navigation */
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
            font-size: 1.5rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-links {
            display: flex;
            gap: 20px;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 4px;
            transition: background 0.3s;
        }

        .nav-links a:hover {
            background: rgba(255,255,255,0.2);
        }

        .nav-links .login-btn {
            background: white;
            color: #008751;
            font-weight: bold;
        }

        .nav-links .login-btn:hover {
            background: #f0f0f0;
        }

        /* Hero Section */
        .hero {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            width: 100%;
        }

        .hero-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }

        .hero-text h1 {
            font-size: 2.5rem;
            color: #008751;
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .hero-text p {
            font-size: 1.1rem;
            color: #555;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .university-name {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 2rem;
            font-style: italic;
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: #008751;
            color: white;
        }

        .btn-primary:hover {
            background: #00663d;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,135,81,0.3);
        }

        .btn-secondary {
            background: white;
            color: #008751;
            border: 2px solid #008751;
        }

        .btn-secondary:hover {
            background: #e8f5e9;
            transform: translateY(-2px);
        }

        .hero-image {
            text-align: center;
        }

        .hero-image img {
            max-width: 100%;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,135,81,0.2);
        }

        /* Features Section */
        .features {
            background: white;
            padding: 4rem 2rem;
            text-align: center;
        }

        .features h2 {
            color: #008751;
            font-size: 2rem;
            margin-bottom: 3rem;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .feature-card {
            padding: 2rem;
            border-radius: 12px;
            background: #f9fff9;
            transition: transform 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .feature-card h3 {
            color: #008751;
            margin-bottom: 1rem;
        }

        .feature-card p {
            color: #666;
            line-height: 1.6;
        }

        /* Footer */
        .footer {
            background: #333;
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .footer p {
            margin: 0.5rem 0;
            color: #ccc;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-content {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 2rem;
            }

            .cta-buttons {
                justify-content: center;
            }

            .navbar {
                flex-direction: column;
                gap: 10px;
            }

            .hero-text h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="logo">
            📚 Exam Revision System
        </div>
        <div class="nav-links">
            <a href="#features">Features</a>
            <a href="#about">About</a>
            <a href="auth/login.php" class="login-btn">Login</a>
            <a href="auth/register.php">Register</a>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <div class="hero-text">
                <h1>Access Past Exam Papers<br>Anytime, Anywhere</h1>
                <p>
                    Your comprehensive exam revision platform. Browse, download, and organize 
                    past exam papers by subject. Prepare effectively with easy access to 
                    revision materials from your lecturers.
                </p>
                <div class="cta-buttons">
                    <a href="auth/register.php" class="btn btn-primary">
                        <span>📝</span> Register
                    </a>
                    <a href="auth/login.php" class="btn btn-secondary">
                        <span>🔐</span> Login
                    </a>
                </div>
            </div>
            <div class="hero-image">
                <!-- You can add an illustration or image here -->
                <div style="background: #e8f5e9; padding: 3rem; border-radius: 20px;">
                    <div style="font-size: 8rem;">📚</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features">
        <h2>Why Choose Our Platform?</h2>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">📑</div>
                <h3>Past Papers</h3>
                <p>Access a growing collection of past exam papers uploaded by your lecturers.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔍</div>
                <h3>Easy Search</h3>
                <p>Find papers quickly by subject, title, or keywords with our advanced search.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">⭐</div>
                <h3>Favorites</h3>
                <p>Save important papers to your favorites for quick access later.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📥</div>
                <h3>Downloads</h3>
                <p>Download papers to study offline, anytime, anywhere.</p>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" style="padding: 4rem 2rem; max-width: 1200px; margin: 0 auto;">
        <h2 style="color: #008751; font-size: 2rem; margin-bottom: 2rem; text-align: center;">About the System</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
            <div style="background: white; padding: 2rem; border-radius: 12px;">
                <h3 style="color: #008751; margin-bottom: 1rem;">For Students</h3>
                <ul style="list-style: none; line-height: 2;">
                    <li>✓ Browse papers by subject</li>
                    <li>✓ Download for offline study</li>
                    <li>✓ Save favorites</li>
                    <li>✓ Track download history</li>
                    <li>✓ Search for specific papers</li>
                </ul>
            </div>
            <div style="background: white; padding: 2rem; border-radius: 12px;">
                <h3 style="color: #008751; margin-bottom: 1rem;">For Lecturers</h3>
                <ul style="list-style: none; line-height: 2;">
                    <li>✓ Upload past papers easily</li>
                    <li>✓ Manage your subjects</li>
                    <li>✓ Organize by course</li>
                    <li>✓ Track uploads</li>
                    <li>✓ Help students succeed</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <p>© <?php echo date('Y'); ?> Exam Revision System</p>
        <p>Empowering Academic Excellence</p>
        <p style="margin-top: 1rem; font-size: 0.9rem;">
            <a href="auth/login.php" style="color: #008751; text-decoration: none;">Login</a> | 
            <a href="auth/register.php" style="color: #008751; text-decoration: none;">Register</a>
        </p>
    </footer>
</body>
</html>