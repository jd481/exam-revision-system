<?php
session_start();
require_once '../config/database.php';

// Ensure only lecturers access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'lecturer') {
    header("Location: ../auth/login.php");
    exit();
}

$error = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $subject_name = trim($_POST['subject_name']);
    $lecturer_id = $_SESSION['user_id'];

    if (!empty($subject_name)) {

        $stmt = $pdo->prepare("INSERT INTO subjects (subject_name, lecturer_id) VALUES (?, ?)");

        if ($stmt->execute([$subject_name, $lecturer_id])) {
            header("Location: add_subject.php?success=1");
            exit();
        } else {
            $error = "Error adding subject.";
        }

    } else {
        $error = "Please enter subject name.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Add Subject</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>

body{
    margin:0;
    font-family: Arial, sans-serif;
    background:#f5f6fa;
}

form{
    max-width:400px;
    margin:60px auto;
    padding:30px;
    border-radius:8px;
    background:#fff;
    display:flex;
    flex-direction:column;
    box-shadow:0 4px 10px rgba(0,0,0,0.05);
}

h2{
    text-align:center;
    margin-bottom:25px;
    color:#008751;
}

input,button{
    padding:12px;
    margin:10px 0;
    border-radius:4px;
    border:1px solid #ccc;
}

input:focus{
    border-color:#008751;
    box-shadow:0 0 5px rgba(0,135,81,0.4);
    outline:none;
}

button{
    background:#008751;
    color:white;
    border:none;
    cursor:pointer;
    font-weight:bold;
}

button:hover{
    background:#00663d;
}

.error{
    color:#721c24;
    text-align:center;
    padding:10px;
    background:#f8d7da;
    border:1px solid #f5c6cb;
    border-radius:4px;
}

.success{
    color:#155724;
    text-align:center;
    padding:10px;
    background:#d4edda;
    border:1px solid #c3e6cb;
    border-radius:4px;
    opacity:1;
    transition:opacity 1s ease;
}

a{
    text-align:center;
    margin-top:15px;
    color:#008751;
    text-decoration:none;
    font-weight:bold;
}

a:hover{
    text-decoration:underline;
}

</style>
</head>

<body>

<form method="POST">

<h2>Add Subject</h2>

<?php if (!empty($error)): ?>
<div class="error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if (isset($_GET['success'])): ?>
<div class="success">Subject added successfully!</div>
<?php endif; ?>

<input
type="text"
name="subject_name"
placeholder="Subject Name"
required
value="<?php echo isset($_POST['subject_name']) ? htmlspecialchars($_POST['subject_name']) : ''; ?>"
>

<button type="submit">Add Subject</button>

<a href="dashboard.php">Back to Dashboard</a>

</form>

<script>

const successDiv = document.querySelector('.success');

if(successDiv){
    setTimeout(() => {
        successDiv.style.opacity = "0";
        setTimeout(() => {
            successDiv.style.display = "none";
        },1000);
    },3000);
}

</script>

</body>
</html>