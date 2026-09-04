<?php
session_start();
include("../database/connection.php");
include("../external_redirect.php");

// A GET request, or a missing/tampered form_id, is not a real submission
// — send it away instead of exposing app internals.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['form_id']) || !ctype_digit((string)$_POST['form_id'])) {
    header("Location: " . EXTERNAL_REDIRECT_URL);
    exit();
}

$form_id = intval($_POST['form_id']);

// Confirm the form exists and is accepting responses
$stmt = $conn->prepare("SELECT * FROM forms WHERE id = ? AND status = 'published' AND accepting_responses = 1");
$stmt->bind_param("i", $form_id);
$stmt->execute();
$form = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$form) {
    header("Location: " . EXTERNAL_REDIRECT_URL);
    exit();
}

// Optional: enforce one response per person by email
if ($form['one_response_per_user'] && !empty($_POST['respondent_email'])) {
    $check = $conn->prepare("SELECT id FROM form_responses WHERE form_id = ? AND respondent_email = ?");
    $check->bind_param("is", $form_id, $_POST['respondent_email']);
    $check->execute();
    if ($check->get_result()->fetch_assoc()) {
        echo "<div class='container mt-5'><h4>You have already submitted a response to this form.</h4></div>";
        exit();
    }
    $check->close();
}

$email = $_POST['respondent_email'] ?? null;
$ip = $_SERVER['REMOTE_ADDR'] ?? null;

$conn->begin_transaction();

try {
    $resp_stmt = $conn->prepare("INSERT INTO form_responses (form_id, respondent_email, ip_address) VALUES (?,?,?)");
    $resp_stmt->bind_param("iss", $form_id, $email, $ip);
    $resp_stmt->execute();
    $response_id = $resp_stmt->insert_id;
    $resp_stmt->close();

    // Fetch this form's questions so we know which POST/FILES fields to read
    $q_stmt = $conn->prepare("SELECT id, question_type FROM form_questions WHERE form_id = ?");
    $q_stmt->bind_param("i", $form_id);
    $q_stmt->execute();
    $questions = $q_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $q_stmt->close();

    $ans_stmt = $conn->prepare("INSERT INTO response_answers (response_id, question_id, answer_text, answer_type) VALUES (?,?,?,?)");

    // Allowed file types for a respondent's file_upload answer, and a 5MB cap —
    // same rule as the admin-side image uploader in upload_image.php, plus a
    // couple of document types since respondents may attach more than photos.
    $allowedFileExt = [
        'image/jpeg'                                                            => 'jpg',
        'image/png'                                                             => 'png',
        'image/gif'                                                             => 'gif',
        'image/webp'                                                            => 'webp',
        'application/pdf'                                                       => 'pdf',
        'application/msword'                                                    => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    ];
    $maxFileBytes = 5 * 1024 * 1024;
    $uploadDir = __DIR__ . '/uploads/responses/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    foreach ($questions as $q) {
        $field = "q_{$q['id']}";
        $answer = null;
        $answerType = 'text';

        if ($q['question_type'] === 'file_upload') {
            if (!empty($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES[$field];
                if ($file['size'] <= $maxFileBytes) {
                    // Detect real MIME type — images via getimagesize, everything
                    // else (pdf/doc/docx) via finfo, never trust the client.
                    $imgInfo = @getimagesize($file['tmp_name']);
                    $mime = $imgInfo['mime'] ?? (function_exists('finfo_open')
                        ? finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file['tmp_name'])
                        : $file['type']);

                    if (isset($allowedFileExt[$mime])) {
                        $ext = $allowedFileExt[$mime];
                        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
                        if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                            $answer = "uploads/responses/{$filename}";
                            $answerType = 'file';
                        }
                    }
                }
            }
        } elseif ($q['question_type'] === 'checkbox') {
            if (!empty($_POST[$field]) && is_array($_POST[$field])) {
                $answer = implode(', ', array_map('trim', $_POST[$field]));
            }
        } else {
            if (isset($_POST[$field]) && $_POST[$field] !== '') {
                $answer = trim($_POST[$field]);
            }
        }

        if ($answer !== null) {
            $ans_stmt->bind_param("iiss", $response_id, $q['id'], $answer, $answerType);
            $ans_stmt->execute();
        }
    }
    $ans_stmt->close();

    $conn->commit();
    $conn->close();
?>



    <!DOCTYPE html>
    <html>

    <head>
        <meta charset="UTF-8">
        <title>Thank you</title>
        <link rel="stylesheet" href="../css/sb-admin-2.min.css">
        <meta http-equiv="refresh" content="2;url=https://bms.ac.lk">



        <script>
            setTimeout(function() {
                window.location.href = "https://bms.ac.lk";
            }, 1000);
        </script>
    </head>

    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>Response Submitted</title>

        <!-- Font Awesome -->
        <link rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

        <style>
            * {
                box-sizing: border-box;
            }

            html,
            body {
                margin: 0;
                padding: 0;
                width: 100%;
                min-height: 100%;
            }

            body {
                background: #f0f2f5;
                font-family: Arial, Helvetica, sans-serif;
            }

            .success-wrapper {
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }

            .success-card {
                width: 100%;
                max-width: 520px;
                background: #ffffff;
                border-radius: 16px;
                padding: 45px 30px;
                text-align: center;
                box-shadow: 0 10px 35px rgba(0, 0, 0, 0.08);
            }

            /* Success Icon */
            .success-icon {
                width: 80px;
                height: 80px;
                margin: 0 auto 22px;

                display: flex;
                align-items: center;
                justify-content: center;

                background: #e8f7ee;
                color: #28a745;

                border-radius: 50%;

                font-size: 38px;
            }

            .success-icon i {
                display: block;
            }

            .success-card h4 {
                margin: 0;
                color: #212529;
                font-size: 26px;
                font-weight: 600;
            }

            .success-message {
                margin: 12px 0 0;
                color: #555;
                font-size: 16px;
                line-height: 1.6;
            }

            .redirect-message {
                margin: 25px 0 0;
                color: #888;
                font-size: 14px;
            }

            .redirect-message i {
                margin-right: 6px;
            }

            /* Spinner */
            .redirect-message .fa-spinner {
                animation: fa-spin 1s linear infinite;
            }

            @keyframes fa-spin {
                from {
                    transform: rotate(0deg);
                }

                to {
                    transform: rotate(360deg);
                }
            }

            @media (max-width: 576px) {

                .success-wrapper {
                    padding: 15px;
                }

                .success-card {
                    padding: 35px 20px;
                }

                .success-icon {
                    width: 70px;
                    height: 70px;
                    font-size: 32px;
                }

                .success-card h4 {
                    font-size: 22px;
                }

                .success-message {
                    font-size: 15px;
                }
            }
        </style>
    </head>

    <body>

        <div class="success-wrapper">

            <div class="success-card">

                <!-- Success Icon -->
                <div class="success-icon">
                    <i class="fa-solid fa-check"></i>
                </div>

                <h4>Thank you!</h4>

                <p class="success-message">
                    Your response has been recorded successfully.
                </p>

                <p class="redirect-message">
                    <i class="fa-solid fa-spinner"></i>
                    Redirecting you back shortly...
                </p>

            </div>

        </div>

    </body>

    </html>

    </html>
<?php

} catch (Exception $e) {
    $conn->rollback();
    echo "<div class='container mt-5'><h4>Something went wrong. Please try again.</h4></div>";
}
