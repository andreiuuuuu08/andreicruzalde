<?php
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
require_once '../includes/functions.php';

// Handle new announcement submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message'])) {
    $msg = sanitize($_POST['message']);
    global $conn;
    $created_by = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    $stmt = $conn->prepare("INSERT INTO announcements (message, created_by, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sis", $msg, $created_by, $role);
    $stmt->execute();
    $stmt->close();
    header("Location: announcements.php");
    exit;
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    global $conn;
    $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: announcements.php");
    exit;
}

// Fetch all announcements
$announcements = [];
$result = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $announcements[] = $row;
    }
}
?>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-10 ms-lg-auto">
            <div class="card shadow border-0 mb-4">
                <div class="card-header bg-primary text-white d-flex align-items-center justify-content-between">
                    <h4 class="mb-0"><i class="bi bi-megaphone-fill me-2"></i> System Announcements</h4>
                </div>
                <div class="card-body">
                    <form method="post" class="mb-4">
                        <div class="input-group">
                            <input type="text" name="message" class="form-control form-control-lg" placeholder="Enter announcement..." required>
                            <button class="btn btn-primary" type="submit"><i class="bi bi-plus-circle"></i> Add</button>
                        </div>
                    </form>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($announcements as $a): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-bell-fill text-warning me-2"></i><?php echo htmlspecialchars($a['message']); ?> <small class="text-muted ms-2"><?php echo $a['created_at']; ?></small></span>
                                <a href="?delete=<?php echo $a['id']; ?>" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></a>
                            </li>
                        <?php endforeach; ?>
                        <?php if (empty($announcements)): ?>
                            <li class="list-group-item text-muted">No announcements yet.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>
