<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    redirect(SITE_URL . '/login.php');
}

// Handle settings update
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $companyName = trim($_POST['company_name']);
    $systemEmail = trim($_POST['system_email']);
    $maintenanceMode = isset($_POST['maintenance_mode']) ? 'on' : 'off';

    $stmt = $conn->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?), (?, ?), (?, ?)");
    $stmt->bind_param(
        "ssssss",
        $k1, $v1, $k2, $v2, $k3, $v3
    );
    $k1 = 'company_name'; $v1 = $companyName;
    $k2 = 'system_email'; $v2 = $systemEmail;
    $k3 = 'maintenance_mode'; $v3 = $maintenanceMode;
    if ($stmt->execute()) {
        $success = 'Settings updated successfully!';
    } else {
        $error = 'Failed to update settings.';
    }
}

// Fetch settings
$companyName = getSetting($conn, 'company_name', 'My Company');
$systemEmail = getSetting($conn, 'system_email', 'admin@example.com');
$maintenanceMode = getSetting($conn, 'maintenance_mode', 'off');

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<div class="container-fluid px-0">
    <div class="row">
        <div class="col-lg-10 ms-lg-auto" id="mainCol">
            <h2 class="mb-3"><i class="bi bi-sliders text-dark"></i> System Settings</h2>
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            <!-- System Preferences (existing) -->
            <div class="card shadow border-0 rounded-4 mb-4">
                <div class="card-header bg-gradient text-white rounded-top-4" style="background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%);">
                    <h5 class="mb-0"><i class="bi bi-gear-fill me-2"></i> System Preferences</h5>
                </div>
                <div class="card-body">
                    <form method="post" class="row g-4">
                        <div class="col-md-6">
                            <label for="company_name" class="form-label fw-semibold">Company Name</label>
                            <input type="text" class="form-control rounded-pill" id="company_name" name="company_name" value="<?php echo htmlspecialchars($companyName); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="system_email" class="form-label fw-semibold">System Email</label>
                            <input type="email" class="form-control rounded-pill" id="system_email" name="system_email" value="<?php echo htmlspecialchars($systemEmail); ?>" required>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode" <?php if ($maintenanceMode === 'on') echo 'checked'; ?> >
                                <label class="form-check-label fw-semibold" for="maintenance_mode">Enable Maintenance Mode</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <button type="submit" name="update_settings" class="btn btn-primary rounded-pill px-4">Save Settings</button>
                        </div>
                    </form>
                </div>
            </div>
            <!-- Evaluation Metrics Section -->
            <div class="card shadow border-0 rounded-4 mb-4">
                <div class="card-header bg-gradient text-white rounded-top-4" style="background: linear-gradient(135deg, #43cea2 0%, #185a9d 100%);">
                    <h5 class="mb-0"><i class="bi bi-list-check me-2"></i> Evaluation Metrics (KPIs)</h5>
                </div>
                <div class="card-body">
                    <form method="post" class="row g-3 align-items-end" action="add_metric.php">
                        <div class="col-md-5">
                            <label for="metric_name" class="form-label">Metric Name</label>
                            <input type="text" class="form-control rounded-pill" id="metric_name" name="metric_name" required>
                        </div>
                        <div class="col-md-5">
                            <label for="metric_desc" class="form-label">Description</label>
                            <input type="text" class="form-control rounded-pill" id="metric_desc" name="metric_desc">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-success rounded-pill w-100">Add Metric</button>
                        </div>
                    </form>
                    <hr>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Metric</th>
                                    <th>Description</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $metrics = $conn->query("SELECT * FROM evaluation_metrics ORDER BY id DESC");
                                if ($metrics && $metrics->num_rows > 0):
                                    while ($m = $metrics->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($m['name']); ?></td>
                                        <td><?php echo htmlspecialchars($m['description']); ?></td>
                                        <td>
                                            <a href="edit_metric.php?id=<?php echo $m['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill">Edit</a>
                                            <a href="delete_metric.php?id=<?php echo $m['id']; ?>" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Delete this metric?')">Delete</a>
                                        </td>
                                    </tr>
                                <?php endwhile; else: ?>
                                    <tr><td colspan="3" class="text-center text-muted">No metrics defined yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- More sections for templates, rating scales, feedback source weights, and visibility controls would go here... -->
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
