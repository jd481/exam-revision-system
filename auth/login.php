<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once __DIR__ . '/../config/database.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    if (empty($email) || empty($password)) {
        $error = "Email and password are required.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user["password"])) {

                // Store session variables
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["full_name"] = $user["full_name"];
                $_SESSION["role"] = $user["role"];
                $_SESSION["school_id"] = $user["school_id"] ?? 1;

                // Redirect based on role
                if ($user["role"] === "lecturer") {
                    header("Location: ../lecturer/dashboard.php");
                } else {
                    header("Location: ../student/dashboard.php");
                }
                exit();

            } else {
                $error = "Invalid email or password.";
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error = "A database error occurred. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
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
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 20px;
        }
        .login-card {
            background: white;
            border-radius: 12px;
            padding: 35px 30px;
            box-shadow: 0 10px 30px rgba(0,135,81,0.15);
            border-left: 5px solid #008751;
        }
        h2 {
            color: #008751;
            font-size: 2rem;
            margin-bottom: 10px;
            text-align: center;
        }
        .subtitle {
            color: #666;
            text-align: center;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }
        input:focus {
            border-color: #008751;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0,135,81,0.1);
        }
        .error {
            background: #ffebee;
            color: #c62828;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #c62828;
        }
        button {
            width: 100%;
            padding: 14px;
            background: #008751;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            margin: 10px 0 20px;
        }
        button:hover {
            background: #00663d;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,135,81,0.3);
        }
        .register-link {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .register-link a {
            color: #008751;
            text-decoration: none;
            font-weight: bold;
        }
        .register-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <h2>🔐 Login</h2>
            <div class="subtitle">Welcome back! Please login to your account</div>
            
            <?php if (!empty($error)): ?>
                <div class="error">
                    ⚠️ <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <input type="email" 
                           name="email" 
                           placeholder="Email" 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                           required>
                </div>

                <div class="form-group">
                    <input type="password" 
                           name="password" 
                           placeholder="Password" 
                           required>
                </div>

                <button type="submit">Login</button>

                <div class="register-link">
                    Don't have an account? <a href="register.php">Register here</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>