<?php
session_start();
include "db.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - View Users</title>
    <style>
        body {
            font-family: Arial;
            padding: 30px;
            background: #f5f5f5;
        }

        h2 {
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: white;
        }

        th {
            background: #343a40;
            color: white;
        }

        td, th {
            padding: 10px;
            text-align: center;
            border: 1px solid #ccc;
        }

        tr:nth-child(even) {
            background: #f2f2f2;
        }

        .back {
            display: inline-block;
            margin-bottom: 20px;
            text-decoration: none;
            background: #007bff;
            color: white;
            padding: 10px 15px;
            border-radius: 5px;
        }

        .back:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>

    <a href="admin_dashboard.php" style="text-decoration:none; padding:8px 12px; background:#343a40; color:white; border-radius:4px;">&larr; Back to Dashboard</a>
    <h2>Registered Users</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Registered At</th>
        </tr>

        <?php
        $users = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC");
        if(mysqli_num_rows($users) > 0){
            while($row = mysqli_fetch_assoc($users)){
                echo "<tr>
                        <td>{$row['user_id']}</td>
                        <td>{$row['name']}</td>
                        <td>{$row['email']}</td>
                        <td>{$row['role']}</td>
                        <td>{$row['created_at']}</td>
                    </tr>";
            }
        } else {
            echo "<tr><td colspan='5'>No users found</td></tr>";
        }
        ?>
    </table>

</body>
</html>
