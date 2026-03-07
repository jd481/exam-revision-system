<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once "../config/database.php";

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $full_name = trim($_POST["full_name"]);
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    $role = $_POST["role"] ?? '';

    if (empty($full_name) || empty($email) || empty($password) || empty($role)) {
        $error = "All fields are required.";
    } else {

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)");

        try {

            $stmt->execute([$full_name, $email, $hashed_password, $role]);
            
            // Redirect to login page with success message
            header("Location: login.php?registration=success");
            exit();

        } catch (PDOException $e) {

            $error = "Error registering user.";

        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Register</title>
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

    .register-container {
        width: 100%;
        max-width: 450px;
        padding: 20px;
    }

    .register-card {
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
        margin-bottom: 25px;
        font-size: 0.95rem;
    }

    .form-group {
        margin-bottom: 15px;
    }

    input, select {
        width: 100%;
        padding: 12px 15px;
        margin: 5px 0 10px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 1rem;
        transition: all 0.3s;
        background: #f9f9f9;
    }

    input:focus, select:focus {
        border-color: #008751;
        outline: none;
        background: white;
        box-shadow: 0 0 0 3px rgba(0,135,81,0.1);
    }

    select {
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23008751' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right 15px center;
        background-size: 15px;
    }

    .error {
        background: #ffebee;
        color: #c62828;
        padding: 12px 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        border-left: 4px solid #c62828;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        gap: 8px;
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
        margin: 15px 0 20px;
    }

    button:hover {
        background: #00663d;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,135,81,0.3);
    }

    .login-link {
        text-align: center;
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #eee;
    }

    .login-link a {
        color: #008751;
        text-decoration: none;
        font-weight: bold;
        transition: color 0.3s;
    }

    .login-link a:hover {
        color: #00663d;
        text-decoration: underline;
    }

    /* Responsive */
    @media (max-width: 480px) {
        .register-card {
            padding: 25px 20px;
        }
        
        h2 {
            font-size: 1.8rem;
        }
    }
</style>
</head>
<body>

<div class="register-container">
    <div class="register-card">
        <h2>📝 Register</h2>
        <div class="subtitle">Create your account to access past papers</div>

        <?php if ($error): ?>
            <div class="error">
                <span>⚠️</span>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <input type="text" 
                       name="full_name" 
                       placeholder="Full Name (e.g., Dr. John Doe)" 
                       value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                       required>
            </div>

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

            <div class="form-group">
                <select name="role" required>
                    <option value="">Select Role</option>
                    <option value="student" <?php echo (isset($_POST['role']) && $_POST['role'] == 'student') ? 'selected' : ''; ?>>Student</option>
                    <option value="lecturer" <?php echo (isset($_POST['role']) && $_POST['role'] == 'lecturer') ? 'selected' : ''; ?>>Lecturer</option>
                </select>
            </div>

            <button type="submit">
                <span>✅</span> Register
            </button>

            <div class="login-link">
                Already have an account? <a href="login.php">Login here</a>
            </div>
        </form>
    </div>
</div>

</body>
</html>