<?php

session_start();

header('Content-Type: application/json');

include("../database/connection.php");


// =====================================================================
// 1. AUTHENTICATION
// =====================================================================

if (!isset($_SESSION['username'])) {

    echo json_encode([
        'success' => false,
        'message' => 'Not authenticated'
    ]);

    exit();
}


// =====================================================================
// 2. READ JSON INPUT
// =====================================================================

$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {

    echo json_encode([
        'success' => false,
        'message' => 'Invalid request data'
    ]);

    exit();
}


// =====================================================================
// 3. VALIDATE REQUIRED DATA
// =====================================================================

$title = trim($input['title'] ?? '');

$questions = $input['questions'] ?? [];

if ($title === '') {

    echo json_encode([
        'success' => false,
        'message' => 'Please enter a form title'
    ]);

    exit();
}

if (!is_array($questions) || count($questions) === 0) {

    echo json_encode([
        'success' => false,
        'message' => 'Add at least one question'
    ]);

    exit();
}


// =====================================================================
// 4. FORM DATA
// =====================================================================

$form_id = !empty($input['form_id'])
    ? (int)$input['form_id']
    : 0;


$program_code = (
    isset($input['program_code']) &&
    $input['program_code'] !== '' &&
    $input['program_code'] !== null
)
    ? (int)$input['program_code']
    : null;


$description = trim($input['description'] ?? '');


$status = $input['status'] ?? 'draft';

if (!in_array($status, ['draft', 'published', 'closed'], true)) {
    $status = 'draft';
}


$collect_email = !empty($input['collect_email'])
    ? 1
    : 0;


$one_response = !empty($input['one_response_per_user'])
    ? 1
    : 0;


$username = $_SESSION['username'];


// =====================================================================
// 5. BANNER IMAGE
// =====================================================================

$banner_image = (
    isset($input['banner_image']) &&
    $input['banner_image'] !== ''
)
    ? trim($input['banner_image'])
    : null;


// =====================================================================
// 6. VALIDATE PROGRAM
// =====================================================================
// If a program is selected, make sure it actually exists in
// program_table.

if ($program_code !== null) {

    $program_stmt = $conn->prepare("
        SELECT program_code
        FROM program_table
        WHERE program_code = ?
        LIMIT 1
    ");

    if (!$program_stmt) {

        echo json_encode([
            'success' => false,
            'message' => 'Failed to prepare program validation query: ' . $conn->error
        ]);

        exit();
    }

    $program_stmt->bind_param(
        "i",
        $program_code
    );

    $program_stmt->execute();

    $program_result = $program_stmt->get_result();

    if ($program_result->num_rows === 0) {

        $program_stmt->close();

        echo json_encode([
            'success' => false,
            'message' => 'Selected program does not exist.'
        ]);

        exit();
    }

    $program_stmt->close();
}


// =====================================================================
// 7. START TRANSACTION
// =====================================================================

$conn->begin_transaction();


try {

    // =================================================================
    // 8. UPDATE EXISTING FORM
    // =================================================================

    if ($form_id > 0) {

        // -------------------------------------------------------------
        // Check whether form exists
        // -------------------------------------------------------------

        $check_stmt = $conn->prepare("
            SELECT id
            FROM forms
            WHERE id = ?
            LIMIT 1
        ");

        if (!$check_stmt) {
            throw new Exception(
                "Failed to prepare form check: " . $conn->error
            );
        }

        $check_stmt->bind_param(
            "i",
            $form_id
        );

        $check_stmt->execute();

        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows === 0) {

            $check_stmt->close();

            throw new Exception(
                "The form you are trying to update does not exist."
            );
        }

        $check_stmt->close();


        // -------------------------------------------------------------
        // Update form
        // -------------------------------------------------------------

        $stmt = $conn->prepare("
            UPDATE forms
            SET
                program_code = ?,
                title = ?,
                description = ?,
                banner_image = ?,
                status = ?,
                collect_email = ?,
                one_response_per_user = ?
            WHERE id = ?
        ");

        if (!$stmt) {
            throw new Exception(
                "Failed to prepare form update: " . $conn->error
            );
        }


        /*
         * Types:
         *
         * i = program_code
         * s = title
         * s = description
         * s = banner_image
         * s = status
         * i = collect_email
         * i = one_response
         * i = form_id
         */

        $stmt->bind_param(
            "issssiii",
            $program_code,
            $title,
            $description,
            $banner_image,
            $status,
            $collect_email,
            $one_response,
            $form_id
        );


        if (!$stmt->execute()) {

            throw new Exception(
                "Failed to update form: " . $stmt->error
            );
        }

        $stmt->close();


        // -------------------------------------------------------------
        // Delete old questions
        // -------------------------------------------------------------
        // Existing question options should be removed through the
        // database relationship/cascade if configured.
        // Questions are then recreated below.

        $del = $conn->prepare("
            DELETE FROM form_questions
            WHERE form_id = ?
        ");

        if (!$del) {
            throw new Exception(
                "Failed to prepare question deletion: " . $conn->error
            );
        }

        $del->bind_param(
            "i",
            $form_id
        );

        if (!$del->execute()) {

            throw new Exception(
                "Failed to remove old questions: " . $del->error
            );
        }

        $del->close();
    } else {

        // =================================================================
        // 9. INSERT NEW FORM
        // =================================================================

        $stmt = $conn->prepare("
            INSERT INTO forms
            (
                program_code,
                title,
                description,
                banner_image,
                created_by,
                status,
                collect_email,
                one_response_per_user
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?
            )
        ");

        if (!$stmt) {

            throw new Exception(
                "Failed to prepare form insert: " . $conn->error
            );
        }


        /*
         * Types:
         *
         * i = program_code
         * s = title
         * s = description
         * s = banner_image
         * s = username
         * s = status
         * i = collect_email
         * i = one_response
         */

        $stmt->bind_param(
            "isssssii",
            $program_code,
            $title,
            $description,
            $banner_image,
            $username,
            $status,
            $collect_email,
            $one_response
        );


        if (!$stmt->execute()) {

            throw new Exception(
                "Failed to create form: " . $stmt->error
            );
        }


        $form_id = $stmt->insert_id;

        $stmt->close();
    }


    // =================================================================
    // 10. PREPARE QUESTION INSERT
    // =================================================================

    $q_stmt = $conn->prepare("
        INSERT INTO form_questions
        (
            form_id,
            question_text,
            question_image,
            question_type,
            is_required,
            order_index,
            scale_min,
            scale_max
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?
        )
    ");

    if (!$q_stmt) {

        throw new Exception(
            "Failed to prepare question insert: " . $conn->error
        );
    }


    // =================================================================
    // 11. PREPARE OPTION INSERT
    // =================================================================

    $o_stmt = $conn->prepare("
        INSERT INTO question_options
        (
            question_id,
            option_text,
            order_index
        )
        VALUES
        (
            ?,
            ?,
            ?
        )
    ");

    if (!$o_stmt) {

        throw new Exception(
            "Failed to prepare option insert: " . $conn->error
        );
    }


    // =================================================================
    // 12. INSERT QUESTIONS
    // =================================================================

    $order = 1;


    foreach ($questions as $q) {

        if (!is_array($q)) {
            continue;
        }


        // -------------------------------------------------------------
        // Question text
        // -------------------------------------------------------------

        $qtext = trim(
            $q['question_text'] ?? ''
        );


        // Skip empty questions

        if ($qtext === '') {
            continue;
        }


        // -------------------------------------------------------------
        // Question type
        // -------------------------------------------------------------

        $allowed_types = [
            'short_text',
            'paragraph',
            'multiple_choice',
            'checkbox',
            'dropdown',
            'linear_scale',
            'date',
            'time',
            'file_upload'
        ];


        $qtype = $q['question_type'] ?? 'short_text';


        if (!in_array($qtype, $allowed_types, true)) {
            $qtype = 'short_text';
        }


        // -------------------------------------------------------------
        // Required
        // -------------------------------------------------------------

        $required = !empty($q['is_required'])
            ? 1
            : 0;


        // -------------------------------------------------------------
        // Scale
        // -------------------------------------------------------------

        $scale_min = (
            isset($q['scale_min']) &&
            $q['scale_min'] !== '' &&
            $q['scale_min'] !== null
        )
            ? (int)$q['scale_min']
            : null;


        $scale_max = (
            isset($q['scale_max']) &&
            $q['scale_max'] !== '' &&
            $q['scale_max'] !== null
        )
            ? (int)$q['scale_max']
            : null;


        // -------------------------------------------------------------
        // Question image
        // -------------------------------------------------------------

        $qimage = (
            !empty($q['question_image'])
        )
            ? trim($q['question_image'])
            : null;


        // -------------------------------------------------------------
        // Insert question
        // -------------------------------------------------------------

        $q_stmt->bind_param(
            "isssiiii",
            $form_id,
            $qtext,
            $qimage,
            $qtype,
            $required,
            $order,
            $scale_min,
            $scale_max
        );


        if (!$q_stmt->execute()) {

            throw new Exception(
                "Failed to save question: " . $q_stmt->error
            );
        }


        $question_id = $q_stmt->insert_id;


        // =================================================================
        // 13. INSERT OPTIONS
        // =================================================================

        if (
            isset($q['options']) &&
            is_array($q['options'])
        ) {

            $opt_order = 1;


            foreach ($q['options'] as $opt) {

                $opt = trim((string)$opt);


                if ($opt === '') {
                    continue;
                }


                $o_stmt->bind_param(
                    "isi",
                    $question_id,
                    $opt,
                    $opt_order
                );


                if (!$o_stmt->execute()) {

                    throw new Exception(
                        "Failed to save question option: " . $o_stmt->error
                    );
                }


                $opt_order++;
            }
        }


        $order++;
    }


    // =================================================================
    // 14. CLOSE STATEMENTS
    // =================================================================

    $q_stmt->close();
    $o_stmt->close();


    // =================================================================
    // 15. COMMIT
    // =================================================================

    $conn->commit();


    // =================================================================
    // 16. SUCCESS RESPONSE
    // =================================================================

    echo json_encode([
        'success' => true,
        'message' => $form_id > 0
            ? 'Form updated successfully.'
            : 'Form created successfully.',
        'form_id' => $form_id,
        'program_code' => $program_code
    ]);
} catch (Throwable $e) {

    // =================================================================
    // 17. ROLLBACK
    // =================================================================

    $conn->rollback();


    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}


// =====================================================================
// 18. CLOSE CONNECTION
// =====================================================================

$conn->close();
