<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect(SITE_URL . '/login.php');
}

$userId = $_SESSION['user_id'];
$success = '';
$error = '';
$assessmentData = null;

// Check if self assessment already exists
$stmt = $conn->prepare("SELECT * FROM self_assessment WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $assessmentData = $result->fetch_assoc();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $strengths = sanitize($_POST['strengths']);
    $weaknesses = sanitize($_POST['weaknesses']);
    $goals = sanitize($_POST['goals']);
    $performanceRating = (int)$_POST['performance_rating'];
    
    // Validate inputs
    if ($performanceRating < 1 || $performanceRating > 5) {
        $error = 'Performance rating must be between 1 and 5.';
    } else {
        if ($assessmentData) {
            // Update existing assessment
            $stmt = $conn->prepare("
                UPDATE self_assessment 
                SET strengths = ?, weaknesses = ?, goals = ?, 
                    performance_rating = ?, updated_at = NOW() 
                WHERE user_id = ?
            ");
            $stmt->bind_param("sssii", $strengths, $weaknesses, $goals, $performanceRating, $userId);
            
            if ($stmt->execute()) {
                $success = 'Self assessment updated successfully!';
                // Refresh data
                $stmt = $conn->prepare("SELECT * FROM self_assessment WHERE user_id = ?");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $assessmentData = $stmt->get_result()->fetch_assoc();
            } else {
                $error = 'Error updating assessment: ' . $conn->error;
            }
        } else {
            // Insert new assessment
            $stmt = $conn->prepare("
                INSERT INTO self_assessment 
                (user_id, strengths, weaknesses, goals, performance_rating) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("isssi", $userId, $strengths, $weaknesses, $goals, $performanceRating);
            
            if ($stmt->execute()) {
                $success = 'Self assessment submitted successfully!';
                // Get the new data
                $stmt = $conn->prepare("SELECT * FROM self_assessment WHERE user_id = ?");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $assessmentData = $stmt->get_result()->fetch_assoc();
            } else {
                $error = 'Error submitting assessment: ' . $conn->error;
            }
        }
    }
}

// Include header
include '../includes/header.php';
include '../includes/sidebar.php';
?>
<div class="container-fluid">
  <div class="row">
    <div class="col-md-3 d-none d-md-block">
      <!-- Sidebar is included above -->
    </div>
    <div class="col-md-9 ms-sm-auto px-4">
      <h2 class="mb-2"><i class="bi bi-person-check"></i> Self Assessment</h2>
      <p class="lead">Reflect on your performance, strengths, weaknesses, and set goals for improvement.</p>

      <?php if ($error): ?>
          <div class="alert alert-danger"><?php echo $error; ?></div>
      <?php endif; ?>

      <?php if ($success): ?>
          <div class="alert alert-success"><?php echo $success; ?></div>
      <?php endif; ?>

      <div class="card shadow-lg border-0">
          <div class="card-header bg-primary text-white">
              <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i><?php echo $assessmentData ? 'Update' : 'Submit'; ?> Self Assessment</h5>
          </div>
          <div class="card-body p-4">
              <form method="post">
                  <div class="mb-4">
                      <label for="strengths" class="form-label fw-bold"><i class="bi bi-star-fill text-warning me-2"></i>Your Strengths</label>
                      <textarea class="form-control rounded-3 shadow-sm" id="strengths" name="strengths" rows="3" required><?php echo $assessmentData ? $assessmentData['strengths'] : ''; ?></textarea>
                      <div class="form-text">What do you consider your main professional strengths?</div>
                  </div>
                  
                  <div class="mb-4">
                      <label for="weaknesses" class="form-label fw-bold"><i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>Areas for Improvement</label>
                      <textarea class="form-control rounded-3 shadow-sm" id="weaknesses" name="weaknesses" rows="3" required><?php echo $assessmentData ? $assessmentData['weaknesses'] : ''; ?></textarea>
                      <div class="form-text">What areas do you feel you need to improve?</div>
                  </div>
                  
                  <div class="mb-4">
                      <label for="goals" class="form-label fw-bold"><i class="bi bi-bullseye text-success me-2"></i>Professional Goals</label>
                      <textarea class="form-control rounded-3 shadow-sm" id="goals" name="goals" rows="3" required><?php echo $assessmentData ? $assessmentData['goals'] : ''; ?></textarea>
                      <div class="form-text">What are your professional goals for the next 6-12 months?</div>
                  </div>
                  
                  <div class="mb-4">
                      <label class="form-label fw-bold"><i class="bi bi-bar-chart-steps text-info me-2"></i>Self-Rating of Overall Performance (1-5)</label>
                      <div class="d-flex flex-wrap gap-3">
                          <?php for ($i = 1; $i <= 5; $i++): ?>
                              <div class="form-check form-check-inline position-relative">
                                  <input class="form-check-input rating-radio" type="radio" name="performance_rating" 
                                      id="rating_<?php echo $i; ?>" value="<?php echo $i; ?>" 
                                      <?php echo ($assessmentData && $assessmentData['performance_rating'] == $i) ? 'checked' : ''; ?> required>
                                  <label class="form-check-label rating-label" for="rating_<?php echo $i; ?>" data-bs-toggle="tooltip" title="<?php 
                                      echo ($i == 1) ? 'Needs significant improvement' : (($i == 5) ? 'Exceptional performance' : ''); ?>">
                                      <span class="rating-circle rating-<?php echo $i; ?>"><?php echo $i; ?></span>
                                  </label>
                              </div>
                          <?php endfor; ?>
                      </div>
                      <div class="form-text mt-1">1 = Needs significant improvement, 5 = Exceptional performance</div>
                  </div>
                  
                  <button type="submit" class="btn btn-primary btn-lg px-4 shadow-sm w-100 mt-2"><?php echo $assessmentData ? 'Update' : 'Submit'; ?> Assessment</button>
              </form>
          </div>
      </div>

      <!-- Custom styles for enhanced UI -->
      <style>
      .rating-circle {
          display: inline-flex;
          align-items: center;
          justify-content: center;
          width: 44px;
          height: 44px;
          border-radius: 50%;
          font-size: 1.25rem;
          font-weight: 600;
          background: #f8f9fa;
          border: 2px solid #dee2e6;
          transition: background 0.2s, border 0.2s, color 0.2s;
          cursor: pointer;
          box-shadow: 0 1px 4px rgba(0,0,0,0.04);
      }
      .rating-radio:checked + .rating-label .rating-circle,
      .rating-label:hover .rating-circle {
          background: #0d6efd;
          color: #fff;
          border-color: #0d6efd;
      }
      .rating-1 { border-color: #dc3545; }
      .rating-2 { border-color: #fd7e14; }
      .rating-3 { border-color: #ffc107; }
      .rating-4 { border-color: #0dcaf0; }
      .rating-5 { border-color: #198754; }
      .rating-radio:checked + .rating-label .rating-1 { background: #dc3545; color: #fff; }
      .rating-radio:checked + .rating-label .rating-2 { background: #fd7e14; color: #fff; }
      .rating-radio:checked + .rating-label .rating-3 { background: #ffc107; color: #fff; }
      .rating-radio:checked + .rating-label .rating-4 { background: #0dcaf0; color: #fff; }
      .rating-radio:checked + .rating-label .rating-5 { background: #198754; color: #fff; }
      @media (max-width: 600px) {
          .card-body { padding: 1rem !important; }
          .rating-circle { width: 36px; height: 36px; font-size: 1rem; }
          .btn-lg { font-size: 1rem; }
      }
      </style>

      <!-- Bootstrap Icons CDN (if not already included) -->
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

      <!-- Enable tooltips -->
      <script>
      document.addEventListener('DOMContentLoaded', function () {
          var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
          tooltipTriggerList.forEach(function (tooltipTriggerEl) {
              new bootstrap.Tooltip(tooltipTriggerEl);
          });
      });
      </script>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
