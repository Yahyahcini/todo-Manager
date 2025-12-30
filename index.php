<?php
require 'config.php';

if (is_logged_in()) header('Location: todo.php');

$error = '';
$password_hints = [];
$password_strength = '';
$mode = $_GET['mode'] ?? 'login';

// REGISTER
if (isset($_POST['register'])) {
    if (!check_csrf($_POST['csrf'])) {
        $error = "Security error! Try again.";
    } elseif (!check_rate_limit('register_' . $_SERVER['REMOTE_ADDR'])) {
        $error = "Too many attempts! Wait 5 minutes.";
    } else {
        $username = clean($_POST['username']);
        $password = $_POST['password'];
        
        // Check username
        if (strlen($username) < 3 || strlen($username) > 20) {
            $error = "Username must be 3-20 characters";
        }
        // Check password strength - THIS IS THE FIX!
        else {
            $password_strength = check_password_strength($password);
            $password_hints = get_password_hints($password);
            
            // Only allow Medium or Strong passwords
            if ($password_strength == 'weak') {
                $error = "Password too weak! Please use a stronger password.";
            }
            // Check if user exists
            else {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                
                if ($stmt->rowCount() > 0) {
                    $error = "Username already taken!";
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
                    
                    if ($stmt->execute([$username, $hash])) {
                        $_SESSION['user_id'] = $pdo->lastInsertId();
                        $_SESSION['username'] = $username;
                        header('Location: todo.php');
                    } else {
                        $error = "Registration failed!";
                    }
                }
            }
        }
    }
    $mode = 'register';
}

// LOGIN (same as before)
if (isset($_POST['login'])) {
    if (!check_csrf($_POST['csrf'])) {
        $error = "Security error! Try again.";
    } elseif (!check_rate_limit('login_' . $_SERVER['REMOTE_ADDR'])) {
        $error = "Too many login attempts! Wait 5 minutes.";
    } else {
        $username = clean($_POST['username']);
        $password = $_POST['password'];
        
        $stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: todo.php');
        } else {
            $error = "Wrong username or password!";
            sleep(1);
        }
    }
    $mode = 'login';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Todo App - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <h1><i class="fas fa-shield-alt"></i> Secure Todo App</h1>
                <p>Password strength required: <strong>Medium or Strong</strong></p>
            </div>

            <!-- Tabs -->
            <div class="tabs">
                <a href="?mode=login" class="tab <?= $mode == 'login' ? 'active' : '' ?>">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
                <a href="?mode=register" class="tab <?= $mode == 'register' ? 'active' : '' ?>">
                    <i class="fas fa-user-plus"></i> Register
                </a>
            </div>

            <?php if ($error): ?>
                <div class="error">
                    <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                    
                    <?php if (!empty($password_hints) && $password_strength == 'weak'): ?>
                    <div class="hints">
                        <strong>Your password needs:</strong>
                        <ul>
                            <?php foreach ($password_hints as $hint): ?>
                                <li><i class="fas fa-times"></i> <?= $hint ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <?php if ($mode == 'login'): ?>
            <form method="POST" class="form">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" placeholder="Username" required 
                           value="<?= isset($_POST['username']) ? clean($_POST['username']) : '' ?>">
                </div>
                
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="Password" required id="loginPassword">
                    <button type="button" class="eye-toggle" onclick="togglePassword('loginPassword')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                
                <button type="submit" name="login" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
                
                <div class="security-note">
                    <i class="fas fa-shield-alt"></i> Rate limited: 5 attempts per 5 minutes
                </div>
                
                <p class="switch">No account? <a href="?mode=register">Register here</a></p>
            </form>
            <?php endif; ?>

            <!-- Register Form -->
            <?php if ($mode == 'register'): ?>
            <form method="POST" class="form" id="registerForm">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" placeholder="Username (3-20 chars)" required
                           value="<?= isset($_POST['username']) ? clean($_POST['username']) : '' ?>"
                           oninput="checkForm()">
                </div>
                
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="Create strong password" required 
                           id="registerPassword" oninput="checkPasswordStrength(this.value)">
                    <button type="button" class="eye-toggle" onclick="togglePassword('registerPassword')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                
                <!-- Password Strength Meter -->
                <div class="password-strength" id="passwordStrength">
                    <div class="strength-bar">
                        <div class="strength-fill"></div>
                    </div>
                    <div class="strength-text">
                        Strength: 
                        <span id="strengthLabel">None</span>
                        <span id="strengthIcon"></span>
                    </div>
                </div>
                
                <!-- Password Requirements -->
                <div class="password-requirements">
                    <strong><i class="fas fa-list-check"></i> Password must have:</strong>
                    <ul>
                        <li><span id="lengthCheck"><i class="fas fa-times"></i> At least 8 characters</span></li>
                        <li><span id="lowerCheck"><i class="fas fa-times"></i> One lowercase letter (a-z)</span></li>
                        <li><span id="upperCheck"><i class="fas fa-times"></i> One uppercase letter (A-Z)</span></li>
                        <li><span id="numberCheck"><i class="fas fa-times"></i> One number (0-9)</span></li>
                        <li><span id="specialCheck"><i class="fas fa-times"></i> One special character (!@#$% etc.)</span></li>
                    </ul>
                </div>
                
                <!-- In index.php, change the register button to this: -->
<button type="submit" name="register" class="btn btn-success" id="registerBtn">
    <i class="fas fa-user-plus"></i> Create Account
</button>
                
                <div class="security-levels">
                    <div class="level">
                        <span class="level-weak">Weak</span> - Less secure
                    </div>
                    <div class="level">
                        <span class="level-medium">Medium</span> - Acceptable
                    </div>
                    <div class="level">
                        <span class="level-strong">Strong</span> - Recommended
                    </div>
                </div>
                
                <p class="switch">Have account? <a href="?mode=login">Login here</a></p>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        let currentStrength = 'none';
        
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = input.parentNode.querySelector('.eye-toggle i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function checkPasswordStrength(password) {
            const strengthDiv = document.getElementById('passwordStrength');
            const strengthFill = strengthDiv.querySelector('.strength-fill');
            const strengthLabel = document.getElementById('strengthLabel');
            const strengthIcon = document.getElementById('strengthIcon');
            const registerBtn = document.getElementById('registerBtn');
            
            // Checks
            const hasLength = password.length >= 8;
            const hasLower = /[a-z]/.test(password);
            const hasUpper = /[A-Z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            const hasSpecial = /[^A-Za-z0-9]/.test(password);
            
            // Update checkmarks with colors
            updateCheck('lengthCheck', hasLength);
            updateCheck('lowerCheck', hasLower);
            updateCheck('upperCheck', hasUpper);
            updateCheck('numberCheck', hasNumber);
            updateCheck('specialCheck', hasSpecial);
            
            // Calculate score
            let score = 0;
            if (hasLength) score++;
            if (hasLower) score++;
            if (hasUpper) score++;
            if (hasNumber) score++;
            if (hasSpecial) score++;
            
            // Determine strength level
            let strength = 'none';
            let width = '0%';
            let color = '#9ca3af';
            let iconClass = 'fa-times-circle';
            let iconColor = '#9ca3af';
            
            if (password.length === 0) {
                strength = 'None';
                width = '0%';
                color = '#9ca3af';
                iconClass = 'fa-times-circle';
                iconColor = '#9ca3af';
            } else if (score <= 2) {
                strength = 'Weak';
                width = '33%';
                color = '#ef4444';
                iconClass = 'fa-exclamation-triangle';
                iconColor = '#ef4444';
            } else if (score <= 4) {
                strength = 'Medium';
                width = '66%';
                color = '#f59e0b';
                iconClass = 'fa-check-circle';
                iconColor = '#f59e0b';
            } else {
                strength = 'Strong';
                width = '100%';
                color = '#10b981';
                iconClass = 'fa-shield-alt';
                iconColor = '#10b981';
            }
            
            // Update UI
            strengthFill.style.width = width;
            strengthFill.style.backgroundColor = color;
            strengthLabel.textContent = strength;
            strengthLabel.style.color = color;
            strengthLabel.style.fontWeight = 'bold';
            
            // Update icon
            strengthIcon.innerHTML = `<i class="fas ${iconClass}" style="color: ${iconColor}; margin-left: 5px;"></i>`;
            
            // Store current strength
            currentStrength = strength;
            
            // Enable/disable register button
            checkForm();
        }
        
        function updateCheck(elementId, isValid) {
            const element = document.getElementById(elementId);
            const icon = element.querySelector('i');
            if (isValid) {
                icon.className = 'fas fa-check';
                icon.style.color = '#10b981';
                element.style.color = '#065f46';
            } else {
                icon.className = 'fas fa-times';
                icon.style.color = '#ef4444';
                element.style.color = '#7f1d1d';
            }
        }
        
        // In index.php, replace the checkForm() function with this:
function checkForm() {
    const registerBtn = document.getElementById('registerBtn');
    const username = document.querySelector('input[name="username"]');
    
    // Simple check: enable if username has at least 3 characters
    if (username && username.value.length >= 3) {
        registerBtn.disabled = false;
        registerBtn.title = "Click to register";
        registerBtn.style.opacity = "1";
    } else {
        registerBtn.disabled = true;
        registerBtn.title = "Username must be at least 3 characters";
        registerBtn.style.opacity = "0.7";
    }
}
        
        // Check form on page load
        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('registerPassword')) {
                checkPasswordStrength(document.getElementById('registerPassword').value);
            }
        });
    </script>
</body>
</html>