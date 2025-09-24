<?php
session_start();

require_once 'backend/config/database.php';
require_once 'backend/utils/helpers.php';

// Test database connection
try {
    $testDb = new Database();
    $testConn = $testDb->getConnection();
    if ($testConn) {
        error_log("Database connection successful");
    } else {
        error_log("Database connection failed");
    }
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
}

// Check if user is already logged in
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    // Only redirect if we're not already on the login page to prevent loops
    $current_page = basename($_SERVER['PHP_SELF']);
    if ($current_page !== 'login.php') {
        header("Location: home.php");
        exit();
    }
}

// Handle login form submission
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Accept both email and username for login
    $login_identifier = isset($_POST['username']) ? sanitizeInput($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $remember_me = isset($_POST['rememberMe']);
    
    error_log("Login attempt - Identifier: " . $login_identifier);
    
    if (empty($login_identifier) || empty($password)) {
        $error_message = 'Email and password are required.';
        error_log("Login failed - Empty identifier or password");
    } else {
        try {
            $database = new Database();
            $conn = $database->getConnection();
            
            if (!$conn) {
                throw new Exception("Database connection failed");
            }
            
            // Debug: Log the identifier being searched for
            error_log("Attempting to find user with identifier: " . $login_identifier);
            
            // First, check if the users table exists
            $tableCheck = $conn->query("SHOW TABLES LIKE 'users'");
            if ($tableCheck->rowCount() == 0) {
                throw new Exception("Users table does not exist in the database.");
            }
            
            // Get column names for debugging
            $columnCheck = $conn->query("SHOW COLUMNS FROM users");
            $columns = [];
            while($col = $columnCheck->fetch(PDO::FETCH_ASSOC)) {
                $columns[] = $col['Field'];
            }
            error_log("Available columns in users table: " . implode(', ', $columns));
            
            // Check if we have username column
            $hasUsernameColumn = in_array('username', $columns);
            
            // Build query based on available columns
            if ($hasUsernameColumn) {
                $query = "SELECT id, first_name, last_name, email, password, role, 
                                 IFNULL(department, '') as department, 
                                 IFNULL(phone, '') as phone, 
                                 IFNULL(location, '') as location,
                                 IFNULL(timezone, 'UTC') as timezone,
                                 IFNULL(language, 'en') as language,
                                 IFNULL(email_notifications, 1) as email_notifications,
                                 IFNULL(push_notifications, 0) as push_notifications,
                                 IFNULL(sms_notifications, 0) as sms_notifications,
                                 IFNULL(avatar, '') as avatar
                          FROM users 
                          WHERE email = :identifier OR username = :identifier
                          LIMIT 1";
            } else {
                $query = "SELECT id, first_name, last_name, email, password, role, 
                                 IFNULL(department, '') as department, 
                                 IFNULL(phone, '') as phone, 
                                 IFNULL(location, '') as location,
                                 IFNULL(timezone, 'UTC') as timezone,
                                 IFNULL(language, 'en') as language,
                                 IFNULL(email_notifications, 1) as email_notifications,
                                 IFNULL(push_notifications, 0) as push_notifications,
                                 IFNULL(sms_notifications, 0) as sms_notifications,
                                 IFNULL(avatar, '') as avatar
                          FROM users 
                          WHERE email = :identifier
                          LIMIT 1";
            }
            
            error_log("Executing query: " . $query . " with identifier: " . $login_identifier);
            $stmt = $conn->prepare($query);
            
            if (!$stmt) {
                throw new Exception("Failed to prepare statement");
            }
            
            $stmt->bindParam(':identifier', $login_identifier, PDO::PARAM_STR);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute query");
            }
            
            error_log("Query executed successfully");
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                error_log("User found - ID: " . $user['id'] . ", Email: " . $user['email']);
            
                // Verify password exists
                if (empty($user['password'])) {
                    error_log("Error: No password hash found for user ID: " . $user['id']);
                    $error_message = 'User account is not properly configured. Please contact support.';
                } else {
                    error_log("Stored password hash length: " . strlen($user['password']));
                    
                    // Check if password needs rehashing
                    if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                        error_log("Password needs rehashing");
                    }
                    
                    if (password_verify($password, $user['password'])) {
                        error_log("Password verification successful for user ID: " . $user['id']);
                        
                        // If password needs rehashing, update it
                        if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                            $newHash = password_hash($password, PASSWORD_DEFAULT);
                            $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                            $updateStmt->execute([$newHash, $user['id']]);
                            error_log("Password rehashed for user ID: " . $user['id']);
                        }
                        
                        // Update last login (only if column exists)
                        try {
                            $hasLastLoginColumn = in_array('last_login', $columns);
                            if ($hasLastLoginColumn) {
                                $updateQuery = "UPDATE users SET last_login = NOW() WHERE id = :id";
                                $updateStmt = $conn->prepare($updateQuery);
                                $updateStmt->bindParam(':id', $user['id'], PDO::PARAM_INT);
                                $updateStmt->execute();
                                error_log("Last login updated for user ID: " . $user['id']);
                            }
                        } catch (PDOException $e) {
                            error_log("Warning: Could not update last login time: " . $e->getMessage());
                            // Continue with login even if last login update fails
                        }
                        
                        // Set session variables
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = trim($user['first_name'] . ' ' . $user['last_name']);
                        $_SESSION['user_email'] = $user['email'];
                        $_SESSION['user_role'] = $user['role'];
                        
                        // Handle avatar
                        if (!empty($user['avatar'])) {
                            $_SESSION['user_avatar'] = $user['avatar'];
                        } else {
                            // Create initials from name
                            $firstInitial = !empty($user['first_name']) ? strtoupper(substr($user['first_name'], 0, 1)) : '';
                            $lastInitial = !empty($user['last_name']) ? strtoupper(substr($user['last_name'], 0, 1)) : '';
                            $_SESSION['user_avatar'] = $firstInitial . $lastInitial;
                        }
                        
                        // Debug session data
                        error_log("Session data after login: " . print_r($_SESSION, true));
                        
                        // Set remember me cookie if checked
                        if ($remember_me) {
                            if (function_exists('generateToken')) {
                                $token = generateToken($user['id']);
                                setcookie('remember_token', $token, time() + (86400 * 30), '/', '', false, true); // 30 days, httponly
                                error_log("Remember me cookie set for user ID: " . $user['id']);
                            } else {
                                error_log("Warning: generateToken function not found");
                            }
                        }
                        
                        // Set a success message
                        $_SESSION['login_success'] = true;
                        
                        // Clear any previous output buffers
                        while (ob_get_level()) {
                            ob_end_clean();
                        }
                        
                        // Redirect to home page or previously requested URL
                        $redirect_url = 'home.php';
                        if (isset($_SESSION['redirect_url'])) {
                            $redirect_url = $_SESSION['redirect_url'];
                            unset($_SESSION['redirect_url']);
                        }
                        
                        // Ensure we don't redirect back to login page
                        if (strpos($redirect_url, 'login.php') !== false) {
                            $redirect_url = 'home.php';
                        }
                        
                        header("Location: " . $redirect_url);
                        exit();
                    } else {
                        $error_message = 'Invalid email or password.';
                        error_log("Password verification failed for identifier: " . $login_identifier);
                    }
                }
            } else {
                $error_message = 'Invalid email or password.';
                error_log("Login failed - No user found with identifier: " . $login_identifier);
            }
            
        } catch (PDOException $e) {
            $error_message = 'Database error occurred. Please try again later.';
            error_log("Database error during login: " . $e->getMessage());
        } catch (Exception $e) {
            $error_message = 'An error occurred. Please try again later.';
            error_log("Login error: " . $e->getMessage());
        }
    }
}

// Handle demo login
if (isset($_GET['demo']) && $_GET['demo'] == '1') {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        if (!$conn) {
            throw new Exception("Database connection failed");
        }
        
        // Query for a demo user
        $query = "SELECT * FROM users WHERE email = 'demo@safetycampaign.org' LIMIT 1";
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Failed to prepare demo query");
        }
        
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = trim($user['first_name'] . ' ' . $user['last_name']);
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            
            // Handle avatar
            if (!empty($user['avatar'])) {
                $_SESSION['user_avatar'] = $user['avatar'];
            } else {
                $firstInitial = !empty($user['first_name']) ? strtoupper(substr($user['first_name'], 0, 1)) : '';
                $lastInitial = !empty($user['last_name']) ? strtoupper(substr($user['last_name'], 0, 1)) : '';
                $_SESSION['user_avatar'] = $firstInitial . $lastInitial;
            }
            
            // Set login success flag
            $_SESSION['login_success'] = true;
            
            error_log("Demo login successful for user: " . $user['email']);
            
            // Clear any previous output buffers
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // Redirect to home page
            header("Location: home.php");
            exit();
        } else {
            $error_message = 'Demo account not found. Please contact support.';
            error_log("Demo account not found");
        }
    } catch (Exception $e) {
        $error_message = 'Demo login failed. Please try again.';
        error_log("Demo login error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FCK</title>
    <link rel="stylesheet" href="login.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="login-container">
        <!-- Background Elements -->
        <div class="background-elements">
            <div class="floating-shape shape-1"></div>
            <div class="floating-shape shape-2"></div>
            <div class="floating-shape shape-3"></div>
        </div>

        <div class="login-panel">
            <div class="login-card">
                <div class="login-header">
                    <h2 class="login-title">Welcome Back</h2>
                    <p class="login-subtitle">Sign in to your account to continue</p>
                </div>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>
                
                <form id="loginForm" class="login-form" method="POST" action="" autocomplete="on">
                    <div class="form-group">
                        <label class="form-label" for="username">
                            <i class="fas fa-user"></i>
                            Email/Username
                        </label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            class="form-input" 
                            placeholder="Enter your email or username"
                            value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                            required
                            autocomplete="username"
                            autofocus
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">
                            <i class="fas fa-lock"></i>
                            Password
                        </label>
                        <div class="password-input-container">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                class="form-input" 
                                placeholder="Enter your password"
                                required
                                autocomplete="current-password"
                            >
                            <button type="button" class="password-toggle" id="passwordToggle">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-options">
                        <label class="checkbox-container">
                            <input type="checkbox" id="rememberMe" name="rememberMe">
                            <span class="checkmark"></span>
                            Remember me
                        </label>
                        <a href="#" class="forgot-password">Forgot password?</a>
                    </div>

                    <button type="submit" class="btn-primary login-btn">
                        <i class="fas fa-sign-in-alt"></i>
                        Sign In
                    </button>

                    <div class="divider">
                        <span>or</span>
                    </div>

                    <button type="button" class="btn-secondary demo-btn" id="demoLoginBtn">
                        <i class="fas fa-play"></i>
                        Try Demo Account
                    </button>
                </form>

                <div class="login-footer">
                    <p>Don't have an account? <a href="#" class="signup-link">Contact Administrator</a></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay" style="display: none;">
        <div class="loading-spinner">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Signing you in...</p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('loginForm');
            const passwordInput = document.getElementById('password');
            const passwordToggle = document.getElementById('passwordToggle');
            const demoLoginBtn = document.getElementById('demoLoginBtn');
            const loadingOverlay = document.getElementById('loadingOverlay');
            
            // Toggle password visibility
            if (passwordToggle) {
                passwordToggle.addEventListener('click', function() {
                    const type = passwordInput.type === 'password' ? 'text' : 'password';
                    passwordInput.type = type;
                    this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
                });
            }
            
            // Demo login button
            if (demoLoginBtn) {
                demoLoginBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (loadingOverlay) {
                        loadingOverlay.style.display = 'flex';
                    }
                    // Redirect to demo login endpoint
                    window.location.href = '?demo=1';
                });
            }
            
            // Show loading overlay on form submit
            if (loginForm && loadingOverlay) {
                loginForm.addEventListener('submit', function() {
                    loadingOverlay.style.display = 'flex';
                });
            }
        });
    </script>

    <style>
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        
        .loading-spinner {
            background: white;
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        
        .loading-spinner i {
            font-size: 2rem;
            color: #4a5568;
            margin-bottom: 15px;
        }
        
        .loading-spinner p {
            margin: 0;
            color: #666;
            font-weight: 500;
        }
        
        .alert {
            padding: 12px 16px;
            margin-bottom: 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
        }

        .alert-error {
            background-color: #fee;
            color: #c53030;
            border: 1px solid #fed7d7;
        }

        .alert-success {
            background-color: #f0fff4;
            color: #22543d;
            border: 1px solid #c6f6d5;
        }
    </style>
</body>
</html>