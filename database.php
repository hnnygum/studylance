<?php
$host = "localhost";
$username = "root";
$password = "";
$dbname = "studylance";

// Create connection
$conn = new mysqli($host, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully or already exists<br>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
}

// Select the database
$conn->select_db($dbname);

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'client', 'freelancer') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'users' created successfully or already exists<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Create jobs table
$sql = "CREATE TABLE IF NOT EXISTS jobs (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    budget DECIMAL(10,2) NOT NULL,
    client_id INT(11) NOT NULL,
    status ENUM('open', 'in_progress', 'completed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'jobs' created successfully or already exists<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Create proposals table
$sql = "CREATE TABLE IF NOT EXISTS proposals (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    job_id INT(11) NOT NULL,
    freelancer_id INT(11) NOT NULL,
    proposal_text TEXT NOT NULL,
    bid_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id),
    FOREIGN KEY (freelancer_id) REFERENCES users(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'proposals' created successfully or already exists<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Create messages table
$sql = "CREATE TABLE IF NOT EXISTS messages (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    sender_id INT(11) NOT NULL,
    receiver_id INT(11) NOT NULL,
    message TEXT NOT NULL,
    job_id INT(11) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id),
    FOREIGN KEY (receiver_id) REFERENCES users(id),
    FOREIGN KEY (job_id) REFERENCES jobs(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'messages' created successfully or already exists<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Insert default admin user if not exists
$sql = "SELECT * FROM users WHERE email = 'admin@studylance.com'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    $hashed_password = password_hash("admin123", PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (name, email, password, role) VALUES ('Admin', 'admin@studylance.com', '$hashed_password', 'admin')";
    
    if ($conn->query($sql) === TRUE) {
        echo "Default admin user created successfully<br>";
    } else {
        echo "Error creating admin user: " . $conn->error . "<br>";
    }
}

echo "<br><strong>Database setup completed!</strong><br>";
echo "<a href='index.html'>Go to Homepage</a>";

$conn->close();
?>
