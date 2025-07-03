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

// Handle employee deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $employeeId = (int)$_GET['delete'];
    
    // Don't allow admins to delete themselves
    if ($employeeId == $_SESSION['user_id']) {
        $error = 'You cannot delete your own account.';
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'employee'");
        $stmt->bind_param("i", $employeeId);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $success = 'Employee deleted successfully.';
        } else {
            $error = 'Failed to delete employee or employee not found.';
        }
    }
}

// Get all employees
$employees = [];
$query = "SELECT * FROM users WHERE role = 'employee' ORDER BY name";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
}

// Get department statistics
$departments = [];
$deptQuery = "SELECT department, COUNT(*) as count FROM users WHERE role = 'employee' GROUP BY department ORDER BY count DESC";
$deptResult = $conn->query($deptQuery);

if ($deptResult && $deptResult->num_rows > 0) {
    while ($row = $deptResult->fetch_assoc()) {
        $departments[$row['department']] = $row['count'];
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-2 d-none d-lg-block">
            <!-- Sidebar is included above -->
        </div>
        <div class="col-lg-10 ms-lg-auto">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold text-primary"><i class="bi bi-people-fill me-2"></i>Manage Employees</h2>
                <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
            </div>

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

            <div class="row mb-4 g-4">
                <div class="col-md-6">
                    <div class="card shadow border-0 bg-gradient" style="background: linear-gradient(135deg, #e0eafc 0%, #f8fafc 100%);">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-bar-chart-fill me-2"></i> Employee Statistics</h5>
                        </div>
                        <div class="card-body">
                            <p class="fw-bold text-primary"><i class="bi bi-people-fill me-1"></i> Total Employees: <?php echo count($employees); ?></p>
                            <?php if (!empty($departments)): ?>
                                <h6 class="fw-semibold">Employees by Department:</h6>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($departments as $dept => $count): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span><i class="bi bi-diagram-3-fill text-info me-2"></i><?php echo htmlspecialchars($dept); ?></span>
                                            <span class="badge bg-info bg-opacity-25 text-info px-3 py-2"><?php echo $count; ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card shadow border-0 bg-gradient" style="background: linear-gradient(135deg, #e3fcec 0%, #e0f7fa 100%);">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="bi bi-lightbulb-fill me-2"></i> Features & Actions</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-3">
                                <li class="mb-2"><i class="bi bi-person-plus-fill text-success me-2"></i> Add new employees via registration page</li>
                                <li class="mb-2"><i class="bi bi-arrow-up-circle-fill text-primary me-2"></i> Promote employee to Team Lead from dashboard</li>
                                <li class="mb-2"><i class="bi bi-bar-chart-line-fill text-info me-2"></i> View employee performance and feedback</li>
                                <li class="mb-2"><i class="bi bi-trash-fill text-danger me-2"></i> Delete employees (except yourself)</li>
                            </ul>
                            <a href="register_employee.php" class="btn btn-success rounded-pill px-4"><i class="bi bi-person-plus"></i> Register Employee</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow border-0 mb-4">
                <div class="card-header bg-primary text-white d-flex align-items-center justify-content-between">
                    <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i> Employee List</h5>
                    <span class="badge bg-light text-primary">Total: <?php echo count($employees); ?></span>
                </div>
                <div class="card-body">
                    <?php if (count($employees) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Department</th>
                                        <th>Joined</th>
                                        <th>Self-Assessment</th>
                                        <th>Feedback Given</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($employees as $employee): ?>
                                        <?php 
                                            // Check if self-assessment exists
                                            $saStmt = $conn->prepare("SELECT * FROM self_assessment WHERE user_id = ?");
                                            $saStmt->bind_param("i", $employee['id']);
                                            $saStmt->execute();
                                            $saResult = $saStmt->get_result();
                                            $hasSelfAssessment = $saResult->num_rows > 0;
                                            
                                            // Count feedback given by employee
                                            $fbStmt = $conn->prepare("SELECT COUNT(*) as count FROM peer_feedback WHERE from_user_id = ?");
                                            $fbStmt->bind_param("i", $employee['id']);
                                            $fbStmt->execute();
                                            $fbResult = $fbStmt->get_result()->fetch_assoc();
                                            $feedbackCount = $fbResult['count'];
                                        ?>
                                        <tr>
                                            <td class="fw-semibold text-primary"><?php echo htmlspecialchars($employee['name']); ?></td>
                                            <td><?php echo htmlspecialchars($employee['email']); ?></td>
                                            <td><span class="badge bg-info bg-opacity-25 text-info px-3 py-2"><?php echo htmlspecialchars($employee['department']); ?></span></td>
                                            <td><?php echo date('M d, Y', strtotime($employee['created_at'])); ?></td>
                                            <td>
                                                <?php if ($hasSelfAssessment): ?>
                                                    <span class="badge bg-success">Completed</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $feedbackCount; ?></td>
                                            <td class="d-flex gap-1">
                                                <a href="edit_user.php?id=<?php echo $employee['id']; ?>" class="btn btn-sm btn-outline-primary rounded-pill"><i class="bi bi-pencil"></i> Edit</a>
                                                <a href="employees.php?delete=<?php echo $employee['id']; ?>" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Are you sure you want to delete this employee?')"><i class="bi bi-trash"></i> Delete</a>
                                                <a href="view_employee.php?id=<?php echo $employee['id']; ?>" class="btn btn-sm btn-outline-info rounded-pill"><i class="bi bi-eye"></i> View</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <p>No employees found. Have them register through the registration page.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
