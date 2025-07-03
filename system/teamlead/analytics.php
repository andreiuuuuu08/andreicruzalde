<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'teamlead') {
    redirect(SITE_URL . '/login.php');
}

$userId = $_SESSION['user_id'];
$userInfo = getUserById($userId);

// Initialize export format from GET param if set, else null
$exportFormat = isset($_GET['export']) ? strtolower($_GET['export']) : null;

// Team members
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

// Team performance analytics (average ratings)
$performance = [
    'communication' => '-',
    'teamwork' => '-',
    'technical' => '-',
    'productivity' => '-',
    'feedback_count' => 0
];
$chartData = [
    'labels' => [],
    'communication' => [],
    'teamwork' => [],
    'technical' => [],
    'productivity' => []
];
if ($conn->query("SHOW TABLES LIKE 'peer_feedback'")->num_rows > 0) {
    $query = "SELECT AVG(communication_rating) as avg_communication, AVG(teamwork_rating) as avg_teamwork, AVG(technical_rating) as avg_technical, AVG(productivity_rating) as avg_productivity, COUNT(*) as feedback_count FROM peer_feedback f JOIN users u ON f.to_user_id = u.id WHERE u.department = ? AND u.role = 'employee'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $userInfo['department']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $row = $result->fetch_assoc()) {
        $performance = [
            'communication' => $row['avg_communication'] ? round($row['avg_communication'], 2) : '-',
            'teamwork' => $row['avg_teamwork'] ? round($row['avg_teamwork'], 2) : '-',
            'technical' => $row['avg_technical'] ? round($row['avg_technical'], 2) : '-',
            'productivity' => $row['avg_productivity'] ? round($row['avg_productivity'], 2) : '-',
            'feedback_count' => $row['feedback_count']
        ];
    }
    // Chart data: last N months
    $filterMonths = isset($_GET['months']) ? max(1, min(24, (int)$_GET['months'])) : 6;
    $chartType = isset($_GET['chartType']) ? $_GET['chartType'] : 'bar';
    $chartQ = "SELECT DATE_FORMAT(f.created_at, '%Y-%m') as month, AVG(communication_rating) as comm, AVG(teamwork_rating) as team, AVG(technical_rating) as tech, AVG(productivity_rating) as prod FROM peer_feedback f JOIN users u ON f.to_user_id = u.id WHERE u.department = ? AND u.role = 'employee' GROUP BY month ORDER BY month DESC LIMIT ?";
    $stmt = $conn->prepare($chartQ);
    $stmt->bind_param("si", $userInfo['department'], $filterMonths);
    $stmt->execute();
    $result = $stmt->get_result();
    $months = [];
    $comm = $team = $tech = $prod = [];
    while ($row = $result->fetch_assoc()) {
        $months[] = $row['month'];
        $comm[] = $row['comm'] ? round($row['comm'],2) : 0;
        $team[] = $row['team'] ? round($row['team'],2) : 0;
        $tech[] = $row['tech'] ? round($row['tech'],2) : 0;
        $prod[] = $row['prod'] ? round($row['prod'],2) : 0;
    }
    $chartData['labels'] = array_reverse($months);
    $chartData['communication'] = array_reverse($comm);
    $chartData['teamwork'] = array_reverse($team);
    $chartData['technical'] = array_reverse($tech);
    $chartData['productivity'] = array_reverse($prod);
    // Radar chart data structure
    if ($chartType === 'radar') {
        $latestIdx = count($chartData['labels']) - 1;
        $radarLabels = ['Communication', 'Teamwork', 'Technical', 'Productivity'];
        $radarData = [
            $chartData['communication'][$latestIdx] ?? 0,
            $chartData['teamwork'][$latestIdx] ?? 0,
            $chartData['technical'][$latestIdx] ?? 0,
            $chartData['productivity'][$latestIdx] ?? 0
        ];
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<div class="container py-4">
    <h2 class="mb-3"><i class="bi bi-bar-chart-line-fill text-success me-2"></i>Team Analytics</h2>
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card shadow border-0 rounded-4 h-100">
                <div class="card-body text-center">
                    <div class="display-6 text-primary mb-2"><i class="bi bi-people-fill"></i></div>
                    <div class="fw-bold">Team Members</div>
                    <div class="fs-3 text-dark"><?php echo count($teamMembers); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow border-0 rounded-4 h-100">
                <div class="card-body text-center">
                    <div class="display-6 text-info mb-2"><i class="bi bi-chat-dots-fill"></i></div>
                    <div class="fw-bold">Feedback Given</div>
                    <div class="fs-3 text-dark"><?php echo $performance['feedback_count']; ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow border-0 rounded-4 h-100">
                <div class="card-body">
                    <h5 class="fw-bold mb-3"><i class="bi bi-bar-chart-fill text-success me-2"></i>Average Ratings</h5>
                    <div class="row text-center">
                        <div class="col-6 col-md-3">
                            <div class="text-muted small">Communication</div>
                            <div class="fs-4 text-primary"><?php echo $performance['communication']; ?></div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-muted small">Teamwork</div>
                            <div class="fs-4 text-success"><?php echo $performance['teamwork']; ?></div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-muted small">Technical</div>
                            <div class="fs-4 text-info"><?php echo $performance['technical']; ?></div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-muted small">Productivity</div>
                            <div class="fs-4 text-warning"><?php echo $performance['productivity']; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card shadow border-0 rounded-4 mb-4">
        <div class="card-header bg-gradient text-white rounded-top-4" style="background: linear-gradient(135deg, #43cea2 0%, #185a9d 100%);">
            <h5 class="mb-0"><i class="bi bi-graph-up-arrow me-2"></i>Team Ratings Trend (Last <?php echo $filterMonths; ?> Months)</h5>
            <form method="get" class="d-inline ms-3">
                <label for="months" class="form-label mb-0 me-1">Months:</label>
                <select name="months" id="months" class="form-select form-select-sm d-inline w-auto" onchange="this.form.submit()">
                    <?php foreach ([3,6,12,18,24] as $m): ?>
                        <option value="<?php echo $m; ?>" <?php if ($filterMonths==$m) echo 'selected'; ?>><?php echo $m; ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="chartType" class="form-label mb-0 ms-2 me-1">Chart:</label>
                <select name="chartType" id="chartType" class="form-select form-select-sm d-inline w-auto" onchange="this.form.submit()">
                    <option value="line" <?php if($chartType=='line') echo 'selected'; ?>>Line</option>
                    <option value="bar" <?php if($chartType=='bar') echo 'selected'; ?>>Bar</option>
                    <option value="radar" <?php if($chartType=='radar') echo 'selected'; ?>>Radar</option>
                </select>
            </form>
        </div>
        <div class="card-body">
            <canvas id="ratingsChart" height="120"></canvas>
        </div>
    </div>
    <a href="analytics.php?export=csv&months=<?php echo $filterMonths; ?>" class="btn btn-outline-secondary rounded-pill mb-2"><i class="bi bi-download"></i> Export CSV</a>
    <a href="analytics.php?export=json&months=<?php echo $filterMonths; ?>" class="btn btn-outline-secondary rounded-pill mb-2"><i class="bi bi-download"></i> Export JSON</a>
    <a href="analytics.php?export=xlsx&months=<?php echo $filterMonths; ?>" class="btn btn-outline-secondary rounded-pill mb-2"><i class="bi bi-download"></i> Export Excel</a>
    <a href="index.php" class="btn btn-outline-secondary rounded-pill mb-2"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
</div>
<?php include '../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('ratingsChart').getContext('2d');
<?php if ($chartType === 'radar'): ?>
const ratingsChart = new Chart(ctx, {
    type: 'radar',
    data: {
        labels: <?php echo json_encode($radarLabels); ?>,
        datasets: [
            {
                label: 'Latest Team Metrics',
                data: <?php echo json_encode($radarData); ?>,
                backgroundColor: 'rgba(67,206,162,0.2)',
                borderColor: '#43cea2',
                pointBackgroundColor: '#43cea2',
                pointBorderColor: '#fff',
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: '#43cea2',
                borderWidth: 2
            }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
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
<?php else: ?>
const ratingsChart = new Chart(ctx, {
    type: '<?php echo $chartType; ?>',
    data: {
        labels: <?php echo json_encode($chartData['labels']); ?>,
        datasets: [
            {
                label: 'Communication',
                data: <?php echo json_encode($chartData['communication']); ?>,
                borderColor: '#0072ff',
                backgroundColor: 'rgba(0,114,255,0.08)',
                tension: 0.3
            },
            {
                label: 'Teamwork',
                data: <?php echo json_encode($chartData['teamwork']); ?>,
                borderColor: '#43cea2',
                backgroundColor: 'rgba(67,206,162,0.08)',
                tension: 0.3
            },
            {
                label: 'Technical',
                data: <?php echo json_encode($chartData['technical']); ?>,
                borderColor: '#185a9d',
                backgroundColor: 'rgba(24,90,157,0.08)',
                tension: 0.3
            },
            {
                label: 'Productivity',
                data: <?php echo json_encode($chartData['productivity']); ?>,
                borderColor: '#ffd200',
                backgroundColor: 'rgba(255,210,0,0.08)',
                tension: 0.3
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            title: { display: false }
        },
        scales: {
            y: { beginAtZero: true, max: 5 }
        }
    }
});
<?php endif; ?>
</script>
<?php
// Export CSV/JSON/XLSX
if ($exportFormat === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename=team_analytics.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Month', 'Communication', 'Teamwork', 'Technical', 'Productivity']);
    for ($i = 0; $i < count($chartData['labels']); $i++) {
        fputcsv($out, [
            $chartData['labels'][$i] ?? '',
            $chartData['communication'][$i] ?? '',
            $chartData['teamwork'][$i] ?? '',
            $chartData['technical'][$i] ?? '',
            $chartData['productivity'][$i] ?? ''
        ]);
    }
    fclose($out);
    exit;
} elseif ($exportFormat === 'json') {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename=team_analytics.json');
    echo json_encode($chartData, JSON_PRETTY_PRINT);
    exit;
} elseif ($exportFormat === 'xlsx') {
    require_once '../includes/xlsxwriter.class.php';
    $filename = 'team_analytics.xlsx';
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    $writer = new XLSXWriter();
    $writer->writeSheetHeader('Sheet1', ['Month'=>'string','Communication'=>'string','Teamwork'=>'string','Technical'=>'string','Productivity'=>'string']);
    for ($i = 0; $i < count($chartData['labels']); $i++) {
        $writer->writeSheetRow('Sheet1', [
            $chartData['labels'][$i] ?? '',
            $chartData['communication'][$i] ?? '',
            $chartData['teamwork'][$i] ?? '',
            $chartData['technical'][$i] ?? '',
            $chartData['productivity'][$i] ?? ''
        ]);
    }
    $writer->writeToStdOut();
    exit;
}
?>
