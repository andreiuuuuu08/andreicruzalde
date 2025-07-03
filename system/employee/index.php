<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn() || $_SESSION['role'] !== 'employee') {
    redirect(SITE_URL . '/login.php');
}

$userId = $_SESSION['user_id'];

// Get user info
$userInfo = getUserById($userId);

// Get feedback received count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM peer_feedback WHERE to_user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$feedbackResult = $stmt->get_result()->fetch_assoc();
$feedbackCount = $feedbackResult['count'];

// Check if self assessment exists
$stmt = $conn->prepare("SELECT * FROM self_assessment WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$selfAssessmentResult = $stmt->get_result();
$hasSelfAssessment = $selfAssessmentResult->num_rows > 0;

// Get latest self-assessment
$selfAssessment = null;
$selfRating = null;
$stmt = $conn->prepare("SELECT performance_rating FROM self_assessment WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $row = $result->fetch_assoc()) {
    $selfAssessment = $row;
    $selfRating = $row['performance_rating'];
}

// Get average peer feedback rating (last 6 months)
$avgPeerRating = null;
$monthsAgo = date('Y-m-01', strtotime('-5 months'));
$stmt = $conn->prepare("SELECT AVG((communication_rating + teamwork_rating + technical_rating + productivity_rating)/4) as avg_rating FROM peer_feedback WHERE to_user_id = ? AND created_at >= ?");
$stmt->bind_param("is", $userId, $monthsAgo);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $row = $result->fetch_assoc()) {
    $avgPeerRating = $row['avg_rating'] ? round($row['avg_rating'], 2) : null;
}

// Get 3 most recent feedback entries
$recentFeedback = [];
$stmt = $conn->prepare("SELECT f.*, u.name FROM peer_feedback f JOIN users u ON f.from_user_id = u.id WHERE f.to_user_id = ? ORDER BY f.created_at DESC LIMIT 3");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recentFeedback[] = $row;
}

// --- Feedback Given History (last 3) ---
$feedbackGivenHistory = [];
$stmt = $conn->prepare("SELECT f.*, u.name FROM peer_feedback f JOIN users u ON f.to_user_id = u.id WHERE f.from_user_id = ? ORDER BY f.created_at DESC LIMIT 3");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $feedbackGivenHistory[] = $row;
}

// Get self-assessment progress (1 if completed, 0 if not)
$selfProgress = $hasSelfAssessment ? 100 : 0;

// Avatar (use initials if no avatar)
function getAvatarUrl($name) {
    $initials = urlencode($name);
    return "https://ui-avatars.com/api/?name=$initials&background=0072ff&color=fff&size=64";
}

// --- More Analytics ---
// 1. Category breakdown of peer feedback (last 6 months)
$categoryAverages = [
    'communication' => null,
    'teamwork' => null,
    'technical' => null,
    'productivity' => null
];
$stmt = $conn->prepare("SELECT AVG(communication_rating) as comm, AVG(teamwork_rating) as team, AVG(technical_rating) as tech, AVG(productivity_rating) as prod FROM peer_feedback WHERE to_user_id = ? AND created_at >= ?");
$stmt->bind_param("is", $userId, $monthsAgo);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $row = $result->fetch_assoc()) {
    $categoryAverages['communication'] = $row['comm'] ? round($row['comm'],2) : null;
    $categoryAverages['teamwork'] = $row['team'] ? round($row['team'],2) : null;
    $categoryAverages['technical'] = $row['tech'] ? round($row['tech'],2) : null;
    $categoryAverages['productivity'] = $row['prod'] ? round($row['prod'],2) : null;
}

// 2. Count of feedback given by this user (last 6 months)
$feedbackGivenCount = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM peer_feedback WHERE from_user_id = ? AND created_at >= ?");
$stmt->bind_param("is", $userId, $monthsAgo);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $row = $result->fetch_assoc()) {
    $feedbackGivenCount = $row['cnt'];
}

// --- Notifications ---
$notifications = [];
if (!$hasSelfAssessment) {
    $notifications[] = [
        'type' => 'warning',
        'icon' => 'bi-exclamation-circle',
        'msg' => 'You have not completed your self assessment.'
    ];
}
if ($feedbackCount == 0) {
    $notifications[] = [
        'type' => 'info',
        'icon' => 'bi-info-circle',
        'msg' => 'No feedback received yet. Ask your peers!'
    ];
}
if ($feedbackGivenCount == 0) {
    $notifications[] = [
        'type' => 'secondary',
        'icon' => 'bi-arrow-repeat',
        'msg' => 'You have not given any peer feedback in the last 6 months.'
    ];
}

// --- Announcements (latest 4 from Admin and Team Lead) ---
$announcements = [];
$announcementQuery = "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 4";
$result = $conn->query($announcementQuery);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $announcements[] = $row;
    }
}

// --- Recognition Badge ---
$recognitionBadge = null;
if ($avgPeerRating && $avgPeerRating >= 4.5) {
    $recognitionBadge = [
        'label' => 'Top Performer',
        'icon' => 'bi-trophy-fill',
        'color' => 'success',
        'desc' => 'You are among the top-rated employees by your peers!'
    ];
} elseif ($avgPeerRating && $avgPeerRating >= 4.0) {
    $recognitionBadge = [
        'label' => 'Highly Rated',
        'icon' => 'bi-star-fill',
        'color' => 'primary',
        'desc' => 'You have received excellent feedback from your peers.'
    ];
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<h2 class="mb-3"><i class="bi bi-speedometer2 me-2"></i>Employee Dashboard</h2>
<p class="lead">Welcome, <span class="fw-bold text-primary"><?php echo htmlspecialchars($_SESSION['name']); ?></span>!</p>

<div class="row mt-4 g-3">
    <div class="col-md-3 col-6">
        <div class="card shadow-sm mb-4 text-center position-relative border-0">
            <?php if ($recognitionBadge): ?>
                <span class="position-absolute top-0 end-0 translate-middle badge rounded-pill bg-<?php echo $recognitionBadge['color']; ?> shadow" style="z-index:2; font-size:0.9rem; margin-top:8px; margin-right:8px;">
                    <i class="bi <?php echo $recognitionBadge['icon']; ?> me-1"></i><?php echo $recognitionBadge['label']; ?>
                </span>
            <?php endif; ?>
            <div class="card-body">
                <img src="<?php echo getAvatarUrl($userInfo['name']); ?>" class="rounded-circle mb-2 shadow" width="64" height="64" alt="Avatar">
                <h6 class="fw-bold mb-0 mt-2">Your Performance</h6>
                <div class="mt-2">
                    <span class="badge bg-primary">Self: <?php echo $selfRating ? $selfRating . '/5' : 'N/A'; ?></span>
                    <span class="badge bg-success ms-1">Peer: <?php echo $avgPeerRating ? $avgPeerRating . '/5' : 'N/A'; ?></span>
                </div>
                <?php if ($recognitionBadge): ?>
                    <div class="mt-2 small text-<?php echo $recognitionBadge['color']; ?>"><i class="bi <?php echo $recognitionBadge['icon']; ?>"></i> <?php echo $recognitionBadge['desc']; ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card shadow-sm mb-4 text-center">
            <div class="card-body">
                <h6 class="fw-bold mb-2">Self Assessment Progress</h6>
                <div class="progress mb-2" style="height: 18px;">
                    <div class="progress-bar <?php echo $selfProgress == 100 ? 'bg-success' : 'bg-warning'; ?>" role="progressbar" style="width: <?php echo $selfProgress; ?>%;" aria-valuenow="<?php echo $selfProgress; ?>" aria-valuemin="0" aria-valuemax="100"><?php echo $selfProgress; ?>%</div>
                </div>
                <a href="self_assessment.php" class="btn btn-outline-primary btn-sm w-100"><?php echo $hasSelfAssessment ? 'Update' : 'Complete'; ?> Assessment</a>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card shadow-sm mb-4 text-center">
            <div class="card-body">
                <h6 class="fw-bold mb-2">Feedback Received</h6>
                <p class="display-6 mb-1"><?php echo $feedbackCount; ?></p>
                <a href="view_feedback.php" class="btn btn-outline-primary btn-sm w-100">View Feedback</a>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card shadow-sm mb-4 text-center">
            <div class="card-body">
                <h6 class="fw-bold mb-2">Peer Feedback</h6>
                <p class="mb-1">Give feedback to colleagues</p>
                <a href="peer_feedback.php" class="btn btn-outline-primary btn-sm w-100">Give Feedback</a>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, #43cea2 0%, #185a9d 100%);">
                <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent Feedback</h6>
            </div>
            <div class="card-body">
                <?php if (count($recentFeedback) > 0): ?>
                    <?php foreach ($recentFeedback as $fb): ?>
                        <div class="border-bottom pb-2 mb-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span><strong><?php echo htmlspecialchars($fb['name']); ?></strong></span>
                                <small class="text-muted"><?php echo date('M d, Y', strtotime($fb['created_at'])); ?></small>
                            </div>
                            <div class="small text-muted mb-1">
                                Communication: <?php echo $fb['communication_rating']; ?>,
                                Teamwork: <?php echo $fb['teamwork_rating']; ?>,
                                Technical: <?php echo $fb['technical_rating']; ?>,
                                Productivity: <?php echo $fb['productivity_rating']; ?>
                            </div>
                            <?php if (!empty($fb['comments'])): ?>
                                <div class="small"><i class="bi bi-chat-left-text"></i> <?php echo nl2br(htmlspecialchars($fb['comments'])); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-muted">No feedback received yet.</div>
                <?php endif; ?>
            </div>
        </div>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, #0072ff 0%, #ffd200 100%);">
                <h6 class="mb-0"><i class="bi bi-send-check me-2"></i>Feedback You've Given</h6>
            </div>
            <div class="card-body">
                <?php if (count($feedbackGivenHistory) > 0): ?>
                    <?php foreach ($feedbackGivenHistory as $fb): ?>
                        <div class="border-bottom pb-2 mb-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span><strong>To: <?php echo htmlspecialchars($fb['name']); ?></strong></span>
                                <small class="text-muted"><?php echo date('M d, Y', strtotime($fb['created_at'])); ?></small>
                            </div>
                            <div class="small text-muted mb-1">
                                Communication: <?php echo $fb['communication_rating']; ?>,
                                Teamwork: <?php echo $fb['teamwork_rating']; ?>,
                                Technical: <?php echo $fb['technical_rating']; ?>,
                                Productivity: <?php echo $fb['productivity_rating']; ?>
                            </div>
                            <?php if (!empty($fb['comments'])): ?>
                                <div class="small"><i class="bi bi-chat-left-text"></i> <?php echo nl2br(htmlspecialchars($fb['comments'])); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-muted">You haven't given any feedback yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, #0072ff 0%, #43cea2 100%);">
                <h6 class="mb-0"><i class="bi bi-lightbulb me-2"></i>Quick Actions</h6>
            </div>
            <div class="card-body d-grid gap-2">
                <a href="profile.php" class="btn btn-outline-secondary"><i class="bi bi-person"></i> Update Profile</a>
                <a href="change_password.php" class="btn btn-outline-secondary"><i class="bi bi-key"></i> Change Password</a>
                <a href="../logout.php" class="btn btn-outline-danger"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </div>
        </div>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, #ffd200 0%, #ff8800 100%);">
                <h6 class="mb-0"><i class="bi bi-bell me-2"></i>Notifications</h6>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <?php foreach ($notifications as $note): ?>
                        <li class="mb-2"><i class="bi <?php echo $note['icon']; ?> text-<?php echo $note['type']; ?>"></i> <?php echo $note['msg']; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <div class="card shadow-sm mb-4 announcement-card">
            <div class="card-header bg-gradient text-white d-flex align-items-center justify-content-between" style="background: linear-gradient(135deg, #43cea2 0%, #0072ff 100%);">
                <h6 class="mb-0"><i class="bi bi-megaphone-fill me-2"></i>Announcements</h6>
                <span class="badge bg-light text-primary">Latest</span>
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <?php foreach ($announcements as $a): ?>
                        <li class="mb-4 p-3 rounded announcement-item position-relative border border-1 bg-light-subtle">
                            <div class="d-flex align-items-center mb-1">
                                <i class="bi bi-bell-fill text-warning me-2 fs-5"></i>
                                <span class="fw-semibold fs-6 flex-grow-1"><?php echo htmlspecialchars($a['message']); ?></span>
                                <div class="ms-2 d-flex gap-1">
                                    <button class="btn btn-outline-success btn-sm px-2 py-0" title="Acknowledge"><i class="bi bi-check2-circle"></i></button>
                                    <button class="btn btn-outline-secondary btn-sm px-2 py-0" title="Pin"><i class="bi bi-pin-angle"></i></button>
                                    <button class="btn btn-outline-primary btn-sm px-2 py-0" title="Mark as Read"><i class="bi bi-eye"></i></button>
                                </div>
                            </div>
                            <div class="d-flex align-items-center small text-muted mt-1 ms-4">
                                <span><?php echo date('M d, Y', strtotime($a['created_at'])); ?></span>
                                <span class="mx-2">|</span>
                                <span class="badge bg-secondary">
                                    From: <?php 
                                        if (isset($a['role'])) {
                                            echo ($a['role'] === 'teamlead') ? 'Team Lead' : 'Admin';
                                        } elseif (isset($a['type'])) {
                                            echo ($a['type'] === 'teamlead') ? 'Team Lead' : 'Admin';
                                        } else {
                                            echo 'Admin';
                                        }
                                    ?>
                                </span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                    <?php if (empty($announcements)): ?>
                        <li class="text-muted">No announcements at this time.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mt-4">
    <div class="card-header">
        <h5 class="mb-0">Your Profile</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Name:</strong> <?php echo $userInfo['name']; ?></p>
                <p><strong>Email:</strong> <?php echo $userInfo['email']; ?></p>
            </div>
            <div class="col-md-6">
                <p><strong>Department:</strong> <?php echo $userInfo['department']; ?></p>
                <p><strong>Role:</strong> <?php echo ucfirst($userInfo['role']); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Mini Chart for Feedback Trend -->
<div class="card shadow-sm mt-4 mb-4">
    <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, #43cea2 0%, #185a9d 100%);">
        <h6 class="mb-0"><i class="bi bi-graph-up-arrow me-2"></i>Feedback Trend (Last 6 Months)</h6>
    </div>
    <div class="card-body">
        <canvas id="feedbackTrendChart" height="80"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Fetch feedback trend data from PHP
<?php
// Prepare data for mini chart (last 6 months)
$trendMonths = [];
$trendAverages = [];
$trendQ = $conn->prepare("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, AVG((communication_rating+teamwork_rating+technical_rating+productivity_rating)/4) as avg_rating FROM peer_feedback WHERE to_user_id = ? GROUP BY month ORDER BY month DESC LIMIT 6");
$trendQ->bind_param("i", $userId);
$trendQ->execute();
$trendRes = $trendQ->get_result();
while ($row = $trendRes->fetch_assoc()) {
    $trendMonths[] = $row['month'];
    $trendAverages[] = $row['avg_rating'] ? round($row['avg_rating'],2) : 0;
}
$trendMonths = array_reverse($trendMonths);
$trendAverages = array_reverse($trendAverages);
?>
const ctx = document.getElementById('feedbackTrendChart').getContext('2d');
const feedbackTrendChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($trendMonths); ?>,
        datasets: [{
            label: 'Avg Rating',
            data: <?php echo json_encode($trendAverages); ?>,
            borderColor: '#43cea2',
            backgroundColor: 'rgba(67,206,162,0.08)',
            tension: 0.3,
            fill: true,
            pointRadius: 4,
            pointBackgroundColor: '#185a9d',
            pointBorderColor: '#fff',
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, max: 5 }
        }
    }
});
</script>

<style>
.card { border-radius: 1rem; transition: box-shadow 0.2s; }
.card:hover { box-shadow: 0 0.75rem 2rem rgba(67,206,162,0.10), 0 0.25rem 0.5rem rgba(0,0,0,0.04); }
.card-header { border-radius: 1rem 1rem 0 0; }
.badge { font-size: 0.95em; }
.bg-gradient { background-size: 200% 200%; animation: gradientMove 4s ease infinite; }
@keyframes gradientMove { 0% {background-position:0% 50%} 100% {background-position:100% 50%} }

.announcement-card .announcement-item {
    background: #f8fafd;
    border-color: #e3eafc;
    transition: box-shadow 0.2s, border-color 0.2s;
}
.announcement-card .announcement-item:hover {
    box-shadow: 0 0.5rem 1.5rem rgba(67,206,162,0.10), 0 0.25rem 0.5rem rgba(0,0,0,0.04);
    border-color: #43cea2;
}
.announcement-card .btn-sm { font-size: 0.95em; }
.announcement-card .btn-outline-success:hover { background: #43cea2; color: #fff; border-color: #43cea2; }
.announcement-card .btn-outline-secondary:hover { background: #ffd200; color: #222; border-color: #ffd200; }
.announcement-card .btn-outline-primary:hover { background: #0072ff; color: #fff; border-color: #0072ff; }
</style>

<?php include '../includes/footer.php'; ?>
