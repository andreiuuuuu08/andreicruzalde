<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    redirect(SITE_URL . '/login.php');
}

// Fetch summary stats
$totalEmployees = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'employee'")->fetch_row()[0];
$totalTeamLeads = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'teamlead'")->fetch_row()[0];
$totalDepartments = $conn->query("SELECT COUNT(DISTINCT department) FROM users WHERE department IS NOT NULL AND department != ''")->fetch_row()[0];

// Fetch department breakdown
$departments = [];
$deptQuery = "SELECT department, COUNT(*) as total, SUM(role = 'teamlead') as teamleads, SUM(role = 'employee') as employees FROM users WHERE department IS NOT NULL AND department != '' GROUP BY department ORDER BY department";
$deptResult = $conn->query($deptQuery);
if ($deptResult) {
    while ($row = $deptResult->fetch_assoc()) {
        $departments[] = $row;
    }
}

// Fetch recent feedback (if table exists)
$recentFeedback = [];
if ($conn->query("SHOW TABLES LIKE 'peer_feedback'")->num_rows > 0) {
    $feedbackQuery = "SELECT f.*, u1.name AS from_name, u2.name AS to_name FROM peer_feedback f JOIN users u1 ON f.from_user_id = u1.id JOIN users u2 ON f.to_user_id = u2.id ORDER BY f.created_at DESC LIMIT 10";
    $result = $conn->query($feedbackQuery);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $recentFeedback[] = $row;
        }
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><i class="bi bi-bar-chart-line-fill text-secondary me-2"></i>Reports & Analytics</h2>
        <a href="index.php" class="btn btn-outline-secondary rounded-pill"><i class="bi bi-arrow-left"></i> Dashboard</a>
    </div>
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card shadow border-0 rounded-4 h-100">
                <div class="card-body text-center">
                    <div class="display-6 text-primary mb-2"><i class="bi bi-people-fill"></i></div>
                    <div class="fw-bold">Total Employees</div>
                    <div class="fs-3 text-dark"><?php echo $totalEmployees; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow border-0 rounded-4 h-100">
                <div class="card-body text-center">
                    <div class="display-6 text-success mb-2"><i class="bi bi-person-badge-fill"></i></div>
                    <div class="fw-bold">Total Team Leads</div>
                    <div class="fs-3 text-dark"><?php echo $totalTeamLeads; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow border-0 rounded-4 h-100">
                <div class="card-body text-center">
                    <div class="display-6 text-info mb-2"><i class="bi bi-diagram-3-fill"></i></div>
                    <div class="fw-bold">Total Departments</div>
                    <div class="fs-3 text-dark"><?php echo $totalDepartments; ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="card shadow border-0 rounded-4 mb-4">
        <div class="card-header bg-secondary text-white rounded-top-4">
            <h5 class="mb-0"><i class="bi bi-diagram-3"></i> Department Breakdown</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Department</th>
                            <th>Total Members</th>
                            <th>Team Leads</th>
                            <th>Employees</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($departments as $dept): ?>
                            <tr>
                                <td class="fw-semibold text-info"><i class="bi bi-diagram-3"></i> <?php echo htmlspecialchars($dept['department']); ?></td>
                                <td><?php echo $dept['total']; ?></td>
                                <td><span class="badge bg-success"><i class="bi bi-person-badge-fill"></i> <?php echo $dept['teamleads']; ?></span></td>
                                <td><span class="badge bg-primary"><i class="bi bi-people-fill"></i> <?php echo $dept['employees']; ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php if (!empty($recentFeedback)): ?>
    <div class="card shadow border-0 rounded-4 mb-4">
        <div class="card-header bg-gradient text-white rounded-top-4" style="background: linear-gradient(135deg, #43cea2 0%, #185a9d 100%);">
            <h5 class="mb-0"><i class="bi bi-chat-dots-fill me-2"></i>Recent Feedback</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Type</th>
                            <th>Comment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentFeedback as $fb): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($fb['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($fb['from_name']); ?></td>
                                <td><?php echo htmlspecialchars($fb['to_name']); ?></td>
                                <td><?php echo htmlspecialchars($fb['type'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($fb['comment'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>
