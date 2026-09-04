<?php
	$conn = new mysqli('localhost', 'bmsims_library', 'Bms@2026', 'bmsims_library');

	if ($conn->connect_error) {
	    die("Connection failed: " . $conn->connect_error);
	}
	
?>