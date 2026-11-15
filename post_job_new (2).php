<?php
/**
 * StudyLance Post Job Page - WITH RUPEES
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
        $errors[] = "Job title must be at least 5 characters long";
    }
    
    if (empty($description)) {
        $errors[] = "Job description is required";
    } elseif (strlen($description) < 20) {
        $errors[] = "Job description must be at least 20 characters long";
    }
    
    if (empty($budget)) {
        $errors[] = "Please enter a valid budget amount";
    } elseif ($budget <= 0) {
        $errors[] = "Budget must be a positive number";
    } elseif ($budget < 10) {
        $errors[] = "Minimum budget is ₹10";
    } elseif ($budget > 10000) {
        $errors[] = "Maximum budget is ₹10,000";
    }
    
    // If no errors, insert job
    if (empty($errors)) {
        try {
            $db = Database::getInstance();
            
            // Insert new job
            $stmt = $db->query(
                "INSERT INTO jobs (client_id, title, description, budget, status, created_at) VALUES (?, ?, ?, 'open', ?)",
                [$_SESSION['user_id'], $title, $description, $budget, date('Y-m-d H:i:s')]
            );
            
            if ($stmt->rowCount() > 0) {
                // Get job ID for email notification
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
        /* Modern Post Job Page Styles - RUPEES CURRENCY */
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
        
        .auth-logo {
            font-size: 48px;
            font-weight: 800;
            background: linear-gradient(135deg,#6a5cff,#a855f7);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }
        
        .auth-right {
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow-y: auto;
        }
        
        .form-container {
            width: 100%;
            max-width: 500px;
        }
        
        .form-title {
            font-size: 28px;
            font-weight: 700;
            color: white;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .form-subtitle {
            font-size: 16px;
            color: rgba(255,255,255,0.7);
            margin-bottom: 30px;
            text-align: center;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            color: rgba(255,255,255,0.9);
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-input,
        .form-textarea {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            background: rgba(255,255,255,0.05);
            color: white;
            font-size: 16px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .form-input:focus,
        .form-textarea:focus {
            outline: none;
            border-color: #6a5cff;
            background: rgba(255,255,255,0.08);
            box-shadow: 0 0 20px rgba(106,92,255,0.3);
        }
        
        .form-textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .form-input::placeholder,
        .form-textarea::placeholder {
            color: rgba(255,255,255,0.5);
        }
        
        .submit-btn {
            width: 100%;
            padding: 18px 30px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg,#6a5cff,#a855f7);
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
            box-shadow: 0 10px 30px rgba(106,92,255,0.3);
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 40px rgba(106,92,255,0.4);
        }
        
        .currency-hint {
            font-size: 12px;
            color: rgba(255,255,255,0.6);
            margin-top: 5px;
            text-align: center;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }
        
        .back-link a:hover {
            color: #6a5cff;
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
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .auth-wrapper {
                grid-template-columns: 1fr;
                max-width: 400px;
                min-height: 600px;
                margin: 20px;
            }
            
            .auth-left,
            .auth-right {
                padding: 40px 30px;
            }
            
            .auth-logo {
                font-size: 36px;
            }
            
            .form-title {
                font-size: 24px;
            }
            
            .form-subtitle {
                font-size: 14px;
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
        <div class="floating-shapes">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
        </div>
        
        <div class="auth-wrapper">
            <!-- Left Side - Branding -->
            <div class="auth-left">
                <div class="auth-logo">StudyLance</div>
            </div>

            <!-- Right Side - Form -->
            <div class="auth-right">
                <div class="form-container">
                    <h1 class="form-title">Post New Job</h1>
                    <p class="form-subtitle">Find talented freelancers for your project</p>
                    
                    <?php if ($flash): ?>
                        <div class="flash-message flash-<?php echo $flash['type']; ?>">
                            <?php echo $flash['message']; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errors)): ?>
                        <div class="error-messages">
                            <?php foreach ($errors as $error): ?>
                                <div class="error-item">
                                    <span>⚠️ <?php echo htmlspecialchars($error); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="form-group">
                            <label class="form-label" for="title">Job Title</label>
                            <input type="text" 
                                   name="title" 
                                   class="form-input"
                                   value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                                   placeholder="e.g., WordPress Website Development"
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="description">Job Description</label>
                            <textarea name="description" 
                                      class="form-textarea"
                                      placeholder="Describe your project requirements, skills needed, timeline, deliverables, etc."
                                      required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="budget">Budget (₹)</label>
                            <input type="number" 
                                   name="budget" 
                                   class="form-input"
                                   value="<?php echo htmlspecialchars($_POST['budget'] ?? ''); ?>"
                                   placeholder="e.g., 15000"
                                   min="10"
                                   max="10000"
                                   step="0.01"
                                   required>
                            <div class="currency-hint">Enter amount in Indian Rupees (₹)</div>
                        </div>
                        
                        <button type="submit" class="submit-btn">Post Job</button>
                    </form>
                    
                    <div class="back-link">
                        <a href="client_dashboard_new.php">← Back to Dashboard</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
