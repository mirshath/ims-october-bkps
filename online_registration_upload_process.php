<?php
session_start();
date_default_timezone_set('Asia/Colombo');
include("database/connection.php");

// ---- PHPMailer for email notifications ----
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once("vendor/autoload.php");

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: online_registration_data.php");
    exit();
}


$student_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if ($student_id <= 0) {
    die("Invalid student ID");
}

// Get batch id from form, allocateSection
$selected_batch_id = isset($_POST['batch_id']) ? intval($_POST['batch_id']) : 0;
// Get first name from POST, using same field name as used in online_registration_data.php form
$first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';

// Save/Update form fields to students_temporary_registration before processing
if (isset($_POST['id'])) {
    $conditional_offer_letter = isset($_POST['conditional_offer_letter']) ? 1 : 0;
    $conditional_offer_letter_text = isset($_POST['conditional_offer_letter_text']) ? trim($_POST['conditional_offer_letter_text']) : '';
    $conditional_offer_letter_text_02 = isset($_POST['conditional_offer_letter_text_02']) ? trim($_POST['conditional_offer_letter_text_02']) : '';
    $conditional_offer_letter_text_03 = isset($_POST['conditional_offer_letter_text_03']) ? trim($_POST['conditional_offer_letter_text_03']) : '';
    $conditional_offer_letter_text_04 = isset($_POST['conditional_offer_letter_text_04']) ? trim($_POST['conditional_offer_letter_text_04']) : '';

    $nic = isset($_POST['nic']) ? trim($_POST['nic']) : '';
    $passport = isset($_POST['passport']) ? trim($_POST['passport']) : '';
    $fname = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
    $lname = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
    $fullname = trim($fname . ' ' . $lname);

    // We update these specifically as requested, and could potentially update others if needed
    $updateFieldsSql = "UPDATE students_temporary_registration SET 
                        conditional_offer_letter = ?, 
                        conditional_offer_letter_text = ?, 
                        conditional_offer_letter_text_02 = ?, 
                        conditional_offer_letter_text_03 = ?, 
                        conditional_offer_letter_text_04 = ?,
                        nic = ?,
                        passport = ?,
                        firstname = ?,
                        lastname = ?,
                        fullname = ?
                        WHERE id = ?";
    if ($updateStmt = $conn->prepare($updateFieldsSql)) {
        $updateStmt->bind_param("isssssssssi", $conditional_offer_letter, $conditional_offer_letter_text, $conditional_offer_letter_text_02, $conditional_offer_letter_text_03, $conditional_offer_letter_text_04, $nic, $passport, $fname, $lname, $fullname, $student_id);
        $updateStmt->execute();
        $updateStmt->close();
    }
}


// Fetch student details
$stmt = $conn->prepare("SELECT * FROM students_temporary_registration WHERE id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$res = $stmt->get_result();
$studentDetails = $res->fetch_assoc();
$stmt->close();

if (!$studentDetails) {
    die("Student not found");
}

// Handle Rejection Action
if (isset($_POST['action']) && $_POST['action'] === 'reject') {
    // 1. Update status to rejected
    $updateSql = "UPDATE students_temporary_registration SET approved = '2', approved_by = ? WHERE id = ?";
    if ($stmt = $conn->prepare($updateSql)) {
        $username = $_SESSION['username'] ?? 'System';
        $stmt->bind_param("si", $username, $student_id);
        $stmt->execute();
        $stmt->close();
    }

    // 2. Send Rejection Email
    $mail = new PHPMailer(true);
    $emailSent = false;
    $errorMsg = "";

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.office365.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'noreply@bms.ac.lk';
        $mail->Password = 'Lox51527';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('noreply@bms.ac.lk', 'Business Management School');
        $mail->addAddress($studentDetails['email'], $studentDetails['firstname'] . ' ' . $studentDetails['lastname']);

        $mail->isHTML(true);
        $mail->Subject = 'Application Status - Business Management School';

        $mail->Body = '
            <!DOCTYPE html>
            <html>
            <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            </head>

            <body style="margin:0; padding:0; background-color:#f4f6f8;">

            <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f8; padding:20px 0;">
            <tr>
            <td align="center">

            <!-- Main Container -->
            <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:10px; overflow:hidden; font-family:Arial, Helvetica, sans-serif;">

            <!-- Banner -->
            <tr>
            <td>
            <img src="https://ims.bms.ac.lk/admin/uploads/img/Registration-form-Banner.jpg" 
                alt="Business Management School" 
                width="600" 
                style="display:block; width:100%; height:auto; border:0;">
            </td>
            </tr>

            <!-- Content -->
            <tr>
            <td style="padding:35px 40px; color:#1d2939;">

            <h2 style="margin:0 0 15px 0; color:#063970; font-size:26px;">
            Application Update
            </h2>

            <hr style="border:none; height:3px; background-color:#063970; margin:20px 0;">

            <p style="font-size:15px; line-height:1.6; margin:0 0 18px 0;">
            Dear <strong>' . htmlspecialchars($studentDetails['firstname']) . '</strong>,
            </p>

            <p style="font-size:15px; line-height:1.6; margin:0 0 18px 0;">
            Thank you for your interest in the 
            <strong style="color:#034ea2;">' . htmlspecialchars($studentDetails['program']) . '</strong> programme at
            <strong>Business Management School (BMS)</strong>.
            </p>

            <p style="font-size:15px; line-height:1.6; margin:0 0 18px 0;">
            We have carefully reviewed your application and the documents submitted.
            </p>

            <p style="font-size:15px; line-height:1.6; color:#b00020; font-weight:bold; margin:0 0 18px 0;">
            We regret to inform you that we are unable to proceed with your application at this time.
            </p>

            <p style="font-size:15px; line-height:1.6; margin:0 0 18px 0;">
            We sincerely appreciate the time and effort you invested in your application and wish you every success in your future academic and professional journey.
            </p>

            <p style="font-size:15px; line-height:1.6; margin:0 0 30px 0;">
            If you require further clarification, please feel free to contact our Admissions Office.
            </p>

            <p style="margin:0;">
            Best Regards,<br>
            <strong style="color:#063970;">Admissions Team</strong><br>
            Business Management School
            </p>

            </td>
            </tr>

            <!-- Footer -->
            <tr>
            <td style="background:#f0f2f5; text-align:center; padding:18px; font-size:12px; color:#777;">
            &copy; ' . date("Y") . ' Business Management School. All rights reserved.<br>
            62, Dharmapala Mawatha, Colombo 03, Sri Lanka
            </td>
            </tr>

            </table>
            <!-- End Container -->

            </td>
            </tr>
            </table>

            </body>
            </html>
            ';


        $mail->send();
        $emailSent = true;
    } catch (Exception $e) {
        $errorMsg = $mail->ErrorInfo;
    }

    $msg = "Application rejected successfully.";
    if ($emailSent) {
        $msg .= " Rejection email sent to student.";
    } else {
        $msg .= " However, email could not be sent. Error: " . $errorMsg;
    }

    echo "<script>
        alert('" . addslashes($msg) . "');
        window.location.href = 'online_registration_data.php';
    </script>";
    exit;
}

$temporaryId = $studentDetails['temp_id'] ?? '';

// Photo/Doc0 code
$studentPhotoUrl = "";
$studentPhotoPath = "";
if (!empty($temporaryId)) {
    $photoQ = $conn->prepare("SELECT doc0, program, batch FROM students_temporary_document 
        LEFT JOIN students_temporary_registration ON students_temporary_registration.temp_id = students_temporary_document.temp_id
        WHERE students_temporary_document.temp_id = ? LIMIT 1");
    if ($photoQ) {
        $photoQ->bind_param('s', $temporaryId);
        $photoQ->execute();
        $photoQRes = $photoQ->get_result();
        if ($photoRow = $photoQRes->fetch_assoc()) {
            $doc0File = $photoRow['doc0'] ?? "";
            $programFolder = $photoRow['program'] ?? ($studentDetails['program'] ?? '');
            $batchFolder = $photoRow['batch'] ?? ($studentDetails['batch'] ?? '');

            // Use very simple sanitize: replace non-A-Za-z0-9_\- with _
            $cleanProgram = preg_replace('/[^A-Za-z0-9_\-]/', '', $programFolder);
            $cleanBatch = preg_replace('/[^A-Za-z0-9_\-]/', '', $batchFolder);
            $cleanTempId = preg_replace('/[^A-Za-z0-9_\-]/', '_', $temporaryId);

            if (!empty($doc0File)) {
                $webPath = "uploaded_documents/{$cleanProgram}/{$cleanBatch}/{$cleanTempId}/{$doc0File}";
                $absolutePath = dirname(__FILE__) . "/uploaded_documents/{$cleanProgram}/{$cleanBatch}/{$cleanTempId}/{$doc0File}";
                if (file_exists($absolutePath)) {
                    $studentPhotoUrl = $webPath;
                    $studentPhotoPath = $absolutePath;
                } else {
                    $studentPhotoUrl = $webPath;
                }
            }
        }
        $photoQ->close();
    }
}
if (empty($studentPhotoUrl)) {
    $studentPhotoUrl = "https://www.shutterstock.com/image-vector/unknown-person-hidden-covered-masked-600nw-1552977773.jpg";
}

// Helper (for consistent output of info)
function showVal($v)
{
    if (is_array($v))
        return htmlspecialchars(implode(', ', $v));
    return htmlspecialchars($v ?? '');
}



// Get university ID
$universityId = 1;
if (!empty($studentDetails['program'])) {
    $uniQuery = "SELECT university_id FROM program_table WHERE program_name = ? LIMIT 1";
    if ($stmt = $conn->prepare($uniQuery)) {
        $stmt->bind_param('s', $studentDetails['program']);
        $stmt->execute();
        $uRes = $stmt->get_result();
        if ($uRow = $uRes->fetch_assoc()) {
            $universityId = $uRow['university_id'];
        }
        $stmt->close();
    }
}

// Get program ID and details
$programId = null;
$programDuration = '-';
$programFeeLKR = 0;
$programFeeGBP = 0;
$programFeeUSD = 0;
$programFeeEuro = 0;

if (!empty($studentDetails['program'])) {
    $q = "SELECT program_code, duration, course_fee_lkr, course_fee_gbp, course_fee_usd, course_fee_euro FROM program_table WHERE university_id = ? AND program_name = ? LIMIT 1";
    if ($stmt = $conn->prepare($q)) {
        $stmt->bind_param('is', $universityId, $studentDetails['program']);
        $stmt->execute();
        $r = $stmt->get_result();
        if ($row = $r->fetch_assoc()) {
            $programId = $row['program_code'];
            $programDuration = $row['duration'];
            $programFeeLKR = $row['course_fee_lkr'];
            $programFeeGBP = $row['course_fee_gbp'];
            $programFeeUSD = $row['course_fee_usd'];
            $programFeeEuro = $row['course_fee_euro'];
        }
        $stmt->close();
    }
}

// IMPORTANT: Use the SELECTED batch_id from the form, not from temporary registration
$batchId = $selected_batch_id;
$batchLabel = '-';
$batchName = '';
$batchIntake = '-';
$attendance = '-';
$awardedBy = '-';
$qualificationLevel = '-';
$intakeNo = '-';
$yearNo = '-';
$recognizedBy = '-';
$accreditedBy = '-';

// Get batch details using the SELECTED batch_id
if ($batchId > 0) {
    $bq = "SELECT id, batch_name, batch_no, year_no, batch_intake, attendance, awarded_by, qualification_level, intake_no, recognized_by, accredited_by FROM batch_table WHERE id = ? LIMIT 1";
    if ($stmt = $conn->prepare($bq)) {
        $stmt->bind_param('i', $batchId);
        $stmt->execute();
        $br = $stmt->get_result();
        if ($bRow = $br->fetch_assoc()) {
            $batchLabel = $bRow['batch_name'];
            $batchName = $bRow['batch_name'];
            $batchIntake = $bRow['batch_intake'];
            $attendance = $bRow['attendance'];
            $awardedBy = $bRow['awarded_by'];
            $qualificationLevel = $bRow['qualification_level'];
            $recognizedBy = $bRow['recognized_by'];
            $accreditedBy = $bRow['accredited_by'];
            $yearNo = $bRow['year_no'];
            $intakeNo = $bRow['intake_no'];
        }
        $stmt->close();
    }
}

// Get batch payment details
$batchDetails = null;
$batchDetailsError = null;
if ($batchId) {
    $batchDetailsQuery = "
        SELECT id, programme_id, batch_id, course_fee_lkr, uni_fee_gbp, uni_fee_usd, uni_fee_euro,
               register_date, installment_no, registration_fee, created_at, only_course_fee
        FROM payment_batch_allocation
        WHERE batch_id = ?
        LIMIT 1
    ";
    if ($stmt = $conn->prepare($batchDetailsQuery)) {
        $stmt->bind_param('i', $batchId);
        $stmt->execute();
        $br = $stmt->get_result();
        if ($row = $br->fetch_assoc())
            $batchDetails = $row;
        else
            $batchDetailsError = "No payment batch allocation details found for the selected batch.";
        $stmt->close();
    }
}

// Get qualifications
$qualifications = [];

// O/L Results
$olQuery = "SELECT subject, grade, exam_year, school FROM student_ol_results WHERE registration_id = ? ORDER BY id";
if ($stmt = $conn->prepare($olQuery)) {
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $olResult = $stmt->get_result();
    while ($row = $olResult->fetch_assoc()) {
        $qualifications['ol'][] = $row;
    }
    $stmt->close();
}

// A/L Results
$alQuery = "SELECT subject, grade, exam_year, school FROM student_al_results WHERE registration_id = ? ORDER BY id";
if ($stmt = $conn->prepare($alQuery)) {
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $alResult = $stmt->get_result();
    while ($row = $alResult->fetch_assoc()) {
        $qualifications['al'][] = $row;
    }
    $stmt->close();
}

// Academic Qualifications
$acadQuery = "SELECT qualification, institution, year FROM student_academic_qualifications WHERE registration_id = ? ORDER BY id";
if ($stmt = $conn->prepare($acadQuery)) {
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $acadResult = $stmt->get_result();
    while ($row = $acadResult->fetch_assoc()) {
        $qualifications['academic'][] = $row;
    }
    $stmt->close();
}

// Other Qualifications
$otherQuery = "SELECT details FROM student_other_qualifications WHERE registration_id = ? ORDER BY id";
if ($stmt = $conn->prepare($otherQuery)) {
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $otherResult = $stmt->get_result();
    while ($row = $otherResult->fetch_assoc()) {
        $qualifications['other'][] = $row;
    }
    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Application Card</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
        }

        .app-card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            max-width: 210mm;
            margin: 0 auto 20px auto;
        }

        .pdf-page {
            page-break-after: always;
            page-break-inside: avoid;
        }

        .pdf-page:last-child {
            page-break-after: auto;
        }

        .new-page-section {
            page-break-before: always;
        }

        .header-section {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid rgb(4 45 92) !important;
            padding-bottom: 15px;
        }

        .hr-section {
            /* text-align: center; */
            margin-bottom: 30px;
            border-bottom: 2px solid rgb(4 45 92) !important;
            padding-bottom: 15px;
        }

        .header-section h1 {
            color: #1e40af;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .header-section h2 {
            color: #64748b;
            font-size: 18px;
            font-weight: 500;
        }

        .photo-section {
            text-align: center;
            margin-bottom: 25px;
        }

        .photo-section img {
            width: 150px;
            height: 180px;
            object-fit: cover;
            border: 3px solid #e2e8f0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            background: rgb(4 45 92) !important;
            color: white;
            padding: 2px 20px;
            /* border-radius: 6px; */
            font-size: 18px;
            font-weight: 600;
            margin: 10px 0 10px 0;
            /* box-shadow: 0 2px 4px rgba(59, 130, 246, 0.3); */
        }

        .info-group {
            margin-bottom: 18px;
            page-break-inside: avoid;
        }

        .label {
            font-weight: 600;
            color: #475569;
            font-size: 13px;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .label-small {
            font-weight: 600;
            color: #64748b;
            font-size: 11px;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .field-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 10px 15px;
            border-radius: 6px;
            font-size: 14px;
            color: #1e293b;
            min-height: 38px;
        }

        .table-custom {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 13px;
            page-break-inside: auto;
        }

        .table-custom thead {
            background: rgb(4 45 92) !important;
            color: white;
        }

        .table-custom th {
            padding: 10px 12px;
            text-align: left;
            font-weight: 600;
            border: 1px solid #e2e8f0;
        }

        .table-custom td {
            padding: 10px 12px;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
        }

        .table-custom tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        .table-custom tbody tr:hover {
            background-color: #eff6ff;
        }

        .bottom-print-btn-area {
            text-align: center;
            margin-top: 30px;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .bottom-print-btn-area button {
            margin: 0 10px;
            padding: 12px 30px;
            font-size: 16px;
            font-weight: 600;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .bottom-print-btn-area button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .app-card {
                box-shadow: none;
                padding: 15mm;
                max-width: 100%;
                margin: 0;
            }

            .no-print {
                display: none !important;
            }

            .pdf-page {
                page-break-after: always;
            }

            .pdf-page:last-child {
                page-break-after: auto;
            }

            .new-page-section {
                page-break-before: always;
            }
        }
    </style>
</head>

<body>
    <!-- <div class="container-fluid" > -->
    <div class="container-fluid">
        <!-- Application Card Section -->
        <div class="app-card pdf-page" id="application-card">
            <div class="header-section">
                <h1 style="font-size: 20px;">Application for Admission </h1>
            </div>

            <div class="row hr-section" style="align-items: flex-start; margin-bottom: 20px;">
                <div class="col-md-8" style="display: flex; flex-direction: column; align-items: flex-start;">
                    <!-- Logo on top -->
                    <img src="admin\uploads\company_profiles\BMSCAMPUSLOGOFINAL.jpg" alt="Logo"
                        style="height: 80px; margin-bottom: 10px;">
                    <!-- Program name below the logo -->

                    <div
                        style="border: 1px solid #4f8fc0; margin-top: 80px; border-radius: 6px; padding: 6px 15px; background: #f8fafc; font-weight: bold; font-size: 1.0em; min-width: 200px; display: inline-block;">
                        <?php echo htmlspecialchars($studentDetails['program'] ?? ''); ?>
                    </div>
                </div>
                <div class="col-md-4 text-right"
                    style="display: flex; justify-content: flex-end; align-items: center; flex-direction: column;">
                    <div class="photo-section"
                        style="width:100px;height:120px;display:flex;align-items:center;justify-content:center;">
                        <img src="<?php echo htmlspecialchars($studentPhotoUrl); ?>" alt="Student Photo"
                            crossorigin="anonymous"
                            style="width:140px;height:160px;object-fit:contain;object-position:center;border:1px solid #a0aec0;border-radius:4px;background:#fff;box-shadow:0 2px 6px rgba(0,0,0,0.07);">
                    </div>

                    Student ID
                    <div
                        style="border: 1px solid #4f8fc0; border-radius: 6px; padding: 20px 18px; background: #f8fafc; font-size: 1.1em; font-weight: bold; letter-spacing: 1px; min-width: 260px; text-align: center;">

                    </div>
                </div>
            </div>

            <!-- --------------------------------------------------------------------------------------------------  -->
            <!-- --------------------------------------------------------------------------------------------------  -->
            <div class="section-title">1. Name of the Student</div>

            <div class="row table">
                <div class="col-md-12" style="display: none; ">
                    <div class="info-group d-flex align-items-center" style="gap: 0; display:flex; line-height:1.0;">
                        <div class="label" style="flex: 0 0 170px; text-align:left; line-height:1.0;">Title</div>
                        <div class="field-box" style="flex: 1;text-align:left; line-height:1.0;">
                            <?php echo showVal($studentDetails['title']); ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="info-group d-flex align-items-center" style="gap: 0; display:flex; line-height:1.0;">
                        <div class="label" style="flex: 0 0 170px; text-align:left; line-height:5px;">First Name</div>
                        <div class="field-box" style="flex: 1;text-align:left; line-height:1.0;">
                            <?php echo showVal($studentDetails['firstname']); ?>
                            <!-- <?= $first_name ?> -->
                        </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="info-group d-flex align-items-center" style="gap: 0; display:flex; line-height:1.0;">
                        <div class="label" style="flex: 0 0 170px; text-align:left; line-height:1.0;">Sure Name</div>
                        <div class="field-box" style="flex: 1;text-align:left; line-height:1.0;">
                            <?php echo showVal($studentDetails['lastname']); ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="info-group d-flex align-items-center" style="gap: 0; display:flex; line-height:1.0;">
                        <div class="label" style="flex: 0 0 170px; text-align:left; line-height:1.0;">Full Name</div>
                        <div class="field-box" style="flex: 1;text-align:left; line-height:1.0;">
                            <?php echo showVal($studentDetails['fullname']); ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="info-group d-flex align-items-center" style="gap: 0; display:flex; line-height:1.0;">
                        <div class="label" style="flex: 0 0 170px; text-align:left; line-height:1.0;">Name for
                            Certificate</div>
                        <div class="field-box" style="flex: 1;text-align:left; line-height:1.0;">
                            <?php echo showVal($studentDetails['certificate_name']); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- --------------------------------------------------------------------------------------------------  -->
            <!-- --------------------------------------------------------------------------------------------------  -->

            <div class="section-title">2. Personal and Contact Details</div>

            <!-- Personal Details Row - commented as requested -->
            <div class="row">
                <div class="col-md-6">
                    <div class="row">

                        <!-- Date of Birth -->
                        <div class="col-md">
                            <div class="info-group" style="line-height:1.0;">
                                <div class="field-box" style="line-height:1.0;">
                                    <div class="label" style="line-height:1.0;">Date of Birth</div>
                                    <?php echo showVal($studentDetails['dob']); ?>
                                </div>
                            </div>
                        </div>

                        <!-- Gender -->
                        <div class="col-md">
                            <div class="info-group" style="line-height:1.0;">
                                <div class="field-box" style="line-height:1.0;">
                                    <div class="label" style="line-height:1.0;">Gender</div>
                                    <?php echo showVal($studentDetails['gender']); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="row">

                        <!-- Nationality -->
                        <div class="col-md">
                            <div class="info-group" style="line-height:1.0;">
                                <div class="field-box" style="line-height:1.0; font-size: 12px;">
                                    <div class="label" style="line-height:1.0;">Nationality</div>
                                    <?php echo showVal($studentDetails['nationality']); ?>
                                </div>
                            </div>
                        </div>

                        <!-- NIC OR PASSPORT (National ID) -->
                        <?php if (!empty($studentDetails['nic']) || !empty($studentDetails['passport'])): ?>
                            <div class="col-md">
                                <div class="info-group" style="line-height:1.0;">
                                    <div class="field-box" style="line-height:1.0; font-size: 12px;">
                                        <div class="label" style="line-height:1.0;">NIC OR PASSPORT</div>
                                        <?php
                                        $nic = $studentDetails['nic'] ?? '';
                                        $passport = $studentDetails['passport'] ?? '';
                                        $values = [];
                                        if (!empty($nic))
                                            $values[] = $nic;
                                        if (!empty($passport))
                                            $values[] = $passport;
                                        echo showVal(implode(' / ', $values));
                                        ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <!-- Passport Number -->
                        <?php if (!empty($studentDetails['passport'])): ?>
                            <div class="col-md">
                                <div class="info-group" style="line-height:1.0;">
                                    <div class="label" style="line-height:1.0;">Passport</div>
                                    <div class="field-box" style="line-height:1.0;">
                                        <?php echo showVal($studentDetails['passport']); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Contact Numbers Section -->
                <div class="col-md-6">
                    <!-- Mobile Number -->
                    <div class="info-group d-flex align-items-center mb-2">
                        <div class="label flex-shrink-0" style="min-width: 140px;">Mobile</div>
                        <div class="field-box flex-grow-1"><?php echo showVal($studentDetails['mobile']); ?></div>
                    </div>
                    <!-- Home Number -->
                    <div class="info-group d-flex align-items-center mb-2">
                        <div class="label flex-shrink-0" style="min-width: 140px;">Home Number</div>
                        <div class="field-box flex-grow-1"><?php echo showVal($studentDetails['home_number']); ?></div>
                    </div>
                    <!-- Office Number -->
                    <div class="info-group d-flex align-items-center mb-2">
                        <div class="label flex-shrink-0" style="min-width: 140px;">Office Number</div>
                        <div class="field-box flex-grow-1"><?php echo showVal($studentDetails['office_number']); ?>
                        </div>
                    </div>
                </div>

                <!-- End of Contact Numbers Section -->
                <!-- Contact Email and Emergency Contact Section -->
                <div class="col-md-6">
                    <!-- Student Email -->
                    <div class="info-group">
                        <div class="field-box">
                            <div class="label">Email</div>
                            <?php
                            // Display the student's email address
                            echo showVal($studentDetails['email']);
                            ?>
                        </div>
                    </div>

                    <!-- Emergency Contact -->
                    <div class="info-group">
                        <div class="field-box">
                            <div class="label">Emergency Contact</div>
                            <?php
                            // Display the student's emergency contact
                            echo showVal($studentDetails['emergency_contact']);
                            ?>
                        </div>
                    </div>

                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="info-group">
                        <div class="field-box">
                            <div class="label">Permanent Address</div>
                            <?php echo showVal($studentDetails['permanent_address']); ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="info-group">
                        <div class="field-box">
                            <div class="label">Current Address</div>
                            <?php echo showVal($studentDetails['current_address']); ?>
                        </div>
                    </div>
                </div>

            </div>

            <div class="section-title" style="display: none;">Programme Information</div>
            <div class="row" style="display: none;">
                <div class="col-md-6">
                    <div class="info-group">
                        <div class="label">Programme</div>
                        <div class="field-box"><?php echo showVal($studentDetails['program']); ?></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-group">
                        <div class="label">Batch</div>
                        <div class="field-box"><?php echo showVal($batchLabel); ?></div>
                    </div>
                </div>
            </div>

            <!-- payment details hidden here for the security -->
            <?php if ($batchDetails): ?>
                <div class="section-title" style="display: none;">Payment Information</div>
                <div class="row" style="display: none;">
                    <div class="col-md-3">
                        <div class="info-group">
                            <div class="label-small">Course Fee (LKR)</div>
                            <div class="field-box"><?php echo showVal($batchDetails['course_fee_lkr']); ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-group">
                            <div class="label-small">Registration Fee</div>
                            <div class="field-box"><?php echo showVal($batchDetails['registration_fee']); ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-group">
                            <div class="label-small">Installments</div>
                            <div class="field-box"><?php echo showVal($batchDetails['installment_no']); ?></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-group">
                            <div class="label-small">Register Date</div>
                            <div class="field-box"><?php echo showVal($batchDetails['register_date']); ?></div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>

        <!-- ------------------------------- Page 02 --------------------------------------------------------------------------  -->
        <!-- Education and Qualifications Page -->
        <div class="app-card pdf-page new-page-section" id="education-section">
            <div class="section-title">3. Educational Qualifications</div>



            <div class="row hr-section">
                <div class="col-md-7">
                    <?php if (!empty($qualifications['ol'])):
                        $olResults = $qualifications['ol'];
                        $firstOl = $olResults[0];
                        $count = count($olResults);
                        ?>

                        <!-- Header info -->
                        <div class="d-flex justify-content-between mb-1" style="font-size:13px;">
                            <strong>GCE O/L</strong>
                            <span>Year: <?php echo showVal($firstOl['exam_year']); ?></span>
                        </div>

                        <div class="mb-2" style="font-size:13px;">
                            <strong>School:</strong> <?php echo showVal($firstOl['school']); ?>
                        </div>

                        <!-- Results table -->
                        <table class="table-custom table-striped">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Grade</th>
                                    <th>Subject</th>
                                    <th>Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php for ($i = 0; $i < $count; $i += 2):
                                    $left = $olResults[$i];
                                    $right = $olResults[$i + 1] ?? null;
                                    ?>
                                    <tr>
                                        <td><?php echo showVal($left['subject']); ?></td>
                                        <td style="font-weight:600;"><?php echo showVal($left['grade']); ?></td>

                                        <td><?php echo $right ? showVal($right['subject']) : ''; ?></td>
                                        <td style="font-weight:600;"><?php echo $right ? showVal($right['grade']) : ''; ?></td>
                                    </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <div class="col-md-5">

                    <?php if (!empty($qualifications['al'])):
                        $alResults = $qualifications['al'];
                        $firstAl = $alResults[0];
                        $count = count($alResults);
                        ?>

                        <!-- Header info -->
                        <div class="d-flex justify-content-between mb-1" style="font-size:13px;">
                            <strong>GCE A/L</strong>
                            <span>Year: <?php echo showVal($firstAl['exam_year']); ?></span>
                        </div>

                        <div class="mb-2" style="font-size:13px;">
                            <strong>School:</strong> <?php echo showVal($firstAl['school']); ?>
                        </div>

                        <!-- Results table -->
                        <table class="table-custom">
                            <thead>
                                <tr>

                                    <th>Subject</th>
                                    <th style="width:15%;">Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($alResults as $index => $al): ?>
                                    <tr>

                                        <td><?php echo showVal($al['subject']); ?></td>
                                        <td style="font-weight:600; text-align:center;">
                                            <?php echo showVal($al['grade']); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <div class="hr-section">
                <div class="section-title">4. Academic / Professional Qualifications</div>
                <?php if (!empty($qualifications['academic'])): ?>

                    <table class="table-custom">
                        <thead>
                            <tr>
                                <th>Qualification</th>
                                <th>Institution</th>
                                <th>Year</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($qualifications['academic'] as $acad): ?>
                                <tr>
                                    <td><?php echo showVal($acad['qualification']); ?></td>
                                    <td><?php echo showVal($acad['institution']); ?></td>
                                    <td><?php echo showVal($acad['year']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <?php if (!empty($qualifications['other'])): ?>
                    <h5 class="mt-4 mb-2" style="color:#1e40af; font-size:15px; font-weight:600;">Other Qualifications</h5>
                    <div style="background:#f8fafc; border:1px solid #e2e8f0; padding:15px; border-radius:6px;">
                        <?php foreach ($qualifications['other'] as $other): ?>
                            <div style="margin-bottom:8px; padding:8px; background:white; border-radius:4px;">
                                <?php echo showVal($other['details']); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            </div>
        </div>

        <!-- -------------------------------------   Page 03 --------------------------------------------------------------------  -->
        <!-- Terms Page -->
        <div class="app-card pdf-page new-page-section" id="terms-section">
            <div class="section-title">5. Terms and Conditions</div>
            <div class="mb-3">
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; padding: 16px;">
                    <p style="font-size: 12px; margin-bottom: 8px; color: #64748b;">Please read and understand the
                        following terms:</p>
                    <ul style="font-size: 12px; margin-bottom: 12px;">
                        <li>Course fees paid are not refundable under any circumstances.</li>
                        <li>Course fee may be transferred, under special circumstances, from one course to another in
                            favour of the same student.</li>
                        <li>The Management reserves the right to alter the timetable at any time after the commencement
                            of the course.</li>
                        <li>Students must abide by the Student Charter, regulations, rules and dress code of BMS.</li>
                        <li>Student exam admission and/or results may be withheld for non-payment of the course fee
                            installment on due date.</li>
                        <li>The qualification can only be awarded after all assessment requirements have been met and
                            all fees have been paid to BMS.</li>
                    </ul>
                    <div
                        style="background: #e0f2fe; border: 1px solid #bae6fd; padding: 10px; border-radius: 3px; font-size: 12px;">
                        I confirm that the information given in this form is correct and complete. I have read and
                        understood the terms and conditions and agreed to abide by the terms and conditions set out
                        above, which I accept as conditions of this application.
                    </div>
                </div>
            </div>

            <!-- Signature and Date Section -->
            <div class="row mt-5">
                <div class="col-md-6">
                    <div style="border-top: 1px solid #000; width: 80%; padding-top: 10px;">
                        <div class="label-small">Student Signature</div>
                        <div class="field-box" style="height: 40px;"></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div style="border-top: 1px solid #000; width: 80%; padding-top: 10px;">
                        <div class="label-small">Date</div>
                        <div class="field-box" style="height: 40px;"><?php echo date('Y-m-d'); ?></div>
                    </div>
                </div>
            </div>
            <!-- <div class="text-center mt-4" style="border-top:1px dashed #ccc; padding-top:0px; font-size:5px; color:#666;"> -->
            <!-- Page 1 of 3 -->
            <!-- </div> -->

            <!-- ---------------------------------------------------------------------------------------------------------  -->
            <!-- Course Fee Payment Schedule (office use only) -->
            <div class="app-cards pdf-page new-page-section" id="payment-schedule-section"
                style="margin-top: 50px; display: none;">
                <div class="section-title" style="margin-bottom:16px;">Course Fee Payment Schedule <span
                        style="font-size:13px;">(office use only)</span></div>
                <table class="table table-bordered" style="width:100%; font-size:13px; background:#f8fafc;">

                    <thead>
                        <tr style="background:#e4e7ec;">
                            <th style="width:25%; text-align:center;">Date</th>
                            <th style="width:25%; text-align:center;">Amount</th>
                            <th style="width:25%; text-align:center;">Reference</th>
                            <th style="width:25%; text-align:center;">Remarks</th>
                        </tr>
                    </thead>


                    <tbody>
                        <!-- Registration fee row -->
                        <tr style="height:38px;">
                            <td style="text-align:center;"></td>
                            <td style="text-align:center;"><?php echo showVal($batchDetails['registration_fee']); ?>
                            </td>
                            <td style="text-align:center;">Registration Fee</td>
                            <td style="text-align:center;"></td>
                        </tr>
                        <?php
                        // Calculate installment rows dynamically
                        
                        $courseFee = isset($batchDetails['course_fee_lkr']) ? floatval($batchDetails['course_fee_lkr']) : 0.0;
                        $registrationFee = isset($batchDetails['registration_fee']) ? floatval($batchDetails['registration_fee']) : 0.0;
                        $installments = isset($batchDetails['installment_no']) ? intval($batchDetails['installment_no']) : 0;
                        $courseFeeOnly = $courseFee - $registrationFee;
                        $installmentAmt = ($installments > 0) ? round($courseFeeOnly / $installments, 2) : 0.00;
                        $maxRows = $installments; // Only as many rows as installments (plus reg row)
                        
                        // Installment rows (start from the 2nd row)
                        for ($i = 1; $i <= $installments; $i++):
                            ?>
                            <tr style="height:38px;">
                                <td style="text-align:center;"></td>
                                <td style="text-align:center;">
                                    <?php echo $installmentAmt > 0 ? number_format($installmentAmt, 2) : '-'; ?>
                                </td>
                                <td style="text-align:center;">Installment <?php echo $i; ?></td>
                                <td style="text-align:center;"></td>
                            </tr>
                        <?php endfor; ?>
                    </tbody>

                </table>
            </div>
        </div>

        <!-- ---------------------------------------- Page 04  -----------------------------------------------------------------  -->
        <div class="app-card pdf-page new-page-section" id="student-charter-section">
            <div class="section-title">BMS Student Charter</div>
            <h4 class="text-center">BMS Student Charter</h4>
            <div style="font-size:15px; line-height:1.8; margin-top: 14px; margin-bottom: 18px; text-align: justify;">
                BMS is committed to providing a quality education and associated facilities to its students,
                while taking every measure to improve them continuously to meet the requirements of
                the day. Our commitment to quality services is matched by a series of obligations on the
                part of the students. This Charter outlines what we provide to students and what we
                expect of the students.
            </div>
            <div class="row" style="margin-top:28px;">
                <div class="col-md-6">
                    <div style="font-weight:600; font-size:16px; margin-bottom:8px;">You can expect us to provide:</div>
                    <ul style="font-size:15px; line-height:1.8; list-style-type:square; padding-left: 20px;">
                        <li>Quality learning environment</li>
                        <li>Appropriate learning resources</li>
                        <li>Fair admissions procedure</li>
                        <li>Trained and qualified staff</li>
                        <li>Full induction process</li>
                        <li>Timely feedback on your assessment</li>
                        <li>Continuous evaluation of teaching quality</li>
                        <li>Support you to complete your studies</li>
                        <li>Commitment to promote equality</li>
                        <li>Your right to confidentiality</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <div style="font-weight:600; font-size:16px; margin-bottom:8px;">You are expected to:</div>
                    <ul style="font-size:15px; line-height:1.8; list-style-type:square; padding-left: 20px;">
                        <li>Provide correct entry information</li>
                        <li>Use resources in a responsible manner</li>
                        <li>Take responsibility for your learning</li>
                        <li>Respect your fellow students and staff</li>
                        <li>Attend academic sessions regularly</li>
                        <li>Submit your work on time</li>
                        <li>Involve in student activities</li>
                        <li>Behave in a professional manner</li>
                        <li>Conform to the code of conduct</li>
                        <li>Pay all fees on time</li>
                    </ul>
                </div>
            </div>
            <table style="width:100%; margin-top:60px; border-collapse:collapse;">
                <tr>
                    <td style="width:50%; text-align:center; padding-top:0;">
                        <div style="height:48px;"></div>
                        <div
                            style="font-size:15px; font-weight:600; border-top:1px solid #222; display:inline-block; min-width:180px; padding-top:6px;">
                            Academic Registrar
                        </div>
                    </td>
                    <td style="width:50%; text-align:center; padding-top:0;">
                        <div style="height:48px;"></div>
                        <div
                            style="font-size:15px; font-weight:600; border-top:1px solid #222; display:inline-block; min-width:180px; padding-top:6px;">
                            Student
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Buttons -->
        <!-- Buttons: Fixed at Top Right -->
        <div class="bottom-print-btn-area no-print"
            style="position: fixed; top: 10px; right: 20px; z-index: 1000; background: transparent; box-shadow: none; padding: 0; margin: 0;">
            <button type="button" class="btn btn-success btn-lg" id="allocate_btn"
                style="box-shadow: 0 4px 12px rgba(0,0,0,0.3);">
                <i class="bi bi-check2-circle"></i> Allocate Student
            </button>
        </div>

    </div>

    <!-- --------------------------------------   -->
    <!-- Offer letter Form here  -->
    <div class="app-card pdf-page new-page-section" id="offer-letter-section">
        <!-- Header with Logo -->
        <div class="header-section" style="border-bottom:none; margin-bottom:10px; padding-bottom:0; text-align:left;">
            <img src="admin/uploads/company_profiles/bmslogo.png" alt="BMS Logo" style="height: 60px;">
        </div>

        <!-- Date -->
        <p style="margin-bottom: 20px; font-size: 14px;"><?php echo date('d F Y'); ?></p>

        <!-- Address Block -->
        <!-- Address Block -->
        <div style="margin-bottom: 20px; font-size: 14px;">
            <strong><?php echo htmlspecialchars($studentDetails['fullname']); ?></strong><br>
            <?php
            $address = htmlspecialchars($studentDetails['permanent_address']);
            $address = preg_replace('/,\s*/', ',<br>', $address);
            echo nl2br($address);
            ?>
        </div>

        <!-- Salutation -->
        <p style="margin-bottom: 20px; font-size: 14px;">
            Dear <?php echo htmlspecialchars($studentDetails['firstname']); ?>,
        </p>

        <!-- Title -->
        <h4
            style="text-align: left; font-weight: bold; text-decoration: underline; margin-bottom: 15px; font-size: 15px;">
            <?= ($studentDetails['conditional_offer_letter'] ?? 0) ? 'CONDITIONAL' : 'UNCONDITIONAL' ?> OFFER -
            <?= strtoupper(htmlspecialchars($studentDetails['program'])); ?>
        </h4>

        <!-- Program Reference -->
        <!-- <h5 style="text-align: ; font-weight: bold; margin-bottom: 25px; color: #000; font-size: 16px;">
            <?php echo htmlspecialchars($studentDetails['program']); ?>
        </h5> -->

        <!-- Body text -->
        <p style="text-align: justify; margin-bottom: 20px; font-size: 14px;">
            Thank you for your application for admission to Business Management School. I am pleased to offer you a
            <strong><?= ($studentDetails['conditional_offer_letter'] ?? 0) ? 'conditional' : 'unconditional' ?></strong>
            place on the <?= strtolower(htmlspecialchars($attendance)) ?> taught programme specified above. Details of
            your programme, important dates, fees
            and cost are as follows:
        </p>

        <!-- Details Table -->
        <table class="table table-bordered" style="font-size: 14px; margin-bottom: 20px; width: 100%;">
            <tbody>
                <tr>
                    <td style="width: 40%; font-weight: 600; background-color: #f8fafc;">Duration</td>
                    <td><?php echo showVal($programDuration); ?></td>
                </tr>
                <tr>
                    <td style="font-weight: 600; background-color: #f8fafc;">Student NIC</td>
                    <td>
                        <?php echo showVal($studentDetails['nic'] ?? '-'); ?>
                    </td>
                </tr>
                <tr>
                    <td style="font-weight: 600; background-color: #f8fafc;">Programme Intake</td>
                    <td><?php echo showVal(date('F', mktime(0, 0, 0, $intakeNo, 1)) . ' ' . '20' . $yearNo); ?></td>
                </tr>

                <tr>
                    <td style="font-weight: 600; background-color: #f8fafc;">Attendance</td>
                    <td><?php echo showVal($attendance); ?></td>
                </tr>
                <tr>
                    <td style="font-weight: 600; background-color: #f8fafc;">Awarded by</td>
                    <td><?php echo $awardedBy ?></td>
                </tr>

                <?php
                $currentProgram = $studentDetails['program'];

                // 1. Executive Certificate in Management -> Recognized By Only
                $showRecognizedBy = ($currentProgram === 'Executive Certificate in Management');

                // 2. Higher Diplomas -> Accredited By
                $showAccreditedBy = in_array($currentProgram, [
                    'Higher Diploma in Biomedical Science',
                    'Higher Diploma in Biotechnology'
                ]);

                // 3. Specific programs -> Qualification Level
                $showQualificationLevel = in_array($currentProgram, [
                    'Graduate Diploma in Management (Level 6)',
                    'International Foundation Diploma (Applied Science) - ATHE Level 3',
                    'International Foundation Diploma (Business) - ATHE Level 3',
                    'BTEC Higher National Diploma in Business'
                ]);
                ?>

                <?php if ($showRecognizedBy): ?>
                    <tr>
                        <td style="font-weight: 600; background-color: #f8fafc;">Recognized By</td>
                        <td><?php echo showVal($recognizedBy); ?></td>
                    </tr>
                <?php endif; ?>

                <?php if ($showAccreditedBy): ?>
                    <tr>
                        <td style="font-weight: 600; background-color: #f8fafc;">Accredited By</td>
                        <td><?php echo showVal($accreditedBy); ?></td>
                    </tr>
                <?php endif; ?>

                <?php if ($showQualificationLevel): ?>
                    <tr>
                        <td style="font-weight: 600; background-color: #f8fafc;">Qualification Level</td>
                        <td><?php echo $qualificationLevel; ?></td>
                    </tr>
                <?php endif; ?>

                <tr>
                    <td style="font-weight: 600; background-color: #f8fafc;">Programme Fee</td>
                    <td>
                        LKR <?php echo number_format($programFeeLKR); ?>
                        <?php
                        if ($programFeeGBP > 0)
                            echo ' + GBP ' . number_format($programFeeGBP);
                        elseif ($programFeeUSD > 0)
                            echo ' + USD ' . number_format($programFeeUSD);
                        elseif ($programFeeEuro > 0)
                            echo ' + EURO ' . number_format($programFeeEuro);
                        ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <?php if ($studentDetails['conditional_offer_letter'] ?? 0): ?>
            <?php
            $all_conditions = [];
            if (!empty($studentDetails['conditional_offer_letter_text']))
                $all_conditions[] = $studentDetails['conditional_offer_letter_text'];
            if (!empty($studentDetails['conditional_offer_letter_text_02']))
                $all_conditions[] = $studentDetails['conditional_offer_letter_text_02'];
            if (!empty($studentDetails['conditional_offer_letter_text_03']))
                $all_conditions[] = $studentDetails['conditional_offer_letter_text_03'];
            if (!empty($studentDetails['conditional_offer_letter_text_04']))
                $all_conditions[] = $studentDetails['conditional_offer_letter_text_04'];
            ?>
            <?php if (!empty($all_conditions)): ?>
                <div style="margin-bottom: 20px; font-size: 14px;">
                    <p style="text-align: justify; margin-bottom: 5px; font-style: italics;">
                        <strong>CONDITIONS</strong>
                    </p>
                    <ul style="margin-top: 0; padding-left: 20px; font-style: italics;">
                        <?php foreach ($all_conditions as $cond): ?>
                            <li style="text-align: justify;"><?= htmlspecialchars($cond) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <p style="text-align: justify; margin-bottom: 10px; font-size: 14px;">
            A minimum payment of LKR
            <?php echo number_format($batchDetails['registration_fee'] ?? 0.00); ?> is required to confirm a place on
            the programme. Fees can be paid in full at
            the time of your enrolment; alternatively, you can obtain a payment plan from BMS Finance Department.
        </p>

        <p style="text-align: justify; margin-bottom: 10px; font-size: 14px;">
            This offer has been issued in accordance with the regulations set out by Business Management School and is
            subject to the terms and conditions agreed and stipulated in the relevant documents.
        </p>

        <p style="text-align: justify; margin-bottom: 40px; font-size: 14px;">
            You shall accept the offer on or before <strong><?php echo date('d F Y', strtotime('+3 weeks')); ?></strong>
            with the payment of the first instalment.
        </p>

        <!-- Sign off -->
        <div style="margin-top: 20px; font-size: 14px;">
            <p style="margin-bottom: 20px;">With best wishes,</p>
            <p style="font-weight: bold;">Academic Registrar,<br>BMS</p>
        </div>

    </div>
    <!-- --------------------------------------   -->

    <!-- JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <script>
        function printAppCardOnly() {
            window.print();
        }

        document.getElementById('allocate_btn').addEventListener('click', async function () {
            // Show loading message
            Swal.fire({
                title: 'Allocating Student and Generating PDF...',
                text: 'Please wait while we process your request',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            try {
                // Generate high-quality PDF with proper page breaks
                const pdf = await generateHighQualityPDF();

                // Prepare FormData for allocation
                const formData = new FormData();
                formData.append('temp_registration_id', <?php echo $student_id; ?>);
                formData.append('selected_batch_id', <?php echo $selected_batch_id; ?>);

                // Send allocation request to server
                const response = await fetch('allocate_student.php', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                const data = await response.json();

                if (data.success) {
                    // Convert PDF to blob for upload
                    const pdfBlob = pdf.output('blob');
                    <?php
                    $nicUser = trim($_POST['nic'] ?? $studentDetails['nic'] ?? '');
                    $passportUser = trim($_POST['passport'] ?? $studentDetails['passport'] ?? '');
                    $identification = !empty($nicUser) ? $nicUser : $passportUser;
                    $cleanIdent = preg_replace('/[^A-Za-z0-9_\-]/', '_', $identification);
                    ?>
                    const pdfFilename = 'Application_' + data.student_code + '_<?php echo htmlspecialchars($studentDetails['firstname'] . '_' . $studentDetails['lastname']); ?>' + '<?= !empty($cleanIdent) ? "_" . $cleanIdent : "" ?>' + '.pdf';

                    // Create FormData for PDF upload
                    const pdfFormData = new FormData();
                    pdfFormData.append('pdf_file', pdfBlob, pdfFilename);
                    pdfFormData.append('student_code', data.student_code);
                    pdfFormData.append('temp_id', '<?php echo $temporaryId; ?>');
                    pdfFormData.append('student_name', '<?php echo htmlspecialchars($studentDetails['firstname'] . ' ' . $studentDetails['lastname']); ?>');
                    pdfFormData.append('identification', '<?= $cleanIdent ?>');
                    pdfFormData.append('program', '<?php echo htmlspecialchars($studentDetails['program'] ?? ''); ?>');
                    pdfFormData.append('batch', '<?php echo htmlspecialchars($batchName); ?>');

                    // Upload PDF to server
                    const uploadResponse = await fetch('save_application_pdf.php', {
                        method: 'POST',
                        body: pdfFormData
                    });

                    const uploadResult = await uploadResponse.json();

                    // Download the Application PDF to user's computer
                    pdf.save(pdfFilename);

                    // Show success message with allocation details
                    Swal.fire({
                        title: 'Success!',
                        html: `
                            <div style="text-align: left;">
                                <p><strong>✅ Student allocated successfully!</strong></p>
                                <hr>
                                <p><strong>Student Code:</strong> ${data.student_code}</p>
                                <p><strong>Name:</strong> ${data.data.student_name}</p>
                                <p><strong>Programme:</strong> ${data.data.programme}</p>
                                <p><strong>Batch:</strong> ${data.data.batch}</p>
                                <hr>
                                <p style="color: #10b981;">✓ ${data.message}</p>
                            </div>
                        `,
                        icon: 'success',
                        confirmButtonColor: '#3b82f6',
                        confirmButtonText: 'OK'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Redirect to registration data page or refresh
                            window.location.href = 'online_registration_data.php';
                        } else {
                            window.location.href = 'online_registration_data.php';

                        }
                    });
                } else {
                    // Show error message from server
                    Swal.fire({
                        icon: 'error',
                        title: 'Allocation Failed',
                        text: data.message || 'Failed to allocate student. Please try again.',
                        confirmButtonColor: '#ef4444'
                    });
                }

            } catch (error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred: ' + error.message,
                    confirmButtonColor: '#ef4444'
                });
            }
        });

        // Enhanced PDF generation function with better page breaks and quality
        async function generateHighQualityPDF() {
            const {
                jsPDF
            } = window.jspdf;
            const pdf = new jsPDF('p', 'mm', 'a4');

            // A4 dimensions in mm
            const pageWidth = pdf.internal.pageSize.getWidth();
            const pageHeight = pdf.internal.pageSize.getHeight();
            const margin = 10;
            const contentWidth = pageWidth - (2 * margin);
            const contentHeight = pageHeight - (2 * margin);

            // const sections = ['application-card', 'education-section', 'terms-section', 'student-charter-section', 'offer-letter-section'];
            const sections = ['application-card', 'education-section', 'terms-section', 'student-charter-section'];
            let isFirstPage = true;

            for (let sectionIndex = 0; sectionIndex < sections.length; sectionIndex++) {
                const el = document.getElementById(sections[sectionIndex]);
                if (!el) continue;

                // Higher scale for better quality
                const canvas = await html2canvas(el, {
                    scale: 3, // Increased from 2 to 3 for better quality
                    useCORS: true,
                    allowTaint: true,
                    backgroundColor: '#ffffff',
                    logging: false,
                    windowWidth: el.scrollWidth,
                    windowHeight: el.scrollHeight,
                    onclone: function (clonedDoc) {
                        // Ensure proper styling in cloned document
                        const clonedEl = clonedDoc.getElementById(sections[sectionIndex]);
                        if (clonedEl) {
                            clonedEl.style.width = el.scrollWidth + 'px';
                        }
                    }
                });

                const imgData = canvas.toDataURL('image/jpeg', 0.95); // JPEG with 95% quality

                // Calculate aspect ratio
                const imgWidth = contentWidth;
                const imgHeight = (canvas.height * imgWidth) / canvas.width;

                // Split content if it exceeds page height
                if (imgHeight <= contentHeight) {
                    // Content fits on one page
                    if (!isFirstPage) {
                        pdf.addPage();
                    }
                    pdf.addImage(imgData, 'JPEG', margin, margin, imgWidth, imgHeight);
                    isFirstPage = false;
                } else {
                    // Content needs multiple pages - split intelligently
                    let currentY = 0;
                    const totalHeight = imgHeight;

                    while (currentY < totalHeight) {
                        if (!isFirstPage) {
                            pdf.addPage();
                        }

                        // Calculate remaining height
                        const remainingHeight = totalHeight - currentY;
                        const pageContentHeight = Math.min(contentHeight, remainingHeight);

                        // Calculate source dimensions from canvas
                        const sourceY = (currentY / imgWidth) * canvas.width;
                        const sourceHeight = (pageContentHeight / imgWidth) * canvas.width;

                        // Create temporary canvas for this page slice
                        const tempCanvas = document.createElement('canvas');
                        tempCanvas.width = canvas.width;
                        tempCanvas.height = Math.min(sourceHeight, canvas.height - sourceY);
                        const tempCtx = tempCanvas.getContext('2d');

                        // Fill with white background
                        tempCtx.fillStyle = '#ffffff';
                        tempCtx.fillRect(0, 0, tempCanvas.width, tempCanvas.height);

                        // Draw the slice from original canvas
                        tempCtx.drawImage(
                            canvas,
                            0, sourceY,
                            canvas.width, tempCanvas.height,
                            0, 0,
                            canvas.width, tempCanvas.height
                        );

                        // Add to PDF
                        const sliceData = tempCanvas.toDataURL('image/jpeg', 0.95);
                        pdf.addImage(sliceData, 'JPEG', margin, margin, imgWidth, pageContentHeight);

                        currentY += pageContentHeight;
                        isFirstPage = false;
                    }
                }
            }

            return pdf;
        }
    </script>


</body>

</html>