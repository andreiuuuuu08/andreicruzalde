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
$department = $userInfo['department'];

// Get team members (employees in same department)
$teamMembers = [];
$stmt = $conn->prepare("SELECT * FROM users WHERE department = ? AND role = 'employee'");
$stmt->bind_param("s", $department);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $teamMembers[] = $row;
    }
}

// Get overall team metrics with default values
$overallMetrics = [
    'avg_communication' => 0,
    'avg_teamwork' => 0,
    'avg_technical' => 0,
    'avg_productivity' => 0,
    'employees_with_feedback' => 0,
    'total_feedback' => 0
];

if (!empty($teamMembers)) {
    $memberIds = array_column($teamMembers, 'id');
    $memberIdsStr = implode(',', $memberIds);
    
    if (!empty($memberIdsStr)) {
        $query = "SELECT 
                AVG(communication_rating) as avg_communication,
                AVG(teamwork_rating) as avg_teamwork,
                AVG(technical_rating) as avg_technical,
                AVG(productivity_rating) as avg_productivity,
                COUNT(DISTINCT to_user_id) as employees_with_feedback,
                COUNT(*) as total_feedback
            FROM peer_feedback 
            WHERE to_user_id IN ($memberIdsStr)
        ";
        $result = $conn->query($query);
        if ($result && $row = $result->fetch_assoc()) {
            // Merge with defaults, preserving any values that exist
            $overallMetrics = array_merge($overallMetrics, array_filter($row, function($value) {
                return $value !== null;
            }));
        }
    }
}

// Get individual member metrics
$memberMetrics = [];
foreach ($teamMembers as $member) {
    $query = "SELECT 
            AVG(communication_rating) as avg_communication,
            AVG(teamwork_rating) as avg_teamwork,
            AVG(technical_rating) as avg_technical,
            AVG(productivity_rating) as avg_productivity,
            COUNT(*) as feedback_count
        FROM peer_feedback 
        WHERE to_user_id = {$member['id']}
    ";
    $result = $conn->query($query);
    if ($result) {
        $metrics = $result->fetch_assoc();
        
        // Check if self-assessment exists
        $saQuery = "SELECT * FROM self_assessment WHERE user_id = {$member['id']}";
        $saResult = $conn->query($saQuery);
        $hasSelfAssessment = ($saResult && $saResult->num_rows > 0);
        $selfAssessment = $hasSelfAssessment ? $saResult->fetch_assoc() : null;
        
        $memberMetrics[$member['id']] = [
            'user' => $member,
            'metrics' => $metrics,
            'has_self_assessment' => $hasSelfAssessment,
            'self_assessment' => $selfAssessment
        ];
    }
}

// Count members with completed self-assessments
$selfAssessmentCount = count(array_filter($memberMetrics, function($m) {
    return $m['has_self_assessment'];
}));

// Calculate monthly trend data for the team
$trendMonths = 6;
$trendLabels = [];
$trendData = [
    'avg_communication' => [],
    'avg_teamwork' => [],
    'avg_technical' => [],
    'avg_productivity' => []
];
if (!empty($teamMembers)) {
    $memberIds = array_column($teamMembers, 'id');
    $memberIdsStr = implode(',', $memberIds);
    if (!empty($memberIdsStr)) {
        $trendQuery = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month,
            AVG(communication_rating) as avg_communication,
            AVG(teamwork_rating) as avg_teamwork,
            AVG(technical_rating) as avg_technical,
            AVG(productivity_rating) as avg_productivity
            FROM peer_feedback
            WHERE to_user_id IN ($memberIdsStr)
            GROUP BY month
            ORDER BY month DESC
            LIMIT $trendMonths";
        $trendResult = $conn->query($trendQuery);
        $months = [];
        $comm = $team = $tech = $prod = [];
        if ($trendResult) {
            while ($row = $trendResult->fetch_assoc()) {
                $months[] = $row['month'];
                $comm[] = $row['avg_communication'] !== null ? round($row['avg_communication'], 2) : 0;
                $team[] = $row['avg_teamwork'] !== null ? round($row['avg_teamwork'], 2) : 0;
                $tech[] = $row['avg_technical'] !== null ? round($row['avg_technical'], 2) : 0;
                $prod[] = $row['avg_productivity'] !== null ? round($row['avg_productivity'], 2) : 0;
            }
        }
        $trendLabels = array_reverse($months);
        $trendData['avg_communication'] = array_reverse($comm);
        $trendData['avg_teamwork'] = array_reverse($team);
        $trendData['avg_technical'] = array_reverse($tech);
        $trendData['avg_productivity'] = array_reverse($prod);
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<div class="container-fluid" style="margin-left: 64px; min-height: 100vh; background: linear-gradient(120deg, #f8fafc 0%, #e9f3fa 100%); padding-bottom: 2rem;">

<div class="d-flex justify-content-between align-items-center mb-4 pt-4 flex-wrap gap-2">
    <div class="d-flex align-items-center gap-3">
        <h2 class="fw-bold text-primary mb-0"><i class="bi bi-graph-up-arrow me-2"></i>Team Performance Dashboard</h2>
        <button class="btn btn-outline-primary" id="downloadPdfBtn" title="Download as PDF"><i class="bi bi-file-earmark-pdf"></i></button>
    </div>
    <div class="d-flex align-items-center gap-2">
        <input type="date" class="form-control" id="dateFrom" title="From date">
        <span class="mx-1">to</span>
        <input type="date" class="form-control" id="dateTo" title="To date">
        <button class="btn btn-outline-secondary" id="filterDateBtn" title="Apply date filter"><i class="bi bi-funnel"></i></button>
        <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4 py-2 shadow-sm"><i class="bi bi-arrow-left me-1"></i>Back to Dashboard</a>
    </div>
</div>

<div class="alert alert-info shadow-sm border-0 rounded-3 mb-4" style="background: linear-gradient(90deg, #e0eafc 0%, #cfdef3 100%);">
    <p class="mb-0"><i class="bi bi-info-circle"></i> This dashboard shows aggregated performance metrics for your team in the <strong><?php echo htmlspecialchars($department); ?></strong> department.</p>
</div>

<!-- Team Overview Card -->
<div class="card shadow-lg mb-4 border-0 rounded-4 animate__animated animate__fadeIn">
    <div class="card-header bg-white border-0 rounded-top-4 pb-0">
        <h5 class="mb-0 fw-semibold text-primary">Team Overview</h5>
    </div>
    <div class="card-body pb-2">
        <div class="row g-3">
            <div class="col-md-3 text-center">
                <div class="bg-light rounded-3 py-3 mb-2 shadow-sm">
                    <h6 class="text-muted">Team Size</h6>
                    <div class="display-5 fw-bold text-primary"><?php echo count($teamMembers); ?></div>
                    <p class="text-muted mb-0">employees</p>
                </div>
            </div>
            <div class="col-md-3 text-center">
                <div class="bg-light rounded-3 py-3 mb-2 shadow-sm">
                    <h6 class="text-muted">Feedback Coverage</h6>
                    <?php 
                        $feedbackCoverage = count($teamMembers) > 0 
                            ? round(($overallMetrics['employees_with_feedback'] / count($teamMembers)) * 100) 
                            : 0;
                    ?>
                    <div class="display-5 fw-bold text-success"><?php echo $feedbackCoverage; ?>%</div>
                    <p class="text-muted mb-0"><?php echo $overallMetrics['employees_with_feedback'] ?? 0; ?> of <?php echo count($teamMembers); ?> employees</p>
                </div>
            </div>
            <div class="col-md-3 text-center">
                <div class="bg-light rounded-3 py-3 mb-2 shadow-sm">
                    <h6 class="text-muted">Self-Assessment Completion</h6>
                    <?php 
                        $saCompletionRate = count($teamMembers) > 0 
                            ? round(($selfAssessmentCount / count($teamMembers)) * 100) 
                            : 0;
                    ?>
                    <div class="display-5 fw-bold text-info"><?php echo $saCompletionRate; ?>%</div>
                    <p class="text-muted mb-0"><?php echo $selfAssessmentCount; ?> of <?php echo count($teamMembers); ?> employees</p>
                </div>
            </div>
            <div class="col-md-3 text-center">
                <div class="bg-light rounded-3 py-3 mb-2 shadow-sm">
                    <h6 class="text-muted">Total Feedback</h6>
                    <div class="display-5 fw-bold text-warning"><?php echo $overallMetrics['total_feedback'] ?? 0; ?></div>
                    <p class="text-muted mb-0">peer evaluations</p>
                </div>
            </div>
        </div>
    </div>
</div>
<hr class="my-4" style="opacity:0.15;">

<!-- Performance Trend Chart -->
<div class="card shadow-lg mb-4 border-0 rounded-4 animate__animated animate__fadeIn">
    <div class="card-header bg-white border-0 rounded-top-4 pb-0 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-semibold text-primary">Performance Trend</h5>
        <select id="trendMetric" class="form-select w-auto">
            <option value="avg_communication">Communication</option>
            <option value="avg_teamwork">Teamwork</option>
            <option value="avg_technical">Technical Skills</option>
            <option value="avg_productivity">Productivity</option>
        </select>
    </div>
    <div class="card-body">
        <canvas id="trendChart" height="80"></canvas>
    </div>
</div>
<hr class="my-4" style="opacity:0.15;">

<!-- Top Performer Card -->
<?php
$topPerformer = null;
$topScore = 0;
foreach ($memberMetrics as $id => $data) {
    if ($data['metrics']['feedback_count'] > 0) {
        $avg = (
            $data['metrics']['avg_communication'] +
            $data['metrics']['avg_teamwork'] +
            $data['metrics']['avg_technical'] +
            $data['metrics']['avg_productivity']
        ) / 4;
        if ($avg > $topScore) {
            $topScore = $avg;
            $topPerformer = $data['user'];
        }
    }
}
?>
<?php if ($topPerformer): ?>
<div class="card shadow-lg mb-4 border-success border-2 rounded-4 animate__animated animate__fadeInDown">
    <div class="card-body d-flex align-items-center">
        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($topPerformer['name']); ?>&background=43cea2&color=fff&size=56" class="rounded-circle me-4 shadow" width="56" height="56" alt="Avatar">
        <div>
            <h5 class="mb-1 fw-bold text-success">Top Performer: <span><?php echo htmlspecialchars($topPerformer['name']); ?></span></h5>
            <div class="text-muted">Avg. Rating: <?php echo number_format($topScore ?? 0, 2); ?> / 5</div>
        </div>
    </div>
</div>
<?php endif; ?>
<hr class="my-4" style="opacity:0.15;">

<!-- Performance Metrics Card -->
<?php if (!empty($overallMetrics) && isset($overallMetrics['avg_communication']) && $overallMetrics['avg_communication'] > 0): ?>
<div class="card shadow-lg mb-4 border-0 rounded-4 animate__animated animate__fadeIn">
    <div class="card-header bg-white border-0 rounded-top-4 pb-0">
        <h5 class="mb-0 fw-semibold text-primary">Team Performance Metrics</h5>
    </div>
    <div class="card-body pb-2">
        <div class="row g-3">
            <div class="col-md-3 text-center">
                <div class="bg-white rounded-3 py-3 mb-2 shadow-sm border border-primary-subtle">
                    <h6 class="text-muted">Communication</h6>
                    <div class="display-6 fw-bold text-primary"><?php echo number_format($overallMetrics['avg_communication'] ?? 0, 1); ?></div>
                    <p class="text-muted mb-0">out of 5</p>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-primary" role="progressbar" 
                             style="width: <?php echo ($overallMetrics['avg_communication'] / 5 * 100); ?>%" 
                             aria-valuenow="<?php echo $overallMetrics['avg_communication']; ?>" 
                             aria-valuemin="0" aria-valuemax="5"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 text-center">
                <div class="bg-white rounded-3 py-3 mb-2 shadow-sm border border-success-subtle">
                    <h6 class="text-muted">Teamwork</h6>
                    <div class="display-6 fw-bold text-success"><?php echo number_format($overallMetrics['avg_teamwork'] ?? 0, 1); ?></div>
                    <p class="text-muted mb-0">out of 5</p>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-success" role="progressbar" 
                             style="width: <?php echo ($overallMetrics['avg_teamwork'] / 5 * 100); ?>%" 
                             aria-valuenow="<?php echo $overallMetrics['avg_teamwork']; ?>" 
                             aria-valuemin="0" aria-valuemax="5"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 text-center">
                <div class="bg-white rounded-3 py-3 mb-2 shadow-sm border border-info-subtle">
                    <h6 class="text-muted">Technical Skills</h6>
                    <div class="display-6 fw-bold text-info"><?php echo number_format($overallMetrics['avg_technical'] ?? 0, 1); ?></div>
                    <p class="text-muted mb-0">out of 5</p>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-info" role="progressbar" 
                             style="width: <?php echo ($overallMetrics['avg_technical'] / 5 * 100); ?>%" 
                             aria-valuenow="<?php echo $overallMetrics['avg_technical']; ?>" 
                             aria-valuemin="0" aria-valuemax="5"></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 text-center">
                <div class="bg-white rounded-3 py-3 mb-2 shadow-sm border border-warning-subtle">
                    <h6 class="text-muted">Productivity</h6>
                    <div class="display-6 fw-bold text-warning"><?php echo number_format($overallMetrics['avg_productivity'] ?? 0, 1); ?></div>
                    <p class="text-muted mb-0">out of 5</p>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-warning" role="progressbar" 
                             style="width: <?php echo ($overallMetrics['avg_productivity'] / 5 * 100); ?>%" 
                             aria-valuenow="<?php echo $overallMetrics['avg_productivity']; ?>" 
                             aria-valuemin="0" aria-valuemax="5"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="card shadow-sm mb-4 animate__animated animate__fadeIn">
    <div class="card-body text-center py-5">
        <h5>Not enough feedback data available yet</h5>
        <p>As team members receive more feedback, performance metrics will appear here.</p>
    </div>
</div>
<?php endif; ?>
<hr class="my-4" style="opacity:0.15;">

<!-- Individual Employee Performance -->
<div class="card shadow-lg mb-4 border-0 rounded-4 animate__animated animate__fadeIn">
    <div class="card-header d-flex justify-content-between align-items-center bg-white border-0 rounded-top-4 pb-0 flex-wrap gap-2">
        <h5 class="mb-0 fw-semibold text-primary">Individual Employee Performance</h5>
        <div class="d-flex align-items-center gap-2">
            <input type="text" class="form-control d-inline-block" id="searchInput" placeholder="Search by name or email..." style="width: 220px;">
            <button class="btn btn-outline-secondary me-1" id="exportCsvBtn" title="Export CSV"><i class="bi bi-file-earmark-spreadsheet"></i></button>
            <button class="btn btn-outline-secondary" id="exportExcelBtn" title="Export Excel"><i class="bi bi-file-earmark-excel"></i></button>
        </div>
    </div>
    <div class="card-body">
        <?php if (count($memberMetrics) > 0): ?>
            <div class="table-responsive d-none d-md-block">
                <table class="table table-hover align-middle" id="performanceTable">
                    <thead class="table-light">
                        <tr>
                            <th class="sortable" data-sort="name">Employee <i class="bi bi-arrow-down-up"></i></th>
                            <th class="sortable" data-sort="communication">Communication</th>
                            <th class="sortable" data-sort="teamwork">Teamwork</th>
                            <th class="sortable" data-sort="technical">Technical Skills</th>
                            <th class="sortable" data-sort="productivity">Productivity</th>
                            <th>Self Rating</th>
                            <th class="sortable" data-sort="feedback">Feedback Count</th>
                            <th>Quick Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($memberMetrics as $id => $data): ?>
                            <tr data-name="<?php echo strtolower($data['user']['name']); ?>" data-communication="<?php echo $data['metrics']['avg_communication']; ?>" data-teamwork="<?php echo $data['metrics']['avg_teamwork']; ?>" data-technical="<?php echo $data['metrics']['avg_technical']; ?>" data-productivity="<?php echo $data['metrics']['avg_productivity']; ?>" data-feedback="<?php echo $data['metrics']['feedback_count']; ?>">
                                <td class="fw-semibold">
                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($data['user']['name']); ?>&background=43cea2&color=fff&size=32" class="rounded-circle shadow-sm me-2" alt="Avatar" width="32" height="32">
                                    <?php echo htmlspecialchars($data['user']['name']); ?>
                                    <?php if ($data['metrics']['feedback_count'] > 0 && isset($data['metrics']['improved']) && $data['metrics']['improved']): ?>
                                        <span class="ms-1 text-success" title="Improved"><i class="bi bi-arrow-up-circle-fill"></i></span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-primary bg-opacity-75 fs-6"><?php echo number_format($data['metrics']['avg_communication'] ?? 0, 1); ?></span></td>
                                <td><span class="badge bg-success bg-opacity-75 fs-6"><?php echo number_format($data['metrics']['avg_teamwork'] ?? 0, 1); ?></span></td>
                                <td><span class="badge bg-info bg-opacity-75 fs-6"><?php echo number_format($data['metrics']['avg_technical'] ?? 0, 1); ?></span></td>
                                <td><span class="badge bg-warning bg-opacity-75 fs-6"><?php echo number_format($data['metrics']['avg_productivity'] ?? 0, 1); ?></span></td>
                                <td><?php if ($data['has_self_assessment']): ?><span class="badge bg-info bg-opacity-75 fs-6"><?php echo $data['self_assessment']['performance_rating']; ?>/5</span><?php else: ?><span class="badge bg-warning bg-opacity-75">Not completed</span><?php endif; ?></td>
                                <td><?php echo $data['metrics']['feedback_count']; ?></td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Quick Actions">
                                            <i class="bi bi-lightning-charge"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="view_employee.php?id=<?php echo $data['user']['id']; ?>"><i class="bi bi-eye me-2"></i>View Details</a></li>
                                            <li><a class="dropdown-item" href="#" onclick="addNote(<?php echo $data['user']['id']; ?>); return false;"><i class="bi bi-journal-text me-2"></i>Add Note</a></li>
                                            <li><a class="dropdown-item" href="#" onclick="sendReminder(<?php echo $data['user']['id']; ?>); return false;"><i class="bi bi-bell me-2"></i>Send Reminder</a></li>
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
                <?php foreach ($memberMetrics as $id => $data): ?>
                <div class="card mb-3 shadow-sm">
                    <div class="card-body d-flex align-items-center gap-3">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($data['user']['name']); ?>&background=43cea2&color=fff&size=40" class="rounded-circle shadow" width="40" height="40" alt="Avatar">
                        <div>
                            <div class="fw-bold text-primary"><?php echo htmlspecialchars($data['user']['name']); ?></div>
                            <div class="small text-muted">Feedback: <span class="badge bg-primary bg-opacity-75"><?php echo $data['metrics']['feedback_count']; ?></span></div>
                            <div class="small">Comm: <span class="badge bg-primary bg-opacity-75"><?php echo number_format($data['metrics']['avg_communication'] ?? 0, 1); ?></span> | Team: <span class="badge bg-success bg-opacity-75"><?php echo number_format($data['metrics']['avg_teamwork'] ?? 0, 1); ?></span></div>
                            <div class="small">Tech: <span class="badge bg-info bg-opacity-75"><?php echo number_format($data['metrics']['avg_technical'] ?? 0, 1); ?></span> | Prod: <span class="badge bg-warning bg-opacity-75"><?php echo number_format($data['metrics']['avg_productivity'] ?? 0, 1); ?></span></div>
                            <div class="small">Self: <?php if ($data['has_self_assessment']): ?><span class="badge bg-info bg-opacity-75"><?php echo $data['self_assessment']['performance_rating']; ?>/5</span><?php else: ?><span class="badge bg-warning bg-opacity-75">Not completed</span><?php endif; ?></div>
                            <div class="mt-2">
                                <button class="btn btn-sm btn-outline-primary" onclick="addNote(<?php echo $data['user']['id']; ?>)"><i class="bi bi-journal-text"></i></button>
                                <button class="btn btn-sm btn-outline-info" onclick="sendReminder(<?php echo $data['user']['id']; ?>)"><i class="bi bi-bell"></i></button>
                                <a href="view_employee.php?id=<?php echo $data['user']['id']; ?>" class="btn btn-sm btn-info"><i class="bi bi-eye"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <p>No team members found in your department.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
<hr class="my-4" style="opacity:0.15;">

<!-- Tips for Improving Team Performance -->
<div class="card shadow-lg border-0 rounded-4 animate__animated animate__fadeIn">
    <div class="card-header bg-white border-0 rounded-top-4 pb-0">
        <h5 class="mb-0 fw-semibold text-primary">Tips for Improving Team Performance</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h6>Communication</h6>
                <ul>
                    <li>Schedule regular team meetings and one-on-ones</li>
                    <li>Implement clear communication channels</li>
                    <li>Encourage open and honest feedback</li>
                    <li>Document important decisions and discussions</li>
                </ul>
                <h6>Teamwork</h6>
                <ul>
                    <li>Organize team-building activities</li>
                    <li>Set clear team goals and celebrate achievements</li>
                    <li>Foster a collaborative environment</li>
                    <li>Recognize and reward collaboration</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6>Technical Skills</h6>
                <ul>
                    <li>Provide learning resources and training opportunities</li>
                    <li>Encourage knowledge sharing sessions</li>
                    <li>Implement mentorship programs</li>
                    <li>Set aside time for skill development</li>
                </ul>
                <h6>Productivity</h6>
                <ul>
                    <li>Help prioritize tasks effectively</li>
                    <li>Remove obstacles and provide necessary resources</li>
                    <li>Encourage work-life balance to prevent burnout</li>
                    <li>Recognize and reward productive team members</li>
                </ul>
            </div>
        </div>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
// Accessibility: keyboard navigation for sorting
const sortableHeaders = document.querySelectorAll('.sortable');
sortableHeaders.forEach(header => {
    header.tabIndex = 0;
    header.setAttribute('role', 'button');
    header.setAttribute('aria-label', 'Sort by ' + header.textContent.trim());
    header.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') header.click();
    });
});
// Column sorting
sortableHeaders.forEach(header => {
    header.addEventListener('click', function() {
        const sortKey = header.getAttribute('data-sort');
        const table = document.getElementById('performanceTable');
        const rows = Array.from(table.querySelectorAll('tbody tr'));
        const sorted = rows.sort((a, b) => {
            if (sortKey === 'name') {
                return a.dataset.name.localeCompare(b.dataset.name);
            } else {
                return parseFloat(b.dataset[sortKey]) - parseFloat(a.dataset[sortKey]);
            }
        });
        const tbody = table.querySelector('tbody');
        sorted.forEach(row => tbody.appendChild(row));
    });
});
// Search/filter
const searchInput = document.getElementById('searchInput');
const performanceTable = document.getElementById('performanceTable');
if (searchInput && performanceTable) {
    searchInput.addEventListener('keyup', function() {
        const filter = searchInput.value.toLowerCase();
        const rows = performanceTable.querySelectorAll('tbody tr');
        rows.forEach(row => {
            const name = row.children[0].textContent.toLowerCase();
            row.style.display = name.includes(filter) ? '' : 'none';
        });
    });
}
// Export CSV/Excel
function downloadCSV(csv, filename) {
    const blob = new Blob([csv], { type: 'text/csv' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename;
    link.click();
}
document.getElementById('exportCsvBtn').addEventListener('click', function() {
    let csv = 'Employee,Communication,Teamwork,Technical Skills,Productivity,Self Rating,Feedback Count\n';
    const rows = performanceTable.querySelectorAll('tbody tr');
    rows.forEach(row => {
        if (row.style.display !== 'none') {
            const cols = row.querySelectorAll('td');
            csv += `${cols[0].textContent.trim()},${cols[1].textContent.trim()},${cols[2].textContent.trim()},${cols[3].textContent.trim()},${cols[4].textContent.trim()},${cols[5].textContent.trim()},${cols[6].textContent.trim()}\n`;
        }
    });
    downloadCSV(csv, 'team_performance.csv');
});
document.getElementById('exportExcelBtn').addEventListener('click', function() {
    let csv = 'Employee,Communication,Teamwork,Technical Skills,Productivity,Self Rating,Feedback Count\n';
    const rows = performanceTable.querySelectorAll('tbody tr');
    rows.forEach(row => {
        if (row.style.display !== 'none') {
            const cols = row.querySelectorAll('td');
            csv += `${cols[0].textContent.trim()},${cols[1].textContent.trim()},${cols[2].textContent.trim()},${cols[3].textContent.trim()},${cols[4].textContent.trim()},${cols[5].textContent.trim()},${cols[6].textContent.trim()}\n`;
        }
    });
    downloadCSV(csv, 'team_performance.xls');
});
// PDF Export
window.jsPDF = window.jspdf.jsPDF;
document.getElementById('downloadPdfBtn').addEventListener('click', function() {
    const doc = new jsPDF();
    doc.text('Team Performance Dashboard', 10, 10);
    doc.html(document.querySelector('.container-fluid'), {
        callback: function (doc) {
            doc.save('team_performance.pdf');
        },
        x: 10,
        y: 20
    });
});
// Performance Trend Chart (functional)
const trendLabels = <?php echo json_encode($trendLabels); ?>;
const trendData = <?php echo json_encode($trendData); ?>;
const metricMap = {
    'avg_communication': { label: 'Communication', color: '#0072ff' },
    'avg_teamwork': { label: 'Teamwork', color: '#43cea2' },
    'avg_technical': { label: 'Technical Skills', color: '#185a9d' },
    'avg_productivity': { label: 'Productivity', color: '#ffd200' }
};
let selectedMetric = document.getElementById('trendMetric').value;
const ctx = document.getElementById('trendChart').getContext('2d');
let trendChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: trendLabels,
        datasets: [{
            label: metricMap[selectedMetric].label,
            data: trendData[selectedMetric],
            borderColor: metricMap[selectedMetric].color,
            backgroundColor: 'rgba(67,206,162,0.1)',
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { min: 0, max: 5 } }
    }
});
document.getElementById('trendMetric').addEventListener('change', function() {
    selectedMetric = this.value;
    trendChart.data.datasets[0].label = metricMap[selectedMetric].label;
    trendChart.data.datasets[0].data = trendData[selectedMetric];
    trendChart.data.datasets[0].borderColor = metricMap[selectedMetric].color;
    trendChart.update();
});
document.getElementById('filterDateBtn').addEventListener('click', function() {
    alert('Date range filter is a placeholder. Implement server-side filtering as needed.');
});
window.addNote = function(userId) {
    // Find user name from table (desktop or mobile)
    let userName = '';
    const row = document.querySelector('tr[data-name][data-feedback] [onclick*="addNote(' + userId + '"]');
    if (row) {
        userName = row.closest('tr').querySelector('.fw-semibold').textContent.trim();
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
