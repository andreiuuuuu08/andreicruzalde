<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Enable more verbose error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Try to include DB connection, but catch any fatal errors
try {
    require_once 'includes/db.php';
    $dbConnected = true;
} catch (Throwable $e) {
    $dbConnected = false;
    $dbError = $e->getMessage();
}

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(SITE_URL);
}

$error = '';
$debug = '';

// Process login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $dbConnected) {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    // Always enable debug for login attempts
    $debugMode = true;
    
    // Basic validation
    if (empty($email) || empty($password)) {
        $error = 'Email and password are required.';
    } else {
        try {
            // Get user by email
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
            if (!$stmt) {
                throw new Exception("Database error: " . $conn->error);
            }
            
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                if ($debugMode) {
                    $debug .= "User found: ID={$user['id']}, Role={$user['role']}, Email={$user['email']}<br>";
                    $debug .= "Stored hash: " . substr($user['password'], 0, 10) . "...<br>";
                }
                
                // Verify password
                $passwordMatch = password_verify($password, $user['password']);
                
                if ($debugMode) {
                    $debug .= "Password verification result: " . ($passwordMatch ? "SUCCESS" : "FAILED") . "<br>";
                }
                
                if ($passwordMatch) {
                    // Login successful - set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    
                    if ($debugMode) {
                        $debug .= "Session set: user_id={$_SESSION['user_id']}, role={$_SESSION['role']}<br>";
                    }
                    
                    // Redirect to appropriate dashboard
                    if ($user['role'] === 'admin') {
                        if ($debugMode) {
                            $debug .= "Redirecting to admin panel...";
                        }
                        redirect(SITE_URL . '/admin/index.php');
                    } elseif ($user['role'] === 'teamlead') {
                        redirect(SITE_URL . '/teamlead/index.php');
                    } else {
                        redirect(SITE_URL . '/employee/index.php');
                    }
                } else {
                    $error = 'Invalid password.';
                }
            } else {
                $error = 'User not found.';
                if ($debugMode) {
                    $debug .= "No user found with email: $email<br>";
                }
            }
        } catch (Exception $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Display header with simple HTML if database connection failed
if (!$dbConnected) {
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login - Database Error</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    </head>
    <body class="bg-light">
        <div class="container py-5">
            <div class="card shadow-sm">
                <div class="card-header bg-danger text-white">
                    <h2>Database Connection Error</h2>
                </div>
                <div class="card-body">
                    <div class="alert alert-danger">
                        Database connection failed: ' . ($dbError ?? 'Unknown error') . '
                    </div>
                    <p>The system cannot connect to the database. Please run the setup script to configure the database.</p>
                    <a href="setup.php" class="btn btn-primary">Run Database Setup</a>
                </div>
            </div>
        </div>
    </body>
    </html>';
    exit;
}

// Display header
include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-header">
                <h4 class="mb-0">Login</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($debug)): ?>
                    <div class="alert alert-info">
                        <h5>Debug Information:</h5>
                        <?php echo $debug; ?>
                    </div>
                <?php endif; ?>
                
                <form method="post">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>
                </form>
                
                <div class="mt-3 text-center">
                    <p>Don't have an account? <a href="register.php">Register</a></p>
                </div>
                
                <div class="mt-3 text-center">
                    <p><a href="setup.php">Database Setup</a> | <a href="check_db.php">Check Database Status</a> | <a href="admin_reset.php">Reset Admin</a></p>
                </div>
                
                <div class="alert alert-info mt-3">
                    <p><strong>Admin Login:</strong></p>
                    <p>Email: admin@gmail.com<br>Password: admin123</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
