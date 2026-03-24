<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

echo "<h1>🔍 Debug Login</h1>";

// Show session data
echo "<h2>Session before login:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

try {
    require_once __DIR__ . '/../config/database.php';
    echo "<p style='color:green'>✅ Database connected</p>";
    
    // Show database info
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $user_count = $stmt->fetchColumn();
    echo "<p>Total users in database: $user_count</p>";
    
} catch(Exception $e) {
    echo "<p style='color:red'>❌ Database error: " . $e->getMessage() . "</p>";
}

// Simple login form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    echo "<h2>Login Attempt:</h2>";
    echo "<p>Email: $email</p>";
    echo "<p>Password: [hidden]</p>";
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "<p style='color:green'>✅ User found in database</p>";
            echo "<p>User ID: " . $user['id'] . "</p>";
            echo "<p>Role: " . $user['role'] . "</p>";
            echo "<p>School ID: " . ($user['school_id'] ?? 'not set') . "</p>";
            
            if (password_verify($password, $user['password'])) {
                echo "<p style='color:green'>✅ Password correct!</p>";
                
                // Set session
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["full_name"] = $user["full_name"];
                $_SESSION["role"] = $user["role"];
                $_SESSION["school_id"] = $user["school_id"] ?? 1;
                
                echo "<p style='color:green'>✅ Session set!</p>";
                echo "<p>Redirecting to: " . ($user["role"] === "lecturer" ? "../lecturer/dashboard.php" : "../student/dashboard.php") . "</p>";
                
                // Uncomment to actually redirect
                // header("Location: " . ($user["role"] === "lecturer" ? "../lecturer/dashboard.php" : "../student/dashboard.php"));
                // exit();
                
            } else {
                echo "<p style='color:red'>❌ Password incorrect</p>";
            }
        } else {
            echo "<p style='color:red'>❌ User not found</p>";
        }
    } catch(Exception $e) {
        echo "<p style='color:red'>❌ Query error: " . $e->getMessage() . "</p>";
    }
}
?>

<h2>Test Login Form</h2>
<form method="POST">
    <input type="email" name="email" placeholder="Email" required><br><br>
    <input type="password" name="password" placeholder="Password" required><br><br>
    <button type="submit">Test Login</button>
</form>

<p><a href="login.php">Back to regular login</a></p>