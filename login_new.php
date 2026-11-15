<?php
/**
 * StudyLance Login Page
 * User authentication with secure session management
 */

require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $role = $_SESSION['role'];
    redirect($role . '_dashboard_new.php');
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    $errors = [];
    
    // Validation
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    if (empty($errors)) {
        try {
            $db = Database::getInstance();
            
            // Find user by email
            $stmt = $db->query("SELECT * FROM users WHERE email = ?", [$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Login successful - create session
                session_regenerate_id(true);
                
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['logged_in'] = true;
                $_SESSION['login_time'] = time();
                
                // Remember me functionality
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    $expiry = time() + (30 * 24 * 60 * 60); // 30 days
                    
                    $stmt = $db->query(
                        "UPDATE users SET remember_token = ?, remember_expiry = ? WHERE id = ?",
                        [$token, date('Y-m-d H:i:s', $expiry), $user['id']]
                    );
                    
                    setcookie('remember_token', $token, $expiry, '/', '', true, true);
                }
                
                // Redirect based on role
                $dashboard = $user['role'] . '_dashboard_new.php';
                redirect($dashboard, 'Login successful! Welcome back, ' . $user['name'] . '!', 'success');
                
            } else {
                $errors[] = "Invalid email or password";
            }
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
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
    <title>Login - StudyLance</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Modern Login Page Styles */
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
            min-height: 600px;
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
            margin-bottom: 25px;
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
        
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-right: 10px;
            accent-color: #6a5cff;
        }
        
        .checkbox-group label {
            color: rgba(255,255,255,0.8);
            font-size: 14px;
            cursor: pointer;
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
        
        .demo-section {
            background: rgba(255,255,255,0.05);
            border-radius: 15px;
            padding: 20px;
            margin-top: 30px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .demo-title {
            font-size: 14px;
            font-weight: 600;
            color: #6a5cff;
            margin-bottom: 15px;
        }
        
        .demo-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            font-size: 13px;
        }
        
        .demo-item:last-child {
            border-bottom: none;
        }
        
        .demo-role {
            color: rgba(255,255,255,0.8);
            font-weight: 500;
        }
        
        .demo-credentials {
            color: rgba(255,255,255,0.6);
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }
        
        .demo-copy {
            background: rgba(106,92,255,0.2);
            border: 1px solid rgba(106,92,255,0.3);
            color: #6a5cff;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .demo-copy:hover {
            background: rgba(106,92,255,0.3);
            color: white;
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
                <div class="auth-title">Welcome back to your freelancing journey</div>
                
                <div class="demo-section">
                    <div class="demo-title">🔐 Demo Accounts</div>
                    <div class="demo-item">
                        <span class="demo-role">Admin</span>
                        <span>
                            <span class="demo-credentials">admin@studylance.com</span>
                            <span class="demo-copy" onclick="copyToClipboard('admin@studylance.com')">📋</span>
                        </span>
                    </div>
                    <div class="demo-item">
                        <span class="demo-role">Client</span>
                        <span>
                            <span class="demo-credentials">john@example.com</span>
                            <span class="demo-copy" onclick="copyToClipboard('john@example.com')">📋</span>
                        </span>
                    </div>
                    <div class="demo-item">
                        <span class="demo-role">Freelancer</span>
                        <span>
                            <span class="demo-credentials">jane@example.com</span>
                            <span class="demo-copy" onclick="copyToClipboard('jane@example.com')">📋</span>
                        </span>
                    </div>
                    <div class="demo-item">
                        <span class="demo-role">Password</span>
                        <span>
                            <span class="demo-credentials">password123</span>
                            <span class="demo-copy" onclick="copyToClipboard('password123')">📋</span>
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Right Side - Login Form -->
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
                        <label class="form-label">Email Address</label>
                        <input type="email" 
                               name="email" 
                               value="<?php echo $_POST['email'] ?? ''; ?>"
                               placeholder="Enter your email"
                               class="form-input"
                               required
                               autofocus>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" 
                               name="password" 
                               id="password"
                               placeholder="Enter your password"
                               class="form-input"
                               required>
                        <button type="button" class="password-toggle" onclick="togglePassword('password')">
                            <span id="password-icon">👁️</span>
                        </button>
                    </div>
                    
                    <div class="checkbox-group">
                        <input type="checkbox" name="remember" id="remember" <?php echo isset($_POST['remember']) ? 'checked' : ''; ?>>
                        <label for="remember">Remember me for 30 days</label>
                    </div>
                    
                    <button type="submit" class="auth-button">Sign In to StudyLance</button>
                </form>
                
                <div class="auth-footer">
                    <p>Don't have an account? <a href="register_new.php">Create Account</a></p>
                    <p><a href="forgot_password.php">Forgot your password?</a></p>
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
        
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                // Show a temporary success message
                const originalText = event.target.textContent;
                event.target.textContent = '✓';
                event.target.style.background = 'rgba(39, 174, 96, 0.3)';
                event.target.style.color = '#4ade80';
                
                setTimeout(function() {
                    event.target.textContent = originalText;
                    event.target.style.background = 'rgba(106,92,255,0.2)';
                    event.target.style.color = '#6a5cff';
                }, 1500);
            }).catch(function(err) {
                console.error('Failed to copy: ', err);
            });
        }
    </script>
</body>
</html>
