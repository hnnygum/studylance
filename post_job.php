<?php
session_start();

// Check if user is logged in and is client
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header("Location: login.php");
    exit();
}

include 'database.php';

$errors = [];
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $budget = trim($_POST['budget']);
    
    // Validation
    if (empty($title)) {
        $errors[] = "Job title is required";
    } elseif (strlen($title) < 5) {
        $errors[] = "Job title must be at least 5 characters long";
    }
    
    if (empty($description)) {
        $errors[] = "Job description is required";
    } elseif (strlen($description) < 20) {
        $errors[] = "Job description must be at least 20 characters long";
    }
    
    if (empty($budget)) {
        $errors[] = "Budget is required";
    } elseif (!is_numeric($budget) || $budget <= 0) {
        $errors[] = "Budget must be a positive number";
    } elseif ($budget < 10) {
        $errors[] = "Minimum budget is $10";
    }
    
    // If no errors, insert job
    if (empty($errors)) {
        $sql = "INSERT INTO jobs (title, description, budget, client_id) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdi", $title, $description, $budget, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $success = "Job posted successfully!";
            // Clear form
            $title = $description = $budget = '';
        } else {
            $errors[] = "Failed to post job. Please try again.";
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Job - StudyLance</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <div class="header-content">
                <h1>Post New Job</h1>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                    <a href="client_dashboard.php" class="btn btn-secondary">Dashboard</a>
                    <a href="logout.php" class="btn btn-logout">Logout</a>
                </div>
            </div>
        </header>

        <main class="dashboard-main">
            <?php if ($success): ?>
                <div class="success-message">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="error-messages">
                    <?php foreach ($errors as $error): ?>
                        <p class="error"><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="job-form">
                <h2>Create New Job Posting</h2>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="title">Job Title *</label>
                        <input type="text" id="title" name="title" 
                               value="<?php echo htmlspecialchars($title ?? ''); ?>" 
                               placeholder="e.g., WordPress Website Development" required>
                        <small>Minimum 5 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Job Description *</label>
                        <textarea id="description" name="description" rows="8" 
                                  placeholder="Provide detailed information about the job, requirements, skills needed, etc." required><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                        <small>Minimum 20 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="budget">Budget ($) *</label>
                        <input type="number" id="budget" name="budget" 
                               value="<?php echo htmlspecialchars($budget ?? ''); ?>" 
                               step="0.01" min="10" placeholder="e.g., 500.00" required>
                        <small>Minimum budget is $10</small>
                    </div>
                    
                    <div class="form-actions">
                        <a href="client_dashboard.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Post Job</button>
                    </div>
                </form>
            </div>

            <div class="guidelines">
                <h3>Posting Guidelines</h3>
                <ul>
                    <li>Be specific about your requirements</li>
                    <li>Mention all necessary skills and qualifications</li>
                    <li>Set a realistic budget for the scope of work</li>
                    <li>Include timeline if applicable</li>
                    <li>Avoid providing personal contact information</li>
                </ul>
            </div>
        </main>
    </div>
</body>
</html>
