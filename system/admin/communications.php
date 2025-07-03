<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect(SITE_URL . '/login.php');
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'];

// Handle sending a message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['recipient_id']) && !empty($_POST['message'])) {
    $recipientId = (int)$_POST['recipient_id'];
    $message = sanitize($_POST['message']);
    $stmt = $conn->prepare("INSERT INTO communications (sender_id, recipient_id, message) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $userId, $recipientId, $message);
    $stmt->execute();
}

// Fetch users for messaging (role-based)
$users = [];
if ($userRole === 'admin') {
    $result = $conn->query("SELECT id, name, role FROM users WHERE id != $userId");
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
} elseif ($userRole === 'teamlead') {
    $result = $conn->query("SELECT id, name, role FROM users WHERE (role = 'employee' OR role = 'admin') AND id != $userId");
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
} else {
    $result = $conn->query("SELECT id, name, role FROM users WHERE (role = 'teamlead' OR role = 'admin') AND id != $userId");
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

// Fetch messages (sent or received)
$messages = [];
$stmt = $conn->prepare("SELECT c.*, u1.name as sender_name, u2.name as recipient_name FROM communications c JOIN users u1 ON c.sender_id = u1.id JOIN users u2 ON c.recipient_id = u2.id WHERE c.sender_id = ? OR c.recipient_id = ? ORDER BY c.created_at DESC LIMIT 20");
$stmt->bind_param("ii", $userId, $userId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<div class="container py-4">
    <h2 class="mb-3"><i class="bi bi-envelope-fill text-primary me-2"></i>Communications</h2>
    <div class="row g-4">
        <div class="col-md-5">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, #43cea2 0%, #185a9d 100%);">
                    <h6 class="mb-0"><i class="bi bi-send me-2"></i>Send a Message</h6>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label for="recipient_id" class="form-label">To:</label>
                            <select class="form-select" id="recipient_id" name="recipient_id" required>
                                <option value="">Select recipient</option>
                                <?php foreach ($users as $u): ?>
                                    <option value="<?php echo $u['id']; ?>"><?php echo htmlspecialchars($u['name']) . ' (' . ucfirst($u['role']) . ')'; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Message:</label>
                            <textarea class="form-control" id="message" name="message" rows="3" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-send"></i> Send</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-7">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, #0072ff 0%, #43cea2 100%);">
                    <h6 class="mb-0"><i class="bi bi-chat-dots-fill me-2"></i>Recent Messages</h6>
                </div>
                <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                    <?php if (count($messages) > 0): ?>
                        <?php foreach ($messages as $msg): ?>
                            <div class="border-bottom pb-2 mb-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span><strong><?php echo htmlspecialchars($msg['sender_name']); ?></strong> <i class="bi bi-arrow-right"></i> <strong><?php echo htmlspecialchars($msg['recipient_name']); ?></strong></span>
                                    <small class="text-muted"><?php echo date('M d, Y H:i', strtotime($msg['created_at'])); ?></small>
                                </div>
                                <div class="mt-1">"<?php echo nl2br(htmlspecialchars($msg['message'])); ?>"</div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-muted">No messages yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
