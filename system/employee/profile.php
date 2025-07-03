<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'employee') {
    redirect(SITE_URL . '/login.php');
}

$userId = $_SESSION['user_id'];
$userInfo = getUserById($userId);
$success = '';
$error = '';

// Fetch more specific profile fields if available
$position = $userInfo['position'] ?? '';
$phone = $userInfo['phone'] ?? '';
$address = $userInfo['address'] ?? '';

// Handle profile update (add new fields)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $department = sanitize($_POST['department']);
    $position = sanitize($_POST['position']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } else {
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, department = ?, position = ?, phone = ?, address = ? WHERE id = ?");
        $stmt->bind_param("ssssssi", $name, $email, $department, $position, $phone, $address, $userId);
        if ($stmt->execute()) {
            $success = 'Profile updated successfully!';
            $userInfo = getUserById($userId);
            $position = $userInfo['position'] ?? '';
            $phone = $userInfo['phone'] ?? '';
            $address = $userInfo['address'] ?? '';
        } else {
            $error = 'Error updating profile: ' . $conn->error;
        }
    }
}

// Handle profile picture upload
$profilePic = $userInfo['profile_pic'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
    $fileTmp = $_FILES['profile_pic']['tmp_name'];
    $fileName = basename($_FILES['profile_pic']['name']);
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    if (in_array($ext, $allowed)) {
        $newName = 'profile_' . $userId . '_' . time() . '.' . $ext;
        $dest = '../assets/profile/' . $newName;
        if (!is_dir('../assets/profile')) { mkdir('../assets/profile', 0777, true); }
        if (move_uploaded_file($fileTmp, $dest)) {
            $stmt = $conn->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
            $stmt->bind_param("si", $newName, $userId);
            $stmt->execute();
            $profilePic = $newName;
            $success = 'Profile picture updated!';
        } else {
            $error = 'Failed to upload image.';
        }
    } else {
        $error = 'Invalid file type. Only JPG, PNG, GIF allowed.';
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-7 col-md-9">
            <div class="card shadow border-0 rounded-4 mb-4">
                <div class="card-header bg-gradient text-white rounded-top-4 d-flex align-items-center" style="background: linear-gradient(135deg, #43cea2 0%, #185a9d 100%); min-height: 70px;">
                    <i class="bi bi-person-circle display-5 me-3"></i>
                    <div>
                        <h4 class="mb-0">My Profile</h4>
                        <div class="small text-light">View and update your personal information</div>
                    </div>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger mb-3"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success mb-3"><?php echo $success; ?></div>
                    <?php endif; ?>
                    <form method="post" class="needs-validation" novalidate enctype="multipart/form-data">
                        <div class="row g-3">
                            <div class="col-12 text-center mb-3">
                                <img src="<?php echo $profilePic ? '../assets/profile/' . htmlspecialchars($profilePic) : 'https://ui-avatars.com/api/?name=' . urlencode($userInfo['name']) . '&background=0072ff&color=fff&size=96'; ?>" class="rounded-circle shadow" width="96" height="96" alt="Profile Picture">
                            </div>
                            <div class="col-12 mb-3 text-center">
                                <label for="profile_pic" class="form-label">Profile Picture</label>
                                <input class="form-control" type="file" id="profile_pic" name="profile_pic" accept="image/*">
                                <div class="form-text">JPG, PNG, GIF. Max 2MB.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="name" class="form-label">Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($userInfo['name']); ?>" required>
                                <div class="invalid-feedback">Name is required.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($userInfo['email']); ?>" required>
                                <div class="invalid-feedback">Valid email is required.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="department" class="form-label">Department</label>
                                <input type="text" class="form-control" id="department" name="department" value="<?php echo htmlspecialchars($userInfo['department']); ?>" required>
                                <div class="invalid-feedback">Department is required.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="position" class="form-label">Position/Title</label>
                                <input type="text" class="form-control" id="position" name="position" value="<?php echo htmlspecialchars($position); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($address); ?></textarea>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn-primary px-4"><i class="bi bi-save me-1"></i>Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
// Bootstrap validation
(function () {
    'use strict';
    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>
<?php include '../includes/footer.php'; ?>
