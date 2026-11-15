<?php
session_start();

// Check if user is logged in and is client
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: login.php");
    exit();
}

include 'database.php';

// Handle job posting
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['post_job'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $budget = trim($_POST['budget']);
    
    $errors = [];
    
    if (empty($title)) {
        $errors[] = "Job title is required";
    }
    
    if (empty($description)) {
        $errors[] = "Job description is required";
    }
    
    if (empty($budget) || !is_numeric($budget) || $budget <= 0) {
        $errors[] = "Valid budget is required";
    }
    
    if (empty($errors)) {
        $sql = "INSERT INTO jobs (title, description, budget, client_id) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdi", $title, $description, $budget, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Job posted successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to post job";
        }
        $stmt->close();
        
        header("Location: client_dashboard.php");
        exit();
    }
}

// Get client's jobs
$sql = "SELECT * FROM jobs WHERE client_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

$jobs = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $jobs[] = $row;
    }
}
$stmt->close();

// Get proposals for client's jobs
$proposals = [];
if (!empty($jobs)) {
    $job_ids = array_column($jobs, 'id');
    $job_ids_str = implode(',', array_fill(0, count($job_ids), '?'));
    
    $sql = "SELECT p.*, j.title as job_title, u.name as freelancer_name 
            FROM proposals p 
            JOIN jobs j ON p.job_id = j.id 
            JOIN users u ON p.freelancer_id = u.id 
            WHERE p.job_id IN ($job_ids_str) 
            ORDER BY p.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(str_repeat('i', count($job_ids)), ...$job_ids);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $proposals[] = $row;
        }
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard - StudyLance</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <div class="header-content">
                <h1>Client Dashboard</h1>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                    <a href="logout.php" class="btn btn-logout">Logout</a>
                </div>
            </div>
        </header>

        <nav class="dashboard-nav">
            <ul>
                <li><a href="client_dashboard.php" class="active">Dashboard</a></li>
                <li><a href="#post-job">Post Job</a></li>
                <li><a href="#my-jobs">My Jobs</a></li>
                <li><a href="#proposals">Proposals</a></li>
                <li><a href="#messages">Messages</a></li>
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

            <?php if (!empty($errors)): ?>
                <div class="error-messages">
                    <?php foreach ($errors as $error): ?>
                        <p class="error"><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <section id="post-job" class="post-job-section">
                <h2>Post New Job</h2>
                <form method="POST" action="" class="job-form">
                    <input type="hidden" name="post_job" value="1">
                    
                    <div class="form-group">
                        <label for="title">Job Title:</label>
                        <input type="text" id="title" name="title" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Job Description:</label>
                        <textarea id="description" name="description" rows="5" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="budget">Budget ($):</label>
                        <input type="number" id="budget" name="budget" step="0.01" min="1" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Post Job</button>
                </form>
            </section>

            <section id="my-jobs" class="jobs-section">
                <h2>My Posted Jobs</h2>
                <?php if (empty($jobs)): ?>
                    <p class="no-data">You haven't posted any jobs yet.</p>
                <?php else: ?>
                    <div class="jobs-grid">
                        <?php foreach ($jobs as $job): ?>
                            <div class="job-card">
                                <h3><?php echo htmlspecialchars($job['title']); ?></h3>
                                <p class="job-description"><?php echo nl2br(htmlspecialchars(substr($job['description'], 0, 200))); ?>...</p>
                                <div class="job-meta">
                                    <span class="budget">$<?php echo number_format($job['budget'], 2); ?></span>
                                    <span class="status status-<?php echo $job['status']; ?>"><?php echo ucfirst($job['status']); ?></span>
                                </div>
                                <div class="job-date">
                                    Posted: <?php echo date('M j, Y', strtotime($job['created_at'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section id="proposals" class="proposals-section">
                <h2>Recent Proposals</h2>
                <?php if (empty($proposals)): ?>
                    <p class="no-data">No proposals received yet.</p>
                <?php else: ?>
                    <div class="proposals-list">
                        <?php foreach ($proposals as $proposal): ?>
                            <div class="proposal-card">
                                <h4><?php echo htmlspecialchars($proposal['job_title']); ?></h4>
                                <p class="freelancer-name">By: <?php echo htmlspecialchars($proposal['freelancer_name']); ?></p>
                                <p class="proposal-text"><?php echo nl2br(htmlspecialchars(substr($proposal['proposal_text'], 0, 150))); ?>...</p>
                                <div class="proposal-meta">
                                    <span class="bid-amount">Bid: $<?php echo number_format($proposal['bid_amount'], 2); ?></span>
                                    <span class="status status-<?php echo $proposal['status']; ?>"><?php echo ucfirst($proposal['status']); ?></span>
                                </div>
                                <div class="proposal-date">
                                    <?php echo date('M j, Y', strtotime($proposal['created_at'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>
