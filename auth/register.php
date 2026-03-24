<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once "../config/database.php";

$error = '';

// Fetch schools for dropdown
$schools = [];
try {
    $stmt = $pdo->query("SELECT id, school_name, school_code FROM schools ORDER BY school_name");
    $schools = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // If schools table doesn't exist yet, show error
    $error = "System setup incomplete. Please contact administrator.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $full_name = trim($_POST["full_name"]);
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    $confirm_password = trim($_POST["confirm_password"]);
    $role = $_POST["role"] ?? '';
    $school_id = $_POST["school_id"] ?? '';

    if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password) || empty($role) || empty($school_id)) {
        $error = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Check if email already exists
        $check_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check_stmt->execute([$email]);
        if ($check_stmt->fetch()) {
            $error = "Email already registered. Please use a different email or login.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role, school_id) VALUES (?, ?, ?, ?, ?)");

            try {
                $stmt->execute([$full_name, $email, $hashed_password, $role, $school_id]);
                
                // Redirect to login page with success message
                header("Location: login.php?registration=success");
                exit();

            } catch (PDOException $e) {
                $error = "Error registering user. Please try again.";
            }
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
        max-width: 500px;
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
        position: relative;
    }

    label {
        display: block;
        margin-bottom: 5px;
        color: #555;
        font-weight: 600;
        font-size: 0.9rem;
    }

    input, select {
        width: 100%;
        padding: 12px 15px;
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

    /* Password field with toggle */
    .password-wrapper {
        position: relative;
        width: 100%;
    }

    .password-wrapper input {
        padding-right: 45px;
    }

    .toggle-password {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        font-size: 1.2rem;
        color: #008751;
        user-select: none;
        background: transparent;
        border: none;
        padding: 0;
    }

    .toggle-password:hover {
        color: #00663d;
    }

    .password-strength {
        height: 4px;
        margin-top: 5px;
        border-radius: 2px;
        transition: all 0.3s;
    }

    .strength-weak {
        background: #ff4444;
        width: 33%;
    }

    .strength-medium {
        background: #ffbb33;
        width: 66%;
    }

    .strength-strong {
        background: #00C851;
        width: 100%;
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

    button:disabled {
        background: #cccccc;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
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

    .school-badge {
        background: #e8f5e9;
        color: #008751;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 0.8rem;
        margin-left: 5px;
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

        <form method="POST" id="registerForm">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" 
                       name="full_name" 
                       id="full_name"
                       placeholder="e.g., Dr. John Doe" 
                       value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                       required>
            </div>

            <div class="form-group">
                <label>Email Address</label>
                <input type="email" 
                       name="email" 
                       id="email"
                       placeholder="your@email.com" 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                       required>
            </div>

            <div class="form-group">
                <label>School/Institution</label>
                <select name="school_id" id="school_id" required>
                    <option value="">-- Select your school --</option>
                    <?php foreach ($schools as $school): ?>
                        <option value="<?php echo $school['id']; ?>" 
                            <?php echo (isset($_POST['school_id']) && $_POST['school_id'] == $school['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($school['school_name']); ?> 
                            <span class="school-badge"><?php echo $school['school_code']; ?></span>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Role</label>
                <select name="role" id="role" required>
                    <option value="">-- Select role --</option>
                    <option value="student" <?php echo (isset($_POST['role']) && $_POST['role'] == 'student') ? 'selected' : ''; ?>>Student</option>
                    <option value="lecturer" <?php echo (isset($_POST['role']) && $_POST['role'] == 'lecturer') ? 'selected' : ''; ?>>Lecturer</option>
                </select>
            </div>

            <div class="form-group">
                <label>Password</label>
                <div class="password-wrapper">
                    <input type="password" 
                           name="password" 
                           id="password"
                           placeholder="Minimum 6 characters" 
                           required>
                    <span class="toggle-password" onclick="togglePassword('password')">👁️</span>
                </div>
                <div class="password-strength" id="password-strength"></div>
            </div>

            <div class="form-group">
                <label>Confirm Password</label>
                <div class="password-wrapper">
                    <input type="password" 
                           name="confirm_password" 
                           id="confirm_password"
                           placeholder="Re-enter password" 
                           required>
                    <span class="toggle-password" onclick="togglePassword('confirm_password')">👁️</span>
                </div>
                <div id="password-match" style="font-size: 0.85rem; margin-top: 5px;"></div>
            </div>

            <button type="submit" id="submitBtn">
                <span>✅</span> Register
            </button>

            <div class="login-link">
                Already have an account? <a href="login.php">Login here</a>
            </div>
        </form>
    </div>
</div>

<script>
// Toggle password visibility
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const type = field.getAttribute('type') === 'password' ? 'text' : 'password';
    field.setAttribute('type', type);
    
    // Toggle the eye icon
    const toggleBtn = event.target;
    toggleBtn.textContent = type === 'password' ? '👁️' : '👁️‍🗨️';
}

// Password strength checker
document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    const strengthBar = document.getElementById('password-strength');
    const strength = checkPasswordStrength(password);
    
    strengthBar.className = 'password-strength';
    
    if (password.length === 0) {
        strengthBar.style.width = '0';
    } else if (strength === 'weak') {
        strengthBar.classList.add('strength-weak');
    } else if (strength === 'medium') {
        strengthBar.classList.add('strength-medium');
    } else if (strength === 'strong') {
        strengthBar.classList.add('strength-strong');
    }
});

function checkPasswordStrength(password) {
    if (password.length < 6) return 'weak';
    
    let strength = 0;
    
    // Check for numbers
    if (password.match(/[0-9]/)) strength++;
    // Check for lowercase letters
    if (password.match(/[a-z]/)) strength++;
    // Check for uppercase letters
    if (password.match(/[A-Z]/)) strength++;
    // Check for special characters
    if (password.match(/[^a-zA-Z0-9]/)) strength++;
    
    if (password.length >= 8 && strength >= 3) return 'strong';
    if (password.length >= 6 && strength >= 2) return 'medium';
    return 'weak';
}

// Password match checker
document.getElementById('confirm_password').addEventListener('input', checkPasswordMatch);
document.getElementById('password').addEventListener('input', checkPasswordMatch);

function checkPasswordMatch() {
    const password = document.getElementById('password').value;
    const confirm = document.getElementById('confirm_password').value;
    const matchDiv = document.getElementById('password-match');
    const submitBtn = document.getElementById('submitBtn');
    
    if (confirm.length === 0) {
        matchDiv.innerHTML = '';
        matchDiv.style.color = '';
        submitBtn.disabled = false;
    } else if (password === confirm) {
        matchDiv.innerHTML = '✓ Passwords match';
        matchDiv.style.color = '#008751';
        submitBtn.disabled = false;
    } else {
        matchDiv.innerHTML = '✗ Passwords do not match';
        matchDiv.style.color = '#c62828';
        submitBtn.disabled = true;
    }
}

// Form validation
document.getElementById('registerForm').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirm = document.getElementById('confirm_password').value;
    const full_name = document.getElementById('full_name').value.trim();
    const email = document.getElementById('email').value.trim();
    const role = document.getElementById('role').value;
    const school_id = document.getElementById('school_id').value;
    
    if (full_name === '' || email === '' || password === '' || confirm === '' || role === '' || school_id === '') {
        e.preventDefault();
        alert('Please fill in all fields');
        return;
    }
    
    if (password.length < 6) {
        e.preventDefault();
        alert('Password must be at least 6 characters long');
        return;
    }
    
    if (password !== confirm) {
        e.preventDefault();
        alert('Passwords do not match');
        return;
    }
});

// Auto-dismiss error message after 5 seconds
setTimeout(function() {
    const errorDiv = document.querySelector('.error');
    if (errorDiv) {
        errorDiv.style.transition = 'opacity 1s';
        errorDiv.style.opacity = '0';
        setTimeout(() => errorDiv.remove(), 1000);
    }
}, 5000);
</script>

</body>
</html>