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

// Handle department creation
if (isset($_POST['add_department']) && !empty($_POST['department_name'])) {
    $departmentName = sanitize($_POST['department_name']);
    
    // Check if department already exists
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE department = ?");
    $checkStmt->bind_param("s", $departmentName);
    $checkStmt->execute();
    $result = $checkStmt->get_result()->fetch_assoc();
    
    if ($result['count'] > 0) {
        $error = 'Department already exists.';
    } else {
        // Create a placeholder entry for the department
        $adminId = $_SESSION['user_id'];
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, department) VALUES ('Department Placeholder', ?, 'placeholder', 'employee', ?)");
        $placeholderEmail = 'dept_' . strtolower(str_replace(' ', '_', $departmentName)) . '@placeholder.com';
        $stmt->bind_param("ss", $placeholderEmail, $departmentName);
        
        if ($stmt->execute()) {
            $success = 'Department added successfully!';
        } else {
            $error = 'Error adding department: ' . $conn->error;
        }
    }
}

// Handle department rename
if (isset($_POST['rename_department'])) {
    $oldName = sanitize($_POST['old_name']);
    $newName = sanitize($_POST['new_name']);
    
    if (empty($newName)) {
        $error = 'New department name cannot be empty.';
    } else {
        $stmt = $conn->prepare("UPDATE users SET department = ? WHERE department = ?");
        $stmt->bind_param("ss", $newName, $oldName);
        
        if ($stmt->execute()) {
            $success = 'Department renamed successfully!';
        } else {
            $error = 'Error renaming department: ' . $conn->error;
        }
    }
}

// Handle department deletion (with safeguards)
if (isset($_POST['delete_department'])) {
    $departmentName = sanitize($_POST['department_name']);
    $transferTo = sanitize($_POST['transfer_to']);
    
    // First check if employees are in this department
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE department = ? AND name != 'Department Placeholder'");
    $checkStmt->bind_param("s", $departmentName);
    $checkStmt->execute();
    $result = $checkStmt->get_result()->fetch_assoc();
    
    if ($result['count'] > 0 && empty($transferTo)) {
        $error = 'You must select a department to transfer employees to before deleting this department.';
    } else {
        // Begin transaction to ensure data integrity
        $conn->begin_transaction();
        
        try {
            // Transfer employees if needed
            if ($result['count'] > 0) {
                $transferStmt = $conn->prepare("UPDATE users SET department = ? WHERE department = ? AND name != 'Department Placeholder'");
                $transferStmt->bind_param("ss", $transferTo, $departmentName);
                $transferStmt->execute();
            }
            
            // Delete department placeholder
            $deleteStmt = $conn->prepare("DELETE FROM users WHERE department = ? AND name = 'Department Placeholder'");
            $deleteStmt->bind_param("s", $departmentName);
            $deleteStmt->execute();
            
            // Commit the transaction
            $conn->commit();
            $success = 'Department deleted successfully!';
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $error = 'Error deleting department: ' . $e->getMessage();
        }
    }
}

// Get all departments with employee counts
$departments = [];
$query = "
    SELECT department, COUNT(*) as employee_count,
           SUM(CASE WHEN role = 'teamlead' THEN 1 ELSE 0 END) as teamlead_count,
           SUM(CASE WHEN role = 'employee' THEN 1 ELSE 0 END) as employees_count
    FROM users
    WHERE department IS NOT NULL AND department != '' AND name != 'Department Placeholder'
    GROUP BY department
    ORDER BY department
";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }
}

// Get additional department placeholders that might exist without employees
$placeholdersQuery = "
    SELECT DISTINCT department
    FROM users
    WHERE name = 'Department Placeholder' AND department NOT IN (
        SELECT DISTINCT department FROM users WHERE name != 'Department Placeholder'
    )
";
$placeholdersResult = $conn->query($placeholdersQuery);

if ($placeholdersResult && $placeholdersResult->num_rows > 0) {
    while ($row = $placeholdersResult->fetch_assoc()) {
        $departments[] = [
            'department' => $row['department'],
            'employee_count' => 0,
            'teamlead_count' => 0,
            'employees_count' => 0
        ];
    }
}

include '../includes/header.php';
?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0"><i class="bi bi-diagram-3-fill text-info me-2"></i>Departments</h2>
        <a href="index.php" class="btn btn-outline-secondary rounded-pill"><i class="bi bi-arrow-left"></i> Dashboard</a>
    </div>
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card shadow border-0 rounded-4 h-100">
                <div class="card-body text-center">
                    <div class="display-6 text-info mb-2"><i class="bi bi-diagram-3-fill"></i></div>
                    <div class="fw-bold">Total Departments</div>
                    <div class="fs-3 text-dark"><?php echo count($departments); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow border-0 rounded-4 h-100">
                <div class="card-body text-center">
                    <div class="display-6 text-primary mb-2"><i class="bi bi-people-fill"></i></div>
                    <div class="fw-bold">Total Employees</div>
                    <div class="fs-3 text-dark"><?php echo array_sum(array_column($departments, 'employees_count')); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow border-0 rounded-4 h-100">
                <div class="card-body text-center">
                    <div class="display-6 text-success mb-2"><i class="bi bi-person-badge-fill"></i></div>
                    <div class="fw-bold">Total Team Leads</div>
                    <div class="fs-3 text-dark"><?php echo array_sum(array_column($departments, 'teamlead_count')); ?></div>
                </div>
            </div>
        </div>
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
            <div class="card shadow border-0 rounded-4">
                <div class="card-header bg-info text-white rounded-top-4">
                    <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Add New Department</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label for="department_name" class="form-label">Department Name</label>
                            <input type="text" class="form-control rounded-pill" id="department_name" name="department_name" required>
                        </div>
                        <button type="submit" name="add_department" class="btn btn-info rounded-pill px-4">Add Department</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow border-0 rounded-4">
                <div class="card-header bg-gradient text-white rounded-top-4" style="background: linear-gradient(135deg, #43cea2 0%, #185a9d 100%);">
                    <h5 class="mb-0"><i class="bi bi-bar-chart-line-fill me-2"></i>Department Statistics</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($departments as $dept): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="fw-semibold text-info"><i class="bi bi-diagram-3"></i> <?php echo htmlspecialchars($dept['department']); ?></span>
                                <span>
                                    <span class="badge bg-primary rounded-pill" title="Employees"><i class="bi bi-people-fill"></i> <?php echo $dept['employees_count']; ?></span>
                                    <?php if ($dept['teamlead_count'] > 0): ?>
                                        <span class="badge bg-success rounded-pill" title="Team Leads"><i class="bi bi-person-badge-fill"></i> <?php echo $dept['teamlead_count']; ?></span>
                                    <?php endif; ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <div class="card shadow border-0 rounded-4 mb-4">
        <div class="card-header bg-info text-white rounded-top-4 d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-diagram-3-fill me-2"></i>Department Management</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Department Name</th>
                            <th>Employees</th>
                            <th>Team Leads</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($departments as $dept): ?>
                            <tr>
                                <td class="fw-semibold text-info"><i class="bi bi-diagram-3"></i> <?php echo htmlspecialchars($dept['department']); ?></td>
                                <td><span class="badge bg-primary"><i class="bi bi-people-fill"></i> <?php echo $dept['employees_count']; ?></span></td>
                                <td><span class="badge bg-success"><i class="bi bi-person-badge-fill"></i> <?php echo $dept['teamlead_count']; ?></span></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill me-1" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#renameDeptModal"
                                        data-dept-name="<?php echo htmlspecialchars($dept['department']); ?>"
                                        title="Rename Department">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#deleteDeptModal"
                                        data-dept-name="<?php echo htmlspecialchars($dept['department']); ?>"
                                        data-has-employees="<?php echo $dept['employee_count'] > 0 ? 'true' : 'false'; ?>"
                                        title="Delete Department">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Rename Department Modal -->
    <div class="modal fade" id="renameDeptModal" tabindex="-1" aria-labelledby="renameDeptModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content rounded-4">
                <div class="modal-header bg-primary text-white rounded-top-4">
                    <h5 class="modal-title" id="renameDeptModalLabel"><i class="bi bi-pencil me-2"></i>Rename Department</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post">
                        <input type="hidden" name="old_name" id="old_dept_name">
                        <div class="mb-3">
                            <label for="new_name" class="form-label">New Department Name</label>
                            <input type="text" class="form-control rounded-pill" id="new_name" name="new_name" required>
                        </div>
                        <div class="alert alert-warning">
                            <p><i class="bi bi-exclamation-triangle"></i> Renaming a department will update all users assigned to this department.</p>
                        </div>
                        <div class="d-grid">
                            <button type="submit" name="rename_department" class="btn btn-primary rounded-pill">Rename Department</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Delete Department Modal -->
    <div class="modal fade" id="deleteDeptModal" tabindex="-1" aria-labelledby="deleteDeptModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content rounded-4">
                <div class="modal-header bg-danger text-white rounded-top-4">
                    <h5 class="modal-title" id="deleteDeptModalLabel"><i class="bi bi-trash me-2"></i>Delete Department</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="post">
                        <input type="hidden" name="department_name" id="delete_dept_name">
                        <div id="transfer_container" style="display:none;">
                            <div class="mb-3">
                                <label for="transfer_to" class="form-label">Transfer Employees To</label>
                                <select class="form-select rounded-pill" id="transfer_to" name="transfer_to">
                                    <option value="">-- Select Department --</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo htmlspecialchars($dept['department']); ?>">
                                            <?php echo htmlspecialchars($dept['department']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="alert alert-danger">
                            <p><i class="bi bi-exclamation-triangle"></i> Deleting a department cannot be undone. All employees must be transferred to another department.</p>
                        </div>
                        <div class="d-grid">
                            <button type="submit" name="delete_department" class="btn btn-danger rounded-pill">Delete Department</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle rename modal
    var renameDeptModal = document.getElementById('renameDeptModal');
    if (renameDeptModal) {
        renameDeptModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var deptName = button.getAttribute('data-dept-name');
            document.getElementById('old_dept_name').value = deptName;
            document.getElementById('new_name').value = deptName;
        });
    }
    // Handle delete modal
    var deleteDeptModal = document.getElementById('deleteDeptModal');
    if (deleteDeptModal) {
        deleteDeptModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var deptName = button.getAttribute('data-dept-name');
            var hasEmployees = button.getAttribute('data-has-employees') === 'true';
            document.getElementById('delete_dept_name').value = deptName;
            // Show transfer dropdown only if department has employees
            document.getElementById('transfer_container').style.display = hasEmployees ? 'block' : 'none';
            // Remove self from transfer options
            var transferSelect = document.getElementById('transfer_to');
            for (var i = 0; i < transferSelect.options.length; i++) {
                if (transferSelect.options[i].value === deptName) {
                    transferSelect.options[i].disabled = true;
                } else {
                    transferSelect.options[i].disabled = false;
                }
            }
        });
    }
});
</script>
<?php include '../includes/footer.php'; ?>
