<?php
/**
 * StudyLance Post Job Page - WITH EMAIL NOTIFICATIONS
 * Job posting form for clients
 */

require_once 'config.php';
require_once 'email_config.php';

// Require client role
requireRole('client');

// Handle job posting
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $budget = filter_var($_POST['budget'] ?? '', FILTER_SANITIZE_NUMBER_FLOAT);
    
    $errors = [];
    
    // Validation
    if (empty($title)) {
        $errors[] = "Job title is required";
    } elseif (strlen($title) < 5) {
        $errors[] = "Job title must be at least 5 characters";
    }
    
    if (empty($description)) {
        $errors[] = "Job description is required";
    } elseif (strlen($description) < 20) {
        $errors[] = "Job description must be at least 20 characters";
    }
    
    if (empty($budget) || $budget <= 0) {
        $errors[] = "Please enter a valid budget amount";
    } elseif ($budget < 10) {
        $errors[] = "Minimum budget is $10";
    } elseif ($budget > 10000) {
        $errors[] = "Maximum budget is $10,000";
    }
    
    if (empty($errors)) {
        try {
            $db = Database::getInstance();
            
            // Insert new job
            $stmt = $db->query(
                "INSERT INTO jobs (client_id, title, description, budget, status, created_at) VALUES (?, ?, ?, ?, 'open', ?)",
                [$_SESSION['user_id'], $title, $description, $budget, date('Y-m-d H:i:s')]
            );
            
            if ($stmt->rowCount() > 0) {
                // Get the job ID for email notification
                $jobId = $db->lastInsertId();
                
                // Send email notifications to all freelancers
                sendJobPostedNotification($jobId);
                
                redirect('client_dashboard_new.php', 'Job posted successfully! All freelancers have been notified.', 'success');
            } else {
                $errors[] = "Failed to post job. Please try again.";
            }
            
        } catch (Exception $e) {
            error_log("Job posting error: " . $e->getMessage());
            $errors[] = "Database error occurred: " . $e->getMessage();
        }
    }
}

// Get flash message
$flash = getFlashMessage();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Job - StudyLance</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Modern Post Job Page Styles */
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .auth-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            max-width: 1200px;
            width: 100%;
            min-height: 700px;
            background: rgba(255,255,255,0.03);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .auth-left {
            background: linear-gradient(135deg, rgba(106,92,255,0.15), rgba(168,85,247,0.15));
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .auth-left::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(106,92,255,0.1), transparent 70%);
            animation: float 20s ease-in-out infinite;
        }
        
        .auth-right {
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow-y: auto;
        }
        
        .auth-logo {
            font-size: 32px;
            font-weight: 800;
            background: linear-gradient(135deg,#6a5cff,#a855f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }
        
        .auth-title {
            font-size: 18px;
            color: rgba(255,255,255,0.7);
            margin-bottom: 40px;
            position: relative;
            z-index: 1;
        }
        
        .auth-form {
            position: relative;
            z-index: 1;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            color: rgba(255,255,255,0.8);
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-input,
        .form-textarea {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid rgba(255,255,255,0.1);
            border-radius: 15px;
            background: rgba(255,255,255,0.05);
            color: white;
            font-size: 16px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .form-textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .form-input:focus,
        .form-textarea:focus {
            outline: none;
            border-color: #6a5cff;
            background: rgba(255,255,255,0.08);
            box-shadow: 0 0 20px rgba(106,92,255,0.3);
            transform: translateY(-2px);
        }
        
        .form-input::placeholder,
        .form-textarea::placeholder {
            color: rgba(255,255,255,0.5);
        }
        
        .auth-button {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg,#6a5cff,#a855f7);
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(106,92,255,0.3);
        }
        
        .auth-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(106,92,255,0.4);
        }
        
        .auth-footer {
            margin-top: 30px;
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        .auth-footer p {
            color: rgba(255,255,255,0.7);
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .auth-footer a {
            color: #6a5cff;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .auth-footer a:hover {
            color: #a855f7;
        }
        
        .job-tips {
            background: rgba(255,255,255,0.05);
            border-radius: 15px;
            padding: 20px;
            margin-top: 30px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .tips-title {
            font-size: 14px;
            font-weight: 600;
            color: #6a5cff;
            margin-bottom: 15px;
        }
        
        .tip-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 8px;
            font-size: 13px;
            color: rgba(255,255,255,0.7);
        }
        
        .tip-item:last-child {
            margin-bottom: 0;
        }
        
        .tip-icon {
            color: #6a5cff;
            font-size: 12px;
            flex-shrink: 0;
            margin-top: 2px;
        }
        
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
        
        .error-messages {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.3);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 25px;
            backdrop-filter: blur(10px);
        }
        
        .error-item {
            color: #ff6b6b;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }
        
        .error-item:last-child {
            margin-bottom: 0;
        }
        
        .flash-message {
            padding: 18px 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255,255,255,0.2);
            font-size: 14px;
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
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .auth-wrapper {
                grid-template-columns: 1fr;
                max-width: 400px;
                margin: 20px;
                min-height: auto;
            }
            
            .auth-left {
                padding: 40px 30px;
                min-height: 200px;
            }
            
            .auth-right {
                padding: 40px 30px;
            }
            
            .auth-logo {
                font-size: 24px;
            }
            
            .auth-title {
                font-size: 16px;
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

    <div class="auth-container">
        <div class="auth-wrapper">
            <!-- Left Side - Branding -->
            <div class="auth-left">
                <div class="floating-shapes">
                    <div class="shape shape-1"></div>
                    <div class="shape shape-2"></div>
                    <div class="shape shape-3"></div>
                </div>
                
                <div class="auth-logo">StudyLance</div>
                <div class="auth-title">Post a new job and find talented freelancers</div>
                
                <div class="job-tips">
                    <div class="tips-title">💡 Job Posting Tips</div>
                    <div class="tip-item">
                        <span class="tip-icon">✓</span>
                        <span>Be specific about the skills required for your project</span>
                    </div>
                    <div class="tip-item">
                        <span class="tip-icon">✓</span>
                        <span>Set a competitive budget to attract quality freelancers</span>
                    </div>
                    <div class="tip-item">
                        <span class="tip-icon">✓</span>
                        <span>Provide clear project details and expected deliverables</span>
                    </div>
                    <div class="tip-item">
                        <span class="tip-icon">✓</span>
                        <span>Mention project timeline and deadlines if applicable</span>
                    </div>
                    <div class="tip-item">
                        <span class="tip-icon">✓</span>
                        <span>Include any specific tools or technologies needed</span>
                    </div>
                </div>
            </div>
            
            <!-- Right Side - Job Posting Form -->
            <div class="auth-right">
                <form method="POST" action="" class="auth-form">
                    <!-- Flash Messages -->
                    <?php if ($flash): ?>
                        <div class="flash-message flash-<?php echo $flash['type']; ?>">
                            <?php echo $flash['message']; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Error Messages -->
                    <?php if (!empty($errors)): ?>
                        <div class="error-messages">
                            <?php foreach ($errors as $error): ?>
                                <div class="error-item">
                                    <span>⚠️</span>
                                    <?php echo $error; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label class="form-label">Job Title</label>
                        <input type="text" 
                               name="title" 
                               value="<?php echo $_POST['title'] ?? ''; ?>"
                               placeholder="Enter a descriptive job title"
                               class="form-input"
                               required
                               autofocus>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Job Description</label>
                        <textarea name="description" 
                                  placeholder="Describe your project in detail..."
                                  class="form-textarea"
                                  required><?php echo $_POST['description'] ?? ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Budget ($)</label>
                        <input type="number" 
                               name="budget" 
                               value="<?php echo $_POST['budget'] ?? ''; ?>"
                               placeholder="Enter your budget amount"
                               class="form-input"
                               min="10"
                               max="10000"
                               step="0.01"
                               required>
                    </div>
                    
                    <button type="submit" class="auth-button">Post Job</button>
                </form>
                
                <div class="auth-footer">
                    <p><a href="client_dashboard_new.php">← Back to Dashboard</a></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
