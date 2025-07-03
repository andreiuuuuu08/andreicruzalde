<?php 
require_once 'config.php';
require_once 'db.php';
require_once 'functions.php';
// Fetch company name from settings if available
// function getSetting($conn, $key, $default = '') {
//     $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
//     $stmt->bind_param("s", $key);
//     $stmt->execute();
//     $result = $stmt->get_result();
//     if ($result && $row = $result->fetch_assoc()) {
//         return $row['setting_value'];
//     }
//     return $default;
// }
$companyName = defined('SITE_NAME') ? SITE_NAME : 'System';
if (isset($conn)) {
    $companyName = getSetting($conn, 'company_name', $companyName);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($companyName); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <style>
        .navbar-custom {
            background: linear-gradient(90deg, #00c6ff 0%, #0072ff 100%);
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            border-bottom-left-radius: 1.5rem;
            border-bottom-right-radius: 1.5rem;
        }
        .navbar-brand {
            font-weight: bold;
            letter-spacing: 1px;
            font-size: 1.7rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .navbar-brand .bi {
            font-size: 2rem;
            color: #ffd600;
        }
        .nav-link.active, .nav-link:focus, .nav-link:hover {
            color: #ffd600 !important;
            font-weight: 600;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 2rem;
        }
        .nav-link {
            transition: color 0.2s, background 0.2s;
            padding: 0.5rem 1.2rem;
            font-size: 1.08rem;
        }
        .navbar-toggler {
            border: none;
            background: #fff;
            border-radius: 50%;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .navbar-toggler:focus {
            outline: none;
            box-shadow: 0 0 0 2px #ffd600;
        }
        @media (max-width: 991.98px) {
            .navbar-brand {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top py-2">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="<?php echo SITE_URL; ?>">
                <i class="bi bi-bar-chart-line-fill"></i> <?php echo htmlspecialchars($companyName); ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>">
                                <i class="bi bi-house-door-fill me-1"></i>Dashboard
                            </a>
                        </li>
                        <?php if (hasRole('teamlead')): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo SITE_URL; ?>/teamlead">
                                    <i class="bi bi-people-fill me-1"></i>Team Management
                                </a>
                            </li>
                        <?php elseif (hasRole('employee')): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo SITE_URL; ?>/employee/peer_feedback.php">
                                    <i class="bi bi-chat-dots-fill me-1"></i>Peer Feedback
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo SITE_URL; ?>/employee/self_assessment.php">
                                    <i class="bi bi-person-badge-fill me-1"></i>Self Assessment
                                </a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>/logout.php">
                                <i class="bi bi-box-arrow-right me-1"></i>Logout
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>/login.php">
                                <i class="bi bi-box-arrow-in-right me-1"></i>Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>/register.php">
                                <i class="bi bi-person-plus-fill me-1"></i>Register
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container py-4">
