<?php
include('c:/xampp/htdocs/ims/database/connection.php');

$sql_active = "ALTER TABLE feedback_links ADD COLUMN IF NOT EXISTS active TINYINT(1) DEFAULT 1 AFTER created_at";
if ($conn->query($sql_active) === TRUE) {
    echo "Column 'active' added successfully (or already exists).\n";
} else {
    echo "Error adding column 'active': " . $conn->error . "\n";
}

$sql_created_by = "ALTER TABLE feedback_links ADD COLUMN IF NOT EXISTS created_by VARCHAR(255) AFTER active";
if ($conn->query($sql_created_by) === TRUE) {
    echo "Column 'created_by' added successfully (or already exists).\n";
} else {
    echo "Error adding column 'created_by': " . $conn->error . "\n";
}

$conn->close();
