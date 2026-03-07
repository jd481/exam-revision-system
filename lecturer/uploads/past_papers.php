<?php
session_start();
require_once '../config/database.php';

// Ensure only lecturers access
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] != "lecturer") {
    header("Location: ../auth/login.php");
    exit();
}

$lecturer_id = $_SESSION["user_id"];

// Get all past papers uploaded by this lecturer
$stmt = $pdo->prepare("
    SELECT past_papers.*, subjects.subject_name 
    FROM past_papers 
    JOIN subjects ON past_papers.subject_id = subjects.id
    WHERE subjects.lecturer_id = ?
    ORDER BY past_papers.id DESC
");

$stmt->execute([$lecturer_id]);
$papers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<title>Past Papers</title>

<style>

body{
    font-family: Arial;
    background:#f5f6fa;
}

.container{
    width:90%;
    max-width:900px;
    margin:auto;
    margin-top:40px;
}

h2{
    color:#008751;
    margin-bottom:20px;
}

table{
    width:100%;
    border-collapse:collapse;
    background:white;
    box-shadow:0 0 10px rgba(0,0,0,0.05);
}

th,td{
    padding:12px;
    border-bottom:1px solid #ddd;
    text-align:left;
}

th{
    background:#008751;
    color:white;
}

tr:hover{
    background:#f1fdf7;
}

a.view{
    background:#008751;
    color:white;
    padding:6px 12px;
    text-decoration:none;
    border-radius:4px;
    font-size:14px;
}

a.view:hover{
    background:#00663d;
}

.empty{
    background:white;
    padding:20px;
    text-align:center;
    border-radius:6px;
    box-shadow:0 0 10px rgba(0,0,0,0.05);
}

</style>
</head>

<body>

<div class="container">

<h2>Your Uploaded Past Papers</h2>

<?php if(count($papers) > 0): ?>

<table>

<tr>
<th>ID</th>
<th>Subject</th>
<th>File</th>
<th>Action</th>
</tr>

<?php foreach($papers as $paper): ?>

<tr>

<td><?php echo $paper['id']; ?></td>

<td><?php echo htmlspecialchars($paper['subject_name']); ?></td>

<td><?php echo htmlspecialchars($paper['file_path']); ?></td>

<td>
<a class="view" 
href="../uploads/past_papers/<?php echo htmlspecialchars($paper['file_path']); ?>" 
target="_blank">
View
</a>
</td>

</tr>

<?php endforeach; ?>

</table>

<?php else: ?>

<div class="empty">
No past papers uploaded yet.
</div>

<?php endif; ?>

</div>

</body>
</html>