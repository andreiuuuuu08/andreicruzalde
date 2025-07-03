<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check if user is logged in and has admin role - fixed unreachable code
if (!isLoggedIn()) {
    redirect(SITE_URL . '/login.php');
}

if ($_SESSION['role'] !== 'admin') {
    // Debug information for non-admin users attempting to access admin area
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log("Access denied to admin area. User role: " . ($_SESSION['role'] ?? 'undefined'));
    }
    redirect(SITE_URL . '/login.php');
}

$userId = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle form submission for creating team lead
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_teamlead'])) {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $department = sanitize($_POST['department']);
    
    // Validate input
    if (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Email already in use.';
        } else {
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $role = 'teamlead';
            
            // Insert team lead
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, department) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $email, $hashedPassword, $role, $department);
            
            if ($stmt->execute()) {
                $success = 'Team Lead created successfully!';
            } else {
                $error = 'Error creating Team Lead: ' . $conn->error;
            }
        }
    }
}

// --- Feedback Analytics (Org-wide) ---
$feedbackStats = [
    'total_feedback' => 0,
    'avg_communication' => null,
    'avg_teamwork' => null,
    'avg_technical' => null,
    'avg_productivity' => null,
    'top_performers' => []
];

// Total feedback count
$result = $conn->query("SELECT COUNT(*) as cnt FROM peer_feedback");
if ($result && $row = $result->fetch_assoc()) {
    $feedbackStats['total_feedback'] = $row['cnt'];
}
// Category averages
$result = $conn->query("SELECT AVG(communication_rating) as comm, AVG(teamwork_rating) as team, AVG(technical_rating) as tech, AVG(productivity_rating) as prod FROM peer_feedback");
if ($result && $row = $result->fetch_assoc()) {
    $feedbackStats['avg_communication'] = $row['comm'] ? round($row['comm'],2) : null;
    $feedbackStats['avg_teamwork'] = $row['team'] ? round($row['team'],2) : null;
    $feedbackStats['avg_technical'] = $row['tech'] ? round($row['tech'],2) : null;
    $feedbackStats['avg_productivity'] = $row['prod'] ? round($row['prod'],2) : null;
}
// Top performers (avg rating >= 4.5, last 6 months)
$since = date('Y-m-01', strtotime('-5 months'));
$result = $conn->query("SELECT u.name, AVG((communication_rating+teamwork_rating+technical_rating+productivity_rating)/4) as avg_rating FROM peer_feedback f JOIN users u ON f.to_user_id = u.id WHERE f.created_at >= '$since' GROUP BY f.to_user_id HAVING avg_rating >= 4.5 ORDER BY avg_rating DESC LIMIT 5");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $feedbackStats['top_performers'][] = $row;
    }
}

// Get count of users by role
$query = "SELECT role, COUNT(*) as count FROM users GROUP BY role";
$result = $conn->query($query);
$userCounts = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $userCounts[$row['role']] = $row['count'];
    }
}

// Get all departments
$query = "SELECT DISTINCT department FROM users WHERE department IS NOT NULL AND department != ''";
$result = $conn->query($query);
$departments = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row['department'];
    }
}

// Get all team leads
$teamLeads = [];
$query = "SELECT * FROM users WHERE role = 'teamlead' ORDER BY name";
$result = $conn->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $teamLeads[] = $row;
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<style>
#mainCol {
    transition: margin-left 0.3s;
}
@media (min-width: 992px) {
    #mainSidebar {
        left: 0;
    }
    #mainCol.sidebar-collapsed {
        margin-left: 0 !important;
    }
    #mainCol {
        margin-left: 240px;
    }
}
.dashboard-card {
    border-radius: 1.5rem;
    box-shadow: 0 4px 24px rgba(0,0,0,0.07);
    transition: transform 0.15s, box-shadow 0.15s;
    background: linear-gradient(135deg, #f8fafc 0%, #e0eafc 100%);
}
.dashboard-card:hover {
    transform: translateY(-4px) scale(1.03);
    box-shadow: 0 8px 32px rgba(0,0,0,0.12);
}
.dashboard-icon {
    font-size: 2.8rem;
    margin-bottom: 0.5rem;
}
</style>
<script>
const sidebar = document.getElementById('mainSidebar');
const toggleBtn = document.getElementById('sidebarToggle');
const mainCol = document.getElementById('mainCol');
let sidebarOpen = true;
if (toggleBtn) {
    toggleBtn.addEventListener('click', function() {
        if (sidebarOpen) {
            sidebar.style.left = '-240px';
            toggleBtn.style.left = '0';
            toggleBtn.innerHTML = '<i class="bi bi-chevron-right"></i>';
            if(mainCol) mainCol.classList.add('sidebar-collapsed');
            sidebarOpen = false;
        } else {
            sidebar.style.left = '0';
            toggleBtn.style.left = '240px';
            toggleBtn.innerHTML = '<i class="bi bi-chevron-left"></i>';
            if(mainCol) mainCol.classList.remove('sidebar-collapsed');
            sidebarOpen = true;
        }
    });
}
</script>

<div class="row">
    <div class="col-lg-2 d-none d-lg-block" id="sidebarCol">
        <!-- Sidebar is included above -->
    </div>
    <div class="col-lg-10 ms-lg-auto" id="mainCol">
        <h2 class="mb-3"><i class="bi bi-speedometer2 text-primary"></i> Admin Dashboard</h2>
        <p class="lead">Welcome, <span class="fw-bold text-primary"><?php echo $_SESSION['name']; ?></span>!</p>

        <!-- Modern Admin Quick Access Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-2 col-lg-2">
                <a href="employees.php" class="dashboard-card card text-decoration-none h-100 p-0 text-center" title="Employees">
                    <div class="card-body p-2">
                        <i class="dashboard-icon bi bi-people-fill text-primary"></i>
                        <div class="small fw-semibold text-dark">Employees</div>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-2 col-lg-2">
                <a href="register_employee.php" class="dashboard-card card text-decoration-none h-100 p-0 text-center" title="Register">
                    <div class="card-body p-2">
                        <i class="dashboard-icon bi bi-person-plus-fill text-success"></i>
                        <div class="small fw-semibold text-dark">Register</div>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-2 col-lg-2">
                <a href="settings.php" class="dashboard-card card text-decoration-none h-100 p-0 text-center" title="Settings">
                    <div class="card-body p-2">
                        <i class="dashboard-icon bi bi-gear-fill text-dark"></i>
                        <div class="small fw-semibold text-dark">Settings</div>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-2 col-lg-2">
                <a href="departments.php" class="dashboard-card card text-decoration-none h-100 p-0 text-center" title="Departments">
                    <div class="card-body p-2">
                        <i class="dashboard-icon bi bi-diagram-3-fill text-info"></i>
                        <div class="small fw-semibold text-dark">Departments</div>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-2 col-lg-2">
                <a href="reports.php" class="dashboard-card card text-decoration-none h-100 p-0 text-center" title="Reports">
                    <div class="card-body p-2">
                        <i class="dashboard-icon bi bi-bar-chart-line-fill text-secondary"></i>
                        <div class="small fw-semibold text-dark">Reports</div>
                    </div>
                </a>
            </div>
        </div>
        <!-- End Modern Admin Quick Access Cards -->

        <!-- Analytics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-6 col-lg-4">
                <a href="audit_logs.php" class="dashboard-card card text-decoration-none h-100 p-0">
                    <div class="card-body text-center p-3">
                        <button class="btn btn-outline-success btn-sm rounded-circle mb-2" style="width:38px;height:38px;display:inline-flex;align-items:center;justify-content:center;"><i class="bi bi-clipboard-data-fill"></i></button>
                        <div class="fw-bold text-dark small">Audit Logs</div>
                    </div>
                </a>
            </div>
            <div class="col-md-6 col-lg-4">
                <a href="announcements.php" class="dashboard-card card text-decoration-none h-100 p-0">
                    <div class="card-body text-center p-3">
                        <button class="btn btn-outline-warning btn-sm rounded-circle mb-2" style="width:38px;height:38px;display:inline-flex;align-items:center;justify-content:center;"><i class="bi bi-megaphone-fill"></i></button>
                        <div class="fw-bold text-dark small">Announcements</div>
                    </div>
                </a>
            </div>
        </div>
        <!-- End Analytics Cards -->

        <!-- User Management Cards -->
        <div class="row g-4 mt-2">
            <div class="col-md-4">
                <a href="employees.php" class="dashboard-card card text-decoration-none h-100 p-0">
                    <div class="card-body text-center p-3">
                        <button class="btn btn-outline-primary btn-sm rounded-circle mb-2" style="width:38px;height:38px;display:inline-flex;align-items:center;justify-content:center;"><i class="bi bi-people-fill"></i></button>
                        <div class="fw-bold text-dark small">Employees</div>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <div class="dashboard-card card h-100 p-0">
                    <div class="card-body text-center p-3">
                        <button class="btn btn-outline-success btn-sm rounded-circle mb-2" style="width:38px;height:38px;display:inline-flex;align-items:center;justify-content:center;"><i class="bi bi-person-badge-fill"></i></button>
                        <div class="fw-bold text-dark small">Team Leads</div>
                        <button type="button" class="btn btn-sm btn-success w-100 rounded-pill fw-semibold mt-2" data-bs-toggle="modal" data-bs-target="#createTeamLeadModal">
                            <i class="bi bi-plus-circle me-1"></i> Add
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow border-0 mt-5">
            <div class="card-header bg-primary text-white d-flex align-items-center justify-content-between">
                <h5 class="mb-0"><i class="bi bi-person-badge-fill me-2"></i> Team Leads</h5>
                <span class="badge bg-light text-primary">Total: <?php echo $userCounts['teamlead'] ?? 0; ?></span>
            </div>
            <div class="card-body">
                <?php if (count($teamLeads) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Department</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($teamLeads as $lead): ?>
                                    <tr>
                                        <td class="fw-semibold text-primary"><?php echo $lead['name']; ?></td>
                                        <td><?php echo $lead['email']; ?></td>
                                        <td><span class="badge bg-info bg-opacity-25 text-info px-3 py-2"><?php echo $lead['department']; ?></span></td>
                                        <td><?php echo date('M d, Y', strtotime($lead['created_at'])); ?></td>
                                        <td>
                                            <a href="edit_user.php?id=<?php echo $lead['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill"><i class="bi bi-pencil"></i> Edit</a>
                                            <a href="delete_user.php?id=<?php echo $lead['id']; ?>" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Are you sure you want to delete this team lead?')"><i class="bi bi-trash"></i> Delete</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <p>No team leads have been created yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Feedback Analytics Section -->
        <div class="card shadow border-0 mt-5 mb-4">
            <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, #43cea2 0%, #185a9d 100%);">
                <h5 class="mb-0"><i class="bi bi-bar-chart-line-fill me-2"></i> Organization Feedback Analytics</h5>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-3">
                        <div class="bg-light rounded-3 p-3 text-center">
                            <div class="fs-2 fw-bold text-primary"><?php echo $feedbackStats['total_feedback']; ?></div>
                            <div class="small text-muted">Total Feedback</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="bg-light rounded-3 p-3 text-center">
                            <div class="fs-4 text-success"><?php echo $feedbackStats['avg_communication'] ?? 'N/A'; ?></div>
                            <div class="small text-muted">Avg. Communication</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="bg-light rounded-3 p-3 text-center">
                            <div class="fs-4 text-success"><?php echo $feedbackStats['avg_teamwork'] ?? 'N/A'; ?></div>
                            <div class="small text-muted">Avg. Teamwork</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="bg-light rounded-3 p-3 text-center">
                            <div class="fs-4 text-success"><?php echo $feedbackStats['avg_technical'] ?? 'N/A'; ?></div>
                            <div class="small text-muted">Avg. Technical</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="bg-light rounded-3 p-3 text-center">
                            <div class="fs-4 text-success"><?php echo $feedbackStats['avg_productivity'] ?? 'N/A'; ?></div>
                            <div class="small text-muted">Avg. Productivity</div>
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <h6 class="fw-bold">Top Performers (Last 6 Months, Avg ≥ 4.5)</h6>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($feedbackStats['top_performers'] as $tp): ?>
                            <li class="list-group-item d-flex align-items-center">
                                <i class="bi bi-trophy-fill text-warning me-2"></i>
                                <span class="fw-semibold"><?php echo htmlspecialchars($tp['name']); ?></span>
                                <span class="badge bg-success ms-2">Avg: <?php echo $tp['avg_rating']; ?></span>
                            </li>
                        <?php endforeach; ?>
                        <?php if (empty($feedbackStats['top_performers'])): ?>
                            <li class="text-muted">No top performers yet.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Modal for creating Team Lead -->
        <div class="modal fade" id="createTeamLeadModal" tabindex="-1" aria-labelledby="createTeamLeadModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="createTeamLeadModalLabel">Create Team Lead</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="post">
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="department" class="form-label">Department</label>
                                <input type="text" class="form-control" id="department" name="department" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="form-text">Password must be at least 6 characters.</div>
                            </div>
                            <div class="d-grid">
                                <button type="submit" name="create_teamlead" class="btn btn-primary">Create Team Lead</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
