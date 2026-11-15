<?php
// Database update script to fix column length issues

$host = "localhost";
$username = "root";
$password = "";
$dbname = "studylance";

$conn = new mysqli($host, $username, $password);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->select_db($dbname);

echo "Updating database structure...<br>";

// Update jobs table status column
$sql = "ALTER TABLE jobs MODIFY COLUMN status ENUM('open', 'in_progress', 'completed', 'closed') DEFAULT 'open'";
if ($conn->query($sql)) {
    echo "✅ Jobs table status column updated<br>";
} else {
    echo "❌ Jobs table status column error: " . $conn->error . "<br>";
}

// Update proposals table status column
$sql = "ALTER TABLE proposals MODIFY COLUMN status ENUM('pending', 'accepted', 'rejected', 'completed') DEFAULT 'pending'";
if ($conn->query($sql)) {
    echo "✅ Proposals table status column updated<br>";
} else {
    echo "❌ Proposals table status column error: " . $conn->error . "<br>";
}

// Check current data
echo "<br>Checking current data:<br>";

$sql = "SELECT id, status FROM jobs LIMIT 5";
$result = $conn->query($sql);
if ($result) {
    echo "Jobs status values:<br>";
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . " - Status: '" . $row['status'] . "'<br>";
    }
} else {
    echo "❌ Error checking jobs: " . $conn->error . "<br>";
}

$sql = "SELECT id, status FROM proposals LIMIT 5";
$result = $conn->query($sql);
if ($result) {
    echo "<br>Proposals status values:<br>";
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . " - Status: '" . $row['status'] . "'<br>";
    }
} else {
    echo "❌ Error checking proposals: " . $conn->error . "<br>";
}

$conn->close();

echo "<br><br>✅ Database update completed! <a href='client_dashboard_new.php'>Go to Dashboard</a>";
?>
