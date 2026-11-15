<?php
/**
 * Test Data Creation - Add Sample Jobs and Proposals
 */

require_once 'config.php';

try {
    $db = Database::getInstance();
    
    echo "<h2>Creating Test Data</h2>";
    
    // Get a client user
    $stmt = $db->query("SELECT id, name FROM users WHERE role = 'client' LIMIT 1");
    $client = $stmt->fetch();
    
    if (!$client) {
        echo "<p>No client user found. Please create a client account first.</p>";
        exit;
    }
    
    echo "<p>Using client: " . htmlspecialchars($client['name']) . " (ID: " . $client['id'] . ")</p>";
    
    // Get a freelancer user
    $stmt = $db->query("SELECT id, name FROM users WHERE role = 'freelancer' LIMIT 1");
    $freelancer = $stmt->fetch();
    
    if (!$freelancer) {
        echo "<p>No freelancer user found. Please create a freelancer account first.</p>";
        exit;
    }
    
    echo "<p>Using freelancer: " . htmlspecialchars($freelancer['name']) . " (ID: " . $freelancer['id'] . ")</p>";
    
    // Create sample jobs if none exist
    $stmt = $db->query("SELECT COUNT(*) as count FROM jobs WHERE client_id = ?", [$client['id']]);
    $jobCount = $stmt->fetch()['count'];
    
    if ($jobCount == 0) {
        echo "<h3>Creating Sample Jobs...</h3>";
        
        $sampleJobs = [
            [
                'title' => 'Website Development Project',
                'description' => 'Looking for an experienced web developer to create a modern e-commerce website with payment integration.',
                'budget' => 25000.00,
                'status' => 'open'
            ],
            [
                'title' => 'Mobile App Design',
                'description' => 'Need UI/UX designer for a mobile application with modern design principles.',
                'budget' => 15000.00,
                'status' => 'open'
            ],
            [
                'title' => 'Content Writing Services',
                'description' => 'Seeking content writers for blog posts and marketing materials.',
                'budget' => 8000.00,
                'status' => 'in_progress'
            ]
        ];
        
        foreach ($sampleJobs as $job) {
            $stmt = $db->query(
                "INSERT INTO jobs (client_id, title, description, budget, status, created_at) VALUES (?, ?, ?, ?, ?, ?)",
                [$client['id'], $job['title'], $job['description'], $job['budget'], $job['status'], date('Y-m-d H:i:s')]
            );
            echo "<p>✅ Created job: " . htmlspecialchars($job['title']) . "</p>";
        }
    } else {
        echo "<p>Client already has $jobCount jobs.</p>";
    }
    
    // Get the jobs for proposals
    $stmt = $db->query("SELECT id, title FROM jobs WHERE client_id = ? ORDER BY created_at DESC LIMIT 3", [$client['id']]);
    $jobs = $stmt->fetchAll();
    
    if ($jobs) {
        echo "<h3>Creating Sample Proposals...</h3>";
        
        foreach ($jobs as $job) {
            // Check if proposal already exists
            $stmt = $db->query("SELECT COUNT(*) as count FROM proposals WHERE job_id = ? AND freelancer_id = ?", [$job['id'], $freelancer['id']]);
            $proposalCount = $stmt->fetch()['count'];
            
            if ($proposalCount == 0) {
                $bidAmount = rand(5000, 20000);
                $statuses = ['pending', 'accepted', 'rejected'];
                $status = $statuses[array_rand($statuses)];
                
                $stmt = $db->query(
                    "INSERT INTO proposals (job_id, freelancer_id, bid_amount, proposal_text, cover_letter, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [
                        $job['id'], 
                        $freelancer['id'], 
                        $bidAmount, 
                        "I have extensive experience in this field and can deliver high-quality work within your timeline. My skills perfectly match your requirements.",
                        "I have extensive experience in this field and can deliver high-quality work within your timeline. My skills perfectly match your requirements.",
                        $status,
                        date('Y-m-d H:i:s')
                    ]
                );
                echo "<p>✅ Created proposal for: " . htmlspecialchars($job['title']) . " (₹" . number_format($bidAmount, 2) . ", Status: $status)</p>";
            } else {
                echo "<p>Proposal already exists for: " . htmlspecialchars($job['title']) . "</p>";
            }
        }
    }
    
    echo "<h3>✅ Test Data Created Successfully!</h3>";
    echo "<p><a href='debug_dashboard.php'>Debug Dashboard</a></p>";
    echo "<p><a href='client_dashboard_new.php'>Client Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<h3>Error:</h3>";
    echo "<p style='color:red;'>" . $e->getMessage() . "</p>";
}
?>
