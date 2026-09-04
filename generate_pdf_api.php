<?php
/**
 * API Endpoint for Professional PDF Generation
 * Calls Python backend with ReportLab for high-quality PDFs
 */

session_start();
include("database/connection.php");
require_once("vendor/autoload.php");

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$student_id = isset($_POST['student_id']) ? intval($_POST['student_id']) : 0;
$batch_id = isset($_POST['batch_id']) ? intval($_POST['batch_id']) : 0;

if ($student_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid student ID']);
    exit();
}

try {
    // Fetch student details
    $stmt = $conn->prepare("SELECT * FROM students_temporary_registration WHERE id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $studentDetails = $result->fetch_assoc();
    $stmt->close();

    if (!$studentDetails) {
        throw new Exception('Student not found');
    }

    // Get photo path
    $photoPath = '';
    $temporaryId = $studentDetails['temp_id'] ?? '';

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

                $cleanProgram = preg_replace('/[^A-Za-z0-9_\-]/', '_', $programFolder);
                $cleanBatch = preg_replace('/[^A-Za-z0-9_\-]/', '_', $batchFolder);
                $cleanTempId = preg_replace('/[^A-Za-z0-9_\-]/', '_', $temporaryId);

                if (!empty($doc0File)) {
                    $absolutePath = dirname(__FILE__) . "/uploaded_documents/{$cleanProgram}/{$cleanBatch}/{$cleanTempId}/{$doc0File}";
                    if (file_exists($absolutePath)) {
                        $photoPath = $absolutePath;
                    }
                }
            }
            $photoQ->close();
        }
    }

    // Get batch details
    $batchLabel = '-';
    $batchDetails = null;

    if ($batch_id > 0) {
        $bq = "SELECT id, batch_name, batch_no FROM batch_table WHERE id = ? LIMIT 1";
        $stmt = $conn->prepare($bq);
        $stmt->bind_param('i', $batch_id);
        $stmt->execute();
        $br = $stmt->get_result();
        if ($bRow = $br->fetch_assoc()) {
            $batchLabel = $bRow['batch_name'];
        }
        $stmt->close();

        // Get batch payment details
        $batchDetailsQuery = "SELECT id, programme_id, batch_id, course_fee_lkr, uni_fee_gbp, uni_fee_usd, uni_fee_euro,
                   register_date, installment_no, registration_fee, created_at, only_course_fee
            FROM payment_batch_allocation WHERE batch_id = ? LIMIT 1";
        $stmt = $conn->prepare($batchDetailsQuery);
        $stmt->bind_param('i', $batch_id);
        $stmt->execute();
        $br = $stmt->get_result();
        if ($row = $br->fetch_assoc()) {
            $batchDetails = $row;
        }
        $stmt->close();
    }

    // Get qualifications
    $qualifications = [
        'ol' => [],
        'al' => [],
        'academic' => [],
        'other' => []
    ];

    // O/L Results
    $olQuery = "SELECT subject, grade, exam_year, school FROM student_ol_results WHERE registration_id = ? ORDER BY id";
    $stmt = $conn->prepare($olQuery);
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $olResult = $stmt->get_result();
    while ($row = $olResult->fetch_assoc()) {
        $qualifications['ol'][] = $row;
    }
    $stmt->close();

    // A/L Results
    $alQuery = "SELECT subject, grade, exam_year, school FROM student_al_results WHERE registration_id = ? ORDER BY id";
    $stmt = $conn->prepare($alQuery);
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $alResult = $stmt->get_result();
    while ($row = $alResult->fetch_assoc()) {
        $qualifications['al'][] = $row;
    }
    $stmt->close();

    // Academic Qualifications
    $acadQuery = "SELECT qualification, institution, year FROM student_academic_qualifications WHERE registration_id = ? ORDER BY id";
    $stmt = $conn->prepare($acadQuery);
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $acadResult = $stmt->get_result();
    while ($row = $acadResult->fetch_assoc()) {
        $qualifications['academic'][] = $row;
    }
    $stmt->close();

    // Other Qualifications
    $otherQuery = "SELECT details FROM student_other_qualifications WHERE registration_id = ? ORDER BY id";
    $stmt = $conn->prepare($otherQuery);
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $otherResult = $stmt->get_result();
    while ($row = $otherResult->fetch_assoc()) {
        $qualifications['other'][] = $row;
    }
    $stmt->close();


    // -------------------------------------
    // PDF Generation using TCPDF (Native PHP)
    // -------------------------------------


    // Prepare data for Python script
    $pdfData = [
        'title' => $studentDetails['title'] ?? '',
        'firstname' => $studentDetails['firstname'] ?? '',
        'lastname' => $studentDetails['lastname'] ?? '',
        'fullname' => $studentDetails['fullname'] ?? '',
        'certificate_name' => $studentDetails['certificate_name'] ?? '',
        'dob' => $studentDetails['dob'] ?? '',
        'nationality' => $studentDetails['nationality'] ?? '',
        'gender' => $studentDetails['gender'] ?? '',
        'nic' => $studentDetails['nic'] ?? '',
        'passport' => $studentDetails['passport'] ?? '',
        'permanent_address' => $studentDetails['permanent_address'] ?? '',
        'current_address' => $studentDetails['current_address'] ?? '',
        'mobile' => $studentDetails['mobile'] ?? '',
        'home_number' => $studentDetails['home_number'] ?? '',
        'office_number' => $studentDetails['office_number'] ?? '',
        'email' => $studentDetails['email'] ?? '',
        'emergency_contact' => $studentDetails['emergency_contact'] ?? '',
        'program' => $studentDetails['program'] ?? '',
        'batch' => $batchLabel,
        'photo_path' => $photoPath,
        'batch_details' => $batchDetails,
        'qualifications' => $qualifications,
        'output_filename' => dirname(__FILE__) . '/generated_pdfs/Application_' . $student_id . '_' .
            preg_replace('/[^A-Za-z0-9_\-]/', '_', $studentDetails['firstname']) . '_' .
            preg_replace('/[^A-Za-z0-9_\-]/', '_', $studentDetails['lastname']) . '.pdf'
    ];

    // Create generated_pdfs directory if it doesn't exist
    $pdfDir = __DIR__ . '/generated_pdfs';
    if (!is_dir($pdfDir)) {
        if (!mkdir($pdfDir, 0755, true)) {
            error_log("CRITICAL: Failed to create generated_pdfs directory: " . $pdfDir);
            throw new Exception("PDF directory could not be created. Check permissions.");
        }
    }

    if (!is_writable($pdfDir)) {
        error_log("CRITICAL: generated_pdfs directory is not writable: " . $pdfDir);
        throw new Exception("PDF directory is not writable. Check permissions.");
    }

    $outputFilename = $pdfData['output_filename'];

    try {
        // Create new PDF document
        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

        // Set document information
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('BMS');
        $pdf->SetTitle('Student Application - ' . $pdfData['fullname']);

        // Remove default header/footer
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Set margins
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(TRUE, 20);

        // ========== PAGE 1: Personal Information ==========
        $pdf->AddPage();

        // Header
        $html = '<h1 style="color:#1e40af; text-align:center; font-size:24pt; margin-bottom:5px;">STUDENT APPLICATION FORM</h1>';
        $html .= '<h2 style="color:#64748b; text-align:center; font-size:16pt; margin-bottom:20px;">Registration Details</h2>';

        // Photo (if available)
        if (!empty($photoPath) && file_exists($photoPath)) {
            $html .= '<div style="text-align:center; margin-bottom:20px;"><img src="' . $photoPath . '" height="120" /></div>';
        }

        // Personal Information Section
        $html .= '<div style="background-color:#3b82f6; color:white; padding:8px 10px; font-weight:bold; font-size:14pt; margin-top:15px; margin-bottom:12px;">PERSONAL INFORMATION</div>';

        $html .= '<table border="0" cellpadding="8" style="width:100%; margin-bottom:5px;">';
        $html .= '<tr>';
        $html .= '<td width="33%"><strong style="color:#475569; font-size:10pt;">TITLE</strong><br/><div style="background-color:#f8fafc; border:1px solid #e2e8f0; padding:8px; font-size:11pt;">' . htmlspecialchars($pdfData['title']) . '</div></td>';
        $html .= '<td width="33%"><strong style="color:#475569; font-size:10pt;">FIRST NAME</strong><br/><div style="background-color:#f8fafc; border:1px solid #e2e8f0; padding:8px; font-size:11pt;">' . htmlspecialchars($pdfData['firstname']) . '</div></td>';
        $html .= '<td width="34%"><strong style="color:#475569; font-size:10pt;">LAST NAME</strong><br/><div style="background-color:#f8fafc; border:1px solid #e2e8f0; padding:8px; font-size:11pt;">' . htmlspecialchars($pdfData['lastname']) . '</div></td>';
        $html .= '</tr></table>';

        // Helper function for fields
        $createField = function ($label, $value) {
            return '<div style="margin-bottom:12px;"><strong style="color:#475569; font-size:10pt;">' . strtoupper($label) . '</strong><br/><div style="background-color:#f8fafc; border:1px solid #e2e8f0; padding:8px; font-size:11pt;">' . htmlspecialchars($value ?: '-') . '</div></div>';
        };

        $html .= $createField('Full Name', $pdfData['fullname']);
        $html .= $createField('Name on Certificate', $pdfData['certificate_name']);
        $html .= $createField('Date of Birth', $pdfData['dob']);
        $html .= $createField('Nationality', $pdfData['nationality']);
        $html .= $createField('Gender', $pdfData['gender']);
        $html .= $createField('NIC', $pdfData['nic']);
        $html .= $createField('Passport', $pdfData['passport']);

        // Contact Information Section
        $html .= '<div style="background-color:#3b82f6; color:white; padding:8px 10px; font-weight:bold; font-size:14pt; margin-top:15px; margin-bottom:12px;">CONTACT INFORMATION</div>';

        $html .= $createField('Permanent Address', $pdfData['permanent_address']);
        $html .= $createField('Current Address', $pdfData['current_address']);
        $html .= $createField('Mobile', $pdfData['mobile']);
        $html .= $createField('Home Number', $pdfData['home_number']);
        $html .= $createField('Office Number', $pdfData['office_number']);
        $html .= $createField('Email', $pdfData['email']);
        $html .= $createField('Emergency Contact', $pdfData['emergency_contact']);

        // Programme Information Section
        $html .= '<div style="background-color:#3b82f6; color:white; padding:8px 10px; font-weight:bold; font-size:14pt; margin-top:15px; margin-bottom:12px;">PROGRAMME INFORMATION</div>';

        $html .= $createField('Programme', $pdfData['program']);
        $html .= $createField('Batch', $pdfData['batch']);

        // Payment Information (if available)
        if ($batchDetails) {
            $html .= '<div style="background-color:#3b82f6; color:white; padding:8px 10px; font-weight:bold; font-size:14pt; margin-top:15px; margin-bottom:12px;">PAYMENT INFORMATION</div>';
            $html .= $createField('Course Fee (LKR)', number_format($batchDetails['course_fee_lkr'] ?? 0));
            $html .= $createField('Registration Fee', number_format($batchDetails['registration_fee'] ?? 0));
            $html .= $createField('Installments', $batchDetails['installment_no'] ?? '-');
            $html .= $createField('Register Date', $batchDetails['register_date'] ?? '-');
        }

        $pdf->writeHTML($html, true, false, true, false, '');

        // ========== PAGE 2: Educational Qualifications ==========
        $pdf->AddPage();

        $html = '<div style="background-color:#3b82f6; color:white; padding:8px 10px; font-weight:bold; font-size:14pt; margin-bottom:12px;">EDUCATIONAL QUALIFICATIONS</div>';

        // O/L Results
        if (!empty($qualifications['ol'])) {
            $html .= '<h3 style="font-size:12pt; margin-top:15px; margin-bottom:8px;">O/L Results</h3>';
            $html .= '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse; width:100%; margin-bottom:20px;">';
            $html .= '<tr style="background-color:#3b82f6; color:white; font-weight:bold; font-size:11pt;">';
            $html .= '<td width="35%">Subject</td><td width="15%">Grade</td><td width="15%">Year</td><td width="35%">School</td>';
            $html .= '</tr>';

            $rowBg = true;
            foreach ($qualifications['ol'] as $ol) {
                $bgColor = $rowBg ? '#f8fafc' : '#ffffff';
                $html .= '<tr style="background-color:' . $bgColor . '; font-size:10pt;">';
                $html .= '<td>' . htmlspecialchars($ol['subject'] ?? '') . '</td>';
                $html .= '<td>' . htmlspecialchars($ol['grade'] ?? '') . '</td>';
                $html .= '<td>' . htmlspecialchars($ol['exam_year'] ?? '') . '</td>';
                $html .= '<td>' . htmlspecialchars($ol['school'] ?? '') . '</td>';
                $html .= '</tr>';
                $rowBg = !$rowBg;
            }
            $html .= '</table>';
        }

        // A/L Results
        if (!empty($qualifications['al'])) {
            $html .= '<h3 style="font-size:12pt; margin-top:15px; margin-bottom:8px;">A/L Results</h3>';
            $html .= '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse; width:100%; margin-bottom:20px;">';
            $html .= '<tr style="background-color:#3b82f6; color:white; font-weight:bold; font-size:11pt;">';
            $html .= '<td width="35%">Subject</td><td width="15%">Grade</td><td width="15%">Year</td><td width="35%">School</td>';
            $html .= '</tr>';

            $rowBg = true;
            foreach ($qualifications['al'] as $al) {
                $bgColor = $rowBg ? '#f8fafc' : '#ffffff';
                $html .= '<tr style="background-color:' . $bgColor . '; font-size:10pt;">';
                $html .= '<td>' . htmlspecialchars($al['subject'] ?? '') . '</td>';
                $html .= '<td>' . htmlspecialchars($al['grade'] ?? '') . '</td>';
                $html .= '<td>' . htmlspecialchars($al['exam_year'] ?? '') . '</td>';
                $html .= '<td>' . htmlspecialchars($al['school'] ?? '') . '</td>';
                $html .= '</tr>';
                $rowBg = !$rowBg;
            }
            $html .= '</table>';
        }

        // Academic Qualifications
        if (!empty($qualifications['academic'])) {
            $html .= '<h3 style="font-size:12pt; margin-top:15px; margin-bottom:8px;">Academic Qualifications</h3>';
            $html .= '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse; width:100%; margin-bottom:20px;">';
            $html .= '<tr style="background-color:#3b82f6; color:white; font-weight:bold; font-size:11pt;">';
            $html .= '<td width="40%">Qualification</td><td width="45%">Institution</td><td width="15%">Year</td>';
            $html .= '</tr>';

            $rowBg = true;
            foreach ($qualifications['academic'] as $acad) {
                $bgColor = $rowBg ? '#f8fafc' : '#ffffff';
                $html .= '<tr style="background-color:' . $bgColor . '; font-size:10pt;">';
                $html .= '<td>' . htmlspecialchars($acad['qualification'] ?? '') . '</td>';
                $html .= '<td>' . htmlspecialchars($acad['institution'] ?? '') . '</td>';
                $html .= '<td>' . htmlspecialchars($acad['year'] ?? '') . '</td>';
                $html .= '</tr>';
                $rowBg = !$rowBg;
            }
            $html .= '</table>';
        }

        // Other Qualifications
        if (!empty($qualifications['other'])) {
            $html .= '<h3 style="font-size:12pt; margin-top:15px; margin-bottom:8px;">Other Qualifications</h3>';
            foreach ($qualifications['other'] as $other) {
                $html .= '<div style="background-color:#f8fafc; border:1px solid #e2e8f0; padding:8px; margin-bottom:8px; font-size:10pt;">';
                $html .= htmlspecialchars($other['details'] ?? '');
                $html .= '</div>';
            }
        }

        $pdf->writeHTML($html, true, false, true, false, '');

        // ========== PAGE 3: Terms and Conditions ==========
        $pdf->AddPage();

        $html = '<div style="background-color:#3b82f6; color:white; padding:8px 10px; font-weight:bold; font-size:14pt; margin-bottom:12px;">TERMS AND CONDITIONS</div>';

        $html .= '<p style="font-size:10pt; margin-bottom:8px;">Please read and understand the following terms:</p>';

        $terms = [
            "Course fees paid are not refundable under any circumstances.",
            "Course fee may be transferred, under special circumstances, from one course to another in favour of the same student.",
            "The Management reserves the right to alter the timetable at any time after the commencement of the course.",
            "Students must abide by the Student Charter, regulations, rules and dress code of BMS.",
            "Student exam admission and/or results may be withheld for non-payment of the course fee installment on due date.",
            "The qualification can only be awarded after all assessment requirements have been met and all fees have been paid to BMS."
        ];

        foreach ($terms as $term) {
            $html .= '<p style="font-size:10pt; margin-left:15px; margin-bottom:6px;">• ' . htmlspecialchars($term) . '</p>';
        }

        $html .= '<div style="background-color:#e0f2fe; border:1px solid #bae6fd; padding:10px; margin-top:20px; margin-bottom:30px; font-size:10pt;">';
        $html .= 'I confirm that the information given in this form is correct and complete. ';
        $html .= 'I have read and understood the terms and conditions and agreed to abide by ';
        $html .= 'the terms and conditions set out above, which I accept as conditions of this application.';
        $html .= '</div>';

        $html .= '<table border="0" cellpadding="0" style="width:100%; margin-top:40px;">';
        $html .= '<tr>';
        $html .= '<td width="50%" style="font-size:10pt;">Student Signature: ___________________________</td>';
        $html .= '<td width="50%" style="text-align:right; font-size:10pt;">Date: ' . date('Y-m-d') . '</td>';
        $html .= '</tr>';
        $html .= '</table>';

        $pdf->writeHTML($html, true, false, true, false, '');

        // Output PDF to file
        $pdf->Output($outputFilename, 'F');

        // Return success with PDF path
        $pdfUrl = 'generated_pdfs/' . basename($outputFilename);

        echo json_encode([
            'success' => true,
            'message' => 'PDF generated successfully',
            'pdf_url' => $pdfUrl,
            'pdf_path' => $outputFilename,
            'student_data' => [
                'student_id' => $student_id,
                'name' => $studentDetails['firstname'] . ' ' . $studentDetails['lastname'],
                'programme' => $studentDetails['program'],
                'batch' => $batchLabel
            ]
        ]);

    } catch (Exception $e) {
        error_log("TCPDF Student Application Error: " . $e->getMessage());
        throw new Exception('PDF generation failed: ' . $e->getMessage());
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>