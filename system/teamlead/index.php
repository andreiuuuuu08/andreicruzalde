<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check if user is logged in and has teamlead role
if (!isLoggedIn() || $_SESSION['role'] !== 'teamlead') {
    redirect(SITE_URL . '/login.php');
}

$userId = $_SESSION['user_id'];

// Get user info
$userInfo = getUserById($userId);

// Get team members (employees in same department)
$teamMembers = [];
$stmt = $conn->prepare("SELECT * FROM users WHERE department = ? AND role = 'employee' AND id != ?");
$stmt->bind_param("si", $userInfo['department'], $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $teamMembers[] = $row;
    }
}

// Count of pending feedback (team members without feedback)
$pendingFeedback = 0;
foreach ($teamMembers as $member) {
    $feedback = getFeedback($userId, $member['id']);
    if ($feedback === null) {
        $pendingFeedback++;
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<div class="container py-4">
    <h2 class="mb-3"><i class="bi bi-bar-chart-line-fill text-success me-2"></i>Team Lead Dashboard</h2>
    <p class="lead">Welcome, <span class="fw-bold text-success"><?php echo $_SESSION['name']; ?></span>!</p>
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card shadow border-0 rounded-4 h-100">
                <div class="card-body text-center">
                    <div class="display-6 text-primary mb-2"><i class="bi bi-people-fill"></i></div>
                    <div class="fw-bold">Team Members</div>
                    <div class="fs-3 text-dark"><?php echo count($teamMembers); ?></div>
                    <a href="team_members.php" class="btn btn-outline-primary btn-sm rounded-pill mt-2">View Team</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow border-0 rounded-4 h-100">
                <div class="card-body text-center">
                    <div class="display-6 text-warning mb-2"><i class="bi bi-chat-dots-fill"></i></div>
                    <div class="fw-bold">Pending Feedback</div>
                    <div class="fs-3 text-dark"><?php echo $pendingFeedback; ?></div>
                    <a href="pending_feedback.php" class="btn btn-outline-warning btn-sm rounded-pill mt-2">Review Team</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow border-0 rounded-4 h-100">
                <div class="card-body text-center">
                    <div class="display-6 text-success mb-2"><i class="bi bi-bar-chart-line-fill"></i></div>
                    <div class="fw-bold">Team Performance</div>
                    <div class="fs-6 text-muted mb-2">View your team's overall performance</div>
                    <a href="team_performance.php" class="btn btn-outline-success btn-sm rounded-pill">View Reports</a>
                </div>
            </div>
        </div>
    </div>
    <div class="card shadow border-0 rounded-4 mt-4">
        <div class="card-header bg-success text-white rounded-top-4">
            <h5 class="mb-0"><i class="bi bi-people-fill me-2"></i>Your Team Members</h5>
        </div>
        <div class="card-body">
            <?php if (count($teamMembers) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Feedback Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($teamMembers as $member): ?>
                                <?php 
                                    $feedback = getFeedback($userId, $member['id']);
                                    $feedbackStatus = $feedback ? 'Completed' : 'Pending';
                                    $statusClass = $feedback ? 'success' : 'warning';
                                ?>
                                <tr>
                                    <td class="fw-semibold text-primary"><?php echo htmlspecialchars($member['name']); ?></td>
                                    <td><?php echo htmlspecialchars($member['email']); ?></td>
                                    <td><span class="badge bg-info bg-opacity-25 text-info px-3 py-2"><?php echo htmlspecialchars($member['department']); ?></span></td>
                                    <td><span class="badge bg-<?php echo $statusClass; ?>"><?php echo $feedbackStatus; ?></span></td>
                                    <td>
                                        <a href="../employee/peer_feedback.php?to_id=<?php echo $member['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill" title="Give Feedback"><i class="bi bi-chat-dots"></i></a>
                                        <a href="view_employee.php?id=<?php echo $member['id']; ?>" class="btn btn-sm btn-outline-info rounded-pill" title="View Details"><i class="bi bi-eye"></i></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <p>You don't have any team members in your department yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="card shadow border-0 rounded-4 mt-4">
        <div class="card-header bg-light text-dark rounded-top-4">
            <h5 class="mb-0"><i class="bi bi-person-badge-fill me-2"></i>Your Profile</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($userInfo['name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($userInfo['email']); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Department:</strong> <?php echo htmlspecialchars($userInfo['department']); ?></p>
                    <p><strong>Role:</strong> <?php echo ucfirst($userInfo['role']); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
