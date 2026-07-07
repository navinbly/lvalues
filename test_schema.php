<?php
$conn = new mysqli('127.0.0.1', 'root', '12345', 'lvalues_database', 3307);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function show_tables($conn, $like) {
    echo "--- TABLES LIKE $like ---\n";
    $res = $conn->query("SHOW TABLES LIKE '$like'");
    while($row = $res->fetch_row()) {
        $table = $row[0];
        echo "Table: $table\n";
        $res2 = $conn->query("SHOW CREATE TABLE $table");
        if($row2 = $res2->fetch_row()) {
            echo $row2[1] . "\n\n";
        }
    }
}

show_tables($conn, '%content%');
show_tables($conn, '%node%');
$conn->close();
