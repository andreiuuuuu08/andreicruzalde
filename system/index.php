<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Try to include DB connection, but catch any fatal errors
try {
    require_once 'includes/db.php';
    $dbConnected = true;
} catch (Throwable $e) {
    $dbConnected = false;
    $dbError = $e->getMessage();
    
    // Display a friendly error page
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Database Error</title>
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
                        Database connection failed: ' . $e->getMessage() . '
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

// Redirect based on user role
if (isLoggedIn()) {
    $role = $_SESSION['role'];
    
    if ($role === 'admin') {
        redirect(SITE_URL . '/admin');
    } elseif ($role === 'teamlead') {
        redirect(SITE_URL . '/teamlead');
    } elseif ($role === 'employee') {
        redirect(SITE_URL . '/employee');
    }
}

include 'includes/header.php';
?>

<!-- Enhanced & Responsive Introduction Section -->
<section class="intro-section py-5 bg-gradient" style="background: linear-gradient(135deg, #f8fafc 0%, #e0eafc 100%); min-height: 380px;">
    <div class="container">
        <div class="row align-items-center flex-column-reverse flex-md-row">
            <div class="col-md-6 text-center text-md-start mt-4 mt-md-0">
                <h1 class="display-4 fw-bold mb-3" style="color: #1a237e;">Welcome to <span style="background: linear-gradient(90deg, #00c6ff, #0072ff); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Employee Performance Review Feedback Analytic System</span></h1>
                <p class="lead mb-3" style="color: #374151;">
                    Empower your organization with a transparent, collaborative, and data-driven platform for employee performance management. Our system streamlines self-assessment, peer feedback, and analytics, helping you unlock every employee's full potential.
                </p>
                <ul class="list-unstyled mb-4 text-start mx-auto mx-md-0" style="max-width: 500px;">
                    <li class="mb-2"><i class="bi bi-star-fill text-warning me-2"></i> <strong>Personalized Self-Assessment:</strong> Reflect on achievements, set goals, and track progress.</li>
                    <li class="mb-2"><i class="bi bi-people-fill text-primary me-2"></i> <strong>Real-Time Peer & Team Feedback:</strong> Foster a culture of open communication and support.</li>
                    <li class="mb-2"><i class="bi bi-graph-up-arrow text-success me-2"></i> <strong>Actionable Analytics:</strong> Visualize performance trends and identify growth opportunities.</li>
                </ul>
                <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center justify-content-md-start">
                    <a class="btn btn-lg btn-gradient px-4" href="login.php" style="background: linear-gradient(90deg, #00c6ff, #0072ff); color: #fff; border: none;">Login</a>
                    <a class="btn btn-lg btn-outline-dark px-4" href="register.php">Register</a>
                </div>
            </div>
            <div class="col-md-6 text-center mb-4 mb-md-0">
                <img src="https://undraw.co/api/illustrations/undraw_team_spirit_re_yl1v.svg" alt="Team Spirit" class="img-fluid rounded shadow-sm" style="max-height: 300px; background: #e3f2fd; padding: 10px;">
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="features-section py-5" style="background: linear-gradient(135deg, #e0eafc 0%, #f8fafc 100%);">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-4 mb-4">
                <div class="card h-100 border-0 shadow-sm" style="background: #fffbe7;">
                    <div class="card-body">
                        <div class="mb-3"><i class="bi bi-person-badge-fill display-4 text-warning"></i></div>
                        <h5 class="card-title">Self Assessment</h5>
                        <p class="card-text">Take charge of your career with guided self-reflection, goal setting, and progress tracking tailored to your role.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 border-0 shadow-sm" style="background: #e3fcec;">
                    <div class="card-body">
                        <div class="mb-3"><i class="bi bi-chat-dots-fill display-4 text-success"></i></div>
                        <h5 class="card-title">Peer & Team Feedback</h5>
                        <p class="card-text">Foster a culture of open communication and support with instant, constructive feedback from your peers and leaders.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 border-0 shadow-sm" style="background: #e3f2fd;">
                    <div class="card-body">
                        <div class="mb-3"><i class="bi bi-bar-chart-steps display-4 text-primary"></i></div>
                        <h5 class="card-title">Performance Analytics</h5>
                        <p class="card-text">Visualize your achievements and areas for growth with interactive dashboards and downloadable reports.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Setup Alert -->
<div class="alert alert-info mt-4 container" style="background: #e3f2fd; color: #1a237e;">
    <p class="mb-2"><strong>First time here?</strong> To get started, please run the database setup below.</p>
    <a href="setup.php" class="btn btn-primary" style="background: linear-gradient(90deg, #00c6ff, #0072ff); border: none;">Run Database Setup</a>
</div>

<?php include 'includes/footer.php'; ?>
