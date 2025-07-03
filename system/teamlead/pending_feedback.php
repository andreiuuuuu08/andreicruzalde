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
$stmt = $conn->prepare("SELECT * FROM users WHERE department = ? AND role = 'employee'");
$stmt->bind_param("s", $userInfo['department']);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $teamMembers[] = $row;
    }
}

// Filter to only members without feedback
$pendingMembers = [];
foreach ($teamMembers as $member) {
    $feedback = getFeedback($userId, $member['id']);
    if ($feedback === null) {
        $pendingMembers[] = $member;
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<div class="container-fluid" style="margin-left: 64px; min-height: 100vh; background: linear-gradient(120deg, #f8fafc 0%, #e9f3fa 100%); padding-bottom: 2rem;">

<div class="d-flex justify-content-between align-items-center mb-4 pt-4">
    <h2 class="fw-bold text-primary"><i class="bi bi-chat-dots-fill me-2"></i>Pending Feedback</h2>
    <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4 py-2 shadow-sm"><i class="bi bi-arrow-left me-1"></i>Back to Dashboard</a>
</div>

<div class="card shadow-lg mb-4 border-0 rounded-4 animate__animated animate__fadeIn">
    <div class="card-header bg-white border-0 rounded-top-4 pb-0 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-semibold text-primary">Team Members Awaiting Your Feedback</h5>
        <div>
            <input type="text" class="form-control d-inline-block me-2" id="searchInput" placeholder="Search by name or email..." style="width: 220px;">
            <button class="btn btn-outline-secondary me-1" id="exportCsvBtn" title="Export CSV"><i class="bi bi-file-earmark-spreadsheet"></i></button>
            <button class="btn btn-outline-secondary" id="exportExcelBtn" title="Export Excel"><i class="bi bi-file-earmark-excel"></i></button>
        </div>
    </div>
    <div class="card-body">
        <?php if (count($pendingMembers) > 0): ?>
            <p class="mb-3">The following team members in your department have not received feedback from you yet:</p>
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="pendingTable">
                    <thead class="table-light">
                        <tr>
                            <th>Avatar</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Joined</th>
                            <th>Self-Assessment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingMembers as $member): ?>
                            <?php 
                                $stmt = $conn->prepare("SELECT * FROM self_assessment WHERE user_id = ?");
                                $stmt->bind_param("i", $member['id']);
                                $stmt->execute();
                                $assessmentResult = $stmt->get_result();
                                $hasAssessment = $assessmentResult->num_rows > 0;
                                $assessmentStatus = $hasAssessment ? 'Completed' : 'Pending';
                                $assessmentClass = $hasAssessment ? 'success' : 'warning';
                            ?>
                            <tr>
                                <td><img src="https://ui-avatars.com/api/?name=<?php echo urlencode($member['name']); ?>&background=43cea2&color=fff&size=32" class="rounded-circle shadow-sm" alt="Avatar" width="32" height="32"></td>
                                <td class="fw-semibold"><?php echo htmlspecialchars($member['name']); ?></td>
                                <td><?php echo htmlspecialchars($member['email']); ?></td>
                                <td><span class="badge bg-primary bg-opacity-75"><?php echo htmlspecialchars($member['department']); ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($member['created_at'])); ?></td>
                                <td><span class="badge bg-<?php echo $assessmentClass; ?> bg-opacity-75"><?php echo $assessmentStatus; ?></span></td>
                                <td>
                                    <a href="../employee/peer_feedback.php?to_id=<?php echo $member['id']; ?>" class="btn btn-sm btn-primary shadow-sm" title="Give Feedback"><i class="bi bi-chat-dots"></i> Give Feedback</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-success d-flex align-items-center">
                <i class="bi bi-check-circle-fill display-6 me-3"></i>
                <div>
                    <h5 class="mb-1">All feedback complete!</h5>
                    <p class="mb-0">You have provided feedback for all your team members.</p>
                </div>
            </div>
            <p class="mt-3">You can view your team members and the feedback you've provided on the <a href="team_members.php">Team Members</a> page.</p>
        <?php endif; ?>
    </div>
</div>

<?php if (count($pendingMembers) > 0): ?>
<div class="card shadow-lg border-0 rounded-4 animate__animated animate__fadeIn">
    <div class="card-header bg-white border-0 rounded-top-4 pb-0">
        <h5 class="mb-0 fw-semibold text-primary">Why Feedback is Important</h5>
    </div>
    <div class="card-body">
        <p>As a team lead, your feedback helps team members understand their strengths and areas for improvement. Regular feedback:</p>
        <ul>
            <li>Improves individual performance</li>
            <li>Enhances team collaboration</li>
            <li>Creates a culture of continuous improvement</li>
            <li>Helps in identifying training and development needs</li>
        </ul>
        <p>Consider providing specific examples when giving feedback to make it more actionable and valuable for your team members.</p>
    </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
</div>

<script>
// Simple client-side search/filter for the table
const searchInput = document.getElementById('searchInput');
const pendingTable = document.getElementById('pendingTable');
if (searchInput && pendingTable) {
    searchInput.addEventListener('keyup', function() {
        const filter = searchInput.value.toLowerCase();
        const rows = pendingTable.querySelectorAll('tbody tr');
        rows.forEach(row => {
            const name = row.children[1].textContent.toLowerCase();
            const email = row.children[2].textContent.toLowerCase();
            row.style.display = (name.includes(filter) || email.includes(filter)) ? '' : 'none';
        });
    });
}
// Export CSV
function downloadCSV(csv, filename) {
    const blob = new Blob([csv], { type: 'text/csv' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename;
    link.click();
}
document.getElementById('exportCsvBtn').addEventListener('click', function() {
    let csv = 'Name,Email,Department,Joined,Self-Assessment\n';
    const rows = pendingTable.querySelectorAll('tbody tr');
    rows.forEach(row => {
        if (row.style.display !== 'none') {
            const cols = row.querySelectorAll('td');
            csv += `${cols[1].textContent},${cols[2].textContent},${cols[3].textContent},${cols[4].textContent},${cols[5].textContent}\n`;
        }
    });
    downloadCSV(csv, 'pending_feedback.csv');
});
document.getElementById('exportExcelBtn').addEventListener('click', function() {
    let csv = 'Name,Email,Department,Joined,Self-Assessment\n';
    const rows = pendingTable.querySelectorAll('tbody tr');
    rows.forEach(row => {
        if (row.style.display !== 'none') {
            const cols = row.querySelectorAll('td');
            csv += `${cols[1].textContent},${cols[2].textContent},${cols[3].textContent},${cols[4].textContent},${cols[5].textContent}\n`;
        }
    });
    downloadCSV(csv, 'pending_feedback.xls');
});
</script>

<style>
.card {
    transition: box-shadow 0.2s;
}
.card:hover {
    box-shadow: 0 0.75rem 2rem rgba(67,206,162,0.10), 0 0.25rem 0.5rem rgba(0,0,0,0.04);
}
.table th, .table td {
    vertical-align: middle;
}
</style>
