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

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<div class="container-fluid" style="margin-left: 64px; min-height: 100vh; background: linear-gradient(120deg, #f8fafc 0%, #e9f3fa 100%); padding-bottom: 2rem;">

<div class="row mb-4">
    <div class="col-md-8 d-flex align-items-center">
        <h2 class="me-3 mb-0"><i class="bi bi-people-fill me-2"></i>Team Members</h2>
        <span class="badge bg-primary ms-2">Total: <?php echo count($teamMembers); ?></span>
    </div>
    <div class="col-md-4">
        <form class="d-flex" method="get" id="searchForm">
            <input type="text" class="form-control me-2" name="search" id="searchInput" placeholder="Search by name or email...">
            <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i></button>
        </form>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-3">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h6 class="text-muted">Feedback Completion</h6>
                <?php
                $feedbackGiven = 0;
                foreach ($teamMembers as $member) {
                    $feedback = getFeedback($userId, $member['id']);
                    if ($feedback) $feedbackGiven++;
                }
                $feedbackRate = count($teamMembers) > 0 ? round(($feedbackGiven/count($teamMembers))*100) : 0;
                ?>
                <div class="display-6 fw-bold text-success"><?php echo $feedbackRate; ?>%</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h6 class="text-muted">Self-Assessment Completion</h6>
                <?php
                $assessmentDone = 0;
                foreach ($teamMembers as $member) {
                    $stmt = $conn->prepare("SELECT * FROM self_assessment WHERE user_id = ?");
                    $stmt->bind_param("i", $member['id']);
                    $stmt->execute();
                    $assessmentResult = $stmt->get_result();
                    if ($assessmentResult->num_rows > 0) $assessmentDone++;
                }
                $assessmentRate = count($teamMembers) > 0 ? round(($assessmentDone/count($teamMembers))*100) : 0;
                ?>
                <div class="display-6 fw-bold text-info"><?php echo $assessmentRate; ?>%</div>
            </div>
        </div>
    </div>
    <div class="col-md-6 d-flex align-items-center justify-content-end">
        <button class="btn btn-outline-secondary me-2" id="exportCsvBtn" title="Export CSV"><i class="bi bi-file-earmark-spreadsheet"></i> Export CSV</button>
        <button class="btn btn-outline-secondary" id="exportExcelBtn" title="Export Excel"><i class="bi bi-file-earmark-excel"></i> Export Excel</button>
    </div>
</div>

<!-- Add Note Modal -->
<div class="modal fade" id="addNoteModal" tabindex="-1" aria-labelledby="addNoteModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addNoteModalLabel">Add Note for <span id="noteUserName"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="noteForm">
          <input type="hidden" id="noteUserId">
          <div class="mb-3">
            <label for="noteText" class="form-label">Note</label>
            <textarea class="form-control" id="noteText" rows="4" required></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="saveNoteBtn">Save Note</button>
      </div>
    </div>
  </div>
</div>

<div class="card shadow-sm">
    <div class="card-header">
        <h5 class="mb-0">Your Team in <?php echo $userInfo['department']; ?> Department</h5>
    </div>
    <div class="card-body">
        <?php if (count($teamMembers) > 0): ?>
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover align-middle" id="teamTable">
                    <thead>
                        <tr>
                            <th>Avatar</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Joined</th>
                            <th>Feedback Status</th>
                            <th>Self-Assessment</th>
                            <th>Quick Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($teamMembers as $member): ?>
                            <?php 
                                $feedback = getFeedback($userId, $member['id']);
                                $feedbackStatus = $feedback ? 'Provided' : 'Pending';
                                $feedbackClass = $feedback ? 'success' : 'warning';
                                $stmt = $conn->prepare("SELECT * FROM self_assessment WHERE user_id = ?");
                                $stmt->bind_param("i", $member['id']);
                                $stmt->execute();
                                $assessmentResult = $stmt->get_result();
                                $hasAssessment = $assessmentResult->num_rows > 0;
                                $assessmentStatus = $hasAssessment ? 'Completed' : 'Pending';
                                $assessmentClass = $hasAssessment ? 'success' : 'warning';
                            ?>
                            <tr data-user-id="<?php echo $member['id']; ?>" data-user-name="<?php echo htmlspecialchars($member['name']); ?>">
                                <td><img src="https://ui-avatars.com/api/?name=<?php echo urlencode($member['name']); ?>&background=43cea2&color=fff&size=32" class="rounded-circle shadow-sm" alt="Avatar" width="32" height="32"></td>
                                <td><?php echo $member['name']; ?></td>
                                <td><?php echo $member['email']; ?></td>
                                <td><?php echo date('M d, Y', strtotime($member['created_at'])); ?></td>
                                <td><span class="badge bg-<?php echo $feedbackClass; ?>" title="<?php echo $feedbackStatus; ?>"><?php echo $feedbackStatus; ?></span></td>
                                <td><span class="badge bg-<?php echo $assessmentClass; ?>" title="<?php echo $assessmentStatus; ?>"><?php echo $assessmentStatus; ?></span></td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Quick Actions">
                                            <i class="bi bi-lightning-charge"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="view_employee.php?id=<?php echo $member['id']; ?>"><i class="bi bi-eye me-2"></i>View Details</a></li>
                                            <li><a class="dropdown-item" href="#" onclick="addNote(<?php echo $member['id']; ?>); return false;"><i class="bi bi-journal-text me-2"></i>Add Note</a></li>
                                            <li><a class="dropdown-item" href="#" onclick="sendReminder(<?php echo $member['id']; ?>); return false;"><i class="bi bi-bell me-2"></i>Send Reminder</a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <!-- Mobile Card View -->
            <div class="d-md-none">
                <?php foreach ($teamMembers as $member): ?>
                <div class="card mb-3 shadow-sm">
                    <div class="card-body d-flex align-items-center gap-3">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($member['name']); ?>&background=43cea2&color=fff&size=40" class="rounded-circle shadow" width="40" height="40" alt="Avatar">
                        <div>
                            <div class="fw-bold text-primary"><?php echo htmlspecialchars($member['name']); ?></div>
                            <div class="small text-muted">Email: <?php echo htmlspecialchars($member['email']); ?></div>
                            <div class="small">Joined: <?php echo date('M d, Y', strtotime($member['created_at'])); ?></div>
                            <div class="small">Feedback: <span class="badge bg-<?php echo $feedbackClass; ?> bg-opacity-75"><?php echo $feedbackStatus; ?></span></div>
                            <div class="small">Self-Assessment: <span class="badge bg-<?php echo $assessmentClass; ?> bg-opacity-75"><?php echo $assessmentStatus; ?></span></div>
                            <div class="mt-2">
                                <button class="btn btn-sm btn-outline-primary" onclick="addNote(<?php echo $member['id']; ?>)"><i class="bi bi-journal-text"></i></button>
                                <button class="btn btn-sm btn-outline-info" onclick="sendReminder(<?php echo $member['id']; ?>)"><i class="bi bi-bell"></i></button>
                                <a href="view_employee.php?id=<?php echo $member['id']; ?>" class="btn btn-sm btn-info"><i class="bi bi-eye"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <p>You don't have any team members in your department yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow-sm mt-4">
    <div class="card-header">
        <h5 class="mb-0">Team Performance Summary</h5>
    </div>
    <div class="card-body">
        <?php if (count($teamMembers) > 0): ?>
            <p>Here's how your team is performing based on peer feedback:</p>
            <?php
            // Get average ratings for department
            $stmt = $conn->prepare("
                SELECT 
                    AVG(communication_rating) as avg_communication,
                    AVG(teamwork_rating) as avg_teamwork,
                    AVG(technical_rating) as avg_technical,
                    AVG(productivity_rating) as avg_productivity
                FROM peer_feedback f
                JOIN users u ON f.to_user_id = u.id
                WHERE u.department = ?
            ");
            $stmt->bind_param("s", $userInfo['department']);
            $stmt->execute();
            $avgRatings = $stmt->get_result()->fetch_assoc();
            ?>
            <div class="row text-center">
                <div class="col-md-8 mx-auto">
                    <canvas id="teamRadarChart" height="220"></canvas>
                </div>
            </div>
            <?php if ($avgRatings && $avgRatings['avg_communication'] !== null): ?>
                <div class="row text-center mt-4">
                    <div class="col-md-3">
                        <h5>Communication</h5>
                        <div class="display-5"><?php echo number_format($avgRatings['avg_communication'], 1); ?></div>
                        <p class="text-muted">out of 5</p>
                    </div>
                    <div class="col-md-3">
                        <h5>Teamwork</h5>
                        <div class="display-5"><?php echo number_format($avgRatings['avg_teamwork'], 1); ?></div>
                        <p class="text-muted">out of 5</p>
                    </div>
                    <div class="col-md-3">
                        <h5>Technical Skills</h5>
                        <div class="display-5"><?php echo number_format($avgRatings['avg_technical'], 1); ?></div>
                        <p class="text-muted">out of 5</p>
                    </div>
                    <div class="col-md-3">
                        <h5>Productivity</h5>
                        <div class="display-5"><?php echo number_format($avgRatings['avg_productivity'], 1); ?></div>
                        <p class="text-muted">out of 5</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info mt-4">
                    <p>Not enough feedback data to generate team performance metrics yet.</p>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert alert-info">
                <p>Add team members to view performance metrics.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('teamRadarChart');
    if (ctx) {
        var data = {
            labels: ['Communication', 'Teamwork', 'Technical', 'Productivity'],
            datasets: [{
                label: 'Team Average',
                data: [
                    <?php echo isset($avgRatings['avg_communication']) && $avgRatings['avg_communication'] !== null ? number_format($avgRatings['avg_communication'], 2, '.', '') : 0; ?>,
                    <?php echo isset($avgRatings['avg_teamwork']) && $avgRatings['avg_teamwork'] !== null ? number_format($avgRatings['avg_teamwork'], 2, '.', '') : 0; ?>,
                    <?php echo isset($avgRatings['avg_technical']) && $avgRatings['avg_technical'] !== null ? number_format($avgRatings['avg_technical'], 2, '.', '') : 0; ?>,
                    <?php echo isset($avgRatings['avg_productivity']) && $avgRatings['avg_productivity'] !== null ? number_format($avgRatings['avg_productivity'], 2, '.', '') : 0; ?>
                ],
                backgroundColor: 'rgba(67,206,162,0.2)',
                borderColor: '#43cea2',
                pointBackgroundColor: '#43cea2',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: '#43cea2',
                borderWidth: 2
            }]
        };
        var options = {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                r: {
                    min: 0,
                    max: 5,
                    ticks: { stepSize: 1, color: '#888' },
                    pointLabels: { font: { size: 14 } }
                }
            }
        };
        new Chart(ctx, { type: 'radar', data: data, options: options });
    }
});
</script>
<script>
// Simple client-side search/filter for the table
const searchInput = document.getElementById('searchInput');
const teamTable = document.getElementById('teamTable');
if (searchInput && teamTable) {
    searchInput.addEventListener('keyup', function() {
        const filter = searchInput.value.toLowerCase();
        const rows = teamTable.querySelectorAll('tbody tr');
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
    let csv = 'Name,Email,Joined,Feedback Status,Self-Assessment\n';
    const rows = teamTable.querySelectorAll('tbody tr');
    rows.forEach(row => {
        if (row.style.display !== 'none') {
            const cols = row.querySelectorAll('td');
            csv += `${cols[1].textContent},${cols[2].textContent},${cols[3].textContent},${cols[4].textContent},${cols[5].textContent}\n`;
        }
    });
    downloadCSV(csv, 'team_members.csv');
});
// Export Excel (simple CSV with .xls extension)
document.getElementById('exportExcelBtn').addEventListener('click', function() {
    let csv = 'Name,Email,Joined,Feedback Status,Self-Assessment\n';
    const rows = teamTable.querySelectorAll('tbody tr');
    rows.forEach(row => {
        if (row.style.display !== 'none') {
            const cols = row.querySelectorAll('td');
            csv += `${cols[1].textContent},${cols[2].textContent},${cols[3].textContent},${cols[4].textContent},${cols[5].textContent}\n`;
        }
    });
    downloadCSV(csv, 'team_members.xls');
});
window.addNote = function(userId) {
    // Find user name from table (desktop or mobile)
    let userName = '';
    const row = document.querySelector('tr[data-user-id="' + userId + '"]');
    if (row) {
        userName = row.querySelector('td:nth-child(2)').textContent.trim();
    } else {
        // Try mobile card
        const card = Array.from(document.querySelectorAll('.d-md-none .card')).find(card => card.innerHTML.includes('addNote(' + userId + ')'));
        if (card) {
            userName = card.querySelector('.fw-bold').textContent.trim();
        }
    }
    document.getElementById('noteUserName').textContent = userName || 'Employee';
    document.getElementById('noteUserId').value = userId;
    document.getElementById('noteText').value = '';
    var modal = new bootstrap.Modal(document.getElementById('addNoteModal'));
    modal.show();
};
document.getElementById('saveNoteBtn').addEventListener('click', function() {
    const userId = document.getElementById('noteUserId').value;
    const note = document.getElementById('noteText').value.trim();
    if (!note) {
        alert('Please enter a note.');
        return;
    }
    // TODO: AJAX to backend to save note
    alert('Note saved for user ID: ' + userId + '\n' + note + ' (implement backend)');
    bootstrap.Modal.getInstance(document.getElementById('addNoteModal')).hide();
});
window.sendReminder = function(userId) {
    if (confirm('Send reminder to user ID: ' + userId + '? (implement backend)')) {
        // TODO: AJAX to backend to send reminder
        alert('Reminder sent! (implement backend)');
    }
};
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
hr {
    border-top: 2px dashed #43cea2;
}
@media (max-width: 767px) {
    .table-responsive { display: none !important; }
    .d-md-none { display: block !important; }
}
</style>

<?php include '../includes/footer.php'; ?>
</div>
