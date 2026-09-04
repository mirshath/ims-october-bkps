<?php
// Set JSON header for all responses
include('../database/connection.php');
header('Content-Type: application/json');

// Start session if not already started (for CSRF protection if needed)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the request is POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $feedback_id = isset($_POST['feedback_id']) ? trim($_POST['feedback_id']) : null;
    $is_final_year = isset($_POST['is_final_year']) && $_POST['is_final_year'] == '1';

    // =================================================================
    // FINAL YEAR: dynamic fields, only the fields that actually belong
    // to this link are required - never the fixed 8-question list.
    // =================================================================
    if ($is_final_year) {
        $link_row_id = isset($_POST['link_row_id']) ? intval($_POST['link_row_id']) : 0;
        $program_id = isset($_POST['program_id']) ? trim($_POST['program_id']) : null;
        $batch_id = isset($_POST['batch_id']) && !empty($_POST['batch_id']) ? intval($_POST['batch_id']) : null;
        $module_id = isset($_POST['module_id']) && !empty($_POST['module_id']) ? intval($_POST['module_id']) : null;
        $lecturer_id = isset($_POST['lecturer_id']) && !empty($_POST['lecturer_id']) ? intval($_POST['lecturer_id']) : null;
        $program_name = isset($_POST['program_name']) ? trim($_POST['program_name']) : '';
        $module_name = isset($_POST['module_name']) ? trim($_POST['module_name']) : '';
        $lecturer_name = isset($_POST['lecturer_name']) ? trim($_POST['lecturer_name']) : '';

        if ($link_row_id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid form reference.']);
            exit;
        }
        if ($program_name === '' || $module_name === '' || $lecturer_name === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing course details.']);
            exit;
        }

        // Fetch the REAL set of fields for this link - this is the fix:
        // required fields are whatever was saved in feedback_form_fields
        // for this link_row_id, not a hardcoded list.
        $fields = [];
        $stmt = $conn->prepare(
            "SELECT id, field_type, field_label FROM feedback_form_fields WHERE link_id = ? ORDER BY field_order ASC, id ASC"
        );
        $stmt->bind_param("i", $link_row_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $fields[] = $row;
        }
        $stmt->close();

        if (empty($fields)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'This form has no fields configured.']);
            exit;
        }

        $valid_values = ['Excellent', 'Good', 'Average', 'Poor'];
        $missing_fields = [];
        $invalid_fields = [];
        $answers = []; // [ ['field_id'=>, 'label'=>, 'type'=>, 'value'=>], ... ]

        foreach ($fields as $field) {
            $post_name = 'field_' . $field['id'];
            $value = isset($_POST[$post_name]) ? trim($_POST[$post_name]) : '';

            if ($value === '') {
                $missing_fields[] = $field['field_label'];
                continue;
            }
            if ($field['field_type'] === 'rating' && !in_array($value, $valid_values, true)) {
                $invalid_fields[] = $field['field_label'];
                continue;
            }

            $answers[] = [
                'field_id' => $field['id'],
                'label' => $field['field_label'],
                'type' => $field['field_type'],
                'value' => $value
            ];
        }

        if (!empty($missing_fields)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Please fill in all required fields: ' . implode(', ', $missing_fields)
            ]);
            exit;
        }
        if (!empty($invalid_fields)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid rating value for: ' . implode(', ', $invalid_fields)
            ]);
            exit;
        }

        try {
            $conn->begin_transaction();

            $stmt = $conn->prepare(
                "INSERT INTO feedback_submissions (link_id, program_id, batch_id, module_id, lecturer_id, program_name, module_name, lecturer_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param(
                "isiiisss",
                $link_row_id,
                $program_id,
                $batch_id,
                $module_id,
                $lecturer_id,
                $program_name,
                $module_name,
                $lecturer_name
            );
            $stmt->execute();
            $submission_id = $stmt->insert_id;
            $stmt->close();

            $stmt = $conn->prepare(
                "INSERT INTO feedback_submission_answers (submission_id, field_id, field_label, field_type, answer_value) VALUES (?, ?, ?, ?, ?)"
            );
            foreach ($answers as $ans) {
                $stmt->bind_param(
                    "iisss",
                    $submission_id,
                    $ans['field_id'],
                    $ans['label'],
                    $ans['type'],
                    $ans['value']
                );
                $stmt->execute();
            }
            $stmt->close();

            $conn->commit();

            if ($feedback_id) {
                setcookie("feedback_submitted_" . $feedback_id, "true", time() + 86400, "/");
            }

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Feedback submitted successfully! Thank you for your feedback.'
            ]);
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Final year feedback submission error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'An error occurred while submitting your feedback. Please try again later.'
            ]);
        } finally {
            if (isset($conn) && $conn) {
                $conn->close();
            }
        }
        exit;
    }

    // =================================================================
    // NORMAL: unchanged - fixed 8-question layout, saved to `feedback`
    // =================================================================

    // Sanitize and get form data - IDs
    $program_id = isset($_POST['program_id']) ? trim($_POST['program_id']) : null;
    $batch_id = isset($_POST['batch_id']) && !empty($_POST['batch_id']) ? intval($_POST['batch_id']) : null;
    $module_id = isset($_POST['module_id']) && !empty($_POST['module_id']) ? intval($_POST['module_id']) : null;
    $lecturer_id = isset($_POST['lecturer_id']) && !empty($_POST['lecturer_id']) ? intval($_POST['lecturer_id']) : null;

    // Sanitize and get form data - Names
    $program_name = isset($_POST['program_name']) ? trim($_POST['program_name']) : '';
    $module_name = isset($_POST['module_name']) ? trim($_POST['module_name']) : '';
    $lecturer_name = isset($_POST['lecturer_name']) ? trim($_POST['lecturer_name']) : '';
    $presentation = isset($_POST['presentation']) ? trim($_POST['presentation']) : '';
    $preparation = isset($_POST['preparation']) ? trim($_POST['preparation']) : '';
    $syllabus_coverage = isset($_POST['syllabus_coverage']) ? trim($_POST['syllabus_coverage']) : '';
    $knowledge_subject = isset($_POST['knowledge_subject']) ? trim($_POST['knowledge_subject']) : '';
    $question_discussion = isset($_POST['question_discussion']) ? trim($_POST['question_discussion']) : '';
    $interaction_students = isset($_POST['interaction_students']) ? trim($_POST['interaction_students']) : '';
    $punctuality = isset($_POST['punctuality']) ? trim($_POST['punctuality']) : '';
    $overall = isset($_POST['overall']) ? trim($_POST['overall']) : '';
    $comments = isset($_POST['comments']) ? trim($_POST['comments']) : '';

    // Validate that all required fields are present
    $required_fields = [
        'program_name',
        'module_name',
        'lecturer_name',
        'presentation',
        'preparation',
        'syllabus_coverage',
        'knowledge_subject',
        'question_discussion',
        'interaction_students',
        'punctuality',
        'overall',
        'comments'
    ];

    $missing_fields = [];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            $missing_fields[] = $field;
        }
    }

    if (!empty($missing_fields)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Please fill in all required fields: ' . implode(', ', $missing_fields)
        ]);
        exit;
    }

    // Validate and sanitize string lengths
    if (strlen($program_name) > 255 || strlen($module_name) > 255 || strlen($lecturer_name) > 255) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'One or more fields exceed maximum length.'
        ]);
        exit;
    }

    // Validate enum values for radio buttons
    $enum_fields = [
        'presentation' => $presentation,
        'preparation' => $preparation,
        'syllabus_coverage' => $syllabus_coverage,
        'knowledge_subject' => $knowledge_subject,
        'question_discussion' => $question_discussion,
        'interaction_students' => $interaction_students,
        'punctuality' => $punctuality,
        'overall' => $overall
    ];

    $valid_values = ['Excellent', 'Good', 'Average', 'Poor'];

    foreach ($enum_fields as $field_name => $field_value) {
        if (!in_array($field_value, $valid_values, true)) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => "Invalid value for $field_name. Please select a valid rating."
            ]);
            exit;
        }
    }

    try {
        // Prepare SQL statement - includes IDs and removed created_at as it has DEFAULT current_timestamp()
        $sql = "INSERT INTO feedback (
            program_id,
            batch_id,
            module_id,
            lecturer_id,
            program_name, 
            module_name, 
            lecturer_name, 
            presentation, 
            preparation, 
            syllabus_coverage, 
            knowledge_subject, 
            question_discussion, 
            interaction_students, 
            punctuality, 
            overall, 
            comments
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        // Bind parameters - 4 IDs (s, i, i, i) + 12 string parameters = 16 total
        // s = string, i = integer
        $stmt->bind_param(
            "siiissssssssssss",
            $program_id,
            $batch_id,
            $module_id,
            $lecturer_id,
            $program_name,
            $module_name,
            $lecturer_name,
            $presentation,
            $preparation,
            $syllabus_coverage,
            $knowledge_subject,
            $question_discussion,
            $interaction_students,
            $punctuality,
            $overall,
            $comments
        );

        // Execute the statement
        if ($stmt->execute()) {
            // Security: Set a cookie to prevent multiple submissions for this link (1 day)
            if ($feedback_id) {
                setcookie("feedback_submitted_" . $feedback_id, "true", time() + 86400, "/");
            }

            // Return JSON response for AJAX handling
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Feedback submitted successfully! Thank you for your feedback.'
            ]);
        } else {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        // Close statement
        $stmt->close();
    } catch (Exception $e) {
        // Log error for debugging (don't expose to user)
        error_log("Feedback submission error: " . $e->getMessage());

        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'An error occurred while submitting your feedback. Please try again later.'
        ]);
    } finally {
        // Close connection
        if (isset($conn) && $conn) {
            $conn->close();
        }
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method. Please use POST.'
    ]);
}
