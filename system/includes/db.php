<?php
require_once 'config.php';

function getDbConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Check if database exists
    $dbExists = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . DB_NAME . "'");
    if ($dbExists->num_rows == 0) {
        // Redirect to setup if accessing from browser
        if (!isset($GLOBALS['ignore_setup_redirect']) && php_sapi_name() !== 'cli') {
            header("Location: " . SITE_URL . "/setup.php");
            exit;
        }
        die("Database does not exist. Please run the setup script.");
    }
    
    // Select database
    $conn->select_db(DB_NAME);
    
    // Check if users table exists
    $tableExists = $conn->query("SHOW TABLES LIKE 'users'");
    if ($tableExists->num_rows == 0) {
        // Redirect to setup if accessing from browser
        if (!isset($GLOBALS['ignore_setup_redirect']) && php_sapi_name() !== 'cli') {
            header("Location: " . SITE_URL . "/setup.php");
            exit;
        }
        die("Required tables don't exist. Please run the setup script.");
    }
    
    return $conn;
}

// Get a database connection
$conn = getDbConnection();
?>
