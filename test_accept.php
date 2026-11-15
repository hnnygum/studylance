<?php
session_start();

// Simple test file to debug the issue
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Test Accept Proposal - Debug Info<br><br>";

echo "Session Status: " . (isset($_SESSION['user_id']) ? "Logged In" : "Not Logged In") . "<br>";
echo "User Role: " . ($_SESSION['role'] ?? 'Not Set') . "<br>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'Not Set') . "<br>";

$proposal_id = trim($_GET['id'] ?? '');
echo "Proposal ID from URL: " . $proposal_id . "<br>";

if (empty($proposal_id) || !is_numeric($proposal_id)) {
    echo "ERROR: Invalid proposal ID<br>";
    exit();
}

try {
    include 'database.php';
    echo "Database connection: " . ($conn ? "Success" : "Failed") . "<br>";
    
    $sql = "SELECT COUNT(*) as count FROM proposals WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $proposal_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    echo "Proposal exists: " . ($row['count'] > 0 ? "Yes" : "No") . "<br>";
    
    if ($row['count'] > 0) {
        echo "SUCCESS: Proposal found and accessible<br>";
    } else {
        echo "ERROR: Proposal not found<br>";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "<br>";
}

echo "<br><a href='client_dashboard_new.php'>Back to Dashboard</a>";
?>
