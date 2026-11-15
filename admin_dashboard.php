<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

include 'database.php';

// Handle user deletion
if (isset($_GET['delete_user']) && is_numeric($_GET['delete_user'])) {
    $user_id = $_GET['delete_user'];
    
    // Don't allow admin to delete themselves
    if ($user_id != $_SESSION['user_id']) {
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "User deleted successfully";
        } else {
            $_SESSION['error_message'] = "Failed to delete user";
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "You cannot delete your own account";
    }
    
    header("Location: admin_dashboard.php");
    exit();
}

// Get all users
$sql = "SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC";
$result = $conn->query($sql);

// Get statistics
$total_users = $result->num_rows;
$total_clients = 0;
$total_freelancers = 0;
$total_admins = 0;

$users = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
        
        switch ($row['role']) {
            case 'client':
                $total_clients++;
                break;
            case 'freelancer':
                $total_freelancers++;
                break;
            case 'admin':
                $total_admins++;
                break;
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - StudyLance</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <div class="header-content">
                <h1>Admin Dashboard</h1>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                    <a href="logout.php" class="btn btn-logout">Logout</a>
                </div>
            </div>
        </header>

        <nav class="dashboard-nav">
            <ul>
                <li><a href="admin_dashboard.php" class="active">Dashboard</a></li>
                <li><a href="#">Users</a></li>
                <li><a href="#">Jobs</a></li>
                <li><a href="#">Reports</a></li>
            </ul>
        </nav>

        <main class="dashboard-main">
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="success-message">
                    <?php 
                    echo htmlspecialchars($_SESSION['success_message']);
                    unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="error-message">
                    <?php 
                    echo htmlspecialchars($_SESSION['error_message']);
                    unset($_SESSION['error_message']);
                    ?>
                </div>
            <?php endif; ?>

            <section class="stats-section">
                <h2>Platform Statistics</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Total Users</h3>
                        <p class="stat-number"><?php echo $total_users; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Clients</h3>
                        <p class="stat-number"><?php echo $total_clients; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Freelancers</h3>
                        <p class="stat-number"><?php echo $total_freelancers; ?></p>
                    </div>
                    <div class="stat-card">
                        <h3>Admins</h3>
                        <p class="stat-number"><?php echo $total_admins; ?></p>
                    </div>
                </div>
            </section>

            <section class="users-section">
                <h2>All Users</h2>
                <div class="table-container">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="role-badge role-<?php echo $user['role']; ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <a href="admin_dashboard.php?delete_user=<?php echo $user['id']; ?>" 
                                               class="btn btn-danger btn-sm" 
                                               onclick="return confirm('Are you sure you want to delete this user?')">
                                                Delete
                                            </a>
                                        <?php else: ?>
                                            <span class="current-user">You</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
