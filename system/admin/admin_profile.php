<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    redirect(SITE_URL . '/login.php');
}

$adminId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $adminId);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

// Initialize profile picture variable
$profilePic = $admin['profile_pic'] ?? '';

include '../includes/header.php';
include '../includes/sidebar.php';

// Handle profile picture upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if a file was uploaded without errors
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['profile_pic']['tmp_name'];
        $fileName = basename($_FILES['profile_pic']['name']);
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array($ext, $allowed)) {
            $newName = 'profile_' . $adminId . '_' . time() . '.' . $ext;
            $dest = '../assets/profile/' . $newName;
            if (!is_dir('../assets/profile')) { mkdir('../assets/profile', 0777, true); }
            if (move_uploaded_file($fileTmp, $dest)) {
                $stmt = $conn->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
                $stmt->bind_param("si", $newName, $adminId);
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
}
?>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-2 d-none d-lg-block">
            <!-- Sidebar is included above -->
        </div>
        <div class="col-lg-10 ms-lg-auto">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-7">
                    <div class="card shadow border-0 mb-4">
                        <div class="card-body text-center p-5">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($admin['name']); ?>&background=0072ff&color=fff&size=96" class="rounded-circle mb-3 shadow" alt="Admin Profile" width="96" height="96">
                            <h3 class="fw-bold mb-1 text-primary"><?php echo htmlspecialchars($admin['name']); ?></h3>
                            <p class="mb-2 text-muted"><i class="bi bi-envelope-at me-1"></i> <?php echo htmlspecialchars($admin['email']); ?></p>
                            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 mb-3">Administrator</span>
                            <div class="mb-3">
                                <i class="bi bi-calendar-event me-1"></i>
                                <span class="text-secondary">Joined: <?php echo date('F d, Y', strtotime($admin['created_at'])); ?></span>
                            </div>
                            <a href="edit_user.php?id=<?php echo $admin['id']; ?>" class="btn btn-outline-primary rounded-pill px-4"><i class="bi bi-pencil"></i> Edit Profile</a>
                        </div>
                    </div>
                    <div class="card shadow border-0 mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-person-lines-fill me-2"></i> Profile Overview</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-6 text-end text-secondary">Full Name:</div>
                                <div class="col-6 fw-semibold text-primary text-start"><?php echo htmlspecialchars($admin['name']); ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-6 text-end text-secondary">Email:</div>
                                <div class="col-6 fw-semibold text-primary text-start"><?php echo htmlspecialchars($admin['email']); ?></div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-6 text-end text-secondary">Role:</div>
                                <div class="col-6 fw-semibold text-primary text-start">Administrator</div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-6 text-end text-secondary">Joined:</div>
                                <div class="col-6 fw-semibold text-primary text-start"><?php echo date('F d, Y', strtotime($admin['created_at'])); ?></div>
                            </div>
                            <hr>
                            <div class="row mb-3">
                                <div class="col-6 text-end text-secondary">Last Login:</div>
                                <div class="col-6 fw-semibold text-primary text-start">
                                    <?php
                                    // Example: fetch last login from a 'last_login' field if available, else show 'N/A'
                                    echo isset($admin['last_login']) ? date('F d, Y h:i A', strtotime($admin['last_login'])) : 'N/A';
                                    ?>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-6 text-end text-secondary">Total Users Managed:</div>
                                <div class="col-6 fw-semibold text-primary text-start">
                                    <?php
                                    // Count all users except the admin
                                    $result = $conn->query("SELECT COUNT(*) as total FROM users WHERE id != " . intval($admin['id']));
                                    $row = $result ? $result->fetch_assoc() : ['total' => 0];
                                    echo $row['total'];
                                    ?>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-6 text-end text-secondary">Announcements Posted:</div>
                                <div class="col-6 fw-semibold text-primary text-start">
                                    <?php
                                    // Count all announcements
                                    $result = $conn->query("SELECT COUNT(*) as total FROM announcements");
                                    $row = $result ? $result->fetch_assoc() : ['total' => 0];
                                    echo $row['total'];
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card shadow border-0 mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-image me-2"></i> Profile Picture</h5>
                        </div>
                        <div class="card-body">
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
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary w-100 py-3">
                                            <i class="bi bi-upload me-2"></i> Upload Picture
                                        </button>
                                    </div>
                                </div>
                            </form>
                            <?php if (!empty($success)): ?>
                            <div class="alert alert-success mt-3" role="alert">
                                <?php echo $success; ?>
                            </div>
                            <?php elseif (!empty($error)): ?>
                            <div class="alert alert-danger mt-3" role="alert">
                                <?php echo $error; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
