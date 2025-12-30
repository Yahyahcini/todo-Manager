<?php
session_start();

// Database
$pdo = new PDO('mysql:host=localhost;dbname=todo_db;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Security functions
function csrf_token() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function check_csrf($token) {
    return hash_equals($_SESSION['csrf'] ?? '', $token);
}

function clean($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Rate limiting function
function check_rate_limit($key) {
    $limit = 5; // 5 attempts
    $window = 300; // 5 minutes in seconds
    
    if (!isset($_SESSION['rate_limit'])) {
        $_SESSION['rate_limit'] = [];
    }
    
    $current_time = time();
    $attempts = $_SESSION['rate_limit'][$key] ?? [];
    
    // Remove old attempts
    $attempts = array_filter($attempts, function($time) use ($current_time, $window) {
        return $time > $current_time - $window;
    });
    
    if (count($attempts) >= $limit) {
        return false; // Too many attempts
    }
    
    $attempts[] = $current_time;
    $_SESSION['rate_limit'][$key] = $attempts;
    
    return true;
}

// Password strength checker
function check_password_strength($password) {
    $score = 0;
    
    // Length check
    if (strlen($password) >= 8) $score++;
    
    // Lowercase check
    if (preg_match('/[a-z]/', $password)) $score++;
    
    // Uppercase check
    if (preg_match('/[A-Z]/', $password)) $score++;
    
    // Number check
    if (preg_match('/[0-9]/', $password)) $score++;
    
    // Special character check
    if (preg_match('/[^A-Za-z0-9]/', $password)) $score++;
    
    // Determine strength
    if ($score <= 2) {
        return 'weak';
    } elseif ($score <= 4) {
        return 'medium';
    } else {
        return 'strong';
    }
}

// Get password hints
function get_password_hints($password) {
    $hints = [];
    
    if (strlen($password) < 8) {
        $hints[] = 'At least 8 characters';
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $hints[] = 'One lowercase letter (a-z)';
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $hints[] = 'One uppercase letter (A-Z)';
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $hints[] = 'One number (0-9)';
    }
    
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $hints[] = 'One special character (!@#$% etc.)';
    }
    
    return $hints;
}
?>