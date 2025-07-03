<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    redirect(SITE_URL . '/login.php');
}

// Fetch all team leads
$teamLeads = [];
$query = "SELECT * FROM users WHERE role = 'teamlead' ORDER BY name";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $teamLeads[] = $row;
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0"><i class="bi bi-person-badge-fill text-success me-2"></i>Team Leads</h2>
                <a href="index.php#teamleads" class="btn btn-primary rounded-pill"><i class="bi bi-plus-circle me-1"></i> Add Team Lead</a>
            </div>
            <div class="card shadow border-0 rounded-4">
                <div class="card-body">
                    <?php if (count($teamLeads) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Department</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($teamLeads as $lead): ?>
                                        <tr>
                                            <td class="fw-semibold text-primary"><?php echo htmlspecialchars($lead['name']); ?></td>
                                            <td><?php echo htmlspecialchars($lead['email']); ?></td>
                                            <td><span class="badge bg-info bg-opacity-25 text-info px-3 py-2"><?php echo htmlspecialchars($lead['department']); ?></span></td>
                                            <td><?php echo date('M d, Y', strtotime($lead['created_at'])); ?></td>
                                            <td>
                                                <a href="edit_user.php?id=<?php echo $lead['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill"><i class="bi bi-pencil"></i> Edit</a>
                                                <a href="delete_user.php?id=<?php echo $lead['id']; ?>" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Are you sure you want to delete this team lead?')"><i class="bi bi-trash"></i> Delete</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <p>No team leads have been created yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
