<?php
/**
 * StudyLance Admin Dashboard
 * Platform management and user administration
 */

require_once 'config.php';

// Require admin role
requireRole('admin');

// Handle user deletion
if (isset($_GET['delete_user'])) {
    $userId = (int)$_GET['delete_user'];
    
    if ($userId != $_SESSION['user_id']) {
        try {
            $db = Database::getInstance();
            $stmt = $db->query("DELETE FROM users WHERE id = ?", [$userId]);
            
            if ($stmt->rowCount() > 0) {
                redirect('admin_dashboard_new.php', 'User deleted successfully', 'success');
            } else {
                redirect('admin_dashboard_new.php', 'User not found', 'error');
            }
        } catch (Exception $e) {
            redirect('admin_dashboard_new.php', 'Error deleting user', 'error');
        }
    } else {
        redirect('admin_dashboard_new.php', 'Cannot delete your own account', 'error');
    }
}

// Get dashboard statistics
try {
    $db = Database::getInstance();
    
    // Total users
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $total_users = $stmt->fetch()['count'];
    
    // Total jobs
    $stmt = $db->query("SELECT COUNT(*) as count FROM jobs");
    $total_jobs = $stmt->fetch()['count'];
    
    // Total proposals
    $stmt = $db->query("SELECT COUNT(*) as count FROM proposals");
    $total_proposals = $stmt->fetch()['count'];
    
    // Recent users
    $stmt = $db->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 10");
    $users = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $total_users = $total_jobs = $total_proposals = 0;
    $users = [];
}

// Get flash message
$flash = getFlashMessage();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - StudyLance</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Modern Asymmetric Admin Dashboard Styles */
        .dashboard-container {
            min-height: 100vh;
            position: relative;
            overflow: hidden;
        }
        
        .dashboard-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 100vh;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Left Side - Navigation & User Info */
        .dashboard-left {
            background: linear-gradient(135deg, rgba(106,92,255,0.15), rgba(168,85,247,0.15));
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }
        
        .dashboard-left::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(106,92,255,0.1), transparent 70%);
            animation: float 20s ease-in-out infinite;
        }
        
        .dashboard-logo {
            font-size: 36px;
            font-weight: 800;
            background: linear-gradient(135deg,#6a5cff,#a855f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }
        
        .dashboard-title {
            font-size: 18px;
            color: rgba(255,255,255,0.7);
            margin-bottom: 40px;
            position: relative;
            z-index: 1;
        }
        
        .nav-section {
            position: relative;
            z-index: 1;
        }
        
        .nav-title {
            font-size: 24px;
            font-weight: 700;
            color: white;
            margin-bottom: 25px;
        }
        
        .nav-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 40px;
        }
        
        .nav-item {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: white;
        }
        
        .nav-item:hover,
        .nav-item.active {
            background: rgba(106,92,255,0.2);
            transform: translateY(-5px);
            border-color: rgba(106,92,255,0.3);
            box-shadow: 0 15px 30px rgba(106,92,255,0.2);
        }
        
        .nav-icon {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .nav-label {
            font-size: 14px;
            font-weight: 500;
        }
        
        .user-section {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 25px;
            position: relative;
            z-index: 1;
        }
        
        .user-name {
            font-size: 20px;
            font-weight: 700;
            color: white;
            margin-bottom: 5px;
        }
        
        .user-role {
            color: #6a5cff;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 15px;
        }
        
        .user-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-user {
            flex: 1;
            padding: 10px 15px;
            border: none;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
        }
        
        .btn-messages {
            background: linear-gradient(135deg,#6a5cff,#a855f7);
            color: white;
        }
        
        .btn-logout {
            background: linear-gradient(135deg,#e74c3c,#c0392b);
            color: white;
        }
        
        .btn-user:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        /* Right Side - Main Content */
        .dashboard-right {
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow-y: auto;
        }
        
        .content-header {
            margin-bottom: 40px;
        }
        
        .page-title {
            font-size: 42px;
            font-weight: 800;
            background: linear-gradient(135deg,#6a5cff,#a855f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }
        
        .page-subtitle {
            color: rgba(255,255,255,0.7);
            font-size: 18px;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 25px;
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(135deg,#6a5cff,#a855f7);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(106,92,255,0.2);
            border-color: rgba(106,92,255,0.3);
        }
        
        .stat-icon {
            font-size: 32px;
            margin-bottom: 15px;
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: 800;
            color: white;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: rgba(255,255,255,0.7);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* Content Sections */
        .content-section {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 30px;
            border: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 30px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .section-title {
            font-size: 24px;
            font-weight: 700;
            color: white;
        }
        
        /* Table Styles */
        .modern-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            overflow: hidden;
            border-radius: 15px;
        }
        
        .modern-table thead {
            background: linear-gradient(135deg, rgba(106,92,255,0.2), rgba(168,85,247,0.2));
        }
        
        .modern-table th {
            padding: 15px 20px;
            text-align: left;
            color: white;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .modern-table tbody tr {
            border-bottom: 1px solid rgba(255,255,255,0.05);
            transition: all 0.3s ease;
        }
        
        .modern-table tbody tr:hover {
            background: rgba(106,92,255,0.05);
        }
        
        .modern-table td {
            padding: 15px 20px;
            color: rgba(255,255,255,0.8);
            font-size: 14px;
        }
        
        .role-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .role-admin {
            background: linear-gradient(135deg,#6a5cff,#a855f7);
            color: white;
        }
        
        .role-client {
            background: linear-gradient(135deg,#3498db,#2980b9);
            color: white;
        }
        
        .role-freelancer {
            background: linear-gradient(135deg,#27ae60,#229954);
            color: white;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        .btn-action {
            padding: 8px 16px;
            border: none;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-delete {
            background: linear-gradient(135deg,#e74c3c,#c0392b);
            color: white;
        }
        
        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(231,76,60,0.3);
        }
        
        /* Flash Messages */
        .flash-message {
            padding: 18px 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255,255,255,0.2);
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .flash-success {
            background: rgba(39, 174, 96, 0.1);
            border-color: rgba(39, 174, 96, 0.3);
            color: #4ade80;
        }
        
        .flash-error {
            background: rgba(220, 53, 69, 0.1);
            border-color: rgba(220, 53, 69, 0.3);
            color: #ff6b6b;
        }
        
        /* Floating Shapes */
        .floating-shapes {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }
        
        .shape {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(106,92,255,0.2), rgba(168,85,247,0.2));
            animation: float 15s ease-in-out infinite;
        }
        
        .shape-1 {
            width: 100px;
            height: 100px;
            top: 20%;
            left: 10%;
            animation-delay: 0s;
        }
        
        .shape-2 {
            width: 60px;
            height: 60px;
            top: 60%;
            left: 70%;
            animation-delay: 3s;
        }
        
        .shape-3 {
            width: 80px;
            height: 80px;
            top: 80%;
            left: 30%;
            animation-delay: 6s;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
        
        /* Responsive Design */
        @media (max-width: 1024px) {
            .dashboard-wrapper {
                grid-template-columns: 1fr;
            }
            
            .dashboard-left {
                padding: 40px 30px;
                min-height: auto;
            }
            
            .dashboard-right {
                padding: 40px 30px;
            }
            
            .nav-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-wrapper {
                margin: 20px;
            }
            
            .dashboard-left,
            .dashboard-right {
                padding: 30px 20px;
            }
            
            .page-title {
                font-size: 32px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .modern-table {
                font-size: 12px;
            }
            
            .modern-table th,
            .modern-table td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Background Video -->
    <video autoplay muted loop playsinline class="bg-video">
        <source src="video.mp4" type="video/mp4">
    </video>
    
    <!-- Overlay -->
    <div class="overlay"></div>

    <div class="dashboard-container">
        <div class="dashboard-wrapper">
            <!-- Left Side - Navigation & User Info -->
            <div class="dashboard-left">
                <div class="floating-shapes">
                    <div class="shape shape-1"></div>
                    <div class="shape shape-2"></div>
                    <div class="shape shape-3"></div>
                </div>
                
                <div class="nav-section">
                    <div class="dashboard-logo">StudyLance</div>
                    <div class="dashboard-title">Admin Control Panel</div>
                    
                    <div class="nav-title">Quick Navigation</div>
                    <div class="nav-grid">
                        <a href="#overview" class="nav-item active">
                            <div class="nav-icon">📊</div>
                            <div class="nav-label">Overview</div>
                        </a>
                        <a href="#users" class="nav-item">
                            <div class="nav-icon">👥</div>
                            <div class="nav-label">Users</div>
                        </a>
                        <a href="#jobs" class="nav-item">
                            <div class="nav-icon">💼</div>
                            <div class="nav-label">Jobs</div>
                        </a>
                        <a href="#proposals" class="nav-item">
                            <div class="nav-icon">📄</div>
                            <div class="nav-label">Proposals</div>
                        </a>
                        <a href="messages_new.php" class="nav-item">
                            <div class="nav-icon">💬</div>
                            <div class="nav-label">Messages</div>
                        </a>
                        <a href="settings.php" class="nav-item">
                            <div class="nav-icon">⚙️</div>
                            <div class="nav-label">Settings</div>
                        </a>
                    </div>
                </div>
                
                <div class="user-section">
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['name']); ?></div>
                    <div class="user-role">Administrator</div>
                    <div class="user-actions">
                        <a href="messages_new.php" class="btn-user btn-messages">💬 Messages</a>
                        <a href="logout_new.php" class="btn-user btn-logout">Logout</a>
                    </div>
                </div>
            </div>

            <!-- Right Side - Main Content -->
            <div class="dashboard-right">
                <!-- Flash Messages -->
                <?php if ($flash): ?>
                    <div class="flash-message flash-<?php echo $flash['type']; ?>">
                        <span><?php echo $flash['type'] === 'success' ? '✅' : '❌'; ?></span>
                        <?php echo $flash['message']; ?>
                    </div>
                <?php endif; ?>

                <!-- Page Header -->
                <div class="content-header">
                    <h1 class="page-title">Admin Dashboard</h1>
                    <p class="page-subtitle">Manage your StudyLance platform</p>
                </div>

                <!-- Statistics Overview -->
                <section id="overview" class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">👥</div>
                        <div class="stat-value"><?php echo $total_users; ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">💼</div>
                        <div class="stat-value"><?php echo $total_jobs; ?></div>
                        <div class="stat-label">Total Jobs</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">📄</div>
                        <div class="stat-value"><?php echo $total_proposals; ?></div>
                        <div class="stat-label">Total Proposals</div>
                    </div>
                </section>

                <!-- Users Management -->
                <section id="users" class="content-section">
                    <div class="section-header">
                        <h2 class="section-title">User Management</h2>
                    </div>
                    <div class="table-container">
                        <table class="modern-table">
                            <thead>
                                <tr>
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
                                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <span class="role-badge role-<?php echo $user['role']; ?>">
                                                <?php echo ucfirst($user['role']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                    <button class="btn-action btn-delete" onclick="deleteUser(<?php echo $user['id']; ?>)">
                                                        🗑️ Delete
                                                    </button>
                                                <?php else: ?>
                                                    <span style="color: #6a5cff; font-style: italic;">You</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </div>

    <script>
        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                window.location.href = 'admin_dashboard_new.php?delete_user=' + userId;
            }
        }

        // Navigation active state
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', function(e) {
                document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
                this.classList.add('active');
            });
        });
    </script>
</body>
</html>
