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
    } else {
        $error = 'User not found.';
    }
} else {
    $error = 'Invalid user ID.';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $department = sanitize($_POST['department']);
    $role = sanitize($_POST['role']);
    $changePassword = isset($_POST['change_password']) && $_POST['change_password'] == '1';
    $password = $changePassword ? $_POST['password'] : '';
    
    // Validate inputs
    $validRoles = ['admin', 'teamlead', 'employee'];
    
    if (empty($name) || empty($email) || empty($department)) {
        $error = 'Name, email, and department are required.';
    } elseif (!in_array($role, $validRoles)) {
        $error = 'Invalid role selected.';
    } elseif ($changePassword && strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        // Check if email exists (excluding current user)
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->bind_param("si", $email, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = 'Email already in use by another user.';
        } else {
            // Prepare base update SQL
            $sql = "UPDATE users SET name = ?, email = ?, department = ?, role = ?";
            $types = "ssss";
            $params = [$name, $email, $department, $role];
            
            // Add password update if needed
            if ($changePassword) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $sql .= ", password = ?";
                $types .= "s";
                $params[] = $hashedPassword;
            }
            
            // Add WHERE clause
            $sql .= " WHERE id = ?";
            $types .= "i";
            $params[] = $userId;
            
            // Execute update
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            
            if ($stmt->execute()) {
                $success = 'User updated successfully!';
                
                // Refresh user data
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
            } else {
                $error = 'Error updating user: ' . $conn->error;
            }
        }
    }
}

// Get all departments for dropdown
$departments = [];
$query = "SELECT DISTINCT department FROM users WHERE department IS NOT NULL AND department != '' ORDER BY department";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row['department'];
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
                <h2 class="fw-bold text-primary"><i class="bi bi-person-gear me-2"></i>Edit User</h2>
                <a href="javascript:history.back()" class="btn btn-outline-secondary rounded-pill px-4"><i class="bi bi-arrow-left"></i> Back</a>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($user): ?>
                <div class="card shadow border-0 mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-person-lines-fill me-2"></i> Edit User: <?php echo htmlspecialchars($user['name']); ?></h5>
                    </div>
                    <div class="card-body p-4">
                        <form method="post">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control rounded-pill" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control rounded-pill" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="department" class="form-label">Department</label>
                                    <select class="form-select rounded-pill" id="department" name="department" required>
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo ($user['department'] == $dept) ? 'selected' : ''; ?>><?php echo htmlspecialchars($dept); ?></option>
                                        <?php endforeach; ?>
                                        <option value="other">Other (specify below)</option>
                                    </select>
                                </div>
                                <div class="col-md-6" id="custom-dept-container" style="display:none;">
                                    <label for="custom_department" class="form-label">Custom Department</label>
                                    <input type="text" class="form-control rounded-pill" id="custom_department" placeholder="Enter department name">
                                </div>
                                <div class="col-md-6">
                                    <label for="role" class="form-label">Role</label>
                                    <select class="form-select rounded-pill" id="role" name="role" required>
                                        <option value="employee" <?php echo ($user['role'] == 'employee') ? 'selected' : ''; ?>>Employee</option>
                                        <option value="teamlead" <?php echo ($user['role'] == 'teamlead') ? 'selected' : ''; ?>>Team Lead</option>
                                        <option value="admin" <?php echo ($user['role'] == 'admin') ? 'selected' : ''; ?>>Administrator</option>
                                    </select>
                                </div>
                                <div class="col-md-6 d-flex align-items-center">
                                    <div class="form-check mt-4">
                                        <input type="checkbox" class="form-check-input" id="change_password" name="change_password" value="1">
                                        <label class="form-check-label" for="change_password">Change Password</label>
                                    </div>
                                </div>
                                <div class="col-md-6" id="password_container" style="display:none;">
                                    <label for="password" class="form-label">New Password</label>
                                    <input type="password" class="form-control rounded-pill" id="password" name="password" minlength="6">
                                    <div class="form-text">Password must be at least 6 characters.</div>
                                </div>
                            </div>
                            <div class="d-flex gap-2 mt-4">
                                <button type="submit" class="btn btn-primary rounded-pill px-4"><i class="bi bi-save me-1"></i> Update User</button>
                                <a href="<?php echo SITE_URL; ?>/admin/delete_user.php?id=<?php echo $user['id']; ?>" class="btn btn-danger rounded-pill px-4" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')"><i class="bi bi-trash"></i> Delete User</a>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="card shadow border-0 mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0 text-primary"><i class="bi bi-activity me-2"></i> User Activity</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="p-3 bg-primary bg-opacity-10 rounded text-center">
                                    <div class="fw-bold text-primary">Account Created</div>
                                    <div><?php echo date('M d, Y', strtotime($user['created_at'])); ?></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-success bg-opacity-10 rounded text-center">
                                    <div class="fw-bold text-success">Feedback Given</div>
                                    <div><?php echo $feedbackGiven; ?></div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-info bg-opacity-10 rounded text-center">
                                    <div class="fw-bold text-info">Feedback Received</div>
                                    <div><?php echo $feedbackReceived; ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4 text-center">
                            <span class="fw-bold">Self Assessment:</span> <?php echo $hasSelfAssessment ? '<span class="badge bg-success">Completed</span>' : '<span class="badge bg-warning">Not Completed</span>'; ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-danger">
                    <p>User not found or error occurred. Please try again or <a href="<?php echo SITE_URL; ?>/admin">return to dashboard</a>.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle password change checkbox
    const changePasswordCheckbox = document.getElementById('change_password');
    const passwordContainer = document.getElementById('password_container');
    
    if (changePasswordCheckbox && passwordContainer) {
        changePasswordCheckbox.addEventListener('change', function() {
            passwordContainer.style.display = this.checked ? 'block' : 'none';
            
            // Clear password field when hiding
            if (!this.checked) {
                document.getElementById('password').value = '';
            }
        });
    }
    
    // Handle custom department selection
    const departmentSelect = document.getElementById('department');
    const customDeptContainer = document.getElementById('custom-dept-container');
    const customDeptInput = document.getElementById('custom_department');
    
    if (departmentSelect && customDeptContainer && customDeptInput) {
        departmentSelect.addEventListener('change', function() {
            if (this.value === 'other') {
                customDeptContainer.style.display = 'block';
                customDeptInput.setAttribute('required', 'required');
            } else {
                customDeptContainer.style.display = 'none';
                customDeptInput.removeAttribute('required');
                customDeptInput.value = '';
            }
        });
        
        // Handle form submission for custom department
        const form = document.querySelector('form');
        form.addEventListener('submit', function(e) {
            if (departmentSelect.value === 'other' && customDeptInput.value.trim()) {
                e.preventDefault();
                departmentSelect.innerHTML += `<option value="${customDeptInput.value.trim()}" selected>${customDeptInput.value.trim()}</option>`;
                departmentSelect.value = customDeptInput.value.trim();
                customDeptContainer.style.display = 'none';
                form.submit();
            }
        });
    }
});
</script>
<?php include '../includes/footer.php'; ?>
