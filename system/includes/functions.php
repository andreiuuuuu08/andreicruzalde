<?php
// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Redirect to a URL
function redirect($url) {
    header("Location: $url");
    exit;
}

// Sanitize input
function sanitize($input) {
    global $conn;
    return htmlspecialchars(trim($conn->real_escape_string($input)));
}

// Check user role
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] == $role;
}

// Get user by ID
function getUserById($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Get all employees
function getAllEmployees() {
    global $conn;
    $query = "SELECT * FROM users WHERE role = 'employee' OR role = 'teamlead'";
    $result = $conn->query($query);
    $employees = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $employees[] = $row;
        }
    }
    
    return $employees;
}

// Check if feedback exists - improved to handle errors more robustly
function getFeedback($from_user_id, $to_user_id) {
    global $conn;
    
    // Validate inputs
    if (!is_numeric($from_user_id) || !is_numeric($to_user_id) || $from_user_id <= 0 || $to_user_id <= 0) {
        error_log("Invalid parameters in getFeedback: from_user_id=$from_user_id, to_user_id=$to_user_id");
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM peer_feedback WHERE from_user_id = ? AND to_user_id = ?");
        if (!$stmt) {
            error_log("Database error in getFeedback: " . $conn->error);
            return null;
        }
        
        $result = null;
        
        // Use try-catch to handle any bind_param or execute errors
        try {
            $stmt->bind_param("ii", $from_user_id, $to_user_id);
            
            if (!$stmt->execute()) {
                error_log("Execute error in getFeedback: " . $stmt->error);
                return null;
            }
            
            $result = $stmt->get_result();
        } catch (Exception $e) {
            error_log("Exception in getFeedback during query execution: " . $e->getMessage());
            return null;
        }
        
        if (!$result) {
            error_log("Result error in getFeedback");
            return null;
        }
        
        return $result->fetch_assoc(); // This returns null if no row found
    } catch (Exception $e) {
        error_log("Exception in getFeedback: " . $e->getMessage());
        return null;
    }
}

// Get team members for a specific department
function getTeamMembers($department) {
    global $conn;
    $query = "SELECT * FROM users WHERE department = ? AND role = 'employee'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $department);
    $stmt->execute();
    $result = $stmt->get_result();
    $members = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $members[] = $row;
        }
    }
    
    return $members;
}

// Get department statistics
function getDepartmentStats($department) {
    global $conn;
    
    // Get average ratings
    $query = "
        SELECT 
            AVG(communication_rating) as avg_communication,
            AVG(teamwork_rating) as avg_teamwork,
            AVG(technical_rating) as avg_technical,
            AVG(productivity_rating) as avg_productivity,
            COUNT(*) as feedback_count
        FROM peer_feedback f
        JOIN users u ON f.to_user_id = u.id
        WHERE u.department = ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $department);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

// Check if user is an admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Check if user is a team lead
function isTeamLead() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'teamlead';
}

// Check if user is an employee
function isEmployee() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'employee';
}

// Fetch settings
function getSetting($conn, $key, $default = '') {
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $row = $result->fetch_assoc()) {
        return $row['setting_value'];
    }
    return $default;
}
?>
