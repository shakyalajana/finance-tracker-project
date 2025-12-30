<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$query = "
SELECT  u.user_id, u.name, u.email, u.created_at, u.last_login, 
COUNT(DISTINCT i.id) AS income_count, COUNT(DISTINCT e.id) AS expense_count
FROM users u
LEFT JOIN income i ON u.user_id = i.user_id
LEFT JOIN expenses e ON u.user_id = e.user_id
WHERE u.role = 'user'
GROUP BY u.user_id
ORDER BY u.created_at DESC
";
$users = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - View Users</title>
    <style>
        body {
            font-family: 'Poppins', Arial, sans-serif;
            background: #eef2f7;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        h2 {
            margin-bottom: 20px;
            color: #333;
        }

        a.back {
            display: inline-block;
            margin-bottom: 15px;
            padding: 8px 14px;
            background: #0c4aad;
            color: white;
            text-decoration: none;
            border-radius: 6px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 6px 18px rgba(0,0,0,0.08);
            border-radius: 10px;
            overflow: hidden;
        }

        th {
            background: #0c4aad;
            color: white;
            padding: 12px;
            font-weight: 500;
        }

        td {
            padding: 10px;
            text-align: center;
            border-bottom: 1px solid #eee;
        }

        tr:nth-child(even) {
            background: #f8f9fc;
        }

        .active {
            color: green;
            font-weight: bold;
        }

        .inactive {
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="container">

    <a href="admin_dashboard.php" class="back">← Back to Dashboard</a>

    <h2>User Activity Overview</h2>

    <table>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Joined On</th>
            <th>Last Login</th>
            <th>Income Entries</th>
            <th>Expense Entries</th>
            <th>Status</th>
        </tr>

        <?php while ($row = mysqli_fetch_assoc($users)) {
            $lastLogin = $row['last_login'];
            if ($lastLogin && strtotime($lastLogin) >= strtotime('-30 days')) {
                $status = 'Active';
            } else {
                $status = 'Inactive';
            }
        ?>
        <tr>
            <td><?php echo htmlspecialchars($row['name']); ?></td>
            <td><?php echo htmlspecialchars($row['email']); ?></td>
            <td><?php echo $row['created_at']; ?></td>
        <td> <?php echo $row['last_login'] ? date("d M Y, h:i A", strtotime($row['last_login'])) : 'Never logged in!';
            ?>
        </td>
            <td><?php echo $row['income_count']; ?></td>
            <td><?php echo $row['expense_count']; ?></td>
            <td class="<?php echo strtolower($status); ?>">
                <?php echo $status; ?>
            </td>
        </tr>
        <?php } ?>
    </table>

</div>
</body>
</html>
