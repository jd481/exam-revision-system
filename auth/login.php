<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once "../config/database.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user["password"])) {

        $_SESSION["user_id"] = $user["id"];
        $_SESSION["full_name"] = $user["full_name"];
        $_SESSION["role"] = $user["role"];

        if ($user["role"] === "lecturer") {
            header("Location: ../lecturer/dashboard.php");
        } elseif ($user["role"] === "student") {
            header("Location: ../student/dashboard.php");
        }
        exit();

    } else {
        $error = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Login</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>

    body{
        font-family: Arial, sans-serif;
        background:#f4f9f4;
    }

    form { 
        max-width: 320px; 
        margin: 80px auto; 
        padding: 25px; 
        border: 1px solid #dcdcdc; 
        border-radius: 6px; 
        background: #ffffff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }

    h2 { 
        text-align: center; 
        color: #2e7d32;   /* university green */
        margin-bottom: 20px;
    }

    input { 
        width: 100%; 
        padding: 10px; 
        margin: 8px 0; 
        border: 1px solid #ccc; 
        border-radius: 4px; 
        box-sizing: border-box; 
    }

    input:focus{
        border-color:#2e7d32;
        outline:none;
    }

    button { 
        width: 100%; 
        padding: 12px; 
        background: #2e7d32;  /* main green */
        color: white; 
        border: none; 
        border-radius: 4px; 
        cursor: pointer; 
        margin-top: 10px;
        font-weight:bold;
    }

    button:hover { 
        background: #1b5e20; /* darker green */
    }

    .error { 
        color: #b71c1c; 
        margin: 10px 0; 
        padding: 10px; 
        background: #ffebee; 
        border: 1px solid #e57373; 
        border-radius: 4px;
        text-align:center;
    }

</style>

</head>

<body>

<form method="POST">

<h2>Login</h2>

<?php if (!empty($error)): ?>
<div class="error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<input type="email" name="email" placeholder="Email" required
value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">

<input type="password" name="password" placeholder="Password" required>

<button type="submit">Login</button>

</form>

</body>
</html>