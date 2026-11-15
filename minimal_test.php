<?php
/**
 * Minimal Test - No Complex Code
 * Tests the absolute basics
 */

echo "<h2>Minimal Database Test</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
    .btn { padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
    form { margin: 20px 0; padding: 20px; background: #f9f9f9; border-radius: 10px; }
    input, textarea { margin: 10px 0; padding: 10px; width: 300px; }
    button { padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; }
</style>";

// Test 1: Basic Connection
try {
    $pdo = new PDO("mysql:host=localhost;dbname=studylance", "root", "", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "<p class='success'>✅ Database connected</p>";
} catch (PDOException $e) {
    echo "<p class='error'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Test 2: Show exact table structure
echo "<h3>📋 Exact Proposals Table Structure:</h3>";
try {
    $stmt = $pdo->query("DESCRIBE proposals");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (PDOException $e) {
    echo "<p class='error'>❌ Table structure error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 3: Check if we have data
echo "<h3>📊 Current Data:</h3>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM jobs WHERE status = 'open'");
    $jobs = $stmt->fetch();
    echo "<p class='info'>Open jobs: " . $jobs['count'] . "</p>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'freelancer'");
    $freelancers = $stmt->fetch();
    echo "<p class='info'>Freelancers: " . $freelancers['count'] . "</p>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM proposals");
    $proposals = $stmt->fetch();
    echo "<p class='info'>Total proposals: " . $proposals['count'] . "</p>";
} catch (PDOException $e) {
    echo "<p class='error'>❌ Data check error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 4: Simple form submission test
echo "<h3>🧪 Manual Proposal Test:</h3>";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jobId = (int)($_POST['job_id'] ?? 1);
    $freelancerId = (int)($_POST['freelancer_id'] ?? 1);
    $bidAmount = (float)($_POST['bid_amount'] ?? 100);
    $proposalText = $_POST['proposal_text'] ?? 'Test proposal';
    $estimatedTime = $_POST['estimated_time'] ?? '';
    
    echo "<p class='info'>Testing with:</p>";
    echo "<pre>";
    echo "Job ID: $jobId\n";
    echo "Freelancer ID: $freelancerId\n";
    echo "Bid Amount: $bidAmount\n";
    echo "Proposal Text: $proposalText\n";
    echo "Estimated Time: $estimatedTime\n";
    echo "</pre>";
    
    try {
        // Build SQL based on available columns
        $sql = "INSERT INTO proposals (job_id, freelancer_id, bid_amount, proposal_text";
        $params = [$jobId, $freelancerId, $bidAmount, $proposalText];
        
        // Add estimated_time if provided
        if (!empty($estimatedTime)) {
            $sql .= ", estimated_time";
            $params[] = $estimatedTime;
        }
        
        $sql .= ", status, created_at) VALUES (?, ?, ?, ?";
        
        if (!empty($estimatedTime)) {
            $sql .= ", ?, 'pending', NOW()";
        } else {
            $sql .= ", 'pending', NOW()";
        }
        
        echo "<p class='info'>SQL: " . htmlspecialchars($sql) . "</p>";
        echo "<p class='info'>Params: " . json_encode($params) . "</p>";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($params);
        
        if ($result) {
            echo "<p class='success'>✅ Proposal inserted successfully!</p>";
            echo "<p class='success'>Rows affected: " . $stmt->rowCount() . "</p>";
        } else {
            echo "<p class='error'>❌ Insert failed</p>";
        }
        
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Insert failed: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<h4>Error Details:</h4>";
        echo "<pre>";
        echo "SQL State: " . $e->errorInfo[0] . "\n";
        echo "Error Code: " . $e->errorInfo[1] . "\n";
        echo "Error Message: " . $e->errorInfo[2] . "\n";
        echo "</pre>";
    }
} else {
    echo "<p>Fill the form below to test proposal insertion:</p>";
}

// Show test form
echo "<form method='post'>";
echo "<h4>Test Proposal Form:</h4>";
echo "<label>Job ID: <input type='number' name='job_id' value='1' required></label><br>";
echo "<label>Freelancer ID: <input type='number' name='freelancer_id' value='1' required></label><br>";
echo "<label>Bid Amount: <input type='number' name='bid_amount' value='100' step='0.01' required></label><br>";
echo "<label>Proposal Text: <textarea name='proposal_text' required>This is a test proposal</textarea></label><br>";
echo "<label>Estimated Time: <input type='text' name='estimated_time' placeholder='2 weeks (optional)'></label><br>";
echo "<button type='submit'>Test Insert</button>";
echo "</form>";

echo "<h3>🔗 Quick Links:</h3>";
echo "<a href='send_proposal_new.php' class='btn'>📝 Try Real Proposal Form</a>";
echo "<a href='login_new.php' class='btn'>🔐 Login to Test</a>";
echo "<a href='fix_database.php' class='btn'>🔄 Reset Database</a>";

// Test 5: Show sample data
echo "<h3>📋 Sample Data:</h3>";
try {
    $stmt = $pdo->query("SELECT * FROM jobs WHERE status = 'open' LIMIT 3");
    $jobs = $stmt->fetchAll();
    if ($jobs) {
        echo "<h4>Available Jobs:</h4>";
        foreach ($jobs as $job) {
            echo "<p><strong>ID {$job['id']}:</strong> " . htmlspecialchars($job['title']) . " - $" . $job['budget'] . "</p>";
        }
    }
    
    $stmt = $pdo->query("SELECT * FROM users WHERE role = 'freelancer' LIMIT 3");
    $freelancers = $stmt->fetchAll();
    if ($freelancers) {
        echo "<h4>Available Freelancers:</h4>";
        foreach ($freelancers as $freelancer) {
            echo "<p><strong>ID {$freelancer['id']}:</strong> " . htmlspecialchars($freelancer['name']) . " - " . htmlspecialchars($freelancer['email']) . "</p>";
        }
    }
} catch (PDOException $e) {
    echo "<p class='error'>❌ Sample data error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
