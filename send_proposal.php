<?php
session_start();

// Check if user is logged in and is freelancer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'freelancer') {
    header("Location: login.php");
    exit();
}

include 'database.php';

$errors = [];
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $job_id = trim($_POST['job_id']);
    $proposal_text = trim($_POST['proposal_text']);
    $bid_amount = trim($_POST['bid_amount']);
    $estimated_time = trim($_POST['estimated_time']);
    
    // Validation
    if (empty($job_id) || !is_numeric($job_id)) {
        $errors[] = "Invalid job selected";
    }
    
    if (empty($proposal_text)) {
        $errors[] = "Proposal text is required";
    } elseif (strlen($proposal_text) < 50) {
        $errors[] = "Proposal must be at least 50 characters long";
    }
    
    if (empty($bid_amount)) {
        $errors[] = "Bid amount is required";
    } elseif (!is_numeric($bid_amount) || $bid_amount <= 0) {
        $errors[] = "Bid amount must be a positive number";
    }
    
    // Check if job exists and is open
    if (empty($errors)) {
        $sql = "SELECT id, title, budget, status FROM jobs WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $job_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            $errors[] = "Job not found";
        } else {
            $job = $result->fetch_assoc();
            if ($job['status'] !== 'open') {
                $errors[] = "This job is no longer accepting proposals";
            }
        }
        $stmt->close();
    }
    
    // Check if already submitted proposal for this job
    if (empty($errors)) {
        $sql = "SELECT id FROM proposals WHERE job_id = ? AND freelancer_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $job_id, $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = "You have already submitted a proposal for this job";
        }
        $stmt->close();
    }
    
    // Insert proposal if no errors
    if (empty($errors)) {
        $sql = "INSERT INTO proposals (job_id, freelancer_id, proposal_text, bid_amount, estimated_time) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisds", $job_id, $_SESSION['user_id'], $proposal_text, $bid_amount, $estimated_time);
        
        if ($stmt->execute()) {
            $success = "Proposal sent successfully! The client will be notified.";
            
            // Redirect to freelancer dashboard after 3 seconds
            header("refresh:3;url=freelancer_dashboard.php");
        } else {
            $errors[] = "Failed to send proposal. Please try again.";
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
    <title>Send Proposal - StudyLance</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <div class="header-content">
                <h1>Send Proposal</h1>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                    <a href="freelancer_dashboard.php" class="btn btn-secondary">Dashboard</a>
                    <a href="logout.php" class="btn btn-logout">Logout</a>
                </div>
            </div>
        </header>

        <main class="dashboard-main">
            <?php if ($success): ?>
                <div class="success-message">
                    <?php echo htmlspecialchars($success); ?>
                </div>
                <div class="redirect-notice">
                    <p>You will be redirected to your dashboard in 3 seconds...</p>
                    <a href="freelancer_dashboard.php" class="btn btn-primary">Go to Dashboard Now</a>
                </div>
            <?php else: ?>
                <?php if (!empty($errors)): ?>
                    <div class="error-messages">
                        <?php foreach ($errors as $error): ?>
                            <p class="error"><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="proposal-form">
                    <h2>Submit Your Proposal</h2>
                    
                    <?php if (isset($job)): ?>
                        <div class="job-preview">
                            <h3>Job: <?php echo htmlspecialchars($job['title']); ?></h3>
                            <p><strong>Budget:</strong> $<?php echo number_format($job['budget'], 2); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="job_id" value="<?php echo htmlspecialchars($job_id ?? ''); ?>">
                        
                        <div class="form-group">
                            <label for="proposal_text">Your Proposal *</label>
                            <textarea id="proposal_text" name="proposal_text" rows="8" required
                                      placeholder="Explain why you're the best fit for this job, your relevant experience, and how you plan to complete the work..."><?php echo htmlspecialchars($proposal_text ?? ''); ?></textarea>
                            <small>Minimum 50 characters. Be specific about your skills and experience.</small>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="bid_amount">Your Bid Amount ($) *</label>
                                <input type="number" id="bid_amount" name="bid_amount" 
                                       value="<?php echo htmlspecialchars($bid_amount ?? ''); ?>"
                                       step="0.01" min="1" required
                                       placeholder="Enter your bid amount">
                                <small>Amount you'll charge for this job</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="estimated_time">Estimated Completion Time</label>
                                <select id="estimated_time" name="estimated_time">
                                    <option value="">Select timeframe</option>
                                    <option value="1-3 days" <?php echo ($estimated_time ?? '') == '1-3 days' ? 'selected' : ''; ?>>1-3 days</option>
                                    <option value="3-7 days" <?php echo ($estimated_time ?? '') == '3-7 days' ? 'selected' : ''; ?>>3-7 days</option>
                                    <option value="1-2 weeks" <?php echo ($estimated_time ?? '') == '1-2 weeks' ? 'selected' : ''; ?>>1-2 weeks</option>
                                    <option value="2-4 weeks" <?php echo ($estimated_time ?? '') == '2-4 weeks' ? 'selected' : ''; ?>>2-4 weeks</option>
                                    <option value="1-2 months" <?php echo ($estimated_time ?? '') == '1-2 months' ? 'selected' : ''; ?>>1-2 months</option>
                                    <option value="2+ months" <?php echo ($estimated_time ?? '') == '2+ months' ? 'selected' : ''; ?>>2+ months</option>
                                </select>
                                <small>Optional but recommended</small>
                            </div>
                        </div>
                        
                        <div class="proposal-tips">
                            <h4>Proposal Tips:</h4>
                            <ul>
                                <li>Address the client's specific needs</li>
                                <li>Highlight relevant experience and skills</li>
                                <li>Explain your approach to the project</li>
                                <li>Be competitive but realistic with your bid</li>
                                <li>Proofread before submitting</li>
                            </ul>
                        </div>
                        
                        <div class="form-actions">
                            <a href="get_jobs.php" class="btn btn-secondary">Back to Jobs</a>
                            <button type="submit" class="btn btn-primary">Send Proposal</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <style>
        .proposal-form {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            max-width: 800px;
            margin: 0 auto;
        }

        .job-preview {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            border-left: 4px solid #3498db;
        }

        .job-preview h3 {
            margin: 0 0 0.5rem 0;
            color: #2c3e50;
        }

        .job-preview p {
            margin: 0;
            color: #666;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .proposal-tips {
            background: #e8f4fd;
            padding: 1.5rem;
            border-radius: 8px;
            margin: 2rem 0;
        }

        .proposal-tips h4 {
            margin: 0 0 1rem 0;
            color: #2c3e50;
        }

        .proposal-tips ul {
            margin: 0;
            padding-left: 1.5rem;
        }

        .proposal-tips li {
            margin-bottom: 0.5rem;
            color: #555;
        }

        .redirect-notice {
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .redirect-notice p {
            margin-bottom: 1rem;
            color: #666;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .proposal-form {
                padding: 1.5rem;
            }
        }
    </style>
</body>
</html>
