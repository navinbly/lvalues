<?php
$conn = new mysqli('127.0.0.1', 'root', '12345', 'lvalues_database', 3307);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "--- Last 5 user_login_history ---\n";
$res = $conn->query("SELECT * FROM user_login_history ORDER BY id DESC LIMIT 5");
if ($res) {
    while($row = $res->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

echo "\n--- Last 5 security_alert_logs ---\n";
$res = $conn->query("SELECT * FROM security_alert_logs ORDER BY id DESC LIMIT 5");
if ($res) {
    while($row = $res->fetch_assoc()) {
        print_r($row);
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

$conn->close();
