<?php
/**
 * StudyLance Registration Page
 * User registration with validation and secure password hashing
 */

require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $role = $_SESSION['role'];
    redirect($role . '_dashboard_new.php');
}

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = sanitize($_POST['role'] ?? '');
    
    $errors = [];
    
    // Validation
    if (empty($name)) {
        $errors[] = "Full name is required";
    } elseif (strlen($name) < 3) {
        $errors[] = "Name must be at least 3 characters";
    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $name)) {
        $errors[] = "Name can only contain letters and spaces";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])/', $password)) {
        $errors[] = "Password must contain uppercase, lowercase, number, and special character";
    }
    
    if (empty($confirm_password)) {
        $errors[] = "Password confirmation is required";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (empty($role)) {
        $errors[] = "Please select a role";
    } elseif (!in_array($role, ['client', 'freelancer'])) {
        $errors[] = "Invalid role selected";
    }
    
    if (empty($errors)) {
        try {
            $db = Database::getInstance();
            
            // Check if email already exists
            $stmt = $db->query("SELECT id FROM users WHERE email = ?", [$email]);
            if ($stmt->rowCount() > 0) {
                $errors[] = "Email already exists";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user
                $stmt = $db->query(
                    "INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, ?, ?)",
                    [$name, $email, $hashed_password, $role, date('Y-m-d H:i:s')]
                );
                
                if ($stmt->rowCount() > 0) {
                    redirect('login_new.php', 'Registration successful! Please login to continue.', 'success');
                } else {
                    $errors[] = "Registration failed. Please try again.";
                }
            }
            
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            $errors[] = "Database error occurred";
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
    <title>Register - StudyLance</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Modern Register Page Styles */
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
            background: linear-gradient(135deg, rgba(106,92,255,0.1), rgba(168,85,247,0.1));
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
            position: relative;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            color: rgba(255,255,255,0.8);
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-input {
            width: 100%;
            padding: 15px 20px;
            padding-right: 50px;
            border: 2px solid rgba(255,255,255,0.1);
            border-radius: 15px;
            background: rgba(255,255,255,0.05);
            color: white;
            font-size: 16px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .form-input:focus {
            outline: none;
            border-color: #6a5cff;
            background: rgba(255,255,255,0.08);
            box-shadow: 0 0 20px rgba(106,92,255,0.3);
            transform: translateY(-2px);
        }
        
        .form-input::placeholder {
            color: rgba(255,255,255,0.5);
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: rgba(255,255,255,0.6);
            cursor: pointer;
            font-size: 18px;
            transition: color 0.3s ease;
            z-index: 2;
        }
        
        .password-toggle:hover {
            color: #6a5cff;
        }
        
        .radio-group {
            display: flex;
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .radio-option {
            flex: 1;
            position: relative;
        }
        
        .radio-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }
        
        .radio-option label {
            display: block;
            padding: 15px 20px;
            border: 2px solid rgba(255,255,255,0.1);
            border-radius: 15px;
            background: rgba(255,255,255,0.05);
            color: rgba(255,255,255,0.8);
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 500;
        }
        
        .radio-option input[type="radio"]:checked + label {
            border-color: #6a5cff;
            background: rgba(106,92,255,0.1);
            color: white;
            box-shadow: 0 0 20px rgba(106,92,255,0.3);
        }
        
        .radio-option label:hover {
            border-color: rgba(106,92,255,0.5);
            transform: translateY(-2px);
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
        
        .password-requirements {
            background: rgba(255,255,255,0.05);
            border-radius: 15px;
            padding: 20px;
            margin-top: 30px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .requirements-title {
            font-size: 14px;
            font-weight: 600;
            color: #6a5cff;
            margin-bottom: 15px;
        }
        
        .requirement-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
            font-size: 13px;
            color: rgba(255,255,255,0.7);
        }
        
        .requirement-item:last-child {
            margin-bottom: 0;
        }
        
        .requirement-icon {
            color: #6a5cff;
            font-size: 12px;
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
            
            .radio-group {
                flex-direction: column;
                gap: 10px;
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
                <div class="auth-title">Start your freelancing journey today</div>
                
                <div class="password-requirements">
                    <div class="requirements-title">🔒 Password Requirements</div>
                    <div class="requirement-item">
                        <span class="requirement-icon">✓</span>
                        At least 8 characters long
                    </div>
                    <div class="requirement-item">
                        <span class="requirement-icon">✓</span>
                        One uppercase letter (A-Z)
                    </div>
                    <div class="requirement-item">
                        <span class="requirement-icon">✓</span>
                        One lowercase letter (a-z)
                    </div>
                    <div class="requirement-item">
                        <span class="requirement-icon">✓</span>
                        One number (0-9)
                    </div>
                    <div class="requirement-item">
                        <span class="requirement-icon">✓</span>
                        One special character (@$!%*?&)
                    </div>
                </div>
            </div>
            
            <!-- Right Side - Registration Form -->
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
                        <label class="form-label">Full Name</label>
                        <input type="text" 
                               name="name" 
                               value="<?php echo $_POST['name'] ?? ''; ?>"
                               placeholder="Enter your full name"
                               class="form-input"
                               required
                               autofocus>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" 
                               name="email" 
                               value="<?php echo $_POST['email'] ?? ''; ?>"
                               placeholder="Enter your email"
                               class="form-input"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" 
                               name="password" 
                               id="password"
                               placeholder="Create a strong password"
                               class="form-input"
                               required>
                        <button type="button" class="password-toggle" onclick="togglePassword('password')">
                            <span id="password-icon">👁️</span>
                        </button>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" 
                               name="confirm_password" 
                               id="confirm_password"
                               placeholder="Confirm your password"
                               class="form-input"
                               required>
                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                            <span id="confirm_password-icon">👁️</span>
                        </button>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Choose Your Role</label>
                        <div class="radio-group">
                            <div class="radio-option">
                                <input type="radio" name="role" value="client" id="role-client" <?php echo ($_POST['role'] ?? '') === 'client' ? 'checked' : ''; ?> required>
                                <label for="role-client">
                                    🏢 Client<br>
                                    <small>Post jobs & hire talent</small>
                                </label>
                            </div>
                            <div class="radio-option">
                                <input type="radio" name="role" value="freelancer" id="role-freelancer" <?php echo ($_POST['role'] ?? '') === 'freelancer' ? 'checked' : ''; ?> required>
                                <label for="role-freelancer">
                                    💼 Freelancer<br>
                                    <small>Find work & earn money</small>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="auth-button">Create Your Account</button>
                </form>
                
                <div class="auth-footer">
                    <p>Already have an account? <a href="login_new.php">Sign In</a></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(inputId) {
            const passwordInput = document.getElementById(inputId);
            const passwordIcon = document.getElementById(inputId + '-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                passwordIcon.textContent = '👁️';
            }
        }
    </script>
</body>
</html>
