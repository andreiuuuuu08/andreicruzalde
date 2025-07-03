<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check if user is logged in and has admin role
if (!isLoggedIn()) {
    redirect(SITE_URL . '/login.php');
}

if ($_SESSION['role'] !== 'admin') {
    redirect(SITE_URL . '/login.php');
}

$success = '';
$error = '';
$user = null;
$feedbackData = [];
$selfAssessment = null;

// Get user ID from URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $userId = (int)$_GET['id'];
    
    // Get user information
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Get feedback received by this user
        $feedbackStmt = $conn->prepare("
            SELECT f.*, u.name as from_name 
            FROM peer_feedback f 
            JOIN users u ON f.from_user_id = u.id 
            WHERE f.to_user_id = ?
            ORDER BY f.created_at DESC
        ");
        $feedbackStmt->bind_param("i", $userId);
        $feedbackStmt->execute();
        $feedbackResult = $feedbackStmt->get_result();
        
        if ($feedbackResult && $feedbackResult->num_rows > 0) {
            while ($row = $feedbackResult->fetch_assoc()) {
                $feedbackData[] = $row;
            }
        }
        
        // Get self assessment if it exists
        $saStmt = $conn->prepare("SELECT * FROM self_assessment WHERE user_id = ?");
        $saStmt->bind_param("i", $userId);
        $saStmt->execute();
        $saResult = $saStmt->get_result();
        
        if ($saResult && $saResult->num_rows > 0) {
            $selfAssessment = $saResult->fetch_assoc();
        }
    } else {
        $error = 'Employee not found.';
    }
} else {
    $error = 'Invalid employee ID.';
}

include '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold text-primary"><i class="bi bi-person-lines-fill me-2"></i>Employee Details</h2>
    <a href="employees.php" class="btn btn-outline-secondary rounded-pill px-4"><i class="bi bi-arrow-left"></i> Back to Employees</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger shadow-sm rounded-pill px-4 py-2"><?php echo $error; ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success shadow-sm rounded-pill px-4 py-2"><?php echo $success; ?></div>
<?php endif; ?>

<?php if ($user): ?>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card shadow border-0 mb-4 rounded-4">
                <div class="card-header bg-gradient text-white rounded-top-4" style="background: linear-gradient(135deg, #2193b0 0%, #6dd5ed 100%);">
                    <h5 class="mb-0"><i class="bi bi-person-badge me-2"></i>Profile</h5>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['name']); ?>&background=2193b0&color=fff&size=96" class="rounded-circle shadow" alt="Avatar">
                    </div>
                    <h4 class="fw-bold mb-1"><?php echo htmlspecialchars($user['name']); ?></h4>
                    <p class="text-muted mb-2"><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
                    <div class="mb-2">
                        <span class="badge bg-info bg-opacity-25 text-info px-3 py-2"><i class="bi bi-diagram-3"></i> <?php echo htmlspecialchars($user['department']); ?></span>
                    </div>
                    <div class="mb-2">
                        <span class="badge bg-primary bg-opacity-25 text-primary px-3 py-2"><i class="bi bi-person"></i> <?php echo ucfirst(htmlspecialchars($user['role'])); ?></span>
                    </div>
                    <div class="mb-2">
                        <span class="badge bg-secondary bg-opacity-25 text-secondary px-3 py-2"><i class="bi bi-calendar"></i> Joined <?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
                    </div>
                    <div class="mt-4">
                        <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-outline-primary rounded-pill w-100"><i class="bi bi-pencil"></i> Edit Profile</a>
                    </div>
                </div>
            </div>
            <div class="card shadow border-0 mb-4 rounded-4">
                <div class="card-header bg-gradient text-white rounded-top-4" style="background: linear-gradient(135deg, #43cea2 0%, #185a9d 100%);">
                    <h5 class="mb-0"><i class="bi bi-bar-chart-line"></i> Performance Summary</h5>
                </div>
                <div class="card-body">
                    <?php 
                        // Calculate average ratings
                        $avgCommunication = 0;
                        $avgTeamwork = 0;
                        $avgTechnical = 0;
                        $avgProductivity = 0;
                        $feedbackCount = count($feedbackData);
                        
                        if ($feedbackCount > 0) {
                            foreach ($feedbackData as $feedback) {
                                $avgCommunication += $feedback['communication_rating'];
                                $avgTeamwork += $feedback['teamwork_rating'];
                                $avgTechnical += $feedback['technical_rating'];
                                $avgProductivity += $feedback['productivity_rating'];
                            }
                            
                            $avgCommunication /= $feedbackCount;
                            $avgTeamwork /= $feedbackCount;
                            $avgTechnical /= $feedbackCount;
                            $avgProductivity /= $feedbackCount;
                        }
                    ?>
                    <?php if ($feedbackCount > 0): ?>
                        <div class="row text-center g-2">
                            <div class="col-6 mb-2">
                                <div class="bg-light rounded-3 py-2">
                                    <h6 class="mb-1">Communication</h6>
                                    <h3 class="text-primary mb-0"><?php echo number_format($avgCommunication, 1); ?></h3>
                                    <span class="text-muted small">/5</span>
                                </div>
                            </div>
                            <div class="col-6 mb-2">
                                <div class="bg-light rounded-3 py-2">
                                    <h6 class="mb-1">Teamwork</h6>
                                    <h3 class="text-success mb-0"><?php echo number_format($avgTeamwork, 1); ?></h3>
                                    <span class="text-muted small">/5</span>
                                </div>
                            </div>
                            <div class="col-6 mb-2">
                                <div class="bg-light rounded-3 py-2">
                                    <h6 class="mb-1">Technical</h6>
                                    <h3 class="text-info mb-0"><?php echo number_format($avgTechnical, 1); ?></h3>
                                    <span class="text-muted small">/5</span>
                                </div>
                            </div>
                            <div class="col-6 mb-2">
                                <div class="bg-light rounded-3 py-2">
                                    <h6 class="mb-1">Productivity</h6>
                                    <h3 class="text-warning mb-0"><?php echo number_format($avgProductivity, 1); ?></h3>
                                    <span class="text-muted small">/5</span>
                                </div>
                            </div>
                        </div>
                        <div class="text-center mt-2">
                            <span class="badge bg-success bg-opacity-25 text-success px-3 py-2">Based on <?php echo $feedbackCount; ?> peer reviews</span>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <p class="text-muted">No feedback data available</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <?php if ($selfAssessment): ?>
                <div class="card shadow border-0 mb-4 rounded-4">
                    <div class="card-header bg-gradient text-white rounded-top-4 d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%);">
                        <h5 class="mb-0"><i class="bi bi-person-check"></i> Self Assessment</h5>
                        <span class="badge bg-success">Completed <?php echo date('M d, Y', strtotime($selfAssessment['created_at'])); ?></span>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6>Self-Rating: <span class="badge bg-primary bg-opacity-25 text-primary px-2 py-1"><?php echo $selfAssessment['performance_rating']; ?>/5</span></h6>
                        </div>
                        <div class="mb-3">
                            <h6>Strengths:</h6>
                            <div class="bg-light rounded-3 p-3 mb-2"><?php echo nl2br(htmlspecialchars($selfAssessment['strengths'])); ?></div>
                        </div>
                        <div class="mb-3">
                            <h6>Areas for Improvement:</h6>
                            <div class="bg-light rounded-3 p-3 mb-2"><?php echo nl2br(htmlspecialchars($selfAssessment['weaknesses'])); ?></div>
                        </div>
                        <div class="mb-3">
                            <h6>Professional Goals:</h6>
                            <div class="bg-light rounded-3 p-3 mb-2"><?php echo nl2br(htmlspecialchars($selfAssessment['goals'])); ?></div>
                        </div>
                        <?php if ($selfAssessment['updated_at']): ?>
                            <p class="text-muted small">Last updated: <?php echo date('M d, Y', strtotime($selfAssessment['updated_at'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="card shadow border-0 mb-4 rounded-4">
                    <div class="card-header bg-gradient text-white rounded-top-4" style="background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%);">
                        <h5 class="mb-0"><i class="bi bi-person-check"></i> Self Assessment</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning rounded-pill px-4 py-2">
                            <p class="mb-0">This employee has not completed their self-assessment yet.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <div class="card shadow border-0 rounded-4">
                <div class="card-header bg-gradient text-white rounded-top-4" style="background: linear-gradient(135deg, #43cea2 0%, #185a9d 100%);">
                    <h5 class="mb-0"><i class="bi bi-people"></i> Peer Feedback Received (<?php echo count($feedbackData); ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if (count($feedbackData) > 0): ?>
                        <?php foreach ($feedbackData as $feedback): ?>
                            <div class="border-bottom pb-3 mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0"><i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($feedback['from_name']); ?></h6>
                                    <span class="text-muted small"><i class="bi bi-calendar"></i> <?php echo date('M d, Y', strtotime($feedback['created_at'])); ?></span>
                                </div>
                                <div class="row mb-3 g-2">
                                    <div class="col-md-3 col-6">
                                        <span class="d-block text-muted small">Communication</span>
                                        <span class="badge bg-primary bg-opacity-25 text-primary px-2 py-1"><?php echo $feedback['communication_rating']; ?>/5</span>
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <span class="d-block text-muted small">Teamwork</span>
                                        <span class="badge bg-success bg-opacity-25 text-success px-2 py-1"><?php echo $feedback['teamwork_rating']; ?>/5</span>
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <span class="d-block text-muted small">Technical</span>
                                        <span class="badge bg-info bg-opacity-25 text-info px-2 py-1"><?php echo $feedback['technical_rating']; ?>/5</span>
                                    </div>
                                    <div class="col-md-3 col-6">
                                        <span class="d-block text-muted small">Productivity</span>
                                        <span class="badge bg-warning bg-opacity-25 text-warning px-2 py-1"><?php echo $feedback['productivity_rating']; ?>/5</span>
                                    </div>
                                </div>
                                <?php if (!empty($feedback['comments'])): ?>
                                    <div>
                                        <h6 class="mb-1"><i class="bi bi-chat-left-text"></i> Comments:</h6>
                                        <div class="bg-light rounded-3 p-3 mb-2"><?php echo nl2br(htmlspecialchars($feedback['comments'])); ?></div>
                                    </div>
                                <?php endif; ?>
                                <?php if ($feedback['updated_at']): ?>
                                    <p class="text-muted small">Last updated: <?php echo date('M d, Y', strtotime($feedback['updated_at'])); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <p class="text-muted">No feedback has been provided for this employee yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
