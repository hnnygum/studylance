<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "DEBUG: Accept Proposal Process<br><br>";

echo "1. Session Check:<br>";
if (!isset($_SESSION['user_id'])) {
    echo "❌ No user_id in session<br>";
    exit();
} else {
    echo "✅ User ID: " . $_SESSION['user_id'] . "<br>";
}

if (!isset($_SESSION['role'])) {
    echo "❌ No role in session<br>";
    exit();
} else {
    echo "✅ Role: " . $_SESSION['role'] . "<br>";
}

if ($_SESSION['role'] !== 'client') {
    echo "❌ User is not a client<br>";
    exit();
} else {
    echo "✅ User is a client<br>";
}

echo "<br>2. Database Connection:<br>";
try {
    include 'database.php';
    echo "✅ Database included<br>";
    if (isset($conn) && $conn) {
        echo "✅ Database connection exists<br>";
    } else {
        echo "❌ Database connection failed<br>";
        exit();
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
    exit();
}

echo "<br>3. Proposal ID Check:<br>";
$proposal_id = trim($_GET['id'] ?? '');
echo "Proposal ID from URL: " . $proposal_id . "<br>";

if (empty($proposal_id)) {
    echo "❌ Empty proposal ID<br>";
    exit();
}

if (!is_numeric($proposal_id)) {
    echo "❌ Proposal ID is not numeric<br>";
    exit();
}

echo "✅ Proposal ID is valid: " . $proposal_id . "<br>";

echo "<br>4. Database Query:<br>";
$sql = "SELECT p.*, j.client_id, j.status as job_status, j.title as job_title,
        u.name as freelancer_name, u.email as freelancer_email
        FROM proposals p 
        JOIN jobs j ON p.job_id = j.id 
        JOIN users u ON p.freelancer_id = u.id
        WHERE p.id = ? AND j.client_id = ?";
echo "SQL: " . $sql . "<br>";
echo "Params: proposal_id=" . $proposal_id . ", user_id=" . $_SESSION['user_id'] . "<br>";

try {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo "❌ SQL prepare failed: " . $conn->error . "<br>";
        exit();
    }
    echo "✅ SQL prepared<br>";
    
    $stmt->bind_param("ii", $proposal_id, $_SESSION['user_id']);
    echo "✅ Parameters bound<br>";
    
    $stmt->execute();
    echo "✅ Query executed<br>";
    
    $result = $stmt->get_result();
    echo "✅ Result obtained<br>";
    
    echo "Number of rows: " . $result->num_rows . "<br>";
    
    if ($result->num_rows == 0) {
        echo "❌ No proposal found<br>";
        exit();
    }
    
    $proposal = $result->fetch_assoc();
    echo "✅ Proposal found<br>";
    echo "Proposal Status: " . $proposal['status'] . "<br>";
    echo "Job Status: " . $proposal['job_status'] . "<br>";
    echo "Freelancer: " . $proposal['freelancer_name'] . "<br>";
    
    if ($proposal['status'] !== 'pending') {
        echo "❌ Proposal is not pending<br>";
        exit();
    }
    
    echo "✅ Proposal is pending<br>";
    
} catch (Exception $e) {
    echo "❌ Query error: " . $e->getMessage() . "<br>";
    exit();
}

echo "<br>5. POST Request Check:<br>";
echo "Request Method: " . $_SERVER["REQUEST_METHOD"] . "<br>";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    echo "✅ POST request detected<br>";
    if (isset($_POST['accept_proposal'])) {
        echo "✅ Accept proposal form submitted<br>";
        
        try {
            $conn->begin_transaction();
            echo "✅ Transaction started<br>";
            
            // Update proposal
            $sql = "UPDATE proposals SET status = 'accepted' WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $proposal_id);
            $stmt->execute();
            echo "✅ Proposal updated to accepted<br>";
            
            // Reject other proposals
            $sql = "UPDATE proposals SET status = 'rejected' 
                    WHERE job_id = ? AND id != ? AND status = 'pending'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $proposal['job_id'], $proposal_id);
            $stmt->execute();
            echo "✅ Other proposals rejected<br>";
            
            // Close job
            $sql = "UPDATE jobs SET status = 'closed' WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $proposal['job_id']);
            $stmt->execute();
            echo "✅ Job closed<br>";
            
            $conn->commit();
            echo "✅ Transaction committed<br>";
            
            echo "<br>🎉 SUCCESS: Proposal accepted successfully!<br>";
            echo '<a href="client_dashboard_new.php">Go to Dashboard</a>';
            
        } catch (Exception $e) {
            $conn->rollback();
            echo "❌ Transaction error: " . $e->getMessage() . "<br>";
        }
        
    } else {
        echo "❌ Accept proposal form not submitted<br>";
    }
} else {
    echo "ℹ️ Waiting for POST request<br>";
    echo '<br><form method="POST"><input type="hidden" name="accept_proposal" value="1"><input type="submit" value="Accept Proposal"></form>';
}

$stmt->close();
$conn->close();
?>
