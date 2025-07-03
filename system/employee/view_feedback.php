<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect(SITE_URL . '/login.php');
}

$userId = $_SESSION['user_id'];

// Include header and sidebar
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<h2 class="mb-3"><i class="bi bi-eye-fill text-primary me-2"></i>Feedback Received</h2>
<p class="lead">View feedback from your colleagues to help you improve.</p>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card shadow border-0 rounded-4 mb-4">
            <div class="card-header bg-gradient text-white rounded-top-4" style="background: linear-gradient(135deg, #43cea2 0%, #185a9d 100%);">
                <h5 class="mb-0"><i class="bi bi-bar-chart-fill me-2"></i>Feedback Summary</h5>
            </div>
            <div class="card-body">
                <?php
                // Get average ratings
                $stmt = $conn->prepare("
                    SELECT 
                        AVG(communication_rating) as avg_communication,
                        AVG(teamwork_rating) as avg_teamwork,
                        AVG(technical_rating) as avg_technical,
                        AVG(productivity_rating) as avg_productivity,
                        COUNT(*) as total_feedback
                    FROM peer_feedback 
                    WHERE to_user_id = ?
                ");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $summary = $stmt->get_result()->fetch_assoc();
                
                if ($summary && $summary['total_feedback'] > 0):
                ?>
                    <div class="row">
                        <div class="col-md-3 text-center mb-3">
                            <h5>Communication</h5>
                            <div class="display-4"><?php echo number_format($summary['avg_communication'], 1); ?></div>
                            <p class="text-muted">out of 5</p>
                        </div>
                        <div class="col-md-3 text-center mb-3">
                            <h5>Teamwork</h5>
                            <div class="display-4"><?php echo number_format($summary['avg_teamwork'], 1); ?></div>
                            <p class="text-muted">out of 5</p>
                        </div>
                        <div class="col-md-3 text-center mb-3">
                            <h5>Technical Skills</h5>
                            <div class="display-4"><?php echo number_format($summary['avg_technical'], 1); ?></div>
                            <p class="text-muted">out of 5</p>
                        </div>
                        <div class="col-md-3 text-center mb-3">
                            <h5>Productivity</h5>
                            <div class="display-4"><?php echo number_format($summary['avg_productivity'], 1); ?></div>
                            <p class="text-muted">out of 5</p>
                        </div>
                    </div>
                    <p class="text-center">Based on feedback from <?php echo $summary['total_feedback']; ?> colleague(s).</p>
                <?php else: ?>
                    <div class="text-center py-4">
                        <p>No feedback received yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow border-0 rounded-4 mb-4">
            <div class="card-header bg-gradient text-white rounded-top-4" style="background: linear-gradient(135deg, #ffd200 0%, #ff8800 100%);">
                <h5 class="mb-0"><i class="bi bi-graph-up-arrow me-2"></i>Feedback Analytics</h5>
            </div>
            <div class="card-body">
                <canvas id="feedbackChart" height="120"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="card shadow border-0 rounded-4 mb-4">
    <div class="card-header bg-gradient text-white rounded-top-4" style="background: linear-gradient(135deg, #0072ff 0%, #43cea2 100%);">
        <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Detailed Feedback</h5>
    </div>
    <div class="card-body">
        <?php
        $stmt = $conn->prepare("
            SELECT f.*, u.name 
            FROM peer_feedback f 
            JOIN users u ON f.from_user_id = u.id 
            WHERE f.to_user_id = ?
            ORDER BY f.created_at DESC
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $result->num_rows > 0):
            while ($feedback = $result->fetch_assoc()):
                if ($feedback !== null): // Added null check
        ?>
            <div class="border-bottom pb-3 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5>Feedback from <?php echo $feedback['name']; ?></h5>
                    <small class="text-muted"><?php echo date('M d, Y', strtotime($feedback['created_at'])); ?></small>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-3">
                        <strong>Communication:</strong> <?php echo $feedback['communication_rating']; ?>/5
                    </div>
                    <div class="col-md-3">
                        <strong>Teamwork:</strong> <?php echo $feedback['teamwork_rating']; ?>/5
                    </div>
                    <div class="col-md-3">
                        <strong>Technical Skills:</strong> <?php echo $feedback['technical_rating']; ?>/5
                    </div>
                    <div class="col-md-3">
                        <strong>Productivity:</strong> <?php echo $feedback['productivity_rating']; ?>/5
                    </div>
                </div>
                
                <?php if (!empty($feedback['comments'])): ?>
                    <div>
                        <strong>Comments:</strong>
                        <p><?php echo nl2br($feedback['comments']); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        <?php 
                endif;
            endwhile; 
        else: 
        ?>
            <div class="text-center py-4">
                <p>No feedback received yet.</p>
                <p>As your colleagues provide feedback, it will appear here.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
<?php
// Prepare data for feedback analytics chart
$labels = ['Communication', 'Teamwork', 'Technical', 'Productivity'];
$summary = isset($summary) ? $summary : [
    'avg_communication' => 0,
    'avg_teamwork' => 0,
    'avg_technical' => 0,
    'avg_productivity' => 0
];
$data = [
    round($summary['avg_communication'] ?? 0, 2),
    round($summary['avg_teamwork'] ?? 0, 2),
    round($summary['avg_technical'] ?? 0, 2),
    round($summary['avg_productivity'] ?? 0, 2)
];
?>
const ctx = document.getElementById('feedbackChart').getContext('2d');
const feedbackChart = new Chart(ctx, {
    type: 'radar',
    data: {
        labels: <?php echo json_encode($labels); ?>,
        datasets: [{
            label: 'Average Rating',
            data: <?php echo json_encode($data); ?>,
            backgroundColor: 'rgba(67,206,162,0.2)',
            borderColor: '#43cea2',
            pointBackgroundColor: '#43cea2',
            pointBorderColor: '#fff',
            borderWidth: 2
        }]
    },
    options: {
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
    }
});
</script>

<?php include '../includes/footer.php'; ?>
