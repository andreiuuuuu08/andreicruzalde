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

// Handle pre-selected user ID from query string
$preSelectedUserId = isset($_GET['to_id']) ? (int)$_GET['to_id'] : null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $toUserId = isset($_POST['to_user_id']) ? (int)$_POST['to_user_id'] : 0;
    $communicationRating = isset($_POST['communication_rating']) ? (int)$_POST['communication_rating'] : 0;
    $teamworkRating = isset($_POST['teamwork_rating']) ? (int)$_POST['teamwork_rating'] : 0;
    $technicalRating = isset($_POST['technical_rating']) ? (int)$_POST['technical_rating'] : 0;
    $productivityRating = isset($_POST['productivity_rating']) ? (int)$_POST['productivity_rating'] : 0;
    $comments = isset($_POST['comments']) ? sanitize($_POST['comments']) : '';
    
    // Validate inputs
    if ($communicationRating < 1 || $communicationRating > 5 ||
        $teamworkRating < 1 || $teamworkRating > 5 ||
        $technicalRating < 1 || $technicalRating > 5 ||
        $productivityRating < 1 || $productivityRating > 5) {
        $error = 'All ratings must be between 1 and 5.';
    } elseif ($toUserId === $userId) {
        $error = 'You cannot provide feedback for yourself.';
    } elseif ($toUserId === 0) {
        $error = 'You must select a colleague.';
    } else {
        // Check if feedback already exists - fixed to handle null return
        $existingFeedback = getFeedback($userId, $toUserId);
        
        if ($existingFeedback !== null) {
            // Update existing feedback - fixed error on line 66
            $updateStmt = $conn->prepare("
                UPDATE peer_feedback 
                SET communication_rating = ?, teamwork_rating = ?, technical_rating = ?, 
                    productivity_rating = ?, comments = ?, updated_at = NOW() 
                WHERE from_user_id = ? AND to_user_id = ?
            ");
            
            if ($updateStmt) {
                $updateStmt->bind_param("iiiisii", $communicationRating, $teamworkRating, $technicalRating, 
                                $productivityRating, $comments, $userId, $toUserId);
                
                if ($updateStmt->execute()) {
                    $success = 'Feedback updated successfully!';
                } else {
                    $error = 'Error updating feedback: ' . $conn->error;
                }
            } else {
                $error = 'Database error: ' . $conn->error;
            }
        } else {
            // Insert new feedback
            $insertStmt = $conn->prepare("
                INSERT INTO peer_feedback 
                (from_user_id, to_user_id, communication_rating, teamwork_rating, 
                 technical_rating, productivity_rating, comments) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            if ($insertStmt) {
                $insertStmt->bind_param("iiiiiis", $userId, $toUserId, $communicationRating, 
                               $teamworkRating, $technicalRating, $productivityRating, $comments);
                
                if ($insertStmt->execute()) {
                    $success = 'Feedback submitted successfully!';
                } else {
                    $error = 'Error submitting feedback: ' . $conn->error;
                }
            } else {
                $error = 'Database error: ' . $conn->error;
            }
        }
    }
}

// Get all employees and team leads except current user
$employees = [];
$employeeQuery = "SELECT id, name, department FROM users WHERE id != ? AND (role = 'employee' OR role = 'teamlead')";
$employeeStmt = $conn->prepare($employeeQuery);

if ($employeeStmt) {
    $employeeStmt->bind_param("i", $userId);
    $employeeStmt->execute();
    $employeeResult = $employeeStmt->get_result();

    if ($employeeResult && $employeeResult->num_rows > 0) {
        while ($row = $employeeResult->fetch_assoc()) {
            $employees[] = $row;
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
      <div class="row mb-4">
        <div class="col-md-8 d-flex align-items-center gap-3">
            <h2 class="fw-bold text-primary mb-0"><i class="bi bi-chat-dots me-2"></i>Peer Feedback</h2>
            <span class="badge bg-primary">Colleagues: <?php echo count($employees); ?></span>
        </div>
        <div class="col-md-4 text-end">
            <?php
            $givenCount = count($feedbackItems ?? []);
            $completionRate = count($employees) > 0 ? round(($givenCount/count($employees))*100) : 0;
            ?>
            <span class="badge bg-success">Feedback Given: <?php echo $givenCount; ?>/<?php echo count($employees); ?> (<?php echo $completionRate; ?>%)</span>
        </div>
      </div>

      <div class="card shadow mb-4">
          <div class="card-header bg-white d-flex align-items-center gap-2">
              <h5 class="mb-0 text-primary"><i class="bi bi-pencil-square me-2"></i>Submit Feedback</h5>
          </div>
          <div class="card-body">
              <form method="post">
                  <div class="mb-3">
                      <label for="to_user_id" class="form-label">Select Colleague</label>
                      <select class="form-select" id="to_user_id" name="to_user_id" required>
                          <option value="">-- Select a colleague --</option>
                          <?php foreach ($employees as $employee): ?>
                              <option value="<?php echo htmlspecialchars($employee['id'] ?? ''); ?>" 
                                  <?php echo ($preSelectedUserId == $employee['id']) ? 'selected' : ''; ?>>
                                  <?php echo htmlspecialchars($employee['name'] ?? ''); ?> 
                                  (<?php echo htmlspecialchars($employee['department'] ?? ''); ?>)
                              </option>
                          <?php endforeach; ?>
                      </select>
                  </div>
                  <div class="row g-3">
                      <div class="col-md-6">
                          <label class="form-label">Communication Skills <i class="bi bi-info-circle" title="How well does this colleague communicate?"></i></label>
                          <div class="d-flex gap-2">
                              <?php for ($i = 1; $i <= 5; $i++): ?>
                                  <div class="form-check">
                                      <input class="form-check-input" type="radio" name="communication_rating" id="comm_<?php echo $i; ?>" value="<?php echo $i; ?>" required>
                                      <label class="form-check-label" for="comm_<?php echo $i; ?>"><?php echo $i; ?></label>
                                  </div>
                              <?php endfor; ?>
                          </div>
                      </div>
                      <div class="col-md-6">
                          <label class="form-label">Teamwork <i class="bi bi-info-circle" title="How well does this colleague work in a team?"></i></label>
                          <div class="d-flex gap-2">
                              <?php for ($i = 1; $i <= 5; $i++): ?>
                                  <div class="form-check">
                                      <input class="form-check-input" type="radio" name="teamwork_rating" id="team_<?php echo $i; ?>" value="<?php echo $i; ?>" required>
                                      <label class="form-check-label" for="team_<?php echo $i; ?>"><?php echo $i; ?></label>
                                  </div>
                              <?php endfor; ?>
                          </div>
                      </div>
                      <div class="col-md-6">
                          <label class="form-label">Technical Skills <i class="bi bi-info-circle" title="How strong are this colleague's technical skills?"></i></label>
                          <div class="d-flex gap-2">
                              <?php for ($i = 1; $i <= 5; $i++): ?>
                                  <div class="form-check">
                                      <input class="form-check-input" type="radio" name="technical_rating" id="tech_<?php echo $i; ?>" value="<?php echo $i; ?>" required>
                                      <label class="form-check-label" for="tech_<?php echo $i; ?>"><?php echo $i; ?></label>
                                  </div>
                              <?php endfor; ?>
                          </div>
                      </div>
                      <div class="col-md-6">
                          <label class="form-label">Productivity <i class="bi bi-info-circle" title="How productive is this colleague?"></i></label>
                          <div class="d-flex gap-2">
                              <?php for ($i = 1; $i <= 5; $i++): ?>
                                  <div class="form-check">
                                      <input class="form-check-input" type="radio" name="productivity_rating" id="prod_<?php echo $i; ?>" value="<?php echo $i; ?>" required>
                                      <label class="form-check-label" for="prod_<?php echo $i; ?>"><?php echo $i; ?></label>
                                  </div>
                              <?php endfor; ?>
                          </div>
                      </div>
                  </div>
                  <div class="mb-3 mt-3">
                      <label for="comments" class="form-label">Comments <i class="bi bi-info-circle" title="Share constructive feedback or suggestions."></i></label>
                      <textarea class="form-control" id="comments" name="comments" rows="3" placeholder="Optional"></textarea>
                  </div>
                  <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Submit Feedback</button>
              </form>
          </div>
      </div>

      <!-- View/Edit Comment Modal -->
      <div class="modal fade" id="viewCommentModal" tabindex="-1" aria-labelledby="viewCommentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="viewCommentModalLabel">Feedback Comment</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <textarea class="form-control" id="modalCommentText" rows="5" readonly></textarea>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
          </div>
        </div>
      </div>

      <div class="card shadow">
          <div class="card-header bg-white d-flex align-items-center gap-2">
              <h5 class="mb-0 text-primary"><i class="bi bi-list-check me-2"></i>Your Submitted Feedback</h5>
          </div>
          <div class="card-body">
              <div class="table-responsive">
              <table class="table table-striped align-middle">
                  <thead>
                      <tr>
                          <th>Colleague</th>
                          <th>Communication</th>
                          <th>Teamwork</th>
                          <th>Technical</th>
                          <th>Productivity</th>
                          <th>Date</th>
                          <th>Actions</th>
                      </tr>
                  </thead>
                  <tbody>
                      <?php
                      // Fixed errors on lines 90 and 93 - completely restructured this section
                      $feedbackItems = [];
                      $feedbackStmt = $conn->prepare("
                          SELECT f.*, u.name, u.department 
                          FROM peer_feedback f 
                          JOIN users u ON f.to_user_id = u.id 
                          WHERE f.from_user_id = ?
                      ");
                      
                      if ($feedbackStmt) {
                          $feedbackStmt->bind_param("i", $userId);
                          if ($feedbackStmt->execute()) {
                              $feedbackResult = $feedbackStmt->get_result();
                              
                              if ($feedbackResult && $feedbackResult->num_rows > 0) {
                                  while ($row = $feedbackResult->fetch_assoc()) {
                                      if ($row !== null) {
                                          $feedbackItems[] = $row;
                                      }
                                  }
                              }
                          }
                      }
                      
                      if (!empty($feedbackItems)):
                          foreach ($feedbackItems as $feedback):
                      ?>
                          <tr>
                              <td>
                                  <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($feedback['name'] ?? ''); ?>&background=43cea2&color=fff&size=32" class="rounded-circle me-2" width="32" height="32" alt="Avatar">
                                  <span class="fw-semibold"><?php echo htmlspecialchars($feedback['name'] ?? 'Unknown'); ?></span>
                                  <span class="badge bg-info ms-1"><?php echo htmlspecialchars($feedback['department'] ?? 'Unknown'); ?></span>
                              </td>
                              <td><span class="badge bg-primary fs-6"><?php echo htmlspecialchars($feedback['communication_rating'] ?? 0); ?>/5</span></td>
                              <td><span class="badge bg-success fs-6"><?php echo htmlspecialchars($feedback['teamwork_rating'] ?? 0); ?>/5</span></td>
                              <td><span class="badge bg-info fs-6"><?php echo htmlspecialchars($feedback['technical_rating'] ?? 0); ?>/5</span></td>
                              <td><span class="badge bg-warning fs-6"><?php echo htmlspecialchars($feedback['productivity_rating'] ?? 0); ?>/5</span></td>
                              <td><?php echo isset($feedback['created_at']) ? date('M d, Y', strtotime($feedback['created_at'])) : 'Unknown'; ?></td>
                              <td>
                                  <a href="peer_feedback.php?to_id=<?php echo htmlspecialchars($feedback['to_user_id'] ?? ''); ?>" class="btn btn-sm btn-primary me-1" title="Edit Feedback"><i class="bi bi-pencil"></i></a>
                                  <button class="btn btn-sm btn-info" title="View Comment" onclick="viewComment('<?php echo htmlspecialchars(addslashes($feedback['comments'] ?? '')); ?>')"><i class="bi bi-chat-left-text"></i></button>
                              </td>
                          </tr>
                      <?php 
                          endforeach; 
                      else:
                      ?>
                          <tr>
                              <td colspan="7" class="text-center">No feedback submitted yet.</td>
                          </tr>
                      <?php endif; ?>
                  </tbody>
              </table>
              </div>
          </div>
      </div>

      <script>
      function viewComment(comment) {
          document.getElementById('modalCommentText').value = comment || 'No comment.';
          var modal = new bootstrap.Modal(document.getElementById('viewCommentModal'));
          modal.show();
      }
      </script>

      <style>
      .card { transition: box-shadow 0.2s; }
      .card:hover { box-shadow: 0 0.75rem 2rem rgba(67,206,162,0.10), 0 0.25rem 0.5rem rgba(0,0,0,0.04); }
      .table th, .table td { vertical-align: middle; }
      .badge { font-size: 0.95em; }
      </style>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
