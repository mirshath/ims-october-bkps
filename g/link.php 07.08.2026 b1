<?php
header('Content-Type: application/json');

include("../database/connection.php"); // Adjust path as necessary

$response = array('status' => 'error', 'message' => 'Invalid request');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $programme_id = $_POST['programme_id'] ?? null;
    $batch_id = $_POST['batch_id'] ?? null;
    $module_id = $_POST['module_id'] ?? null;
    $lecturer_id = $_POST['lecturer_id'] ?? null;

    if ($programme_id && $batch_id && $module_id && $lecturer_id) {
        // Check if this combination already exists
        $check_stmt = $conn->prepare("SELECT link_id FROM feedback_links WHERE programme_id = ? AND batch_id = ? AND module_id = ? AND lecturer_id = ? LIMIT 1");
        $check_stmt->bind_param("ssss", $programme_id, $batch_id, $module_id, $lecturer_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            // Already exists - get the existing link
            $existing_record = $check_result->fetch_assoc();
            $existing_link_id = $existing_record['link_id'];
            // Protocol-relative URL to handle both HTTP and HTTPS
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $existing_link = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/g/feedback.php?id=" . $existing_link_id;
            
            // Fetch names for the response
            $stmt_program = $conn->prepare("SELECT program_name FROM program_table WHERE program_code = ?");
            $stmt_program->bind_param("s", $programme_id);
            $stmt_program->execute();
            $result_program = $stmt_program->get_result();
            $program_name = $result_program->fetch_assoc()['program_name'] ?? 'N/A';
            $stmt_program->close();
            
            $stmt_lecturer = $conn->prepare("SELECT l.lecturer_name as username, a.full_name 
                                             FROM lecturer_table l 
                                             LEFT JOIN admin a ON l.lecturer_name = a.username 
                                             WHERE l.id = ?");
            $stmt_lecturer->bind_param("s", $lecturer_id);
            $stmt_lecturer->execute();
            $result_lecturer = $stmt_lecturer->get_result();
            $lecturer_row = $result_lecturer->fetch_assoc();
            $lecturer_name = $lecturer_row['username'] ?? 'N/A';
            if (!empty($lecturer_row['full_name'])) {
                $lecturer_name .= ' (' . $lecturer_row['full_name'] . ')';
            }
            $stmt_lecturer->close();
            
            $check_stmt->close();
            
            $response = array(
                'status' => 'duplicate',
                'link' => $existing_link,
                'message' => 'This feedback link already exists!',
                'programme_name' => $program_name,
                'lecturer_name' => $lecturer_name
            );
        } else {
            $check_stmt->close();
            
            // Fetch programme name
            $stmt_program = $conn->prepare("SELECT program_name FROM program_table WHERE program_code = ?");
            $stmt_program->bind_param("s", $programme_id);
            $stmt_program->execute();
            $result_program = $stmt_program->get_result();
            $program_name = $result_program->fetch_assoc()['program_name'] ?? 'N/A';
            $stmt_program->close();

            // Fetch batch name
            $stmt_batch = $conn->prepare("SELECT batch_name FROM batch_table WHERE id = ?");
            $stmt_batch->bind_param("s", $batch_id);
            $stmt_batch->execute();
            $result_batch = $stmt_batch->get_result();
            $batch_name = $result_batch->fetch_assoc()['batch_name'] ?? 'N/A';
            $stmt_batch->close();

            // Fetch module name
            $stmt_module = $conn->prepare("SELECT module_name FROM modules WHERE id = ?");
            $stmt_module->bind_param("s", $module_id);
            $stmt_module->execute();
            $result_module = $stmt_module->get_result();
            $module_name = $result_module->fetch_assoc()['module_name'] ?? 'N/A';
            $stmt_module->close();

            // Fetch lecturer name and full_name from admin table
            $stmt_lecturer = $conn->prepare("SELECT l.lecturer_name as username, a.full_name 
                                             FROM lecturer_table l 
                                             LEFT JOIN admin a ON l.lecturer_name = a.username 
                                             WHERE l.id = ?");
            $stmt_lecturer->bind_param("s", $lecturer_id);
            $stmt_lecturer->execute();
            $result_lecturer = $stmt_lecturer->get_result();
            $lecturer_row = $result_lecturer->fetch_assoc();
            $lecturer_name = $lecturer_row['username'] ?? 'N/A';
            if (!empty($lecturer_row['full_name'])) {
                $lecturer_name .= ' (' . $lecturer_row['full_name'] . ')';
            }
            $stmt_lecturer->close();

            // Sanitize names for URL
            $sanitized_program_name = strtolower(preg_replace('/[^a-zA-Z0-9-]/m', '-', $program_name));
            $sanitized_batch_name = strtolower(preg_replace('/[^a-zA-Z0-9-]/m', '-', $batch_name));
            $sanitized_module_name = strtolower(preg_replace('/[^a-zA-Z0-9-]/m', '-', $module_name));
            $sanitized_lecturer_name = strtolower(preg_replace('/[^a-zA-Z0-9-]/m', '-', $lecturer_name));
            $five_digit_unique_id = substr(uniqid(), -5);

            // Generate a unique link identifier with names and 5-digit ID
            $unique_link_id = "{$sanitized_program_name}-{$sanitized_batch_name}-{$sanitized_module_name}-{$sanitized_lecturer_name}-{$five_digit_unique_id}";

            // Get username from session
            session_start();
            $created_by = $_SESSION['username'] ?? 'system'; // Default to 'system' if not set

            // Store the details in a database
            $stmt = $conn->prepare("INSERT INTO feedback_links (link_id, programme_id, batch_id, module_id, lecturer_id, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $unique_link_id, $programme_id, $batch_id, $module_id, $lecturer_id, $created_by);

            if ($stmt->execute()) {
                // Protocol-relative URL to handle both HTTP and HTTPS
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $generated_link = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/g/feedback.php?id=" . $unique_link_id;
                $response = array('status' => 'success', 'link' => $generated_link, 'message' => 'Link generated successfully', 'programme_name' => $program_name, 'lecturer_name' => $lecturer_name);
            } else {
                $response['message'] = 'Database error: ' . $stmt->error;
            }
            $stmt->close();
        }
    } else {
        $response['message'] = 'Missing required parameters.';
    }
}

echo json_encode($response);
